<?php

namespace Drupal\Tangler;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Composer\Installer\InstallationManager;
use Composer\Repository\RepositoryManager;

class Mapper
{
    private $fs     = false;
    private $finder = false;
    private $root   = false;
    private $name   = false;

    public function __construct($path, $drupal, $copy = false)
    {
        $this->root   = rtrim($path, DIRECTORY_SEPARATOR);
        $this->drupal = rtrim($drupal, DIRECTORY_SEPARATOR);
        $this->copy   = $copy;
    }

    private function getFS()
    {
        return $this->fs ? $this->fs : $this->fs = new Filesystem();
    }

    public function getRoot()
    {
        return $this->root;
    }

    private function getFinder()
    {
        return new Finder();
    }

    public function getMap(InstallationManager $im, RepositoryManager $rm)
    {
        return array_merge(
            $this->mapContrib($im, $rm),
            $this->mapByType('module'),
            $this->mapByType('theme'),
            $this->mapCustom(),
            $this->mapSettings(),
            $this->mapVendor(),
            $this->mapFiles()
        );
    }

    public function mapContrib(InstallationManager $im, RepositoryManager $rm)
    {
        $typePathMap = $this->getTypePathMap();
        $typeInstallMap = [];
        $packages = $rm->getLocalRepository()
            ->getCanonicalPackages();
        foreach ($packages as $package) {
            if ($drupalType = $this->getDrupalType($package)) {

                if (!isset($typePathMap[$drupalType])) {
                  continue;
                }

                $installPath = self::changeSlashes($im->getInstaller($package->getType())
                    ->getInstallPath($package));
                if (strpos($installPath, $root = $this->getRoot()) !== false) {
                    $installPath = self::changeSlashes($this->getFS()->makePathRelative(
                        $installPath,
                        $root
                    ));
                }
                $name = explode('/', $package->getPrettyName())[1];
                $mapRef =& $typeInstallMap[$drupalType][rtrim($installPath, DIRECTORY_SEPARATOR)] ;
                if (in_array($drupalType, ['module', 'theme'])) {
                    $mapRef = sprintf(
                        $typePathMap[$drupalType] . DIRECTORY_SEPARATOR . '%s',
                        'contrib',
                        $name
                    );
                }
                else {
                    $mapRef = sprintf($typePathMap[$drupalType], $name);
                }
            }
        }
        return array_intersect_key($typeInstallMap, $typePathMap);
    }

    private function getCustomFilesFinder()
    {
        $finder = $this->getFinder()
            ->ignoreUnreadableDirs()
            ->ignoreVCS(true)
            ->exclude(['vendor', 'www', 'cnf'])
            ->depth('== 0')
            ->in($this->getRoot())
            ->name("*.php")
            ->name("*.inc")
            ->name("*.module")
            ->name("*.info")
            ->name("*.install")
            ->name('src')
            ->name('lib');
        return $finder;
    }

    private function getTypeFinder($type)
    {
        if (file_exists($dir = $this->getRoot() . DIRECTORY_SEPARATOR . "{$type}s")) {
            $finder = $this->getFinder()
                ->ignoreUnreadableDirs()
                ->depth('== 0')
                ->in($dir);
            return $finder;
        }
        return [];
    }

    private function getName()
    {
        if (!$this->name) {
            foreach ($this->getCustomFilesFinder() as $file) {
                if (preg_match('/(.*)\.info(\.yml)?/', $file->getFilename(), $matches)) {
                    $this->name = $matches[1];
                }
            }
        }
        return $this->name;
    }

    public function mapCustom()
    {
        $root   = $this->getRoot();
        $fs     = $this->getFS();
        $paths  = [];
        if ($name = $this->getName()) {
            foreach ($this->getCustomFilesFinder() as $file) {
                $install = rtrim(self::changeSlashes($fs->makePathRelative($file->getRealpath(), $root)), DIRECTORY_SEPARATOR);
                $paths["custom"][$install] = sprintf(
                    $this->getTypePathMap('module') . DIRECTORY_SEPARATOR . '%s',
                    $name,
                    $file->getFilename()
                );
            }
        }
        return $paths;
    }

    public function mapFiles()
    {
        return [
            'files' => [
                self::changeSlashes('cnf/files') => $this->drupal . self::changeSlashes('/sites/default/files'),
                self::changeSlashes('cnf/translations') => $this->drupal . self::changeSlashes('/sites/all/translations'),
            ]
        ];
    }

    public function mapSettings()
    {
        return [
            'settings' => [self::changeSlashes('cnf/settings.php') => $this->drupal . self::changeSlashes('/sites/default/settings.php')]
        ];
    }

    public function mapVendor()
    {
        return [
            'vendor' => ['vendor' => $this->drupal . self::changeSlashes('/sites/default/vendor')]
        ];
    }

    public function mapByType($type)
    {
        $paths  = [];
        $root   = $this->getRoot();
        $fs     = $this->getFS();
        foreach ($this->getTypeFinder($type) as $file) {
            $install = rtrim(self::changeSlashes($fs->makePathRelative($file->getRealpath(), $root)), DIRECTORY_SEPARATOR);
            $paths["{$type}s"][$install] = sprintf(
                $this->getTypePathMap($type),
                $file->getFilename()
            );
        }
        return $paths;
    }

    public function mirror($map)
    {
        var_dump($map);
        $fs = $this->getFS();
        $root = $this->getRoot();
        foreach ($map as $type => $pathMap) {
            foreach ($pathMap as $installPath => $targetPath) {
                $installPath = $fs->isAbsolutePath($installPath)? $installPath : $root . DIRECTORY_SEPARATOR . $installPath;
                if ($fs->exists($installPath)) {
                    if ($type === 'core') {
                        $fs->mirror($installPath, $targetPath);
                    }
                    elseif ($this->copy == true) {
                        if (is_dir($installPath)) {
                            $fs->mirror(
                                $installPath,
                                $targetPath
                            );
                        }
                        else {
                            $fs->copy(
                                $installPath,
                                $targetPath
                            );
                        }
                    }
                    else {
                        $fs->symlink(
                            rtrim(substr($fs->makePathRelative(
                                $installPath,
                                $targetPath
                            ), 3), DIRECTORY_SEPARATOR),
                            $targetPath,
                            true
                        );
                    }
                }
            }
        }
    }

    public function clear()
    {
        $this->getFS()->remove(['directory', $this->getTypePathMap()['core']]);
    }

    public function getTypePathMap($type = null)
    {
        $map = [
            'core'    => $this->drupal,
            'module'  => $this->drupal . self::changeSlashes('/sites/all/modules/%s'),
            'theme'   => $this->drupal . self::changeSlashes('/sites/all/themes/%s'),
            'engine'  => $this->drupal . self::changeSlashes('/sites/all/themes/engines/%s'),
            'drush'   => $this->drupal . self::changeSlashes('/sites/all/drush/%s'),
            'profile' => $this->drupal . self::changeSlashes('/profiles/%s')
        ];
        if ($type) {
            return $map[$type];
        }
        return $map;
    }

    public function getDrupalType($package)
    {
        if (strpos($package->getType(), 'drupal') === 0) {
            return substr($package->getType(), strlen('drupal-'));
        }
        elseif ($package->getPrettyName() === 'drupal/drupal') {
            return 'core';
        }
        return false;
    }

    static public function changeSlashes($string) {
        return str_replace('/', DIRECTORY_SEPARATOR, $string);
    }
}

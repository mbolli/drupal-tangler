<?php

namespace Drupal\Tangler;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Composer\Script\Event;

class ScriptHandler
{
    public static function postUpdate(Event $event)
    {
        $composer = $event->getComposer();
        $extra = $composer->getPackage()->getExtra();
        $config = isset($extra['tangler']) ? $extra['tangler'] : array();
        $project = isset($config['project']) ? $config['project'] : getcwd();
        $drupal = isset($config['drupal']) ? $config['drupal'] : getcwd() . DIRECTORY_SEPARATOR . 'www';
        $copy = isset($config['copy']);
        if (!$copy) {
            // Check for symlink ability. If we don't have it, hard copy.
            // This is great for VMs on Windows hosts.
            $fs = new Filesystem();
            try {
                $fs->symlink($drupal, 'test');
                $fs->remove('test');
            }
            catch (IOException $e) {
                $copy = TRUE;
            }
        }
        $mapper = new Mapper($project, $drupal, $copy);
        $mapper->mirror($mapper->getMap(
            $composer->getInstallationManager(),
            $composer->getRepositoryManager()
        ));
    }
}

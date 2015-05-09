<?php

namespace Drupal\Tangler;

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
        $mapper = new Mapper($project, $drupal, $copy);
        $mapper->mirror($mapper->getMap(
            $composer->getInstallationManager(),
            $composer->getRepositoryManager()
        ));
    }
}

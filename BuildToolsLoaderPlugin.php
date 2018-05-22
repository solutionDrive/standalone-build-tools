<?php

/*
 * Created by solutionDrive GmbH.
 *
 * (c) 2018 solutionDrive GmbH
 */

namespace sd;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class BuildToolsLoaderPlugin implements PluginInterface, EventSubscriberInterface
{
    /** @var Composer */
    private $composer;

    /** @var IOInterface */
    private $io;

    private $toolsToInstall = [
        'behat-standalone.phar'
            => 'http://build-tools.cloud.solutiondrive.de/phar/behat-standalone.php{{PHP_VERSION}}.phar',
        'ecs-standalone.phar'
            => 'http://build-tools.cloud.solutiondrive.de/phar/coding-standard-standalone.ecs.php{{PHP_VERSION}}.phar',
        'easy-coding-standard.yml'
            => 'http://build-tools.cloud.solutiondrive.de/phar/easy-coding-standard-php{{PHP_VERSION}}.yml',
    ];

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }


    public static function getSubscribedEvents()
    {
        return [
            'post-install-cmd' => 'install',
            'post-update-cmd'  => 'install',
        ];
    }

    public function install()
    {
        $binDir = $this->composer->getConfig()->get('bin-dir');
        $configuredTools = $this->composer->getConfig()->get('standalone-build-tools');
        if (null !== $configuredTools) {
            $this->toolsToInstall = $configuredTools;
        }

        foreach ($this->toolsToInstall as $target => $url) {
            $richUrl = $this->getUrlWithoutPlaceholders($url);
            $fullTarget = $binDir . DIRECTORY_SEPARATOR . $target;
            $this->io->write("Downloading $target from $richUrl ...");
            copy($richUrl, $fullTarget);
            chmod($fullTarget, 0755);
        }
    }

    private function getUrlWithoutPlaceholders($url)
    {
        $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $url = preg_replace('/\{\{\s*PHP_VERSION\s*\}\}/i', $phpVersion, $url);
        return $url;
    }
}

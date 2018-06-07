Standalone Build Tools composer plugin
======================================

Composer plugin that installs some standalone build tools.


Usage
-----

Just include in your composer.json as dev-requirement:

    "solutiondrive/standalone-build-tools": "*"

On each ```update``` or ```install``` the plugin loads the latest version of the build tools.

You can provide a custom list of files to download by setting:

    "config": {
        "standalone-build-tools": {
            "target-filename-in-bin-dir": "https://source.test/of/the/file/to/download"
        }
    }

For example you can download solutionDrive's builds:

    "config": {
        "standalone-build-tools": {
            "phpspec-standalone": "http://build-tools.cloud.solutiondrive.de/phar/phpspec-standalone.php{{PHP_VERSION}}.phar"
        }
    }

Note that ```{{PHP_VERSION}}``` will be replaced by the currently used PHP version, for example: ```5.6``` or ```7.0```.


License
-------

MIT

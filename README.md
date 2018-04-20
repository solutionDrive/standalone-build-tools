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
        "standalone-build-tools": [
            "target-filename-in-bin-dir": "https://source.test/of/the/file/to/download"
        ]
    }


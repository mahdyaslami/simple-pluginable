# simple-pluginable
This package allows other packages to make these changes at minimal cost if they need to change the structure of the project folder.

## Installation

1. Add following script to your project `composer.json` file.

```
{
    ...
    "scripts": {
            "post-autoload-dump": "SSFW\\Plugins\\Composer::postAutoloadDump"
    }
}
```

This script will run after dump autoload command and start process of changing workspaces.

2. Install `simple-pluginable` package.

```
composer require simple-pluginable
```

## Process

Pluginable will after running dump autoload command and check all packages and execute `installer.php` file of packages if exists.

Installer of packages will update project workspace.

Pluginable prevents a plugins from being installed twice and after installing all the plugins, they save them in the plugins.json file in the project root directory.

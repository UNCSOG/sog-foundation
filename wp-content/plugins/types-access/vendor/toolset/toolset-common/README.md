# Toolset Common

A collection of php, javascript and CSS libraries, utilities and models 
to be used with Toolset plugins.

## Installation

There's nothing to do for using Toolset Common in production, since it is not
a standalone package. It will be already bundled in Toolset plugins, wherever
it is needed.

## Development

1. Clone the project to your plugins directory.
2. Run `make dev` (or `make setup` if you're doing this for the first time).

Note: Running `make` or `make help` will show more available make targets.

### Basic requirements

- PHP 7.1+
- git
- make
- composer
- nodejs

### Autoloader classmap

Whenever you add, remove or move a PHP file with a class, 
you need to update the classmap by running `make classmap`.

Note that this script requires having a Zend framework available. See 
inline comments for more information. 

Of course, it is also possible - although discouraged - to update the 
classmap files manually.

### Updating $toolset_common_version

You can run `make version-bump` to increase the `$toolset_common_version` value
in `loader.php` by 1. However, this is also automatically executed as a part
of the pre-commit git hook, if you have it installed.

Before tagging a new version of the library, `$toolset_common_version` is
set to the value corresponding to the tag provided to `make pre-release`.

### Updating embedded Auryn library

Run `make update-auryn` to execute the shell script that handles updating and prefixing.
However, keep in mind that a new version of Auryn can break during this process and needs to be thoroughly tested.
That's why we recommend performing this update only when necessary.

### Updating embedded Twig library

The Twig library can be updated via [twig-scoper](https://github.com/OnTheGoSystems/twig-scoper) by using its
[run-toolset.sh](https://github.com/OnTheGoSystems/twig-scoper/blob/master/run-toolset.sh) script.

Again, every change should be performed carefully and with additional testing. For actually updating Twig version, 
the twig-scoper utility needs to be updated first.

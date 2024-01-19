# Autoloading

## PSR4 autoloader

`psr4-application-models.php` contains an autoloader based on the PSR4 specification. Right now it covers just the `application/models/` tree, but should be expanded to cover also `application/controllers`.

## Legacy

`autoload-classmap.php` is a (desirable) dynamically generated classmap pointing to all classes that can be autoloaded by `WPV_Main::register_autoloaded_classes`.

While it is a shared mechanism used by other Toolset plugins, we should no longer enlarge that classmap.

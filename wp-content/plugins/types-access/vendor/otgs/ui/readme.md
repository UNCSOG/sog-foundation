# OTGS UI

A collection of scripts and styles available for all OTGS projects, especially WPML and Toolset.

## Include in your project

Just include this library in your `composer.json` object, pointing to `master` (or the most recent version/tag):

```php
"require": {
	"otgs/ui": "dev-master"
}
```

```php
"require": {
	"otgs/ui": "~1.0"
}
```

Then you need to load it **manually** to avoid autoloaders, as we do want to load always the newest version of the library:

```php
/* path to the root of your relative vendor directory housing this repository, no trailing slash */
$vendor_path = '';

/* URL of the root of your relative vendor directory housing this repository, no trailing slash */
$vendor_url = '';

require_once( $vendor_path . '/otgs/ui/loader.php' );
otgs_ui_initialize( $vendor_path . '/otgs/ui', $vendor_url . '/otgs/ui' );
```

## Development

- Run `make setup` to install/update the required Git hooks for validating your commits
- New JavaScript or CSS must be transpiled with `make prod` (you can use `make dev` during development)

### Adding new assets

Assets libraries are registered and imported directly form `/dist/assets.json`, generated automatically by `webpack`.

New assets should be:

- Included in the right `src/js` or `src/scss` directory.
- Registered in the `/src/webpack.config.js` export mechanism, inside the `libraries` object.
- Listed in the `OTGS_Assets_Handles` class under `/src/php/OTGS_Assets_Handles.php` for reference to be used as other assets dependencies.

### Raising the library version

After every change in this library, its loader number needs to be increased. This should happen when:

- New assets are introduced.
- Existing assets get updated.

Using a tagging workflow, the verison number should match the tag name.

The library loader number is located in `/loader.php` as the numeric variable `$otg_ui_version`.

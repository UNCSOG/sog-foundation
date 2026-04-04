# SOG-UNC-Rebrand

Theme-agnostic WordPress plugin that injects a standardized School of Government header and footer into most themes.

## Architecture

- `sog-unc-rebrand.php` bootstraps the plugin, registers the autoloader, and starts the plugin container on `plugins_loaded`.
- `includes/admin/` contains the single settings module and placeholder admin screen.
- `includes/admin/` contains the single settings module and full configuration screen.
- `includes/core/` contains assets, display rules, and frontend hook wiring.
- `includes/core/` also contains activation logic for seeding default settings.
- `includes/frontend/` contains separate header and footer renderer classes.
- `includes/menus/` contains plugin-owned menu location registration.
- `templates/` contains minimal scaffold templates, including a separate utility bar partial.
- `assets/css/` and `assets/js/` contain isolated placeholder assets with `sog-rebrand__` namespaced selectors.

## How to Install

If you just need to pull a copy use git clone like normal

### Run from plugin directory

If you want to clone this repo.

```bash
git clone git@sc.unc.edu:sog-it/sog-unc-rebrand.git
```

Inside existing repo

If you need to pull this into an existing repo (like a pantheon website) use a git subtree instead

```bash
git subtree add --prefix=wp-content/plugins/sog-unc-rebrand git@sc.unc.edu:sog-it/sog-unc-rebrand.git main --squash
```

If you need to update the plugin you can pull updates for that submodule and merge them into the site repo.

```bash
git subtree pull --prefix=wp-content/plugins/sog-unc-rebrand git@sc.unc.edu:sog-it/sog-unc-rebrand.git main --squash
git commit -m "Updating sog-unc-rebrand plugin."
```

## Plugin TODO's


1. Known/Found Bugs.
   1. Footer.
      1. If you try to disable the about text wysiwyg field and any other setting, the setting will not save.
         1. Temp fix I have it set to false by default instead of true.

# sog-menu-options

## Purpose

Adds a Target field to WordPress menu items.

## Instructions

Use it in Appearance > Menus to save values such as _blank, _self, _parent, or _top directly to each menu item.


## How to Install

If you just need to pull a copy use git clone like normal

### Run from plugin directory

If you want to clone this repo.

```bash
git clone git@sc.unc.edu:sog-it/sog-menu-options.git
```

Inside existing repo

If you need to pull this into an existing repo (like a pantheon website) use a git subtree instead

```bash
git subtree add --prefix=wp-content/plugins/sog-menu-options git@sc.unc.edu:sog-it/sog-menu-options.git main --squash
```

If you need to update the plugin you can pull updates for that submodule and merge them into the site repo.

```bash
git subtree pull --prefix=wp-content/plugins/sog-menu-options git@sc.unc.edu:sog-it/sog--menu-options.git main --squash
git commit -m "Updating sog-menu-options plugin."
```

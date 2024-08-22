# sog-attachment-redirect

A WordPress plugin to automatically redirect the "post-single" page of cjil and court appearance toolbox pages to their respective resource, regardless of number of times the resource upload has been modified.
This is tightly coupled and will need modification to work outside of its original sites. Still useful as reference.

## Usage

### Standalone

If you just need to pull a copy use git clone like normal

```bash
# Run from plugin directory
git clone git@sc.unc.edu:sog-it/sog-attachment-redirect.git
```

### Inside existing repo

If you need to pull this into an existing repo (like a pantheon website) use a git subtree instead

```bash
# from the project root dir
git subtree add --prefix=wp-content/plugins/sog-attachment-redirect git@sc.unc.edu:sog-it/sog-attachment-redirect.git main
```

If you need to update the plugin you can pull updates for that submodule and merge them into the site repo.

```bash
# Run from the project root
git subtree pull --prefix=wp-content/plugins/sog-attachment-redirect git@sc.unc.edu:sog-it/sog-attachment-redirect.git main
git commit -m "updating sog-attachment-redirect"
```
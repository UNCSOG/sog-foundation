# sog-saml-conf

A WordPress plugin to automate handling of SSO configuration and development account management.
This will eventually become part our upstream image, meaning that all new WordPress sites will have accounts set up automatically out of the box

## Usage

### Standalone

If you just need to pull a copy use git clone like normal

```bash
# Run from plugin directory
git clone git@sc.unc.edu:sog-it/sog-saml-conf.git
```

### Inside existing repo

If you need to pull this into an existing repo (like a pantheon website) use a git subtree instead

```bash
# from the project root dir
git subtree add --prefix=wp-content/plugins/sog-saml-conf git@sc.unc.edu:sog-it/sog-saml-conf.git main
```

If you need to update the plugin you can pull updates for that submodule and merge them into the site repo.

```bash
# Run from the project root
git subtree pull --prefix=wp-content/plugins/sog-saml-conf git@sc.unc.edu:sog-it/sog-saml-conf.git main
git commit -m "updating sog-saml-conf"
```

## Features

- Automatic setup of SOG SSO (just the WordPress side for now)
- Environment configuration
- Easy admin access on development environments
  - username: sog_apps
  - password: livelaughlove

## Future improvements

- Allow admin access based on AD groups (SOG>IT>Apps). This will likely require am SSO rebuild.
- Include as a must-use plugin in a custom upstream image.
- Login rate limiting
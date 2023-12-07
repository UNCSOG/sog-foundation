# sog-saml-conf

A WordPress plugin to automate handling of SSO configuration and development account management.
This will eventually become part our upstream image, meaning that all new WordPress sites will have accounts set up automatically out of the box

## Usage

Clone the repo into /wp-content/plugins/

```bash
git clone git@sc.unc.edu:sog-it/sog-saml-conf.git

# Or to keep versioning
git subtree add --prefix=wp-content/plugins/sog-saml-conf git@sc.unc.edu:sog-it/sog-saml-conf.git main
```

## Features

- Automatic setup of SOG SSO (just the WordPress Side for now)
- Environment configuration
- Easy admin access on development environments
  - username: sog_apps
  - password: livelaughlove

## Future improvements

- Allow admin access based on AD groups (SOG>IT>Apps). This will likely require am SSO rebuild.
- Include as a must-use plugin in a custom upstream image.
- Login rate limiting? I think sso does this already.
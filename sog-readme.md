## Updating the custom wordpress pantheon stream


Update via composer first.

```
    composer update
```

Then you can run the following commands:

```
    ddev wp plugin update --all
    ddev wp theme update --all
    git subtree pull --prefix=wp-content/plugins/sog-saml-conf git@sc.unc.edu:sog-it/sog-saml-conf.git main
```


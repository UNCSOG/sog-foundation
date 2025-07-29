## Updating the custom wordpress pantheon stream


Update via composer first.

```
    composer update
```

Updating the pantheon wordpress commands:

```
    git remote add pantheon-wordpress https://github.com/pantheon-systems/WordPress.git
    git fetch pantheon-wordpress
    git merge pantheon-wordpress/master -Xtheirs
```

Then you can run the following commands:

```
    ddev wp plugin update --all
    ddev wp theme update --all
    git subtree pull --prefix=wp-content/plugins/sog-saml-conf git@sc.unc.edu:sog-it/sog-saml-conf.git main
```


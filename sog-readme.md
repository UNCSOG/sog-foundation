# Updating the custom wordpress pantheon stream

Update via composer first.

```bash
    composer update
```

or use:

```bash
    composer install
```

## Updating the pantheon wordpress commands

```bash
    git remote add pantheon-wordpress https://github.com/pantheon-systems/WordPress.git
    git fetch pantheon-wordpress
    git merge pantheon-wordpress/master -Xtheirs
    git commit -m "Merging pantheon-wordpress/master to update wordpress."
```

Then you can run the following commands:

```bash
    ddev wp plugin update --all
    ddev wp theme update --all
    git subtree pull --prefix=wp-content/plugins/sog-saml-conf git@sc.unc.edu:sog-it/sog-saml-conf.git main  --squash
    git subtree pull --prefix=wp-content/plugins/wp-audit-tool https://github.com/pantheon-systems/wp-audit-tool main --squash
    git subtree pull --prefix=wp-content/plugins/sog-unc-cookie-banner ssh://git@sc.unc.edu/sog-it/sog-unc-cookie-banner.git main --squash
    git subtree pull --prefix=wp-content/plugins/sog-unc-utility-bar ssh://git@sc.unc.edu/sog-it/sog-unc-utility-bar.git main --squash
    # git subtree pull --prefix=wp-content/plugins/unc-utility-bar ssh://git@sc.unc.edu/itsds/unc-utility-bar.git main --squash
    # git subtree pull --prefix=wp-content/plugins/unc-cookie-banner ssh://git@sc.unc.edu/itsds/unc-cookie-banner.git main --squash
```

## To fix merge conflicts in a site using this upstream

```bash
    git remote add sog-foundation https://github.com/UNCSOG/sog-foundation.git
    git fetch sog-foundation
    git merge sog-foundation/main -Xtheirs
```

NOTE: Fix your merge conflicts if you still have any try not to rebase if can.

```bash
    git add .
    git push origin master
```




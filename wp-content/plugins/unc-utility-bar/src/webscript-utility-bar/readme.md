# Standalone Utility Bar

A web assets folder can be built to get the utility bar to the most possible places.  This creates a self contained web widget that can be added to a site using `<script type="text/javascript" id="unc-ub-script" data-color="navy" src="https://unc.edu/web-assets/utility-bar/utility-bar.min.js" ></script>`.  Once built you will need to manually add the web-assets to the server through.  They will be availabel in the build folder

## Structure

The standalone utility bar is primarily implemented as a JavaScript file (load-utility-bar.js). It imports CSS from the utility bar block and the screen-reader-text.scss file located in this folder.
When the build process completes, two essential files are generated in the build directory:
```
utility-bar.min.js
utility-bar.min.css
```
These two files are required for the widget to function properly.
Additionally, the build creates a [demonstration](https://unc.edu/web-assets/utility-bar/). The demo’s markup is contained in reference.html, while its styles and scripts are located in reference.css and reference.js, respectively.

## Building

To build in production mode, run the following command in the root of this project

```bash
npm run build:web-assets-utility-bar
```

If you are working on the script you can use 

```bash
npm run start:web-assets-utility-bar
```
and this folder will be watched for changes, bundling when a change is made in development mode

## Viewing

After building you should be able to see the reference page at `wp-content/plugins/unc-utility-bar/build/utility-bar/`
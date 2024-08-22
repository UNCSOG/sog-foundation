# UNC Cookie Banner Plugin

**Requires at least:** WordPress 5.8  
**Tested up to:** WordPress 6.3.2  
**Requires PHP:** 7.4  
**Version:** 1.0.3

UNC Cookie Banner is a WordPress plugin designed to display a customizable cookie consent banner across all blogs in a network. It aids in compliance with data protection laws and ensures users are informed about the use of cookies on the websites. Single Site installs will also function properly without exclude blogs function.

## Features
- **Customizable Banner:** Modify banner text, button text, and other settings.
- **Network-wide Display:** Shows the cookie consent banner on every blog in the network.
- **Exclusion Option:** Ability to exclude certain blogs from displaying the banner.
- **Accessibility:** Developed with a focus on accessibility and screen reader compatibility.

## Configuration
Navigate to the 'Cookie Banner Settings' page in the WordPress Network Dashboard to customize the banner text, button text, and other settings.

### Prerequisites
- Node.js and NPM.

### Setup & Build
1. Open a terminal and navigate to the plugin directory.
2. Run `npm install` to install the required dependencies.
3. Modify the SASS and JS files in the `/src` directory as needed.
4. Run the appropriate build command:
   - `npm run start` for development (Compiles SASS to CSS without minification).
   - `npm run build` for production (Minifies CSS and JS along with compiling SASS to CSS).

## Support and Issues
If you encounter any issues or have questions regarding this plugin, please visit the https://sc.unc.edu/itsds/wp-plugins/unc-cookie-banner/ for support and reporting issues.
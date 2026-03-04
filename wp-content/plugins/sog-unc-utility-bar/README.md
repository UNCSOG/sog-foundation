# UNC Utility Bar

**Contributors:** H. Adam Lenz, Douglas Slingerland
**Requires at least:** WordPress 5.8  
**Tested up to:** WordPress 6.9     
**Requires PHP:** 7.4  
**Version:** 2.2.2

The Unified UNC Utility bar with Alerts from Alert Carolina and a propper place to put your notifications on top of the page

- [UNC Utility Bar](#unc-utility-bar)
  - [Parts](#parts)
  - [Body Classes](#body-classes)
  - [Standalone Utility Bar](#standalone-utility-bar)
  - [Developing](#developing)
  - [Customization](#customization)
    - [Overriding templates](#overriding-templates)
  - [Setup and Workflow](#setup-and-workflow)
  - [Filters](#filters)
    - [alert\_service\_banner\_style](#alert_service_banner_style)
    - [alert\_service\_linkify\_banner](#alert_service_linkify_banner)
    - [alert\_service\_banner\_msg](#alert_service_banner_msg)
    - [alert\_service\_skip\_link\_target](#alert_service_skip_link_target)
    - [alert\_service\_skip\_link\_text](#alert_service_skip_link_text)
    - [alert\_service\_show\_skip\_link](#alert_service_show_skip_link)
    - [alert\_service\_banner\_data](#alert_service_banner_data)
    - [alert\_service\_get\_property](#alert_service_get_property)
    - [alert\_service\_get\_info](#alert_service_get_info)
    - [alert\_service\_set\_property](#alert_service_set_property)
    - [utility\_bar\_themes\_with\_utility\_bar](#utility_bar_themes_with_utility_bar)
  - [CLI Commands](#cli-commands)
    - [wp alert-service publish](#wp-alert-service-publish)
    - [wp alert-service unpublish](#wp-alert-service-unpublish)
    - [wp alert-service status](#wp-alert-service-status)
  - [Rest End Points](#rest-end-points)
    - [cap\_post\_endpoint](#cap_post_endpoint)
    - [cap\_get\_endpoint](#cap_get_endpoint)
    - [cap\_status\_get\_endpoint](#cap_status_get_endpoint)
    - [cap\_status\_set\_endpoint](#cap_status_set_endpoint)
  - [Security](#security)
    - [To Do](#to-do)


## Parts

This plugin is the combination of three header items that are used across the unc ecosystem.  

* The Uitlity Bar - the utility bar is a comon header item that contains links to other common sites within the unc networks
* The Alert Banner - when an alert is sent out from Rave/Alert Carolina, the alert will be displayed
* Site Banner - like the alert banner, allows site admins to post a message to their users


## Body Classes

When one of the parts is displayed a body class is added to the classes in `<body class="{BODY_CLASSES}">` tag to make it easier to style for a theme

* `.unc-utility-bar-plugin-enabled` when the plugin is turned on 
* `.utility-bar-displayed` when the utility bar is displayed
* `.alert-published` and  `.alert-unpublished` the status of the alert
* `.utility-bar-site-banner-displayed` when the site banner is displayed

## Standalone Utility Bar

An embeded web widget for sites that do not use WordPress, [Click here for more information about the standalone Utility Bar](src/webscript-utility-bar?ref_type=heads)

## Developing

The plugin uses [@WordPress/scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/)
To build run `npm run build` production ready, you should always run this before merging and tagging.  building is slower
To watch while you work run `npm run start` builds using source maps, not production ready.  building is fater and automatic when you save a file in the watch list

## Customization

### Overriding templates 
This plugin uses a template loader, If you wish to use your own utility bar template for your theme place a file named `utility-bar/utility-bar.php`.  Have a look at that file to see what variables to set. PLEASE DO NOT TRY TO OVERWRITE THE ALERT! thank you


## Setup and Workflow
The plugin is designed to accept CAP 1.2 alerts inside the body of a POST method.  This request should be sent to the REST endpoint `POST: /wp-json/site-list/v1/alert-service/cap/`.  That request is stored in the database and logged to a log file.  If a new request comes in that is not a normal, the request currently stored in the database is removed and the new request replaces it.  

A banner is shown on all pages where the plugin in active when the publish status of the cap message is set to "published".  When a "normal" cap alert is sent from rave, the alert is logged but the stored message is just set to "unplublished".  A banner publish status can be set using the WP CLI commands or the REST API commands detailed below.

## Filters

### alert_service_banner_style
Filter the style that will be outputted   
`\classes\class-banner-display.php`  
```
apply_filters( 'alert_service_banner_style', $style, $actype, $cap )
```        
* [array] $style an array that will be used as the styles for the banner block
* [string] $actype is a string, possibilities are 'weather', 'adverse', 'emergency', 'crime'
* [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object. 

### alert_service_linkify_banner
turn the link part on and off  
`\classes\class-banner-display.php`  
```
apply_filters('alert_service_linkify_banner', true, $cap)
```
* bool, if it's on
* [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object. 

### alert_service_banner_msg
Filter the message  
`\classes\class-banner-display.php`  
```
apply_filters('alert_service_banner_msg', $cap_msg, $actype, $cap)
```
* [string] $cap_msg a string, the message the users will see
* [string] $actype is a string, possibilities are 'weather', 'adverse', 'emergency', 'crime'
* [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object.  

### alert_service_skip_link_target
filter the skip linktarget  
`\classes\class-banner-display.php`  
```
apply_filters('alert_service_skip_link_target', '#maincontent', $actype, $cap )
```
* [string] target is a dom identifier of some location on the page the skip link should go to.  IDs work best, defailts to #maincontent
* [string] $actype is a string, possibilities are 'weather', 'adverse', 'emergency', 'crime'
* [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object. 

### alert_service_skip_link_text
filter the skip link test  
`\classes\class-banner-display.php`  
```
apply_filters('alert_service_skip_link_text', 'Skip to main content', $actype, $cap )
```
* [string] the text used in the skip link
* [string] $actype is a string, possibilities are 'weather', 'adverse', 'emergency', 'crime'
* [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object. 

### alert_service_show_skip_link
show or hide the skip link  
`\classes\class-banner-display.php`  
```
apply_filters('alert_service_show_skip_link', true, $cap )
```
* [bool] bool to show or hide the skiplink
* [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object. 

### alert_service_banner_data
filter the entire data array  
`\classes\class-banner-display.php`  
```
apply_filters('alert_service_banner_data', $data, $actype, $cap )
```
* [array] $data, an array of all the daya going to the banner template
* [string] $actype is a string, possibilities are 'weather', 'adverse', 'emergency', 'crime'
* [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object. 

### alert_service_get_property
Filters the $value when a value is being got from the object  
`\classes\class-cap.php` 
```
apply_filters( 'alert_service_get_property', $value, $property, $this )
```
* [mixed] $value the value of the property
* [string] $property
* [\Alert_Service\Cap] $this The Cap Object

### alert_service_get_info
Filters the info $value when a value is being got from the object  
`\classes\class-cap.php` 
```
apply_filters( 'alert_service_get_info', $info_array[$info_field], $info_field, $this );
```
* [mixed] $value the value of the property
* [string] $info_field the name of the info field
* [\Alert_Service\Cap] $this The Cap Object

### alert_service_set_property
Filters the $value when a value is being set in the object  
`\classes\class-cap.php` 
```
apply_filters( 'alert_service_banner_style', $value, $property, $cap )
```
* [mixed] $value the value of the property
* [string] $the name of the property being used
* [\Alert_Service\Cap] $cap The Cap Object

### utility_bar_themes_with_utility_bar
Filters the array of themes that already have the utility bar
`\unc-utility-bar.php`
```
apply_filter('utility_bar_themes_with_utility_bar', $themes_with_utility_bar );
```
* [array] $themes_with_utility_bar themes that already have the utility bar, so it wont be auto added
         
## CLI Commands

### wp alert-service publish
Publish the Current Alert Message

### wp alert-service unpublish
Unpublish the Current Alert Message

### wp alert-service status
Check the Publish Status of the Current Alert Message

## Rest End Points
The plugin exposes the following REST endpoints:

### cap_post_endpoint
`POST: /wp-json/site-list/v1/alert-service/cap/`  
* endpoint to post a cap message to
* requires an application password
* send the cap message as XML in the post body

### cap_get_endpoint
`GET: /wp-json/site-list/v1/alert-service/cap/(?P\<form\>( [json|xml] )+)`  
* gets the current published cap message as either json or xml
* requires an application password. 

### cap_status_get_endpoint
`GET: /wp-json/site-list/v1/alert-service/status/`  
* Gets the current status of the cap message as either published or unpublished. 
* requires an application password. 

### cap_status_set_endpoint
`POST: /wp-json/site-list/v1/alert-service/status/(?P\<status\>( [published|unpublished]) +)`  
* Set the status of the cap message as either published or unpublished
* requires an application password. 

## Security

Some of the REST endpoints require an [Application Password](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/) in order to work.  Make the application password using the application name Alert_Service-{rest endpoint function name}.  for example:

`Alert_Service-cap_get_endpoint`

`Alert_Service-cap_post_endpoint`

The variables are tacked onto the end of the url like so:

For a service account user
* username = srvc
* application password = abcd EFGH 1234 ijkl MNOP 6789

to attach the credentials to the end of a request add the following to the end of the URL like:  
`?login=srvc&auth=abcdEFGH1234ijklMNOP6789`

### To Do
1. when classic wp is gone combine the alert and utility bar together
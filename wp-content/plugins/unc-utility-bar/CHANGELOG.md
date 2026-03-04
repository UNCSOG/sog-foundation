# Changelog
### 2.2.2 Dec 18 2025
* enqueue alert style again for classic themes
* move utility bar enqueue to the main enqueue
* allow on student-aid-2 enrollment theme

### 2.2.1 Dec 15 2025
* change version from utility bar block so that local cache updates for the block styles

### 2.2.0 Dec 15 2025
* Update utility bar design to mirrow the new identity guidelines 
* create a standalone build process script

### 2.1.1 Oct 21 2025
* fix: make the default style work when the block is added #20
* fix: the current cap message is shown in the editor when viewing the alert #22
* fix: error message from Gutenberg packages when user not logged in. Split up front and back scripts #28
* fix: go back to ServerSideRender component as the new functionality is not ready #31

### 2.1.0 Sept 9 2025
* use in more classic themes
* Create a filter to change the array $themes_with_utility_bar
* bugfix#23 wp_is_block_theme called too early
* bugfix#25 PHP Warning: Undefined array key "callback"

## 2.0.19 May 14th 2025
* (re)Allow for themes to overwrite the utility bar template 

## 2.0.18 April 25th 2025
* block theme detection a bit later so that the wp_is_block_theme() function can load properly

## 2.0.17 April 8th 2025
* add a class to utility bar if classic editor is turned on for block themes
* better classnames in the utility bar template

## 2.0.16 March 12the 2025
* fix closue problem on rest endpoints

## 2.0.15
* dont show skip link on modular theme, fixes #15
* add check to see if we are using blocks in utility bar template, fixes #17

## 2.0.14
* fixing php 8.1 warning Warning: Trying to access array offset on value of type null in /var/www/html/web/wp-includes/class-wp-block-supports.php on line 99

## 2.0.14 Jan 14th 2025
* hook alert banner in after utility bar
* clear cache when a new cap message is saved

## 2.0.13 July 11th. 2024
* make font size a pixel !important 11.8

## 2.0.12 July 10th. 2024
* Set the font size to a pixel size cause thats the only way it will stay the same eveywhere 
* some minor issue fixing 

## 2.0.11 Apr 18. 2024 
* Fix the font family and size in the utility bar

## Jan 23rd 2024
* Clean up console log warnings and errors

## 2.0.6 Jan 22 2024
* switch xml parser

## 2.0.5 Jan 18th 2024
* make the alert fallback to rest and then rave

# 2.0.4 Dec 18th 2023
* fixing the fonts a bit

# 2.0.3 Sept 13th, 2023
* Remove actype from cap msg

# 2.0.0 April 25th, 2023
* Combining the utility bar, alert service and a handy site banner into one plugin
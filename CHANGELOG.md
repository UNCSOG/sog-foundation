# Plugin Change Log

## June 25, 2026 - Version 0.3

## April 5, 2026 - Version 0.2.1

## April 3, 2026 - Version 0.2.0

1. Updated the .gitignore file.
2. Minor text update on the navigation padding fields so that plugin user can know exactly which element that they are adding the padding to.
3. Added link on the plugins page to this plugins settings page.
4. Bug fixes
   1. Fixing the bottom nav background color not outputting.
   2. Fixed the upper footer separator mobile output.

## April 2, 2026 - Version 0.1.10

1. Added a few setting fields for the following:
   1. main navigation - padding (top, left, bottom, right), text color, font family, size, weight, style, text decoration and text transform.
   2. mobile menu - padding (top, left, bottom, right), text color, hover color, background color.
   3. site description - text transform
2. Switched out all of the '_font_weight' and '_font_size' fields to be number fields from text fields.
3. Updated the 3 navigation header template variants and add styles for it.
4. Style grid layout updates.
5. Broke all of the styles into a separate stylesheet for each variant to make it easier that we won't or are less likely to affect another variant with changes made.
6. Add new a new footer column mode called 'shortcode' to support shortcodes.
7. Add new footer column modes that are variations for shortcode, give button, social media, menu and wysiwyg.
8. Renamed some variant's in the get_header_core_variant_options() function so it better helps describe the layout to users.
9. Added a missing separator to the core-simple-text-vertical-line-special-btn.php variant.
10. Created another new variant: core-simple-text-vertical-line-alternate.php
11. Fixed the order of the styles in the header_style array.
12. Mobile style updates for small media devices.
13. 'Exclude post ids' renamed to 'exclude content ids'. To clear things up for plugin users.
14. Bug fixes
    1. Site names were always linking even if the option wasn't enabled in almost all simple-text variant templates.
    2. Exclude content ids setting field when set was still outputting the footer template on the excluded page.

## March 31, 2026 - Version 0.1.9

1. Added a few setting fields.
   1. text transform fields for the school and site name fields.
2. Updated a header variant templates and add styles for the new settings that were added.
3. Created an array for variant_templates so that the user can choose more than the original 2 variants in the header.php template.
4. Added another color setting for the school name field.
5. Removed the conditional settings for the site name, description, etc. fields so that we can set them on all variants.
6. Created a 'footer_give_social_gap' setting.
7. Added text transform and text decoration settings for the footer heading columns.
8. Style updates.
9. Added the footer first column into the bottom-row.php variant template so that we can get the address in with the copyright information to match the figma design. This only display on small mobile devices 768px and under.
10. Added a 'hide on mobile' field for both separator fields in the footer. Updated styles to support this functionality.

## March 27, 2026 - Version 0.1.8

Changes were made by Lindsay Hoyt.

1. Settings page
   1. Added social links hide on mobile fields for the header and footer areas.
   2. Added give button hide on mobile fields for the header and footer areas.
   3. Added special navigation button hide on mobile field.
2. Stylesheet
   1. Separated all of my style updates into a separate css file call frontend-hoyt.css.
   2. Fixing medium mobile styles so they aren't all left aligned and look more like the dfi medium mobile design.
3. Updated the gitignore file for the plugin.

## March 27, 2026 - Version 0.1.7

Changes were made by Lindsay Hoyt.

1. Settings page
   1. Added social links alignment field for the header area.
   2. Added give button padding, border radius, alignment and orientation fields for the header area.
   3. Added padding fields for the special navigation button for the header area.
   4. Added social links enable in column, alignment and orientation fields for the footer area.
   5. Added give button padding, border radius, enable in column, alignment and orientation fields for the footer area.
   6. Added a column heading alignment field.
   7. Added a none option to the footer column options.
   8. Presets added the new fields.
2. Stylesheet
   1. Style updates for corresponding fields.
   2. Small mobile style updates for the footer.
3. Bug fixes
   1. Display the missing give and special button fields - new tab and radius fields.
   2. The 'footer_give_button_text_transform' from not saving on the settings page.
   3. Give button not displaying.
   4. The footer columns width for columns 2 and 3. Even though the setting was being adjusted by user nothing happened on the frontend.
   5. Give and special button text transform not saving when saving the settings page.

## March 26, 2026 - Version 0.1.6

Changes were made by Lindsay Hoyt.

1. Header templates.
   1. Added 2 more header layout variants.
   2. Updated 3 layout variants.
   3. Header styles updated.
   4. Added more fields.
      1. Special button (unique button that will be inline with the navigation.)
      2. Give button (donate button).
2. Footer template.
   1. Give button fields.
3. Added a python script for distributing this plugin to all wordpress sites on a local computer. (You may need to adjust file paths if you want to use it.)

## March 25, 2026 - Version 0.1.5

Changes were made by Lindsay Hoyt.

1. Updated the Header templates.
   1. Added 7 more header layout variants.
   2. Header styles updated.
   3. Added more fields.
      1. Special button (unique button that will be inline with the navigation.)
      2. Give button (donate button).
      3. Social media for the header.
      4. Site search.
2. Footer template.
   1. Give button fields.
   2. Column headings.
3. Fixed a few syntax errors (missing commas in array's) in the includes/admin/class-settings.php file.
4. Re-organized some color fields to output on the correct tab on the settings page.

## March 24, 2026 - Version 0.1.4

Changes were made by Lindsay Hoyt.

1. Updated the Header templates.
   1. Fixed the layout styles so that the separator actually outputs.
   2. Bug Fix: Site tagline/description field actually outputs.
   3. Adding the description to actually output if we need it.
2. Updated the Footer templates.
   1. logos-row.php added another container element around the first 2 columns if all 3 columns have content so that it matches the design for spacing.
   2. Added give button fields to be outputted in the footer template.
3. Settings Page Header Section.
   1. Added a School Name field. Which if empty defaults to 'School of Government'.
   2. Added a header separator and all it's fields.
   3. Added search fields for the header.
   4. Added a checkbox option to uppercase the site name.
4. Settings Page Footer Section.
   1. Added a description to the Footer top text. Noting that "This currently has a bug that affects saving the settings on the footer tab setting's page."
   2. Added another gap field.
   3. Added a dashed option to the separator style field.
   4. Added a give button and all of the fields that it would need with it.
   5. Added a checkbox option to uppercase the give button.
5. This plugin now creates a 'sog-rebrand-footer-bottom' menu exists if there is none found and auto populates it with the accessibility and the privacy policy menu links.
6. Adjust and added some styles for the plugin.
7. Added TODO's to the plugin README file.
8. Updated the .gitignore file.
9. Fixing readme formatting issues.

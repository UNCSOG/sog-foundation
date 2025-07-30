# Filtering

Views and WordPress Archives can be filtered by some conditions.

There are two main kind of filters:
* Query filters.
* Frontend filters.

Well defined query filters should offer both sides, and both implementations end up on a set of query arguments.

## Query filters

Adding a query filter consists basically in providing the right data to print some GUI for it, and then storing the filter settings.

* The filter `wpv_filters_add_filter` will register a query filter for Views usage.
* The filter `wpv_filters_add_archive_filter` will register a query filter for WPAs usage.
* Both filters callbacks receive enough data to register the right query filters, and should return elements like the filter title and slug, the callback to execute when adding a new filter to a View or WPA, etc.
* The action `wpv_add_filter_list_item` will print a query filter into the right lists when editing a View or WPA.
* The filter `wpv_filter_register_shortcode_attributes_for_posts` will register the shortcode attributes that this filter describes as to be listening for values in order to apply the filter.
* The filter `wpv_filter_register_url_parameters_for_posts` will register the URL parameters that this filter describes as to be listening for values in order to apply the filter.

The data structure for classic query filters follows some legacy schema that pollutes the Views or WPA settings object with top level items depending on custom field or taxonomy slugs, for example. We should avoid defining new query filters in that way.

A new schema is being projected, which will store all query filters definition under a `filters` entry on the settings array. Each query filter will declare a key slug in that area: for example, data for the filter by on sale status for WooCommerce products is stored under `filters[post_product/onsale]`. For query filters with multiple endpoints, like taxonomy or custom field filters, sub-keys should exist: `filters[taxonomy][taxonomy_slug]`.

There can be a transparent transtion and migration between legacy query filters data structures and new ones: most of the settings can be moved in a on-on-one match.

Note that each query filter must register its own editor script to handle saving and deleting itself, and therefore it also needs to register two proper AJAX callbacks for saving and deleting data form the right View or WPA.

At the moment, block-based Views and WordPress Archives reuse the same codebase as legacy editors for query filters. That means that some adjustments are required in the query filter script.

## Frontend filters

Frontend filters consist basically on shortcodes rendering frontend controls. Then, those shortcodes must be registered as frontend search shortcodes.

* The filter `wpv_filter_wpv_register_form_filters_shortcodes` registers the shortcode as a frontend search one, and sets the conditions to offer it (target, flag for existence to avoid duplication, etc).
* The filter `wpv_filter_wpv_shortcodes_gui_data` registers the callback to gather the filter GUI data. This data matches the same structure and schema as the Views (and in general the paralel Toolset Common) shortcoes GUI API.
* The filter `wpv_filter_object_settings_for_fake_url_query_filters` (name to be adjusted) gathers data form any existing frontend filter and generates a companion ghost query filter to the query component can apply the filter settings. Th mechanism consists in generating a fake shortcode callback that will gather the shortcode information and transpose it into View or WPA settings matching what a legitimate query filter would store.

Defining frontend filters to work on the blocks editor is a little more complex, since the React components used to generate the GUI and save the filter are less than ideal. We will be reviewing this component in Toolset 1.6.

## Query component

The end goal for both query and frontend filters is to modify the query for the View or WordPress Archive.

* The filter `wpv_filter_query` will be used to apply the filter settings into a View query.
* The filter `wpv_action_apply_archive_query_settings` will be used to apply the filter settings into a WPA query.
* The filter `wpv_filter_object_settings_for_fake_url_query_filters` (name to be adjusted) can be used here to migrate legacy flters data structures to new ones, before applying them in the two filters above.

Most of the filters default to specific `WP_Query` arguments; some of them default to hijack existing arguments by performing auxiliary queries, for example, and injecting results into the `post__in` query argument. It is important to play nicely with multiple filters injecting into the same query argument, and evaluate early if a previously managed filter has declared the query as empty, to avoid further data processing.

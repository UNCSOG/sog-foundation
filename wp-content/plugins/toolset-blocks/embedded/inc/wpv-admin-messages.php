<?php

/**
* wpv-admin-messages.php
*
* @package Views
*
* @since unknown
*
* @note be sure htmlentities is applied to data-attributes
*/

/**
* WPV_Admin_Messages
*
* Wrapper for static methods used to display all sort of admin messages in Views
*
* @since 1.7
*/

class WPV_Admin_Messages {

	//----------------------------------------
	// Help boxes
	//----------------------------------------

	/**
	* toolset_help_box
	*
	* Creates the HTML version for wpvToolsetHelp()
	*
	* @param data array containing the attributes
	*
	* @return echo HTML box
	*
	* @since unknown
	*
	* @todo Use Toolset_Utils::help_box()
	*/

	static function toolset_help_box( $data = array() ) {
		if ( is_array( $data ) && ! empty( $data ) ) {
			$data_attr = '';
			foreach ( $data as $key => $value ) {
				if ( 'text' != $key ) {
					$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '"';
				}
			}
			?>
			<div class="js-show-toolset-message"<?php echo $data_attr; ?>>
			<?php if ( isset( $data['text'] ) ) {
				echo $data['text'];
			} ?>
			</div>
		<?php }
	}

	//----------------------------------------
	// Toggle boxes
	//----------------------------------------

	// @todo maybe move this along with script to common

	/**
	* render_toggle_structure
	*
	* Renders a complete toggle structure
	*
	* @param $args (array)
	*     'toggler_classname' => (string) the toggler classname
	*     'toggler_target' => (string) the toggler target classname
	*     'toggler_title' => (string) the toggler title
	*     'toggled_classname' => (string) the toggled classname
	*     'toggled_intro' => (string) the toggled intro
	*     'toggled_sections' => (array) the toggled sections
	*/

	static function render_toggle_structure( $args = array() ) {
		$args_default = array(
			'toggler_classname' => '',
			'toggler_target' => '',
			'toggler_title' => '',
			'toggled_classname' => '',
			'toggled_intro' => '',
			'toggled_sections' => array()
		);
		$args = wp_parse_args( $args, $args_default );
		WPV_Admin_Messages::render_toggle_toggler( $args['toggler_classname'], $args['toggler_target'], $args['toggler_title'] );
		WPV_Admin_Messages::render_toggle_toggled( $args['toggled_classname'], $args['toggled_intro'], $args['toggled_sections'] );
	}

	/**
	* render_toggle_toggler
	*
	* Display the toggler span
	*
	* @param $classname (string) classname of the toggler
	* @param $target (string) classname of the div to toggle
	* @param $title (string) text to show inside the span
	*
	* @return echo the span HTML tag
	*
	* @since 1.7
	*/

	static function render_toggle_toggler( $classname = '', $target = '', $title = '' ) {
		?>
		<span class="<?php echo $classname; ?>" data-target="<?php echo $target; ?>">
			<span class="wpv-toggle-toggler-icon js-wpv-toggle-toggler-icon">
				<i class="fa fa-caret-down icon-large fa-lg"></i>
			</span>
			<?php echo $title; ?>
		</span>
		<?php
	}

	/**
	* render_toggle_toggled
	*
	* Display the toggled div
	*
	* @param $classname (string) classname of the div to toggle including the $target one in the toggler
	* @param $intro (string) text to introduce the toggler
	* "param $sections (array) list of sections to display
	*
	* @return echo the div HTML tag
	*
	* @since 1.7
	*/

	static function render_toggle_toggled( $classname = '', $intro = '', $sections = array() ) {
		?>
		<div class="<?php echo $classname; ?> hidden">
		<?php
		if ( ! empty( $intro ) ) {
		?>
			<?php echo $intro; ?>
		<?php
		}
		if ( ! empty( $sections ) ) {
			foreach ( $sections as $section ) {
				$args = wpv_views_instructions_section_data( $section );
				WPV_Admin_Messages::render_toggle_section( $args );
			}
		}
		?>
		</div>
		<?php
	}

	/**
	* render_toggle_section
	*
	* Display a section inside a toggled div
	*
	* @param $args (array)
	*     $args['classname'] (string) the classname of the section
	*     $args['title'] (string) the h4 title of the section
	*     $args['content'] (string) the introduction text of the section
	*     $args['table'] (array) the elements in the table of the section, as an array:
	*         array(
	*             'element' => the title of the element
	*             'description' => the description of the element
	*         )
	*     $args['content_extra'] (string) the text to display after the table
	*
	* @return echo the span HTML tag
	*
	* @since 1.7
	*/

	static function render_toggle_section( $args = array() ) {
		if ( $args == false ) {
			return;
		}
		$args_default = array(
			'classname' => '',
			'title' => '',
			'content' => '',
			'table' => array(),
			'content_extra' => ''
		);
		$args = wp_parse_args( $args, $args_default );
		?>
		<div class="<?php echo $args['classname']; ?>">
		<?php
		if ( ! empty( $args['title'] ) ) {
		?>
			<h4><?php echo $args['title']; ?></h4>
		<?php
		}
		if ( ! empty( $args['content'] ) ) {
		?>
			<?php echo $args['content']; ?>
		<?php
		}
		if ( ! empty( $args['table'] ) ) {
		?>
			<table class="widefat">
				<thead>
					<tr>
						<th style="width:200px;">
							<?php _e( 'Element', 'wpv-views' ); ?>
						</th>
						<th>
							<?php _e( 'Description', 'wpv-views' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $args['table'] as $table_row_index => $table_row ) {
					?>
					<tr <?php if ( ! ( $table_row_index % 2 ) ) { echo 'class="alt"'; } ?>>
						<td>
							<?php echo $table_row['element']; ?>
						</td>
						<td>
							<?php echo $table_row['description']; ?>
						</td>
					</tr>
					<?php
					} ?>
				</tbody>
			</table>
		<?php
		}
		if ( ! empty( $args['content_extra'] ) ) {
		?>
			<?php echo $args['content_extra']; ?>
		<?php
		}
		?>
		</div>
		<?php
	}

	//----------------------------------------
	// Help hints per edit sections - full plugin mode
	//----------------------------------------

	/**
	* edit_section_help_pointer
	*
	* Help pointer contents for custom edit pages sections
	*
	* @param $section (string) Section
	*
	* @return $return (array) Both title and description for the section help pointer
	*
	* @since 1.8.0
	*/

	static function edit_section_help_pointer( $section = '' ) {
		$return = array(
			'title' => '',
			'content' => ''
		);
		switch ( $section ) {
			case 'title_and_description':
				// DEPRECATED
				$return = array(
					'title' => __( 'Title and Description', 'wpv-views' ),
					'content' => __("Each View has a title and an optional description. These are used for you, to identify different Views. The title and the description don't appear anywhere on the site's public pages.", 'wpv-views')
				);
				break;
			case 'content_section':
				$return = array(
					'title' => __('Content to load', 'wpv-views'),
					'content' => __('Choose between posts, taxonomy and users and then select the specific content type to load. For posts, you can select multiple content types.', 'wpv-views')
				);
				break;
			case 'archive_include_post_type':
				$return = array(
					'title' => __('Content to load', 'wpv-views'),
					'content' => __('Choose what post types will be included in this archive loop.', 'wpv-views')
				);
				break;
			case 'query_options':
				$return = array(
					'title' => __('Query Options', 'wpv-views'),
					'content' => __('This section includes additional options for what content to load. You will see different options for posts, taxonomy and users.', 'wpv-views')
				);
				break;
			case 'ordering':
				$return = array(
					'title' => __('Ordering', 'wpv-views'),
					'content' => __('Choose how to order the results that the View gets from the database. You can select the sorting key and direction.', 'wpv-views')
				);
				break;
			case 'archive_ordering':
				$return = array(
					'title' => __('Ordering', 'wpv-views'),
					'content' => __('Choose how to order the items in the WordPress Archive. You can select the sorting key and direction.', 'wpv-views')
				);
				break;
			case 'limit_and_offset':
				$return = array(
					'title' => __('Limit and Offset', 'wpv-views'),
					'content' => __('You can limit the number of results returned by the query and set an offset.', 'wpv-views')
				);
				break;
			case 'filter_the_results':
				$return = array(
					'title' => __('Query Filter', 'wpv-views'),
					'content' => __("You can filter the View query by status, custom fields, taxonomy, users fields and even text search depending on the content that you are going to load. Click on 'Add a filter' and then select the filter type. A View may have as many filters as you like.", 'wpv-views')
				);
				break;
			case 'view_wrapper':
				$return = array(
					'title' => __( 'View Wrapper', 'wpv-views' ),
					'content' => __( 'Normally, the View has a wrapper DIV which includes IDs and classes required for AJAX updates and pagination. If you want to show the View inline with text, you can disable this DIV, but you will not be able to use some of the functionality that Views offer.', 'wpv-views' ),
				);
				break;
			case 'filter_the_archive_results':
				$return = array(
					'title' => __('Query Filter', 'wpv-views'),
					'content' => __("You can filter the WordPress Archive by status, custom fields, taxonomy and even searching on the content that you are going to load. Click on 'Add a filter' and then select the filter type. A WordPress Archive may have as many filters as you like.", 'wpv-views')
				);
				break;
			case 'pagination_and_sliders_settings':
				$return = array(
					'title' => __('Pagination and sliders settings', 'wpv-views'),
					'content' => __("You can use a View to display paginated results and sliders. Both are built using 'Pagination'. For paginated listings, choose to update the entire page. For sliders, choose to update only the View.", 'wpv-views')
				);
				break;
			case 'archive_pagination':
				$return = array(
					'title' => __('Pagination settings', 'wpv-views'),
					'content' => __("You can set pagination for this WordPress Archive, or disable it to display all items on the same page. Remember to add pagination controls in the Loop Editor.", 'wpv-views')
				);
				break;
			case 'filters_html_css_js':
				$return = array(
					'title' => __('Search and Pagination', 'wpv-views'),
					'content' => __("In this section you can add pagination controls, slider controls and custom searches. If you have enabled pagination, you need to insert the pagination controls here. They are used for both paged results and sliders. For custom searches, insert 'filter' elements. The output of this section is displayed via the [wpv-filter-meta-html] shortcode in the Output section.", 'wpv-views')
				);
				break;
			case 'parametric_search':
				$return = array(
					'title' => __('Custom search', 'wpv-views'),
					'content' => __("In this section you can choose when to refresh the Views results and which options to show in form inputs.", 'wpv-views')
				);
				break;
			case 'parametric_search_archive':
				$return = array(
					'title' => __('Custom search', 'wpv-views'),
					'content' => __("In this section you can choose when to refresh the WordPress Archive results and which options to show in form inputs.", 'wpv-views')
				);
				break;
			case 'layout_html_css_js':
				$return = array(
					'title' => __('View HTML output', 'wpv-views'),
					'content' => __('This HTML determines what the View outputs for the query results. Use the Loop Wizard to design the output of this View. Then, edit the design by adding fields, HTML and media, using the toolbar buttons.', 'wpv-views')
				);
				break;
			case 'layout_archive_html_css_js':
				$return = array(
					'title' => __('WordPress Archive HTML output', 'wpv-views'),
					'content' => __('This HTML determines what the WordPress Archive outputs for the results. Use the Loop Wizard to design the output of this archive. Then, edit the design by adding fields, HTML and media, using the toolbar buttons.', 'wpv-views')
				);
				break;
			case 'layout_extra_js':
				$return = array(
					'title' => __('Additional Javascript files', 'wpv-views'),
					'content' => __('Here you can set the URLs of additional scripts that need to be loaded with this View. Use a comma separated list of URLs', 'wpv-views')
				);
				break;
			case 'templates_for_view':
				// DEPRECATED
				$return = array(
					'title' => __('Templates for this View', 'wpv-views'),
					'content' => __("A View may include Content Templates. These templates make it easy to output design structures without having to repeat them in the loop.", 'wpv-views')
				);
				break;
			case 'complete_output':
				$return = array(
					'title' => __('Output Integration', 'wpv-views'),
					'content' => __( 'This editor lets you control how the Search and Pagination, and Loop sections of this View are displayed. The [wpv-filter-meta-html] shortcode displays the output of the Search and Pagination section. The [wpv-layout-meta-html] shortcode displays the output of the Loop section. You can add your HTML and fields to rearrange and style the output.', 'wpv-views' )
				);
				break;
			case 'complete_output_archive':
				$return = array(
					'title' => __('Output Integration', 'wpv-views'),
					'content' => __( 'This editor lets you control how the Search and Pagination, and Loop sections of this WordPress Archives are displayed. The [wpv-filter-meta-html] shortcode displays the output of the Search and Pagination section. The [wpv-layout-meta-html] shortcode displays the output of the Loop section. You can add your HTML and fields to rearrange and style the output.', 'wpv-views' )
				);
				break;
			case 'loops_selection':
				$return = array(
					'title' => __('Loop selection', 'wpv-views'),
					'content' => __("Choose which listing page to customize. The WordPress Archive will display by default the exact same content as WordPress normally does, but you can adjust the ordering, add extra filters or modify the HTML output.", 'wpv-views')
				);
				break;
			case 'loops_selection_layouts':
				$return = array(
					'title' => __('Layouts archive loop', 'wpv-views'),
					'content' => __('This WordPress Archive is used in a Layout, so it will be used in the archive loops that the Layout has been assigned to.', 'wpv-views')
				);
				break;
			case 'module_manager':
				$return = array(
					'title' => __('Module Manager', 'wpv-views'),
					'content' => __("With Modules, you can easily reuse your designs in different websites and create your own library of building blocks.", 'wpv-views')
				);
				break;
			case 'scan_usage_view':
				$return = array(
					'title' => __('Views usage', 'wpv-views'),
					'content' => __('Scan your content to check where this View is used, and get links to edit or view the results.', 'wpv-views'),
				);
				break;
		}
		return $return;
	}

	/**
	* get_documentation_promotional_link
	*
	* @param $args	array
	* 		@param query	array
	* 		@param anchor	string
	* @param $url	string
	*
	* @return string
	*
	* @since 1.12
	*/

	static function get_documentation_promotional_link( $args = array(), $url = 'https://toolset.com/home/toolset-components/' ) {
		if ( isset( $args['query'] ) ) {
			$url = esc_url( add_query_arg( $args['query'], $url ) );
		}
		if ( isset( $args['anchor'] ) ) {
			$url .= '#' . esc_attr( $args['anchor'] );
		}
		return $url;
	}

}

//----------------------------------------
// Help boxes texts - function
//----------------------------------------

/**
* wpv_toolset_help_box
*
* Creates the HTML version for wpvToolsetHelp()
*
* @param data array() containing the attributes
* @return echo HTML box
*
* @since unknown
*
* @todo use Toolset_Utils::help_box()
*/

function wpv_toolset_help_box( $data ) {
	if ( is_array( $data ) && ! empty( $data ) ) { ?>
	<div class="js-show-toolset-message"<?php foreach ( $data as $key => $value ) {if ( 'text' != $key ) { ?> data-<?php echo $key; ?>="<?php echo esc_attr( $value ); ?>"<?php } } ?>>
	<?php if ( isset( $data['text'] ) ) {
		echo $data['text'];
	} ?>
	</div>
	<?php }
}

//----------------------------------------
// Help boxes texts - full plugin mode
//----------------------------------------

/**
* wpv_get_view_introduction_data
*
* ToolSet Help Box for Views
* Adds a different help box for each View purpose, will be shown/hidden using a script
*
* @return echo the main help boxes for the Query section
*
* @since unknown
*/

function wpv_get_view_introduction_data() {
	$all = array(
		'text'			=> '<p>' . __('A View loads content from the database and displays it anyway you choose.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The Query section lets you choose the content to load.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('A basic query selects all items of a chosen type.', 'wpv-views') . '</p>'
						. '<ul><li>' . __('You can refine the selection by adding filters.', 'wpv-views') . '</li>'
						. '<li>' . __('At the bottom of this page you will find the Loop section, where you control the output.', 'wpv-views') . '</li></ul>',
		'close'			=> 'true',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-query js-for-view-purpose-all'
	);
	wpv_toolset_help_box($all);
	$pagination = array(
		'text'			=> '<p>' . __('A View loads content from the database and displays it anyway you choose.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The Query section lets you choose the content to load.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('A basic query selects all items of a chosen type.', 'wpv-views') . '</p>'
						. '<ul><li>' . __('You can refine the selection by adding filters.', 'wpv-views') . '</li>'
						. '<li>' . __('The Front-end Filter section includes the pagination controls, allowing visitors to choose which results page to show.', 'wpv-views') . '</li>'
						. '<li>' . __('At the bottom of this page you will find the Loop section, where you control the output.', 'wpv-views') . '</li></ul>',
		'tutorial-button-text'	=> htmlentities( __('Creating paginated listings with Views', 'wpv-views'), ENT_QUOTES ),
		'tutorial-button-url'	=> WPV_LINK_CREATE_PAGINATED_LISTINGS,
		'close'			=> 'true',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-query js-for-view-purpose-pagination'
	);
	wpv_toolset_help_box($pagination);
	$slider = array(
		'text'			=> '<p>' . __('A View loads content from the database and displays it anyway you choose.','wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The Query section lets you choose the content to load.','wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('A basic query selects all items of a chosen type.','wpv-views') . '</p>'
						. '<ul><li>' . __('You can refine the selection by adding filters.','wpv-views') . '</li>'
						. '<li>' . __('The Front-end Filter section includes the slider controls, allowing visitors switch between slides.','wpv-views') . '</li>'
						. '<li>' . __('At the bottom of this page you will find a slide Content Template, where you design slides.', 'wpv-views') . '</li></ul>',
		'tutorial-button-text'	=> htmlentities( __('Creating sliders with Views', 'wpv-views'),ENT_QUOTES ),
		'tutorial-button-url'	=> WPV_LINK_CREATE_SLIDERS,
		'close'			=> 'true',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-query js-for-view-purpose-slider'
	);
	wpv_toolset_help_box($slider);
	$parametric = array(
		'text'			=> '<h3>' . __('Building a View for a custom search:', 'wpv-views') . '</h3>'
						. '<ol><li>' . __('Select which content to load in the \'Content Selection\' section.', 'wpv-views') . '</li>'
						. '<li>' . __('Add filter input to the Filter section.', 'wpv-views') . '</li>'
						. '<li>' . __('Select advanced search options in the \'Custom Search Settings\' section.', 'wpv-views') . '</li>'
						. '<li>' . __('Design the search results in the Loop section.', 'wpv-views') . '</li>'
						. '<li class="js-layouts-search-help" style="display:none">' . __('Select if you want the form, the results or both displayed in the Layouts cell.', 'wpv-views') . '</li></ol>'
						. '<p>' . __('Remember to click on Update after you complete each section and before you continue to the next section.', 'wpv-views') . '</p>',
		'tutorial-button-text'	=> htmlentities( __('Creating custom searches with Views', 'wpv-views'), ENT_QUOTES ),
		'tutorial-button-url'	=> WPV_LINK_CREATE_PARAMETRIC_SEARCH,
		'close'			=> 'true',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-query js-for-view-purpose-parametric'
	);
	wpv_toolset_help_box($parametric);
	$full = array(
		'text'			=> '<p>' . __('A View loads content from the database and displays it anyway you choose.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The Query section lets you choose the content to load.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('A basic query selects all items of a chosen type.', 'wpv-views') . '</p>'
						. '<ul><li>' . __('You can refine the selection by adding filters.','wpv-views') . '</li>'
						. '<li>' . __('The Front-end filter section lets you add pagination, slider controls and custom search to the View.', 'wpv-views') . '</li>'
						. '<li>' .  __('At the bottom of this page you will find the Loop section, where you control the output.', 'wpv-views') . '</li></ul>',
		'close'			=> 'true',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-query js-for-view-purpose-full'
	);
	wpv_toolset_help_box($full);
}

/**
* wpv_get_view_filter_introduction_data
*
* ToolSet Help Box for Views
* Pagination introduction
*
* @return echo the main help boxes for the Filter section
*
* @since unknown
*/

function wpv_get_view_filter_introduction_data() {
	$pagination = array(
		'text'			=> '<p>' . __('Pagination lets you break the results into separate pages.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('This way, you can display a large number of results, in shorter pages.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('You can insert next/previous links and page selectors, for navigating directly to a specific page.', 'wpv-views') . '</p>'
						. '<ul><li>' . __('The first part of this section lets you choose how pagination works.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('Select how many results to show in each page and how pages transition.', 'wpv-views') . '</li>'
						. '<li>' . __('The second part of this section lets you design the pagination controls that would appear on the page for visitors.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The toolbar above the HTML editor includes buttons for inserting various controls.', 'wpv-views') . '</li>'
						. '<li>' . __('Besides pagination, you can also insert custom search filters and text search controls.', 'wpv-views') . '</li></ul>',
		'close'			=> 'true',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-filter js-for-view-purpose-pagination'
	);
	wpv_toolset_help_box($pagination);
	$slider = array(
		'text'			=> '<p>' . __('The pagination section lets you build sliders with Views.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The View will display each slide at a time and allow visitors to switch between slides using next/previous links and slide selectors.', 'wpv-views') . '</p>'
						. '<ul><li>' . __('The first part of this section lets you choose how the slider pagination works.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('Select how many results to show in each slide and how slides transition.', 'wpv-views') . '</li>'
						. '<li>' . __('The second part of this section lets you design the transition controls for the slider.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The toolbar above the HTML editor includes buttons for inserting the slide transition controls.', 'wpv-views') . '</li></ul>',
		'tutorial-button-text'	=> htmlentities( __('Creating sliders with Views', 'wpv-views'), ENT_QUOTES ),
		'tutorial-button-url'	=> WPV_LINK_DESIGN_SLIDER_TRANSITIONS,
		'close'			=> 'true',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-filter js-for-view-purpose-slider'
	);
	wpv_toolset_help_box($slider);
	$full = array(
		'text'			=> '<p>' . __('Pagination lets you break the results into separate pages.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('This way, you can display a large number of results, in shorter pages.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('You can insert next/previous links and page selectors, for navigating directly to a specific page.','wpv-views') . '</p><p>'
						. __('Using pagination you can also implement sliders.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. '<a href="' . WPV_LINK_CREATE_SLIDERS . '" target="_blank">' . __('Learn how to create sliders with Views.', 'wpv-views') . '</a></p>'
						. '<ul><li>' . __('The first part of this section lets you choose how pagination works.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('Select how many results to show in each page and how pages transition.', 'wpv-views') . '</li>'
						. '<li>' . __('The second part of this section lets you design the pagination controls that would appear on the page for visitors.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The toolbar above the HTML editor includes buttons for inserting various controls.', 'wpv-views') . '</li>'
						. '<li>' . __('Besides pagination and slider transition controls, you can also insert custom search filters and text search controls.', 'wpv-views') . '</li></ul>'
						. '<p><a href="' . WPV_LINK_CREATE_PARAMETRIC_SEARCH . '" target="_blank">' . __('Learn how to create custom searches with Views.', 'wpv-views') . '</a></p>',
		'close'			=> 'true',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-filter js-for-view-purpose-full'
	);
	wpv_toolset_help_box($full);
	$parametric = array(
		'text'			=> '<h3>' . __('Custom Search Instructions', 'wpv-views' ) . '</h3>'
						. '<p>' . __('This section controls how the search form for your custom search will behave and appear.','wpv-views') . '</p>'
						. '<ol><li>' . __('First, choose how to update the results. Depending on your selections, the Filter HTML editor will include different controls.','wpv-views') . '</li>'
						. '<li>' . __('Then, add input filters and search-form controls to the Filter section.','wpv-views') . '</li></ul>'
						. '<p>' . __('Use the "formatting and editing instructions" bar at the bottom of the editor to see full editing instructions.','wpv-views') . '</p>'
						. '<p><a href="' . WPV_LINK_CREATE_PARAMETRIC_SEARCH . '" target="_blank" class="btn">' . __('Complete Views Custom Search tutorial', 'wpv-views') . '</a></p>'
						. ' <input id="wpv-parametric-hint-dismiss" type="hidden" class="js-wpv-parametric-hint-dismiss" data-nonce="' . wp_create_nonce( 'wpv_view_parametric_hint_dismiss_nonce')  . '" /> ',
		'close'			=> 'true',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-filter js-for-view-purpose-parametric',
	);
	wpv_toolset_help_box($parametric);
}

/**
* wpv_get_view_layout_introduction_data
*
* ToolSet Help Box for Views
* Layout introduction
*
* @return echo the main help box for the Layout section
*
* @since unknown
*/

function wpv_get_view_layout_introduction_data() {

    $result = array(
		'text'			=> '<h3>' . __('How to Design the Loop','wpv-views') . '</h3>'
					. '<p>' . __('The Loop HTML editor lets you style the loop of the View.', 'wpv-views') . '</p>'
					. '<p>' . __('The content between the <strong>&lt;wpv-loop&gt;</strong> and <strong>&lt;/wpv-loop&gt;</strong> tags will repeat for every item in the View loop.') . '</p>'
					. '<p>' . __('To get started easily, click on the <strong>Loop Wizard</strong>. You will select the loop style and the fields to display. Then, edit by adding you own HTML markup, media and additional fields.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. sprintf( __('Learn more by reading the %sViews Loop documentation%s.', 'wpv-views'), '<a href="' . WPV_LINK_LOOP_DOCUMENTATION . '" target="_blank">', '</a>' ) . '</p>'
						. '<p>' . __('The <strong>Output Integration</strong> section lets you control the order of the <strong>Search and Pagination</strong> and <strong>Loop</strong> sections and to add your HTML between these two sections.', 'wpv-views') . '</p>'
						. ' <input id="wpv-layout-hint-dismiss" type="hidden" class="js-wpv-layout-hint-dismiss" data-nonce="' . wp_create_nonce( 'wpv_view_layout_hint_dismiss_nonce')  . '" /> ',
		'close'			=> 'true',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-layout js-for-view-purpose-all js-for-view-purpose-pagination js-for-view-purpose-slider js-for-view-purpose-parametric js-for-view-purpose-full',
	);


	$result_views_lite = array(
		'text'			=> '<h3>' . __('How to Design the Loop','wpv-views') . '</h3>'
		                     . '<p>' . __('The Loop HTML editor lets you style the loop of the View.', 'wpv-views') . '</p>'
		                     . '<p>' . __('The content between the <strong>&lt;wpv-loop&gt;</strong> and <strong>&lt;/wpv-loop&gt;</strong> tags will repeat for every item in the View loop.') . '</p>'
		                     . '<p>' . __('To get started easily, click on the <strong>Loop Wizard</strong>. You will select the loop style and the fields to display. Then, edit by adding you own HTML markup, media and additional fields.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
		                     . sprintf( __('Learn more by reading the %sViews Loop documentation%s.', 'wpv-views'), '<a href="' . WPV_LINK_LOOP_DOCUMENTATION . '" target="_blank">', '</a>' ) . '</p>'
		                     . ' <input id="wpv-layout-hint-dismiss" type="hidden" class="js-wpv-layout-hint-dismiss" data-nonce="' . wp_create_nonce( 'wpv_view_layout_hint_dismiss_nonce')  . '" /> ',
		'close'			=> 'true',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-layout js-for-view-purpose-all js-for-view-purpose-pagination js-for-view-purpose-slider js-for-view-purpose-parametric js-for-view-purpose-full',
	);

	return ( ! wpv_is_views_lite() ) ? $result : $result_views_lite;
}

/**
* wpv_get_view_ct_slider_introduction_data
*
* ToolSet Help Box for Views
* Content Template: View slider mode
*
* @return echo the main help boxes for the Content template section section
*
* @since unknown
*/
function wpv_get_view_ct_slider_introduction_data() {
    $data = array(
        'text'          => '<p class="js-wpv-ct-was-inserted">'
						. __('This Content Template lets you design slides in this slider. Add any field you need to display and design them using HTML and CSS. To style the slide transition controls, scroll up to the filter section.', 'wpv-views')
						. '</p>',
        'close'         => 'false',
        'hidden'        => 'false',
        'classname'     => 'js-wpv-content-template-slider-hint',
        'footer'        => 'false'
    );
	wpv_toolset_help_box( $data );
}

/**
* wpv_display_view_howto_help_box
*
* View display help box
*
* @return echo the help box about displaying this View
*
* @since unknown
*/

function wpv_display_view_howto_help_box() {
	$general = array(
		'text'			=> '<h3>' . __( 'How to display this View', 'wpv-views' ) . '</h3>'
						. '<p>' . __('You can display this View inside content or as a widget.', 'wpv-views') . '</p><p>'
						. __('To display inside content (post, page, custom type), edit that content.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __( 'You will find the <strong>Views</strong> button.', 'wpv-views' ) . WPV_MESSAGE_SPACE_CHAR
						. __( 'Click on it and locate this View to insert it anywhere you want inside the content.', 'wpv-views' ) . '</p><p>'
						. sprintf( __('To display as a widget, go to <a href="%s">Appearance -> Widgets</a> and select the <strong>WP Views</strong> widget.', 'wpv-views'), admin_url( 'widgets.php' ) ) . '</p><p>'
						. '</p>',
		'classname'		=> 'js-display-view-howto js-for-view-purpose-all js-for-view-purpose-pagination js-for-view-purpose-slider js-for-view-purpose-full',
        'close' => false
	);
	wpv_toolset_help_box( $general );
	$parametric = array(
		'text'			=> '<h3>' . __( 'How to display this Custom Search View', 'wpv-views' ) . '</h3>'
						. '<p>' . __( 'This View contains a search form and results list.', 'wpv-views' ) . WPV_MESSAGE_SPACE_CHAR
						. __( 'You can display them together, on one page, or have the search in one page and the results on another page.', 'wpv-views' ) . '</p><p>'
						. __('Start with the location where you want the search form to appear.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __( 'You can display the search inside content or as a widget.', 'wpv-views' ) . '</p><p>'
						. __('To display inside content (post, post, custom type), edit that content.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __( 'You will find the <strong>Views</strong> button.', 'wpv-views' ) . WPV_MESSAGE_SPACE_CHAR
						. __( 'Click on it and locate this View to insert it anywhere you want inside the content.', 'wpv-views' ) . '</p><p>'
						. sprintf( __('To display as a widget, go to <a href="%s">Appearance -> Widgets</a> and select the <strong>WP Views Filter</strong> widget.', 'wpv-views'), admin_url( 'widgets.php' ) ) . '</p><p>'
						. __( 'When you insert the search form, Views will offer you where to display the results.', 'wpv-views' )
						. '</p>',
		'classname'		=> 'js-display-view-howto js-for-view-purpose-parametric'
	);
	wpv_toolset_help_box( $parametric );
}

//----------------------------------------
// Help boxes texts - embedded plugin mode
//----------------------------------------

function wpv_get_embedded_promotional_box( $type = 'view' ) {
	$target = __( 'View', 'wpv-views' );
	switch ( $type ) {
		case 'view':
			$target = __( 'View', 'wpv-views' );
			break;
		case 'ct':
			$target = __( 'Content Template', 'wpv-views' );
			break;
		case 'wpa':
			$target = __( 'WordPress Archive', 'wpv-views' );
			break;
	}
	$promo_link_args = array(
		'query'		=> array(
			'utm_source'	=> 'plugin',
			'utm_campaign'	=> 'views',
			'utm_medium'	=> 'gui',
			'utm_term'		=> 'Get Views'

		),
		'anchor'	=> 'views'
	);
	$promo_link = WPV_Admin_Messages::get_documentation_promotional_link( $promo_link_args, 'https://toolset.com/home/toolset-components/' );
	$data = array(
		'text'			=> '<p>' . sprintf( __('You are viewing the read-only version of this %s.', 'wpv-views'), $target )
						. WPV_MESSAGE_SPACE_CHAR
						. __('To edit it, you need to get the Views plugin.', 'wpv-views')
						. '</p>'
						. '<p><a href="' . $promo_link . '" title="" class="button button-primary-toolset" target="_blank">'
						. __( 'Get Views', 'wpv-views' )
						. '</a></p>',
		'close'			=> 'false',
		'hidden'		=> 'false',
		'classname'		=> ''
	);
	wpv_toolset_help_box( $data );
}

function wpv_get_embedded_view_introduction_data() {
	$promo_link_args = array(
		'query'		=> array(
			'utm_source'	=> 'plugin',
			'utm_campaign'	=> 'views',
			'utm_medium'	=> 'gui',
			'utm_term'		=> 'Get Views'

		),
		'anchor'	=> 'views'
	);
	$promo_link = WPV_Admin_Messages::get_documentation_promotional_link( $promo_link_args, 'https://toolset.com/home/toolset-components/' );
	$promotional = '<p class="toolset-promotional">' . __( 'You are viewing the read-only version of this View. To edit it, you need to get Views plugin.', 'wpv-views' )
				. '&nbsp;&nbsp;&nbsp;<a href="' . $promo_link . '" title="" class="button button-primary-toolset" target="_blank">' . __( 'Get Views', 'wpv-views' ) . '</a>'
				. '</p>';
	$all = array(
		'text'			=> $promotional . '<p>' . __('A View loads content from the database and displays it anyway you choose.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The Query section lets you choose the content to load.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('A basic query selects all items of a chosen type.', 'wpv-views') . '</p>'
						. '<ul><li>' . __('You can refine the selection by adding filters.', 'wpv-views') . '</li>'
						. '<li>' . __('At the bottom of this page you will find the Loop section, where you control the output.', 'wpv-views') . '</li></ul>',
		'close'			=> 'false',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-query js-for-view-purpose-all'
	);
	wpv_toolset_help_box($all);
	$pagination = array(
		'text'			=> $promotional . '<p>' . __('A View loads content from the database and displays it anyway you choose.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The Query section lets you choose the content to load.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('A basic query selects all items of a chosen type.', 'wpv-views') . '</p>'
						. '<ul><li>' . __('You can refine the selection by adding filters.', 'wpv-views') . '</li>'
						. '<li>' . __('The Front-end Filter section includes the pagination controls, allowing visitors to choose which results page to show.', 'wpv-views') . '</li>'
						. '<li>' . __('At the bottom of this page you will find the Loop section, where you control the output.', 'wpv-views') . '</li></ul>',
		'tutorial-button-text'	=> htmlentities( __('Creating paginated listings with Views', 'wpv-views'), ENT_QUOTES ),
		'tutorial-button-url'	=> WPV_LINK_CREATE_PAGINATED_LISTINGS,
		'close'			=> 'false',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-query js-for-view-purpose-pagination'
	);
	wpv_toolset_help_box($pagination);
	$slider = array(
		'text'			=> $promotional . '<p>' . __('A View loads content from the database and displays it anyway you choose.','wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The Query section lets you choose the content to load.','wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('A basic query selects all items of a chosen type.','wpv-views') . '</p>'
						. '<ul><li>' . __('You can refine the selection by adding filters.','wpv-views') . '</li>'
						. '<li>' . __('The Front-end Filter section includes the slider controls, allowing visitors switch between slides.','wpv-views') . '</li>'
						. '<li>' . __('At the bottom of this page you will find a slide Content Template, where you design slides.', 'wpv-views') . '</li></ul>',
		'tutorial-button-text'	=> htmlentities( __('Creating sliders with Views', 'wpv-views'),ENT_QUOTES ),
		'tutorial-button-url'	=> WPV_LINK_CREATE_SLIDERS,
		'close'			=> 'false',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-query js-for-view-purpose-slider'
	);
	wpv_toolset_help_box($slider);
	$parametric = array(
		'text'			=> $promotional . '<p>' . __('Building a View for a custom search:', 'wpv-views') . '</p>'
						. '<ol><li>' . __('Select which content to load in the \'Content Selection\' section.', 'wpv-views') . '</li>'
						. '<li>' . __('Add filter input to the Filter section.', 'wpv-views') . '</li>'
						. '<li>' . __('Select advanced search options in the \'Custom Search Settings\' section.', 'wpv-views') . '</li>'
						. '<li>' . __('Design the search results in the Loop section.', 'wpv-views') . '</li></ol>'
						. '<p>' . __('Remember to click on Update after you complete each section and before you continue to the next section.', 'wpv-views') . '</p>',
		'tutorial-button-text'	=> htmlentities( __('Creating custom searches with Views', 'wpv-views'), ENT_QUOTES ),
		'tutorial-button-url'	=> WPV_LINK_CREATE_PARAMETRIC_SEARCH,
		'close'			=> 'false',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-query js-for-view-purpose-parametric'
	);
	wpv_toolset_help_box($parametric);
	$full = array(
		'text'			=> $promotional . '<p>' . __('A View loads content from the database and displays it anyway you choose.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The Query section lets you choose the content to load.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('A basic query selects all items of a chosen type.', 'wpv-views') . '</p>'
						. '<ul><li>' . __('You can refine the selection by adding filters.','wpv-views') . '</li>'
						. '<li>' . __('The Front-end filter section lets you add pagination, slider controls and custom search to the View.', 'wpv-views') . '</li>'
						. '<li>' .  __('At the bottom of this page you will find the Loop section, where you control the output.', 'wpv-views') . '</li></ul>',
		'close'			=> 'false',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-query js-for-view-purpose-full'
	);
	wpv_toolset_help_box($full);
}

function wpv_get_embedded_content_template_introduction_data() {
	$all = array(
		'text'			=> '<p>' . __('This Content Template replaces the content area of the posts that you assign it to.', 'wpv-views')
						. WPV_MESSAGE_SPACE_CHAR .  __('It can be used to tweak the content of a post when it is displayed alone or in an archive page.', 'wpv-views') . '</p>'
						. '<p>' . __( 'You can also call this Template using a shortcode [wpv-post-body view_template="XX"] to render specific information about the current post.', 'wpv-views' ) . '</p>'
						. '<p>' . __('You can add shortcodes to post fields, and also your own HTML and CSS to style the fields and design the page template.', 'wpv-views') . '</p>',
		'tutorial-button-text'	=> htmlentities( __('Content Template documentation', 'wpv-views'), ENT_QUOTES ),
		'tutorial-button-url'	=> WPV_LINK_CONTENT_TEMPLATE_DOCUMENTATION,
		'close'			=> 'false',
		'hidden'		=> 'false'
	);
	wpv_toolset_help_box($all);
}

function wpv_get_embedded_wordpress_archive_introduction_data() {
	$promo_link_args = array(
		'query'		=> array(
			'utm_source'	=> 'plugin',
			'utm_campaign'	=> 'views',
			'utm_medium'	=> 'gui',
			'utm_term'		=> 'Get Views'

		),
		'anchor'	=> 'views'
	);
	$promo_link = WPV_Admin_Messages::get_documentation_promotional_link( $promo_link_args, 'https://toolset.com/home/toolset-components/' );
	$promotional = '<p class="toolset-promotional">' . __( 'You are viewing the read-only version of this WordPress Archive. To edit it, you need to get Views plugin.', 'wpv-views' )
				. '&nbsp;&nbsp;&nbsp;<a href="' . $promo_link . '" title="" class="button-primary button-primary-toolset">' . __( 'Get Views', 'wpv-views' ) . '</a>'
				. '</p>';
	$all = array(
		'text'			=> $promotional . '<p>' . __('This WordPress Archive replaces the natural archive loops created by WordPress.', 'wpv-views') . '</p>',
		'tutorial-button-text'	=> htmlentities( __('WordPress Archives documentation', 'wpv-views'), ENT_QUOTES ),
		'tutorial-button-url'	=> WPV_LINK_WORDPRESS_ARCHIVE_DOCUMENTATION,
		'close'			=> 'false',
		'hidden'		=> 'false'
	);
	wpv_toolset_help_box($all);
}

function wpv_get_embedded_layouts_loop_introduction_data() {
	$promo_link_args = array(
		'query'		=> array(
			'utm_source'	=> 'viewsplugin',
			'utm_campaign'	=> 'views',
			'utm_medium'	=> 'embedded-archive-loop-promotional-link',
			'utm_term'		=> 'Get Views'

		),
		'anchor'	=> 'views'
	);
	$promo_link = WPV_Admin_Messages::get_documentation_promotional_link( $promo_link_args, 'https://toolset.com/home/toolset-components/' );
	$promotional = '<p class="toolset-promotional">' . __( 'You are viewing the read-only version of this WordPress Archive. To edit it, you need to get Views plugin.', 'wpv-views' )
				. '&nbsp;&nbsp;&nbsp;<a href="' . $promo_link . '" title="" class="button-primary button-primary-toolset">' . __( 'Get Views', 'wpv-views' ) . '</a>'
				. '</p>';
	$all = array(
		'text'			=> $promotional . '<p>' . __('This WordPress Archive replaces the natural archive loops created by WordPress.', 'wpv-views') . '</p>',
		'tutorial-button-text'	=> htmlentities( __('WordPress Archives documentation', 'wpv-views'), ENT_QUOTES ),
		'tutorial-button-url'	=> WPV_LINK_WORDPRESS_ARCHIVE_DOCUMENTATION,
		'close'			=> 'false',
		'hidden'		=> 'false'
	);
	wpv_toolset_help_box($all);
}

function wpv_get_embedded_view_filter_introduction_data() {
	$pagination = array(
		'text'			=> '<p>' . __('Pagination lets you break the results into separate pages.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('This way, you can display a large number of results, in shorter pages.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('You can insert next/previous links and page selectors, for navigating directly to a specific page.', 'wpv-views') . '</p>'
						. '<ul><li>' . __('The first part of this section lets you choose how pagination works.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('Select how many results to show in each page and how pages transition.', 'wpv-views') . '</li>'
						. '<li>' . __('The second part of this section lets you design the pagination controls that would appear on the page for visitors.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The toolbar above the HTML editor includes buttons for inserting various controls.', 'wpv-views') . '</li>'
						. '<li>' . __('Besides pagination, you can also insert custom search filters and text search controls.', 'wpv-views') . '</li></ul>',
		'close'			=> 'false',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-filter js-for-view-purpose-pagination'
	);
	wpv_toolset_help_box($pagination);
	$slider = array(
		'text'			=> '<p>' . __('The pagination section lets you build sliders with Views.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The View will display each slide at a time and allow visitors to switch between slides using next/previous links and slide selectors.', 'wpv-views') . '</p>'
						. '<ul><li>' . __('The first part of this section lets you choose how the slider pagination works.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('Select how many results to show in each slide and how slides transition.', 'wpv-views') . '</li>'
						. '<li>' . __('The second part of this section lets you design the transition controls for the slider.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The toolbar above the HTML editor includes buttons for inserting the slide transition controls.', 'wpv-views') . '</li></ul>',
		'tutorial-button-text'	=> htmlentities( __('Creating sliders with Views', 'wpv-views'), ENT_QUOTES ),
		'tutorial-button-url'	=> WPV_LINK_DESIGN_SLIDER_TRANSITIONS,
		'close'			=> 'false',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-filter js-for-view-purpose-slider'
	);
	wpv_toolset_help_box($slider);
	$full = array(
		'text'			=> '<p>' . __('Pagination lets you break the results into separate pages.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('This way, you can display a large number of results, in shorter pages.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('You can insert next/previous links and page selectors, for navigating directly to a specific page.','wpv-views') . '</p><p>'
						. __('Using pagination you can also implement sliders.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. '<a href="' . WPV_LINK_CREATE_SLIDERS . '" target="_blank">' . __('Learn how to create sliders with Views.', 'wpv-views') . '</a></p>'
						. '<ul><li>' . __('The first part of this section lets you choose how pagination works.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('Select how many results to show in each page and how pages transition.', 'wpv-views') . '</li>'
						. '<li>' . __('The second part of this section lets you design the pagination controls that would appear on the page for visitors.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The toolbar above the HTML editor includes buttons for inserting various controls.', 'wpv-views') . '</li>'
						. '<li>' . __('Besides pagination and slider transition controls, you can also insert custom search filters and text search controls.', 'wpv-views') . '</li></ul>'
						. '<p><a href="' . WPV_LINK_CREATE_PARAMETRIC_SEARCH . '" target="_blank">' . __('Learn how to create custom searches with Views.', 'wpv-views') . '</a></p>',
		'close'			=> 'false',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-filter js-for-view-purpose-full'
	);
	wpv_toolset_help_box($full);
	$parametric = array(
		'text'			=> '<p>' . sprintf(__('To create a custom search, position the cursor between the %s and %s shortcodes and click on the <strong>New filter</strong> button to insert filter elements.', 'wpv-views'),'<strong>[wpv-filter-controls]</strong>','<strong>[/wpv-filter-controls]</strong>') . WPV_MESSAGE_SPACE_CHAR
						. __('Your custom search can contain filters by custom fields, taxonomies or even post relationships.', 'wpv-views') . '</p>'
						. '<p>' . __('You can also click on the <strong>Text search</strong> button to add a search box for visitors', 'wpv-views') . '</p>'
						. '<p>'. __('Use HTML and CSS to style the filter.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('Remember to include the <strong>Submit</strong> button for the form.', 'wpv-views'). '</p>'
						. '<p><a href="' . WPV_LINK_CREATE_PARAMETRIC_SEARCH . '" target="_blank">' . __('Learn how to create custom searches with Views.', 'wpv-views') . '</a></p>'
						. ' <input id="wpv-parametric-hint-dismiss" type="hidden" class="js-wpv-parametric-hint-dismiss" data-nonce="' . wp_create_nonce( 'wpv_view_parametric_hint_dismiss_nonce')  . '" /> ',
		'close'			=> 'false',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-filter js-for-view-purpose-parametric',
	);
	wpv_toolset_help_box($parametric);
}

function wpv_get_embedded_view_layout_introduction_data() {
	$data = array(
		'text'			=> '<p>' . __('The layout HTML box lets you output your View results and style them on your page.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						.  __('Views will provide a wizard that lets you design how the results display.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						.  __('You can also insert fields manually.', 'wpv-views') . '</p>'
						. '<p>' . __('The full Views plugin will let you display Content Templates as part of the loop.', 'wpv-views') . '</p>'
						. '<p>' . __('Besides these helpers, you can edit the HTML content yourself by writing your own HTML and CSS.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. __('The View will iterate through the the results and display them one by one.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
						. sprintf( __('Learn more by reading the %sViews Loop documentation%s.', 'wpv-views'), '<a href="' . WPV_LINK_LOOP_DOCUMENTATION . '" target="_blank">', '</a>' ) . '</p>',
		'close'			=> 'false',
		'hidden'		=> 'true',
		'classname'		=> 'js-metasection-help-layout js-for-view-purpose-all js-for-view-purpose-pagination js-for-view-purpose-slider js-for-view-purpose-parametric js-for-view-purpose-full',
	);
	wpv_toolset_help_box( $data );
}

//----------------------------------------
// Help boxes texts - parametric search workflow
//----------------------------------------

/**
* wpv_insert_form_workflow_help_boxes
*
* Display a help box in the post create/edit screen above the post editor, with instructions to complete the workflow for parametric search
*
* @param $post (object)
*
* @since 1.7.0
*/

add_action( 'edit_form_after_title', 'wpv_insert_form_workflow_help_boxes' );

function wpv_insert_form_workflow_help_boxes( $post ) {
	$excluded_post_type_slugs = array();
	$excluded_post_type_slugs = apply_filters( 'wpv_admin_exclude_post_type_slugs', $excluded_post_type_slugs );
	if ( ! in_array( $post->post_type, $excluded_post_type_slugs ) ) {
		$has_view = false;
		$has_orig = false;
		$has_orig_completed = false;
		$has_completed = false;
		$has_orig_type = '';
		$orig_title = '';
		$orig_content = '';
		$view_name = '<strong class="js-wpv-insert-view-form-results-helper-name"></strong>';
		$view_shortcode = '<code class="js-wpv-insert-view-form-results-helper-shortcode"></code>';
		$view_classname = '';

		//-----
		// Preparation
		//-----

		if ( isset( $_GET['completeview'] ) && ! empty( $_GET['completeview'] ) ) {

			global $wpdb;
			$view_id = $_GET['completeview'];
			$title = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_title FROM {$wpdb->posts}
					WHERE ID = %d
					LIMIT 1",
					$view_id
				)
			);
			if ( $title !== NULL ) {
				$has_view = true;
				$view_name = '<strong class="js-wpv-insert-view-form-results-helper-name">' . $title . '</strong>';
				$view_shortcode = '<code class="js-wpv-insert-view-form-results-helper-shortcode">[wpv-view name="' . $title . '"]</code>';
				$view_classname = ' js-wpv-insert-form-workflow-help-box-for-' . $view_id;
				$view_classname_after = ' js-wpv-insert-form-workflow-help-box-for-after-' . $view_id;
				if ( strpos( $post->post_content, '[wpv-view name="' . $title ) !== false ) {
					$has_completed = true;
				}
				if ( isset( $_GET['origid'] ) && !empty( $_GET['origid'] ) &&  $_GET['origid'] != '0' ) {
					$orig_id = $_GET['origid'];
					if ( $orig_id == 'widget' || $orig_id == 'layout' ) {
						$has_orig = true;
						$has_orig_type = $orig_id;
					} else {
						$orig_data_array = $wpdb->get_results(
							$wpdb->prepare(
								"SELECT post_title, post_content FROM {$wpdb->posts}
								WHERE ID = %d
								LIMIT 1",
								$orig_id
							)
						);
						if ( !empty( $orig_data_array ) ) {
							$has_orig = true;
							$has_orig_type = 'post';
							$orig_data = $orig_data_array[0];
							$orig_title = $orig_data->post_title;
							$orig_content = $orig_data->post_content;
							if ( strpos( $orig_content, '[wpv-form-view name="' . $title ) !== false ) {
								$has_orig_completed = true;
							}
						}
					}
				}
			}
		}

		//-----
		// Execution
		//-----

		if ( ! $has_view ) {
			// Add the basic help box for the SELF case, hidden
			$data_def = array(
				'text'			=> '<h2>' . __( 'Complete the custom search setup by inserting the results', 'wpv-views' ) . '</h2>'
								. '<p>' . sprintf( __('This page should display the results of the custom search provided by the View <strong>%s</strong>.', 'wpv-views'), $view_name ) . WPV_MESSAGE_SPACE_CHAR
								. sprintf( __( 'You can copy and paste this shortcode wherever you want to display the results: %s', 'wpv-views' ), $view_shortcode ) . '</p>'
								. '<p>' . sprintf( __( 'Also, you can click in the <strong>Views</strong> button and select <strong>%s</strong> in the <em>View</em> section.', 'wpv-views' ), $view_name ) . WPV_MESSAGE_SPACE_CHAR
								. __( 'Then, select the option to display just the results for the custom search.', 'wpv-views' ) . '</p>'
								. '<p>' . sprintf( __( 'Finally, if you use a page builder, activate it on this page, create a <strong>Toolset View</strong> widget/module/block and select <strong>%s</strong> in the <em>View</em> selection control.', 'wpv-views' ), $view_name ) . WPV_MESSAGE_SPACE_CHAR
								. __( 'Then, select the option to display just the results for the custom search.', 'wpv-views' ) . '</p>',
				'close'			=> 'true',
				'hidden'		=> 'true',
				'classname'		=> 'js-wpv-insert-form-workflow-help-box'
			);
			wpv_toolset_help_box( $data_def );
		} else {
			// There is a $_GET['completeview'] attribute, and it matches an existing View
			if ( $has_orig ) {
				// There is also a $_GET['origid'] attribute
				switch ( $has_orig_type ) {
					case 'post':
						// Has View data and orig data, so show everything
						if ( $has_completed ) {
							if ( $has_orig_completed ) {
								// Target has shortcode, and orig has shortcode
								$data = array(
									'text'			=> '<h2>' . __( 'Custom search setup completed!', 'wpv-views' ) . '</h2>'
													. '<p>' . sprintf( __('You have finished the setup of the custom search provided by the View <strong>%s</strong>.', 'wpv-views'), $view_name ) . WPV_MESSAGE_SPACE_CHAR
													. sprintf( __( 'The form will appear on the page <strong>%s</strong> and the results will be shown in this page.', 'wpv-views' ), $orig_title ) . '</p>'
													. '<p><a href="#" class="button button-small button-primary-toolset js-wpv-insert-form-workflow-help-box-close">' . __( 'Close', 'wpv-views' ) . '</a></p>',
									'close'			=> 'true',
									'hidden'		=> 'false',
									'classname'		=> 'js-wpv-insert-form-workflow-help-box' . $view_classname
								);
								wpv_toolset_help_box( $data );
							} else {
								// Target has shortcode, but orig lacks shortcode
								$data = array(
									'text'			=> '<h2>' . sprintf( __( 'Don\'t forget to insert the custom search form into %s', 'wpv-views' ), $orig_title ) . '</h2>'
													. '<p>' . sprintf( __('You are almost done with this custom search provided by the View <strong>%s</strong>.', 'wpv-views'), $view_name ) . WPV_MESSAGE_SPACE_CHAR
													. __( 'This page already has all it needs to display the results.', 'wpv-views' ) . WPV_MESSAGE_SPACE_CHAR
													. sprintf( __( 'Remember to get back to the other tab in your browser and insert the search View into <strong>%s</strong>.', 'wpv-views' ), $orig_title ) . '</p>'
													. '<p><a href="#" class="button button-small button-primary-toolset js-wpv-insert-form-workflow-help-box-close">' . __( 'Close', 'wpv-views' ) . '</a></p>',
									'close'			=> 'true',
									'hidden'		=> 'false',
									'classname'		=> 'js-wpv-insert-form-workflow-help-box' . $view_classname
								);
								wpv_toolset_help_box( $data );
							}
						} else {
							$data = array(
								'text'			=> '<h2>' . __( 'Complete the custom search setup by inserting the results', 'wpv-views' ) . '</h2>'
												. '<p>' . sprintf( __('This page should display the results of the custom search provided by the View <strong>%s</strong>.', 'wpv-views'), $view_name ) . WPV_MESSAGE_SPACE_CHAR
												. sprintf( __( 'You can copy and paste this shortcode wherever you want to display the results: %s', 'wpv-views' ), $view_shortcode ) . '</p>'
												. '<p>' . sprintf( __( 'Also, you can click in the <strong>Views</strong> button and select <strong>%s</strong> in the <em>View</em> section.', 'wpv-views' ), $view_name ) . WPV_MESSAGE_SPACE_CHAR
												. __( 'Then, select the option to display just the results for the custom search.', 'wpv-views' ) . '</p>'
												. '<p>' . sprintf( __( 'Finally, if you use a page builder, activate it on this page, create a <strong>Toolset View</strong> widget/module/block and select <strong>%s</strong> in the <em>View</em> selection control.', 'wpv-views' ), $view_name ) . WPV_MESSAGE_SPACE_CHAR
												. __( 'Then, select the option to display just the results for the custom search.', 'wpv-views' ) . '</p>',
								'close'			=> 'true',
								'hidden'		=> 'false',
								'classname'		=> 'js-wpv-insert-form-workflow-help-box' . $view_classname
							);
							wpv_toolset_help_box( $data );
							if ( $has_orig_completed ) {
								// After inserting the shortcode, we can call it complete!
								$data_after = array(
									'text'			=> '<h2>' . __( 'Custom search setup completed!', 'wpv-views' ) . '</h2>'
													. '<p>' . sprintf( __('You have finished the setup of the custom search provided by the View <strong>%s</strong>.', 'wpv-views'), $view_name ) . WPV_MESSAGE_SPACE_CHAR
													. sprintf( __( 'The form will appear on the page <strong>%s</strong> and the results will be shown in this page.', 'wpv-views' ), $orig_title ) . '</p>'
													. '<p><a href="#" class="button button-small button-primary-toolset js-wpv-insert-form-workflow-help-box-close">' . __( 'Close', 'wpv-views' ) . '</a></p>',
									'close'			=> 'true',
									'hidden'		=> 'true',
									'classname'		=> 'js-wpv-insert-form-workflow-help-box-after' . $view_classname_after
								);
								wpv_toolset_help_box( $data_after );
							} else {
								// After inserting, origin is lacking shortcode
								$data_after = array(
									'text'			=> '<h2>' . sprintf( __( 'Don\'t forget to insert the custom search form into %s', 'wpv-views' ), $orig_title ) . '</h2>'
													. '<p>' . sprintf( __('You are almost done with this custom search provided by the View <strong>%s</strong>.', 'wpv-views'), $view_name ) . WPV_MESSAGE_SPACE_CHAR
													. __( 'This page already has all it needs to display the results.', 'wpv-views' ) . WPV_MESSAGE_SPACE_CHAR
													. sprintf( __( 'Remember to get back to the other tab in your browser and insert the search View into <strong>%s</strong>.', 'wpv-views' ), $orig_title ) . '</p>'
													. '<p><a href="#" class="button button-small button-primary-toolset js-wpv-insert-form-workflow-help-box-close">' . __( 'Close', 'wpv-views' ) . '</a></p>',
									'close'			=> 'true',
									'hidden'		=> 'true',
									'classname'		=> 'js-wpv-insert-form-workflow-help-box-after' . $view_classname_after
								);
								wpv_toolset_help_box( $data_after );
							}
						}
						break;
					case 'widget':
					case 'layout':
						if ( $has_orig_type == 'widget' ) {
							$orig_label = __( 'widget', 'wpv-views' );
						} else if ( $has_orig_type == 'layout' ) {
							$orig_label = __( 'layout', 'wpv-views' );
						}
						// Has View data and orig data from widget, so show everything
						if ( $has_completed ) {
							$data = array(
								'text'			=> '<h2>' . __( 'Custom search setup completed!', 'wpv-views' ) . '</h2>'
													. '<p>' . sprintf( __('This page will display the results of the custom search provided by the View <strong>%s</strong> used in a %s.', 'wpv-views'), $view_name, $orig_label ) . '</p>'
													. '<p>' . sprintf( __( 'Remember to get back to the other tab in your browser and save the %s settings.', 'wpv-views' ), $orig_label ) . '</p>'
													. '<p><a href="#" class="button button-small button-primary-toolset js-wpv-insert-form-workflow-help-box-close">' . __( 'Close', 'wpv-views' ) . '</a></p>',
								'close'			=> 'true',
								'hidden'		=> 'false',
								'classname'		=> 'js-wpv-insert-form-workflow-help-box' . $view_classname
							);
						} else {
							$data = array(
								'text'			=> '<h2>' . __( 'Complete the custom search setup by inserting the results', 'wpv-views' ) . '</h2>'
												. '<p>' . sprintf( __('This page should display the results of the custom search provided by the View <strong>%s</strong> used in a %s.', 'wpv-views'), $view_name, $orig_label ) . WPV_MESSAGE_SPACE_CHAR
												. sprintf( __( 'You can copy and paste this shortcode wherever you want to display the results: %s', 'wpv-views' ), $view_shortcode ) . '</p>'
												. '<p>' . sprintf( __( 'Also, you can click in the <strong>Views</strong> button and select <strong>%s</strong> in the <em>View</em> section.', 'wpv-views' ), $view_name ) . WPV_MESSAGE_SPACE_CHAR
												. __( 'Then, select the option to display just the results for the custom search.', 'wpv-views' ) . '</p>'
												. '<p>' . sprintf( __( 'Finally, if you use a page builder, activate it on this page, create a <strong>Toolset View</strong> widget/module/block and select <strong>%s</strong> in the <em>View</em> selection control.', 'wpv-views' ), $view_name ) . WPV_MESSAGE_SPACE_CHAR
												. __( 'Then, select the option to display just the results for the custom search.', 'wpv-views' ) . '</p>',
								'close'			=> 'true',
								'hidden'		=> 'false',
								'classname'		=> 'js-wpv-insert-form-workflow-help-box' . $view_classname
							);
							$data_after = array(
								'text'			=> '<h2>' . __( 'Custom search setup completed!', 'wpv-views' ) . '</h2>'
													. '<p>' . sprintf( __('This page will display the results of the custom search provided by the View <strong>%s</strong> used in a %s.', 'wpv-views'), $view_name, $orig_label ) . '</p>'
													. '<p>' . sprintf( __( 'Remember to get back to the other tab in your browser and save the %s settings.', 'wpv-views' ), $orig_label ) . '</p>'
													. '<p><a href="#" class="button button-small button-primary-toolset js-wpv-insert-form-workflow-help-box-close">' . __( 'Close', 'wpv-views' ) . '</a></p>',
								'close'			=> 'true',
								'hidden'		=> 'true',
								'classname'		=> 'js-wpv-insert-form-workflow-help-box-after' . $view_classname_after
							);
							wpv_toolset_help_box( $data_after );
						}
						wpv_toolset_help_box( $data );
						break;
					default:
						break;
				}
				// We also need to add basic help box, for maybe future SELF cases, hidden
				$data_def = array(
					'text'			=> '<h2>' . __( 'Complete the custom search setup by inserting the results', 'wpv-views' ) . '</h2>'
									. '<p>' . sprintf( __('This page should display the results of the custom search provided by the View <strong>%s</strong>.', 'wpv-views'), $view_name ) . WPV_MESSAGE_SPACE_CHAR
									. sprintf( __( 'You can copy and paste this shortcode wherever you want to display the results: %s', 'wpv-views' ), $view_shortcode ) . '</p>'
									. '<p>' . sprintf( __( 'Also, you can click in the <strong>Views</strong> button and select <strong>%s</strong> in the <em>View</em> section.', 'wpv-views' ), $view_name ) . WPV_MESSAGE_SPACE_CHAR
									. __( 'Then, select the option to display just the results for the custom search.', 'wpv-views' ) . '</p>'
									. '<p>' . sprintf( __( 'Finally, if you use a page builder, activate it on this page, create a <strong>Toolset View</strong> widget/module/block and select <strong>%s</strong> in the <em>View</em> selection control.', 'wpv-views' ), $view_name ) . WPV_MESSAGE_SPACE_CHAR
									. __( 'Then, select the option to display just the results for the custom search.', 'wpv-views' ) . '</p>',
					'close'			=> 'true',
					'hidden'		=> 'true',
					'classname'		=> 'js-wpv-insert-form-workflow-help-box'
				);
				wpv_toolset_help_box( $data_def );
			} else {
				// There is no valid $_GET['origid'] attribute
				// We check whether the current page has the shortcode already inserted or not
				if ( $has_completed ) {
					// It has View data, no orig data and is completed
					$data_already_inserted = array(
						'text'			=> '<h2>' . __( 'Custom search setup completed!', 'wpv-views' ) . '</h2>'
										. '<p>' . sprintf( __('You have finished the setup of the custom search provided by the View <strong>%s</strong>.', 'wpv-views'), $view_name ) . '</p>'
										. '<p><a href="#" class="button button-small button-primary-toolset js-wpv-insert-form-workflow-help-box-close">' . __( 'Close', 'wpv-views' ) . '</a></p>',
						'close'			=> 'true',
						'hidden'		=> 'false',
						'classname'		=> 'js-wpv-insert-form-workflow-help-box-completed'
					);
					wpv_toolset_help_box( $data_already_inserted );
					// We also add the basic help box for SELF, hidden
					$data_def = array(
						'text'			=> '<h2>' . __( 'Complete the custom search setup by inserting the results', 'wpv-views' ) . '</h2>'
										. '<p>' . sprintf( __('This page should display the results of the custom search provided by the View <strong>%s</strong>.', 'wpv-views'), $view_name ) . WPV_MESSAGE_SPACE_CHAR
										. sprintf( __( 'You can copy and paste this shortcode wherever you want to display the results: %s', 'wpv-views' ), $view_shortcode ) . '</p>'
										. '<p>' . sprintf( __( 'Also, you can click in the <strong>Views</strong> button and select <strong>%s</strong> in the <em>View</em> section.', 'wpv-views' ), $view_name ) . WPV_MESSAGE_SPACE_CHAR
										. __( 'Then, select the option to display just the results for the custom search.', 'wpv-views' ) . '</p>'
										. '<p>' . sprintf( __( 'Finally, if you use a page builder, activate it on this page, create a <strong>Toolset View</strong> widget/module/block and select <strong>%s</strong> in the <em>View</em> selection control.', 'wpv-views' ), $view_name ) . WPV_MESSAGE_SPACE_CHAR
										. __( 'Then, select the option to display just the results for the custom search.', 'wpv-views' ) . '</p>',
						'close'			=> 'true',
						'hidden'		=> 'true',
						'classname'		=> 'js-wpv-insert-form-workflow-help-box'
					);
					wpv_toolset_help_box( $data_def );
				} else {
					// It has View data, no orig data and is not completed
					// So we display the basic help box with View data
					$data = array(
						'text'			=> '<h2>' . __( 'Complete the custom search setup by inserting the results', 'wpv-views' ) . '</h2>'
										. '<p>' . sprintf( __('This page should display the results of the custom search provided by the View <strong>%s</strong>.', 'wpv-views'), $view_name ) . WPV_MESSAGE_SPACE_CHAR
										. sprintf( __( 'You can copy and paste this shortcode wherever you want to display the results: %s', 'wpv-views' ), $view_shortcode ) . '</p>'
										. '<p>' . sprintf( __( 'Also, you can click in the <strong>Views</strong> button and select <strong>%s</strong> in the <em>View</em> section.', 'wpv-views' ), $view_name ) . WPV_MESSAGE_SPACE_CHAR
										. __( 'Then, select the option to display just the results for the custom search.', 'wpv-views' ) . '</p>'
										. '<p>' . sprintf( __( 'Finally, if you use a page builder, activate it on this page, create a <strong>Toolset View</strong> widget/module/block and select <strong>%s</strong> in the <em>View</em> selection control.', 'wpv-views' ), $view_name ) . WPV_MESSAGE_SPACE_CHAR
										. __( 'Then, select the option to display just the results for the custom search.', 'wpv-views' ) . '</p>',
						'close'			=> 'true',
						'hidden'		=> 'false',
						'classname'		=> 'js-wpv-insert-form-workflow-help-box' . $view_classname
					);
					wpv_toolset_help_box( $data );
				}
			}
		}
	}
}

//----------------------------------------
// Formatting instruction boxes
//----------------------------------------

function wpv_views_instructions_section_data( $section = '' ) {
	$return = array(
		'classname' => '',
		'title' => '',
		'content' => '',
		'table' => array(),
		'content_extra' => ''
	);
	switch ( $section ) {
		case 'styling':
			$return = array(
				'classname' => 'js-wpv-editor-instructions-for-styling',
				'title' => __( 'Styling', 'wpv-views' ),
				'content' => '',
				'table' => array(
					array(
						'element' => '<span class="wpv-code wpv-code-html">&lt;p&gt;</span>' . __( 'Content', 'wpv-views' ) . '<span class="wpv-code wpv-code-html">&lt;/p&gt;</span>',
						'description' => __( 'Paragraph.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-html">&lt;br /&gt;</span>',
						'description' => __( 'Line break.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-html">&lt;div&gt;</span>' . __( 'Content', 'wpv-views' ) . '<span class="wpv-code wpv-code-html">&lt;/div&gt;</span>',
						'description' => __( 'Blocking div.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-html">&lt;span&gt;</span>' . __( 'Content', 'wpv-views' ) . '<span class="wpv-code wpv-code-html">&lt;/span&gt;</span>',
						'description' => __( 'Inline formatting.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-html">&lt;a ...&gt;</span>' . __( 'Text', 'wpv-views' ) . '<span class="wpv-code wpv-code-html">&lt;/a&gt;</span>',
						'description' => __( 'Link.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-html">&lt;img ... /&gt;</span>',
						'description' => __( 'Image.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-html">&lt;ul&gt;<br />&lt;li&gt;</span>' . __( 'Item1', 'wpv-views' ) . '<span class="wpv-code wpv-code-html">&lt;/li&gt;<br />&lt;li&gt;</span>' . __( 'Item2' , 'wpv-views' ) . '<span class="wpv-code wpv-code-html">&lt;/li&gt;<br />&lt;/ul&gt;</span>',
						'description' => __( 'An unordered list.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-html">&lt;ol&gt;<br />&lt;li&gt;</span>' . __( 'Item1', 'wpv-views' ) . '<span class="wpv-code wpv-code-html">&lt;/li&gt;<br />&lt;li&gt;</span>' . __( 'Item2' , 'wpv-views' ) . '<span class="wpv-code wpv-code-html">&lt;/li&gt;<br />&lt;/ol&gt;</span>',
						'description' => __( 'An ordered list.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-html">&lt;strong&gt;</span>' . __( 'Content', 'wpv-views' ) . '<span class="wpv-code wpv-code-html">&lt;/strong&gt;</span>',
						'description' => __( 'Strong (bold) text.', 'wpv-views' )
					)
				),
				'content_extra' => ''
			);
			break;
		case 'fields-and-views':
			$return = array(
				'classname' => 'js-wpv-editor-instructions-for-fields-and-views',
				'title' => __( 'Fields and Views', 'wpv-views' ),
				'content' => '<p>'
						. __( 'Click on the <strong>Fields and Views</strong> button to insert fields that belong to the content, as well as Views and other Content Templates.', 'wpv-views' )
						. '</p>',
				'table' => array(),
				'content_extra' => ''
			);
			break;
		case 'media':
			$return = array(
				'classname' => 'js-wpv-editor-instructions-for-media',
				'title' => __( 'Media', 'wpv-views' ),
				'content' => '<p>'
						. __( 'The <strong>Media</strong> button opens the WordPress media window, where you can upload media and insert media that you’ve already uploaded here.', 'wpv-views' )
						. '</p>',
				'table' => array(),
				'content_extra' => ''
			);
			break;
		case 'pagination':
			if ( isset( $_GET['page'] ) && $_GET['page'] == 'view-archives-editor' ) {
				$return = false;
				break;
			}
			$return = array(
				'classname' => 'js-wpv-editor-instructions-for-pagination',
				'title' => __( 'Pagination', 'wpv-views' ),
				'content' => '',
				'table' => array(
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-pager-current-page]</span>',
						'description' => __( 'The current page number.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-pager-num-page]</span>',
						'description' => __( 'The number of pages.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-pager-current-page ...]</span>',
						'description' => __( 'Displays a pager with the current page selected.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'Depending on the value of the style parameter it displays a list of links to the other pages or a drop-down list to select another page.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-pager-prev-page]</span><br />' . __( 'Previous', 'wpv-views' ) . '<br /><span class="wpv-code wpv-code-shortcode">[/wpv-pager-prev-page]</span>',
						'description' => __( 'A link to the previous page.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'You can edit the text between the shortcodes and use any HTML.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'The link will appear only if there is a previous page.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-pager-next-page]</span><br />' . __( 'Next', 'wpv-views' ) . '<br /><span class="wpv-code wpv-code-shortcode">[/wpv-pager-next-page]</span>',
						'description' => __( 'A link to the next page.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'You can edit the text between the shortcodes and use any HTML.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'The link will appear only if there is a next page.', 'wpv-views' )
					)
				),
				'content_extra' => ''
			);
			break;
		case 'filter_section':
			$return = array(
				'classname' => 'js-wpv-editor-instructions-for-filter-section',
				'title' => __( 'The filter section', 'wpv-views' ),
				'content' => '',
				'table' => array(
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-filter-start]</span><br />' . __( 'Content', 'wpv-views' ) . '<br /><span class="wpv-code wpv-code-shortcode">[wpv-filter-end]</span>',
						'description' => __( 'Wrapper for the entire filter section, including the pagination controls and custom search.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'The filter section includes these shortcodes by default and you should not remove them.', 'wpv-views' )
					)
				),
				'content_extra' => ''
			);
			break;
		case 'parametric':
			$return = array(
				'classname' => 'js-wpv-editor-instructions-for-purpose js-wpv-display-for-purpose js-wpv-display-for-purpose-parametric js-wpv-display-for-purpose-full',
				'title' => __( 'Custom search shortcodes', 'wpv-views' ),
				'content' => '',
				'table' => array(
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-filter-controls]</span><br />' . __( 'Content', 'wpv-views' ) . '<br /><span class="wpv-code wpv-code-shortcode">[/wpv-filter-controls]</span>',
						'description' => __( 'Wrapper for the custom search controls.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'All fields must appear between these shortcodes.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-control-post-taxonomy]</span><br /><span class="wpv-code wpv-code-shortcode">[wpv-control-postmeta]</span><br /><span class="wpv-code wpv-code-shortcode">[wpv-control-post-relationship]</span>',
						'description' => __( 'A filter element.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'You can insert filter elements by clicking on the <strong>New filter</strong> button.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'To edit an existing filter, place the cursor inside it and click on <strong>Edit filter</strong>.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-filter-search-box ...]</span>',
						'description' => __( 'A text search input, which can search the title or title and content.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'To insert this shortcode and set its option, click on the <strong>Text search</strong> button.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-filter-spinner ...]</span>',
						'description' => __( 'A spinner, which is displayed when the filter needs update and hidden when update is complete.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'To insert, click on the <strong>Spinner graphics</strong> button.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-filter-reset ...]</span>',
						'description' => __( 'A button that resets the filter and allows visitors to start a fresh search.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'To insert click on the <strong>Clear form</strong> button.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-filter-submit ...]</span>',
						'description' => __( 'A submit button for the search filter.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'To insert click on the <strong>Submit button</strong> button.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'A submit button is not available for forms that have automatic updates on field changes.', 'wpv-views' )
					)
				),
				'content_extra' => ''
			);
			break;
		case 'layout_section':
			$return = array(
				'classname' => 'js-wpv-editor-instructions-for-layout-section',
				'title' => __( 'Output loop', 'wpv-views' ),
				'content' => '',
				'table' => array(
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-layout-start]<br />[wpv-layout-end]</span>',
						'description' => __( 'Wrapper for the Loop section.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'These shortcodes are added by default and you should keep them.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'Add all your elements between these shortcodes.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-items-found]</span><br />' . __( 'Content', 'wpv-views' ) . '<br /><span class="wpv-code wpv-code-shortcode">[/wpv-items-found]</span>',
						'description' => __( 'Wrapper for what to display when the query finds results.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-no-items-found]</span><br />' . __( 'Content', 'wpv-views' ) . '<br /><span class="wpv-code wpv-code-shortcode">[/wpv-no-items-found]</span>',
						'description' => __( 'Wrapper for what to display when the query doesn\'t find results.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-html">&lt;wpv-loop&gt;</span><br />' . __( 'Content', 'wpv-views' ) . '<br /><span class="wpv-code wpv-code-html">&lt;/wpv-loop&gt;</span>',
						'description' => __( 'The main loop over every item loaded from the database.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-item ...]</span>',
						'description' => __( 'Optional shortcode that targets specific items in the loop.', 'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. __( 'Only needed if you want to display different items in the results with different HTML.', 'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. __( 'You can target specific positions in the loop, add zebra styling or add extra markup in the middle of the results.', 'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. sprintf(
							__( 'For documentation, see: %1$sLoop parameters in the Loop%2$s.', 'wpv-views' ),
							'<a href="https://toolset.com/documentation/user-guides/views/digging-into-view-outputs/?utm_source=plugin&utm_medium=gui&utm_campaign=views#vmeta-wpv-loop-parameters" title="' . __( 'Loop parameters in the Loop', 'wpv-views' ) . '">',
							'</a>'
						)
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-conditional]</span><br />' . __( 'Content', 'wpv-views' ) . '<br /><span class="wpv-code wpv-code-shortcode">[/wpv-conditional]</span>',
						'description' => __( 'Conditional display for parts of the loop.', 'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. __( 'You need to add the [wpv-conditional] shortcodes around the content that you want to display conditionally.', 'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. sprintf(
							__( 'For documentation, see: %sConditional HTML output in Views%s.', 'wpv-views' ),
							'<a href="https://toolset.com/documentation/user-guides/views/conditional-html-output-in-views/?utm_source=plugin&utm_medium=gui&utm_campaign=views" title="' . __( 'Conditional output in Views', 'wpv-views' ) . '">',
							'</a>'
						)
					),
				),
				'content_extra' => ''
			);
			break;
		case 'combined_output_section':
			$return = array(
				'classname' => 'js-wpv-editor-instructions-for-combined-output-section',
				'title' => __( 'Main sections', 'wpv-views' ),
				'content' => '',
				'table' => array(
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-filter-meta-html]</span>',
						'description' => __( 'The entire Filter section.', 'wpv-views' )
					),
					array(
						'element' => '<span class="wpv-code wpv-code-shortcode">[wpv-layout-meta-html]</span>',
						'description' => __( 'The entire Loop section.', 'wpv-views' )
					)
				),
				'content_extra' => ''
			);
			break;
		case 'extra_css':
			$return = array(
				'classname' => 'js-wpv-editor-instructions-for-extra-css',
				'title' => __( 'Adding custom CSS', 'wpv-views' ),
				'content' => '<p>'
						. __( 'You can add custom CSS styling to the CSS editor and it will be loaded when you display this in the frontend.', 'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. __( 'Note that you do not need to add any &lt;style&gt;&lt;/style&gt; tag at all: we will wrap your CSS before adding it to the page.', 'wpv-views' )
						. '</p><p>'
						. __( 'For example, if you would want to style the h3 tags featured in your output, you would add this CSS styling here:', 'wpv-views' )
						. '<code>'
						. 'h3 { color: #2a6496; font-weight: normal }'
						. '</code>'
						. '</p><!--<p>'
						. sprintf(
							__( 'To learn more about adding custom CSS, please review our %1$sonline tutorial%2$s', 'wpv-views' ),
							'<a href="#" title="' . __( ' Views tutorial on custom CSS', 'wpv-views' ) . '">',
							'</a>'
						)
						. '</p>-->',
				'table' => array(),
				'content_extra' => ''
			);
			break;
		case 'extra_js':
			$return = array(
				'classname' => 'js-wpv-editor-instructions-for-extra-js',
				'title' => __( 'Adding custom JavaScript', 'wpv-views' ),
				'content' => '<p>'
						. __( 'You can add custom JavaScript to the JS editor and it will be loaded when you display this in the frontend.', 'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. __( 'Note that you do not need to add any &lt;script&gt;&lt;/script&gt; tag at all: we will wrap your JavaScript before adding it to the page.', 'wpv-views' )
						. '</p><p>'
						. __( 'For example, if you would want to present an important message to your users through an alert pop-up, you would add this JavaScript here:', 'wpv-views' )
						. '<code>'
						. 'alert("Hello! I am an alert box!");'
						. '</code>'
						. '</p><!--<p>'
						. sprintf(
							__( 'To learn more about adding custom JavaScript, please review our %1$sonline tutorial%2$s', 'wpv-views' ),
							'<a href="#" title="' . __( ' Views tutorial on custom JS', 'wpv-views' ) . '">',
							'</a>'
						)
						. '</p>-->',
				'table' => array(),
				'content_extra' => ''
			);
			break;
		default:
			$return = false;
			$return = apply_filters( 'wpv_filter_formatting_instructions_section', $return, $section );
			break;
	}
	return $return;
}

function wpv_formatting_help_filter() {
	$sections = array( 'filter_section', 'pagination', 'parametric', 'fields-and-views', 'extra_css', 'extra_js', 'media', 'styling' );
	$sections = apply_filters( 'wpv_filter_formatting_help_filter', $sections );
	$args = array(
		'toggler_classname' => 'wpv-editor-instructions-toggle js-wpv-editor-instructions-toggle',
		'toggler_target' => 'js-wpv-editor-instructions-filter-html',
		'toggler_title' => __( 'Formatting and editing instructions', 'wpv-views' ),
		'toggled_classname' => 'wpv-editor-instructions js-wpv-editor-instructions js-wpv-editor-instructions-filter-html',
		'toggled_intro' => '<p>'
						. __( 'The filter section may include pagination for the results and a custom search (or both of them together).', 'wpv-views' )
						. '</p><p>'
						. sprintf(__('To create a custom search, position the cursor between the %s and %s shortcodes and click on the <strong>New filter</strong> button to insert filter elements.', 'wpv-views'),'<strong>[wpv-filter-controls]</strong>','<strong>[/wpv-filter-controls]</strong>')
						. WPV_MESSAGE_SPACE_CHAR
						. __('Your custom search can contain filters by custom fields, taxonomies or even post relationships.', 'wpv-views')
						. '</p><p>'
						. __('Your custom search can also include a <strong>text search input</strong>, <strong>spinner</strong> to indicate updates in progress, a button to <strong>clear the form input</strong> and a <strong>submit button</strong>.', 'wpv-views')
						. '</p><p>'
						. __('Use HTML and CSS to style the filter.', 'wpv-views')
						. '</p><p>'
						. __( 'If you are not using pagination or a custom search, this section outputs nothing.', 'wpv-views' )
						. '</p>',
		'toggled_sections' => $sections
	);
	WPV_Admin_Messages::render_toggle_structure( $args );
}

function wpv_formatting_help_layout() {
	$sections = array( 'layout_section', 'pagination', 'fields-and-views', 'extra_css', 'extra_js', 'media', 'styling' );
	$sections = apply_filters( 'wpv_filter_formatting_help_layout', $sections );
	$cred_intro = '';
	if ( in_array( 'cred', $sections ) ) {
		$cred_intro = '<dt>' . __('CRED forms', 'wpv-views') . '</dt><dd>' . __('Allows to insert CRED forms. You will usually use this controls to insert edit and delete CRED links.', 'wpv-views') . '</dd>';
	}
	$args = array(
		'toggler_classname' => 'wpv-editor-instructions-toggle js-wpv-editor-instructions-toggle',
		'toggler_target' => 'js-wpv-editor-instructions-layout-html',
		'toggler_title' => __( 'Formatting and editing instructions', 'wpv-views' ),
		'toggled_classname' => 'wpv-editor-instructions js-wpv-editor-instructions js-wpv-editor-instructions-layout-html',
		'toggled_intro' =>		'<h4>' . __('Toolbar buttons','wpv-views') . '</h4>'
						. '<dl><dt>' . __( 'Loop wizard', 'wpv-views' ) . '</dt><dd>' . __( 'A wizard that lets you design the loop.', 'wpv-views' ) . WPV_MESSAGE_SPACE_CHAR
							. __('Recommended if you are new to Views.', 'wpv-views') . '</dd>'
						. '<dt>' . __('Fields and Views', 'wpv-views') . '</dt><dd>' . __('Add fields and nested Views to the output.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
							. __('Good for building your own unique loops.', 'wpv-views') . '</dd>'
						. '<dt>' . __('Content Template', 'wpv-views') . '</dt><dd>' . __('Add complete blocks into the Loop using Content Templates.', 'wpv-views') . WPV_MESSAGE_SPACE_CHAR
							. __('This method makes it easy to achieve complex loop designs.', 'wpv-views') . '</dd>'
						. '<dt>' . __('Pagination controls', 'wpv-views') . '</dt><dd>' . __('Add controls to move between different result pages', 'wpv-views') . '</dd>'
						. $cred_intro
						. '<dt>' . __('Media', 'wpv-views') . '</dt><dd>' . __('Add images and other media items to the editor.', 'wpv-views') . '</dd></dl>'
						. '<p>' . __('Besides these buttons, you can edit the HTML content yourself by writing your own HTML and CSS.', 'wpv-views')

						. '</p><p>'
						. sprintf(
								__( 'Full documentation is found in the %1$sViews shortcodes%2$s page.', 'wpv-views' ),
								'<a href="https://toolset.com/documentation/programmer-reference/views/views-shortcodes/?utm_source=plugin&utm_medium=gui&utm_campaign=views" title="' . __( 'Views shortcodes documentation', 'wpv-views' ) . '">',
								'</a>'
							)
						. '</p>',
		'toggled_sections' => $sections
	);
	WPV_Admin_Messages::render_toggle_structure( $args );
}

function wpv_formatting_help_inline_content_template( $template = null ) {
	if (
		! is_object( $template )
		|| ! isset( $template->post_type )
		|| $template->post_type != 'view-template'
	) {
		return;
	}
	$sections = array( 'fields-and-views', 'extra_css', 'extra_js', 'media', 'styling' );
	$sections = apply_filters( 'wpv_filter_formatting_help_inline_content_template', $sections );
	$args = array(
		'toggler_classname' => 'wpv-editor-instructions-toggle js-wpv-editor-instructions-toggle',
		'toggler_target' => 'js-wpv-editor-instructions-inline-content-template-' . $template->ID,
		'toggler_title' => __( 'Formatting and editing instructions', 'wpv-views' ),
		'toggled_classname' => 'wpv-editor-instructions js-wpv-editor-instructions js-wpv-editor-instructions-inline-content-template-' . $template->ID,
		'toggled_intro' => '<p>'
						. __( 'A Content Template works like a subroutine.', 'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. __( 'It displays the fields of the post in the loop, by using a simple shortcode:', 'wpv-views' )
						. '<code>'
						. '[wpv-post-body view_template="' . $template->post_title . '"]'
						. '</code>'
						. '</p>',
		'toggled_sections' => $sections
	);
	WPV_Admin_Messages::render_toggle_structure( $args );
}

function wpv_formatting_help_layouts_content_template_cell( $template = null ) {
	if (
		! is_object( $template )
		|| ! isset( $template->post_type )
		|| $template->post_type != 'view-template'
	) {
		return;
	}
	$sections = array( 'fields-and-views', 'media', 'styling' );
	$sections = apply_filters( 'wpv_filter_formatting_help_layouts_content_template_cell', $sections );
	$args = array(
		'toggler_classname' => 'wpv-editor-instructions-toggle js-wpv-editor-instructions-toggle',
		'toggler_target' => 'js-wpv-editor-instructions-inline-content-template-' . $template->ID,
		'toggler_title' => __( 'Formatting and editing instructions', 'wpv-views' ),
		'toggled_classname' => 'wpv-editor-instructions js-wpv-editor-instructions js-wpv-editor-instructions-inline-content-template-' . $template->ID,
		'toggled_intro' => '<p>'
						. __( 'A Content Template is like a chunk of content.', 'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. __( 'It displays fields from the current post using simple shortcodes.', 'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. __( 'For example:', 'wpv-views' )
						. '<ul>'
						. '<li>'
						. '<code>[wpv-post-title]</code>'
						. WPV_MESSAGE_SPACE_CHAR
						. __( 'will display the post title', 'wpv-views' )
						. '</li>'
						. '<li>'
						. '<code>[wpv-post-body view_template="None"]</code>'
						. WPV_MESSAGE_SPACE_CHAR
						. __( 'will display the post content', 'wpv-views' )
						. '</li>'
						. '</ul>'
						. '</p>',
		'toggled_sections' => $sections
	);
	WPV_Admin_Messages::render_toggle_structure( $args );
}

function wpv_formatting_help_combined_output() {
	$sections = array( 'combined_output_section', 'fields-and-views', 'media', 'styling' );
	$sections = apply_filters( 'wpv_filter_formatting_help_combined_output', $sections );
	$args = array(
		'toggler_classname' => 'wpv-editor-instructions-toggle js-wpv-editor-instructions-toggle',
		'toggler_target' => 'js-wpv-editor-instructions-content',
		'toggler_title' => __( 'Formatting and editing instructions', 'wpv-views' ),
		'toggled_classname' => 'wpv-editor-instructions js-wpv-editor-instructions js-wpv-editor-instructions-content',
		'toggled_intro' => '<p>'
						. __( 'When you display this in the frontend, the content of this box gets displayed.', 'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. __( 'Normally, it includes a shortcode for the <strong>Filter</strong> section and another shortcode for the <strong>Loop</strong> section, so they will display one after the other.', 'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. __( 'You can add HTML for styling and switch the order of those two sections.', 'wpv-views' )
						. '</p>',
		'toggled_sections' => $sections
	);
	WPV_Admin_Messages::render_toggle_structure( $args );
}

// @todo when moving the CT to their own editor we will need to review/delete this hook
// @todo after CT editor is refreshed, remove the hook and the $template argument
add_action ( 'edit_form_after_editor', 'wpv_formatting_help_content_template', 5 );

/**
 * @param null|WP_Post $template Will be deprecated after new CT edit page is finished.
 */
function wpv_formatting_help_content_template( $template = null ) {
	global $pagenow;
	$action = wpv_getget( 'action' );
	if (
	        (
	                null == $template
                    || 'view-template' == $template->post_type
            )
            && 'post.php' != $pagenow
    ) {
		$sections = array( 'fields-and-views', 'extra_css', 'extra_js', 'media', 'styling' );
		$sections = apply_filters( 'wpv_filter_formatting_help_content_template', $sections );
		$args = array(
			'toggler_classname' => 'wpv-editor-instructions-toggle js-wpv-editor-instructions-toggle',
			'toggler_target' => 'js-wpv-editor-instructions-content-template',
			'toggler_title' => __( 'Formatting and editing instructions', 'wpv-views' ),
			'toggled_classname' => 'wpv-editor-instructions js-wpv-editor-instructions js-wpv-editor-instructions-content-template',
			'toggled_intro' => '<p>'
							. __( 'A Content Template is used to style single-item pages.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'It will replace the ‘main content’ section of the page.', 'wpv-views' )
							. WPV_MESSAGE_SPACE_CHAR
							. __( 'The Content Template can include fields that belong to the content, Views, CRED forms, HTML and media', 'wpv-views' )
							. '</p>',
			'toggled_sections' => $sections
		);
		WPV_Admin_Messages::render_toggle_structure( $args );
	}
}

// DEPRECATED
function wpv_formatting_help_extra_css( $section = 'filter' ) {
	$sections = array( 'extra_css' );
	$sections = apply_filters( 'wpv_filter_formatting_help_extra_css', $sections );
	$args = array(
		'toggler_classname' => 'wpv-editor-instructions-toggle js-wpv-editor-instructions-toggle',
		'toggler_target' => 'js-wpv-editor-instructions-' . $section . '-css',
		'toggler_title' => __( 'Formatting and editing instructions', 'wpv-views' ),
		'toggled_classname' => 'wpv-editor-instructions js-wpv-editor-instructions js-wpv-editor-instructions-' . $section . '-css',
		'toggled_intro' => '<p>'
						. __( 'This editor allows you to add CSS code, which will determine the styling of the page.', 'wpv-views' )
						. '</p>',
		'toggled_sections' => $sections
	);
	WPV_Admin_Messages::render_toggle_structure( $args );
}

// DEPRECATED
function wpv_formatting_help_extra_js( $section = 'filter' ) {
	$sections = array( 'extra_js' );
	$sections = apply_filters( 'wpv_filter_formatting_help_extra_js', $sections );
	$args = array(
		'toggler_classname' => 'wpv-editor-instructions-toggle js-wpv-editor-instructions-toggle',
		'toggler_target' => 'js-wpv-editor-instructions-' . $section . '-js',
		'toggler_title' => __( 'Formatting and editing instructions', 'wpv-views' ),
		'toggled_classname' => 'wpv-editor-instructions js-wpv-editor-instructions js-wpv-editor-instructions-' . $section . '-js',
		'toggled_intro' => '<p>'
						. __( 'This editor allows you to add javascript code, which will run in the browser, when this content is displayed.', 'wpv-views' )
						. '</p>',
		'toggled_sections' => $sections
	);
	WPV_Admin_Messages::render_toggle_structure( $args );
}

add_action( 'wpv_action_view_editor_section_hidden',	'wpv_view_editor_section_hidden_pointers_dialogs' );
add_action( 'wpv_action_wpa_editor_section_hidden',		'wpv_wpa_editor_section_hidden_pointers_dialogs' );

function wpv_view_editor_section_hidden_pointers_dialogs( $args ) {

	$dismissed_pointers = get_user_meta( $args['user_id'], '_wpv_dismissed_pointers', true );
	if (
		! is_array( $dismissed_pointers )
		|| empty( $dismissed_pointers )
	) {
		$dismissed_pointers = array();
	}

	render_view_pointers( $args, $dismissed_pointers );
	render_shared_pointers( $args, $dismissed_pointers );

	$dismissed_dialogs = get_user_meta( $args['user_id'], '_wpv_dismissed_dialogs', true );
	if (
		! is_array( $dismissed_dialogs )
		|| empty( $dismissed_dialogs )
	) {
		$dismissed_dialogs = array();
	}

	render_view_dialogs( $args, $dismissed_dialogs );
	render_shared_dialogs( $args, $dismissed_dialogs );
}

function wpv_wpa_editor_section_hidden_pointers_dialogs( $args ) {

	$dismissed_pointers = get_user_meta( $args['user_id'], '_wpv_dismissed_pointers', true );
	if (
		! is_array( $dismissed_pointers )
		|| empty( $dismissed_pointers )
	) {
		$dismissed_pointers = array();
	}

	render_wpa_pointers( $args, $dismissed_pointers );
	render_shared_pointers( $args, $dismissed_pointers );

	$dismissed_dialogs = get_user_meta( $args['user_id'], '_wpv_dismissed_dialogs', true );
	if (
		! is_array( $dismissed_dialogs )
		|| empty( $dismissed_dialogs )
	) {
		$dismissed_dialogs = array();
	}

	render_wpa_dialogs( $args, $dismissed_dialogs );
	render_shared_dialogs( $args, $dismissed_dialogs );
}

function render_view_pointers( $args, $dismissed_pointers ) {

	$view_settings					= $args['settings'];
	$view_settings_stored			= $args['settings_stored'];
	$view_layout_settings			= $args['layout_settings'];
	$view_layout_settings_stored	= $args['layout_settings_stored'];
	$view_id						= $args['id'];
	$user_id						= $args['user_id'];

	?>
	<div id="js-wpv-view-hidden-pointers-container" class="popup-window-container">

	</div>
	<?php
}

function render_wpa_pointers( $args, $dismissed_pointers ) {

    $view_settings               = $args['settings'];
    $view_settings_stored        = $args['settings_stored'];
    $view_layout_settings        = $args['layout_settings'];
    $view_layout_settings_stored = $args['layout_settings_stored'];
    $view_id                     = $args['id'];
    $user_id                     = $args['user_id'];

    ?>
    <div id="js-wpv-wpa-hidden-pointers-container" class="popup-window-container">
        <?php
        /**
         * Screen Options pointer
         *
         * SHown only when the WPA does not have a stored purpose.
         */
        if (
	        (
		        ! isset( $view_settings_stored['view_purpose'] )
		        || $view_settings_stored['view_purpose'] == 'all'
	        )
	        && ! wpv_is_views_lite() // Show only for full Views
        ) {
            $dismissed_classname = '';
            if ( isset( $dismissed_pointers['set-wpa-purpose'] ) ) {
                $dismissed_classname = ' js-wpv-pointer-dismissed';
            }
            ?>
            <div class="js-wpv-set-wpa-purpose-pointer<?php echo $dismissed_classname; ?>">
                <h3><?php _e( 'Screen Options', 'wpv-views' ); ?></h3>
                <p>
                    <?php
                    echo __( 'Did you know that you can add a custom search to this Archive?', 'wpv-views' );
                    ?>
                </p>
                <p>
                    <?php
                    echo __( 'Open the Screen Options and set the WordPress Archive purpose to display it as a custom search.', 'wpv-views' );
                    ?>
                </p>
                <p>
                    <label>
                        <input type="checkbox" class="js-wpv-dismiss-pointer" data-pointer="set-wpa-purpose"
                               id="wpv-dismiss-set-wpa-purpose-pointer"/>
                        <?php _e( 'Don\'t show this again', 'wpv-views' ); ?>
                    </label>
                </p>
            </div>
            <?php
        }
        ?>
    </div>
    <?php

}

function render_shared_pointers( $args, $dismissed_pointers ) {

	$view_settings					= $args['settings'];
	$view_settings_stored			= $args['settings_stored'];
	$view_layout_settings			= $args['layout_settings'];
	$view_layout_settings_stored	= $args['layout_settings_stored'];
	$view_id						= $args['id'];
	$user_id						= $args['user_id'];

	$query_type = 'posts';
	if (
		isset( $view_settings['view-query-mode'] )
		&& 'normal' ==  $view_settings['view-query-mode']
	) {
		if ( isset( $view_settings['query_type'][0] ) ) {
			$query_type = $view_settings['query_type'][0];
		}
	}

	?>
	<div id="js-wpv-shared-hidden-pointers-container" class="popup-window-container">
		<?php
		/**
		* Loop Wizard completed pointer
		*/
		$dismissed_classname = '';
		if ( isset( $dismissed_pointers['inserted-layout-loop'] ) ) {
			$dismissed_classname = ' js-wpv-pointer-dismissed';
		}

		?>
		<div class="js-wpv-inserted-layout-loop-pointer<?php echo $dismissed_classname; ?>">
			<h3><?php _e( 'Fields updated in the Loop', 'wpv-views' ); ?></h3>
			<p>
				<?php
					_e( 'The Loop Wizard just updated the editor with the fields that you added.', 'wpv-views' );
					echo WPV_MESSAGE_SPACE_CHAR;
					_e( 'You can change the appearance by adding HTML and CSS.', 'wpv-views' );
				?>
			</p>
			<p>
				<label>
					<input type="checkbox" class="js-wpv-dismiss-pointer" data-pointer="inserted-layout-loop" id="wpv-dismiss-inserted-layout-loop-pointer" />
					<?php _e( 'Don\'t show this again', 'wpv-views' ); ?>
				</label>
			</p>
		</div>

		<?php
		/**
		* Loop Content Template pointer
		*/
		$dismissed_classname = '';
		if ( isset( $dismissed_pointers['inserted-layout-loop-content-template'] ) ) {
			$dismissed_classname = ' js-wpv-pointer-dismissed';
		}

		?>
		<div class="js-wpv-inserted-layout-loop-content-template-pointer<?php echo $dismissed_classname; ?>">
			<h3><?php _e( 'Content Template used as a loop block', 'wpv-views' ); ?></h3>
			<p>
				<?php
					_e( 'The Loop Wizard just updated the editor and created a Content Template.', 'wpv-views' );
					echo WPV_MESSAGE_SPACE_CHAR;
					_e( 'The HTML box includes the Loop and the Content Template contains the fields that you added.', 'wpv-views' );
					echo WPV_MESSAGE_SPACE_CHAR;
					_e( 'You can change the appearance by adding HTML and CSS.', 'wpv-views' );
				?>
			</p>
			<p>
				<label>
					<input type="checkbox" class="js-wpv-dismiss-pointer" data-pointer="inserted-layout-loop-content-template" id="wpv-dismiss-inserted-layout-loop-content-template-pointer" />
					<?php _e( 'Don\'t show this again', 'wpv-views' ); ?>
				</label>
			</p>
		</div>
		<?php
		$context = array(
			'dismissed_pointers' => $dismissed_pointers,
		);

		$template_repository = WPV_Output_Template_Repository::get_instance();
		$renderer = Toolset_Renderer::get_instance();
		$renderer->render(
			$template_repository->get( WPV_Output_Template_Repository::VIEWS_EDITOR_VIEW_WRAPPER_DISABLE_FOR_SEPARATORS_LIST ),
			$context
		);

		?>
	</div>
	<?php
}

function render_view_dialogs( $args, $dismissed_dialogs ) {

	$view_settings					= $args['settings'];
	$view_settings_stored			= $args['settings_stored'];
	$view_layout_settings			= $args['layout_settings'];
	$view_layout_settings_stored	= $args['layout_settings_stored'];
	$view_id						= $args['id'];
	$user_id						= $args['user_id'];

	?>
	<div id="js-wpv-view-hidden-dialogs-container" class="popup-window-container">
		<?php
		/**
		* Delete or edit termmeta field filters on bulk delete
		*/
		?>
		<div id="js-wpv-filter-termmeta-field-delete-filter-row-dialog" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container wpv-dialog-frontend-events">
			<div class="wpv-dialog">
				<h3><?php _e('There are more than one termmeta field filters', 'wpv-views'); ?></h3>
				<p>
					<?php _e( 'You can delete them all at once or open the editor and delete individual filters.', 'wpv-views' ); ?>
				</p>
			</div>
		</div>

		<?php
		/**
		* Delete or edit usermeta field filters on bulk delete
		*/
		?>
		<div id="js-wpv-filter-usermeta-field-delete-filter-row-dialog" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container wpv-dialog-frontend-events">
			<div class="wpv-dialog">
				<h3><?php _e('There are more than one usermeta field filters', 'wpv-views'); ?></h3>
				<p>
					<?php _e( 'You can delete them all at once or open the editor and delete individual filters.', 'wpv-views' ); ?>
				</p>
			</div>
		</div>

		<?php
		/**
		* Pagination controls dialog
		*/
		?>
		<div class="js-wpv-pagination-form-dialog toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container">
			<div class="wpv-dialog wpv-dialog-pagination-wizard js-wpv-dialog-pagination-wizard">
				<?php
				$bootstrap_version = Toolset_Settings::get_instance();
				$bs_version = ( isset( $bootstrap_version->toolset_bootstrap_version ) ) ? $bootstrap_version->toolset_bootstrap_version : '-1';
				if ( '-1' == $bs_version ) {
					?>
                    <p class="toolset-alert toolset-alert-info js-wpv-pagination-wizard-bootstrap-notice">
						<?php
						echo esc_html( __( 'Pagination works better if the activated theme or plugins include Bootstrap.', 'wpv-views' ) );
						echo WPV_MESSAGE_SPACE_CHAR;
						echo
							'<a href="https://toolset.com/course-lesson/creating-a-view/?utm_source=plugin&utm_medium=gui&utm_campaign=views" title="' . esc_attr( __( 'Documentation', 'wpv-views' ) ) . '" target="blank">'
							. esc_html( __( 'Check the documentation', 'wpv-views' ) )
							. '</a>';
						?>
                    </p>
					<?php
				}
				?>

				<h3><?php _e('Pagination data', 'wpv-views'); ?></h3>
				<div class="wpv-dialog-pagination-wizard-controls">

					<div class="wpv-dialog-pagination-wizard-item js-wpv-dialog-pagination-wizard-item">
						<div class="wpv-dialog-pagination-wizard-data">
							<input type="checkbox" name="pagination_control" class="js-wpv-pagination-dialog-control" id="pagination-include-page-num" value="page_num" data-target="current-page-number" />
							<label for="pagination-include-page-num"><?php _e('Current page number','wpv-views'); ?></label>
						</div>
						<div class="wpv-dialog-pagination-wizard-preview js-wpv-dialog-pagination-wizard-preview disabled">
							<p class="wpv-pagination-preview-element js-wpv-pagination-preview-element" data-name="current-page-number">
								<?php _e('Showing page','wpv-views'); ?>: 2
							</p>
						</div>
					</div>

					<div class="wpv-dialog-pagination-wizard-item js-wpv-dialog-pagination-wizard-item">
						<div class="wpv-dialog-pagination-wizard-data">
							<input type="checkbox" name="pagination_control" class="js-wpv-pagination-dialog-control" id="pagination-include-page-total" value="page_total" data-target="total-pages" />
							<label for="pagination-include-page-total"><?php _e('Total pages count','wpv-views'); ?></label>
						</div>
						<div class="wpv-dialog-pagination-wizard-preview js-wpv-dialog-pagination-wizard-preview disabled">
							<p class="wpv-pagination-preview-element js-wpv-pagination-preview-element" data-name="total-pages">
								<?php _e('There are 3 pages','wpv-views'); ?>
							</p>
						</div>
					</div>

				</div>

				<h3><?php _e('Pagination controls', 'wpv-views'); ?></h3>
				<div class="wpv-dialog-pagination-wizard-controls">

					<div class="wpv-dialog-pagination-wizard-item js-wpv-dialog-pagination-wizard-item">
						<div class="wpv-dialog-pagination-wizard-data">
							<input type="checkbox" name="pagination_control" class="js-wpv-pagination-dialog-control" id="pagination-include-controls" value="page_controls" data-target="next-previous-controls" />
							<label for="pagination-include-controls"><?php _e('Previous and Next page controls','wpv-views'); ?></label>
                            <div class="wpv-dialog-pagination-wizard-item-extra js-wpv-dialog-pagination-wizard-item-extra">
                                <p>
                                    <input type="checkbox" name="wpv_pagination_insert_prev_force" id="wpv-pagination-insert-prev-force" class="js-wpv-pagination-shortcode-attribute" data-attribute="force" value="true" />
                                    <label for="wpv-pagination-insert-prev-force"><?php echo esc_html( __( 'Include the Previous/Next links on the first and the last pages','wpv-views' ) ); ?></label>
								</p>
                            </div>
						</div>
						<div class="wpv-dialog-pagination-wizard-preview js-wpv-dialog-pagination-wizard-preview disabled">
							<p class="wpv-pagination-preview-element js-wpv-pagination-preview-element next-previous-controls" data-name="next-previous-controls">
								<a href="#" class="js-wpv-disable-events">&laquo; <?php _e('Previous','wpv-views') ?></a>
								&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								<a href="#" class="js-wpv-disable-events"><?php _e('Next','wpv-views') ?> &raquo;</a>
							</p>
						</div>
					</div>

					<div class="wpv-dialog-pagination-wizard-item js-wpv-dialog-pagination-wizard-item">
						<div class="wpv-dialog-pagination-wizard-data">
							<input type="checkbox" name="pagination_control" class="js-wpv-pagination-dialog-control" id="pagination-include-nav-links" value="page_nav_links" data-target="nav-controls-link" />
							<label for="pagination-include-nav-links"><?php _e('Navigation controls using links','wpv-views'); ?></label>
							<div class="wpv-dialog-pagination-wizard-item-extra js-wpv-dialog-pagination-wizard-item-extra">
                                <p>
                                    <input type="checkbox"
                                           name="sub_pagination_control"
                                           class="js-wpv-pagination-sub-dialog-control js-wpv-dialog-pagination-wizard-item-extra-nav-links"
                                           id="js-wpv-dialog-pagination-wizard-item-extra-nav-links-first-last"
                                           data-attr="first_last_links" value="true" />
                                    <label for="js-wpv-dialog-pagination-wizard-item-extra-nav-links-first-last"><?php echo esc_html( __( 'Add First/Last page controls','wpv-views' ) ); ?></label>
                                </p>
                                <p>
                                    <input type="checkbox"
                                           name="sub_pagination_control"
                                           class="js-wpv-pagination-sub-dialog-control js-wpv-dialog-pagination-wizard-item-extra-nav-links"
                                           id="js-wpv-dialog-pagination-wizard-item-extra-nav-links-previous-next"
                                           data-attr="previous_next_links" value="true" />
                                    <label for="js-wpv-dialog-pagination-wizard-item-extra-nav-links-previous-next"><?php echo esc_html( __( 'Add Previous/Next page controls','wpv-views' ) ); ?></label>
                                </p>
                                <p class="js-wpv-dialog-pagination-wizard-sub-item-dependant-previous_next_links">
                                    <input type="checkbox"
                                           data-attr="force_previous_next"
                                           value="true"
                                           id="wpv-dialog-pagination-wizard-item-extra-nav-links-next-prev-force"
                                           class="js-wpv-pagination-shortcode-attribute js-wpv-dialog-pagination-wizard-item-extra-nav-links" />
                                    <label for="wpv-dialog-pagination-wizard-item-extra-nav-links-next-prev-force"><?php echo esc_html( __( 'Include the Previous/Next links on the first and the last pages','wpv-views' ) ); ?></label>
                                </p>
								<p>
									<label for="wpv-dialog-pagination-wizard-item-extra-nav-links-text"><?php _e( 'Text for link to page: ', 'wpv-views' ); ?></label>
									<input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-text" data-attr="anchor_text" class="js-wpv-dialog-pagination-wizard-item-extra-nav-links" placeholder="%%PAGE%%" />
								</p>
								<p>
									<label for="wpv-dialog-pagination-wizard-item-extra-nav-links-step"><?php _e( 'Pages to skip:', 'wpv-views' ); ?></label>
									<input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-step" data-attr="step" class="js-wpv-dialog-pagination-wizard-item-extra-nav-links small-text" />
								</p>
								<p>
									<label for="wpv-dialog-pagination-wizard-item-extra-nav-links-ellipsis"><?php _e( 'Placeholder for skipped pages:', 'wpv-views' ); ?></label>
									<input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-ellipsis" data-attr="ellipsis" class="js-wpv-dialog-pagination-wizard-item-extra-nav-links small-text" placeholder="..." />
								</p>
								<p>
									<label for="wpv-dialog-pagination-wizard-item-extra-nav-links-reach"><?php _e( 'Number of preceding and following pages:', 'wpv-views' ); ?></label>
									<input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-reach" data-attr="reach" class="js-wpv-dialog-pagination-wizard-item-extra-nav-links small-text" />
								</p>
                                <p class="js-wpv-dialog-pagination-wizard-sub-item-dependant-previous_next_links">
                                    <label for="wpv-dialog-pagination-wizard-item-extra-nav-links-previous-text"><?php echo esc_html( __( 'Text for "Previous" link: ', 'wpv-views' ) ); ?></label>
                                    <input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-previous-text" data-attr="text_for_previous_link" class="js-wpv-dialog-pagination-wizard-item-extra-nav-links" placeholder="<?php echo esc_attr( __( 'Previous', 'wpv-views' ) ); ?>" />
                                </p>
                                <p class="js-wpv-dialog-pagination-wizard-sub-item-dependant-previous_next_links">
                                    <label for="wpv-dialog-pagination-wizard-item-extra-nav-links-next-text"><?php echo esc_html( __( 'Text for "Next" link: ', 'wpv-views' ) ); ?></label>
                                    <input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-next-text" data-attr="text_for_next_link" class="js-wpv-dialog-pagination-wizard-item-extra-nav-links" placeholder="<?php echo esc_attr( __( 'Next', 'wpv-views' ) ); ?>" />
                                </p>
                                <p class="js-wpv-dialog-pagination-wizard-sub-item-dependant-first_last_links">
                                    <label for="wpv-dialog-pagination-wizard-item-extra-nav-links-first-text"><?php echo esc_html( __( 'Text for "First" link: ', 'wpv-views' ) ); ?></label>
                                    <input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-first-text" data-attr="text_for_first_link" class="js-wpv-dialog-pagination-wizard-item-extra-nav-links" placeholder="<?php echo esc_attr( __( 'First', 'wpv-views' ) ); ?>" />
                                </p>
                                <p class="js-wpv-dialog-pagination-wizard-sub-item-dependant-first_last_links">
                                    <label for="wpv-dialog-pagination-wizard-item-extra-nav-links-last-text"><?php echo esc_html( __( 'Text for "Last" link: ', 'wpv-views' ) ); ?></label>
                                    <input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-last-text" data-attr="text_for_last_link" class="js-wpv-dialog-pagination-wizard-item-extra-nav-links" placeholder="<?php echo esc_attr( __( 'Last', 'wpv-views' ) ); ?>" />
                                </p>
								<p style="border-top:solid 1px #dedede;padding-top:5px;">
									<?php
									echo
										'<a href="https://toolset.com/course-lesson/creating-a-view/?utm_source=plugin&utm_medium=gui&utm_campaign=views" title="' . esc_attr( __( 'Documentation', 'wpv-views' ) ) . '" target="blank">'
										. __( 'Check the documentation', 'wpv-views' )
										. '</a>';
									?>
								</p>
							</div>
						</div>
						<div class="wpv-dialog-pagination-wizard-preview wpv-dialog-pagination-wizard-pagenavi-preview js-wpv-dialog-pagination-wizard-preview disabled">
							<p class="wpv-pagination-preview-element js-wpv-pagination-preview-element" data-name="nav-controls-link">
                                <a href="#" class="js-wpv-disable-events js-wpv-dialog-pagination-wizard-preview-first_last_links">&laquo;&laquo; <?php echo esc_html( __( 'First', 'wpv-views' ) ); ?></a>
                                <a href="#" class="js-wpv-disable-events js-wpv-dialog-pagination-wizard-preview-previous_next_links">&laquo; <?php echo esc_html( __( 'Previous', 'wpv-views' ) ); ?></a>
								<span class="current">1</span>&nbsp;
								<a href="#" class="js-wpv-disable-events">2</a>&nbsp;
								<span>...</span>&nbsp;
								<a href="#" class="js-wpv-disable-events">10</a>&nbsp;
								<span>...</span>&nbsp;
								<a href="#" class="js-wpv-disable-events">27</a>&nbsp;
                                <a href="#" class="js-wpv-disable-events js-wpv-dialog-pagination-wizard-preview-previous_next_links"><?php echo esc_html( __( 'Next', 'wpv-views' ) ); ?> &raquo;</a>
                                <a href="#" class="js-wpv-disable-events js-wpv-dialog-pagination-wizard-preview-first_last_links"><?php echo esc_html( __( 'Last', 'wpv-views' ) ); ?> &raquo;&raquo;</a>
							</p>
						</div>
					</div>

					<div class="wpv-dialog-pagination-wizard-item js-wpv-dialog-pagination-wizard-item">
						<div class="wpv-dialog-pagination-wizard-data">
							<input type="checkbox" name="pagination_control" class="js-wpv-pagination-dialog-control" id="pagination-include-nav-dots" value="page_nav_dots" data-target="nav-controls-dots" />
							<label for="pagination-include-nav-dots"><?php _e('Navigation controls using dots','wpv-views'); ?></label>
						</div>
						<div class="wpv-dialog-pagination-wizard-preview js-wpv-dialog-pagination-wizard-preview disabled">
							<p class="wpv-pagination-preview-element js-wpv-pagination-preview-element" data-name="nav-controls-dots">
								<img src="<?php echo (WPV_URL . '/res/img/dots.png'); ?>" alt="dots" />
							</p>
						</div>
					</div>

					<div class="wpv-dialog-pagination-wizard-item js-wpv-dialog-pagination-wizard-item">
						<div class="wpv-dialog-pagination-wizard-data">
							<input type="checkbox" name="pagination_control" class="js-wpv-pagination-dialog-control" id="pagination-include-nav-dropdown" value="page_nav_dropdown" data-target="nav-controls-dropdown" />
							<label for="pagination-include-nav-dropdown"><?php _e('Navigation controls using a dropdown','wpv-views'); ?></label>
						</div>
						<div class="wpv-dialog-pagination-wizard-preview js-wpv-dialog-pagination-wizard-preview disabled">
							<p class="wpv-pagination-preview-element js-wpv-pagination-preview-element" data-name="nav-controls-dropdown">
								<?php _e( 'Go to page', 'wpv-views' ); ?>
								<select class="js-wpv-disable-events" disabled="disabled">
									<option>2</option>
								</select>
							</p>
						</div>
					</div>
				</div>

                <h3><?php echo esc_html( __( 'Output style', 'wpv-views' ) ); ?></h3>
                <div class="wpv-dialog-pagination-wizard-controls">
                    <div class="wpv-dialog-pagination-wizard-item js-wpv-dialog-pagination-wizard-item">
                        <div class="wpv-dialog-pagination-wizard-data">
                            <p>
                                <input type="radio"
                                       name="pagination_control"
                                       id="wpv-pagination-bootstrap-output"
                                       class="js-wpv-pagination-dialog-control js-wpv-pagination-output-style" value="<?php echo esc_attr( 'bootstrap' ); ?>" />
                                <label for="wpv-pagination-bootstrap-output"><?php echo esc_html( __( 'Fully styled output', 'wpv-views' ) ); ?></label>
                                <input type="radio"
                                       style="margin-left:15px"
                                       name="pagination_control"
                                       id="wpv-pagination-raw-output"
                                       class="js-wpv-pagination-dialog-control js-wpv-pagination-output-style"
                                       value="<?php echo esc_attr( 'raw' ); ?>" checked="checked" />
                                <label for="wpv-pagination-raw-output"><?php echo esc_html( __( 'Raw output', 'wpv-views' ) ); ?></label>
                            </p>
                        </div>
                    </div>
                </div>

                <h3><?php echo esc_html( __( 'Other pagination options', 'wpv-views' ) ); ?></h3>
                <div class="wpv-dialog-pagination-wizard-controls">
                    <div class="wpv-dialog-pagination-wizard-item">
                        <div class="wpv-dialog-pagination-wizard-data">
                            <p>
                                <input type="checkbox" name="pagination_display" class="js-wpv-pagination-dialog-display" id="pagination-include-wrapper" />
                                <label for="pagination-include-wrapper"><?php echo esc_html( __( 'Don\'t show pagination elements if there is only one page','wpv-views' ) ); ?></label>
                            </p>
                        </div>
                    </div>
                </div>
			</div>
		</div>
	</div>
	<?php
}

function render_wpa_dialogs( $args, $dismissed_dialogs ) {

	$view_settings					= $args['settings'];
	$view_settings_stored			= $args['settings_stored'];
	$view_layout_settings			= $args['layout_settings'];
	$view_layout_settings_stored	= $args['layout_settings_stored'];
	$view_id						= $args['id'];
	$user_id						= $args['user_id'];

	?>
	<div id="js-wpv-wpa-hidden-dialogs-container" class="popup-window-container">

		<?php
		/**
		* Assign post types to archive loop dialog
		*/
		?>

		<div id="js-wpv-dialog-assign-post-type-to-archive-loop-dialog" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container">
			<div class="wpv-dialog js-wpv-dialog-assign-post-type-to-archive-loop-dialog-content">
				<p>
					<?php
					printf(
						__( "Choose Post Types that will be displayed in the %s:" ),
						'<span class="js-wpv-archive-loop-for-post-type-assignment"></span>'
					);
					?>
				</p>
				<ul class="wpv-mightlong-list">
				<?php
				$post_types = get_post_types( array( 'public' => true ), 'objects' );
				foreach ( $post_types as $candidate_post_type_name => $candidate_post_type_data ) {
					?>
					<li>
						<label>
							<input
								type="checkbox"
								class="js-wpv-post-types-for-archive-loop js-wpv-post-types-for-archive-loop-<?php echo esc_attr( $candidate_post_type_name ); ?>"
								value="<?php echo esc_attr( $candidate_post_type_name ); ?>"
								autocomplete="off"
							/>
							<?php echo $candidate_post_type_data->labels->name; ?>
						</label>
					</li>
					<?php
				}
				?>
				</ul>
				<div class="js-wpv-message-container"></div>
			</div>
		</div>

		<?php
		/**
		* Pagination dialog
		*/
		?>

		<div id="js-wpv-archive-pagination-dialog" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container">
			<div class="wpv-dialog wpv-dialog-pagination-wizard js-wpv-dialog-pagination-wizard">
				<?php
				$bootstrap_version = Toolset_Settings::get_instance();
				$bs_version = ( isset( $bootstrap_version->toolset_bootstrap_version ) ) ? $bootstrap_version->toolset_bootstrap_version : '-1';
				if ( '-1' == $bs_version ) {
					?>
                    <p class="toolset-alert toolset-alert-info js-wpv-pagination-wizard-bootstrap-notice">
						<?php
						echo esc_html( __( 'Pagination works better if the activated theme or plugins include Bootstrap.', 'wpv-views' ) );
						echo WPV_MESSAGE_SPACE_CHAR;
						echo
							'<a href="https://toolset.com/course-lesson/creating-a-custom-archive-page/?utm_source=plugin&utm_medium=gui&utm_campaign=blocks#add-pagination-to-the-archive" title="' . esc_attr( __( 'Documentation for pagination links', 'wpv-views' ) ) . '" target="blank">'
							. esc_html( __( 'Check the documentation', 'wpv-views' ) )
							. '</a>';
						?>
                    </p>
					<?php
				}
				?>

				<h3><?php _e( 'Pagination data', 'wpv-views' ); ?></h3>
				<div class="wpv-dialog-pagination-wizard-controls">

					<div class="wpv-dialog-pagination-wizard-item js-wpv-archive-pagination-shortcode">
						<div class="wpv-dialog-pagination-wizard-data">
							<input type="checkbox"
								name="pagination_control" id="pagination-include-page-num"
								class="js-wpv-archive-pagination-control"
								value="wpv-pager-archive-current-page" />
							<label for="pagination-include-page-num"><?php _e('Current page number','wpv-views'); ?></label>
						</div>
						<div class="wpv-dialog-pagination-wizard-preview js-wpv-dialog-pagination-wizard-preview disabled">
							<p class="wpv-pagination-preview-element js-wpv-pagination-preview-element">
								<?php _e('Showing page','wpv-views'); ?>: 2
							</p>
						</div>
					</div>

					<div class="wpv-dialog-pagination-wizard-item js-wpv-archive-pagination-shortcode">
						<div class="wpv-dialog-pagination-wizard-data">
							<input type="checkbox"
								name="pagination_control" id="pagination-include-page-total"
								class="js-wpv-archive-pagination-control"
								value="wpv-pager-archive-total-pages" />
							<label for="pagination-include-page-total"><?php _e('Total pages count','wpv-views'); ?></label>
						</div>
						<div class="wpv-dialog-pagination-wizard-preview js-wpv-dialog-pagination-wizard-preview disabled">
							<p class="wpv-pagination-preview-element js-wpv-pagination-preview-element">
								<?php _e('There are 3 pages','wpv-views'); ?>
							</p>
						</div>
					</div>

				</div>

				<h3><?php _e( 'Pagination controls', 'wpv-views' ); ?></h3>
				<div class="wpv-dialog-pagination-wizard-controls">

					<div class="wpv-dialog-pagination-wizard-item js-wpv-archive-pagination-shortcode">

						<div class="wpv-dialog-pagination-wizard-data">
							<input type="checkbox"
								name="archive_pagination_insert_prev" id="archive-pagination-insert-prev"
								class="js-wpv-archive-pagination-control"
								value="wpv-pager-archive-prev-page" />
							<label for="archive-pagination-insert-prev"><?php _e( 'Link to the previous page','wpv-views' ); ?></label>
                            <div class="wpv-dialog-pagination-wizard-item-extra js-wpv-dialog-pagination-wizard-item-extra js-wpv-archive-pagination-shortcode-attribute-container">
                                <p>
                                    <input type="checkbox"
                                           name="archive_pagination_insert_prev_force" id="archive-pagination-insert-prev-force"
                                           class="js-wpv-archive-pagination-shortcode-attribute"
                                           data-attribute="force" value="true" />
                                    <label for="archive-pagination-insert-prev-force"><?php echo esc_html( __( 'Include the Previous link on the first page.','wpv-views' ) ); ?>
                                </p>
                            </div>
						</div>

						<div class="wpv-dialog-pagination-wizard-preview js-wpv-dialog-pagination-wizard-preview disabled">
							<p class="wpv-pagination-preview-element js-wpv-pagination-preview-element next-previous-controls">
								<a href="#" class="js-wpv-disable-events">&laquo; <?php _e('Previous','wpv-views') ?></a>
							</p>
						</div>

					</div>

					<div class="wpv-dialog-pagination-wizard-item js-wpv-archive-pagination-shortcode">

						<div class="wpv-dialog-pagination-wizard-data">
							<input type="checkbox"
								name="archive_pagination_insert_next" id="archive-pagination-insert-next"
								class="js-wpv-archive-pagination-control"
								value="wpv-pager-archive-next-page" />
							<label for="archive-pagination-insert-next"><?php _e( 'Link to the next page','wpv-views' ); ?></label>
                            <div class="wpv-dialog-pagination-wizard-item-extra js-wpv-dialog-pagination-wizard-item-extra js-wpv-archive-pagination-shortcode-attribute-container">
                                <p>
                                    <input type="checkbox"
                                           name="archive_pagination_insert_next_force" id="archive-pagination-insert-next-force"
                                           class="js-wpv-archive-pagination-shortcode-attribute"
                                           data-attribute="force" value="true" />
                                    <label for="archive-pagination-insert-next-force"><?php echo esc_html( __( 'Include the Next link on the last page.','wpv-views' ) ); ?>
                                </p>
                            </div>
						</div>

						<div class="wpv-dialog-pagination-wizard-preview js-wpv-dialog-pagination-wizard-preview disabled">
							<p class="wpv-pagination-preview-element js-wpv-pagination-preview-element next-previous-controls">
								<a href="#" class="js-wpv-disable-events"><?php _e('Next','wpv-views') ?> &raquo;</a>
							</p>
						</div>

					</div>

					<div class="wpv-dialog-pagination-wizard-item js-wpv-archive-pagination-shortcode">

						<div class="wpv-dialog-pagination-wizard-data">
							<input type="checkbox"
								name="archive_pagination_control_links" id="archive-pagination-include-nav-links"
								class="js-wpv-archive-pagination-control"
								value="wpv-pager-archive-nav-links" />
							<label for="archive-pagination-include-nav-links"><?php _e('Navigation controls using links','wpv-views'); ?></label>
							<div class="wpv-dialog-pagination-wizard-item-extra js-wpv-dialog-pagination-wizard-item-extra js-wpv-archive-pagination-shortcode-attribute-container">
                                <p>
                                    <input type="checkbox"
                                           name="sub_pagination_control"
                                           class="js-wpv-pagination-sub-dialog-control js-wpv-archive-pagination-shortcode-attribute"
                                           id="js-wpv-dialog-pagination-wizard-item-extra-nav-links-first-last"
                                           data-attribute="first_last_links" value="true" />
                                    <label for="js-wpv-dialog-pagination-wizard-item-extra-nav-links-first-last"><?php echo esc_html( __( 'Add First/Last page controls','wpv-views' ) ); ?></label>
                                </p>
                                <p>
                                    <input type="checkbox"
                                           name="sub_pagination_control"
                                           class="js-wpv-pagination-sub-dialog-control js-wpv-archive-pagination-shortcode-attribute"
                                           id="js-wpv-dialog-pagination-wizard-item-extra-nav-links-previous-next"
                                           data-attribute="previous_next_links" value="true" />
                                    <label for="js-wpv-dialog-pagination-wizard-item-extra-nav-links-previous-next"><?php echo esc_html( __( 'Add Previous/Next page controls','wpv-views' ) ); ?></label>
                                </p>
                                <p class="js-wpv-dialog-pagination-wizard-sub-item-dependant-previous_next_links">
                                    <input type="checkbox"
                                           data-attribute="force_previous_next"
                                           value="true"
                                           id="wpv-dialog-pagination-wizard-item-extra-nav-links-next-prev-force"
                                           class="js-wpv-pagination-shortcode-attribute js-wpv-archive-pagination-shortcode-attribute" />
                                    <label for="wpv-dialog-pagination-wizard-item-extra-nav-links-next-prev-force"><?php echo esc_html( __( 'Include the Previous/Next links on the first and the last pages', 'wpv-views' ) ); ?></label>
                                </p>
								<p>
									<label for="wpv-dialog-pagination-wizard-item-extra-nav-links-text"><?php _e( 'Text for link to page: ', 'wpv-views' ); ?></label>
									<input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-text" data-attribute="anchor_text" class="js-wpv-archive-pagination-shortcode-attribute" placeholder="%%PAGE%%" />
								</p>
								<p>
									<label for="wpv-dialog-pagination-wizard-item-extra-nav-links-step"><?php _e( 'Pages to skip:', 'wpv-views' ); ?></label>
									<input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-step" data-attribute="step" class="js-wpv-archive-pagination-shortcode-attribute small-text" />
								</p>
								<p>
									<label for="wpv-dialog-pagination-wizard-item-extra-nav-links-ellipsis"><?php _e( 'Placeholder for skipped pages:', 'wpv-views' ); ?></label>
									<input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-ellipsis" data-attribute="ellipsis" class="js-wpv-archive-pagination-shortcode-attribute small-text" placeholder="..." />
								</p>
								<p>
									<label for="wpv-dialog-pagination-wizard-item-extra-nav-links-reach"><?php _e( 'Number of preceding and following pages:', 'wpv-views' ); ?></label>
									<input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-reach" data-attribute="reach" class="js-wpv-archive-pagination-shortcode-attribute small-text" />
								</p>
                                <p class="js-wpv-dialog-pagination-wizard-sub-item-dependant-previous_next_links">
                                    <label for="wpv-dialog-pagination-wizard-item-extra-nav-links-previous-text"><?php echo esc_html( __( 'Text for "Previous" link: ', 'wpv-views' ) ); ?></label>
                                    <input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-previous-text" data-attribute="text_for_previous_link" class="js-wpv-archive-pagination-shortcode-attribute" placeholder="<?php echo esc_attr( __( 'Previous', 'wpv-views' ) ); ?>" />
                                </p>
                                <p class="js-wpv-dialog-pagination-wizard-sub-item-dependant-previous_next_links">
                                    <label for="wpv-dialog-pagination-wizard-item-extra-nav-links-next-text"><?php echo esc_html( __( 'Text for "Next" link: ', 'wpv-views' ) ); ?></label>
                                    <input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-next-text" data-attribute="text_for_next_link" class="js-wpv-archive-pagination-shortcode-attribute" placeholder="<?php echo esc_attr( __( 'Next', 'wpv-views' ) ); ?>" />
                                </p>
                                <p class="js-wpv-dialog-pagination-wizard-sub-item-dependant-first_last_links">
                                    <label for="wpv-dialog-pagination-wizard-item-extra-nav-links-first-text"><?php echo esc_html( __( 'Text for "First" link: ', 'wpv-views' ) ); ?></label>
                                    <input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-first-text" data-attribute="text_for_first_link" class="js-wpv-archive-pagination-shortcode-attribute" placeholder="<?php echo esc_attr( __( 'First', 'wpv-views' ) ); ?>" />
                                </p>
                                <p class="js-wpv-dialog-pagination-wizard-sub-item-dependant-first_last_links">
                                    <label for="wpv-dialog-pagination-wizard-item-extra-nav-links-last-text"><?php echo esc_html( __( 'Text for "Last" link: ', 'wpv-views' ) ); ?></label>
                                    <input type="text" id="wpv-dialog-pagination-wizard-item-extra-nav-links-last-text" data-attribute="text_for_last_link" class="js-wpv-archive-pagination-shortcode-attribute" placeholder="<?php echo esc_attr( __( 'Last', 'wpv-views' ) ); ?>" />
                                </p>
								<p style="border-top:solid 1px #dedede;padding-top:5px;">
									<?php
									echo
										'<a href="https://toolset.com/course-lesson/creating-a-custom-archive-page/?utm_source=plugin&utm_medium=gui&utm_campaign=blocks#add-pagination-to-the-archive" title="' . esc_attr( __( 'Documentation for pagination links', 'wpv-views' ) ) . '" target="blank">'
										. __( 'Check the documentation', 'wpv-views' )
										. '</a>';
									?>
								</p>
							</div>
						</div>

						<div class="wpv-dialog-pagination-wizard-preview wpv-dialog-pagination-wizard-pagenavi-preview js-wpv-dialog-pagination-wizard-preview disabled">
                            <p class="wpv-pagination-preview-element js-wpv-pagination-preview-element">
                                <a href="#" class="js-wpv-disable-events js-wpv-dialog-pagination-wizard-preview-first_last_links">&laquo;&laquo; <?php echo esc_html( __( 'First', 'wpv-views' ) ); ?></a>
                                <a href="#" class="js-wpv-disable-events js-wpv-dialog-pagination-wizard-preview-previous_next_links">&laquo; <?php echo esc_html( __( 'Previous', 'wpv-views' ) ); ?></a>
                                <span class="current">1</span>&nbsp;
                                <a href="#" class="js-wpv-disable-events">2</a>&nbsp;
                                <span>...</span>&nbsp;
                                <a href="#" class="js-wpv-disable-events">10</a>&nbsp;
                                <span>...</span>&nbsp;
                                <a href="#" class="js-wpv-disable-events">27</a>&nbsp;
                                <a href="#" class="js-wpv-disable-events js-wpv-dialog-pagination-wizard-preview-previous_next_links"><?php echo esc_html( __( 'Next', 'wpv-views' ) ); ?> &raquo;</a>
                                <a href="#" class="js-wpv-disable-events js-wpv-dialog-pagination-wizard-preview-first_last_links"><?php echo esc_html( __( 'Last', 'wpv-views' ) ); ?> &raquo;&raquo;</a>
                            </p>
						</div>

					</div>

				</div>
                <h3><?php echo esc_html( __( 'Output style', 'wpv-views' ) ); ?></h3>
                <div class="wpv-dialog-pagination-wizard-controls">
                    <div class="wpv-dialog-pagination-wizard-item js-wpv-dialog-pagination-wizard-item">
                        <div class="wpv-dialog-pagination-wizard-data">
                            <p>
                                <input type="radio"
                                       name="pagination_control"
                                       id="wpv-pagination-bootstrap-output"
                                       class="js-wpv-archive-pagination-control" value="<?php echo esc_attr( 'bootstrap' ); ?>" />
                                <label for="wpv-pagination-bootstrap-output"><?php echo esc_html( __( 'Fully styled output', 'wpv-views' ) ); ?></label>
                                <input type="radio"
                                       style="margin-left:15px"
                                       name="pagination_control"
                                       id="wpv-pagination-raw-output"
                                       class="js-wpv-archive-pagination-control"
                                       value="<?php echo esc_attr( 'raw' ); ?>" checked="checked" />
                                <label for="wpv-pagination-raw-output"><?php echo esc_html( __( 'Raw output', 'wpv-views' ) ); ?></label>
                            </p>
                        </div>
                    </div>
                </div>
			</div>
		</div>

	</div>
	<?php
}

function render_shared_dialogs( $args, $dismissed_dialogs ) {

	$view_settings					= $args['settings'];
	$view_settings_stored			= $args['settings_stored'];
	$view_layout_settings			= $args['layout_settings'];
	$view_layout_settings_stored	= $args['layout_settings_stored'];
	$view_id						= $args['id'];
	$user_id						= $args['user_id'];

	$query_type = 'posts';
	if (
		isset( $view_settings['view-query-mode'] )
		&& 'normal' ==  $view_settings['view-query-mode']
	) {
		if ( isset( $view_settings['query_type'][0] ) ) {
			$query_type = $view_settings['query_type'][0];
		}
	}

	?>
	<div id="js-wpv-shared-hidden-dialogs-container" class="popup-window-container">
		<?php
		/**
		* Insert frontend sorting controls dialog
		*/
		$list_style_options = apply_filters( 'wpv_filter_wpv_get_styles_for_list_controls', array() );
		?>
		<div id="js-wpv-frontend-sorting-dialog" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container wpv-dialog-frontend-sorting-controls">
			<div class="wpv-dialog wpv-shortcode-gui-content-wrapper">
				<div class="wpv-controls-with-preview-container">
					<div class="wpv-controls-with-preview-container-controls">
						<h3><?php _e( 'Sorting options for visitors', 'wpv-views' ); ?></h3>
						<div class="wpv-editable-list-wrapper js-wpv-frontend-sorting-orderby-options-wrapper" style="overflow:hidden;padding-bottom:5px;">
							<table class="wpv-editable-list js-wpv-frontend-sorting-orderby-options-list">
								<thead>
									<tr>
										<th class="wpv-collapsed-width js-wpv-frontend-sorting-orderby-options-list-column-sortable">

										</th>
										<th class="js-wpv-frontend-sorting-orderby-options-list-column-field">
											<?php echo esc_html( 'Field to order by', 'wpv-views' ); ?>
										</th>
										<th class="js-wpv-frontend-sorting-orderby-options-list-column-direction">
											<?php echo esc_html( 'Default direction', 'wpv-views' ); ?>
										</th>
										<th class="js-wpv-frontend-sorting-orderby-options-list-column-label">
											<?php echo esc_html( 'Option labels', 'wpv-views' ); ?>
										</th>
										<th class="wpv-collapsed-width js-wpv-frontend-sorting-orderby-options-list-column-remove">

										</th>
									</tr>
								</thead>
								<tbody>
									<!-- This gets populated in JS -->
								</tbody>
								<tfoot>
									<tr>
										<th class="js-wpv-frontend-sorting-orderby-options-list-column-sortable">

										</th>
										<th class="js-wpv-frontend-sorting-orderby-options-list-column-field">
											<?php echo esc_html( 'Field to order by', 'wpv-views' ); ?>
										</th>
										<th class="js-wpv-frontend-sorting-orderby-options-list-column-direction">
											<?php echo esc_html( 'Default direction', 'wpv-views' ); ?>
										</th>
										<th class="js-wpv-frontend-sorting-orderby-options-list-column-label">
											<?php echo esc_html( 'Option labels', 'wpv-views' ); ?>
										</th>
										<th class="js-wpv-frontend-sorting-orderby-options-list-column-remove">

										</th>
									</tr>
								</tfoot>
							</table>
							<button class="button-secondary wpv-editable-list-item-add js-wpv-frontend-sorting-orderby-options-add">
								<?php
								echo sprintf(
									__( '%s Add a new option', 'wpv-views' ),
									'<i class="fa fa-plus"></i>'
								);
								?>
							</button>
						</div>
						<div class="js-wpv-frontend-sorting-orderby-options-enabled" style="display:none">
							<?php _e( 'Show these options', 'wpv-views' ); ?>
							<select id="js-wpv-frontend-sorting-orderby-options-type" class="js-wpv-frontend-sorting-orderby-options-type">
								<option value="select"><?php _e( 'as a dropdown select', 'wpv-views' ); ?></option>
								<option value="list"><?php _e( 'as a dropdown list', 'wpv-views' ); ?></option>
								<option value="radio"><?php _e( 'as a set of radio options', 'wpv-views' ); ?></option>
							<select>
							<span class="js-wpv-frontend-sorting-orderby-type-extra js-wpv-frontend-sorting-orderby-type-list-extra" style="display:none">
								<?php _e( 'using the style', 'wpv-views' ); ?>
								<select id="js-wpv-frontend-sorting-orderby-list-style" autocomplete="off">
								<?php
								foreach ( $list_style_options as $style_option_key => $style_option_data ) {
								?>
									<option value="<?php echo esc_attr( $style_option_key ); ?>"><?php echo isset( $style_option_data['label'] ) ? esc_html( $style_option_data['label'] ) : esc_html( $style_option_key ); ?></option>
								<?php
								}
								?>
								</select>
							</span>
							<p>
								<input type="checkbox" class="js-wpv-frontend-sorting-order-enable" id="js-wpv-frontend-sorting-order-enable" name="wpv-frontend-sorting-order-enable" />
								<label for="js-wpv-frontend-sorting-order-enable"><?php _e( 'Allow to modify the sorting direction', 'wpv-views' ); ?></label>
								<span class="js-wpv-frontend-sorting-order-options" style="display:none;">
									<select id="js-wpv-frontend-sorting-order-options-type" class="js-wpv-frontend-sorting-order-options-type">
										<option value="select"><?php _e( 'as a dropdown select', 'wpv-views' ); ?></option>
										<option value="list"><?php _e( 'as a dropdown list', 'wpv-views' ); ?></option>
										<option value="radio"><?php _e( 'as a set of radio options', 'wpv-views' ); ?></option>
									<select>
									<span class="js-wpv-frontend-sorting-order-type-extra js-wpv-frontend-sorting-order-type-list-extra" style="display:none">
										<?php _e( 'using the style', 'wpv-views' ); ?>
										<select id="js-wpv-frontend-sorting-order-list-style" autocomplete="off">
										<?php
										foreach ( $list_style_options as $style_option_key => $style_option_data ) {
										?>
											<option value="<?php echo esc_attr( $style_option_key ); ?>"><?php echo isset( $style_option_data['label'] ) ? esc_html( $style_option_data['label'] ) : esc_html( $style_option_key ); ?></option>
										<?php
										}
										?>
										</select>
									</span>
									<select id="wpv-frontend-sorting-order-options-order" class="js-wpv-frontend-sorting-order-options-order" style="display:none">
										<option value="asc,desc"><?php _e( 'Ascending, Descending', 'wpv-views' ); ?></option>
										<option value="desc,asc"><?php _e( 'Descending, Ascending', 'wpv-views' ); ?></option>
									<select>
									<span style="display:none">
									<label for="js-wpv-frontend-sorting-order-options-label-asc"><?php _e( 'Label for the "ascending" option', 'wpv-views' ); ?></label>
									<input type="text" id="js-wpv-frontend-sorting-order-options-label-asc" class="regular-text js-wpv-frontend-sorting-order-options-label-asc" value="<?php echo esc_attr( __( 'Ascending', 'wpv-views' ) ); ?>" />
									<br />
									<label for="js-wpv-frontend-sorting-order-options-label-desc"><?php _e( 'Label for the "descending" option', 'wpv-views' ); ?></label>
									<input type="text" id="js-wpv-frontend-sorting-order-options-label-desc" class="regular-text js-wpv-frontend-sorting-order-options-label-desc" value="<?php echo esc_attr( __( 'Descending', 'wpv-views' ) ); ?>" />
									</span>
								</span>
							</p>
						</div>
					</div>
					<div class="wpv-controls-with-preview-container-preview">
						<h3><?php _e( 'Preview', 'wpv-views' ); ?></h3>
						<p class="toolset-alert toolset-alert-info js-wpv-frontend-sorting-preview-disabled" style="padding-left:20px;padding-right:10px;">
							<?php _e( 'Add sorting options to see the preview.', 'wpv-views' ); ?>
						</p>
						<p class="js-wpv-frontend-sorting-preview-enabled" style="display:none;padding-left:20px;padding-right:10px;line-height:2.5;">
							<span class="wpv-controls-with-preview-container-preview-item disabled js-wpv-frontend-sorting-orderby-preview js-wpv-frontend-sorting-orderby-select-preview">
								<?php _e( 'Order by: ', 'wpv-views' ); ?>
								<select class="js-wpv-disable-events">
									<option><?php _e( 'Option 1', 'wpv-views' ); ?></option>
									<option><?php _e( 'Option 2', 'wpv-views' ); ?></option>
									<option><?php _e( 'Option 3', 'wpv-views' ); ?></option>
								</select>
							</span>
							<span class="wpv-controls-with-preview-container-preview-item disabled js-wpv-frontend-sorting-orderby-preview js-wpv-frontend-sorting-orderby-list-preview" style="display:none">
								<?php _e( 'Order by: ', 'wpv-views' ); ?>
								<span class="wpv-sort-list-dropdown wpv-sort-list-orderby-dropdown wpv-sort-list-dropdown-style-default js-wpv-sort-list-dropdown js-wpv-sort-list-orderby-dropdown" style="width:9.5em;">
									<span class="wpv-sort-list js-wpv-sort-list">
										<span class="wpv-sort-list-item wpv-sort-list-orderby-item wpv-sort-list-current js-wpv-sort-list-item" style="width:9.5em; display: block;">
											<a class="wpv-sort-list-anchor js-wpv-sort-list-orderby js-wpv-disable-events" href="#">
												<span><?php _e( 'Option 1', 'wpv-views' ); ?></span>
											</a>
										</span>
										<span class="wpv-sort-list-item wpv-sort-list-orderby-item js-wpv-sort-list-item" style="width:9.5em; display: none;">
											<a class="wpv-sort-list-anchor js-wpv-sort-list-orderby js-wpv-disable-events" href="#">
												<span><?php _e( 'Option 2', 'wpv-views' ); ?></span>
											</a>
										</span>
										<span class="wpv-sort-list-item wpv-sort-list-orderby-item js-wpv-sort-list-item" style="width:9.5em; display: none;">
											<a class="wpv-sort-list-anchor js-wpv-sort-list-orderby js-wpv-disable-events" href="#">
												<span><?php _e( 'Option 3', 'wpv-views' ); ?></span>
											</a>
										</span>
									</span>
								</span>
							</span>
							<span class="wpv-controls-with-preview-container-preview-item disabled js-wpv-frontend-sorting-orderby-preview js-wpv-frontend-sorting-orderby-radio-preview" style="display:none">
								<?php _e( 'Order by: ', 'wpv-views' ); ?>
								<label><input type="radio" class="js-wpv-disable-events" checked="checked"><?php echo esc_html( __( 'Option 1', 'wpv-views' ) ); ?></label>
								<label><input type="radio" class="js-wpv-disable-events"><?php echo esc_html( __( 'Option 2', 'wpv-views' ) ); ?></label>
								<label><input type="radio" class="js-wpv-disable-events"><?php echo esc_html( __( 'Option 3', 'wpv-views' ) ); ?></label>
							</span>
							<span class="wpv-controls-with-preview-container-preview-item disabled js-wpv-frontend-sorting-order-preview js-wpv-frontend-sorting-order-select-preview">
								<select class="js-wpv-disable-events">
									<option><?php _e( 'Ascending', 'wpv-views' ); ?></option>
									<option><?php _e( 'Descending', 'wpv-views' ); ?></option>
								</select>
							</span>
							<span class="wpv-controls-with-preview-container-preview-item disabled js-wpv-frontend-sorting-order-preview js-wpv-frontend-sorting-order-list-preview" style="display:none">
								<span class="wpv-sort-list-dropdown wpv-sort-list-order-dropdown wpv-sort-list-dropdown-style-default js-wpv-sort-list-dropdown js-wpv-sort-list-order-dropdown" style="width:9.5em;">
									<span class="wpv-sort-list js-wpv-sort-list">
										<span class="wpv-sort-list-item wpv-sort-list-order-item wpv-sort-list-current js-wpv-sort-list-item" style="width:9.5em; display: block;">
											<a class="wpv-sort-list-anchor js-wpv-sort-list-orderby js-wpv-disable-events" href="#">
												<span><?php _e( 'Ascending', 'wpv-views' ); ?></span>
											</a>
										</span>
										<span class="wpv-sort-list-item wpv-sort-list-orderby-item js-wpv-sort-list-item" style="width:9.5em; display: none;">
											<a class="wpv-sort-list-anchor js-wpv-sort-list-order js-wpv-disable-events" href="#">
												<span><?php _e( 'Descending', 'wpv-views' ); ?></span>
											</a>
										</span>
									</span>
								</span>
							</span>
							<span class="wpv-controls-with-preview-container-preview-item disabled js-wpv-frontend-sorting-order-preview js-wpv-frontend-sorting-order-radio-preview" style="display:none">
								<br />
								<?php _e( 'Order: ', 'wpv-views' ); ?>
								<label><input type="radio" class="js-wpv-disable-events" checked="checked"><?php echo esc_html( __( 'Ascending', 'wpv-views' ) ); ?></label>
								<label><input type="radio" class="js-wpv-disable-events"><?php echo esc_html( __( 'Descending', 'wpv-views' ) ); ?></label>
							</span>
						</p>
					</div>
				</div>
			</div>
		</div>

		<?php
		/**
		* Insert frontend events dialog
		*/
		?>
		<div id="js-wpv-dialog-views-frontend-events" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container wpv-dialog-frontend-events">
			<div class="wpv-dialog wpv-shortcode-gui-content-wrapper">
				<p class="toolset-alert toolset-alert-info" style="margin-top:0;">
					<i class="icon-fire fa fa-fire toolset-rounded-icon"></i>
					<?php _e( 'You can add javascript callbacks when some events are triggered', 'wpv-views' ); ?>
				</p>
				<div id="js-wpv-shortcode-gui-dialog-tabs" class="wpv-shortcode-gui-tabs js-wpv-shortcode-gui-tabs">
					<?php
					$frontend_events_tabs = array(
						'wpv-dialog-frontend-events-pagination-container'	=> array(
							'title'	=> __( 'Pagination', 'wpv-views' )
						)
					);
					if ( $query_type == 'posts' ) {
						$frontend_events_tabs['wpv-dialog-frontend-events-parametric-container'] = array(
							'title' => __( 'Custom search', 'wpv-views' )
						);
					}
					$frontend_events_tabs = apply_filters( 'wpv_filter_wpv_dialog_frontend_events_tabs', $frontend_events_tabs, $query_type );
					?>
					<ul>
						<?php
						foreach( $frontend_events_tabs as $tab_id => $tab_data ) {
							?>
							<li><a href="#<?php echo esc_attr( $tab_id ); ?>"><?php echo esc_html( $tab_data['title'] ); ?></a></li>
							<?php
						}
						?>
					</ul>
					<div id="wpv-dialog-frontend-events-pagination-container">
						<h2><?php _e( 'Pagination events', 'wpv-views' ); ?></h2>
						<p>
							<?php _e( 'The Views AJAX pagination triggers some events.', 'wpv-views' ); ?>
						</p>
						<ul>
							<li>
								<label for="wpv-frontent-event-pagination-completed">
									<input type="checkbox" id="wpv-frontent-event-pagination-completed" value="1" class="js-wpv-frontend-event-gui" data-event="js_event_wpv_pagination_completed" />
									<?php _e( 'The AJAX pagination has been completed', 'wpv-views' ); ?>
								</label>
								<span class="wpv-helper-text"><?php _e( 'This happens after the AJAX pagination has been completed', 'wpv-views' ); ?></span>
							</li>
						</ul>
					</div>
					<div id="wpv-dialog-frontend-events-parametric-container" class="wpv-settings-query-type-posts<?php if ( $query_type != 'posts' ) { echo ' hidden'; } ?>">
						<h2><?php _e( 'Custom search events', 'wpv-views' ); ?></h2>
						<p>
							<?php _e( 'The custom search triggers some events when set to update options or results automatically.', 'wpv-views' ); ?>
						</p>
						<ul>
							<li>
								<label for="wpv-frontent-event-parametric-search-triggered">
									<input type="checkbox" id="wpv-frontent-event-parametric-search-triggered" value="1" class="js-wpv-frontend-event-gui" data-event="js_event_wpv_parametric_search_triggered" />
									<?php _e( 'The custom search has been triggered', 'wpv-views' ); ?>
								</label>
								<span class="wpv-helper-text"><?php _e( 'This happens before Views collects any form data', 'wpv-views' ); ?></span>
							</li>
							<li>
								<label for="wpv-frontend-event-parametric-search-started">
									<input type="checkbox" id="wpv-frontend-event-parametric-search-started" value="1" class="js-wpv-frontend-event-gui" data-event="js_event_wpv_parametric_search_started" />
									<?php _e( 'The custom search data has been collected', 'wpv-views' ); ?>
								</label>
								<span class="wpv-helper-text"><?php _e( 'This happens after the data has been collected, but before performing any action', 'wpv-views' ); ?></span>
							</li>
							<li>
								<label for="wpv-frontend-event-parametric-search-form-updated">
									<input type="checkbox" id="wpv-frontend-event-parametric-search-form-updated" value="1" class="js-wpv-frontend-event-gui" data-event="js_event_wpv_parametric_search_form_updated" />
									<?php _e( 'The custom search form has been updated', 'wpv-views' ); ?>
								</label>
								<span class="wpv-helper-text"><?php _e( 'This happens after Views updates the form to show only relevant options', 'wpv-views' ); ?></span>
							</li>
							<li>
								<label for="wpv-frontend-event-parametric-search-results-updated">
									<input type="checkbox" id="wpv-frontend-event-parametric-search-results-updated" value="1" class="js-wpv-frontend-event-gui" data-event="js_event_wpv_parametric_search_results_updated" />
									<?php _e( 'The custom search results have been updated', 'wpv-views' ); ?>
								</label>
								<span class="wpv-helper-text"><?php _e( 'This happens after Views updates the search results', 'wpv-views' ); ?></span>
							</li>
						</ul>
					</div>
					<?php
					do_action( 'wpv_filter_wpv_dialog_frontend_events_sections', $query_type );
					?>
				</div>
			</div>
		</div>

		<?php
		/**
		* Delete or edit custom field filters on bulk delete
		*/
		?>
		<div id="js-wpv-filter-custom-field-delete-filter-row-dialog" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container wpv-dialog-frontend-events">
			<div class="wpv-dialog">
				<h3><?php _e('There are more than one custom field filters', 'wpv-views'); ?></h3>
				<p>
					<?php _e( 'You can delete them all at once or open the editor and delete individual filters.', 'wpv-views' ); ?>
				</p>
			</div>
		</div>

		<?php
		/**
		* Delete or edit taxonomy filters on bulk delete
		*/
		?>
		<div id="js-wpv-filter-taxonomy-delete-filter-row-dialog" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container wpv-dialog-frontend-events">
			<div class="wpv-dialog">
				<h3><?php _e('There are more than one taxonomy filters', 'wpv-views'); ?></h3>
				<p>
					<?php _e( 'You can delete them all at once or open the editor and delete individual filters.', 'wpv-views' ); ?>
				</p>
			</div>
		</div>

		<?php
		/**
		* Remove CT from View or WPA assignment dialog
		*/
		$dismissed_classname = '';
		if ( isset( $dismissed_dialogs['remove-content-template-from-view'] ) ) {
			$dismissed_classname = ' js-wpv-dialog-dismissed';
		}
		?>
		<div id="js-wpv-dialog-remove-content-template-from-view-dialog" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container<?php echo $dismissed_classname; ?>">
            <div class="wpv-dialog">
                <p class="toolset-alert toolset-alert-info">
                    <?php _e( "This will not delete the Content Template, it will just remove the link between it and the current element." ) ?>
                </p>
				<p>
					<input type="checkbox" id="wpv-dettach-inline-content-template-dismiss" class="js-wpv-dettach-inline-content-template-dismiss" />
					<label for="wpv-dettach-inline-content-template-dismiss">
						<?php _e("Don't show this message again",'wpv-views') ?>
					</label>
            	</p>
            </div>
		</div>
	</div>
	<?php
}


// ========================================
// This should be moved to CRED
// @todo add this in CRED with a higher priority for the filters
// @todo remove this when CRED has been released with this included
// ========================================

/**
* wpv_add_cred_to_formatting_instructions
*
* Registers the hooks to add the CRED information to the formatting instructions under CodeMirror textareas
*
* @since 1.7
*/

add_action( 'init', 'wpv_add_cred_to_formatting_instructions' );

function wpv_add_cred_to_formatting_instructions() {
	if ( class_exists( 'CRED_CRED' ) ) {
		// Register the section
		add_filter( 'wpv_filter_formatting_help_layout', 'wpv_register_cred_section' );// -2
		add_filter( 'wpv_filter_formatting_help_inline_content_template', 'wpv_register_cred_section' );// -2
		add_filter( 'wpv_filter_formatting_help_layouts_content_template_cell', 'wpv_register_cred_section' );// -2
		add_filter( 'wpv_filter_formatting_help_combined_output', 'wpv_register_cred_section' );// -2
		add_filter( 'wpv_filter_formatting_help_content_template', 'wpv_register_cred_section' );// -2
		// Register the section content
		add_filter( 'wpv_filter_formatting_instructions_section', 'wpv_cred_shortcodes_instructions', 10, 2 );
	}
}

function wpv_register_cred_section( $sections ) {
	if ( ! in_array( 'cred', $sections ) ) {
		array_splice( $sections, -2, 0, array( 'cred' ) );
	}
	return $sections;
}

function wpv_cred_shortcodes_instructions( $return, $section ) {
	if ( $section == 'cred' ) {
		$return = array(
			'classname' => 'js-wpv-editor-instructions-for-cred',
			'title' => __( 'CRED Forms', 'wpv-views' ),
			'content' => '<p>'
					. __( 'Click on the <strong>CRED Forms</strong> button to add forms.', 'wpv-views' )
					. WPV_MESSAGE_SPACE_CHAR
					. __( 'Normally, you will use \'edit forms\' and \'delete forms\' in Content Templates used in loops.', 'wpv-views' )
					. '</p>',
			'table' => array(),
			'content_extra' => ''
		);
	}
	return $return;
}

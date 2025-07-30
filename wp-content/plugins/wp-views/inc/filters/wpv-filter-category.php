<?php

/**
* Taxonomy filter
*
* @package Views
*
* @since unknown
*/

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

WPV_Taxonomy_Filter::on_load();

/**
* WPV_Taxonomy_Filter
*
* Views Taxonomy Filter Class
*
* @since 1.7.0
* @since 1.12.1	Changes in the filter modes for the posts filter and the taxonomy filter
* 		FROM PAGE becomes current_post_or_parent_post_view and tracks $WP_Views->get_current_page()
* 		top_current_post tracks $WP_Views->get_top_current_page()
* 		FROM PARENT VIEW becomes becomes current_taxonomy_view
* @since 2.1.0 Added to WordPress Archives
* @since 2.1.0 Include this file only when editing a View or WordPress Archive, or when doing AJAX
* @since 2.4.0 Include a custom search frontend filter
*/

class WPV_Taxonomy_Filter {

	const FILTER_TYPE = 'taxonomy';

    static function on_load() {
        add_action( 'init',			 array( 'WPV_Taxonomy_Filter', 'init' ) );
		add_action( 'admin_init',	 array( 'WPV_Taxonomy_Filter', 'admin_init' ) );
		add_action( 'rest_api_init', array( 'WPV_Taxonomy_Filter', 'admin_init' ) );
		// Scripts
		add_action( 'admin_enqueue_scripts', array( 'WPV_Taxonomy_Filter','admin_enqueue_scripts' ), 20 );
		// Register custom search filter in dialog
		add_filter( 'wpv_filter_wpv_register_form_filters_shortcodes', array( 'WPV_Taxonomy_Filter', 'wpv_custom_search_filter_shortcodes_post_taxonomy' ), 5 );
    }

    static function init() {
		wp_register_script(
			'views-filter-category-js',
			WPV_URL . "/res/js/filters/views_filter_category.js",
			array( 'views-filters-js', 'underscore' ),
			WPV_VERSION,
			false
		);
		$filter_texts = array(
			'dialog_title'		=> __( 'Delete taxonomy filters', 'wpv-views' ),
			'cancel'			=> __( 'Cancel', 'wpv-views' ),
			'edit_filters'		=> __( 'Edit the taxonomy filters', 'wpv-views' ),
			'delete_filters'	=> __( 'Delete all taxonomy filters', 'wpv-views' ),
			'post'				=> array(

								),
			'archive'			=> array(
									'disable_post_taxonomy_filter'	=> __( 'This filter will not be applied to Taxonomy Archives matching the filtered taxonomies: %s', 'wpv-views' ),
								),
		);
		wp_localize_script( 'views-filter-category-js', 'wpv_category_filter_texts', $filter_texts );
    }

	static function admin_init() {
		// Register filters in dialogs
		add_filter( 'wpv_filters_add_filter',						array( 'WPV_Taxonomy_Filter', 'wpv_filters_add_filter_taxonomy' ), 20, 2 );
		add_filter( 'wpv_filters_add_archive_filter',				array( 'WPV_Taxonomy_Filter', 'wpv_filters_add_archive_filter_taxonomy' ), 20, 1 );
		// Register filters in lists
		add_action( 'wpv_add_filter_list_item',						array( 'WPV_Taxonomy_Filter', 'wpv_add_filter_taxonomy_list_item' ), 1, 1 );
		// Update and delete
		add_action( 'wp_ajax_wpv_filter_taxonomy_update',			array( 'WPV_Taxonomy_Filter', 'wpv_filter_taxonomy_update_callback' ) );
		add_action( 'wp_ajax_wpv_filter_taxonomy_delete',			array( 'WPV_Taxonomy_Filter', 'wpv_filter_taxonomy_delete_callback' ) );
		// TODO This might not be needed here, maybe for summary filter
		//add_action( 'wp_ajax_wpv_filter_taxonomy_sumary_update',	array( 'WPV_Taxonomy_Filter', 'wpv_filter_taxonomy_sumary_update_callback' ) );
	}

	/**
	* admin_enqueue_scripts
	*
	* Register the needed script for this filter
	*
	* @since 1.7
	*/

	static function admin_enqueue_scripts( $hook ) {
		wp_enqueue_script( 'views-filter-category-js' );
	}

	/**
	* wpv_filters_add_filter_taxonomy
	*
	* Register the taxonomy filter in the popup dialog
	*
	* @param $filters
	* @param $post_type
	*
	* @since unknown
	*/

	static function wpv_filters_add_filter_taxonomy( $filters, $post_type ) {
		$taxonomies_valid = get_object_taxonomies( $post_type, 'objects' );
		$exclude_tax_slugs = array();
		$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
		foreach ( $taxonomies_valid as $category_slug => $category ) {
			if ( in_array( $category_slug, $exclude_tax_slugs ) ) {
				continue;
			}
			if ( ! $category->show_ui ) {
				continue; // Only show taxonomies with show_ui set to TRUE
			}
			$taxonomy = $category->name;
			$name = ( $taxonomy == 'category' ) ? 'post_category' : 'tax_input[' . $taxonomy . ']';
			$filters[$name] = array(
				'name' => $category->label,
				'present' => 'tax_' . $taxonomy . '_relationship',
				'callback' => array( 'WPV_Taxonomy_Filter', 'wpv_add_new_filter_taxonomy_list_item' ),
				'args' => $category,
				'group' => __( 'Taxonomies', 'wpv-views' )
			);
		}
		return $filters;
	}

	/**
	* wpv_filters_add_archive_filter_taxonomy
	*
	* Register the taxonomy filter in the popup dialog
	*
	* @param $filters
	*
	* @since 2.1
	*/

	static function wpv_filters_add_archive_filter_taxonomy( $filters ) {
		$taxonomies_valid = get_taxonomies( '', 'objects' );
		$exclude_tax_slugs = array();
		$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
		foreach ( $taxonomies_valid as $category_slug => $category ) {
			if ( in_array( $category_slug, $exclude_tax_slugs ) ) {
				continue;
			}
			if ( ! $category->show_ui ) {
				continue; // Only show taxonomies with show_ui set to TRUE
			}
			$taxonomy = $category->name;
			$name = ( $taxonomy == 'category' ) ? 'post_category' : 'tax_input[' . $taxonomy . ']';
			$filters[$name] = array(
				'name'		=> $category->label,
				'present'	=> 'tax_' . $taxonomy . '_relationship',
				'callback'	=> array( 'WPV_Taxonomy_Filter', 'wpv_add_new_archive_filter_taxonomy_list_item' ),
				'args'		=> $category,
				'group'		=> __( 'Taxonomies', 'wpv-views' )
			);
		}
		return $filters;
	}

	/**
	* wpv_add_new_filter_taxonomy_list_item
	*
	* Register the taxonomy filter in the filters list
	*
	* @param $args
	*
	* @since unknown
	*/

	static function wpv_add_new_filter_taxonomy_list_item( $args ) {
		$relationship_name = ( $args->name == 'category' ) ? 'tax_category_relationship' : 'tax_' . $args->name . '_relationship';
		$new_tax_filter_settings = array(
			'view-query-mode'	=> 'normal',
			$relationship_name	=> 'IN',
		);
		WPV_Taxonomy_Filter::wpv_add_filter_taxonomy_list_item( $new_tax_filter_settings );
	}

	/**
	* wpv_add_new_archive_filter_taxonomy_list_item
	*
	* Register the taxonomy filter in the filters list
	*
	* @param $args
	*
	* @since 2.1
	*/

	static function wpv_add_new_archive_filter_taxonomy_list_item( $args ) {
		$relationship_name = ( $args->name == 'category' ) ? 'tax_category_relationship' : 'tax_' . $args->name . '_relationship';
		$new_tax_filter_settings = array(
			'view-query-mode'	=> 'archive',
			$relationship_name	=> 'IN',
		);
		WPV_Taxonomy_Filter::wpv_add_filter_taxonomy_list_item( $new_tax_filter_settings );
	}

	/**
	* wpv_add_filter_taxonomy_list_item
	*
	* Render taxonomy filter item in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_add_filter_taxonomy_list_item( $view_settings ) {
		if ( ! isset( $view_settings['taxonomy_relationship'] ) ) {
			$view_settings['taxonomy_relationship'] = 'AND';
		}
		$summary = '';
		$td = '';
		$taxonomies = get_taxonomies( '', 'objects' );
		foreach ( $taxonomies as $category_slug => $category ) {
			$save_name = ( $category->name == 'category' ) ? 'post_category' : 'tax_input_' . $category->name;
			$relationship_name = ( $category->name == 'category' ) ? 'tax_category_relationship' : 'tax_' . $category->name . '_relationship';
			if ( isset( $view_settings[$relationship_name] ) ) {
				if ( ! isset( $view_settings[$save_name] ) ) {
					$view_settings[$save_name] = array();
				}
				if ( ! empty( $view_settings[$save_name] ) ) {
					$adjusted_term_ids = array();
					foreach ( $view_settings[$save_name] as $candidate_term_id ) {
						// WordPress 4.2 compatibility - split terms
						$candidate_term_id_splitted = wpv_compat_get_split_term( $candidate_term_id, $category->name );
						if ( $candidate_term_id_splitted ) {
							$candidate_term_id = $candidate_term_id_splitted;
						}
						// WPML support
						$candidate_term_id = apply_filters( 'translate_object_id', $candidate_term_id, $category->name, true, null );
						$adjusted_term_ids[] = $candidate_term_id;
					}
					$view_settings[$save_name] = $adjusted_term_ids;
				}
				$name = ( $category->name == 'category' ) ? 'post_category' : 'tax_input[' . $category->name . ']';
				if ( \WPV_Filter_Manager::FILTER_MODE_FULL === toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, 'taxonomy', $category->name, \WPV_Filter_Manager::EDITOR_MODE ), \WPV_Filter_Manager::FILTER_MODE_FULL ) ) {
					$td .= WPV_Taxonomy_Filter::wpv_get_list_item_ui_post_taxonomy( $category, $view_settings[$save_name], $view_settings );
				} else {
					$td .= '<div class="wpv-filter-multiple-element wpv-filter-multiple-readonly-element js-wpv-filter-multiple-element js-wpv-filter-multiple-readonly-element js-wpv-filter-taxonomy-multiple-element">';
					$td .= wpv_get_taxonomy_summary( $name, $view_settings, $view_settings[$save_name] );
					$td .= '</div>';
				}
				if ( $summary != '' ) {
					if ( $view_settings['taxonomy_relationship'] == 'OR') {
						$summary .= __( ' OR ', 'wpv-views' );
					} else {
						$summary .= __( ' AND ', 'wpv-views' );
					}
				}
				$summary .= wpv_get_taxonomy_summary( $name, $view_settings, $view_settings[$save_name] );
			}
		}
		if ( $td != '' ) {
			ob_start();
			WPV_Filter_Item::filter_list_item_buttons( 'taxonomy', 'wpv_filter_taxonomy_update', wp_create_nonce( 'wpv_view_filter_taxonomy_nonce' ), 'wpv_filter_taxonomy_delete', wp_create_nonce( 'wpv_view_filter_taxonomy_delete_nonce' ) );
			?>
			<?php if ( $summary != '' ) {
			?>
				<p class='wpv-filter-taxonomy-edit-summary js-wpv-filter-summary js-wpv-filter-taxonomy-summary'>
				<?php
				echo __( 'Select posts with taxonomy: ', 'wpv-views' ) . $summary;
				?>
				</p>
			<?php
			}
			?>
			<div id="wpv-filter-taxonomy-edit" class="wpv-filter-edit js-wpv-filter-edit js-wpv-filter-taxonomy-edit js-wpv-filter-options" style="padding-bottom:28px;">
				<?php echo $td;?>
				<div class="wpv-filter-taxonomy-relationship wpv-filter-multiple-element js-wpv-filter-taxonomy-relationship">
					<h4><?php _e('Taxonomy relationship:', 'wpv-views') ?></h4>
					<div class="wpv-filter-multiple-element-options">
						<?php _e('Relationship to use when querying with multiple taxonomies:', 'wpv-views'); ?>
						<select name="taxonomy_relationship">
							<option value="AND" <?php selected( $view_settings['taxonomy_relationship'], 'AND' ); ?>><?php _e( 'AND', 'wpv-views' ); ?>&nbsp;</option>
							<option value="OR" <?php selected( $view_settings['taxonomy_relationship'], 'OR' ); ?>><?php _e( 'OR', 'wpv-views' ); ?></option>
						</select>
					</div>
				</div>
				<div class="js-wpv-filter-multiple-toolset-messages"></div>
				<span class="filter-doc-help">
				<?php echo sprintf(
					__( '%sLearn about filtering by taxonomy%s', 'wpv-views' ),
					'<a class="wpv-help-link" href="' . WPV_FILTER_BY_TAXONOMY_LINK . '" target="_blank">',
					' &raquo;</a>'
				); ?>
				</span>
			</div>
		<?php
			$li_content = ob_get_clean();
			WPV_Filter_Item::multiple_filter_list_item( 'taxonomy', 'posts', __( 'Taxonomy filter', 'wpv-views' ), $li_content );
		}
	}

	/**
	* wpv_get_list_item_ui_post_taxonomy
	*
	* Render taxonomy filter item content in the filters list
	*
	* @param $category
	* @param $cats_selected
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_get_list_item_ui_post_taxonomy( $category, $cats_selected, $view_settings = array() ) {
		global $WP_Views_fapi;
		$type = ( $category->name == 'category' ) ? 'post_category' : 'tax_input[' . $category->name . ']';
		$taxonomy = $category->name;
		$taxonomy_name = $category->label;

		if ( ! isset( $view_settings['view-query-mode'] ) ) {
			$view_settings['view-query-mode'] = 'normal';
		}

		if ( ! isset($view_settings['tax_' . $taxonomy . '_relationship'] ) ) {
			$view_settings['tax_' . $taxonomy . '_relationship'] = 'IN';
		}
		if (
			! isset( $view_settings['taxonomy-' . $taxonomy . '-attribute-url'] )
			|| empty( $view_settings['taxonomy-' . $taxonomy . '-attribute-url'] )
		) {
			$view_settings['taxonomy-' . $taxonomy . '-attribute-url'] = 'wpv' . preg_replace( "/[^a-z0-9]+/", "", $taxonomy );
		}
		if (
			isset( $view_settings['taxonomy-' . $taxonomy . '-attribute-url-format'] )
			&& is_array( $view_settings['taxonomy-' . $taxonomy . '-attribute-url-format'] )
		) {
			$view_settings['taxonomy-' . $taxonomy . '-attribute-url-format'] = $view_settings['taxonomy-' . $taxonomy . '-attribute-url-format'][0];
		}
		if ( ! isset( $view_settings['taxonomy-' . $taxonomy . '-attribute-url-format'] ) ) {
			$view_settings['taxonomy-' . $taxonomy . '-attribute-url-format'] = 'slug';
		}
		if ( ! isset( $view_settings['taxonomy-' . $taxonomy . '-framework'] ) ) {
			$view_settings['taxonomy-' . $taxonomy . '-framework'] = '';
		}

		ob_start();
		?>
			<div class="wpv-filter-multiple-element js-wpv-filter-multiple-element js-wpv-filter-taxonomy-multiple-element js-wpv-filter-row-taxonomy-<?php echo esc_attr( $taxonomy ); ?> js-wpv-filter-row-tax-<?php echo esc_attr( $type ); ?>" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>">
				<h4><?php echo $taxonomy_name; ?></h4>
				<span class="wpv-filter-multiple-element-delete">
					<button class="button button-secondary button-small js-filter-remove" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_filter_taxonomy_delete_nonce' );?>">
						<i class="icon-trash fa fa-trash"></i>&nbsp;<?php _e( 'Delete', 'wpv-views' ); ?>
					</button>
				</span>
				<div class="wpv-filter-multiple-element-options">
				<?php echo sprintf( __( '%s are:', 'wpv-views' ), $taxonomy_name ); ?>
					<select class="wpv_taxonomy_relationship js-wpv-taxonomy-relationship js-wpv-filter-validate" name="tax_<?php echo esc_attr( $taxonomy ); ?>_relationship" data-type="select" autocomplete="off">
						<?php
						if (
							! $WP_Views_fapi->framework_valid
							&& $view_settings['tax_' . $taxonomy . '_relationship'] == 'framework'
						) {
						?>
						<option value=""><?php _e( 'Select one option...', 'wpv-views' ); ?></option>
						<?php
						} ?>
						<option value="IN" <?php selected( $view_settings['tax_' . $taxonomy . '_relationship'], 'IN' ); ?>><?php _e('Any of the following', 'wpv-views'); ?></option>
						<option value="NOT IN" <?php selected( $view_settings['tax_' . $taxonomy . '_relationship'], 'NOT IN' ); ?>><?php _e('No one of the following', 'wpv-views'); ?></option>
						<option value="AND" <?php selected( $view_settings['tax_' . $taxonomy . '_relationship'], 'AND' ); ?>><?php _e('All of the following', 'wpv-views'); ?></option>
						<?php
						if ( $view_settings['view-query-mode'] == 'normal' ) {
						?>
						<option value="top_current_post" <?php selected( $view_settings['tax_' . $taxonomy . '_relationship'], 'top_current_post' ); ?>><?php _e('Set by the page where this View is shown', 'wpv-views'); ?></option>
						<?php
						}
						if ( $view_settings['view-query-mode'] == 'normal' ) {
						?>
						<option value="current_post_or_parent_post_view" <?php selected( in_array( $view_settings['tax_' . $taxonomy . '_relationship'], array( 'FROM PAGE', 'current_post_or_parent_post_view' ) ) ); ?>><?php _e('Set by the current post in the loop', 'wpv-views'); ?></option>
						<?php
						}
						if ( $view_settings['view-query-mode'] == 'normal' ) {
						?>
						<option value="FROM ARCHIVE" <?php selected( $view_settings['tax_' . $taxonomy . '_relationship'], 'FROM ARCHIVE' ); ?>><?php _e('Set by the current archive page', 'wpv-views'); ?></option>
						<?php
						}
						if ( $view_settings['view-query-mode'] == 'normal' ) {
						?>
						<option value="current_taxonomy_view" <?php selected( in_array( $view_settings['tax_' . $taxonomy . '_relationship'], array( 'FROM PARENT VIEW', 'current_taxonomy_view' ) ) ); ?>><?php _e('Set by the parent Taxonomy View', 'wpv-views'); ?></option>
						<?php
						}
						if ( $view_settings['view-query-mode'] == 'normal' ) {
						?>
						<option value="FROM ATTRIBUTE" <?php selected( $view_settings['tax_' . $taxonomy . '_relationship'], 'FROM ATTRIBUTE' ); ?>><?php _e('Set by one View shortcode attribute', 'wpv-views'); ?></option>
						<?php
						}
						?>
						<option value="FROM URL" <?php selected( $view_settings['tax_' . $taxonomy . '_relationship'], 'FROM URL' ); ?>><?php _e('Set by one URL parameter', 'wpv-views'); ?></option>
						<?php
						if ( $WP_Views_fapi->framework_valid ) {
							$framework_data = $WP_Views_fapi->framework_data
						?>
						<option value="framework" <?php selected( $view_settings['tax_' . $taxonomy . '_relationship'], 'framework' ); ?>><?php echo sprintf( __('Set by one %s key', 'wpv-views'), sanitize_text_field( $framework_data['name'] ) ); ?></option>
						<?php
						}
						?>
					</select>
					<?php
					$hidden = '';
					if ( ! in_array( $view_settings['tax_' . $taxonomy . '_relationship'], array( 'IN', 'NOT IN', 'AND' ) ) ) {
						$hidden = ' hidden';
					}
					?>
					<ul id="taxonomy-<?php echo esc_attr( $taxonomy ); ?>" class="wpv-mightlong-list wpv-filter-multiple-element-options-mode js-taxonomy-checklist<?php echo $hidden; ?>">
						<?php
						$my_walker = new WPV_Walker_Taxonomy_Checkboxes_Flat();
						wp_terms_checklist( 0, array( 'taxonomy' => $taxonomy, 'selected_cats' => $cats_selected, 'walker' => $my_walker ) ) ?>
					</ul>
					<?php
					$hidden = '';
					if ( ! in_array( $view_settings['tax_' . $taxonomy . '_relationship'], array( 'FROM ATTRIBUTE', 'FROM URL' ) ) ) {
						$hidden = ' hidden';
					}
					?>
					<div id="taxonomy-<?php echo esc_attr( $taxonomy ); ?>-attribute-url" class="wpv-filter-multiple-element-options-mode js-taxonomy-parameter<?php echo $hidden; ?>">
						<span class="wpv-combo">
						<?php echo __( 'The', 'wpv-views' ); ?>
						<select name="taxonomy-<?php echo esc_attr( $taxonomy ); ?>-attribute-url-format[]">
							<option value="slug" <?php selected( $view_settings['taxonomy-' . $taxonomy . '-attribute-url-format'], 'slug' ); ?>><?php echo sprintf( __( '%s slug', 'wpv-views' ), $taxonomy );?></option>
							<option value="name" <?php selected( $view_settings['taxonomy-' . $taxonomy . '-attribute-url-format'], 'name' ); ?>><?php echo sprintf( __( '%s name', 'wpv-views' ), $taxonomy );?></option>
						</select>
						</span>
						<span class="wpv-combo">
						<?php echo __( 'is', 'wpv-views' ); ?>
						<?php
							if ( ! isset( $view_settings['taxonomy-' . $taxonomy . '-attribute-operator'] ) ) {
								$view_settings['taxonomy-' . $taxonomy . '-attribute-operator'] = 'IN';
							}
						?>
						<select name="taxonomy-<?php echo esc_attr( $taxonomy ); ?>-attribute-operator" id="taxonomy-<?php echo esc_attr( $taxonomy ); ?>-attribute-operator" class="js-taxonomy-<?php echo esc_attr( $taxonomy ); ?>-attribute-operator">
							<option value="IN" <?php echo selected( $view_settings['taxonomy-' . $taxonomy . '-attribute-operator'], 'IN' ); ?>><?php echo __('any of the values', 'wpv-views'); ?></option>
							<option value="NOT IN" <?php echo selected( $view_settings['taxonomy-' . $taxonomy . '-attribute-operator'], 'NOT IN' ); ?>><?php echo __('not one of the values', 'wpv-views'); ?></option>
							<option value="AND" <?php echo selected( $view_settings['taxonomy-' . $taxonomy . '-attribute-operator'], 'AND' ); ?>><?php echo __('all of the values', 'wpv-views'); ?></option>
						</select>
						</span>
						<?php echo __( ' coming from the ', 'wpv-views' ); ?>
						<span class="wpv-combo">
						<span class="js-taxonomy-param-label" data-attribute="<?php echo esc_attr( __( 'Shortcode attribute', 'wpv-views' ) );?>" data-parameter="<?php echo esc_attr( __( 'URL parameter', 'wpv-views' ) );?>"><?php echo ( $view_settings['tax_' . $taxonomy . '_relationship'] == 'FROM URL' ) ? __( 'URL parameter', 'wpv-views' ) : __( 'Shortcode attribute', 'wpv-views' );?></span>
						<input type="text" data-class="js-taxonomy-<?php echo esc_attr( $taxonomy ); ?>-param" data-type="url" class="wpv_taxonomy_param js-taxonomy-param js-taxonomy-<?php echo esc_attr( $taxonomy ); ?>-param js-wpv-filter-validate" name="taxonomy-<?php echo esc_attr( $taxonomy ); ?>-attribute-url" value="<?php echo esc_attr($view_settings['taxonomy-' . $taxonomy . '-attribute-url']); ?>" />
						</span>
					</div>
					<?php
					$hidden = '';
					if ( ! in_array( $view_settings['tax_' . $taxonomy . '_relationship'], array( 'framework' ) ) ) {
						$hidden = ' hidden';
					}
					?>
					<div id="taxonomy-<?php echo esc_attr( $taxonomy ); ?>-framework" class="wpv-filter-multiple-element-options-mode js-taxonomy-framework<?php echo $hidden; ?>">
					<?php if ( $WP_Views_fapi->framework_valid ) { ?>
						<label for="taxonomy-<?php echo esc_attr( $taxonomy ); ?>-framework-select"><?php _e( 'Use the Framework value for', 'wpv-views' ); ?></label>
						<select id="taxonomy-<?php echo esc_attr( $taxonomy ); ?>-framework-select" name="taxonomy-<?php echo esc_attr( $taxonomy ); ?>-framework" autocomplete="off">
							<option value=""><?php _e( 'Select a key', 'wpv-views' ); ?></option>
							<?php
							$fw_key_options = array();
							$fw_key_options = apply_filters( 'wpv_filter_extend_framework_options_for_category', $fw_key_options );
							foreach ( $fw_key_options as $index => $value ) {
								?>
								<option value="<?php echo esc_attr( $index ); ?>" <?php selected( $view_settings['taxonomy-' . $taxonomy . '-framework'], $index ); ?>><?php echo $value; ?></option>
								<?php
							}
							?>
						</select>
					<?php } else {
						$WP_Views_fapi->framework_missing_message_for_filters( false, false );
					} ?>
					</div>
				</div>
				<div class="js-wpv-filter-toolset-messages"></div>
			</div>
		<?php
		$buffer = ob_get_clean();
		$buffer = str_replace( 'tax_input[' . $category->name . ']', 'tax_input_' . $category->name, $buffer );
		return $buffer;
	}

	/**
	* wpv_filter_taxonomy_update_callback
	*
	* Update taxonomy filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_taxonomy_update_callback() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_filter_taxonomy_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["id"] )
			|| ! is_numeric( $_POST["id"] )
			|| intval( $_POST['id'] ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if ( empty( $_POST['filter_taxonomy'] ) ) {
			$data = array(
				'type' => 'data_missing',
				'message' => __( 'Wrong or missing data.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$change = false;
		$involved_taxonomies = array();
		$view_id = $_POST['id'];
		parse_str( $_POST['filter_taxonomy'], $filter_taxonomy );
		$view_array = get_post_meta( $view_id, '_wpv_settings', true );
		foreach ( $filter_taxonomy as $filter_key => $filter_data ) {
			if (
				strpos( $filter_key, 'tax_' ) === 0
				&& strpos( $filter_key, '_relationship' ) === strlen( $filter_key ) - strlen( '_relationship' )
			) {
				$tax_name = substr( $filter_key, 0, strlen( $filter_key ) - strlen( '_relationship' ) );
				$tax_name = substr( $tax_name, strlen( 'tax_' ) );
				$involved_taxonomies[] = $tax_name;
			}
			if (
				! isset( $view_array[$filter_key] )
				|| $filter_data != $view_array[$filter_key]
			) {
				if ( is_array( $filter_data ) ) {
					$filter_data = array_map( 'sanitize_text_field', $filter_data );
				} else {
					$filter_data = sanitize_text_field( $filter_data );
				}
				$change = true;
				$view_array[$filter_key] = $filter_data;
			}
		}
		foreach ( $involved_taxonomies as $involved_tax ) {
			if ( 'category' == $involved_tax ) {
				$needle_tax = 'post_category';
			} else {
				$needle_tax = 'tax_input_' . $involved_tax;
			}
			if ( ! isset( $filter_taxonomy[$needle_tax] ) ) {
				$view_array[$needle_tax] = array();
				$change = true;
			}
		}
		if ( $change ) {
			update_post_meta( $view_id, '_wpv_settings', $view_array );
			do_action( 'wpv_action_wpv_save_item', $view_id );
		}
		$summary = __( 'Select posts with taxonomy: ', 'wpv-views' );
		$result = '';
		// @todo maybe we can use here the $involved_taxonomies instead remove the $save_name construct repetition
		$taxonomies = get_taxonomies( '', 'objects' );
		foreach ( $taxonomies as $category_slug => $category ) {
			$relationship_name = ( $category->name == 'category' ) ? 'tax_category_relationship' : 'tax_' . $category->name . '_relationship';
			if ( isset( $view_array[$relationship_name] ) ) {
				$save_name = ( $category->name == 'category' ) ? 'post_category' : 'tax_input_' . $category->name;
				if ( ! isset( $view_array[$save_name] ) ) {
					$view_array[$save_name] = array();
				}
				$name = ( $category->name == 'category' ) ? 'post_category' : 'tax_input[' . $category->name . ']';
				if ( $result != '' ) {
					if ( $view_array['taxonomy_relationship'] == 'OR' ) {
						$result .= __( ' OR ', 'wpv-views' );
					} else {
						$result .= __( ' AND ', 'wpv-views' );
					}
				}
				$result .= wpv_get_taxonomy_summary( $name, $view_array, $view_array[$save_name] );
			}
		}
		$summary .= $result;

		$parametric_search_hints = wpv_get_parametric_search_hints_data( $view_id );

		$data = array(
			'id'			=> $view_id,
			'message'		=> __( 'Taxonomy filter saved', 'wpv-views' ),
			'summary'		=> $summary,
			'parametric'	=> $parametric_search_hints
		);
		wp_send_json_success( $data );
	}


	/*
	static function wpv_filter_taxonomy_sumary_update_callback() {
		parse_str($_POST['filter_taxonomy'], $view_settings);
		$summary = __('Select posts with taxonomy: ', 'wpv-views');
		$result = '';
		$taxonomies = get_taxonomies('', 'objects');
		foreach ($taxonomies as $category_slug => $category) {
			$save_name = ( $category->name == 'category' ) ? 'post_category' : 'tax_input_' . $category->name;
			$relationship_name = ( $category->name == 'category' ) ? 'tax_category_relationship' : 'tax_' . $category->name . '_relationship';

			if ( isset( $view_settings[$relationship_name] )) {

				if (!isset($view_settings[$save_name])) {
					$view_settings[$save_name] = array();
				}

				$name = ( $category->name == 'category' ) ? 'post_category' : 'tax_input[' . $category->name . ']';
				if ($result != '') {
					if ($view_settings['taxonomy_relationship'] == 'OR') {
						$result .= __(' OR ', 'wpv-views');
					} else {
						$result .= __(' AND ', 'wpv-views');
					}
				}

				$result .= wpv_get_taxonomy_summary($name, $view_settings, $view_settings[$save_name]);

			}
		}

		$summary .= $result;

		echo $summary;
		die();
	}
	*/

	/**
	* wpv_filter_taxonomy_delete_callback
	*
	* Delete taxonomy filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_taxonomy_delete_callback() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_filter_taxonomy_delete_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["id"] )
			|| ! is_numeric( $_POST["id"] )
			|| intval( $_POST['id'] ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		$taxonomies = is_array( $_POST['taxonomy'] ) ? $_POST['taxonomy'] : array( $_POST['taxonomy'] );
		foreach ( $taxonomies as $taxonomy ) {
			$to_delete = array(
				'tax_' . $taxonomy . '_relationship',
				'taxonomy-' . $taxonomy . '-attribute-url',
				'taxonomy-' . $taxonomy . '-attribute-url-format',
				'taxonomy-' . $taxonomy . '-attribute-operator',
				'taxonomy-' . $taxonomy . '-framework',
				// Backwards compatibility:
				// those entries existed in the View settings up until 2.4.0
				'filter_controls_field_name',
				'filter_controls_mode',
				'filter_controls_label',
				'filter_controls_type',
				'filter_controls_values',
				'filter_controls_enable',
				'filter_controls_param'
			);
			if ( 'category' == $taxonomy ) {
				$to_delete[] = 'post_category';
			} else {
				$to_delete[] = 'tax_input_' . $taxonomy;
			}
			foreach ( $to_delete as $index ) {
				if ( isset( $view_array[$index] ) ) {
					unset( $view_array[$index] );
				}
			}
		}
		update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		do_action( 'wpv_action_wpv_save_item', $_POST["id"] );

		$parametric_search_hints = wpv_get_parametric_search_hints_data( $_POST["id"] );

		$data = array(
			'id'			=> $_POST["id"],
			'parametric'	=> $parametric_search_hints,
			'message'		=> __( 'Taxonomy filter deleted', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

	/**
	 * Register the wpv-control-post-taxonomy shortcode on the custom search frontend filters.
	 *
	 * @since 2.4.0
	 */

	static function wpv_custom_search_filter_shortcodes_post_taxonomy( $shortcodes ) {
		$items = array();
		$taxonomies_valid = get_taxonomies( '', 'objects' );
		$exclude_tax_slugs = array();
		$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
		foreach ( $taxonomies_valid as $taxonomy_slug => $taxonomy_data ) {
			if ( in_array( $taxonomy_slug, $exclude_tax_slugs ) ) {
				continue;
			}
			if ( ! $taxonomy_data->show_ui ) {
				continue; // Only show taxonomies with show_ui set to TRUE
			}
			$items['post_taxonomy_' . $taxonomy_data->name] = array(
				'name'			=> $taxonomy_data->label,
				'present'		=> 'tax_' . $taxonomy_data->name . '_relationship',
				'params'		=> array(
					'attributes'	=> array(
						'taxonomy'	=> $taxonomy_data->name
					)
				)
			);
		}
		if ( count( $items ) > 0 ) {

			$shortcodes['wpv-control-post-taxonomy'] = array(
				'query_type_target'				=> 'posts',
				'query_filter_define_callback'	=> array( 'WPV_Taxonomy_Filter', 'query_filter_define_callback' ),
				'custom_search_filter_group'	=> __( 'Taxonomy filters', 'wpv-views' ),
				'custom_search_filter_items'	=> $items
			);

		}
		return $shortcodes;
	}

	/**
	 * Callback to create or modify the query filter after creating or editing the custom search shortcode.
	 *
	 * @param $view_id		int		The View ID
	 * @param $shortcode	string	The affected shortcode, wpv-control-post-taxonomy
	 * @param $attributes	array	The associative array of attributes for this shortcode
	 * @param $attributes_raw array	The associative array of attributes for this shortcode, as collected from its dialog, before being filtered
	 *
	 * @uses wpv_action_wpv_save_item
	 *
	 * @since 2.4.0
	 *
	 * @todo the operator defaults to IN but on previous versions we had a setting for it when defining the shortcode...
	 */

	static function query_filter_define_callback( $view_id, $shortcode, $attributes, $attributes_raw ) {
		if ( ! isset( $attributes['url_param'] ) ) {
			return;
		}
		$view_array = get_post_meta( $view_id, '_wpv_settings', true );
		$view_array['tax_' . $attributes['taxonomy'] . '_relationship'] = 'FROM URL';
		$view_array['taxonomy-' . $attributes['taxonomy'] . '-attribute-url'] = $attributes['url_param'];
		$view_array['taxonomy-' . $attributes['taxonomy'] . '-attribute-url-format'] = array( 'slug' );
		$view_array['taxonomy-' . $attributes['taxonomy'] . '-attribute-operator'] = isset( $attributes_raw['value_compare'] ) ? $attributes_raw['value_compare'] : 'IN';//$attributes['url_param'];
		$result = update_post_meta( $view_id, '_wpv_settings', $view_array );
		do_action( 'wpv_action_wpv_save_item', $view_id );
	}

}


function wpv_taxonomy_get_url_params( $view_settings ) {
	$results = array();
	$taxonomies = get_taxonomies( '', 'objects' );
	foreach ( $taxonomies as $category_slug => $category ) {
		$save_name = ( $category->name == 'category' ) ? 'post_category' : 'tax_input_' . $category->name;
		$relationship_name = ( $category->name == 'category' ) ? 'tax_category_relationship' : 'tax_' . $category->name . '_relationship';
		if ( isset( $view_settings[$relationship_name] ) && $view_settings[$relationship_name] == 'FROM URL' ) {
			$url_parameter = $view_settings['taxonomy-' . $category->name . '-attribute-url'];
			$results[] = array(
				'name' => $category->name,
				'param' => $url_parameter,
				'mode' => 'tax',
				'cat' => $category
			);
		}
	}
	return $results;
}

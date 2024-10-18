<?php

/**
 * GUI component of the filter by post relationship.
 *
 * This manages the backend GUI for setting the query filter for Views and WPAs.
 *
 * @since m2m
 */
class WPV_Filter_Post_Relationship_Gui {

	const SCRIPT_BACKEND = 'wpv-filter-post-relationship';

	/**
	 * @var WPV_Filter_Base
	 */
	private $filter = null;

	function __construct( WPV_Filter_Base $filter ) {
		$this->filter = $filter;

		if ( $this->filter->is_types_installed() ) {
			add_action( 'admin_init', array( $this, 'register_assets' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ), 20 );

			add_action( 'init', array( $this, 'load_hooks' ) );

			add_action( 'admin_footer', array( $this, 'render_footer_templates' ) );
		}
	}

	/**
	 * Get the filter backend script localization data.
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	private function get_scipt_backend_i18n() {

		$toolset_ajax = Toolset_Ajax::get_instance();
		$wpv_ajax = WPV_Ajax::get_instance();

		$wpv_post_relationship_filter_i18n = array(
			'is_enabled_m2m' => $this->filter->check_and_init_m2m(),
			'ajaxaction' => array(
				'get_relationships_data' => array(
					'action' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_GET_RELATIONSHIPS_DATA ),
					'nonce' => wp_create_nonce( WPV_Ajax::CALLBACK_GET_RELATIONSHIPS_DATA )
				),
				'select2_suggest_posts_by_title' => array(
					'action' => $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE ),
					'nonce' => wp_create_nonce( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE )
				),
				'filter_relationship_action' => array(
					'action' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_FILTER_RELATIONSHIP ),
					'nonce' => wp_create_nonce( WPV_Ajax::LEGACY_VIEW_QUERY_TYPE_NONCE )
				)
			),
			'messages' => array(
				'no_further_ancestors' => __( 'There are no further ancestors defined', 'wpv-views' ),
				'post_missing' => __( 'There is no post type selected in the Content Selection section', 'wpv-views' ),
				'post_not_related_legacy' => __( 'This will filter out posts of the following types, because they are not children of any other post type: %s', 'wpv-views' ),
				'post_not_related' => __( 'This will filter out posts of the following types, because they are not related to any other post type: %s', 'wpv-views' ),
				'no_post_type_found' => __( 'No post type found', 'wpv-views' ),
				'select_a_post_type' => __( 'Select a post type', 'wpv-views' ),
				'select_one' => __( 'Select one', 'wpv-views' )
			),
			'data' => array(
				'post_types_info' => array()
			)
		);

		$registered_post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $registered_post_types as $registered_type_slug => $registered_type_data ) {
			$wpv_post_relationship_filter_i18n['data']['post_types_info'][ $registered_type_slug ] = array(
				'label' => $registered_type_data->label,
				'labelSingular' => $registered_type_data->labels->name,
				'type' => $registered_type_slug
			);
		};

		if ( $this->filter->check_and_init_m2m() ) {
			do_action( 'toolset_do_m2m_full_init' );
			$rfg_post_types = get_post_types( array( Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP => true ), 'objects' );
			foreach ( $rfg_post_types as $rfg_type_slug => $rfg_type_data ) {
				$wpv_post_relationship_filter_i18n['data']['post_types_info'][ $rfg_type_slug ] = array(
					'label' => $rfg_type_data->label,
					'labelSingular' => $rfg_type_data->labels->name,
					'type' => $rfg_type_slug
				);
			}
		}

		return $wpv_post_relationship_filter_i18n;
	}

	/**
	 * Register the filter backend assets.
	 *
	 * @since m2m
	 */
	public function register_assets() {
		$assets_manager = Toolset_Assets_Manager::get_instance();

		$assets_manager->register_script(
			self::SCRIPT_BACKEND,
			WPV_URL . "/public/js/admin/filters/post_relationship.js",
			array( 'views-filters-js', Toolset_Assets_Manager::SCRIPT_SELECT2, 'underscore' ),
			WPV_VERSION,
			false
		);

		$assets_manager->localize_script(
			self::SCRIPT_BACKEND,
			'wpv_post_relationship_filter_i18n',
			$this->get_scipt_backend_i18n()
		);
	}

	/**
	 * Enqueue the filter backend assets.
	 *
	 * @since m2m
	 */
	public function enqueue_assets() {
		if (
			'views-editor' === toolset_getget( 'page' )
			|| 'view-archives-editor' === toolset_getget( 'page' )
		) {
			do_action( 'toolset_enqueue_scripts', array( self::SCRIPT_BACKEND ) );
		}
	}

	/**
	 * Make sure this script is available on the blocks editor, for the Views block.
	 *
	 * @since 2.9
	 */
	public function enqueue_block_editor_assets() {
		do_action( 'toolset_enqueue_scripts', array( self::SCRIPT_BACKEND ) );
	}

	/**
	 * Load the hooks to register the filter GUI.
	 *
	 * @since m2m
	 */
	public function load_hooks() {
		// Register filter in filter dialogs
		add_filter( 'wpv_filters_add_filter', array( $this, 'add_filter' ), 1, 2 );
		add_filter( 'wpv_filters_add_archive_filter', array( $this, 'add_archive_filter' ), 1, 1 );
		// Register filter in filter lists
		add_action( 'wpv_add_filter_list_item', array( $this, 'add_filter_list_item' ), 1, 1 );
		// Include the filter in the Views shortcodes GUI
		add_filter( 'wpv_filter_register_shortcode_attributes_for_posts', array( $this, 'shortcode_attributes' ), 10, 2 );
		add_filter( 'wpv_filter_register_url_parameters_for_posts', array( $this, 'url_parameters' ), 10, 2 );
	}

	/**
	 * Register the post relationship filter in the popup dialog.
	 *
	 * @param array $filters Registered filters
	 * @param array $post_type Returned post types by this loop
	 *
	 * @return array
	 *
	 * @since unknown
	 */
	public function add_filter( $filters, $post_type ) {
		$filters['post_relationship'] = array(
			'name'		=> $this->filter->check_and_init_m2m()
				? __( 'Post relationship or repeatable field groups owner', 'wpv-views' )
				: __( 'Post relationship - Post is a child of', 'wpv-views' ),
			'present'	=> 'post_relationship_mode',
			'callback'	=> array( $this, 'add_new_filter_list_item' ),
			'args'		=> $post_type,
			'group'		=> __( 'Post filters', 'wpv-views' )
		);
		return $filters;
	}

	/**
	 * Register the post relationship filter in the filters list.
	 *
	 * @param array $post_type Returned post types by this loop
	 *
	 * @since unknown
	 */
	public function add_new_filter_list_item( $post_type ) {
		$args = array(
			'view-query-mode'			=> 'normal',
			'post_relationship_mode'	=> array( 'top_current_post' ),
			'post_type'					=> $post_type
		);
		$this->add_filter_list_item( $args );
	}

	/**
	 * Register the post relationship filter in the popup dialog for WPAs.
	 *
	 * @param array $filters Registered filters
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	public function add_archive_filter( $filters ) {
		$filters['post_relationship'] = array(
			'name'		=> $this->filter->check_and_init_m2m()
				? __( 'Post relationship', 'wpv-views' )
				: __( 'Post relationship - Post is a child of', 'wpv-views' ),
			'present'	=> 'post_relationship_mode',
			'callback'	=> array( $this, 'add_new_archive_filter_list_item' ),
			'group'		=> __( 'Post filters', 'wpv-views' )
		);
		return $filters;
	}

	/**
	* Register the post relationship filter in the filters list,
	*
	* @since 2.1
	*/
	public function add_new_archive_filter_list_item() {
		$args = array(
			'view-query-mode'			=> 'archive',
			'post_relationship_mode'	=> array( 'this_page' ),
		);
		$this->add_filter_list_item( $args );
	}

	/**
	 * Render post relationship filter item in the filters list.
	 *
	 * @param array $view_settings
	 *
	 * @since unknown
	 */
	public function add_filter_list_item( $view_settings ) {
		if (
			$this->filter->is_types_installed()
			&& isset( $view_settings['post_relationship_mode'][0] )
		) {
			$filter_title =  ( 'normal' === toolset_getarr( $view_settings, 'view-query-mode', 'normal' ) )
				? __( 'Filter by post relationship or repeatable fields group owner', 'wpv-views' )
				: __( 'Post relationship filter', 'wpv-views' );

			$filter_list_item_ui = $this->get_filter_ui( $view_settings );
			WPV_Filter_Item::simple_filter_list_item(
				'post_relationship',
				'posts',
				'post-relationship',
				$filter_title,
				$filter_list_item_ui
			);
		}
	}

	/**
	 * Set default settings for th filter GUI.
	 *
	 * @param array $view_settings Passed by reference
	 *
	 * @since m2m
	 */
	private function set_filter_ui_defaults( &$view_settings ) {
		if (
			isset( $view_settings['post_relationship_mode'] )
			&& is_array( $view_settings['post_relationship_mode'] )
		) {
			$view_settings['post_relationship_mode'] = $view_settings['post_relationship_mode'][0];
		}
		if (
			isset( $view_settings['post_relationship_id'] )
			&& ! empty( $view_settings['post_relationship_id'] )
		) {
			// Adjust for WPML support
			$view_settings['post_relationship_id'] = apply_filters(
				'translate_object_id',
				$view_settings['post_relationship_id'],
				'any',
				true,
				null
			);
		}
		if ( ! isset( $view_settings['post_type'] ) ) {
			$view_settings['post_type'] = array();
		}
	}

	/**
	 * Get the filter GUI.
	 *
	 * @param array $view_settings
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	private function get_filter_ui( $view_settings ) {
		$this->set_filter_ui_defaults( $view_settings );
		$views_ajax = WPV_Ajax::get_instance();
		ob_start()
		?>
		<p class="wpv-filter-<?php echo WPV_Filter_Post_Relationship::SLUG; ?>-edit-summary js-wpv-filter-summary js-wpv-filter-<?php echo WPV_Filter_Post_Relationship::SLUG; ?>-summary">
			<?php echo $this->get_filter_summary( $view_settings ); ?>
		</p>
		<?php
		// If the Relationship Query filter is auto-generated, then the rendering of the filter's control ("Edit" & "Delete" buttons) needs to be skipped.
		if ( \WPV_Filter_Manager::FILTER_MODE_FULL === toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, \WPV_Filter_Post_Relationship::SLUG, \WPV_Filter_Manager::EDITOR_MODE ), \WPV_Filter_Manager::FILTER_MODE_FULL ) ) {
			WPV_Filter_Item::simple_filter_list_item_buttons(
				WPV_Filter_Post_Relationship::SLUG,
				$views_ajax->get_action_js_name( WPV_Ajax::CALLBACK_FILTER_POST_RELATIONSHIP_UPDATE ),
				wp_create_nonce( WPV_Ajax_Handler_Filter_Post_Relationship_Update::NONCE ),
				$views_ajax->get_action_js_name( WPV_Ajax::CALLBACK_FILTER_POST_RELATIONSHIP_DELETE ),
				wp_create_nonce( WPV_Ajax_Handler_Filter_Post_Relationship_Delete::NONCE )
			);
		}
		?>
		<div id="wpv-filter-<?php echo WPV_Filter_Post_Relationship::SLUG; ?>-edit"
			class="wpv-filter-edit js-wpv-filter-edit"
			style="padding-bottom:28px;">
			<div id="wpv-filter-<?php echo WPV_Filter_Post_Relationship::SLUG; ?>"
				class="js-wpv-filter-options js-wpv-filter-<?php echo WPV_Filter_Post_Relationship::SLUG; ?>-options">
				<?php $this->render_filter_options( $view_settings ); ?>
			</div>
			<div class="js-wpv-filter-toolset-messages"></div>
			<span class="filter-doc-help">
				<a class="wpv-help-link" target="_blank" href="https://toolset.com/course-lesson/displaying-related-posts/?utm_source=plugin&utm_medium=gui&utm_campaign=blocks">
					<?php _e('Querying and Displaying Child Posts', 'wpv-views'); ?>
				 &raquo;</a>
			</span>
		</div>
		<?php
		$res = ob_get_clean();
		return $res;
	}

	/**
	 * Render post relationship filter options
	 *
	 * @param $view_settings
	 *
	 * @since unknown
	 */
	private function render_filter_options( $view_settings = array() ) {
		$defaults = array(
			'view-query-mode'						=> 'normal',
			'post_relationship_mode'				=> 'top_current_post',
			'post_relationship_id'					=> 0,
			'post_relationship_shortcode_attribute'	=> 'wpvrelatedto',
			'post_relationship_url_parameter'		=> 'wpv-relationship-filter',
			'post_relationship_framework'			=> ''
		);
		$view_settings = wp_parse_args( $view_settings, $defaults );
		$relationship_slug = isset( $view_settings['post_relationship_slug'] )
			? $view_settings['post_relationship_slug']
			: '';

		?>
		<h4><?php
			if ( $this->filter->check_and_init_m2m() ) {
				$returned_post_types = $this->filter->get_returned_post_types( $view_settings );
				$relationship_role = isset( $view_settings['post_relationship_role'] )
					? $view_settings['post_relationship_role']
					: '';
				echo sprintf(
					// translators: Relationship type combo and role type combo
					__( 'Select items %s as related %s of...', 'wpv-views' ),
					$this->get_relationships_combo_by_post_type( $returned_post_types, $relationship_slug ),
					$this->get_role_types( $relationship_role, $relationship_slug )
				);
			} else {
				_e( 'Select posts that are children of...', 'wpv-views' );
			} ?></h4>
		<ul class="wpv-filter-options-set">
			<?php
			$options_to_render = $this->get_options_by_query_mode( $view_settings['view-query-mode'] );
			foreach ( $options_to_render as $renderer ) {
				$this->render_option( $renderer, $view_settings );
			}
			?>
		</ul>
		<?php
	}

	/**
	 * Check whether a relationship is relevant for the current loop.
	 *
	 * @since m2m
	 */
	private function is_relationship_relevant( $returned_post_types, $relationship_definition_types ) {
		if ( empty( $returned_post_types ) ) {
			return true;
		}
		$matching_post_types = array_intersect( $returned_post_types, $relationship_definition_types );
		if ( count( $matching_post_types ) > 0 ) {
			return true;
		}
		return false;
	}

	/**
	 * Produce a combo with the relationships with the belongings belonging post types
	 *
	 * @param array $post_types A list of post types
	 * @param string $relationship_slug The selected relationship slug
	 *
	 * @return string The rendered HTML
	 *
	 * @since m2m
	 */
	public function get_relationships_combo_by_post_type( $post_types, $relationship_slug = null ) {
		do_action( 'toolset_do_m2m_full_init' );
		$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
		$definitions = $relationship_repository->get_definitions();

		$html = '<select name="post_relationship_slug" %s autocomplete="off" >';
		$options = '';
		foreach( $definitions as $definition ) {
			$parent_type = $definition->get_parent_type()->get_types();
			$child_type = $definition->get_child_type()->get_types();
			$intermediary_type = $definition->get_intermediary_post_type();
			if ( null === $intermediary_type ) {
				$intermediary_type= array();
			} else {
				$intermediary_type = array( $intermediary_type );
			}

			$definition_types = array_merge( $parent_type, $child_type, $intermediary_type );
			$definition_types = array_values( $definition_types );

			if ( ! $this->is_relationship_relevant( $post_types, $definition_types) ) {
				continue;
			}

			$relationship_type = '';
			$cardinality = $definition->get_cardinality();
			if ( $cardinality->is_many_to_many() ) {
				$relationship_type = 'many-to-many';
			} elseif ( $cardinality->is_many_to_one() || $cardinality->is_one_to_many() ) {
				$relationship_type = 'one-to-many';
			} else {
				$relationship_type = 'one-to-one';
			}

			$relationship_origin = $definition->get_origin()->get_origin_keyword();

			$options .= '<option value="'
				. esc_attr( $definition->get_slug() ). '"'
				. ' ' . ( selected( $definition->get_slug(), $relationship_slug, false ) )
				// This data will be used for displaying or not the role select.
				. ' data-relationship-type="' . esc_attr( $relationship_type ) . '" '
				. ' data-relationship-parent="' . esc_attr( json_encode( $parent_type ) ) . '"'
				. ' data-relationship-child="' . esc_attr( json_encode( $child_type ) ) . '"'
				. ' data-relationship-intermediary="' . esc_attr( json_encode( $intermediary_type ) ). '"'
				. '>';

			$option_label = sprintf(
				__( 'in the %s relationship', 'wpv-views' ),
				$definition->get_display_name()
			);
			if ( Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD === $relationship_origin ) {
				$child_post_type_object = get_post_type_object( $child_type[0] );
				if ( null != $child_post_type_object ) {
					$option_label = sprintf(
						__( 'from %s groups', 'wpv-views' ),
						$child_post_type_object->labels->name
					);
				}
			}

			$options .= $option_label;
			$options .= '</option>';
		}
		if ( ! $options ) {
			$options = '<option value="" data-relationship-type="" data-relationship-parent="" data-relationship-child="">' . __( '-- no relationships found --', 'wpv-views' ) . '</option>';
			$html = sprintf( $html, 'disabled="disabled"' );
		} else {
			$options = '<option value="" data-relationship-type="" data-relationship-parent="" data-relationship-child="">' . __( 'in any relationship', 'wpv-views') . '</option>' . $options;
			$html = sprintf( $html, '' );
		}
		$html .= $options . '</select>';
		return $html;
	}

	/**
	 * Render a combo with the relationships with the belongings belonging post types
	 *
	 * @param array $post_types A list of post types
	 * @param string $relationship_slug The selected relationship slug
	 *
	 * @since m2m
	 */
	public function render_relationships_combo_by_post_type( $post_types, $relationship_slug = null ) {
		echo $this->get_relationships_combo_by_post_type( $post_types, $relationship_slug );
	}

	/**
	 * Produce a combo with the role types. Also a string for when filtering by any relationship.
	 *
	 * @param String $relationship_role The relationship role
	 * @param String $relationship_slug The relationship slug
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	private function get_role_types( $relationship_role, $relationship_slug ) {
		$html = '<span class="js-wpv-post-relationship-role-any"' . ( '' == $relationship_slug ? '' : ' style="display:none"' ) . '>' . __( 'items', 'wpv-views' ) . '</span>';
		$html .= '<select name="post_relationship_role" autocomplete="off"' . ( '' == $relationship_slug ? ' style="display:none"' : '' ) . '>';
		$roles = array(
			array( Toolset_Relationship_Role::CHILD, __( '%%child%%', 'wpv-views' ) ),
			array( Toolset_Relationship_Role::PARENT, __( '%%parent%%', 'wpv-views' ) ),
			array( Toolset_Relationship_Role::INTERMEDIARY, __( '%%intermediary%%', 'wpv-views' ) )
		);
		foreach ( $roles as $role_data ) {
			$selected = selected( $relationship_role === $role_data[0], true, false );
			$html .= '<option value="' . esc_attr( $role_data[0] ) . '" ' . $selected . ' data-label="' . esc_attr( $role_data[1] ) . '">' . esc_html( $role_data[1] ) . '</option>';
		}
		$html .= '</select>';
		return $html;
	}

	/**
	 * Render a combo with the role types. Also a string for when filtering by any relationship.
	 *
	 * @param String $relationship_role The relationship role
	 * @param String $relationship_slug The relationship slug
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	private function render_role_types( $relationship_role, $relationship_slug ) {
		echo $this->get_role_types( $relationship_role, $relationship_slug );
	}

	/**
	 * Define which options will be offered depending on the query mode.
	 *
	 * @param string $query_mode
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	private function get_options_by_query_mode( $query_mode = 'normal' ) {
		$options = array();
		if ( 'normal' == $query_mode ) {
			$options = array( 'top_current_post', 'current_post_or_parent_post_view', 'this_page', 'shortcode_attribute', 'url_parameter', 'framework' );
		} else {
			$options = array( 'this_page', 'url_parameter', 'framework' );
		}
		return $options;
	}

	/**
	 * Render each filter option.
	 *
	 * @param string $parent_mode
	 * @param array $view_settings
	 *
	 * @since 2.1
	 */
	public function render_option( $post_relationship_option, $view_settings ) {
		$is_enabled_m2m = apply_filters( 'toolset_is_m2m_enabled', false );
		switch ( $post_relationship_option ) {
			case 'top_current_post':
				?>
				<li>
					<input type="radio" id="post-relationship-mode-current-page" class="js-post-relationship-mode" name="post_relationship_mode[]" value="top_current_post" <?php checked( in_array( $view_settings['post_relationship_mode'], array( 'current_page', 'top_current_post' ) ) ); ?> autocomplete="off" />
					<label for="post-relationship-mode-current-page"><?php _e('The post where this View is shown', 'wpv-views'); ?></label>
				</li>
				<?php
				break;
			case 'current_post_or_parent_post_view':
				?>
				<li>
					<input type="radio" id="post-relationship-mode-parent-view" class="js-post-relationship-mode" name="post_relationship_mode[]" value="current_post_or_parent_post_view" <?php checked( in_array( $view_settings['post_relationship_mode'], array( 'parent_view', 'current_post_or_parent_post_view' ) ) ); ?> autocomplete="off" />
					<label for="post-relationship-mode-parent-view"><?php _e('The current post in the loop', 'wpv-views'); ?></label>
				</li>
				<?php
				break;
			case 'this_page':
				if ( $is_enabled_m2m ) {
				?>
				<li>
					<input type="radio" id="post-relationship-mode-this-page" class="js-post-relationship-mode" name="post_relationship_mode[]" value="this_page" <?php checked( $view_settings['post_relationship_mode'], 'this_page' ); ?> autocomplete="off" />
					<label for="post-relationship-mode-this-page"><?php _e('Specific:', 'wpv-views'); ?></label>
					<?php $this->render_post_relationship_mode_this_page( $view_settings ); ?>
				</li>
				<?php
				} else {
				?>
					<input type="radio" id="post-relationship-mode-this-page" class="js-post-relationship-mode" name="post_relationship_mode[]" value="this_page" <?php checked( $view_settings['post_relationship_mode'], 'this_page' ); ?> autocomplete="off" />
					<label for="post-relationship-mode-this-page"><?php _e('Specific:', 'wpv-views'); ?></label>
					<select id="wpv_post_relationship_post_type" name="post_relationship_type" class="js-post-relationship-post-type" data-nonce="<?php echo wp_create_nonce( 'wpv_view_filter_post_relationship_post_type_nonce' ); ?>" autocomplete="off">
					<?php
					$post_types = get_post_types( array( 'public' => true ), 'objects' );
					if (
						$view_settings['post_relationship_id'] == 0
						|| $view_settings['post_relationship_id'] == ''
					) {
						$selected_type = 'page';
					} else {
						global $wpdb;
						$selected_type = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT post_type FROM {$wpdb->posts}
								WHERE ID = %d
								LIMIT 1",
								$view_settings['post_relationship_id']
							)
						);
						if ( ! $selected_type ) {
							$selected_type = 'page';
						}
					}
					foreach ( $post_types as $post_type ) {
						?>
						<option value="<?php echo esc_attr( $post_type->name ); ?>" <?php selected( $selected_type, $post_type->name ); ?>><?php echo $post_type->labels->singular_name; ?></option>
						<?php
					}
					?>
					</select>
					<?php
					$dropdown_args = array(
						'post_type'		=> $selected_type,
						'name'			=> 'post_relationship_id',
						'selected'		=> (int) $view_settings['post_relationship_id']
					);
					wpv_render_posts_select_dropdown( $dropdown_args );
				}
				break;
			case 'shortcode_attribute':
				?>
				<li>
					<input type="radio" id="post-relationship-mode-shortcode" class="js-post-relationship-mode" name="post_relationship_mode[]" value="shortcode_attribute" <?php checked( $view_settings['post_relationship_mode'], 'shortcode_attribute' ); ?> autocomplete="off" />
					<label for="post-relationship-mode-shortcode"><?php _e('The post with ID set by the shortcode attribute', 'wpv-views'); ?></label>
					<input class="js-post-relationship-shortcode-attribute js-wpv-filter-validate" name="post_relationship_shortcode_attribute" data-type="shortcode" type="text" value="<?php echo esc_attr( $view_settings['post_relationship_shortcode_attribute'] ); ?>" autocomplete="off" />
				</li>
				<?php
				break;
			case 'url_parameter':
				?>
				<li>
					<input type="radio" id="post-relationship-mode-url" class="js-post-relationship-mode" name="post_relationship_mode[]" value="url_parameter" <?php checked( $view_settings['post_relationship_mode'], 'url_parameter' ); ?> autocomplete="off" />
					<label for="post-relationship-mode-url"><?php _e('The post with ID set by the URL parameter', 'wpv-views'); ?></label>
					<input class="js-post-relationship-url-parameter js-wpv-filter-validate" name="post_relationship_url_parameter" data-type="url" type="text" value="<?php echo esc_attr( $view_settings['post_relationship_url_parameter'] ); ?>" autocomplete="off" />
				</li>
				<?php
				break;
			case 'framework':
				global $WP_Views_fapi;
				if ( $WP_Views_fapi->framework_valid ) {
					$framework_data = $WP_Views_fapi->framework_data
				?>
				<li>
					<input type="radio" id="post-relationship-mode-framework" class="js-post-relationship-mode" name="post_relationship_mode[]" value="framework" <?php checked( $view_settings['post_relationship_mode'], 'framework' ); ?> autocomplete="off" />
					<label for="post-relationship-mode-framework"><?php echo sprintf( __( 'Post with ID set by the %s key: ', 'wpv-views'), sanitize_text_field( $framework_data['name'] ) ); ?></label>
					<select name="post_relationship_framework" autocomplete="off">
						<option value=""><?php _e( 'Select a key', 'wpv-views' ); ?></option>
						<?php
						$fw_key_options = array();
						$fw_key_options = apply_filters( 'wpv_filter_extend_framework_options_for_post_relationship', $fw_key_options );
						foreach ( $fw_key_options as $index => $value ) {
							?>
							<option value="<?php echo esc_attr( $index ); ?>" <?php selected( $view_settings['post_relationship_framework'], $index ); ?>><?php echo $value; ?></option>
							<?php
						}
						?>
					</select>
				</li>
				<?php
				}
				break;
		};
	}

	/**
	 * Render the select with the post types included in the relationships that have a post type in their parent or child elements
	 *
	 * @param array $view_settings A list of settings, the important one is 'post_type'
	 *
	 * @since m2m
	 */
	public function render_post_relationship_mode_this_page( $view_settings ) {
		do_action( 'toolset_do_m2m_full_init' );
		$html = '<select id="wpv_post_relationship_post_type" name="post_relationship_type" class="js-post-relationship-post-type" data-nonce="' . wp_create_nonce( 'wpv_view_filter_post_relationship_post_type_nonce' ) . '" autocomplete="off" ';
		$post_types_selected = $this->filter->get_returned_post_types( $view_settings );
		$post_types = array();

		$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
		$definitions = array();
		if ( toolset_getarr( $view_settings, 'post_relationship_slug', '' ) ) {
			$definition = $relationship_repository->get_definition( $view_settings['post_relationship_slug'] );
			if ( $definition ) {
				$definitions[] = $definition;
			}
		} else {
			if ( is_array( $post_types_selected ) ) {
				$intermediary_post_types = $this->filter->get_intermediary_post_types();
				$relationship_query = new Toolset_Relationship_Query_V2();
				$conditions = array();
				foreach ( $post_types_selected as $selected_post_type ) {
					if ( in_array( $selected_post_type, $intermediary_post_types ) ) {
						$conditions[] = $relationship_query->intermediary_type( $selected_post_type );
					} else {
						$conditions[] = $relationship_query->has_domain_and_type( $selected_post_type, Toolset_Element_Domain::POSTS );
					}
				}
				if ( count( $conditions ) > 0 ) {
					$relationship_query->add( $relationship_query->do_or( $conditions ) );
				}
				$definitions = $relationship_query
					->get_results();
			}
		}

		foreach ( $definitions as $definition ) {
			$parent_types = $definition->get_parent_type()->get_types();
			$parent_types_selected = array_intersect( $parent_types, $post_types_selected );
			$child_types = $definition->get_child_type()->get_types();
			$child_types_selected = array_intersect( $child_types, $post_types_selected );
			$intermediary_type = $definition->get_intermediary_post_type();
			if (
				empty( $post_types_selected )
				|| count( $parent_types_selected ) > 0
				|| count( $child_types_selected ) > 0
				|| null != $intermediary_type
			) {
				if (
					! toolset_getarr( $view_settings, 'post_relationship_slug', '' )
					|| empty( $view_settings['post_relationship_role'] )
				) {
					$post_types = array_merge( $post_types, $parent_types );
					$post_types = array_merge( $post_types, $child_types );
					$post_types = array_values( $post_types );
					if ( null != $intermediary_type ) {
						$post_types[] = $intermediary_type;
					}
				} else {
					if ( Toolset_Relationship_Role::CHILD === $view_settings['post_relationship_role'] ) {
						$post_types = array_merge( $post_types, $parent_types );
						if ( null != $intermediary_type ) {
							$post_types[] = $intermediary_type;
						}
					}
					if ( Toolset_Relationship_Role::PARENT === $view_settings['post_relationship_role'] ) {
						$post_types = array_merge( $post_types, $child_types );
						if ( null != $intermediary_type ) {
							$post_types[] = $intermediary_type;
						}
					}
					if ( Toolset_Relationship_Role::INTERMEDIARY === $view_settings['post_relationship_role'] ) {
						$post_types = array_merge( $post_types, $parent_types );
						$post_types = array_merge( $post_types, $child_types );
					}
					$post_types = array_values( $post_types );
				}
			}
		}
		$post_types = array_unique( $post_types );

		$selected_type = '';
		if ( ! empty( $view_settings['post_relationship_id'] ) ) {
			global $wpdb;
			$selected_type = get_post_type( $view_settings['post_relationship_id'] );
		}

		if ( empty( $post_types ) ) {
			// Select disabled.
			$html .= ' disabled="disabled" >';
			$html .= '<option value="">' . esc_html__( 'No post type found', 'wpv-views' ) . '</option>';
		} else {
			// Select enabled.
			$html .= ' >';
			$html .= '<option value="">' . esc_html__( 'Select a post type', 'wpv-views' ) . '</option>';
			foreach ( $post_types as $post_type_slug ) {
				$post_type = get_post_type_object( $post_type_slug );
				if ( $post_type ) {
					$html .= '<option value="' . esc_attr( $post_type->name ) .'" ' . selected( $selected_type, $post_type->name, false ) . '>' . esc_html( $post_type->labels->singular_name ) . '</option>';
				}
			}
		}
		$html .= '</select>';

		if ( ! empty( $selected_type ) ) {
			$html .= '<select name="post_relationship_id" id="post_relationship_id" data-placeholder="' . esc_attr( __( 'Select one', 'wpv-views' ) ) . '">';
			if ( ! empty( $view_settings['post_relationship_id'] ) ) {
				$selected_post = get_post( $view_settings['post_relationship_id'] );
				if ( $selected_post ) {
					$html .= '<option value="' . $view_settings['post_relationship_id'] . '">' .esc_html( $selected_post->post_title ) . '</option>';
				}
			}
			$html .= '</select>';
		}

		echo $html;
	}

	/**
	 * Register the filter by post relationship on the method to get View shortcode attributes.
	 *
	 * @param array $attributes
	 * @param array $view_settings
	 *
	 * @return array
	 *
	 * @since 1.10
	 */
	public function shortcode_attributes( $attributes, $view_settings ) {
		if (
			isset( $view_settings['post_relationship_mode'] )
			&& isset( $view_settings['post_relationship_mode'][0] )
			&& $view_settings['post_relationship_mode'][0] == 'shortcode_attribute'
		) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_relationship',
				'filter_label'	=> __( 'Post relationship', 'wpv-views' ),
				'value'			=> 'ancestor_id',
				'attribute'		=> $view_settings['post_relationship_shortcode_attribute'],
				'expected'		=> 'number',
				'placeholder'	=> '103',
				'description'	=> $this->filter->check_and_init_m2m()
					? __( 'Please type a post ID to get its related posts', 'wpv-views' )
					: __( 'Please type a post ID to get its children', 'wpv-views' )
			);
		}
		return $attributes;
	}

	/**
	 * Register the filter by post relationship on the method to get View URL parameters.
	 *
	 * @param array $attributes
	 * @param array $view_settings
	 *
	 * @return array
	 *
	 * @since 1.11.0
	 * @since 2.3.0 Ensured that each ancestor gets a proper 'filter_type' key, since we then
	 *     wp_list_pluck by that key and having repeated values produced some unexpected issues. Also,
	 *     make sure that we do not get duplicates, since first-level parents should be covered by the
	 *     default $view_settings['post_relationship_url_parameter'] attribute.
	 */
	public function url_parameters( $attributes, $view_settings ) {
		if (
			isset( $view_settings['post_relationship_mode'] )
			&& isset( $view_settings['post_relationship_mode'][0] )
			&& $view_settings['post_relationship_mode'][0] == 'url_parameter'
		) {
			$attributes[] = array(
				'query_type'	=> 'posts',
				'filter_type'	=> 'post_relationship',
				'filter_label'	=> __( 'Post relationship', 'wpv-views' ),
				'value'			=> 'ancestor_id',
				'attribute'		=> $view_settings['post_relationship_url_parameter'],
				'expected'		=> 'number',
				'placeholder'	=> '103',
				'description'	=> __( 'Please type a post ID to get its children', 'wpv-views' )
			);

			if ( $this->filter->check_and_init_m2m() ) {
				// Bold solution: register all existing posts URL parameters as there might be wild relationships
				$existing_post_types = get_post_types( array( 'public' => true ) );
				foreach ( $existing_post_types as $existing_post_type ) {
					$attributes[] = array(
						'query_type'	=> 'posts',
						'filter_type'	=> 'post_relationship_' . $existing_post_type,
						'filter_label'	=> __( 'Post relationship', 'wpv-views' ),
						'value'			=> 'ancestor_id',
						'attribute'		=> $view_settings['post_relationship_url_parameter'] . '-' . $existing_post_type,
						'expected'		=> 'number',
						'placeholder'	=> '103',
						'description'	=> __( 'Please type a post ID to get its children', 'wpv-views' )
					);
				}
			} else {
				$returned_post_types = $this->filter->get_returned_post_types( $view_settings );
				$ancestor_post_types = array();
				if (
					! empty( $returned_post_types )
					&& function_exists( 'wpcf_pr_get_belongs' )
				) {
					$returned_post_types_parents = array();
					foreach ( $returned_post_types as $ground_post_type ) {
						$ground_post_type_parents = wpcf_pr_get_belongs( $ground_post_type );
						if (
							$ground_post_type_parents != false
							&& is_array( $ground_post_type_parents )
						) {
							$ground_post_type_parents = array_values( array_keys( $ground_post_type_parents ) );
							$returned_post_types_parents = array_merge( $returned_post_types_parents, $ground_post_type_parents );
						}
					}
					$returned_post_types_parents = array_unique( $returned_post_types_parents );
					$returned_post_types_parents = array_values( $returned_post_types_parents );
					if ( ! empty( $returned_post_types_parents ) ) {
						$ancestor_post_types = $this->filter->get_legacy_post_type_ancestors( $returned_post_types_parents );
					}
				}
				foreach ( $ancestor_post_types as $ancestor_slug ) {
					$attributes[] = array(
						'query_type'	=> 'posts',
						'filter_type'	=> 'post_relationship_' . $ancestor_slug,
						'filter_label'	=> __( 'Post relationship', 'wpv-views' ),
						'value'			=> 'ancestor_id',
						'attribute'		=> $view_settings['post_relationship_url_parameter'] . '-' . $ancestor_slug,
						'expected'		=> 'number',
						'placeholder'	=> '103',
						'description'	=> __( 'Please type a post ID to get its children', 'wpv-views' )
					);
				}
			}
		}
		return $attributes;
	}

	/**
	 * Render the footer templates needed for the filter.
	 *
	 * @since m2m
	 */
	public function render_footer_templates() {
		if (
			'views-editor' !== toolset_getget( 'page' )
			&& 'view-archives-editor' !== toolset_getget( 'page' )
		) {
			return;
		}

		$template_repository = WPV_Output_Template_Repository::get_instance();
		$renderer = Toolset_Renderer::get_instance();

		$renderer->render(
			$template_repository->get( WPV_Output_Template_Repository::ADMIN_FILTERS_POST_RELATIONSHIP_ANCESTOR_NODE ),
			null
		);
	}

	public function get_filter_summary( $view_settings ) {
		$editor_mode = toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, \WPV_Filter_Post_Relationship::SLUG, \WPV_Filter_Manager::EDITOR_MODE ), \WPV_Filter_Manager::FILTER_MODE_FULL );

		if ( \WPV_Filter_Manager::FILTER_MODE_FROM_SEARCH_FILTER === $editor_mode ) {
			return __( 'Filter based on the frontend search filter selection.', 'wpv-views' );
		}

		if ( \WPV_Filter_Manager::FILTER_MODE_FROM_CONTENT_SELECTION === $editor_mode ) {
			return __( 'Filter based on the current content selection.', 'wpv-views' );
		}

		$is_enabled_m2m = apply_filters( 'toolset_is_m2m_enabled', false );

		if ( !isset( $view_settings['post_relationship_mode'] ) ) {
			return;
		} elseif ( is_array( $view_settings['post_relationship_mode'] ) ) {
			$view_settings['post_relationship_mode'] = $view_settings['post_relationship_mode'][0];
		}
		if ( !isset( $view_settings['post_relationship_shortcode_attribute'] ) ) {
			$view_settings['post_relationship_shortcode_attribute'] = '';
		}
		if ( !isset( $view_settings['post_relationship_url_parameter'] ) ) {
			$view_settings['post_relationship_url_parameter'] = '';
		}
		if ( !isset( $view_settings['post_relationship_framework'] ) ) {
			$view_settings['post_relationship_framework'] = '';
		}
		ob_start();

		if ( $is_enabled_m2m ) { // m2m enabled.
			do_action( 'toolset_do_m2m_full_init' );
			$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
			if ( toolset_getarr( $view_settings, 'post_relationship_slug', '' ) ) {
				$definition = $relationship_repository->get_definition( $view_settings['post_relationship_slug'] );
				$relationship_display_name = $definition ? $definition->get_display_name() : '';
				$relationship_origin = $definition ? $definition->get_origin()->get_origin_keyword() : '';

				// translators: parent, child or both
				$type_relationship_summary = sprintf( __( 'Select posts in a <strong>%s</strong> relationship ', 'wpv-views' ), $relationship_display_name );

				if ( Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD === $relationship_origin ) {
					$child_post_types = $definition->get_child_type()->get_types();
					$child_post_type_object = get_post_type_object( $child_post_types[0] );
					if ( null != $child_post_type_object ) {
						$type_relationship_summary = sprintf( __( 'Select items from the <strong>%s</strong> group ', 'wpv-views' ), $child_post_type_object->labels->name );
					}
				}
			} else {
				$type_relationship_summary = __( 'Select posts in <strong>Any</strong> relationship ', 'wpv-views' );
			}
			switch ( $view_settings['post_relationship_mode'] ) {
				case 'current_page': // @deprecated in 1.12.1
				case 'top_current_post':
					// translators: parent, child or both
					echo __( $type_relationship_summary . 'that are <strong>related</strong> to the <strong>Post where this View is shown</strong>.', 'wpv-views' );
					break;
				case 'parent_view': // @deprecated in 1.12.1
				case 'current_post_or_parent_post_view':
					echo __( $type_relationship_summary . 'that are a <strong>related</strong> to the <strong>current post in the loop</strong>.', 'wpv-views' );
					break;
				case 'shortcode_attribute':
					echo sprintf( __( $type_relationship_summary . 'that are <strong>related</strong> to the <strong>Post with ID set by the shortcode attribute %s</strong>.', 'wpv-views' ), esc_html( $view_settings['post_relationship_shortcode_attribute'] ) );
					echo '<br /><code>' . sprintf( __( ' eg. [wpv-view name="view-name" <strong>%s="123"</strong>]', 'wpv-views' ), $view_settings['post_relationship_shortcode_attribute'] ) . '</code>';
					break;
				case 'url_parameter':
					echo sprintf( __( $type_relationship_summary . 'that are <strong>related</strong> to the <strong>Post with ID set by the URL parameter %s</strong>.', 'wpv-views' ), esc_html( $view_settings['post_relationship_url_parameter'] ) );
					echo '<br /><code>' . sprintf( __( ' eg. http://www.example.com/my-page/?<strong>%s=123</strong>', 'wpv-views' ), esc_html( $view_settings['post_relationship_url_parameter'] ) ) . '</code>';
					break;
				case 'this_page':
					if (
						isset( $view_settings['post_relationship_id'] )
						&& $view_settings['post_relationship_id'] > 0
					) {
						$wpv_wpml_integration = WPV_WPML_Integration_Embedded::get_instance();
						$view_settings['post_relationship_id'] = $wpv_wpml_integration->wpml_get_user_admin_language_post_id( $view_settings['post_relationship_id'] );
						global $wpdb;
						$selected_title = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT post_title FROM {$wpdb->posts}
								WHERE ID = %d
								LIMIT 1",
								$view_settings['post_relationship_id']
							)
						);
					} else {
						$selected_title = 'None';
					}
					echo sprintf( __( $type_relationship_summary . 'that are related to <strong>%s</strong>.', 'wpv-views' ), esc_html( $selected_title ) );
					break;
				case 'framework':
					global $WP_Views_fapi;
					if ( $WP_Views_fapi->framework_valid ) {
						if ( isset( $view_settings['post_relationship_framework'] ) && '' != $view_settings['post_relationship_framework'] ) {
							$post_relationship_framework = $view_settings['post_relationship_framework'];
						} else {
							$post_relationship_framework = __( 'None', 'wpv-views' );
						}
						echo sprintf( $type_relationship_summary . __( 'that are <strong>related</strong> to the <strong>Post with ID is set by the Framework option "%s"</strong>', 'wpv-views' ), esc_html( $post_relationship_framework ) );
					} else {
						$WP_Views_fapi->framework_missing_message_for_filters();
					}
					break;
				default:
					_e( 'Oops! It seems there is a filter by post relationship that is missing some options', 'wpv-views' );
					break;
			}
		} else { // m2m not enabled.
			switch ( $view_settings['post_relationship_mode'] ) {
				case 'current_page': // @deprecated in 1.12.1
				case 'top_current_post':
					_e( 'Select posts that are <strong>children</strong> of the <strong>Post where this View is shown</strong>.', 'wpv-views' );
					break;
				case 'parent_view': // @deprecated in 1.12.1
				case 'current_post_or_parent_post_view':
					_e( 'Select posts that are a <strong>children</strong> of the <strong>current post in the loop</strong>.', 'wpv-views' );
					break;
				case 'shortcode_attribute':
					echo sprintf( __( 'Select posts that are <strong>children</strong> of the <strong>Post with ID set by the shortcode attribute %s</strong>.', 'wpv-views' ), esc_html( $view_settings['post_relationship_shortcode_attribute'] ) );
					echo '<br /><code>' . sprintf( __( ' eg. [wpv-view name="view-name" <strong>%s="123"</strong>]', 'wpv-views' ), $view_settings['post_relationship_shortcode_attribute'] ) . '</code>';
					break;
				case 'url_parameter':
					echo sprintf( __( 'Select posts that are <strong>children</strong> of the <strong>Post with ID set by the URL parameter %s</strong>.', 'wpv-views' ), esc_html( $view_settings['post_relationship_url_parameter'] ) );
					echo '<br /><code>' . sprintf( __( ' eg. http://www.example.com/my-page/?<strong>%s=123</strong>', 'wpv-views' ), esc_html( $view_settings['post_relationship_url_parameter'] ) ) . '</code>';
					break;
				case 'this_page':
					if (
						isset( $view_settings['post_relationship_id'] )
						&& $view_settings['post_relationship_id'] > 0
					) {
						$wpv_wpml_integration = WPV_WPML_Integration_Embedded::get_instance();
						$view_settings['post_relationship_id'] = $wpv_wpml_integration->wpml_get_user_admin_language_post_id( $view_settings['post_relationship_id'] );
						global $wpdb;
						$selected_title = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT post_title FROM {$wpdb->posts}
								WHERE ID = %d
								LIMIT 1",
								$view_settings['post_relationship_id']
							)
						);
					} else {
						$selected_title = 'None';
					}
					echo sprintf( __( 'Select posts that are children of <strong>%s</strong>.', 'wpv-views' ), esc_html( $selected_title ) );
					break;
				case 'framework':
					global $WP_Views_fapi;
					if ( $WP_Views_fapi->framework_valid ) {
						if ( isset( $view_settings['post_relationship_framework'] ) && '' != $view_settings['post_relationship_framework'] ) {
							$post_relationship_framework = $view_settings['post_relationship_framework'];
						} else {
							$post_relationship_framework = __( 'None', 'wpv-views' );
						}
						echo sprintf( __( 'Select posts that are <strong>children</strong> of the <strong>Post with IDs is set by the Framework option "%s"</strong>', 'wpv-views' ), esc_html( $post_relationship_framework ) );
					} else {
						$WP_Views_fapi->framework_missing_message_for_filters();
					}
					break;
				default:
					_e( 'Oops! It seems there is a filter by post relationship that is missing some options', 'wpv-views' );
					break;
			}
		}
		$data = ob_get_clean();
		return $data;
	}

}

<?php

use OTGS\Toolset\Views\Models\Shortcode;
use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Views loop wizard controller.
 *
 * - Adds the Loop Wizard button to the Loop editor in Views and WordPress ARchives.
 * - Generates the loop wizard dialog content.
 * - Generates the new field select dropdown.
 * - Creates a wrapper Content Template on the fly.
 * - Generates the loop.
 * - Updates the stored data.
 *
 * @since m2m
 * @todo Move to /application/controllers soon.
 * @todo Move the AJAX callbacks to proper AJAX handlers.
 */
class WPV_Loop_Output_Wizard {

	const AJAX_ACTION_LOAD_WIZARD = 'wpv_layout_wizard';
	const AJAX_ACTION_ADD_FIELD = 'wpv_loop_wizard_add_field';

	private $menu_per_target = array();

	public function initialize() {
		add_action( 'wpv_action_wpv_codemirror_editor_toolbar', array( $this, 'add_button' ), 10 );
		add_action( 'view-editor-section-hidden', array( $this, 'add_data_to_js' ), 10, 4 );

		add_action( 'wp_ajax_' . self::AJAX_ACTION_LOAD_WIZARD, array( $this, 'load_wizard' ) );

		add_action( 'wp_ajax_' . self::AJAX_ACTION_ADD_FIELD, array( $this, 'add_field' ) );

		add_action( 'wp_ajax_wpv_update_loop_wizard_data', array( $this, 'update_loop_wizard_data' ) );
	}

	/**
	 * Print the loop wizard butto to the Loop editor in Views and WordPress Archives.
	 *
	 * @param $toolbar_data array
	 *
	 * @sine m2m
	 */
	public function add_button( $toolbar_data ) {
		if ( 'wpv_layout_meta_html_content' != toolset_getarr( $toolbar_data, 'editor_id' ) ) {
			return;
		}
		?>
		<li>
			<button class="button button-secondary js-code-editor-toolbar-button js-wpv-loop-wizard-open">
				<i class="icon-th fa fa-th"></i>
				<span class="button-label"><?php _e( 'Loop Wizard','wpv-views' ); ?></span>
			</button>
		</li>
		<?php

		/**
		 * wpv_filter_wpv_loop_output_editor_disable_forced_loop_wizard
		 *
		 * Disable the workflow that forces the Loop Wizard on the Loop Editor
		 *
		 * @since 2.2.0
		 */
		if (
			$toolbar_data['has_default_loop_output']
			&& ! apply_filters( 'wpv_filter_wpv_loop_output_editor_disable_forced_loop_wizard', false )
		) {
			?>
			<li>
				<a href="#" class="js-wpv-loop-wizard-skip" style="display:inline-block;height:28px;line-height:26px;">
					<?php _e( 'Skip wizard', 'wpv-views' ); ?>
				</a>
			</li>
			<?php
		}
	}

	/**
	 * Get the wizard saved settings, including the saved fields and the Bootstrap version, for JS usage.
	 *
	 * @param $view_id int
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	private function load_saved_settings( $view_id ) {
		$view_layout_settings = get_post_meta( $view_id, '_wpv_layout_settings', true);

		$bootstrap_version = Toolset_Settings::get_instance();
		if ( isset( $bootstrap_version->toolset_bootstrap_version ) ) {
			$bs_version = $bootstrap_version->toolset_bootstrap_version;
			/**
			 * Version numbers are stored as integers, like 2 or 3,
			 * with a .toolset suffix when Toolset needs to load the style itself.
			 */
			$view_layout_settings['wpv_bootstrap_version'] = str_replace( '.toolset', '', $bs_version );
		} else {
			$wpv_global_settings = WPV_Settings::get_instance();
			$view_layout_settings['wpv_bootstrap_version'] = 1;
			//Load bootstrap version from views settings
			if ( isset( $wpv_global_settings['wpv_bootstrap_version'] ) ) {
				$view_layout_settings['wpv_bootstrap_version'] = $wpv_global_settings['wpv_bootstrap_version'];
			}
		}

		if (
			isset( $view_layout_settings['fields'] )
			&& is_array( $view_layout_settings['fields'] )
		) {
			// Avoid associative arrays as we will pass this to JS as an array
			$view_layout_settings['fields'] = array_values( $view_layout_settings['fields'] );
		}

		return $view_layout_settings;
	}

	/**
	 * Print some JS variables so the loop wizard script can use them.
	 *
	 * @param $view_settings        array
	 * @param $view_layout_settings array
	 * @param $view_id              int
	 * @param $user_id              int
	 *
	 * @since m2m
	 * @todo Move this to the script localization, I just need the view ID here.
	 */
	public function add_data_to_js( $view_settings, $view_layout_settings, $view_id, $user_id ) {
		$loop_wizard_saved_settings = $this->load_saved_settings( $view_id );
		ob_start();
		require_once( WPV_PATH . '/inc/redesign/templates/wpv-layout-edit-wizard.tpl.php' );
		$dialog = ob_get_clean();
		?>
		<script type="text/javascript">
			var WPViews = WPViews || {};
			WPViews.layout_wizard_saved_settings = <?php echo json_encode( $loop_wizard_saved_settings ); ?>;
			WPViews.layout_wizard_saved_dialog = <?php echo json_encode( $dialog ); ?>;
		</script>
		<?php
	}

	/**
	 * Generate the loop wizard dialog and settings in an AJAX callback.
	 *
	 * @since m2m
	 */
	public function load_wizard() {
		if (
			! isset( $_POST["view_id"] )
			|| ! is_numeric( $_POST["view_id"] )
			|| intval( $_POST['view_id'] ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		ob_start();
		require_once( WPV_PATH . '/inc/redesign/templates/wpv-layout-edit-wizard.tpl.php' );
		$dialog = ob_get_clean();
		$data = array(
			'dialog' => $dialog,
			'settings' => $this->load_saved_settings( $_POST["view_id"] )
		);
		wp_send_json_success( $data );
	}

	/**
	 * Generate the list of available fields per View type.
	 *
	 * For Views listing posts, also generate a group for all non-Types postmeta fields.
	 *
	 * @param $view_id int
	 *
	 * @note We might end up with empty field groups since they might contain only items without a valid shortcode definition.
	 * That means that the grup is not empty, but it will produce no field items. We should detect and avoid.
	 *
	 * @since m2m
	 * @since 2.7.3 Include the wpv-loop-index shortcode in the Loop Wizard fields
	 */
	private function get_available_fields( $view_id ) {

		$view_object = WPV_View::get_instance( $view_id );
		$target = $view_object->query_type;

		if ( isset( $this->menu_per_target[ $target ] ) ) {
			return $this->menu_per_target[ $target ];
		}

		do_action( 'wpv_action_collect_shortcode_groups' );
		$shortcode_groups_all = apply_filters( 'wpv_filter_wpv_get_shortcode_groups', array() );
		$shortcode_groups = array();

		foreach ( $shortcode_groups_all as $group_id => $group_data ) {
			if ( ! in_array( $target, $group_data['target'] ) ) {
				continue;
			}

			$shortcode_groups[ $group_id ] = $group_data;
		}

		if ( 'posts' == $target ) {
			// Adjust the Post felds native group to include all non-Types native fields
			// Remove the wpv-post-field entry from the Post data group
			unset( $shortcode_groups['post']['fields']['wpv-post-field'] );
			$shortcode_groups['non-types-post-fields']['fields'] = array();
			$postmeta_keys = apply_filters( 'wpv_filter_wpv_get_postmeta_keys', array() );
			foreach ( $postmeta_keys as $postmeta_field ) {
				if ( ! wpv_is_types_custom_field( $postmeta_field ) ) {
					$shortcode_groups['non-types-post-fields']['fields'][ $postmeta_field ] = array(
						'name'		=> $postmeta_field,
						'handle'	=> 'wpv-post-field',
						'shortcode'	=> '[wpv-post-field name="' . $postmeta_field . '"]',
						'callback'	=> "WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpv-post-field', title: '" . esc_js( __( 'Post field', 'wpv-views' ) ) . "', overrides: {attributes:{name:'" . esc_js( $postmeta_field ) . "'}} })"
					);

				}
			}
			if ( count( $shortcode_groups['non-types-post-fields']['fields'] ) == 0 ) {
				unset( $shortcode_groups['non-types-post-fields'] );
			}

		}

		$this->menu_per_target[ $target ] = $shortcode_groups;
		return $this->menu_per_target[ $target ];
	}

	/**
	 * AJAX callback to generate a new field in the wizard.
	 *
	 * @since m2m
	 */
	public function add_field() {
		wpv_ajax_authenticate( 'wpv_loop_wizard_nonce', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );

		if (
			! isset( $_POST["view_id"] )
			|| ! is_numeric( $_POST["view_id"] )
			|| intval( $_POST['view_id'] ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing View ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		$view_id = intval( $_POST['view_id'] );
		$pattern = get_shortcode_regex();
		$menus = $this->get_available_fields( $view_id );

		$views_shortcodes_with_api_obj = apply_filters( 'wpv_filter_wpv_shortcodes_gui_data', array() );
		$views_shortcodes_with_api = array_keys( $views_shortcodes_with_api_obj );

		ob_start();
		?>
		<div id="layout-wizard-style___wpv_layout_count_placeholder__" class="wpv-loop-wizard-item-container js-wpv-loop-wizard-item-container">
			<i class="icon-move fa fa-arrows js-layout-wizard-move-field"></i>
			<select
				name="layout-wizard-style"
				class="wpv-layout-wizard-item js-wpv-select2 js-wpv-layout-wizard-item js-layout-wizard-item"
				id="js-wpv-layout-wizard-item-__wpv_layout_count_placeholder__"
			>
			<?php
			foreach ( $menus as $group_id => $group_data ) {
				$group_title = $group_data['name'];
				$group_items = $group_data['fields'];
				?>
				<optgroup label="<?php echo esc_attr( $group_title ); ?>">
				<?php foreach ( $group_items as $current_item_slug => $current_item ) {

					if ( empty( $current_item['shortcode'] ) ) {
						continue;
					}

					$current_shortcode_name = $current_item[ 'name' ];
					$current_shortcode_handle = isset( $current_item['handle'] ) ? $current_item['handle'] : $current_item_slug;
					$current_shortcode_handle_for_gui = $current_shortcode_handle;
					$current_shortcode_to_insert = $current_item['shortcode'];
					$current_shortcode_attributes = array();
					$current_shortcode_identifier_attribute = '';
					$current_shortcode_identifier_value = '';
					$current_shortcode_types_prameters = ( 'types' == $current_shortcode_handle ) ? $current_item['parameters'] : array();

					$current_shortcode_head = ''; // populate for table layouts

					// Legacy: before m2m shortcodes were registered without proper brackets
					if ( '[' !== substr( $current_shortcode_to_insert, 0, 1 ) ) {
						$current_shortcode_to_insert = '[' . $current_shortcode_to_insert . ']';
					}

					// Until I adjust the available shortcodes
					// so xxx-field shortcodes come with a 'name' attribute.
					// Also, do not include loop iterator shortcodes.
					if ( in_array( $current_shortcode_handle, array( 'wpv-taxonomy-field', 'wpv-for-each', Shortcode\Post\Taxonomy_Iterator::SHORTCODE_NAME ) ) ) {
						continue;
					}

					/**
					 * Manage shotcodes that can not be descrbed by ther handle alone:
					 * - set the identifier attribute and value
					 * - calculate some table headers
					 */
					// wpv-post-body
					if ( 'wpv-post-body' == $current_shortcode_handle ) {
						$current_shortcode_identifier_attribute = 'view_template';
						$current_shortcode_attributes = array();
						if ( 0 !== preg_match( "/$pattern/s", $current_shortcode_to_insert, $current_shortcode_data ) ) {
							$current_shortcode_attributes = shortcode_parse_atts( $current_shortcode_data[3] );
						}
						if (
							isset( $current_shortcode_attributes['view_template'] )
							&& 'None' != $current_shortcode_attributes['view_template']
						) {
							$current_shortcode_identifier_value = $current_shortcode_attributes['view_template'];
						} else {
							$current_shortcode_to_insert = '[wpv-post-body view_template="None"]';
							$current_shortcode_identifier_value = 'None';
						}
					}
					// wpv-post-taxonomy
					if ( 'wpv-post-taxonomy' == $current_shortcode_handle ) {
						$current_shortcode_identifier_attribute = 'type';
						if ( 0 !== preg_match( "/$pattern/s", $current_shortcode_to_insert, $current_shortcode_data ) ) {
							$current_shortcode_attributes = shortcode_parse_atts( $current_shortcode_data[3] );
						}
						$current_shortcode_identifier_value = $current_shortcode_attributes['type'];
					}
					if ( 'wpv-post-field' == $current_shortcode_handle ) {
						$current_shortcode_identifier_attribute = 'name';
						if ( 0 !== preg_match( "/$pattern/s", $current_shortcode_to_insert, $current_shortcode_data ) ) {
							$current_shortcode_attributes = shortcode_parse_atts( $current_shortcode_data[3] );
						}
						$current_shortcode_identifier_value = $current_shortcode_attributes['name'];
						$current_shortcode_head = 'post-field-' . $current_shortcode_attributes['name'];
					}
					// wpv-user
					if ( 'wpv-user' == $current_shortcode_handle ) {
						$current_shortcode_identifier_attribute = 'field';
						if ( 0 !== preg_match( "/$pattern/s", $current_shortcode_to_insert, $current_shortcode_data ) ) {
							$current_shortcode_attributes = shortcode_parse_atts( $current_shortcode_data[3] );
						}
						$current_shortcode_identifier_value = $current_shortcode_attributes['field'];
						if ( in_array(
							$current_shortcode_attributes['field'],
							array( 'user_email', 'display_name', 'user_login', 'user_url', 'user_registered', 'user_nicename' )
						) ) {
							$current_shortcode_head = $current_shortcode_attributes['field'];
						}
					}
					// wpv-view
					if ( 'wpv-view' == $current_shortcode_handle ) {
						$current_shortcode_identifier_attribute = 'name';
						if ( 0 !== preg_match( "/$pattern/s", $current_shortcode_to_insert, $current_shortcode_data ) ) {
							$current_shortcode_attributes = shortcode_parse_atts( $current_shortcode_data[3] );
						}
						$current_shortcode_identifier_value = $current_shortcode_attributes['name'];
					}
					// types
					if ( 'types' == $current_shortcode_handle ) {
						if ( 0 !== preg_match( "/$pattern/s", $current_shortcode_to_insert, $current_shortcode_data ) ) {
							$current_shortcode_attributes = shortcode_parse_atts( $current_shortcode_data[3] );
						}
						if ( isset( $current_shortcode_attributes['field'] ) ) {
							$current_shortcode_identifier_attribute = 'field';
							$current_shortcode_head = 'types-field-' . $current_shortcode_attributes['field'];
						} elseif ( isset( $current_shortcode_attributes['termmeta'] ) ) {
							$current_shortcode_identifier_attribute = 'termmeta';
							$current_shortcode_head = 'taxonomy-field-' . $current_shortcode_attributes['termmeta'];
						} elseif ( isset( $current_shortcode_attributes['usermeta'] ) ) {
							$current_shortcode_identifier_attribute = 'usermeta';
							$current_shortcode_head = 'user-field-' . $current_shortcode_attributes['usermeta'];
						}
						$current_shortcode_identifier_value = $current_shortcode_attributes[ $current_shortcode_identifier_attribute ];
					}

					/**
					 * Manage table headers for remaining shortcodes
					 */
					// wpv-taxonomy-field
					if ( 'wpv-taxonomy-field' == $current_shortcode_handle ) {
						if ( 0 !== preg_match( "/$pattern/s", $current_shortcode_to_insert, $current_shortcode_data ) ) {
							$current_shortcode_attributes = shortcode_parse_atts( $current_shortcode_data[3] );
						}
						$current_shortcode_head = 'taxonomy-field-' . $current_shortcode_attributes['name'];
					// wpv-post-xxx
					} else if ( 'wpv-post' == substr( $current_shortcode_handle, 0, 8 ) ) {
						$current_shortcode_head = substr( $current_shortcode_handle, 4 );
						if ( in_array(
							$current_shortcode_handle,
							array( 'wpv-post-status', 'wpv-post-class', 'wpv-post-body', 'wpv-post-featured-image' )
						) ) {
							$current_shortcode_head = '';
						}
					// wpv-taxonomy
					} else if ( 'wpv-taxonomy' == substr( $current_shortcode_handle, 0, 12 ) ) { // heading table solumns for wpv-taxonomy-* shortcodes
						if ( in_array(
							$current_shortcode_handle,
							array( 'wpv-taxonomy-link', 'wpv-taxonomy-title', 'wpv-taxonomy-id', 'wpv-taxonomy-slug' )
						) ) {
							$current_shortcode_head = substr( $current_shortcode_handle, 4 );
						} elseif ( $current_shortcode_handle == 'wpv-taxonomy-post-count' ) {
							$current_shortcode_head = 'taxonomy-post_count';
						}
					}
					?>
					<option value="<?php echo base64_encode( $current_shortcode_to_insert ); ?>"
						data-handle="<?php echo esc_attr( $current_shortcode_handle ); ?>"
						data-idattribute="<?php echo esc_attr( $current_shortcode_identifier_attribute ); ?>"
						data-idvalue="<?php echo esc_attr( $current_shortcode_identifier_value ); ?>"
						data-head="<?php echo esc_attr( $current_shortcode_head ); ?>"
						data-hasgui="<?php echo ( in_array( $current_shortcode_handle_for_gui, $views_shortcodes_with_api ) ) ? '1' : '0'; ?>"
						data-typesparameters="<?php echo esc_attr( json_encode( $current_shortcode_types_prameters ) ); ?>"
						>
						<?php echo $current_shortcode_name; ?>
					</option>
				<?php } ?>
				</optgroup>
			<?php } ?>
			</select>
			<button class="button-secondary js-wpv-loop-wizard-types-shortcode-ui" style="display: none">
				<i class="icon-edit fa fa-pencil-square-o"></i> <?php _e('Edit', 'wpv-views'); ?>
			</button>

			<button class="button-secondary js-wpv-loop-wizard-shortcode-ui" style="display: none" data-nonce="<?php echo wp_create_nonce('wpv_editor_callback'); ?>">
				<i class="icon-edit fa fa-pencil-square-o"></i> <?php _e('Edit', 'wpv-views'); ?>
			</button>

			<button class="button-secondary js-layout-wizard-remove-field" style="position: absolute; top: 5px; right: 5px;"><i class="icon-remove fa fa-times"></i></button>
		</div>
		<?php
		$result_html = ob_get_clean();
		$data = array(
			'html' => $result_html
		);
		wp_send_json_success( $data );
	}

	/**
	 * Update just the Loop Wizard data.
	 *
	 * This is needed when there were only fields-related changes
	 * coming and pushing a Loop using a loop Template - so no Layout Output update is needed.
	 *
	 * @since 1.9.0
	 */
	public function update_loop_wizard_data() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_layout_extra_nonce' )
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
		// Get View settings and layout settings
		$view_layout_array = get_post_meta( $_POST["id"], '_wpv_layout_settings', true );
		// Save the wizard settings
		if ( isset( $_POST['include_wizard_data'] ) ) {
			$view_layout_array['style'] = sanitize_text_field( $_POST['style'] );
			$view_layout_array['table_cols'] = sanitize_text_field( $_POST['table_cols'] );
			$view_layout_array['bootstrap_grid_cols'] = sanitize_text_field( $_POST['bootstrap_grid_cols'] );
			$view_layout_array['bootstrap_grid_container'] = sanitize_text_field( $_POST['bootstrap_grid_container'] );
			$view_layout_array['bootstrap_grid_row_class'] = sanitize_text_field( $_POST['bootstrap_grid_row_class'] );
			$view_layout_array['bootstrap_grid_individual'] = sanitize_text_field( $_POST['bootstrap_grid_individual'] );
			$view_layout_array['include_field_names'] = sanitize_text_field( $_POST['include_field_names'] );
			$view_layout_array['fields'] = ( isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ) ? array_map( 'sanitize_text_field', $_POST['fields'] ) : array();
			$view_layout_array['real_fields'] = ( isset( $_POST['real_fields'] ) && is_array( $_POST['real_fields'] ) ) ? array_map( 'sanitize_text_field', $_POST['real_fields'] ) : array();
		}
		update_post_meta( $_POST["id"], '_wpv_layout_settings', $view_layout_array );
		do_action( 'wpv_action_wpv_save_item', $_POST["id"] );
		$data = array(
			'id' => $_POST["id"],
			'message' => __( 'Loop saved', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

}

$wpv_loop_output_wizard_object = new WPV_Loop_Output_Wizard();
$wpv_loop_output_wizard_object->initialize();

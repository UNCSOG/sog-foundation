<?php

/**
* wpv-framework-api.php
*
* API definitions for third party frameworks integration
*
* @since 1.8.0
*/

/**
 * API function to register framework integration with Views
 *
 * @param $framework_id (string) Framework ID
 * @param $framework_data (array) Framework data:
 *         'name' (string) (optional) The name of the framework, will default to $framework_id
 *         'api_mode' (string) <function|option> The kind of framework API
 *         'api_handler' (string) The name of the function|option that can be used to get values from option slugs
 * @return bool (boolean) True if the framework was registered, false otherwise or if it was already registered
 *
 * @since 1.8.0
 */
function wpv_api_register_framework( $framework_id, $framework_data ) {
	global $WP_Views_fapi;
	if ( ! isset( $WP_Views_fapi ) ) {
		return false;
	}
	return $WP_Views_fapi->register_framework( $framework_id, $framework_data );
}

/**
* WP_Views_Integration_API
*
* API class for Views framework integration
*
* @since 1.8.0
*/
class WP_Views_Integration_API {

	public $framework = null;
	public $framework_data = array();
	public $framework_valid = false;
	public $framework_is_autodetected = false;
	public $framework_registered_keys = array();
	public $framework_integration_page = null;

	public $auto_detect_list  = array();

	public $example_register = '';
	public $example_register_key = '';

	public function __construct() {

		$this->framework = null;
		$this->framework_data = array();
		$this->framework_valid = false;
		$this->framework_is_autodetected = false;
		$this->framework_registered_keys = array();
		$this->framework_integration_page = null;

		/**
		* auto_detect_list
		*
		* List of known frameworks we offer to auto-register
		*/

		$this->auto_detect_list = array(
			'Options_Framework'	=> array(
				'id'			=> 'options_framework',
				'name'			=> __( 'Options Framework', 'wpv-views'  ),
				'api_mode'		=> 'function',
				'api_handler'	=> 'of_get_option',
				'link'			=> 'http://wptheming.com/options-framework-plugin'
			),
			'OT_Loader'			=> array(
				'id'			=> 'option_tree',
				'name'			=> __( 'OptionTree', 'wpv-views'  ),
				'api_mode'		=> 'function',
				'api_handler'	=> 'ot_get_option',
				'link'			=> 'https://wordpress.org/plugins/option-tree/'
			),
			/*
			'ReduxFramework'	=> array(
				'id'			=> 'redux',
				'name'			=> __( 'Redux', 'wpv-views'  ),
				'api_mode'		=> 'option',
				'api_handler'	=> 'redux_demo',
				'link'			=> 'https://reduxframework.com/'
			),
			*/
			'upfw_init'			=> array(// TBD
				'id'			=> 'upthemes',
				'name'			=> __( 'UpThemes', 'wpv-views'  ),
				'api_mode'		=> 'function',
				'api_handler'	=> array( $this, 'compat_upthemes_handler' ),
				'link'			=> 'https://upthemes.com/upthemes-framework/'
			),
			'get_option'   => array(
				'id'			    => 'toolset-customizer-options',
				'name'			    => __( 'Customizer - Site Options', 'wpv-views'  ),
				'api_mode'		    => 'function',
				'api_handler'	    => 'get_option',
				'link'			    => 'https://developer.wordpress.org/themes/advanced-topics/customizer-api/',
			),
			'get_theme_mod'   => array(
				'id'			    => 'toolset-customizer-theme-mod',
				'name'			    => __( 'Customizer - Theme Mods', 'wpv-views'  ),
				'api_mode'		    => 'function',
				'api_handler'	    => 'get_theme_mod',
				'link'			    => 'https://developer.wordpress.org/themes/advanced-topics/customizer-api/',
			),
		);

		/**
		* example_register
		*
		* Code example to register manually
		*/

		$this->example_register = "<pre><code style='display:block'>add_action( 'init', 'prefix_register_framework_in_views' );\n"
				. "function prefix_register_framework_in_views() {\n"
				. "\t" . '$framework_id = \'framework_slug\';' . "\n"
				. "\t" . '$framework_data = array(' . "\n"
				. "\t\t'name'\t\t=> '" . __( 'The framework name', 'wpv-views' ) . "',\n"
				. "\t\t'api_mode'\t=> '" . __( 'function|option', 'wpv-views' ) . "',\n"
				. "\t\t'api_handler'\t=> '" . __( 'Function name|Option ID', 'wpv-views' ) . "'\n"
				. "\t" . ');' . "\n"
				. "\t" . 'if ( function_exists( \'wpv_api_register_framework\' ) ) {' . "\n"
				. "\t\t" . 'wpv_api_register_framework( $framework_id, $framework_data );' . "\n"
				. "\t" . '}' . "\n"
				. '}'
				. "</code></pre>";

		/**
		* example_register
		*
		* Code example to register manually
		*
		* @nore Not used anywhere since 2.0
		*/

		$this->example_register_key = "<pre><code style='display:block'>add_action( 'wpv_action_wpv_register_integration_keys', 'prefix_register_framework_keys' );\n"
				. "function prefix_register_framework_keys( " . '$wpv_framework' . " ) {\n"
				. "\t" . '$key = \'' . __( 'option_slug', 'wpv-views' ) . "';\n"
				. "\t" . '$key_data = array(' . "\n"
				. "\t\t'framework_id'\t=> 'framework_slug'\n"
				. "\t" . ');' . "\n"
				. "\t" . '$wpv_framework->register_key( $key, $key_data );' . "\n"
				. '}'
				. "</code></pre>";

		add_action( 'init',												array( $this, 'init' ) );
		add_action( 'init',												array( $this, 'register_saved_auto_detected' ), 99 );

		add_action( 'wp_loaded',										array( $this, 'wp_loaded' ) );

		add_filter( 'toolset_filter_toolset_register_settings_section',	array( $this, 'register_settings_views_integration_section' ), 31 );
		add_filter( 'toolset_filter_toolset_register_settings_views-integration_section',	array( $this, 'views_integration_autodetect_options' ) );
		add_filter( 'toolset_filter_toolset_register_settings_views-integration_section',	array( $this, 'views_integration_registered_options' ), 20 );
		add_filter( 'toolset_filter_toolset_register_settings_views-integration_section',	array( $this, 'views_integration_error_options' ), 30 );
		add_filter( 'toolset_filter_toolset_register_settings_views-integration_section',	array( $this, 'views_integration_manual_options' ), 40 );

		add_action( 'toolset_menu_admin_enqueue_styles', array( $this, 'enqueue_assets' ) );

		// Bring the frameworks shortcode into the Views GUI
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data',				array( $this, 'register_shortcode_in_gui' ) );

		add_action( 'wp_ajax_wpv_register_auto_detected_framework',		array( $this, 'wpv_register_auto_detected_framework' ) );
		add_action( 'wp_ajax_wpv_update_framework_integration_keys',	array( $this, 'wpv_update_framework_integration_keys' ) );

		// Extend Views settings to allow for registered framework options
		add_filter( 'wpv_filter_extend_limit_options',					array( $this, 'extend_view_settings_as_array_options' ), 10 );
		add_filter( 'wpv_filter_extend_offset_options',					array( $this, 'extend_view_settings_as_array_options' ), 10 );
		add_filter( 'wpv_filter_extend_posts_per_page_options',			array( $this, 'extend_view_settings_as_array_options' ), 10 );

		// Extend Views filters to allow for registered framework options
		add_filter( 'wpv_filter_extend_framework_options_for_post_author',			array( $this, 'extend_view_settings_as_array_options_for_filters' ), 10 );
		add_filter( 'wpv_filter_extend_framework_options_for_post_id',				array( $this, 'extend_view_settings_as_array_options_for_filters' ), 10 );
		add_filter( 'wpv_filter_extend_framework_options_for_post_relationship',	array( $this, 'extend_view_settings_as_array_options_for_filters' ), 10 );
		add_filter( 'wpv_filter_extend_framework_options_for_parent',				array( $this, 'extend_view_settings_as_array_options_for_filters' ), 10 );
		add_filter( 'wpv_filter_extend_framework_options_for_category',				array( $this, 'extend_view_settings_as_array_options_for_filters' ), 10 );
		add_filter( 'wpv_filter_extend_framework_options_for_custom_field',			array( $this, 'extend_view_settings_as_array_options_for_filters' ), 10 );
		add_filter( 'wpv_filter_extend_framework_options_for_taxonomy_term',		array( $this, 'extend_view_settings_as_array_options_for_filters' ), 10 );
		add_filter( 'wpv_filter_extend_framework_options_for_termmeta_field',		array( $this, 'extend_view_settings_as_array_options_for_filters' ), 10 );
		add_filter( 'wpv_filter_extend_framework_options_for_users',				array( $this, 'extend_view_settings_as_array_options_for_filters' ), 10 );
		add_filter( 'wpv_filter_extend_framework_options_for_usermeta_field',		array( $this, 'extend_view_settings_as_array_options_for_filters' ), 10 );

		// API
		add_filter( 'wpv_filter_framework_has_valid_framework',						array( $this, 'has_valid_framework' ) );
		add_filter( 'wpv_filter_framework_count_registered_keys',					array( $this, 'count_registered_keys' ) );

		// Filter to check available Cherry Framework(s) and to populate (add to) auto_detect_list accordingly
		add_filter( 'wpv_filter_extend_framework_auto_detect_list', array( $this, 'detect_cherry_frameworks' ) );
	}

	/**
	* init
	*
	* Executed at init, used to register scripts/styles and shortcodes
	*
	* @since 1.8.0
	*/

	function init() {
		wp_register_script( 'views-framework-integration-js' , WPV_URL_EMBEDDED . '/res/js/views_framework_integration.js', array( 'jquery', 'toolset-utils', 'underscore' ), WPV_VERSION );
		$framework_translations = array(
			'warning_change'	=> __( "Please note that changing the registered framework will restart the registered options", 'wpv-views'),
			'wpv_close'			=> __( 'Close', 'wpv-views'),
			'nonce'				=> wp_create_nonce( 'wpv_framework_integration_nonce' )
		);
		wp_localize_script( 'views-framework-integration-js', 'views_framework_integration_texts', $framework_translations );
		add_shortcode( 'wpv-theme-option', array( $this, 'wpv_shortcode_wpv_theme_option' ) );

		$this->auto_detect_list = apply_filters('wpv_filter_extend_framework_auto_detect_list', $this->auto_detect_list);
	}

	/**
	 * Check for available Cherry Framework(s)
	 *
	 * @param array $auto_detected_list An array of auto detected list of frameworks.
	 * @return array Extended list of auto detected frameworks, after detection of available cherry frameworks.
	 *
	 * @see $this->auto_detect_list
	 * @since 2.2
	 */
	function detect_cherry_frameworks( $auto_detected_list ) {
		$known_foot_prints = array (
			'getCherryVersion'	=> array(// Version < 4
				'id'			=> 'cherry_three',
				'name'			=> __( 'Cherry Framework', 'wpv-views'  ),
				'api_mode'		=> 'function',
				'api_handler'	=> 'of_get_option',
				'link'			=> 'https://github.com/CherryFramework/CherryFramework',
				'version'		=> '< v4'
			),
			'Cherry_Options_Framework'	=> array(// Version 4
				'id'			=> 'cherry_four',
				'name'			=> __( 'Cherry Framework', 'wpv-views'  ),
				'api_mode'		=> 'function',
				'api_handler'	=> 'cherry_get_option',
				'link'			=> 'http://www.cherryframework.com/',
				'version'		=> 'v4'
			)
		);

		// Check how many are available
		$available_frameworks = array();
		foreach( $known_foot_prints as $framework => $data ) {
			if( function_exists( $framework ) || class_exists( $framework ) ) {
				$available_frameworks[$framework] = $data;
			}
		}

		// Prepare data and extend auto_detect_list
		$framework_count = sizeof( $available_frameworks );

		if( $framework_count > 0 ) {
			// Found cherry...!
			foreach( $available_frameworks as $framework => $data ) {
				if( $framework_count > 1 ) {
					// Stress framework version with the name
					// For this, concatenate 'version' with 'name'
					$data['name'] .= ' '.$data['version'];
				}

				$auto_detected_list[$framework] = $data;
			}
		} else {
			// Cherry doesn't exist here
			// Add foot prints to the latest version - for the sake of list items.
			$auto_detected_list['Cherry_Options_Framework'] = $known_foot_prints['Cherry_Options_Framework'];
		}

		return $auto_detected_list;
	}

	/**
	* register_saved_auto_detected
	*
	* Auto-register selected and stored framework late on init
	*
	* @since 1.10
	*/

	function register_saved_auto_detected() {
		$settings = WPV_Settings::get_instance();
		$auto_detected = $this->auto_detect();
		if (
			! $this->framework_valid
			&& ! empty( $auto_detected )
			&& isset( $settings->wpv_saved_auto_detected_framework )
			&& ! empty( $settings->wpv_saved_auto_detected_framework )
			&& in_array( $settings->wpv_saved_auto_detected_framework, $auto_detected )
		) {
			$saved_auto_detected_data = $this->auto_detect_list[$settings->wpv_saved_auto_detected_framework];
			$this->register_framework( $saved_auto_detected_data['id'], $saved_auto_detected_data );
			if ( $this->framework_valid ) {
				$this->framework_is_autodetected = true;
			}
		}
	}

	/**
	* wp_loaded
	*
	* @since 1.10
	*/

	function wp_loaded() {

		/**
		* Fires once WordPress has loaded, allowing keys to be registered.
		*
		* @see $this->register_key()
		*
		* @since 1.10
		*/

		do_action( 'wpv_action_wpv_register_integration_keys', $this );
	}

	/**
	 * Load assets in the Toolset Settings page.
	 *
	 * @param string $current_page Current Toolset admin page slug.
	 * @since 2.0
	 */
	function enqueue_assets( $current_page ) {
		if ( 'toolset-settings' === $current_page ) {
			wp_enqueue_script( 'views-framework-integration-js' );
		}
	}

	/**
	* register_settings_views_integration_section
	*
	* Add the Views Integration tab in the Toolset Settings page
	*
	* @since 2.0
	*/

	function register_settings_views_integration_section( $sections ) {
		$sections['views-integration'] = array(
			'slug'	=> 'views-integration',
			'title'	=> __( 'Views Integration', 'wpv-views' )
		);
		return $sections;
	}

	/**
	* views_integration_autodetect_options
	*
	* Add the Views Integration autodetect section in the Toolset Settings page
	*
	* @since 2.0
	*/

	function views_integration_autodetect_options( $sections ) {
        if (
            ! $this->framework_valid
            || $this->framework_is_autodetected
        ) {
            $section_content = '';
            ob_start();
            $this->render_autodetected_frameworks_selection();
            $section_content = ob_get_clean();

            $sections['views-autodetected-frameworks'] = array(
                'slug' => 'views-autodetected-frameworks',
                'title' => __('Autodetected frameworks', 'wpv-views'),
                'content' => $section_content
            );

            if (
                $this->framework
                && !$this->framework_valid
            ) {
                $sections['views-autodetected-frameworks']['hidden'] = true;
            }
        }

		return $sections;
	}

	/**
	* render_autodetected_frameworks_selection
	*
	* Render the autodetect section in the Toolset Settings page
	*
	* @since 2.0
	*/

	function render_autodetected_frameworks_selection() {
		$settings = WPV_Settings::get_instance();
		$saved_auto_detected = $settings->wpv_saved_auto_detected_framework;
		$auto_detected = $this->auto_detect();
		if (
			! $this->framework_valid
			|| $this->framework_is_autodetected
		) {
			?>
			<div class="toolset-advanced-setting">
				<p><?php _e( 'We have detected the following frameworks on your site:', 'wpv-views' ); ?></p>
				<ul>
					<li>
						<input type="radio" name="wpv-framework-auto" <?php checked( $saved_auto_detected, '' ); ?> id="wpv-framework-auto" data-id="" class="js-wpv-framework-auto" value="" autocomplete="off" />
						<label for="wpv-framework-auto"><?php _e( 'Do not register any framework automatically', 'wpv-views' ); ?></label>
					</li>
					<?php
					foreach ( $this->auto_detect_list as $auto_detect_key => $auto_detect_offer ) {
						?>
						<li>
							<input type="radio" name="wpv-framework-auto" <?php checked( $saved_auto_detected, $auto_detect_key ); ?> id="wpv-framework-auto-<?php echo $auto_detect_offer['id']; ?>" data-id="<?php echo esc_attr( $auto_detect_offer['id'] ); ?>" <?php disabled( ! in_array( $auto_detect_key , $auto_detected ) ); ?> class="js-wpv-framework-auto" value="<?php echo $auto_detect_key; ?>" autocomplete="off" />
							<label for="wpv-framework-auto-<?php echo $auto_detect_offer['id']; ?>"><?php echo $auto_detect_offer['name']; ?></label>
							- <a href="<?php echo esc_url( $auto_detect_offer['link'] ); ?>" target="_blank">
							<?php
							echo sprintf(
								__( 'Check the details for %s', 'wpv-views' ),
								$auto_detect_offer['name']
							);
							?>
							</a>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
			<p class="toolset-alert toolset-alert-info js-wpv-autodetect-frameworks-selection-warning" style="display:none">
				<?php
				_e( 'Please note that changing the registered framework will restart the registered options', 'wpv-views' );
				?>
				<br /><br />
				<button class="button js-wpv-autodetect-frameworks-selection-warning-action js-wpv-autodetect-frameworks-selection-warning-cancel"><?php _e( 'Cancel', 'wpv-views' ); ?></button>
				<button class="button button-primary alignright wpv-autodetect-frameworks-selection-warning-apply js-wpv-autodetect-frameworks-selection-warning-action js-wpv-autodetect-frameworks-selection-warning-apply"><?php _e( 'Apply', 'wpv-views' ); ?></button>
			</p>
			<?php
		}
	}

	/**
	* views_integration_registered_options
	*
	* Add the Views Integration registered section in the Toolset Settings page
	*
	* @since 2.0
	*/

	function views_integration_registered_options( $sections ) {
		$section_content = '';

		$sections['views-registered-framework'] = array(
			'slug'		=> 'views-registered-framework',
			'title'		=> __( 'Registered framework', 'wpv-views' ),
			'content'	=> ''
		);

		if (
			$this->framework
			&& $this->framework_valid
		) {
			ob_start();
			echo $this->render_registered_framework_settings();
			$section_content = ob_get_clean();
			$sections['views-registered-framework']['content'] = $section_content;
		} else {
			$sections['views-registered-framework']['hidden'] = true;
		}

		return $sections;
	}

	/**
	* render_registered_framework_settings
	*
	* Render the registered section in the Toolset Settings page
	*
	* @since 2.0
	*/

	function render_registered_framework_settings() {
		if ( $this->framework_valid ) {
			$framework_data = $this->framework_data;
			$framework_registered_keys = $this->get_stored_framework_keys();
			?>
			<div class="toolset-advanced-setting wpv-add-item-settings js-toolset-views-registered-framework-<?php echo esc_attr( $this->framework ); ?>">
				<p>
					<?php
					if ( $this->framework_is_autodetected ) {
						echo sprintf( __( 'You have selected the <strong>%s</strong> options framework to be used with Views.', 'wpv-views' ), $framework_data['name'] );
					} else {
						echo sprintf( __( 'Your theme has registered the <strong>%s</strong> options framework to be used with Views.', 'wpv-views' ), $framework_data['name'] );
					}
					?>
				</p>
				<?php
				if (
					isset( $this->framework_registered_keys[$this->framework] )
					&& ! empty( $this->framework_registered_keys[$this->framework] )
				) {
					echo '<h3>'
						. __( 'Auto-registered options', 'wpv-views' )
						. '</h3>'
						. '<p>'
						. __( 'Those options were registered using the Views Integration API.', 'wpv-views' )
						. '</p>'
						. '<ul class="toolset-taglike-list">';
					foreach( $this->framework_registered_keys[$this->framework] as $reg_key ) {
						echo '<li>'
							. $reg_key
							. '</li>';
					}
					echo '</ul>';
				}
				?>
				<h3><?php _e( 'Declare theme options', 'wpv-views' ); ?></h3>
				<p>
					<?php
					_e( 'You can use the theme options as a source of values in several settings of Views.', 'wpv-views' );
					echo WPV_MESSAGE_SPACE_CHAR;
					_e( 'To do that, you need to declare here which theme options should be available to be used inside Views.', 'wpv-views' );
					?>
				</p>
				<p>
					<?php
					_e( 'Use the form below to register options.', 'wpv-views' );
					echo WPV_MESSAGE_SPACE_CHAR;
					_e( 'Also, note that you can delete options that no longer need to be available.', 'wpv-views' );
					?>
				</p>
				<div class="js-wpv-add-item-settings-wrapper">
					<ul class="toolset-taglike-list js-wpv-add-item-settings-list js-custom-shortcode-list">
						<?php
						foreach ( $framework_registered_keys as $fw_key ) {
								?>
								<li class="js-<?php echo $fw_key; ?>-item">
									<span class=""><?php echo $fw_key; ?></span>
									<i class="icon-remove-sign fa fa-times-circle js-wpv-framework-slug-delete" data-target="<?php echo esc_attr( $fw_key ); ?>"></i>
								</li>
								<?php
							}
						?>
					</ul>
					<form class="js-wpv-add-item-settings-form js-wpv-framework-integration-form-add">
						<input type="text" placeholder="<?php _e( 'Option slug', 'wpv-views' ); ?>" class="js-wpv-add-item-settings-form-newname js-wpv-framework-integration-newname" />
						<button class="button button-secondary js-wpv-add-item-settings-form-button js-wpv-framework-slug-add" type="button" disabled><i class="icon-plus fa fa-plus"></i> <?php _e( 'Add', 'wpv-views' ); ?></button>
						<span class="toolset-alert toolset-alert-error hidden js-wpv-cs-error"><?php _e( 'Only latin letters, numbers, underscores and dashes', 'wpv-views' ); ?></span>
						<span class="toolset-alert toolset-alert-info hidden js-wpv-cs-dup"><?php _e( 'That option was already declared', 'wpv-views' ); ?></span>
						<span class="toolset-alert toolset-alert-info hidden js-wpv-cs-ajaxfail"><?php _e( 'An error ocurred', 'wpv-views' ); ?></span>
					</form>
				</div>
			</div>
			<?php
		}
	}

	/**
	* views_integration_error_options
	*
	* Add the Views Integration error section in the Toolset Settings page
	*
	* @since 2.0
	*/

	function views_integration_error_options( $sections ) {
		$section_content = '';
		ob_start();
		?>
		<p>
			<?php _e( 'Your framework was not correctly registered. Remember that you need to register your framework as follows:', 'wpv-views' ); ?>
		</p>
		<?php
		echo $this->example_register;
		?>
		<?php
		$section_content = ob_get_clean();

		$sections['views-error-framework'] = array(
			'slug'		=> 'views-error-framework',
			'title'		=> __( 'Broken integration', 'wpv-views' ),
			'content'	=> $section_content
		);

		if (
			! $this->framework
			|| $this->framework_valid
		) {
			$sections['views-error-framework']['hidden'] = true;
		}

		return $sections;
	}

	/**
	* views_integration_manual_options
	*
	* Add the Views Integration manual section in the Toolset Settings page
	*
	* @since 2.0
	*/

	function views_integration_manual_options( $sections ) {
		$section_content = '';
		ob_start();
		?>
		<p>
			<?php _e( 'You can register your framework manually as follows:', 'wpv-views' ); ?>
		</p>
		<?php
		echo $this->example_register;
		?>
		<?php
		$section_content = ob_get_clean();

		$sections['views-manual-framework'] = array(
			'slug'		=> 'views-manual-framework',
			'title'		=> __( 'Register your framework manually', 'wpv-views' ),
			'content'	=> $section_content
		);

		if ( $this->framework ) {
			$sections['views-manual-framework']['hidden'] = true;
		}

		return $sections;
	}

	/**
	* register_shortcode_in_gui
	*
	* Register the wpv-theme-option shortcode into the Views GUI
	*
	* @since 1.10
	*/

	function register_shortcode_in_gui( $views_shortcodes ) {
		if ( $this->framework_valid ) {
			$views_shortcodes['wpv-theme-option'] = array(
				'callback' => array( $this, 'get_shortcode_data' )
			);
		}
		return $views_shortcodes;
	}

	/**
	* wpv_register_auto_detected_framework
	*
	* Registers the framework selected when clicking the Save button
	*
	* @since 1.10
	*/

	function wpv_register_auto_detected_framework() {
		if ( ! wp_verify_nonce( $_POST['wpv_framework_integration_nonce'], 'wpv_framework_integration_nonce' ) ) {
			wp_send_json_error();
        }
		$auto_detected = $this->auto_detect();
		$framework = isset( $_POST['framework'] ) ? sanitize_text_field( $_POST['framework'] ) : '';
		$include_section = isset( $_POST['include_section'] ) ? sanitize_text_field( $_POST['include_section'] ) : '';
		if (
			empty( $framework )
			|| isset( $this->auto_detect_list[ $framework ] )
		) {
			$data = array();

			$settings = WPV_settings::get_instance();
			$settings['wpv_saved_auto_detected_framework'] = $framework;
			$settings->save();

			$this->framework = null;
			$this->framework_valid = false;
			$this->register_saved_auto_detected();
			$section_content = '';
			$data['section'] = '';

			if (
				! empty( $include_section )
				&& $include_section == $framework
			) {
				do_action( 'wpv_action_wpv_register_integration_keys', $this );
				ob_start();
				echo $this->render_registered_framework_settings();
				$section_content = ob_get_clean();
				$data['section'] = $section_content;
			}

			wp_send_json_success( $data );
		} else {
			$data = array(
				'message'	=> __( 'The framework does not exist', 'wpv-views' )
			);
			wp_send_json_error();
		}
	}

	/**
	* wpv_update_framework_integration_keys
	*
	* AJAX callback for saving the VIews theme framework integration settings
	*
	* @since 1.8.0
	*/

	function wpv_update_framework_integration_keys() {
		if ( ! wp_verify_nonce( $_POST['wpv_framework_integration_nonce'], 'wpv_framework_integration_nonce' ) ) {
            wp_send_json_error();
        }
		$fw_keys = $this->get_stored_framework_keys();
		if (
			isset( $_POST['update_action'] )
			&& isset( $_POST['update_tag'] )
		) {
            $update_tag = sanitize_text_field( $_POST['update_tag'] );
			switch ( $_POST['update_action'] ) {
                case 'add':
                    if ( ! in_array( $update_tag, $fw_keys ) ) {
                        $fw_keys[] = $update_tag;
                    }
                    break;
                case 'delete':
                    $key = array_search( $update_tag, $fw_keys );
                    if ( $key !== false ) {
                        unset( $fw_keys[$key] );
                    }
                    break;
            }
            $this->set_stored_framework_keys( $fw_keys );
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
		wp_send_json_error();
	}

	/**
	* extend_view_settings_as_array_options
	*
	* Add the framework registered options to some View settings
	*
	* @since 1.8.0
	*/

	function extend_view_settings_as_array_options( $options = array() ) {
		if ( $this->framework_valid ) {
			$framework_keys = $this->get_combined_framework_keys();
			foreach ( $framework_keys as $fw_key ) {
				$options['FRAME_KEY(' . $fw_key . ')'] = $this->framework_data['name'] . ': ' . $fw_key;
			}
		}
		return $options;
	}

	/**
	* extend_view_settings_as_array_options_for_filters
	*
	* Add the framework registered options to some View query filters
	*
	* @since 1.8.0
	*/

	function extend_view_settings_as_array_options_for_filters( $options = array() ) {
		if ( $this->framework_valid ) {
			$framework_keys = $this->get_combined_framework_keys();
			foreach ( $framework_keys as $fw_key ) {
				$options[$fw_key] = $this->framework_data['name'] . ': ' . $fw_key;
			}
		}
		return $options;
	}

	/**
	* framework_missing_message_for_filters
	*
	* Display a generic error message when a query filter should be using a framework option, but no valid framework was registered
	*
	* @param $item (string|false) (optional) The specific query filter
	* @param $show_flag (boolean) (optional) Whether to add a red flag
	*
	* @return echo (string)
	*
	* @since 1.8.0
	*/

	function framework_missing_message_for_filters( $item = false, $show_flag = true ) {
		echo $this->get_framework_missing_message_for_filters( $item, $show_flag );
	}

	/**
	* get_framework_missing_message_for_filters
	*
	* Return a generic error message when a query filter should be using a framework option, but no valid framework was registered
	*
	* @param $item (string|false) (optional) The specific query filter
	* @param $show_flag (boolean) (optional) Whether to add a red flag
	*
	* @return (string)
	*
	* @since 1.8.0
	*/

	function get_framework_missing_message_for_filters( $item = false, $show_flag = true ) {
		$return = '';
		if ( $show_flag ) {
			$return .= '<span class="wpv-filter-title-notice wpv-filter-title-notice-error">'
			. '<i class="icon-bookmark fa fa-bookmark fa-rotate-270 icon-large fa-lg" title="This filters needs some action"></i>'
			. '</span>';
		}
		if ( $item ) {
			$return .= '<strong style="color:#d54e21">' . $item . '</strong> - '
			. WPV_MESSAGE_SPACE_CHAR;
		}
		$return .= __( 'This filter should use a <strong style="color:#d54e21">Framework option</strong>, but there is no Framework registered with Views.', 'wpv-views' )
			. WPV_MESSAGE_SPACE_CHAR
			. __( 'Unless you edit it, this filter will not be applied at all.', 'wpv-views' );
		return $return;
	}

	/**
	* wpv_shortcode_wpv_theme_option
	*
	* Shortcode to display theme framework option values
	*
	* @param $atts array
	*     'name'		=> (mandatory)	the option name
	*     'separator'	=> (optional)	the separator for array values
	*
	* @since 1.8.1
	*/

	function wpv_shortcode_wpv_theme_option( $atts ) {
		extract(
			shortcode_atts(
				array(
					'name' => '',
					'separator' => ', '
				),
				$atts
			)
		);
		if (
			empty( $name )
			|| ! $this->framework_valid
		) {
			return '';
		}
		$value = $this->get_framework_value( $name, '' );
		if ( is_array( $value ) ) {
			return implode( $separator, $value );
		} else {
			return $value;
		}
	}

	/**
	* get_shortcode_data
	*
	* Get the data for the wpv-theme-option shortcode to build its GUI
	*
	* @since 1.10
	*/

	function get_shortcode_data() {
		$url = esc_url(
			add_query_arg(
				array( 'page' => 'views-framework-integration' ),
				admin_url( 'admin.php' )
			)
		);
		$keys = $this->get_combined_framework_keys();
		$name_options = array(
			'' => __( 'Select an option name', 'wpv-views' )
		);
		foreach ( $keys as $fw_key ) {
			$name_options[$fw_key] = $fw_key;
		}
		$data = array(
			'name' => __( 'Integrated option', 'wpv-views' ),
			'label' => __( 'Integrated option', 'wpv-views' ),
			'attributes' => array(
				'display-options' => array(
					'label' => __('Display options', 'wpv-views'),
					'header' => __('Display options:', 'wpv-views'),
					'fields' => array(
						'name' => array(
							'label' => __( 'Name', 'wpv-views'),
							'type' => 'select',
							'options' => $name_options,
							'description' => __( 'The name of the theme option to display', 'wpv-views' ),
							'documentation' => '<a href="' . $url . '" target="_blank">' . __( 'Views integration with theme options', 'wpv-views' ) . '</a>',
							'required' => true,
						),
						'separator' => array(
							'label' => __( 'Separator', 'wpv-views'),
							'type' => 'text',
							'default' => ', ',
							'description' => __( 'When that theme option holds more than one value, display this separator between them', 'wpv-views' ),
						),
					),
				),
			),
		);
		return $data;
	}

	/**
	* register_framework
	*
	* Register framework integration with Views
	*
	* @param $framework_id (string) Framework ID
	* @param $framework_data (array) Framework data
	*		'name' => The framework name
	*		'api_mode' => <function|option>
	*		'api_handler' => Function name|Option ID
	*
	* @return (boolean) True if the framework was registered, false if it was already registered
	*
	* @since 1.8.0
	*/

	function register_framework( $framework_id, $framework_data ) {
		if ( ! is_null( $this->framework ) ) {
			return false;
		} else {
			$this->framework = $framework_id;
			$this->framework_data = $framework_data;
			$this->check_framework_data( $this->framework_data );
			return $this->framework_valid;
		}
	}

	/**
	* check_framework_data
	*
	* Validate the data when registering a framework
	* Used to decide whether it is valid or not, setting the $framework_valid property
	*
	* @param $framework_data (array)
	*/

	function check_framework_data( $framework_data ) {
		if (
			is_array( $framework_data )
			&& ! empty( $framework_data )
		) {
			if ( ! isset( $framework_data['name'] ) ) {
				$this->framework_data['name'] = $this->framework;
			}
			if (
				isset( $framework_data['api_mode'] )
				&& isset( $framework_data['api_handler'] )
			) {
				switch ( $framework_data['api_mode'] ) {
					case 'function':
						if ( is_callable( $framework_data['api_handler'] ) ) {
							$this->framework_valid = true;
						}
						break;
					case 'option':
						$framework_options = get_option( $framework_data['api_handler'], array() );
						if ( is_array( $framework_options ) ) {
							$this->framework_valid = true;
						}
						break;
				}
			}
		} else {
			$this->framework_data = array(
				'name' => __( 'Unknown framework', 'wpv-views' )
			);
		}
	}

	/**
	* register_key
	*
	* API method to register key in PHP
	*
	* @since 1.10
	*/

	function register_key( $id, $args = array() ) {
		if ( $this->framework_valid ) {
			if ( ! isset( $args['framework_id'] ) ) {
				$args['framework_id'] = $this->framework;
			}
			if ( $args['framework_id'] == $this->framework ) {
				if ( ! isset( $this->framework_registered_keys[$this->framework] ) ) {
					$this->framework_registered_keys[$this->framework] = array();
				}
				$this->framework_registered_keys[$this->framework][] = sanitize_text_field( $id );
			}

		}
	}

	/**
	 * auto_detect
	 *
	 * Check for familiar frameworks and offer them for registration.
	 *
	 * @return array An array of detected framework names.
	 * @since 1.10
	 */
	function auto_detect() {
		$auto_detected = array();
		foreach ( $this->auto_detect_list as $thiz_present => $thiz_data ) {
			if (
				function_exists( $thiz_present )
				|| class_exists( $thiz_present, false )
			) {
				$auto_detected[] = $thiz_present;
			}
		}
		return $auto_detected;
	}

	/**
	* get_registered_framework_management_structure
	*
	* Display the option keys management section for the registered framework.
	*
	* @since 1.10
	*/

	function get_registered_framework_management_structure() {
		$return = '';
		if ( $this->framework_valid ) {
			$framework_data = $this->framework_data;
			$framework_registered_keys = $this->get_stored_framework_keys();
			ob_start();
			?>
			<div class="wpv-settings-header">
				<h2><?php echo $this->framework_data['name']; ?></h2>
			</div>
			<div class="wpv-setting">
				<div class="wpv-advanced-setting wpv-add-item-settings">
					<p>
						<?php
						if ( $this->framework_is_autodetected ) {
							echo sprintf( __( 'You have selected the <strong>%s</strong> options framework to be used with Views.', 'wpv-views' ), $framework_data['name'] );
						} else {
							echo sprintf( __( 'Your theme has registered the <strong>%s</strong> options framework to be used with Views.', 'wpv-views' ), $framework_data['name'] );
						}
						?>
					</p>
					<?php
					if (
						isset( $this->framework_registered_keys[$this->framework] )
						&& ! empty( $this->framework_registered_keys[$this->framework] )
					) {
						echo '<h3>'
							. __( 'Auto-registered options', 'wpv-views' )
							. '</h3>'
							. '<p>'
							. __( 'Those options were registered using the Views Integration API.', 'wpv-views' )
							. '</p>'
							. '<ul class="toolset-taglike-list">';
						foreach( $this->framework_registered_keys[$this->framework] as $reg_key ) {
							echo '<li>'
								. $reg_key
								. '</li>';
						}
						echo '</ul>';
					}
					?>
					<h3><?php _e( 'Declare theme options', 'wpv-views' ); ?></h3>
					<p>
						<?php
						_e( 'You can use the theme options as a source of values in several settings of Views.', 'wpv-views' );
						echo WPV_MESSAGE_SPACE_CHAR;
						_e( 'To do that, you need to declare here which theme options should be available to be used inside Views.', 'wpv-views' );
						?>
					</p>
					<p>
						<?php
						_e( 'Use the form below to register options.', 'wpv-views' );
						echo WPV_MESSAGE_SPACE_CHAR;
						_e( 'Also, note that you can delete options that no longer need to be available.', 'wpv-views' );
						?>
					</p>
					<div class="js-wpv-add-item-settings-wrapper">
						<ul class="toolset-taglike-list js-wpv-add-item-settings-list js-custom-shortcode-list">
							<?php
							foreach ( $framework_registered_keys as $fw_key ) {
									?>
									<li class="js-<?php echo $fw_key; ?>-item">
										<span class=""><?php echo $fw_key; ?></span>
										<i class="icon-remove-sign fa fa-times-circle js-wpv-framework-slug-delete" data-target="<?php echo esc_attr( $fw_key ); ?>"></i>
									</li>
									<?php
								}
							?>
						</ul>
						<form class="js-wpv-add-item-settings-form js-wpv-framework-integration-form-add">
							<input type="text" placeholder="<?php _e( 'Option slug', 'wpv-views' ); ?>" class="js-wpv-add-item-settings-form-newname js-wpv-framework-integration-newname" />
							<button class="button button-secondary js-wpv-add-item-settings-form-button js-wpv-framework-slug-add" type="button" disabled><i class="icon-plus fa fa-plus"></i> <?php _e( 'Add', 'wpv-views' ); ?></button>
							<span class="toolset-alert toolset-alert-error hidden js-wpv-cs-error"><?php _e( 'Only latin letters, numbers, underscores and dashes', 'wpv-views' ); ?></span>
							<span class="toolset-alert toolset-alert-info hidden js-wpv-cs-dup"><?php _e( 'That option was already declared', 'wpv-views' ); ?></span>
							<span class="toolset-alert toolset-alert-info hidden js-wpv-cs-ajaxfail"><?php _e( 'An error ocurred', 'wpv-views' ); ?></span>
						</form>
					</div>
				</div>
			</div>
			<?php
			$return = ob_get_clean();
		}
		return $return;
	}

	/**
	* get_stored_framework_keys
	*
	* Get framework keys registered in Views
	*
	* @return (array)
	*
	* @since 1.8.0
	*/

	function get_stored_framework_keys() {
		$return = array();
		if ( is_null( $this->framework ) ) {
			return $return;
		}
		$settings = WPV_Settings::get_instance();
		if (
			isset( $settings->wpv_framework_keys )
			&& is_array( $settings->wpv_framework_keys )
			&& isset( $settings->wpv_framework_keys[$this->framework] )
			&& is_array( $settings->wpv_framework_keys[$this->framework] )
		) {
			$return = $settings->wpv_framework_keys[$this->framework];
		}

		/**
		* wpv_filter_get_stored_framework_keys
		*
		* Filter to include or exclude specific or special registered framework keys
		*
		* @param $return 			(array) 	Existing registered framework keys
		* @param $this 	(string) 	ID of the currently registered framework
		*
		* @since 1.8.0
		*/

		$return = apply_filters( 'wpv_filter_get_stored_framework_keys', $return, $this );
		return $return;
	}

	/**
	* get_registered_framework_keys
	*
	* Get framework keys registered in PHP
	*
	* @return array
	*
	* @since 1.10
	*/

	function get_registered_framework_keys() {
		$return = array();
		if ( $this->framework_valid ) {
			if (
				isset( $this->framework_registered_keys[$this->framework] )
				&& ! empty( $this->framework_registered_keys[$this->framework] )
			) {
				$return = $this->framework_registered_keys[$this->framework];
			}
		}
		return $return;
	}

	/**
	* get_combined_framework_keys
	*
	* Get framework keys registered in Views and by PHP
	*
	* @return (array)
	*
	* @since 1.10
	*/

	function get_combined_framework_keys() {
		$return = array();
		if ( is_null( $this->framework ) ) {
			return $return;
		}
		$stored = $this->get_stored_framework_keys();
		$registered = $this->get_registered_framework_keys();

		$return = array_merge( $stored, $registered );
		$return = array_unique( $return );

		/**
		* wpv_filter_get_combined_framework_keys
		*
		* Filter to include or exclude specific or special registered framework keys
		*
		* @param $return 			(array) 	Existing registered framework keys
		* @param $this 	(string) 	ID of the currently registered framework
		*
		* @since 1.8.0
		*/

		$return = apply_filters( 'wpv_filter_get_combined_framework_keys', $return, $this );
		return $return;
	}

	/**
	* set_stored_framework_keys
	*
	* Set framework keys registered in Views
	*
	* @param $fw_keys (array) Keys to register
	*
	* @since 1.8.0
	*/

	function set_stored_framework_keys( $fw_keys = array() ) {
		if ( is_null( $this->framework ) ) {
			return;
		}

		$settings = WPV_Settings::get_instance();
		if (
			isset( $settings->wpv_framework_keys )
			&& is_array( $settings->wpv_framework_keys )
		) {
			$wpv_framework_settings = $settings->wpv_framework_keys;
		} else {
			$wpv_framework_settings = array();
		}
		$wpv_framework_settings[$this->framework] = $fw_keys;
		$settings->wpv_framework_keys = $wpv_framework_settings;
		$settings->save();
	}

	/**
	* get_framework_value
	*
	* Get the value given a registered framework key
	*
	* @param $key (string) The key
	* @param $default (mixed) The value to return when there is no valid framework registered
	*
	* @return $return (mixed)
	*
	* @since 1.8.0
	*/

	function get_framework_value( $key, $return ) {
		if ( $this->framework_valid ) {
			$framework_keys = $this->get_combined_framework_keys();
			if ( in_array( $key, $framework_keys ) ) {
				switch ( $this->framework_data['api_mode'] ) {
					case 'function':
						$fw_name = $this->framework_data['api_handler'];
						$return = call_user_func( $fw_name, $key );
						break;
					case 'option':
						$framework_options = get_option( $this->framework_data['api_handler'], array() );
						if ( isset( $framework_options[$key] ) ) {
							$return = $framework_options[$key];
						}
						break;
					case 'setting':

						break;
				}
			}
		}
		return $return;
	}

	/**
	* --------------------------------
	* Compatibility
	* --------------------------------
	*/

	/**
	* compat_upthemes_handler
	*
	* Wrapper to get values from the UpThemes framework
	*
	* @since 1.10
	*/

	function compat_upthemes_handler( $name ) {
		$return = '';
		if (
			function_exists( 'upfw_init' )
			&& function_exists( 'upfw_get_options' )
		) {
			$wpv_up_options = upfw_get_options();
			$wpv_up_options_array = ( array ) $wpv_up_options;
			if ( isset( $wpv_up_options_array[$name] ) ) {
				$return = $wpv_up_options_array[$name];
			}
		}
		return $return;
	}

	/**
	* --------------------------------
	* API
	* --------------------------------
	*/

	function has_valid_framework( $status ) {
		$status = $this->framework_valid;
		return $status;
	}

	function count_registered_keys( $keys_count ) {
		$keys = $this->get_combined_framework_keys();
		$keys_count = count( $keys );
		return $keys_count;
	}
}

global $WP_Views_fapi;
$WP_Views_fapi = new WP_Views_Integration_API();

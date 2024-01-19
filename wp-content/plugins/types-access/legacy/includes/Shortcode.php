<?php

if ( ! class_exists( 'OTG_Access_Shortcode_Generator' ) ) {

	/**
	 * OTG_Access_Shortcode_Generator
	 *
	 * Shortcodes generator for Access.
	 *
	 * Inherits from Toolset_Shortcode_Generator which is the base class
	 * used to register items in the backend Admin Bar item for Toolset shortcodes.
	 * Also used to generate the Access editor button and the dialogs for inserting shortcodes.
	 *
	 * @since 2.3.0
	 */

	class OTG_Access_Shortcode_Generator extends Toolset_Shortcode_Generator {

		/**
		 * Admin bar shortcodes button priority.
		 *
		 * Set to 5 to follow an order for Toolset buttons:
		 * - 5 Types/Views
		 * - 6 Forms
		 * - 7 Access
		 */
		const ADMIN_BAR_BUTTON_PRIORITY = 7;

		/**
		 * Media toolbar shortcodes button priority. Note that the native button is loaded at 10.
		 *
		 * Set to 11 to follow an order for Toolset buttons:
		 * - 11 Types/Views
		 * - 12 Forms
		 * - 13 Access
		 */
		const MEDIA_TOOLBAR_BUTTON_PRIORITY = 13;

		/**
		 * MCE shortcodes button priority.
		 *
		 * Set to 5 to follow an order for Toolset buttons:
		 * - 5 Types/Views
		 * - 6 Forms
		 * - 7 Access
		 */
		const MCE_BUTTON_PRIORITY = 7;

		public $admin_bar_item_registered = false;
		public $footer_dialogs_needed = false;
		public $footer_dialogs_added = false;
		public $mce_view_templates_needed = false;

		/**
		 * Constructor.
		 *
		 * @since 2.3.0
		 * @see Toolset_Shortcode_Generator::__construct
		 */
		public function __construct() {

			parent::__construct();

			/**
			 * ---------------------
			 * Admin Bar
			 * ---------------------
			 */

			// Track whether the Admin Bar item has been registered
			$this->admin_bar_item_registered = false;
			// Register the Access item in the backend Admin Bar
			add_filter( 'toolset_shortcode_generator_register_item', array( $this, 'register_access_shortcode_generator' ), self::ADMIN_BAR_BUTTON_PRIORITY );

			/**
			 * ---------------------
			 * Access button and dialogs
			 * ---------------------
			 */

			// Access button in native editors plus on demand:
			// - From media_buttons actions
			// - From custom otg_access_action_generate_access_button action for adding the button
			// - From Toolset custom editor toolbars
			add_action( 'media_buttons',										array( $this, 'generate_access_button' ), self::MEDIA_TOOLBAR_BUTTON_PRIORITY );
			add_action( 'otg_access_action_generate_access_button',				array( $this, 'generate_access_custom_button' ), 30 );
			add_action( 'toolset_action_toolset_editor_toolbar_add_buttons',	array( $this, 'generate_access_custom_button' ), 30, 2 );

			// Shortcodes button in Gutenberg classic TinyMCE editor blocks
			add_filter( 'mce_external_plugins', array( $this, 'mce_button_scripts' ), self::MCE_BUTTON_PRIORITY );
			add_filter( 'mce_buttons', array( $this, 'mce_button' ), self::MCE_BUTTON_PRIORITY );

			// Track whether dialogs are needed, and have been rendered in the footer
			$this->footer_dialogs_needed			= false;
			$this->footer_dialogs_added				= false;

			// Print the shortcodes dialogs in the footer,
			// both in frotend and backend, as long as there is anything to print.
			// Do it as late as possible because page builders tend to register their templates,
			// including native WP editors, hence shortcode buttons, in wp_footer:10.
			add_action( 'wp_footer',										array( $this, 'render_footer_dialogs' ), PHP_INT_MAX );
			add_action( 'admin_footer',										array( $this, 'render_footer_dialogs' ), PHP_INT_MAX );

			/**
			 * ---------------------
			 * Assets
			 * ---------------------
			 */

			// Register shortcodes dialogs assets
			add_action( 'init',											array( $this, 'register_assets' ) );
			add_action( 'wp_enqueue_scripts',							array( $this, 'frontend_enqueue_assets' ) );
			add_action( 'admin_enqueue_scripts',						array( $this, 'admin_enqueue_assets' ) );

			// Ensure that shortcodes dialogs assets re enqueued
			// both when using the Admin Bar item and when an Access button is on the page.
			add_action( 'otg_access_action_enforce_shortcodes_assets', 	array( $this, 'enforce_shortcodes_assets' ) );

		}

		/**
		 * Register assets needed for the Access button and dialogs.
		 *
		 * @since 2.3.0
		 */
		public function register_assets() {
			global $pagenow;
			wp_register_script( 'otg-access-shortcodes-gui-script', TACCESS_ASSETS_URL . '/js/shortcode.js', array( 'jquery', 'jquery-ui-dialog', 'icl_editor-script', 'underscore', 'toolset-event-manager' ), TACCESS_VERSION );
			$shortcodes_gui_translations = array(
				/* translators: Label for the Access button to insert the Access hortcodes into editors */
				'button_title' => __( 'Access', 'wpcf-access' ),
				/* translators: Label for the button in the dialog to insert the Access shortcode to editors */
				'insert_shortcode'			=> __( 'Insert shortcode', 'wpcf-access'),
				/* translators: Label for the button in the dialog to create an Access shortcode */
				'create_shortcode'			=> __( 'Create shortcode', 'wpcf-access' ),
				/* translators: Label for the button to close the dialog to insert an Access shortcode */
				'close'						=> __( 'Close', 'wpcf-access'),
				/* translators: Label for the button to cancel the dialog to create an Access shortcode */
				'cancel'					=> __( 'Cancel', 'wpcf-access' ),
				/* translators: Title of the dialog to insert an Access shortcode */
				'dialog_title'				=> __( 'Conditionally-displayed text', 'wpcf-access' ),
				/* translators: Title of the dialog that shows an Access created shortcode */
				'dialog_title_generated'	=> __( 'Generated shortcode', 'wpcf-access' ),
				'mce' => array(
					'access' => array(
						'button' => __( 'Access', 'wpcf-access' ),
					),
				),
				'pagenow'					=> $pagenow
			);
			wp_localize_script( 'otg-access-shortcodes-gui-script', 'otg_access_shortcodes_gui_texts', $shortcodes_gui_translations );
		}

		/**
		 * Enforce the Access assets in fronted pages where we know they are needed.
		 *
		 * @since 2.3.0
		 */
		public function frontend_enqueue_assets() {

			if ( $this->is_editor_button_disabled() ) {
				return;
			}

			if ( $this->is_frontend_editor_page() ) {
				$this->enforce_shortcodes_assets();
			}

		}

		/**
		 * Enforce the Access assets in backend pages where we know they are needed.
		 *
		 * @since 2.3.0
		 */
		public function admin_enqueue_assets() {

			if ( $this->is_editor_button_disabled() ) {
				return;
			}

			if (
				$this->admin_bar_item_registered
				|| $this->is_admin_editor_page()
			) {
				$this->enforce_shortcodes_assets();
			}

		}

		/**
		 * Enforce the Access assets,
		 * primarily fired when an Access button is printed or the Admin Bar item is registered.
		 *
		 * @note Style assets might be enqueued too late if the Admin Bar item is not needed,
		 * or in the frontend when editors are printed too late.
		 *
		 * @since 2.3.0
		 */
		public function enforce_shortcodes_assets() {

			wp_enqueue_script( 'otg-access-shortcodes-gui-script' );
			wp_enqueue_style( 'toolset-common' );
			wp_enqueue_style( 'wp-jquery-ui-dialog' );
			wp_enqueue_style( 'toolset-dialogs-overrides-css' );

			do_action( 'otg_action_otg_enforce_styles' );

		}

		/**
		 * Register the Access entry in the Admin Bar item.
		 *
		 * @since 2.3.0
		 */
		public function register_access_shortcode_generator( $registered_sections ) {

			if ( $this->is_editor_button_disabled() ) {
				return $registered_sections;
			}

			$this->footer_dialogs_needed = true;
			$this->admin_bar_item_registered = true;
			$this->enforce_shortcodes_assets();
			$registered_sections[ 'access' ] = array(
				'id'		=> 'access',
				'title'		=> __( 'Access', 'wpcf-access' ),
				'href'		=> '#access_shortcodes',
				'parent'	=> 'toolset-shortcodes',
				'meta'		=> 'js-otg-access-shortcode-generator-node'
			);

			return $registered_sections;

		}

		/**
		 * Generate the Access button on native editors.
		 *
		 * @since 2.3.0
		 */
		public function generate_access_button( $editor ) {
			if (
				empty( $editor )
				|| strpos( $editor, 'acf-field' ) !== false
				|| strpos( $editor, 'acf-editor' ) !== false
			) {
				return;
			}

			global $post;
			if (
				isset( $post )
				&& ! empty( $post )
				&& isset( $post->post_type )
				&& 'attachment' == $post->post_type
			) {
				return;
			}

			if ( $this->is_editor_button_disabled() ) {
				return;
			}

			$this->footer_dialogs_needed = true;
			$this->enforce_shortcodes_assets();
			?>
            <span class="button js-wpcf-access-editor-button" data-editor="<?php echo esc_attr( $editor ); ?>">
				<i class="icon-access-logo ont-icon-18 ont-color-gray"></i>
				<?php echo __( 'Access', 'wpcf-access' ); ?>
			</span>
			<?php
		}

		/**
		 * Generate the Access button on custom editors.
		 *
		 * Usually, custom editor toolbars expect atual buttons wrapped in <li></li> HTML tags.
		 *
		 * @since 2.3.0
		 */
		public function generate_access_custom_button( $editor, $source = '' ) {
			if ( empty( $editor ) ) {
				return;
			}

			if ( $this->is_editor_button_disabled() ) {
				return;
			}

			$this->footer_dialogs_needed = true;
			$this->enforce_shortcodes_assets();
			?>
            <li>
                <button class="button-secondary js-wpcf-access-editor-button" data-editor="<?php echo esc_attr( $editor ); ?>">
                    <i class="icon-access-logo ont-icon-18"></i>
					<?php echo __( 'Access', 'wpcf-access' ); ?>
                </button>
            </li>
			<?php
		}

		/**
		 * Add a TinyMCE plugin script for the shortcodes generator button.
		 *
		 * Note that this only gets registered when editing a post with Gutenberg.
		 *
		 * @param array $plugin_array
		 * @return array
		 * @since 2.6
		 */
		public function mce_button_scripts( $plugin_array ) {
			if (
				! $this->is_blocks_editor_page()
				|| $this->is_editor_button_disabled()
			) {
				return $plugin_array;
			}
			// Add the shortcodes assets as they might be needed by some party.
			$this->gutenberg_enqueue_assets();

			/*
			 * Note that we are not including this MCE button because
			 * the classic editor block visual mode does not accept
			 * HTML in shortcodes content: we will include a custom Access block instead.
			 * Keep this for reference in case we use it later.
			 */
			//$this->mce_view_templates_needed = true;
			//$plugin_array["toolset_add_access_shortcode_button"] = TACCESS_ASSETS_URL . '/js/mce/button/access.js?ver=' . TACCESS_VERSION;
			//$plugin_array["toolset_access_shortcode_view"] = TACCESS_ASSETS_URL . '/js/mce/view/access.js?ver=' . TACCESS_VERSION;

			return $plugin_array;
		}

		/**
		 * Add a TinyMCE button for the shortcodes generator button.
		 *
		 * Note that this only gets registered when editing a post with Gutenberg.
		 *
		 * @param array $buttons
		 * @return array
		 * @since 2.6
		 */
		public function mce_button( $buttons ) {
			if (
				! $this->is_blocks_editor_page()
				|| $this->is_editor_button_disabled()
			) {
				return $buttons;
			}
			// Add the shortcodes assets as they might be needed by some party.
			$this->gutenberg_enqueue_assets();
			/*
			 * Note that we are not including this MCE button because
			 * the classic editor block visual mode does not accept
			 * HTML in shortcodes content: we will include a custom Access block instead.
			 * Keep this for reference in case we use it later.
			 */
			//array_push( $buttons, "toolset_access_shortcodes" );
			//$classic_editor_block_toolbar_icon_style = ".ont-icon-block-classic-toolbar::before {position:absolute;top:1px;left:2px;}";
			//wp_add_inline_style(
			//	Toolset_Assets_Manager::STYLE_TOOLSET_COMMON,
			//	$classic_editor_block_toolbar_icon_style
			//);

			return $buttons;
		}

		/**
		 * Enforce the shortcodes generator assets when using a Gutenberg editor.
		 *
		 * @since 2.6
		 */
		public function gutenberg_enqueue_assets() {
			$this->footer_dialogs_needed = true;
			$this->enforce_shortcodes_assets();
		}

		/**
		 * Adds the HTML markup for the shortcode dialogs to both
		 * backend and frontend footers, as late as possible,
		 * because page builders tend to register their templates,
		 * including native WP editors, hence shortcode buttons, in wp_footer:10.
		 *
		 * @since 2.3.0
		 */
		public function render_footer_dialogs() {
			if (
				$this->footer_dialogs_needed
				&& ! $this->footer_dialogs_added
			) {
				global $wp_roles;
				$roles = $wp_roles->roles;
				$this->footer_dialogs_added = true;
				?>
                <div id="wpcf-access-shortcodes-dialog-tpl" style="display: none;">
                    <form id="access-shortcodes-form">

                        <h3><?php echo __('Select roles: ', 'wpcf-access'); ?></h3>
                        <ul class="toolset-mightlong-list">
							<?php
							foreach ( $roles as $levels => $roles_data ) {
								echo '<li>'
								     . '<label>'
								     . '<input type="checkbox" class="js-wpcf-access-list-roles" value="' . esc_attr( $roles_data['name'] ) . '" /> '
								     . esc_html( $roles_data['name'] )
								     . '</label>'
								     . '</li>';
							}
							?>
                            <li>
                                <label>
                                    <input type="checkbox" class="js-wpcf-access-list-roles" value="Guest" />
									<?php echo __('Guest', 'wpcf-access'); ?>
                                </label>
                            </li>
                        </ul>

                        <h3><?php echo __('Enter the text for conditional display: ', 'wpcf-access'); ?></h3>
                        <p>
                            <textarea class="otg-access-shortcode-conditional-message js-wpcf-access-conditional-message" rows="6" style="width: 100%;height: 100px;"></textarea>
                            <small><?php echo __('You will be able to add other fields and apply formatting after inserting this text', 'wpcf-access'); ?></small>
                        </p>

                        <h3><?php echo __('Will these roles see the text? ', 'wpcf-access'); ?></h3>
                        <p>
                            <label>
                                <input type="radio" class="js-wpcf-access-shortcode-operator" name="wpcf-access-shortcode-operator" value="allow" /> <?php echo __('Only users belonging to these roles will see the text', 'wpcf-access'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="radio" class="js-wpcf-access-shortcode-operator" name="wpcf-access-shortcode-operator" value="deny" /> <?php echo __('Everyone except these roles will see the text', 'wpcf-access'); ?>
                            </label>
                            <br>
                        </p>

                        <h3><?php echo __('Output format ', 'wpcf-access'); ?></h3>
                        <p>
                            <label>
                                <input type="checkbox" class="js-wpcf-access-shortcode-format" name="wpcf-access-shortcode-format" value="raw" /> <?php echo __('Display the text without any formatting', 'wpcf-access'); ?>
                            </label>
                            <br>
                        </p>

                    </form>
                </div>
				<?php
			}

			if ( $this->mce_view_templates_needed ) {
				$this->render_mce_view_templates();
			}
		}

		/**
		 * Generate the templates for the Access shortcode MCE view.
		 *
		 * @since 2.6
		 */
		private function render_mce_view_templates() {
			?>
            <script type="text/html" id="tmpl-toolset-shortcode-toolset_access-mce-banner">
                <div class="toolset-access-shortcode-mce-view"
                     data-tag="{{ data.tag }}"
                     data-keymap="{{ data.keymap }}"
                <#
                _.each( data.attributes, function( value, key, list ) {
                #>
                data-{{ key }}="{{ value }}"
                <#
                });
                #>
                contenteditable="false">
                <span contenteditable="false" class="toolset-access-shortcode-mce-view-content js-toolset-access-shortcode-mce-view-content" style="display:none">
						{{{ data.content }}}
					</span>
                <i class="ont-icon-block-classic-mce-view icon-access-logo ont-icon-25 ont-color-orange"></i>
				<?php
				echo __( 'Access conditional output', 'wpcf-access' );
				?>
                </div>
            </script>
			<?php
		}

		/**
		 * Check whether the shortcodes generator button should not be included in editors.
		 *
		 * @return bool
		 * @since 2.5.2
		 */
		public function is_editor_button_disabled() {

			// General shared filter for all Toolset buttons
			if ( ! apply_filters( 'toolset_editor_add_form_buttons', true ) ) {
				return true;
			}
			$access_roles = \OTGS\Toolset\Access\Models\UserRoles::get_instance();
			$current_role = $access_roles->get_current_user_roles( true );

			/**
			 * Legacy filter that blacklists roles from showing the button.
			 * Despite its name, it does blacklist roles.
			 * Yes, really.
			 *
			 * Returning an array of roles here will disable the button for those roles.
			 *
			 * @since unknown
			 * @since 2.5.2 Returning false to this filter will disable the button globally.
			 */
			$hide_access_button = apply_filters( 'toolset_editor_add_access_button', true );
			if ( false === $hide_access_button ) {
				return true;
			}
			if ( is_array( $hide_access_button ) ) {
				if ( in_array( $current_role, $hide_access_button, true ) ) {
					return true;
				}
			}

			/**
			 * Blacklist roles from showing the Access editor button.
			 *
			 * @since 2.5.2
			 */
			$blacklisted_roles = apply_filters( 'toolset_editor_access_button_disable_by_role', array() );
			if ( in_array( $current_role, $blacklisted_roles, true ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Generate a dummy dialog for the shortcode generation response on the Admin Bar item.
		 *
		 * @since 2.3.0
		 */
		public function display_shortcodes_target_dialog() {
			parent::display_shortcodes_target_dialog();
			if ( $this->admin_bar_item_registered ) {
				?>
                <div class="toolset-dialog-container">
                    <div id="otg-access-shortcode-generator-target-dialog" class="toolset-shortcode-gui-dialog-container js-otg-access-shortcode-generator-target-dialog">
                        <div class="toolset-dialog">
                            <p>
								<?php echo __( 'This is the generated shortcode, based on the settings that you have selected:', 'wpcf-access' ); ?>
                            </p>
                            <span id="otg-access-shortcode-generator-target" style="font-family:monospace;display:block;padding:5px;background-color:#ededed"></span>
                            <p>
								<?php echo __( 'You can now copy and paste this shortcode anywhere you want.', 'wpcf-access' ); ?>
                            </p>
                        </div>
                    </div>
                </div>
				<?php
			}
		}

	}

}

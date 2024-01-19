<?php
add_filter( 'set-screen-option', array( 'CRED_Helper', 'setScreenOptions' ), 10, 3 );

/**
 * Helper Class
 *
 */
final class CRED_Helper {

	private static $cred_wpml_option = '_cred_cred_wpml_active';
	private static $caps = null;
	public static $currentPage = null;
	public static $currentUPage = null;

	/**
	 * Holds data regarding the current page if it's relationships
	 *
	 * @var null|object
	 */
	public static $current_relationships_page = null;

	public static $current_form_fields = null;

	/**
     * Get Extra setting to debug icl_get_extra_debug_info filter callback
     *
	 * @param array $extra_debug
	 *
	 * @return mixed
	 */
	public static function getExtraDebugInfo( $extra_debug ) {
		$sm = CRED_Loader::get( 'MODEL/Settings' );
		$extra_debug['CRED'] = $sm->getSettings();
		if ( isset( $extra_debug['CRED']['recaptcha'] ) ) {
			unset( $extra_debug['CRED']['recaptcha'] );
		}

		return $extra_debug;
	}

	/**
	 * @param array $ids
	 *
	 * @return array
	 */
	public static function add_toolset_promotion_screen_id( $ids ) {
		// Old admin pages, DEPRECATED
		$ids[] = 'toplevel_page_CRED_Forms';
		$ids[] = 'cred_page_CRED_Forms';
		$ids[] = 'toplevel_page_CRED_User_Forms';
		$ids[] = 'cred_page_CRED_User_Forms';
		// New admin pages
		$ids[] = 'toolset_page_CRED_Forms';
		$ids[] = 'toolset_page_CRED_User_Forms';
		$ids[] = 'toolset_page_CRED_Fields';
		$ids[] = 'toolset_page_CRED_User_Fields';

		return $ids;
	}

	/**
	 * @deprecated since version 1.9
	 */
	public static function FormsMenuPage() {
		CRED_Loader::load( 'VIEW/embedded-forms' );
	}

	/**
	 * @deprecated since version 1.9
	 */
	public static function UserFormsMenuPage() {
		CRED_Loader::load( 'VIEW/embedded-user-forms' );
	}

	// TODO There is duplicated code here and in setupAdmin!!!!
	public static function setJSAndCSS() {
		// setup js, css assets
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'onAdminEnqueueScripts' ) );

		// add custom js on certain pages
		add_action( 'admin_head-post.php', array( __CLASS__, 'jsForCredCustomPost' ) );
		add_action( 'admin_head-post-new.php', array( __CLASS__, 'jsForCredCustomPost' ) );

		if ( isset( $_GET['page'] ) ) {
			$page = $_GET['page'];

			$set_on_pages = array( 'view-archives-editor', 'views-editor' );

			/**
			 * Get admin page slugs where Toolset Forms assets, used in form create and edit admin pages,
			 * should be enueued.
			 *
			 * @param array $set_on_pages Array of page slugs.
			 *
			 * @since 1.3.6.1
			 */
			$set_on_pages = apply_filters( 'cred_get_custom_pages_to_load_assets', $set_on_pages );

			if ( in_array( $page, $set_on_pages ) ) {
				add_action( 'admin_head', array( __CLASS__, 'jsForCredCustomPost' ) );
			}
		}
	}

	public static function getCurrentPostType() {
		if ( is_admin() ) {
			if ( isset( $_REQUEST['post_type'] ) ) {
				return is_array( $_REQUEST['post_type'] ) ? cred_sanitize_array( $_REQUEST['post_type'] ) : sanitize_text_field( $_REQUEST['post_type'] );
			} else {
				if ( isset( $_REQUEST['action'] ) && isset( $_REQUEST['post'] ) ) {
					$postid = intval( $_REQUEST['post'] );

					return get_post_type( $postid );
				} else {
					return null;
				}
			}
		}
	}

	/**
	 * admin_enqueue_scripts action callback
	 */
	public static function onAdminEnqueueScripts() {
		// On what admin pages should Toolset Forms assets be loaded?
		$set_on_pages = array(
			'view-archives-editor',
			'views-editor',
			'CRED_User_Forms',
			'CRED_Forms',
			'CRED_Fields',
			'CRED_User_Fields',
			'CRED_Settings',
			'toolset-settings',
			'CRED_Help',
		);

		// Filter description is placed in setJSAndCSS().
		$set_on_pages = apply_filters( 'cred_get_custom_pages_to_load_assets', $set_on_pages );

		// setup css js
		// determine current admin page
		self::getAdminPage( array(
			'post_type' => CRED_FORMS_CUSTOM_POST_NAME,
			'base' => 'admin.php',
			'pages' => $set_on_pages,
		) );

		self::getAdminPage( array(
			'post_type' => CRED_USER_FORMS_CUSTOM_POST_NAME,
			'base' => 'admin.php',
			'pages' => $set_on_pages,
		) );

		CRED_Loader::loadAsset( 'STYLE/cred_utility_css', 'cred_utility_css', false, false );
		wp_enqueue_style( 'cred_utility_css' );

		do_action( 'toolset_enqueue_styles', array( 'toolset-notifications-css' ) );

		if ( ( self::$currentPage->isCustomPostEdit || self::$currentPage->isCustomPostNew ) ||
			self::$currentUPage->isCustomPostEdit || self::$currentUPage->isCustomPostNew
		) {
			wp_dequeue_script( 'autosave' );
			wp_deregister_script( 'autosave' );

			global $post;
			// add form saved admin message
			if ( isset( $post ) ) {
				if ( $post->post_type == CRED_FORMS_CUSTOM_POST_NAME ) {
					$form_validation = CRED_Loader::get( 'MODEL/Forms' )->getFormCustomField( $post->ID, 'validation' );
					if (
						( isset( $_GET['message'] ) && '4' == $_GET['message'] ) &&
						( isset( $form_validation ) && isset( $form_validation['fail'] ) && $form_validation['fail'] )
					) {
						$form_saved_and_valid = false;
						add_action( 'admin_notices', array( __CLASS__, 'formNotValidNotice' ), 10 );
						// force opne metabox if validation issues
						add_filter( 'postbox_classes_' . CRED_FORMS_CUSTOM_POST_NAME . "_crednotificationdiv", array(
							__CLASS__,
							'forceMetaboxOpen',
						) );
					} elseif (
						( isset( $_GET['message'] ) && ('4' == $_GET['message'] || '1' == $_GET['message']) ) &&
						( ! isset( $form_validation ) || ! isset( $form_validation['fail'] ) || ! $form_validation['fail'] )
					) {

					    $form_model = CRED_Loader::get( 'MODEL/Forms' );
						CRED_Helper::$current_form_fields = $form_model->getFormCustomFields( $post->ID, array(
							'form_settings',
						) );
						$form_saved_and_valid = true;
						if(CRED_Helper::$current_form_fields['form_settings']->form['type'] == 'new'){
							add_action( 'admin_notices', array( __CLASS__, 'add_form_valid_notice' ), 10 );
						} else {
							add_action( 'admin_notices', array( __CLASS__, 'edit_form_valid_notice' ), 10 );
                        }
					}
				}
				if ( $post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME ) {
					$form_validation = array( "success" => 1 );
					if (
						( isset( $_GET['message'] ) && '4' == $_GET['message'] ) &&
						( isset( $form_validation ) && isset( $form_validation['fail'] ) && $form_validation['fail'] )
					) {
						$form_saved_and_valid = false;
						add_action( 'admin_notices', array( __CLASS__, 'formNotValidNotice' ), 10);
						// force opne metabox if validation issues
						add_filter( 'postbox_classes_' . CRED_USER_FORMS_CUSTOM_POST_NAME . "_crednotificationdiv", array(
							__CLASS__,
							'forceMetaboxOpen',
						) );
					} elseif (
						( isset( $_GET['message'] ) && ('4' == $_GET['message'] || '1' == $_GET['message']) ) &&
						( ! isset( $form_validation ) || ! isset( $form_validation['fail'] ) || ! $form_validation['fail'] )
					) {
						$form_model = CRED_Loader::get( 'MODEL/UserForms' );
						CRED_Helper::$current_form_fields = $form_model->getFormCustomFields( $post->ID, array(
							'form_settings',
							'notification',
						) );
						$form_saved_and_valid = true;
						if(CRED_Helper::$current_form_fields['form_settings']->form['type'] == 'new'){
							add_action( 'admin_notices', array( __CLASS__, 'add_user_form_valid_notice' ), 10 );
						} else {
							add_action( 'admin_notices', array( __CLASS__, 'edit_user_form_valid_notice' ), 10 );
						}

						$show_notification_alert = false;
						$correct_notification_set = false;
						//at least 1 autogeneration field is set
						if ( /* $form_fields['form_settings']->form['autogenerate_username_scaffold'] == 1 ||
                          $form_fields['form_settings']->form['autogenerate_nickname_scaffold'] == 1 || */
							CRED_Helper::$current_form_fields['form_settings']->form['autogenerate_password_scaffold'] == 1
						) {
							//checking each notification
							foreach ( CRED_Helper::$current_form_fields['notification']->notifications as $n => $notification ) {
								if ( isset( $notification['event'] ) ) {
									if (
										( isset( $notification['event']['type'] ) && $notification['event']['type'] == 'form_submit' ) &&
										( isset( $notification['event']['post_status'] ) && $notification['event']['post_status'] == 'publish' ) &&
										( isset( $notification['to']['mail_field']['to_type'] ) && $notification['to']['mail_field']['to_type'] == 'to' ) &&
										( isset( $notification['to']['mail_field']['address_field'] ) && $notification['to']['mail_field']['address_field'] == 'user_email' )
									) {
										//at least body must contains placeholder
										if ( isset( $notification['mail']['body'] ) &&
											( CRED_Helper::$current_form_fields['form_settings']->form['autogenerate_password_scaffold'] == 1 && preg_match( '/%%USER_PASSWORD%%/', $notification['mail']['body'] ) )
										) {
											foreach ( $notification['to']['type'] as $m => $type ) {
												//at least email notification to the user must be set
												if ( $type == 'mail_field' ) {
													$correct_notification_set = true;
													break;
												}
											}
										}
									}
								}
								if ( $correct_notification_set ) {
									break;
								}
							}
							$show_notification_alert = ! $correct_notification_set;
						}

						if ( $show_notification_alert ) {
							add_action( 'admin_notices', array( __CLASS__, 'userFormAlertNotice' ), 10 );
						}
						//#########################################################################################################################
					}

					$cred_commerce = get_post_meta( $post->ID, '_cred_commerce', true );
					if ( isset( $cred_commerce ) && isset( $cred_commerce['enable'] ) && $cred_commerce['enable'] == 1 ) {
						$woocommerce_enable_signup_and_login_from_checkout = get_option( 'woocommerce_enable_signup_and_login_from_checkout' );
						if ( $woocommerce_enable_signup_and_login_from_checkout == 'yes' ) {
							add_action( 'admin_notices', array( __CLASS__, 'userFormCommerceAlertNotice' ), 12 );
						}
					}
				}
			}
		}

		if ( apply_filters( 'cred-register_cred_editor_scripts_and_styles', ( self::$currentPage->isPostEdit ||
				self::$currentPage->isPostNew ||
				self::$currentPage->isCustomAdminPage ) ||
			( self::$currentUPage->isPostEdit ||
				self::$currentUPage->isPostNew ||
				self::$currentUPage->isCustomAdminPage ) ) ) {

			if ( ( self::$currentPage->isCustomPostEdit || self::$currentPage->isCustomPostNew ) ||
				( self::$currentUPage->isCustomPostEdit || self::$currentUPage->isCustomPostNew )
			) {
				wp_enqueue_script( 'cred_cred_dev' );
				wp_enqueue_script( 'cred_cred_post_dev' );
				wp_enqueue_script( 'cred_wizard_dev' );
				wp_enqueue_style( 'cred_cred_style_dev' );
				wp_enqueue_style( 'cred_wizard_general_style' );
				// WordPress 4.0 compatibility: remove all the new fancy editor enhancements that break the highlighting and toolbars
				wp_dequeue_script( 'editor-expand' );
			} else {
				wp_enqueue_script( 'cred_cred_post_dev' );
				wp_enqueue_style( 'cred_cred_style_dev' );
			}

			// enqueue them with dependencies
			wp_enqueue_style( 'cred_cred_style' );
			wp_enqueue_script( 'cred_cred' );

			if ( isset( $post ) ) {
				$fm = CRED_Loader::get( 'MODEL/UserForms' );
				CRED_Helper::$current_form_fields = $fm->getFormCustomFields( $post->ID, array(
					'form_settings',
					'notification',
				) );
			}
			wp_enqueue_script( 'cred_settings' );
		}
	}

	/**
	 * admin_notice action callback
	 */
	public static function formNotValidNotice() {
		?>
        <div class="cred-notification cred-error">
        <p>
            <i class="fa fa-warning"></i>
			<?php _e( 'The form was saved, but some settings are not complete, please review the alert icons below', 'wp-cred' ); ?>
        </p>
        </div><?php
	}

	/**
	 * @param string $form_type
	 * @param string $post_type
	 */
	public static function render_form_creation_notice( $form_type, $post_type ) {

		switch( $form_type ) {
			case 'edit':
				$help_link = CRED_CRED::$help['cred_inserting_edit_links']['link'];
				$help_text = CRED_CRED::$help['cred_inserting_edit_links']['text'];
				break;
			default:
				if ( 'post' == $post_type  ) {
					$help_link = CRED_CRED::$help['add_post_forms_to_site']['link'];
					$help_text = CRED_CRED::$help['add_post_forms_to_site']['text'];
				} else {
					$help_link = CRED_CRED::$help['add_user_forms_to_site']['link'];
					$help_text = CRED_CRED::$help['add_user_forms_to_site']['text'];
				}
				break;
		}

		$message = ( $post_type == 'post' ? __( 'The post form was successfully saved.', 'wp-cred' ) : __( 'The user form was successfully saved.', 'wp-cred' ) );
		$help_link_html = '<a target="_blank" href="' . $help_link . '">' . $help_text . '</a>';
		$notice = new Toolset_Admin_Notice_Success( 'render_form_creation_notice', $message . '&nbsp;' . $help_link_html );
		$notice->render();
    }

    public static function add_form_valid_notice() {
	    self::render_form_creation_notice('new', 'post');
	}

	public static function edit_form_valid_notice() {
		self::render_form_creation_notice('edit', 'post');
	}

	public static function add_user_form_valid_notice() {
		self::render_form_creation_notice('add', 'user');
	}

	public static function edit_user_form_valid_notice() {
		self::render_form_creation_notice('edit', 'user');
	}

	/**
	 * admin_notice action callback
	 */
	public static function userFormAlertNotice() {
		?>
        <div class="cred-notification cred-error">
        <p>
            <i class="fa fa-warning"></i>
			<?php printf( __( 'This form will auto-generate the password for the new user.<br>In order for the user to receive the password, you need to create a notification which will include that password.<br>%s', 'wp-cred' ), '<a target="_blank" href="' . CRED_CRED::$help['autogeneration_notification_missing_alert']['link'] . '">' . CRED_CRED::$help['autogeneration_notification_missing_alert']['text'] . '</a>' ); ?>
        </p>
        </div><?php
	}

	/**
	 * admin_notice action callback
	 */
	public static function userFormCommerceAlertNotice() {
		//You selected to 'Enable registration on the Checkout' in WooCommerce. This means that WooCommerce will automatically create users when paying. You are also use Toolset Forms to create new users, so for each registration, your site will have two users. To avoid this, go to the WooCommerce settings and disable 'Enable registration on the Checkout'.
		$woocommerce_settings = admin_url( 'admin.php' ) . '?page=wc-settings&tab=account';
		?>
        <div class="cred-notification cred-error">
        <p>
            <i class="fa fa-warning"></i>
			<?php printf( __( 'You selected to \'Enable registration on the Checkout\' in WooCommerce.<br>This means that WooCommerce will automatically create users when paying.<br>You are also using Toolset Forms to create new users, so for each registration, your site will have two users.<br>To avoid this, go to the %s and disable \'Enable registration on the Checkout\'.', 'wp-cred' ), '<a target="_blank" href="' . $woocommerce_settings . '">WooCommerce settings</a>' ); ?>
        </p>
        </div><?php
	}

	/**
	 * @param array $custom_data
	 *
	 * @return object
	 */
	public static function getAdminPage( $custom_data = array() ) {
		global $pagenow, $post, $post_type;

		$page_data = (object) array(
			'post_type' => $custom_data['post_type'],
			'isAdmin' => false,
			'isAdminAjax' => false,
			'isPostEdit' => false,
			'isPostNew' => false,
			'isCustomPostEdit' => false,
			'isCustomPostNew' => false,
			'isCustomAdminPage' => false,
		);

		if ( ! is_admin() ) {
			static::save_current_page( $custom_data['post_type'], $page_data );
			return $page_data;
		}

		$page_data->isAdmin = true;
		$page_data->isPostEdit = (bool) ( 'post.php' === $pagenow );
		$page_data->isPostNew = (bool) ( 'post-new.php' === $pagenow );
		if ( ! empty( $custom_data ) ) {
			$custom_post_type = isset( $custom_data['post_type'] ) ? $custom_data['post_type'] : false;
			$edit_custom_post_type = $page_data->isPostEdit && isset ( $_GET['post'] )  ? get_post_type( $_GET['post'] ) : false;

			$page_data->isCustomPostEdit = (bool) ( $page_data->isPostEdit && $custom_post_type === $edit_custom_post_type );
			$page_data->isCustomPostNew = (bool) ( $page_data->isPostNew && isset( $_GET['post_type'] ) && $custom_post_type === $_GET['post_type'] );

			$custom_admin_base = isset( $custom_data['base'] ) ? $custom_data['base'] : false;
			$custom_admin_pages = isset( $custom_data['pages'] ) ? (array) $custom_data['pages'] : array();
			$page_data->isCustomAdminPage = (bool) ( $custom_admin_base === $pagenow && isset( $_GET['page'] ) && in_array( $_GET['page'], $custom_admin_pages, true ) );
			$page_data->hide_wpml_switcher = $page_data->isCustomAdminPage || $page_data->isCustomPostEdit || $page_data->isCustomPostNew;
		}

		static::save_current_page($custom_data['post_type'], $page_data );
		return $page_data;
	}

	public static function save_current_page($post_type, $page_data) {
		if ( $post_type === CRED_FORMS_CUSTOM_POST_NAME ) {
						self::$currentPage = $page_data;
					}
		if ( $post_type === CRED_USER_FORMS_CUSTOM_POST_NAME ) {
			self::$currentUPage = $page_data;
		}

		if ( $post_type === CRED_RELATIONSHIP_FORMS_CUSTOM_POST_NAME ) {
			self::$current_relationships_page = $page_data;
		}
	}

	/**
	 * js used in form create and edit admin pages
	 */
	public static function jsForCredCustomPost() {
		global $post;
		$current_post_type = (
			isset( $_GET['post_type'] )
				? sanitize_text_field( $_GET['post_type'] )
				: ( isset( $post->post_type ) ? $post->post_type : '' )
		);

		if ( apply_filters( 'cred-cred_wizard_scripts_run', ( self::$currentPage->isCustomPostEdit || self::$currentPage->isCustomPostNew ) ||
			( self::$currentUPage->isCustomPostEdit || self::$currentUPage->isCustomPostNew ) ) ) {
			$newform = false;
			if (
				(
					self::$currentPage->isCustomPostNew || self::$currentUPage->isCustomPostNew
				) && (
					CRED_FORMS_CUSTOM_POST_NAME == $current_post_type || CRED_USER_FORMS_CUSTOM_POST_NAME == $current_post_type
				)
			) {
				$newform = true;
			}

			$sm = CRED_Loader::get( 'MODEL/Settings' );
			$settings = $sm->getSettings();
			$fm = ( CRED_USER_FORMS_CUSTOM_POST_NAME == $current_post_type ) ? CRED_Loader::get( 'MODEL/UserForms' ) : CRED_Loader::get( 'MODEL/Forms' );
			$form_fields = $fm->getFormCustomFields( $post->ID, array(
				'form_settings',
				'notification',
				'extra',
				'wizard',
			) );

			$add_wizard = false;
			if ( $settings['wizard'] ) {
				$wizard = ( $post && isset( $form_fields['wizard'] ) ) ? $form_fields['wizard'] : 0;
				if ( $wizard == false || $wizard == null || $wizard == '-1' ) {
					$wizard = - 1;
				}
				$wizard = intval( $wizard );
				if ( 0 != $wizard && $newform ) {
					$wizard = 0;
				}

				if ( 0 <= $wizard ) {
					$add_wizard = true;
				}

				if ( $add_wizard ) // include wizard
				{
					wp_enqueue_script( 'cred_wizard' );
				}
			}

			// add these to be same as template input fields
			// format the data for the view/model data
			$form_fields['form'] = isset( $form_fields['form_settings']->form ) ? $form_fields['form_settings']->form : array();
			$form_fields['post'] = isset( $form_fields['form_settings']->post ) ? $form_fields['form_settings']->post : array();
			?>
            <script type='text/javascript'>
                /*<![CDATA[ */
                var _credFormData =<?php echo json_encode( $form_fields ); ?>;
                (function ($) {
                    $(function () {
                        cred_cred.forms();
						<?php if ( $add_wizard ) { ?>
                        cred_wizard.init(<?php echo $wizard; ?>, <?php
							if ( $newform ) {
								echo 'true';
							} else {
								echo 'false';
							}
							?>);
						<?php } else { ?>
							Toolset.hooks.doAction( 'cred_editor_init_top_bar' );
						<?php } ?>
                    });
                })(jQuery);
                /*]]>*/
            </script>
			<?php
		}
	}

	/**
	 * @param $id
	 * @param null $type
	 *
	 * @return mixed
	 */
	public static function getLocalisedID( $id, $type = null ) {
		static $_cache = array();

		if ( ! isset( $_cache[ $id ] ) ) {
			/*
              WPML localised ID
              function icl_object_id($element_id, $element_type='post',
              $return_original_if_missing=false, $ulanguage_code=null)
             */
			if ( function_exists( 'icl_object_id' ) ) {
				if ( null === $type ) {
					$type = get_post_type( $id );
				}
				$loc_id = icl_object_id( $id, $type, true );
			} else {
				$loc_id = $id;
			}
			$_cache[ $id ] = $loc_id;
		}

		return $_cache[ $id ];
	}

	/**
	 * @param $data
	 */
	public static function localizeFormOnSave( $data ) {
		// if WMPL string is active, process form content for strings in shortcode attributes for translation
		if ( self::check_wpml_string() ) {
			$cfp = CRED_Loader::get( 'CLASS/Form_Translator' );
			$cfp->processForm( $data );
		} else {
			update_option( self::$cred_wpml_option, 'no' );
		}
	}

	public static function localizeForms() {
		// stub wpml-string shortcode
		if ( ! self::check_wpml_string() ) {
			// WPML string translation is not active
			// Add our own do nothing shortcode
			add_shortcode( 'wpml-string', 'cred_stub_wpml_string_shortcode' );
		} else {
			$wpml_was_active = get_option( self::$cred_wpml_option );
			// if changes before wpml activated, re-process all forms
			if ( $wpml_was_active && $wpml_was_active == 'no' ) {
				$cfp = CRED_Loader::get( 'CLASS/Form_Translator' );
				$cfp->processAllForms();
				update_option( self::$cred_wpml_option, 'yes' );
			}
		}
	}

	/**
	 * Setup necessary DB model settings
	 */
	public static function prepareDB() {
		$forms_model = CRED_Loader::get( 'MODEL/Forms' );
		$forms_model->prepareDB();

		$user_forms_model = CRED_Loader::get( 'MODEL/UserForms' );
		$user_forms_model->prepareDB();

		$settings_model = CRED_Loader::get( 'MODEL/Settings' );
		$settings_model->prepareDB();
	}

	// add custom classes to our metaboxes, so they can be handled as needed
	public static function forceMetaboxOpen( $classes ) {
		return array_diff( $classes, array( 'closed' ) );
	}

	/**
	 * @param $status
	 * @param $option
	 * @param $value
	 *
	 * @return mixed
	 */
	public static function setScreenOptions( $status, $option, $value ) {
		if ( 'cred_per_page' == $option ) {
			return $value;
		}
		return $status;
	}

	public static function setupExtraHooks() {
		// setup module manager hooks and actions
		if ( defined( 'MODMAN_PLUGIN_NAME' ) ) {
			$section_id = _CRED_MODULE_MANAGER_KEY_;
			$section_id2 = _CRED_MODULE_MANAGER_USER_KEY_;

			add_filter( 'wpmodules_register_sections', array( __CLASS__, 'register_modules_cred_sections' ), 30, 1 );

			add_filter( 'wpmodules_register_items_' . $section_id, array(
				__CLASS__,
				'register_modules_cred_items',
			), 10, 1 );
			add_filter( 'wpmodules_export_items_' . $section_id, array(
				__CLASS__,
				'export_modules_cred_items',
			), 10, 2 );
			add_filter( 'wpmodules_import_items_' . $section_id, array(
				__CLASS__,
				'import_modules_cred_items',
			), 10, 3 );
			add_filter( 'wpmodules_items_check_' . $section_id, array( __CLASS__, 'modules_cred_items_exist' ), 10, 1 );

			add_filter( 'wpmodules_register_sections', array(
				__CLASS__,
				'register_modules_cred_user_sections',
			), 30, 1 );

			add_filter( 'wpmodules_register_items_' . $section_id2, array(
				__CLASS__,
				'register_modules_cred_user_items',
			), 10, 1 );
			add_filter( 'wpmodules_export_items_' . $section_id2, array(
				__CLASS__,
				'export_modules_cred_user_items',
			), 10, 2 );
			add_filter( 'wpmodules_import_items_' . $section_id2, array(
				__CLASS__,
				'import_modules_cred_user_items',
			), 10, 3 );
			add_filter( 'wpmodules_items_check_' . $section_id2, array(
				__CLASS__,
				'modules_cred_user_items_exist',
			), 10, 1 );

			//Module manager: Hooks for adding plugin version

			/* Export */
			add_filter( 'wpmodules_export_pluginversions_' . $section_id, array(
				__CLASS__,
				'modules_cred_pluginversion',
			) );
			/* Import */
			add_filter( 'wpmodules_import_pluginversions_' . $section_id, array(
				__CLASS__,
				'modules_cred_pluginversion',
			) );

			/* Export */
			add_filter( 'wpmodules_export_pluginversions_' . $section_id2, array(
				__CLASS__,
				'modules_cred_pluginversion',
			) );
			/* Import */
			add_filter( 'wpmodules_import_pluginversions_' . $section_id2, array(
				__CLASS__,
				'modules_cred_pluginversion',
			) );

			/* Link to read-only versions of elements in installed modules */
			add_action( 'wpmodules_library_link_components', array(
				__CLASS__,
				'cred_modules_library_link_components',
			), 10, 2 );
		}
		// setup cred bypass form submissions
		if ( defined( 'CRED_DISABLE_SUBMISSION' ) && CRED_DISABLE_SUBMISSION ) {
			add_filter( 'cred_bypass_save_data', array( __CLASS__, '_true' ), 20 );
			add_filter( 'cred_bypass_credaction', array( __CLASS__, '_true' ), 20 );
			add_filter( 'cred_data_saved_message', array( __CLASS__, 'disableCREDSubmitMessage' ), 20 );
			add_filter( 'cred_data_not_saved_message', array( __CLASS__, 'disableCREDSubmitMessage' ), 20 );
		}

		// Toolset Forms filters

		/**
		 * Returns Toolset Forms form messages
		 *
		 * @since 1.9
		 *
		 * @param array  $form_messages empty array to populate with form messages.
		 * @param int $form_id form id to retrieve its messages.
		 */
		add_filter('toolset_cred_form_messages', array(__CLASS__, 'get_form_messages'), 10, 2);
	}

	/**
	 * cred_modules_library_link_components
	 *
	 * Hooks into the Module Manager Library listing and offers links to edit/readonly versions of each Toolset Forms component
	 *
	 * @param $current_module
	 * @param $modman_modules (array) installed modules as stored in the Options table
	 *
	 * @since 1.3.4
	 */
	public static function cred_modules_library_link_components( $current_module = array(), $modman_modules = array() ) {
		$this_module_data = array();
		foreach ( $modman_modules as $hackey => $hackhack ) {
			if ( strtolower( $hackey ) == strtolower( $current_module['name'] ) ) {
				$this_module_data = $hackhack;
			}
		}
		if (
		( isset( $this_module_data[ _CRED_MODULE_MANAGER_KEY_ ] ) &&
			is_array( $this_module_data[ _CRED_MODULE_MANAGER_KEY_ ] ) )
		) {
			global $wpdb;
			?>
            <div class="module-elements-container">
                <h4><?php _e( 'Toolset Forms elements in this Module', 'wp-cred' ); ?></h4>
                <ul class="module-elements">
					<?php
					if ( isset( $this_module_data[ _CRED_MODULE_MANAGER_KEY_ ] ) &&
						is_array( $this_module_data[ _CRED_MODULE_MANAGER_KEY_ ] )
					) {
						$cred_titles = array();
						foreach ( $this_module_data[ _CRED_MODULE_MANAGER_KEY_ ] as $this_cred ) {
							$cred_titles[] = $this_cred['title'];
						}
						$cred_titles_flat = implode( "','", $cred_titles );
						$cred_pairs = $wpdb->get_results( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_title IN ('{$cred_titles_flat}') AND post_type = 'cred-form'" );
						if ( $cred_pairs ) {
							$suffix = 'editor';
							foreach ( $cred_pairs as $cred_data ) {
								$prefix = 'cred';
								echo '<li class="cred-element"><a href="' . admin_url() . 'admin.php?page=' . $prefix . '-' . $suffix . '&cred_id=' . $cred_data->ID . '"><i class="icon-cred ont-icon-19 ont-color-orange"></i>' . $cred_data->post_title . '</a></li>';
							}
						}
					}
					?>
                </ul>
            </div>
			<?php
		}
		if (
		( isset( $this_module_data[ _CRED_MODULE_MANAGER_USER_KEY_ ] ) &&
			is_array( $this_module_data[ _CRED_MODULE_MANAGER_USER_KEY_ ] ) )
		) {
			global $wpdb;
			?>
            <div class="module-elements-container">
                <h4><?php _e( 'Toolset Forms elements in this Module', 'wp-cred' ); ?></h4>
                <ul class="module-elements">
					<?php
					if ( isset( $this_module_data[ _CRED_MODULE_MANAGER_USER_KEY_ ] ) &&
						is_array( $this_module_data[ _CRED_MODULE_MANAGER_USER_KEY_ ] )
					) {
						$cred_titles = array();
						foreach ( $this_module_data[ _CRED_MODULE_MANAGER_USER_KEY_ ] as $this_cred ) {
							$cred_titles[] = $this_cred['title'];
						}
						$cred_titles_flat = implode( "','", $cred_titles );
						$cred_pairs = $wpdb->get_results( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_title IN ('{$cred_titles_flat}') AND post_type = 'cred-user-form'" );
						if ( $cred_pairs ) {
							$suffix = 'editor';
							foreach ( $cred_pairs as $cred_data ) {
								$prefix = 'cred';
								echo '<li class="cred-element"><a href="' . admin_url() . 'admin.php?page=' . $prefix . '-' . $suffix . '&cred_id=' . $cred_data->ID . '"><i class="icon-cred ont-icon-19 ont-color-orange"></i>' . $cred_data->post_title . '</a></li>';
							}
						}
					}
					?>
                </ul>
            </div>
			<?php
		}
	}

	public static function modules_cred_pluginversion() {
		return CRED_FE_VERSION;
	}

	/**
	 * @param $m
	 *
	 * @return mixed|string|void
	 */
	public static function disableCREDSubmitMessage( $m ) {
		return __( 'Form data saving has been disabled', 'wp-cred' );
	}

	/**
	 * @param $sections
	 *
	 * @return mixed
	 */
	public static function register_modules_cred_sections( $sections ) {
		//TODO: change with fonctcustom icon
		$sections[ _CRED_MODULE_MANAGER_KEY_ ] = array(
			'title' => __( 'Toolset Post Forms', 'wp-cred' ),
			'icon' => CRED_ASSETS_URL . '/images/CRED-icon-color_12X12.png',
			'icon_css' => 'icon-cred-logo ont-icon-16 ont-color-orange',
		);

		return $sections;
	}

	/**
	 * @param $sections
	 *
	 * @return mixed
	 */
	public static function register_modules_cred_user_sections( $sections ) {
		$sections[ _CRED_MODULE_MANAGER_USER_KEY_ ] = array(
			'title' => __( 'Toolset User Forms', 'wp-cred' ),
			'icon' => CRED_ASSETS_URL . '/images/CRED-icon-color_12X12.png',
			'icon_css' => 'icon-cred-logo ont-icon-16 ont-color-orange',
		);

		return $sections;
	}

	/**
	 * @param $items
	 *
	 * @return array
	 */
	public static function register_modules_cred_items( $items ) {
		$forms = self::getAllFormsCached();

		foreach ( $forms as $form ) {
			if ( isset( $form->meta->form['type'] ) ) {
				if ( 'edit' == $form->meta->form['type'] ) {
					$details = sprintf( __( 'This form edits posts of post type "%s".', 'wp-cred' ), $form->meta->post['post_type'] );
				} elseif ( 'new' == $form->meta->form['type'] ) {
					$details = sprintf( __( 'This form creates posts of post type "%s".', 'wp-cred' ), $form->meta->post['post_type'] );
				} else {
					$details = __( 'Type not set or unknown.', 'wp-cred' );
				}
			} else {
				$details = __( 'Type not set or unknown.', 'wp-cred' );
			}

			$items[] = array(
				'id' => _CRED_MODULE_MANAGER_KEY_ . $form->ID,
				'title' => $form->post_title,
				'details' => '<p style="padding:5px;">' . $details . '</p>',
			);
		}

		return $items;
	}

	/**
	 * @param $items
	 *
	 * @return array
	 */
	public static function register_modules_cred_user_items( $items ) {
		$forms = self::getAllUserFormsCached();
		foreach ( $forms as $form ) {
			if ( isset( $form->meta->form['type'] ) ) {
				if ( 'edit' == $form->meta->form['type'] ) {
					$details = sprintf( __( 'This form edits users.', 'wp-cred' ), $form->meta->post['post_type'] );
				} elseif ( 'new' == $form->meta->form['type'] ) {
					$details = sprintf( __( 'This form creates users.', 'wp-cred' ), $form->meta->post['post_type'] );
				} else {
					$details = __( 'Type not set or unknown.', 'wp-cred' );
				}
			} else {
				$details = __( 'Type not set or unknown.', 'wp-cred' );
			}

			$items[] = array(
				'id' => _CRED_MODULE_MANAGER_USER_KEY_ . $form->ID,
				'title' => $form->post_title,
				'details' => '<p style="padding:5px;">' . $details . '</p>',
			);
		}

		return $items;
	}

	/**
	 * @param $res
	 * @param $items
	 *
	 * @return array
	 */
	public static function export_modules_cred_items( $res, $items ) {
		$newitems = array();
		// items is now, whole array, not just IDs
		foreach ( $items as $ii => $item ) {
			$newitems[ $ii ] = str_replace( _CRED_MODULE_MANAGER_KEY_, '', $item['id'] );
		}
		CRED_Loader::load( 'CLASS/XML_Processor' );
		$hashes = array();
		$xmlstring = CRED_XML_Processor::exportToXMLString( $newitems, array( 'hash' => true ), $hashes );
		if ( ! empty( $hashes ) ) {
			foreach ( $items as $ii => $item ) {
				$id = str_replace( _CRED_MODULE_MANAGER_KEY_, '', $item['id'] );
				if ( isset( $hashes[ $id ] ) ) {
					$items[ $ii ]['hash'] = $hashes[ $id ];
				}
			}
		}

		return array( 'xml' => $xmlstring, 'items' => $items );
	}

	/**
	 * @param $res
	 * @param $items
	 *
	 * @return array
	 */
	public static function export_modules_cred_user_items( $res, $items ) {
		$newitems = array();
		// items is now, whole array, not just IDs
		foreach ( $items as $ii => $item ) {
			$newitems[ $ii ] = str_replace( _CRED_MODULE_MANAGER_USER_KEY_, '', $item['id'] );
		}
		CRED_Loader::load( 'CLASS/XML_Processor' );
		$hashes = array();
		$xmlstring = CRED_XML_Processor::exportUsersToXMLString( $newitems, array( 'hash' => true ), $hashes );
		if ( ! empty( $hashes ) ) {
			foreach ( $items as $ii => $item ) {
				$id = str_replace( _CRED_MODULE_MANAGER_USER_KEY_, '', $item['id'] );
				if ( isset( $hashes[ $id ] ) ) {
					$items[ $ii ]['hash'] = $hashes[ $id ];
				}
			}
		}

		return array( 'xml' => $xmlstring, 'items' => $items );
	}

	/**
	 * @param string $res
	 * @param string $xmlstring
	 * @param bool $selecteditems
	 * @param bool $allitems
	 *
	 * @return array|string|WP_Error
	 */
	public static function import_modules_cred_items( $res, $xmlstring, $selecteditems = false, $allitems = false ) {
		CRED_Loader::load( 'CLASS/XML_Processor' );
		if ( false !== $selecteditems && is_array( $selecteditems ) ) {
			$import_items = array();
			foreach ( $selecteditems as $item ) {
				$import_items[] = str_replace( _CRED_MODULE_MANAGER_KEY_, '', $item );
			}
			unset( $selecteditems );
			$results = CRED_XML_Processor::importFromXMLString( $xmlstring, array(
				'overwrite_forms' => true,
				'items' => $import_items,
				'return_ids' => true,
			) );
		} else {
			$results = CRED_XML_Processor::importFromXMLString( $xmlstring );
		}
		if ( false === $results || is_wp_error( $results ) ) {
			$error = ( false === $results ) ? __( 'Error during Toolset Post Forms import', 'wp-cred' ) : $results->get_error_message( $results->get_error_code() );
			$results = array( 'new' => 0, 'updated' => 0, 'failed' => 0, 'errors' => array( $error ) );
		}
		unset( $results['settings'] );
		// for module manager
		if ( isset( $results['items'] ) ) {
			foreach ( $results['items'] as $old_id => $new_id ) {
				$results['items'][ _CRED_MODULE_MANAGER_KEY_ . $old_id ] = _CRED_MODULE_MANAGER_KEY_ . $new_id;
				unset( $results['items'][ $old_id ] );
			}
		}

		return $results;
	}

	/**
	 * @param string $res
	 * @param string $xmlstring
	 * @param bool $selecteditems
	 * @param bool $allitems
	 *
	 * @return array|string|WP_Error
	 */
	public static function import_modules_cred_user_items( $res, $xmlstring, $selecteditems = false, $allitems = false ) {
		CRED_Loader::load( 'CLASS/XML_Processor' );
		if ( false !== $selecteditems && is_array( $selecteditems ) ) {
			$import_items = array();
			foreach ( $selecteditems as $item ) {
				$import_items[] = str_replace( _CRED_MODULE_MANAGER_USER_KEY_, '', $item );
			}
			unset( $selecteditems );
			$results = CRED_XML_Processor::importUsersFromXMLString( $xmlstring, array(
				'overwrite_forms' => true,
				'items' => $import_items,
				'return_ids' => true,
			) );
		} else {
			$results = CRED_XML_Processor::importUserFromXMLString( $xmlstring );
		}
		if ( false === $results || is_wp_error( $results ) ) {
			$error = ( false === $results ) ? __( 'Error during Toolset User Forms import', 'wp-cred' ) : $results->get_error_message( $results->get_error_code() );
			$results = array( 'new' => 0, 'updated' => 0, 'failed' => 0, 'errors' => array( $error ) );
		}
		unset( $results['settings'] );
		// for module manager
		if ( isset( $results['items'] ) ) {
			foreach ( $results['items'] as $old_id => $new_id ) {
				$results['items'][ _CRED_MODULE_MANAGER_USER_KEY_ . $old_id ] = _CRED_MODULE_MANAGER_USER_KEY_ . $new_id;
				unset( $results['items'][ $old_id ] );
			}
		}

		return $results;
	}

	/**
	 * Get a WP_Post object matchign a form given its title and for post type
	 *
	 * @param string title
	 * @param string $form_type
	 * @return \WP_Post|false
	 */
	public static function get_form_by_title( $title, $form_type ) {
		$forms = get_posts(
			array(
				'post_type'              => $form_type,
				'title'                  => $title,
				'post_status'            => 'all',
				'numberposts'            => 1,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'orderby'                => 'post_date ID',
				'order'                  => 'ASC',
			)
		);

		if ( empty( $forms ) ) {
			return false;
		}

		return $forms[0];
	}

	/**
	 * @param $items
	 *
	 * @return mixed
	 */
	public static function modules_cred_items_exist( $items ) {
		foreach ( $items as $key => $item ) {
			// item exists already
			$form = self::get_form_by_title( $item['title'], CRED_FORMS_CUSTOM_POST_NAME );
			if ( $form ) {
				$items[ $key ]['exists'] = true;
				if ( isset( $item['hash'] ) ) {
					CRED_Loader::load( 'CLASS/XML_Processor' );
					$hash = CRED_XML_Processor::computeHashForForm( $form->ID );
					if ( $hash && $item['hash'] != $hash ) {
						$items[ $key ]['is_different'] = true;
					} //$hashes[$form->ID];
					else {
						$items[ $key ]['is_different'] = false;
					} //$hashes[$form->ID];
				}
			} else {
				$items[ $key ]['exists'] = false;
			}
		}

		return $items;
	}

	/**
	 * @param $items
	 *
	 * @return mixed
	 */
	public static function modules_cred_user_items_exist( $items ) {
		foreach ( $items as $key => $item ) {
			// item exists already
			$form = self::get_form_by_title( $item['title'], CRED_USER_FORMS_CUSTOM_POST_NAME );
			if ( $form ) {
				$items[ $key ]['exists'] = true;
				if ( isset( $item['hash'] ) ) {
					CRED_Loader::load( 'CLASS/XML_Processor' );
					$hash = CRED_XML_Processor::computeHashForUserForm( $form->ID );
					if ( $hash && $item['hash'] != $hash ) {
						$items[ $key ]['is_different'] = true;
					} //$hashes[$form->ID];
					else {
						$items[ $key ]['is_different'] = false;
					} //$hashes[$form->ID];
				}
			} else {
				$items[ $key ]['exists'] = false;
			}
		}

		return $items;
	}


    /**
     * Get all forms... from cache?
     *
     * This is a potential performance bottleneck. Use apply_filters( 'cred_get_available_forms', array(), $domain ); instead.
     *
     * @deprecated
     */
	public static function getAllFormsCached() {
		static $cache = null;
		if ( null === $cache ) {
			$cache = CRED_Loader::get( 'MODEL/Forms' )->getFormsForTable( 1, - 1 );
		}

		return $cache;
	}

    /**
     * Get all user forms... from cache?
     *
     * This is a potential performance bottleneck. Use apply_filters( 'cred_get_available_forms', array(), $domain ); instead.
     *
     * @deprecated
     */
	public static function getAllUserFormsCached() {
		static $cache = null;
		if ( null === $cache ) {
			$cache = CRED_Loader::get( 'MODEL/UserForms' )->getFormsForTable( 1, - 1 );
		}

		return $cache;
	}

	public static $message_after;
	public static $my_message_after;

	/**
	 * @param bool $post_id
	 * @param string $text
	 * @param string $action
	 * @param string $class
	 * @param string $style
	 * @param string $message
	 * @param string $message_after
	 * @param int $message_show
	 * @param bool $redirect
	 *
	 * @return bool|mixed|string
	 */
	public static function cred_delete_post_link( $post_id = false, $text = '', $action = '', $class = '', $style = '', $message = '', $message_after = '', $message_show = 1, $redirect = false ) {
		global $post, $current_user;
		static $idcount = 0;

		self::$message_after = $message_after;
		self::$my_message_after = __( 'Do you want to go to the home page?', 'wp-cred' );

		if ( ! current_user_can( 'delete_own_posts_with_cred' ) && $current_user->ID == $post->post_author ) {
			//return '<strong>'.__('Do not have permission (delete own)','wp-cred').'</strong>';
			return '';
		}
		if ( ! current_user_can( 'delete_other_posts_with_cred' ) && $current_user->ID != $post->post_author ) {
			//return '<strong>'.__('Do not have permission (delete other)','wp-cred').'</strong>';
			return '';
		}

		if ( $post_id === false || empty( $post_id ) || ! isset( $post_id ) || ! is_numeric( $post_id ) ) {
			if ( ! isset( $post->ID ) ) {
				return '<strong>' . __( 'No post specified', 'wp-cred' ) . '</strong>';
			} else {
				$post_id = $post->ID;
			}
		}

		// localise the ID
		$post_id = self::getLocalisedID( intval( $post_id ) );
		// provide WPML localization for hardcoded texts
		$text = str_replace( array( '%TITLE%', '%ID%' ), array(
			get_the_title( $post_id ),
			$post_id,
		), cred_translate( 'Delete Link Text', $text, 'CRED Shortcodes' ) );

		if ( 'delete' !== $action ) {
			// Adjust the action for RFGs: they shoul dalways get deleted, not trashed.
			$deleting_post_type = get_post_type( $post_id );
			$deleting_post_type_object = get_post_type_object( $deleting_post_type );
			if (
				property_exists( $deleting_post_type_object, \Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP )
				&& $deleting_post_type_object->{\Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP}
			) {
				$action = 'delete';
			}
		}


		//$link_id = '_cred_cred_' . $post_id . '_' . ++$idcount . '_' . rand(1, 10);
		$link_id = '_cred_cred_' . $post_id . '_' . ++ $idcount;
		$_wpnonce = wp_create_nonce( $link_id . '_' . $action );
		$link = CRED_CRED::routeAjax( 'cred-ajax-delete-post&cred_post_id=' . $post_id . '&cred_action=' . $action . '&redirect=' . $redirect . '&_wpnonce=' . $_wpnonce );

		$_atts = array();
		if ( ! empty( $class ) ) {
			$_atts[] = 'class="' . esc_attr( str_replace( '"', "'", $class ) ) . '"';
		}
		if ( ! empty( $style ) ) {
			$_atts[] = 'style="' . esc_attr( str_replace( '"', "'", $style ) ) . '"';
		}

		$dps = "";
		if ( $idcount == 1 ) {
			//$dps = self::get_delete_post_link_js($message_after);
			add_action( 'wp_footer', array( 'CRED_Helper', 'get_delete_post_link_js' ), 100 );
			add_action( 'admin_footer', array( 'CRED_Helper', 'get_delete_post_link_js' ), 100 );
		}

		return CRED_Loader::tpl( 'delete-post-link', array(
			'link' => $link,
			'text' => $text,
			'link_id' => $link_id,
			'link_atts' => ( ! empty( $_atts ) ) ? implode( ' ', $_atts ) : false,
			/* 'include_js' => $dps, */
			'message' => $message,
			'message_after' => $message_after,
			'message_show' => $message_show,
			'js' => $dps,
		) );
	}

	public static function get_delete_post_link_js() {
		$add_string = "";
		if ( ! empty( self::$message_after ) ) {
			$add_string = 'alert(\'';
			$add_string .= esc_js( self::$message_after );
			$add_string .= '\');';
			$add_string .= PHP_EOL;

			$add_go_home_message = apply_filters( 'cred_after_delete_post_link_home_message', false );
			if ( $add_go_home_message ) {
				$add_string .= 'if (confirm(\'';
				$add_string .= esc_js( self::$my_message_after );
				$add_string .= '\')) { window.location="' . get_home_url() . '"; return; }';
				$add_string .= PHP_EOL;
			}
		}

		$v = "<script type='text/javascript'>
            function _cred_cred_parse_url(__url__, __params__)
            {
                var __urlparts__ = __url__.split('?'), __urlparamblocks__, __paramobj__, __p__, __v__, __query_string__ = [], __ii__;
                if (__urlparts__.length >= 2)
                {

                    __urlparamblocks__ = __urlparts__[1].split(/[&;]/g);
                    for (__ii__ = 0; __ii__ < __urlparamblocks__.length; __ii__++)
                    {
                        var u = __urlparamblocks__[__ii__];
                        __paramobj__ = u.split('=');
                        var v = __paramobj__[0];
                        __p__ = decodeURIComponent(v);
                        var t = __paramobj__[1];
                        if (t)
                            __v__ = decodeURIComponent(t);
                        else
                            __v__ = false;

                        if (__params__.remove && __params__.remove.length)
                        {
                            if (__params__.remove.indexOf(__p__) > -1)
                                continue;
                        }
                        if (__v__)
                            __query_string__.push(encodeURIComponent(__p__) + '=' + encodeURIComponent(__v__));
                        else
                            __query_string__.push(encodeURIComponent(__p__));
                    }
                    if (__params__.add)
                    {
                        for (__ii__ in __params__.add)
                        {
                            if (__params__.add.hasOwnProperty(__ii__))
                            {
                                if (__params__.add[__ii__])
                                    __query_string__.push(encodeURIComponent(__ii__) + '=' + encodeURIComponent(__params__.add[__ii__]));
                                else
                                    __query_string__.push(encodeURIComponent(__ii__));
                            }
                        }
                    }
                    if (__query_string__.length)
                    {
                        __query_string__ = __query_string__.join('&');
                        __url__ = __urlparts__[0] + '?' + __query_string__;
                    }
                    else
                    {
                        __url__ = __urlparts__[0];
                    }
                }
                return __url__;
            }

            function _cred_cred_delete_post_handler(__isFromLink__, __link__, __url__, __result__, __message__, __message_show__)
            {

                var __ltext__ = '';

                /*if (typeof __isFromLink__=='undefined')
                 __isFromLink__=false;*/

                if (__isFromLink__) // callback from link click
                {
                    if (__message_show__) {
                        if (undefined === __message__)
                        {
                            __message__ = '';
                        }
                        var __go__ = confirm(__message__ == '' ? '" . esc_js( __( 'Are you sure you want to delete this post?', 'wp-cred' ) ) . "' : __message__);
                        if (!__go__)
                            return false;
                    }

                    if (__link__.text)
                        __ltext__ = __link__.text;
                    else if (__link__.innerText)
                        __ltext__ = __link__.innerText;

                    var __deltext__ = '" . esc_js( __( 'Deleting..', 'wp-cred' ) ) . "';
                    // static storage of reference texts of related post delete links
                    _cred_cred_delete_post_handler.refs = _cred_cred_delete_post_handler.refs || {};
                    if (!_cred_cred_delete_post_handler.refs[__link__.id])
                        _cred_cred_delete_post_handler.refs[__link__.id] = __ltext__;
                    if (__link__.text)
                        __link__.text = __deltext__;
                    else if (__link__.innerText)
                        __link__.innerText = __deltext__;

                    __link__.href = _cred_cred_parse_url(__link__.href, {
                        remove: ['_cred_link_id', '_cred_url'],
                        add: {
                            '_cred_link_id': __link__.id,
                            '_cred_url': ''
                        }
                    });

                    // this is set to refresh page
                    if (!__url__ && __link__.className.indexOf('cred-refresh-after-delete') >= 0) {
                        var current_url = document.location.href.split('?');
                        var query_params, query_params_to_keep = [];
                        if(current_url.length >= 2){
                            query_params = current_url[1].split('&');
                            while(query_params.length > 0){
                            var poped_param = query_params.pop();
                                if( poped_param.indexOf('_tt') == -1 && poped_param.indexOf('_target') == -1 && poped_param.indexOf('_success') == -1){
                                    query_params_to_keep.push(poped_param);
                                }
                            }
                        }

                        __link__.href = _cred_cred_parse_url(__link__.href, {
                            remove: ['_cred_url'],
                            add: {
                                '_cred_url': current_url[0] + (query_params_to_keep.length > 0 ? '?' + query_params_to_keep.join('&') : '')
                            }
                        });
                    }
                    jQuery(document).trigger('cred-post-delete-link-completed');
                    return true;
                }
                else // callback from iframe return function
                {
                    //console.log(__result__);

                    // success
                    if (__result__ && 101 == __result__)
                    {
                    " . $add_string . "
                        var __linkel__ = document.getElementById(__link__);

                        if (__linkel__) {


                            //TODO: check WHY????? there is __linkel__.className.indexOf('cred-refresh-after-delete') >= 0
                            if (__url__ && __linkel__.className.indexOf('cred-refresh-after-delete') >= 0)
                            {
                                document.location = __url__;
                            }
                        }
                    }
                    else
                    {

                        if (404 == __result__)
                            alert('" . esc_js( __( 'No post defined', 'wp-cred' ) ) . "');
                        else if (505 == __result__)
                            alert('" . esc_js( __( 'Permission denied', 'wp-cred' ) ) . "');
                        else if (202 == __result__)
                            alert('" . esc_js( __( 'Post delete failed', 'wp-cred' ) ) . "');
                    }
                }
            }
            </script>";

		echo $v;
	}

	/**
	 * @param int $form
	 * @param bool $post_id
	 * @param string $text
	 * @param string $class
	 * @param string $style
	 * @param string $target
	 * @param string $attributes
	 *
	 * @return string
	 */
	public static function cred_edit_post_link( $form, $post_id = false, $text = '', $class = '', $style = '', $target = '', $attributes = '' ) {
		global $post, $current_user;

		if ( empty( $form ) ) {
			return '<strong>' . __( 'No form specified', 'wp-cred' ) . '</strong>';
		}

		if ( $post_id === false || empty( $post_id ) || ! isset( $post_id ) || ! is_numeric( $post_id ) ) {
			if ( ! isset( $post->ID ) ) {
				return '<strong>' . __( 'No post specified', 'wp-cred' ) . '</strong>';
			} else {
				$post_id = $post->ID;
			}
		}

		// localise the ID
		if ( apply_filters( 'cred_wpml_get_localised_id', true ) ) {
			$post_id = self::getLocalisedID( intval( $post_id ) );
		}

		if ( ! is_numeric( $form ) ) {
			$form_object = self::get_form_object( $form, CRED_FORMS_CUSTOM_POST_NAME );
			if ( ! $form_object ) {
				return '<strong>' . sanitize_text_field( sprintf( __( 'Form [%s] does not exist', 'wp-cred' ), $form ) ) . '</strong>';
			}
			$form = $form_object->ID;
		} else {
			$form = intval( $form );
		}

		/**
		 * get form settings to check form type
		 * 'type' == 'edit'
		 * 'post_type' == $post->post_type
		 */
		$form_settings = (array) get_post_meta( $form, '_cred_form_settings', true );
		if (
			0 || ! is_array( $form_settings ) || empty( $form_settings ) || ! array_key_exists( 'form', $form_settings ) || ! array_key_exists( 'type', $form_settings['form'] ) || ! array_key_exists( 'post', $form_settings ) || ! array_key_exists( 'post_type', $form_settings['post'] )
		) {
			if ( current_user_can( 'manage_options' ) ) {
				return sprintf(
					'<p class="alert"><strong>%s</strong></p>', __( 'Missing form configuration.', 'wp-cred' )
				);
			}

			return;
		}
		if ( get_post_type( $post_id ) != $form_settings['post']['post_type'] ) {
			if ( current_user_can( 'manage_options' ) ) {
				return sprintf(
					'<p class="alert"><strong>%s</strong></p>', __( 'Edit form link can not be displayed (post type mismatch).', 'wp-cred' )
				);
			}

			return;
		}

		if ( ! current_user_can( 'edit_own_posts_with_cred_' . $form ) && $current_user->ID == $post->post_author ) {
			//return '<strong>'.__('Do not have permission (edit own with this form)','wp-cred').'</strong>';
			return '';
		}
		if ( ! current_user_can( 'edit_other_posts_with_cred_' . $form ) && $current_user->ID != $post->post_author ) {
			//return '<strong>'.__('Do not have permission (edit other with this form)','wp-cred').'</strong>';
			return '';
		}

		$link = get_permalink( $post_id );
		$link = add_query_arg( array( 'cred-edit-form' => $form ), $link );
		//esc_url only to the last add_query arg
		$link = esc_url( add_query_arg( array( '_id' => $post_id ), $link ) );
		// provide WPML localization for hardcoded texts
		$text = str_replace( array( '%TITLE%', '%ID%' ), array(
			get_the_title( $post_id ),
			$post_id,
		), cred_translate( 'Edit Link Text', $text, 'CRED Shortcodes' ) );

		$_atts = array();
		if ( ! empty( $class ) ) {
			$_atts[] = 'class="' . esc_attr( str_replace( '"', "'", $class ) ) . '"';
		}
		if ( ! empty( $style ) ) {
			$_atts[] = 'style="' . esc_attr( str_replace( '"', "'", $style ) ) . '"';
		}
		if ( ! empty( $target ) ) {
			$_atts[] = 'target="' . esc_attr( str_replace( '"', "'", $target ) ) . '"';
		}
		if ( ! empty( $attributes ) ) {
			$_atts[] = str_replace( array( '%eq%', '%dbquo%', '%quot%' ), array( "=", '"', "'" ), $attributes );
		}

		return "<a href='{$link}' " . implode( ' ', $_atts ) . ">" . $text . "</a>";
	}

	/**
	 * @global int $post
	 * @global int $current_user
     *
	 * @param int $form
	 * @param bool $user_id
	 * @param string $text
	 * @param string $class
	 * @param string $style
	 * @param string $target
	 * @param string $attributes
	 *
	 * @return string
	 */
	public static function cred_edit_user_link( $form, $user_id = false, $text = '', $class = '', $style = '', $target = '', $attributes = '' ) {
		global $user, $post, $current_user;

		if ( empty( $form ) ) {
			return '<strong>' . __( 'No user form specified', 'wp-cred' ) . '</strong>';
		}

		if ( $user_id === false || empty( $user_id ) || ! isset( $user_id ) || ! is_numeric( $user_id ) ) {
			$user_id = get_current_user_id();
			if ( $user_id == 0 ) {
				return __( 'No user specified', 'wp-cred' );
			}
		}

		if ( ! is_numeric( $form ) ) {
			$form_object = self::get_form_object( $form, CRED_USER_FORMS_CUSTOM_POST_NAME );
			if ( ! $form_object ) {
				return '<strong>' . sanitize_text_field( sprintf( __( 'User Form [%s] does not exist', 'wp-cred' ), $form ) ) . '</strong>';
			}
			$form = $form_object->ID;
		} else {
			$form = intval( $form );
		}

		/**
		 * get form settings to check form type
		 * 'type' == 'edit'
		 * 'post_type' == $post->post_type
		 */
		$form_settings = (array) get_post_meta( $form, '_cred_form_settings', true );
		if (
			0 || ! is_array( $form_settings ) || empty( $form_settings ) || ! array_key_exists( 'form', $form_settings ) || ! array_key_exists( 'type', $form_settings['form'] )
		) {
			if ( current_user_can( 'manage_options' ) ) {
				return sprintf(
					'<p class="alert"><strong>%s</strong></p>', __( 'Missing form configuration.', 'wp-cred' )
				);
			}

			return;
		}

		$user = get_user_by( "ID", $user_id );
		if ( ! isset( $user->data ) ) {
			return __( 'Invalid user', 'wp-cred' );
		}

		$user = $user->data;

		$link = get_permalink( $post->ID );
		$link = add_query_arg( array( 'cred-edit-user-form' => $form ), $link );
		$link = add_query_arg( array( 'user_id' => $user_id ), $link );
		$link = esc_url( $link );
		// provide WPML localization for hardcoded texts

		$text = str_replace( array( '%TITLE%', '%ID%' ), array(
			$user->user_login,
			$user->ID,
		), cred_translate( 'Edit ', $text, 'CRED Shortcodes' ) );

		$_atts = array();
		if ( ! empty( $class ) ) {
			$_atts[] = 'class="' . esc_attr( str_replace( '"', "'", $class ) ) . '"';
		}
		if ( ! empty( $style ) ) {
			$_atts[] = 'style="' . esc_attr( str_replace( '"', "'", $style ) ) . '"';
		}
		if ( ! empty( $target ) ) {
			$_atts[] = 'target="' . esc_attr( str_replace( '"', "'", $target ) ) . '"';
		}
		if ( ! empty( $attributes ) ) {
			$_atts[] = str_replace( array( '%eq%', '%dbquo%', '%quot%' ), array( "=", '"', "'" ), $attributes );
		}

		return "<a href='{$link}' " . implode( ' ', $_atts ) . ">" . $text . "</a>";
	}

	/**
	 * @param int $form
	 * @param int $parent_id
	 * @param string $text
	 * @param string $class
	 * @param string $style
	 * @param string $target
	 * @param string $attributes
	 *
	 * @return string
	 */
	public static function cred_child_link_form(
		$form, $parent_id = null, /* $parent_type='', */
		$text = '', $class = '', $style = '', $target = '', $attributes = ''
	) {
		global $post;

		if ( empty( $form ) || ! is_numeric( $form ) ) {
			return '<strong>' . __( 'No Child Form Page specified', 'wp-cred' ) . '</strong>';
		}

		$form = intval( $form );

		$link = get_permalink( $form );

		if ( $parent_id !== null ) {
			$parent_id = intval( $parent_id );

			if ( $parent_id < 0 /* && $post->post_type==$parent_type */ ) {
				$parent_id = $post->ID;
			}
			/* elseif ($parent_id<0)
              $parent_id=null; */
		}

		if ( $parent_id !== null ) {
			$parent_type = get_post_type( $parent_id );
			// localise the ID
			$parent_id = self::getLocalisedID( $parent_id, $parent_type );
			if ( $parent_type === false ) {
				return __( 'Unknown Parent Type', 'wp-cred' );
			}
			$link = esc_url( add_query_arg( array( 'parent_' . $parent_type . '_id' => $parent_id ), $link ) );
		}

		$_atts = array();
		if ( ! empty( $class ) ) {
			$_atts[] = 'class="' . esc_attr( str_replace( '"', "'", $class ) ) . '"';
		}
		if ( ! empty( $style ) ) {
			$_atts[] = 'style="' . esc_attr( str_replace( '"', "'", $style ) ) . '"';
		}
		if ( ! empty( $target ) ) {
			$_atts[] = 'target="' . esc_attr( str_replace( '"', "'", $target ) ) . '"';
		}
		if ( ! empty( $attributes ) ) {
			$_atts[] = str_replace( array( '%eq%', '%dbquo%', '%quot%' ), array( "=", '"', "'" ), $attributes );
		}

		// provide WPML localization for hardcoded texts
		return "<a href='{$link}' " . implode( ' ', $_atts ) . ">" . cred_translate( 'Child Link Text', $text, 'CRED Shortcodes' ) . "</a>";
	}

	/**
	 * Render a post form.
	 *
	 * @param mixed $form_identifier Title, slug or ID of the form to render
	 * @param bool|int $post_id ID of the post to edit, if any
	 *
	 * @return string
	 *
	 * @since unknown
	 * @since m2m Introduce the ability to set the post to edit on an URL parameter for repeatable field groups when:
	 * - The form is not forced to edit a specific post with its post shortcode attribute
	 * - And there is a cred_action=edit_rfg URL query argument
	 * - And there is a cred_rfg_id URL query parameter
	 * - And the ID of the post to edit is a child in a RFG relationship (the form itself will perform later some post type checks)
	 */
	public static function cred_form( $form_identifier, $post_id = false ) {
		if ( empty( $form_identifier ) ) {
			return '<strong>' . __( 'No form specified', 'wp-cred' ) . '</strong>';
		}

		$form = cred_get_object_form( $form_identifier, CRED_FORMS_CUSTOM_POST_NAME );
		if ( ! $form ) {
			if ( current_user_can( 'manage_options' ) ) {
				return sprintf( __( "The Toolset Form %s does not exist", "wp-cred" ), $form_identifier );
			}
			return '';
		}

		$form_id = cred_get_form_id_by_form( $form );

		// Since m2m: support editing RFG items set by URL parameter
		// Make sure we are indeed getting a RFG object ID by:
		// - checking that it is indeed in an association, as a child
		// - checking that this association belongs to a RFG relationship
		if (
			! $post_id
			&& isset( $_GET['cred_action'] )
			&& 'edit_rfg' === $_GET['cred_action']
			&& isset( $_GET['cred_rfg_id'] )
		) {
			if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
				return '';
			}

			do_action( 'toolset_do_m2m_full_init' );
			$cred_rfg_id = (int) $_GET['cred_rfg_id'];

			$association_query = new Toolset_Association_Query_V2();
			$associations = $association_query
				->limit( 1 )
				->add( $association_query->element_id_and_domain( $cred_rfg_id, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Child() ) )
				->get_results();

			if (
				is_array( $associations )
				&& count( $associations )
			) {
				$association = reset( $associations );
				$relationship_origin = $association->get_definition()->get_origin();
				if ( Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD != $relationship_origin::ORIGIN_KEYWORD ) {
					return '';
				}
				$post_id = $cred_rfg_id;
			} else {
				return '';
			}
		}

		// Load assets now that we know that the form will be printed.
		do_action( 'toolset_forms_enqueue_frontend_form_assets' );

		// localise the ID
		if ( $post_id ) {
			$post_id = self::getLocalisedID( intval( $post_id ) );
		}

		// prevent recursion if form shortcode inside posts content
		remove_shortcode( 'cred-form', array( __CLASS__, 'credFormShortcode' ) );
		remove_shortcode( 'cred_form', array( __CLASS__, 'credFormShortcode' ) );

		/**
		 * Track the currently rendering form.
		 *
		 * @param \WP_Post $form
		 * @param array attributes Set of attributes passed to this form shortcode
		 * @since 2.2.1.1
		 */
		do_action(
			'toolset_forms_frontend_flow_form_start',
			$form,
			array(
				'form' => $form_identifier,
				'post' => $post_id,
			)
		);

		$output = CRED_CRED::get_form_builder()->get_form( $form_id, $post_id );

		/**
		 * End tracking the currently rendering form.
		 *
		 * @since 2.2.1.1
		 */
		do_action( 'toolset_forms_frontend_flow_form_end' );

		add_shortcode( 'cred-form', array( __CLASS__, 'credFormShortcode' ) );
		add_shortcode( 'cred_form', array( __CLASS__, 'credFormShortcode' ) );

		return $output;
	}

	/**
	 * Render an user form.
	 *
	 * @param mixed $form_identifier Title, slug or ID of the form to render
	 * @param bool|int $user_id ID of the user to edit, if any
	 *
	 * @return string
	 *
	 * @since unknown
	 */
	public static function cred_user_form( $form_identifier, $user_id = false ) {
		if ( empty( $form_identifier ) ) {
			return '<strong>' . __( 'No user form specified', 'wp-cred' ) . '</strong>';
		}

		$form = cred_get_object_form( $form_identifier, CRED_USER_FORMS_CUSTOM_POST_NAME );

		if ( ! $form ) {
			if ( current_user_can( 'manage_options' ) ) {
				return sprintf( __( "The Toolset User Form %s does not exist", "wp-cred" ), $form_identifier );
			}
			return '';
		}

		$form_id = cred_get_form_id_by_form( $form );

		$fm = CRED_Loader::get( 'MODEL/UserForms' );
		$form_fields = $fm->getFormCustomFields( $form_id, array( 'form_settings' ) );
		$form_type = $form_fields['form_settings']->form['type'];

		if ( $form_type == 'edit' ) {
			$user = new WP_User( $user_id );
			if ( ! isset( $user ) || $user->ID == 0 || empty( $user->data ) ) {
				return __( 'Invalid user', 'wp-cred' );
			}
		} else {
			$user_id = false;
		}

		// Load assets now that we know that the form will be printed.
		do_action( 'toolset_forms_enqueue_frontend_form_assets' );

		// prevent recursion if form shortcode inside posts content
		remove_shortcode( 'cred-user-form', array( __CLASS__, 'credUserFormShortcode' ) );
		remove_shortcode( 'cred_user_form', array( __CLASS__, 'credUserFormShortcode' ) );

		/**
		 * Track the currently rendering form.
		 *
		 * @param \WP_Post $form
		 * @param array attributes Set of attributes passed to this form shortcode
		 * @since 2.2.1.1
		 */
		do_action(
			'toolset_forms_frontend_flow_form_start',
			$form,
			array(
				'form' => $form_identifier,
				'user' => $user_id,
			)
		);

		$output = CRED_CRED::get_form_builder()->get_form( $form_id, $user_id );

		/**
		 * End tracking the currently rendering form.
		 *
		 * @since 2.2.1.1
		 */
		do_action( 'toolset_forms_frontend_flow_form_end' );

		add_shortcode( 'cred-user-form', array( __CLASS__, 'credUserFormShortcode' ) );
		add_shortcode( 'cred_user_form', array( __CLASS__, 'credUserFormShortcode' ) );

		return $output;
	}

	public static function addShortcodesAndFilters() {
		// check to see if form preview is required
		if ( isset( $_REQUEST['cred_user_form_preview'] ) ) {
			add_filter( 'the_posts', array( __CLASS__, 'preview_user_form' ), 5000 );
			add_filter( 'user_can_richedit', array( __CLASS__, '_true' ), 100 );
		}

		if ( isset( $_REQUEST['cred_form_preview'] ) ) {
			add_filter( 'the_posts', array( __CLASS__, 'preview_form' ), 5000 );
			add_filter( 'user_can_richedit', array( __CLASS__, '_true' ), 100 );
		}

		// IMPORTANT: add both formats of shortcodes, because the dashes are strange in shortcodes, so use underscores
		// delete post link shortcode
		add_shortcode( 'cred-delete-post-link', array( __CLASS__, 'credDeletePostLinkShortcode' ) );
		add_shortcode( 'cred_delete_post_link', array( __CLASS__, 'credDeletePostLinkShortcode' ) );

		// edit post form link shortcode
		add_shortcode( 'cred-link-form', array( __CLASS__, 'credFormLinkShortcode' ) );
		add_shortcode( 'cred_link_form', array( __CLASS__, 'credFormLinkShortcode' ) );

		// edit post form link shortcode
		add_shortcode( 'cred-link-user-form', array( __CLASS__, 'credUserFormLinkShortcode' ) );
		add_shortcode( 'cred_link_user_form', array( __CLASS__, 'credUserFormLinkShortcode' ) );

		// link to child form
		add_shortcode( 'cred-child-link-form', array( __CLASS__, 'credChildFormLinkShortcode' ) );
		add_shortcode( 'cred_child_link_form', array( __CLASS__, 'credChildFormLinkShortcode' ) );

		// form display shortcode
		add_shortcode( 'cred-form', array( __CLASS__, 'credFormShortcode' ) );
		add_shortcode( 'cred_form', array( __CLASS__, 'credFormShortcode' ) );

		// form display shortcode
		add_shortcode( 'cred-user-form', array( __CLASS__, 'credUserFormShortcode' ) );
		add_shortcode( 'cred_user_form', array( __CLASS__, 'credUserFormShortcode' ) );

		// replace content when preview or edit form
		add_action( 'loop_start', array( __CLASS__, 'overrideContentFilter' ), 1000 );
		add_action( 'ddl-layouts-render-start-post-content', array( __CLASS__, 'overrideContentFilter' ), 1000 );

		if (
			array_key_exists( 'cred_form_preview', $_GET ) || array_key_exists( 'cred_user_form_preview', $_GET )
		) {
			// Force no WordPress Archive on previews
			add_filter( 'wpv_filter_force_wordpress_archive', array( __CLASS__, 'overrideViewArchive' ) );
		}
	}

	public static function preview_form( $posts ) {
		global $wp, $wp_query, $post;
		static $preview_done = false;

		if ( ! $preview_done ) {
			$preview_done = true;

			// allow preview only if form preview key set
			if ( ! array_key_exists( 'cred_form_preview', $_GET ) ) {
				return $posts;
			}

			$posts = array();
			$posts[] = get_post( intval( $_GET['cred_form_preview'] ) );

			//Not sure if this one is necessary but might as well set it like a true page
			$wp_query->is_singular = true;
			//$wp_query->is_home = false;
			$wp_query->is_archive = false;
			$wp_query->is_category = false;
			//Longer permalink structures may not match the fake post slug and cause a 404 error so we catch the error here
			unset( $wp_query->query["error"] );
			$wp_query->query_vars["error"] = "";
			$wp_query->is_404 = false;
			$wp_query->max_num_pages = 1;
		}

		add_action('wp_enqueue_scripts', array(__CLASS__, 'frontend_preview_flag'));

		return $posts;
	}

	public static function preview_user_form( $posts ) {
		global $wp, $wp_query, $post;
		static $preview_done = false;

		if ( ! $preview_done ) {
			$preview_done = true;

			// allow preview only if form preview key set
			if ( ! array_key_exists( 'cred_user_form_preview', $_GET ) ) {
				return $posts;
			}

			$posts = array();
			$posts[] = get_post( intval( $_GET['cred_user_form_preview'] ) );

			//Not sure if this one is necessary but might as well set it like a true page
			$wp_query->is_singular = true;
			//$wp_query->is_home = false;
			$wp_query->is_archive = false;
			$wp_query->is_category = false;
			//Longer permalink structures may not match the fake post slug and cause a 404 error so we catch the error here
			unset( $wp_query->query["error"] );
			$wp_query->query_vars["error"] = "";
			$wp_query->is_404 = false;
			$wp_query->max_num_pages = 1;
		}

		add_action('wp_enqueue_scripts', array(__CLASS__, 'frontend_preview_flag'));

		return $posts;
	}

	public static function overrideViewArchive( $view_id ) {
		return 0;
	}

	public static function frontend_preview_flag() {
	    echo "<script>window.cred_form_preview_mode=true;</script>";
    }

	public static function overrideContentFilter() {
		global $wp_query, $post;

		$is_preview = ( array_key_exists( 'cred_form_preview', $_GET ) ||
			array_key_exists( 'cred_user_form_preview', $_GET ) );

		$is_edit_form = ( array_key_exists( 'cred-edit-form', $_GET )
			|| array_key_exists( 'cred-edit-user-form', $_GET ));

		// if it is front page and form preview is required
		if ( ( $is_preview
				// if post edit url is given
				|| $is_edit_form )
			&& is_singular()
		) {

			do_action( 'toolset_forms_enqueue_frontend_form_assets' );
			/*
		     * Fixing oceanWP forcing it to use the_content under preview and making filter usable as well
		     */
			add_filter( 'theme_mod_ocean_blog_entry_excerpt_length', array( __CLASS__, 'fix_ocean_blog_entry_excerpt_length_to_display_the_content' ) );

			/*
			 * Replace post content with edit form if post editing url is given
			 */
			add_filter( 'the_content', array( __CLASS__, 'replaceContentWithForm' ), 1000 );
			add_action( 'woocommerce_after_main_content', array( __CLASS__, 'replaceContentWithForm' ) );
		}
	}

	/**
	 * OceanWP Action Callback Fix compatibility issue with oceanWP theme
	 * where the_content filter is not applied unless the value is '500'
	 */
	public static function fix_ocean_blog_entry_excerpt_length_to_display_the_content() {
		return '500';
	}

	/**
	 * @param string|null $content
	 *
	 * @return bool|null
	 */
	public static function replaceContentWithForm( $content = null ) {
		global $post, $wp_query;

		//Modification for cred edit link inside a template
		if ( isset( $_GET['_id'] ) ) {
			$post->ID = intval( $_GET['_id'] );
		}

		//resolve problem when view templates are added in sidebar widgets
		remove_filter( 'the_content', array( __CLASS__, 'replaceContentWithForm' ), 1000 );

		// if it is front page and form preview is required

		if ( current_action() == 'woocommerce_after_main_content' ) {

			remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10, 0 );
			remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );
			remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10, 0 );

			if ( array_key_exists( 'cred_form_preview', $_GET ) /* && is_front_page() */ ) {
				echo CRED_CRED::get_form_builder()->get_form( intval( $_GET['cred_form_preview'] ), null, 0, true );
			}
			if ( array_key_exists( 'cred_user_form_preview', $_GET ) /* && is_front_page() */ ) {
				echo CRED_CRED::get_form_builder()->get_form( intval( $_GET['cred_user_form_preview'] ), null, 0, true );
			}

			return false;
		}

		if ( array_key_exists( 'cred_form_preview', $_GET ) /* && is_front_page() */ ) {
			return CRED_CRED::get_form_builder()->get_form( intval( $_GET['cred_form_preview'] ), null, 0, true );
		}

		if ( array_key_exists( 'cred_user_form_preview', $_GET ) /* && is_front_page() */ ) {
			return CRED_CRED::get_form_builder()->get_form( intval( $_GET['cred_user_form_preview'] ), null, 0, true );
		}

		global $_creds_created, $_user_creds_created;

		$user_id = ( isset( $_GET['user_id'] ) ) ? $_GET['user_id'] : null;

		if ( ! isset( $_creds_created ) ) {
			$_creds_created = array();
		}

		if ( ! isset( $_user_creds_created ) ) {
			$_user_creds_created = array();
		}

		if ( ! empty( $_creds_created ) && in_array( $_GET['cred-edit-form'], $_creds_created ) ) {
			return apply_filters( 'the_content', $content );
		}

		if ( ! empty( $_user_creds_created ) && in_array( $_GET['cred-edit-user-form'] . "-" . $user_id, $_user_creds_created ) ) {
			return apply_filters( 'the_content', $content );
		}

		if ( isset( $_GET['cred-edit-form'] ) && ( strpos( $content, 'cred-edit-form=' . $_GET['cred-edit-form'] ) !== false ) ||
			( array_key_exists( 'cred-edit-form', $_GET ) /* && is_singular() */ && ! is_admin() )
		) {
			array_push( $_creds_created, $_GET['cred-edit-form'] );
			return CRED_CRED::get_form_builder()->get_form( self::getLocalisedID( intval( $_GET['cred-edit-form'] ) ), $post->ID );
		}

		if ( $_GET['cred-edit-user-form'] && ( strpos( $content, 'cred-edit-user-form=' . $_GET['cred-edit-user-form'] ) !== false ) ||
			( array_key_exists( 'cred-edit-user-form', $_GET ) /* && is_singular() */ && ! is_admin() )
		) {
			array_push( $_user_creds_created, $_GET['cred-edit-user-form'] . "-" . $user_id );
			return CRED_CRED::get_form_builder()->get_form( self::getLocalisedID( intval( $_GET['cred-edit-user-form'] ) ), $user_id );
		}

		if ( array_key_exists( 'cred-edit-form', $_GET ) /* && is_singular() */ && ! is_admin() ) {

			if ( strpos( $content, 'cred-edit-form=' . $_GET['cred-edit-form'] ) !== false ) {
				array_push( $_creds_created, $_GET['cred-edit-form'] );
				// Show if the content has a cred-edit-form link.
				//CRED_Loader::load('CLASS/Form_Builder');
				// get a localised form if exists
				//return CRED_Form_Builder::getForm(self::getLocalisedID(intval($_GET['cred-edit-form'])), $post->ID, false);
				return CRED_CRED::get_form_builder()->get_form( self::getLocalisedID( intval( $_GET['cred-edit-form'] ) ), $post->ID );
			} else {
				// Check if it's called from the_content function or wpv-post-body function.
				// phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
				$db = debug_backtrace();
				foreach ( $db as $n => $dbf ) {
					if ( isset( $dbf['function'] ) &&
						(
						( $dbf['function'] == 'the_content' || $dbf['function'] == 'wpv_shortcode_wpv_post_body' ) ) ||
						( $dbf['function'] == 'apply_filters' && in_array( 'the_content', $dbf['args'] ) )
					) {
						array_push( $_creds_created, $_GET['cred-edit-form'] );
						return CRED_CRED::get_form_builder()->get_form( self::getLocalisedID( intval( $_GET['cred-edit-form'] ) ), $post->ID );
					}
				}
			}
		}

		if ( array_key_exists( 'cred-edit-user-form', $_GET ) /* && is_singular() */ && ! is_admin() ) {

			if ( strpos( $content, 'cred-edit-user-form=' . $_GET['cred-edit-user-form'] ) !== false ) {
				array_push( $_user_creds_created, $_GET['cred-edit-user-form'] . "-" . $user_id );
				return CRED_CRED::get_form_builder()->get_form( self::getLocalisedID( intval( $_GET['cred-edit-form'] ) ), $post->ID );
			} else {
				// Check if it's called from the_content function or wpv-post-body function.
				// phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
				$db = debug_backtrace();
				foreach ( $db as $n => $dbf ) {
					if ( isset( $dbf['function'] ) &&
						(
						( $dbf['function'] == 'the_content' || $dbf['function'] == 'wpv_shortcode_wpv_post_body' ) ) ||
						( $dbf['function'] == 'apply_filters' && in_array( 'the_content', $dbf['args'] ) )
					) {
						array_push( $_user_creds_created, $_GET['cred-edit-user-form'] . "-" . $user_id );
						//CRED_Loader::load('CLASS/Form_Builder');
						//return CRED_Form_Builder::getUserForm(self::getLocalisedID(intval($_GET['cred-edit-user-form'])), $user_id, false);
						return CRED_CRED::get_form_builder()->get_form( self::getLocalisedID( intval( $_GET['cred-edit-user-form'] ) ), $user_id );
					}
				}
			}
		}

		// else do nothing
		return $content;
	}

	/**
	 * Description: Display a link to delete a post
	 *
	 * Parameters:
	 * 'action'=> either 'trash' (sent post to Trash) or 'delete' (completely delete post)
	 * 'post' => [optional] Post ID of post to delete
	 * 'text'=> [optional] Text to use for link
	 * 'class'=> [optional] css class to apply to link
	 * 'style'=> [optional] css style to apply to link
	 * 'message'=> [optional] message to confirm action
	 *
	 * Example usage:
	 *
	 *  Display link for deleting car custom post with ID 145
	 * [cred_delete_post_link post="145" text="Delete this car"]
	 *
	 * There is also a php tag to use in templates and themes that has the same functionality as the shortcode
	 * <?php cred_delete_post_link($post_id, $text, $action, $class, $style); ?>
	 *
	 * Link:
	 *
	 *
	 * Note:
	 *  'post'> if post is omitted then current post_id will be used, for example inside Loop
	 *  'text'> can use meta-variables like %TITLE% and %ID%
	 *
	 *
	 * */
	public static function credDeletePostLinkShortcode( $atts, $content = null ) {
		global $post, $current_user;

		//cred-290 YT
		$curr_post = ( isset( $post ) && isset( $post->ID ) ) ? $post->ID : '';

		$params = shortcode_atts( array(
			'post' => $curr_post,
			'text' => '',
			'redirect' => '',
			'action' => '',
			'class' => '',
			'style' => '',
			'message' => '',
			'message_show' => 1,
			'message_after' => '',
		), $atts );

		if (
			null !== $content
			&& empty( $params['text'] )
		) {
			$params['text'] = $content;
		}

		return self::cred_delete_post_link( $params['post'], $params['text'], $params['action'], $params['class'], $params['style'], $params['message'], $params['message_after'], $params['message_show'], isset( $params['redirect'] ) && ! empty( $params['redirect'] ) ? $params['redirect'] : 0 );
	}

	/**
	 * Description: Display a link to edit a post with given form
	 *
	 * Parameters:
	 * 'form' => Form Title or Form ID of form to use.
	 * 'post' => [optional] Post ID of post to edit with this form
	 * 'text'=> [optional] Text to use for link
	 * 'class'=> [optional] css class to apply to link
	 * 'style'=> [optional] css style to apply to link
	 * 'target'=> [optional] open link in the specific target (_blank,_self,_top)
	 * 'attributes'=> [optional] additional html attrubutes (eg onclick)
	 *
	 * Example usage:
	 *
	 *  Display link for editing car custom post with ID 145 (use form with title "Edit Car")
	 * [cred_link_form form="Edit Car" post="145" text="Edit this car"]
	 *
	 * There is also a php tag to use in templates and themes that has the same functionality as the shortcode
	 * <?php cred_edit_post_link($form, $post_id, $text, $class, $style, $target, $attributes); ?>
	 *
	 * Link:
	 *
	 *
	 * Note:
	 *  'post'> if post is omitted then current post_id will be used, for example inside Loop
	 *  'text'> can use meta-variables like %TITLE% and %ID%
	 *
	 * */
	public static function credFormLinkShortcode( $atts ) {
		global $post;

		$params = shortcode_atts( array(
			'form' => '',
			'post' => '',
			'text' => '',
			'class' => '',
			'style' => '',
			'target' => '',
			'attributes' => '',
		), $atts );

		$form = cred_get_object_form( $params['form'], CRED_FORMS_CUSTOM_POST_NAME );
		if ( ! $form ) {
			if ( current_user_can( 'manage_options' ) ) {
				return sprintf( __( "The Toolset Form %s does not exist", "wp-cred" ), $params['form'] );
			}
			return;
		}

		return self::cred_edit_post_link( $params['form'], $params['post'], $params['text'], $params['class'], $params['style'], $params['target'], $params['attributes'] );
	}

	public static function credUserFormLinkShortcode( $atts ) {
		$params = shortcode_atts( array(
			'form' => '',
			'user' => '',
			'post' => '',
			'text' => '',
			'class' => '',
			'style' => '',
			'target' => '',
			'attributes' => '',
		), $atts );

		$form = cred_get_object_form( $params['form'], CRED_USER_FORMS_CUSTOM_POST_NAME );
		if ( ! $form ) {
			if ( current_user_can( 'manage_options' ) ) {
				return sprintf( __( "The Toolset User Form %s does not exist", "wp-cred" ), $params['form'] );
			}
			return;
		}

		// Back compatibility to Toolset Forms 1.6.x
		$user = ( isset( $params['user'] ) && ! empty( $params['user'] ) ) ? $params['user'] : ( ( isset( $params['post'] ) && ! empty( $params['post'] ) ) ? $params['post'] : "" );

		return self::cred_edit_user_link( $params['form'], $user, $params['text'], $params['class'], $params['style'], $params['target'], $params['attributes'] );
	}

	/**
	 * Description: Display a link to create a child post with given form and parent
	 *
	 * Parameters:
	 * 'form' => Page ID containing teh child form.
	 * 'parent_type' => Post Type of Parent
	 * 'parent_id' => [optional] Parent to set for the child
	 * 'text'=> [optional] Text to use for link
	 * 'class'=> [optional] css class to apply to link
	 * 'style'=> [optional] css style to apply to link
	 * 'target'=> [optional] open link in the specific target (_blank,_self,_top)
	 * 'attributes'=> [optional] additional html attrubutes (eg onclick)
	 *
	 * Example usage:
	 *
	 *  Display link for editing car custom post with ID 145 (use form with title "Edit Car")
	 * [cred_child_link_form form="New  Review" parent="145" parent_type='book' text="Add new Review"]
	 *
	 * Link:
	 *
	 *
	 * Note:
	 *
	 *
	 * */
	public static function credChildFormLinkShortcode( $atts ) {
		global $post;

		$params = shortcode_atts( array(
			'form' => null,
			/* 'parent_type' => null, */
			'parent_id' => - 1,
			'text' => '',
			'class' => '',
			'style' => '',
			'target' => '_self',
			'attributes' => '',
		), $atts );

		return self::cred_child_link_form( $params['form'], $params['parent_id']/* ,$params['parent_type'] */, $params['text'], $params['class'], $params['style'], $params['target'], $params['attributes'] );
	}

	/**
	 * Description: Display a Toolset Form
	 *
	 * Parameters:
	 * 'form' => Form Title or Form ID of form to display.
	 * 'post' => [optional] Post ID of post to edit with this form
	 *
	 * Example usage:
	 *
	 *  Display form for editing car custom post with ID 145 (use form with title "Edit Car")
	 * [cred-form form="Edit Car" post="145"]
	 *  Display form to create a car post (use form with title "Create Car")
	 * [cred_form form="Create Car"]
	 *  Display form with ID 120
	 * [cred_form form="120"]
	 *
	 * There is also a php tag to use in templates and themes that has the same functionality as the shortcode
	 * <?php cred_form($form,$post); ?>
	 *
	 * Link:
	 *
	 *
	 * Note:
	 *  'post'> if post is omitted and form is an edit form, then current post_id will be used, for example inside Loop
	 */
	public static function credFormShortcode( $atts ) {
		global $post;
		if ( is_null( $post ) && ! is_archive() ) {
			return null;
		}
		/**
		 * clone post object to revert after form
		 */
		if ( isset( $post ) ) {
			$original_post = clone $post;
		}

		$params = shortcode_atts( array(
			'form' => '',
			'post' => '',
		), $atts );

		$out = self::cred_form( $params['form'], $params['post'] );
		wp_reset_query();
		/**
		 * revert orginal $post
		 */
		if ( isset( $original_post ) ) {
			$post = $original_post;
			unset( $original_post );
		}

		return $out;
	}

	public static function credUserFormShortcode( $atts ) {
		global $post;
		if ( is_null( $post ) && ! is_archive() ) {
			return null;
		}

		/**
		 * clone post object to revert after form
		 */
		if ( isset( $post ) ) {
			$original_post = clone $post;
		}

		$params = shortcode_atts( array(
			'form' => '',
			'user' => '',
			'post' => '',
		), $atts );

		if ( isset( $_GET['user_id'] ) ) {
			$user = (int) $_GET['user_id'];
		} else {
			//Back compatibility to Toolset Forms 1.6.x
			$user = ( isset( $params['user'] ) && ! empty( $params['user'] ) ) ? $params['user'] : ( ( isset( $params['post'] ) && ! empty( $params['post'] ) ) ? $params['post'] : "" );
		}

		$form = cred_get_object_form( $params['form'], CRED_USER_FORMS_CUSTOM_POST_NAME );
		if ( ! $form ) {
			if ( current_user_can( 'manage_options' ) ) {
				return sprintf( __( "The Toolset User Form %s does not exist", "wp-cred" ), $params['form'] );
			}
			return '';
		}

		$type = "edit";
		if ( empty( $user ) ) {
			$formData = new CRED_Form_Data( $form->ID, CRED_USER_FORMS_CUSTOM_POST_NAME, false );
			$fields = $formData->getFields();
			$type = $fields['form_settings']->form['type'];
		}
		if ( $type == 'edit' ) {
			if ( empty( $user ) ) {
				$user_id = get_current_user_id();
				if ( $user_id == 0 ) {
					$out = __( 'No user specified', 'wp-cred' );
				} else {
					$out = self::cred_user_form( $params['form'], $user_id );
				}
			} else {
				$out = self::cred_user_form( $params['form'], $user );
			}
		} else {
			$out = self::cred_user_form( $params['form'], $user );
		}

		wp_reset_query();
		/**
		 * revert orginal $post
		 */
		if ( isset( $original_post ) ) {
			$post = $original_post;
			unset( $original_post );
		}

		return $out;
	}

	// auxiliary functions
	public static function _true() {
		return true;
	}

	public static function _false() {
		return false;
	}

	public static function strHash( $str ) {
		return md5( preg_replace( '/\s+/', '', $str ) );
	}

	public static function mergeArrays() {
		if ( func_num_args() < 1 ) {
			return;
		}

		$arrays = func_get_args();
		$merged = array_shift( $arrays );

		$isTargetObject = false;
		if ( is_object( $merged ) ) {
			$isTargetObject = true;
			$merged = (array) $merged;
		}

		foreach ( $arrays as $arr ) {
			$isObject = false;
			if ( is_object( $arr ) ) {
				$isObject = true;
				$arr = (array) $arr;
			}

			foreach ( $arr as $key => $val ) {
				if ( array_key_exists( $key, $merged ) && ( is_array( $val ) || is_object( $val ) ) ) {
					$merged[ $key ] = self::mergeArrays( $merged[ $key ], $arr[ $key ] );
					if ( is_object( $val ) ) {
						$merged[ $key ] = (object) $merged[ $key ];
					}
				} else {
					$merged[ $key ] = $val;
				}
			}

			/* if ($isObject)
              {
              $arr=(object)$arr;
              } */
		}
		if ( $isTargetObject ) {
			$isTargetObject = false;
			$merged = (object) $merged;
		}

		return $merged;
	}

	public static function applyDefaults( $arr, $defaults = array() ) {
		if ( ! empty( $defaults ) ) {
			foreach ( $arr as $ii => $item ) {
				$arr[ $ii ] = self::mergeArrays( $defaults, $arr[ $ii ] );
			}
		}

		return $arr;
	}

	public static function filterByKeys( $a, $f ) {
		return array_intersect_key( (array) $a, array_flip( (array) $f ) );
	}

	public static function check_wpml_string() {
		global $WPML_String_Translation;

		return ( isset( $WPML_String_Translation ) && function_exists( 'icl_register_string' ) );
	}

	public static function getUsersByRole( $roles ) {
		global $wpdb;
		if ( ! is_array( $roles ) ) {
			$roles = explode( ",", $roles );
			array_walk( $roles, 'trim' );
		}
		$sql = '
            SELECT  u.ID, u.display_name, u.user_email
            FROM        ' . $wpdb->users . ' AS u INNER JOIN ' . $wpdb->usermeta . ' AS um
            ON      u.ID  = um.user_id
            WHERE   um.meta_key     =       \'' . $wpdb->prefix . 'capabilities\'
            AND     (
        ';
		$i = 1;
		foreach ( $roles as $role ) {
			$sql .= ' um.meta_value LIKE    \'%"' . cred_wrap_esc_like( $role ) . '"%\' ';
			if ( $i < count( $roles ) ) {
				$sql .= ' OR ';
			}
			$i ++;
		}
		$sql .= ' ) ';
		$sql .= ' ORDER BY u.display_name ';
		$users = $wpdb->get_results( $sql );

		return $users;
	}

	public static function clearCREDAutoDrafts() {
		_deprecated_function( __FUNCTION__, '2.5.7', '\OTGS\Toolset\CRED\Controller\DraftsManager\Post\Action::do_scheduled_action()' );
	}

	/**
	 * setupAdmin when embedded
	 *
	 * @deprecated since version 1.9
	 * @todo There is duplicated code here and in setJSandCSS!!!!
	 */
	public static function setupAdmin() {

		

		// determine current admin page
		self::getAdminPage( array(
			'post_type' => CRED_FORMS_CUSTOM_POST_NAME,
			'base' => 'admin.php',
			'pages' => array(
				'view-archives-editor',
				'views-editor',
				'CRED_Forms',
				'CRED_Fields',
				'CRED_Settings',
				'toolset-settings',
				'CRED_Help',
			),
		) );

		self::getAdminPage( array(
			'post_type' => CRED_USER_FORMS_CUSTOM_POST_NAME,
			'base' => 'admin.php',
			'pages' => array(
				'view-archives-editor',
				'views-editor',
				'CRED_User_Forms',
				'CRED_Fields',
				'CRED_Settings',
				'toolset-settings',
				'CRED_Help',
			),
		) );

		add_filter( 'toolset_promotion_screen_ids', array( __CLASS__, 'add_toolset_promotion_screen_id' ) );

		// setup js, css assets
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'onAdminEnqueueScripts' ) );

		// add custom js on certain pages
		add_action( 'admin_head-post.php', array( __CLASS__, 'jsForCredCustomPost' ) );
		add_action( 'admin_head-post-new.php', array( __CLASS__, 'jsForCredCustomPost' ) );

		if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'view-archives-editor' ||
				$_GET['page'] == 'views-editor' )
		) {
			add_action( 'admin_head', array( __CLASS__, 'jsForCredCustomPost' ) );
		}

		/**
		 * add debug information
		 */
		add_filter( 'icl_get_extra_debug_info', array( __CLASS__, 'getExtraDebugInfo' ) );
	}



	/**
	 * get_form_object
	 *
	 * @param string $form
	 * @param string $type
	 *
	 * @return \WP_Post|false|null
	 */
	public static function get_form_object( $form, $type ) {
		$form_object = self::get_form_by_title( $form, $type );
		if ( ! $form_object ) {
			$form_object = get_page_by_path( $form, OBJECT, $type );
		}

		return $form_object;
	}

	/**
	 * returns an array of translated form messages
	 *
	 * @param string[] $messages_array
	 * @param int $form_id
	 *
	 * @return array
	 */

	public static function get_form_messages( $messages_array, $form_id ) {
		if ( ! $form_id ) {
			return $messages_array;
		}

		$form_type = get_post_type( $form_id );

		switch ( $form_type ) {
			case 'cred_rel_form':
				return get_post_meta( $form_id, 'messages', true );
			default:
				$forms_model = CRED_Loader::get( 'MODEL/Forms' );
				$form_post = get_post( $form_id );
				$form_extra_settings = $forms_model->getFormCustomField( $form_id , 'extra' );
				if ( isset( $form_extra_settings->messages ) ) {
					$messages_array = $form_extra_settings->messages;
					foreach( $messages_array as $message_key => $message_value ) {
						$messages_array[$message_key] = cred_translate( 'Message_' . $message_key, $message_value, 'cred-form-' . $form_post->post_title . '-' . $form_id );
					}
				}
				break;
		}

        return $messages_array;
    }

    /**
     * Get the per_page variable used Fields and Custom_Fields list tables
     *
     * @return int
     */
	public static function get_current_screen_per_page() {
	    $user = get_current_user_id();
	    $screen = get_current_screen();
	    $option = $screen->get_option( 'per_page', 'option' );
	    $per_page = get_user_meta( $user, $option, true );
	    if ( empty( $per_page ) || $per_page < 1 ) {
		    $per_page = $screen->get_option( 'per_page', 'default' );
	    }

	    return $per_page;
    }

}

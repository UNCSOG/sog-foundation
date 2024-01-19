<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Compatibility class for WooCommerce, despite WooCommerce Blocks
 *
 * @since 2.2.1
 */
class WPV_Compatibility_WooCommerce {

	protected static $instance              = null;

	private $is_woocommerce_installed       = false;
	private $is_woocommerce_views_installed = false;
	private $is_installer_installed         = false;

	private $is_asset_required              = false;

	private $current_theme                  = false;

	const WCV_SINGLE_TEMPLATE_SETTING       = 'woocommerce_views_theme_template_file';
	const WCV_ARCHIVE_TEMPLATE_SETTING      = 'woocommerce_views_theme_archivetemplate_file';

	/**
	 * Get or generate an instance of WPV_Compatibility_WooCommerce
	 *
	 * @return null|WPV_Compatibility_WooCommerce
	 *
	 * @since 2.2.1
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new WPV_Compatibility_WooCommerce();
		}
		return self::$instance;
	}

	/**
	 * WPV_Compatibility_WooCommerce constructor.
	 *
	 * @since 2.2.1
	 */
	function __construct() {

		add_action( 'plugins_loaded',       array( $this, 'plugins_loaded' ), 99 );
		add_action( 'after_setup_theme',    array( $this, 'after_setup_theme' ), 99 );
		add_action( 'init',                 array( $this, 'register_assets' ) );
		add_action( 'init',                 array( $this, 'register_admin_notices' ) );
		add_action( 'admin_enqueue_scripts',    array( $this, 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_wpv_wcv_missing_mandatory_dismiss',        array( $this, 'wpv_wcv_missing_mandatory_dismiss' ) );
		add_action( 'wp_ajax_wpv_wcv_switch_single_template',           array( $this, 'wpv_wcv_switch_single_template' ) );
		add_action( 'wp_ajax_wpv_wcv_switch_single_template_dismiss',   array( $this, 'wpv_wcv_switch_single_template_dismiss' ) );

		add_action( 'wp_ajax_wpv_wcv_switch_archive_template',          array( $this, 'wpv_wcv_switch_archive_template' ) );
		add_action( 'wp_ajax_wpv_wcv_switch_archive_template_dismiss',  array( $this, 'wpv_wcv_switch_archive_template_dismiss' ) );

		add_action( 'wpv_action_wpv_before_forgot_password_form',      array( $this, 'wpv_action_wpv_before_forgot_password_form' ) );
		add_action( 'wpv_action_wpv_after_forgot_password_form',       array( $this, 'wpv_action_wpv_after_forgot_password_form' ) );

		add_action( 'wpv_action_after_archive_set', array( $this, 'fix_legacy_woocommerce_taxonomy_archives' ) );

	}

	/**
	 * Initialize the flags on whether the right plugins are available.
	 *
	 * @since 2.2.1
	 */
	public function plugins_loaded() {

		$this->is_woocommerce_installed         = class_exists( 'WooCommerce' );
		$this->is_woocommerce_views_installed   = class_exists( 'Class_WooCommerce_Views' );

	}

	/**
	 * Initialize the falg on whether WP_INstaller is available.
	 *
	 * @note WP_Installer gets loaded at after_setup_theme so it requires this special management
	 * @note Store the current theme slug for later usage
	 *
	 * @since 2.2.1
	 */
	public function after_setup_theme() {
		$this->is_installer_installed           = class_exists( 'WP_Installer' );
		$this->current_theme                    = get_stylesheet();
	}

	/**
	 * Register assets.
	 *
	 * @since 2.2.1
	 */
	public function register_assets() {
		wp_register_script( 'views-compatibility-woocommerce', WPV_URL_EMBEDDED . '/res/js/views_compatibility_woocommerce.js', array( 'jquery', 'jquery-ui-dialog', 'underscore' ), WPV_VERSION, true );
		$compat_woocommerce_i18n = array(
			'nonce'     => wp_create_nonce( 'wpv_wcv_compatibility_nonce' ),
			'dialog'    => array(
								'title'     => __( 'Toolset WooCommerce Blocks templates', 'wpv-views' ),
								'text'      => sprintf(
													__( 'You can choose a different PHP template for WooCommerce products in %1$sToolset->WooCommerce Blocks%2$s.', 'wpv-views' ),
													'<a href="' . esc_url( admin_url( 'admin.php?page=wpv_wc_views' ) ) . '" target="_blank">',
													'</a>'
												),
								'button'    => __( 'OK', 'wpv-views' )
							)
		);
		wp_localize_script( 'views-compatibility-woocommerce', 'wpv_wcv_i18n', $compat_woocommerce_i18n );
	}

	/**
	 * Register WooCommerce related admin notices.
	 *
	 * Different admin notices get registered depending on whether wooCommerce Blocks is installed or not.
	 *
	 * @since 2.2.1
	 */
	public function register_admin_notices() {
		if ( $this->is_woocommerce_installed ) {
			if ( $this->is_woocommerce_views_installed ) {
				$this->recommend_update_woocommerceviews_single();
				$this->recommend_update_woocommerceviews_archive();
			} else {
				$this->recommend_woocommerceviews();
			}
		}
	}

	/**
	 * Enqueue admin notices.
	 *
	 * @since 2.2.1
	 */
	public function admin_enqueue_scripts() {
		if ( $this->is_asset_required ) {
			wp_enqueue_script( 'views-compatibility-woocommerce' );
		}
	}

	/**
	 * Register the generic admin notice when Views and WooCommerce are available, but WooCommerce Blocks is not,
	 * to be shown in almost all the backend.
	 *
	 * @since 2.2.1
	 */
	public function recommend_woocommerceviews() {
		global $pagenow;
		if (
			'plugin-install.php' == $pagenow
			&& isset( $_GET['tab'] )
			&& 'commercial' == $_GET['tab']
		) {
			return;
		}

		if (
			current_user_can( 'activate_plugins' )
			&& (
				! isset( $_GET['page'] )
				|| ! in_array( $_GET['page'], array( 'views-editor', 'ct-editor', 'view-archives-editor' ) )
			)
			&& apply_filters( 'wpv_filter_wpv_is_dismissed_notice', false, array( 'id' => 'wc_active_wcv_missing', 'type' => 'global' ) )
		) {
			return;
		}

		$this->is_asset_required = true;
		add_filter( 'wptoolset_filter_admin_notices', array( $this, 'recommend_woocommerceviews_missing' ) );
	}

	/**
	 * Register admin notice to recommend installing WooCommerce Blocks.
	 * When on a CT or WPA edit page with the object assigned somehow to WooCommerce products,
	 * display a slightly different notice.
	 *
	 * @param $notices  array   Already registered notices
	 *
	 * @return array
	 */
	public function recommend_woocommerceviews_missing( $notices = array() ) {

		if (
			isset( $_GET['page'] )
			&& in_array( $_GET['page'], array( 'views-editor', 'ct-editor', 'view-archives-editor' ) )
		) {
			if ( apply_filters( 'wpv_filter_wpv_is_dismissed_notice', false, array( 'id' => 'wc_active_wcv_missing_mandatory', 'type' => 'global' ) ) ) {
				return;
			}
			switch( $_GET['page'] ) {
				case 'views-editor':
					break;
				case 'ct-editor':
					if ( $this->ct_is_assigned_to_product_post_type( (int) wpv_getget( 'ct_id', 0 ) ) ) {
						$links = '';
						if ( $this->is_installer_installed ) {
							$links .= '<a href="'
						         . esc_url( admin_url( 'plugin-install.php?tab=commercial' ) )
						         . '" class="button button-primary">'
						         . __( 'Install Toolset WooCommerce Blocks', 'wpv-views' )
						         . '</a> ';
						}
						$links .= '<a href="'
							. 'https://toolset.com/account/downloads/?utm_source=plugin&utm_medium=gui&utm_campaign=blocks'
							. '" target="_blank">'
							. __( 'Download from your Toolset account', 'wpv-views' )
							. '</a>'
							. ' | '
							. '<a class="js-wpv-dismiss js-wpv-wcv-missing-mandatory-dismiss" href="' . esc_url( add_query_arg( array( 'wpv_dismiss_global_notice' => 'wc_active_wcv_missing_mandatory' ) ) ) . '">'
							. __( 'Dismiss', 'wpv-views' )
							. '</a>';
						$notice_text = '<p>'
							. __( 'To design templates for WooCommerce products, you need to have the <strong>Toolset WooCommerce Blocks</strong> plugin installed.', 'wpv-views' )
							. '</p><p>'
						    . $links
							. '</p>';
						$args = array(
							'notice_class' => 'notice notice-error js-wpv-wcv-missing-mandatory',
							'notice_text' => $notice_text,
							'notice_type' => 'global'
						);
						$notices['wc_active_wcv_missing_mandatory'] = $args;
					}
					break;
				case 'view-archives-editor':
					$current_wpa = isset( $_GET['view_id' ] ) ? (int) $_GET['view_id' ] : 0;
					$current_wpa_archive_assigned_post_types = array();
					if ( $current_wpa ) {
						$current_wpa_object = new WPV_WordPress_Archive_Embedded( $current_wpa );
						$current_wpa_archive_assigned = $current_wpa_object->get_assigned_loops( 'post_type' );
						$current_wpa_archive_assigned_post_types = wp_list_pluck( $current_wpa_archive_assigned, 'post_type_name' );
					}
					if ( in_array( 'product', $current_wpa_archive_assigned_post_types ) ) {
						$links = '';
						if ( $this->is_installer_installed ) {
							$links .= '<a href="'
						          . esc_url( admin_url( 'plugin-install.php?tab=commercial' ) )
						          . '" class="button button-primary">'
						          . __( 'Install Toolset WooCommerce Blocks', 'wpv-views' )
						          . '</a> ';
						}
						$links .= '<a href="'
							. 'https://toolset.com/account/downloads/?utm_source=plugin&utm_medium=gui&utm_campaign=blocks'
							. '" target="_blank">'
							. __( 'Download from your Toolset account', 'wpv-views' )
							. '</a>'
							. ' | '
							. '<a class="js-wpv-dismiss js-wpv-wcv-missing-mandatory-dismiss" href="' . esc_url( add_query_arg( array( 'wpv_dismiss_global_notice' => 'wc_active_wcv_missing_mandatory' ) ) ) . '">'
							. __( 'Dismiss', 'wpv-views' )
							. '</a>';
						$notice_text = '<p>'
							. __( 'To design the WooCommerce products archive, you need to have the <strong>Toolset WooCommerce Blocks</strong> plugin installed.', 'wpv-views' )
							. '</p><p>'
							. $links
							. '</p>';
						$args = array(
							'notice_class' => 'notice notice-error js-wpv-wcv-missing-mandatory',
							'notice_text' => $notice_text,
							'notice_type' => 'global'
						);
						$notices['wc_active_wcv_missing_mandatory'] = $args;
					}
					break;
			}
		} else if ( current_user_can( 'activate_plugins' ) ) {
			if ( $this->is_installer_installed ) {
				$links = '<a href="'
				         . esc_url( admin_url( 'plugin-install.php?tab=commercial' ) )
				         . '" class="button button-primary">'
				         . __( 'Install Toolset WooCommerce Blocks', 'wpv-views' )
				         . '</a>';
			} else {
				$links = '<a href="'
				         . 'https://toolset.com/account/?utm_source=plugin&utm_medium=gui&utm_campaign=blocks'
				         . '" class="button button-primary">'
				         . __( 'Download Toolset WooCommerce Blocks', 'wpv-views' )
				         . '</a>';
			}
			$notice_text = '<p>'
			               . sprintf(
				               __( 'To design templates for WooCommerce products, you need to use <a href="%s" title="Getting started with Toolset WooCommerce Blocks">Toolset WooCommerce Blocks</a>.', 'wpv-views' ),
				               'https://toolset.com/course/custom-woocommerce-sites/?utm_source=plugin&utm_medium=gui&utm_campaign=blocks'
			               )
			               . '</p><p>'
			               . $links
			               . '  <a class="js-wpv-dismiss" href="' . esc_url( add_query_arg( array( 'wpv_dismiss_global_notice' => 'wc_active_wcv_missing' ) ) ) . '">'
			               . __( 'Dismiss', 'wpv-views' )
			               . '</a>'
			               . '</p>';
			$args = array(
				'notice_class' => 'notice notice-warning',
				'notice_text' => $notice_text,
				'notice_type' => 'global'
			);
			$notices['wc_active_wcv_missing'] = $args;
		}
		return $notices;
	}

	/**
	 * Determine if the given Content Template is assigned to the Products post type.
	 *
	 * @param integer   $current_ct     The Content Template under review
	 * @param string    $usage          The usage of the Content Template we check for. If empty, the function checks for both usages.
	 *
	 * @return bool
	 */
	private function ct_is_assigned_to_product_post_type( $current_ct, $usage = '' ) {
		$is_assigned = false;
		$current_ct_assigned_post_types = array();
		if ( $current_ct ) {
			$current_ct_object = new WPV_Content_Template_Embedded( $current_ct );
			$current_ct_singular_assigned = $current_ct_object->get_assigned_single_post_types();
			$current_ct_singular_assigned_post_types = wp_list_pluck( $current_ct_singular_assigned, 'post_type_name' );
			$current_ct_plural_assigned = $current_ct_object->get_assigned_loops( 'post_type' );
			$current_ct_plural_assigned_post_types = wp_list_pluck( $current_ct_plural_assigned, 'post_type_name' );

			switch ( $usage ) {
				case 'single':
					$current_ct_assigned_post_types = $current_ct_singular_assigned_post_types;
					break;
				case 'archive':
					$current_ct_assigned_post_types = $current_ct_plural_assigned_post_types;
					break;
				default:
					$current_ct_assigned_post_types = array_unique( array_merge( $current_ct_singular_assigned_post_types, $current_ct_plural_assigned_post_types ) );
					break;
			}
		}

		if ( in_array( 'product', $current_ct_assigned_post_types ) ) {
			$is_assigned = true;
		}

		return $is_assigned;
	}

	public function wpv_wcv_missing_mandatory_dismiss() {

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_wcv_compatibility_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		do_action( 'wpv_action_wpv_dismiss_notice', array( 'type' => 'global', 'id' => 'wc_active_wcv_missing_mandatory' ) );

		wp_send_json_success();

	}

	/**
	 * Register the admin notice when all plugins are available but the WooCommerce Blocks setting for the singular template is wrong.
	 *
	 * @since 2.2.1
	 */
	public function recommend_update_woocommerceviews_single() {
		if (
			! isset( $_GET['page'] )
			|| ! in_array( $_GET['page'], array( 'ct-editor' ) )
		) {
			return;
		}

		if ( apply_filters( 'wpv_filter_wpv_is_dismissed_notice', false, array( 'id' => 'wc_active_wcv_active_wrong_single_template', 'type' => 'global' ) ) ) {
			return;
		}

		if ( $this->ct_is_assigned_to_product_post_type( (int) wpv_getget( 'ct_id', 0 ), 'single' ) ) {
			$stored_wcv_product_single_template = get_option( WPV_Compatibility_WooCommerce::WCV_SINGLE_TEMPLATE_SETTING, false );
			if (
				is_array( $stored_wcv_product_single_template )
				&& isset( $stored_wcv_product_single_template[ $this->current_theme ] )
			) {
				$stored_wcv_product_single_template_path    = $stored_wcv_product_single_template[ $this->current_theme ];
				$valid_wcv_product_single_template_path     = WOOCOMMERCE_VIEWS_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'single-product.php';
				if ( $stored_wcv_product_single_template_path == $valid_wcv_product_single_template_path ) {
					return;
				}
			}

			$this->is_asset_required = true;
			add_filter( 'wptoolset_filter_admin_notices', array( $this, 'recommend_update_woocommerceviews_single_settings' ) );
		}
	}

	/**
	 * Display the admin notice when all plugins are available but the WooCommerce Blocks setting for the singular template is wrong.
	 *
	 * @since 2.2.1
	 */
	public function recommend_update_woocommerceviews_single_settings( $notices ) {

		$links = '<a href="'
		         . esc_url( admin_url( 'admin.php?page=wpv_wc_views' ) )
		         . '" class="button button-primary js-wpv-wcv-switch-single-template">'
		         . __( 'Switch to the WooCommerce Blocks template', 'wpv-views' )
		         . '</a> <a href="#" class="js-wpv-wcv-switch-single-template-dismiss">'
				 . __( 'Dismiss', 'wpv-views' )
				 . '</a>';
		$notice_text = '<p>'
			. __( 'WooCommerce is currently overriding the template for products and your design will not be used.', 'wpv-views' )
			. '</p><p>'
			. $links
			.'</p>';
		$args = array(
			'notice_class' => 'notice notice-error js-wpv-notice-wcv-switch-single-template',
			'notice_text' => $notice_text,
			'notice_type' => 'global'
		);
		$notices['wc_active_wcv_active_wrong_single_template'] = $args;

		return $notices;
	}

	/**
	 * Switch the WooCommerce Blocks setting for the singular template if it was wrong.
	 *
	 * @since 2.2.1
	 */
	public function wpv_wcv_switch_single_template() {

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_wcv_compatibility_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		$single_template = array(
			$this->current_theme => WOOCOMMERCE_VIEWS_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'single-product.php'
		);
		update_option( WPV_Compatibility_WooCommerce::WCV_SINGLE_TEMPLATE_SETTING, $single_template );

		wp_send_json_success();

	}

	/**
	 * Dismiss the admin notice when all plugins are available but the WooCommerce Blocks setting for the singular template is wrong.
	 *
	 * @since 2.2.1
	 */
	public function wpv_wcv_switch_single_template_dismiss() {

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_wcv_compatibility_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		do_action( 'wpv_action_wpv_dismiss_notice', array( 'type' => 'global', 'id' => 'wc_active_wcv_active_wrong_single_template' ) );

		wp_send_json_success();

	}

	/**
	 * Register the admin notice when all plugins are available but the WooCommerce Blocks setting for the archive template is wrong.
	 *
	 * @since 2.2.1
	 */
	public function recommend_update_woocommerceviews_archive() {
		$page = wpv_getget( 'page', '' );

		if (
			'' == $page
			|| ! in_array( $page, array( 'view-archives-editor', 'ct-editor' ) )
		) {
			return;
		}

		if ( apply_filters( 'wpv_filter_wpv_is_dismissed_notice', false, array( 'id' => 'wc_active_wcv_active_wrong_archive_template', 'type' => 'global' ) ) ) {
			return;
		}

		if ( 'view-archives-editor' == $page ) {
			$current_wpa = isset( $_GET['view_id' ] ) ? (int) $_GET['view_id' ] : 0;
			$current_wpa_archive_assigned_post_types = array();
			if ( $current_wpa ) {
				$current_wpa_object = new WPV_WordPress_Archive_Embedded( $current_wpa );
				$current_wpa_archive_assigned = $current_wpa_object->get_assigned_loops( 'post_type' );
				$current_wpa_archive_assigned_post_types = wp_list_pluck( $current_wpa_archive_assigned, 'post_type_name' );
			}
			if ( ! in_array( 'product', $current_wpa_archive_assigned_post_types ) ) {
				return;
			}
		} else {
			// $page == 'ct-editor'
			if ( ! $this->ct_is_assigned_to_product_post_type( (int) wpv_getget( 'ct_id', 0 ), 'archive' ) ) {
				return;
			}
		}

		$stored_wcv_product_archive_template = get_option( WPV_Compatibility_WooCommerce::WCV_ARCHIVE_TEMPLATE_SETTING, false );
		if (
			is_array( $stored_wcv_product_archive_template )
			&& isset( $stored_wcv_product_archive_template[ $this->current_theme ] )
		) {
			$stored_wcv_product_archive_template_path   = $stored_wcv_product_archive_template[ $this->current_theme ];
			$valid_wcv_product_archive_template_path    = WOOCOMMERCE_VIEWS_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'archive-product.php';
			if ( $stored_wcv_product_archive_template_path == $valid_wcv_product_archive_template_path ) {
				return;
			}
		}

		$this->is_asset_required = true;
		add_filter( 'wptoolset_filter_admin_notices', array( $this, 'recommend_update_woocommerceviews_archive_settings' ) );
	}

	/**
	 * Display the admin notice when all plugins are available but the WooCommerce Blocks setting for the archive template is wrong.
	 *
	 * @since 2.2.1
	 */
	public function recommend_update_woocommerceviews_archive_settings( $notices ) {

		$links = '<a href="'
		         . esc_url( admin_url( 'admin.php?page=wpv_wc_views' ) )
		         . '" class="button button-primary js-wpv-wcv-switch-archive-template">'
		         . __( 'Switch to the WooCommerce Blocks template', 'wpv-views' )
		         . '</a> <a href="#" class="js-wpv-wcv-switch-archive-template-dismiss">'
		         . __( 'Dismiss', 'wpv-views' )
		         . '</a>';
		$notice_text = '<p>'
		               . __( 'WooCommerce is currently overriding the template for products archive and your design might not be used.', 'wpv-views' )
		               . '</p><p>'
		               . $links
		               .'</p>';
		$args = array(
			'notice_class' => 'notice notice-error js-wpv-notice-wcv-switch-archive-template',
			'notice_text' => $notice_text,
			'notice_type' => 'global'
		);
		$notices['wc_active_wcv_active_wrong_archive_template'] = $args;

		return $notices;
	}

	/**
	 * Switch the WooCommerce Blocks setting for the archive template if it was wrong.
	 *
	 * @since 2.2.1
	 */
	public function wpv_wcv_switch_archive_template() {

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_wcv_compatibility_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		$archive_template = array(
			$this->current_theme => WOOCOMMERCE_VIEWS_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'archive-product.php'
		);
		update_option( WPV_Compatibility_WooCommerce::WCV_ARCHIVE_TEMPLATE_SETTING, $archive_template );

		wp_send_json_success();

	}

	/**
	 * Dismiss the admin notice when all plugins are available but the WooCommerce Blocks setting for the archive template is wrong.
	 *
	 * @since 2.2.1
	 */
	public function wpv_wcv_switch_archive_template_dismiss() {

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_wcv_compatibility_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		do_action( 'wpv_action_wpv_dismiss_notice', array( 'type' => 'global', 'id' => 'wc_active_wcv_active_wrong_archive_template' ) );

		wp_send_json_success();

	}

	/**
	 * Remove the WooCommerce callback that modifies the wp_lostpassword_url.
	 *
	 * @since 2.3.0
	 */
	public function wpv_action_wpv_before_forgot_password_form() {
		if ( $this->is_woocommerce_installed ) {
			remove_filter( 'lostpassword_url', 'wc_lostpassword_url' );
		}
	}

	/**
	 * Restore the WooCommerce callback that modifies the wp_lostpassword_url.
	 *
	 * @since 2.3.0
	 */
	public function wpv_action_wpv_after_forgot_password_form() {
		if ( $this->is_woocommerce_installed ) {
			add_filter( 'lostpassword_url', 'wc_lostpassword_url', 10, 1 );
		}
	}

	/**
	 * Remove the WooCommerce fix for a product taxonomy archive loop when a WPA is assigned to it.
	 *
	 * @param int|null $wpa_id
	 *
	 * @since 2.6.0
	 */
	public function fix_legacy_woocommerce_taxonomy_archives( $wpa_id ) {
		if (
			null === $wpa_id
			|| ! $this->is_woocommerce_installed
			|| ! function_exists( 'is_product_taxonomy' )
			|| ! is_callable( array( 'WC_Template_Loader', 'unsupported_theme_init' ) )
		) {
			return;
		}
		if ( is_product_taxonomy() ) {
			remove_action( 'template_redirect', array( 'WC_Template_Loader', 'unsupported_theme_init' ) );
		}
	}

}

WPV_Compatibility_WooCommerce::get_instance();

<?php

namespace OTGS\Toolset\CRED\Controller\ExpirationManager\Post;

use OTGS\Toolset\CRED\Controller\ExpirationManager\Post as PostExpirationManager;
use OTGS\Toolset\CRED\Controller\ExpirationManager\Post\Action;
use OTGS\Toolset\CRED\Controller\ExpirationManager\Post\Notifications;

use OTGS\Toolset\CRED\Model\Forms\Post\Expiration\Settings as FormExpirationSettingsModel;

/**
 * Controller for singular post editors on post expirations.
 *
 * @since 2.3
 */
class Singular {

	const POST_EDIT_NONCE_NAME = 'cred-post-expiration-nonce';
	const POST_EDIT_NONCE_VALUE = 'cred-post-expiration-date';

	const POST_METABOX_NAMESPACE = 'cred_pe';
	const POST_META_TIME = '_cred_post_expiration_time';
	const POST_META_NOTIFICATION = '_cred_post_expiration_notifications';
	const POST_META_ACTION = '_cred_post_expiration_action';

	const SCRIPT_HANDLE = 'cred-post-expiration-singular';
	const SCRIPT_I18N = 'cred_post_expiration_singular_i18n';

	/**
	 * @var \OTGS\Toolset\CRED\Controller\ExpirationManager\Post
	 */
	private $manager;

	/**
	 * Manager constructor.
	 *
	 * @since 2.3
	 */
	public function __construct(
		PostExpirationManager $manager
	) {
		$this->manager = $manager;
	}

	/**
	 * Initialize the manager.
	 *
	 * @since 2.3
	 */
	public function initialize() {
		$this->register_assets();
		$this->add_hooks();
	}

	/**
	 * Register the assets for the post expiration metabox in singular editors.
	 *
	 * @since 2.3
	 */
	private function register_assets() {
		wp_register_script(
			self::SCRIPT_HANDLE,
			CRED_ABSURL . '/public/expiration/post/singular.js',
			array( 'jquery-ui-datepicker' ),
			CRED_FE_VERSION,
			true
		);

		$cred_ajax = \CRED_Ajax::get_instance();

		$calendar_image = apply_filters( 'wptoolset_filter_wptoolset_calendar_image', CRED_ASSETS_URL . '/images/calendar.gif' );
		$post_expiration_i18n = array(
			'datepicker_style_url' => TOOLSET_COMMON_FRONTEND_URL . '/toolset-forms/css/wpt-jquery-ui/jquery-ui-1.11.4.custom.css',
			'buttonImage' => apply_filters( 'wptoolset_filter_wptoolset_calendar_image', CRED_ASSETS_URL . '/images/calendar.gif' ),
			/* translators: Title of the button to select a date for expiring a post while editing it */
			'buttonText' => __( 'Select date', 'wp-cred' ),
			'dateFormat' => $this->get_date_format(),
			'yearMin' => (int) adodb_date( 'Y', \Toolset_Date_Utils::TIMESTAMP_LOWER_BOUNDARY ) + 1,
			'yearMax' => (int) adodb_date( 'Y', \Toolset_Date_Utils::TIMESTAMP_UPPER_BOUNDARY ),
			'ajaxurl' => admin_url( 'admin-ajax.php', null ),
			'ajax' => array(
				'formatPostExpirationDate' => array(
					'action' => $cred_ajax->get_action_js_name( \CRED_Ajax::CALLBACK_FORMAT_POST_EXPIRATION_DATE ),
					'nonce' => wp_create_nonce( \CRED_Ajax::CALLBACK_FORMAT_POST_EXPIRATION_DATE ),
				),
			),
		);
		wp_localize_script( self::SCRIPT_HANDLE, self::SCRIPT_I18N, $post_expiration_i18n );
	}

	/**
	 * Initialize the manager hooks related to saving a post.
	 *
	 * @since 2.3
	 */
	private function add_hooks() {
		// Backend: add metabox
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		// Backend: save expiration date, if any
		add_action( 'save_post', array( $this, 'maybe_save_expiration_date' ) );
		// Frontend: save expiration date, if any, on post created or edited with a form
		add_action( 'cred_save_data', array( $this, 'maybe_save_expiration_date_on_frontend_submit' ), 10, 2 );
	}

	/**
	 * Register the admin metabox on singular edit pages.
	 *
	 * Note that this is only added on post types marked as "with expiration", which means that at least one
	 * post of that type was created or edited with a form that has expiration enabled.
	 * I do think this should be enabled by default for all posts and all post types: it looks arbitrary!
	 *
	 * @param string $post_type
	 * @since 2.3
	 */
	public function add_meta_box( $post_type ) {
		$settings = $this->manager->get_settings();
		$post_types_with_expiration = toolset_getarr( $settings, 'post_expiration_post_types', array() );

		if ( ! in_array( $post_type, $post_types_with_expiration ) ) {
			return;
		}

		wp_enqueue_script( self::SCRIPT_HANDLE );
		$post_type_object = get_post_type_object( $post_type );
		if ( null === $post_type_object ) {
			return;
		}
		add_meta_box(
			'cred_post_expiration_meta_box',
			sprintf(
				/* translators: Title of the metabox for enabling post expiration for a post while editing it, the placeholder will get replaced by the post type singular name laber */
				__( '%s expiration', 'wp-cred' ),
				$post_type_object->labels->singular_name
			),
			array( $this, 'render_meta_box' ),
			$post_type,
			'side',
			'high',
			array(
				'__block_editor_compatible_meta_box' => true
			)
		);
	}

	/**
     * Render the admin metabox on singular edit pages.
     *
	 * @param WP_Post $post The post object.
	 * @since 2.3
	 */
	public function render_meta_box( $post ) {
		$post_expiration_time = get_post_meta( $post->ID, self::POST_META_TIME, true );
		$post_expiration_values = array(
			'date'    => '',
			'hours'   => 0,
			'minutes' => 0
		);

		if ( ! empty( $post_expiration_time ) ) {
		    $post_expiration_datetime = $this->get_gmt_date_by_time( $post_expiration_time );

            $post_expiration_values['minutes'] = $post_expiration_datetime->format( 'i' );
			$post_expiration_values['hours'] = $post_expiration_datetime->format( 'H' );
			$post_expiration_values['date'] = $post_expiration_datetime->format( $this->manager->get_date_utils()->get_supported_date_format() );
		}

		$post_expiration_action = get_post_meta( $post->ID, self::POST_META_ACTION, true );

		$post_expiration_action = toolset_ensarr( $post_expiration_action );

		if ( ! isset( $post_expiration_action['post_status'] ) ) {
			$post_expiration_action['post_status'] = '';
		}

		$post_type_object = get_post_type_object( $post->post_type );

		$template_repository = \CRED_Output_Template_Repository::get_instance();
		$renderer = \Toolset_Renderer::get_instance();

		$template_data = array(
			'post_expiration_time' => $post_expiration_time,
			'post_expiration_action' => $post_expiration_action,
			'post_expiration_values' => $post_expiration_values,
			'stati' => array(
				'basic' => apply_filters(
					'cred_pe_post_expiration_post_basic_status',
					$this->manager->get_status_model()->get_basic_stati()
				),
				'native' => apply_filters(
					'cred_pe_post_expiration_post_status',
					$this->manager->get_status_model()->get_native_stati_with_trash()
				),
				'custom' => apply_filters(
					'cred_pe_post_expiration_post_custom_status',
					$this->manager->get_status_model()->get_custom_stati()
				),
			),
			'stati_label' => array(
				'native' => $this->manager->get_status_model()->get_native_stati_group_label(),
				'custom' => $this->manager->get_status_model()->get_custom_stati_group_label(),
			),
			'post_type_object' => $post_type_object,
		);

		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::SINGULAR_METABOX_POST_EXPIRATION ),
			$template_data
		);
	}

	/**
	 * Check whether the currently saving post needs to get an expiration date,
	 * only when saved from its own admin edit page.
	 *
	 * @param int $post_id
	 * @since 2.3
	 * @since 2.4 Unoffset the timestamp we save, based on the timezone site setting.
	 */
	public function maybe_save_expiration_date( $post_id ) {

		$nonce = toolset_getpost( self::POST_EDIT_NONCE_NAME, false );

		if (
			false === $nonce
			|| ! wp_verify_nonce( $nonce, self::POST_EDIT_NONCE_VALUE )
			|| $this->is_doing_autosave()
		) {
			return;
		}

		$post_type = toolset_getpost( 'post_type' );

		if (
			'page' === $post_type
			&& ! current_user_can( 'edit_page', $post_id )
		) {
			return;
		} else if (
			'page' !== $post_type
			&& ! current_user_can( 'edit_post', $post_id )
		) {
			return;
		}

		if ( ! toolset_getnest( $_POST, array( self::POST_METABOX_NAMESPACE, 'enable' ), false ) ) {
			// Zero as expiration time means no expiration
			update_post_meta( $post_id, self::POST_META_TIME, 0 );
			delete_post_meta( $post_id, self::POST_META_ACTION );
			return;
		}

		$expiration_posted_data = toolset_getnest( $_POST, array( self::POST_METABOX_NAMESPACE, self::POST_META_TIME ), false );

		if ( ! $expiration_posted_data ) {
			return;
		}

		$expiration_date = toolset_getarr( $expiration_posted_data, 'date', false );

		if ( ! $expiration_date ) {
			return;
		}

		$expiration_date_object = $this->get_raw_date_by_time( sanitize_text_field( $expiration_date ) );

		$expiration_hour = sanitize_text_field( toolset_getarr( $expiration_posted_data, 'hours', 0 ) );
		$expiration_minute = sanitize_text_field( toolset_getarr( $expiration_posted_data, 'minutes', 0 ) );

		$expiration_date_object->setTime( $expiration_hour, $expiration_minute, 0 );
		$expiration_date_calculated = $expiration_date_object->getTimestamp();

		$expiration_date_calculated = $this->unoffset_timestamp( $expiration_date_calculated );

		if ( ! $this->is_valid_timestamp( $expiration_date_calculated ) ) {
			return;
		}

		update_post_meta( $post_id, self::POST_META_TIME, $expiration_date_calculated );

		$this->update_action( $post_id );
	}

	/**
	 * Set post expiration data when adding or editing a post with a frontend form.
     *
	 * @param int $post_id
	 * @param array $form_data
	 * @since 2.3
	 */
	public function maybe_save_expiration_date_on_frontend_submit( $post_id, $form_data ) {
		$form_id = toolset_getarr( $form_data, 'id', 0 );
		if ( 0 === $form_id ) {
			return;
		}

		if ( \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE !== get_post_type( $form_id ) ) {
			// Only post forms have expiration settings.
			return;
		}

		$settings = get_post_meta( $form_id, FormExpirationSettingsModel::FORM_META_SETTING_NAME, true );
		$settings = is_array( $settings ) ? $settings : array();
		// Only set expire time if post expire is enabled
		if ( ! toolset_getarr( $settings, 'enable', false ) ) {
			return;
		}

		$expiration_amount = (int) toolset_getnest( $settings, array( 'expiration_time', 'expiration_date' ), 0 );
		$expiration_period = toolset_getnest( $settings, array( 'expiration_time', 'expiration_period' ), 0 );
		$expire_time = $this->calculate_expire_time( $expiration_amount, $expiration_period );

		update_post_meta( $post_id, self::POST_META_TIME, $expire_time );

		// Custom actions on expiration
		// Note that custom actions do nothing, keep for legacy compatibility
		$settings['action']['custom_actions'] = isset( $settings['action']['custom_actions'] )
			? $settings['action']['custom_actions']
			: array();

		$form_slug = get_post_field( 'post_name', $form_id );

		$settings['action']['custom_actions'] = apply_filters( Action::CUSTOM_ACTIONS_FILTER_HANDLE . '_' . $form_slug, $settings['action']['custom_actions'], $post_id, $form_data );
		$settings['action']['custom_actions'] = apply_filters( Action::CUSTOM_ACTIONS_FILTER_HANDLE . '_' . $form_id, $settings['action']['custom_actions'], $post_id, $form_data );
		$settings['action']['custom_actions'] = apply_filters( Action::CUSTOM_ACTIONS_FILTER_HANDLE, $settings['action']['custom_actions'], $post_id, $form_data );

		if ( ! is_array( $settings['action']['custom_actions'] ) ) {
			$settings['action']['custom_actions'] = array();
		}

		update_post_meta( $post_id, self::POST_META_ACTION, $settings['action'] );

		// Check for notifications: translate form notifications about expiration to postmeta entries
		$form_notifications = get_post_meta( $form_id, Notifications::FORM_META, true );
		if ( isset( $form_notifications->notifications ) ) {
			// get only 'expiration_date' notifications
			$post_notifications = array();
			foreach ( $form_notifications->notifications as $notification_index => $notification ) {
				if ( 'expiration_date' == $notification['event']['type'] ) {
					// Fill the notification with some valuable data:
					// - form_id
					// - notification index, required for translating it on queue event.
					$notification['form_id'] = $form_id;
					$notification['notification_index'] = $notification_index;
					// Set the form notification index here too, for translatability.
					$post_notifications[ $notification_index ] = $notification;
				}
			}

			update_post_meta( $post_id, self::POST_META_NOTIFICATION, $post_notifications );
		}
	}

	/**
	 * Produce a proper timestamp for the expiration date to store.
	 *
	 * @param int $expiration_amount Amount of $expiration_period that we will wait to expire the post
	 * @param string $expiration_period Date period used to calculate the expiration time
	 * @return int Timestamp
	 * @since 2.5.4
	 */
	private function calculate_expire_time( $expiration_amount, $expiration_period  ) {
		// Expire time default is 0, that means no expiration
		$expire_time = 0;

		if ( $expiration_period !== null && $expiration_amount >= 0 ) {
			// calculate expiration time and get the corresponding timestamp
			$expire_time = strtotime( '+' . $expiration_amount . ' ' . $expiration_period );
		}

		if ( false === $expire_time ) {
			// strtotime failed,
			// lets push the right upper boundary
			return \Toolset_Date_Utils::TIMESTAMP_UPPER_BOUNDARY;
		}

		if (
			$expiration_amount > 0
			&& $expire_time < time()
		) {
			// Somehow, strtotime fails and produces past dates when
			// the expiration amount for the given period exceeds the range of valid timestamps
			// for the platform.
			// For example, +99999999999999999999999 weeks.
			return \Toolset_Date_Utils::TIMESTAMP_UPPER_BOUNDARY;
		}

		if ( $expire_time > \Toolset_Date_Utils::TIMESTAMP_UPPER_BOUNDARY ) {
			// strtotime returned a timestamp above the supported date,
			// lets push the right upper boundary
			return \Toolset_Date_Utils::TIMESTAMP_UPPER_BOUNDARY;
		}

		return $expire_time;
	}

	/**
	 * Update the action linked to a post expiration event:
	 * when saving a post with an expiration date, store the expiration action to perform.
	 *
	 * @param int $post_id
	 * @since 2.3
	 */
	private function update_action( $post_id ) {
		$post_status = array(
			'post_status' => sanitize_text_field( toolset_getnest( $_POST, array( self::POST_METABOX_NAMESPACE, self::POST_META_ACTION, 'post_status' ) ) )
		);

		$post_action = get_post_meta( $post_id, self::POST_META_ACTION, true );

		if ( ! is_array( $post_action ) ) {
			$post_action = array(
				'post_status' => '',
				'custom_actions' => array()
			);
		}
		$post_action = $this->manager->array_merge_distinct( $post_action, $post_status );

		update_post_meta( $post_id, self::POST_META_ACTION, $post_action );
	}

	/**
	 * Auxiliar method to check whether doing autosave.
	 *
	 * @return bool
	 * @since 2.3
	 */
	private function is_doing_autosave() {
		return ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE );
	}

	/**
     * Return the correct local DateTime time from Timestamp time.
     *
	 * @param int $time
	 * @return \DateTime
	 * @since 2.3
	 */
	private function get_gmt_date_by_time( $time ) {
		if ( ! $this->is_valid_timestamp( $time ) ) {
			$time = \Toolset_Date_Utils::TIMESTAMP_UPPER_BOUNDARY;
		}
		$get_gmt_date = get_date_from_gmt( date( 'Y-m-d H:i:s', $time ), 'Y-m-d H:i:s' );

		return new \DateTime( $get_gmt_date );
	}

	/**
	 * Get a Datetime object for a given timestamp.
	 *
	 * @param int $time
	 * @return \DateTime
	 * @since 2.5.1
	 */
	private function get_raw_date_by_time( $time ) {
		if ( ! $this->is_valid_timestamp( $time ) ) {
			$time = \Toolset_Date_Utils::TIMESTAMP_UPPER_BOUNDARY;
		}
		$get_date = date( 'Y-m-d H:i:s', $time );

		return new \DateTime( $get_date );
	}

	/**
	 * Adjust the timestamp by removing the offset provided by the timezone setting of the site.
	 *
	 * The POSTed expiration time for a given post getting saved in the backend includes the offset
	 * set by the WordPress timezone settings. As we expect this expiration timestamp to match UTC
	 * we need to un-offset it before saving it; otherwise, saving a post will push the
	 * expiration date all the offset up or down, every time.
	 *
	 * @param int $timestamp
	 * @return int
	 * @since 2.4
	 */
	private function unoffset_timestamp( $timestamp ) {
		$current_gmt_timestamp = current_time( 'timestamp', true );
		$current_local_timestamp = current_time( 'timestamp', false );

		$offset = $current_local_timestamp - $current_gmt_timestamp;

		return $timestamp - $offset;
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 * @since 2.3
	 */
	private function is_valid_timestamp( $timestamp ) {
		return $this->manager->get_date_utils()->is_timestamp_in_range( $timestamp );
	}

	/**
	 * Get the post expiration datepicker date format.
	 * Note that we only support a number of date formats.
	 *
	 * @return string
	 * @since 2.3
	 */
	private function get_date_format() {
		$php_date_format = $this->manager->get_date_utils()->get_supported_date_format();

		switch( $php_date_format ) {
			//Predefined WP date formats
			case 'Y/m/d':
				return( 'yy/mm/dd' );
			case 'Y-m-d':
				return( 'yy-mm-dd' );
			case 'm/d/Y':
				return( 'mm/dd/yy' );
			case 'd/m/Y':
				return( 'dd/mm/yy' );
			case 'd.m.Y':
				return( 'dd.mm.yy' );
			case 'Y/n/j':
				return( 'yy/m/d' );
			case 'j.n.Y':
				return( 'd.m.yy' );
			case 'j F Y':
				return( 'd MM yy' );
			case 'F j, Y':
			default:
				return( 'MM d, yy' );
		}

		return( 'MM d, yy' );
	}
}

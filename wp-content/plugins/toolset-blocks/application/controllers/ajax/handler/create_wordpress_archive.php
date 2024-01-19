<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Create a WordPress Archive
 *
 * @since unknown
 * @since 2.7.0 Moved the callback here.
 */
class WPV_Ajax_Handler_Create_Wordpress_Archive extends Toolset_Ajax_Handler_Abstract {
	private $wpa;

	private $wpv_view_archive_loop;

	public function __construct(
		Toolset_Ajax $ajax_manager,
		$wpa = null,
		WPV_WordPress_Archive_Frontend $wpv_view_archive_loop = null
	) {
		parent::__construct( $ajax_manager );

		$this->wpa = $wpa;

		$this->wpv_view_archive_loop = $wpv_view_archive_loop;
	}

	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin(
			array(
				'nonce' => WPV_Ajax::CALLBACK_CREATE_WORDPRESS_ARCHIVE,
				'public' => false,
				'capability_needed' => EDIT_VIEWS,
			)
		);

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'capability',
					'message' => __( 'You do not have permissions for that.', 'wpv-views' ),
				),
				false
			);

			return;
		}

		if ( ! isset( $_POST['title'] ) ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'title',
					'message' => __( 'You can not create a Wordpress Archive with an empty name.', 'wpv-views' ),
				),
				false
			);

			return;
		}

		if ( ! isset( $_POST['form'] ) ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'data',
					'message' => __( 'Wrong data', 'wpv-views' ),
				),
				false
			);

			return;
		}

		if ( ! $this->wpv_view_archive_loop ) {
			global $WPV_view_archive_loop;
			$this->wpv_view_archive_loop = $WPV_view_archive_loop;
		}

		parse_str( $_POST['form'], $form_data );

		$title = sanitize_text_field( $_POST['title'] );
		$purpose = ( isset( $_POST['purpose'] ) && in_array( $_POST['purpose'], array( 'all', 'parametric' ) ) ) ? $_POST['purpose'] : 'all';
		$args = array(
			'view_settings' => array(
				'view_purpose' => $purpose,
				'sections-show-hide' => 'all' === $purpose ?
					array(
						'filter-extra-parametric' => 'off',
						'filter-extra' => 'off',
					) :
					array(),
			),
		);

		try {
			$wpa = $this->wpa ?
				$this->wpa :
				WPV_WordPress_Archive::create( $title, $args );

			$this->wpv_view_archive_loop->update_view_archive_settings( $wpa->id, $form_data );

			/**
			 * Filter for getting the ID of the created WordPress Archive post.
			 *
			 * @param int                   $wpa_id
			 * @param WPV_WordPress_Archive $wpa
			 *
			 * @since 3.0
			 */
			$wpa_id = apply_filters( 'wpv_filter_wpa_created', $wpa->id, $wpa );

			$ajax_manager->ajax_finish(
				array(
					'id' => $wpa_id,
				),
				true
			);
		} catch ( WPV_RuntimeExceptionWithMessage $e ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'error',
					'message' => $e->getUserMessage(),
				),
				false
			);
		}
	}
}

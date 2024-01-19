<?php

use OTGS\Toolset\Access\Ajax;
use OTGS\Toolset\Access\Models\Settings;
use OTGS\Toolset\Common\WpQueryFactory;

/**
 * Class Access_Ajax_Handler_Modify_Group_Process
 * Process modify Post Group
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Modify_Group_Process extends Toolset_Ajax_Handler_Abstract {

	/** @var Settings */
	private $access_settings;

	/** @var WpQueryFactory */
	private $query_factory;


	/**
	 * Access_Ajax_Handler_Modify_Group_Process constructor.
	 *
	 * @param Ajax $access_ajax
	 * @param Settings|null $access_settings
	 * @param WpQueryFactory|null $query_factory
	 */
	public function __construct( Ajax $access_ajax, Settings $access_settings = null, WpQueryFactory $query_factory = null ) {
		parent::__construct( $access_ajax );
		$this->access_settings = $access_settings ? : Settings::get_instance();
		$this->query_factory = $query_factory ? : new WpQueryFactory();
	}


	/**
	 * @param mixed $arguments
	 *
	 * @return array
	 */
	function process_call( $arguments ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

		$this->ajax_begin( array( 'nonce' => 'wpcf-access-error-pages' ) );

		if ( ! isset( $_POST['id'] ) || ! isset( $_POST['title'] ) ) {
			return wp_send_json_error( 'error' );
		}

		$_POST['id'] = str_replace( '%', '--ACCESS--', $_POST['id'] );
		$nice = str_replace( '--ACCESS--', '%', sanitize_text_field( $_POST['id'] ) );
		$_POST['id'] = str_replace( '--ACCESS--', '%', $_POST['id'] );
		$posts = array();
		if ( isset( $_POST['posts'] ) ) {
			$posts = array_map( 'intval', $_POST['posts'] );
		}

		$settings_access = $this->access_settings->get_types_settings( true, true );
		$process = true;
		if ( isset( $settings_access[ $nice ] ) ) {
			foreach ( $settings_access as $permission_slug => $data ) {
				if ( isset( $data['title'] )
					&& $data['title'] == sanitize_text_field( $_POST['title'] )
					&& $permission_slug != $nice ) {
					$process = false;
				}
			}
		} else {
			$process = false;
		}

		if ( ! $process ) {
			return wp_send_json_error( 'error' );
		}

		$settings_access[ $nice ]['title'] = sanitize_text_field( $_POST['title'] );
		$this->access_settings->updateAccessTypes( $settings_access );

		for ( $i = 0, $posts_limit = count( $posts ); $i < $posts_limit; $i ++ ) {
			update_post_meta( $posts[ $i ], '_wpcf_access_group', $nice );
		}
		$group_output = '';
		$post_types_array = get_post_types( array( 'show_ui' => true ), 'names' );
		$args = array(
			'post_type' => $post_types_array,
			'posts_per_page' => 0,
			'meta_key' => '_wpcf_access_group',
			'meta_value' => $nice,
			'suppress_filters' => true,
		);

		$the_query = $this->query_factory->create( $args );
		if ( $the_query->have_posts() ) {
			$group_output .= '<strong>' . __( 'Posts in this Post Group', 'wpcf-access' ) . ':</strong> ';
			$posts_list = '';
			$show_assigned_posts = 4;
			while ( $the_query->have_posts() && $show_assigned_posts != 0 ) {
				$the_query->the_post();
				$posts_list .= esc_html( get_the_title() ) . ', ';
				$show_assigned_posts --;
			}
			$group_output .= substr( $posts_list, 0, - 2 );
			if ( $the_query->found_posts > 4 ) {
				$group_output .= sprintf( __( ' and %d more', 'wpcf-access' ), ( $the_query->found_posts - 2 ) );
			}
		}

		return wp_send_json_success( $group_output );
	}
}

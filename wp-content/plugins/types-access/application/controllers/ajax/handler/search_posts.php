<?php
/**
 * Class Access_Ajax_Handler_Search_Post
 * Select2 search posts
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Search_Posts extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Search_Post constructor.
	 *
	 * @param \OTGS\Toolset\Access\Ajax $access_ajax
	 */
	public function __construct( \OTGS\Toolset\Access\Ajax $access_ajax ) {
		parent::__construct( $access_ajax );
	}


	/**
	 * @param mixed $arguments
	 *
	 * @return array
	 */
	function process_call( $arguments ) {

		$this->ajax_begin( array( 'nonce' => 'wpcf-access-error-pages' ) );
		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();

		$settings_access = $access_settings->get_types_settings( true, true );

		$out = array();
		$post_types_array = array();
		if ( isset( $_POST['post_type'] ) && ! empty( $_POST['post_type'] ) ) {
			$post_types_array[] = sanitize_text_field( $_POST['post_type'] );
		} else {
			$post_types = get_post_types( array( 'public' => true ), 'names' );
			foreach ( $post_types as $post_type ) {
				if ( isset( $settings_access[ $post_type ] )
					&& $settings_access[ $post_type ]['mode']
					!= 'not_managed' ) {
					$post_types_array[] = $post_type;
				}
			}
		}
		$assigned_posts = array();
		if ( isset( $_POST['assigned_posts'] ) && is_array( $_POST['assigned_posts'] ) ) {
			$assigned_posts_array = $_POST['assigned_posts'];
			for ( $i = 0, $count = count( $assigned_posts_array ); $i < $count; $i ++ ) {
				$assigned_posts[] = intval( $assigned_posts_array[ $i ] );
			}
		}
		$args = array(
			'posts_per_page' => '10',
			'post_status' => 'publish',
			'post_type' => $post_types_array,
			's' => $access_settings->esc_like( $_POST['q'] ),
			'post__not_in' => $assigned_posts,
			'suppress_filters' => true,
		);

		$total = 0;
		$out['items'] = array();
		if ( count( $post_types_array ) > 0 ) {
			$the_query = new WP_Query( $args );
			if ( $the_query->have_posts() ) {
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$total ++;
					$out['items'][] = array( 'id' => esc_js( get_the_ID() ), 'name' => esc_js( get_the_title() ) );
				};
			}
		}
		$out['total_count'] = $total;
		$out['incomplete_results'] = 'false';
		wp_send_json_success( $out );
	}
}

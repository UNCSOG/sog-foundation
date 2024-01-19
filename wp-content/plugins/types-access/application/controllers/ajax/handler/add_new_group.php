<?php
/**
 * Class Access_Ajax_Handler_Add_New_Group
 * Generate 'add new Post Group' form
 *
 * @since 2.7
 */
class Access_Ajax_Handler_Add_New_Group extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Add_New_Group constructor.
	 *
	 * @param \OTGS\Toolset\Access\Ajax $access_ajax
	 */
	public function __construct( \OTGS\Toolset\Access\Ajax $access_ajax ) {//phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		parent::__construct( $access_ajax );
	}


	/**
	 * @param array $arguments
	 */
	public function process_call( $arguments ) {//phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$this->ajax_begin( array( 'nonce' => 'wpcf-access-error-pages' ) );
		$settings_access = \OTGS\Toolset\Access\Models\Settings::get_instance()->get_types_settings( true, true );

		$out = '<form method="" id="wpcf-access-set_error_page">';
		$act = 'Add';
		$title = '';

		$edit_post_id = ( isset( $_POST['modify'] ) && ! empty( $_POST['modify'] ) ? $_POST['modify'] : '' );//phpcs:ignore
		if ( ! empty( $edit_post_id ) ) {
			$act = 'Modify';
			$title = toolset_getnest( $settings_access, array( $edit_post_id, 'title' ), '' );
		}

		$out .= '
			<p>
				<label for="wpcf-access-new-group-title">' . __( 'Group title', 'wpcf-access' ) . '</label><br>
				<input type="text" id="wpcf-access-new-group-title" value="' . esc_attr( $title ) . '">
			</p>
			<div class="js-error-container"></div>
			<input type="hidden" value="add" id="wpcf-access-new-group-action">
			<input type="hidden" value="' . esc_attr( $edit_post_id ) . '" id="wpcf-access-group-slug">';

		$out .= '<div class="otgs-access-search-posts-container">
                <label for="wpcf-access-new-group-title">'
			. __( 'Choose which posts belongs to this group', 'wpcf-access' )
			. '</label><br>
                <select class="js-otgs-access-suggest-posts otgs-access-suggest-posts" style="width:72%;">
                </select>
                <select class="js-otgs-access-suggest-posts-types otgs-access-suggest-posts-types" style="width:25%;">
                  <option selected="selected" value="">'
			. __( 'All post types', 'wpcf-access' )
			. '</option>';
		$post_types = get_post_types( array( 'public' => true ), 'object' );
		$post_types_array = array();
		foreach ( $post_types as $post_type ) {
			if ( 'attachment' !== $post_type->name ) {
				$is_option_disabled = (
					! isset( $settings_access[ $post_type->name ] )
					|| 'not_managed' === $settings_access[ $post_type->name ]['mode']
				);
				$out .= sprintf( '<option value="%s" %s>%s</option>',
					esc_attr( $post_type->name ),
					disabled( $is_option_disabled, true, false ),
					esc_html( $post_type->labels->name )
				);
				$post_types_array[] = $post_type->name;
			}
		}

		$out .= '</select>
            </div>
            <div class="js-otgs-access-posts-listing otgs-access-posts-listing">';
		if ( 'Modify' === $act ) {
			$args = array(
				'posts_per_page' => - 1,
				'post_status' => 'publish',
				'post_type' => $post_types_array,
				'suppress_filters' => true,
				'meta_query' => array(//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key' => '_wpcf_access_group',
						'value' => $edit_post_id,
					),
				),
			);
			$the_query = new WP_Query( $args );
			if ( $the_query->have_posts() ) {
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$post_id = esc_attr( get_the_ID() );
					$out .= sprintf( '<div class="js-assigned-access-post js-assigned-access-post-%d" data-postid="%d">%s'
						.
						'<a href="" class="js-wpcf-unassign-access-post" data-id="%d"> <i class="fa fa-times"></i></a></div>',
						$post_id,
						$post_id,
						esc_html( get_the_title() ),
						$post_id
					);
				};
			}
		}
		$out .= '</div>';
		$out .= '</div>';
		$out .= '</form>';
		wp_send_json_success( $out );
	}
}

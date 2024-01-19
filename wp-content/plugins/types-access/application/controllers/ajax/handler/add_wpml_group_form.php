<?php
/**
 * Class Access_Ajax_Handler_Add_Wpml_Group_Form
 * Show add new WPML group form
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Add_Wpml_Group_Form extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Add_Wpml_Group_Form constructor.
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

		$group_info = $original_name = $group_nice = '';
		$disabled_language_list = array();
		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$settings_access = $access_settings->get_types_settings( true, true );
		$post_types = $access_settings->get_post_types();
		$action = 'add';
		$output = '';
		if ( isset( $_POST['group_id'] ) && ! empty( $_POST['group_id'] ) ) {
			$action = 'modify';
			$group_info = $settings_access[ $_POST['group_id'] ];
		}

		foreach ( $settings_access as $group_slug => $group_data ) {
			if ( strpos( $group_slug, 'wpcf-wpml-group-' ) !== 0 ) {
				continue;
			}
			if ( $_POST['group_id'] == $group_slug ) {
				continue;
			}
			if ( isset( $group_data['languages'] ) && ! empty( $group_data['languages'] ) ) {
				foreach ( $group_data['languages'] as $lang => $lang_active ) {
					$disabled_language_list[ $group_data['post_type'] ][ $lang ] = 1;
				}
			}
		}

		$output .= '
            <input type="hidden" value="'
			. $_POST['group_id']
			. '" id="wpcf-access-wpml-group-nice">
			<input type="hidden" value="'
			. $action
			. '" id="wpcf-access-group-action">
            <input type="hidden" value="'
			. ( isset( $_POST['group_id'] ) && ! empty( $_POST['group_id'] )
				? $_POST['group_id'] : '' )
			. '" id="wpcf-access-group-id">
            <input type="hidden" value="'
			. esc_js( json_encode( $disabled_language_list ) )
			. '" id="wpcf-wpml-group-disabled-languages">';

		$managed_post_types = array();
		$output .= '<h3>' . __( 'Post Type', 'wpcf-access' ) . '</h3>' .
			'<select id="wpcf-wpml-group-post-type" ' . ( isset( $group_info['post_type'] ) ? ' disabled="disabled"'
				: '' ) . '>';
		foreach ( $post_types as $post_type_object ) {
			if ( ! in_array( $post_type_object->name, array( 'attachment', 'cred-form', 'cred-user-form' ), true )
				&& apply_filters( 'wpml_is_translated_post_type', null, $post_type_object->name )
				&& ( isset( $settings_access[ $post_type_object->name ] )
					&& $settings_access[ $post_type_object->name ]['mode']
					!= 'not_managed' ) ) {
				$output .= '<option ' . ( isset( $group_info['post_type'] ) && $group_info['post_type'] == $post_type_object->name
						? ' selected="selected"' : '' )
					. ' value="' . $post_type_object->name . '">' . $post_type_object->labels->name . '</option>';
				$managed_post_types[] = $post_type_object->name;
			}
		}
		$output .= '</select>';

		//Show notification that group post type not managed by Access
		if ( isset( $group_info['post_type'] ) && ! in_array( $group_info['post_type'], $managed_post_types ) ) {
			$message = sprintf( __( 'WPML group for %s is not active.', 'wpcf-access' ), $group_info['post_type'] );
			$details = __( 'Set the post type managed by Access in Post Types tab, and check that it is translatable in the WPML settings, in order to activate it.', 'wpcf-access' );
			$this->send_group_disabled_message( $message, $details );
		}

		//Show notification that no post types manged by Access
		if ( count( $managed_post_types ) === 0 ) {
			$message = __( 'No translatable post types are currently managed by Access.', 'wpcf-access' );
			$details = __( 'Set at least one translatable post type managed by Access in the Post Types tab.', 'wpcf-access' );
			$this->send_group_disabled_message( $message, $details );
		}

		$output .= '<h3>' . __( 'Languages', 'wpcf-access' ) . '</h3>' .
			'<ul class="wpcf-available-languages">';

		$wpml_active_languages = apply_filters( 'wpml_active_languages', '', array( 'skip_missing' => 0 ) );
		foreach ( $wpml_active_languages as $language => $language_data ) {
			$checked = '';
			if ( ! empty( $group_info ) && isset( $group_info['languages'][ $language ] ) ) {
				$checked = ' checked="checked" ';
			}
			$language_name = ( isset( $language_data['translated_name'] ) ? $language_data['translated_name']
				: $language_data['english_name'] );
			$output .= '<li><label><input type="checkbox" value="'
				. $language
				. '" '
				. $checked
				. ' name="group_language_list"> '
				. $language_name
				. '</lable></li>';
		}

		$output .= '</ul>';

		wp_send_json_success( $output );

	}


	/**
	 *
	 * @param string $message
	 * @param string $details
	 *
	 * @since 2.4.2
	 */
	private function send_group_disabled_message( $message, $details ) {
		$output = sprintf(
			'<div class="toolset-access-alarm-wrap-left"><i class="fa fa-exclamation-triangle fa-5x"></i></div>
            <div class="toolset-access-alarm-wrap-right"><h4>%s</h4>%s</div>
            <input type="hidden" value="1" id="wpcf-access-wpml-group-disabled">',
			esc_html( $message ),
			esc_html( $details )
		);

		wp_send_json_success( $output );
	}
}

<?php
/**
 * Class Access_Ajax_Handler_Select_Post_Group_For_Post_Form
 * Select Post Group for a post form
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Select_Post_Group_For_Post_Form extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Select_Post_Group_For_Post_Form constructor.
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
		$requeste_capability = ( current_user_can( 'manage_options') ? 'manage_options' : 'access_change_post_group' );

		$this->ajax_begin( array(
			'nonce' => 'wpcf-access-error-pages',
			'capability_needed' => $requeste_capability,
		) );

		if ( ! isset( $_POST['id'] ) ) {
			wp_send_json_error( __( 'Post ID not found', 'wpcf-access' ) );
		}

		$group = get_post_meta( sanitize_text_field( $_POST['id'] ), '_wpcf_access_group', true );
		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$settings_access = $access_settings->get_types_settings( true, true );

		$out = '<form method="#" id="wpcf-access-set_error_page">';

		$groups_list = '';
		foreach ( $settings_access as $permission_slug => $data ) {
			if ( strpos( $permission_slug, 'wpcf-custom-group-' ) === 0 ) {
				$checked = ( $permission_slug == $group ) ? ' selected="selected" ' : '';
				$groups_list .= '
						<option value="' . $permission_slug . '"' . $checked . '>' . $data['title'] . '</option>';
			}
		}
		$checked = ( isset( $group ) && ! empty( $group ) && isset( $settings_access[ $group ] ) )
			? ' checked="checked" ' : '';
		$out .= '<div class="otg-access-dialog-wraper">
				<p>
					<input type="radio" name="wpcf-access-group-method" id="wpcf-access-group-method-existing-group" value="existing_group" '
			. $checked
			. ' '
			. ( empty( $groups_list ) ? 'disabled="disabled"' : '' )
			. '>
					<label for="wpcf-access-group-method-existing-group">'
			. __( 'Select existing group', 'wpcf-access' )
			. '</label>
					<select name="wpcf-access-existing-groups" class="hidden">
						<option value="">- '
			. __( 'None', 'wpcf-access' )
			. ' -</option>';
		$out .= $groups_list;

		$out .= '
					</select>
				</p>
		';
		if ( current_user_can( 'manage_options' ) || current_user_can( 'access_create_new_group' ) ) {
			$out .= '
				<p>
					<input type="radio" name="wpcf-access-group-method" id="wpcf-access-group-method-new-group" value="new_group" '
				. ( empty( $groups_list ) ? 'checked="checked"' : '' )
				. '>
					<label for="wpcf-access-group-method-new-group">'
				. __( 'Create new group', 'wpcf-access' )
				. '</label>
					<input type="text" name="wpcf-access-new-group" class="'
				. ( ! empty( $groups_list ) ? 'hidden"' : '' )
				. '">
					<div class="js-error-container"></div>
				</p>';
		}
		$out .= '</div></form>';

		wp_send_json_success( $out );
	}
}

<?php
/**
 * Class Access_Ajax_Handler_Change_Role_Caps_Form
 * Change role capabilities form
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Change_Role_Caps_Form extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Change_Role_Caps_Form constructor.
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

		$access_roles = \OTGS\Toolset\Access\Models\UserRoles::get_instance();
		$role = sanitize_text_field( $_POST['role'] );
		$out = '<div class="otg-access-change-role-caps-tabs js-otg-access-change-role-caps-tabs">';
		$role_data = get_role( $role );
		$role_caps = $role_data->capabilities;
		$managed_capabilities_list = array();

		/**
		 * list wordpress, toolset, wpml, woocommerce capabilities.
		 */
		$data = apply_filters( 'wpcf_access_custom_capabilities', array() );
		$caps = '';
		$out .= '<ul class="wpcf-access-capability-tabs">';
		foreach ( $data as $capabilities ) {
			if ( ! isset( $capabilities['capabilities'] ) ) {
				continue;
			}
			if ( isset( $capabilities['label'] ) ) {
				$out .= sprintf( '<li><a href="#plugin_%s">%s</a></li>', md5( $capabilities['label'] ), $capabilities['label'] );
				$caps .= sprintf( '<div id="plugin_%s"><h3>%s</h3>', md5( $capabilities['label'] ), $capabilities['label'] );
			}
			foreach ( $capabilities['capabilities'] as $cap => $cap_info ) {
				$caps .= sprintf(
					'<p><label for="cap_%s"><input type="checkbox" name="current_role_caps[]" value="Access:cap_%s" id="cap_%s" %s>%s<br><small> %s</small></label></p>',
					$cap,
					$cap,
					$cap,
					( isset( $role_caps[ $cap ] ) && $role_caps[ $cap ] == 1 ) ? ' checked="checked" ' : '',
					$cap,
					$cap_info
				);
				$managed_capabilities_list[] = $cap;
			}
			if ( isset( $capabilities['label'] ) ) {
				$caps .= '</div>';
			}
		}

		$out .= '<li><a href="#plugin_'
			. md5( __( 'Custom capabilities', 'wpcf-access' ) )
			. '">'
			. __( 'Custom capabilities', 'wpcf-access' )
			. '</a></li>';
		$caps .= '<div id="plugin_'
			. md5( __( 'Custom capabilities', 'wpcf-access' ) )
			. '"><h3>'
			. __( 'Custom capabilities', 'wpcf-access' )
			. '</h3>';
		$custom_caps = get_option( 'wpcf_access_custom_caps', array() );
		$caps .= '<div class="js-wpcf-list-custom-caps">';
		if ( is_array( $custom_caps ) && count( $custom_caps ) > 0 ) {
			foreach ( $custom_caps as $cap => $cap_info ) {
				$managed_capabilities_list[] = $cap;
				$checked = ( isset( $role_caps[ $cap ] ) && $role_caps[ $cap ] == 1 ) ? ' checked="checked" ' : '';
				$caps .= '<p id="wpcf-custom-cap-'
					. $cap
					. '">'
					.
					'<label for="cap_'
					. $cap
					. '">'
					.
					'<input type="checkbox" name="current_role_caps[]" value="Access:cap_'
					. $cap
					. '" id="cap_'
					. $cap
					. '" '
					. $checked
					. '>'
					.
					$cap
					. '<br><small>'
					. $cap_info
					. '</small></label>'
					.
					'<span class="js-wpcf-remove-custom-cap js-wpcf-remove-custom-cap_'
					. $cap
					. '">'
					.
					'<a href="" data-object="wpcf-custom-cap-'
					. $cap
					. '" data-remove="0" data-cap="'
					. $cap
					. '">Delete</a><span class="ajax-loading spinner"></span>'
					.
					'</span>'
					.
					'</p>';
			}
		}
		$hidden = count( $custom_caps ) > 0 ? ' hidden' : '';
		$caps .= '<p class="js-wpcf-no-custom-caps '
			. $hidden
			. '">'
			. __( 'No custom capabilities', 'wpcf-access' )
			. '</p>';
		$caps .= '</div>';

		ob_start();
		?>
		<div class="wpcf-create-new-cap-div js-wpcf-create-new-cap-div">
			<p>
				<button
					class="button js-wpcf-access-add-custom-cap"><?php _e( 'New custom capability', 'wpcf-access' ) ?></button>
			</p>
			<div class="js-wpcf-create-new-cap-form hidden">
				<p>
					<label for="js-wpcf-new-cap-slug"><?php _e( 'Capability name', 'wpcf-access' ) ?>:</label>
					<input type="text" name="new_cap_name" id="js-wpcf-new-cap-slug">
				</p>
				<p>
					<label for="js-wpcf-new-cap-description"><?php _e( 'Capability description', 'wpcf-access' ) ?>
						:</label>
					<input type="text" name="new_cap_description" id="js-wpcf-new-cap-description">
				</p>
				<p class="wpcf-access-buttons-wrap wpcf-access-buttons-wrap-left">
					<button class="button js-wpcf-new-cap-cancel"><?php _e( 'Cancel', 'wpcf-access' ) ?></button>
					<button class="button button-primary js-wpcf-new-cap-add" disabled="disabled"
						data-error="<?php echo esc_attr( __( 'Only lowercase letters, numbers, _ and - allowed in capability name', 'wpcf-access' ) ) ?>">
						<?php _e( 'Add', 'wpcf-access' ) ?></button>
					<span class="ajax-loading spinner js-new-cap-spinner"></span>
				</p>
			</div>
		</div>

		<input type="hidden" value="<?php echo esc_attr( $role ) ?>" class="js-wpcf-current-edit-role">
		<?php
		$caps .= ob_get_contents();
		ob_end_clean();
		$caps .= '</div>';

		//Add Access role capability from list
		$managed_capabilities_list[] = 'wpcf_access_role';
		$other_roles_capabilities = $access_roles->get_roles_capabilities_list( $managed_capabilities_list );
		if ( count( $other_roles_capabilities ) ) {
			asort( $other_roles_capabilities );
			$out .= sprintf( '<li><a href="#plugin_%s">%s</a></li>', md5( __( 'Other capabilities', 'wpcf-access' ) ), __( 'Other capabilities', 'wpcf-access' ) );
			$caps .= sprintf( '<div id="plugin_%s"><h3>%s</h3>', md5( __( 'Other capabilities', 'wpcf-access' ) ), __( 'Other capabilities', 'wpcf-access' ) );
			foreach ( $other_roles_capabilities as $key => $value ) {
				$cap = $value;
				$caps .= sprintf(
					'<p><label for="cap_%s"><input type="checkbox" name="current_role_caps[]" value="Access:cap_%s" id="cap_%s" %s>%s<br></label></p>',
					$cap,
					$cap,
					$cap,
					( isset( $role_caps[ $cap ] ) && $role_caps[ $cap ] == 1 ) ? ' checked="checked" ' : '',
					$cap
				);
			}

			$caps .= '</div>';
		}

		$out .= '</ul>';
		$out .= $caps . '</div>';

		wp_send_json_success( $out );
	}
}

<?php if ( ! defined( 'ABSPATH' ) ) {
	die( 'Security check' );
}
// @cred-984 Commenting out some settings as they are set by an MCV!!!!
$settings = CRED_Helper::mergeArrays( array(
	'type' => 'new',
	'action' => 'form',
	'user_role' => '',
	'action_page' => '',
	'action_message' => '',
	'redirect_delay' => 0,
	/*'hide_comments' => 0,*/
	'theme' => 'minimal',
	/*'has_media_button' => 0,*/
	'include_wpml_scaffold' => 0,
	'include_captcha_scaffold' => 0,
), (array) $settings );

$settings['user_role'] = json_decode( $settings['user_role'], true );

if ( is_array( $settings['user_role'] ) ) {
	array_filter( $settings['user_role'] );
}

$template_repository = \CRED_Output_Template_Repository::get_instance();
$renderer = \Toolset_Renderer::get_instance();
?>

<?php wp_nonce_field( 'cred-admin-post-page-action', 'cred-admin-post-page-field' ); ?>
<table class="widefat cred-editor-table">
	<tbody>

		<tr>
			<td>
				<?php _e( "Form type", "wp-cred" ); ?>
			</td>
			<td>
				<?php
					$form_types = apply_filters( 'cred_admin_form_type_options', array(
						"new" => __( 'Create new user', 'wp-cred' ),
						"edit" => __( 'Edit existing user', 'wp-cred' ),
					), $settings['type'], $form );
					foreach ( $form_types as $type => $label ) {
						if ( empty( $settings['type'] ) ) {
							$settings['type'] = $type;
						}
						?>
						<label class="cred-label" style="display: inline;margin-right: 10px;">
							<input type="radio" name="_cred[form][type]" value="<?php echo esc_attr( $type ); ?>" <?php checked( $settings['type'] === $type ); ?> /><span><?php echo $label; ?></span>
						</label><?php
					}
				?>
			</td>
		</tr>

		<tr>
			<td>
				<?php _e( 'Role of the user to create/edit:', 'wp-cred' ); ?>
			</td>
			<td>
				<select class="roles_selectbox" id="cred_form_user_role" name="_cred[form][user_role][]" autocomplete="false" required >
					<option value=""><?php echo __( '-- Select role --' ); ?></option><?php
					foreach ( $user_roles as $k => $v ) {
						?>
						<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v['name'] ); ?></option><?php
					}
					?>
				</select>

				<?php
				foreach ( $user_roles as $k => $v ) {
					?><label class="roles_checkboxes cred-label-inline"><?php
					?>
					<input class="roles_checkboxes" id="role_<?php echo esc_attr( $k ); ?>" type="checkbox" name="_cred[form][user_role][]" value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v['name'] ); ?><?php
					?></label><?php
				}
				?>
			</td>
		</tr>

		<?php
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::SETTINGS_FORM_SUBMIT ),
			null
		);
		?>

		<tr>
			<td>
				<?php _e( 'After visitors submit this form:', 'wp-cred' ); ?>
			</td>
			<td>
				<select id="cred_form_success_action" name="_cred[form][action]" required >
					<?php
					/*
					// Toolset Forms 1.9: disable the option to redirect to the user because:
					// - the actual stored action value is 'user', but on the redirection management we heck against 'post', so this never worked
					// - when redirecting to a newly created user, we get  404 as his archive page is empty
					// - when redireting to a specific user, we never applied the specific user ID
					*/
					$form_actions = apply_filters( 'cred_admin_submit_action_options', array(
						"form" => __( 'Keep displaying this form', 'wp-cred' ),
						"message" => __( 'Display a message instead of the form...', 'wp-cred' ),
						"custom_post" => __( 'Go to a specific post...', 'wp-cred' ),
						//"user" => __('Display the user', 'wp-cred'),
						"page" => __( 'Go to a page...', 'wp-cred' ),
					), $settings['action'], $form );
					if (
						isset( $settings['action'] )
						&& $settings['action'] == 'user'
					) {
						$settings['action'] = 'form';
					}
					?>
					<option value="" disabled="disabled"><?php echo esc_html( __( '-- Select action --', 'wp-cred' ) ); ?></option><?php
					foreach ( $form_actions as $value => $label ) {
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, toolset_getarr( $settings, 'action' ) ); ?>><?php echo esc_html( $label ); ?></option>
						<?php
					}
					?>
				</select>

				<div id="after_visitors_submit_this_form">
					<span data-cred-bind="{ action: 'show', condition: '_cred[form][action]=page' }">
						<select id="cred_form_success_action_page" name="_cred[form][action_page]">
						<?php echo $form_action_pages; ?>
						</select>
					</span>

					<span data-cred-bind="{ action: 'show', condition: '_cred[form][action]=custom_post' }">
						<select id="cred_form_action_post_type" name="action_post_type" data-placeholder="<?php echo esc_attr( $default_empty_action_post_type ); ?>">
							<?php echo $form_post_types; ?>
						</select>
					</span>

					<span data-cred-bind="{ action: 'show', condition: '_cred[form][action]=custom_post' }">
						<div style="display: none;" id="cred_form_action_ajax_loader" class="cred_ajax_loader_small"></div>
						<select id="cred_form_action_custom_post" name="_cred[form][action_post]" data-placeholder="<?php echo esc_attr( $default_empty_action_post ); ?>">
							<?php echo $form_current_custom_post; ?>
						</select>
					</span>

					<span data-cred-bind="{ action: 'show', condition: '_cred[form][action]=user' }">
						<input type='text' id='action_user' name='_cred[form][action_user]' value='' placeholder="<?php echo esc_attr( __( 'Type some characters..', 'wp-cred' ) ); ?>"/>
					</span>

					<span data-cred-bind="{ action: 'show', condition: '_cred[form][action] in [post,custom_post,page]' }">
						<?php _e( 'Redirect delay (in seconds)', 'wp-cred' ); ?>
						<input type='text' size='3' id='cred_form_redirect_delay' name='_cred[form][redirect_delay]' value='<?php echo esc_attr( $settings['redirect_delay'] ); ?>'/>
					</span>

					<?php
					$template_data = array(
						'action_message' => $settings['action_message']
					);
					$renderer->render(
						$template_repository->get( \CRED_Output_Template_Repository::SETTINGS_ACTION_MESSAGE ),
						$template_data
					);
					?>
				</div>
			</td>
		</tr>

		<?php
		do_action( 'cred_ext_cred_user_form_settings', $form, $settings );
		?>

		<?php
		$template_data = array(
			'form' => $form
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::SETTINGS_OTHER_SETTINGS ),
			$template_data
		);
		?>

	</tbody>
</table>

<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Security check' );
}
// @cred-984 Commenting out some settings as they are set by an MCV!!!!
// @cred-984 This array merging has no sense at all: we are not setting any default whatsoever because the first array is multidimmensional
$settings = CRED_Helper::mergeArrays( array(
	'post' => array(
		'post_type' => '',
		'post_status' => 'draft',
	),
	'form' => array(
		'type' => 'new',
		'action' => 'form',
		'action_page' => '',
		'action_message' => '',
		'redirect_delay' => 0,
		/*'hide_comments' => 0,*/
		'theme' => 'minimal',
		/*'has_media_button' => 0,*/
		'include_wpml_scaffold' => 0,
		'include_captcha_scaffold' => 0,
	),
), (array) $settings );

$template_repository = \CRED_Output_Template_Repository::get_instance();
$renderer = \Toolset_Renderer::get_instance();
?>

<script>
	var $toolsetFormsOriginalStatusOption = "<option value='original'><?php echo esc_html( 'Keep original status', 'wp-cred' ); ?></option>";
</script>
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
					"new" => __( 'Add new content', 'wp-cred' ),
					"edit" => __( 'Edit existing content', 'wp-cred' ),
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
				<?php _e( 'Post type to create/edit:', 'wp-cred' ); ?>
			</td>
			<td>
				<select id="cred_post_type" name="_cred[post][post_type]" class='cred_ajax_change' required>
					<?php
					echo '<option value="" selected="selected">' . __( '-- Select Post Type --', 'wp-cred' ) . '</option>';
					foreach ( $post_types as $pt ) {
						if ( ! has_filter( 'cred_wpml_glue_is_translated_and_unique_post_type' ) || apply_filters( 'cred_wpml_glue_is_translated_and_unique_post_type', $pt['type'] ) ) {
							if ( $settings['post']['post_type'] == $pt['type']
								|| ( isset( $_GET['glue_post_type'] )
								&& $pt['type'] == $_GET['glue_post_type'] )
							) {
								echo '<option value="' . esc_attr($pt['type']) . '" selected="selected">' . $pt['name'] . '</option>';
							} else {
								echo '<option value="' . esc_attr($pt['type']) . '">' . $pt['name'] . '</option>';
							}
						}
					}
					?>
					<?php
					if ( ! empty( $repeating_fields_groups_post_types ) ) {
						?>
						<optgroup label="<?php echo esc_attr( __( 'Repeatable field groups', 'wp-cred' ) ); ?>">
						<?php
						foreach ( $repeating_fields_groups_post_types as $rfg_pt ) {
							echo '<option value="' . esc_attr( $rfg_pt->name ) . '"'
								. ' ' . selected( $settings['post']['post_type'], $rfg_pt->name, false )
								. '>'
								. $rfg_pt->labels->name
								. '</option>';
						}
						?>
						</optgroup>
						<?php
					}
					?>
				</select>
			</td>
		</tr>

		<tr>
			<td>
				<?php _e( 'Set this post status:', 'wp-cred' ); ?>
			</td>
			<td>
				<select id="cred_post_status" name="_cred[post][post_status]" class='cred_ajax_change' required>
					<option value='' <?php if ( ! isset( $settings['post']['post_status'] ) || empty( $settings['post']['post_status'] ) ) {
						echo 'selected="selected"';
					} ?>><?php _e( '-- Select status --', 'wp-cred' ); ?></option>
					<?php
					foreach ( $stati['basic'] as $basic_post_status_name => $basic_post_status_label ) {
						?>
						<option value='<?php echo esc_attr( $basic_post_status_name ); ?>'><?php echo esc_html( $basic_post_status_label ); ?></option>
						<?php
					}
					?>
					<optgroup label="<?php echo esc_attr( $stati_label['native'] ); ?>">
					<?php
					foreach ( $stati['native'] as $native_post_status_name => $native_post_status_label ) {
						?>
						<option value='<?php echo esc_attr( $native_post_status_name ); ?>'><?php echo esc_html( $native_post_status_label ); ?></option>
						<?php
					}
					?>
					</optgroup>
					<?php
					if ( count( $stati['custom'] ) > 0 ) {
						?>
						<optgroup label="<?php echo esc_attr( $stati_label['custom'] ); ?>">
						<?php
						foreach ( $stati['custom'] as $custom_post_status_name => $custom_post_status_label ) {
							?>
							<option value='<?php echo esc_attr( $custom_post_status_name ); ?>'><?php echo esc_html( $custom_post_status_label ); ?></option>
							<?php
						}
						?>
						</optgroup>
						<?php
					}
					?>
				</select>
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
				<select id="cred_form_success_action" name="_cred[form][action]" required>
					<?php
					$form_actions = apply_filters( 'cred_admin_submit_action_options', array(
						"form" => __( 'Keep displaying this form', 'wp-cred' ),
						"message" => __( 'Display a message instead of the form...', 'wp-cred' ),
						"post" => __( 'Display the post', 'wp-cred' ),
						"custom_post" => __( 'Go to a specific post...', 'wp-cred' ),
						"page" => __( 'Go to a page...', 'wp-cred' ),
					), $settings['action'], $form );
					?>
					<option value="" disabled="disabled"><?php echo esc_html( __( '-- Select action --', 'wp-cred' ) ); ?></option>
					<?php
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

					<span data-cred-bind="{ action: 'show', condition: '_cred[form][action] in [post,custom_post,page]' }">
						<?php _e( 'Redirect delay for: ', 'wp-cred' ); ?>
						<input type='text' size='3' id='cred_form_redirect_delay' name='_cred[form][redirect_delay]' value='<?php echo esc_attr( $settings['redirect_delay'] ); ?>'/>
						<?php _e( ' seconds.', 'wp-cred' ); ?>
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
		do_action( 'cred_ext_cred_form_settings', $form, $settings );
		do_action( 'cred_ext_cred_post_form_settings', $form, $settings );
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

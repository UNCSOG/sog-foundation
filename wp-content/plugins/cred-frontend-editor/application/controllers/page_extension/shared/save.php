<?php

namespace OTGS\Toolset\CRED\Controller\Forms\Shared\PageExtension;

/**
 * Form save metabox extension.
 *
 * @since 2.1
 * @todo Review this HTML layout, FGS
 * @todo Review this $delete_link, FGS
 */
class Save {

	/**
	 * Generate the Sve metabox.
	 *
	 * @param object $form
	 * @param array $callback_args
	 *
	 * @since 2.1
	 */
	public function print_metabox_content( $form, $callback_args = array() ) {
		$button_save_label = 'private' === $form->post_status
			// translators: Text of a button for updating a form.
			? __( 'Update form', 'wp-cred' )
			// translators: Text of a button for saving a form.
			: __( 'Save form', 'wp-cred' );
		?>
		<div id="save-form-actions" style="display:none">
			<label>
				<?php esc_html_e( 'Form slug:', 'wp-cred' ); ?> <input name="post_name" size="13" id="post_name" class="regular-text" value="<?php echo esc_attr( $form->post_name ); ?>" type="text">
			</label>
			<?php
			if ( $this->maybe_show_actions() ) {
			?>
			<a href="#" id="js-cred-delete-form" class="submitdelete deletion js-cred-delete-form">
				<?php
				/* translators: Label of the element that will delete a post or user form from its editor page */
				echo esc_html( __( 'Delete form', 'wp-cred' ) );
				?>
			</a>
			<input id="js-cred-save-form" name="save" type="submit" class="cred-save-form js-cred-save-form button button-primary" value="<?php esc_attr_e( "Save form", 'wp-cred' ); ?>">
			<?php
			}
			?>
		</div>
		<?php
   }

   /**
	* Decide whether the save and trash actions should be available.
	*
	* @return bool
	* @since 2.3
	*/
   private function maybe_show_actions() {
		if ( toolset_getget( 'in-iframe-for-layout', false ) ) {
			return false;
		}
		return true;
   }
}

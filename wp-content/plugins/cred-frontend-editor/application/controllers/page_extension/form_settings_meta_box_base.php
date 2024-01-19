<?php

/**
 * Abstract Class Responsible to create Form Settings Meta Box
 *
 * @since 1.9.3
 */
abstract class CRED_Page_Extension_Form_Settings_Meta_Box_Base implements CRED_Page_Extension_Form_Settings_Meta_Box_Interface {

	/**
	 * @param array $settings
	 */
	public function enqueue_scripts( $settings ) {
		//enqueue template script
		wp_enqueue_script( 'cred_form_settings_box' );
		wp_localize_script( 'cred_form_settings_box', 'cred_form_settings_box', $settings );
	}

	/**
	 * @param $settings
	 *
	 * @return string
	 */
	public function get_form_action_pages( $settings ) {
		$page_query = new WP_Query( array(
			'post_type' => 'page',
			'post_status' => 'publish',
			'posts_per_page' => - 1,
		) );

		ob_start();
		/** @var WP_Post $current_post */
		foreach( $page_query->posts as $current_post ) {
			?>
			<option value="<?php esc_attr_e( $current_post->ID ); ?>" <?php selected( isset( $settings['action_page'] ) && $settings['action_page'] == $current_post->ID ); ?>><?php esc_html_e( $current_post->post_title ) ?></option>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * @param $settings
	 * @param $default_empty_action_post_type_label
	 * @param $default_empty_action_post_label
	 * @param $current_action_post
	 * @param $form_current_custom_post
	 * @param $form_post_types
	 */
	public function get_form_go_to_specific_post_settings( &$settings, $default_empty_action_post_type_label, $default_empty_action_post_label, &$current_action_post, &$form_current_custom_post, &$form_post_types ) {
		$current_action_post = null;
		ob_start();
		?>
        <option value=""><?php echo $default_empty_action_post_label; ?></option>
		<?php
		if ( isset( $settings['action_post'] )
			&& ! empty( $settings['action_post'] ) ) {
			$post = get_post( $settings['action_post'] );
			$current_action_post = $post;
			?>
            <option value="<?php echo esc_attr( $post->ID ); ?>">
				<?php echo $post->post_title; ?>
            </option>
			<?php
		}
		$form_current_custom_post = ob_get_clean();

		$post_types = get_post_types( array( 'public' => true, 'publicly_queryable' => true, 'show_in_nav_menus' => true ), 'names' );
		$post_types = array_merge( array( '' => $default_empty_action_post_type_label ), $post_types );

		ob_start();
		foreach ( $post_types as $post_type_key => $post_type_value ) {
			?>
            <option value="<?php echo esc_attr( $post_type_key ); ?>" <?php selected( isset( $post ) && $post_type_value === $post->post_type ); ?>><?php echo $post_type_value; ?></option>
			<?php
		}
		$form_post_types = ob_get_clean();
	}
}

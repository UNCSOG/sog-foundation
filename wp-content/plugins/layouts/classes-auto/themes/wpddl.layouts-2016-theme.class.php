<?php
class Toolset_Compatibility_Theme_twenty_sixteen extends Toolset_Compatibility_Theme_Handler{


	public function add_register_styles( $styles ) {

		$styles['twenty-sixteen-overrides-css'] = new WPDDL_style( 'twenty-sixteen-overrides-css', WPDDL_RES_RELPATH . '/css/themes/twenty-sixteen-overrides.css', array(), WPDDL_VERSION, 'screen' );

		return $styles;
	}

	public function frontend_enqueue() {
		do_action( 'toolset_enqueue_styles', array( 'twenty-sixteen-overrides-css' ) );
	}

	protected function run_hooks() {
		add_filter( 'toolset_add_registered_styles', array( $this, 'add_register_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ) );

		add_action( 'wp_get_attachment_image_src', array( $this, 'fix_attachment_page_layout' ), 100, 4 );
		add_filter('ddl_render_cell_content', array($this,'fix_attachment_output'), 10, 3 );
	}

	public function fix_attachment_page_layout( $image, $attachment_id, $size, $icon ) {
		if ( is_attachment() && ! is_admin() ) {
			return '';
		}
		return $image;
	}

	function fix_attachment_output( $content, $cell, $renderer ) {

		if ( is_attachment() ) {
			global $post;

			remove_filter('wp_get_attachment_image_src', array( $this, 'fix_attachment_page_layout' ), 100);
			$attach = wp_get_attachment_url($post->ID );
			// Do not render attachment post type posts' bodies automatically
			if ( WPDD_Utils::is_wp_post_object( $post ) && $post->post_type === 'attachment' ) {
				if ( $cell->get_cell_type() === "cell-post-content" && ! empty( $attach ) && is_array( getimagesize( $attach ) ) ) {
					return '<a href="'.$attach.'"><img src="'.$attach.'"></a>' . $content;
				}
			}
		}

		return $content;
	}

}
<?php

/**
 * Class WPDDL_Update_Translated_Private_layout
 */
class WPDDL_Update_Translated_Private_Layout {

	const PACKAGE_KIND = 'Layout';

	/** @var  WPDDL_WPML_Private_Layout */
	private $private_layout;

	public function __construct( WPDDL_WPML_Private_Layout $private_layout ) {
		$this->private_layout = $private_layout;
	}

	public function add_hooks() {
		add_action('ddl_update_translated_posts', array( $this, 'update_translated_layouts_when_original_is_updated' ), 10, 1);
	}


	public function update_translated_layouts_when_original_is_updated( $post_id ) {
		$wpml_status = new WPDDL_WPML_Status();
		if ( $wpml_status->is_string_translation_active() ) {
			$post = get_post( $post_id );
			if ( $this->is_original_post( $post ) ) {
				$string_translations = $this->get_string_translations( $post );
				$translations        = $this->get_translations( $post );
				foreach ( $translations as $translation ) {
					$this->private_layout->update_translation(
						self::PACKAGE_KIND,
						$translation->element_id,
						$post,
						$string_translations,
						$translation->language_code
					);
				}

				do_action( 'wpml_tm_save_post', $post_id, $post, false );

			}
		}
	}

	private function is_original_post( $post ) {
		return apply_filters( 'wpml_is_original_content', false, $post->ID, 'post_' . $post->post_type );
	}

	private function get_string_translations( $post ) {
		return apply_filters( 'wpml_get_translated_strings',
			array(),
			array(
				'kind' => self::PACKAGE_KIND,
				'name' => $post->ID,
			)
		);
	}

	private function get_translations( $post ) {
		$translations = array();

		$trid = apply_filters( 'wpml_element_trid', 0, $post->ID, 'post_' . $post->post_type );
		if ( $trid ) {
			$translations = apply_filters( 'wpml_get_element_translations', array(), $trid, 'post_' . $post->post_type );
		}

		foreach ( $translations as $lang => $translation ) {
			if ( $translation->element_id == $post->ID ) {
				unset ( $translations[ $lang ] );
			}
		}

		return $translations;
	}


}

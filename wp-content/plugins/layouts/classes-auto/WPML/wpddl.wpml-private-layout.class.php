<?php

class WPDDL_WPML_Private_Layout {

	const PACKAGE_KIND = 'Layout';
	const TRANSLATION_COMPLETE = 10;

	private $context;
	private $string_translations;
	private $lang;

	/** @var  WPDD_json2layout */
	private $json_to_layout_converter;
	/** @var WPDDL_Private_Layout */
	private $private_layout;

	public function __construct( WPDD_json2layout $json_to_layout_converter, WPDDL_Private_Layout $private_layout ) {
		$this->json_to_layout_converter = $json_to_layout_converter;
		$this->private_layout           = $private_layout;
	}

	public function add_hooks() {
		add_filter( 'wpml_page_builder_support_required', array( $this, 'wpml_page_builder_support' ), 10, 1 );
		add_action( 'wpml_page_builder_string_translated', array( $this, 'update_translation' ), 10, 5 );
	}

	public function wpml_page_builder_support( array $data ) {
		$data[] = self::PACKAGE_KIND;
		return $data;
	}

	/**
	 * @deprecated
	 */
	public function update_translation( $kind, $translated_post_id, $original_post, $string_translations, $lang ) {

		if ( $kind == self::PACKAGE_KIND ) {

			$this->string_translations = $string_translations;
			$this->lang                = $lang;

			$layout_settings = new WPDDL_Layout_Settings( $original_post->ID );
			$layout_json     = $layout_settings->get();
			if ( $layout_json ) {
				$layout        = $this->json_to_layout_converter->json_decode( $layout_json );
				$this->context = $layout->get_string_context();

				$layout->process_cells( $this );

				$translated_rows   = $layout->get_as_array();
				$translated_layout = json_decode( $layout_json );
				$translated_layout->Rows = $translated_rows['Rows'];

				$layout_settings = new WPDDL_Layout_Settings( $translated_post_id );
				$layout_settings->update( $translated_layout );

				$this->private_layout->update_post_content_for_private_layout( $translated_post_id );
			}
		}
	}

	public function process_cell( $cell ) {
		$cell->set_content( $cell->get_translated_content( $this->context, $this ) );
	}

	public function translate( $string_value, $string_name, $package ) {
		if ( isset( $this->string_translations[ $string_name ][ $this->lang ] ) ) {
			if ( $this->string_translations[ $string_name ][ $this->lang ]['status'] == self::TRANSLATION_COMPLETE ) {
				$string_value = $this->string_translations[ $string_name ][ $this->lang ]['value'];
			}
		}

		return $string_value;
	}

}

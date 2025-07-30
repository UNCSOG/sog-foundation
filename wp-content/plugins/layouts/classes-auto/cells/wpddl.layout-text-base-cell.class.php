<?php

abstract class WPDD_layout_text_based_cell extends WPDD_layout_cell {

	protected $content_translatable_strings = null;

	function __construct( $id, $name, $width, $css_class_name = '', $screen_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '' ) {
		parent::__construct( $id, $name, $width, $css_class_name, $screen_name, $content, $css_id, $tag, $unique_id );
	}

	function register_strings_for_translation( $context ) {

		$unique_id = $this->get_unique_id();

		if ( $unique_id ) {

			$which = $this->get( 'wpml_strings_option' );
			if ( ! $which ) {
				$which = 'all';
			}

			if ( $which === 'none' ) {
				return;
			}

			$content = $this->get( 'content' );

			if ( empty( $content ) ) {
				return;
			}

			if ( $which === 'all' ) {

				do_action( 'wpml_register_string',
					$content,
					$unique_id . '_content',
					$context,
					$this->get_name() . ' - Content',
					'VISUAL' );

			} else if ( $which === 'selected' ) {

				$wpml_string_finder = new WPDDL_WPML_Strings_In_Content();
				$strings            = $wpml_string_finder->find( $content );

				foreach ( $strings as $string ) {

					if( isset( $string['name'] ) ){
						$name = $string['name'];
					} else {
						$name = $this->get_string_name_for_wpml_string( $unique_id, $string['string'] );
					}

					if( isset( $string['context'] ) ){
						$context['name'] = $string['context'];
					}

					do_action( 'wpml_register_string',
						$string['string'],
						$name,
						$context,
						$this->get_name() . ' ' . $name,
						'VISUAL' );

				}
			}
		}
	}

	function get_user_option_for_how_to_handle_wpml_strings() {
		$option_value = $this->get( 'ddl_translatable_strings_options' );

		return $option_value ? $option_value : 'all';
	}

	function process( $processor ) {
		$processor->process_cell( $this );
	}

	function get_translated_content( $context, $translate_method = null ) {
		$main_content = $this->get_content();
		$content      = $this->get( 'content' );

		if ( ! $translate_method ) {
			$translate_method = new WPDDL_Translate_String_Via_Filter();
		}

		$which = $this->get( 'wpml_strings_option' );
		if ( ! $which ) {
			$which = 'all';
		}

		if ( $which === 'all' ) {
			$content = $translate_method->translate( $content, $this->get_unique_id() . '_content', $context );
		} else if ( $which === 'selected' ) {

			$wpml_string_finder = new WPDDL_WPML_Strings_In_Content();
			$strings            = $wpml_string_finder->find( $content );

			foreach ( $strings as $string ) {
				$string_value = $translate_method->translate(
					$string['string'],
					$this->get_string_name_for_wpml_string( $this->get_unique_id(), $string['string'] ),
					$context
				);
				$content      = str_replace( $string['content'], $string_value, $content );

			}
		}
		$main_content['content'] = $content;

		return $main_content;
	}

	private function get_string_name_for_wpml_string( $unique_id, $string ) {
		return $unique_id . '_content_wpml-string_' . md5( $string );
	}

	/**
	 * @return bool|mixed|void
	 * checks if content.content contains tags/shortcodes to render post_content and returns true
	 */
	public function check_if_cell_renders_post_content()
	{
		$content = $this->get( 'content' );

		if ( !$content ) {
			return false;
		}

		$checks = apply_filters('ddl-do-not-apply-overlay-for-post-editor', array('wpv-post-body', 'wpv-woo-display-tabs') );

		$bool = false;

		foreach( $checks as $check ){
			if( strpos($content, $check) !== false ){
				$bool = true;
				break;
			}
		}

		return apply_filters( 'ddl-show_post_edit_page_editor_overlay', $bool, $this );
	}
}

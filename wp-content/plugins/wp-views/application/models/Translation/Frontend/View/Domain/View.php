<?php

namespace  OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain;

// Common Dependencies
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\IBlockContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\ITranslatable;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\PostContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\Settings;

/**
 * Class View
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain
 *
 * @since TB 1.3
 */
class View {
	/** @var PostContent */
	private $post_content;

	/** @var Settings */
	private $wpv_settings;

	/** @var IBlockContent */
	private $block_current_language;

	/** @var ITranslatable[] */
	private $translatable_components = [];

	/** @var bool */
	private $are_settings_translated = false;

	/**
	 * View constructor.
	 *
	 * @param PostContent $post_content
	 * @param IBlockContent $block_current_language
	 */
	public function __construct( PostContent $post_content, IBlockContent $block_current_language ) {
		$this->post_content = $post_content;
		$this->block_current_language = $block_current_language;
	}

	public function set_settings( Settings $wpv_settings ) {
		$this->wpv_settings = $wpv_settings;
	}

	/**
	 * @param ITranslatable $component
	 */
	public function add_translatable_component( ITranslatable $component ) {
		$this->translatable_components[] = $component;
	}

	/**
	 * Return translated settings.
	 *
	 * @return array
	 */
	public function get_translated_wpv_settings() {
		if( ! $this->wpv_settings ) {
			throw new \RuntimeException( 'Settings must be set to get the translation of them.' );
		}

		if( ! $this->are_settings_translated ) {
			// Settings are not translated yet.
			$this->are_settings_translated = true;
			$this->translate_wpv_settings();
		}

		return $this->wpv_settings->get();
	}

	/**
	 * Everything related to the search is taken from the wpv_settings post_meta.
	 * As there is no wpv_settings per language we need to translate "manually".
	 */
	private function translate_wpv_settings() {
		foreach( $this->translatable_components as $component ) {
			$component->translate_settings( $this->wpv_settings, $this->block_current_language );
		}
	}

	public function get_translated_post_content( $untranslated_post_content ) {
		$this->post_content->set( $untranslated_post_content );

		foreach( $this->translatable_components as $component ) {
			$component->translate_content( $this->post_content, $this->block_current_language );
		}

		return $this->post_content->get();
	}
}

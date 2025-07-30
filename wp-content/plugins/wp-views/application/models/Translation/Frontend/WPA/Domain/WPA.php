<?php

namespace  OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Domain;

// Common Dependencies
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\IBlockContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\ITranslatable;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\PostContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\Settings;

/**
 * Class WPA
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Domain
 *
 * @since TB 1.3
 */
class WPA {
	/** @var PostContent */
	private $post_content;

	/** @var Settings */
	private $wpa_settings;

	/** @var IBlockContent */
	private $block_current_language;

	/** @var ITranslatable[] */
	private $translatable_components = [];

	/** @var bool */
	private $are_settings_translated = false;

	/**
	 * WPA constructor.
	 *
	 * @param PostContent $post_content
	 * @param IBlockContent $block_current_language
	 */
	public function __construct(
		PostContent $post_content,
		IBlockContent $block_current_language
	) {
		$this->post_content = $post_content;
		$this->block_current_language = $block_current_language;
	}

	/**
	 * @param ITranslatable $component
	 */
	public function add_translatable_component( ITranslatable $component ) {
		$this->translatable_components[] = $component;
	}

	/**
	 * @param Settings $wpa_settings
	 */
	public function set_settings( Settings $wpa_settings ) {
		$this->wpa_settings = $wpa_settings;

		// These settings are probably not translated.
		$this->are_settings_translated = false;
	}

	public function get_translated_wpa_settings() {
		if( $this->wpa_settings === null ) {
			throw new \RuntimeException( 'Settings must be set before translated settings can be requested.' );
		}

		if( ! $this->are_settings_translated ) {
			$this->are_settings_translated = true;
			$this->translate_wpa_settings();
		}
		return $this->wpa_settings->get();
	}

	/**
	 * Everything related to the search is taken from the wpa_settings post_meta.
	 * As there is no wpa_settings per language we need to translate "manually".
	 */
	private function translate_wpa_settings() {
		foreach( $this->translatable_components as $component ) {
			$component->translate_settings( $this->wpa_settings, $this->block_current_language );
		}
	}

	/**
	 * @param string $untranslated_post_content
	 *
	 * @return string
	 */
	public function get_translated_post_content( $untranslated_post_content ) {
		$this->post_content->set( $untranslated_post_content );

		foreach( $this->translatable_components as $component ) {
			$component->translate_content( $this->post_content, $this->block_current_language );
		}

		return $this->post_content->get();
	}
}

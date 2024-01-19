<?php

namespace ToolsetBlocks\WPML;

use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class Settings
 *
 * Provides following user settings for post types
 * - is translatable
 * - should fallback to original if translation is missing
 *
 * @since 1.5.0
 */
class Settings {
	/*
	 * WPML uses integers for the different settings:
	 * - 0 = Not translatable
	 * - 1 = Translatable and NOT return original if there is no translated content.
	 * - 2 = Translatable and return original if there is no translated content.
	 */
	const WPML_VALUE_FOR_NOT_TRANSLATABLE = 0;
	const WPML_VALUE_FOR_NO_ORIGINAL_ON_MISSING_TRANSLATION = 1;
	const WPML_VALUE_FOR_USE_ORIGINAL_ON_MISSING_TRANSLATION = 2;

	/** @var Actions */
	private $wp;

	/** @var ?array */
	private $post_types_translation_settings;

	/**
	 * Settings constructor.
	 *
	 * @param Actions $wp
	 */
	public function __construct( Actions $wp ) {
		$this->wp = $wp;
	}

	/**
	 * Returns true if the given post type is translatable.
	 *
	 * @param string $post_type The requested post type.
	 *
	 * @return bool
	 */
	public function is_post_type_translatable( $post_type ) {
		$setting = $this->setting_for_post_type( $post_type );

		return $setting > self::WPML_VALUE_FOR_NOT_TRANSLATABLE;
	}


	/**
	 * Returns true if the original content should be returned when there is no translated content.
	 *
	 * @param string $post_type The requested post type.
	 *
	 * @return bool
	 */
	public function is_translation_fallback_to_original_active_for_post_type( $post_type ) {
		$setting = $this->setting_for_post_type( $post_type );

		return $setting === self::WPML_VALUE_FOR_USE_ORIGINAL_ON_MISSING_TRANSLATION;
	}


	/**
	 * Returns settings for post types.
	 * The WPML setting name is 'custom_posts...' but it also holds settings for post, page and attachment.
	 *
	 * @return array
	 */
	private function post_types_translation_settings() {
		if ( $this->post_types_translation_settings === null ) {
			// Not loaded yet, get settings by using wpml filter.
			$this->post_types_translation_settings =
				$this->wp->apply_filters( 'wpml_setting', [], 'custom_posts_sync_option' );
		}

		// Return settings.
		return $this->post_types_translation_settings;
	}


	/**
	 * Returns the wpml setting for a post type.
	 *
	 * @param string $post_type
	 *
	 * @return int 0, 1 or 2.
	 */
	private function setting_for_post_type( $post_type ) {
		if ( ! is_string( $post_type ) || empty( $post_type ) ) {
			return self::WPML_VALUE_FOR_NOT_TRANSLATABLE;
		}

		$settings = $this->post_types_translation_settings();
		if ( ! array_key_exists( $post_type, $settings ) ) {
			return self::WPML_VALUE_FOR_NOT_TRANSLATABLE;
		}

		// Normalise setting to make sure having an integer.
		return is_numeric( $settings[ $post_type ] )
			? (int) $settings[ $post_type ]
			: self::WPML_VALUE_FOR_NOT_TRANSLATABLE;
	}
}

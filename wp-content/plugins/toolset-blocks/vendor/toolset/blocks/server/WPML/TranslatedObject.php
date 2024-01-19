<?php

namespace ToolsetBlocks\WPML;

use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class TranslatedObject
 *
 * Provides the current language id for a given id & post type. It's basically an alias of the filter wpml_object_id,
 * but with the advantage that $return_original_if_missing will be set automatically regarding the user setting for
 * the post type if not explicit set.
 *
 * @since 1.5.0
 */
class TranslatedObject {

	/** @var Actions */
	private $wp;

	/** @var Settings */
	private $wpml_settings;

	/**
	 * @param Actions $wp
	 * @param Settings $wpml_settings
	 */
	public function __construct( Actions $wp, Settings $wpml_settings ) {
		$this->wp = $wp;
		$this->wpml_settings = $wpml_settings;
	}

	/**
	 * @param int $id The id (in the wp_post table).
	 * @param string $post_type The post type of the id.
	 * @param ?bool $force_return_original_if_missing Show original content if there is no translation.
	 *                                          If not set the config in the WPML Settings will be used.
	 *
	 * @return mixed The translated id as integer or the original input on error.
	 */
	public function current_language_id( $id, $post_type, $force_return_original_if_missing = null ) {
		if ( ! is_numeric( $id ) || empty( $id ) || ! is_string( $post_type ) || empty( $post_type ) ) {
			return $id;
		}

		if ( ! $this->wpml_settings->is_post_type_translatable( $post_type ) ) {
			// This post type is not translatable. Nothing to do here.
			return $id;
		}

		if ( $force_return_original_if_missing === null ) {
			// No forced_original_if_missing, get user setting for the post type.
			$force_return_original_if_missing = $this->wpml_settings->is_post_type_translatable( $post_type );
		}

		return $this->wp->apply_filters( 'wpml_object_id', $id, $post_type, $force_return_original_if_missing );
	}
}

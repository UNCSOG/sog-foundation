<?php

namespace ToolsetBlocks\Block\Image;

use ToolsetBlocks\WPML\Settings;
use ToolsetBlocks\WPML\TranslatedObject;
use ToolsetCommonEs\Library\WordPress\Image;

/**
 * Class MediaLibrary
 *
 * Provides caption and alt text by attachment id or guid.
 *
 * @since 1.5.0
 */
class MediaLibrary {
	const POST_TYPE_ATTACHMENT = 'attachment';

	/** @var TranslatedObject */
	private $wpml_object;

	/** @var Settings */
	private $wpml_settings;

	/** @var Image */
	private $wp;

	/** @var ?bool */
	private $is_translation_fallback_to_original_active;

	/**
	 * @param TranslatedObject $wpml_object
	 * @param Settings $wpml_settings
	 * @param Image $wp
	 */
	public function __construct( TranslatedObject $wpml_object, Settings $wpml_settings, Image $wp ) {
		$this->wpml_object = $wpml_object;
		$this->wpml_settings = $wpml_settings;
		$this->wp = $wp;
	}

	/**
	 * Returns the alt text of the image by its ID.
	 *
	 * @param int $attachment_id The id of the attachment which should be translated.
	 *                           Note: if the id is already the translated one it will just return that alt text.
	 *
	 * @return string Translated alt text.
	 */
	public function alt_text_by_id( $attachment_id ) {
		if ( ! is_numeric( $attachment_id ) || empty( $attachment_id ) ) {
			return '';
		}

		// Get translated attachment.
		$translated_attachment_id =
			$this->wpml_object->current_language_id( $attachment_id, self::POST_TYPE_ATTACHMENT );

		if ( $alt_text = $this->wp->alt_text_by_id( $translated_attachment_id ) ) {
			// Caption found.
			return $alt_text;
		}

		// Check if the original should be returned if no translated alt text is available.
		if ( $translated_attachment_id !== $attachment_id && $this->is_translation_fallback_to_original_active() ) {
			return $this->wp->alt_text_by_id( $attachment_id );
		}

		// Simply no alt text.
		return '';
	}


	/**
	 * Returns the alt text of the image by its GUID.
	 *
	 * @param string $guid Usually the guid is the url of the image.
	 *
	 * @return string The alt text of the image.
	 */
	public function alt_text_by_guid( $guid ) {
		if ( ! is_string( $guid ) || empty( $guid ) ) {
			return '';
		}

		return $this->alt_text_by_id( $this->wp->attachment_id_by_guid( $guid ) );
	}

	/**
	 * Returns the caption of the image with the given id.
	 *
	 * @param int $attachment_id The id of the attachment which should be translated.
	 *                           Note: if the id is already the translated one it will just return that caption.
	 *
	 * @return string Translated caption text. Can be empty.
	 */
	public function caption_by_id( $attachment_id ) {
		if ( ! is_numeric( $attachment_id ) || empty( $attachment_id ) ) {
			return '';
		}

		// Get translated attachment.
		$translated_attachment_id =
			$this->wpml_object->current_language_id( $attachment_id, self::POST_TYPE_ATTACHMENT );

		if ( $caption = $this->wp->caption_by_id( $translated_attachment_id ) ) {
			// Caption found.
			return $caption;
		}

		// Check if the original should be returned if no translated caption is available.
		if ( $translated_attachment_id !== $attachment_id && $this->is_translation_fallback_to_original_active() ) {
			return $this->wp->caption_by_id( $attachment_id );
		}

		// Simply no caption.
		return '';
	}

	/**
	 * Returns the caption of the image with the given guid.
	 *
	 * @param string $guid GUID of image.
	 *
	 * @return string Translated caption text. Can be empty.
	 */
	public function caption_by_guid( $guid ) {
		if ( ! is_string( $guid ) || empty( $guid ) ) {
			return '';
		}

		return $this->caption_by_id( $this->wp->attachment_id_by_guid( $guid ) );
	}

	/**
	 * Returns true when the original text should be used when there is no translated text.
	 *
	 * @return bool
	 */
	private function is_translation_fallback_to_original_active() {
		if ( $this->is_translation_fallback_to_original_active === null ) {
			$this->is_translation_fallback_to_original_active =
				$this->wpml_settings->is_translation_fallback_to_original_active_for_post_type(
					self::POST_TYPE_ATTACHMENT
				);
		}

		return $this->is_translation_fallback_to_original_active;
	}
}

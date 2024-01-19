<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\TranslatableStructure;

use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\ITranslatable;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\PostContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\IBlockContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\Settings;

/**
 * Class OutputBeforeSearch
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain
 *
 * @since TB 1.3
 */
class OutputBeforeSearch implements ITranslatable {

	/** @var ITranslatable */
	private $different_structure;

	/**
	 * OutputBeforeSearch constructor.
	 *
	 * @param ITranslatable|null $different_structure
	 */
	public function __construct( ITranslatable $different_structure = null ) {
		$this->different_structure = $different_structure;
	}

	/**
	 * @inheritDoc
	 */
	public function translate_settings( Settings $settings, IBlockContent $block_current_language ) {
		// Nothing to do for the settings.
	}

	/**
	 * @inheritDoc
	 */
	public function translate_content( PostContent $post_untranslated, IBlockContent $block_translated ) {

		// Check the contents ends with a closing </div>.
		if( ! $post_untranslated->ends_with_closing_div_tag() ) {
			$this->delegate_to_different_structure( $post_untranslated, $block_translated );
			return;
		}

		// Get start position,php if not possible continue with next structure.
		$pos_start = $post_untranslated->position_start();
		if( $pos_start === false ) {
			$this->delegate_to_different_structure( $post_untranslated, $block_translated );
			return;
		}

		// Get loop position, if not possible continue with next structure.
		if( ! $pos_loop = $post_untranslated->position_loop() ) {
			$this->delegate_to_different_structure( $post_untranslated, $block_translated );
			return;
		}

		// Get search position, if not possible continue with next structure.
		if( ! $pos_search = $post_untranslated->position_search() ) {
			$this->delegate_to_different_structure( $post_untranslated, $block_translated );
			return;
		}

		// Make sure the order is correct.
		if( $pos_start > $pos_loop || $pos_loop > $pos_search  ) {
			// This structure does not apply. Delegate to different structure.
			$this->delegate_to_different_structure( $post_untranslated, $block_translated );
			return;
		}

		// The post content uses this structure. Apply translation:

		// Between Block Start and Loop.
		$translation = $block_translated->get_content_between_start_and_output();
		$post_untranslated->apply_translation_between_start_and_loop( $translation );

		// Between Loop and Search.
		$translation = $block_translated->get_content_between_output_and_search();
		$post_untranslated->apply_translation_between_loop_and_search( $translation );

		// Between Search and Block End.
		$translation = $block_translated->get_content_between_search_and_end();
		$post_untranslated->apply_translation_between_search_and_end( $translation );
	}

	private function delegate_to_different_structure( PostContent $post_untranslated, IBlockContent $block_translated ) {
		if( $this->different_structure ) {
			$this->different_structure->translate_content( $post_untranslated, $block_translated );
		}
	}
}

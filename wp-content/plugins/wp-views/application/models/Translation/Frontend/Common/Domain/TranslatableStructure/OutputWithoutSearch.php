<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\TranslatableStructure;

use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\ITranslatable;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\PostContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\IBlockContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\Settings;

/**
 * Class OutputWithoutSearch
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain
 *
 * @since TB 1.3
 */
class OutputWithoutSearch implements ITranslatable {

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
		// Between Block Start and Loop.
		$translation = $block_translated->get_content_between_start_and_output();
		$post_untranslated->apply_translation_between_start_and_loop( $translation );

		// Between Loop and Block End.
		$translation = $block_translated->get_content_between_output_and_end();
		$post_untranslated->apply_translation_between_loop_and_end( $translation );
	}
}

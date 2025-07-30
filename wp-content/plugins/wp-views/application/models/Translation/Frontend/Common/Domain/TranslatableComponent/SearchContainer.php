<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\TranslatableComponent;

// Domain Dependencies
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\ITranslatable;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\Settings;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\PostContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\IBlockContent;

/**
 * Class SearchContainer
 *
 * With the current structure the complete search container content can be replaced and we do not need to apply
 * translations block by block.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain
 *
 * @since TB 1.3
 */
class SearchContainer implements ITranslatable {

	/**
	 * @inheritDoc
	 */
	public function translate_settings(
		Settings $settings_untranslated,
		IBlockContent $block_translated
	) {
		if( $filter_meta_html_current_language = $block_translated->get_content_search_container() ) {
			$settings_untranslated->set_filter_meta_html( $filter_meta_html_current_language );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function translate_content( PostContent $post_untranslated, IBlockContent $block_translated ) {
		// Nothing to do for the Search Container.
	}
}

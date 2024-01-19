<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain;


/**
 * Interface ITranslatable
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain
 *
 * @since TB 1.3
 */
interface ITranslatable {
	/**
	 * @param Settings $settings
	 * @param IBlockContent $block_current_language
	 */
	public function translate_settings(
		Settings $settings,
		IBlockContent $block_current_language
	);

	/**
	 * Apply translations of strings inside the content.
	 *
	 * @param PostContent $post_untranslated
	 * @param IBlockContent $block_translated
	 *
	 * @return mixed
	 */
	public function translate_content(
		PostContent $post_untranslated,
		IBlockContent $block_translated
	);
}

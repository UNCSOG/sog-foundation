<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\Repository;


use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain\Block;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain\ContentLines;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain\CustomSearchFilter;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain\CustomSearchReset;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain\CustomSearchSubmit;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain\ITranslatableBlock;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain\Loop;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain\IRepository;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain\View;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Common\Domain\TranslationService;
use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class WordPressRepository
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\Repository
 * @codeCoverageIgnore No need to test how the domain is build. Very obvious if a mistake is being done here.
 *
 * @since TB 1.3
 */
class WordPressRepository implements IRepository {
	/** @var View */
	private $view;

	/**
	 * @inheritDoc
	 * @return ITranslatableBlock
	 */
	public function get_entity_by_wp_block_parser_class( \WP_Block_Parser_Block $block ) {
		$translation_service = new TranslationService( new Actions() );

		switch( $block->blockName ) {
			case 'toolset-views/view-editor':
				// The view editor itself has no translation to be handled here. But the template block (the loop)
				// needs information of the View itself. Luckily the blocks are register from outer to inner block
				// so the view-editor (outer block) is always registered before the loop block.
				if( ! array_key_exists( 'viewId', $block->attrs ) ) {
					throw new \InvalidArgumentException( 'No viewId in the block attributes.' );
				}
				$this->view = new View( get_post_meta( $block->attrs[ 'viewId' ], '_wpv_view_data', true ) );
				return null;
			case 'toolset-views/view-template-block':
				if( ! isset( $this->view ) ) {
					// Running into this exception probably means the blocks are no longer read from outer to inner.
					throw new \RuntimeException( 'The view block is not registered yet.' );
				}
				return new Loop( $this->create_block( $block ), $translation_service, $this->view );
			case 'toolset-views/custom-search-filter':
				return new CustomSearchFilter( $this->create_block( $block ), $translation_service );
			case 'toolset-views/custom-search-submit':
				return new CustomSearchSubmit( $this->create_block( $block ), $translation_service );
			case 'toolset-views/custom-search-reset':
				return new CustomSearchReset( $this->create_block( $block ), $translation_service );
		}

		throw new \InvalidArgumentException( $block->blockName . " has no handled translations." );
	}

	private function create_block( \WP_Block_Parser_Block $block ) {
		return new Block( $block->blockName, new ContentLines( $block->innerContent ) );
	}
}

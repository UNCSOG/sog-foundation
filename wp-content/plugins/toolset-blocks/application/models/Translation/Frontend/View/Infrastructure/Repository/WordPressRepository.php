<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\Repository;

// Domain Depndencies
use OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain\BlockContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain\PostContentType;
use OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain\IRepository;
use OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain\View;
use OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain\ViewId;

// Common Dependencies
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\BlockContent as CommonBlockContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\TranslatableComponent\SearchContainer;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\PostContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\Settings;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\TranslatableStructure\OutputBeforeSearch;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\TranslatableStructure\OutputWithoutSearch;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\TranslatableStructure\SearchBeforeOutput;

/**
 * Class WordPressRepository
 *
 * All data coming from the filters, so all the repo does is composing.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\Repository
 * @codeCoverageIgnore No need to test how the domain is build. Very obvious if a mistake is being done here.
 *
 * @since TB 1.3
 */
class WordPressRepository implements IRepository {
	/** @var CommonBlockContent */
	private $common_block_content;


	/**
	 * WordPressRepository constructor.
	 *
	 * @param CommonBlockContent $common_block_content
	 */
	public function __construct( CommonBlockContent $common_block_content ) {
		$this->common_block_content = $common_block_content;
	}


	public function get_view_by_id_and_settings_and_post( $view_id, $settings, \WP_Post $post_translated ) {
		$wpv_settings = new Settings( $settings );
		$view_id = new ViewId( $view_id );
		$block_current_language = new BlockContent(
			$post_translated->post_content,
			$view_id,
			$this->common_block_content
		);

		$view = new View( new PostContent( new PostContentType() ), $block_current_language );
		$view->set_settings( $wpv_settings );
		$view->add_translatable_component( new SearchContainer() );

		return $view;
	}

	public function get_view_by_id( $view_id, \WP_Post $post_translated ) {
		$view_id = new ViewId( $view_id );
		$block_current_language = new BlockContent( $post_translated->post_content, $view_id, $this->common_block_content );

		$view = new View( new PostContent( new PostContentType() ), $block_current_language );
		$view->add_translatable_component(
			new SearchBeforeOutput(
				new OutputBeforeSearch(
					new OutputWithoutSearch()
				)
			)
		);

		return $view;
	}
}

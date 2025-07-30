<?php

namespace ToolsetBlocks\PublicDependencies;

use ToolsetBlocks\PublicDependencies\Dependency\IContent;
use ToolsetBlocks\PublicDependencies\Dependency\IGeneral;


/**
 * Frontend dependencies
 */
class Frontend {

	/** @var IGeneral[] */
	private $dependencies = array();

	/** @var IContent[] */
	private $dependencies_content = array();

	/**
	 * Add a content based dependecy
	 *
	 * @param IContent $dependency [description]
	 */
	public function add_content_based_dependency( IContent $dependency ) {
		$this->dependencies_content[] = $dependency;
	}

	/**
	 * Load all previous added dependencies
	 */
	public function load() {
		// content related dependencies
		if ( null !== $this->dependencies_content ) {
			// Requires to run on 8 as on 9 "do_blocks" will run, which will render the blocks and that removes
			// the blocks config from the $content. Some dependencies loaders use the block config to trigger their
			// resource load.
			add_filter( 'the_content', array( $this, 'load_dependencies_content' ), 8 );
			// Priorities 5-10 are when some blocks are invisible in $content when inside Conditional block which is
			// inside a View block. However, at priority 4, Conditional seems to have not yet decided if it's content
			// should be shown or not (so, library is loaded even if it's block gets hidden). At 11, everything seems
			// to work properly. And that's why we run this filter again at 11.
			add_filter( 'the_content', array( $this, 'load_dependencies_content' ), 11 );
			// And for WPAs, we need to hook to this other filter, because the_content is empty there.
			add_filter( 'toolset_the_content_wpa', array( $this, 'load_dependencies_content' ) );
			add_filter( 'wpv_view_pre_do_blocks_view_layout_meta_html', array( $this, 'load_dependencies_content' ) );
		}

		// general dependencies
		foreach ( $this->dependencies as $dependency ) {
			$dependency->load_dependencies();
		}
	}

	/**
	 * Add a content based dependecy
	 *
	 * @param IGeneral $dependency [description]
	 */
	public function add_dependency( IGeneral $dependency ) {
		$this->dependencies[] = $dependency;
	}

	/**
	 * Load content based dependencies
	 *
	 * @filter 'the_content' 8
	 * @param string $content
	 * @return string Untouched content
	 */
	public function load_dependencies_content( $content ) {
		foreach ( $this->dependencies_content as $dependency ) {
			if ( $dependency->is_required_for_content( $content ) ) {
				$dependency->load_dependencies();
			}
		}

		return $content;
	}
}

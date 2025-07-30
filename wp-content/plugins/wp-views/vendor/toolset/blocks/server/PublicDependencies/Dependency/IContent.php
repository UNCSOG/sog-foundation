<?php

namespace ToolsetBlocks\PublicDependencies\Dependency;

/**
 * Interface for content based dependencies
 */
interface IContent extends IGeneral {

	/**
	 * Returns true/false if the current dependency is required for the content
	 *
	 * @param string $content Content of the current post
	 * @return bool
	 */
	public function is_required_for_content( $content );


}

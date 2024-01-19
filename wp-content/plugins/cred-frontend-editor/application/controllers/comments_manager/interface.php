<?php

namespace OTGS\Toolset\CRED\Controller\CommentsManager;

/**
 * Comments manager interface.
 *
 * @since 2.4
 */
interface ICommentsManager {

	/**
	 * Check whether a given form should hide comments.
	 *
	 * @param int $form_id
	 * @return bool
	 * @since 2.4
	 */
	public function maybe_disable_comments( $form_id );

}

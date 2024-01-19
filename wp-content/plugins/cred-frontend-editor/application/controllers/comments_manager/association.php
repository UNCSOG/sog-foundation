<?php

namespace OTGS\Toolset\CRED\Controller\CommentsManager;

/**
 * Comments manager for relationship forms.
 *
 * @since 2.4
 */
class Association implements ICommentsManager {

	/**
	 * Store whether individual forms, indexed by ID, should hide comments or not.
	 *
	 * @var bool[]
	 */
	private $cache = array();

	/**
	 * Check whether a given form should hide comments.
	 *
	 * @param int $form_id
	 * @return bool
	 * @since 2.4
	 */
	public function maybe_disable_comments( $form_id ) {
		$cached_result = toolset_getarr( $this->cache, $form_id, null );

		if ( null !== $cached_result ) {
			return $cached_result;
		}

		$disable_comments = get_post_meta( $form_id, 'disable_comments', true );
		$this->cache[ $form_id ] = ( 'true' === $disable_comments );

		return $this->cache[ $form_id ];
	}
}

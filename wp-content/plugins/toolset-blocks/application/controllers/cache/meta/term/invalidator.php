<?php

namespace OTGS\Toolset\Views\Controller\Cache\Meta\Term;

use OTGS\Toolset\Views\Controller\Cache\Meta\InvalidatorBase;

/**
 * Termmeta cache controller.
 *
 * @since 2.8.1
 */
class Invalidator extends InvalidatorBase {

	const FORCE_DELETE_ACTION = 'wpv_action_wpv_delete_transient_termmeta_keys';
	const TYPES_GROUP_UPDATED_ACTION = 'types_fields_group_term_saved';

	/**
	 * Add the right invalidation hooks for the cache on postmeta fields.
	 *
	 * @since 2.8.1
	 */
	protected function add_update_hooks() {
		add_action( 'added_term_meta', array( $this, 'maybe_update_transient' ), 10, 4 );
		add_action( 'updated_term_meta', array( $this, 'maybe_update_transient' ), 10, 4 );
	}

}

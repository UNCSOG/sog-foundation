<?php

namespace OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages;

class RelationshipFormList extends BasePage {

	const ID = 'cred_relationship_forms';
	const SLUG = 'cred_relationship_forms';

	/**
	 * Get the callback to show when Forms runs in embedded mode.
	 *
	 * @return callable|null
	 */
	protected function get_embedded_callback() {
		return null;
	}

	/**
	 * Get the callback to show when Forms runs in full mode.
	 *
	 * Note that this is managed on \CRED_Association_Form_Back_End::add_pages().
	 *
	 * @return callable|null
	 */
	protected function get_full_callback() {
		return null;
	}

}

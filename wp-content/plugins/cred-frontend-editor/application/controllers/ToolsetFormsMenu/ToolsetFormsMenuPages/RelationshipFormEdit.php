<?php

namespace OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages;

class RelationshipFormEdit extends BasePage {

	const ID = 'cred_relationship_form';
	const SLUG = 'cred_relationship_form';

	/** @var bool */
	private $conditions_met = null;

	/**
	 * Whether the current page meets some conditions to define the callback.
	 *
	 * @return bool
	 */
	protected function meet_conditions() {
		if ( null !== $this->conditions_met ) {
			return $this->conditions_met;
		}

		global $pagenow;

		$this->conditions_met = (
			static::SLUG === toolset_getget( 'page' )
		);

		return $this->conditions_met;
	}

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

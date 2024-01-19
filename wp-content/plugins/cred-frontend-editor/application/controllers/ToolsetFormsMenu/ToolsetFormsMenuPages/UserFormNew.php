<?php

namespace OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages;

class UserFormNew extends BasePage {

	const ID = 'CRED_User_Form_New';
	const SLUG = 'CRED_User_Form_New';

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
			'post-new.php' === $pagenow
			&& $this->toolset_constants->constant( 'CRED_USER_FORMS_CUSTOM_POST_NAME' ) === toolset_getget( 'post_type' )
		);

		return $this->conditions_met;
	}

	/**
	 * Get page slug.
	 *
	 * @return string
	 */
	public function get_slug() {
		if ( null === $this->conditions_met ) {
			$this->conditions_met = $this->meet_conditions();
		}

		return $this->conditions_met
			? 'post-new.php?post_type=' . esc_html( $this->toolset_constants->constant( 'CRED_USER_FORMS_CUSTOM_POST_NAME' ) )
			: '';
	}

	/**
	 * Get the callback to show when Forms runs in embedded mode.
	 *
	 * @return callable|null
	 */
	protected function get_embedded_callback() {
		if ( null === $this->conditions_met ) {
			$this->conditions_met = $this->meet_conditions();
		}

		return $this->conditions_met
			? '__return_empty_string'
			: null;
	}

	/**
	 * Get the callback to show when Forms runs in full mode.
	 *
	 * @return callable|null
	 */
	protected function get_full_callback() {
		if ( null === $this->conditions_met ) {
			$this->conditions_met = $this->meet_conditions();
		}

		return $this->conditions_met
			? '__return_empty_string'
			: null;
	}

}

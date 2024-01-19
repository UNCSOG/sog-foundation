<?php

namespace OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages;

class PostFormEdit extends BasePage {

	const ID = 'CRED_Form_Edit';
	const SLUG = 'CRED_Form_Edit';

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
			'post.php' === $pagenow
			&& $this->toolset_constants->constant( 'CRED_FORMS_CUSTOM_POST_NAME' ) === get_post_type( toolset_getget( 'post' ) )
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
			? 'post.php?post=' . esc_html( toolset_getget( 'post' ) ) . '&action=edit'
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

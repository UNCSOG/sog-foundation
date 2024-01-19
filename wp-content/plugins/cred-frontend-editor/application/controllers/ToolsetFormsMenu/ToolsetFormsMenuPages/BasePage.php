<?php

namespace OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages;

abstract class BasePage {

	const MODE_FULL = 'full';
	const MODE_EMBEDDED = 'embedded';
	const MODE_EMPTY = 'empty';

	const ID = '';
	const SLUG = '';

	/** @var string */
	protected $mode;

	/** @var \Toolset_Constants */
	protected $toolset_constants;

	/**
	 * Constructor.
	 *
	 * @param string $mode
	 * @param \Toolset_Constants $toolset_constants
	 */
	public function __construct(
		$mode,
		\Toolset_Constants $toolset_constants
	) {
		$this->mode = $mode;
		$this->toolset_constants = $toolset_constants;
	}

	/**
	 * Get page slug.
	 *
	 * @return string
	 */
	public function get_slug() {
		return static::SLUG;
	}

	/**
	 * Get page callback, based on the current mode and eventually on specific conditions.
	 *
	 * @return callable|null
	 */
	public function get_callback() {
		switch ( $this->mode ) {
			case self::MODE_EMPTY:
				return $this->get_empty_calback();
			case self::MODE_EMBEDDED:
				return $this->get_embedded_callback();
			case self::MODE_FULL:
			default:
				return $this->get_full_callback();
		}
	}

	/**
	 * Whether the current page meets some conditions to define the callback.
	 *
	 * @return bool
	 */
	protected function meet_conditions() {
		return true;
	}

	/**
	 * Get the callback to show when Forms is not registering all its codebase.
	 *
	 * See \CRED_Main::should_initialize_plugin().
	 *
	 * @return callable|null
	 */
	protected function get_empty_calback() {
		if ( $this->meet_conditions() ) {
			return '__return_empty_string';
		}

		return null;
	}

	/**
	 * Get the callback to show when Forms runs in embedded mode.
	 *
	 * @return callable|null
	 */
	abstract protected function get_embedded_callback();

	/**
	 * Get the callback to show when Forms runs in full mode.
	 *
	 * @return callable|null
	 */
	abstract protected function get_full_callback();

	/**
	 * Get the minimum capability to access the current page.
	 *
	 * @return string
	 */
	public function get_capability() {
		return $this->toolset_constants->constant( 'CRED_CAPABILITY' );
	}

	/**
	 * Set a posts per page screen options, on listing pages.
	 */
	protected function set_posts_per_page_option() {
		add_screen_option( 'per_page', array(
			'label' => __( 'Per Page', 'wp-cred' ),
			'default' => 10,
			'option' => 'cred_per_page'
		) );
	}

	/**
	 * Maybe execute some extra logic for some specific pages, if conditions are met.
	 */
	public function execute_maybe_current_page() {}

}

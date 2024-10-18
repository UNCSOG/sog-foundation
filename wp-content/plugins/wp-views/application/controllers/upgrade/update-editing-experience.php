<?php

namespace OTGS\Toolset\Views\Controller\Upgrade;

/**
 * Update the editing experience when switching between Toolset Views and Toolset Blocks
 *
 * @since 3.0
 */
class UpdateEditingExperience implements IRoutine {

	/**
	 * Name of the option used to store flavour version.
	 *
	 * @since 2.6.4
	 */
	const FLAVOUR_VERSION_OPTION = 'wpv_flavour_version';

	/**
	 * @var \WPV_Settings
	 */
	private $settings;

	/**
	 * @var string
	 */
	private $toolset_views_flavour;

	/**
	 * @var string
	 */
	private $stored_views_flavour;

	/**
	 * @var string
	 */
	private $current_editing_experience;

	/**
	 * Constructor.
	 *
	 * @param \WPV_Settings $settings
	 * @param \OTGS\Toolset\Views\Model\Wordpress\Wpdb $wpdb_wrapper
	 */
	public function __construct( \WPV_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Execute database upgrade up to 3.0
	 *
	 * @param array $args
	 * @since 3.0
	 */
	public function execute_routine( $args = array() ) {// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$this->current_editing_experience = $this->settings->get_raw_value( 'editing_experience' );
		if ( 'mixed' === $this->current_editing_experience ) {
			// Do nothing if already using a mixed editing experience.
			return;
		}

		$this->toolset_views_flavour = wpv_get_views_flavour();
		$this->stored_views_flavour = get_option( self::FLAVOUR_VERSION_OPTION );
		if (
			false !== $this->stored_views_flavour
			&& $this->toolset_views_flavour === $this->stored_views_flavour
		) {
			// Do nothing if using the same plugin flavour as last request.
			return;
		}

		// If changing the stored flavour...
		switch ( $this->toolset_views_flavour ) {
			case 'classic':
				// ... for the Views plugin, use the classic editing experience.
				$this->settings->set( 'editing_experience', 'classic' );
				break;
			case 'blocks':
				// ... for the Blocks plugin, use the blocks editing experience.
				$this->settings->set( 'editing_experience', 'blocks' );
				break;
		}
		update_option( self::FLAVOUR_VERSION_OPTION, $this->toolset_views_flavour );
	}

}

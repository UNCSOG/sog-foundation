<?php
/**
 * Upgrade routines controller.
 *
 * @package Toolset Views
 * @since 2.6.4
 */

namespace OTGS\Toolset\Views\Controller;

/**
 * Plugin upgrade controller.
 *
 * Compares current plugin version with a version number stored in the database, and performs upgrade routines if
 * necessary.
 *
 * @since 2.6.4
 * @since 3.0 Stop using the constant WPV_VERSION as source of the current plugin version, and use a hardcoded value instead.
 *     This provides the ability to run upgrade routines between beta versions and between beta and stable releases.
 * @note: Filters to add upgrade routines are not provided on purpose, so all routines need to be defined here
 */
class Upgrade {

	/**
	 * @var \OTGS\Toolset\Views\Controller\Upgrade\Factory
	 */
	private $routines_factory = null;

	/**
	 * @var int
	 */
	private $database_version = null;

	/**
	 * @var int The database version, usually made of Mmmhhxx, being:
	 *     - M the major version number.
	 *     - mm the minor version number.
	 *     - hh the hotfix version number.
	 *     - xx 99 degrees of liberty.
	 *
	 * Increase every time you need to include a new routine.
	 */
	private $plugin_version = 3050000;

	public function __construct(
		\OTGS\Toolset\Views\Controller\Upgrade\Factory $routines_factory
	) {
		$this->routines_factory = $routines_factory;
	}

	public function initialize() {
		$this->set_hooks();
		$this->check_upgrade();
	}

	/**
	 * Name of the option used to store version number.
	 *
	 * @since 2.6.4
	 */
	const DATABASE_VERSION_OPTION = 'wpv_database_version';

	/**
	 * Set some API hooks.
	 *
	 * @since 2.8.3
	 */
	public function set_hooks() {
		add_filter( 'wpv_filter_wpv_upgrade_get_database_version', array( $this, 'get_database_version' ) );
		add_action( 'wpv_action_wpv_check_import_upgrade', array( $this, 'check_import_upgrade' ) );
	}

	/**
	 * Helper public method for unit tests.
	 *
	 * @param int $version
	 * @since 3.0
	 */
	public function set_plugin_version( $version ) {
		$this->plugin_version = $version;
	}

	/**
	 * Check if a setup and an upgrade are needed, and if yes, perform them.
	 *
	 * @since 2.6.4
	 */
	public function check_upgrade() {
		if ( $this->is_setup_needed() ) {
			$this->do_setup();
		}
		if ( $this->is_upgrade_needed() ) {
			$this->do_upgrade();
		}
		$this->maybe_update_editing_experience();
	}

	/**
	 * Returns true if a setup is needed.
	 *
	 * @return bool
	 * @since 2.6.4
	 */
	private function is_setup_needed() {
		return ( $this->get_database_version() === 0 );
	}

	/**
	 * Returns true if an upgrade is needed.
	 *
	 * @return bool
	 * @since 2.6.4
	 */
	private function is_upgrade_needed() {
		return ( $this->get_database_version() < $this->get_plugin_version() );
	}

	/**
	 * Check if an upgrade is needed after importing data, and if yes, perform it.
	 *
	 * @param int|null $from_version The version to upgrade from
	 *
	 * @since 2.6.4
	 */
	public function check_import_upgrade( $from_version = 0 ) {
		if ( $this->is_import_upgrade_needed( $from_version ) ) {
			$this->do_upgrade( $from_version );
		}
	}

	/**
	 * Returns true if an upgrade after importing data is needed.
	 *
	 * @param int|null $from_version The version to upgrade from
	 *
	 * @return bool
	 * @since 2.6.4
	 */
	private function is_import_upgrade_needed( $from_version) {
		return ( $from_version < $this->get_plugin_version() );
	}

	/**
	 * Get current plugin version number.
	 *
	 * @return int
	 * @since 2.6.4
	 */
	private function get_plugin_version() {
		return $this->plugin_version;
	}

	/**
	 * Get number of the version stored in the database.
	 *
	 * @return int
	 * @since 2.6.4
	 */
	public function get_database_version() {
		if ( null === $this->database_version ) {
			$this->database_version = (int) get_option( self::DATABASE_VERSION_OPTION, 0 );
		}

		return $this->database_version;
	}

	/**
	 * Update the version number stored in the database.
	 *
	 * @param int $version_number
	 * @since 2.6.4
	 */
	private function update_database_version( $version_number ) {
		if ( is_numeric( $version_number ) ) {
			update_option( self::DATABASE_VERSION_OPTION, (int) $version_number );
		}
	}

	/**
	 * Get an array of upgrade routines.
	 *
	 * Each routine is defined as an associative array with two elements:
	 *     - 'version': int, which specifies the *target* version after the upgrade
	 *     - 'callback': callable
	 *
	 * @return array
	 * @since 2.6.4
	 */
	private function get_upgrade_routines() {
		$upgrade_routines = array(
			array(
				'version' => 2080300,
				'callback' => array( $this, 'upgrade_db_to_2080300' ),
			),
			array(
				'version' => 3000001,
				'callback' => array( $this, 'upgrade_db_to_3000001' ),
			),
			array(
				'version' => 3010001,
				'callback' => array( $this, 'upgrade_db_to_3010001' ),
			),
		);

		return $upgrade_routines;
	}

	/**
	 * Perform the upgrade by calling the appropriate upgrade routines and updating the version number in the database.
	 *
	 * @param int|null $from_version The version to upgrade from, null to use the current database version
	 *
	 * @since 2.6.4
	 */
	private function do_upgrade( $from_version = null ) {
		$from_version = is_null( $from_version )
			? $this->get_database_version()
			: $from_version;
		$upgrade_routines = $this->get_upgrade_routines();
		$target_version = $this->get_plugin_version();

		// Sort upgrade routines by their version.
		$routines_by_version = array();
		foreach( $upgrade_routines as $key => $row ) {
			$routines_by_version[ $key ] = $row['version'];
		}
		array_multisort( $routines_by_version, SORT_DESC, $upgrade_routines );

		// Run all the routines necessary
		foreach( $upgrade_routines as $routine ) {
			$upgrade_version = (int) toolset_getarr( $routine, 'version' );

			if ( $from_version < $upgrade_version && $upgrade_version <= $target_version ) {
				$callback = toolset_getarr( $routine, 'callback' );
				if ( is_callable( $callback ) ) {
					call_user_func( $callback );
				}
				$this->update_database_version( $upgrade_version );
			}
		}

		$this->do_mandatory_upgrade();

		// Finally, update to current plugin version even if there are no other routines to run, so that
		// this method is not called every time by check_upgrade().
		$this->update_database_version( $target_version );
	}

	/**
	 * Set database for new sites.
	 *
	 * @since 2.6.4
	 * @since 3.0 Move to a proper routine
	 */
	public function do_setup() {
		$setup_routine = $this->routines_factory->get_routine( 'setup' );
		$setup_routine->execute_routine();
	}

	/**
	 * Upgrade database to 2080300 (Views 2.8.3)
	 *
	 * Fix the postmeta cache generation regarding Types meta meys.
	 * Invalidate the postmeta cache so it gets recreated.
	 *
	 * @since 2.8.3
	 */
	public function upgrade_db_to_2080300() {
		$upgrade_routine = $this->routines_factory->get_routine( 'upgrade_db_to_2080300' );
		$upgrade_routine->execute_routine();
	}

	/**
	 * Upgrade database to 3000001 (Views 3.0)
	 *
	 * Set some defaults for user editors on Content Templates.
	 *
	 * @since 3.0
	 */
	public function upgrade_db_to_3000001() {
		// Set the new capability to users with editing rights.
		$capabilities_routine = $this->routines_factory->get_routine( 'capabilities' );
		$capabilities_routine->execute_routine();
		// Set default editors for existing sites.
		$default_editors = $this->routines_factory->get_routine( 'default_editors' );
		$default_editors->execute_routine();
		// Set redirection to the welcome page.
		$maybe_redirect = $this->routines_factory->get_routine( 'maybe_redirect' );
		$maybe_redirect->execute_routine();
	}

	/**
	 * Upgrade database to 3010001 (Views 3.1).
	 *
	 * Cleanup the legacy Views output cache.
	 *
	 * @since 3.1
	 */
	public function upgrade_db_to_3010001() {
		$legacy_cache_cleanup_routine = $this->routines_factory->get_routine( 'clean_legacy_cache_indexes' );
		$legacy_cache_cleanup_routine->execute_routine();
	}

	/**
	 * Maybe switch the default editing experience when switching between plugins.
	 *
	 * @since 3.0
	 */
	private function maybe_update_editing_experience() {
		$update_editing_experience = $this->routines_factory->get_routine( 'update_editing_experience' );
		$update_editing_experience->execute_routine();
	}

	/**
	 * Run routines required and mandatory every time the plugin gets updated.
	 *
	 * @since 3.2
	 */
	private function do_mandatory_upgrade() {
		// Cleanup Views frontend output cache.
		do_action( 'wpv_invalidate_all_views_cache' );
	}
}

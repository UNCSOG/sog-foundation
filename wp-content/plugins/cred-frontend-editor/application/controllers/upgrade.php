<?php

namespace OTGS\Toolset\CRED\Controller;

/**
 * Plugin upgrade controller.
 *
 * Compares current plugin version with a version number stored in the database, and performs upgrade routines if
 * necessary.
 *
 * Note: Filters to add upgrade routines are not provided on purpose, so all routines need to be defined here.
 *
 * It works with version numbers, which are easier to compare and manipulate with. See convert_version_string_to_number()
 * for details.
 *
 * @since 2.1.2
 */
class Upgrade {

	/**
	 * @var \Toolset_Constants
	 */
	private $constants_manager = null;

	/**
	 * @var \OTGS\Toolset\CRED\Controller\Upgrade\Factory
	 */
	private $routines_factory = null;

	public function __construct(
		\Toolset_Constants $constants_manager,
		\OTGS\Toolset\CRED\Controller\Upgrade\Factory $routines_factory
	) {
		$this->constants_manager = $constants_manager;
		$this->routines_factory = $routines_factory;
	}

	/**
	 * Name of the option used to store version number.
	 *
	 * @since 2.1.2
	 */
	const DATABASE_VERSION_OPTION = 'toolset_forms_db_version';

	/**
	 * Initialize the workflow of this class.
	 *
	 * @since 2.1.2
	 */
	public function initialize() {
		$this->check_upgrade();
	}

	/**
	 * Check if a setup and an upgrade are needed, and if yes, perform them.
	 *
	 * @since 2.1.2
	 */
	public function check_upgrade() {
		if ( $this->is_setup_needed() ) {
			$this->do_setup();
		}
		if ( $this->is_upgrade_needed() ) {
			$this->do_upgrade();
		}
	}

	/**
	 * Returns true if a setup is needed.
	 *
	 * @return bool
	 * @since 2.1.2
	 */
	private function is_setup_needed() {
		return ( $this->get_database_version() === 0 );
	}

	/**
	 * Returns true if an upgrade is needed.
	 *
	 * @return bool
	 * @since 2.1.2
	 */
	private function is_upgrade_needed() {
		return ( $this->get_database_version() < $this->get_plugin_version() );
	}

	/**
	 * Check if an upgrade is needed after importing data, and if yes, perform it.
	 *
	 * @param int|null $from_version The version to upgrade from
	 *
	 * @since 2.1.2
	 */
	public function check_import_upgrade( $from_version ) {
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
	 * @since 2.1.2
	 */
	private function is_import_upgrade_needed( $from_version) {
		return ( $from_version < $this->get_plugin_version() );
	}

	/**
	 * Get current plugin version number.
	 *
	 * @return int
	 * @since 2.1.2
	 */
	private function get_plugin_version() {
		$plugin_version = $this->constants_manager->constant( 'CRED_FE_VERSION' );
		return $this->convert_version_string_to_number( $plugin_version );
	}

	/**
	 * Get number of the version stored in the database.
	 *
	 * @return int
	 * @since 2.1.2
	 */
	public function get_database_version() {
		$version = (int) get_option( self::DATABASE_VERSION_OPTION, 0 );
		return $version;
	}

	/**
	 * Transform a version string to a version number.
	 *
	 * The version string looks like this: "major.minor[.maintenance[.revision]]". We expect that all parts have
	 * two digits at most.
	 *
	 * Conversion to version number is done like this:
	 * $ver_num  = MAJOR      * 1000000
	 *           + MINOR        * 10000
	 *           + MAINTENANCE    * 100
	 *           + REVISION         * 1
	 *
	 * That means, for example "1.8.11.12" will be equal to:
	 *                          1000000
	 *                        +   80000
	 *                        +    1100
	 *                        +      12
	 *                        ---------
	 *                        = 1081112
	 *
	 * @param string $version_string
	 * @return int
	 * @since 2.1.2
	 */
	private function convert_version_string_to_number( $version_string ) {
		if ( 0 === $version_string ) {
			return 0;
		}

		$version_parts = explode( '.', $version_string );
		$multipliers = array( 1000000, 10000, 100, 1 );

		$version_part_count = count( $version_parts );
		$version = 0;
		for( $i = 0; $i < $version_part_count; ++$i ) {
			$version_part = (int) $version_parts[ $i ];
			$multiplier = $multipliers[ $i ];

			$version += $version_part * $multiplier;
		}

		return $version;
	}

	/**
	 * Update the version number stored in the database.
	 *
	 * @param int $version_number
	 * @since 2.1.2
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
	 * @since 2.1.2
	 */
	private function get_upgrade_routines() {
		$upgrade_routines = array(
			array(
				'version' => 2010200,
				'callback' => array( $this, 'upgrade_db_to_2010200' ),
			),
			array(
				'version' => 2030500,
				'callback' => array( $this, 'upgrade_db_to_2030500' )
			),
			array(
				'version' => 2040000,
				'callback' => array( $this, 'upgrade_db_to_2040000' ),
			),
		);

		return $upgrade_routines;
	}

	/**
	 * Perform the upgrade by calling the appropriate upgrade routines and updating the version number in the database.
	 *
	 * @param int|null $from_version The version to upgrade from, null to use the current database version
	 *
	 * @since 2.1.2
	 */
	private function do_upgrade( $from_version = null ) {
		$from_version = is_null( $from_version )
			? $this->get_database_version()
			: $from_version;
		$upgrade_routines = $this->get_upgrade_routines();
		$target_version = $this->get_plugin_version();

		// Run all the routines necessary
		foreach( $upgrade_routines as $routine ) {
			$upgrade_version = (int) toolset_getarr( $routine, 'version' );

			if ( $from_version < $upgrade_version && $upgrade_version <= $target_version ) {
				$callback = toolset_getarr( $routine, 'callback' );
				if ( is_callable( $callback ) ) {
					call_user_func( $callback );
				}
			}
		}

		// Finally, update to current plugin version even if there are no other routines to run, so that
		// this method is not called every time by check_upgrade().
		$this->update_database_version( $target_version );
	}

	/**
	 * Set database for new sites.
	 *
	 * @since 2.1.2
	 */
	public function do_setup() {

	}

	/**
	 * Upgrade database to 2010200 (Forms 2.1.2)
	 *
	 * Batch set default values for post and user forms settings about:
	 * - hide comments
	 * - include Add Media buttons on frontend editors
	 * - include Toolset buttons on frontend editors
	 */
	public function upgrade_db_to_2010200() {
		$upgrade_routine = $this->routines_factory->get_routine( 'upgrade_db_to_2010200' );
		$upgrade_routine->execute_routine();
	}

	/**
	 * Upgrade database to 2030500 (Forms 2.3.5)
	 *
	 * Batch set default values for existing post, user and relationship forms settings about:
	 * - editor_origin
	 */
	public function upgrade_db_to_2030500() {
		$upgrade_routine = $this->routines_factory->get_routine( 'upgrade_db_to_2030500' );
		$upgrade_routine->execute_routine();
	}

	/**
	 * Upgrade database to 2040000 (Forms 2.4)
	 *
	 * Batch set default values for post and user forms settings about:
	 * - setting for native media manager on media fields
	 */
	public function upgrade_db_to_2040000() {
		$upgrade_routine = $this->routines_factory->get_routine( 'upgrade_db_to_2040000' );
		$upgrade_routine->execute_routine();
	}

}

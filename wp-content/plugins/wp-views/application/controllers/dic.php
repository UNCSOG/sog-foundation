<?php

namespace OTGS\Toolset\Views\Controller;

/**
 * Class Dic
 *
 * Handles the instantiation of the dependency injector for Views.
 *
 * @package OTGS\Toolset\Views\Controller
 *
 * @since 3.0.0
 */
class Dic {
	public function initialize() {
		/** @var \OTGS\Toolset\Common\Auryn\Injector $dic */
		$dic = apply_filters( 'toolset_dic', null );

		$singleton_delegates = array(
			'\WPV_Settings' => function() {
				// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
				global $WPV_settings;
				return $WPV_settings;
				// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			},
			'\WPV_WordPress_Archive_Frontend' => function() {
				// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
				global $WPV_view_archive_loop;
				return $WPV_view_archive_loop;
				// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			},
			'\WPV_Editor_Loop_Selection' => function() {
				return \WPV_Editor_Loop_Selection::get_instance();
			},
			'WPV_Filter_Manager' => function() {
				return \WPV_Filter_Manager::get_instance();
			},

			'\Kadence\Theme' => function() {
				return is_callable( array( '\Kadence\Theme', 'instance' ) ) ? \Kadence\Theme::instance() : null;
			},
			'\Kadence\Theme_Meta' => function() {
				return is_callable( array( '\Kadence\Theme_Meta', 'get_instance' ) ) ? \Kadence\Theme_Meta::get_instance() : null;
			},
		);

		foreach ( $singleton_delegates as $class_name => $callback ) {
			/** @noinspection PhpUnhandledExceptionInspection */
			$dic->delegate(
				$class_name,
				function() use ( $callback, $dic ) {
					$instance = $callback();
					$dic->share( $instance );
					return $instance;
				}
			);
		}

	}
}

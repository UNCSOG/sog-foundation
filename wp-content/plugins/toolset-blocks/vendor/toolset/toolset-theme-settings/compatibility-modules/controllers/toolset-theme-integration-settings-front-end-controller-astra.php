<?php

class Toolset_Theme_Integration_Settings_Front_End_Controller_astra extends Toolset_Theme_Integration_Settings_Front_End_Controller {

	/**
	 * @since 2.5
	 * filters the global options present in the config file.
	 */
	public function add_filter_global_option_objects() {
		$global_models = $this->get_global_collection_items();

		if ( null === $global_models ) {
			return;
		}

		foreach ( $global_models as $model ) {
			if( !is_null( $model->get_current_value() ) && $model->get_current_value() !== $model->get_default_value() ){
				$this->global_options[$model->name] = $model->get_current_value();
				add_filter( "astra_get_option_{$model->name}", array( $this, "astra_global_option_save" ), 99, 3 );
			}
		}
	}


	/**
	 * @since 2.5
	 *
	 * @param $theme_setting
	 * @param $option_key
	 *
	 */
	public function astra_global_option_save( $value, $key, $default ) {

		if( !$this->helper->has_theme_settings() ){
			return $value;
		}

		$allowed_targets = $this->allowed_targets;
		$global = $this->get_collection_by_type( $allowed_targets['global'] );

		$model = $global->where( 'name', $key );

		if( isset( $model[0] ) && $model[0] instanceof Toolset_Theme_Integration_Settings_Model_global ){

			$model = $model[0];

			if ( ! is_null( $model->get_current_value() ) &&
			     $model->get_current_value() !== $model->get_default_value() &&
			     $model->get_current_value() !== $value
			) {
				$value = $model->get_current_value();
				if ( 'boolean' === $model->get_expected_value_type() ){
					$value = (bool) $value;
				}
			}
		}

		return $value;
	}
}
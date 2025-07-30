<?php

class Toolset_Theme_Integration_Settings_Front_End_Controller_genesis extends Toolset_Theme_Integration_Settings_Front_End_Controller {

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
				add_filter( "genesis_pre_get_option_{$model->name}", array( $this, "genesis_global_option_save" ), 100, 2 );
			}
		}
	}


	/**
	 * @since 2.5
	 *
	 * @param $options
	 * @param $global_key
	 *
	 */
	public function genesis_global_option_save( $value, $settings ) {

		if( !$this->helper->has_theme_settings() ){
			return null;
		}

		$allowed_targets = $this->allowed_targets;
		$global = $this->get_collection_by_type( $allowed_targets['global'] );

		$filter = current_filter();
		$key = explode( 'genesis_pre_get_option_',  $filter );
		$name = $key[1];
		$model = $global->where( 'name', $name );

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
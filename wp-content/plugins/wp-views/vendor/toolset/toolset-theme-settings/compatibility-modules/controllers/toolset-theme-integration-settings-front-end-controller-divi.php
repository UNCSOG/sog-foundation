<?php

class Toolset_Theme_Integration_Settings_Front_End_Controller_divi extends Toolset_Theme_Integration_Settings_Front_End_Controller {

	/**
	 * @since 2.5
	 * filters the global options present in the config file.
	 */
	public function add_filter_global_option_objects() {
		$global_models = $this->get_global_collection_items();

		if ( null === $global_models ) {
			return;
		}

		$et_theme_options_name = 'et_' . $this->theme_slug . '_';

		foreach ( $global_models as $model ) {
			if( !is_null( $model->get_current_value() ) && $model->get_current_value() !== $model->get_default_value() ){
				$this->global_options[$model->name] = $model->get_current_value();
				// add the filter in the form "et_divi" + global option name
				$et_one_row_option_name = $model->global_key . '_' . $model->name;
				add_filter( "et_get_option_{$et_one_row_option_name}", array( $this, "divi_global_option_save" ), 100, 2 );
				// add the filter in the form "et_+CHILD_THEME_SLUG" + option name
				$et_one_row_option_name = $et_theme_options_name . '_' . $model->name;
				add_filter( "et_get_option_{$et_one_row_option_name}", array( $this, "divi_global_option_save" ), 100, 2 );
				// add the filter in the form option name only
				add_filter( "et_get_option_{$model->name}", array( $this, "divi_global_option_save" ), 100, 2 );
				// since we can't predict the form the tag will have to apply the filter, we are adding all the 3 possible forms it can take
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
	public function divi_global_option_save( $value, $key ) {

		if( !$this->helper->has_theme_settings() ){
			return null;
		}

		$allowed_targets = $this->allowed_targets;
		$global = $this->get_collection_by_type( $allowed_targets['global'] );
		
		$key = substr( $key, strlen( 'et_' . $this->theme_slug . '_' ) );

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
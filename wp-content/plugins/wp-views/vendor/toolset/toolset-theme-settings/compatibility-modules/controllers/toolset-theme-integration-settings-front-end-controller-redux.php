<?php

class Toolset_Theme_Integration_Settings_Front_End_Controller_redux extends Toolset_Theme_Integration_Settings_Front_End_Controller {
	/**
	 * @since 2.5
	 * filters the global options present in the config file and overrides Redux $GLOBALS
	 */
	public function add_filter_global_option_objects() {
		$global_models = $this->get_global_collection_items();

		if ( null === $global_models ) {
			return;
		}

		foreach ( $global_models as $model ) {
			if( !is_null( $model->get_current_value() ) && $model->get_current_value() !== $model->get_default_value() ){
				$GLOBALS[$model->get_redux_global_key()][$model->get_name()] = $model->get_current_value();
				$this->global_options[$model->get_name()] = $model->get_current_value();
			}
		}
	}

}
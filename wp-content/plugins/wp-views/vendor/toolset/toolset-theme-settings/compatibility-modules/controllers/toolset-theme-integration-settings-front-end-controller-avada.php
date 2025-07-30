<?php

class Toolset_Theme_Integration_Settings_Front_End_Controller_avada extends Toolset_Theme_Integration_Settings_Front_End_Controller {
	/**
	 * @param $theme_settings
	 * @param null $option_key
	 *
	 * @return mixed
	 */
	public function pre_global_option_save( $theme_settings, $option_key = null ) {
		$settings_data = $this->get_keys_from_filter( current_filter() );

		if( isset( $settings_data['subset'] ) && isset( $settings_data['setting'] ) ){
			$option_key = $settings_data['subset'];
			$array_key = $settings_data['setting'];
		} elseif( !isset( $settings_data['subset'] ) && isset( $settings_data['setting'] ) ){
			$option_key = $settings_data['setting'];
		} else {
			return $theme_settings;
		}

		$this->collect_postsponed_callbacks( $option_key );

		$global = $this->get_collection_by_type( $this->allowed_targets['global'] );

		if( !$global instanceof Toolset_Theme_Integration_Settings_Model_Collection ){
			return $theme_settings;
		}

		$option = $global->where( 'name', $option_key );

		if ( isset($option[0]) && $option[0] instanceof Toolset_Theme_Integration_Settings_Model_global ) {
			$option = $option[0];
				if ( ! is_null( $option->get_current_value() ) && $option->get_current_value() !== $option->get_default_value() ) {
					$theme_settings = $option->get_current_value();
				}

		} elseif (  $option_key && array_key_exists( $option_key, $this->global_options ) ) {
			$theme_settings = $this->global_options[ $option_key ];
		}

		return $theme_settings;
	}

	/**
	 * @param $theme_setting
	 * @param $model
	 * @param $depth
	 *
	 * @return mixed
	 * @deprecated
	 */
	private function handle_theme_settings_as_multidimensional_array( $theme_setting, $model, $depth ){

		if( ! $depth || $depth > 4 ) return $theme_setting;

		switch( $depth ){
			case 1:
				$theme_setting[$model->array_keys[0]][$model->name] = $model->get_current_value();
				break;
			case 2:
				$theme_setting[$model->array_keys[0]][$model->array_keys[1]][$model->name] = $model->get_current_value();
				break;
			case 3:
				$theme_setting[$model->array_keys[0]][$model->array_keys[1]][$model->array_keys[2]][$model->name] = $model->get_current_value();
				break;
			case 4:
				$theme_setting[$model->array_keys[0]][$model->array_keys[1]][$model->array_keys[2]][$model->array_keys[3]][$model->name] = $model->get_current_value();
				break;
		}

		return $theme_setting;
	}

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
				if ( property_exists( $model, 'array_keys' ) && is_array( $model->array_keys ) && count( $model->array_keys ) === 1 ) {
					add_filter( 'avada_setting_get_' . $model->array_keys[0] . '['. $model->name .']', array($this, 'pre_global_option_save') );
				} else {
					add_filter( 'avada_setting_get_' . $model->name, array($this, 'pre_global_option_save') );
				}

			}
		}
	}

	protected function get_keys_from_filter( $filter ){
		$prefix = 'avada_setting_get_';
		$current = explode( $prefix, $filter );
		$ret = array();

		if( isset( $current[1] ) && strpos( $current[1], '[') !== false  ){
			preg_match_all("/\[([^\]]*)\]/", $current[1], $matches);
			// handle apply_filters( "avada_setting_get_{$setting}[{$subset}]", $value[ $subset ] ); as Avada does
			if( isset($matches[1]) && count($matches[1]) === 1 ){
				$ret['subset'] = $matches[1][0];
				$tmp = explode( '[', $current[1] );
				$ret['settings'] = $tmp[0];
			}
		} else {
			// handle apply_filters( "avada_setting_get_{$setting}", $value ); as Avada does
			$ret['setting'] = isset( $current[1] ) ? $current[1] : array();
		}

		return $ret;
	}
}
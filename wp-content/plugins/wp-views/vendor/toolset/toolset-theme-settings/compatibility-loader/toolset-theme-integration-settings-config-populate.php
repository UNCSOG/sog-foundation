<?php

class Toolset_Theme_Integration_Settings_Config_Populate {
	
	/**
	 * @var Toolset_Theme_Integration_Settings_Models_Factory
	 */
	private $model_factory;
	/**
	 * @var Toolset_Theme_Integration_Settings_Collections_Factory
	 */
	private $collections_factory;
	/**
	 * @var Toolset_Theme_Integration_Settings_Config_Update
	 */
	private $update_manager;
	
	/**
	 * Toolset_Theme_Integration_Settings_Config_Populate constructor.
	 *
	 * @param Toolset_Theme_Integration_Settings_Models_Factory $model_factory
	 * @param Toolset_Theme_Integration_Settings_Collections_Factory $collections_factory
	 * @param Toolset_Theme_Integration_Settings_Config_Update $update_manager
	 */
	public function __construct( Toolset_Theme_Integration_Settings_Models_Factory $model_factory,  Toolset_Theme_Integration_Settings_Collections_Factory $collections_factory, Toolset_Theme_Integration_Settings_Config_Update $update_manager ){
		$this->model_factory = $model_factory;
		$this->collections_factory = $collections_factory;
		$this->update_manager = $update_manager;
	}
	
	public function run( $selected_settings = null ) {

		$settings = $this->get_toolset_config();
		// little fix if option record is not dropped but truncated
		if ( empty( $settings ) ) {
			return null;
		}

		$at_least_one_valid_model_added = false;

		foreach( $settings as $item ) {
			$types = $item->type;
			$label = $item->group;
			foreach( $types as $type ){
				$collection = $this->collections_factory->get_collection( $type, $label );
				$model = $this->model_factory->build( $type, $item->name );
				$is_valid = $model->populate( $item );

				if ( apply_filters( 'toolset_theme_settings_integration_model_is_valid_'. $type . '_' . $model->name , $is_valid, $model, $item ) ) {
					try {
						if ( $selected_settings &&
						    is_array( $selected_settings )
						) {
							if ( isset( $selected_settings[$model->name] ) &&
								$selected_settings[$model->name] != $model->get_default_value()
							) {
								$model->set_current_value( $selected_settings[$model->name] );
							}
							
							$switch_name = 'toolset_switch_' . $model->name;
							if (
								isset( $selected_settings[ $switch_name ] ) &&
								$selected_settings[ $switch_name ] != $model->get_default_switch_value()
							) {
								$model->set_current_switch_value( $selected_settings[ $switch_name ] );
							}
						}
						$collection->addItem( $model );
						$at_least_one_valid_model_added = true;
					} catch ( Exception $e ) {
						//error_log( $e->getMessage() );
					}

				}
			}
		}

		return $at_least_one_valid_model_added ? $this->collections_factory->get_collections() : null;
	}
	
	private function get_toolset_config() {
		$settings = get_option( TOOLSET_THEME_SETTINGS_CACHE_OPTION, null );
		
		if ( empty( $settings ) ) {
			$settings = $this->update_manager->run();
		}
		
		return $settings;
	}
	
}
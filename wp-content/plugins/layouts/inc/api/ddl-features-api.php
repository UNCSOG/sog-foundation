<?php
/**
 * Class WPDDL_features
 */

class WPDDL_features
{
	private $_support_features = array();
	private $_all_features = array();

	function __construct()
	{
		

        add_filter( 'ddl_default_support_features', array($this, 'add_supported_featured_cells'), 8, 1 );

		$this->_support_features = apply_filters('ddl_default_support_features',array(
                'fixed-layout',
                'fluid-layout',
                'warn-missing-post-loop-cell'
        ));

		$this->_all_features = apply_filters('ddl_default_all_features', $this->_support_features );
		$this->_all_features[] = 'post-content-cell';
		$this->_all_features[] = 'post-loop-cell';

	}


	function remove_ddl_support($feature)
	{
		if (($key = array_search($feature, $this->_support_features)) !== false) {
			unset($this->_support_features[$key]);
			return true;
		} else {
			return false;
		}
	}

	function add_ddl_support($feature)
	{
		if (($key = array_search($feature, $this->_all_features)) !== false) {
			if (($key = array_search($feature, $this->_support_features)) === false) {
				$this->_support_features[] = $feature;
				return true;
			}
		}

		return false;
	}

	function is_feature($feature)
	{
		return in_array($feature, $this->_support_features);
	}

	public static function ddl_featured_cells(){
		return apply_filters( 'ddl_default_featured_cells', array(
				'child-layout',
				'cell-widget-area',
                'cell-post-content',
				'widget-cell',
				'post-loop-views-cell',
				'cell-content-template',
				'views-content-grid-cell',
				'video-cell',
				'cell-text',
				'slider-cell',
				'post-loop-cell',
				'menu-cell',
				'imagebox-cell',
				'cred-user-cell',
				'cred-cell',
				'cred-relationship-cell',
				'comments-cell',
				'grid-cell',
				'ddl_missing_cell_type',
				'ddl-container',
				'tabs-cell',
				'accordion-cell'
		) );
	}

	public function add_supported_featured_cells( $features ){
		$cells = self::ddl_featured_cells();

        $features = array_merge($features, $cells);

        $this->_support_features = array_unique( array_merge( $this->_support_features, $features) );

		return  $this->_support_features;
	}
}


global $wpddl_features;
$wpddl_features = new WPDDL_features();

function remove_ddl_support($feature)
{
	global $wpddl_features;
	return $wpddl_features->remove_ddl_support($feature);
}

function add_ddl_support($feature) {
	global $wpddl_features;
	return $wpddl_features->add_ddl_support($feature);
}

function ddl_has_feature( $feature ){
    global $wpddl_features;
    return $wpddl_features->is_feature($feature);
}

add_filter( 'ddl-get_global_features', 'ddl_get_global_features');
function ddl_get_global_features(){
	global $wpddl_features;
	return $wpddl_features;
}

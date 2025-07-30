<?php

/**
 * Postmeta filter select options walker class
 *
 * @package Views
 *
 * @extends WPV_Walker_Control_Base
 *
 * @since 2.4.0
 */
class WPV_Walker_Postmeta_Select extends WPV_Walker_Control_Base {

	public $tree_type = 'postmeta';

	public $db_fields = array(
		'parent' => 'meta_parent',
		'id' => 'meta_key',
	);

	/** @var array */
	private $walker_args;

	/** @var mixed */
	private $current_post_query;


	/**
	 * Walker construct
	 *
	 * @param $walker_args array(
	 *		'field'				string	The field which values are being walked
	 *		'name'				string	Name attribute to use, comes from the URL prameter to listen to
	 *		'selected'			string|array Selected items
	 *		'format'			string	Placeholders: %%NAME%%, %%COUNT%%
	 *		'style'				string	Input custom inline styles
	 *		'class'				string	Input custom classnames
	 *		'label_style'		string	Label custom inline styles
	 *		'label_class'		string	Label custom classnames
	 *		'output'			string	Kind of output, 'bootstrap'|'legacy'
	 *		'type'				string	Type of output, 'select'|'multi-select'
	 *		'toolset_type'		string	Type of the field as a Types field, if any
	 *		'dependency'		string	'disabled'|'enabled'
	 *		'counters'			string	'disabled'|'enabled'
	 *		'use_query_cache'	string	'disabled'|'enabled'
	 *		'empty_action'		string	'hide'|'disable'
	 *		'query_cache'		array	Cache to use for counters and dependency
	 *		'comparator'		string	Method to use when checking the current item against the cached data
	 *		'auxiliar_query_count' bool|int Count of the auxiliar cache run by disabling this filter
	 * )
	 *
	 * @since 2.4.0
	 */
	function __construct( $walker_args ) {
		parent::__construct( $walker_args );
		
		$defaults = array(
			'field'				=> '',
			'name'				=> '',
			'selected'			=> '',
			'format'			=> '%%NAME%%',
			'style'				=> '',
			'class'				=> '',
			'label_style'		=> '',
			'label_class'		=> '',
			'output'			=> 'legacy',
			'type'				=> 'checkboxes',
			'toolset_type'		=> '',
			'dependency'		=> 'disabled',
			'counters'			=> 'disabled',
			'use_query_cache'	=> 'disabled',
			'auxiliar_query_count'	=> false,
			'empty_action'		=> 'hide',
			'comparator'		=> 'equal',
			'query_cache'		=> array()
		);

		$walker_args = wp_parse_args( $walker_args, $defaults );

		$this->walker_args = array_intersect_key( $walker_args, $defaults );
		
		$this->current_post_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
		
	}
	
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		
	}

	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		
	}

	public function start_el( &$output, $meta_object, $depth = 0, $args = array(), $current_object_id = 0 ) {
		$format = 'default' !== $meta_object->meta_key ? $this->walker_args['format'] : '%%NAME%%';

		$meta_object->meta_option = str_replace(
			'%%NAME%%',
			$meta_object->display_value,
			$format
		);
		
		$meta_object->show_item = $this->show_item_by_dependency_and_counters( $meta_object );
		
		$option_args = array(
			'label'			=> $meta_object->meta_option,
			'attributes'	=> array(
				'value'		=> $meta_object->meta_value
			)
		);
		
		if ( is_array( $this->walker_args['selected'] ) ) {
			if ( in_array( $meta_object->meta_value, $this->walker_args['selected'] ) ) {
				$option_args['attributes']['selected'] = 'selected';
				$meta_object->show_item = true;
			}
		} else if ( $this->walker_args['selected'] == $meta_object->meta_value ) {
			$option_args['attributes']['selected'] = 'selected';
			$meta_object->show_item = true;
		}
		
		if ( ! $meta_object->show_item ) {
			if ( 'hide' == $this->walker_args['empty_action'] ) {
				return;
			} else {
				$option_args['attributes']['disabled'] = 'disabled';
			}
		}
		
		switch ( $this->walker_args['output'] ) {
			case 'bootstrap':
				break;
			case 'legacy':
			default:
				$option_args['attributes']['class'] = array( 'js-wpv-filter-trigger wpcf-form-option form-option option' );
				break;
		}
		
		$output .= $this->el_option( $option_args['label'], $option_args['attributes'] );
		
	}

	public function end_el( &$output, $meta_object, $depth = 0, $args = array() ) {
		
	}
	
	/**
	 * Calculate whether the current item should be disabled or hidden, and its match count, based on ependency anc counters.
	 *
	 * @param object $meta_object
	 *
	 * @return bool
	 *
	 * @since 2.4.0
	 */
	public function show_item_by_dependency_and_counters( &$meta_object ) {
		$show_item_bool = true;
		if ( 'enabled' == $this->walker_args['use_query_cache'] ) {
			
			if ( 
				empty( $meta_object->meta_value ) 
				&& ! is_numeric( $meta_object->meta_value ) 
				&& is_object( $this->current_post_query ) 
			) {
				if ( $this->walker_args['auxiliar_query_count'] !== false ) {
					$this_checker = $this->walker_args['auxiliar_query_count'];
				} else {
					$this_checker = $this->current_post_query->found_posts;
				}
			} else {
				$wpv_meta_criteria_to_filter = array( $meta_object->meta_key => array( $meta_object->meta_value ) );
				$data = array();
				$data['list'] = $this->walker_args['query_cache'];
				$data['args'] = $wpv_meta_criteria_to_filter;
				$data['kind'] = $this->walker_args['toolset_type'];
				$data['comparator'] = $this->walker_args['comparator'];
				if ( 'enabled' == $this->walker_args['counters'] ) {
					$data['count_matches'] = true;
				}
				$this_checker = wpv_list_filter_checker( $data );
			}
			
			if ( 
				! $this_checker 
				&& ( 
					! empty( $meta_object->meta_value ) 
					|| is_numeric( $meta_object->meta_value ) 
				) 
				&& 'enabled' == $this->walker_args['dependency'] 
			) {
				$show_item_bool = false;
			}

			if ( 'enabled' == $this->walker_args['counters'] ) {
				$meta_object->meta_option = str_replace( '%%COUNT%%', $this_checker, $meta_object->meta_option );
			}
			
		}
		return $show_item_bool;
	}
	
}

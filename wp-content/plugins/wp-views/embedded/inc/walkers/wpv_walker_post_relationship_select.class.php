<?php

/**
 * Post relationship filter select options walker class
 *
 * @package Views
 *
 * @extends WPV_Walker_Control_Base
 *
 * @since 2.4.0
 */
class WPV_Walker_Post_Relationship_Select extends WPV_Walker_Control_Base {

	public $tree_type = 'relationship';

	public $db_fields = array(
		'parent' => 'ID',
		'id' => 'ID',
	);

	/** @var array */
	private $walker_args;

	/** @var mixed */
	private $current_post_query;


	/**
	 * Walker construct
	 *
	 * @param $walker_args array(
	 *		'name'				string	Name attribute to use, comes from the URL prameter to listen to
	 *		'selected'			string|array Selected items
	 *		'format'			string	Placeholders: %%NAME%%, %%COUNT%%
	 *		'style'				string	Input custom inline styles
	 *		'class'				string	Input custom classnames
	 *		'label_style'		string	Label custom inline styles
	 *		'label_class'		string	Label custom classnames
	 *		'extra_class'		array	Extra classnames to add the the inputs, based on the ancestor condition of the walked post type
	 *		'output'			string	Kind of output, 'bootstrap'|'legacy'
	 *		'type'				string	Type of output, 'select'|'multi-select'
	 *		'ancestor_type'		string	Slug of the post type being walked
	 *		'dependency'		string	'disabled'|'enabled'
	 *		'counters'			string	'disabled'|'enabled'
	 *		'use_query_cache'	string	'disabled'|'enabled'
	 *		'empty_action'		string	'hide'|'disable'
	 *		'query_cache'		array	Cache to use for counters and dependency
	 *		'auxiliar_query_count' bool|int Count of the auxiliar cache run by disabling this filter
	 * )
	 *
	 * @since 2.4.0
	 */
	public function __construct( $walker_args ) {

		parent::__construct( $walker_args );
		
		$defaults = array(
			'name'              => '',
			'selected'          => array( 0 ),
			'format'            => '%%NAME%%',
			'style'             => '',
			'class'             => '',
			'label_style'       => '',
			'label_class'       => '',
			'extra_class'       => array(),
			'output'            => 'legacy',
			'type'              => 'checkboxes',
			'ancestor_type'     => '',
			'dependency'        => 'disabled',
			'counters'          => 'disabled',
			'use_query_cache'   => 'disabled',
			'empty_action'      => 'hide',
			'query_cache'       => array(),
			'auxiliar_query_count' => false
		);

		$walker_args = wp_parse_args( $walker_args, $defaults );

		$this->walker_args = array_intersect_key( $walker_args, $defaults );
		
		$this->current_post_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
		
	}
	
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		
	}

	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		
	}

	public function start_el( &$output, $item_object, $depth = 0, $args = array(), $current_object_id = 0 ) {
		
		if ( 0 == $item_object->ID ) {
			$item_object->display_option = $item_object->post_title;
		} else {
			$item_object->display_option = str_replace( 
				'%%NAME%%', 
				$item_object->post_title, 
				$this->walker_args['format'] 
			);
		}
		
		$item_object->show_item = $this->show_item_by_dependency_and_counters( $item_object );
		
		$option_args = array(
			'label'			=> $item_object->display_option,
			'attributes'	=> array(
				'value'		=> $item_object->ID
			)
		);
		
		if ( is_array( $this->walker_args['selected'] ) ) {
			if ( in_array( $item_object->ID, $this->walker_args['selected'] ) ) {
				$option_args['attributes']['selected'] = 'selected';
				$item_object->show_item = true;
			}
		} else if ( $this->walker_args['selected'] == $item_object->ID ) {
			$option_args['attributes']['selected'] = 'selected';
			$item_object->show_item = true;
		}
		
		if ( ! $item_object->show_item ) {
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
				$option_args['attributes']['class'] = array( 'wpcf-form-option form-option option' );
				break;
		}
		
		$output .= $this->el_option( $option_args['label'], $option_args['attributes'] );
		
	}

	public function end_el( &$output, $item_object, $depth = 0, $args = array() ) {
		
	}
	
	/**
	 * Calculate whether the current item should be disabled or hidden, and its match count, based on ependency anc counters.
	 *
	 * @param object $item_object
	 *
	 * @return bool
	 *
	 * @since 2.4.0
	 */
	public function show_item_by_dependency_and_counters( &$item_object ) {
		$show_item_bool = true;
		
		if ( 'enabled' == $this->walker_args['use_query_cache'] ) {
			
			if (
				isset( $this->walker_args['query_cache'][ $item_object->ID ] )
				&& is_array( $this->walker_args['query_cache'][ $item_object->ID ] )
			) {
				if ( 
					'enabled' == $this->walker_args['counters'] 
					&& isset( $this->walker_args['query_cache'][ $item_object->ID ]['count'] )
				) {
					$item_object->display_option = str_replace( '%%COUNT%%', $this->walker_args['query_cache'][ $item_object->ID ]['count'], $item_object->display_option );
				}
			} else {
				if ( 'enabled' == $this->walker_args['counters'] ) {
					if ( 0 == $item_object->ID ) {
						if ( $this->walker_args['auxiliar_query_count'] !== false ) {
							$item_object->display_option = str_replace( '%%COUNT%%', $this->walker_args['auxiliar_query_count'], $item_object->display_option );
						} else {
							$item_object->display_option = str_replace( '%%COUNT%%', $this->current_post_query->found_posts, $item_object->display_option );
						}
					} else {
						$item_object->display_option = str_replace( '%%COUNT%%', '0', $item_object->display_option );
					}
				}
				if ( 
					'enabled' == $this->walker_args['dependency'] 
					&& 0 != $item_object->ID
				) {
					$show_item_bool = false;
				}
			}
			
		}
		return $show_item_bool;
	}
	
}

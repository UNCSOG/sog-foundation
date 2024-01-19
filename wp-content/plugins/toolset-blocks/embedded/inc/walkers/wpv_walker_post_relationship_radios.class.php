<?php

/**
 * Post relationship filter radios walker class
 *
 * @package Views
 *
 * @extends WPV_Walker_Control_Base
 *
 * @since 2.4.0
 */
class WPV_Walker_Post_Relationship_Radios extends WPV_Walker_Control_Base {

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
	 *		'type'				string	Type of output, 'radios'
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
		
		$el_args = array(
			'attributes'	=> array(
				'input'		=> array(
					'type'		=> 'radio',
					'style'		=> $this->walker_args['style'],
					'class'		=> ( empty( $this->walker_args['class'] ) ) ? array() : explode( ' ', $this->walker_args['class'] ),
					'name'		=> $this->walker_args['name'],
					'value'		=> $item_object->ID,
					'data-currentposttype'	=> $this->walker_args['ancestor_type']
				),
				'label'		=> array(
					'style'		=> $this->walker_args['label_style'],
					'class'		=> ( empty( $this->walker_args['label_class'] ) ) ? array() : explode( ' ', $this->walker_args['label_class'] )
				),
				
			)
		);
		
		$input_id_label_for = 'form-' 
			. md5( serialize( $el_args ) ) 
			. '-'
            . $this->get_next_post_relationship_radio_index( $item_object );
		
		$el_args['attributes']['input']['id'] = $input_id_label_for;
		$el_args['attributes']['label']['for'] = $input_id_label_for;
		
		$el_args['attributes']['input']['class'] = array_merge( $el_args['attributes']['input']['class'], $this->walker_args['extra_class'] );
		$el_args['attributes']['input']['class'] = array_unique( $el_args['attributes']['input']['class'] );
		
		$item_object->show_item = $this->show_item_by_dependency_and_counters( $item_object );
		
		if ( is_array( $this->walker_args['selected'] ) ) {
			if ( in_array( $item_object->ID, $this->walker_args['selected'] ) ) {
				$el_args['attributes']['input']['checked'] = 'checked';
			}
		} else {
			if ( $this->walker_args['selected']== $item_object->ID ) {
				$el_args['attributes']['input']['checked'] = 'checked';
			}
		}
		
		$el_args['label'] = $item_object->display_option;

		if ( 
			$item_object->show_item 
			|| (
				isset( $el_args['attributes']['input']['checked'] ) 
				&& 'checked' == $el_args['attributes']['input']['checked']
			)
		) {
			switch ( $this->walker_args['output'] ) {
				case 'bootstrap':
					$output .= $this->input_el_bootstrap( $el_args );
					break;
				case 'legacy':
				default:
					$el_args['attributes']['input']['class'][] = 'wpcf-form-radio form-radio radio';
					$el_args['attributes']['label']['class'][] = 'wpcf-form-label wpcf-form-radio-label';
					$output .= $this->input_el_legacy( $el_args );
					break;
			}
			
			
			
		} else if ( $this->walker_args['empty_action'] != 'hide') {
			$el_args['attributes']['input']['disabled'] = 'disabled';
			$el_args['attributes']['label']['class'][] = 'wpv-parametric-disabled';
			
			switch ( $this->walker_args['output'] ) {
				case 'bootstrap':
					$output .= $this->input_el_bootstrap( $el_args );
					break;
				case 'legacy':
				default:
					$el_args['attributes']['input']['class'][] = 'wpcf-form-radio form-radio radio';
					$el_args['attributes']['label']['class'][] = 'wpcf-form-label wpcf-form-radio-label';
					$output .= $this->input_el_legacy( $el_args );
					break;
			}
			
			
		}
		
	}

	public function end_el( &$output, $item_object, $depth = 0, $args = array() ) {
		
	}
	
	/**
	 * Calculate a unique index for setting proper and unique IDs for radio items.
	 *
	 * @param object $item_object
	 *
	 * @return int The index for the next apearance of the given $item_object
	 * 
	 * @since 2.4.0
	 */
	public function get_next_post_relationship_radio_index( $item_object ) {
		static $post_relationship_radio_counter;
		if ( ! isset( $post_relationship_radio_counter[ $item_object->ID ] ) ) {
			$post_relationship_radio_counter[ $item_object->ID ] = 1;
		} else {
			$post_relationship_radio_counter[ $item_object->ID ] = $post_relationship_radio_counter[ $item_object->ID ] + 1;
		}
		return $post_relationship_radio_counter[ $item_object->ID ];
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
	
	/**
	 * Render an item using the legacy output.
	 *
	 * @param array $args The input tag and label arguments.
	 *
	 * @return string The complete HTML for a radio option.
	 *
	 * @since 2.4.0
	 */
	public function input_el_legacy( $args ) {
		$output = '';
		// Input
		$output .= $this->el_input( $args['attributes']['input'] );
		// Label
		$output .= $this->el_label( $args['label'], $args['attributes']['label'] );
		// Compatibility: this was added by Enlimbo :-(
		$output .= '<br />';
		return $output;
	}
	
	/**
	 * Render an item using the bootstrap output.
	 *
	 * @param array $args The input tag and label arguments.
	 *
	 * @return string The complete HTML for a radio option.
	 *
	 * @since 2.4.0
	 */
	public function input_el_bootstrap( $args ) {
		switch ( Toolset_Settings::get_instance()->bootstrap_version_numeric ) {
			case \OTGS\Toolset\Common\Settings\BootstrapSetting::NUMERIC_BS4:
				// BS4 requires the input and the label to be siblings and has different element classes than we used to render before.
				$output = '<div class="form-check">'
					. $this->el_input( $this->add_attribute_element( $args['attributes']['input'], 'class', 'form-check-input' ) )
					. $this->el_label( $args['label'], $this->add_attribute_element( $args['attributes']['label'], 'class', 'form-check-label' ) )
					. '</div>';

				return $output;

			default:
				$output = '';
				$output .= '<div class="radio">';
				$input_output = $this->el_input( $args['attributes']['input'] );
				$output .= $this->el_label( $input_output . $args['label'], $args['attributes']['label'] );
				$output .= '</div>';

				return $output;
		}
	}
	
}

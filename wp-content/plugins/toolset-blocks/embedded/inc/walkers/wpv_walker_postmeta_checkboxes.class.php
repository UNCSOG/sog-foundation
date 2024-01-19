<?php

use OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale;

/**
 * Postmeta filter checkboxes walker class
 *
 * @package Views
 *
 * @extends WPV_Walker_Control_Base
 *
 * @since 2.4.0
 */
class WPV_Walker_Postmeta_Checkboxes extends WPV_Walker_Control_Base {

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
	 *		'type'				string	Type of output, 'checkboxes'
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
	public function __construct( $walker_args ) {
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

		$meta_object->meta_option = str_replace(
			'%%NAME%%',
			$meta_object->display_value,
			$this->walker_args['format']
		);

		$el_args = array(
			'attributes'	=> array(
				'input'		=> array(
					'type'		=> 'checkbox',
					'style'		=> $this->walker_args['style'],
					'class'		=> ( empty( $this->walker_args['class'] ) ) ? array() : explode( ' ', $this->walker_args['class'] ),
					'name'		=> $this->walker_args['name'] . '[]',
					'value'		=> $meta_object->meta_value
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
            . $this->get_next_postmeta_checkbox_index( $meta_object );

		$el_args['attributes']['input']['id'] = $input_id_label_for;
		$el_args['attributes']['label']['for'] = $input_id_label_for;

		$el_args['attributes']['input']['class'][] = 'js-wpv-filter-trigger';

		$show_item = $this->show_item_by_dependency_and_counters( $meta_object );

		if ( is_array( $this->walker_args['selected'] ) ) {
			if ( in_array( $meta_object->meta_value, $this->walker_args['selected'] ) ) {
				$el_args['attributes']['input']['checked'] = 'checked';
			}
		} else {
			if ( $this->walker_args['selected']== $meta_object->meta_value ) {
				$el_args['attributes']['input']['checked'] = 'checked';
			}
		}

		$el_args['label'] = $meta_object->meta_option;

		if (
			$show_item
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
					$el_args['attributes']['input']['class'][] = 'wpcf-form-checkbox form-checkbox checkbox';
					$el_args['attributes']['label']['class'][] = 'wpcf-form-label wpcf-form-checkbox-label';
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
					$el_args['attributes']['input']['class'][] = 'wpcf-form-checkbox form-checkbox checkbox';
					$el_args['attributes']['label']['class'][] = 'wpcf-form-label wpcf-form-checkbox-label';
					$output .= $this->input_el_legacy( $el_args );
					break;
			}


		}

	}

	public function end_el( &$output, $meta_object, $depth = 0, $args = array() ) {

	}

	/**
	 * Calculate a unique index for setting proper and unique IDs for checkbox items.
	 *
	 * @param object $meta_object
	 *
	 * @return int The index for the next apearance of the given $meta_object
	 *
	 * @since 2.4.0
	 */
	public function get_next_postmeta_checkbox_index( $meta_object ) {
		static $postmeta_checkbox_counter;
		if ( ! isset( $postmeta_checkbox_counter[ $meta_object->meta_key ] ) ) {
			$postmeta_checkbox_counter[ $meta_object->meta_key ] = 1;
		} else {
			$postmeta_checkbox_counter[ $meta_object->meta_key ] = $postmeta_checkbox_counter[ $meta_object->meta_key ] + 1;
		}
		return $postmeta_checkbox_counter[ $meta_object->meta_key ];
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
			if ( ProductOnsale::LEGACY_FIELD_SLUG === $meta_object->meta_key ) {
				return $this->show_views_woo_on_sale_item_by_dependency_and_counters( $meta_object );
			}

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

	/**
	 * Consider the special case of filtering by the legacy ProductOnsale::LEGACY_FIELD_SLUG field, and adjust show/hide plus counters.
	 *
	 * @param object $meta_object
	 * @return bool
	 */
	private function show_views_woo_on_sale_item_by_dependency_and_counters( &$meta_object ) {
		if ( ! function_exists( 'wc_get_product_ids_on_sale' ) ) {
			$meta_object->meta_option = str_replace( '%%COUNT%%', '0', $meta_object->meta_option );
			return false;
		}

		$show_item_bool = true;
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
			$returned_post_ids = array_keys( $this->walker_args['query_cache'] );
			$on_sale_products = \wc_get_product_ids_on_sale();
			$relevant_on_sale_products = array_intersect( $returned_post_ids, $on_sale_products );
			$this_checker = count( $relevant_on_sale_products );
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

		return $show_item_bool;
	}

	/**
	 * Render an item using the legacy output.
	 *
	 * @param array $args The input tag and label arguments.
	 *
	 * @return string The complete HTML for a checkbox option.
	 *
	 * @since 2.4.0
	 */
	public function input_el_legacy( $args ) {
		$output = '';
		$output .= '<div'
			. ' id="' . $args['attributes']['input']['id'] . '-wrapper"'
			. ' class="form-item form-item-checkbox wpcf-form-item wpcf-form-item-checkbox"'
			. '>';
		// Input
		$output .= $this->el_input( $args['attributes']['input'] );
		// Compatibility: this was added by Enlimbo :-(
		$output .= '&nbsp;';
		// Label
		$output .= $this->el_label( $args['label'], $args['attributes']['label'] );
		$output .= '</div>';
		return $output;
	}

	/**
	 * Render an item using the bootstrap output.
	 *
	 * @param array $args The input tag and label arguments.
	 *
	 * @return string The complete HTML for a checkbox option.
	 *
	 * @since 2.4.0
	 */
	public function input_el_bootstrap( $args ) {
		switch ( Toolset_Settings::get_instance()->bootstrap_version_numeric ) {
			case \OTGS\Toolset\Common\Settings\BootstrapSetting::NUMERIC_BS4:
				$output = '<div class="form-check">'
					. $this->el_input( $this->add_attribute_element( $args['attributes']['input'], 'class', 'form-check-input' ) )
					. $this->el_label( $args['label'], $this->add_attribute_element( $args['attributes']['label'], 'class', 'form-check-label' ) )
					. '</div>';

				return $output;

			default:
				$output = '';
				$output .= '<div class="checkbox">';
				$input_output = $this->el_input( $args['attributes']['input'] );
				$output .= $this->el_label( $input_output . $args['label'], $args['attributes']['label'] );
				$output .= '</div>';

				return $output;
		}
	}

}

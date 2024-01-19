<?php

/**
 * Post taxonomy filter select options walker class
 *
 * @package Views
 *
 * @extends WPV_Walker_Control_Base
 *
 * @since 2.4.0
 */
class WPV_Walker_Taxonomy_Select extends WPV_Walker_Control_Base {

	public $tree_type = 'taxonomy';

	public $db_fields = array(
		'parent' => 'parent',
		'id' => 'term_id',
	);

	/** @var array */
	private $walker_args;

	/** @var bool */
	private $in_this_tax_archive_page;

	private $tax_archive_term;


	/**
	 * Walker constructor.
	 *
	 * @param $walker_args array(
	 *		'taxonomy'			string	The slug of the taxonomy which terms are being walked
	 *		'name'				string	Name attribute to use, comes from the URL prameter to listen to
	 *		'value_type'		string	Whether to listen and use the term 'name'|'slug' in options
	 *		'selected'			string|array Selected items
	 *		'format'			string	Placeholders: %%NAME%%, %%COUNT%%
	 *		'style'				string	Input custom inline styles
	 *		'class'				string	Input custom classnames
	 *		'label_style'		string	Label custom inline styles
	 *		'label_class'		string	Label custom classnames
	 *		'output'			string	Kind of output, 'bootstrap'|'legacy'
	 *		'type'				string	Type of output, 'select'|'multi-select'
	 *		'dependency'		string	'disabled'|'enabled'
	 *		'empty_action'		string	'hide'|'disable'
	 *		'query_cache'		array	Cache to use for counters and dependency
	 *		'operator'			string	Method to use when applying the current filter: 'IN'|'NOT IN'|'AND'
	 * )
	 *
	 * @since 2.4.0
	 */
	public function __construct( $walker_args ) {
		parent::__construct( $walker_args );

		$defaults = array(
			'name' => '',
			'selected' => '',
			'value_type' => 'name',
			'format' => '%%NAME%%',
			'style' => '',
			'class' => '',
			'label_style' => '',
			'label_class' => '',
			'output' => 'legacy',
			'taxonomy' => '',
			'type' => 'checkboxes',
			'dependency' => 'disabled',
			'empty_action' => 'hide',
			'operator' => 'IN',
			'query_cache' => array(),
			'query_mode' => 'normal',
		);

		$walker_args = wp_parse_args( $walker_args, $defaults );

		$this->walker_args = array_intersect_key( $walker_args, $defaults );

		$this->walker_args['counters'] = ( strpos( $this->walker_args['format'], '%%COUNT%%' ) !== false ) ? 'enabled' : 'disabled';
		$this->walker_args['use_query_cache'] = ( $this->walker_args['dependency'] == 'enabled' || $this->walker_args['counters'] == 'enabled' ) ? 'enabled' : 'disabled';

		global $wp_query;
        $this->in_this_tax_archive_page = false;
        $this->tax_archive_term = null;

        if (
			'normal' !== toolset_getarr( $this->walker_args, 'query_mode', 'normal' )
			&& (
				is_tax()
				|| is_category()
				|| is_tag()
			)
        ) {
            $term = $wp_query->get_queried_object();

            if ( $term
                && isset( $term->taxonomy )
                && $term->taxonomy == $this->walker_args['taxonomy']
            ) {
                $this->in_this_tax_archive_page = true;
                $this->tax_archive_term = $term;
            }
        }
	}

	public function start_lvl( &$output, $depth = 0, $args = array() ) {

	}

	public function end_lvl( &$output, $depth = 0, $args = array() ) {

	}

	public function start_el( &$output, $taxonomy_term, $depth = 0, $args = array(), $current_object_id = 0 ) {
		$indent = str_repeat( '-', $depth );
		if ( $indent != '' ) {
			$indent = '&nbsp;' . str_repeat( '&nbsp;', $depth ) . $indent;
		}

		$taxonomy_term->tax_option = str_replace(
			'%%NAME%%',
			$taxonomy_term->name,
			$this->walker_args['format']
		);


		switch ( $this->walker_args['value_type'] ) {
			case 'name':
				$tax_value = $taxonomy_term->name;
				break;
			case 'slug':
			default:
				$tax_value = urldecode( $taxonomy_term->slug );
				break;
		}

		// If the current page is a taxonomy page for the taxonomy the filter refers to
        if ( $this->in_this_tax_archive_page ) {
		    // ... and if the queried taxonomy term is the current term rendered in the filter
            if ( $this->tax_archive_term->slug == $taxonomy_term->slug ) {
                // ... display the term and make it selected
				if ( $this->walker_args['counters'] ) {
					$wpv_tax_criteria_to_filter = array( $taxonomy_term->term_id => $taxonomy_term->term_id );
					$wpv_tax_criteria_matching_posts = wp_list_filter( $this->walker_args['query_cache'], $wpv_tax_criteria_to_filter );
					$taxonomy_term->tax_option = str_replace( '%%COUNT%%', count( $wpv_tax_criteria_matching_posts ), $taxonomy_term->tax_option );
				}
                $output .= '<option value="' . esc_attr( $tax_value ) . '" selected="selected">' . $indent . $taxonomy_term->tax_option . "</option>\n";
            }
            // ... else disregard this taxonomy term option for the filter
        } else {

			$show_item = $this->show_item_by_dependency_and_counters( $taxonomy_term );

			$option_args = array(
				'label'			=> $indent . $taxonomy_term->tax_option,
				'attributes'	=> array(
					'value'		=> $tax_value
				)
			);

			if ( is_array( $this->walker_args['selected'] ) ) {
				if ( in_array( $tax_value, $this->walker_args['selected'] ) ) {
					$option_args['attributes']['selected'] = 'selected';
					$show_item = true;
				}
			} else if ( $this->walker_args['selected'] == $tax_value ) {
				$option_args['attributes']['selected'] = 'selected';
				$show_item = true;
			}

			if ( ! $show_item ) {
				if ( 'hide' == $this->walker_args['empty_action'] ) {
					return;
				} else {
					$option_args['attributes']['disabled'] = 'disabled';
				}
			}

			$output .= $this->el_option( $option_args['label'], $option_args['attributes'] );
        }
	}

	public function end_el( &$output, $taxonomy_term, $depth = 0, $args = array() ) {

	}

	/**
	 * Calculate whether the current item should be disabled or hidden, and its match count, based on ependency anc counters.
	 *
	 * @param object $taxonomy_term
	 *
	 * @return bool
	 *
	 * @since 2.4.0
	 */
	public function show_item_by_dependency_and_counters( &$taxonomy_term ) {
		$show_item_bool = true;
		if ( 'enabled' == $this->walker_args['use_query_cache'] ) {
			$wpv_tax_criteria_to_filter = array( $taxonomy_term->term_id => $taxonomy_term->term_id );
			$wpv_tax_criteria_matching_posts = wp_list_filter( $this->walker_args['query_cache'], $wpv_tax_criteria_to_filter );
			if (
				count( $wpv_tax_criteria_matching_posts ) == 0
				&& 'enabled' == $this->walker_args['dependency']
			) {
				$show_item_bool = false;
			}
			if ( $this->walker_args['counters'] ) {
				$taxonomy_term->tax_option = str_replace( '%%COUNT%%', count( $wpv_tax_criteria_matching_posts ), $taxonomy_term->tax_option );
			}
		}
		return $show_item_bool;
	}

}

<?php

/**
 * Post taxonomy filter checkboxes walker class
 *
 * @package Views
 *
 * @extends WPV_Walker_Control_Base
 *
 * @since 2.4.0
 */
class WPV_Walker_Taxonomy_Checkboxes extends WPV_Walker_Control_Base {

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

	private $name;

	/** @var bool */
	private $show_legacy_item;


	/**
	 * Walker construct
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
	 *		'type'				string	Type of output, 'checkboxes'
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
			'name'				=> '',
			'selected'			=> '',
			'value_type'		=> 'name',
			'format'			=> '%%NAME%%',
			'style'				=> '',
			'class'				=> '',
			'label_style'		=> '',
			'label_class'		=> '',
			'output'			=> 'legacy',
			'taxonomy'			=> '',
			'type'				=> 'checkboxes',
			'dependency'		=> 'disabled',
			'empty_action'		=> 'hide',
			'operator'			=> 'IN',
			'query_cache'		=> array(),
			'query_mode' => 'normal',
			'shortcode' => 'wpv-control-post-taxonomy',
		);

		$walker_args = wp_parse_args( $walker_args, $defaults );

		$this->walker_args = array_intersect_key( $walker_args, $defaults );

		$this->walker_args['counters'] = ( strpos( $this->walker_args['format'], '%%COUNT%%' ) !== false ) ? 'enabled' : 'disabled';
		$this->walker_args['use_query_cache'] = ( $this->walker_args['dependency']== 'enabled' || $this->walker_args['counters'] == 'enabled' ) ? 'enabled' : 'disabled';

		global $wp_query;
        $this->in_this_tax_archive_page = false;
        $this->tax_archive_term = null;

		$this->name = $this->walker_args['taxonomy'];
		if ( $this->name == 'category' ) {
			$this->name = 'post_category';
		}

		// The ID for li items on this output are slightly different when using the old shortcode
		// and when using the new shortcode with an output="legacy" attribute, on category filters,
		// because of reasons. No breaking chnage just keep this difference.
		$this->legacy_li_item_id_prefix = $this->name;
		if ( 'wpv-control-post-taxonomy' !== $this->walker_args['shortcode'] ) {
			$this->legacy_li_item_id_prefix = $this->walker_args['taxonomy'];
		}

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

		// Flag to signal whether the legacy structure nees to be displayed.
		// Needed because legacy checkboxes display in an uordered list structure, and
		// children lists must be included in parent list items.

		$this->show_legacy_item = false;

	}

	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		if ( 'legacy' == $this->walker_args['output'] ) {
			$indent = str_repeat("\t", $depth);
			$output .= "$indent<ul class='children'>\n";
		}
	}

	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		if ( 'legacy' == $this->walker_args['output'] ) {
			$indent = str_repeat("\t", $depth);
			$output .= "$indent</ul>\n";
		}
	}

	public function start_el( &$output, $taxonomy_term, $depth = 0, $args = array(), $current_object_id = 0 ) {

		$popular_cats = isset( $args['popular_cats'] ) ? $args['popular_cats'] : array();

		$taxonomy_term->tax_option = str_replace(
			'%%NAME%%',
			$taxonomy_term->name,
			$this->walker_args['format']
		);

		switch ( $this->walker_args['value_type'] ) {
			case 'slug':
				$tax_value = urldecode( $taxonomy_term->slug );
				break;
			case 'name':
			default:
				$tax_value = $taxonomy_term->name;
				break;
		}

		$li_class = in_array( $taxonomy_term->term_id, $popular_cats ) ? ' class="popular-category"' : '';

		$el_args = array(
			'attributes'	=> array(
				'input'		=> array(
					'type'		=> 'checkbox',
					'id'		=> 'in-' . $this->walker_args['taxonomy'] . '-' . $taxonomy_term->term_id,
					'style'		=> $this->walker_args['style'],
					'class'		=> ( empty( $this->walker_args['class'] ) ) ? array() : explode( ' ', $this->walker_args['class'] ),
					'name'		=> $this->walker_args['name'] . '[]',
					'value'		=> $tax_value
				),
				'label'		=> array(
					'for'		=> 'in-' . $this->walker_args['taxonomy'] . '-' . $taxonomy_term->term_id,
					'style'		=> $this->walker_args['label_style'],
					'class'		=> ( empty( $this->walker_args['label_class'] ) ) ? array() : explode( ' ', $this->walker_args['label_class'] )
				),
			),
		);

		$el_args['attributes']['input']['class'][] = 'js-wpv-filter-trigger';

		switch ( $this->walker_args['output'] ) {
			case 'bootstrap':
				$el_args['attributes']['input']['id'] = $this->name . '-' . $taxonomy_term->slug;
				$el_args['attributes']['label']['for'] = $this->name . '-' . $taxonomy_term->slug;
				break;
			case 'legacy':
			default:
				$el_args['attributes']['label']['class'][] = 'selectit';
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

				$el_args['label'] = $taxonomy_term->tax_option;
				$el_args['attributes']['input']['checked'] = 'checked';

				switch ( $this->walker_args['output'] ) {
					case 'bootstrap':
						$output .= $this->input_el_bootstrap( $el_args );
						break;
					case 'legacy':
					default:
						$output .= '<li id="' . esc_attr( $this->legacy_li_item_id_prefix ) . '-' . esc_attr( $taxonomy_term->slug ) . '"'
							. $li_class
							. '>';
						$output .= $this->input_el_legacy( $el_args );
						$this->show_legacy_item = true;
						break;
				}

            }
            // ... else disregard this taxonomy term option for the filter
        } else {

			$show_item = $this->show_item_by_dependency_and_counters( $taxonomy_term );

		    // ... else let the normal procedures decide whether to display the option or not.
            if ( is_array( $this->walker_args['selected'] ) ) {
				if ( in_array( $tax_value, $this->walker_args['selected'] ) ) {
					$el_args['attributes']['input']['checked'] = 'checked';
				}
            } else {
				if ( $this->walker_args['selected']== $tax_value ) {
					$el_args['attributes']['input']['checked'] = 'checked';
				}
            }

			$el_args['label'] = $taxonomy_term->tax_option;

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
						$output .= '<li id="' . esc_attr( $this->legacy_li_item_id_prefix ) . '-' . esc_attr( $taxonomy_term->term_id ) . '"'
							. $li_class
							. '>';
						$output .= $this->input_el_legacy( $el_args );
						$this->show_legacy_item = true;
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
						$output .= '<li id="' . esc_attr( $this->legacy_li_item_id_prefix ) . '-' . esc_attr( $taxonomy_term->term_id ) . '"'
							. $li_class
							. '>';
						$output .= $this->input_el_legacy( $el_args );
						$this->show_legacy_item = true;
						break;
				}

            }

        }

	}

	public function end_el( &$output, $taxonomy_term, $depth = 0, $args = array() ) {
		if ( $this->show_legacy_item ) {
			$output .= '</li>';
			$output .= "\n";
			$this->show_legacy_item = false;
		}
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
		$input_output = $this->el_input( $args['attributes']['input'] );
		$output .= $this->el_label( $input_output . ' ' . $args['label'], $args['attributes']['label'] );
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

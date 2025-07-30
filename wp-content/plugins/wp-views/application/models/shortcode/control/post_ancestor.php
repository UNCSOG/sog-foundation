<?php

/**
 * Manage the wpv-control-post-ancestor shortcode and its legacy wpv-control-item alias.
 *
 * @since m2m
 */
abstract class WPV_Shortcode_Control_Post_Ancestor {

	const SHORTCODE_NAME = 'wpv-control-post-ancestor';
	const SHORTCODE_NAME_ALIAS = 'wpv-control-item';

	const WP_QUERY_ANCESTOR_TREE_ROOF_FLAG = 'wpv_ancestor_tree_roof_query';

	/**
	 * @var string[]
	 */
	protected $shortcode_atts = array(
		'type'					=> '',
		'url_param'				=> '',
		'ancestor_type'			=> '',
		'ancestor_tree'			=> '',
		'default_label'			=> '',
		'returned_pt_parents'	=> '',
		'format'				=> '%%NAME%%',
		'orderby'				=> 'title',
		'order'					=> 'ASC',
		'style'					=> '',
		'class'					=> '',
		'label_style'			=> '',
		'label_class'			=> '',
		'output'				=> 'bootstrap'
	);

	/**
	 * @var string[]
	 */
	protected $required_atts = array(
		'url_param', 'type', 'ancestor_type', 'ancestor_tree'
	);

	/**
	 * @var string[]
	 */
	protected $allowed_orderby_values = array(
		'id'			=> 'ID',
		'title'			=> 'post_title',
		'date'			=> 'post_date',
		'date_modified'	=> 'post_modified',
		'comment_count'	=> 'comment_count'
	);

	/**
	 * @var string|null
	 */
	protected $user_content;

	/**
	 * @var string[]
	 */
	protected $user_atts;

	/**
	 * @var string
	 */
	protected $shortcode;

	/**
	 * @var WPV_Filter_Post_Relationship
	 */
	protected $filter;

	/**
	 * @var string[]
	 */
	protected $relationship_closest;

	/**
	 * @var array
	 */
	protected $relationship_pieces;

	/**
	 * @var array
	 */
	protected $relationship_tree;

	/**
	 * @var string
	 */
	protected $relationship_tree_ground;

	/**
	 * @var string
	 */
	protected $relationship_tree_roof;

	/**
	 * @var array
	 */
	protected $ancestor_data;

	/**
	 * @var string
	 */
	protected $url_param;

	/**
	 * @var string
	 */
	protected $orderby;

	/**
	 * @var string
	 */
	protected $order;

	/**
	 * @var array
	 */
	protected $dependency_and_counters_data;

	/**
	 * @var array
	 */
	protected $filter_options;

	/**
	 * @var string
	 */
	protected $style;

	/**
	 * @var string[]
	 */
	protected $class_array;


	/**
	 * Constructor.
	 *
	 * @param $shortcode
	 */
	public function __construct( $shortcode ) {
		$this->shortcode = $shortcode;
		if ( self::SHORTCODE_NAME_ALIAS === $this->shortcode ) {
			$this->shortcode_atts['output'] = 'legacy';
		}
		$this->shortcode_atts['default_label'] = __( 'Please select', 'wpv-views' );
	}

	/**
	 * Get the shortcode output value.
	 *
	 * @param $atts
	 * @param $content
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		$filter_manager = WPV_Filter_Manager::get_instance();
		$this->filter = $filter_manager->get_filter( Toolset_Element_Domain::POSTS, 'relationship' );

		if ( ! $this->filter->is_types_installed() ) {
			return __( 'You need the Types plugin to render this custom search control.', 'wpv-views' );
		}

		$this->relationship_closest = $this->filter->get_closest_related_to_returned_post_types();

		// Remove once ported
		$this->user_atts['returned_pt_parents'] = implode( ',' , $this->relationship_closest );

		$this->user_atts['url_param'] = $this->filter->get_filter_data( 'url_param' );
		$this->user_atts['ancestor_tree'] = $this->filter->get_filter_data( 'ancestors' );

		$format = $this->filter->get_filter_data( 'format' );

		if ( ! empty( $format ) ) {
			$this->user_atts['format'] = $format;
		}

		foreach ( $this->required_atts as $required_att ) {
			if ( empty( $this->user_atts[ $required_att ] ) ) {
				return sprintf( __( 'The %s attribute is missing.', 'wpv-views' ), $required_att );
			}
		}

		// Set basic data about the relationships tree (including roof and ground),
		// and the current ancestor, plus output classes and styles
		$this->set_data();

		if ( ! array_key_exists( $this->ancestor_data['type'], $this->relationship_tree ) ) {
			return __( 'The ancestor_type argument refers to a post type that is not included in the relationship tree.', 'wpv-views' );
		}

		if ( ! in_array( $this->relationship_tree_ground, $this->relationship_closest ) ) {
			return __( 'The ancestors argument does not end with a valid post that is related for the returned post types on this View.', 'wpv-views' );
		}

		// Orderby and order
		if ( ! array_key_exists( $this->user_atts['orderby'], $this->allowed_orderby_values ) ) {
			$this->user_atts['orderby'] = 'title';
		}
		$this->orderby = $this->allowed_orderby_values[ $this->user_atts['orderby'] ];
		$this->order = ( 'DESC' == strtoupper( $this->user_atts['order'] ) ) ? 'DESC' : 'ASC';

		// URL parameter and extra classes
		if ( $this->ancestor_data['type'] == $this->relationship_tree_ground ) {
			$this->url_param = $this->user_atts['url_param'];
			$this->class_array[] = 'js-wpv-filter-trigger';
		} else {
			$this->url_param = $this->user_atts['url_param'] . '-' . $this->ancestor_data['type'];
			$this->class_array[] = 'js-wpv-post-relationship-update';
		}

		// Default label
		if ( ! empty( $this->user_atts['default_label'] ) ) {
			$view_id    = apply_filters( 'wpv_filter_wpv_get_current_view', null );
			$view_name  = get_post_field( 'post_name', $view_id );
			$this->user_atts['default_label'] = wpv_translate(
				$this->ancestor_data['type'] . '_default_label',
				$this->user_atts['default_label'],
				false,
				'View ' . $view_name
			);

			$this->user_atts['default_label'] = wpv_translate(
				$this->ancestor_data['type'] . '@' . $this->ancestor_data['relationship'] . '.' . $this->ancestor_data['role'] . '_default_label',
				$this->user_atts['default_label'],
				false,
				'View ' . $view_name
			);
		}

		// Format
		if ( ! empty( $this->user_atts['format'] ) ) {
			$view_id                   = apply_filters( 'wpv_filter_wpv_get_current_view', null );
			$view_name                 = get_post_field( 'post_name', $view_id );
			$this->user_atts['format'] = wpv_translate(
				$this->ancestor_data['type'] . '_format',
				$this->user_atts['format'],
				false,
				'View ' . $view_name
			);

			$this->user_atts['format'] = wpv_translate(
				$this->ancestor_data['type'] . '@' . $this->ancestor_data['relationship'] . '.' . $this->ancestor_data['role'] . '_format',
				$this->user_atts['format'],
				false,
				'View ' . $view_name
			);
		}

		// Filter options:
		$this->filter_options = array();
		if ( $this->ancestor_data['type'] == $this->relationship_tree_roof ) {
			$this->filter_options = $this->get_relationship_tree_roof_options();
		} else {
			$this->filter_options = $this->get_relationship_tree_item_options();
		}

		// Dependency and counters are different
		$this->setup_frontend_dependency_and_counters_data_with_cache();

		$output = '';

		// Lets try to keep output methods shared too
		switch ( $this->user_atts['output'] ) {
			case 'legacy':
				$output = $this->get_legacy_value();
				break;
			case 'bootstrap':
				$output = $this->get_bootstrap_value();
				break;
		}

		$this->clear_data();
		return $output;
	}

	/**
	 * Set shortcode basic data, including:
	 * - Relationships tree, roof and ground.
	 * - Current ancestor data.
	 * - Output styles and classnames.
	 *
	 * @since m2m
	 */
	protected function set_data() {
		$this->relationship_pieces = explode( '>', $this->user_atts['ancestor_tree'] );
		$this->relationship_tree = $this->filter->get_relationship_tree( $this->user_atts['ancestor_tree'] );

		$ancestors_keys = array_keys( $this->relationship_tree );
		$this->relationship_tree_roof = $ancestors_keys[0];
		$this->relationship_tree_ground = array_pop( $ancestors_keys );

		$ancestor_data = explode( '@', $this->user_atts['ancestor_type'] );
		$this->ancestor_data = array_key_exists( $ancestor_data[0], $this->relationship_tree )
			? $this->relationship_tree[ $ancestor_data[0] ]
			: array(
				'type' => '',
				'relationship' => '',
				'role' => '',
				'role_target' => ''
			);

		$this->class_array = explode( ' ', $this->user_atts['class'] );
		$this->style = $this->user_atts['style'];
	}

	/**
	 * Get the posts to include in the ancestor roof selector.
	 *
	 * Note that the way to gather such options depend on WPML.
	 * - When the relevant post type is set to display as translated, default to a
	 *   WP_Query and let WPML do its magic to return results.
	 * - When the relevant post type is translatable, apply filters per current language.
	 * - Otherwise, just get all IDs and titles.
	 *
	 * @return array of objects with at least ID and post_title properties.
	 * @since m2m
	 */
	protected function get_relationship_tree_roof_options() {
		$values_to_prepare = array();

		$needs_wpml_support = $this->filter->is_wpml_installed_and_ready();

		if (
			$needs_wpml_support
			&& apply_filters( 'wpml_is_display_as_translated_post_type', false, $this->ancestor_data['type'] )
		) {
			// Translation mode: display as translated.
			// In this case we default to the WP_Query hooked results from WPML.
			// This is slightly more expensive than a custom query,
			// but the custom query here would be too complex.
			$ancestor_query_args = array(
				'post_type' => $this->ancestor_data['type'],
				'posts_per_page' => -1,
				'no_found_rows' => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'ignore_sticky_posts' => true,
				self::WP_QUERY_ANCESTOR_TREE_ROOF_FLAG => true,
				'orderby' => $this->orderby,
				'order' => $this->order
			);

			add_filter( 'posts_fields', array( $this, 'query_just_id_and_title' ), 10, 2 );
			$ancestor_query = new \WP_Query( $ancestor_query_args );
			remove_filter( 'posts_fields', array( $this, 'query_just_id_and_title' ), 10 );

			return $ancestor_query->posts;
		}

		// In any other case, run a custom query, as it is cheaper.
		global $wpdb;
		$wpml_join = $wpml_where = "";
		if (
			$needs_wpml_support
			&& apply_filters( 'wpml_is_translated_post_type', false, $this->ancestor_data['type'] )
		) {
			$wpml_current_language = apply_filters( 'wpml_current_language', null );
			$wpml_join = " JOIN {$wpdb->prefix}icl_translations icl_t ";
			$wpml_where = " AND p.ID = icl_t.element_id AND icl_t.language_code = %s AND icl_t.element_type LIKE 'post_%' ";
			$values_to_prepare[] = $wpml_current_language;
		}

		$values_to_prepare[] = $this->ancestor_data['type'];

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title
				FROM {$wpdb->posts} p {$wpml_join}
				WHERE p.post_status = 'publish'
				{$wpml_where}
				AND p.post_type = %s
				ORDER BY p.{$this->orderby} {$this->order}",
				$values_to_prepare
			)
		);
	}

	/**
	 * Callback on the posts_fields filter to adjust the returned fields from a WP_Query.
	 *
	 * @param string $fields
	 * @param \WP_Query $query
	 * @return string
	 */
	public function query_just_id_and_title( $fields, $query ) {
		if ( $query->get( self::WP_QUERY_ANCESTOR_TREE_ROOF_FLAG ) ) {
			global $wpdb;
			$fields = "$wpdb->posts.ID, $wpdb->posts.post_title";
		}

		return $fields;
	}

	/**
	 * Get the posts to include in any ancestor selector but the tree roof.
	 *
	 * The actual implementation depends on whether m2m has been activated.
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	protected abstract function get_relationship_tree_item_options();

	/**
	 * Get the cache target data for search dependency and counters.
	 *
	 * The actual data to cache depends on whether m2m has been activated.
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	protected abstract function set_target_for_cache_extended_for_post_relationship();

	/**
	 * Calculate the needed data to be used by dependency and counters, including auxiliar queries.
	 *
	 * @since m2m
	 */
	protected function setup_frontend_dependency_and_counters_data_with_cache() {
		$object_settings = $this->filter->get_current_object_settings();

		$this->dependency_and_counters_data = array(
			'use_query_cache' => 'disabled',
			'dependency' => 'disabled',
			'counters' => 'disabled',
			'empty_action' => 'hide',
			'relationship_cache' => array(),
			'auxiliar_query_count' => false
		);

		if (
			isset( $object_settings['dps'] )
			&& is_array( $object_settings['dps'] )
			&& isset( $object_settings['dps']['enable_dependency'] )
			&& $object_settings['dps']['enable_dependency'] == 'enable'
		) {
			$this->dependency_and_counters_data['dependency'] = 'enabled';
			$force_disable_dependant = apply_filters( 'wpv_filter_wpv_get_force_disable_dps', false );
			if ( $force_disable_dependant ) {
				$this->dependency_and_counters_data['dependency'] = 'disabled';
			}
		}
		if (
			strpos( $this->user_atts['format'], '%%COUNT%%' ) !== false
			|| strpos( $this->user_atts['default_label'], '%%COUNT%%' ) !== false
		) {
			$this->dependency_and_counters_data['counters'] = 'enabled';
		}

		$this->dependency_and_counters_data['use_query_cache'] =
			(
				'enabled' == $this->dependency_and_counters_data['dependency']
				|| 'enabled' == $this->dependency_and_counters_data['counters']
			)
			? 'enabled'
			: 'disabled';

		if ( 'disabled' === $this->dependency_and_counters_data['use_query_cache'] ) {
			return;
		}

		// Empty action
		$empty_action_type = $this->user_atts['type'];
		switch ( $empty_action_type ) {
			case 'radio':
				$empty_action_type = 'radios';
				break;
			case 'multi-select':
			case 'multiselect':
				$empty_action_type = 'multi_select';
				break;
		}
		if ( isset( $object_settings['dps'][ 'empty_' . $empty_action_type ] ) ) {
			$this->dependency_and_counters_data['empty_action'] = $object_settings['dps'][ 'empty_' . $empty_action_type ];
		}

		if ( ! $this->is_ancestor_filter_submitted() ) {
			// This is when there is no value selected
			// And will return the natural cache
			WPV_Cache::generate_cache_extended_for_post_relationship();
		} else {
			// When there is a selected value, create a pseudo-cache based on all the other filters
			// Note that as this is a hierarquical filter, disabling the current means leaving the ancestors ones
			$current_filter = $_GET[ $this->url_param ];
			$object_settings = $this->filter->get_current_object_settings();
			unset( $_GET[ $this->url_param ] );
			$query = apply_filters( 'wpv_filter_wpv_get_dependant_extended_query_args', array(), $object_settings, array( 'relationship' => 'pr_filter_post__in' ) );
			$aux_cache_query = null;
			if (
				isset( $query['post__in'] )
				&& is_array( $query['post__in'] )
				&& isset( $query['pr_filter_post__in'] )
				&& is_array( $query['pr_filter_post__in'] )
			) {
				$diff = array_diff( $query['post__in'], $query['pr_filter_post__in'] );
				if ( empty( $diff ) ) {// TODO maybe we can skip the query here
					unset( $query['post__in'] );
				} else {
					$query['post__in'] = $diff;
				}
			}
			$aux_cache_query = new WP_Query( $query );
			if (
				is_array( $aux_cache_query->posts )
				&& ! empty( $aux_cache_query->posts )
			) {
				WPV_Cache::generate_cache_extended_for_post_relationship(
					$aux_cache_query->posts,
					$this->set_target_for_cache_extended_for_post_relationship()
				);
				$this->dependency_and_counters_data['auxiliar_query_count'] = count( $aux_cache_query->posts );
			} else {
				// Just in case, this will return the natural cache
				WPV_Cache::generate_cache_extended_for_post_relationship();
			}
			$_GET[ $this->url_param  ] = $current_filter;
		}
		// Now, generate the WPV_Cache::$stored_relationship_cache from the current ancestor data
		// Notice that this just extends the native cache with the one generated by the current relationship data
		// so it clears previous iteration automatically, as it is non-permanent data
		$ancestor_tree_array = array_keys( $this->relationship_tree );
		$calculate_counters = ( 'enabled' == $this->dependency_and_counters_data['counters'] );
		WPV_Cache::generate_post_relationship_tree_cache( $ancestor_tree_array, $calculate_counters );
		$this->dependency_and_counters_data['relationship_cache'] = WPV_Cache::$stored_relationship_cache;
	}

	/**
	 * Check whether the current search filter has been submitted.
	 *
	 * Just needs to check that the associated URL parameter is in the URL query string.
	 *
	 * @return bool
	 *
	 * @since m2m
	 */
	protected function is_ancestor_filter_submitted() {
		if ( empty( $_GET[ $this->url_param ] ) ) {
			return false;
		}
		if ( $_GET[ $this->url_param ] === 0 ) {
			return false;
		}
		if (
			is_array( $_GET[ $this->url_param ] )
			&& in_array( (string) 0, $_GET[ $this->url_param ] )
		) {
			return false;
		}
		return true;
	}

	/**
	 * Clear any shortcode stored data.
	 *
	 * @since m2m
	 */
	protected function clear_data() {
		$this->relationship_closest = array();
		$this->relationship_tree = array();
		$this->relationship_tree_ground = null;
		$this->relationship_tree_roof = null;

	}

	/**
	 * Get the shortcode value for the legacy output.
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	protected function get_legacy_value() {
		$return = '';

		switch ( $this->user_atts['type'] ) {
			case 'select':
			case 'multi-select':
				// Add the default value to the top of the options if $type is select, with a 0 value
				$options = array();
				if ( 'select' === $this->user_atts['type'] ) {
					if ( empty( $this->user_atts['default_label'] ) ) {
						$options[''] = 0;
					} else {
						if ( strpos( $this->user_atts['default_label'], '%%COUNT%%' ) !== false ) {
							if ( $this->dependency_and_counters_data['auxiliar_query_count'] !== false ) {
								$this->user_atts['default_label'] = str_replace(
									'%%COUNT%%',
									$this->dependency_and_counters_data['auxiliar_query_count'],
									$this->user_atts['default_label']
								);
							} else {
								$current_post_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
								$this->user_atts['default_label'] = str_replace(
									'%%COUNT%%',
									$current_post_query->found_posts, $this->user_atts['default_label']
								);
							}
						}
						$options[ $this->user_atts['default_label'] ] = 0;
					}
				}
				// Create the basic $element that will hold the wpv_form_control attributes
				$element = array(
					'field'	=> array(
						'#type'			=> 'select',
						'#attributes'	=> array(
							'style'					=> $this->user_atts['style'],
							'class'					=> implode( ' ', $this->class_array ),
							'data-currentposttype'	=> $this->ancestor_data['type']
						),
						'#inline'		=> true
					)
				);
				// Build the name, id and default values depending whether we are dealing with a real parent or not
				$element['field']['#default_value'] = array( 0 );
				$element['field']['#name'] = $this->url_param . '[]';
				if ( $this->ancestor_data['type'] == $this->relationship_tree_ground ) {
					$element['field']['#id'] = 'wpv_control_' . $this->user_atts['type']
						. '_' . $this->user_atts['url_param'];
				} else {
					$element['field']['#id'] = 'wpv_control_' . $this->user_atts['type']
						. '_' . $this->user_atts['url_param'] . '_' . $this->ancestor_data['type'];
				}
				if ( isset( $_GET[ $this->url_param ] ) ) {
					$element['field']['#default_value'] = $_GET[ $this->url_param ] ;
				}
				// Security check: this must always be an array!
				if ( ! is_array( $element['field']['#default_value'] ) ) {
					$element['field']['#default_value'] = array( $element['field']['#default_value'] );
				}
				// Loop through the posts and add them as options like post_title => ID
				foreach ( $this->filter_options as $pa_item ) {
					$options[ $pa_item->ID ] = array(
						'#title'	=> str_replace( '%%NAME%%', $pa_item->post_title, $this->user_atts['format'] ),
						'#value'	=> $pa_item->ID,
						'#inline'	=> true,
						'#after'	=> '<br />'
					);
					if (
						'enabled' === $this->dependency_and_counters_data['dependency']
						|| 'enabled' === $this->dependency_and_counters_data['counters']
					) {
						if (
							isset( $this->dependency_and_counters_data['relationship_cache'][ $pa_item->ID ] )
							&& is_array( $this->dependency_and_counters_data['relationship_cache'][ $pa_item->ID ] )
						) {
							if (
								'enabled' === $this->dependency_and_counters_data['counters']
								&& isset( $this->dependency_and_counters_data['relationship_cache'][ $pa_item->ID ]['count'] )
							) {
								$options[ $pa_item->ID ]['#title'] = str_replace(
									'%%COUNT%%',
									$this->dependency_and_counters_data['relationship_cache'][ $pa_item->ID ]['count'],
									$options[ $pa_item->ID ]['#title']
								);
							}
						} else {
							if ( 'enabled' === $this->dependency_and_counters_data['counters'] ) {
								$options[ $pa_item->ID ]['#title'] = str_replace(
									'%%COUNT%%',
									'0',
									$options[ $pa_item->ID ]['#title']
								);
							}
							if (
								'enabled' === $this->dependency_and_counters_data['dependency']
								&& ! in_array( $pa_item->ID, $element['field']['#default_value'] )
							) {
								$options[ $pa_item->ID ]['#disable'] = 'true';
								$options[ $pa_item->ID ]['#labelclass'] = 'wpv-parametric-disabled';
								if ( 'hide' === $this->dependency_and_counters_data['empty_action'] ) {
									unset( $options[ $pa_item->ID ] );
								}
							}
						}
					}
				}
				$element['field']['#options'] = $options;
				// If there are no options and is multi-select, break NOTE this break is for hide, maybe disable, we will see
				/* if (
					$this->user_atts['type'] == 'multi-select'
					&& count( $options ) == 0
				) {
				//	break;
				} */
				// If there is only one option for select or none for multi-select, disable this form control NOTE review this
				if (
					count( $options ) == 1
					&& $this->user_atts['type'] == 'select'
				) {
					$element['field']['#attributes']['disabled'] = 'disabled';
				}
				// If type is multi-select, use it
				if ( $this->user_atts['type'] == 'multi-select' ) {
					$element['field']['#multiple'] = 'multiple';
				}
				// Create the form control and add it to the $returned_value
				$return .= wpv_form_control( $element );
				break;
			case 'checkboxes':
				$options = array();
				// Create the basic $element that will hold the wpv_form_control attributes
				$element = array(
					'field'	=> array(
						'#type'			=> $this->user_atts['type'],
						'#attributes'	=> array(
							'style' => $this->user_atts['style'],
							'class' => implode( ' ', $this->class_array ),
						),
						'#inline'		=> true,
						'#before'		=> '<div class="wpcf-checkboxes-group">', //we need to wrap them for js purposes
						'#after'		=> '</div>'
					)
				);
				// Build the name, id and default values depending whether we are dealing with a real parent or not
				$element['field']['#default_value'] = array( -1 );
				if ( isset( $_GET[ $this->url_param ] ) ) {
					$element['field']['#default_value'] = $_GET[ $this->url_param ];
				}
				$element['field']['#name'] = $this->url_param . '[]';
				if ( $this->ancestor_data['type'] == $this->relationship_tree_ground ) {
					$element['field']['#id'] = 'wpv_control_' . $this->user_atts['type']
						. '_' . $this->user_atts['url_param'];
				} else {
					$element['field']['#id'] = 'wpv_control_' . $this->user_atts['type']
						. '_' . $this->user_atts['url_param'] . '_' . $this->ancestor_data['type'];
				}
				// Security check: this must always be an array!
				if ( ! is_array( $element['field']['#default_value'] ) ) {
					$element['field']['#default_value'] = array( $element['field']['#default_value'] );
				}
				// Loop through the posts and add them as options
				foreach ( $this->filter_options as $pa_item ) {
					$options[ $pa_item->ID ] = array(
						'#name'				=> $this->url_param . '[]',
						'#title'			=> str_replace( '%%NAME%%', $pa_item->post_title, $this->user_atts['format'] ),
						'#value'			=> $pa_item->ID,
						'#default_value'	=> in_array( $pa_item->ID, $element['field']['#default_value'] ), // set default using option titles too
						'#inline'			=> true,
						'#after'			=> '&nbsp;&nbsp;',
						'#attributes'	=> array(
							'data-currentposttype'	=> $this->ancestor_data['type'],
							'data-triggerer'		=> 'rel-relationship',
							'style'					=> $this->user_atts['style'],
							'class'					=> implode( ' ', $this->class_array )
						),
						'#labelclass'		=> $this->user_atts['label_class'],
						'#labelstyle'		=> $this->user_atts['label_style']
					);
					if (
						'enabled' === $this->dependency_and_counters_data['dependency']
						|| 'enabled' === $this->dependency_and_counters_data['counters']
					) {
						if (
							isset( $this->dependency_and_counters_data['relationship_cache'][ $pa_item->ID ] )
							&& is_array( $this->dependency_and_counters_data['relationship_cache'][ $pa_item->ID ] )
						) {
							if (
								'enabled' === $this->dependency_and_counters_data['counters']
								&& isset( $this->dependency_and_counters_data['relationship_cache'][ $pa_item->ID ]['count'] )
							) {
								$options[ $pa_item->ID ]['#title'] = str_replace(
									'%%COUNT%%',
									$this->dependency_and_counters_data['relationship_cache'][ $pa_item->ID ]['count'],
									$options[ $pa_item->ID ]['#title']
								);
							}
						} else {
							if ( 'enabled' === $this->dependency_and_counters_data['counters'] ) {
								$options[ $pa_item->ID ]['#title'] = str_replace( '%%COUNT%%', '0', $options[ $pa_item->ID ]['#title'] );
							}
							if (
								'enabled' === $this->dependency_and_counters_data['dependency']
								& ! in_array( $pa_item->ID, $element['field']['#default_value'] )
							) {
								$options[ $pa_item->ID ]['#attributes']['#disabled'] = 'true';
								$options[ $pa_item->ID ]['#labelclass'] .= ' wpv-parametric-disabled';
								if ( 'hide' === $this->dependency_and_counters_data['empty_action'] ) {
									unset( $options[ $pa_item->ID ] );
								}
							}
						}
					}
				}
				$element['field']['#options'] = $options;
				// Calculate the control
				$return .= wpv_form_control( $element );
				break;
			case 'radio':
			case 'radios':
				// Create the basic $element that will hold the wpv_form_control attributes
				$element = array(
					'field'	=> array(
						'#type'			=> 'radios',
						'#attributes'	=> array(
							'style'					=> $this->user_atts['style'],
							'class'					=> implode( ' ', $this->class_array ),
							'data-currentposttype'	=> $this->ancestor_data['type'],
							'data-triggerer'		=> 'rel-relationship'
						),
						'#inline'		=> true
					)
				);
				$options = array();
				if ( ! empty( $this->user_atts['default_label'] ) ) {

					if ( strpos( $this->user_atts['default_label'], '%%COUNT%%' ) !== false ) {
						if ( $this->dependency_and_counters_data['auxiliar_query_count'] !== false ) {
							$this->user_atts['default_label'] = str_replace(
								'%%COUNT%%',
								$this->dependency_and_counters_data['auxiliar_query_count'],
								$this->user_atts['default_label']
							);
						} else {
							$current_post_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
							$this->user_atts['default_label'] = str_replace(
								'%%COUNT%%',
								$current_post_query->found_posts,
								$this->user_atts['default_label']
							);
						}
					}

					$options[ $this->user_atts['default_label'] ] = array(
						'#title'	=> $this->user_atts['default_label'],
						'#value'	=> 0,
						'#inline'	=> true,
						'#after'	=> '<br />'
					);
					if (
						$this->ancestor_data['type'] == $this->relationship_tree_ground
						&& 'enabled' === $this->dependency_and_counters_data['dependency']
						&& count( $this->filter_options ) == 0
						&& (
							! isset( $_GET[ $this->url_param ] )
							|| $_GET[ $this->url_param ] != ''
						)
					) {
						$options[ $this->user_atts['default_label'] ]['#disable'] = 'true';
						$options[ $this->user_atts['default_label'] ]['#labelclass'] = ' wpv-parametric-disabled';
					}
				}
				// Build the name, id and default values depending whether we are dealing with a real parent or not
				$element['field']['#default_value'] = 0;
				$element['field']['#name'] = $this->url_param;
				if (
					isset( $_GET[ $this->url_param ] )
					&& $_GET[ $this->url_param ] != 0
				) {
					$element['field']['#default_value'] = $_GET[ $this->url_param ];
				}
				if ( $this->ancestor_data['type'] == $this->relationship_tree_ground  ) {
					$element['field']['#id'] = 'wpv_control_' . $this->user_atts['type']
						. '_' . $this->user_atts['url_param'];
				} else {
					$element['field']['#id'] = 'wpv_control_' . $this->user_atts['type']
						. '_' . $this->user_atts['url_param'] . '_' . $this->ancestor_data['type'];
				}
				// Security check: this must always be a string!
				if ( is_array( $element['field']['#default_value'] ) ) {
					$element['field']['#default_value'] = reset( $element['field']['#default_value'] );
				}
				// Loop through the posts and add them as options like post_title => ID
				foreach ( $this->filter_options as $pa_item ) {
					$options[ $pa_item->ID ] = array(
						'#title'		=> str_replace( '%%NAME%%', $pa_item->post_title, $this->user_atts['format'] ),
						'#value'		=> $pa_item->ID,
						'#inline'		=> true,
						'#after'		=> '<br />',
						'#labelclass'	=> $this->user_atts['label_class'],
						'#labelstyle'	=> $this->user_atts['label_style']
					);
					if (
						'enabled' === $this->dependency_and_counters_data['dependency']
						|| 'enabled' === $this->dependency_and_counters_data['counters']
					) {
						if (
							isset( $this->dependency_and_counters_data['relationship_cache'][ $pa_item->ID ] )
							&& is_array( $this->dependency_and_counters_data['relationship_cache'][ $pa_item->ID ] )
						) {
							if (
								'enabled' === $this->dependency_and_counters_data['counters']
								&& isset( $this->dependency_and_counters_data['relationship_cache'][ $pa_item->ID ]['count'] )
							) {
								$options[ $pa_item->ID ]['#title'] = str_replace(
									'%%COUNT%%',
									$this->dependency_and_counters_data['relationship_cache'][ $pa_item->ID ]['count'],
									$options[ $pa_item->ID ]['#title']
								);
							}
						} else {
							if ( 'enabled' === $this->dependency_and_counters_data['counters'] ) {
								$options[ $pa_item->ID ]['#title'] = str_replace( '%%COUNT%%', '0', $options[ $pa_item->ID ]['#title'] );
							}
							if (
								'enabled' === $this->dependency_and_counters_data['dependency']
								&& $pa_item->ID != $element['field']['#default_value']
							) {
								$options[ $pa_item->ID ]['#disable'] = 'true';
								$options[ $pa_item->ID ]['#labelclass'] .= ' wpv-parametric-disabled';
								if ( 'hide' === $this->dependency_and_counters_data['empty_action'] ) {
									unset( $options[ $pa_item->ID ] );
								}
							}
						}
					}
				}
				$element['field']['#options'] = $options;
				// If there is only one option, disable this form control
				//This is not really needed,asin this case we are breaking above TODO review this
				if ( count( $options ) == 0 ) {
					$element['field']['#attributes']['disabled'] = 'disabled';
				}
				// Calculate the control
				$return .= wpv_form_control( $element );
				break;
			default:
				break;
		}
		return $return;
	}

	/**
	 * Get the shortcode value for the bootstrap output.
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	protected function get_bootstrap_value() {

		$walker_args = array(
			'name'				=> $this->url_param,
			'selected'			=> array( 0 ),
			'format'			=> $this->user_atts['format'],
			'style'				=> $this->user_atts['style'],
			'class'				=> '',
			'label_style'		=> $this->user_atts['label_style'],
			'label_class'		=> $this->user_atts['label_class'],
			'extra_class'		=> $this->class_array,
			'output'			=> $this->user_atts['output'],
			'type'				=> $this->user_atts['type'],
			'ancestor_type'		=> $this->ancestor_data['type'],
			'use_query_cache'	=> $this->dependency_and_counters_data['use_query_cache'],
			'dependency'		=> $this->dependency_and_counters_data['dependency'],
			'counters'			=> $this->dependency_and_counters_data['counters'],
			'empty_action'		=> $this->dependency_and_counters_data['empty_action'],
			'query_cache'		=> $this->dependency_and_counters_data['relationship_cache'],
			'auxiliar_query_count'	=> $this->dependency_and_counters_data['auxiliar_query_count']
		);

		if ( isset( $_GET[ $this->url_param ] ) ) {
			if ( is_array( $_GET[ $this->url_param ] ) ) {
				$walker_args['selected'] = $_GET[ $this->url_param ];
			} else {
				$walker_args['selected'] = array( $_GET[ $this->url_param ] );
			}
		}

		$return = '';

		switch ( $this->user_atts['type'] ) {
			case 'select':
			case 'multi-select':
				$select_args = array(
					'id'	=> 'wpv_control_' . $this->user_atts['type']
						. '_' . $this->user_atts['url_param'] . '_' . $this->ancestor_data['type'],
					'name'	=> $walker_args['name'],
					'class'	=> ( empty( $walker_args['class'] ) ) ? array() : explode( ' ', $walker_args['class'] ),
					'data-currentposttype'	=> $this->ancestor_data['type']
				);

				$minimum_options_count_to_disable = 0;
				if ( 'select' === $this->user_atts['type'] ) {
					if ( empty( $this->user_atts['default_label'] ) ) {
						$default_option = new stdClass();
						$default_option->ID = 0;
						$default_option->post_title = '';
					} else {
						$default_option = new stdClass();
						$default_option->ID = 0;
						$default_option->post_title = $this->user_atts['default_label'];
					}
					array_unshift( $this->filter_options, $default_option );
					$minimum_options_count_to_disable = 1;
				}

				if (
					$this->ancestor_data['type'] !== $this->relationship_tree_roof
					&& count( $this->filter_options ) == $minimum_options_count_to_disable
					&& $this->user_atts['type'] == 'select'
				) {
					$select_args['disabled'] = 'disabled';
				}

				$post_relationship_walker = new WPV_Walker_Post_Relationship_Select( $walker_args );

				// Backwards compatibility: for the actual parent post type, we do nto add the type name to the id attribute
				if ( $this->ancestor_data['type'] === $this->relationship_tree_ground ) {
					$select_args['id'] = 'wpv_control_' . $this->user_atts['type'] . '_' . $this->user_atts['url_param'];
				}

				$select_args['class'] = array_merge( $select_args['class'], $this->class_array );
				$select_args['class'] = array_unique( $select_args['class'] );

				switch ( $walker_args['output'] ) {
					case 'bootstrap':
						$select_args['class'][] = 'form-control';
						break;
					case 'legacy':
					default:
						$select_args['class'][] = 'wpcf-form-select form-select select';
						break;
				}

				if ( ! empty( $walker_args['style'] ) ) {
					$select_args['style'] = $walker_args['style'];
				}

				if ( 'multi-select' == $walker_args['type'] ) {
					$select_args['name'] = $walker_args['name'] . '[]';
					$select_args['multiple'] = 'multiple';
				}

				$return .= '<select';
				foreach ( $select_args as $att_key => $att_value ) {
					if (
						in_array( $att_key, array( 'style', 'class' ) )
						&& empty( $att_value )
					) {
						continue;
					}
					$return .= ' ' . $att_key . '="';
					if ( is_array( $att_value ) ) {
						$att_real_value = implode( ' ', $att_value );
						$return .= $att_real_value;
					} else {
						$return .= $att_value;
					}
					$return .= '"';
				}
				$return .=  '>';
				$return .=  call_user_func_array( array( &$post_relationship_walker, 'walk' ), array( $this->filter_options, 0 ) );
				$return .=  '</select>';
				return $return;
				break;
			case 'radio':
			case 'radios':
				if ( ! empty( $this->user_atts['default_label'] ) ) {
					$default_option = new stdClass();
					$default_option->ID = 0;
					$default_option->post_title = $this->user_atts['default_label'];
					array_unshift( $this->filter_options, $default_option );
				}
				$post_relationship_walker = new WPV_Walker_Post_Relationship_Radios( $walker_args );
				$return .= call_user_func_array( array( &$post_relationship_walker, 'walk' ), array( $this->filter_options, 0 ) );
				return $return;
				break;
			case 'checkboxes':
				$post_relationship_walker = new WPV_Walker_Post_Relationship_Checkboxes( $walker_args );
				$return .= call_user_func_array( array( &$post_relationship_walker, 'walk' ), array( $this->filter_options, 0 ) );
				return $return;
				break;
		}

		return '';
	}

}

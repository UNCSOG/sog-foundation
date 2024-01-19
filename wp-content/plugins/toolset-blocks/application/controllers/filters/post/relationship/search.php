<?php

/**
 * Search component of the filter by post relationship.
 *
 * This manages the frontend search shortcodes for this filter.
 *
 * @since m2m
 */
class WPV_Filter_Post_Relationship_Search {

	/**
	 * @var WPV_Filter_Base
	 */
	private $filter = null;

	/**
	 * @var string
	 */
	private $current_relationship_tree = '';

	private $current_filter_view_settings = array();
	private $extra_fake_view_settings = array();

	function __construct( WPV_Filter_Base $filter ) {
		$this->filter = $filter;

		//--------------------------------
		// Load hooks early, since the shortcodes registration needs to be available at init:10
		// because we localize the shortcodes GUI API JS at that time.
		//--------------------------------
		add_action( 'init', array( $this, 'load_hooks' ), 1 );
	}

	/**
	 * Load the hooks to register the filter search.
	 *
	 * @since m2m
	 */
	public function load_hooks() {
		add_filter( 'wpv_filter_wpv_register_form_filters_shortcodes', array( $this, 'register_form_filters_shortcodes' ), 0 );
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data', array( $this, 'register_shortcodes_data' ) );

		add_filter( 'wpv_filter_object_settings_for_fake_url_query_filters', array( $this, 'fake_url_query_filters' ) );
	}

	/**
	 * Register the wpv-control-post-relationship shortcode filter in the frontend search API.
	 *
	 * @param array $form_filters_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.4.0
	 */
	public function register_form_filters_shortcodes( $form_filters_shortcodes ) {
		$form_filters_shortcodes['wpv-control-post-relationship'] = array(
			'query_type_target' => 'posts',
			'query_filter_define_callback' => array( $this, 'query_filter_define_callback' ),
			'custom_search_filter_group' => __( 'Post filters', 'wpv-views' ),
			'custom_search_filter_items' => array(
				'post_relationship' => array(
					'name' => $this->filter->check_and_init_m2m()
					? __( 'Post relationship or repeatable field groups owner', 'wpv-views' )
					: __( 'Post relationship', 'wpv-views' ),
					'present' => 'post_relationship_mode',
					'params' => array()
				)
			)
		);
		return $form_filters_shortcodes;
	}

	/**
	 * Callback to create or modify the query filter after creating or editing the custom search shortcode.
	 *
	 * @param $view_id int The View ID
	 * @param $shortcode string The affected shortcode, wpv-control-post-relationship
	 * @param $attributes array The associative array of attributes for this shortcode
	 * @param $attributes_raw array The associative array of attributes for this shortcode, as collected from its dialog, before being filtered
	 *
	 * @since 2.4.0
	 */
	public function query_filter_define_callback( $view_id, $shortcode, $attributes = array(), $attributes_raw = array() ) {
		if ( ! isset( $attributes['url_param'] ) ) {
			return;
		}

		$view = WPV_View_Base::get_instance( $view_id );

		if ( null === $view ) {
			return;
		}

		try {
			$view->begin_modifying_view_settings();

			$settings_to_save = $this->query_filter_get_required_data( $attributes, $view->view_settings );

			$view->set_view_settings( $settings_to_save );

			$view->finish_modifying_view_settings();

		} catch ( WPV_RuntimeExceptionWithMessage $e ) {
			return;
		} catch ( Exception $e ) {
			return;
		}
	}

	/**
	 * Compose the View settings entry for a query filter by post relationship,
	 * given the shortcode attributes for a related frontend search filter.
	 *
	 * @param array $attributes
	 * @param array $current_object_settings The View or WPA settings
	 * @return array
	 * @since 3.0
	 */
	private function query_filter_get_required_data( $attributes, $current_object_settings ) {
		$settings_to_save = array(
			'post_relationship_mode' => array( 'url_parameter' ),
			'post_relationship_url_parameter' => $attributes['url_param']
		);

		$ancestors_attr = toolset_getarr( $attributes, 'ancestors', '' );

		if ( empty( $ancestors_attr ) ) {
			$settings_to_save['post_relationship_slug'] = '';
			$settings_to_save['post_relationship_role'] = 'child';
			return $settings_to_save;
		}

		// Make sure that HTML-entitied relationship chain strings are managed properly.
		$ancestors_attr = str_replace( '&gt;', '>', $ancestors_attr );

		$ancestors = explode( '>', $ancestors_attr );
		$related_ancestor = end( $ancestors );
		$piece_data = explode( '@', $related_ancestor );
		$related_ancestor_data = isset( $piece_data[1] )
			? explode( '.', $piece_data[1] )
			: array( '', 'parent' );

		$settings_to_save['post_relationship_slug'] = $related_ancestor_data[0];

		$post_relationship_role = ( 'parent' === $related_ancestor_data[1] )
			? 'child'
			: 'parent'; // Default for legacy relationships and for those that have no IPT

		if (
			! empty( $piece_data[0] )
			&& ! empty( $related_ancestor_data[0] )
			&& $this->filter->check_and_init_m2m()
		) {
			$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
			$relationship_definition = $relationship_repository->get_definition( $related_ancestor_data[0] );
			if (
				null != $relationship_definition
				&& null != $relationship_definition->get_intermediary_post_type()
			) {
				$returned_post_types = $this->filter->get_returned_post_types( $current_object_settings );
				$parent_post_types = $relationship_definition->get_parent_type()->get_types();
				$child_post_types = $relationship_definition->get_child_type()->get_types();
				$intermediary_post_type = $relationship_definition->get_intermediary_post_type();

				if (
					Toolset_Relationship_Role::PARENT === $related_ancestor_data[1]
					&& in_array( $piece_data[0], $parent_post_types )
				) {
					$intermediary_intersect = array_intersect( $returned_post_types, array( $intermediary_post_type ) );
					if ( count( $intermediary_intersect ) > 0 ) {
						$post_relationship_role = Toolset_Relationship_Role::INTERMEDIARY;
					} else {
						$post_relationship_role = Toolset_Relationship_Role::CHILD;
					}
				} else if (
					Toolset_Relationship_Role::CHILD === $related_ancestor_data[1]
					&& in_array( $piece_data[0], $child_post_types )
				) {
					$intermediary_intersect = array_intersect( $returned_post_types, array( $intermediary_post_type ) );
					if ( count( $intermediary_intersect ) > 0 ) {
						$post_relationship_role = Toolset_Relationship_Role::INTERMEDIARY;
					} else {
						$post_relationship_role = Toolset_Relationship_Role::PARENT;
					}
				} else if (
					Toolset_Relationship_Role::INTERMEDIARY === $related_ancestor_data[1]
					&& $intermediary_post_type === $piece_data[0]
				) {
					$parent_intersect = array_intersect( $returned_post_types, $parent_post_types );
					if ( count( $parent_intersect ) > 0 ) {
						$post_relationship_role = Toolset_Relationship_Role::PARENT;
					} else {
						$post_relationship_role = Toolset_Relationship_Role::CHILD;
					}
				}
			}
		}

		$settings_to_save['post_relationship_role'] = $post_relationship_role;

		return $settings_to_save;
	}

	/**
	 * Register the wpv-control-post-relationship shortcode attributes in the shortcodes GUI API.
	 *
	 * @param array $views_shortcodes
	 *
	 * @return array
	 *
	 * @note Some options are different when adding and when editing, mainly for BETWEEN comparisons.
	 *
	 * @since 2.4.0
	 */
	public function register_shortcodes_data( $views_shortcodes ) {
		$views_shortcodes['wpv-control-post-relationship'] = array(
			'callback' => array( $this, 'get_wpv_control_post_relationship_shortcode_data' )
		);
		return $views_shortcodes;
	}

	/**
	 * Get the wpv-control-post-relationship attributes for its GUI.
	 *
	 * @param array $parameters
	 * @param array $overrides
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	public function get_wpv_control_post_relationship_shortcode_data( $parameters = array(), $overrides = array() ) {
		$data = array(
			'name' => $this->filter->check_and_init_m2m()
				? __( 'Filter by post relationship / repeatable field groups owner', 'wpv-views' )
				: __( 'Filter by post relationship', 'wpv-views' ),
			'label' => $this->filter->check_and_init_m2m()
				? __( 'Filter by post relationship / repeatable field groups owner', 'wpv-views' )
				: __( 'Filter by post relationship', 'wpv-views' ),
			'attributes' => array(
				'display-options' => array(
					'label'		=> __( 'Display options', 'wpv-views' ),
					'header'	=> __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'type' => array(
							'label'			=> __( 'Type of control', 'wpv-views'),
							'type'			=> 'select',
							'options'		=> array(
												'select'		=> __( 'Select dropdown', 'wpv-views' ),
												'multi-select'	=> __( 'Select multiple', 'wpv-views' ),
												'radios'		=> __( 'Set of radio buttons', 'wpv-views' ),
												'checkboxes'	=> __( 'Set of checkboxes', 'wpv-views' ),
											),
							'default_force' => 'select'
						),
						'ancestors' => array(
							'label'			=> $this->filter->check_and_init_m2m()
											? __( 'Post relationship or repeatable field groups owner', 'wpv-views' )
											: __( 'Post relationship', 'wpv-views' ),
							'type'			=> 'text',
							'default'		=> '',
							'description'	=> __( 'Select which ancestors should be part of this filter.', 'wpv-views' ),
							'required'		=> true
						),
						'default_label' => array(
							'label'			=> __( 'Label for the first \'default\' option', 'wpv-views'),
							'type'			=> 'text',
							'default'		=> '',
							'placeholder'	=> __( 'Please select', 'wpv-views' ),
							'hidden'		=> true,
						),
						'format' => array(
							'label'			=> __( 'Format', 'wpv-views' ),
							'type'			=> 'text',
							'placeholder'	=> '%%NAME%%',
							'description'	=> __( 'You can use %%NAME%% or %%COUNT%% as placeholders.', 'wpv-views' ),
						),
						'relationship_order_combo' => array(
							'label'			=> __( 'Options sorting', 'wpv-views' ),
							'type'			=> 'grouped',
							'fields'		=> array(
								'orderby' => array(
									'pseudolabel'	=> __( 'Order by', 'wpv-views'),
									'type'			=> 'select',
									'default'		=> 'title',
									'options'		=> array(
														'title'	=> __( 'Post title', 'wpv-views' ),
														'id'	=> __( 'Post ID', 'wpv-views' ),
														'date'	=> __( 'Post date', 'wpv-views' ),
														'date_modified'	=> __( 'Post last modified date', 'wpv-views' ),
														'comment_count'	=> __( 'Post comment count', 'wpv-views' ),
													),
									'description'	=> __( 'Order options by this parameter.', 'wpv-views' ),
								),
								'order' => array(
									'pseudolabel'	=> __( 'Order', 'wpv-views' ),
									'type'			=> 'select',
									'default'		=> 'ASC',
									'options'		=> array(
														'ASC'	=> __( 'Ascending', 'wpv-views' ),
														'DESC'	=> __( 'Descending', 'wpv-views' ),
													),
									'description'	=> __( 'Order options in this direction.', 'wpv-views' ),
								)
							)
						),
					),
					'fields_logic' => [
						'default_label' => [
							[
								'attribute' => 'type',
								'value' => 'multi-select',
								'negative' => true,
							],
						],
					],
				),
				'style-options' => array(
					'label'		=> __( 'Style options', 'wpv-views' ),
					'header'	=> __( 'Style options', 'wpv-views' ),
					'fields' => array(
						'output' => array(
							'label'		=> __( 'Output style', 'wpv-views' ),
							'type'		=> 'radio',
							'options'		=> array(
								'legacy'	=> __( 'Raw output', 'wpv-views' ),
								'bootstrap'	=> __( 'Fully styled output', 'wpv-views' ),
							),
							'default'		=> 'bootstrap',
						),
						'input_frontend_combo' => array(
							'label'			=> __( 'Element styling', 'wpv-views' ),
							'type'			=> 'grouped',
							'fields'		=> array(
								'class' => array(
									'pseudolabel'	=> __( 'Element classnames', 'wpv-views' ),
									'type'			=> 'text',
									'description'	=> __( 'Space-separated list of classnames to apply. For example: classone classtwo', 'wpv-views' )
								),
								'style' => array(
									'pseudolabel'	=> __( 'Element inline style', 'wpv-views' ),
									'type'			=> 'text',
									'description'	=> __( 'Raw inline styles to apply. For example: color:red;background:none;', 'wpv-views' )
								),
							),
						),
						'label_frontend_combo' => array(
							'label'			=> __( 'Label styling', 'wpv-views' ),
							'type'			=> 'grouped',
							'fields'		=> array(
								'label_class' => array(
									'pseudolabel'	=> __( 'Label classnames', 'wpv-views' ),
									'type'			=> 'text',
									'description'	=> __( 'Space-separated list of classnames to apply to the labels. For example: classone classtwo', 'wpv-views' )
								),
								'label_style' => array(
									'pseudolabel'	=> __( 'Label inline style', 'wpv-views' ),
									'type'			=> 'text',
									'description'	=> __( 'Raw inline styles to apply to the labels. For example: color:red;background:none;', 'wpv-views' )
								),
							),
						),
					)
				),
				'filter-options' => array(
					'label' => __( 'Filter options', 'wpv-views' ),
					'header' => __( 'Filter options', 'wpv-views' ),
					'fields' => array(
						'url_param' => array(
							'label'			=> __( 'URL parameter to use', 'wpv-views' ),
							'type'			=> 'text',
							'default_force'	=> 'wpv-relationship-filter',
							'required'		=> true
						),
					),
				),
			),
		);
		return $data;
	}

	/**
	 * Get the filter relationship tree string, as stored in the wpv-control-post-relationship shortcode of the filter editor.
	 *
	 * @param null|array $object_settings
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	public function get_relationship_tree_string( $object_settings = null ) {
		$object_settings = ( null === $object_settings )
			? $this->filter->get_current_object_settings()
			: $object_settings;

		$filter_meta_html = toolset_getarr( $object_settings, 'filter_meta_html', '' );

		if (
			false === strpos( $filter_meta_html, '[wpv-control-set ' )
			&& false === strpos( $filter_meta_html, '[wpv-control-post-relationship ' )
		) {
			return '';
		}

		global $shortcode_tags;
		// Back up current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();

		add_shortcode( 'wpv-control-set', array( $this, 'read_relationship_tree' ) );
		add_shortcode( 'wpv-control-post-relationship', array( $this, 'read_relationship_tree' ) );

		do_shortcode( $filter_meta_html );

		$shortcode_tags = $orig_shortcode_tags;

		$current_relationship_tree = $this->current_relationship_tree;
		$this->current_relationship_tree = '';

		return $current_relationship_tree;
	}

	/**
	 * Auxiliar method to fake the callback of the wpv-control-post-relationship shortcode.
	 *
	 * @param array $atts
	 * @param string|null $content
	 *
	 * @since m2m
	 */
	public function read_relationship_tree( $atts, $content ) {
		if ( isset( $atts['ancestors'] ) ) {
			$this->current_relationship_tree = $atts['ancestors'];
		}
	}

	/**
	 * Fake a query filter by post relationship, if a related frontend search filter exists.
	 *
	 * @param array $view_settings
	 * @return array
	 * @since 3.0
	 */
	public function fake_url_query_filters( $view_settings ) {
		$filter_html = toolset_getarr( $view_settings, 'filter_meta_html', '' );
		if ( strpos( $filter_html, '[wpv-control-post-relationship' ) === false ) {
			return $view_settings;
		}

		$this->current_filter_view_settings = $view_settings;

		global $shortcode_tags;
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();
		add_shortcode( 'wpv-control-post-relationship', array( $this, 'fake_post_relationship_search_shortcode' ) );
		do_shortcode( $filter_html );
		$shortcode_tags = $orig_shortcode_tags;
		// No need to single out any generated setting:
		// they all are required for the query filter, and should override existing ones, if any
		foreach ( $this->extra_fake_view_settings as $key => $value ) {
			$view_settings[ $key ] = $value;
		}

		$filters = toolset_getarr( $view_settings, \WPV_Filter_Manager::SETTING_KEY, array() );
		$filters[ \WPV_Filter_Post_Relationship::SLUG ] = array(
			\WPV_Filter_Manager::EDITOR_MODE => \WPV_Filter_Manager::FILTER_MODE_FROM_SEARCH_FILTER,
		);
		$view_settings[ \WPV_Filter_Manager::SETTING_KEY ] = $filters;

		$this->current_filter_view_settings = array();
		$this->extra_fake_view_settings = array();

		return $view_settings;
	}

	/**
	 * Gather the required View settings for a query filter by post relationship,
	 * once we know the frontend search shortcode generating it.
	 *
	 * @param array $atts
	 * @param string|null $content
	 * @since 3.0
	 */
	public function fake_post_relationship_search_shortcode( $atts, $content ) {
		$current_object_settings = $this->current_filter_view_settings;
		$this->extra_fake_view_settings = $this->query_filter_get_required_data( $atts, $current_object_settings );
	}

}

<?php

/**
 * Class Select2 Frontend Manager will handle and replace all the select fields that have to become
 * select2 with ajax/not ajax feature
 *
 * @since 1.9.3
 */
class CRED_Frontend_Select2_Manager {

	const SELECT2_SHORTCODE_NEVER = 'never';
	const SELECT2_SHORTCODE_ALWAYS = 'always';
	const SELECT2_SHORTCODE_ONMANY = 'on_many';
	const SELECT2_PARENTS = 'select2_potential_parents';
	const SELECT2_RELATIONSHIP_PARENTS = 'select2_potential_relationship_parents';
	const SELECT2_POSTS = 'select2_potential_posts';

	const DEFAULT_PARENTS_QUERY_LIMIT = 10;
	const DEFAULT_POSTS_QUERY_LIMIT = 10;
	const DEFAULT_RELATIONSHIP_PARENTS_QUERY_LIMIT = 10;

	private static $instance;

	/**
	 * Array that contains all the form fields select that have to be replaced by select2 Ajax
	 *
	 * @var array
	 */
	protected $select2_fields_list;


	protected function __construct() {

		$this->register_select2_wp_ajax_callbacks();

		if ( ! cred_is_ajax_call() ) {
			$this->select2_fields_list = array();
			//Once all fields are elaborated i can localize select2 scripts
			add_action( 'wp_footer', array( $this, 'localize_frontend_select2_script' ) );
		}
	}

	/**
	 * Function to register the select2 main ajax callbacks
	 */
	protected function register_select2_wp_ajax_callbacks() {
		add_action( 'wp_ajax_' . self::SELECT2_PARENTS, array( $this, 'get_potential_parents' ) );
		add_action( 'wp_ajax_nopriv_' . self::SELECT2_PARENTS, array( $this, 'get_potential_parents' ) );
		add_action( 'wp_ajax_' . self::SELECT2_RELATIONSHIP_PARENTS, array(
			$this,
			'get_potential_relationship_parents',
		) );
		add_action( 'wp_ajax_nopriv_' . self::SELECT2_RELATIONSHIP_PARENTS, array(
			$this,
			'get_potential_relationship_parents',
		) );
		add_action( 'wp_ajax_' . self::SELECT2_POSTS, array( $this, 'get_potential_posts' ) );
		add_action( 'wp_ajax_nopriv_' . self::SELECT2_POSTS, array( $this, 'get_potential_posts' ) );
	}

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Used specially after a ajax call in order to update the select2 list field under js
	 * as localize_frontend_select2_script is no more called
	 *
	 * @return array
	 */
	public function get_select2_fields_list() {
		return $this->select2_fields_list;
	}

	/**
	 * Reset select2_fields_list for ajax refresh calls
	 */
	public function empty_select2_fields_list() {
		$this->select2_fields_list = array();
	}

	/**
	 * @param $html_form_id
	 * @param $field_name
	 * @param $select2_args {action,parameter,placeholder,other_args related to select2 options like multiple}
	 */
	public function register_field_to_select2_list( $html_form_id, $field_name, $select2_args ) {
		if ( ! isset( $this->select2_fields_list[ $html_form_id ] ) ) {
			$this->select2_fields_list[ $html_form_id ] = array();
		}
		if ( ! isset( $this->select2_fields_list[ $html_form_id ][ $field_name ] ) ) {
			$this->select2_fields_list[ $html_form_id ][ $field_name ] = array();
		}
		$this->select2_fields_list[ $html_form_id ][ $field_name ] = $select2_args;
	}

	/**
	 * @param string $html_form_id
	 * @param string $field_name
	 * @param int|string $value
	 * @param string $post_type
	 */
	public function set_current_value_to_registered_select2_field( $html_form_id, $field_name, $value, $post_type ) {
		if ( ! isset( $value )
			|| empty( $value ) ) {
			return;
		}
		if ( isset( $this->select2_fields_list[ $html_form_id ][ $field_name ] ) ) {
			$post = CRED_Field_Utils::get_instance()->get_parent_by_post_id( $value );
			if ( isset( $post )
				&& ! empty( $post ) ) {
				$this->select2_fields_list[ $html_form_id ][ $field_name ][ 'current_option' ] = $this->get_option_value_by_post( $post );
			}
		}
	}


	/**
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	protected function get_option_value_by_post( $post ) {
		return array( 'value' => esc_attr( $post->ID ), 'text' => sanitize_text_field( $post->post_title ) );
	}

	/**
	 * Enqueue main file manager javascript with toolset_select2 lib dependency
	 */
	public function localize_frontend_select2_script() {
		wp_localize_script( 'cred-select2-frontend-js', 'cred_select2_frontend_settings',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'select2_fields_list' => $this->select2_fields_list,
				'cred_lang' => apply_filters( 'wpml_current_language', '' )
			)
		);
	}

	/**
	 * WP_Ajax Callback to get parents elements with Join WPML translations
	 */
	public function get_potential_parents() {
		if ( ! isset( $_POST[ 'parameter' ] ) ) {
			$data = array(
				'type' => 'parameter',
				'message' => __( 'Wrong or missing post_type.', 'wp-cred' ),
			);
			wp_send_json_error( $data );
		}

		$query = cred_wrap_esc_like( $_POST[ 'q' ] );
		$post_type = sanitize_text_field( $_POST[ 'parameter' ] );
		$wpml_context = sanitize_text_field( $_POST[ 'wpml_context' ] );
		$wpml_name = sanitize_text_field( $_POST[ 'wpml_name' ] );

		if ( isset( $_POST['cred_lang'] ) ) {
			do_action( 'wpml_switch_language', sanitize_text_field( $_POST[ 'cred_lang' ] ) );
		}

		/**
		 * cred_select2_ajax_get_potential_parents_query_limit
		 *
		 * Filter used to handle the limit of get potential parents query
		 * during an ajax call by a select2 ajax component
		 *
		 * @param int $limit
		 *
		 * @since 1.9.4
		 */
		$limit = apply_filters( 'cred_select2_ajax_get_potential_parents_query_limit', self::DEFAULT_PARENTS_QUERY_LIMIT );

		$args = array();

		if ( in_array( toolset_getpost('orderBy'), array( 'date', 'title', 'ID' ) ) ) {
			$args['orderby'] = toolset_getpost('orderBy');
		}

		if ( in_array( strtoupper( toolset_getpost('order') ), array( 'ASC', 'DESC' ) ) ) {
			$args['order'] = toolset_getpost('order');
		}

		if ( '' != toolset_getpost('author') ) {
			$args['author'] = (int) toolset_getpost('author');
			if ( 0 === $args['author'] ) {
				$args['post__in'] = array( '0' );
			}
		}

		$potential_parents = CRED_Field_Utils::get_instance()->get_potential_parents( $post_type, $wpml_name, $wpml_context, $limit, $query, $args );

		try {
			$this->print_select2_json_success_output( $potential_parents );
		} catch ( Exception $e ) {
			$data = array(
				'type' => 'parents',
				'message' => $e->getMessage(),
			);
			wp_send_json_error( $data );
		}
	}

	/**
	 * WP_Ajax callback to get relationship parent using Relationship Model methods
	 */
	public function get_potential_relationship_parents() {
		if ( ! CRED_Form_Relationship::get_instance()->is_m2m_enabled() ) {
			$data = array(
				'type' => 'm2m',
				'message' => __( 'Wrong m2m is not enabled.', 'wpv-cred' ),
			);
			wp_send_json_error( $data );
		}
		$mandatory_parameters = array( 'cred_post_id', 'slug', 'role' );
		foreach ( $mandatory_parameters as $mandatory_parameter ) {
			if ( ! isset( $_POST[ $mandatory_parameter ] ) ) {
				$data = array(
					'type' => $mandatory_parameter,
					'message' => sprintf( __( 'Wrong or missing %s.', 'wpv-cred' ), $mandatory_parameter ),
				);
				wp_send_json_error( $data );
			}
		}

		if ( isset( $_POST['cred_lang'] ) ) {
			do_action( 'wpml_switch_language', sanitize_text_field( $_POST[ 'cred_lang' ] ) );
		}

		/**
		 * cred_select2_ajax_get_potential_relationship_parents_query_limit
		 *
		 * Filter used to handle the limit of get potential relationship parents query (different by legacy potential parents query)
		 * during an ajax call by a select2 ajax component
		 *
		 * @param int $limit
		 *
		 * @since 2.0
		 */
		$limit = apply_filters( 'cred_select2_ajax_get_potential_relationship_parents_query_limit', self::DEFAULT_RELATIONSHIP_PARENTS_QUERY_LIMIT );

		$args = array();

		//Handle the number of results
		$args[ 'items_per_page' ] = $limit;

		$query = cred_wrap_esc_like( $_POST[ 'q' ] );
		//Args Search
		$args[ 'search_string' ] = $query;

		$wp_query_override = array();

		if ( in_array( toolset_getpost('orderBy'), array( 'date', 'title', 'ID' ) ) ) {
			$wp_query_override['orderby'] = toolset_getpost('orderBy');
		}

		if ( in_array( strtoupper( toolset_getpost('order') ), array( 'ASC', 'DESC' ) ) ) {
			$wp_query_override['order'] = toolset_getpost('order');
		}

		if ( '' != toolset_getpost('author') ) {
			$wp_query_override['author'] = (int) toolset_getpost('author');
			if ( 0 === $wp_query_override['author'] ) {
				$wp_query_override['post__in'] = array( '0' );
			}
		}

		if ( count( $wp_query_override ) > 0 ) {
			$args['wp_query_override'] = $wp_query_override;
		}

		$check_distinct_relationships = 'edit' !== toolset_getpost( 'form_type' );

		//Post ID where to attach the Parent Element to
		$referrer_role_post_id = (int) $_POST[ 'cred_post_id' ];
		$connect_to_as_object = Toolset_Element::get_instance( 'posts', $referrer_role_post_id );

		//Relationship slug
		$relationship_slug = sanitize_text_field( $_POST[ 'slug' ] );
		$relationship_definition = CRED_Form_Relationship::get_instance()->get_definition_by_relationship_slug( $relationship_slug );

		//Target Role (opposite of $for_element/$connect_to so if the post_id is Child the role should be 'parent')
		$relationship_target_role = sanitize_text_field( $_POST[ 'role' ] );
		$role_object = Toolset_Relationship_Role::role_from_name( $relationship_target_role );

		$potential_associable_parent_query = new CRED_Form_Potential_Associable_Parent_Query( new \OTGS\Toolset\Common\Relationships\API\Factory() );
		$potential_parents = $potential_associable_parent_query->get_potential_associable_parent_result(
			$relationship_definition, $role_object, $connect_to_as_object, $args, $check_distinct_relationships
		);

		try {
			$this->print_select2_json_success_output( $potential_parents );
		} catch ( Exception $e ) {
			$data = array(
				'type' => 'parents',
				'message' => $e->getMessage(),
			);
			wp_send_json_error( $data );
		}
	}


	/**
	 * Get array of Objects list and print select2 compatible output
	 *
	 * @param WP_Post[]|IToolset_Post[] $wp_items
	 *
	 * @throws Exception
	 */
	protected function print_select2_json_success_output( $wp_items ) {
		$output_results = array();
		if ( is_array( $wp_items ) && ! empty( $wp_items ) ) {
			foreach ( $wp_items as $item ) {
				if ( $item instanceof IToolset_Post ) {
					$output_results[] = array(
						'text' => $item->get_title(),
						'id' => $item->get_id(),
					);
				} elseif ( $item instanceof WP_Post ) {
					$output_results[] = array(
						'text' => $item->post_title,
						'id' => $item->ID,
					);
				} else {
					throw new Exception( __( 'Wrong items for select2', 'wp-cred' ) );
				}
			}
		}

		wp_send_json_success( $output_results );
	}

	/**
	 * WP_Ajax Callback to get posts elements
	 */
	public function get_potential_posts() {
		if ( ! isset( $_POST[ 'parameter' ] ) ) {
			$data = array(
				'type' => 'parameter',
				'message' => __( 'Wrong or missing post_type.', 'wp-cred' ),
			);
			wp_send_json_error( $data );
		}

		$query = sanitize_text_field( $_POST[ 'q' ] );
		$post_type = sanitize_text_field( $_POST[ 'parameter' ] );

		if ( isset( $_POST['cred_lang'] ) ) {
			do_action( 'wpml_switch_language', sanitize_text_field( $_POST[ 'cred_lang' ] ) );
		}

		/**
		 * cred_select2_ajax_get_potential_posts_query_limit
		 *
		 * Filter used to handle the limit of get potential parents query
		 * during an ajax call by a select2 ajax component
		 *
		 * @param int $limit
		 *
		 * @since 1.9.4
		 */
		$limit = apply_filters( 'cred_select2_ajax_get_potential_posts_query_limit', self::DEFAULT_POSTS_QUERY_LIMIT );

		$potential_posts = CRED_Field_Utils::get_instance()->get_potential_posts( $post_type, $limit, $query );

		try {
			$this->print_select2_json_success_output( $potential_posts );
		} catch ( Exception $e ) {
			$data = array(
				'type' => 'parents',
				'message' => $e->getMessage(),
			);
			wp_send_json_error( $data );
		}
	}

	/**
	 * Core Method that elaborate the decision to use select2 or not
	 * depending by the shortcode attribute {always,never,on_many} and if there are many options
	 *
	 * @param string $shortcode use_select2 field shortcode: {always,never,on_many}
	 * @param bool $many_options
	 *
	 * @return bool
	 */
	public function use_select2( $shortcode, $many_options = true ) {
		//Default behavior when attribute use_select2 is not present in the shortcode is intended as null
		if ( ! isset( $shortcode )
			&& $many_options ) {
			return true;
		}

		if ( $shortcode == self::SELECT2_SHORTCODE_NEVER ) {
			return false;
		}

		if ( $shortcode == self::SELECT2_SHORTCODE_ALWAYS ) {
			return true;
		}

		return ( $many_options );
	}

	/**
	 * @param string $field_type
	 *
	 * @return bool
	 */
	public function is_valid_field_type_for_select2( $field_type ) {
		return ( $field_type === 'select' || $field_type === 'multiselect' );
	}
}

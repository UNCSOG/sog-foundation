<?php

/**
 * Utils API init
 */
final class WPDD_Utils {

	public static $default_page_template;

	public static $is_integrated_theme = null;

	public static $calculated_subclasses = array();
	public static $reflected_classes_instances = array();

	public static final function init() {
		add_action( 'ddl-before_init_layouts_plugin', array( __CLASS__, 'add_hooks' ), 1 );
	}

	public static final function add_hooks() {

		self::$default_page_template = apply_filters( 'ddl-wp-default-page-template', self::buildPageTemplateFileName() );

		/*
		 * @param $bool, boolean default to false
		 * @param $cell, object
		 * @param $property, string
		 */
		add_filter( 'ddl-is_cell_and_of_type', array( __CLASS__, 'isCellAndOfType' ), 99, 2 );

		add_filter( 'ddl-toolset_cell_types', array( __CLASS__, 'toolsetCellTypes' ), 99, 1 );
		/*
		 * @param $layout, mixed, false or layout slug
		 * @param $post_id, integer
		 */
		add_filter( 'ddl-page_has_layout', array( __CLASS__, 'page_has_layout' ), 99, 1 );

		add_filter( 'ddl-page_has_private_layout', array( __CLASS__, 'page_has_private_layout' ), 99, 1 );

		add_filter( 'ddl-is_private_layout_in_use', array( __CLASS__, 'is_private_layout_in_use' ), 99, 1 );

		add_filter( 'ddl-template_have_layout', array( __CLASS__, 'template_have_layout' ), 99, 2 );

		add_filter( 'ddl-this_page_template_have_layout', array( __CLASS__, 'this_page_template_have_layout' ), 99, 1 );
		/*
		 * @param $value, array default to null
		 */
		add_filter( 'ddl-get_all_layouts_settings', array( __CLASS__, 'get_all_settings' ), 99, 1 );

		add_filter( 'ddl-filter_layouts_by_cell_type', array( __CLASS__, 'filter_layouts_by_cell_type' ), 10, 2 );

		/*
		 * @param $bool, boolean default to false
		 */
		add_action( 'ddl-check-page_templates_have_layout', array( __CLASS__, 'page_templates_have_layout' ), 10, 1 );

		add_filter( 'ddl_get_page_template', array( __CLASS__, 'get_page_template' ), 10, 1 );

		add_filter( 'ddl-layout_is_parent', array( __CLASS__, 'layout_is_parent' ), 10, 1 );

		add_filter( 'ddl-get_current_integration_template_path', array(
			__CLASS__,
			'get_current_integration_template_path'
		), 10, 1 );

		add_filter( 'assign_layout_to_post_object', array( __CLASS__, 'clear_cache' ), 999, 5 );

		add_filter( 'remove_layout_assignment_to_post_object', array( __CLASS__, 'clear_cache' ), 999, 5 );

		add_filter( 'ddl-is_layout_assigned', array( __CLASS__, 'is_layout_assigned' ), 10, 2 );

		add_filter( 'ddl-get_post_type_items_assigned_count', array(
			__CLASS__,
			'get_post_type_items_assigned_count'
		), 10, 1 );

		add_filter( 'ddl-is_integrated_theme', array( __CLASS__, 'is_integrated_theme' ), 9, 1 );

		add_filter( 'ddl-disabled_cells_on_content_layout', array(
			__CLASS__,
			'disabled_cells_on_content_layout'
		), 999, 1 );

		add_filter( 'ddl-is_wpml_active_and_configured', array( __CLASS__, 'is_wpml_active_and_configured' ), 10, 1 );

		add_filter( 'ddl-get_layout_id_by_slug', array( __CLASS__, 'get_layout_id_by_slug' ), 10, 2 );

		add_filter( 'ddl-minify_html', array( __CLASS__, 'minify_html' ), 10, 1 );

		add_filter( 'ddl-remove_unwanted_breaks', array( __CLASS__, 'remove_unwanted_breaks' ), 10, 1 );

		add_filter( 'ddl-remove_spaces_between_tags', array( __CLASS__, 'remove_spaces_between_tags' ), 10, 1 );

		add_filter( 'ddl-remove_empty_paragraphs', array( __CLASS__, 'remove_empty_paragraphs' ), 10, 1 );

		add_filter( 'ddl-filter_get_layout_cells_by_type', array(
			__CLASS__,
			'filter_get_layout_cells_by_type'
		), 10, 3 );

		add_filter( 'ddl-filter_get_cell_content_property', array(
			__CLASS__,
			'filter_get_cell_content_property'
		), 10, 3 );

		add_filter( 'ddl-remove_extra_lines', array( __CLASS__, 'remove_extra_lines' ), 10, 1 );

		add_filter( 'toolset_register_compatibility_classes', array(
			__CLASS__,
			'register_compatibility_classes'
		), 99, 1 );

		add_filter( 'ddl-get_layout_posts_ids', array( __CLASS__, 'get_layout_posts_ids' ), 10, 1 );

		add_filter( 'ddl-get_layout_as_php_object', array( __CLASS__, 'get_layout_as_php_object' ), 10, 4 );

		add_action( 'ddl-delete_layouts_template_dirt', array( __CLASS__, 'delete_layouts_template_dirt' ), 10, 2 );

		/**
		 * Group of filters to determine Woocommerce status and their callback calls with:
		 * 1. is active works sync and async
		 * 2. is active and one of its pages (generic), works only in sync calls (uses WC conditional native)
		 * 3. it is "shop" page: works sync and async (queries DB directly)
		 * 4. it is product, product category or product tag archive: works only sync (uses WC conditional native)
		 * 5. single product page (uses WC conditional native)
		 */
		add_filter( 'ddl-is_woocommerce_enabled', array( __CLASS__, 'is_woocommerce_enabled' ), 10, 1 );
		add_filter( 'ddl-is_woocommerce', array( __CLASS__, 'is_woocommerce' ), 10, 1 );
		add_filter( 'ddl-is_woocommerce_shop', array( __CLASS__, 'is_woocommerce_shop' ), 10, 1 );
		add_filter( 'ddl-is_woocommerce_archive', array( __CLASS__, 'is_woocommerce_archive' ), 10, 1 );
		add_filter( 'ddl-is_woocommerce_product', array( __CLASS__, 'is_woocommerce_product' ), 10, 1 );

		/**
		 * Filter added to determine if a call is meant to re-render one of FE editor cells after a change has been applied
		 */
		add_filter( 'ddl-is_front_end_editor_re_render', array( __CLASS__, 'is_front_end_editor_re_render' ), 10, 1 );

		/**
		 * Make sure that Woocommerce Archive title is correct when using [wpv-archive-title] shortcode
		 */
		add_filter( 'get_the_archive_title', array( __CLASS__, 'fix_woocomerce_archive_title' ), 10, 1 );

		/**
		 * Implements callback to apply_filters( 'ddl-is_layout_private', $bool = false, $layout_id = 0 );
		 */
		add_filter( 'ddl-is_layout_private', array(__CLASS__, 'is_layout_private'), 10, 2 );

		/**
		 * Implements callback to apply_filters( 'ddl-layout_at_least_one_single_assignment', $layout_id = 0 );
		 */
		add_filter( 'ddl-layout_at_least_one_single_assignment', array(__CLASS__, 'layout_assigned_count'), 10, 1 );
	}

	/**
	 * @param $post_id
	 * @param $layout_slug
	 * Helps you remove '_layouts_template' data in case layout is not there anymore
	 */
	public static function delete_layouts_template_dirt( $post_id, $layout_slug ) {
		delete_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, $layout_slug );
	}

	/**
	 * @param $layout_id
	 * @param bool $preset
	 * @param bool $as_array
	 * @param bool $clear_cache
	 *
	 * @return mixed|null|void
	 */
	public static final function get_layout_as_php_object( $layout_id, $preset = false, $as_array = false, $clear_cache = false ) {
		$layout         = apply_filters( 'ddl-get_layout_settings', $layout_id, $as_array, $clear_cache );
		$layout_factory = new WPDD_json2layout( $preset );
		$layout         = $layout_factory->json_decode( $layout, $as_array );

		return $layout;
	}

	/**
	 * Hook up get_the_archive_title function when rendering changed element and set correct WooCommerce archive title
	 * from localized variable.
	 *
	 * @param $title
	 *
	 * @return $title
	 */
	public static function fix_woocomerce_archive_title( $title ) {

		if ( apply_filters( 'ddl-is_woocommerce_enabled', false ) === false ) {
			return $title;
		}

		if ( isset( $_POST['toolset_editor'] ) && $_POST['action'] === 'render_element_changed' && ( isset( $_POST['woocommerce_archive_title'] ) ) ) {
			$title = $_POST['woocommerce_archive_title'];
		}

		return $title;

	}

	/**
	 * @param bool $bool
	 *
	 * @return bool
	 */
	public static function is_front_end_editor_re_render( $bool = false ) {
		if ( isset( $_POST['toolset_editor'] ) && $_POST['toolset_editor'] === "true" && isset( $_POST['action'] ) && $_POST['action'] === 'render_element_changed' ) {
			$bool = true;
		} else {
			$bool = false;
		}

		return $bool;
	}

	/**
	 * WooCommerce / Generic utility methods
	 * returns true if the Woocommerce plugin is active
	 */
	public static function is_woocommerce_enabled( $bool = false ) {

		if ( function_exists( 'is_woocommerce' ) ) {
			$bool = true;
		} else {
			$bool = false;
		}

		return $bool;
	}

	public static function is_woocommerce( $bool = false ) {

		if ( self::is_woocommerce_enabled() ) {
			$bool = is_woocommerce();
		}

		return $bool;
	}

	/**
	 * @param bool $mixed
	 *
	 * @return bool|mixed
	 * If it's shop page and a Layout is assigned to it, it returns it, else returns false
	 */
	public static function is_woocommerce_shop( $mixed = false ) {
		if ( self::is_woocommerce_enabled() ) {
			// Check if 'Shop' page has a separate layout assigned.
			$shop_page_id    = get_option( 'woocommerce_shop_page_id' );
			$layout_selected = get_post_meta( $shop_page_id, WPDDL_LAYOUTS_META_KEY, true );
		}

		// If it's 'Shop' page and has a separate layout assigned.
		if ( isset( $layout_selected ) && $layout_selected && function_exists( 'is_shop' ) && is_shop() ) {
			$mixed = $layout_selected;
		} else {
			$mixed = false;
		}

		return $mixed;
	}

	/**
	 * @param bool $bool
	 *
	 * @return bool
	 * If it's a Woocommerce product page and plugin active returns true
	 */
	public static function is_woocommerce_product( $bool = false ) {
		if ( self::is_woocommerce_enabled() && function_exists( 'is_product' ) && is_product() ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param bool $bool
	 *
	 * @return bool
	 * If Woocommerce active and it's a Woocommerce archive, returns true
	 */
	public static function is_woocommerce_archive( $bool = false ) {
		if ( self::is_woocommerce_enabled() && ( ( function_exists( 'is_product_category' ) && is_product_category() ) || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) || ( function_exists( 'is_product_tag' ) && is_product_tag() ) ) ) {
			$bool = true;
		} else {
			$bool = false;
		}

		return $bool;
	}

	/**
	 * From layout ID get list of all posts that are using layout under passed ID
	 *
	 * @param $layout_id
	 *
	 * @return array
	 */
	public static function get_layout_posts_ids( $layout_id ) {

		$list_of_posts = array();

		// Get layout slug/post_name from ID
		$layout_slug = self::get_post_property_from_ID( $layout_id );
		if ( $layout_slug === null ) {
			return $list_of_posts;
		}

		// Get post ID-s
		$assigned_to_posts = self::select_posts_ids_for_layout( $layout_slug );
		if ( count( $assigned_to_posts ) === 0 ) {
			return $list_of_posts;
		}

		if ( is_array( $assigned_to_posts ) ) {
			foreach ( $assigned_to_posts as $one_id ) {
				$list_of_posts[] = (int) $one_id['post_id'];
			}
		}

		return $list_of_posts;
	}

	/**
	 * SQL query to get list of post IDs that are using layout under specific name
	 * We are using direct sql query to save resources
	 *
	 * @param $layout_name
	 *
	 * @return array
	 */
	public static function select_posts_ids_for_layout( $layout_name ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s", WPDDL_LAYOUTS_META_KEY, $layout_name ), ARRAY_A );
	}

	public static function register_compatibility_classes( $registered_classes = array() ) {

		$compatibility_manager = new DDL_Compatibility_Manager();
		$get_classes_to_load   = $compatibility_manager->classes_to_load();

		return array_merge( $registered_classes, $get_classes_to_load );

	}

	public static function get_post_type_items_assigned_count( $post_type ) {
		global $wpdb;
		$query = $wpdb->prepare( "SELECT COUNT(wposts.ID) FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '%s' AND wposts.post_type = '%s' ORDER BY wpostmeta.meta_value DESC", WPDDL_LAYOUTS_META_KEY, $post_type );

		return $wpdb->get_var( $query );
	}

	/**
	 * @param null $list
	 * @param array $args
	 *
	 * @return array|mixed|void
	 * @throws Exception
	 * @throws InvalidArgumentException
	 *
	 * A callback static method that uses WPDD_json2layout and WPDD_element and subclasses to filter arrays of layouts by generic property/value pairs.
	 * This means that callable object constructor should accept as its first argument an instance of WPDD_json2layout class to convert each layout json
	 * data from the database into a WPDD_layout php object.
	 *
	 * Example of a valid callable class:
	 *
	 * class WPDD_FilterLayoutsByPropertyValue{
	 *         protected $value = 'cred-cell';
	 *         private $parser;
	 *
	 *        public function __construct( WPDD_json2layout $parser, $args = array() ) {
	 *            $this->parser          = $parser;
	 *            $this->value           = isset( $args['value'] ) ? $args['value'] : $this->value ;
	 *         }
	 *
	 *        public function filter_layouts_has_cell_of_type( $json ){
	 *            $layout = $this->parser->json_decode( $raw, false );
	 *            return $layout->has_cell_of_type( $this->value, false );
	 *        }
	 *    }
	 *
	 **/
	public static function filter_layouts_by_cell_type( /* php prevent warnings */
		$list = null, $args = array()
	) {

		$default = array(
			'property'        => 'cell_type',
			'value'           => 'cred-cell',
			'already_encoded' => true,
			'as_object'       => true,
			'class_callback'  => 'WPDD_FilterLayoutsByPropertyValue',
			'method_callback' => 'filter_layouts_has_cell_of_type',
			'include_parents' => false,
			'include_private' => false,
			'full_object'     => false
		);

		$args = wp_parse_args( $args, $default );

		if ( ! $args['already_encoded'] && ( ! $args['include_private'] || ! $args['include_private'] ) ) {
			throw new InvalidArgumentException( sprintf( 'When $include_private: %s and $include_parents: %s arguments are set to false, JSON data       must be already encoded, thus $already_encoded: %s argument must be set to true.', $args['include_private'], $args['include_parents'], $args['already_encoded'] ) );
		}

		if ( ( ! $args['already_encoded'] && $args['as_object'] ) || ( $args['already_encoded'] && ! $args['as_object'] ) ) {
			throw new InvalidArgumentException( sprintf( '$already_encoded: %s and $as_object: %s parameters should be coherent.', $args['already_encoded'], $args['as_object'] ) );
		}

		$layouts = self::get_all_layouts_json_by_status();

		if ( $args['already_encoded'] === true ) {
			$layouts = array_map( 'json_decode', $layouts );
		}

		if ( $args['already_encoded'] && $args['include_private'] === false ) {
			$layouts = array_filter( $layouts, array( __CLASS__, 'layout_object_is_template' ) );
		}

		if ( $args['already_encoded'] && $args['include_parents'] === false ) {
			$layouts = array_filter( $layouts, array( __CLASS__, 'layout_object_is_not_parent' ) );
		}

		$parser = new WPDD_json2layout( false, new WPDD_FactoryManager(), WPDD_RegisteredCellTypesFactory::build() );


		$filter   = new $args['class_callback']( $parser, $args );
		$callable = array( $filter, $args['method_callback'] );

		if ( is_callable( $callable ) ) {
			$layouts = array_filter( $layouts, $callable );
		} else {
			throw new Exception( sprintf( '%s::%s is not a valid callable class method', $args['class_callback'], $args['method_callback'] ) );
		}


		if ( ! isset( $args['full_object'] ) || $args['full_object'] === false ) {
			$layouts = array_map( array( __CLASS__, 'remove_unwanted_properties' ), $layouts );
		}

		return $layouts;
	}


	public static final function get_layout_id_by_slug( $layout_id, $slug ) {
		$layout_meta = self::get_layout_by_slug( $slug );

		if ( $layout_meta != null ) {
			$layout_id = $layout_meta->id;
		}

		return $layout_id;
	}

	/**
	 * Filter callback to get an array of cells of a given type, given a layout ID.
	 *
	 * @param array $cells
	 * @param int $layout_id
	 * @param string $cell_type
	 *
	 * @return array
	 *
	 * @since 2.0.0
	 */
	public static function filter_get_layout_cells_by_type(/* php prevent warnings */
		$cells = array(), $layout_id = 0, $cell_type = ''
	) {
		$cells = self::get_layout_cells_by_type( $layout_id, $cell_type );

		return $cells;
	}

	/**
	 * Method to get an array of cells of a given type, given a layout ID.
	 *
	 * @param int $layout_id
	 * @param string $cell_type
	 *
	 * @return array
	 *
	 * @since 2.0.0
	 */
	public static final function get_layout_cells_by_type( $layout_id = 0, $cell_type = '' ) {

		$cells = array();

		if ( empty( $layout_id ) || empty( $cell_type ) ) {
			return $cells;
		}

		$layout_json = WPDD_Layouts::get_layout_settings( $layout_id );
		$builder     = new WPDD_json2layout();
		$layout      = $builder->json_decode( $layout_json );
		if ( is_object( $layout ) && method_exists( $layout, 'get_cells_of_type' ) ) {
			$cells = $layout->get_cells_of_type( $cell_type );
		}

		return $cells;

	}

	/**
	 * Filter callback to get a content property from a layout cell.
	 *
	 * @param string $value
	 * @param object $cell
	 * @param string $property_name
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */
	public static function filter_get_cell_content_property(/* php prevent warnings */
		$value, $cell = null, $property_name = ''
	) {
		$value_candidate = self::get_cell_content_property( $cell, $property_name );
		if ( $value_candidate !== false ) {
			$value = $value_candidate;
		}

		return $value;
	}

	/**
	 * Method to get a content property from a layout cell.
	 *
	 * @param object $cell
	 * @param string $property_name
	 *
	 * @return string|bool
	 *
	 * @since 2.0.0
	 */
	public static function get_cell_content_property( $cell = null, $property_name = '' ) {

		$value = false;

		if ( is_null( $cell ) || empty( $property_name ) ) {
			return $value;
		}

		if ( is_object( $cell ) && method_exists( $cell, 'get' ) ) {
			$value = $cell->get( $property_name );
		}

		return $value;

	}

	public static function remove_unwanted_properties( $layout ) {
		return (object) array( 'id' => (int) $layout->id, 'slug' => $layout->slug, 'name' => $layout->name );
	}

	public static function is_wpml_active_and_configured( $bool = false /* make php happy*/ ) {
		return Toolset_WPML_Compatibility::get_instance()->is_wpml_active_and_configured();
	}

	public static function disabled_cells_on_content_layout( $cells = array() ) {

		// cells disabled by default
		$disabled_cells = array(
			"post-loop-views-cell",
			"child-layout",
			"cell-post-content",
			"comments-cell",
			"widget-cell",
			"cell-widget-area",
			"menu-cell"
		);

		$all_disabled_cells = array_merge( $disabled_cells, $cells );

		return $all_disabled_cells;

	}

	public static function remove_unwanted_breaks( $html = '' ) {
		$html = preg_replace( '/[\r\n]+/', ' ', $html );
		$html = preg_replace( '/[\t]+/', ' ', $html );

		return $html;
	}

	public static function minify_html( $html = '' ) {
		$html = str_replace( PHP_EOL, ' ', $html );
		$html = self::remove_unwanted_breaks( $html );
		$html = self::clean_html_output_from_extra_spaces( $html );

		return $html;
	}

	public static function remove_extra_lines( $content = '' ) {
		$content = str_replace( "\n", ' ', rtrim( ltrim( $content ) ) );
		$content = str_replace( "\t", ' ', rtrim( ltrim( $content ) ) );
		$content = str_replace( "\r", ' ', rtrim( ltrim( $content ) ) );
		$i       = 0;
		while ( $i < 5 ) {
			$content = str_replace( '  ', ' ', $content );
			$i ++;
		}

		return $content;
	}

	/*
	 * Remove whitespace and \n from accordion title HTML, to avoid issues with unwanted <b> and <p>
	 */
	public static function clean_html_output_from_extra_spaces( $content, $cell = null ) {
		$content = self::remove_extra_lines( $content );
		$content = self::remove_spaces_between_tags( $content );

		return $content;
	}

	public static function remove_spaces_between_tags( $content = '' ) {
		$content = preg_replace( '~>\s+<~', '><', $content );
		$content = preg_replace( '/\s\s+/', ' ', $content );
		$content = preg_replace( '~>\s*\n\s*<~', '><', $content );

		return $content;
	}

	public static function remove_empty_paragraphs( $content = '' ) {
		return preg_replace( "/<p[^>]*>[\s|&nbsp;]*<\/p>/", '', $content );
	}

	public static final function is_integrated_theme( $bool = false /* php argument missing waring prevent */ ) {
		if ( null !== self::$is_integrated_theme ) {
			return self::$is_integrated_theme;
		}
		self::$is_integrated_theme = ( null !== self::introspect( 'WPDDL_Theme_Integration_Setup_Abstract' ) );

		return self::$is_integrated_theme;
	}

	public static final function clear_cache( $ret, $post_id, $layout_slug, $template = null, $meta = '' ) {

		if ( $ret === false ) {
			return $ret;
		}

		clean_post_cache( $post_id );

		return $ret;
	}

	public static final function is_wp_post_object( $post ) {
		return 'object' === gettype( $post ) && get_class( $post ) === 'WP_Post';
	}

	public static function content_template_cell_has_body_tag( $cells ) {

		if ( ! is_array( $cells ) || count( $cells ) === 0 ) {
			return '';
		}

		$ret = '';

		foreach ( $cells as $cell ) {
			if ( method_exists( $cell, 'check_if_cell_renders_post_content' ) && $cell->check_if_cell_renders_post_content() ) {
				$ret = 'cell-content-template';
				break;
			} else {
				$ret = '';
			}

		}

		return $ret;
	}

	public static function is_woocommerce_page() {
		return function_exists( 'is_woocommerce' ) && is_woocommerce();
	}

	public static function layout_assigned_count( $layout_id ) {
		global $wpdb;
		$layout_name = WPDD_Layouts_Cache_Singleton::get_name_by_id( $layout_id );
		$count       = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(meta_id) FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s", WPDDL_LAYOUTS_META_KEY, $layout_name ) );

		return $count && $count > 0;
	}

	public static function layout_assigned_count_num( $layout_id ) {
		global $wpdb;
		$layout_name = WPDD_Layouts_Cache_Singleton::get_name_by_id( $layout_id );
		$count       = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(meta_id) FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s", WPDDL_LAYOUTS_META_KEY, $layout_name ) );

		return (int) $count;
	}

	public static function is_layout_assigned( $bool, $layout_id ) {

		if ( ! $layout_id ) {
			return $bool;
		}

		$archives = apply_filters( 'ddl-get_layout_loops', $layout_id );
		$single   = self::layout_assigned_count( $layout_id );
		$types    = apply_filters( 'ddl-get_layout_post_types', $layout_id );

		return $single || count( $archives ) > 0 || count( $types ) > 0;
	}

	public static function visual_editor_cell_has_wpvbody_tag( $cells ) {
		if ( ! is_array( $cells ) || count( $cells ) === 0 ) {
			return '';
		}

		$ret = '';

		foreach ( $cells as $cell ) {
			$content = $cell->get_content();

			if ( ! $content ) {
				$ret = '';
			} else {
				$content = (object) $content;
				if ( self::content_content_has_views_tag( $content ) ) {
					$ret = 'cell-content-template';
					break;
				} else {
					$ret = '';
				}
			}
		}

		return $ret;

	}

	public static function content_content_has_views_tag( $content ) {

		if ( ! $content ) {
			return false;
		}

		$content = (object) $content;

		if ( property_exists( $content, 'content' ) === false ) {
			return false;
		}

		$checks = apply_filters( 'ddl-do-not-apply-overlay-for-post-editor', array(
			'wpv-post-body',
			'wpv-woo-display-tabs'
		) );

		$bool = false;

		foreach ( $checks as $check ) {
			if ( strpos( $content->content, $check ) !== false ) {
				$bool = true;
				break;
			}
		}

		return apply_filters( 'ddl-show_post_edit_page_editor_overlay', $bool, __CLASS__ );
	}


	public static function get_post_property_from_ID( $id, $property = 'post_name' ) {
		if ( is_nan( $id ) || ! $id ) {
			return null;
		}

		$post = get_post( $id );

		if ( is_object( $post ) === false ) {
			return null;
		}

		if ( get_class( $post ) !== 'WP_Post' ) {
			return null;
		}

		return $post->{$property};
	}

	public static function string_contanins_strings( $string = '', $strings = array() ) {

		if ( $string === '' ) {
			return false;
		}

		if ( count( $strings ) === 0 ) {
			return false;
		}

		$bool = false;

		foreach ( $strings as $check ) {
			if ( strpos( $string, $check ) !== false ) {
				$bool = true;
				break;
			}
		}

		return $bool;
	}

	public static function get_layout_id_from_post_name( $layout_name ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_name=%s", WPDDL_LAYOUTS_POST_TYPE, $layout_name ) );
	}

	/**
	 * @param $class
	 * @param string $method
	 *
	 * @return mixed|null
	 */
	public static function invoke_child_method( $class, $method = 'get_instance' ) {

		$sub = self::introspect( $class );

		if ( null === $sub ) {
			return null;
		}

		$reflected_classes_instances = self::$reflected_classes_instances;
		if ( array_key_exists( $sub, $reflected_classes_instances ) ) {
			$r = $reflected_classes_instances[ $sub ];
			$instance = $r->getMethod( $method )->invoke( null );
			return $instance;
		}

		$r        = new ReflectionClass( $sub );
		$instance = $r->getMethod( $method )->invoke( null );
		return $instance;
	}

	/**
	 * @param $class
	 *
	 * @return mixed|null
	 */
	public static function introspect( $class ) {
		$calculated_subclasses = self::$calculated_subclasses;
		if ( array_key_exists( $class, $calculated_subclasses ) ) {
			$sub = $calculated_subclasses[ $class ];
		} else {
			$sub = self::getSubclassesOf( $class );
		}
		if ( count( $sub ) > 0 ) {
			return $sub[0];
		}

		return null;
	}

	public static function getSubclassesOf( $parent ) {
		$calculated_subclasses = self::$calculated_subclasses;
		if ( array_key_exists( $parent, $calculated_subclasses ) ) {
			return $calculated_subclasses[ $parent ];
		}

		$reflected_classes_instances = self::$reflected_classes_instances;
		$result = array();
		foreach ( get_declared_classes() as $class ) {
			if ( ! is_subclass_of( $class, $parent ) ) {
				continue;
			}
			$reflection = new ReflectionClass( $class );
			if ( $reflection->isAbstract() === false ) {
				$result[] = $class;
				$reflected_classes_instances[ $class ] = $reflection;
			}
		}

		$calculated_subclasses[ $parent ] = $result;
		self::$calculated_subclasses = $calculated_subclasses;
		self::$reflected_classes_instances = $reflected_classes_instances;

		return $result;
	}

	public static function get_current_integration_template_path( $tpl ) {

		$router = self::invoke_child_method( 'WPDDL_Integration_Theme_Template_Router_Abstract', 'get_instance' );

		if ( $router ) {
			$path = $router->locate_template( array( $tpl ), false, false );
		} else {
			$path = locate_template( array( $tpl ), false, false );
		}

		return is_file( $path ) ? $path : null;
	}

	public static function getPageDefaultTemplate() {
		return self::$default_page_template;
	}

	public static function buildPageTemplateFileName() {
		$files = wp_get_theme()->get_files( 'php', 1, true );

		if ( array_key_exists( 'page.php', $files ) ) {
			return 'page.php';
		}

		return 'index.php';
	}

	public static final function isCellAndOfType( $cell = null, $type = 'menu-cell' ) {

		return is_object( $cell ) && $cell instanceof WPDD_layout_cell && $cell->get_cell_type() === $type;
	}

	public static final function toolsetCellTypes( $cell_types = array() ) {

		$cell_types = apply_filters( 'ddl-toolset-types', array(
			"cell-content-template"   => array(
				"type"     => "view-template",
				"property" => "ddl_view_template_id",
				"label"    => "Content template"
			),
			"post-loop-views-cell"    => array(
				"type"     => "view",
				"property" => "ddl_layout_view_id",
				"label"    => "Archive view"
			),
			"views-content-grid-cell" => array(
				"type"     => "view",
				"property" => "ddl_layout_view_id",
				"label"    => "View"
			),
			"cred-cell"               => array(
				"type"     => "cred-form",
				"property" => "ddl_layout_cred_id",
				"label"    => "Post Form"
			),
			"cred-user-cell"          => array(
				"type"     => "cred-user-form",
				"property" => "ddl_layout_cred_user_id",
				"label"    => "User form"
			)
		) );

		return $cell_types;
	}

	public static final function array_unshift_assoc( &$arr, $key, $val ) {
		$arr         = array_reverse( $arr, true );
		$arr[ $key ] = $val;

		return array_reverse( $arr, true );
	}

	public static function get_property_from_cell_type( $type, $property ) {
		$infos = self::toolsetCellTypes();

		if ( ! isset( $infos[ $type ] ) ) {
			return null;
		}

		if ( ! isset( $infos[ $type ][ $property ] ) ) {
			return null;
		}

		return $infos[ $type ][ $property ];
	}

	public static final function assign_layout_to_post_object( $post_id, $layout_slug, $template = null, $meta = '' ) {

		if( !$meta ){
			$meta = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );
		}

		$ret = update_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, $layout_slug, $meta );

		if ( $ret && $template !== null ) {
			update_post_meta( $post_id, '_wp_page_template', $template );
		}

		return apply_filters( 'assign_layout_to_post_object', $ret, $post_id, $layout_slug, $template, $meta );
	}

	public static final function remove_layout_assignment_to_post_object( $post_id, $meta = '', $and_template = true ) {
		if( !$meta ){
			$meta = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );
		}
		$ret = delete_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, $meta );
		if ( $ret && $and_template ) {
			delete_post_meta( $post_id, '_wp_page_template' );
		}

		return apply_filters( 'remove_layout_assignment_to_post_object', $ret, $post_id, $meta, $and_template, $meta );
	}

	public static final function get_all_settings( $ret = null ) {
		global $wpdb;

		$query = $wpdb->prepare( "SELECT wpostmeta.meta_value
                                              FROM $wpdb->postmeta wpostmeta, $wpdb->posts wposts
                                              WHERE wpostmeta.post_id = wposts.ID
                                              AND wpostmeta.meta_key = %s
                                              AND wposts.post_type = %s
                                              ORDER BY wpostmeta.meta_value ASC", WPDDL_LAYOUTS_SETTINGS, WPDDL_LAYOUTS_POST_TYPE );

		return $wpdb->get_col( $query );
	}

	public static final function get_all_layouts_json_by_status( $status = 'publish' ) {
		global $wpdb;
		$query = $wpdb->prepare( "SELECT wpostmeta.meta_value
                                              FROM $wpdb->postmeta wpostmeta, $wpdb->posts wposts
                                              WHERE wpostmeta.post_id = wposts.ID
                                              AND wpostmeta.meta_key = %s
                                              AND wposts.post_type = %s
                                              AND wposts.post_status = %s
                                              ORDER BY wpostmeta.meta_value ASC", WPDDL_LAYOUTS_SETTINGS, WPDDL_LAYOUTS_POST_TYPE, $status );

		return $wpdb->get_col( $query );
	}

	public static final function get_all_published_settings_as_array() {
		return array_map( 'json_decode', self::get_all_layouts_json_by_status() );
	}

	public static function get_page_template( $id ) {
		return get_post_meta( $id, '_wp_page_template', true );
	}

	public static final function layout_has_one_of_type( $layout_json, $additional_types = array(), $only_extra = false ) {

		$types   = $only_extra ? array() : array_keys( self::toolsetCellTypes() );
		$builder = new WPDD_json2layout();
		$layout  = $builder->json_decode( $layout_json );
		$bool    = false;

		$types = wp_parse_args( $additional_types, $types );

		foreach ( $types as $type ) {
			if ( $layout->has_cell_of_type( $type, true ) ) {
				$bool = true;
				break;
			}
		}

		return $bool;
	}

	public static function layout_is_parent( $layout_id ) {
		$layout = WPDD_Layouts::get_layout_settings( $layout_id, true );

		return is_object( $layout ) && property_exists( $layout, 'has_child' ) && $layout->has_child === true;
	}

	public static function layout_object_is_parent( $layout ) {
		return is_object( $layout ) && property_exists( $layout, 'has_child' ) && $layout->has_child === true;
	}

	public static function layout_object_is_template( $layout ) {
		return is_object( $layout ) && ( ! property_exists( $layout, 'layout_type' ) || $layout->layout_type !== 'private' );
	}

	public static function layout_object_is_not_parent( $layout ) {
		return is_object( $layout ) && ( ! property_exists( $layout, 'has_child' ) || $layout->has_child === false );
	}

	public static function at_least_one_layout_exists() {
		$args = array(
			"status"                 => array( 'publish', 'trash', 'draft', 'private' ),
			"order_by"               => "date",
			"fields"                 => "ids",
			"return_query"           => false,
			"no_found_rows"          => false,
			"update_post_term_cache" => false,
			"update_post_meta_cache" => false,
			"cache_results"          => false,
			"order"                  => "DESC",
			"post_type"              => WPDDL_LAYOUTS_POST_TYPE
		);

		return count( DDL_GroupedLayouts::get_all_layouts_as_posts( $args ) ) > 0;
	}

	public static final function page_has_layout( $post_id ) {
		$meta = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );

		if ( $meta === '' ) {
			$ret = false;
		} elseif ( $meta == '0' ) {
			$ret = false;
		} else {
			$ret = $meta;
		}

		return $ret;
	}

	public static final function page_has_private_layout( $post_id ) {
		$get_layout_data = self::get_private_layout_data( $post_id );
		$ret             = ( $get_layout_data ) ? true : false;

		return $ret;
	}

	public static final function is_private_layout_in_use( $post_id ) {
		$meta = get_post_meta( $post_id, WPDDL_PRIVATE_LAYOUTS_IN_USE, true );

		if ( $meta === '' ) {
			$ret = false;
		} elseif ( $meta == '0' ) {
			$ret = false;
		} else {
			$ret = $meta;
		}

		return $ret;
	}

	/*
	 * Generate HTML output for private layout
	 */
	public static final function get_private_layout_HTML_output( $private_layout_id, $apply_content_filters = false ) {

		$layout_content = WPDD_Layouts::get_layout_settings_raw_not_cached( $private_layout_id, false );

		if ( sizeof( $layout_content ) > 0 ) {
			return self::get_layout_HTML_from_json( $layout_content[0], true, $apply_content_filters );
		}

		return '';
	}

	/**
	 * @param $layout_object
	 * @param bool $is_private
	 * @param bool $run_content_filters should be set to true for Template Layouts, previews and FE Editor re-render or any time you want the_content filter to run.
	 *
	 * @return null/object
	 *
	 */
	public static final function get_layout_HTML_from_json( $layout_object, $is_private = false, $run_content_filters = false /*defaults to private layout*/ ) {

		if ( ! $layout_object ) {
			return null;
		}

		self::remove_whitespace_from_output();

		$jsonObject                    = new WPDD_json2layout();
		$layout                        = $jsonObject->json_decode( $layout_object );
		$manager                       = new WPDD_layout_render_manager( $layout, null, $is_private );
		$renderer                      = $manager->get_renderer();
		$renderer->run_content_filters = $run_content_filters;
		$content                       = $renderer->render_to_html( $is_private );

		return $content;
	}

	public static function remove_whitespace_from_output() {
		// accordion cell
		add_filter( 'ddl-accordion_panel_open', array( __CLASS__, 'clean_html_output_from_extra_spaces' ), 10, 2 );
		add_filter( 'ddl-accordion_panel_close', array( __CLASS__, 'clean_html_output_from_extra_spaces' ), 10, 2 );
		add_filter( 'ddl-accordion_open', array( __CLASS__, 'clean_html_output_from_extra_spaces' ), 10, 2 );
		add_filter( 'ddl-accordion_close', array( __CLASS__, 'clean_html_output_from_extra_spaces' ), 10, 2 );
	}

	public static final function get_layout_by_slug( $slug ) {

		$meta = false;

		$get_post_object = get_page_by_path( $slug, 'OBJECT', WPDDL_LAYOUTS_POST_TYPE );
		if ( $get_post_object ) {
			$object_id = $get_post_object->ID;
			$meta      = get_post_meta( $object_id, WPDDL_LAYOUTS_SETTINGS, true );
			if ( $meta ) {
				$meta = json_decode( $meta );
			}
		}

		return $meta;

	}

	public static final function get_private_layout_data( $layout_id ) {

		$meta = get_post_meta( $layout_id, WPDDL_LAYOUTS_SETTINGS, true );
		if ( $meta === '' ) {
			$ret = false;
		} elseif ( $meta == '0' ) {
			$ret = false;
		} else {
			$ret = $meta;
		}

		return $ret;

	}

	public static function page_templates_have_layout( $ret = null ) {
		$bool = false;
		if ( ! function_exists( 'get_page_templates' ) ) {
			include_once ABSPATH . 'wp-admin/includes/theme.php';
		}
		$tpls = apply_filters( 'ddl-theme_page_templates', get_page_templates() );

		foreach ( $tpls as $tpl ) {
			$check = self::template_have_layout( $tpl );
			if ( $check ) {
				$bool = true;
				break;
			}
		}

		return apply_filters( 'ddl-page_templates_have_layout', $bool, $tpls );
	}

	public static function this_page_template_have_layout( $post_id ) {
		$current_template = get_post_meta( $post_id, '_wp_page_template', true );

		return apply_filters( 'ddl-current_page_templates_have_layout', self::template_have_layout( $current_template ), $post_id );
	}

	public static function templates_have_layout( $templates ) {

		$layout_templates = array();
		$file_data        = false;
		foreach ( $templates as $file => $name ) {

			if ( ! in_array( $file, $layout_templates ) ) {
				if ( file_exists( get_template_directory() . '/' . $file ) ) {
					$file_data = @file_get_contents( get_template_directory() . '/' . $file );
				}

				if ( self::is_child_theme() ) {
					// try child theme.
					if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
						$file_data = @file_get_contents( get_stylesheet_directory() . '/' . $file );
					}
				}
				if ( $file_data !== false ) {
					if ( strpos( $file_data, 'the_ddlayout' ) !== false ) {
						$layout_templates[] = $file;
					}
				}
			}
		}

		return apply_filters( 'ddl_templates_have_layout', $layout_templates, $templates );
	}

	public static function is_child_theme() {
		return get_stylesheet_directory() !== get_template_directory();
	}

	public static final function template_have_layout( $file, $dir = '' ) {

		if ( $file === null ) {
			return false;
		}

		$bool      = false;
		$file_data = false;

		$directory = $dir ? $dir : get_template_directory();

		$file_abs = $directory . '/' . $file;

		if ( file_exists( $file_abs ) ) {
			$file_data = @file_get_contents( $file_abs );

		} else {
			if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
				$file_data = @file_get_contents( get_stylesheet_directory() . '/' . $file );
			}
		}

		if ( $file_data !== false ) {
			if ( strpos( $file_data, 'the_ddlayout' ) !== false ) {
				$bool = true;
			}
		}

		return apply_filters( 'ddl_template_have_layout', $bool, $file );
	}

	public static final function page_template_has_layout( $post_id ) {
		$template = get_post_meta( $post_id, '_wp_page_template', true );

		return self::template_have_layout( $template );
	}

	public static function get_single_template( $post_type ) {
		$templates = array();

		if ( $post_type === 'page' ) {
			/** Thanks to http://wordpress.stackexchange.com/questions/83180/get-page-templates
			 **  get_page_templates function is not defined in FE so we need to load it in order
			 **  for this one to work
			 **/
			if ( ! function_exists( 'get_page_templates' ) ) {
				include_once ABSPATH . 'wp-admin/includes/theme.php';
			}
			$templates[ $post_type ] = "{$post_type}.php";
			$templates               += apply_filters( 'ddl-theme_page_templates', get_page_templates() );
		} else if ( $post_type === 'post' ) {
			$templates['single'] = "single.php";
		} else {
			$templates["single-{$post_type}"] = "single-{$post_type}.php";
			$templates['single']              = "single.php";
		}

		$templates['index'] = 'index.php';

		return apply_filters( 'ddl-get_single_templates', $templates, $post_type );
	}

	public static function post_type_template_have_layout( $post_type ) {

		$bool = false;
		$tpls = self::get_single_template( $post_type );

		foreach ( $tpls as $tpl ) {
			$check = self::template_have_layout( $tpl );
			if ( $check ) {
				$bool = true;
				break;
			}
		}

		return apply_filters( 'ddl_check_layout_template_page_exists', $bool, $post_type );
	}

	public static function ajax_nonce_fail( $method ) {
		return wp_json_encode( array( 'Data' => array( 'error' => __( sprintf( 'Nonce problem: apparently we do not know where the request comes from. %s', $method ), 'ddl-layouts' ) ) ) );
	}

	public static function ajax_caps_fail( $method ) {
		return wp_json_encode( array( 'Data' => array( 'error' => __( sprintf( 'I am sorry but you don\'t have the necessary privileges to perform this action. %s', $method ), 'ddl-layouts' ) ) ) );
	}

	public static function user_not_admin() {
		return ! current_user_can( DDL_CREATE );
	}

	public static function get_image_sizes( $size = '' ) {

		global $_wp_additional_image_sizes;

		$sizes                        = array();
		$get_intermediate_image_sizes = get_intermediate_image_sizes();

		// Create the full array with sizes and crop info
		foreach ( $get_intermediate_image_sizes as $_size ) {

			if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {

				$sizes[ $_size ]['width']  = get_option( $_size . '_size_w' );
				$sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
				$sizes[ $_size ]['crop']   = (bool) get_option( $_size . '_crop' );

			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {

				$sizes[ $_size ] = array(
					'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $_size ]['crop']
				);

			}

		}

		// Get only 1 size if found
		if ( $size ) {

			if ( isset( $sizes[ $size ] ) ) {
				return $sizes[ $size ];
			} else {
				return false;
			}

		}

		$sizes['Custom'] = array(
			'width'  => '',
			'height' => '',
			'crop'   => ''
		);

		return $sizes;
	}

	public static function create_cell( $name, $divider = 1, $cell_type = 'spacer', $options = array() ) {
		// create most complex id possible
		$id = (string) uniqid( 's', true );
		// het only the latest numeric only part
		$id = explode( '.', $id );
		$id = "s" . $id[1];
		// keep only 5 chars to help base64_encode slowness
		$id = substr( $id, 0, 5 );

		// build a spacer and return it

		return (object) wp_parse_args( $options, array(
			'name'                   => $name,
			'cell_type'              => $cell_type,
			'row_divider'            => $divider,
			'content'                => isset( $options['content'] ) ? $options['content'] : '',
			'cssClass'               => '',
			'cssId'                  => 'span1',
			'tag'                    => 'div',
			'width'                  => isset( $options['width'] ) ? $options['width'] : 1,
			'additionalCssClasses'   => '',
			'editorVisualTemplateID' => '',
			'id'                     => $id,
			'kind'                   => 'Cell'
		) );
	}


	public static function create_cells( $amount, $divider = 1, $cell_type = 'spacer' ) {
		$spacers = array();
		for ( $i = 0; $i < $amount; $i ++ ) {
			$spacers[] = self::create_cell( $i + 1, $divider, $cell_type );
		}

		return $spacers;
	}

	public static function is_post_published( $id ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->posts WHERE ID = '%s' AND post_status = 'publish'", $id ) ) > 0;
	}

	public static function where( $array, $property, $value ) {
		return array_filter( $array, array( new Toolset_ArrayUtils( $property, $value ), 'filter_array' ) );
	}

	public static final function property_exists( $object, $property ) {
		return is_object( $object ) ? property_exists( $object, $property ) : false;
	}

	public static final function str_replace_once( $str_pattern, $str_replacement, $string ) {

		if ( strpos( $string, $str_pattern ) !== false ) {
			$occurrence = strpos( $string, $str_pattern );

			return substr_replace( $string, $str_replacement, $occurrence, strlen( $str_pattern ) );
		}

		return $string;
	}

	/*
	 * Check layout attribute and return is it static or not.
	 */
	public static function is_private( $layout_id ) {
		// check layout_type parameter, decide is it private or not
		$meta      = get_post_meta( $layout_id, WPDDL_LAYOUTS_SETTINGS, true );
		$post_meta = json_decode( $meta );

		if ( isset( $post_meta->layout_type ) && $post_meta->layout_type === 'private' ) {
			return true;
		} else {
			return false;
		}
	}

	public static function is_layout_private( $bool = false, $layout_id = 0 ){
		return self::is_private( $layout_id );
	}
}

/**
 * Class WPDD_FilterLayoutsByPropertyValue
 */
class WPDD_FilterLayoutsByPropertyValue {
	protected $poperty;
	protected $value;
	private $parser;
	private $already_encoded = true;
	private $as_object = true;

	/**
	 * WPDD_FilterLayoutsByPropertyValue constructor.
	 *
	 * @param WPDD_json2layout $parser
	 * @param array $args
	 */
	public function __construct( WPDD_json2layout $parser, $args = array() ) {
		$this->parser          = $parser;
		$this->poperty         = isset( $args['property'] ) ? $args['property'] : null;
		$this->value           = isset( $args['value'] ) ? $args['value'] : null;
		$this->already_encoded = isset( $args['already_encoded'] ) ? $args['already_encoded'] : $this->already_encoded;
		$this->as_object       = isset( $args['as_object'] ) ? $args['as_object'] : $this->as_object;
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function filter_layouts_has_cell_of_type( $raw ) {

		if ( $this->as_object ) {
			$raw = json_decode( wp_json_encode( (array) $raw ), true );
		}

		$layout = $this->parser->json_decode( $raw, $this->already_encoded );

		return $layout->has_cell_of_type( $this->value, false );
	}
}

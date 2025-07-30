<?php

/**
 * Class WPDDL_Layouts_WPML
 *
 * handles mostly automatic translations updates related to assignments
 */
class WPDDL_Layouts_WPML{

	private static $instance = null;
	static $languages = null;
	static $current_language = 'en';
	static $default_language = 'en';

	private function __construct(){

		self::$current_language = apply_filters( 'wpml_current_language', NULL );
		self::$default_language = apply_filters('wpml_default_language', NULL );

		add_filter('assign_layout_to_post_object', array(&$this, 'handle_save_update_assignment'), 99, 5 );

		add_filter('remove_layout_assignment_to_post_object', array(&$this, 'handle_remove_assignment'), 99, 4 );

		add_action('ddl-add-wpml-custom-switcher', array(&$this, 'print_wpml_custom_switcher') );

		add_action('ddl-wpml-switch-language', array(&$this, 'ddl_wpml_switch_language'), 10, 1 );

		add_action( 'ddl-wpml-switcher-scripts', array(&$this, 'enqueue_language_switcher_script') );

		add_action('admin_init', array(&$this, 'get_active_languages') );

		add_filter( 'wpml_pb_is_page_builder_page', array( $this, 'isLayoutPage' ), 10, 2 );
	}

	function get_active_languages(){
		self::$languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=name&order=asc&skip_missing=0' );
		return self::$languages;
	}

	public function ddl_wpml_switch_language( $lang ){
		self::$current_language = isset( $lang ) && $lang ? $lang :self::$default_language;
		do_action( 'wpml_switch_language', self::$current_language );
	}

	public function enqueue_language_switcher_script(){
		add_action('admin_print_scripts', array(&$this, 'enqueue_wpml_selector_script'));
	}

	function enqueue_wpml_selector_script(){

		if( null === self::wpml_languages() ) return;

		global $wpddlayout;

		$wpddlayout->enqueue_scripts('ddl-wpml-switcher');
		$wpddlayout->localize_script('ddl-wpml-switcher', 'DDLayout_LangSwitch_Settings', apply_filters( 'ddl-wpml-localize-switcher', array(
			'default_language' => self::$default_language,
			'current_language' => self::$current_language,
		) ) );
	}

	public function print_wpml_custom_switcher(){
		$languages = self::wpml_languages();
		if( null === $languages ) return;

		ob_start();
		include_once WPDDL_GUI_ABSPATH . 'templates/layout-language-switcher.tpl.php';
		echo ob_get_clean();
	}

	public static function wpml_languages(){

		if( self::$languages === null || count(self::$languages) === 0 ) return null;

		return self::$languages;
	}

	public function handle_save_update_assignment(  $ret, $post_id, $layout_slug, $template, $meta ){
		if( $ret === false ) return $ret;

		$post_type = get_post_type( $post_id );
		$is_translated_post_type = apply_filters( 'wpml_is_translated_post_type', null, $post_type );
		if( $is_translated_post_type === false ){
			return $ret;
		}

		$translations  = apply_filters('wpml_content_translations', null, $post_id, $post_type);

		if( !$translations ){
			return $ret;
		}

		foreach( $translations as $translation){
			if( $translation->element_id !== $post_id ){
				$up = update_post_meta($translation->element_id, WPDDL_LAYOUTS_META_KEY, $layout_slug, $meta);
				if( $up && $template !== null ){
					update_post_meta($translation->element_id, '_wp_page_template', $template);
				}
			}
		}

		return $ret;
	}

	public function handle_remove_assignment( $ret, $post_id, $meta, $and_template ){
		if( $ret === false ) return $ret;

		$post_type = get_post_type( $post_id );
		$is_translated_post_type = apply_filters( 'wpml_is_translated_post_type', null, $post_type );
		if( $is_translated_post_type === false ){
			return $ret;
		}
		$translations  = apply_filters('wpml_content_translations', null, $post_id, $post_type);

		if( !$translations ){
			return $ret;
		}

		foreach( $translations as $translation){

			if( $translation->element_id !== $post_id ){
				$up = delete_post_meta( $translation->element_id, WPDDL_LAYOUTS_META_KEY, $meta );
				if( $up && $and_template ){
					delete_post_meta($translation->element_id, '_wp_page_template');
				}
			}
		}

		return $ret;
	}

	/**
	 * Tell WPML that the current page is using a private layout.
	 *
	 * @param bool $isPbPage
	 * @param \WP_Post $post
	 * @return bool
	 * @since 2.6.5
	 */
	public function isLayoutPage( $isPbPage, \WP_Post $post ) {
		if ( 'yes' === get_post_meta( $post->ID, '_private_layouts_template_in_use', true ) ) {
			return true;
		}
		return $isPbPage;
	}

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new WPDDL_Layouts_WPML();
		}

		return self::$instance;
	}
}

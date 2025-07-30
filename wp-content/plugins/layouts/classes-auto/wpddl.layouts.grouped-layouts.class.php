<?php

/**
 * Class DDL_GroupedLayouts
 * Helper class to group layouts based on assignments
 */
class DDL_GroupedLayouts
{

	const NUMBER_OF_ITEMS = 1;

	private $loop_manager;
	private $post_types = array();
	private $to_single = array();
	private $parents = array();
	private $not_assigned = array();
	private $to_loops = array();
	private $all_children_assigned = false;

	private $layouts = array();
	private $query;

	static $blacklist = array('post_parent',
		'post_password',
		'comment_count',
		'comment_status',
		'guid',
		'menu_order',
		'pinged',
		'ping_status',
		'post_author',
		'post_content',
		'post_content_filtered',
		'post_date_gmt',
		'post_excerpt',
		'post_mime_type',
		'post_modified',
		'post_modified_gmt',
		'to_ping');

	private static $instance;

	private function __construct()
	{
		global $wpddlayout;
		$this->loop_manager = $wpddlayout->layout_post_loop_cell_manager;
	}

	public function init( $status = 'publish' )
	{
		$args = array( "status" => $status,
		               "order_by" => "date",
		               "fields" => "all",
		               "return_query" => true);

		$get_all = self::get_all_layouts_as_posts( $args );
		$this->query = $get_all->query;
		$this->set_layouts( $get_all->posts );
	}

	public function get_query()
	{
		return $this->query;
	}

	public function get_layouts()
	{
		return $this->layouts;
	}

	public function set_layouts( $layouts )
	{
		$this->layouts = $layouts;
	}

	public static function get_all_layouts_as_posts( $args = array( "status" => "publish",
	                                                                "order_by" => "date",
	                                                                "fields" => "all",
	                                                                "return_query" => false,
	                                                                "no_found_rows" => false,
	                                                                "update_post_term_cache" => true,
	                                                                "update_post_meta_cache" => true,
	                                                                "cache_results" => true,
	                                                                "order" => "DESC",
	                                                                "post_type" => WPDDL_LAYOUTS_POST_TYPE ) ) {


		$res = new stdClass();

		$args = wp_parse_args( $args, array( "status" => "publish",
		                                     "order_by" => "date",
		                                     "fields" => "all",
		                                     "return_query" => false,
		                                     "no_found_rows" => false,
		                                     "update_post_term_cache" => true,
		                                     "update_post_meta_cache" => true,
		                                     "cache_results" => true,
		                                     "order" => "DESC",
		                                     "post_type" => WPDDL_LAYOUTS_POST_TYPE ) );

		$defaults = array(
			'post_type' => $args["post_type"],
			'suppress_filters' => false,
			'order' => $args["order"],
			'orderby' => $args["order_by"],
			'post_status' => $args["status"],
			'posts_per_page' => WPDDL_MAX_POSTS_OPTION_DEFAULT,
			'fields' => $args["fields"],
			'no_found_rows' => $args["no_found_rows"],
			// leave the terms alone we don't need them
			'update_post_term_cache' => $args["update_post_term_cache"],
			// leave the meta alone we don't need them
			'update_post_meta_cache' => $args["update_post_meta_cache"],
			// don't cache results
			'cache_results' => $args["cache_results"]
		);

		$res->query = new WP_Query($defaults);
		$res->posts = $res->query->posts;
		$res->query->posts = null;

		if ( $args['return_query'] ) {
			return $res;
		} else {
			return $res->posts;
		}

		return null;
	}

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new DDL_GroupedLayouts();
		}

		return self::$instance;
	}

	public static function _filter_post($post, $black = false, $do_edit_link = true)
	{

		$blacklist = $black ? $black : array('post_parent',
			'post_password',
			'comment_count',
			'comment_status',
			'guid',
			'menu_order',
			'pinged',
			'ping_status',
			'post_author',
			'post_content',
			'post_content_filtered',
			'post_date_gmt',
			'post_excerpt',
			'post_mime_type',
			'post_modified',
			'post_modified_gmt',
			'to_ping');

		$post->post_name = urldecode($post->post_name);
		foreach ($blacklist as $remove) {
			unset($post->{$remove});
		}
		if( $do_edit_link ){
			$edit_link = get_edit_post_link($post->ID);
			if ($edit_link) {
				if( !$post->post_title || $post->post_title === '' ){
					$post->post_title = sprintf( __('%sno title%s', 'ddl-layouts'), '&lpar;', '&rpar;' );
				}
				$post->post_title = '<a href="' . $edit_link . '">' . $post->post_title . '</a>';
			}
		}


		return $post;
	}

	private function _filter_layout_item($item)
	{
		foreach (self::$blacklist as $remove) {
			if( property_exists($item, $remove ) ){
				unset($item->{$remove});
			}
		}
		return $item;
	}


	function process_items_common_properties( $item, $layout, $args){

		$item = $this->_filter_layout_item($item);
		$item->show_posts = $this->set_number_of_items_for_posts($item, $args);
		$item->kind = 'Item';
		$item->post_name = urldecode($item->post_name);
		$item->id = $item->ID;
		$item->is_parent = $layout->has_child;
		$item->date_formatted = get_the_time(get_option('date_format'), $item->ID);
		$item->post_title = str_replace('\\\"', '\"', $item->post_title);
		$item->has_loop = property_exists($layout, 'has_loop') ? $layout->has_loop : false;
		$item->has_post_content_cell = property_exists($layout, 'has_post_content_cell') ? $layout->has_post_content_cell : false;

		if( WPDD_Utils::layout_has_one_of_type( json_encode( $layout ) ) ){
			$item->layout = $layout;
		}

		return $item;
	}

	function process_item_is_parent( $item, $layout ){

		if ($item->is_parent) {
			$item->children = self::get_children($layout, $this->layouts);
			//$item->all_children_assigned = self::all_children_assigned($item->children);
		}

		if (property_exists($layout, 'parent') && $layout->parent) {
			$parent = get_post(WPDD_Layouts::get_layout_parent($item->ID, $layout));
			$item->is_child = true;
			if (is_object($parent) && $parent->post_status == $item->post_status) {
				$item->parent = $parent->ID;
			}
		} else {
			$item->is_child = false;
		}

		return $item;
	}

	function process_item_post_types( $item, $types ){

		if ($types && !$item->is_parent) {
			$item->types = $types;
			$item->count = count($types);
			$this->post_types[] = (array)$item;
		}
		return $item;
	}

	function process_item_loops( $item, $loops ){

		if ($loops && !$item->is_parent) {
			$item->loops = $loops;
			$item->count = count($loops);
			$this->to_loops[] = (array)$item;
		}

		return $item;
	}

	function all_children_assigned($children){

		$all_assigned = false;
		global $wpddlayout;
		foreach($children as $child){

			$where_used = $wpddlayout->get_where_used($child);
			$used_for_loops = $this->loop_manager->get_layout_loops_labels($child);

			if(count($where_used) === 0 && count($used_for_loops)===0){
				return false;
			} else {
				$all_assigned = true;
			}
		}
		return $all_assigned;
	}

	function process_item_single_assignments( $item, $types, $loops, $args ){

		global $wpddlayout;

		$posts_ids = $this->get_posts_where_used($item, $types, $args);

		if (($posts_ids && count($posts_ids) > 0) && !$item->is_parent) {

			$yes = $this->show_in_single($types, $posts_ids, $args);

			$item->posts = $yes;

			// $item_posts_count = count( $item->posts );
			$total_count = $wpddlayout->get_where_used_count();

			if ($total_count > self::NUMBER_OF_ITEMS) {
				$item->show_more_button = true;
			}

			if (sizeof($item->posts) > 0) {
				$item->count = count( $item->posts );
				$this->to_single[] = (array)$item;
			}

		} elseif ($item->is_parent || (!$posts_ids && !$types && !$loops)) {

			if($item->is_parent && $item->all_children_assigned === true){
				$this->parents[] = (array)$item;
			} else {
				$item->count = 0;
				$this->not_assigned[] = (array)$item;
			}
		}

		return $item;
	}

	public function get_groups( $args = array() )
	{
		foreach ($this->layouts as $item) {

			$layout = WPDD_Layouts::get_layout_settings($item->ID, true);

			if ( $layout ) {

				if (property_exists($layout, 'has_child') === false) $layout->has_child = false;

				$item = $this->process_items_common_properties( $item, $layout, $args);

				$item = $this->process_item_is_parent( $item, $layout );

				$types = apply_filters( 'ddl-get_layout_post_types_object', $item->ID, true );

				$item = $this->process_item_post_types( $item, $types );

				$loops = $this->loop_manager->get_layout_loops_labels($item->ID);

				$item = $this->process_item_loops( $item, $loops );

				$item = $this->process_item_single_assignments( $item, $types, $loops, $args );

			}
		}

		$ret = $this->return_default_groups($this->parents, $this->not_assigned, $this->to_single, $this->post_types, $this->to_loops );

		return apply_filters( 'ddl_get_layouts_listing_groups', $ret, $this );
	}


	public function build_groups_from_settings( $args = array('link'=>true) ){

		foreach( $this->layouts as $layout ){

			$item = $this->get_post_item_from_settings( $layout );

			if (property_exists($layout, 'has_child') === false) $layout->has_child = false;

			$item = $this->process_items_common_properties( $item, $layout, $args);

			$item = $this->process_item_is_parent( $item, $layout );

			$types = apply_filters( 'ddl-get_layout_post_types_object', $item->ID, false );

			if( is_array($types) ){
				$this->current = $item->ID;
				$types = array_map( array(&$this, 'get_archive_link'), $types);
			}

			$item = $this->process_item_post_types( $item, $types );

			$loops = $this->loop_manager->get_layout_loops($item->ID);

			if( is_array($loops) ){
				$loops = array_map( array(&$this, 'get_loop_display_object'), $loops );
			}

			$item = $this->process_item_loops( $item, $loops );

			$args['do_edit_link'] = false;
			$item = $this->process_item_single_assignments( $item, $types, $loops, $args );
		}

		return $this->return_default_groups_with_count($this->parents, $this->not_assigned, $this->to_single, $this->post_types, $this->to_loops );
	}

	public function get_archive_link( $type ){
		$link = '';
		if( apply_filters('ddl-get_post_type_was_batched', $this->current, $type['post_type'])){
			$post = apply_filters( 'ddl-get_x_posts_of_type', $type['post_type'], $this->current, 1);
			if( isset($post[0]) && is_object($post[0]) ){
				$link = apply_filters( 'ddl-ddl_get_post_type_batched_preview_permalink', $type['post_type'], $post[0]->ID );
			}
		}
		$type['link'] = $link;
		return $type;
	}

	public function get_loop_display_object( $loop ){
		global $wpddlayout;
		return $wpddlayout->layout_post_loop_cell_manager->get_loop_display_object( $loop );
	}

	private function get_post_item_from_settings( $layout ){
		return (object) array(
			'ID' => $layout->id,
			'post_name' => $layout->slug,
			'post_title' => $layout->name,
			'post_date' => date("Y-m-d H:i:s"),
			'post_status' => 'publish',
			'post_type' => WPDDL_LAYOUTS_POST_TYPE
		);
	}

	private function return_default_groups($zero, $one, $two, $three, $four){

		return array(
			array(
				'id' => 3,
				'name' => __('Layouts being used as templates for post types', 'ddl-layouts'),
				'kind' => 'Group',
				'items' => $three
			),
			array(
				'id' => 4,
				'name' => __('Layouts being used to customize archives', 'ddl-layouts'),
				'kind' => 'Group',
				'items' => $four
			),
			array(
				'id' => 2,
				'name' => __('Layouts being used to display single posts or pages', 'ddl-layouts'),
				'kind' => 'Group',
				'items' => $two
			),
			array(
				'id' => 1,
				'name' => __("Layouts not being used anywhere", 'ddl-layouts'),
				'kind' => 'Group',
				'items' => $one
			),
			array(
				'id' => 0,
				'name' => __("Parents with all children assigned", 'ddl-layouts'),
				'kind' => 'Group',
				'items' => $zero
			),
		);
	}

	public function return_default_groups_with_count($zero, $one, $two, $three, $four ){
		$ret = $this->return_default_groups($zero, $one, $two, $three, $four);
		$ret['count_assignments'] = array_reduce($ret, array(&$this, 'count_children_items'), 0);
		$ret['count_children'] = array_reduce($ret, array(&$this, 'count_children'), 0);
		return $ret;
	}

	public function count_children_items(  $carry ,  $item ){
		$count = 0;
		if( isset($item['items']) && is_array($item['items']) ){
			foreach( $item['items'] as $current ){
				$count += $current['count'];
			}
			$carry += $count;
		}
		return $carry;
	}

	public function count_children( $carry ,  $item ){
		if( isset( $item['items'] ) && is_array( $item['items'] ) ){
			$carry += count( $item['items'] );
		}
		return $carry;
	}

	private function show_in_single($types, $posts_ids, $args = array() )
	{
		if (!$types) return $posts_ids;
		$post_types = is_array( $types ) ? array_map(array(&$this, 'map_layout_post_types_name'), $types) : array();
		$ret = array();
		foreach ($posts_ids as $post) {
			if (in_array($post->post_type, $post_types) === false) {
				if( isset( $args['link'] ) && $args['link'] === true ){
					$post->link = get_permalink( $post->ID );
				}
				$ret[] = $post;
			}
		}
		return $ret;
	}

	private function get_post_ids($item, $types, $amount = self::NUMBER_OF_ITEMS)
	{

		global $wpddlayout;

		if ($types) {
			$post_types_to_query = $this->get_post_types_to_query($item->ID, $types);
			if (count($post_types_to_query) === 0) {
				return false;
			} else {
				$posts_ids = $wpddlayout->get_where_used($item->ID, $item->post_name, true, $amount, array('publish', 'draft', 'pending', 'future'), 'ids', $post_types_to_query, false);
			}
		} else {
			$posts_ids = $wpddlayout->get_where_used( $item->ID, $item->post_name, true, $amount, array( 'publish', 'draft', 'pending', 'private', 'future' ), 'ids', 'any', true );
		}

		return $posts_ids;
	}

	public function get_posts_where_used($layout, $post_types, $args = array())
	{

		$show_posts = isset($args['show_posts']) ? $args['show_posts'] : false;

		if ($show_posts && isset($show_posts[$layout->ID])) {
			$layout->show_posts = $show_posts[$layout->ID];
		}

		$posts_ids = $this->get_post_ids($layout, $post_types, (int)$layout->show_posts);

		if (!$posts_ids || count($posts_ids) === 0) return $posts_ids;

		return $this->set_layout_posts_and_return_them($posts_ids, $args);
	}

	private function set_number_of_items_for_posts($item, $args = array())
	{
		$ret = self::NUMBER_OF_ITEMS;

		$show_posts = isset($args['show_posts']) ? $args['show_posts'] : false;

		if ($show_posts && isset($show_posts[$item->ID])) {
			$ret = $show_posts[$item->ID];
		}
		return $ret;
	}

	public function get_all_layouts_posts()
	{

		if (ob_get_length()) {
			ob_clean();
		}

		if( user_can_edit_layouts() === false ){
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}
		if ($_POST && wp_verify_nonce($_POST['nonce'], 'ddl_listing_show_posts_nonce')) {

			$data = json_decode(stripslashes($_POST['data']), true);

			$layout = (object)$data['layout'];

			$post_types = isset($data['post_types']) ? $data['post_types'] : array();

			$posts = $this->get_posts_where_used((object)$layout, $post_types);

			$send = wp_json_encode(array('Data' => array('posts' => $posts)));

		} else {
			$send = WPDD_Utils::ajax_nonce_fail( __METHOD__ );
		}
		die($send);
	}


	private function set_layout_posts_and_return_them($posts_ids, $args = array())
	{
		$posts = array();
		foreach ($posts_ids as $post_id) {
			$post = get_post($post_id);

			if( is_object( $post ) && property_exists( $post, 'post_title' ) && $post->post_title === ''){
                $post->post_title = __( '(no title)', 'ddl-layouts' );
            }

			$do_edit = isset($args['do_edit_link']) && $args['do_edit_link'];
			$post = self::_filter_post( $post, self::$blacklist, $do_edit);
			if( isset( $args['link'] ) && $args['link'] === true ){
				$post->link = get_permalink( $post->ID );
			}
			$posts[] = $post;
		}
		return $posts;
	}

	private function get_batched_post_types_array($layout_id, $post_type_object)
	{
		if( is_array($post_type_object) === false ) return array();

		global $wpddlayout;

		$ret = array();

		$layout_batched_types = $wpddlayout->post_types_manager->get_layout_batched_post_types($layout_id);

		if (count($layout_batched_types) === 0) return $ret;

		$layout_batched_types = $layout_batched_types[0];

		foreach ($post_type_object as $post_type) {
			if (in_array($post_type['post_type'], $layout_batched_types)) {
				$ret[] = $post_type;
			}
		}

		return $ret;
	}

	private function get_post_types_to_query($layout_id, $layout_post_types)
	{
		global $wpddlayout;
		$all_types = array_map(array(&$this, 'map_wp_post_types_name'), $wpddlayout->post_types_manager->get_post_types_from_wp());
		$batched = $this->get_batched_post_types_array($layout_id, $layout_post_types);

		if (!$batched || count($batched) === 0) return $all_types;

		$batched = array_map(array(&$this, 'map_layout_post_types_name'), $batched);

		return array_diff($all_types, $batched);
	}

	function map_layout_post_types_name($m)
	{
		return $m['post_type'];
	}

	function map_wp_post_types_name($m)
	{
		return $m->name;
	}

	public static function get_children($layout, $layouts_list, $previous_slug = null)
	{
		$ret = array();

		if (isset($layout->has_child) && ($layout->has_child === 'true' || $layout->has_child === true)) {

			$layout_slug = $previous_slug === null ? $layout->slug : $previous_slug;

			foreach ($layouts_list as $post) {
				$child = null;
				/**
				 * if searched by settings child is child
				 */
				if( property_exists($post, 'ID') === false && property_exists($post, 'id') === true ){
					$post_id = $post->id;
					$child = $post;
					/**
					 * if searched by post fetch settings
					 */
				} else {
					$post_id = $post->ID;
					$child = WPDD_Layouts::get_layout_settings($post_id, true);
				}

				if ($child) {
					if (property_exists($child, 'parent') && $child->parent == $layout_slug && $layout->id != $post_id) {
						$ret[] = $post_id;
					}
				}
			}
			return $ret;
		}
		return $ret;
	}

}
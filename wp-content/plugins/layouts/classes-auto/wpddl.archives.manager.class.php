<?php
class  WPDD_layout_post_loop_cell_manager
{
    private static $instance;
    private $layouts_options;
    const OPTION_BLOG = 'layouts_home-blog-page';
    const OPTION_STATIC_BLOG = 'layouts_home-static-blog-page';
    const OPTION_HOME = 'layouts_home-home-page';
    const OPTION_SEARCH = 'layouts_search-page';
    const OPTION_YEAR = 'layouts_year-page';
    const OPTION_MONTH = 'layouts_month-page';
    const OPTION_DAY = 'layouts_day-page';
    const OPTION_404 = 'layouts_404_page';
    const OPTION_AUTHOR = 'layouts_author-page';
    const OPTION_TAXONOMY_PREFIX = 'layouts_taxonomy_loop_';
    const OPTION_TYPES_PREFIX = 'layouts_cpt_';

    const TAXONOMY_LOOPS_NAME = 'wordpress_archive_loops';
    const POST_TYPES_LOOPS_NAME = 'wordpress_archive_loops';
    const WORDPRESS_DEFAULT_LOOPS_NAME = 'wordpress_archive_loops';
    const WORDPRESS_OTHERS_SECTION = 'wordpress_others_section';


    private function __construct()
    {
        
        
        $this->layouts_options = new WPDDL_Options_Manager(WPDDL_GENERAL_OPTIONS);

        add_filter('dd_layouts_register_cell_factory', array(&$this, 'dd_layouts_register_loop_cell_factory'));
        add_filter( 'ddl_get_change_dialog_html', array(&$this, 'add_dialog_change_use_html'), 11, 5 );
        add_filter('ddl-rules_for_post_types_archive_rewrite', array(&$this, 'rules_for_post_types_archive_rewrite'), 10, 2 );
        add_filter( 'ddl-get_layout_loops', array(&$this, 'get_layout_loops'), 10, 1 );
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDD_layout_post_loop_cell_manager();
        }

        return self::$instance;
    }

    public function add_dialog_change_use_html($html, $current, $do_not_show, $id, $show_ui){

        $html .= $this->display_loops($current, $id, $show_ui);
        $html .= $this->display_others($current, $id, $show_ui);
        return $html;
    }

    public function get_option($option)
    {
        return $this->layouts_options->get_options($option);
    }

    public function dd_layouts_register_loop_cell_factory($factories)
    {
        if (class_exists('WPDD_layout_loop_views_cell_factory')) {
            $factories['post-loop-views-cell'] = new WPDD_layout_loop_views_cell_factory;
        }
        return $factories;
    }

    private function _get_default_archive_loops()
    {
        
        
        $loops = array(
            self::OPTION_SEARCH => __('Search results', 'ddl-layouts'),
            self::OPTION_AUTHOR => __('Author archives', 'ddl-layouts'),
            self::OPTION_YEAR => __('Year archives', 'ddl-layouts'),
            self::OPTION_MONTH => __('Month archives', 'ddl-layouts'),
            self::OPTION_DAY => __('Day archives', 'ddl-layouts'),
        );
        
        $frontpage_id = get_option('page_on_front');
        $page_for_posts = get_option( 'page_for_posts' );
        
        if(!$frontpage_id && !$page_for_posts){
            $loops[self::OPTION_BLOG] =  __('Blog/Home', 'ddl-layouts');
        } else if(!$frontpage_id && $page_for_posts){
            $loops[self::OPTION_STATIC_BLOG] =  __('Blog', 'ddl-layouts');
        } else if($frontpage_id && !$page_for_posts){
            $loops[self::OPTION_HOME] =  __('Home', 'ddl-layouts');
        } else {
            $loops[self::OPTION_STATIC_BLOG] =  __('Blog', 'ddl-layouts');
            $loops[self::OPTION_HOME] =  __('Home', 'ddl-layouts');
        }
        
        
        return $loops;
    }

    private function _get_others()
    {
        $ret = array();
        $is_integrated_theme = apply_filters( 'ddl-is_integrated_theme', false );

        if ($this->is_using_permalinks() === false || false === $is_integrated_theme ){
            $ret = array();
        } else{
            $ret = array(
                self::OPTION_404 => __('Error 404 Page', 'ddl-layouts')
            );
        }

        return apply_filters('ddl_create_others_sections_loops', $ret );
    }

    private function _get_post_types_loops()
    {
        $loops = array();
        // Only offer loops for post types that already have an archive
        $post_types = get_post_types(array('public' => true, 'has_archive' => true), 'objects');
        foreach ($post_types as $post_type) {
            if (!in_array($post_type->name, array('post', 'page', 'attachment'))) {
                $type = self::OPTION_TYPES_PREFIX . $post_type->name;
                $name = $post_type->labels->name;
                $loops[$type] = $name;
            }
        }
        return $loops;
    }

    private function get_loops_to_display()
    {
        return array(
            (object)array(
                'title' => __('Custom Post Archives:', 'ddl-layouts'),
                'loop' => $this->_get_post_types_loops(),
                'name' => self::POST_TYPES_LOOPS_NAME,
            ),
            (object)array(
                'title' => __('Taxonomy Archives:', 'ddl-layouts'),
                'loop' => $this->_get_taxonomies_loops(),
                'name' => self::TAXONOMY_LOOPS_NAME
            ),
            (object)array(
                'title' => __('Main Archives:', 'ddl-layouts'),
                'loop' => $this->_get_default_archive_loops(),
                'name' => self::WORDPRESS_DEFAULT_LOOPS_NAME
            )
        );
    }

    private function get_others_to_display()
    {
	    $is_integrated_theme = apply_filters( 'ddl-is_integrated_theme', false );
        if ($this->is_using_permalinks() === false || $is_integrated_theme === false ) return array();

        return array(
            (object)array(
                'title' => __('Others:', 'ddl-layouts'),
                'loop' => $this->_get_others(),
                'name' => self::WORDPRESS_OTHERS_SECTION
            )
        );
    }

    public function display_loops($current = false, $id_string = "", $show_ui = true)
    {
        $loops = $this->get_loops_to_display();
        ob_start();
        if (sizeof($loops) > 0) {
            include WPDDL_GUI_ABSPATH . 'editor/templates/select-wordpress-archives.box.tpl.php';
        }
        return ob_get_clean();
    }

    public function display_others($current = false, $id_string = "", $show_ui = true)
    {
        $loops = $this->get_others_to_display();

        ob_start();
        if (sizeof($loops) > 0) {
            include WPDDL_GUI_ABSPATH . 'editor/templates/select-wordpress-others.box.tpl.php';
        }
        return ob_get_clean();
    }

    private function _get_taxonomies_loops()
    {
        $taxonomies = get_taxonomies('', 'objects');
        $loops = array();
        $exclude_tax_slugs = array('link_category', 'nav_menu', 'post_format');
        $exclude_tax_slugs = apply_filters('layouts_admin_exclude_tax_slugs', $exclude_tax_slugs);
        foreach ($taxonomies as $category_slug => $category) {
            if (in_array($category_slug, $exclude_tax_slugs)) {
                continue;
            }
            if (!$category->show_ui) {
                continue; // Only show taxonomies with show_ui set to TRUE
            }
            $type = self::OPTION_TAXONOMY_PREFIX . $category->name;
            $label = $category->label;
            $loops[$type] = $label;
        }

        return $loops;
    }

    private function save_archive_loop_option($option, $layout_id)
    {
        $this->layouts_options->update_options($option, $layout_id, true);
    }

    private function archive_has_layout($archive, $layout_id)
    {
        return (int)$this->layouts_options->get_options($archive) === (int)$layout_id;
    }

    public function handle_archives_data_save($archives, $layout_id)
    {

        if ($archives === null || !is_array($archives)) return;

        $options = $this->layouts_options->get_options();

        $types = $this->_get_post_types_loops();
        $taxonomies = $this->_get_taxonomies_loops();
        $wp_defaults = $this->_get_default_archive_loops();

        /**
         * @todo: Carefully remove following commented lines.
         */
        // Since we are dealing Error 404 as an archive, in this case it should be handled by it's own update request.
        // Handling it with Archives, removes it's assignment from the layout.
        //$others = $this->_get_others();

        //$check_options = array_merge($wp_defaults, $taxonomies, $types, $others);
        $check_options = array_merge($wp_defaults, $taxonomies, $types);

        // remove loop if present in options and then unchecked
        foreach ($check_options as $check => $label) {
            if (isset($options[$check]) && (int)$options[$check] === (int)$layout_id && !in_array($check, $archives)) {
                $this->layouts_options->delete_option($check);
            }
        }

        // then save what's there for this layout

        $ret = array();

        foreach ($archives as $archive) {
            if ($this->check_archive_exists($archive)) {
                $ret[] = $this->save_archive_loop_option($archive, $layout_id);
            }
        }

        return count($ret) > 0;
    }

    private function check_archive_exists($archive)
    {

        $prefix = $this->get_archive_type($archive);

        if ($prefix === true) {
            return true;
        } elseif ($prefix === self::OPTION_TYPES_PREFIX || $prefix === self::OPTION_TAXONOMY_PREFIX) {
            $check = explode($prefix, $archive);
            $check = $check[1];
            $ret = taxonomy_exists($check) || post_type_exists($check);
            return $ret;
        }


        return true;
    }

    private function get_archive_type($archive)
    {
        $haystack = array(
            self::OPTION_BLOG,
            self::OPTION_SEARCH,
            self::OPTION_YEAR,
            self::OPTION_MONTH,
            self::OPTION_DAY,
            self::OPTION_404,
            self::OPTION_AUTHOR,
            self::OPTION_TAXONOMY_PREFIX,
            self::OPTION_TYPES_PREFIX
        );

        $prefix = array_filter($haystack, array(new DDL_FilterArchive($archive), 'contains'));

        $prefix = implode('', $prefix );

        if ($prefix === self::OPTION_TYPES_PREFIX || $prefix === self::OPTION_TAXONOMY_PREFIX) {
            return $prefix;
        }

        return true;
    }

    public function get_options_general()
    {
        return $this->layouts_options->get_options();
    }

    public function handle_others_data_save($archives, $layout_id)
    {

        if ($archives === null || !is_array($archives)) return;

        $options = $this->layouts_options->get_options();

        $others = $this->_get_others();

        $check_options = $others;

        // remove loop if present in options and then unchecked
        foreach ($check_options as $check => $label) {
            if (isset($options[$check]) && (int)$options[$check] === (int)$layout_id && !in_array($check, $archives)) {
                $this->layouts_options->delete_option($check);
            }
        }

        // then save what's there for this layout

        foreach ($archives as $archive) {
            $this->save_archive_loop_option($archive, $layout_id);
        }

    }

    public function remove_archives_association($archives, $layout_id)
    {
        if ($archives === null || !is_array($archives)) return;

        $options = $this->layouts_options->get_options();

        $types = $this->_get_post_types_loops();
        $taxonomies = $this->_get_taxonomies_loops();
        $wp_defaults = $this->_get_default_archive_loops();
        $others = $this->_get_others();

        $check_options = array_merge($wp_defaults, $taxonomies, $types, $others);

        // remove loop if present in options and then unchecked
        foreach ($check_options as $check => $label) {
            if (isset($options[$check]) && (int)$options[$check] === (int)$layout_id && in_array($check, $archives)) {
                $this->layouts_options->delete_option($check);
            }
        }

    }

    public function get_layout_loops($layout_id)
    {
        $options = $this->layouts_options->get_options();

        if (is_array($options) === false || sizeof($options) === 0) return array();

        $res = array_keys($options, $layout_id);

        return $res;
    }

    public function get_layout_loops_labels($layout_id)
    {
        $layout_loops = $this->get_layout_loops($layout_id);

        if (is_array($layout_loops) === false || sizeof($layout_loops) === 0) return false;

        $ret = array();
        $types = $this->_get_post_types_loops();
        $taxonomies = $this->_get_taxonomies_loops();
        $wp_defaults = $this->_get_default_archive_loops();
        $wp_others = $this->_get_others();

        $check_options = array_merge($wp_defaults, $taxonomies, $types, $wp_others);

        foreach ($layout_loops as $loop) {
            if (isset($check_options[$loop])) {
                $ret[] = $check_options[$loop];
            }
        }

        return $ret;
    }

    public function get_loop_display_object($loop)
    {

        $ret = null;

        $url_array = parse_url( get_bloginfo('url') );

        if ($loop == self::OPTION_BLOG) {
            $ret = array(
                'href' => isset( $url_array['path'] ) ? get_bloginfo('url') . '/index.php' : get_bloginfo('url'),
                'title' => __('Home/Blog', 'ddl-layouts'),
                'type' => 'archives',
                'types' => __('Archives', 'ddl-layouts')
            );
        } elseif (self::OPTION_404 === $loop) {
            $ret = array(
                'href' => get_bloginfo('url') . '/A_BROKEN_LINK',
                'title' => __('Error 404 Page', 'ddl-layouts'),
                'type' => 'archives',
                'types' => __('Archives', 'ddl-layouts')
            );
        } elseif (self::OPTION_HOME === $loop) {
            $ret = array(
                'href' => get_permalink( get_option('page_on_front') ),
                'title' => __('Home', 'ddl-layouts'),
                'type' => 'archives',
                'types' => __('Archives', 'ddl-layouts')
            );
        } elseif (self::OPTION_STATIC_BLOG === $loop) {
            $ret = array(
                'href' =>  get_permalink( get_option('page_for_posts') ),
                'title' => __('Blog', 'ddl-layouts'),
                'type' => 'archives',
                'types' => __('Archives', 'ddl-layouts')
            );
        } else if ($loop == self::OPTION_SEARCH) {
            $ret = array(
                'href' => isset( $url_array['path'] ) ? get_bloginfo('url') . '/index.php?s=' : get_bloginfo('url') . '?s=',
                'title' => __('Search archives', 'ddl-layouts'),
                'type' => 'archives',
                'types' => __('Archives', 'ddl-layouts')
            );
        } elseif ($loop == self::OPTION_AUTHOR) {

            $user_ID = get_current_user_id();

            $ret = array(
                'href' => get_author_posts_url($user_ID),
                'title' => __('Author archives', 'ddl-layouts'),
                'type' => 'archives',
                'types' => __('Archives', 'ddl-layouts')
            );
        } elseif ($loop == self::OPTION_YEAR) {

            $date = $this->get_last_post_date();

            $ret = array(
                'href' => get_year_link($date->year),
                'title' => __('Year archives', 'ddl-layouts'),
                'type' => 'archives',
                'types' => __('Archives', 'ddl-layouts')
            );
        } elseif ($loop == self::OPTION_MONTH) {
            $date = $this->get_last_post_date();

            $ret = array(
                'href' => get_month_link($date->year, $date->month),
                'title' => __('Month archives', 'ddl-layouts'),
                'type' => 'archives',
                'types' => __('Archives', 'ddl-layouts')
            );
        } elseif ($loop == self::OPTION_DAY) {
            $date = $this->get_last_post_date();

            $ret = array(
                'href' => get_day_link($date->year, $date->month, $date->day),
                'title' => __('Day archives', 'ddl-layouts'),
                'type' => 'archives',
                'types' => __('Archives', 'ddl-layouts')
            );
        } elseif (sizeof(explode(self::OPTION_TAXONOMY_PREFIX, $loop)) > 1) {
            $tax = explode(self::OPTION_TAXONOMY_PREFIX, $loop);
            $taxonomy = get_taxonomy($tax[1]);
            if ( isset($taxonomy->rewrite['slug']) ){
                $slug = $taxonomy->rewrite['slug'];
                $terms = array_values(get_terms($tax[1], array('hide_empty' => true)));
                $tax_label = $taxonomy->labels->singular_name;
            }else{
                $tax_label = __('Undefined taxonomy', 'ddl-layouts');
            }

            $ret = array(
                'href' => '',
                'title' => __(sprintf("%s archives", $tax_label), 'ddl-layouts'),
                'type' => 'archives',
                'types' => __('Archives', 'ddl-layouts')
            );

            if ( isset($taxonomy->rewrite['slug']) && is_array($terms) && count($terms) > 0 && isset($terms[0]) && is_object($terms[0]) && property_exists($terms[0], 'slug')) {


                if ($this->is_using_permalinks()) {
                    $link = get_bloginfo('url') . '/' . $slug . '/' . $terms[0]->slug;
                } else {

                    $link = get_bloginfo('url') . '?' . $slug . '=' . $terms[0]->slug;
                }

                $ret['href'] = $link;

            }else{
                $ret['href'] = '#';
            }
        } elseif (sizeof(explode(self::OPTION_TYPES_PREFIX, $loop)) > 1) {
            $type = explode(self::OPTION_TYPES_PREFIX, $loop);

            $post_type = get_post_type_object($type[1]);

            if ( isset($post_type->rewrite['slug']) ){
                if ($this->is_using_permalinks()) {
                    $link = get_bloginfo('url') . '/' . apply_filters('ddl-rules_for_post_types_archive_rewrite', $post_type->rewrite['slug'], $post_type->rewrite['slug'] );
                } else {
                    $link = get_bloginfo('url') . apply_filters('ddl-rules_for_post_types_archive_rewrite', '?post_type=' . $post_type->rewrite['slug'], $post_type->rewrite['slug'] );
                }
                $post_label = $post_type->labels->singular_name;
            }else{
                $link = '#';
                $post_label = is_object($post_type) && property_exists($post_type, 'labels') && is_object($post_type->labels) && $post_type->labels->singular_name ? $post_type->labels->singular_name : __('Undefined post type', 'ddl-layouts');
            }
            $ret = array(
                'href' => $link,
                'title' => __(sprintf("%s archives", $post_label), 'ddl-layouts'),
                'type' => 'archives',
                'types' => __('Archives', 'ddl-layouts'),
            );

        }

        return $ret;
    }

    public function rules_for_post_types_archive_rewrite( $uri_comp, $slug ){
        /**
         * Woocommerce shop page
         */
        $get_woocommerce_permalinks = get_option( 'woocommerce_permalinks' );
        
        if (is_array($get_woocommerce_permalinks) && array_key_exists('product_base', $get_woocommerce_permalinks)) {
            $product_base = $get_woocommerce_permalinks['product_base'];
        } else {
            $product_base = false;
        }
        
        
        if (
            ( $slug === 'product' && $product_base )  &&
            $this->is_using_permalinks() &&
            in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )
        ){
            $uri_comp = 'shop';
        }

        return $uri_comp;
    }

    function get_last_post_date()
    {

        $ret = array();

        // get_lastpostdate built in function retrieves date for whatever post type while we
        // want posts not to break the archive display
        // if no posts are available we use get_lastpostdate to prevent interpreter to throw errors
        // and we expect a 404 page in FE
        $args = array(
            'posts_per_page' => 1,
            'offset' => 0,
            'category' => '',
            'orderby' => 'post_date',
            'order' => 'DESC',
            'include' => '',
            'exclude' => '',
            'meta_key' => '',
            'meta_value' => '',
            'post_type' => 'post',
            'post_mime_type' => '',
            'post_parent' => '',
            'post_status' => array('publish'),
            'suppress_filters' => false);

        $posts = get_posts($args);

        if ($posts && isset($posts[0])) {
            $date = $posts[0]->post_date;
        } else {
            $date = get_lastpostdate('blog');
        }

        $lastpost = strtotime($date);
        $ret['year'] = date('Y', $lastpost);
        $ret['month'] = date('m', $lastpost);
        $ret['day'] = date('d', $lastpost);

        return (object)$ret;
    }

    private function is_using_permalinks()
    {
        global $wp_rewrite;

        return $wp_rewrite->using_permalinks();
    }
}


class DDL_FilterArchive
{
    private $archive;

    function __construct($archive)
    {
        $this->archive = $archive;
    }

    function contains($string)
    {
        return strpos($this->archive, $string) !== false;
    }
}
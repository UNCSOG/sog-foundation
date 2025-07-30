<?php

use OTGS\Toolset\Common\Utils\RequestMode as RequestMode;
use OTGS\Toolset\Layouts\ClassesAuto\Gutenberg\PrivateLayout as PrivateLayout;

/**
 * Class WPDD_PostEditPageManager
 * @since 1.0
 */
class WPDD_PostEditPageManager {

    private static $instance;
    private $main = null;
    private $pagenow = '';
    private $create_object = null;
    private $post_type = null;
    private $post_id = null;
    private $has_post_content_cell = false;
    private $has_private_layout = false;
    private $private_layout_in_use = false;
    private $current_layout = null;
    private $post_title = '';
    private $main_template = 'default';
    private $decoder = null;
    private $gutenberg_create_private_layout;

    private static $FORBIDDEN_ACTIONS = array( 'inline-save', 'heartbeat', 'save_layout_data_front_end', 'wpml_save_job_ajax' );

    private static $WHITE_LIST = array(
        'cred-form',
        'cred-user-form',
        'nav_menu_item',
        'attachment',
        'wp-types-group',
        'view',
        'dd_layouts',
        'product_variation',
        'shop_order',
        'shop_coupon',
        'refunded',
        'failed',
        'revoked',
        'abandoned',
        'active',
        'inactive',
        'edd_discount',
        'edd_payment',
        'download',
        'product_variation',
        'shop_order',
        'shop_coupon',
        'shop_email',
        'wpsc_log',
        'wpsc-product-file',
        'wpsc-product',
	    'elementor_library'
    );

    /**
     *
     */
    private function __construct( $main = null, $pagenow = '', WPDD_json2layout $decoder = null ) {
        $this->pagenow = $pagenow;
        $this->main    = $main;
        $this->decoder = $decoder;
    }
    /* FIXME: we have a problem here, we changed the way scripts are enqued and we do not take $WHITE_LIST into account with the result that scripts load also in editors we don't want them to (see past months conflicts with CRED Edit pages). Unfortunately if we are using if( $this->in_white_list( $this->get_post_type() ) ) return; here tests are failing since they were written with the WRONG logic in mind. We need to change all this to avoid future conflicts */
    public function add_hooks(){
        if ( defined( 'JIGOSHOP_VERSION' ) ) {
            array_push( self::$WHITE_LIST, 'product' );
        }

        if ( is_admin() ) {

            if (
							$this->pagenow == 'post.php'
							&& isset( $_GET['post'] )
							&& isset( $_GET['action'] )
							&& $_GET['action'] === 'edit'
						) {
							$post_id = intval( $_GET['post'] );
							$post_object = get_post( $post_id );
							if ( ! $post_object ) {
								return;
							}
							$this->post_id = $post_id;
							$this->init();
							$this->has_private_layout    = WPDD_Utils::page_has_private_layout( $this->post_id );
							$this->private_layout_in_use = WPDD_Utils::is_private_layout_in_use( $this->post_id );

            } elseif ( $this->pagenow == 'post-new.php' ) {
                $this->post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : 'post';
                add_action( 'admin_print_scripts', array( &$this, 'init_on_post_create' ), 1 );
            }

            /*Actions at admin page for layout edit and layout*/
            if ( $this->pagenow == 'post.php' || $this->pagenow == 'post-new.php' || $this->pagenow == 'admin-ajax.php' ) {
                $this->main_template = $this->determine_main_template();

                /*
                 * Remove MetaBox, from 1.9 we will not have this selector anymore
                 */
                add_action( 'admin_enqueue_scripts', array( $this, 'page_edit_scripts' ) );
                add_action( 'admin_head', array( $this, 'wpddl_edit_template_options' ) );

                add_action( 'admin_enqueue_scripts', array( $this, 'page_edit_styles' ) );
                /*Saving layout settings at post/page edit page*/
                add_action( 'save_post', array( $this, 'wpddl_save_post' ), 10, 2 );
            }

            WPDD_PostEditPrivateLayoutHelper::getInstance( new WPDDL_Private_Layout() );
            WPDD_PostEditPageTemplateLayoutHelper::getInstance( $this->main instanceof WPDD_Layouts ? $this->main->post_types_manager : null  );
            $this->gutenberg_create_private_layout = $this->get_create_private_layout_button_for_gutenberg( $this->post_id, $this->pagenow );
            $this->gutenberg_create_private_layout->add_hooks();

            add_filter( 'screen_options_show_screen', array( &$this, 'remove_screen_options' ), 10, 2 );

        } else {
            add_action( 'save_post', array( $this, 'wpddl_save_post' ), 10, 2 );
        }
    }

    private function get_create_private_layout_button_for_gutenberg( $post_id, $pagenow ){
    	$condition = $this->get_toolset_condition_gutenberg_editor( $pagenow );
    	$request_mode = $this->get_toolset_request_mode();
    	$constants = $this->get_toolset_constants( );
    	return new PrivateLayout\CreateButton( $condition, $request_mode, $constants, $post_id );
	}

	private function get_toolset_condition_gutenberg_editor( $pagenow ) {
    	return new PrivateLayout\ConditionPostEditor( $pagenow );
	}

	private function get_toolset_request_mode( ) {
    	return new RequestMode();
	}

	private function get_toolset_constants( ) {
    	return new Toolset_Constants( );
	}

    private function in_white_list( $post_type ){

        if( ! $post_type ) return true;

	    $in_white_list     = in_array( $post_type, self::$WHITE_LIST );

	    if( $in_white_list ) return true;

	    return false;
    }

    public function get_post_id(){
        return $this->post_id;
    }

    private function init() {

        $post_object = get_post( $this->post_id );
				if ( ! $post_object ) {
					return;
				}
        $this->post_type   = &$post_object->post_type;
        $this->post_title  = &$post_object->post_title;
        $post_type_object  = get_post_type_object( $post_object->post_type );
        $missing_post_loop = ddl_has_feature( 'warn-missing-post-loop-cell' );
        $in_white_list     = in_array( $this->post_type, self::$WHITE_LIST );

        $woocommerce_support_message = $this->main->post_types_manager->check_layout_template_for_woocommerce( $post_type_object );

        // if we are in forbidden post types edit page don't do anything
        if ( $missing_post_loop === false || $in_white_list  || $woocommerce_support_message !== '' ) {
            return;
        }

        $this->show_hide_content_editor_in_post_edit_page();
        $this->add_create_layout_support( $post_object );
    }

    public function get_post_type(){
        return $this->post_type;
    }

    private function determine_main_template() {

        $for_pages = $this->main->post_types_manager->get_layout_template_for_post_type( $this->post_type );
        $page_php  = $for_pages === 'default' ? 'page.php' : $for_pages;

        return apply_filters( 'ddl-determine_main_template', $page_php, $for_pages, $this->post_type );
    }

    /**
     * @param $display_boolean
     * @param $wp_screen_object
     *
     * @return bool
     *  Avoid to show visibility option for Layouts metabox for page post type
     */
    function remove_screen_options( $display_boolean, $wp_screen_object ) {
        if ( 'blocks' === apply_filters( 'toolset_views_flavour_installed', 'classic' ) ) {
            // Using Toolset Blocks - means there is no layouts meta box anyway.
            return $display_boolean;
        }

        global $wp_meta_boxes;

        if ( $wp_screen_object->post_type === 'page' && isset( $wp_meta_boxes[ $wp_screen_object->id ] ) ) {
            $meta_box = $wp_meta_boxes[ $wp_screen_object->id ]['side']['high']['wpddl_template'];
            unset( $wp_meta_boxes[ $wp_screen_object->id ]['side']['high']['wpddl_template'] );
            $wp_screen_object->render_screen_options();
            $wp_meta_boxes[ $wp_screen_object->id ]['side']['high']['wpddl_template'] = $meta_box;
        }

        return $display_boolean;
    }


    function wpddl_edit_template_options() {
		if ( 'blocks' === apply_filters( 'toolset_views_flavour_installed', 'classic' ) ) {
			// Using Toolset Blocks, so skip Layouts.
			return;
		}

        global $post;

        if ( ! is_object( $post ) ) {
            return;
        }

	    if( $this->in_white_list( $this->get_post_type() ) ) return;

        $post_object = get_post_type_object( $post->post_type );

        if ( ( $post_object->publicly_queryable || $post_object->public ) ) {
            add_meta_box( 'wpddl_template', __( 'Template Layout', 'wpdd-layout' ), array(
                $this,
                'meta_box'
            ), $post->post_type, 'side', 'high' );
        }
    }

    /**/
    public static function this_page_template_have_layout( $post_id ) {
        return WPDD_Utils::this_page_template_have_layout( $post_id );
    }

    public static function page_templates_have_layout() {
        return WPDD_Utils::page_templates_have_layout();
    }

    public static function post_type_template_have_layout( $post_type ) {
        return WPDD_Utils::post_type_template_have_layout( $post_type );
    }

    public function init_on_post_create() {
        global $post;
				if ( ! $post ) {
					return;
				}
        $this->post_id = $post->ID;
        $this->init();
    }

    private function add_create_layout_support( $post ) {
        $this->create_object = new WPDD_CreateLayoutForSinglePage( $post, $this->main );
        add_action( 'ddl_add_create_layout_button', array( &$this, 'add_create_button' ) );
        add_action( 'ddl-create-layout-from-page-extra-fields', array( &$this, 'add_create_extra_fields' ) );
    }

    public function show_hide_content_editor_in_post_edit_page() {
        $this->has_post_content_cell = $this->has_layout_with_post_content_cell( $this->post_id, $this->post_type );

        add_action( 'edit_form_after_title', array( &$this, 'include_overlay_template' ) );
        add_action( 'admin_print_scripts', array( &$this, 'ddl_post_editor_overrides_scripts' ), 110 );
        add_action( 'edit_form_after_editor', array( &$this, 'print_alternate_content_in_place_of_editor' ) );
    }

    public function include_overlay_template() {
        $private_layout_in_use = false;
        $has_private_layout    = false;

        global $post;
        $post_type = get_post_type( $post );

        $has_private_layout = $this->has_private_layout;
        if ( $has_private_layout ) {
            $layout_slug           = WPDD_Utils::page_has_private_layout( $post->ID );
            $private_layout_in_use = WPDD_Utils::is_private_layout_in_use( $post->ID );
        }

        $additional_cells = $this->get_display_post_content_cells();
        include_once WPDDL_GUI_ABSPATH . 'templates/layout-post-edit-page-post-content-cell-overlay.tpl.php';
    }

    private function get_display_post_content_cells() {

        $post_content_cells = array();
        $cells              = $this->main->get_registered_cells();

        foreach ( $cells as $cell ) {
            $data = $cell->get_cell_data();
            if ( isset( $data['displays-post-content'] ) && $data['displays-post-content'] === true ) {
                $post_content_cells[] = sprintf( __( 'or a "%s" cell', 'ddl-layouts' ), $data['name'] );

            }
        }

        if ( count( $post_content_cells ) === 0 ) {
            return '';
        }

        return implode( $post_content_cells );
    }

    public function print_alternate_content_in_place_of_editor() {
        ob_start() ?>
        <div class="ddl-post-content-message-in-post-editor js-ddl-post-content-message-in-post-editor toolset-alert">
        </div>
        <?php
        echo ob_get_clean();
    }

    /**
     *
     */
    public function ddl_post_editor_overrides_scripts() {
        $layouts = $this->get_eligible_layouts_for_assignation();

        $this->has_post_content_cell = $this->current_layout ? $this->current_layout->has_post_content_cell : false;

        do_action( 'toolset_enqueue_styles', array(
            'toolset-notifications-css',
            'toolset-colorbox',
            'ddl-dialogs-forms-css',
            'wpt-toolset-backend',
            'ddl-dialogs-general-css',
        ) );

        do_action( 'toolset_enqueue_scripts', array(
            'ddl-post-editor-overrides'
        ) );

        global $wp_version;

        do_action( 'toolset_localize_script', 'ddl-post-editor-overrides', 'DDLayout_settings', array(
            'strings' => array(),
            'DDL_JS'  => array(
                'post'                              => array(
                    'ID'                    => $this->post_id,
                    'post_type'             => $this->post_type,
                    'post_title'            => $this->post_title,
                    'has_post_content_cell' => $this->has_post_content_cell,
                    'has_private_layout'    => $this->has_private_layout,
                    'private_layout_in_use' => $this->private_layout_in_use

                ),
                'post_edit_page'                    => true,
                'layout'                            => $this->current_layout,
                'layouts'                           => $layouts,
                'ddl_switch_layout_from_post_nonce' => wp_create_nonce( 'ddl_switch_layout_from_post_nonce' ),
                'message_same'                      => sprintf( __( 'The selected layout is already assigned to %s' ), '' ),
                'current_template'                  => get_page_template_slug( $this->post_id ),
                'no_items_found_message' => __( 'Sorry, nothing found!','ddl-layouts' ),
				'isWpmlActive' => apply_filters( 'ddl-is_wpml_active_and_configured', false ),
				'wpVersion503' => version_compare( $wp_version, '5.0.3' )
            )
        ) );
    }

    public function get_eligible_layouts_for_assignation() {
		$cache = apply_filters( 'ddl_get_elfa_cache', array() );

		if ( false !== $cache ) {
			return $cache;
		}

        $ret = array();

        $args = array(
            "status"                 => "publish",
            "order_by"               => "title",
            "fields"                 => "ids",
            "return_query"           => false,
            "no_found_rows"          => true,
            "update_post_term_cache" => false,
            "update_post_meta_cache" => false,
            "cache_results"          => true,
            "order"                  => "ASC",
            "post_type"              => WPDDL_LAYOUTS_POST_TYPE
        );

        $layouts = DDL_GroupedLayouts::get_all_layouts_as_posts( $args );

        foreach ( $layouts as $layout ) {
            $clone = WPDD_Layouts::get_layout_settings( $layout, true );

            if ( is_object( $clone ) === false ) {
                continue;
            }

            $opts = clone $clone;

            if ( is_object( $opts ) && ( property_exists( $opts, 'has_child' ) === false || property_exists( $opts, 'has_child' ) && $opts->has_child === false ) ) {
                $opts  = $this->get_post_content_cell( $opts );
                $ret[] = self::_filter_fields_to_keep( $opts );
            }
		}

		do_action( 'ddl_set_elfa_cache', $ret );

        return $ret;
    }

    public function get_post_content_cell( $opts ) {

        $test              = $this->decoder;
        $layout            = $test->json_decode( wp_json_encode( $opts ) );
        $cell_post_content = $layout->has_cell_of_type( 'cell-post-content' );

        $cells_types = apply_filters( 'ddl-get_cell_types_without_overlay', array( 'cell-content-template' ) );

        $cell_content_template = array();


        foreach ( $cells_types as $cells_type ) {
            $tmp                   = $layout->get_all_cells_of_type( $cells_type );
            $cell_content_template = array_merge( $cell_content_template, $tmp );
        }

        if ( $cell_post_content ) {
            $opts->cell_post_content_type = 'cell-post-content';

        } elseif ( count( $cell_content_template ) > 0 ) {
            if ( WPDD_Utils::content_template_cell_has_body_tag( $cell_content_template ) ) {
                $opts->cell_post_content_type = 'cell-content-template';
                $opts->has_post_content_cell  = true;
            } else {
                $opts->cell_post_content_type = 'cell-content-template-no-body';
                $opts->has_post_content_cell  = false;
            }

        }

        $cell_visual_editor = $layout->get_all_cells_of_type( 'cell-text' );

        if ( count( $cell_visual_editor ) > 0 ) {
            $cell_visual_editor_has_post_content = WPDD_Utils::visual_editor_cell_has_wpvbody_tag( $cell_visual_editor );
            if ( $cell_visual_editor_has_post_content !== '' ) {
                $opts->has_post_content_cell  = true;
                $opts->cell_post_content_type = $cell_visual_editor_has_post_content;
            }
        } else {
            if ( property_exists( $opts, 'cell_post_content_type' ) === false ) {
                $opts->cell_post_content_type = '';
                $opts->has_post_content_cell  = false;
            }
        }

        return $opts;
    }


    public static function _filter_fields_to_keep( $obj ) {
        $preserve = array(
            'id',
            'slug',
            'name',
            'has_post_content_cell',
            'cell_post_content_type',
            'post_content_icon'
        );

        foreach ( $obj as $key => $val ) {
            if ( in_array( $key, $preserve ) === false ) {
                unset( $obj->{$key} );

            }
        }

        if ( property_exists( $obj, 'has_post_content_cell' ) === false ) {
            $obj->has_post_content_cell = false;
        }

        return $obj;
    }

    public function has_layout_with_post_content_cell( $post_id, $post_type ) {
        $layout_id = $this->get_layout_id( $post_id, $post_type );

        if ( $layout_id === null ) {
            return false;
        }

        $this->current_layout = $this->set_layout_id_for_json( $layout_id );

        if ( ! $this->current_layout ) {
            return false;
        }

        return property_exists( $this->current_layout, 'has_post_content_cell' ) ? $this->current_layout->has_post_content_cell : false;
    }

    private function get_layout_id( $post_id, $post_type ) {

        if ( $this->pagenow == 'post-new.php' ) {

            $layout = $this->main->post_types_manager->get_layout_to_type_object( $post_type );

            if ( null === $layout ) {
                return null;
            }

            return $layout->layout_id;
        } else {

            $layout_slug = self::page_has_layout( $post_id );

            if ( $layout_slug === false ) {
                return null;
            }

            return $this->main->get_layout_id( $layout_slug );
        }
    }

    private function set_layout_id_for_json( $layout_id ) {

        $settings = apply_filters( 'ddl-get_layout_settings', $layout_id, true );

        if ( is_object( $settings ) === false ) {
            return null;
        }

        $ret = clone $settings;

        $ret = $this->get_post_content_cell( $ret );

        $ret = $this->_filter_fields_to_keep( $ret );

        return $ret;
    }

    public function add_create_button() {
        if ( is_null( $this->create_object ) ) {
            return;
        }

        $this->create_object->add_button();
    }

    public function add_create_extra_fields() {
        if ( is_null( $this->create_object ) ) {
            return;
        }

        $this->create_object->add_create_extra_fields();
    }

    public static function page_has_layout( $post_id ) {
        return WPDD_Utils::page_has_layout( $post_id );
    }

    public static function getInstance( $main = null, $pagenow = '', WPDD_json2layout $decoder = null ) {
        if ( ! self::$instance ) {
            self::$instance = new WPDD_PostEditPageManager( $main, $pagenow, $decoder );
        }

        return self::$instance;
    }

    public static function page_template_has_layout( $post_id ) {
        return WPDD_Utils::page_template_has_layout( $post_id );
    }


    function meta_box( $post ) {
        // we assume there are no data for layouts
        $layout_data = null;
        $show_template_layout_selector = true;
        $post_type_obj                 = get_post_type_object( $post->post_type );


        $woocommerce_support_message = $this->main->post_types_manager->check_layout_template_for_woocommerce( $post_type_obj );
        if ( $woocommerce_support_message ) {
            ?>
            <p class=" toolset-alert toolset-alert-warning js-layout-support-missing">
                <?php echo $woocommerce_support_message; ?>
            </p>
            <?php
            return;
        }

        if ( isset( $_GET['post'] ) ) {
            // get the value for "_layouts_template"
            $template_selected = get_post_meta( $_GET['post'], WPDDL_LAYOUTS_META_KEY, true );
            // if there is a template
            if( $template_selected ){
                // let's make sure there is layout_data corresponding and store it
                $layout_data = WPDD_Utils::get_layout_by_slug( $template_selected );
                // if no layout object exist then we assume we are not using any (existing) layout
                if( !is_object( $layout_data ) ){
                    $template_selected = '';
                    do_action( 'ddl-delete_layouts_template_dirt', $_GET['post'], $layout_data );
                }
            }
        } else {
            $template_selected = '';
        }

        $post_type_theme = $this->main->post_types_manager->get_layout_template_for_post_type( $post->post_type );
        $theme_template  = $post_type_theme == 'default' ? basename( get_page_template() ) : $post_type_theme;

        ?>

        <div id="js_show_template_layout_selector"
             <?php if ( $show_template_layout_selector === false ): ?>style="display: none;"<?php endif; ?>>
            <div class="js-dd-layout-selector"
                 <?php if ( $template_selected === '' ): ?>style="display:block;"<?php endif; ?>>

                <script type="text/javascript">
                    var ddl_old_template_text = "<?php echo esc_js( __( 'Template', 'ddl-layouts' ) ); ?>";
                </script>
                <input type="hidden" name="ddl-namespace-post-type-tpl"
                       value="<?php echo $post_type_theme == 'default' ? 'default' : $theme_template; ?>"
                       class="js-ddl-namespace-post-type-tpl"/>

                <select name="layouts_template" class="ddl-layouts-selector-menu" id="ddl-js-layout-template-name" <?php disabled( $post->post_type == 'attachment' || user_can_assign_layouts() === false );?> >
                    <option value="-1" data-id="-1"><?php _e("Don't use a layout","ddl-layouts");?></option>
                </select>
                <input type="button" class="button js-confirm-template-layout-change" <?php disabled( $post->post_type == 'attachment' || user_can_assign_layouts() === false );?> value="<?php echo esc_attr( __( 'OK', 'dd-layouts' ) );?>">
                <input type="hidden" class="js-wpddl-default-template-message" value="<?php echo $this->main_template;?>" data-message="<?php echo esc_attr( __( 'Show all templates', 'ddl-layouts' ) ); ?>" />

                <span <?php if ( $post->post_type == 'attachment' ): ?>style="display:none;"<?php endif; ?>
                      class="ddl-centred-text-separator"><?php _e( 'OR', 'dd-layouts' ); ?></span>


                <?php do_action( 'ddl_add_create_layout_button' ); ?>
                <div class="display_errors js-display-errors"></div>
                <p class="toolset-alert toolset-alert-warning js-layout-support-warning" style="display:none"></p>


                <?php wp_nonce_field( 'wp_nonce_ddl_dismiss', 'wp_nonce_ddl_dismiss' ); ?>
            </div>


            <div class="ddl-layout-selected"
                 <?php if ( $template_selected === '' ): ?>style="display:none;"<?php endif; ?>>

                <b><?php _e( 'Selected template Layout:', 'dd-layouts' ); ?></b> <span
                        id="js_selected_layout_template_name"><?php if ( is_object( $layout_data ) && property_exists( $layout_data, 'name' ) ) {
                        echo $layout_data->name;
                    } ?></span>

                <?php if ( $post->post_type !== 'attachment' && user_can_edit_layouts() ): ?>
                    <br>
                    <a data-href="<?php echo admin_url() . 'admin.php?page=dd_layouts_edit&action=edit&layout_id='; ?>"
                       class="button edit-layout-template js-edit-layout-template"><?php _e( 'Edit this layout', 'ddl-layouts' ); ?></a>
                    <br><br><?php endif; ?>
		<?php if ( $post->post_type !== 'attachment' && ( user_can_assign_layouts() || user_can_edit_layouts() ) ): ?>
                    <a href="#"
                       class="js_ddl_stop_using_this_template_layout"><?php _e( 'Stop using this Template Layout', 'dd-layouts' ); ?></a>
		<?php endif; ?>
            </div>
        </div>
        <input type="hidden" id="js-assigned-layout-id"
               value="<?php if ( is_object( $layout_data ) && property_exists( $layout_data, 'id' ) ) {
                   echo $layout_data->id;
               } ?>">

        <?php

    }

    function page_edit_scripts() {
		if ( 'blocks' === apply_filters( 'toolset_views_flavour_installed', 'classic' ) ) {
			// Using Toolset Blocks, so skip Layouts.
			return;
		}

        do_action( 'toolset_enqueue_scripts', array(
            'toolset_select2',
            'ddl-post-overrides',
            'toolset-chosen',
            'ddl_post_edit_page',
        ) );

        $opts = $this->main->layout_get_templates_options_object();

        if ( $this->main_template !== 'default' && in_array( $this->main_template, $opts->layout_templates ) === false ) {
            $opts->layout_templates[] = $this->main_template;
        }

        $this->has_post_content_cell = $this->has_layout_with_post_content_cell( $this->post_id, $this->post_type );

        $selected_template = apply_filters( 'ddl_get_page_template', $this->post_id );

        do_action( 'toolset_localize_script', 'ddl_post_edit_page', 'DDLayout_settings_post_edit', array(
            'strings'                  => array(
                'content_template_diabled' => __( 'Since this page uses a layout, styling with a Content Template is disabled.', 'ddl-layouts' ),
                'layout_has_loop_cell'     => __( 'This layout has a WordPress Archive cell and shouldn\'t be used for single posts of this post type.', 'ddl-layouts' )
            ),
            'DDL_JS' => array(
                'post' => array(
                    'ID'                    => $this->post_id,
                    'private_layout_in_use' => $this->private_layout_in_use
                ),
                'layout'                            => $this->current_layout,
                'no_items_found_message'            => __( 'Sorry, nothing found!', 'ddl-layouts' )
            ),
            'layout_templates'         => $opts->layout_templates,
            'layout_template_defaults' => $opts->template_option,
            'selected_template'        => $selected_template ? $selected_template : 'default'
        ) );
    }

    function page_edit_styles() {
        do_action( 'toolset_enqueue_styles', array(
            'toolset-notifications-css',
            'toolset-select2-css',
            'wp-layouts-pages',
            'toolset-chosen-styles'
        ) );
	}

	/**
	 * Check whether the save_post action is being executed while saving another post in its native post edit page.
	 * Used to avoid actions on secondary post savings when a post is being updated.
	 *
	 * @param int $post_id The ID of the post to check against.
	 * @return bool
	 * @since 2.6.3
	 */
	private function is_natively_saving_another_post( $post_id ) {
		if (
			in_array( $this->pagenow, array( 'post.php', 'post-new.php' ), true )
			&& 'editpost' === toolset_getpost( 'action' )
			&& $post_id != toolset_getpost( 'post_ID' )
		) {
			// We are in the native post edit page,
			// we are saving a post,
			// but the post being saved does not match the passed ID.
			return true;
		}
		return false;
	}

    function wpddl_save_post( $pidd ) {

        if ( user_can_assign_layouts() === false ) {
            return;
		} // prevent anything to happen since layout is always null here

		if ( $this->is_natively_saving_another_post( $pidd ) ) {
			// We are saving another post from its native post edit page,
			// and saving this one got mixed in the middle.
			return;
		}

        // get array with list of actions where assignements should not be changed
        $forbidden_actions = apply_filters( 'ddl_do_not_update_layout_for_actions', self::$FORBIDDEN_ACTIONS );

        if (
			$_POST
			&& isset( $_POST['action'] )
			&& in_array( $_POST['action'], $forbidden_actions ) === false
		) {
            // Currently assigned layout for this post.
            $layout_template = WPDD_Utils::page_has_layout( isset( $_POST['post_ID'] ) ? $_POST['post_ID'] : null );

			// Selected layout to apply.
            $layout_selected = isset( $_POST['layouts_template'] ) && $_POST['layouts_template'] !== '-1' ? $_POST['layouts_template'] : null;

			if ( $layout_template === $layout_selected ) {
				// Nothing left to do, just leave!
				return;
			}

            if ( $layout_selected ) {
				// A new layout was selected: let's save it!
				$tpl = isset( $_POST['page_template'] ) ? $_POST['page_template'] : null;
				WPDD_Utils::assign_layout_to_post_object( $pidd, $layout_selected, $tpl );

				return;
            }

			// Now, deal with removing the assigned layout...

			// Do not remove assignments for specific actions.
            if (
                ( isset( $_POST['action'] ) && $_POST['action'] === 'wcml_update_product' ) ||
                ( isset( $_POST['render_private'] ) &&  $_POST['render_private'] ==='ddl_update_post_content_for_private_layout' )
            ) {
                return;
            }

            WPDD_Utils::remove_layout_assignment_to_post_object( $pidd, '', false );

        }
    }

}

class WPDD_CreateLayoutForSinglePage {

    private $main = null;
    private $post_id = 0;
    private $post = null;
    private $post_type = null;

    public function __construct( &$post, $main = null ) {

        if ( null === $post || ! is_object( $post ) || property_exists( $post, 'ID' ) === false ) {
            return;
        }
        $this->main      = $main;
        $this->post      = &$post;
        $this->post_id   = $post->ID;
        $this->post_type = $post->post_type;
        add_action( 'admin_print_scripts', array( &$this, 'handle_scripts' ), 99 );
    }

    public function add_button() {
        ob_start();
        ?>
        <div class="create-layout-for-page-wrap hidden">
            <?php
            do_action( 'ddl_create_layout_for_this_cpt' );
            ?>
        </div>
        <?php
        $this->include_creation_php();
        echo ob_get_clean();
    }

    public function add_create_extra_fields() {
        ob_start(); ?>
        <input type="hidden" name="associate-post-upon-creation" id="js-associate-post-upon-creation"
               value="<?php echo $this->post_id; ?>"/>
        <?php
        echo ob_get_clean();
    }

    public function include_creation_php() {
        if ( class_exists( 'WPDDL_Admin_Pages' ) ) {
            WPDDL_Admin_Pages::getInstance()->include_nonce();
        }
    }

    public function handle_scripts() {

        if ( $this->main->is_embedded() ) {
            return;
        }

        do_action( 'toolset_enqueue_styles', array(
            'toolset-notifications-css',
            'toolset-colorbox',
            'ddl-dialogs-forms-css',
            'wpt-toolset-backend',
            'ddl-dialogs-general-css',
            'toolset-chosen-styles'
        ));

        do_action( 'toolset_enqueue_scripts', array(
            /*'layouts-prototypes',*/
            'toolset-utils',
            'wp-layouts-dialogs-script',
            'ddl_create_new_layout',
            'ddl-create-for-pages',
            'toolset-chosen'
        ) );

        $post_type_obj = get_post_type_object( $this->post_type );

        do_action( 'toolset_localize_script', 'ddl-create-for-pages', 'DDLayout_settings_create', array(
            'strings' => array(),
            'user_can_create' => user_can_create_layouts(),
            'user_can_create_private' => user_can_create_private_layouts(),
            'DDL_JS'  => array(
                'post'                  => array(
                    'post_title'      => $this->post->post_title,
                    'post_id'         => $this->post_id,
                    'post_type'       => $this->post_type,
                    'post_name'       => $this->post->post_name,
                    'post_type_label' => $post_type_obj->label,
                ),
                'new_layout_title_text' => sprintf( __( 'Layout for %s' ), $this->post->post_title )
            )
        ) );
    }
}

class WPDD_PostEditPrivateLayoutHelper {

    private static $instance;

	public $PrivateLayout;

    private function __construct( WPDDL_Private_Layout $private_layout ) {

        $this->PrivateLayout = $private_layout;

        add_action( 'wp_ajax_ddl_private_layout_in_use_status_update', array(
            $this,
            'private_layout_in_use_status_update'
        ) );
    }

    public static function getInstance( WPDDL_Private_Layout $private_layout = null ) {
        if ( ! self::$instance ) {
            self::$instance = new WPDD_PostEditPrivateLayoutHelper( $private_layout );
        }

        return self::$instance;
    }

    public function private_layout_in_use_status_update() {

        if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'ddl_update_private_layout_status' ) ) {
            die( 'verification failed' );
        }

        $status_value  = ( $_POST['status'] === 'yes' ) ? 'yes' : false;
        $update_status = update_post_meta( $_POST['content_id'], WPDDL_PRIVATE_LAYOUTS_IN_USE, $status_value );

        $get_original_content = false;

        if ( isset( $_POST['what_to_edit'] ) && $_POST['what_to_edit'] === 'original_content' ) {
            $get_original_content = $this->get_original_content_from_post_meta( $_POST['content_id'] );
            $this->PrivateLayout->update_post_content_with_private_layout_output( $get_original_content, $_POST['content_id'] );
            $get_original_content = nl2br( $get_original_content );
        } elseif ( isset( $_POST['what_to_edit'] ) && $_POST['what_to_edit'] === 'layout_output' ) {
            $get_original_content = $this->clean_up_front_end_editor_dirt_from_post_content( $_POST['content_id'] );
        }

        // remove meta key
        if ( $status_value === false ) {
            delete_post_meta( $_POST['content_id'], WPDDL_PRIVATE_LAYOUTS_ORIGINAL_CONTENT_META_KEY );
        }

        wp_send_json( array(
            'status'           => $update_status,
            'layout_id'        => $_POST['layout_id'],
            'original_content' => $get_original_content
        ) );
    }

    private function get_original_content_from_post_meta( $post_id ) {
        return get_post_meta( $post_id, WPDDL_PRIVATE_LAYOUTS_ORIGINAL_CONTENT_META_KEY, true );
    }

    private function clean_up_front_end_editor_dirt_from_post_content( $post_id ) {
        $post    = get_post( $post_id );
        $content = $this->clean_up_content( $post->post_content );

        $data = array(
            'ID'           => $post_id,
            'post_content' => $content,
        );

        if ( wp_update_post( $data ) ) {
            return $content;
        } else {
            return $post->post_content;
        }
    }

    private function clean_up_content( $content ){
        // remove extra FE editor classes
        $content = str_replace( 'ddl-frontend-editor-row', '', $content );
        $content = str_replace( 'ddl-frontend-editor-editable', '', $content );
        $content = str_replace( 'js-ddl-frontend-editor-cell', '', $content );
        $content = str_replace( 'ddl-frontend-editor-cell', '', $content );
        // remove FE editor data attributes
        $content = preg_replace( '/(<[^>]+) data-id=\'.*?\'/i', '$1', $content );
        $content = preg_replace( '/(<[^>]+) data-name=\'.*?\'/i', '$1', $content );
        $content = preg_replace( '/(<[^>]+) data-type=\'.*?\'/i', '$1', $content );
        $content = preg_replace( '/(<[^>]+) data-layout_slug=\'.*?\'/i', '$1', $content );
        $content = preg_replace( '/(<[^>]+) data-kind=\'.*?\'/i', '$1', $content );

        return $content;
    }
}

class WPDD_PostEditPageTemplateLayoutHelper{
    private static $instance;
    private $post_types_manager = null;

    private function __construct( WPDD_Layouts_PostTypesManager $post_types_manager ) {
        $this->post_types_manager = $post_types_manager;
        add_action( 'wp_ajax_ddl_update_template_layout', array( $this, 'update_template_layout' ) );
        add_action( 'wp_ajax_ddl_stop_using_template_layout', array( $this, 'ddl_stop_using_template_layout' ) );
        add_action( 'wp_ajax_ddl_load_selector_items', array( $this, 'generate_layout_selector' ) );
        add_filter( 'hidden_meta_boxes', array( &$this, 'force_showing_template_selector_on_pages' ) );
    }

    public static function getInstance( WPDD_Layouts_PostTypesManager $post_types_manager = null ) {
        if ( ! self::$instance ) {
            self::$instance = new WPDD_PostEditPageTemplateLayoutHelper( $post_types_manager );
        }

        return self::$instance;
    }

    public function update_template_layout() {

        if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'ddl_update_private_layout_status' ) ) {
            die( 'verification failed' );
        }

        if ( user_can_assign_layouts() === false ) {
            die( 'you are not authorized to change layout assignment' );
        }

        $send = array();

        if ( isset( $_POST ) && $_POST['post_id'] && $_POST['layout_slug'] && $_POST['layout_slug'] !== '0' ) {

            $meta = $this->update_layout_for_page( $_POST['layout_slug'], $_POST['post_id'] );
            $send = array(
                'message' => array(
                    'meta'    => $meta,
                    'current' => $_POST['layout_id'],
                    'post_id' => $_POST['post_id'],
                    'key'     => WPDDL_LAYOUTS_META_KEY
                )
            );
        }

        wp_send_json( $send );
    }

    public function ddl_stop_using_template_layout() {

        if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'ddl_update_private_layout_status' ) ) {
            wp_send_json_error( array( 'error' => 'verification failed' ) );
        }

		$remove_post_meta = false;

        if ( isset( $_POST ) && $_POST['post_id'] ) {
            $remove_post_meta = WPDD_Utils::remove_layout_assignment_to_post_object( $_POST['post_id'] );
        }

        wp_send_json( array( 'message' => $remove_post_meta ) );
    }

    private function update_layout_for_page( $layout_slug, $post_id ) {
        return WPDD_Utils::assign_layout_to_post_object( $post_id, $layout_slug, null );
    }

    function generate_layout_selector() {

        if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'ddl_update_private_layout_status' ) ) {
            wp_send_json_error( array( 'error' => 'verification failed' ) );
        }
        if ( user_can_assign_layouts() === false ) {
            die( json_encode( array() ) );
        }

        $post = get_post( $_POST['post_id'] );

        $possible_layouts = array();

        $layout_templates_available = WPDD_Layouts_Cache_Singleton::get_published_layouts_with_all_data();

        if ( isset( $post ) && $post->ID ) {
            $template_selected = get_post_meta( $post->ID, WPDDL_LAYOUTS_META_KEY, true );
        } else {
            $template_selected = '';
        }

        if(isset($post)){
            $post_type_layout = $this->post_types_manager->get_layout_to_type_object( $post->post_type );
        } else {
            $post_type_layout = null;
        }


        if ( isset( $layout_templates_available ) ) {
            foreach ( $layout_templates_available as $key => $single_layout ) {

                // get layouts data
                $get_single_layout_data         = json_decode( $single_layout->layout_settings );
                $is_layout_assigned_to_archives = apply_filters( 'ddl-get_layout_loops', $single_layout->ID );

                // remove layout from the list if it has a loop or it is assigned to archive
                if ( ( ! is_object( $get_single_layout_data ) || ( property_exists( $get_single_layout_data, 'has_loop' ) && $get_single_layout_data->has_loop === true ) || count( $is_layout_assigned_to_archives ) > 0 || ( property_exists( $get_single_layout_data, 'has_child' ) && $get_single_layout_data->has_child === true ) ) && $single_layout->post_name !== $template_selected ) {
                    unset( $layout_templates_available[ $key ] );
                }

            }
        }

        $template_selected = $this->wpml_layout_for_post_edit( $template_selected );

        if ( isset( $layout_templates_available ) ) {
            foreach ( $layout_templates_available as $template ) {
                $title    = ( $template->post_title !== '' ) ? $template->post_title : $template->post_name;
                $selected = false;
                if ( $template_selected == $template->post_name ) {
                    $selected = true;
                } elseif ( ! isset( $_GET['post'] ) && is_object( $post_type_layout ) && property_exists( $post_type_layout, 'layout_id' ) && (int) $template->ID === (int) $post_type_layout->layout_id ) {
                    $selected = true;
                }

                $possible_layouts[] = array(
                    "title"       => $title,
                    "selected"    => $selected,
                    "template_id" => esc_attr( $template->ID ),
                    "post_name"   => esc_attr( $template->post_name )
                );
            }
        }


        wp_send_json( $possible_layouts );

    }

    private function wpml_layout_for_post_edit( $template_selected ) {
        $source_post_id = apply_filters( 'wpml_new_post_source_id', null );

        if ( $source_post_id ) {
            $template_selected = get_post_meta( $source_post_id, WPDDL_LAYOUTS_META_KEY, true );
        }

        return $template_selected;
    }

    /**
     * @param $hidden
     * Make sure that Template selector is not hidden on the pages
     *
     * @return array with hidden meta boxes
     */
    function force_showing_template_selector_on_pages( $hidden ) {
        if ( ( $key = array_search( 'wpddl_template', $hidden ) ) !== false ) {
            unset( $hidden[ $key ] );
        }

        return $hidden;
    }

    //TODO: this is currently deprecated since the update happens when post is saved
    public function ddl_switch_layout_from_post_callback() {

        if ( WPDD_Utils::user_not_admin() ) {
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
        if ( wp_verify_nonce( $_POST['ddl_switch_layout_from_post_nonce'], 'ddl_switch_layout_from_post_nonce' ) ) {
            $this->post_id = $_POST['post_id'];
            $meta          = $this->update_layout_for_page( $_POST['layout_slug'], $_POST['post_id'] );
            $send          = wp_json_encode( array(
                'message' => array(
                    'meta'    => $meta,
                    'current' => $_POST['layout_id'],
                    'post_id' => $this->post_id,
                    'key'     => WPDDL_LAYOUTS_META_KEY
                )
            ) );
        } else {
            $send = WPDD_Utils::ajax_nonce_fail( __METHOD__ );
        }

        die( $send );
    }

}

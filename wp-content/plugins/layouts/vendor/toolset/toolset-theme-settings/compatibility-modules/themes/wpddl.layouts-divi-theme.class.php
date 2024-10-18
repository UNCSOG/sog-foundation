<?php

/**
 * Class Toolset_Compatibility_Theme_generatepress
 * @since layouts 2.0.2
 */
class Toolset_Compatibility_Theme_divi extends Toolset_Compatibility_Theme_Handler {

	protected $has_been_removed = false;
	protected $content_filter_added = false;
	protected $visual_editor_button_removed = false;

	protected function run_hooks() {
		add_action( 'get_header', array( $this, 'disable_pagination' ) );
		add_action( 'get_header', array( $this, 'disable_title' ) );
		add_action( 'get_header', array( $this, 'disable_archive_sidebar' ) );

		add_filter( 'body_class', array( $this, 'layout_body_class' ) );

		$layouts_active = new Toolset_Theme_Settings_Condition_Plugin_Layouts_Active();

		if ( $layouts_active->is_met() ) {
			add_filter( 'ddl-render_tab_cell', array( $this, 'render_tab_cell_js' ), 10, 3 );
			add_filter( 'ddl-accordion_open', array( $this, 'render_accordion_cell_js' ), 10, 2 );

			add_action( 'ddl_before_frontend_render_cell', array(
				$this,
				'remove_extra_instances_divi_builder'
			), 10, 2 );
			add_action( 'ddl_before_frontend_render_cell', array(
				$this,
				'remove_divi_builder_button_when_necessary'
			), 10, 2 );

			remove_action( 'woocommerce_before_main_content', 'et_divi_output_content_wrapper', 10 );
			remove_action( 'woocommerce_after_main_content', 'et_divi_output_content_wrapper_end', 10 );

			add_filter( 'get_the_archive_title', array( $this, 'toolset_woocommerce_show_page_title' ), 10, 1 );


			add_filter( 'toolset_add_registered_script', array( &$this, 'add_register_script' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ) );

			add_filter( 'ddl_do_not_update_layout_for_actions', array( $this, 'do_not_change_layout_assignment' ) );

			$this->hook_enqueue_backend_scripts();
			$this->hook_enqueue_backend_styles();
		}
	}


	public function disable_title() {
		$toolset_disable_title = apply_filters( 'toolset_theme_integration_get_setting', null, 'toolset_disable_title' );

		if ( 1 == $toolset_disable_title ) {
			$this->add_inline_style_for_title_entry();
		}
	}

	public function add_inline_style_for_title_entry() {
		wp_register_style( 'toolset-theme-settings', false );
		wp_enqueue_style( 'toolset-theme-settings' );

		$custom_css = '.entry-title { display: none; }';
		wp_add_inline_style( 'toolset-theme-settings', $custom_css );
	}


	public function disable_archive_sidebar() {
		$toolset_disable_archive_sidebar = apply_filters( 'toolset_theme_integration_get_setting', null, 'toolset_disable_archive_sidebar' );

		if ( 1 == $toolset_disable_archive_sidebar ) {
			add_filter( 'is_active_sidebar', array( $this, 'is_active_sidebar_callback' ), 10, 2 );
		}
	}

	public function is_active_sidebar_callback( $is_active_sidebar, $index ) {
		$sidebar_index = apply_filters( 'toolset_divi_active_sidebar_index', 'sidebar-1' );

		if ( $index === $sidebar_index ) {
			$is_active_sidebar = false;
		}

		return $is_active_sidebar;
	}

	/**
	 * Get value from theme integration settings filter and disable pagination image for current page if option is enabled
	 * We are doing this with small js code, since theme doesn't provide any hook to do it
	 */
	public function disable_pagination() {
		$toolset_disable_pagination = apply_filters( 'toolset_theme_integration_get_setting', null, 'toolset_disable_pagination' );

		if ( "1" == $toolset_disable_pagination ) {
			add_action( 'wp_head', array( $this, 'remove_pagination_box' ) );
		}
	}

	public function remove_pagination_box() {
		echo '<script type="text/javascript">jQuery(function() { jQuery("#et-main-area .pagination.clearfix").remove(); });</script>';
	}

	public function do_not_change_layout_assignment( $forbidden_actions = array() ) {
		array_push( $forbidden_actions, "et_fb_ajax_save" );

		return $forbidden_actions;
	}

	public function render_tab_cell_js( $output, $cell, $tab_id ) {
		ob_start(); ?>
        <script type="text/javascript">
            jQuery(function ( $ ) {
                $('#<?php echo $tab_id; ?>' + ' a').click(function ( e ) {
                    e.preventDefault();
                    $(this).tab('show');
                })
            });
        </script>
		<?php
		$output = $output . ob_get_clean();

		return $output;
	}

	public function render_accordion_cell_js( $content, $cell ) {
		$cell_id = $cell->get_unique_identifier();
		ob_start(); ?>

        <script type="text/javascript">
            jQuery(function ( $ ) {
                $('#<?php echo $cell_id; ?> .panel a').on('click', function ( e ) {
                    e.preventDefault();

                    $('#<?php echo $cell_id; ?> .panel a').each(function ( i ) {
                        var tt = $(this).attr('href');
                        $(this).addClass('collapsed');
                        $(tt).removeClass('in').addClass('collapse');
                    });

                    var t = $(this).attr("href");

                    if ( $(this).hasClass('collapsed') ) {
                        $(this).removeClass('collapsed');
                        $(t).removeClass('collapse').addClass('in');
                    } else {
                        $(this).addClass('collapsed');
                        $(t).addClass('collapse').removeClass('in');
                    }

                });
            });
        </script>

		<?php
		$content = $content . ob_get_clean();

		return $content;
	}

	/**
	 * In case when we are using Layouts for rendering page, and on layout we don't have any
	 * cell that can render post content we will remove Divi builder button since there is
	 * nothing that can be actually changed using Divi visual builder.
	 */
	public function remove_divi_builder_button_when_necessary( $cell, $renderer ) {

		if ( $this->visual_editor_button_removed === false ) {
			remove_action( 'admin_bar_menu', 'et_fb_add_admin_bar_link', 999 );
			$this->visual_editor_button_removed = true;
		}

		if ( ( ( $cell->get_cell_type() === 'cell-post-content' ) || ( $cell->get_cell_type() === 'cell-text' && $this->has_wpvbody_tag( array( $cell ) ) === true ) ) && $this->visual_editor_button_removed === true ) {
			add_action( 'admin_bar_menu', 'et_fb_add_admin_bar_link', 999 );
		}

		return $this->visual_editor_button_removed;

	}

	/**
	 * Removes extra instances of Divi Front-end Builder.
	 *
	 * These are invoked by `the_content` filter, added in /themes/Divi/includes/builder/frontend-builder/view.php
	 * Mainly the callback depends on is_main_query() and adds FE builder instance.
	 * Since, the cells (mentioned in $content_cells below) play with the main query, the above callback is trapped.
	 * We need to remove the filter after first execution, for these particular cells.
	 *
	 * @since 1.5
	 */
	public function remove_extra_instances_divi_builder( $cell, $renderer ) {
		// Cells playing with main query and causing `the_content` filter to be trapped.
		if ( isset( $_GET['et_fb'] ) && $_GET['et_fb'] === '1' ) {

			if ( $this->has_been_removed === false ) {
				// Remove the filter
				remove_filter( 'the_content', 'et_fb_app_boot', 1 );
				$this->has_been_removed = true;
			}

			if ( ( ( $cell->get_cell_type() === 'cell-post-content' && $cell->check_if_cell_renders_post_content() === true ) || ( $cell->get_cell_type() === 'cell-text' && $this->has_wpvbody_tag( array( $cell ) ) === true ) ) && $this->has_been_removed === true && $this->content_filter_added === false ) {
				add_filter( 'the_content', 'et_fb_app_boot', 1 );
				$this->has_been_removed     = false;
				$this->content_filter_added = true;
			}

			return $this->content_filter_added;
		}
	}

	/**
	 * Check do we have wpvbody tag inside any cell from the list
	 *
	 * @param $cells
	 *
	 * @return bool
	 */
	public function has_wpvbody_tag( $cells ) {
		return ( WPDD_Utils::visual_editor_cell_has_wpvbody_tag( $cells ) === '' ) ? false : true;
	}


	public function frontend_enqueue() {
		do_action( 'toolset_enqueue_styles', array( 'divi-overrides-css' ) );
		do_action( 'toolset_enqueue_scripts', array( 'divi-overrides' ) );

		if ( isset( $_GET['toolset_editor'] ) ) {
			do_action( 'toolset_enqueue_styles', array( 'divi-overrides-admin-css' ) );
			do_action( 'toolset_enqueue_scripts', array( 'divi-overrides-admin' ) );
		}
	}

	public function add_register_styles( $styles ) {

		$styles['divi-overrides-css']       = new WPDDL_style( 'divi-overrides-css', WPDDL_RES_RELPATH . '/css/themes/divi-overrides.css', array(), WPDDL_VERSION, 'screen' );
		$styles['divi-overrides-admin-css'] = new WPDDL_style( 'divi-overrides-admin-css', WPDDL_RES_RELPATH . '/css/themes/divi-backend-overrides.css', array(), WPDDL_VERSION, 'screen' );

		return $styles;
	}

	public function add_register_script( $script ) {

		$script['divi-overrides']       = new WPDDL_script( 'divi-overrides', WPDDL_RES_RELPATH . '/js/themes/divi-frontend-overrides.js', array(
			'underscore',
			'jquery'
		), WPDDL_VERSION, true );
		$script['divi-overrides-admin'] = new WPDDL_script( 'divi-overrides-admin', WPDDL_RES_RELPATH . '/js/themes/divi-admin-overrides.js', array(
			'underscore',
			'jquery'
		), WPDDL_VERSION, true );

		return $script;
	}

	protected function hook_enqueue_backend_scripts() {

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ), 1 );

	}

	protected function hook_enqueue_backend_styles() {

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ), 1 );

	}

	public function admin_styles() {
		do_action( 'toolset_enqueue_styles', array( 'divi-overrides-admin-css' ) );
	}

	public function admin_enqueue() {
		do_action( 'toolset_enqueue_scripts', array( 'divi-overrides-admin' ) );
	}


	/**
	 * Divi posts using a Content Template that is designed using the page builder should get the right body classname.
	 *
	 * @since 1.2.0
	 */
	public function layout_body_class( $classes ) {
		$current_object_id = $this->helper->get_object_id();

		if (
			$current_object_id
			&& apply_filters( 'toolset_theme_integration_get_setting', null, '_et_pb_page_layout' )
		) {
			$classes[] = 'et_pb_pagebuilder_layout';
		}

		return $classes;
	}

}

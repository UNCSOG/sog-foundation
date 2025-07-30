<?php

use OTGS\Toolset\Views\Services\ContentTemplateService;
use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

class WPV_template{

	public $wpautop_removed = false;

	public $view_template_used_ids = array();

	/**
	 * @var boolean Used to know if CT rendering is being executed
	 */
	private $is_render_executing = false;

    function __construct( ) {

        add_action('init', array($this, 'init'));
		$this->wpautop_removed = false;
		$this->view_template_used_ids = array();
    }

    function __destruct(){

    }

    function init(){

	    

        wpv_register_type_view_template();
        add_filter( 'the_content', array( $this, 'the_content' ), 1, 1 );
        add_filter( 'the_content', array( $this, 'restore_wpautop' ), 999, 1 );

		/**
		 * Recreate the the_content filters, when doing a wpv-post-body shortcode with suppress_filters="true"
		 *
		 * @since 1.8.0
		 */

		if (
			function_exists( 'WPV_wpcf_record_post_relationship_belongs' )
			&& ! has_filter( 'wpv_filter_wpv_the_content_suppressed', 'WPV_wpcf_record_post_relationship_belongs' )
		) {
			// We moved this filter hook to common code, keep it just in case but check whether it has already been added
			add_filter( 'wpv_filter_wpv_the_content_suppressed', 'WPV_wpcf_record_post_relationship_belongs', 0, 1 );
		}

		add_filter( 'wpv_filter_wpv_the_content_suppressed', array( $this, 'the_content' ), 1, 1 );
		if ( isset( $GLOBALS['wp_embed'] ) ) {
			add_filter( 'wpv_filter_wpv_the_content_suppressed', array( $GLOBALS['wp_embed'], 'run_shortcode' ), 8 );
			add_filter( 'wpv_filter_wpv_the_content_suppressed', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
		}
		if ( function_exists( 'do_blocks' ) ) {
			add_filter( 'wpv_filter_wpv_the_content_suppressed', 'do_blocks', 9 );
		}
		if ( function_exists( 'wp_filter_content_tags' ) ) {
			add_filter( 'wpv_filter_wpv_the_content_suppressed', 'wp_filter_content_tags', 10 );
		}
		if ( function_exists( 'wp_replace_insecure_home_url' ) ) {
			add_filter( 'wpv_filter_wpv_the_content_suppressed', 'wp_replace_insecure_home_url', 10 );
		}
		add_filter( 'wpv_filter_wpv_the_content_suppressed', 'capital_P_dangit', 11 );
		add_filter( 'wpv_filter_wpv_the_content_suppressed', 'do_shortcode', 11 );
		add_filter( 'wpv_filter_wpv_the_content_suppressed', 'convert_smilies', 20 );
		add_filter( 'wpv_filter_wpv_the_content_suppressed', array( $this, 'restore_wpautop' ), 999, 1 );

        add_filter('the_excerpt', array($this, 'the_excerpt_for_archives'), 1, 1);

		add_filter( 'the_content',								array( $this, 'prevent_double_wpautop' ), 99 );
		add_filter( 'wpv_filter_wpv_the_content_suppressed',	array( $this, 'prevent_double_wpautop' ), 99 );
		add_filter( 'the_excerpt',								array( $this, 'prevent_double_wpautop' ), 99 );

        if(is_admin()){
            global $pagenow;

            // Post/page Content Template box
            if($pagenow == 'post.php' || $pagenow == 'post-new.php'){
                add_action('admin_head', array($this,'post_edit_template_options'));

				// Post/page save actions
				add_action('save_post', array($this,'save_post_actions'), 10, 2);


            } elseif ($pagenow == 'admin-ajax.php') {
				// For when Types saves a child post
				add_action('save_post', array($this,'save_post_actions'), 10, 2);
			}

			add_filter( 'wpv_filter_get_meta_html_extra_css', array( $this, 'get_meta_html_extra_css' ) );
        } else {
            add_filter('body_class', array($this, 'body_class'), 10, 2);
            add_action('wp_footer', array($this, 'wpv_meta_html_extra_css'), 5); // Set priority lower than 20, so we load the CSS before the footer scripts and avoid the bottleneck
            add_action('wp_footer', array($this, 'wpv_meta_html_extra_js'), 25); // Set priority higher than 20, when all the footer scripts are loaded
        }

		add_action('add_attachment', array($this, 'set_template_for_attachments'), 10, 1);
		add_action('edit_attachment', array($this, 'set_template_for_attachments'), 10, 1);

		add_filter( 'wpv_filter_wpv_disable_post_content_template_metabox', array( $this, 'maybe_disable_post_content_template_metabox_for_layouts' ), 10, 2 );
		add_filter( 'wpv_filter_wpv_disable_post_content_template_metabox', array( $this, 'maybe_disable_post_content_template_metabox_for_page_builders' ), 10, 2 );

		add_action( 'wpv_register_printed_content_template', array( $this, 'register_printed_content_template' ) );

		// Returns if the view is being rendered
		add_filter( 'wpv_is_ct_rendering', [$this, 'is_render_executing' ] );
	}

	public function register_printed_content_template( $template_id = 0 ) {
		$this->view_template_used_ids[] = $template_id;
	}

    function body_class( $body_class ) {
	    if( !is_singular() ) {
            return $body_class;
        }

		$template_selected = apply_filters( 'wpv_content_template_for_post', 0, get_post( get_the_ID() ) );
		if ( isset( $_GET['view-template'] ) && $_GET['view-template'] != '' ) {
		    $template_selected = $this->get_template_id( $_GET['view-template'] );
	    }

        if( $template_selected == 0 ) {
            return $body_class;
        }

        $template_class_title = preg_replace("/[^A-Za-z0-9_ ]/",'', get_the_title( $template_selected ) );
        $template_class = 'views-template-' . strtolower( str_replace( ' ', '-', $template_class_title ) );
        $body_class[] = $template_class;
        return $body_class;
    }

	/**
	 * Add metaboxes to the post edit pages as required
	 *
	 */
    function post_edit_template_options() {
		global $post;
		$post_object = get_post_type_object( $post->post_type );

		if (
			! apply_filters( 'wpv_filter_wpv_disable_post_content_template_metabox', false, $post )
			&& (
				$post_object->publicly_queryable
				|| $post_object->public
			)
		) {
			// Add meta box so that a Content Template can be set for a post
			add_meta_box( 'views_template', __( 'Content Template', 'wpv-views' ), array( $this, 'content_template_select_meta_box' ), $post->post_type, 'side', 'high' );
		}
    }

	/**
	 * Disable the Content Template metabox on post edit pages when Layouts is active and no CT is assigned.
	 *
	 * @since 2.3.0
	 */

	function maybe_disable_post_content_template_metabox_for_layouts( $status, $post ) {
		if (
			! $post instanceof WP_Post
			|| ! class_exists( 'WPDD_PostEditPageManager' )
		) {
			return $status;
		}

		if ( 'blocks' === wpv_get_views_flavour() ) {
			// Do not disable this metabox when using the blocks flavour.
			return $status;
		}

		$template_selected = 0;

		if (
			isset( $_GET['post'] )
			&& intval( $_GET['post'] ) > 0
		) {
			// Editing a post: check the stord value
			$template_selected = get_post_meta( (int) $_GET['post'], '_views_template', true );
			if ( empty( $template_selected ) ) {
				$template_selected = 0;
			}
		} else {
			// Creating a post: check the assigned CT by post type
			global $pagenow, $post_type;
            if ( $pagenow == 'post-new.php' ) {
				// Creating a post
				// If it is a translation, set the CT of the original post
				if (
					isset( $_GET['trid'] )
					&& isset( $_GET['source_lang'] )
				) {
					$sp_trid = sanitize_text_field( $_GET['trid'] );
					$sp_source_lang = sanitize_text_field( $_GET['source_lang'] );
					$translations = apply_filters( 'wpml_get_element_translations', array(), $sp_trid, 'post_' . $post->post_type );
					if ( isset( $translations[ $sp_source_lang ] ) ) {
						$template_selected = get_post_meta( $translations[ $sp_source_lang ]->element_id, '_views_template', true );
						if ( empty( $template_selected ) ) {
							$template_selected = 0;
						}
					}
				}
				// Otherwise, set the value stored in the Views settings, if any
				if ( $template_selected == 0 ) {
					$wpv_stored_settings = WPV_Settings::get_instance();
					if ( isset( $wpv_stored_settings['views_template_for_' . $post_type] ) ) {
						$template_selected = $wpv_stored_settings['views_template_for_' . $post_type];
					}
				}
            }
		}

		if ( $template_selected == 0 ) {
			return true;
		}
	}

	/**
	 * Disable the Content Template metabox from edit pages that belong to page builders backend editors.
	 *
	 * @param boolean $status
	 * @param WP_Post $post
	 * @return boolean
	 * @since 2.6.3
	 */
	public function maybe_disable_post_content_template_metabox_for_page_builders( $status, $post ) {
		$page_builder_post_types = array( 'elementor_library' );

		if ( in_array( $post->post_type, $page_builder_post_types ) ) {
			return true;
		}

		return $status;
	}



	/**
	 * Add a meta box to public and publicly_queryable post types to set the Content Template to be used for that single item being edited
	 *
	 * @param WP_Post $post the post being edited
	 *
	 * Echoes the meta box.
	 *
	 * @since unknown
     *
	 * @note 1.7 added a link to edit the Content Template
	 * @note 1.7 removed loop Templates from the dropdown
	 */
    function content_template_select_meta_box( $post ) {

		$template_selected					= '';
		$wpml_current_language				= apply_filters( 'wpml_current_language', '' );
		$wpml_default_language				= apply_filters( 'wpml_default_language', '' );
		// Works when editing a post
		$is_in_wpml_original_language		= apply_filters( 'wpml_is_original_content', true, $post->ID , 'post_' . $post->post_type );
		// Works when creating a new post, where the previous one gives false positives
		if ( $is_in_wpml_original_language ) {
			$is_in_wpml_original_language	= ( $wpml_current_language == $wpml_default_language );
		}
		$content_templates_translatable	= apply_filters( 'wpml_is_translated_post_type', false, 'view-template' );
		$dynamic_template_selected = apply_filters( 'wpv_content_template_for_post', 0, $post );


        if (
			isset( $_GET['post'] )
			&& intval( $_GET['post'] ) > 0
		) {
			// Editing a post
            $template_selected = get_post_meta( (int) $_GET['post'], '_views_template', true );
        } else {
            global $pagenow, $post_type;
            if ( $pagenow == 'post-new.php' ) {
				// Creating a post
				// If it is a translation, set the CT of the original post
				if (
					isset( $_GET['trid'] )
					&& isset( $_GET['source_lang'] )
				) {
					$sp_trid = sanitize_text_field( $_GET['trid'] );
					$sp_source_lang = sanitize_text_field( $_GET['source_lang'] );
					$translations = apply_filters( 'wpml_get_element_translations', array(), $sp_trid, 'post_' . $post->post_type );
					if ( isset( $translations[ $sp_source_lang ] ) ) {
						$template_selected = get_post_meta( $translations[ $sp_source_lang ]->element_id, '_views_template', true );
					}
				}
            }
        }

		if (
			$template_selected != 0
			&& $content_templates_translatable
		) {
			// Adjust for WPML support, in case CTs are translatable
			$template_selected = apply_filters( 'translate_object_id', $template_selected, 'view-template', true, null );
		}

		// Get available CTs
		// Note that when the select dropdown is going to be disabled we just get the selected, if any, and skip the query entirely otherwise
		$view_tempates_available = array();
		if (
			! empty( $template_selected )
			|| $is_in_wpml_original_language
		) {
			// We need to run a query since:
			// - we have a template selected and we need its title/name
			// - or we do not have a template selected but we are in the WPML original language, so we need to offer options
			global $wpdb;

			$values_to_prepare = array();

			$wpml_join = $wpml_where = "";

			if ( ! $is_in_wpml_original_language ) {
				// Editing or creating a translation, so only get the selected CT
				$wpml_where = " AND p.ID = %d ";
				$values_to_prepare[] = $template_selected;
			} else if ( $content_templates_translatable ) {
				// Current language is the original language and CTs are translatable, so get only the ones in this original language
				$wpml_join = " JOIN {$wpdb->prefix}icl_translations icl_t ";
				$wpml_where = " AND p.ID = icl_t.element_id AND icl_t.language_code = %s AND icl_t.element_type LIKE 'post_%' ";
				$values_to_prepare[] = $wpml_current_language;
			}

			$exclude_loop_templates = '';
			$exclude_loop_templates_ids = wpv_get_loop_content_template_ids();
			if ( count( $exclude_loop_templates_ids ) > 0 ) {
				$exclude_loop_templates_ids_sanitized = array_map( 'esc_attr', $exclude_loop_templates_ids );
				$exclude_loop_templates_ids_sanitized = array_map( 'trim', $exclude_loop_templates_ids_sanitized );
				// is_numeric + intval does sanitization
				$exclude_loop_templates_ids_sanitized = array_filter( $exclude_loop_templates_ids_sanitized, 'is_numeric' );
				$exclude_loop_templates_ids_sanitized = array_map( 'intval', $exclude_loop_templates_ids_sanitized );
				if ( count( $exclude_loop_templates_ids_sanitized ) > 0 ) {
					$exclude_loop_templates = " AND p.ID NOT IN ('" . implode( "','" , $exclude_loop_templates_ids_sanitized ) . "') ";
				}
			}

			$values_to_prepare[] = 'view-template';
			$view_tempates_available = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT p.ID, p.post_name, p.post_title
					FROM {$wpdb->posts} p {$wpml_join}
					WHERE p.post_status = 'publish'
					{$wpml_where}
					AND p.post_type = %s
					{$exclude_loop_templates}
					ORDER BY p.post_title",
					$values_to_prepare
				)
			);

		}

		?>
		<select
			data-dynamic-template-id="<?php echo esc_attr( $dynamic_template_selected ); ?>"
			data-dynamic-template-title="<?php echo esc_html( get_the_title( $dynamic_template_selected ) ); ?>"
			name="views_template[<?php echo esc_attr( $post->ID ); ?>]"
			id="views_template"
			class="widefat meta-box-sortables js-wpv-edit-post-select-ct"
			<?php disabled( ! $is_in_wpml_original_language ); ?>
			style="width: 100%;"
		>
		<?php
        // Add a "None" type to the list.
        $none = new stdClass();
        $none->ID = '0';
        $none->post_title = __( 'None', 'wpv-views' );
        array_unshift( $view_tempates_available, $none );

		// Add a 'Cascade decision tree' option, which is equal to deleted/empty post meta.
		$decision_tree = new stdClass();
		$decision_tree->ID = '';
		$decision_tree->post_title = __( 'Template Dynamically Assigned by Toolset', 'wpv-views' );
		array_unshift( $view_tempates_available, $decision_tree );

        foreach( $view_tempates_available as $template ) {
			if ( $template->post_title != '' ) {
				?>
				<option value="<?php echo esc_attr( $template->ID ); ?>" <?php selected( $template_selected, $template->ID ); ?>><?php echo $template->post_title; ?></option>
				<?php
			} else {
				?>
				<option value="<?php echo esc_attr( $template->ID ); ?>" <?php selected( $template_selected, $template->ID ); ?>><?php echo $template->post_name; ?></option>
				<?php
			}
        }
		?>
		</select>
		<?php
		if ( ! $is_in_wpml_original_language ) {
			?>
			<p class="toolset-alert toolset-alert-info">
				<?php
				echo __( 'The Content Template is copied from the original language' , 'wpv-views' );
				?>
			</p>
			<?php
		}
		if ( ! apply_filters( 'toolset_is_views_embedded_available', false ) ) {
			$edit_link = '';
			$edit_link_visible = ' hidden';
			$dynamic_name = '';
			$dynamic_name_visible = ' hidden';
			if ( '' === $template_selected ) {
				if ( $dynamic_template_selected > 0 ) {
					$edit_link = wpv_ct_editor_url( $dynamic_template_selected );
					$dynamic_name = get_the_title( $dynamic_template_selected );
					$edit_link_visible = '';
					$dynamic_name_visible = '';
				}
			}
			if (
				! empty( $template_selected )
				&& $template_selected !== 0
				&& get_post_status( $template_selected ) == 'publish'
			) {
				$edit_link = wpv_ct_editor_url( $template_selected );
				$edit_link_visible = '';
			}
			?>
			<div class="wpv-edit-post-edit-ct-dynamic-container js-wpv-edit-post-edit-ct-dynamic-container<?php echo esc_attr( $dynamic_name_visible ); ?>">
				<span class="wpv-edit-post-edit-ct-dynamic-name js-wpv-edit-post-edit-ct-dynamic-name"><?php echo esc_html( $dynamic_name ); ?></span>
			</div>
			<div class="wpv-edit-post-edit-ct-link-container js-wpv-edit-post-edit-ct-link-container<?php echo esc_attr( $edit_link_visible ); ?>">
				<a href="<?php echo esc_url( $edit_link ); ?>" class="wpv-edit-post-edit-ct-link js-wpv-edit-post-edit-ct-link" target="_blank"><?php esc_html_e( 'Edit this Content Template', 'wpv-views' ); ?></a>
			</div>
			<script type="text/javascript">
				jQuery( function( $ ) {
					// Warning! We need to take care of Layouts compatibility: if there is a select for Layouts AND has a value, we need to hide the thiz_wpv_ct_link_container
					var thiz_wpv_layout_select = $( document.getElementById( 'js-layout-template-name' ) ),
					thiz_wpv_ct_select = $( '.js-wpv-edit-post-select-ct' ),
					thiz_wpv_ct_link = $( '.js-wpv-edit-post-edit-ct-link' ),
					thiz_wpv_ct_dynamic_name = $( '.js-wpv-edit-post-edit-ct-dynamic-name' ),
					thiz_wpv_ct_dynamic_container = $( '.js-wpv-edit-post-edit-ct-dynamic-container' ),
					thiz_wpv_ct_link_container = $( '.js-wpv-edit-post-edit-ct-link-container' ),
					thiz_wpv_ct_select_manager = function() {
						var ct_id = thiz_wpv_ct_select.val();
						var show_dynamic_name = false
						if ( '' === ct_id ) {
							ct_id = thiz_wpv_ct_select.data('dynamic-template-id');
							thiz_wpv_ct_dynamic_name.html( thiz_wpv_ct_select.data('dynamic-template-title') );
							thiz_wpv_ct_dynamic_name.html( thiz_wpv_ct_select.data('dynamic-template-title') );
							show_dynamic_name = true
						}

						if ( 0 !== parseInt( ct_id ) && true === show_dynamic_name ) {
							thiz_wpv_ct_dynamic_container.fadeIn( 'fast' );
						} else {
							thiz_wpv_ct_dynamic_container.hide();
						}

						if ( 0 !== parseInt( ct_id ) ) {
							thiz_wpv_ct_link.attr(
                                'href',
                                'admin.php?page=' + <?php printf( '\'%s\'', WPV_CT_EDITOR_PAGE_NAME ); ?> + '&ct_id=' + ct_id + '&action=edit'
                            );
							thiz_wpv_ct_link_container.fadeIn( 'fast' );
						} else {
							thiz_wpv_ct_link_container.hide();
						}
					};
					if ( thiz_wpv_layout_select.length > 0 ) {
						if ( thiz_wpv_layout_select.val() != '0' ) {
							thiz_wpv_ct_link_container.hide();
						} else {
							thiz_wpv_ct_select_manager();
						}
						thiz_wpv_layout_select.on( 'change', function() {
							if ( thiz_wpv_layout_select.val() != '0' ) {
								thiz_wpv_ct_link_container.hide();
							} else {
								thiz_wpv_ct_select_manager();
							}
						});
					}
					thiz_wpv_ct_select.on( 'change', thiz_wpv_ct_select_manager );
					thiz_wpv_ct_select.toolset_select2( {
						sorter: function(data) {
							return data.sort(function(a, b) {

								// Show the default value first.
								if ( '' === a.id ) return -1;
								if ( '' === b.id ) return 1;

								// None value below.
								if ( '0' === a.id ) return -1;
								if ( '0' === b.id ) return 1;

								// The rest of values sorted alphabetically.
								const aText = a.text.toLowerCase();
								const bText = b.text.toLowerCase();

								if ( aText < bText ) return -1;
								if ( bText < aText ) return 1;
								return 0;
							});
						}
					} );
				});
			</script>
			<?php
		}
    }

	/**
	 * Save the meta box data when a post is saved
	 *
     * @since unknown
	 */
    function save_post_actions( $pidd, $post ) {
		global $wpdb;
        if ( isset( $_POST['views_template'] ) ) {
			// make sure we only update this for the current post.
	        if (
				isset( $_POST['post_ID'] )
				&& $_POST['post_ID'] == $pidd
				&& isset( $_POST['views_template'][$pidd] )
			) {
				$template_selected = $_POST['views_template'][$pidd];
		        WPV_Content_Template_Embedded::assign_ct_to_post_object( $pidd, $template_selected );
			}
		} elseif ( isset( $_POST['wpcf_post_relationship'] ) ) {
			// handle Types post relationships
			if ( isset( $_POST['wpcf_post_relationship'][$pidd]['post_type'] ) ) {
				// Saving an existing child post
				$post_type = $_POST['wpcf_post_relationship'][$pidd]['post_type'];
			} else {
				// Saving a new child post
				$post_type = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT post_type FROM {$wpdb->posts}
						WHERE ID = %d
						LIMIT 1",
						$pidd
					)
				);
			}
		}
    }

	/**
	 * get the template id from the name and include caching.
	 *
     * @deprecated Consider using WPV_Content_Template_Embedded::get_template_id_by_name directly.
	 */
	function get_template_id( $template_name ) {
        return WPV_Content_Template_Embedded::get_template_id_by_name( $template_name );
	}

	/**
	 *	get the template content and include caching
	 *
     * @todo IMHO the caching is not necessary as get_post() already uses caching in WP core.
	 * @deprecated Use WPV_Content_Template_Embedded instead.
	 */
	function get_template_content( $template_id ) {
		static $view_templates = array();
		if ( ! isset( $view_templates[$template_id] ) ) {
            $status = get_post_status( $template_id );

            // If the templated is not 'publish'ed
            if( $status != 'publish' ) {
                return null;
            }

            //FIXME: Check user has permission to see this content template

			$template_full = get_post( $template_id );
			// If there is no post with that ID
			if (
				is_null( $template_full )
				|| ! is_object( $template_full )
			) {
				return null;
			}
			if (
				isset( $template_full->post_type )
				&& $template_full->post_type == 'view-template'
			) {
				$view_templates[$template_id] = $template_full->post_content;
			} else {
				// If there is a post with that ID but it is not a Content Template
				return null;
			}

		}
		return $view_templates[$template_id];
	}

	/**
	 * Used for a filter that returns if the view is being rendered
	 *
	 * @see wpv_is_view_rendering
	 */
	public function is_render_executing() {
		return $this->is_render_executing;
	}

	/**
	 * apply the filter to the content/body of a post.
	 * Checks to see if a view-template is set for the post
	 * and renders using the view_template if on exists
     *
     * @since unknown
	 * @since 2.4.0 Added a listener to the content-template-id URL parameter to force a Content Template by ID
	 * @since 2.8.3 Do not apply a CT to an excluded post type
	 */
    function the_content( $content ) {
        global $id, $post, $wp_query, $WPVDebug;

		$post = get_post( $post );
		if ( is_null( $post ) ) {
			return $content;
		}

		if ( post_password_required( $post ) ) {
			return $content;
		}

		// Do not apply a CT to an excluded post type
		$toolset_exclude_list = new Toolset_Post_Type_Exclude_List();
		if ( $toolset_exclude_list->is_excluded( $post->post_type ) ) {
			return $content;
		}

		$this->is_render_executing = true;
		$wpv_global_settings = WPV_Settings::get_instance();

		// Core functions that we accept calls from.
		// since 2.6.0 we render the wpv-post-body using the WPV_Shortcode_Post_Body::get_value method
		$the_content_core = array(
				'the_content',
				'the_excerpt_for_archives', 'WPV_template::the_excerpt_for_archives',
				'wpv_shortcode_wpv_post_body', 'WPV_Shortcode_Post_Body::get_value'
		);
		// Known theme functions that we accept calls from.
		$the_content_themes = array(
			// WPTouch theme(s)
			'wptouch_the_content'
		);
		// Known plugin functions that we accept calls from.
		$the_content_plugins = array(
			// Elementor Pro content widget
			'ElementorPro\Modules\ThemeBuilder\Widgets\Post_Content::render'
		);

		$the_content_blacklist = array(
				'require', 'require_once', 'include', 'include_once',
				'locate_template', 'load_template',
				'apply_filters', 'call_user_func_array',
				'wpcf_fields_wysiwyg_view'
		);

		// phpcs:disable PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
		// phpcs:disable PHPCompatibility.FunctionUse.NewFunctionParameters.debug_backtrace_limitFound
		// phpcs:disable PHPCompatibility.Constants.NewConstants.debug_backtrace_ignore_argsFound
		// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		if ( version_compare( PHP_VERSION, '5.4.0' ) >= 0 ) {
			$db = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 6 );
		} else {
			$db = debug_backtrace();
		}
		// phpcs:enable

		$function_candidate = array();

		// From php7 debug_backtrace() has changed, and the target function might be at index 2 instead of 3 as in php < 7
		// Also, from WP 4.7 the new way to manage hooks adds some intermediary items so let's cover our backs reaching to 5
		// Also, the_excerpt_for_archives is supposed to be at index 1
		// Note: we might want to add a pluk and filter here, maybe...

		if ( isset( $db[5]['function'] ) ) {
			if ( isset( $db[5]['class'] ) ) {
				$function_candidate[] = $db[5]['class'] . '::' . $db[5]['function'] ;
			} else {
				$function_candidate[] = $db[5]['function'];
			}
		}

		if ( isset( $db[4]['function'] ) ) {
			if ( isset( $db[4]['class'] ) ) {
				$function_candidate[] = $db[4]['class'] . '::' . $db[4]['function'] ;
			} else {
				$function_candidate[] = $db[4]['function'];
			}
		}

		if ( isset( $db[3]['function'] ) ) {
			if ( isset( $db[3]['class'] ) ) {
				$function_candidate[] = $db[3]['class'] . '::' . $db[3]['function'] ;
			} else {
				$function_candidate[] = $db[3]['function'];
			}
		}

		if ( isset( $db[2]['function'] ) ) {
			if ( isset( $db[2]['class'] ) ) {
				$function_candidate[] = $db[2]['class'] . '::' . $db[2]['function'] ;
			} else {
				$function_candidate[] = $db[2]['function'];
			}
		}

		if ( isset( $db[1]['function'] ) ) {
			if ( isset( $db[1]['class'] ) ) {
				$function_candidate[] = $db[1]['class'] . '::' . $db[1]['function'] ;
			} else {
				$function_candidate[] = $db[1]['function'];
			}
		}

		$function_candidate = array_diff( $function_candidate, $the_content_blacklist );

		if ( empty( $function_candidate ) ) {

			if (
				current_user_can( EDIT_VIEWS )
				&& isset( $wpv_global_settings->wpv_theme_function_debug )
				&& $wpv_global_settings->wpv_theme_function_debug
			) {
				$debug_info = '<p style="font-weight:bold !important;color: red !important;">'
					. __( '<strong>Content Template debug:</strong>', 'wpv-views' )
					. WPV_MESSAGE_SPACE_CHAR
					. __( 'your theme does not provide a valid, dedicated function to render the post content.', 'wpv-views' )
					. '</p>';

				$content = $debug_info . $content;
			}
			$this->is_render_executing = false;

			return $content;

		}

		$function_ok = false;

		foreach ( $function_candidate as $function_candidate_for_content ) {
			if (
				in_array( $function_candidate_for_content, $the_content_core )
				|| in_array( $function_candidate_for_content, $the_content_themes )
				|| in_array( $function_candidate_for_content, $the_content_plugins )
			) {
				$function_ok = true;
			}
		}

		$registered_function_candidate = array();
		if ( ! $function_ok ) {
			if (
				isset( $wpv_global_settings->wpv_theme_function )
				&& ! empty( $wpv_global_settings->wpv_theme_function )
			) {
				$registered_function_candidate = explode( ',', str_replace( ', ', ',', $wpv_global_settings->wpv_theme_function ) );
				foreach ( $function_candidate as $function_candidate_for_content ) {
					if (
						in_array( $function_candidate_for_content, $registered_function_candidate )
						|| in_array( $function_candidate_for_content, $registered_function_candidate )
					) {
						$function_ok = true;
					}
				}
            }
        }

		if ( ! $function_ok ) {
			// We don't accept calls from the calling function.
			if (
				current_user_can( EDIT_VIEWS )
				&& isset( $wpv_global_settings->wpv_theme_function_debug )
				&& $wpv_global_settings->wpv_theme_function_debug
			) {

				$debug_info = '<p style="font-weight:bold !important;color: red !important;">'
					. __( '<strong>Content Template debug:</strong>', 'wpv-views' )
					. '<ul>';

				$function_candidate_string = implode( ', ', $function_candidate );
				$debug_info .= '<li>'
					. sprintf(
						__( 'Calling functions are <strong>%s</strong>', 'wpv-views' ),
						$function_candidate_string
					)
					. '</li>';

				$registered_function_valid			= array_diff( $registered_function_candidate, $the_content_blacklist );
				$registered_function_blacklisted	= array_intersect( $registered_function_candidate, $the_content_blacklist );
				if ( count( $registered_function_valid ) > 0 ) {
					$registered_function_valid_string = implode( ', ', $registered_function_valid );
					$debug_info .= '<li>'
						. sprintf(
							__( 'Registered valid functions are <strong>%s</strong>', 'wpv-views' ),
							$registered_function_valid_string
						)
						. '</li>';
				}
				if ( count( $registered_function_blacklisted ) > 0 ) {
					$registered_function_blacklisted_string = implode( ', ', $registered_function_blacklisted );
					$debug_info .= '<li>'
						. sprintf(
							__( 'Registered invalid functions are <strong>%s</strong>', 'wpv-views' ),
							$registered_function_blacklisted_string
						)
						. '</li>';
				}

				$debug_info .= '</ul></p>';

				$content = $debug_info . $content;

			}
			$this->is_render_executing = false;

			return $content;
		}

		// Here we will store what kind of place this template is being used on: single-{post-type}, singular, archive-{post-type}, archive-{taxonomy}, archive, listing-{post-type}
		$kind = $this->get_current_rendering_kind();
		$archive_loop = $this->get_current_archive_loop();

		$template_selected = 0;
		$template_apply_filter = true;
		$template_extra_override = false;
		if ( isset( $post->view_template_override ) ) {
			if ( strtolower( $post->view_template_override ) == 'none' ) {
				$template_selected = 0;
			} else {
				$template_selected = $this->get_template_id( $post->view_template_override );
			}
		} else if (
			isset( $_GET['view-template'] )
			&& $_GET['view-template'] != ''
		) {
			if ( ! isset( $post->view_template_override_get ) ) {
				$template_selected = $this->get_template_id( $_GET['view-template'] );
				$post->view_template_override_get = true;
			}
		} else if (
			isset( $_GET['content-template-id'] )
			&& $_GET['content-template-id'] != ''
		) {
			if ( ! isset( $post->view_template_override_get ) ) {
				$template_selected = (int) $_GET['content-template-id'];
				$post->view_template_override_get = true;
			}
		} else if (
			isset( $_GET['cred-edit-form'] )
			&& $_GET['cred-edit-form'] != ''
		) { // special case when displaying CRED forms
			if (
				isset( $post->view_template_override )
				&& strtolower( $post->view_template_override ) != 'none'
			) { // if displaying CRED form and this template is inside the form in a wpv-post-body shortcode, apply it
				$template_selected = $this->get_template_id( $post->view_template_override );
			} else { // if this template is being used for the post type being edited by a CRED form, do not apply it
				$template_selected = 0;
				$template_apply_filter = false;
			}
		} else {
			if ( $archive_loop ) {
				if (
					isset( $wpv_global_settings[ $archive_loop ] )
					&& $wpv_global_settings[ $archive_loop ] > 0
				) {
					if ( ! isset( $post->view_template_override_loop_setting ) ) {
						$template_selected = $wpv_global_settings[ $archive_loop ];
						$template_extra_override = '_for_archive';
						$post->view_template_override_loop_setting = true;
					}
				}
			} else {
				$template_selected = apply_filters( 'wpv_content_template_for_post', $template_selected, $post );
				$template_extra_override = '_for_single';
			}
		}

		if ( $template_apply_filter ) {

			/**
			* Filter wpv_filter_force_template
			*
			* @param $template_selected the template ID we want to overwrite
			* @param $id the post the template is being applied to
			* @param $kind the kind of place where this template is being used: single-{post-type}, singular, archive-{post-type}, archive-{taxonomy}, archive, listing-{post-type}
			*
			* @return $template_selected
			*
			* @since 1.4.0
			* @since 2.3.0 Added a duplicated wpv_filter_wpv_override_content_template for better naming
			* @since 2.3.0 Added a synamic filter wpv_filter_wpv_override_content_template{$template_extra_override} for single and archive loops.
			*
			*/
			//$old_template_selected = $template_selected;
			$template_selected = apply_filters( 'wpv_filter_force_template', $template_selected, $id, $kind );
			$template_selected = apply_filters( 'wpv_filter_wpv_override_content_template', $template_selected, $id, $kind );
			if ( $template_extra_override ) {
				$template_selected = apply_filters( 'wpv_filter_wpv_override_content_template' . $template_extra_override, $template_selected, $id );
			}
		    //$WPVDebug->add_log( 'info' , "wpv_filter_force_template\n" . __('ID: ', 'wpv-views') . $id . "\n" . __('Content Template: from ', 'wpv-views') . $old_template_selected . __(' to ', 'wpv-views') . $template_selected, 'filters', 'Filter: wpv_filter_force_template' );
		    //The debug is not being added, I will need a better way to show this
		}

		// If we already rendered this post with this CT on this same filter just return the un-filtered content
		// to avoid recursion
		static $in_progress = array();
		if ( isset( $in_progress[ $id . '#' . $template_selected ] ) ) {
			$this->is_render_executing = false;
			return $content;
		}

		$in_progress[ $id . '#' . $template_selected ] = true;

		$WPVDebug->update_template_id( $template_selected );

        if ( $template_selected ) {
			// Adjust for WPML support
			$template_selected = apply_filters( 'translate_object_id', $template_selected, 'view-template', true, null );
			$this->view_template_used_ids[] = $template_selected;
			toolset_wplog( 'Using Content Template: ' . $template_selected . ' on post: ' . $post->ID, null, __FILE__, 'WPV_template::the_content', 598 );

            $content_aux = $this->get_template_content( $template_selected );

			// If this function returns null, $template_selected does not exist or is not a Content Template
			if ( is_null( $content_aux ) ) {
				unset( $in_progress[ $id . '#' . $template_selected ] );
				return $content;
			} else {
				$content = $content_aux;
			}

			$output_mode = get_post_meta( $template_selected, '_wpv_view_template_mode', true );

			if (
				'raw_mode' === $output_mode
				&& ! $this->has_blocks( $content )
			) {
				$this->remove_wpautop();
			}

        }

		$WPVDebug->add_log_item( 'shortcodes', $content );

		/**
		  * Filter wpv_filter_content_template_output
		 *
		 * @param string $content the content to be displayed, whether it's the real post content or the one coming from a Content Template
		 * @param int $template_selected the template ID being applied, can be 0 when there is none
		 * @param int $id the post the template is being applied to
		 * @param string $kind the kind of place where this template is being used: single-{post-type}, singular, archive-{post-type}, archive-{taxonomy}, archive, listing-{post-type}
		 * @return string
		 * @since 1.5.1
		 */
		$content = apply_filters( 'wpv_filter_content_template_output', $content, $template_selected, $id, $kind );

		unset( $in_progress[ $id . '#' . $template_selected ] );

		$this->is_render_executing = false;
		return $content;

	}

	/**
	 * Check whether a piece of content has been created using the blocks editor.
	 *
	 * @param string $content
	 * @return bool
	 * @since 3.2
	 */
	private function has_blocks( $content ) {
		if ( function_exists( 'has_blocks' ) ) {
			return has_blocks( $content );
		}

		return false;
	}

	public function get_current_rendering_kind() {
		global $post, $wp_query;

		$kind = '';

		if ( is_singular() ) {
			$top_current_post = apply_filters( 'wpv_filter_wpv_get_top_current_post', null );
			if (
				is_null( $top_current_post )
				|| !isset( $top_current_post->ID )
			) {
				$current_id = 0;
			} else {
				$current_id = $top_current_post->ID;
			}
			if ( isset( $post->post_type ) ) {
				if ( $current_id == $post->ID ) {
					$kind = 'single-' . $post->post_type;
				} else {
					$kind = 'listing-' . $post->post_type;
				}
			} else {
				$kind = 'singular';
			}
		}

		if ( is_archive() ) {
			if (
				is_tax()
				|| is_category()
				|| is_tag()
			) {
				$term = $wp_query->get_queried_object();
				$kind = 'archive-' . $term->taxonomy;
			} else if ( is_post_type_archive( $post->post_type ) ) {
				$kind = 'archive-' . $post->post_type;
			} else if ('post' == $post->post_type) {
				$kind = 'archive-post';
			} else { // if on an archive page but not a tax one and not on the archive for the displayed $post, then it's a generic archive
				$kind = 'archive';
			}
		}

		return $kind;
	}

	public function get_current_archive_loop() {
		global $post, $wp_query;

		$archive_loop = null;

		if ( is_archive() ) {
			if (
				is_tax()
				|| is_category()
				|| is_tag()
			) {
				$term = $wp_query->get_queried_object();
				$archive_loop = 'views_template_loop_' . $term->taxonomy;
			} else if ( is_post_type_archive( $post->post_type ) ) {
				$archive_loop = 'views_template_archive_for_' . $post->post_type;
			} else if ('post' == $post->post_type) {
				$archive_loop = 'views_template_archive_for_post';
			}
		}

		return $archive_loop;
	}

	function is_wpautop_removed() {
		return $this->wpautop_removed;
	}

	function remove_wpautop() {
		remove_filter('the_content', 'wpautop');
		remove_filter('the_content', 'shortcode_unautop');
		// Remove the autop behavior introduced by Gutenberg when the content does not contain blocks.
		// The relevant filter is documented in gutenberg/lib/compat.php
        if ( function_exists( 'gutenberg_wpautop' ) ) {
			remove_filter( 'the_content', 'gutenberg_wpautop', 8 );
		}
		remove_filter( 'wpv_filter_wpv_the_content_suppressed', 'wpautop' );
		remove_filter( 'wpv_filter_wpv_the_content_suppressed', 'shortcode_unautop' );
		remove_filter('the_excerpt', 'wpautop');
		remove_filter('the_excerpt', 'shortcode_unautop');

		$this->wpautop_removed = true;
	}

	function restore_wpautop( $content = '' ) {
		if ( $this->wpautop_removed ) {
			add_filter('the_content', 'wpautop');
			add_filter('the_content', 'shortcode_unautop');
			// Restore the autop behavior introduced by Gutenberg when the content does not contain blocks.
			// The relevant filter is documented in gutenberg/lib/compat.php
			if ( function_exists( 'gutenberg_wpautop' ) ) {
				add_filter( 'the_content', 'gutenberg_wpautop', 8 );
			}
			add_filter( 'wpv_filter_wpv_the_content_suppressed', 'wpautop' );
			add_filter( 'wpv_filter_wpv_the_content_suppressed', 'shortcode_unautop' );
			add_filter('the_excerpt', 'wpautop');
			add_filter('the_excerpt', 'shortcode_unautop');
			$this->wpautop_removed = false;
		}

		return $content;
	}

	/**
	 * Handle the_excerpt filter when it's used in an archive loop
	 *
	 */
	function the_excerpt_for_archives( $content ) {
		global $post, $wp_query;

		$wpv_stored_settings = WPV_Settings::get_instance();

		static $archive_type_has_been_checked = false;
		static $archive_template_selected = 0;
		if ( !$archive_type_has_been_checked ) {
            $archive_type_has_been_checked = true;

            if ( is_archive() ) {
				if ( is_tax() || is_category() || is_tag() ) {
					$term = $wp_query->get_queried_object();
					if( $term ) {
						$archive_loop = 'views_template_loop_' . $term->taxonomy;
						$archive_template_selected = isset( $wpv_stored_settings[ $archive_loop ] )
							? $wpv_stored_settings[ $archive_loop ]
							: 0;
					}
				} else if (is_post_type_archive($post->post_type)) {
					$archive_loop = 'views_template_archive_for_' . $post->post_type;
					$archive_template_selected = isset( $wpv_stored_settings[ $archive_loop ] ) ? $wpv_stored_settings[ $archive_loop ] : 0;
				}
			}
		}

		if ( $archive_template_selected > 0 ) {
            $content = wpv_do_shortcode( $this->the_content( $content ) );
        }

        return $content;
	}

	/**
	* prevent_double_wpautop
	*
	* Prevent empty open or close paragraph tags that can come when wpautop is fired twice, maybe from a page builder.
	*
	* @since 2.2
	*/

	function prevent_double_wpautop( $content ) {
		$content = str_replace( '<p><p>', '<p>', $content );
		$content = str_replace( '</p></p>', '</p>', $content );
		$content = str_replace( "</p>\n</p>", '</p>', $content );

		return $content;
	}

	/**
	 * Get content templates in a select box
	 *
	 * @deprecated
	 * @todo the $row attribute is deprecated... in fact all the method is deprecated
	 *
	 */
	function get_view_template_select_box( $row, $template_selected ) {
		global $wpdb;
        $view_tempates_available = $wpdb->get_results(
			"SELECT ID, post_title, post_name FROM {$wpdb->posts}
			WHERE post_type='view-template'
			AND post_status in ('publish')"
		);
        $view_template_select_box = '';
		if ( $row === '' ) {
			$view_template_select_box .= '<select class="views_template_select" name="views_template" id="views_template">';
		} else {
			$view_template_select_box .= '<select class="views_template_select" name="views_template_' . $row . '" id="views_template_' . $row . '">';
		}
        // Add a "None" type to the list.
        $none = new stdClass();
        $none->ID = '0';
        $none->post_title = __('None', 'wpv-views');
        array_unshift( $view_tempates_available, $none );
        foreach( $view_tempates_available as $template ) {
			if ( $template->post_title ) {
				$view_template_select_box .= '<option value="' . $template->ID . '" ' . selected( $template_selected, $template->ID, false ) . '>' . $template->post_title . '</option>';
			} else {
				$view_template_select_box .= '<option value="' . $template->ID . '" ' . selected( $template_selected, $template->ID, false ) . '>' . $template->post_name . '</option>';
			}
        }
        $view_template_select_box .= '</select>';
        return $view_template_select_box;
	}

	function get_view_template_titles() {
        global $wpdb;

        static $view_templates_available = null;

		if ($view_templates_available === null) {

			$view_templates_available = array();
			$view_templates = $wpdb->get_results(
				"SELECT ID, post_title FROM {$wpdb->posts}
				WHERE post_type = 'view-template'"
			);
			foreach ( $view_templates as $view_template ) {
				$view_templates_available[$view_template->ID] = $view_template->post_title;
			}
		}
		return $view_templates_available;
	}

	function set_template_for_attachments( $pidd ) {
        global $WPV_settings;

        if ( !isset( $_POST['views_template'] ) ) {
            // set the content template if one hasn't been set.
            $template_selected = get_post_meta( $pidd, '_views_template', true );
            if ( $template_selected == '' ) {
                if ( isset( $WPV_settings['views_template_for_attachment'] ) ) {
                    $template_selected = $WPV_settings['views_template_for_attachment'];
	                WPV_Content_Template_Embedded::assign_ct_to_post_object( $pidd, $template_selected );
                }
            }
        } elseif ( isset( $_POST['views_template'][$pidd] ) ) {
	        WPV_Content_Template_Embedded::assign_ct_to_post_object( $pidd, $_POST['views_template'][$pidd] );
        }
    }

    /**
	* Add extra CSS and javascript to wp_footer
	* This renders CSS and JS added by the user on the Content Template settings
	*/
	public function get_meta_html_extra_css( $cssout = '' ) {
		$view_templates_ids = array_unique( $this->view_template_used_ids );

		foreach ( $view_templates_ids as $view_template_id ) {
			$extra_css = '';

			$view_template = WPV_Content_Template::get_instance( $view_template_id );

			if ( null === $view_template ) {
				continue;
			}

			if ( $view_template->should_include_frontend_extra_assets() ) {
				$extra_css = $view_template->get_template_extra_css();
			}

			/**
			 * Get third party CSS code added inside a page builder or elsewhere.
			 *
			 * @since 2.5.0
			 *
			 * @param string $extra_third_party_css The third party CSS code.
			 */
			$extra_third_party_css = apply_filters( 'wpv_filter_wpv_get_third_party_css', '', $view_template_id );

			if (
				! empty( $extra_css ) ||
				! empty( $extra_third_party_css )
			) {
				$cssout_item_title = get_post_field( 'post_name', get_post( $view_template_id ) );
				$cssout            .= "/* ----------------------------------------- */\n";
				$cssout            .= "/* " . esc_html( sprintf( __( 'Content Template: %s - start', 'wpv-views' ), $cssout_item_title ) ) . " */\n";
				$cssout            .= "/* ----------------------------------------- */\n";
				$cssout            .= ! empty( $extra_css ) ? $extra_css . "\n" : '';
				$cssout            .= ! empty( $extra_third_party_css ) ? $extra_third_party_css . "\n" : '';
				$cssout            .= "/* ----------------------------------------- */\n";
				$cssout            .= "/* " . esc_html( sprintf( __( 'Content Template: %s - end', 'wpv-views' ), $cssout_item_title ) ) . " */\n";
				$cssout            .= "/* ----------------------------------------- */\n";
			}
		}

		return $cssout;
	}

	public function wpv_meta_html_extra_css() {
		$cssout = $this->get_meta_html_extra_css();

		if ( '' != $cssout ) {
			$cssout = preg_replace( '~\R~u', '', addslashes( $cssout ) );

			$vanilla_js_for_css_out = "\n<script type=\"text/javascript\">\n";
			$vanilla_js_for_css_out .= 'const wpvCtHead = document.getElementsByTagName( "head" )[ 0 ];' . "\n";
			$vanilla_js_for_css_out .= 'const wpvCtExtraCss = document.createElement( "style" );' . "\n";
			$vanilla_js_for_css_out .= 'wpvCtExtraCss.textContent = \'' . $cssout . '\';' . "\n";
			$vanilla_js_for_css_out .= 'wpvCtHead.appendChild( wpvCtExtraCss );' . "\n";
			$vanilla_js_for_css_out .= "</script>\n";
			echo $vanilla_js_for_css_out;
		}
	}


	function wpv_meta_html_extra_js() {
		$view_templates_ids = array_unique( $this->view_template_used_ids );
		$jsout = '';
		foreach ( $view_templates_ids as $view_template_id ) {
			$extra_js = '';

			$view_template = WPV_Content_Template::get_instance( $view_template_id );

			if ( null === $view_template ) {
				continue;
			}

			if ( $view_template->should_include_frontend_extra_assets() ) {
				$extra_js = $view_template->get_template_extra_js();
			}

			if (
				isset( $extra_js )
				&& '' != $extra_js
			) {
				$jsout_item_title = get_post_field( 'post_name', get_post( $view_template_id ) );
				$jsout .= "//-----------------------------------------\n";
				$jsout .= "// " . esc_html( sprintf( __( 'Content Template: %s - start', 'wpv-views' ), $jsout_item_title ) ) . "\n";
				$jsout .= "//-----------------------------------------\n";
				$jsout .= $extra_js . "\n";
				$jsout .= "//-----------------------------------------\n";
				$jsout .= "// " . esc_html( sprintf( __( 'Content Template: %s - end', 'wpv-views' ), $jsout_item_title ) ) . "\n";
				$jsout .= "//-----------------------------------------\n";
			}
		}
		if ( '' != $jsout ) {
			echo "\n<script type=\"text/javascript\">\n" . $jsout . "</script>\n";
		}
	}
}

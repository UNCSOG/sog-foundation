<?php
/**
 * Class Access_Ajax_Handler_Show_Error_List
 * Show custom errors list
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Show_Error_List extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Show_Error_List constructor.
	 *
	 * @param \OTGS\Toolset\Access\Ajax $access_ajax
	 */
	public function __construct( \OTGS\Toolset\Access\Ajax $access_ajax ) {
		parent::__construct( $access_ajax );
	}


	/**
	 * @param mixed $arguments
	 *
	 * @return array
	 */
	function process_call( $arguments ) {

		$this->ajax_begin( array( 'nonce' => 'wpcf-access-error-pages' ) );

		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$custom_errors = \OTGS\Toolset\Access\Controllers\CustomErrorSettings::get_instance();
		$types_settings = $access_settings->get_types_settings( true, true );
		$post_type = sanitize_text_field( $_POST['posttype'] );
		$is_archive = sanitize_text_field( $_POST['is_archive'] );
		$role = ( isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '' );

		$out = '
			<form method="" id="wpcf-access-set_error_page">
				<input type="hidden" value="' . esc_attr( $_POST['access_type'] ) . '" name="typename">
				<input type="hidden" value="' . esc_attr( $_POST['access_value'] ) . '" name="valuename">';
		if ( $is_archive == 1 ) {
			$out .= '<input type="hidden" value="'
				. esc_attr( $_POST['access_archivetype'] )
				. '" name="archivetypename">
				<input type="hidden" value="'
				. esc_attr( $_POST['access_archivevalue'] )
				. '" name="archivevaluename">';
		}

		$out .= '<h2>'
			. __( 'What to display for single-posts when there is no read permission', 'wpcf-access' )
			. '</h2>';
		$checked = ( isset( $_POST['cur_type'] ) && $_POST['cur_type'] == '' ) ? ' checked="checked" ' : '';
		if ( $_POST['forall'] != 1 ) {
			$out .= '
				<p>
					<label>
						<input type="radio" value="default" name="error_type" class="js-wpcf-access-type"'
				. $checked
				. '> '
				. __( 'Default error', 'wpcf-access' )
				. '
					</label>
				</p>';
		}
		$checked = ( isset( $_POST['cur_type'] ) && $_POST['cur_type'] == 'error_404' ) ? ' checked="checked" ' : '';
		if ( $_POST['forall'] == 1 && isset( $_POST['cur_type'] ) && $_POST['cur_type'] == '' ) {
			$checked = ' checked="checked" ';
		}

		$out .= '
				<p>
					<label>
						<input type="radio" value="error_404" name="error_type"'
			. $checked
			. ' class="js-wpcf-access-type"> '
			. __( 'Show 404 - page not found', 'wpcf-access' )
			. '
					</label>
				</p>';
		if ( class_exists( 'WP_Views' ) && ! defined( 'WPDDL_VERSION' ) ) {
			$checked = ( isset( $_POST['cur_type'] ) && $_POST['cur_type'] == 'error_ct' ) ? ' checked="checked" ' : '';
			$out .= '
				<p>
					<label>
						<input type="radio" value="error_ct" name="error_type"'
				. $checked
				. ' class="js-wpcf-access-type"> '
				. __( 'Show Content Template', 'wpcf-access' )
				. '
					</label>
					<select name="wpcf-access-ct" class="hidden" class="js-wpcf-error-ct-value">
						<option value="">'
				. __( 'None', 'wpcf-access' )
				. '</option>';
			$wpv_args = array(
				'post_type' => 'view-template',
				'posts_per_page' => - 1,
				'order' => 'ASC',
				'orderby' => 'title',
				'post_status' => 'publish',
			);
			$content_tempaltes = get_posts( $wpv_args );
			foreach ( $content_tempaltes as $post ) :
				$out .= '
						<option value="'
					. esc_attr( $post->ID )
					. '"'
					. selected( ( isset( $_POST['cur_value'] ) && $_POST['cur_value'] == $post->ID ), true, false )
					. '>'
					. $post->post_title
					. '</option>';
			endforeach;
			$out .= '
					</select>
				</p>';
		}

		if ( defined( 'WPDDL_VERSION' ) ) {
			$checked = ( isset( $_POST['cur_type'] ) && $_POST['cur_type'] == 'error_layouts' ) ? ' checked="checked" '
				: '';
			$layouts_settings = \OTGS\Toolset\Access\Models\GuiCommon::get_instance()->get_layouts_list();

			if ( ! empty( $layouts_settings ) ) {
				$out .= '
					<p>
						<label>
							<input type="radio" value="error_layouts" name="error_type"'
					. $checked
					. ' class="js-wpcf-access-type"> '
					. __( 'Show Template Layout', 'wpcf-access' )
					. '
						</label>
						<select name="wpcf-access-layouts" class="hidden" class="js-wpcf-error-layouts-value">
							<option value="">'
					. __( 'None', 'wpcf-access' )
					. '</option>';
				foreach ( $layouts_settings as $id => $layout ) {
					$out .= '
										<option value="'
						. esc_attr( $id )
						. '"'
						. selected( ( isset( $_POST['cur_value'] ) && $_POST['cur_value'] == $id ), true, false )
						. '>'
						. $layout['post_title']
						. '</option>';
				}
				$out .= '
						</select>
					</p>';
			}
		}

		$templates = wp_get_theme()->get_page_templates();
		if ( ! empty( $templates ) ) {
			$checked = ( isset( $_POST['cur_type'] ) && $_POST['cur_type'] == 'error_php' ) ? ' checked="checked" '
				: '';
			$out .= '
				<p>
					<label>
						<input type="radio" value="error_php" name="error_type"'
				. $checked
				. ' class="js-wpcf-access-type"> '
				. __( 'Show Page template', 'wpcf-access' )
				. '
					</label>
					<select name="wpcf-access-php" class="hidden" class="js-wpcf-error-php-value">
						<option value="">'
				. __( 'None', 'wpcf-access' )
				. '</option>';
			foreach ( $templates as $template_name => $template_filename ) {
				$out .= '<option value="'
					. esc_attr( $template_filename )
					. '"'
					. selected( ( isset( $_POST['cur_value'] )
						&& $_POST['cur_value']
						== $template_filename ), true, false )
					. '>'
					. $template_filename
					. '</option>';
			}
			$out .= '
					</select>
				</p>';
		}

		if ( strpos( $post_type, 'wpcf-custom-group' ) !== false ) {
			global $wpcf_access;
			$link_title = sprintf( __( 'Preview error for %s', 'wpcf-access' ), $wpcf_access->settings->types[ $post_type ]['title'] );
			$single_post_preview_url = $custom_errors->get_single_cpt_preview_link( $post_type, 'post-group' );
		} elseif ( strpos( $post_type, 'wpcf-wpml-group' ) !== false ) {
			global $wpcf_access;
			$link_title = sprintf( __( 'Preview error for %s', 'wpcf-access' ), $wpcf_access->settings->types[ $post_type ]['title'] );
			$single_post_preview_url = $custom_errors->get_single_cpt_preview_link( $post_type, 'wpml-group' );
		} else {
			$single_post_preview_url = $custom_errors->get_single_cpt_preview_link( $post_type, 'post' );
			$post_type_object = get_post_type_object( $post_type );
			$link_title = sprintf( __( 'Preview error for %s', 'wpcf-access' ), $post_type_object->labels->singular_name );
		}
		if ( ! empty( $single_post_preview_url ) && $_POST['forall'] != 1
			&& 'not_managed'
			!= $single_post_preview_url ) {
			//Show preview single post link
			$out .= '<div class="align-right">
					<a  href="#preview_error" class="js-toolset-access-preview-single" data-role="' . $role . '"
					data-posttype="' . $post_type . '" data-url="' . esc_attr( $single_post_preview_url ) . '">'
				. $link_title . '
		            <i class="icon-external-link fa fa-external-link icon-small"></i></a>
				</div>';
		} elseif ( empty( $single_post_preview_url ) && $_POST['forall'] != 1 ) {
			$out .= '<div class="align-right"><a href="" class="toolset-access-disabled-link">'
				. __( 'No posts for preview', 'wpcf-access' )
				. '</a></div>';
		}

		if ( $is_archive == 1 ) {
			$archive_out = '';
			//Hide php templates
			$show_php_templates = true;
			$out .= '<h2>'
				. __( 'What to display for archives when there is no read permission', 'wpcf-access' )
				. '</h2>';

			if ( class_exists( 'WP_Views' )
				&& function_exists( 'wpv_force_wordpress_archive' )
				&& ! class_exists( 'WPDD_Layouts' ) ) {
				global $WPV_view_archive_loop, $WP_Views;

				$show_php_templates = false;

				$checked = ( isset( $_POST['cur_archivetype'] ) && $_POST['cur_archivetype'] == 'error_ct' )
					? ' checked="checked" ' : '';
				$has_items = wpv_check_views_exists( 'archive' );
				if ( $has_items ) {
					$archive_out .= '<p><label>
						<input type="radio" value="error_ct" name="archive_error_type" '
						. $checked
						. 'class="js-wpcf-access-type-archive">
						'
						. __( 'Choose a different WordPress archive for people without read permission', 'wpcf-access' )
						. '<br />';
					$archive_out .= '</label>';
					$wpv_args = array( // array of WP_Query parameters
						'post_type' => 'view',
						'post__in' => $has_items,
						'posts_per_page' => - 1,
						'order' => 'ASC',
						'orderby' => 'title',
						'post_status' => 'publish',
					);
					$wpv_query = new WP_Query( $wpv_args );
					$wpv_count_posts = $wpv_query->post_count;
					if ( $wpv_count_posts > 0 ) {
						$archive_out .= '<select name="wpcf-access-archive-ct" class="js-wpcf-error-ct-value">
						<option value="">' . __( 'None', 'wpcf-access' ) . '</option>';
						while ( $wpv_query->have_posts() ) :
							$wpv_query->the_post();
							$post_id = get_the_id();

							$post = get_post( $post_id );
							$archive_out .= '<option value="'
								. esc_attr( $post->ID )
								. '" '
								. selected( ( isset( $_POST['cur_archivevalue'] )
									&& $_POST['cur_archivevalue']
									== $post->ID ), true, false )
								. '>'
								. $post->post_title
								. '</option>';
						endwhile;
						$archive_out .= '</select>';
					} else {
						$archive_out = '<p>'
							. __( 'Sorry, no alternative WordPress Archives. First, create a new WordPress Archive, then return here to choose it.', 'wpcf-access' )
							. '</p>';
					}

				} else {
					$archive_out .= '<p>'
						. __( 'Sorry, no alternative WordPress Archives. First, create a new WordPress Archive, then return here to choose it.', 'wpcf-access' )
						. '</p>';
				}

			}

			if ( class_exists( 'WPDD_Layouts' ) ) {
				$checked = ( isset( $_POST['cur_archivetype'] ) && $_POST['cur_archivetype'] == 'error_layouts' )
					? ' checked="checked" ' : '';
				$layouts_settings = \OTGS\Toolset\Access\Models\GuiCommon::get_instance()->get_layouts_list();
				if ( ! empty( $layouts_settings ) ) {
					$archive_out .= '
						<p>
							<label>
								<input type="radio" value="error_layouts" name="archive_error_type"'
						. $checked
						. ' class="js-wpcf-access-type"> '
						.
						__( 'Show Template Layout', 'wpcf-access' )
						. '<br />
							</label>
							<select name="wpcf-access-archive-layouts" class="js-wpcf-archive-error-layouts-value">
								<option value="">'
						. __( 'None', 'wpcf-access' )
						. '</option>';
					foreach ( $layouts_settings as $id => $layout ) {
						$archive_out .= '
											<option value="'
							. esc_attr( $id )
							. '"'
							. selected( ( isset( $_POST['cur_archivevalue'] )
								&& $_POST['cur_archivevalue']
								== $id ), true, false )
							. '>'
							. $layout['post_title']
							. '</option>';
					}
					$archive_out .= '
							</select>
						</p>';
				} else {
					$archive_out = '<p>'
						. __( 'Sorry, no Template Layouts found. First, create new Template Layout, then return here to choose it.', 'wpcf-access' )
						. '</p>';
				}

				$show_php_templates = false;

			}


			if ( $show_php_templates ) {
				$theme_files = array();
				if ( isset( $_POST['cur_archivevalue'] ) ) {
					$_POST['cur_archivevalue'] = urldecode( $_POST['cur_archivevalue'] );
					$_POST['cur_archivevalue'] = str_replace( "\\\\", "\\", $_POST['cur_archivevalue'] );
				}

				if ( is_child_theme() ) {
					$child_theme_dir = get_stylesheet_directory();
					$theme_files = $custom_errors->wpcf_access_scan_dir( $child_theme_dir, $theme_files );
				}
				$theme_dir = get_template_directory() . '/';

				if ( file_exists( $theme_dir . 'archive-' . $post_type . '.php' ) ) {
					$current_file = 'archive-' . $post_type . '.php';
				} elseif ( file_exists( $theme_dir . 'archive.php' ) ) {
					$current_file = 'archive.php';
				} else {
					$current_file = 'index.php';
				}
				$error_message = sprintf(
					__( 'This custom post archive displays with the PHP template "%s".', 'wpcf-access' ), $current_file );
				$theme_files = $custom_errors->wpcf_access_scan_dir( $theme_dir, $theme_files, $current_file );
				$checked = ( isset( $_POST['cur_archivetype'] ) && $_POST['cur_archivetype'] == 'error_php' )
					? ' checked="checked" ' : '';

				$archive_out .= '<p><label>
					<input type="radio" value="error_php" name="archive_error_type"'
					. $checked
					. ' class="js-wpcf-access-type-archive"> '
					. __( 'Choose a different PHP template for people without read permission', 'wpcf-access' )
					. '
					</label>
					<p class="toolset-alert toolset-alert- js-wpcf-error-php-value-info" style="display: none; opacity: 1;">
					'
					. $error_message
					. '
					</p><select name="wpcf-access-archive-php" class="js-wpcf-error-php-value hidden">
					<option value="">'
					. __( 'None', 'wpcf-access' )
					. '</option>';
				for ( $i = 0, $limit = count( $theme_files ); $i < $limit; $i ++ ) {
					$archive_out .= '<option value="'
						. esc_attr( $theme_files[ $i ] )
						. '" '
						. selected( ( isset( $_POST['cur_archivevalue'] )
							&& $_POST['cur_archivevalue']
							== $theme_files[ $i ] ), true, false )
						. '>'
						. preg_replace( "/.*(\/.*\/)/", "$1", $theme_files[ $i ] )
						. '</option>';
				}
				$archive_out .= '</select></p>';
			}

			//Default error, use for everyone if set.
			if ( $_POST['forall'] != 1 ) {
				$checked = ( empty( $checked ) ) ? ' checked="checked" ' : '';
				$out .= '<p><label>
				<input type="radio" value="default" name="archive_error_type" class="js-wpcf-access-type-archive"'
					. $checked
					. '> '
					. __( 'Default error', 'wpcf-access' )
					. '
				</label></p>';
			}

			//Show post not found message'
			//Set post type not queryable
			$checked = ( isset( $_POST['cur_archivetype'] )
				&& $_POST['cur_archivetype'] == 'default_error'
				|| empty( $checked ) ) ? ' checked="checked" ' : '';
			$out .= '<p><label>
			<input type="radio" value="default_error" name="archive_error_type" class="js-wpcf-access-type-archive"'
				. $checked
				. '> '
				. __( 'Display: "No posts found"', 'wpcf-access' )
				. '
			</label></p>';

			$out .= $archive_out;

			$archive_preview_url = get_bloginfo( 'url' ) . '/?post_type=' . $post_type;
			if ( $_POST['forall'] == 0 && isset( $types_settings[ $post_type ]['mode'] )
				&& 'permissions'
				== $types_settings[ $post_type ]['mode'] ) {
				//Show preview archive link
				$out .= '<div class="align-right">
					<a  href="#preview_error" class="js-toolset-access-preview-archive" data-role="' . $role . '"
					data-posttype="' . $post_type . '" data-url="' . esc_attr( $archive_preview_url ) . '">'
					. sprintf( __( 'Preview error for archive %s', 'wpcf-access' ), $post_type ) . '
		            <i class="icon-external-link fa fa-external-link icon-small"></i></a>
				</div>';
			}

		}//End check if post type have archive

		$out .= '</form>';
		wp_send_json_success( $out );

	}
}

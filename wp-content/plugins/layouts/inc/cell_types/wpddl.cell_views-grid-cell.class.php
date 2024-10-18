<?php
/*
 * Theme Views content grid cell type.
 * Displays current theme basic footer with two credits area.
 *
 */



if ( ddl_has_feature( 'views-content-grid-cell' ) === false ) {
	return;
}

if ( ! class_exists( 'Layouts_cell_views_content_grid', false ) ) {
	class Layouts_cell_views_content_grid extends Layouts_views_based_cell {

		protected $cell_type = 'views-content-grid-cell';

		private $views_not_available_message = "Sorry, preview is not available. Please make sure that the Toolset Views plugin is active.";

		function __construct() {

			$this->views_not_available_message = apply_filters( 'toolset_is_views_available', false ) === false ? __( "Sorry, preview is not available. Please make sure that the Toolset Views plugin is active.", 'ddl-layouts' ) : __( "Sorry, preview is not available. This View may have been deleted.", 'ddl-layouts' );

			// add actions
			add_action( 'init', array( &$this, 'register_views_content_grid_cell_init' ), 12 );

			// javascript calls
			add_action( 'wp_ajax_ddl_views_content_grid_preview', array( &$this, 'ddl_views_content_grid_preview' ) );
			add_action( 'wp_ajax_ddl_create_new_view', array( &$this, 'ddl_create_new_view' ) );
			add_action( 'wp_ajax_ddl_get_settings_for_view', array( &$this, 'ddl_get_settings_for_view' ) );
			add_action( 'wp_ajax_ddl_save_view_columns', array( &$this, 'ddl_save_view_columns' ) );
			// add shortcodes
			add_shortcode( 'ddl-pager-prev-page', array( &$this, 'ddl_pagination_previous_shortcode' ) );
			add_shortcode( 'ddl-pager-next-page', array( &$this, 'ddl_pagination_next_shortcode' ) );
		}


		function register_views_content_grid_cell_init() {

			if ( function_exists( 'register_dd_layout_cell_type' ) ) {
				register_dd_layout_cell_type( $this->cell_type, array(
						'name'                     => __( 'View (content lists, custom searches, custom sliders)', 'ddl-layouts' ),
						'description'              => __( 'Load content and display it with your styling. A View is used for any custom content display, including custom searches, tables, grids, sliders and content lists.', 'ddl-layouts' ),
						'category'                 => __( 'Lists and loops', 'ddl-layouts' ),
						'button-text'              => __( 'Assign View cell', 'ddl-layouts' ),
						'dialog-title-create'      => __( 'Create new View cell', 'ddl-layouts' ),
						'dialog-title-edit'        => __( 'Edit View cell', 'ddl-layouts' ),
						'dialog-template-callback' => array( &$this, 'cell_dialogs_callback' ),
						'cell-content-callback'    => array( &$this, 'views_content_grid_content_callback' ),
						'cell-template-callback'   => array( &$this, 'views_content_grid_template_callback' ),
						'preview-image-url'        => DDL_ICONS_PNG_REL_PATH . 'views-content-grid_expand-image2.png',
						'cell-image-url'           => DDL_ICONS_SVG_REL_PATH . 'views-grid-01.svg',
						'has_settings'             => false,
						'register-scripts'         => $this->cell_edit_script(),
					) );
			}
		}

		protected function cell_edit_script() {
			if ( is_admin() ) {
				return array(
					array(
						'ddl_views_content_grid_js',
						WPDDL_RELPATH . '/inc/gui/dialogs/js/views-grid-cell.js',
						array( 'jquery' ),
						WPDDL_VERSION,
						true
					)
				);
			}
		}

		function ddl_views_content_grid_preview() {
			/*
			if(!class_exists('WP_Views')){
				$output = "<center>";
				$output .=__($this->views_not_available_message, 'ddl-layouts');
				$output .= "</center><br><br>";
				die( $output );
			}
			*/
			// check permissions
			if ( WPDD_Utils::user_not_admin() ) {
				die( __( "You don't have permission to perform this action!", 'ddl-layouts' ) );
			}
			// check nonce
			if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'ddl_layout_view_nonce' ) ) {
				die( 'verification failed' );
			}

			global $wpdb;

			if ( isset( $_POST['view_id'] ) ) {
				$view_id = $_POST['view_id'];
			} else {
				return __( 'View not set', 'ddl-layouts' );
			}

			$layout_style = array(
				'unformatted'     => __( 'Unformatted', 'ddl-layouts' ),
				'bootstrap-grid'  => __( 'Unformatted', 'ddl-layouts' ),
				'table'           => __( 'Table-based grid', 'ddl-layouts' ),
				'table_of_fields' => __( 'Table', 'ddl-layouts' ),
				'un_ordered_list' => __( 'Unordered list', 'ddl-layouts' ),
				'ordered_list'    => __( 'Ordered list', 'ddl-layouts' )
			);

			$view = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title, post_status FROM $wpdb->posts WHERE ID = %d AND post_type='view'", $view_id ) );
			if ( isset( $view[0] ) ) {

				if ( $view[0]->post_status === 'trash' ) {
					$view_details   = get_post_meta( $view_id );
					$views_settings = unserialize( $view_details['_wpv_settings'][0] );
					$cell_type      = ( is_array( $views_settings ) && in_array( $views_settings['view-query-mode'], array(
							'layouts-loop',
							'archive'
						) ) ) ? 'views_archive' : 'view';

					die( json_encode( array( "status" => "trash", "cell_type" => $cell_type ) ) );
				}


				$post_title    = $view[0]->post_title;
				$id            = $view[0]->ID;
				$view_settings = get_post_meta( $id, '_wpv_settings', true );
				$meta          = get_post_meta( $id, '_wpv_layout_settings', true );
				if ( ! isset( $meta['style'] ) ) {
					$meta['style'] = 'unformatted';
				}

				$view_purpose = '';

				if ( isset( $view_settings['view-query-mode'] ) && $view_settings['view-query-mode'] == 'normal' ) {
					$view_output = get_view_query_results( $id );
					if ( ! isset( $view_settings['view_purpose'] ) ) {
						$view_settings['view_purpose'] = 'full';
					}
					switch ( $view_settings['view_purpose'] ) {
						case 'all':
							$view_purpose = __( 'Display all results', 'ddl-layouts' );
							break;

						case 'pagination':
							$view_purpose = __( 'Display the results with pagination', 'ddl-layouts' );
							break;

						case 'slider':
							$view_purpose = __( 'Display the results as a slider', 'ddl-layouts' );
							break;

						case 'parametric':
							$content = $_POST['content'];
							switch ( $content['parametric_mode'] ) {
								case 'full':
									?>
                                    <div class="ddl-parametric-search-preview">
                                        <img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/png/parametric-search-cells/both-search-form-and-results.png'; ?>"
                                             height="204px">
                                    </div>
									<?php
									die();
									break;

								case 'form':

									if ( $content['parametric_mode_target'] == 'self' && $_POST['target_found'] != 'true' ) {
										?>
                                        <div class="ddl-parametric-search-preview">
                                            <img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/png/parametric-search-cells/search-form-only-results-missing.png'; ?>"
                                                 height="204px">
                                        </div>
										<?php
										die();
									}

									if ( $content['parametric_mode_target'] == 'other' ) {
										?>
                                        <div class="ddl-parametric-search-preview">
                                            <img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/png/parametric-search-cells/search-form-only-results-in-a-different-page.png'; ?>"
                                                 height="204px">
                                        </div>
										<?php
										die();
									}

									?>
                                    <div class="ddl-parametric-search-preview">
                                        <img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/png/parametric-search-cells/search-form-only.png'; ?>"
                                             height="100px">
                                    </div>
									<?php
									die();
									break;

								case 'results':
									?>
                                    <div class="ddl-parametric-search-preview">
                                        <img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/png/parametric-search-cells/search-results-only.png'; ?>"
                                             height="100px">
                                    </div>
									<?php
									die();
									break;
							}
							break;

						case 'full':
							$view_purpose = __( 'Displays a fully customized display', 'ddl-layouts' );
							break;
					}

					echo $view_purpose;
					echo '<br />';

				} else {
					$view_output = array();

					if ( $meta['style'] == 'bootstrap-grid' || $meta['style'] == 'table' ) {
						if ( $meta['style'] == 'bootstrap-grid' ) {
							$col_number = $meta['bootstrap_grid_cols'];
						} else {
							$col_number = $meta['table_cols'];
						}

						// add 2 rows of items.
						for ( $i = 1; $i <= 2 * $col_number; $i ++ ) {
							$item             = new stdClass();
							$item->post_title = sprintf( __( 'Post %d', 'ddl-layouts' ), $i );
							$view_output[]    = $item;
						}

					} else {
						// just add 3 items
						for ( $i = 1; $i <= 3; $i ++ ) {
							$item             = new stdClass();
							$item->post_title = sprintf( __( 'Post %d', 'ddl-layouts' ), $i );
							$view_output[]    = $item;
						}
					}

				}
				$this->ddl_views_generate_cell_preview( $post_title, $id, $meta, $view_output );
			}

			die();
		}


		function ddl_create_new_view() {
			global $wpdb;

			if ( WPDD_Utils::user_not_admin() ) {
				die( __( "You don't have permission to perform this action!", 'ddl-layouts' ) );
			}
			if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'ddl_layout_view_nonce' ) ) {
				die( 'verification failed' );
			}

			$view_type = 'normal';
			if ( isset( $_POST['is_archive'] ) ) {
				$view_type = 'layouts-loop';
			}
			$view_purpose = 'full';
			if ( isset( $_POST['purpose'] ) ) {
				$view_purpose = $_POST['purpose'];
			}

			$name        = $original_name = $_POST['cell_name'];
			$i           = 0;
			$name_in_use = true;
			while ( $name_in_use ) {
				$i ++;
				$postid = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts}
                            WHERE ( post_title = %s OR post_name = %s )
                            AND post_type = 'view'
                            LIMIT 1", $name, strtolower( $name ) ) );
				if ( $postid ) {
					$name = $original_name . ' ' . $i;
				} else {
					$name_in_use = false;
				}
			}
			$args    = array(
				'title'    => $name,
				'settings' => array(
					'view_purpose'    => $view_purpose,// This is not a purpose but a layout
					'view-query-mode' => $view_type
				)
			);
			$view_id = wpv_create_view( $args );
			if ( isset( $view_id['success'] ) ) {

				$id = $view_id['success'];

				// set it to filter posts by default.
				$view_settings              = get_post_meta( $id, '_wpv_settings', true );
				$view_settings['post_type'] = array( 'post' );
				if ( isset( $_POST['purpose'] ) ) {
					$view_settings['view_purpose'] = $_POST['purpose'];
				} else {
					$view_settings['view_purpose'] = 'full';
				}

				if ( $view_type == 'layouts-loop' ) {
					// show the content section for pagination.
					unset( $view_settings['sections-show-hide']['content'] );
				}

				update_post_meta( $id, '_wpv_settings', $view_settings );

				$res       = $wpdb->get_results( "SELECT post_name FROM $wpdb->posts WHERE ID = '" . $id . "' AND post_type='view'" );
				$post_name = $res[0]->post_name;
				$output    = wp_json_encode( array( 'id' => $id, 'post_name' => $post_name, 'post_title' => $name ) );
				//print wp_json_encode(array( 'id'=>$id, 'post_name' => $post_name, 'post_title'=> $name));

			} else {
				$output = wp_json_encode( array( 'error' => $view_id, 'message' => $view_id ) );
			}

			die( $output );
		}


		/*
		 * Shortcodes functions
		 */
		function ddl_pagination_previous_shortcode( $atts, $value ) {
			return get_next_posts_link( do_shortcode( $value ) );
		}

		function ddl_pagination_next_shortcode( $atts, $value ) {
			return get_previous_posts_link( do_shortcode( $value ) );
		}

		/*
		* Get settings about the View
		* $id, $slug, $title
		*
		* DEPRECATED
		* Not sure if in use anymore
		* We do not get the View settings anymore when selecting one
		*/
		function ddl_get_settings_for_view() {
			global $wpdb;

			if ( WPDD_Utils::user_not_admin() ) {
				die( __( "You don't have permission to perform this action!", 'ddl-layouts' ) );
			}
			if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'ddl_layout_view_nonce' ) ) {
				die( 'verification failed' );
			}

			$result = array();

			if ( isset( $_POST['view_id'] ) ) {
				$view_id = $_POST['view_id'];
				$view    = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM $wpdb->posts WHERE ID = %d AND post_type='view'", $view_id ) );
				if ( isset( $view[0] ) ) {
					$id   = $view[0]->ID;
					$meta = get_post_meta( $id, '_wpv_layout_settings', true );
					// depricated
					if ( $this->ddl_confirm_ok_to_change_grid_cols( $meta ) ) {
						//$result['grid_settings'] = $meta['bootstrap_grid_cols'];
					}
					$result['title'] = $view[0]->post_title;

				}
			}

			print wp_json_encode( $result );

			die();
		}


		/*
		* Save the View settings for columns
		*
		* DEPRECATED
		*/
		function ddl_save_view_columns() {
			global $wpdb;

			if ( WPDD_Utils::user_not_admin() ) {
				die( __( "You don't have permission to perform this action!", 'ddl-layouts' ) );
			}
			if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'ddl_layout_view_nonce' ) ) {
				die( 'verification failed' );
			}

			$result = array();

			print wp_json_encode( $result );

			die();
		}

		// DEPRECATED
		function ddl_confirm_ok_to_change_grid_cols( $meta ) {
			$ok_to_update = false;

			if ( isset( $meta['style'] ) && $meta['style'] == 'bootstrap-grid' ) {
				if ( function_exists( 'wpv_create_bootstrap_meta_html' ) ) {
					$meta_html_current = $meta['layout_meta_html'];
					// find the content template used
					$match = array();

					if ( preg_match( '/\[wpv-post-body view_template="(.*?)\"\]/', $meta_html_current, $match ) ) {
						$template = $match[1];
						$old_test = wpv_create_bootstrap_meta_html( $meta['bootstrap_grid_cols'], $template, $meta_html_current );

						if ( preg_replace( '/\s+/', '', $old_test ) == preg_replace( '/\s+/', '', $meta_html_current ) ) {
							$ok_to_update = true;
						}
					}
				} else {
					// set it to true so that the column select or shown.
					$ok_to_update = true;
				}
			}

			return $ok_to_update;

		}


		function views_content_grid_template_callback() {
			global $WP_Views;
			if ( class_exists( 'WP_Views' ) ) {

				ob_start();

				?>
                <div class="cell-content">
                    <p class="cell-name"><?php echo __( 'View' ); ?>: {{ name }}</p>
                    <div class="cell-preview">
                        <#
                        if (content) {
                        var preview = DDLayout.views_preview.get_preview( name,
                        content,
                        '<?php _e( 'Updating', 'ddl-layouts' ); ?>...',
                        '<?php _e( 'Loading', 'ddl-layouts' ); ?>...',
                        '<?php echo DDL_ICONS_SVG_REL_PATH . 'views-content-grid.svg'; ?>',
                        'view'
                        );
                        print( preview );
                        }
                        #>
                    </div>
                </div>
				<?php
				return ob_get_clean();
			} else {
				ob_start();
				$output = '<div class="ddl-center-align">';
				$output .= __( $this->views_not_available_message, 'ddl-layouts' );
				$output .= "</div>";
				?>
                <div class="cell-content">
                    <p class="cell-name"><?php echo __( 'View' ); ?>: {{ name }}</p>
                    <div class="cell-preview">
                        <div class="js-views-content-grid-92-full92">
							<?php echo $output; ?>
                        </div>
                    </div>
                </div>
				<?php
				return ob_get_clean();
			}
		}

		function get_view_slug_from_id( $id ) {
			return get_post_field( 'post_name', $id );
		}

		function views_content_grid_content_callback() {
			//Render View
			if ( function_exists( 'render_view' ) ) {
				$mode              = get_ddl_field( 'parametric_mode' );
				$target            = get_ddl_field( 'parametric_mode_target' );
				$target_id         = get_ddl_field( 'parametric_target_id' );
				$is_private_layout = get_ddl_field( 'is_private_layout' );

				if ( $target == 'self' ) {
					$target_id = 'self';
				}

				if ( $is_private_layout === true ) {
					$view_id   = get_ddl_field( 'ddl_layout_view_id' );
					$view_slug = $this->get_view_slug_from_id( $view_id );

					if ( $mode == 'form' && ! empty( $target_id ) ) {
					    return '[wpv-form-view name="' . $view_slug . '" target_id="'.$target_id.'"]';
				    } elseif ( $mode == 'results' ) {
						return '[wpv-view name="' . $view_slug . '" view_display="layout"]';
					} else {
						return '[wpv-view name="' . $view_slug . '"]';
					}
				}


				if ( $mode == 'form' && ! empty( $target_id ) ) {
					return render_view( array(
						'id'        => get_ddl_field( 'ddl_layout_view_id' ),
						'target_id' => $target_id
					) );
				} else if ( $mode == 'results' ) {
					return render_view( array(
						'id'           => get_ddl_field( 'ddl_layout_view_id' ),
						'view_display' => 'layout'
					) );
				} else {
					return render_view( array( 'id' => get_ddl_field( 'ddl_layout_view_id' ) ) );
				}
			} else {
				return WPDDL_Messages::views_missing_message();
			}

		}


		function ddl_views_generate_cell_preview( $post_title, $id, $meta, $view_output ) {
            $count_view_output = count($view_output);
            //Generate preview for bootstrap grid and table based grid
            if ( !isset($meta['style']) ){
                $meta['style'] = 'unformatted';
            }
            if ( $meta['style'] == 'bootstrap-grid'  ):
                $col_number = $meta['bootstrap_grid_cols'];
                $i=$k=0;
                $col_width = 12/$col_number;
                ?>
                <i class="fa fa-th-large icon-th-large ddl-view-layout-icon"></i><?php _e('Bootstrap grid', 'ddl-layouts'); ?>
                <br />
                <div class="presets-list fields-group">
                <?php
                $total_rows = 0;

                if ( $count_view_output > 0 ){
                    for ($j = 0, $limit=$count_view_output; $j < $limit; $j++){
                        $view_post = $view_output[$j];
                        $cell_content = $this->ddl_view_content_grid_get_title( $view_post );
                        $i++;
                        if ($i == 1){
                            $total_rows++;
                            if ( $total_rows > 3){
                                $j = $count_view_output+1;
                                $hidden_items_count = $limit-$k;
                                $hidden_rows = ceil($hidden_items_count/$col_number);
                                ?>
                                <div class="row-fluid">
                                    <div class="span-preset12 views-cell-preview views-cell-preview-more">
                                        <?php echo sprintf(__('Plus %s more rows - %s items in total', 'ddl-layouts'), $hidden_rows, $limit); ?>
                                    </div>
                                </div>
                                <?php
                                continue;
                            }
                            ?>
                            <div class="row-fluid">
                        <?php
                        }
                        ?>
                        <div class="span-preset<?php echo $col_width; ?> views-cell-preview" ><?php echo $cell_content; ?></div>
                        <?php
                        if ( $i == $col_number){
                            $i=0;
                            ?></div><?php
                        }
                        $k++;
                    }
                    if ( $i != 0 ){
                        ?></div><?php
                    }
                } else {
                    //Show empty grid when no posts
                    ?>
                    <div class="row-fluid">
                        <?php
                        for( $i=0; $i<$col_number; $i++){
                            ?>
                            <div class="span-preset<?php echo $col_width;?> views-cell-preview" ></div>
                        <?php
                        }
                        ?>
                    </div>
                    <div class="row-fluid">
                        <div class="span-preset12 views-cell-preview views-cell-preview-more">
                            <?php _e('No items were returned by the View', 'ddl-layouts'); ?>
                        </div>
                    </div>
                <?php
                }
                ?></div><?php
            elseif ( $meta['style'] == 'table' ):
                $col_number = $meta['table_cols'];
                $i=$k=0;
                $col_width = round(100/$col_number, 2)-2;
                $total_rows = 0;
                ?>
                        <i class="fa fa-th-list icon-th ddl-view-layout-icon"></i><?php _e('Table-based grid', 'ddl-layouts'); ?>
                        <br />
                        <?php
                if ( $count_view_output > 0 ){
                    $total_rows = 0;
                    for ($j = 0, $limit=$count_view_output; $j < $limit; $j++){
                        $view_post = $view_output[$j];
                        $cell_content = $this->ddl_view_content_grid_get_title( $view_post );
                        $i++;
                        if ( $i == 1){
                            $total_rows++;
                            if ( $total_rows > 3){
                                $j = $count_view_output+1;
                                $hidden_items_count = $limit-$k;
                                $hidden_rows = ceil($hidden_items_count/$col_number);
                                ?>
                                <div class="row-fluid row">
                                    <div class="views-cell-table-preview views-cell-preview views-cell-preview-more views-cell-table-preview-more" style="width:100%;">
                                        <?php echo sprintf(__('Plus %s more rows - %s items in total', 'ddl-layouts'), $hidden_rows, $limit); ?>
                                    </div>
                                </div>
                                <?php
                                continue;
                            }
                            ?>
                            <div class="row-fluid">
                        <?php }	?>
                        <div class="views-cell-preview views-cell-table-preview" style="width:<?php echo $col_width?>%;"><?php echo $cell_content;?></div>
                        <?php
                        if ( $i == $col_number ){
                            $i = 0;
                            ?>
                            </div>
                        <?php }

                    }
                    if ( $i != 0 ){
                        ?></div><?php
                    }
                } else {
                    //If table 0 posts
                    ?>
                    <div class="row-fluid">
                        <?php
                        for( $i=0; $i<$col_number; $i++){
                            ?>
                            <div class="views-cell-preview views-cell-table-preview" style="width:<?php echo $col_width?>%;"></div>
                        <?php
                        }
                        ?>
                    </div>
                    <div class="row-fluid row">
                        <div class="views-cell-table-preview views-cell-preview views-cell-preview-more views-cell-table-preview-more" style="width:100%;">
                            <?php _e('No items were returned by the View', 'ddl-layouts'); ?>
                        </div>
                    </div>
                <?php
                }
            elseif ( $meta['style'] == 'unformatted' ||  $meta['style'] == 'un_ordered_list' || $meta['style'] == 'ordered_list' ):
                switch ($meta['style']) {
                    case 'unformatted':
                        $style_icon = 'icon-code fa fa-code';
                        $style_name = __('Unformatted', 'ddl-layouts');
                        break;

                    case 'un_ordered_list':
                        $style_icon = 'fa fa-list-ul icon-list-ul';
                        $style_name = __('Unordered list', 'ddl-layouts');
                        break;

                    case 'ordered_list':
                        $style_icon = 'fa fa-list-ol icon-list-ol';
                        $style_name = __('Ordered list', 'ddl-layouts');
                        break;

                }
                ?>
            <i class="<?php echo $style_icon; ?> ddl-view-layout-icon"></i><?php echo $style_name; ?>
                <br />
                <div class="presets-list fields-group">
                    <?php
                    for ( $i=0; $i<3; $i++ ){
                        if (isset($view_output[$i])) {
                            $view_post = $view_output[$i];
                            $cell_content = $this->ddl_view_content_grid_get_title( $view_post );
                        } else {
                            $cell_content = '';
                        }
                        ?>
                        <div class="row-fluid row">
                            <?php if ( $meta['style'] == 'unformatted' ){?>
                                <div class="span-preset12 views-cell-preview" >
                                    <?php echo $cell_content;?>
                                </div>
                            <?php }elseif(  $meta['style'] == 'un_ordered_list' || $meta['style'] == 'ordered_list' ){
                                $list = '&#149;';
                                if ( $meta['style'] == 'ordered_list' ){
                                    $list = $i+1;
                                }
                                ?>
                                <div class="views-cell-preview views-cell-table-preview views-cell-table-preview-no-border" style="width:8%;">
                                    <?php echo $list;?>
                                </div>
                                <div class="views-cell-preview views-cell-table-preview" style="width:85%;">
                                    <?php echo $cell_content;?>
                                </div>
                            <?php }?>
                        </div>
                    <?php
                    }
                    if ($count_view_output) {
                        $cell_message = '';
                        $limit = $count_view_output;
                        if ( $limit > 3 ){
                            $limit -= 3;
                            $cell_message = sprintf(__('Plus %s more items', 'ddl-layouts'), $limit);
                        }
                    } else {
                        $cell_message = __('No items were returned by the View', 'ddl-layouts');
                    }
                    ?>
                    <?php if ($cell_message): ?>
                        <div class="row-fluid">
                            <div class="span-preset12 views-cell-preview views-cell-preview-more">
                                <?php echo $cell_message ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php
            elseif ( $meta['style'] == 'table_of_fields' ):
                $col_number = (count($meta['fields'])+1)/5-1;
                $i=$k=0;
                $col_width = round(100/$col_number, 2)-2;
                $total_rows = 0;
                ?>
                <i class="icon-table fa fa-table ddl-view-layout-icon"></i><?php _e('Table', 'ddl-layouts'); ?>
                <br />
                <div class="presets-list fields-group">
                <table class="ddl-view-table-preview" width="100%">
                    <thead>
                    <tr>

                        <?php
                        for ( $i=0,$limit=$col_number; $i<$limit; $i++ ){
                            $col_title = __('Column ', 'ddl-layouts').' '.$i;
                            if ( isset($meta['fields']['row_title_'.$i]) && !empty($meta['fields']['row_title_'.$i]) ){
                                $col_title = $meta['fields']['row_title_'.$i];
                            }
                            ?>
                            <td width="<?php echo 100/count($meta['fields']); ?>%"><?php echo $col_title;?></td>
                        <?php
                        }
                        ?>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td colspan="<?php echo count($meta['fields']); ?>">

                            <?php
                            for ( $i=0; $i<3; $i++ ){
                                if (isset($view_output[$i])) {
                                    $view_post = $view_output[$i];
                                    $cell_content = $this->ddl_view_content_grid_get_title( $view_post );
                                } else {
                                    $cell_content = '';
                                }
                                ?>
                                <div class="row-fluid row">
                                    <div class="span-preset12 views-cell-preview" >
                                        <?php echo $cell_content;?>
                                    </div>
                                </div>
                            <?php
                            }
                            $cell_message = __('No items were returned by the View', 'ddl-layouts');
                            $limit = $count_view_output;
                            if ( $limit > 3 ){
                                $limit -= 3;
                                $cell_message = sprintf(__('Plus %s more items', 'ddl-layouts'), $limit);
                            }
                            ?>

                        </td>
                    </tr>
                    </tbody>
                </table>
                <div class="presets-list fields-group">
                    <div class="row-fluid">
                        <div class="span-preset12 views-cell-preview views-cell-preview-more">
                            <?php echo $cell_message ?>
                        </div>
                    </div>
                </div>
            <?php
            else:
                $view_count = $count_view_output;
                ?>
                <?php _e('View name', 'ddl-layouts'); ?>: <?php echo $post_title; ?><br>
                        <?php _e('Layout Style', 'ddl-layouts'); ?>: <?php echo isset($layout_style[$meta['style']])?$layout_style[$meta['style']]:'Undefined'; ?><br>
                        <?php if ( $meta['style'] == 'bootstrap-grid' ) : ?>
                <?php _e('Columns', 'ddl-layouts'); ?>: <?php echo $meta['bootstrap_grid_cols']; ?><br>
            <?php endif; ?>
                <?php if ( $meta['style'] == 'table' ): ?>
                <?php _e('Columns', 'ddl-layouts'); ?> <?php echo $meta['table_cols']; ?><br>
            <?php endif; ?>
                <?php _e('Items to display', 'ddl-layouts'); ?>: <?php echo $view_count; ?><br>
                        <?php
            endif;
        }

		function ddl_view_content_grid_get_title( $view_post ) {
			$cell_content = '';
			if ( isset( $view_post->post_title ) ) {
				$cell_content = $view_post->post_title;
			}
			if ( isset( $view_post->name ) ) {
				$cell_content = $view_post->name;
			}
			if ( isset( $view_post->user_login ) ) {
				$cell_content = $view_post->user_login;
			}

			return $cell_content;
		}


	}

	new Layouts_cell_views_content_grid();
}

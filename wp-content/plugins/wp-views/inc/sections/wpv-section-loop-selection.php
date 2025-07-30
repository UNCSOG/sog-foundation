<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

WPV_Editor_Loop_Selection::on_load();

class WPV_Editor_Loop_Selection {

	/** @var WPV_Editor_Loop_Selection */
	private static $instance;

	/**
	 * Get the instance of the class.
	 *
	 * @return WPV_Editor_Loop_Selection
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	static function on_load() {
		// Register the section in the screen options of the editor pages
		add_filter( 'wpv_screen_options_wpa_editor_section_query',		array( 'WPV_Editor_Loop_Selection', 'wpv_screen_options_loop_selection' ), 10 );
		// Register the section in the editor pages
		add_action( 'wpv_action_wpa_editor_section_query',				array( 'WPV_Editor_Loop_Selection', 'wpv_wpa_editor_section_loop_selection' ), 10, 2 );
		// AJAX management
		add_action( 'wp_ajax_wpv_update_loop_selection',				array( 'WPV_Editor_Loop_Selection', 'wpv_update_loop_selection_callback' ) );
		add_action( 'wp_ajax_wpv_update_post_types_for_archive_loop',	array( 'WPV_Editor_Loop_Selection', 'wpv_update_post_types_for_archive_loop' ) );
	}

	static function wpv_screen_options_loop_selection( $sections ) {
		$sections['archive-loop'] = array(
			'name'		=> __( 'Loop Selection', 'wpv-views' ),
			'disabled'	=> true,
		);
		return $sections;
	}

	static function wpv_wpa_editor_section_loop_selection( $view_settings, $view_id ) {
		$hide = '';
		if ( isset( $view_settings['sections-show-hide'] )
			&& isset( $view_settings['sections-show-hide']['archive-loop'] )
			&& 'off' == $view_settings['sections-show-hide']['archive-loop'] )
		{
			$hide = ' hidden';
		}
		if ( 'layouts-loop' ==  $view_settings['view-query-mode'] ) {
		$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'loops_selection_layouts' );
		?>
		<div class="wpv-setting-container wpv-settings-archive-loops js-wpv-settings-archive-loop<?php echo $hide; ?>">
			<div class="wpv-settings-header">
				<h2>
					<?php _e('Loops Selection', 'wpv-views' ) ?>
				</h2>
				<i class="icon-question-sign fa fa-question-circle js-display-tooltip"
					data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>"
					data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
				</i>
			</div>
			<div class="wpv-setting js-wpv-setting">
				<p>
					<?php _e( 'This WordPress Archive is part of a Layout, so it will display the archive(s) to which the Layout is assigned.', 'wpv-views' ); ?>
				</p>
			</div>
		</div>
		<?php
		} else {
		$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'loops_selection' );
		?>
		<div class="wpv-setting-container wpv-settings-archive-loops js-wpv-settings-archive-loop<?php echo $hide; ?>">
			<div class="wpv-settings-header">
				<h2>
					<?php _e('Loops Selection', 'wpv-views' ) ?>
					<i class="icon-question-sign fa fa-question-circle js-display-tooltip"
						data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>"
						data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
					</i>
				</h2>
			</div>
			<div class="wpv-setting js-wpv-setting">
				<form class="js-loop-selection-form">
					<?php WPV_Editor_Loop_Selection::render_view_loop_selection_form( $view_id ); ?>
				</form>
				<div class="js-wpv-multiple-archive-loops-selected hidden">
					<p class="toolset-alert toolset-alert-info">
						<?php
						echo sprintf(
							__( '%s This WordPress Archive will be used for displaying all the different archive pages selected in this section', 'wpv-views' ),
							'<i class="fa fa-info-circle" aria-hidden="true"></i>'
						);
						?>
					</p>
				</div>
				<div class="js-wpv-toolset-messages"></div>
			</div>
			<span class="update-action-wrap auto-update js-wpv-update-action-wrap">
				<span class="js-wpv-message-container"></span>
				<input type="hidden" data-success="<?php echo esc_attr( __( 'Updated', 'wpv-views' ) ); ?>" data-unsaved="<?php echo esc_attr( __( 'Not saved', 'wpv-views' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpv_view_loop_selection_nonce' ) ); ?>" class="js-wpv-loop-selection-update" />
			</span>
		</div>
		<?php
		}
	}

	/**
	* wpv_update_loop_selection_callback
	*
	* Save WPA loop selection section
	*
	* @since unknown
	*/

	static function wpv_update_loop_selection_callback() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_loop_selection_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["id"] )
			|| ! is_numeric( $_POST["id"] )
			|| intval( $_POST['id'] ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		global $WPV_view_archive_loop;
		parse_str( $_POST['form'], $form_data );
		$WPV_view_archive_loop->update_view_archive_settings( $_POST["id"], $form_data );
		$loop_form = '';
		ob_start();
		WPV_Editor_Loop_Selection::render_view_loop_selection_form( $_POST['id'] );
		$loop_form = ob_get_contents();
		ob_end_clean();
		do_action( 'wpv_action_wpv_save_item', $_POST["id"] );
		$data = array(
			'id' => $_POST["id"],
			'updated_archive_loops' => $loop_form,
			'message' => __( 'Loop Selection saved', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

	static function wpv_update_post_types_for_archive_loop() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_nonce_editor_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["id"] )
			|| ! is_numeric( $_POST["id"] )
			|| intval( $_POST['id'] ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		$type = sanitize_text_field( $_POST['type'] );
		$name = sanitize_text_field( $_POST['name'] );
		$post_types = array_map( 'sanitize_text_field', $_POST['post_types'] );

		$stored_settings = WPV_Settings::get_instance();
		$wpv_post_types_for_archive_loop = $stored_settings->wpv_post_types_for_archive_loop;
		$wpv_post_types_for_archive_loop[ $type ] = isset( $wpv_post_types_for_archive_loop[ $type ] ) ? $wpv_post_types_for_archive_loop[ $type ] : array();
		$wpv_post_types_for_archive_loop[ $type ][ $name ] = $post_types;
		// Chck whether the ones passed are the default ones, and remove in that case the settings?
		// Nope, as when exporting/importing there might be different post types on the target page...
		// So for loops without stored data, actual results on different sites can be different indeed
		$stored_settings->wpv_post_types_for_archive_loop = $wpv_post_types_for_archive_loop;
		$stored_settings->save();

		$loop_form = '';
		ob_start();
		WPV_Editor_Loop_Selection::render_view_loop_selection_form( $_POST['id'] );
		$loop_form = ob_get_contents();
		ob_end_clean();
		//do_action( 'wpv_action_wpv_save_item', $_POST["id"] );
		$data = array(
			'id' => $_POST["id"],
			'updated_archive_loops' => $loop_form,
			'message' => __( 'Loop Selection saved', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

	/**
	 * Static method to get the native WordPress Loops.
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	static function get_wp_loops() {
		return self::get_instance()->get_wp_loops_non_static();
	}

	/**
	 * Non-static method to get the native WordPress Loops.
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	public function get_wp_loops_non_static() {
		// Prepare archive URL for different loops.
		$recent_posts = get_posts( array( "posts_per_page" => 1 ) );
		$default_search_term = __( 'something', 'wpv-views' );
		if( !empty( $recent_posts ) ) {
			$recent_post = reset( $recent_posts );

			// Try to get first word of the post and use it as a search term for search-page loop.
			$recent_post_content = explode( " ", strip_tags( $recent_post->post_content ), 1 );
			$first_word_in_post = reset( $recent_post_content );
			if( false != $first_word_in_post ) {
				$search_page_archive_url = get_search_link( $first_word_in_post );
			} else {
				// No first word, the post is empty (wordless after striping html tags, to be precise).
				$search_page_archive_url = get_search_link( $default_search_term );
			}

			$post_date = new DateTime( $recent_post->post_date );

		} else {
			// No recent post exists, use default values.
			$search_page_archive_url = get_search_link( $default_search_term );
			$post_date = new DateTime(); // now
		}

		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$post_types_in_search = wp_list_filter( $post_types, array( 'exclude_from_search' => 1 ), 'NOT' );
		$post_year = $post_date->format( "Y" );
		$post_month = $post_date->format( "n" );
		$post_day = $post_date->format( "j" );

		/* $loops: Definition of standard WP loops, each array element contains array of "display_name" and "archive_url"
		 * (url to display the archive in frontend). */
		return array(
			'home-blog-page'	=> array(
				"display_name"	=> __( 'Home/Blog', 'wpv-views' ),
				'archive_url' => get_post_type_archive_link( 'post' ),
				'type'			=> 'native',
				'name'			=> 'home',
				'post_type'		=> array( 'post' ),
				'extendable'	=> true,
			),
			'search-page'		=> array(
				"display_name"	=> __( 'Search results', 'wpv-views' ),
				"archive_url"	=> $search_page_archive_url,
				'type'			=> 'native',
				'name'			=> 'search',
				'post_type'		=> array_keys( $post_types_in_search ),
				'extendable'	=> true,
			),
			'author-page' => array(
				"display_name" => __( 'Author archives', 'wpv-views' ),
				"archive_url" => get_author_posts_url( get_current_user_id() ),
				'type'			=> 'native',
				'name'			=> 'author',
				'post_type'		=> array( 'post' ),
				'extendable'	=> true,
			),
			'year-page' => array(
				"display_name" => __( 'Year archives', 'wpv-views' ),
				"archive_url" => get_year_link( $post_year ),
				'type'			=> 'native',
				'name'			=> 'year',
				'post_type'		=> array( 'post' ),
				'extendable'	=> true,
			),
			'month-page' => array(
				"display_name" => __( 'Month archives', 'wpv-views' ),
				"archive_url" => get_month_link( $post_year, $post_month ),
				'type'			=> 'native',
				'name'			=> 'month',
				'post_type'		=> array( 'post' ),
				'extendable'	=> true,
			),
			'day-page' => array(
				"display_name" => __( 'Day archives', 'wpv-views' ),
				"archive_url" => get_day_link( $post_year, $post_month, $post_day ),
				'type'			=> 'native',
				'name'			=> 'day',
				'post_type'		=> array( 'post' ),
				'extendable'	=> true,
			)
		);
	}

	/**
	 * Static method to get the post type loops.
	 *
	 * @return array
	 */
	static function get_pt_loops() {
		return self::get_instance()->get_pt_loops_non_static();
	}

	/**
	 * Non-static method to get the post type loops.
	 *
	 * @return array
	 */
	public function get_pt_loops_non_static() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		/* Definition of post type archive loops. Keys are post type slugs and each array element contains array of
				 * "display_name" and "archive_url" (url to display the archive in frontend) and "loop".*/
		// We only offer loops for post types that already have an archive
		$pt_loops = array();

		foreach ( $post_types as $post_type_name => $post_type ) {
			if (
				! in_array( $post_type_name, array( 'post', 'page', 'attachment' ) )
				&& $post_type->has_archive !== false
			) {
				$pt_loops[ $post_type_name ] = array(
					'loop'			=> 'cpt_' . $post_type_name,
					'display_name'	=> $post_type->labels->name,
					'archive_url'	=> get_post_type_archive_link( $post_type_name ),
					'type'			=> 'post_type',
					'name'			=> $post_type_name,
					'hierarchical'	=> $post_type->hierarchical ? 'yes' : 'no',
				);
			}
		}

		return $pt_loops;
	}

	/**
	 * Static method to get the taxonomy loops.
	 *
	 * @return array|string[]|WP_Taxonomy[]
	 */
	static function get_taxonomy_loops() {
		return self::get_instance()->get_taxonomy_loops_non_static();
	}

	/**
	 * Non-static method to get the taxonomy loops.
	 *
	 * @return array|string[]|WP_Taxonomy[]
	 */
	public function get_taxonomy_loops_non_static() {
		// Only taxonomies with show_ui set to TRUE will be used.
		$taxonomies = get_taxonomies( array( 'show_ui' => true ), 'objects' );
		$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', array() );

		return array_filter(
			$taxonomies,
			function( $taxonomy ) use ( $exclude_tax_slugs ) {
				return ! in_array( $taxonomy->name, $exclude_tax_slugs );
			}
		);
	}

	static function render_view_loop_selection_form( $view_id = 0 ) {
		global $WPV_view_archive_loop, $WPV_settings;
		$WPV_view_archive_loop->_view_edit_options( $view_id, $WPV_settings ); // TODO check if we just need the $WPV_settings above

		$asterisk = ' <span style="color:red">*</span>';
		$asterisk_explanation = __( '<span style="color:red">*</span> A different WordPress Archive is already assigned to this item', 'wpv-views' );
		$show_asterisk_explanation = false;

		// Label and template for "View archive" link.
		$view_archive_template = '<span style="margin-left: 3px;"></span><a style="text-decoration: none;" target="_blank" href="%s"><i class="icon-external-link fa fa-external-link icon-small"></i></a>';

		$loops = self::get_wp_loops();

		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		// === Selection for Standard Archives === //
		?>
		<div class="wpv-advanced-setting">
			<h3><?php _e( 'Standard Archives', 'wpv-views' ); ?></h3>
			<ul class="wpv-mightlong-list">
				<?php
					$loop_counter = 0;
					foreach ( $loops as $loop => $loop_definition ) {
						$show_asterisk = false;
						$post_types_included = array();
						$is_checked = ( isset( $WPV_settings['view_' . $loop] ) && $WPV_settings['view_' . $loop] == $view_id );
						if ( isset( $WPV_settings['view_' . $loop] )
							&& $WPV_settings['view_' . $loop] != $view_id
							&& $WPV_settings['view_' . $loop] != 0 )
						{
							$show_asterisk = true;
							$show_asterisk_explanation = true;
						}
						?>
							<li class="wpv-mightlong-list-item-fixwidth<?php if ( 0 == $loop_counter % 2 ) { echo ' wpv-mightlong-list-item-clear'; }?>">
								<input
									type="checkbox"
									<?php checked( $is_checked ); ?>
									id="wpv-view-loop-<?php echo esc_attr( $loop ); ?>"
									name="wpv-view-loop-<?php echo esc_attr( $loop ); ?>"
									autocomplete="off"
									data-type="<?php echo esc_attr( $loop_definition['type'] ); ?>"
									data-name="<?php echo esc_attr( $loop_definition['name'] ); ?>"
								/>
								<label for="wpv-view-loop-<?php echo esc_attr( $loop ); ?>"><?php
										echo $loop_definition[ "display_name" ];
										echo $show_asterisk ? $asterisk : '';
								?></label>
								<?php
								if ( $is_checked ) {
									printf( $view_archive_template, $loop_definition[ "archive_url" ] );
									WPV_Editor_Loop_Selection::render_view_loop_post_types_info( $loop_definition, $post_types );
								}
								?>
							</li>
						<?php
						$loop_counter++;
					}
				?>
			</ul>
		</div>
		<?php

		// === Selection for Custom Post Archives === //

		$pt_loops = self::get_pt_loops();

		if ( count( $pt_loops ) > 0 ) {
			?>
			<div class="wpv-advanced-setting">
				<h3><?php _e( 'Custom Post Archives', 'wpv-views' ); ?></h3>
				<ul class="wpv-mightlong-list">
					<?php
						foreach ( $pt_loops as $loop_definition ) {
							$loop = $loop_definition[ 'loop' ];
							$show_asterisk = false;
							$is_checked = ( isset( $WPV_settings['view_' . $loop] ) && $WPV_settings['view_' . $loop] == $view_id );
							if ( isset( $WPV_settings['view_' . $loop] ) && $WPV_settings['view_' . $loop] != $view_id && $WPV_settings['view_' . $loop] != 0 ) {
								$show_asterisk = true;
								$show_asterisk_explanation = true;
							}
							?>
								<li >
									<input
										type="checkbox"
										<?php checked( $is_checked ); ?>
										id="wpv-view-loop-<?php echo esc_attr( $loop ); ?>"
										name="wpv-view-loop-<?php echo esc_attr( $loop ); ?>"
										autocomplete="off"
										data-type="<?php echo esc_attr( $loop_definition['type'] ); ?>"
										data-name="<?php echo esc_attr( $loop_definition['name'] ); ?>"
										data-hierarchical="<?php echo esc_attr( $loop_definition['hierarchical'] ); ?>"
									/>
									<label for="wpv-view-loop-<?php echo esc_attr( $loop ); ?>">
										<?php
											echo $loop_definition[ 'display_name' ];
											echo $show_asterisk ? $asterisk : '';
										?>
									</label>
									<?php
										if( $is_checked ) {
											printf( $view_archive_template, $loop_definition[ 'archive_url' ] );
										}
									?>
								</li>
							<?php
						}
					?>
				</ul>
			</div>
			<?php
		}

		// === Selection for Taxonomy Archives === //
		$taxonomies = self::get_taxonomy_loops();

		$types_cpt = get_option('wpcf-custom-types');
        if (
			! is_array( $types_cpt )
			|| empty( $types_cpt )
		) {
            $types_cpt = array();
        }

        $types_cpt_for_native = array(
			'category'	=> array( 'post' ),
			'post_tag'	=> array( 'post' )
		);

		foreach ( $types_cpt as $cpt_slug => $cpt ) {
			if (
				array_key_exists( 'taxonomies', $cpt )
				&& is_array( $cpt['taxonomies'] )
			) {
				foreach ( $cpt['taxonomies'] as $tax_slug => $value ) {
					if (
						'category' == $tax_slug
						&& $value
					) {
						$types_cpt_for_native['category'][] = $cpt_slug;
					}
					if (
						'post_tag' == $tax_slug
						&& $value
					) {
						$types_cpt_for_native['post_tag'][] = $cpt_slug;
					}
				}
			}
		}

		// TODO get_terms( $taxonomies, array( "fields" => "id", hide_empty => 1 ) )
		// and then get_term_link( $term_id, $taxonomy_slug )
		// get_terms( $taxonomy_slug, array( "fields" => "id", "hide_empty" => 1, "number" => 1 ) )

		?>
		<div class="wpv-advanced-setting">
			<h3><?php _e( 'Taxonomy Archives', 'wpv-views' ); ?></h3>
			<ul class="wpv-mightlong-list">
				<?php
					$loop_counter = 0;
					foreach ( $taxonomies as $category_slug => $category ) {
						$name = $category->name;
						$show_asterisk = false;
						$is_checked = ( isset( $WPV_settings['view_taxonomy_loop_' . $name ] ) && $WPV_settings['view_taxonomy_loop_' . $name ] == $view_id );
						if ( isset( $WPV_settings['view_taxonomy_loop_' . $name ] )
							&& $WPV_settings['view_taxonomy_loop_' . $name ] != $view_id
							&& $WPV_settings['view_taxonomy_loop_' . $name ] != 0 )
						{
							$show_asterisk = true;
							$show_asterisk_explanation = true;
						}
						?>
							<li class="wpv-mightlong-list-item-fixwidth<?php if ( 0 == $loop_counter % 2 ) { echo ' wpv-mightlong-list-item-clear'; }?>">
								<input
									type="checkbox"
									<?php checked( $is_checked ); ?>
									id="wpv-view-taxonomy-loop-<?php echo esc_attr( $name ); ?>"
									name="wpv-view-taxonomy-loop-<?php echo esc_attr( $name ); ?>"
									autocomplete="off"
									data-type="taxonomy"
									data-name="<?php echo esc_attr( $name ); ?>"
								/>
								<label for="wpv-view-taxonomy-loop-<?php echo esc_attr( $name ); ?>">
									<?php
										echo $category->labels->name;
										echo $show_asterisk ? $asterisk : '';
									?>
								</label>
								<?php
									if( $is_checked ) {
										// Get ID of a term that has some posts, if such term exists.
										$terms_with_posts = get_terms( $category_slug, array( "hide_empty" => 1, "number" => 1 ) );
										if (
											$terms_with_posts instanceof WP_Error
											|| ! is_array( $terms_with_posts )
											|| empty( $terms_with_posts )
										) {
											printf(
												'<span style="margin-left: 3px;"></span><span style="color: grey"><i class="icon-external-link fa fa-external-link icon-small" title="%s"></i></span>',
												sprintf(
														__( 'The %s page cannot be viewed because no post has any %s.', 'wpv-views' ),
														$category->labels->name,
														$category->labels->singular_name ) );
										} else {
											$terms_with_posts = array_values( $terms_with_posts );
											$term = array_shift( $terms_with_posts );
											printf( $view_archive_template, get_term_link( $term, $category_slug ) );
										}
										$loop_definition = array(
											'type'			=> 'taxonomy',
											'name'			=> $name,
											'post_type'		=> in_array( $name, array( 'category', 'post_tag' ) ) ? $types_cpt_for_native[ $name ] : $category->object_type,
											'display_name'	=> $category->labels->name,
											'extendable'	=> in_array( $name, array( 'category', 'post_tag' ) ),
										);
										WPV_Editor_Loop_Selection::render_view_loop_post_types_info( $loop_definition, $post_types );
									}
								?>
							</li>
						<?php
						$loop_counter++;
					}
				?>
			</ul>
		</div>
		<?php
		if ( $show_asterisk_explanation ) {
			?>
			<div class="wpv-advanced-setting">
				<span class="wpv-options-box-info">
					<?php echo $asterisk_explanation; ?>
				</span>
			</div>
			<?php
		}
	}

	static function render_view_loop_post_types_info( $loop_definition, $post_types ) {
		?>
		<div class="wpv-archive-loop-post-types-info">
		<?php
		$post_types_included = array();

		$stored_settings = WPV_Settings::get_instance();
		$wpv_post_types_for_archive_loop = $stored_settings->wpv_post_types_for_archive_loop;
		$wpv_post_types_for_archive_loop[ $loop_definition['type'] ] = isset( $wpv_post_types_for_archive_loop[ $loop_definition['type'] ] ) ? $wpv_post_types_for_archive_loop[ $loop_definition['type'] ] : array();
		if (
			isset( $wpv_post_types_for_archive_loop[ $loop_definition['type'] ][ $loop_definition['name'] ] )
			&& ! empty( $wpv_post_types_for_archive_loop[ $loop_definition['type'] ][ $loop_definition['name'] ] )
		) {
			foreach ( $wpv_post_types_for_archive_loop[ $loop_definition['type'] ][ $loop_definition['name'] ] as $included_post_type ) {
				if ( isset( $post_types[ $included_post_type ] ) ) {
					$post_types_included[] = $post_types[ $included_post_type ]->labels->name;
				}
			}
			$selected_post_types = $wpv_post_types_for_archive_loop[ $loop_definition['type'] ][ $loop_definition['name'] ];
		} else {
			foreach ( $loop_definition['post_type'] as $included_post_type ) {
				if ( isset( $post_types[ $included_post_type ] ) ) {
					$post_types_included[] = $post_types[ $included_post_type ]->labels->name;
				}
			}
			$selected_post_types = $loop_definition['post_type'];
		}

		$archive_will_include_template		= __( 'This archive will include %s', 'wpv-views' );
		$archive_will_include_only_template = __( 'This archive will include %s only', 'wpv-views' );
		$archive_will_include_one_more_template = __( 'This archive will include %1$s and another post type', 'wpv-views' );
		$archive_will_include_more_template = __( 'This archive will include %1$s and %2$s more post types', 'wpv-views' );
		$count_post_types_included = count( $post_types_included );

		switch ( $count_post_types_included ) {
			case 1:
				printf( $archive_will_include_only_template, implode( ', ', $post_types_included ) );
				break;
			case 2:
			case 3:
				printf( $archive_will_include_template, implode( ', ', $post_types_included ) );
				break;
			case 4:
				printf( $archive_will_include_one_more_template, implode( ', ', array_slice( $post_types_included, 0, 3 ) ) );
				break;
			default:
				if ( $count_post_types_included < 1 ) {
					echo __( 'This archive will include no post types', 'wpv-views' );
				} else {
					printf( $archive_will_include_more_template, implode( ', ', array_slice( $post_types_included, 0, 3 ) ), ( $count_post_types_included - 3 ) );
				}
				break;
		}
		if ( $loop_definition['extendable'] ) {
		?>
		<button class="button button-secomdary button-small js-wpv-apply-post-types-to-archive-loop-tracker js-wpv-apply-post-types-to-archive-loop-dialog"
			data-display="<?php echo esc_attr( $loop_definition['display_name'] ); ?>"
			data-type="<?php echo esc_attr( $loop_definition['type'] ); ?>"
			data-name="<?php echo esc_attr( $loop_definition['name'] ); ?>"
			data-selected="<?php echo esc_js( json_encode( $selected_post_types ) ); ?>"
			data-default="<?php echo esc_js( json_encode( $loop_definition['post_type'] ) ); ?>"
		>
			<?php echo __( 'Edit', 'wpv-views' ); ?>
		</button>
		<?php
		} else {
		?>
		<span style="display:none;" class="js-wpv-apply-post-types-to-archive-loop-tracker"
			data-type="<?php echo esc_attr( $loop_definition['type'] ); ?>"
			data-name="<?php echo esc_attr( $loop_definition['name'] ); ?>"
			data-selected="<?php echo esc_js( json_encode( $selected_post_types ) ); ?>"
		></span>
		<?php
		}
		?>
		</div>
		<?php
	}

}

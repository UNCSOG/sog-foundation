<?php

/**
* wpv_admin_archive_listing_page
*
* Creates the main structure of the WPA admin listing page: wrapper and header
*
*/
function wpv_admin_archive_listing_page() {

	global $WPV_view_archive_loop;
	?>
	<div class="wrap toolset-views">

		<h1><!-- classname wpv-page-title removed -->
			<?php _e( 'WordPress Archives', 'wpv-views' ); ?>

				<a href="#" class="add-new-h2 page-title-action js-wpv-views-archive-add-new wpv-views-archive-add-new">
					<?php _e('Add New','wpv-views') ?>
				</a>
				<?php

				// 'trash' or 'publish'
				$current_post_status = wpv_getget( 'status', 'publish', array( 'trash', 'publish' ) );
				$search_term = esc_attr( urldecode( wp_unslash( wpv_getget( 's', '' ) ) ) );
				$arrange_by_usage = ( sanitize_text_field( wpv_getget( 'arrangeby' ) ) == 'usage' );

				wp_nonce_field( 'work_views_listing', 'work_views_listing' );
				wp_nonce_field( 'wpv_remove_view_permanent_nonce', 'wpv_remove_view_permanent_nonce' );

				if ( !empty( $search_term ) ) {
					$search_message = __('Search results for "%s"','wpv-views');
					if ( 'trash' == $current_post_status ) {
						$search_message = __('Search results for "%s" in trashed WordPress Archives', 'wpv-views');
					}
					?>
						<span class="subtitle">
							<?php echo sprintf( $search_message, $search_term ); ?>
						</span>
					<?php
				}
			?>
		</h1>

		<!-- wpv-views-listing-archive-page can be removed -->
		<div class="wpv-views-listing-page wpv-views-listing-archive-page" data-none-message="<?php _e("This WordPress Archive isn't being used for any loops.",'wpv-views') ?>" >
			<?php
				// Messages: trashed, untrashed, deleted

				// We can reuse the function from Views listing (there's a note saying we're doing that).
				add_filter( 'wpv_maybe_show_listing_message_undo', 'wpv_admin_view_listing_message_undo', 10, 3 );

				wpv_maybe_show_listing_message(
						'trashed', __( 'WordPress Archive moved to the Trash.', 'wpv-views' ), __( '%d WordPress Archives moved to the Trash.', 'wpv-views' ), true );
				wpv_maybe_show_listing_message(
						'untrashed', __( 'WordPress Archive restored from the Trash.', 'wpv-views' ), __( '%d WordPress Archives restored from the Trash.', 'wpv-views' ) );
				wpv_maybe_show_listing_message(
						'deleted', __( 'WordPress Archive permanently deleted.', 'wpv-views' ), __( '%d WordPress Archives permanently deleted.', 'wpv-views' ) );

				// "Arrange by" tabs
				?>

				<div class="wpv-admin-tabs">
					<ul class="wpv-admin-tabs-links">
						<li>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'view-archives' ), admin_url( 'admin.php' ) ) ); ?>"
									<?php wpv_current_class( ! $arrange_by_usage ); ?> >
								<?php _e( 'Name', 'wpv-views' ); ?>
							</a>
						</li>
						<li>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'view-archives', 'arrangeby' => 'usage' ), admin_url( 'admin.php' ) ) ); ?>"
									<?php wpv_current_class( $arrange_by_usage ); ?> >
								<?php _e( 'Usage for archive loops', 'wpv-views' ); ?>
							</a>
						</li>
					</ul>
				</div>

			<?php
				if ( $arrange_by_usage ) {

					// Show table arranged by Usage
					wp_nonce_field( 'wpv_wp_archive_arrange_usage', 'wpv_wp_archive_arrange_usage' );

					if ( !$WPV_view_archive_loop->check_archive_loops_exists() ) {
						?>
						<p id="js-wpv-no-archive" class="toolset-alert toolset-alert-success update below-h2">
							<?php _e('All loops have a WordPress Archive assigned','wpv-views'); ?>
						</p>
						<?php
					}

					wpv_admin_wordpress_archives_listing_table_by_usage();


				} else {

					// IDs of possible results and counts per post status.
					$views_pre_query_data = wpv_prepare_view_listing_query( array( 'archive', 'layouts-loop' ), $current_post_status );

					if ( !$WPV_view_archive_loop->check_archive_loops_exists() ) {
						?>
						<p id="js-wpv-no-archive" class="toolset-alert toolset-alert-success">
							<?php _e('All loops have a WordPress Archive assigned','wpv-views'); ?>
						</p>
						<?php
					}

					wpv_admin_wordpress_archives_listing_table_by_name( $views_pre_query_data, $current_post_status, $search_term );
				}
			?>
		</div> <!-- .wpv-settings-container" -->
	</div>
	<?php
}


/**
 * wpv_admin_wordpress_archives_listing_table_by_name
 *
 * Prepares the container of the WPA admin listing page arranged by name.
 *
 * @param array $views_pre_query_data Array with IDs of possible results and counts per post status.
 *     See wpv_prepare_view_listing_query() for details.
 * @param string $current_post_status Status of posts to display. Can be 'publish' or 'trash'.
 * @param string $search_term Sanitized search term or empty string if no search is being performed.
 *
 * @since unknown
 * @since 2.4 Added the $search_term parameter that contains the sanitized search term when a search is performed.
 */
function wpv_admin_wordpress_archives_listing_table_by_name( $views_pre_query_data, $current_post_status, $search_term ) {
	?>
		<div id="js-wpv-archive-tables-containter" class="wpv-archive-tables-containter">
			<?php wpv_admin_archive_listing_name( $views_pre_query_data, $current_post_status, $search_term ); ?>
		</div>
	<?php
	// Render dialog templates
	wpv_render_wpa_listing_dialog_templates_arrangeby_name();
}


function wpv_admin_wordpress_archives_listing_table_by_usage() {
	?>
	<div id="js-wpv-archive-tables-containter" class="wpv-archive-tables-containter">
		<?php wpv_admin_archive_listing_usage(); ?>
	</div>
	<?php
	// Render dialog templates
	wpv_render_wpa_listing_dialog_templates_arrangeby_usage();
}


/**
 * wpv_admin_archive_listing_name
 *
 * Displays the content of the WordPress Archives admin listing page: status, table and pagination.
 *
 * @param array $views_pre_query_data Array with IDs of possible results and counts per post status.
 *     See wpv_prepare_view_listing_query() for details.
 * @param string $current_post_status Status of posts to display. Can be 'publish' or 'trash'.
 * @param string $search_term Sanitized search term or empty string if no search is being performed.
 *
 * @since unknown
 * @since 2.4 Added the $search_term parameter that contains the sanitized search term when a search is performed.
 */
function wpv_admin_archive_listing_name( $views_pre_query_data, $current_post_status, $search_term ) {

	global $WP_Views, $WPV_settings, $WPV_view_archive_loop;

	// array of URL modifiers
	$mod_url = array(
		'orderby' => '',
		'order' => '',
		's' => '',
		'items_per_page' => '',
		'paged' => '',
		'status' => $current_post_status
	);

	// array of WP_Query parameters
	$wpv_args = array(
		'post_type' => 'view',
		'post__in' => $views_pre_query_data[ 'post__in' ],
		'posts_per_page' => WPV_ITEMS_PER_PAGE,
		'order' => 'ASC',
		'orderby' => 'title',
		'post_status' => $current_post_status
	);

	$is_search = ! empty( $search_term );

	// perform the search in WPA titles and decriptions and add post__in argument to $wpv_args.
	if ( $is_search ) {
		$wpv_args = wpv_modify_wpquery_for_search( $search_term, $wpv_args );
		$mod_url['s'] = urlencode( $search_term );
	}

	$items_per_page = (int) wpv_getget( 'items_per_page', 0 ); // 0 means "not set"
	if (
		$items_per_page > 0
		|| $items_per_page == -1
	) {
		$wpv_args['posts_per_page'] = $items_per_page;
		$mod_url['items_per_page'] = $items_per_page;
	}

	$orderby = sanitize_text_field( wpv_getget( 'orderby' ) );
	$order = sanitize_text_field( wpv_getget( 'order' ) );
	if ( '' != $orderby ) {
		$wpv_args['orderby'] = $orderby;
		$mod_url['orderby'] = $orderby;
		if ( '' != $order ) {
			$wpv_args['order'] = $order;
			$mod_url['order'] = $order;
		}
	}

	$paged = (int) wpv_getget( 'paged', 0 );
	if ( $paged > 0 ) {
		$wpv_args['paged'] = $paged;
		$mod_url['paged'] = $paged;
	}

	$wpv_query = new WP_Query( $wpv_args );

	// The number of WPAs being displayed.
	$wpv_count_posts = $wpv_query->post_count;

	// Total number of WPAs matching query parameters.
	$wpv_found_posts = $wpv_query->found_posts;

	?>

	<!-- links to lists WPA in different statuses -->
	<ul class="subsubsub">
		<?php
		// Show link to published WPA templates.
		// "publish" status
		$is_plain_publish_current_status = ( 'publish' == $current_post_status );

		printf(
			'<li><a href="%s" %s >%s</a> (%s)%s</li>',
			esc_url( add_query_arg(
				array( 'page' => 'view-archives', 'status' => 'publish' ),
				admin_url( 'admin.php' ) ) ),
			$is_plain_publish_current_status ? 'class="current"' : '',
			__( 'Published', 'wpv-views' ),
			$views_pre_query_data['published_count'],
			( $views_pre_query_data['trashed_count'] > 0 ) ? ' | ' : '' );


		// Show link to trashed WPA templates.
		// "trash" status
		if( $views_pre_query_data['trashed_count'] > 0 ) {
			$is_plain_trash_current_status = ( 'trash' == $current_post_status );

			printf(
				'<li><a href="%s" %s >%s</a> (%s)</li>',
				esc_url( add_query_arg(
					array( 'page' => 'view-archives', 'status' => 'trash' ),
					admin_url( 'admin.php' ) ) ),
				$is_plain_trash_current_status ? 'class="current"' : '',
				__( 'Trash', 'wpv-views' ),
				$views_pre_query_data['trashed_count'] );
		}
		?>
	</ul>

	<?php

	// A nonce for WPA action - used for individual as well as for bulk actions
	$wpa_action_nonce = wp_create_nonce( 'wpv_view_listing_actions_nonce' );

	// If there is one or more WPAs in this query or if there is a search happening, show search box
	if ( $wpv_found_posts > 0 || ( isset( $_GET["s"] ) && $_GET["s"] != '' ) ) {
		// Show search box
		?>
		<div class="alignright">
			<form id="posts-filter" action="" method="get">
				<p class="search-box">
					<label class="screen-reader-text"
						   for="post-search-input"><?php _e( 'Search WordPress Archives', 'wpv-views' ); ?>:</label>
					<input type="search" id="post-search-input" name="s" value="<?php echo $search_term; ?>"/>
					<input type="submit" name="" id="search-submit" class="button"
						   value="<?php echo htmlentities( __( 'Search WordPress Archives', 'wpv-views' ), ENT_QUOTES ); ?>"/>
					<input type="hidden" name="paged" value="1"/>
				</p>
			</form>
		</div>
		<?php
	}

	// === Render "tablenav" section (Bulk actions and Search box) ===
	echo '<div class="tablenav top">';

	// If this page has one or more WPAs, show Bulk actions controls
	if ( $wpv_count_posts > 0 ) {

		// Prepare ender bulk actions dropdown.
		if ( 'publish' == $current_post_status ) {
			$bulk_actions = array( 'trash' => __( 'Move to Trash', 'wpv-views' ) );
		} else {
			$bulk_actions = array(
				'restore-from-trash' => __( 'Restore', 'wpv-views' ),
				'delete'             => __( 'Delete Permanently', 'wpv-views' )
			);
		}

		$bulk_actions_args  = array( 'data-viewactionnonce' => $wpa_action_nonce );
		$bulk_actions_class = 'js-wpv-wpa-listing-bulk-action';

		echo wpv_admin_table_bulk_actions( $bulk_actions, $bulk_actions_class, $bulk_actions_args, 'top' );
	}

	if ( isset( $_GET["status"] ) && $_GET["status"] == 'trash' ) {
		$empty_trash_args = array( 'data-viewactionnonce' => $wpa_action_nonce );
		$empty_trash_class = 'js-wpv-views-empty-trash';

		echo wpv_admin_empty_trash( $empty_trash_class, $empty_trash_args, 'top' );
	}

	echo '</div>'; // End of tablenav section

	?>
	<table id="wpv_view_list" class="js-wpv-views-listing wpv-views-listing wpv-views-listing-by-name widefat">
		<thead>
			<?php
				/* To avoid code duplication, table header is stored in output buffer and echoed twice - within
				 * thead and tfoot tags. */
				ob_start();
			?>
			<tr>
				<td class="wpv-admin-listing-col-bulkactions check-column">
					<input type="checkbox" />
				</td>

				<?php
					$column_active = '';
					$column_sort_to = 'ASC';
					$column_sort_now = 'ASC';
					if ( $wpv_args['orderby'] === 'ID' ) {
						$column_active = ' views-list-sort-active';
						$column_sort_to = ( $wpv_args['order'] === 'ASC' ) ? 'DESC' : 'ASC';
						$column_sort_now = $wpv_args['order'];
					}
				?>
				<th class="wpv-admin-listing-col-id">
					<?php
					// "sort by ID" link
					printf(
						'<a href="%s" class="%s", data-orderby="ID">%s <i class="%s"></i></a>',
						wpv_maybe_add_query_arg(
							array(
								'page' => 'view-archives',
								'orderby' => 'ID',
								'order' => $column_sort_to,
								's' => $mod_url['s'],
								'items_per_page' => $mod_url['items_per_page'],
								'paged' => $mod_url['paged'],
								'status' => $mod_url['status'] ),
							admin_url( 'admin.php' ) ),
						'js-views-list-sort views-list-sort' . $column_active,
						__( 'ID', 'wpv-views' ),
						( 'DESC' === $column_sort_now ) ? 'icon-sort-by-attributes-alt fa fa-sort-amount-desc' : 'icon-sort-by-attributes fa fa-sort-amount-asc' );
					?>
				</th>
				<?php
					$column_active = '';
					$column_sort_to = 'ASC';
					$column_sort_now = 'ASC';
					if ( $wpv_args['orderby'] === 'title' ) {
						$column_active = ' views-list-sort-active';
						$column_sort_to = ( $wpv_args['order'] === 'ASC' ) ? 'DESC' : 'ASC';
						$column_sort_now = $wpv_args['order'];
					}
				?>
				<th class="wpv-admin-listing-col-title">
					<?php
						// "sort by title" link
						printf(
								'<a href="%s" class="%s", data-orderby="title">%s <i class="%s"></i></a>',
								wpv_maybe_add_query_arg(
										array(
												'page' => 'view-archives',
												'orderby' => 'title',
												'order' => $column_sort_to,
												's' => $mod_url['s'],
												'items_per_page' => $mod_url['items_per_page'],
												'paged' => $mod_url['paged'],
												'status' => $mod_url['status'] ),
										admin_url( 'admin.php' ) ),
								'js-views-list-sort views-list-sort' . $column_active,
								__( 'Title', 'wpv-views' ),
								( 'DESC' === $column_sort_now ) ? 'icon-sort-by-alphabet-alt fa fa-sort-alpha-desc' : 'icon-sort-by-alphabet fa fa-sort-alpha-asc' );
					?>
				</th>
				<th class="wpv-admin-listing-col-usage"><?php _e('Archive usage','wpv-views') ?></th>

				<?php
					$column_active = '';
					$column_sort_to = 'DESC';
					$column_sort_now = 'DESC';
					if ( $wpv_args['orderby'] === 'modified' ) {
						$column_active = ' views-list-sort-active';
						$column_sort_to = ( $wpv_args['order'] === 'ASC' ) ? 'DESC' : 'ASC';
						$column_sort_now = $wpv_args['order'];
					}
				?>
				<th class="wpv-admin-listing-col-modified">
					<?php
						// "sort by modified" link
						printf(
								'<a href="%s" class="%s" data-orderby="modified">%s <i class="%s"></i></a>',
								wpv_maybe_add_query_arg(
										array(
												'page' => 'view-archives',
												'orderby' => 'modified',
												'order' => $column_sort_to,
												's' => $mod_url['s'],
												'items_per_page' => $mod_url['items_per_page'],
												'paged' => $mod_url['paged'],
												'status' => $mod_url['status'] ),
										admin_url( 'admin.php' ) ),
										'js-views-list-sort views-list-sort ' . $column_active,
										/* translators: Label of the link in the admin table column head to sort WordPress Archives by their last modified date */
										__( 'Modified', 'wpv-views' ),
										( 'DESC' === $column_sort_now ) ? 'icon-sort-by-attributes-alt fa fa-sort-amount-desc' : 'icon-sort-by-attributes fa fa-sort-amount-asc' );
					?>
				</th>
			</tr>
			<?php
				// Get table header from output buffer and stop buffering
				$table_header = ob_get_contents();
				ob_end_clean();

				echo $table_header;
			?>
		</thead>
		<tfoot>
			<?php
				echo $table_header;
			?>
		</tfoot>
		<tbody class="js-wpv-views-listing-body">
		<?php
			if ( $wpv_count_posts > 0 ) {
				$loops             = $WPV_view_archive_loop->_get_post_type_loops();
				$builtin_loops     = array(
					'home-blog-page' => __( 'Home/Blog', 'wpv-views' ),
					'search-page'    => __( 'Search results', 'wpv-views' ),
					'author-page'    => __( 'Author archives', 'wpv-views' ),
					'year-page'      => __( 'Year archives', 'wpv-views' ),
					'month-page'     => __( 'Month archives', 'wpv-views' ),
					'day-page'       => __( 'Day archives', 'wpv-views' )
				);
				$taxonomies        = get_taxonomies( '', 'objects' );
				$exclude_tax_slugs = array();
				$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
				$alternate         = '';

				while ( $wpv_query->have_posts() ) :
					$wpv_query->the_post();
					$post_id          = get_the_id();
					$post             = get_post( $post_id, OBJECT, 'edit' );
					$view_settings    = $WP_Views->get_view_settings( $post_id );
					$view_description = get_post_meta( $post_id, '_wpv_description', true );
					$alternate        = ' alternate' == $alternate ? '' : ' alternate';
				?>
					<tr id="wpv_view_list_row_<?php echo $post->ID; ?>"
						class="js-wpv-view-list-row <?php echo $alternate; ?>">
						<th class="wpv-admin-listing-col-bulkactions check-column">
							<?php
							printf( '<input type="checkbox" value="%s" name="wpa[]" />', $post->ID );
							?>
						</th>
						<td class="wpv-admin-listing-col-id">
							<?php echo $post_id; ?>
						</td>
						<td class="wpv-admin-listing-col-title">
						<span class="row-title">
							<?php
							if ( 'trash' == $current_post_status ) {
								echo esc_html( trim( $post->post_title ) );
							} else {
								// Title + edit link
								printf(
									'<a href="%s">%s</a>',
									esc_url( \WPV_WordPress_Archive_Frontend::archive_edit_link( $post->ID ) ),
									esc_html( trim( $post->post_title ) )
								);

								$is_gutenberg_wpa = \WPV_WordPress_Archive::get_instance( $post_id )->get_is_gutenberg_wpa();
								if ( $is_gutenberg_wpa ) {
									echo ' &mdash; ';
									echo '<span class="post-builder">' . esc_html( __( 'Block Editor', 'wpv-views' ) ) . '</span>';
								}
							}
							?>
						</span>
							<?php
							// Show the description if there is any.
							if ( isset( $view_description ) && '' != $view_description ) {
								?>
								<p class="desc">
									<?php echo nl2br( $view_description ); ?>
								</p>
								<?php
							}

							/* Generate and show row actions.
							 * Note that we want to add also 'simple' action names to the action list because
							 * they get echoed as a class of the span tag and get styled by WordPress core css
							 * accordingly (e.g. trash in different colour than the rest) */
							$row_actions = array();

							if ( 'publish' == $current_post_status ) {
								$row_actions['edit'] = sprintf(
									'<a href="%s">%s</a>',
									esc_url( \WPV_WordPress_Archive_Frontend::archive_edit_link( $post->ID ) ),
									__( 'Edit', 'wpv-views' ) );
								/* Note that hash in <a href="#"> is present so the link behaves like a link.
								 * <a href=""> causes problems with colorbox and with mere <a> the mouse cursor
								 * doesn't change when hovering over the link. */
								$row_actions['trash js-list-views-action-trash'] = sprintf(
									'<a href="#">%s</a>',
									__( 'Trash', 'wpv-views' ) );
							} else if ( 'trash' == $current_post_status ) {
								$row_actions['restore-from-trash js-list-views-action-restore-from-trash'] = sprintf(
									'<a href="#">%s</a>',
									__( 'Restore', 'wpv-views' ) );
								$row_actions['delete js-list-views-action-delete']                         = sprintf( '<a href="#">%s</a>', __( 'Delete Permanently', 'wpv-views' ) );
							}

							echo wpv_admin_table_row_actions( $row_actions, array(
								"data-view-id"         => $post->ID,
								"data-viewactionnonce" => $wpa_action_nonce
							) );
							?>
						</td>
						<td class="wpv-admin-listing-col-usage">
							<?php
							if ( $view_settings['view-query-mode'] == 'archive' ) {
								$selected = array();
								foreach ( $loops as $loop => $loop_name ) {
									if ( isset( $WPV_settings[ 'view_' . $loop ] ) && $WPV_settings[ 'view_' . $loop ] == $post->ID ) {
										$not_built_in = '';
										if ( ! isset( $builtin_loops[ $loop ] ) ) {
											$not_built_in = __( ' (post type archive)', 'wpv-views' );
										}
										$selected[] = '<li>' . $loop_name . $not_built_in . '</li>';
									}
								}

								foreach ( $taxonomies as $category_slug => $category ) {
									if ( in_array( $category_slug, $exclude_tax_slugs ) ) {
										continue;
									}
									// Only show taxonomies with show_ui set to TRUE
									if ( ! $category->show_ui ) {
										continue;
									}
									$name = $category->name;
									if ( isset ( $WPV_settings[ 'view_taxonomy_loop_' . $name ] )
										 && $WPV_settings[ 'view_taxonomy_loop_' . $name ] == $post->ID
									) {
										$selected[] = '<li>' . $category->labels->name . __( ' (taxonomy archive)', 'wpv-views' ) . '</li>';
									} else {
										$selected_terms = $WPV_view_archive_loop->get_archive_taxonomy_terms( $post, $name );

										if ( count( $selected_terms ) > 0 ) {
											/* translators: (1) %s: a coma separated list of the term slugs that are applied in this WPA. */
											$term_label = sprintf( __( ' (taxonomy archive for terms: %s)', 'wpv-views' ), implode( ', ', $selected_terms ) );
											$selected[] = sprintf( '<li>%s%s</li>', $category->labels->name, $term_label );
										}
									}
								}

								if ( ! empty( $selected ) ) {
									?>
									<ul class="wpv-taglike-list js-list-views-loops">
										<?php
										echo implode( $selected );
										?>
									</ul>
									<?php
								} else {
									_e( 'This WordPress Archive isn\'t being used for any loops.', 'wpv-views' );
								}
							} else if ( $view_settings['view-query-mode'] == 'layouts-loop' ) {
								_e( 'This WordPress Archive is part of a Layout, so it will display the archive(s) to which the Layout is assigned.', 'wpv-views' );
							}
							?>
						</td>
						<td class="wpv-admin-listing-col-modified">
							<?php
							$display_date = get_the_modified_time( get_option( 'date_format' ), $post->ID  );
							$abbr_date = get_the_modified_time( __( 'Y/m/d g:i:s a' ), $post->ID  );
							echo '<abbr title="' . $abbr_date . '">' . $display_date . '</abbr>';
							?>
						</td>
					</tr>
					<?php
				endwhile;
			} else {
				// No WordPress Archives matches the criteria
				?>
				<div class="wpv-views-listing views-empty-list">
					<?php
					if ( 'trash' == $current_post_status && $is_search ) {
						?>
						<tr class="no-items">
							<td class="js-wpv-view-list-row alternate" colspan="6"><?php _e( 'No WordPress Archives found in Trash that matched your criteria.', 'wpv-views' ); ?></td>
						</tr>
						<?php
					} else if ( 'trash' == $current_post_status ) {
						?>
						<tr class="no-items">
							<td class="js-wpv-view-list-row alternate" colspan="6"><?php _e( 'No WordPress Archives found in Trash.', 'wpv-views' ); ?></td>
						</tr>
						<?php
					} else if ( $is_search ) {
						?>
						<tr class="no-items">
							<td class="js-wpv-view-list-row alternate" colspan="6"><?php _e( 'No WordPress Archives found that matched your criteria.', 'wpv-views' ); ?></td>
						</tr>
						<?php
					} else {
						?>
						<tr class="no-items">
							<td class="js-wpv-view-list-row alternate" colspan="6"><?php _e( 'No WordPress Archives found.', 'wpv-views' ); ?></td>
						</tr>
						<?php
					}
					?>
				</div>
				<?php
			}
		?>
		</tbody>
	</table>
	<div class="tablenav bottom">
		<?php
			if ( $wpv_count_posts > 0 ) {
				echo wpv_admin_table_bulk_actions( $bulk_actions, $bulk_actions_class, $bulk_actions_args, 'bottom' );
			}

			if ( isset( $_GET["status"] ) && $_GET["status"] == 'trash' ) {
				$empty_trash_args = array( 'data-viewactionnonce' => $wpa_action_nonce );
				$empty_trash_class = 'js-wpv-views-empty-trash';

				echo wpv_admin_empty_trash( $empty_trash_class, $empty_trash_args, 'bottom' );
			}
		?>
	</div>

	<?php

	wpv_admin_listing_pagination( $wpv_found_posts, 'view-archives', $wpv_args['posts_per_page'], $mod_url );
}


function wpv_admin_archive_listing_usage() {

	?>
	<table id="wpv_view_list_usage" class="js-wpv-views-listing wpv-views-listing wpv-views-listing-by-usage widefat">
		<thead>
			<tr>
				<th class="wpv-admin-listing-col-usage js-wpv-col-one"><?php _e('Archive loop','wpv-views') ?></th>
				<th class="wpv-admin-listing-col-title js-wpv-col-two"><?php _e('WordPress Archive used','wpv-views') ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th class="wpv-admin-listing-col-usage js-wpv-col-one"><?php _e('Used for','wpv-views') ?></th>
				<th class="wpv-admin-listing-col-title js-wpv-col-two"><?php _e('Title','wpv-views') ?></th>
			</tr>
		</tfoot>

		<tbody class="js-wpv-views-listing-body">
			<?php
				global $WPV_settings;

				$alternate = '';

				$loops = array(
						'home-blog-page' => __('Home/Blog', 'wpv-views'),
						'search-page' => __('Search results', 'wpv-views'),
						'author-page' => __('Author archives', 'wpv-views'),
						'year-page' => __('Year archives', 'wpv-views'),
						'month-page' => __('Month archives', 'wpv-views'),
						'day-page' => __('Day archives', 'wpv-views') );

				foreach ( $loops as $slug => $name ) {
					$alternate = ' alternate' == $alternate ? '' : ' alternate';
					$post = null;
					if ( isset( $WPV_settings['view_' . $slug] ) ) {
						$post = get_post( $WPV_settings['view_' . $slug], OBJECT, 'edit' );
					}

					?>
					<tr class="js-wpv-view-list-row<?php echo $alternate; ?>">
						<td class="wpv-admin-listing-col-usage">
							<span class="row-title"><?php echo $name ?></span>
							<?php
								echo wpv_admin_table_row_actions(
										array( ),
										array( "data-view-id" => 'view_' . $slug ) );
							?>
						</td>
						<?php
							if ( is_null( $post ) ) {
								?>
								<td colspan="2">
									<a class="button button-small js-wpv-create-wpa-for-archive-loop" data-forwhomtitle="<?php echo esc_attr( $name ); ?>" data-forwhomloop="<?php echo esc_attr( 'wpv-view-loop-' . $slug ); ?>" href="#">
										<i class="icon-plus fa fa-plus"></i>
										<?php _e('Create a WordPress Archive for this loop');?>
									</a>
								</td>
								<?php
							} else {
								?>
								<td class="wpv-admin-listing-col-title">
									<?php
										printf(
											'<a href="%s">%s</a>',
											esc_url( \WPV_WordPress_Archive_Frontend::archive_edit_link( $post->ID ) ),
											esc_html( $post->post_title )
										);
									?>
								</td>
								<?php
							}
						?>
					</tr>
					<?php
				}

				$pt_loops = array();
				// Only offer loops for post types that already have an archive
				$post_types = get_post_types( array( 'public' => true, 'has_archive' => true), 'objects' );
				foreach ( $post_types as $post_type ) {
					if ( !in_array( $post_type->name, array( 'post', 'page', 'attachment' ) ) ) {
						$type = 'cpt_' . $post_type->name;
						$name = $post_type->labels->name;
						$pt_loops[ $type ] = $name;
					}
				}

				if ( count( $pt_loops ) > 0 ) {
					foreach ( $pt_loops as $slug => $name ) {
						$alternate = ' alternate' == $alternate ? '' : ' alternate';
						$post = null;
						if ( isset( $WPV_settings['view_' . $slug] ) ) {
							$post = get_post( $WPV_settings['view_' . $slug], OBJECT, 'edit' );
						}
						?>
						<tr class="js-wpv-view-list-row<?php echo $alternate; ?>">
							<td class="wpv-admin-listing-col-usage">
								<span class="row-title"><?php echo $name . __(' (post type archive)', 'wpv-views'); ?></span>
								<?php
									echo wpv_admin_table_row_actions(
											array( ),
											array( "data-view-id" => 'view_' . $slug ) );
								?>
							</td>
							<?php
								if ( is_null( $post ) ) {
									?>
									<td colspan="2">
										<a class="button button-small js-wpv-create-wpa-for-archive-loop" data-forwhomtitle="<?php echo esc_attr( $name ); ?>" data-forwhomloop="<?php echo esc_attr( 'wpv-view-loop-' . $slug ); ?>" href="#"><i class="icon-plus fa fa-plus"></i><?php _e('Create a WordPress Archive for this loop');?></a>
									</td>
									<?php
								} else {
									?>
									<td class="wpv-admin-listing-col-title">
										<?php
											printf(
												'<a href="%s">%s</a>',
												esc_url( \WPV_WordPress_Archive_Frontend::archive_edit_link( $post->ID ) ),
												esc_html( $post->post_title )
											);
										?>
									</td>
									<?php
								}
							?>
						</tr>
						<?php
					}
				}

				$taxonomies = get_taxonomies( '', 'objects' );
				$exclude_tax_slugs = array();
				$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
				foreach ( $taxonomies as $category_slug => $category ) {
					if ( in_array( $category_slug, $exclude_tax_slugs ) ) {
						continue;
					}
					// Only show taxonomies with show_ui set to TRUE
					if ( !$category->show_ui ) {
						continue;
					}
					$alternate = ' alternate' == $alternate ? '' : ' alternate';
					$name = $category->name;
					$label = $category->labels->singular_name;
					$post = null;
					if ( isset( $WPV_settings['view_taxonomy_loop_'.$name] ) ) {
						$post = get_post( $WPV_settings['view_taxonomy_loop_' . $name], OBJECT, 'edit' );
					}
					?>
					<tr class="js-wpv-view-list-row<?php echo $alternate; ?>">
						<td class="wpv-admin-listing-col-usage">
							<span class="row-title"><?php echo $label . __(' (taxonomy archive)', 'wpv-views'); ?></span>
							<?php
								echo wpv_admin_table_row_actions(
										array( ),
										array( "data-view-id" => 'view_taxonomy_loop_' . $name ) );
							?>
						</td>
						<?php
							if ( is_null( $post ) ) {
								?>
								<td colspan="2">
									<a class="button button-small js-wpv-create-wpa-for-archive-loop" data-forwhomtitle="<?php echo esc_attr( $label ); ?>" data-forwhomloop="<?php echo esc_attr( 'wpv-view-taxonomy-loop-' . $name ); ?>" href="#"><i class="icon-plus fa fa-plus"></i><?php _e('Create a WordPress Archive for this loop');?></a>
								</td>
								<?php
							} else {
								?>
								<td class="wpv-admin-listing-col-title">
									<?php
										printf(
											'<a href="%s">%s</a>',
											esc_url( \WPV_WordPress_Archive_Frontend::archive_edit_link( $post->ID ) ),
											esc_html( $post->post_title )
										);
									?>
								</td>
								<?php
							}
						?>
					</tr>
					<?php
				}
			?>
		</tbody>
	</table>
	<?php
}

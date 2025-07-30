<?php

/**
* wpv_admin_menu_views_listing_page
*
* Creates the main structure of the Views admin listing page: wrapper and header
*
*/

function wpv_admin_menu_views_listing_page()
{
	?>
	<div class="wrap toolset-views">

		<h1><!-- classname wpv-page-title removed -->
			<?php
			_e( 'Views', 'wpv-views' );

			printf( ' <a href="#" class="add-new-h2 page-title-action js-wpv-views-add-new-top">%s</a>', __( 'Add New', 'wpv-views' ) );

			// TODO maybe have this nonce as a data attribute for all buttons opening the popup
			wp_nonce_field( 'wp_nonce_create_view_wrapper', 'wp_nonce_create_view_wrapper' );

			// 'trash' or 'publish'
			$current_post_status = wpv_getget( 'status', 'publish', array( 'trash', 'publish', 'not_used' ) );

			$search_term = esc_attr( urldecode( wp_unslash( wpv_getget( 's', '' ) ) ) );

			// IDs of possible results and counts per post status
			$views_pre_query_data = wpv_prepare_view_listing_query( 'normal', $current_post_status );

			// general nonce
			// TODO please do NOT use this general nonce
			wp_nonce_field( 'work_views_listing', 'work_views_listing' );

			if ( ! empty( $search_term ) ) {
				if ( 'trash' == $current_post_status ) {
					$search_message = __( 'Search results for "%s" in trashed Views', 'wpv-views' );
				} else {
					$search_message = __( 'Search results for "%s"', 'wpv-views' );
				}
				?>
				<span class="subtitle">
							<?php echo sprintf( $search_message, $search_term ); ?>
						</span>
				<?php
			}
			?>
		</h1>

		<div class="wpv-views-listing-page">
			<?php
			// Messages: trashed, untrashed, deleted
			add_filter( 'wpv_maybe_show_listing_message_undo', 'wpv_admin_view_listing_message_undo', 10, 3 );

			wpv_maybe_show_listing_message( 'trashed', __( 'View moved to the Trash.', 'wpv-views' ), __( '%d Views moved to the Trash.', 'wpv-views' ), true );
			wpv_maybe_show_listing_message( 'untrashed', __( 'View restored from the Trash.', 'wpv-views' ), __( '%d Views restored from the Trash.', 'wpv-views' ) );
			wpv_maybe_show_listing_message( 'deleted', __( 'View permanently deleted.', 'wpv-views' ), __( '%d Views permanently deleted.', 'wpv-views' ) );
			wpv_maybe_show_listing_message( 'not_used', __( 'View not used in any post.', 'wpv-views' ), __( '%d Views not used in any post.', 'wpv-views' ) );


			wpv_admin_view_listing_table( $views_pre_query_data, $current_post_status, $search_term );

			?>

		</div> <!-- .wpv-views-listing-page" -->

	</div> <!-- .toolset-views" -->

	<?php

	wpv_render_view_listing_dialog_templates();
}


/**
 * wpv_admin_view_listing_table
 *
 * Displays the content of the Views admin listing page: status, table and pagination.
 *
 * @param array $views_pre_query_data Array with IDs of possible results and counts per post status.
 *     See wpv_prepare_view_listing_query() for details.
 * @param string $current_post_status Status of posts to display. Can be 'publish' or 'trash'.
 * @param string $search_term Sanitized search term or empty string if no search is being performed.
 *
 * @since unknown
 * @since 2.4 Added the $search_term parameter that contains the sanitized search term when a search is performed.
 */
function wpv_admin_view_listing_table( $views_pre_query_data, $current_post_status, $search_term ) {
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
		'posts_per_page' => WPV_ITEMS_PER_PAGE,
		'order' => 'ASC',
		'orderby' => 'title',
	);

	if ( $current_post_status == 'not_used' ) {
		$wpv_args['meta_query'] = array(
			array(
				'key' => '_wpv_used_in_posts',
				'value' => '',
				'compare' => '=',
			)
		);
	}
	else {
		$wpv_args['post__in'] = $views_pre_query_data[ 'post__in' ];
		$wpv_args['post_status'] = $current_post_status;
	}

	$is_search = ! empty( $search_term );

	// perform the search in Views titles and decriptions and update post__in argument to $wpv_args.
	if ( $is_search ) {
		$wpv_args = wpv_modify_wpquery_for_search( $search_term, $wpv_args );
		$mod_url['s'] = urlencode( $search_term );
	}

	// apply posts_per_page coming from the URL parameters
	if ( isset( $_GET["items_per_page"] ) && '' != $_GET["items_per_page"] ) {
		$wpv_args['posts_per_page'] = (int) $_GET["items_per_page"];
		$mod_url['items_per_page'] = (int) $_GET["items_per_page"];
	}

	// apply orderby coming from the URL parameters
	if ( isset( $_GET["orderby"] ) && '' != $_GET["orderby"] ) {
		$wpv_args['orderby'] = sanitize_text_field( $_GET["orderby"] );
		$mod_url['orderby'] = sanitize_text_field( $_GET["orderby"] );

		// apply order coming from the URL parameters
		if ( isset( $_GET["order"] ) && '' != $_GET["order"] ) {
			$wpv_args['order'] = sanitize_text_field( $_GET["order"] );
			$mod_url['order'] = sanitize_text_field( $_GET["order"] );
		}
	}

	// apply paged coming from the URL parameters
	if ( isset( $_GET["paged"] ) && '' != $_GET["paged"] ) {
		$wpv_args['paged'] = (int) $_GET["paged"];
		$mod_url['paged'] = (int) $_GET["paged"];
	}

	$wpv_query = new WP_Query( $wpv_args );

	// The number of Views being displayed.
	$wpv_count_posts = $wpv_query->post_count;

	// Total number of Views matching query parameters.
	$wpv_found_posts = $wpv_query->found_posts;

	?>
		<ul class="subsubsub"><!-- links to lists Views in different statuses -->
			<?php
			// "publish" status
			$is_current = ( 'publish' == $current_post_status );
			printf(
				'<li><a href="%s" %s >%s</a> (%s)%s</li>',
				esc_url( add_query_arg(
					array( 'page' => 'views', 'status' => 'publish' ),
					admin_url('admin.php') ) ),
				$is_current ? ' class="current" ' : '',
				__( 'Published', 'wpv-views' ),
				$views_pre_query_data['published_count'],
				( $views_pre_query_data['trashed_count'] > 0 ) ? ' | ' : '' );

			// "trash" status
			if( $views_pre_query_data['trashed_count'] > 0 ) {
				$is_current = ( ( 'trash' == $current_post_status ) );
				printf(
					'<li><a href="%s" %s >%s</a> (%s)</li>',
					esc_url( add_query_arg(
						array( 'page' => 'views', 'status' => 'trash' ),
						admin_url('admin.php') ) ),
					$is_current ? ' class="current" ' : '',
					__( 'Trash', 'wpv-views' ),
					$views_pre_query_data['trashed_count'] );
			}
			?>
			<?php
				if ( $wpv_count_posts > 0 ) {
					$is_current = ( 'not_used' == $current_post_status );
					printf(
						'<li><a href="%s" %s >%s</a> (%s)</li>',
						esc_url( add_query_arg(
							array( 'page' => 'views', 'status' => 'not_used' ),
							admin_url('admin.php') ) ),
						$is_current ? ' class="current" ' : '',
						__( 'Not used', 'wpv-views' ),
						$wpv_found_posts );
				}
			?>
		</ul>
	<?php

	// A nonce for view action - used for individual as well as for bulk actions
	$view_action_nonce = wp_create_nonce( 'wpv_view_listing_actions_nonce' );

	// If there is one or more Views in this query or if there is a search happening, show search box
	if ( $wpv_found_posts > 0 || ( isset( $_GET["s"] ) && $_GET["s"] != '' ) ) {
		?>
		<div class="alignright">
			<form id="posts-filter" action="" method="get">
				<p class="search-box">
					<label class="screen-reader-text" for="post-search-input"><?php _e('Search Views','wpv-views'); ?>:</label>
					<input type="search" id="post-search-input" name="s" value="<?php echo $search_term; ?>" />
					<input type="submit" name="" id="search-submit" class="button" value="<?php echo htmlentities( __('Search Views','wpv-views'), ENT_QUOTES ); ?>" />
					<input type="hidden" name="paged" value="1" />
				</p>
			</form>
		</div>
		<?php
	}

	// === Render "tablenav" section (Bulk actions and Search box) ===
	echo '<div class="tablenav top">';

	// If this page has one or more Views, show Bulk actions controls
	if ( $wpv_count_posts > 0 ) {
		// Prepare ender bulk actions dropdown.
		if( 'publish' == $current_post_status ) {
			$bulk_actions = array( 'trash' => __( 'Move to Trash', 'wpv-views' ) );
		} else {
			$bulk_actions = array(
				'restore-from-trash' => __( 'Restore', 'wpv-views' ),
				'delete' => __( 'Delete Permanently', 'wpv-views' ) );
		}

		$bulk_actions_args = array( 'data-viewactionnonce' => $view_action_nonce );
		$bulk_actions_class = 'js-wpv-views-listing-bulk-action';

		echo wpv_admin_table_bulk_actions( $bulk_actions, $bulk_actions_class, $bulk_actions_args, 'top' );
	}

	if ( isset( $_GET["status"] ) && $_GET["status"] == 'trash' ) {

		$empty_trash_args = array( 'data-viewactionnonce' => $view_action_nonce );

		$empty_trash_class = 'js-wpv-views-empty-trash';

		echo wpv_admin_empty_trash( $empty_trash_class, $empty_trash_args, 'top' );
	}

	echo '</div>'; // End of tablenav section


	//Show the table
	?>
	<table class="wpv-views-listing js-wpv-views-listing widefat">
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
						'<a href="%s" class="%s" data-orderby="ID">%s <i class="%s"></i></a>',
						wpv_maybe_add_query_arg(
							array(
								'page' => 'views',
								'orderby' => 'ID',
								'order' => $column_sort_to,
								's' => $mod_url['s'],
								'items_per_page' => $mod_url['items_per_page'],
								'paged' => $mod_url['paged'],
								'status' => $mod_url['status']
							),
							admin_url( 'admin.php' )
						),
						'js-views-list-sort views-list-sort ' . $column_active,
						__( 'ID','wpv-views' ),
						( 'DESC' === $column_sort_now ) ? 'icon-sort-by-attributes-alt fa fa-sort-amount-desc' : 'icon-sort-by-attributes fa fa-sort-amount-asc'
					);
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
							'<a href="%s" class="%s" data-orderby="title">%s <i class="%s"></i></a>',
							wpv_maybe_add_query_arg(
								array(
									'page' => 'views',
									'orderby' => 'title',
									'order' => $column_sort_to,
									's' => $mod_url['s'],
									'items_per_page' => $mod_url['items_per_page'],
									'paged' => $mod_url['paged'],
									'status' => $mod_url['status']
								),
								admin_url( 'admin.php' )
							),
							'js-views-list-sort views-list-sort ' . $column_active,
							__( 'Title','wpv-views' ),
							( 'DESC' === $column_sort_now ) ? 'icon-sort-by-alphabet-alt fa fa-sort-alpha-desc' : 'icon-sort-by-alphabet fa fa-sort-alpha-asc'
						);
					?>
				</th>

				<th class="wpv-admin-listing-col-summary js-wpv-col-two"><?php _e('Content to load','wpv-views') // TODO review this classname ?></th>
				<th class="wpv-admin-listing-col-scan"><?php _e('Used on','wpv-views') ?></th>

				<th class="wpv-admin-listing-col-cached">
					<?php echo __( 'Frontend output cache', 'wpv-views' ); ?>
				</th>

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
												'page' => 'views',
												'orderby' => 'modified',
												'order' => $column_sort_to,
												's' => $mod_url['s'],
												'items_per_page' => $mod_url['items_per_page'],
												'paged' => $mod_url['paged'],
												'status' => $mod_url['status'] ),
										admin_url( 'admin.php' ) ),
								'js-views-list-sort views-list-sort ' . $column_active,
								/* translators: Label of the link in the admin table column head to sort Views by their last modified date */
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

		<?php
		$views_output_cache_store = \OTGS\Toolset\Views\Controller\Cache\Views\Store::get_instance();
		$views_output_cache_index = $views_output_cache_store->get_stored_index();
		?>
		<tbody class="js-wpv-views-listing-body">
			<?php
			// If this page has one or more Views
			if ( $wpv_count_posts > 0) {
				$alternate = '';
				while ( $wpv_query->have_posts() ) :
					$wpv_query->the_post();
					$post_id = get_the_id();
					$post = get_post( $post_id, OBJECT, 'edit' );
					$meta = get_post_meta( $post_id, '_wpv_settings', true );
					$is_gutenberg_view = \WPV_View::get_instance( $post_id )->get_is_gutenberg_view();
					$gutenberg_view_edit_hint = __( 'This View can only be edited in the Block Editor from within the post that includes it.', 'wpv_views' );
					$view_description = get_post_meta( $post_id, '_wpv_description', true );
					$alternate = ' alternate' == $alternate ? '' : ' alternate';
					?>
					<tr id="wpv_view_list_row_<?php echo $post->ID; ?>" class="js-wpv-view-list-row<?php echo $alternate; ?>">
						<th class="wpv-admin-listing-col-bulkactions check-column">
							<?php
							if ( ! $is_gutenberg_view ) {
								printf( '<input type="checkbox" value="%s" name="view[]" />', esc_html( $post->ID ) );
							}
							?>
						</th>
						<td class="wpv-admin-listing-col-id">
							<?php echo $post_id; ?>
						</td>
						<td class="wpv-admin-listing-col-title">
							<span class="row-title">
							<?php
								if ( 'trash' == $current_post_status ) {
									echo esc_html( $post->post_title );
								} elseif ( $is_gutenberg_view ) {
									printf(
										'<span class="js-otgs-popover-tooltip" data-tippy-content="%3$s">%1$s &mdash; %2$s</span>',
										esc_html( $post->post_title ),
										/* translators: The name of the editor, the View was built with. */
										'<span class="post-builder">' . esc_html( __( 'Block Editor', 'wpv-views' ) ) . '</span>',
										esc_html( $gutenberg_view_edit_hint )
									);
								} else {
									printf( '<a href="%s">%s</a>',
										esc_url( add_query_arg(
												array( 'page' => 'views-editor', 'view_id' => $post->ID ),
												admin_url( 'admin.php' ) ) ),
										esc_html( $post->post_title )
									);
								}
							?>
							</span>
							<?php
								if ( isset( $view_description ) && '' != $view_description ) {
									?>
									<p class="desc">
										<?php echo nl2br($view_description); ?>
									</p>
									<?php
								}

								/* Generate and show row actions.
								 * Note that we want to add also 'simple' action names to the action list because
								 * they get echoed as a class of the span tag and get styled from WordPress core css
								 * accordingly (e.g. trash in different colour than the rest) */
								$row_actions = array( );

								if ( 'publish' == $current_post_status ) {
									if ( $is_gutenberg_view ) {
										/**
										 * Get the View's parent post ID.
										 *
										 * @param null|string|int $parent_post_id
										 * @param string|int      $view_id
										 */
										$maybe_parent_post_id = apply_filters( 'wpv_filter_get_view_parent_post_id', null, $post_id );
										$maybe_parent_post = $maybe_parent_post_id && 'trash' !== get_post_status( $maybe_parent_post_id ) ?
											get_post( $maybe_parent_post_id ) :
											null;
										if ( null !== $maybe_parent_post ) {
											$edit_link_title = sprintf(
												/* translators: Edit action link text for the cases where the View is build with the new View editor. */
												__( 'Edit in the "%s"', 'wpv-views' ),
												$maybe_parent_post->post_title
											);

											// Edit action.
											$row_actions['edit'] = sprintf(
												'<span class="js-otgs-popover-tooltip" data-tippy-content="%3$s"><a href="%2$s">%1$s</a></span>',
												esc_html( $edit_link_title ),
												esc_attr( get_edit_post_link( $maybe_parent_post_id ) ),
												esc_attr( $gutenberg_view_edit_hint )
											);
										} else {
											$row_actions['edit'] = sprintf(
												'<span class="js-otgs-popover-tooltip" data-tippy-content="%2$s"><a>%1$s</a></span>',
												esc_html( __( 'Edit is not supported for this View', 'wpv-views' ) ),
												esc_attr( __( 'To edit this View, it needs to be added inside a post first.', 'wpv-views' ) )
											);
										}
									} else {
										// Edit action.
										$row_actions['edit'] = sprintf(
											'<a href="%s">%s</a>',
											esc_url( add_query_arg(
												array( 'page' => 'views-editor', 'view_id' => $post->ID ),
												admin_url( 'admin.php' ) ) ),
											__( 'Edit', 'wpv-views' )
										);

										// Duplicate action.
										$row_actions['duplicate js-views-actions-duplicate'] = sprintf( '<a href="#">%s</a>', __( 'Duplicate', 'wpv-views' ) );
									}

									// Trash action.
									$row_actions['trash js-views-actions-trash'] = sprintf( '<a href="#">%s</a>', __( 'Trash', 'wpv-views' ) );
								} else if ( 'trash' == $current_post_status ) {
									$row_actions['restore-from-trash js-views-actions-restore-from-trash'] = sprintf( '<a href="#">%s</a>', __( 'Restore', 'wpv-views' ) );
									$row_actions['delete js-views-actions-delete'] = sprintf( '<a href="#">%s</a>', __( 'Delete Permanently', 'wpv-views' ) );
								}

								echo wpv_admin_table_row_actions( $row_actions,	array(
										"data-view-id" => $post->ID,
										"data-view-title" => esc_html( $post->post_title ),
										"data-viewactionnonce" => $view_action_nonce ) );
							?>
						</td>
						<td class="wpv-admin-listing-col-summary">
							<?php echo wpv_create_content_summary_for_listing( $post->ID ); ?>
						</td>
						<td class="wpv-admin-listing-col-scan">
							<button class="button js-scan-button" data-view-id="<?php echo $post->ID; ?>">
								<i class="icon-barcode fa fa-barcode"></i> <?php _e( 'Scan', 'wpv-views' ); ?>
							</button>
							<span class="js-nothing-message hidden"><?php _e( 'Nothing found', 'wpv-views' ); ?></span>
						</td>
						<td class="wpv-admin-listing-col-cached">
							<?php
							$query_type_array = toolset_getarr( $meta, 'query_type', array( 'posts' ) );
							$query_type = empty( $query_type_array ) ? 'posts' : reset( $query_type_array );
							$views_output_cache_per_domain = toolset_getarr( $views_output_cache_index, $query_type, array() );
							if ( array_key_exists( $post->ID, $views_output_cache_per_domain ) ) {
								?>
								<button class="button js-wpv-clear-view-cache" data-view-id="<?php echo $post->ID; ?>">
									<i class="fa fa-times"></i> <?php _e( 'Clean cache', 'wpv-views' ); ?>
								</button>
								<?php
							} else {
								echo __( 'Not cached', 'wpv-views' );
							}
							?>
						</td>
						<td class="wpv-admin-listing-col-modified">
							<?php
							$display_date = get_the_modified_time( get_option( 'date_format' ), $post->ID );
							$abbr_date = get_the_modified_time( __( 'Y/m/d g:i:s a' ), $post->ID );
							echo '<abbr title="' . $abbr_date . '">' . $display_date . '</abbr>';
							?>
						</td>
					</tr>
				<?php
				endwhile;
			}
			// No Views matches the criteria
			else {
				if ( isset( $_GET["status"] ) && $_GET["status"] == 'trash' && isset( $_GET["s"] ) && $_GET["s"] != '' ) {
					?>
					<tr class="no-items">
						<td class="js-wpv-view-list-row alternate" colspan="6">
							<?php
							_e( 'No Views found in Trash that matched your criteria.', 'wpv-views' );
							?>
						</td>
					</tr>
					<?php
				} else if ( isset( $_GET["status"] ) && $_GET["status"] == 'trash' ) {
					?>
					<tr class="no-items">
						<td class="js-wpv-view-list-row alternate" colspan="6"><?php _e( 'No Views found in Trash.', 'wpv-views' ); ?></td>
					</tr>
					<?php
				} else if ( isset( $_GET["s"] ) && $_GET["s"] != '' ) {
					?>
					<tr class="no-items">
						<td class="js-wpv-view-list-row alternate" colspan="6"><?php _e( 'No Views matched your criteria.', 'wpv-views' ); ?></td>
					</tr>
					<?php
				} else {
					?>
					<tr class="no-items">
						<td class="js-wpv-view-list-row alternate" colspan="6"><?php _e( 'No Views found.', 'wpv-views' ); ?></td>
					</tr>
					<?php
				}
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
				$empty_trash_args = array( 'data-viewactionnonce' => $view_action_nonce );
				$empty_trash_class = 'js-wpv-views-empty-trash';

				echo wpv_admin_empty_trash( $empty_trash_class, $empty_trash_args, 'bottom' );
			}
		?>
	</div>

	<?php
		wpv_admin_listing_pagination( $wpv_found_posts, 'views', $wpv_args['posts_per_page'], $mod_url );
	?>
	<?php
}


/**
 * DEPRECATED.
 *
 * This is not used anywhere in Views.
 */
function wpv_admin_menu_views_listing_row($post_id) {

	ob_start();
	$post = get_post($post_id);
	$meta = get_post_meta($post_id, '_wpv_settings');
	$view_description = get_post_meta($post_id, '_wpv_description', true);
	?>
	<tr id="wpv_view_list_row_<?php echo $post->ID; ?>" class="js-wpv-view-list-row">
		<td class="post-title page-title column-title">
			<span class="row-title">
				<a href="admin.php?page=views-editor&amp;view_id=<?php echo esc_attr( $post->ID ); ?>"><?php echo esc_html( $post->post_title ); ?></a>
			</span>
			<?php if (isset($view_description) && '' != $view_description): ?>
				<p class="desc">
					<?php echo nl2br($view_description)?>
				</p>
			<?php endif; ?>
		</td>
		<td>
			<?php echo wpv_create_content_summary_for_listing($post->ID); ?>
		</td>
		<td>
			<select class="js-views-actions" name="list_views_action_<?php echo $post->ID; ?>" id="list_views_action_<?php echo $post->ID; ?>" data-view-id="<?php echo $post->ID; ?>">
				<option value="0"><?php _e('Choose','wpv-views') ?>&hellip;</option>
				<option value="delete"><?php _e('Delete','wpv-views') ?></option>
				<option value="duplicate"><?php _e('Duplicate','wpv-views') ?></option>
			</select>
		</td>
		<td>
			<button class="button js-scan-button" data-view-id="<?php echo $post->ID; ?>"><?php _e('Scan','wpv-views') ?></button>
			<span class="js-nothing-message hidden"><?php _e('Nothing found','wpv-views');?></span>
		</td>
		<td>
			<?php echo get_the_modified_time( get_option( 'date_format' ), $post->ID ); ?>
		</td>
	</tr>
	<?php
	$row = ob_get_contents();
	ob_end_clean();

	return $row;

}

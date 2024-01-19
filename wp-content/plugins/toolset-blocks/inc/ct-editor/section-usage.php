<?php

/**
 *
 *
 * @since 2.6.0
 */
class Wpv_Content_Template_Editor_Section_Usage {

	private $single_pages_assigned_labels = array();
	private $single_pages_assigned_to_other = array();
	private $post_archives_assigned_labels = array();
	private $post_archives_assigned_to_other = array();
	private $taxonomy_archives_assigned_labels = array();
	private $taxonomy_archives_assigned_to_other = array();

	public function __construct() {

		add_filter( 'wpv_ct_editor_request_properties', array( $this, 'request_properties' ) );
		add_filter( 'wpv_ct_editor_localize_script', array( $this, 'localize_script' ) );
		add_filter( 'wpv_ct_editor_add_custom_properties', array( $this, 'add_properties' ), 10, 2 );

		add_action( 'wpv_ct_editor_sections', array( $this, 'section' ), 40 );

	}

	/**
	 * Request WPV_Content_Template properties for the JS side
	 *
	 * @since 1.9.0
	 */
	public function request_properties( $property_names ) {
		return array_merge( $property_names, array(
			'assigned_single_post_types',
			'assigned_post_archives',
			'assigned_taxonomy_archives',
			'dissident_posts'
		) );
	}

	/**
	 * Localize the section in JS
	 *
	 * @since 1.9.0
	 */
	public function localize_script( $l10n_data ) {
		$l10n_data['usage_section'] = array(
			'saved' => esc_attr( __( 'Content Template usage changed.', 'wpv-views' ) ),
			'unsaved' => esc_attr( __( 'Content Template usage not changed.', 'wpv-views' ) ),
			'ptr_section' => array(
				'title' => __( 'Choose which content types will display using this template.', 'wpv-views' ),
				'paragraphs' => array(
					__( 'Choose which content types will display using this template.', 'wpv-views' )
				)
			)
		);
		return $l10n_data;
	}


	/**
	 * Pass custom properties to the JS side
	 *
	 * This will add following properties to the $ct_data that will eventually be passed
	 * to the JS script:
	 *
	 * - usage_bind_nonce: A nonce to bind dissident posts to this CT.
	 * - usage_bind_dialog_template: Localized HTML template for a Colorbox dialog for
	 *     asking user's confirmation to bind dissident posts.
	 *
	 * @param array $ct_data Content Template data
	 * @param WPV_Content_Template $ct
	 * @return array Updated Content Template data
	 *
	 * @since 1.9
	 */
	public function add_properties( $ct_data, $ct ) {
		$uid = get_current_user_id();

		$ct_data['usage_bind_nonce'] = wp_create_nonce( "wpv_ct_{$ct->id}_bind_posts_by_{$uid}" );

		$bind_dialog_message_template = sprintf(
			/* translators: Hint on how many items are not using a given Content Template, like: 5 Books use either no Content Template, or a different one. */
			__( '%s %s use either no Content Template, or a different one.', 'wpv-views' ),
			'<span class="js-wpv-ct-bind-dialog-post-count"></span>' ,
			'<span class="js-wpv-ct-bind-dialog-post-type"></span>' );

		$ct_data['usage_bind_dialog_template'] = '
			<div class="wpv-dialog js-wpv-bind-dissident-posts-dialog">
				<div class="wpv-dialog-header">
					<h2>' . __( 'Do you want to apply to all?', 'wpv-views' ) . '</h2>
					<i class="icon-remove fa fa-times js-dialog-close"></i>
				</div>
				<div class="wpv-dialog-content">' . $bind_dialog_message_template . '</div>
				<div class="wpv-dialog-footer">
					<button class="button js-dialog-close">' . __( 'Cancel', 'wpv-views' ) . '</button>
					<button class="button button-primary js-wpv-dialog-update-button">
						'. __( 'Update', 'wpv-views' ) . '</button>
				</div>
			</div>';

		$post_type_labels = array();

		global $WPV_view_archive_loop;
		$public_post_types = $WPV_view_archive_loop->get_archive_loops( 'post_type', false, true, true );
		foreach( $public_post_types as $post_type ) {
			$post_type_labels[ $post_type['post_type_name'] ] = array(
				'singular' => $post_type['singular_name'],
				'plural' => $post_type['display_name']
			);
		}

		$ct_data['usage_post_type_labels'] = $post_type_labels;

		return $ct_data;
	}

	/**
	 * Render section content
	 *
	 * @since 1.9.0
	 */
	public function section( $ct ) {
		ob_start();

		$parent_view = null;
		if( $ct->is_owned_by_view ) {
			$parent_view = WPV_View_Base::get_instance($ct->loop_output_id);
		}

		if( null != $parent_view ) {

			if( $parent_view->is_published ) {
				$edit_page = 'views-editor';
				if ( WPV_View_Base::is_archive_view( $parent_view->id ) ) {
					$edit_page = 'view-archives-editor';
				}
				$loop_template_notice = sprintf(
					__( 'This Content Template is used as the loop block for the %s <a href="%s" target="_blank">%s</a>.', 'wpv-views' ),
					$parent_view->query_mode_display_name,
					esc_attr( add_query_arg(
						array(
							'page' => $edit_page,
							'view_id' => $parent_view->id
						),
						admin_url( 'admin.php' )
					) ),
					$parent_view->title
				);

			} else {

				$loop_template_notice = sprintf(
					__( 'This Content Template is used as the loop block for the trashed %s %s.', 'wpv-views' ),
					$parent_view->query_mode_display_name,
					"<strong>{$parent_view->title}</strong>"
				);
			}

			printf( '<div class="wpv-advanced-setting"><p>%s</p></div>', $loop_template_notice );

		} else {

			$asterisk_explanation =
				'<span data-bind="fadeVisibility: isAsteriskExplanationVisible(\'%s\', \'%s\')"><span style="color:red">*</span> '
				. __('A different Content Template is already assigned to this item.', 'wpv-views')
				. '</span>';

			// Gather the uages of the current CT
			$assigned_single_post_types = $ct->assigned_single_post_types;
			$assigned_post_archives = $ct->assigned_post_archives;
			$assigned_taxonomy_archives = $ct->assigned_taxonomy_archives;

			$has_assignment = (
				! empty( $assigned_single_post_types )
				|| ! empty( $assigned_post_archives )
				|| ! empty( $assigned_taxonomy_archives )
			);

			// If the CT is assigned, hide the sets of checkboxes and display a summary instead
			if ( $has_assignment ) {
				?>
				<div class="js-wpv-ct-usage-container" style="display:none">
				<?php
			}

			// Render checkboxes for each type of assignment.
			$this->single_pages($ct, $asterisk_explanation);
			$this->post_archives($ct, $asterisk_explanation);
			$this->taxonomy_archives($ct, $asterisk_explanation);

			// Print information about other CT assignments for JS
			$other_assignments = array(
				'single_posts' => $this->single_pages_assigned_to_other,
				'cpt_archives' => $this->post_archives_assigned_to_other,
				'taxonomy_archives' => $this->taxonomy_archives_assigned_to_other
			);


			printf(
				'<span style="visibility: hidden" class="js-wpv-usage-other-assignments" data-value="%s"></span>',
				htmlentities(json_encode($other_assignments))
			);
			?>

			<p class="update-button-wrap">
				<span class="update-action-wrap">
					<span class="js-wpv-message-container"></span>
				</span>
			</p>

		<?php

			// If the CT is assigned, hide the sets of checkboxes and display a summary instead
			if ( $has_assignment ) {
				$assigned_labels= $this->get_assigned_labels();
				?>
				</div><!-- js-wpv-ct-usage-container -->
				<div class="js-wpv-ct-usage-container-summary">
					<p>
						<?php echo sprintf(
							__( 'This Content Template is used for %s.', 'wpv-views' ),
							implode( ', ', $assigned_labels )
						);
						?>
					</p>
					<p>
						<button class="button-secondary js-wpv-ct-usage-toggle">
							<?php _e( 'Change usage', 'wpv-views' ); ?>
						</button>
					</p>
				</div>
				<?php
			}
		}

		$content = ob_get_contents();
		ob_end_clean();

		wpv_ct_editor_render_section(
			__( 'Usage', 'wpv-views' ),
			'js-wpv-usage-section',
			$content,
			false,
			'',
			'',
			array( 'section' => 'usage_section', 'pointer_slug' => 'ptr_section' ) );
	}

	/**
	 * Join all the labels for all th usages of the current Content Template
	 *
	 * @return string[]
	 *
	 * @since 2.6.0
	 */
	private function get_assigned_labels() {
		$assigned_labels = array();
		if ( ! empty( $this->single_pages_assigned_labels ) ) {
			$assigned_labels[] = '<strong>'
				. implode( '</strong>, <strong>', $this->single_pages_assigned_labels )
				. '</strong>';
		}
		if ( ! empty( $this->post_archives_assigned_labels ) ) {
			$assigned_labels[] = '<strong>'
				. implode( '</strong>, <strong>', $this->post_archives_assigned_labels )
				. '</strong>';
		}
		if ( ! empty( $this->taxonomy_archives_assigned_labels ) ) {
			$assigned_labels[] = '<strong>'
				. implode( '</strong>, <strong>', $this->taxonomy_archives_assigned_labels )
				. '</strong>';
		}
		return $assigned_labels;
	}

	/**
	 * Render subsection for assignment type "single page"
	 *
	 * @param WPV_Content_Template $ct
	 * @param string $asterisk_explanation Localized HTML template with asterisk explanation.
	 *     Should contain two "%s" placeholders, one for the Knockout ViewModel property name and second
	 *     for the assignment type (see js-wpv-usage-other-assignments).
	 * @return array Post type slugs with different CT assigned.
	 *
	 * @since 1.9
	 */
	private function single_pages( $ct, $asterisk_explanation ) {
		global $WPV_view_archive_loop;

		?>
		<div class="wpv-advanced-setting">
			<h3><?php _e( 'Single pages', 'wpv-views' ); ?></h3>
			<ul class="wpv-mightlong-list">
				<?php

				$public_post_types = $WPV_view_archive_loop->get_archive_loops( 'post_type', false, true, true );

				foreach( $public_post_types as $post_type ) {

					$is_assigned_to_other_ct = ( $post_type['single_ct'] != 0 && $post_type['single_ct'] != $ct->id );

					if( $is_assigned_to_other_ct ) {
						$this->single_pages_assigned_to_other[] = $post_type['post_type_name'];
					}

					if ( $post_type['single_ct'] == $ct->id ) {
						$this->single_pages_assigned_labels[] = $post_type['display_name'];
					}

					$this->render_item(
						$post_type['post_type_name'],
						$post_type['display_name'],
						'assignedSinglePostTypesAccepted',
						'single_posts',
						$is_assigned_to_other_ct
					);

				}
				?>
			</ul>
			<?php
			printf( $asterisk_explanation, 'assignedSinglePostTypesAccepted', 'single_posts' );
			?>
		</div>

	<?php
	}

	/**
	 * Render subsection for assignment type "post archive"
	 *
	 * @param WPV_Content_Template $ct
	 * @param string $asterisk_explanation Localized HTML template with asterisk explanation.
	 *     Should contain two "%s" placeholders, one for the Knockout ViewModel property name and second
	 *     for the assignment type (see js-wpv-usage-other-assignments).
	 * @return array Post type slugs with different CT assigned.
	 *
	 * @since 1.9
	 */
	private function post_archives( $ct, $asterisk_explanation ) {
		global $WPV_view_archive_loop;

		?>
		<div class="wpv-advanced-setting">
			<h3><?php _e( 'Post archives', 'wpv-views' ); ?></h3>
			<?php
			$custom_post_types = $WPV_view_archive_loop->get_archive_loops( 'post_type', false, true, false );

			$cpt_archives_exist = !empty( $custom_post_types );

			if( $cpt_archives_exist ) {
				?>
				<ul class="wpv-mightlong-list">
					<?php
					foreach( $custom_post_types as $post_type ) {

						$is_assigned_to_other_ct = ( $post_type['ct'] != 0 && $post_type['ct'] != $ct->id );

						if( $is_assigned_to_other_ct ) {
							$this->post_archives_assigned_to_other[] = $post_type['post_type_name'];
						}

						if ( $post_type['ct'] == $ct->id ) {
							$this->post_archives_assigned_labels[] = sprintf(
								__( '%s archives', 'wpv-views' ),
								$post_type['display_name']
							);
						}

						$this->render_item(
							$post_type['post_type_name'], $post_type['display_name'], 'assignedPostArchivesAccepted', 'cpt_archives', $is_assigned_to_other_ct
						);

					}
					?>
				</ul>
			<?php
			} else {
				printf( '<p>%s</p>', __( 'There are no custom post type archives', 'wpv-views' ) );
			}

			printf( $asterisk_explanation, 'assignedPostArchivesAccepted', 'cpt_archives' );
			?>
		</div>

	<?php
	}

	/**
	 * Render subsection for assignment type "taxonomy archive"
	 *
	 * @param WPV_Content_Template $ct
	 * @param string $asterisk_explanation Localized HTML template with asterisk explanation.
	 *     Should contain two "%s" placeholders, one for the Knockout ViewModel property name and second
	 *     for the assignment type (see js-wpv-usage-other-assignments).
	 * @return array Taxonomy slugs with different CT assigned.
	 *
	 * @since 1.9
	 */
	private function taxonomy_archives( $ct, $asterisk_explanation ) {
		global $WPV_view_archive_loop;

		?>
		<div class="wpv-advanced-setting">
			<h3><?php _e( 'Taxonomy archives', 'wpv-views' ); ?></h3>
			<ul class="wpv-mightlong-list">
				<?php

				$taxonomy_loops = $WPV_view_archive_loop->get_archive_loops( 'taxonomy', false, true );

				foreach( $taxonomy_loops as $taxonomy_loop ) {

					$is_assigned_to_other_ct = ( $taxonomy_loop['ct'] != 0 && $taxonomy_loop['ct'] != $ct->id );

					if( $is_assigned_to_other_ct ) {
						$this->taxonomy_archives_assigned_to_other[] = $taxonomy_loop['slug'];
					}

					if ( $taxonomy_loop['ct'] == $ct->id ) {
						$this->taxonomy_archives_assigned_labels[] = sprintf(
							__( '%s archives', 'wpv-views' ),
							$taxonomy_loop['display_name']
						);
					}

					$this->render_item(
						$taxonomy_loop['slug'], $taxonomy_loop['display_name'], 'assignedTaxonomyArchivesAccepted', 'taxonomy_archives', $is_assigned_to_other_ct
					);

				}
				?>
			</ul>
			<?php
			printf( $asterisk_explanation, 'assignedTaxonomyArchivesAccepted', 'taxonomy_archives' );
			?>
		</div>

	<?php
	}

	/**
	 * Render an item with a checkbox for the Usage settings.
	 *
	 * It will render a list (li) item with a label, checkbox and optionally also
	 * an asterisk (if there is other CT assigned) and "Bind posts" button for dissident posts.
	 * All with proper Knockout bindings.
	 *
	 * @param string $value Value of the checkbox.
	 * @param string $display_name Label for the checkbox.
	 * @param string $binding Second parameter for vm.isAsteriskVisible in JS (look there for an explanation).
	 * @param string $assignment_type Assignment type (see js-wpv-usage-other-assignments).
	 * @param bool $is_assigned_to_other_ct Determines whether this item has different CT assigned at the time.
	 *
	 * @internal param WPV_Content_Template $ct
	 * @since 1.9
	 */
	private function render_item( $value, $display_name, $binding, $assignment_type, $is_assigned_to_other_ct )
	{
		?>
		<li>
			<label>
				<?php
					printf( '<input type="checkbox" value="%s" data-bind="checked: %s"/> ', $value, $binding );

					echo $display_name;

					if( $is_assigned_to_other_ct ) {
						printf(
							' <span style="color:red;" data-bind="fadeVisibility: isAsteriskVisible(\'%s\', \'%s\', \'%s\')">*</span>',
							$assignment_type,
							$binding,
							$value
						);

					}
				?>
			</label>
		</li>
		<?php
	}

}

new Wpv_Content_Template_Editor_Section_Usage();










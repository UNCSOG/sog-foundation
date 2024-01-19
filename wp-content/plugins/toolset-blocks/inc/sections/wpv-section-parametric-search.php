<?php
/**
 * This class is handling all functionality related to parametric search
 */

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

new WPV_Editor_Parametric_Search();

class WPV_Editor_Parametric_Search {

	function __construct() {
		add_action( 'wpv_action_view_editor_section_filter', array( $this, 'wpv_editor_section_parametric_search' ), 30, 2 );
		add_action( 'wpv_action_wpa_editor_section_filter', array( $this, 'wpv_editor_section_parametric_search' ), 30, 2 );
		add_action( 'wpv_parametric_search_buttons', array( $this, 'wpv_add_parametric_search_buttons_to_editor' ), 10, 4 );
		add_action( 'wp_ajax_wpv_parametric_search_filter_create_dialog', array( $this, 'wpv_parametric_search_filter_create_dialog' ) );

		add_filter( 'wpv_screen_options_editor_section_filter', array( $this, 'wpv_screen_options_filter_editor' ), 20 );
		add_filter( 'wpv_screen_options_wpa_editor_section_filter', array( $this, 'wpv_screen_options_filter_editor' ), 20 );
	}

	/**
     * Add parametric search section to screen options
	 * @param $sections
	 * @return array
	 */
	function wpv_screen_options_filter_editor( $sections ){

		$sections['filter-extra-parametric'] = array(
			'name'		=> __( 'Custom Search Settings', 'wpv-views' ),
			'disabled'	=> false,
		);
		return $sections;
    }

	function wpv_editor_section_parametric_search( $view_settings, $view_id ) {
		$is_section_hidden = false;
		if ( isset( $view_settings['sections-show-hide'] )
		     && isset( $view_settings['sections-show-hide']['filter-extra-parametric'] )
		     && 'off' == $view_settings['sections-show-hide']['filter-extra-parametric'] )
		{
			$is_section_hidden = true;
		}
		$hidden_class = $is_section_hidden ? 'hidden' : '';
		if (
			isset( $view_settings['view-query-mode'] )
			&& $view_settings['view-query-mode'] == 'normal'
		) {
			$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'parametric_search' );
		} else if ( isset( $view_settings['query_type'][0] ) ) {
			$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'parametric_search_archive' );
		}
		?>
		<div class="wpv-setting-container js-wpv-settings-filter-extra-parametric <?php echo $hidden_class; ?>">

			<div class="wpv-settings-header">
				<h2>
					<?php _e( 'Custom Search Settings', 'wpv-views' ) ?>
					<i class="icon-question-sign fa fa-question-circle js-display-tooltip"
					   data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>"
					   data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
					</i>
				</h2>
			</div>

			<div class="wpv-setting js-wpv-dps-settings">
				<?php
				$listing = '';
				if (
					isset( $view_settings['query_type'] )
					&& is_array( $view_settings['query_type'] )
					&& in_array( 'posts', $view_settings['query_type'] )
				) {
					$listing = 'posts';
				}
				?>
				<p class="toolset-alert toolset-alert-info wpv-settings-query-type-taxonomy wpv-settings-query-type-users<?php echo $listing == 'posts' ? ' hidden' : ''; ?>">
					<?php _e('Only Views listing posts can have custom search inputs.', 'wpv-views'); ?>
				</p>
				<div class="wpv-settings-query-type-posts<?php echo $listing == 'posts' ? '' : ' hidden'; ?>">
					<?php
					$controls_per_kind = wpv_count_filter_controls( $view_settings );
					$no_intersection = array();
					$controls_count = $controls_per_kind['cf'] + $controls_per_kind['tax'] + $controls_per_kind['pr'] + $controls_per_kind['search'];

					if (
						isset( $controls_per_kind['cf'] )
						&& $controls_per_kind['cf'] > 1
						&& (
							! isset( $view_settings['custom_fields_relationship'] )
							|| $view_settings['custom_fields_relationship'] != 'AND'
						)
					) {
						$no_intersection[] = __( 'custom field', 'wpv-views' );
					}
					if (
						isset( $controls_per_kind['tax'] )
						&& $controls_per_kind['tax'] > 1
						&& (
							! isset( $view_settings['taxonomy_relationship'] )
							|| $view_settings['taxonomy_relationship'] != 'AND'
						)
					) {
						$no_intersection[] = __( 'taxonomy', 'wpv-views' );
					}

					if ( isset( $controls_per_kind['warning'] ) ) {
						?>
						<!--<p class="toolset-alert toolset-alert-info js-wpv-mismatch-parametric-search-count">
							<?php echo $controls_per_kind['warning']; ?>
						</p>-->
						<?php
					}

					if ( isset( $controls_per_kind['error'] ) ) {
						echo $controls_per_kind['error'];
					}

					if ( ! isset( $view_settings['dps'] ) ) {
						$view_settings['dps'] = array();
						$view_settings['dps']['mode_helper'] = '';
					} else {
						if ( ! isset( $view_settings['dps']['mode_helper'] ) ) {
							$view_settings['dps']['mode_helper'] = '';
						}
					}
					?>
					<h3><?php _e( 'How do you want to update the results?', 'wpv-views' ); ?></h3>
					<ul>
						<li>
							<input type="radio" <?php checked( $view_settings['dps']['mode_helper'], '' ); ?> class="js-wpv-dps-mode-helper js-wpv-dps-mode-helper-fullrefreshonsubmitnodependency" name="wpv-dps-mode-helper" id="wpv-dps-mode-helper-fullrefreshonsubmitnodependency" value="" autocomplete="off" />
							<label for="wpv-dps-mode-helper-fullrefreshonsubmitnodependency"><?php _e( 'Full page refresh when visitors click on the search button', 'wpv-views' ); ?></label>
						</li>
						<li>
							<input type="radio" <?php checked( $view_settings['dps']['mode_helper'], 'fullrefreshonsubmit' ); ?> class="js-wpv-dps-mode-helper js-wpv-dps-mode-helper-fullrefreshonsubmit" name="wpv-dps-mode-helper" id="wpv-dps-mode-helper-fullrefreshonsubmit" value="fullrefreshonsubmit" autocomplete="off" />
							<label for="wpv-dps-mode-helper-fullrefreshonsubmit"><?php _e( 'Full page refresh when visitors click on the search button with input values auto-updating', 'wpv-views' ); ?></label>
						</li>
						<li>
							<input type="radio" <?php checked( $view_settings['dps']['mode_helper'], 'ajaxrefreshonsubmit' ); ?> class="js-wpv-dps-mode-helper js-wpv-dps-mode-helper-ajaxrefreshonsubmit" name="wpv-dps-mode-helper" id="wpv-dps-mode-helper-ajaxrefreshonsubmit" value="ajaxrefreshonsubmit" autocomplete="off" />
							<label for="wpv-dps-mode-helper-ajaxrefreshonsubmit"><?php _e( 'AJAX results update when visitors click on the search button', 'wpv-views' ); ?></label>
						</li>
						<li>
							<input type="radio" <?php checked( $view_settings['dps']['mode_helper'], 'ajaxrefreshonchange' ); ?> class="js-wpv-dps-mode-helper js-wpv-dps-mode-helper-ajaxrefreshonchange" name="wpv-dps-mode-helper" id="wpv-dps-mode-helper-ajaxrefreshonchange" value="ajaxrefreshonchange" autocomplete="off" />
							<label for="wpv-dps-mode-helper-ajaxrefreshonchange"><?php _e( 'AJAX results update when visitors change any filter values', 'wpv-views' ); ?></label>
						</li>
						<li>
							<input type="radio" <?php checked( $view_settings['dps']['mode_helper'], 'custom' ); ?> class="js-wpv-dps-mode-helper js-wpv-dps-mode-helper-custom" name="wpv-dps-mode-helper" id="wpv-dps-mode-helper-custom" value="custom" autocomplete="off" />
							<label for="wpv-dps-mode-helper-custom"><?php _e( 'Let me choose individual settings manually', 'wpv-views' ); ?></label>
						</li>
					</ul>
					<div class="wpv-advanced-setting js-wpv-ps-settings-custom"<?php if ( $view_settings['dps']['mode_helper'] != 'custom' ) { echo ' style="display:none"'; } ?>>
						<h4><?php _e('When to update the Views results', 'wpv-views'); ?></h4>
						<ul>
							<?php
							if ( ! isset( $view_settings['dps']['ajax_results'] ) ) {
								$view_settings['dps']['ajax_results'] = 'disable';
							}
							?>
							<li>
								<input type="radio" <?php checked( $view_settings['dps']['ajax_results'], 'disable' ); ?> value="disable" id="wpv-dps-ajax-results-disable" class="js-wpv-dps-ajax-results js-wpv-dps-ajax-results-disable" name="wpv-dps-ajax-results" autocomplete="off" />
								<label for="wpv-dps-ajax-results-disable"><?php _e('Update the View results only when clicking on the search button', 'wpv-views'); ?></label>
								<div class="wpv-setting-extra js-wpv-dps-ajax-results-extra js-wpv-dps-ajax-results-extra-disable"<?php if ( $view_settings['dps']['ajax_results'] != 'disable' ) { echo 'style="display:none"'; } ?>>
									<?php
									if ( !isset( $view_settings['dps']['ajax_results_submit'] ) ) {
										$view_settings['dps']['ajax_results_submit'] = 'reload';
									}
									?>
									<p>
									<ul>
										<li>
											<input type="radio" <?php checked( $view_settings['dps']['ajax_results_submit'], 'ajaxed' ); ?> name="wpv-dps-ajax-results-submit" id="wpv-ajax-results-submit-ajaxed" class="js-wpv-ajax-results-submit js-wpv-ajax-results-submit-ajaxed" value="ajaxed" autocomplete="off" />
											<label for="wpv-ajax-results-submit-ajaxed"><?php _e('Update the Views results without reloading the page', 'wpv-views'); ?></label>
										</li>
										<li>
											<input type="radio" <?php checked( $view_settings['dps']['ajax_results_submit'], 'reload' ); ?> name="wpv-dps-ajax-results-submit" id="wpv-ajax-results-submit-reload" class="js-wpv-ajax-results-submit js-wpv-ajax-results-submit-reload" value="reload" autocomplete="off" />
											<label for="wpv-ajax-results-submit-reload"><?php _e('Reload the page to update the View results', 'wpv-views'); ?></label>
										</li>
									</ul>
									</p>
								</div>
							</li>
							<li>
								<input type="radio" <?php checked( $view_settings['dps']['ajax_results'], 'enable' ); ?> value="enable" id="wpv-dps-ajax-results-enable" class="js-wpv-dps-ajax-results js-wpv-dps-ajax-results-enable" name="wpv-dps-ajax-results" autocomplete="off" />
								<label for="wpv-dps-ajax-results-enable"><?php _e('Update the View results every time an input changes', 'wpv-views'); ?></label>
							</li>
						</ul>
						<div class="wpv-ajax-results-details js-wpv-ajax-extra-callbacks"<?php if ( $view_settings['dps']['ajax_results'] != 'enable' && $view_settings['dps']['ajax_results_submit'] == 'reload' ) { echo ' style="display:none"'; } ?>>
							<?php
							$global_enable_manage_history = apply_filters( 'wpv_filter_wpv_global_parametric_search_manage_history_status', true );
							if ( $global_enable_manage_history ) {
								if ( ! isset( $view_settings['dps']['enable_history'] ) ) {
									$view_settings['dps']['enable_history'] = 'enable';
								}
								?>
								<h4><?php _e('Browser history management', 'wpv-views'); ?></h4>
								<p>
									<?php _e('You can automatically adjust the URL of the page every time that the search results are updated:', 'wpv-views'); ?>
								</p>
								<ul>
									<li>
										<input type="radio" <?php checked( $view_settings['dps']['enable_history'], 'disable' ); ?> value="disable" id="wpv-dps-history-disable" class="js-wpv-dps-history js-wpv-dps-history-disable" name="wpv-dps-history" autocomplete="off" />
										<label for="wpv-dps-history-disable"><?php _e('Do not adjust URLs after loading search results', 'wpv-views'); ?></label>
									</li>
									<li>
										<input type="radio" <?php checked( $view_settings['dps']['enable_history'], 'enable' ); ?> value="enable" id="wpv-dps-history-enable" class="js-wpv-dps-history js-wpv-dps-history-enable" name="wpv-dps-history" autocomplete="off" />
										<label for="wpv-dps-history-enable"><?php _e('Update URLs after loading search results', 'wpv-views'); ?></label>
									</li>
								</ul>
								<?php
							}
							?>
							<h4><?php _e('Javascript settings', 'wpv-views'); ?></h4>
							<p>
								<?php _e('You can execute custom javascript functions before and after the View results are updated:', 'wpv-views'); ?>
							</p>
							<ul>
								<li>
									<input type="text" id="wpv-dps-ajax-results-pre-before" class="js-wpv-dps-ajax-results-pre-before" name="wpv-dps-ajax-results-pre-before" value="<?php echo ( isset( $view_settings['dps']['ajax_results_pre_before'] ) ) ? esc_attr( $view_settings['dps']['ajax_results_pre_before'] ) : ''; ?>" autocomplete="off" />
									<label for="wpv-dps-ajax-results-pre-before"><?php _e('will run before getting the new results', 'wpv-views'); ?></label>
								</li>
								<li>
									<input type="text" id="wpv-dps-ajax-results-before" class="js-wpv-dps-ajax-results-before" name="wpv-dps-ajax-results-before" value="<?php echo ( isset( $view_settings['dps']['ajax_results_before'] ) ) ? esc_attr( $view_settings['dps']['ajax_results_before'] ) : ''; ?>" autocomplete="off" />
									<label for="wpv-dps-ajax-results-before"><?php _e('will run after getting the new results, but before updating them', 'wpv-views'); ?></label>
								</li>
								<li>
									<input type="text" id="wpv-dps-ajax-results-after" class="js-wpv-dps-ajax-results-after" name="wpv-dps-ajax-results-after" value="<?php echo ( isset( $view_settings['dps']['ajax_results_after'] ) ) ? esc_attr( $view_settings['dps']['ajax_results_after'] ) : ''; ?>" autocomplete="off" />
									<label for="wpv-dps-ajax-results-after"><?php _e('will run after updating the results', 'wpv-views'); ?></label>
								</li>
							</ul>
						</div>
						<h4><?php _e('Which options to display in the form inputs', 'wpv-views'); ?></h4>
						<?php
						if ( ! isset( $view_settings['dps']['enable_dependency'] ) ) {
							$view_settings['dps']['enable_dependency'] = 'disable';
						}
						?>
						<p class="toolset-alert toolset-alert-info js-wpv-dps-intersection-fail<?php if ( count( $no_intersection ) == 0 ) echo ' hidden'; ?>">
							<?php
							$glue = __( ' and ', 'wpv-views' );
							$no_intersection_text = implode( $glue , $no_intersection );
							echo sprintf( __( 'Your %s filters are using an internal "OR" kind of relationship, and dependant custom search for those filters needs "AND" relationships.', 'wpv-views' ), $no_intersection_text );
							?>
							<br /><br />
							<button class="button-secondary js-make-intersection-filters" data-nonce="<?php echo wp_create_nonce( 'wpv_view_make_intersection_filters' ); ?>" data-cf="<?php echo ( in_array( 'cf', $no_intersection ) ) ? 'true' : 'false'; ?>" data-tax="<?php echo ( in_array( 'tax', $no_intersection ) ) ? 'true' : 'false'; ?>">
								<?php _e('Fix filters relationship', 'wpv-views'); ?>
							</button>
						</p>
						<div class="js-wpv-dps-intersection-ok<?php if ( count( $no_intersection ) > 0 ) echo ' hidden'; ?>">
							<ul>
								<li>
									<input type="radio" <?php checked( $view_settings['dps']['enable_dependency'], 'disable' ); ?> value="disable" id="wpv-dps-enable-disable" class="js-wpv-dps-enable js-wpv-dps-enable-disable" name="wpv-dps-enable" autocomplete="off" />
									<label for="wpv-dps-enable-disable"><?php _e('Always show all values for inputs', 'wpv-views'); ?></label>
								</li>
								<li>
									<input type="radio" <?php checked( $view_settings['dps']['enable_dependency'], 'enable' ); ?> value="enable" id="wpv-dps-enable-enable" class="js-wpv-dps-enable js-wpv-dps-enable-enable" name="wpv-dps-enable" autocomplete="off" />
									<label for="wpv-dps-enable-enable"><?php _e('Show only available options for each input', 'wpv-views'); ?></label>
								</li>
							</ul>
							<div class="wpv-dps-crossed-details js-wpv-dps-crossed-details"<?php if ( $view_settings['dps']['enable_dependency'] != 'enable' ) { echo ' style="display:none"'; } ?>>
								<p>
									<?php _e('Choose if you want to hide or disable irrelevant options for inputs:', 'wpv-views'); ?>
								</p>
								<table class="widefat">
									<thead>
									<tr>
										<th>
											<?php _e('Input type', 'wpv-views'); ?>
										</th>
										<th>
											<?php _e('Disable / Hide', 'wpv-views'); ?>
										</th>
									</tr>
									</thead>
									<tbody>
									<tr class="alternate">
										<?php
										if ( ! isset( $view_settings['dps']['empty_select'] ) ) {
											$view_settings['dps']['empty_select'] = 'hide';
										}
										?>
										<td>
											<?php _e('Select dropdown', 'wpv-views'); ?>
										</td>
										<td>
											<input type="radio" <?php checked( $view_settings['dps']['empty_select'], 'disable' ); ?> id="wpv-dps-empty-select-disable" value="disable" class="js-wpv-dps-empty-select" name="wpv-dps-empty-select" autocomplete="off" />
											<label for="wpv-dps-empty-select-disable"><?php _e('Disable', 'wpv-views'); ?></label>
											&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
											<input type="radio" <?php checked( $view_settings['dps']['empty_select'], 'hide' ); ?> id="wpv-dps-empty-select-hide" value="hide" class="js-wpv-dps-empty-select" name="wpv-dps-empty-select" autocomplete="off" />
											<label for="wpv-dps-empty-select-hide"><?php _e('Hide', 'wpv-views'); ?></label>
										</td>
									</tr>
									<tr>
										<?php
										if ( ! isset( $view_settings['dps']['empty_multi_select'] ) ) {
											$view_settings['dps']['empty_multi_select'] = 'hide';
										}
										?>
										<td>
											<?php _e('Multi-select', 'wpv-views'); ?>
										</td>
										<td>
											<input type="radio" <?php checked( $view_settings['dps']['empty_multi_select'], 'disable' ); ?> id="wpv-dps-empty-multi-select-disable" value="disable" class="js-wpv-dps-empty-multi-select" name="wpv-dps-empty-multi-select" autocomplete="off" />
											<label for="wpv-dps-empty-multi-select-disable"><?php _e('Disable', 'wpv-views'); ?></label>
											&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
											<input type="radio" <?php checked( $view_settings['dps']['empty_multi_select'], 'hide' ); ?> id="wpv-dps-empty-multi-select-hide" value="hide" class="js-wpv-dps-empty-multi-select" name="wpv-dps-empty-multi-select" autocomplete="off" />
											<label for="wpv-dps-empty-multi-select-hide"><?php _e('Hide', 'wpv-views'); ?></label>
										</td>
									</tr>
									<tr class="alternate">
										<?php
										if ( ! isset( $view_settings['dps']['empty_radios'] ) ) {
											$view_settings['dps']['empty_radios'] = 'hide';
										}
										?>
										<td>
											<?php _e('Radio inputs', 'wpv-views'); ?>
										</td>
										<td>
											<input type="radio" <?php checked( $view_settings['dps']['empty_radios'], 'disable' ); ?> id="wpv-dps-empty-radios-disable" value="disable" class="js-wpv-dps-empty-radios" name="wpv-dps-empty-radios" autocomplete="off" />
											<label for="wpv-dps-empty-radios-disable"><?php _e('Disable', 'wpv-views'); ?></label>
											&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
											<input type="radio" <?php checked( $view_settings['dps']['empty_radios'], 'hide' ); ?> id="wpv-dps-empty-radios-hide" value="hide" class="js-wpv-dps-empty-radios" name="wpv-dps-empty-radios" autocomplete="off" />
											<label for="wpv-dps-empty-radios-hide"><?php _e('Hide', 'wpv-views'); ?></label>
										</td>
									</tr>
									<tr>
										<?php
										if ( ! isset( $view_settings['dps']['empty_checkboxes'] ) ) {
											$view_settings['dps']['empty_checkboxes'] = 'hide';
										}
										?>
										<td>
											<?php _e('Checkboxes', 'wpv-views'); ?>
										</td>
										<td>
											<input type="radio" <?php checked( $view_settings['dps']['empty_checkboxes'], 'disable' ); ?> id="wpv-dps-empty-checkboxes-disable" value="disable" class="js-wpv-dps-empty-checkboxes" name="wpv-dps-empty-checkboxes" autocomplete="off" />
											<label for="wpv-dps-empty-checkboxes-disable"><?php _e('Disable', 'wpv-views'); ?></label>
											&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
											<input type="radio" <?php checked( $view_settings['dps']['empty_checkboxes'], 'hide' ); ?> id="wpv-dps-empty-checkboxes-hide" value="hide" class="js-wpv-dps-empty-checkboxes" name="wpv-dps-empty-checkboxes" autocomplete="off" />
											<label for="wpv-dps-empty-checkboxes-hide"><?php _e('Hide', 'wpv-views'); ?></label>
										</td>
									</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div><!-- end .js-wpv-dps-settings -->
			<span class="update-action-wrap auto-update js-wpv-update-action-wrap">
				<span class="js-wpv-message-container"></span>
				<input type="hidden" data-success="<?php echo esc_attr( __('Updated', 'wpv-views') ); ?>" data-unsaved="<?php echo esc_attr( __('Not saved', 'wpv-views') ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_filter_dps_nonce' ); ?>" class="js-wpv-filter-dps-update">
			</span>
		</div>
		<?php
	}

	/**
	 * Function to add the parametric search buttons to the selected editor
	 *
	 * @param string    $editor_id      The id of the editor where we want to include the parametric search buttons.
	 * @param array     $view_settings  The View settings array.
	 * @param string    $listing        The View listing type.
	 * @param string    $purpose        The purpose of the View.
	 *
	 * @since unknown
	 * @since 2.4.1     Added 3 more argument in order to support the new workflow in the case of parametric search
	 *                  View purpose.
	 */
	function wpv_add_parametric_search_buttons_to_editor( $editor_id, $view_settings = array(), $listing = 'posts', $purpose = 'full' ) {

		/**
		 * Filter to determine whether the filter editor content matches the default or not.
		 *
		 * @param array     $view_settings  The View settings.
		 * @param integer   $view_id        The View id (optional).
		 *
		 * @since 2.4.1
		 */
		$has_default_filter_output = apply_filters( 'wpv_filter_wpv_has_default_filter_editor_content', false, $view_settings );

		?>
		<li>
			<button class="button-secondary js-code-editor-toolbar-button js-wpv-parametric-search-filter-create" data-editor="<?php echo esc_attr( $editor_id ); ?>">
				<i class="icon-create fa fa-filter fa fa-parametric_filter_create"></i><?php _e( 'New filter', 'wpv-views' ); ?>
			</button>
		</li>

		<?php
		if ( $listing == 'posts' && $purpose == 'parametric' && $has_default_filter_output ) {
			?>
			<li>
				<a href="#" class="js-wpv-filter-editor-unlock" style="display:inline-block;height:28px;line-height:26px;">
					<?php echo esc_html( __( 'Unlock editor', 'wpv-views' ) ); ?>
				</a>
			</li>
			<?php
		}
		?>

		<li>
			<button class="button-secondary js-code-editor-toolbar-button js-wpv-parametric-search-filter-edit" data-editor="<?php echo esc_attr( $editor_id ); ?>">
				<i class="icon-edit fa fa-pencil-square-o fa fa-parametric_filter_edit"></i><?php _e( 'Edit filter', 'wpv-views' ); ?>
			</button>
		</li>
		<li>
			<button class="button-secondary js-code-editor-toolbar-button js-wpv-parametric-search-text-filter-manage" data-editor="<?php echo esc_attr( $editor_id ); ?>">
				<i class="icon-search fa fa-search"></i><?php _e( 'Text search', 'wpv-views' ); ?>
				<i class="icon-bookmark fa fa-bookmark flow-complete js-parametric-search-text-filter-button-complete" style="display:none"></i>
				<i class="icon-bookmark fa fa-bookmark flow-warning js-parametric-search-text-filter-button-filter-missing" style="display:none"></i>
			</button>
		</li>
		<li>
			<button class="button-secondary js-code-editor-toolbar-button js-wpv-parametric-search-submit-add" data-editor="<?php echo esc_attr( $editor_id ); ?>">
				<i class="icon-forward fa fa-forward"></i><?php _e( 'Submit button', 'wpv-views' ); ?>
				<i class="icon-bookmark fa fa-bookmark flow-complete js-wpv-parametric-search-submit-button-complete" style="display:none"></i>
				<i class="icon-bookmark fa fa-bookmark flow-warning js-wpv-parametric-search-submit-button-incomplete" style="display:none"></i>
				<i class="icon-bookmark fa fa-bookmark flow-info js-wpv-parametric-search-submit-button-irrelevant" style="display:none"></i>
			</button>
		</li>
		<li>
			<button class="button-secondary js-code-editor-toolbar-button js-wpv-parametric-search-reset-add" data-editor="<?php echo esc_attr( $editor_id ); ?>">
				<i class="icon-recycle fa fa-recycle"></i><?php _e( 'Reset button', 'wpv-views' ); ?>
				<i class="icon-bookmark fa fa-bookmark flow-complete js-wpv-parametric-search-reset-button-complete" style="display:none"></i>
			</button>
		</li>
		<li>
			<button class="button-secondary js-code-editor-toolbar-button js-wpv-parametric-search-spinner-add" data-editor="<?php echo esc_attr( $editor_id ); ?>">
				<i class="icon-spinner fa fa-spinner"></i><?php _e( 'Spinner graphics', 'wpv-views' ); ?>
				<i class="icon-bookmark fa fa-bookmark flow-complete js-wpv-parametric-search-spinner-button-complete" style="display:none"></i>
			</button>
		</li>
		<?php
	}


	function wpv_parametric_search_filter_create_dialog() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_GET["id"] )
			|| ! is_numeric( $_GET["id"] )
			|| intval( $_GET['id'] ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		$view_id = (int) $_GET["id"];
		$view_settings	= get_post_meta( $view_id, '_wpv_settings', true );
		$view_settings = apply_filters( 'wpv_filter_object_settings_for_fake_url_query_filters', $view_settings );
		$view_query_type = apply_filters( 'wpv_filter_wpv_get_query_type', 'posts', $view_id );

		$custom_search_shortcodes = apply_filters( 'wpv_filter_wpv_get_form_filters_shortcodes', array() );
		$custom_search_filters = array();

		foreach ( $custom_search_shortcodes as $search_shortcode_key => $search_shortcode_data ) {
			if ( $search_shortcode_data['query_type_target'] != $view_query_type ) {
				return;
			}
			if ( isset( $search_shortcode_data['custom_search_filter_subgroups'] ) ) {
				foreach( $search_shortcode_data['custom_search_filter_subgroups'] as $search_shortcode_data_subgroup ) {
					if (
						isset( $search_shortcode_data_subgroup['custom_search_filter_group'] )
						&& isset( $search_shortcode_data_subgroup['custom_search_filter_items'] )
					) {
						if ( ! isset( $custom_search_filters[ $search_shortcode_data_subgroup['custom_search_filter_group'] ] ) ) {
							$custom_search_filters[ $search_shortcode_data_subgroup['custom_search_filter_group'] ] = array();
						}
						foreach ( $search_shortcode_data_subgroup['custom_search_filter_items'] as $search_shortcode_data_item ) {
							$custom_search_filters[ $search_shortcode_data_subgroup['custom_search_filter_group'] ][] = array(
								'shortcode'		=> $search_shortcode_key,
								'name'			=> $search_shortcode_data_item['name'],
								'params'		=> $search_shortcode_data_item['params'],
								'present'		=> $search_shortcode_data_item['present']
							);
						}
					}
				}
			} else if (
				isset( $search_shortcode_data['custom_search_filter_group'] )
				&& isset( $search_shortcode_data['custom_search_filter_items'] )
			) {
				if ( ! isset( $custom_search_filters[ $search_shortcode_data['custom_search_filter_group'] ] ) ) {
					$custom_search_filters[ $search_shortcode_data['custom_search_filter_group'] ] = array();
				}
				foreach ( $search_shortcode_data['custom_search_filter_items'] as $search_shortcode_data_item ) {
					$custom_search_filters[ $search_shortcode_data['custom_search_filter_group'] ][] = array(
						'shortcode'		=> $search_shortcode_key,
						'name'			=> $search_shortcode_data_item['name'],
						'params'		=> $search_shortcode_data_item['params'],
						'present'		=> $search_shortcode_data_item['present']

					);
				}
			}
		}
		ob_start();
		?>
		<div class="wpv-dialog wpv-dialog-parametric-search-filter-dialog">
			<div class="searchbar">
				<label for="searchbar-input-for-parametric-search"><?php echo __( 'Search:', 'wpv-views' ); ?></label>
				<input id="searchbar-input-for-parametric-search" class="search_field" onkeyup="wpv_on_search_filter(this)" type="text" />
			</div>
			<?php
			foreach ( $custom_search_filters as $filter_group => $filter_items ) {
				echo '<div class="group">';
				echo '<h4 class="group-title">' . esc_html( $filter_group ) . '</h4>';
				foreach ( $filter_items as $filter_item_key => $filter_item_data ) {
					echo '<button class="item button button-small js-wpv-parametric-search-filter-item-dialog"'
					     . ' onclick="WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: \'' . esc_js( $filter_item_data['shortcode'] ) . '\', title: \'' . esc_js( $filter_item_data['name'] ) . '\', params: ' . esc_attr( json_encode( $filter_item_data['params'] ) ) . ' }); return false;"'
					     . ' ' . disabled( \WPV_Editor_Query_Filter::is_filter_in_use( $view_settings, $filter_item_data['present'] ), true, false )
					     . ' style="margin:5px 5px 0 0;font-size:11px;"'
					     . '>'
					     . esc_html( $filter_item_data['name'] )
					     . '</button>';
				}
				echo '</div>';
			}
			echo '<div class="group js-wpv-parametric-search-filter-group-native-postmeta">';
			echo '<h4 class="group-title">' . __( 'Post fields', 'wpv-views' ) . '</h4>';
			echo '<button class="item button button-small js-wpv-parametric-search-filter-load-group-native-postmeta">'
			     . __( 'Load non-Types custom fields', 'wpv-views' )
			     . '</button>';
			echo '</div>';
			?>
		</div>
		<?php
		$dialog = ob_get_clean();
		$data = array(
			'dialog' => $dialog
		);
		wp_send_json_success( $data );
	}



}

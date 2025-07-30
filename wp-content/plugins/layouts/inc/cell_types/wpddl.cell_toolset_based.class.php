<?php
abstract class Layouts_toolset_based_cell{
	const POSTS_PER_PAGE = 20;
	protected $cell_type;

	public function __construct( ) {

	}

	public function get_cell_type(){
		return $this->cell_type;
	}

	abstract protected function cell_edit_script();

	protected function front_end_dialog() {
		$url = $this->get_edit_link();

		if ( '' === $url ) {
			return '';
		} else {
			$cell_data = ( object ) $this->get_toolset_cell_data( $this->get_cell_type() );
			$cell_name = property_exists( $cell_data, 'nice_name' ) ? $cell_data->nice_name : '';
			ob_start();
			?>
			<div class="ddl-popup-tab">
			<div class="post-content-cell-button-wrap">
				<a href="<?php echo $url; ?>" target="_blank" class="js-toolset-resource-link notarget"><span
						class="button button-primary btn-large button-large large">
                <?php _e( 'Edit %NAME%', 'ddl-layouts' ); ?>
            </span></a></div>
			<div class="ddl-fields-description-wrap">
				<p class="ddl-fields-description">
				<?php printf( __( 'You can edit a %s only in the admin. Once you are done editing, save and close the window to return here.', 'ddl-layouts' ), $cell_name );?>
			</p></div></div>
			<?php
			return ob_get_clean();
		}

		return '';
	}

	abstract protected function back_end_dialog();

	public function cell_dialogs_callback(){
		if( is_admin() ){
			return $this->back_end_dialog();
		} else {
			return $this->front_end_dialog();
		}
	}

	public function element_name($param) {
		// returns the name of the input element used in the dialog
		return 'ddl-layout-' . $param;
	}

	public static function toolset_cells_options(){
		$SPECIAL_CELLS_OPTIONS = array(
			'views-content-grid-cell' => array(
				'field' => "ddl_layout_view_id",
				'url' => admin_url( 'admin.php?page=views-editor&view_id=%POST_ID%' ),
				'nice_name' => __('View', 'ddl-layouts')
			),
			'cell-content-template' => array(
				'field' => "ddl_view_template_id",
				'url' => admin_url( 'admin.php?page=ct-editor&ct_id=%POST_ID%&action=edit' ),
				'nice_name' => __('Content Template', 'ddl-layouts')
			),
			'post-loop-views-cell' => array(
				'field' => "ddl_layout_view_id",
				'url' => admin_url( 'admin.php?page=view-archives-editor&view_id=%POST_ID%' ),
				'nice_name' => __('WordPress Archive', 'ddl-layouts')
			),
			'cred-cell' => array(
				'field' => "ddl_layout_cred_id",
				'url' => admin_url( 'post.php?post=%POST_ID%&action=edit' ),
				'nice_name' => __('Post Form', 'ddl-layouts')
			),
			'cred-user-cell' => array(
				'field' => "ddl_layout_cred_user_id",
				'url' => admin_url( 'post.php?post=%POST_ID%&action=edit' ),
				'nice_name' => __('User Form', 'ddl-layouts')
			),
			'ddl-container' => null,
			'child-layout' => array(
				'field' => "parent",
				'url' => admin_url( 'admin.php?page=dd_layouts_edit&layout_id=%POST_ID%&action=edit' ),
				'nice_name' => __('Child Layout', 'ddl-layouts')
			),
			'ddl_missing_cell_type' => null
		);

		return $SPECIAL_CELLS_OPTIONS;
	}

	public function get_toolset_cell_data( $type ){
		$options = self::toolset_cells_options();
		return isset( $options[$type] ) ? $options[$type] : '';
	}

	private function get_edit_link(){
		$type = $this->get_cell_type();
		$data = $this->get_toolset_cell_data($type);
		if( $data && isset($data['url']) ){
			return $data['url'];
		}
		return '';
	}

}

class Layouts_views_based_cell extends Layouts_toolset_based_cell {

	protected function back_end_dialog() {
		global $WP_Views;
		ob_start();

		?>
		<div class="ddl-form">
			<?php
			echo wp_nonce_field('ddl_layout_view_nonce', 'ddl_layout_view_nonce', true, false);
			if ( class_exists('WP_Views') ) {
				if ( defined('WPV_VERSION') ) {
					if ( version_compare( WPV_VERSION, '1.6.1', '<=' ) ) {
						?>
						<input type="hidden" value="0" class="js-views-content-is_views_installed" />
						<input type="hidden" value="0" class="js-views-content-is_views_embedded" />
						<div class="toolset-alert toolset-alert-info">
							<p>
								<i class="icon-views-logo ont-color-orange ont-icon-24"></i>
								<?php _e('This cell requires the Toolset Views plugin above version 1.6.1 to create custom content-driven cells.', 'ddl-layouts'); ?>



								&nbsp;&nbsp;
								<a class="fieldset-inputs" href="https://toolset.com/home/views-create-elegant-displays-for-your-content/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts" target="_blank">
									<?php _e('About Views', 'ddl-layouts');?>
								</a>

							</p>
						</div>
						<?php
					} else {
						?>
						<input type="hidden" value="1" class="js-views-content-is_views_installed" />
						<?php
						if ( $WP_Views->is_embedded() ) {
							?>
							<input type="hidden" value="1" class="js-views-content-is_views_embedded" />
							<?php
						} else {
							?>
							<input type="hidden" value="0" class="js-views-content-is_views_embedded" />
							<?php
						}
						if ( version_compare( WPV_VERSION, '1.7', '>=' ) ) {
							?>
							<input type="hidden" value="1" class="js-views-content-is_views_above_oneseven" />
							<?php
						} else {
							?>
							<input type="hidden" value="0" class="js-views-content-is_views_above_oneseven" />
							<?php
						}
						?>

						<fieldset class="js-view-result-missing" style="display:none; margin-top:-20px">
							<div class="fields-group">
								<div class="toolset-alert toolset-alert-warning from-bot-10">
									<p><i class="icon-warning-sign fa fa-exclamation-triangle"></i> <?php _e('This layout has a search form, but is missing the results.', 'ddl-layouts'); ?></p>
									</div>
										<ul>
											<li>
												<label class="radio">
													<input type="radio" class="js-ddl-complete-search" value="complete" data-cell-name-text="<?php _e('%CELL_NAME% search results', 'ddl-layouts'); ?>" />
													<?php _e('Complete the custom search setup by inserting a cell for the results', 'ddl-layouts');?>
												</label>
											</li>
											<li>
												<label class="radio">
													<input type="radio" class="js-ddl-different-view" value="cancel" />
													<?php _e('Insert a different View, not related to the custom search results', 'ddl-layouts');?>
												</label>
											</li>
										</ul>
									</div>
						</fieldset>


						<fieldset class="js-view-result-ok">
							<legend><?php _e('View:', 'ddl-layouts'); ?></legend>
							<div class="fields-group">
								<?php if ( $WP_Views->is_embedded() ) { ?>
									<p style="display: none;">
										<label class="radio">
											<input type="radio" name="view-grid-view-action" class="js-ddl-views-grid-create" value="new_layout" />
											<?php _e('Create new View', 'ddl-layouts');?>
										</label>
									</p>
									<p style="display:none;">
										<label class="radio">
											<input type="radio" name="view-grid-view-action" class="js-ddl-views-grid-existing" value="existing_layout" checked="checked" />
											<?php _e('Use an existing View', 'ddl-layouts');?>
										</label>
									</p>
								<?php } else { ?>
									<p>
										<label class="radio">
											<input type="radio" name="view-grid-view-action" class="js-ddl-views-dialog-mode js-ddl-views-grid-create" <?php checked( get_ddl_field('ddl_layout_view_id'), '' ); ?> value="new_layout" />
											<?php _e('Create new View', 'ddl-layouts');?>
										</label>
										<label class="radio">
											<?php $checked = ( get_ddl_field('ddl_layout_view_id') != '' ) ? ' checked="checked" ' : '';?>
											<input type="radio" name="view-grid-view-action" class="js-ddl-views-dialog-mode js-ddl-views-grid-existing" value="existing_layout" <?php echo $checked?> />
											<?php _e('Use an existing View', 'ddl-layouts');?>
										</label>
									</p>
								<?php }
								$hidden = '';
								if ( get_ddl_field('ddl_layout_view_id') == '' ) {
									$hidden = ' style="display:none;" ';
								}
								?>
								<p class="js-ddl-select-existing-view"<?php echo $hidden; ?>>
									<?php
									$views_objects = apply_filters( 'wpv_get_available_views', array() );
									$views_as_options = '';

									$view_query_types = array( 'posts', 'taxonomy', 'users' );
									$wpv_total_views = 0;
									foreach ( $view_query_types as $view_type ) {
										if (
											isset( $views_objects[ $view_type ] )
											&& is_array( $views_objects[ $view_type ] )
										) {
											$wpv_total_views += count( $views_objects[ $view_type ] );
											foreach ( $views_objects[ $view_type ] as $view_candidate ) {
												$views_as_options .= '<option data-id="' . esc_attr( $view_candidate->ID ) .'" value="' . esc_attr( $view_candidate->ID ) . '" data-mode="normal">' . esc_attr( $view_candidate->post_title ) . '</option>';
											}
										}
									}

									$wpv_total_archives = 0;
									if (
										isset( $views_objects['archive'] )
										&& is_array( $views_objects['archive'] )
									) {
										$wpv_total_archives = count( $views_objects['archive'] );
										foreach ( $views_objects['archive'] as $view_candidate ) {
											$views_as_options .= '<option data-id="' . esc_attr( $view_candidate->ID ) .'" value="' . esc_attr( $view_candidate->ID ) . '" data-mode="archive">' . esc_attr( $view_candidate->post_title ) . '</option>';
										}
									}
									?>
									<input type="hidden" value="<?php echo esc_attr( $wpv_total_archives ); ?>" class="js-wpv-total-archives" />
									<input type="hidden" value="<?php echo esc_attr( $wpv_total_views ); ?>" class="js-wpv-total-views" />
									<?php
									if ( ! empty( $views_as_options ) ) {
									?>
									<select name="<?php the_ddl_name_attr('ddl_layout_view_id'); ?>" class="ddl-view-select js-ddl-view-select">
										<option value="" data-mode="both"><?php _e('--- Select View ---','ddl-layouts');?></option>
										<?php echo $views_as_options; ?>
									</select>
									<?php
									if ( $WP_Views->is_embedded() ) {
									?>
								<div class="toolset-alert toolset-alert-info">
									<?php _e('You are using the embedded version of Views. Install and activate the full version of Views and you will be able to create custom content-driven grids.', 'ddl-layouts'); ?>

									&nbsp;&nbsp;
									<a class="fieldset-inputs" href="https://toolset.com/home/views-create-elegant-displays-for-your-content/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts" target="_blank">
										<?php _e('About Views', 'ddl-layouts');?>
									</a>
								</div>
							<?php
							}
							} else {
								if ( $WP_Views->is_embedded() ) {
									?>
									<div class="toolset-alert toolset-alert-info js-data-embedded_no_views_at_all" data-embedded_no_views_at_all="yes">
										<?php _e('You are using the embedded version of the Toolset Views plugin and there are no Views available.', 'ddl-layouts'); ?>
										<!--<br />
                                            <?php _e('You can download pre-built modules using the Toolset Module Manager plugin.', 'ddl-layouts'); ?>
                                            <br />
                                            <br />
                                            <?php if (defined( 'MODMAN_CAPABILITY' )) { ?>
                                                <a class="fieldset-inputs button button-primary-toolset" href="<?php echo admin_url('admin.php?page=ModuleManager_Modules&amp;tab=library&amp;module_cat=layouts'); ?>" target="_blank">
                                                    <i class="icon-download fa fa-download-alt"></i> <?php _e('Download Modules', 'ddl-layouts');?>
                                                </a>
                                            <?php } else { ?>
                                                <a class="fieldset-inputs button button-primary-toolset" href="https://toolset.com/home/module-manager/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts" target="_blank">
                                                    <?php _e('Get Toolset Module Manager', 'ddl-layouts');?>
                                                </a>
                                            <?php } ?>
                                            -->
									</div>
									<?php
								} else {
									?>
									<select name="<?php the_ddl_name_attr('ddl_layout_view_id'); ?>" class="ddl-view-select js-ddl-view-select" style="display:none;">
										<option value="" data-mode="both"><?php _e('None','ddl-layouts');?></option>
									</select>
									<?php /*<div class="toolset-alert toolset-alert-info">
                                                                                            <?php _e('There are no Views available.', 'ddl-layouts'); ?>
                                                                                            <br />
                                                                                            <?php _e('You can create one or download pre-built modules using the Module Manager plugin.', 'ddl-layouts'); ?>
                                                                                            <br />
                                                                                            <br />
                                                                                            <?php if (defined( 'MODMAN_CAPABILITY' )) { ?>
                                                                                                    <a class="fieldset-inputs button button-primary-toolset" href="<?php echo admin_url('admin.php?page=ModuleManager_Modules&amp;tab=library&amp;module_cat=layouts'); ?>" target="_blank">
                                                                                                            <i class="icon-download fa fa-download-alt"></i> <?php _e('Download Modules', 'ddl-layouts');?>
                                                                                                    </a>
                                                                                            <?php } else { ?>
                                                                                                    <a class="fieldset-inputs button button-primary-toolset" href="https://toolset.com/home/module-manager/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts" target="_blank">
                                                                                                            <?php _e('Get Module Manager plugin', 'ddl-layouts');?>
                                                                                                    </a>
                                                                                            <?php } ?>
                                                                                    </div>
                                                                                    <?php
                                                                                    */
								}
							}
							?>
								<div class="toolset-alert toolset-alert-info js-no-views-message" style="display: none;">
									<span class="js-no-views-message-views" style="display: none;"><?php _e('There are no Views available.', 'ddl-layouts'); ?></span>
									<span class="js-no-views-message-archives" style="display: none;"><?php _e('There are no Archive Views available.', 'ddl-layouts'); ?></span>
									<!--<br />
                                        <?php _e('You can create one or download pre-built modules using the Toolset Module Manager plugin.', 'ddl-layouts'); ?>
                                        <br />
                                        <br />
                                        <?php if (defined( 'MODMAN_CAPABILITY' )) { ?>
                                            <a class="fieldset-inputs button button-primary-toolset" href="<?php echo admin_url('admin.php?page=ModuleManager_Modules&amp;tab=library&amp;module_cat=layouts'); ?>" target="_blank">
                                                <i class="icon-download fa fa-download-alt"></i> <?php _e('Download Modules', 'ddl-layouts');?>
                                            </a>
                                        <?php } else { ?>
                                            <a class="fieldset-inputs button button-primary-toolset" href="https://toolset.com/home/module-manager/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts" target="_blank">
                                                <?php _e('Get Toolset Module Manager', 'ddl-layouts');?>
                                            </a>
                                        <?php } ?>
                                        -->
								</div>
								</p>
							</div>
						</fieldset>
						<?php
						$hidden = '';
						if ( $WP_Views->is_embedded() ) {
							$hidden = ' style="display:none;" ';
						}
						?>
						<fieldset class="js-ddl-set-view-purpose"<?php echo $hidden; ?>>
							<legend><?php _e('View purpose:', 'ddl-layouts'); ?></legend>
							<div class="fields-group ddl-form-indent" style="background:#f0f0f0;padding:1px 10px 10px">
								<ul>
									<li>
										<label class="radio">
											<input checked="checked" type="radio" name="view_purpose" class="js-view-purpose" id="view_purpose_all" value="all" />
											<?php _e('Display all results','ddl-layouts'); ?>
										</label class="radio">
										<p class="desc cleared"><?php _e('The View will output all the results returned from the query section.', 'ddl-layouts'); ?></p>
									</li>
									<li>
										<label class="radio">
											<input type="radio" name="view_purpose" class="js-view-purpose" id="view_purpose_pagination" value="pagination" />
											<?php _e('Display the results with pagination','ddl-layouts'); ?>
										</label>
										<p class="desc cleared"><?php _e('The View will display the query results in pages.', 'ddl-layouts'); ?></p>
									</li>
									<li>
										<label class="radio">
											<input type="radio" name="view_purpose" class="js-view-purpose" id="view_purpose_slider" value="slider" />
											<?php _e('Display the results as a slider','ddl-layouts'); ?>
										</label>
										<p class="desc cleared"><?php _e('The View will display the query results as slides.', 'ddl-layouts'); ?></p>
									</li>
									<li>
										<label class="radio">
											<input type="radio" name="view_purpose" class="js-view-purpose" id="view_purpose_parametric" value="parametric" />
											<?php _e('Display the results as a custom search','ddl-layouts'); ?>
										</label>
										<p class="desc cleared"><?php _e('Visitors will be able to search through your content using different search criteria.', 'ddl-layouts'); ?></p>
									</li>
									<li>
										<label class="radio">
											<input type="radio" name="view_purpose" class="js-view-purpose" id="view_purpose_full" value="full"/>
											<?php _e('Full custom display mode','ddl-layouts'); ?>
										</label>
										<p class="desc cleared"><?php _e('See all the View controls open and set up things manually.', 'ddl-layouts'); ?></p>
									</li>
								</ul>
							</div>
						</fieldset>
						<fieldset class="js-ddl-view-main-buttons">

							<button data-close="no" class="button button-primary js-ddl-create-edit-view ddl-toolset-cell-button">
								<?php _e('Create', 'ddl-layouts'); ?>
							</button>
						</fieldset>
						<fieldset class="js-wpv-settings-views-layouts-parametric-extra" style="display:none">
							<div id="views-layouts-parametric-div"
							     class="wpv-setting js-wpv-setting"
							     data-notice-1="<?php _e('Since you are only displaying the %NNN% in this cell, the %MMM% section is disabled. A custom search should have the %NNN% and %MMM%. To display the %MMM% you need to:','ddl-layouts'); ?>"
							     data-notice-2="<?php _e('Create a different Layout cell and display this View.','ddl-layouts'); ?>"
							     data-notice-3="<?php _e('Choose to display the %MMM%','ddl-layouts'); ?>"
							     data-notice-form="<?php _e('search form','ddl-layouts'); ?>"
							     data-notice-results="<?php _e('search results','ddl-layouts'); ?>"
							>
								<h3><?php _e('What do you want to display in this cell?', 'ddl-layouts'); ?></h3>
								<ul>
									<li>
										<input type="radio" id="wpv-ddl-parametric-mode-full" class="js-wpv-ddl-parametric-mode" name="<?php the_ddl_name_attr('parametric_mode'); ?>" value="full" />
										<label for="wpv-ddl-parametric-mode-full"><?php _e('The search form and the results', 'ddl-layouts'); ?></label>
									</li>
									<li>
										<input type="radio" id="wpv-ddl-parametric-mode-form" class="js-wpv-ddl-parametric-mode" name="<?php the_ddl_name_attr('parametric_mode'); ?>" value="form" />
										<label for="wpv-ddl-parametric-mode-form"><?php _e('Only the search form', 'ddl-layouts'); ?></label>
										<div class="js-wpv-ddl-parametric-mode-form-settings wpv-advanced-setting" style="margin:10px 0 10px 20px;">
											<p>
												<?php _e( 'Where do you want to display the results?', 'ddl-layouts' ); ?>
											</p>
											<ul>
												<li>
													<input id="wpv-filter-form-target-self" value="self" type="radio" name="<?php the_ddl_name_attr('parametric_mode_target'); ?>" class="js-wpv-ddl-parametric-target" />
													<label for="wpv-filter-form-target-self"><?php _e('In other place on this same page', 'ddl-layouts'); ?></label>
												</li>
												<li>
													<input id="wpv-filter-form-target-other" value="other" type="radio" name="<?php the_ddl_name_attr('parametric_mode_target'); ?>" class="js-wpv-ddl-parametric-target" />
													<label for="wpv-filter-form-target-other"><?php _e('On another page', 'ddl-layouts'); ?></label>
													<span class="toolset-alert" id="wpv-ddl-target-other-forbidden" style="display:none;">
																												<?php _e( 'If you want to display the results on a different page, the View form must contain a search button', 'ddl-layouts' ); ?>
																										</span>
												</li>
											</ul>
											<div class="js-wpv-ddl-parametric-target-other-div" style="margin:0 20px 10px;">
												<p>
													<label for="wpv-ddl-parametric-mode-form-target-title"><?php _e('Target page to show the results:', 'ddl-layouts'); ?></label>
													<input type="text" id="wpv-ddl-parametric-mode-form-target-title" name="<?php the_ddl_name_attr('parametric_target_title'); ?>" class="widefat js-wpv-widget-form-target-suggest-title" placeholder="<?php echo esc_attr( __( 'Please type', 'ddl-layouts' ) ); ?>" />
													<input type="hidden" id="wpv-ddl-parametric-mode-form-target-id" name="<?php the_ddl_name_attr('parametric_target_id'); ?>" class="widefat js-wpv-widget-form-target-id" />
												</p>
												<div class="js-wpv-check-target-setup-box" style="display:none;">
													<?php _e( 'Be sure to complete the setup:', 'ddl-layouts' ); ?><br />
													<a href="#" target="_blank" class="button-primary js-wpv-ddl-insert-view-form-target-set-existing-link" data-editurl="<?php echo admin_url( 'post.php' ); ?>?post="><?php _e( 'Add the search results to this page', 'ddl-layouts' ); ?></a>
													<a href="#" class="button-secondary js-wpv-ddl-discard-target-setup-link"><?php _e( 'Not now', 'ddl-layouts' ); ?></a>
												</div>
											</div>
										</div>
									</li>
									<li>
										<input type="radio" id="wpv-ddl-parametric-mode-results" class="js-wpv-ddl-parametric-mode" name="<?php the_ddl_name_attr('parametric_mode'); ?>" value="results" />
										<label for="wpv-ddl-parametric-mode-results"><?php _e('Only the results', 'ddl-layouts'); ?></label>
									</li>
								</ul>
							</div>
						</fieldset>
						<?php
					}
				} else {
					?>
					<input type="hidden" value="0" class="js-views-content-is_views_installed" />
					<div class="toolset-alert toolset-alert-info">
						<p>
							<i class="icon-views-logo ont-color-orange ont-icon-24"></i>
							<?php _e('This cell requires the Toolset Views plugin. Install and activate Views and you will be able to create custom content-driven cells.', 'ddl-layouts'); ?>

							&nbsp;&nbsp;
							<a class="fieldset-inputs" href="https://toolset.com/home/views-create-elegant-displays-for-your-content/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts" target="_blank">
								<?php _e('About Views', 'ddl-layouts');?>
							</a>
						</p>
					</div>
					<?php
				}
			} else {
				?>
				<input type="hidden" value="0" class="js-views-content-is_views_installed" />
				<div class="toolset-alert toolset-alert-info">
					<p>
						<i class="icon-views-logo ont-color-orange ont-icon-24"></i>
						<?php _e('This cell requires the Toolset Views plugin. Install and activate Views and you will be able to create custom content-driven cells.', 'ddl-layouts'); ?>

						&nbsp;&nbsp;
						<a class="fieldset-inputs" href="https://toolset.com/home/views-create-elegant-displays-for-your-content/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts" target="_blank">
							<?php _e('About Views', 'ddl-layouts');?>
						</a>
					</p>
				</div>
				<?php
			}
			?>
			<div class="js-views-content-grid-help">
				<?php ddl_add_help_link_to_dialog(WPDLL_VIEWS_CONTENT_GRID_CELL, __('Learn about the Views cell', 'ddl-layouts')); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();

	}

	protected function cell_edit_script() {
		return null;
	}

}

class  Layouts_wordpress_archives_cell extends Layouts_views_based_cell{
		protected $cell_type = 'post-loop-views-cell';
}

<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

WPV_Editor_Pagination::on_load();

/**
* WPV_Editor_Pagination
*
* Manage the pagination settings when editing a View or WPA, from the Screen Options to the section itself.
*
* @since unknown
* @since 2.2		Deprecate the slideright and slidedown effects, and replace with sliderightforward and slidedownforward.
*/

class WPV_Editor_Pagination {

	static $pagination_ajax_effects		= array();
	static $pagination_rollover_effects	= array();
	static $pagination_posts_per_page	= array();

	static $site_url;
	static $site_url_force_non_ssl;

	static function on_load() {
		// Register the section in the screen options of the editor pages
		add_filter( 'wpv_screen_options_editor_section_filter',		array( 'WPV_Editor_Pagination', 'wpv_screen_options_pagination' ), 10 );
		add_filter( 'wpv_screen_options_wpa_editor_section_filter',	array( 'WPV_Editor_Pagination', 'wpv_screen_options_wpa_pagination' ), 10 );
		// Register the section in the editor pages
		add_action( 'wpv_action_view_editor_section_filter',		array( 'WPV_Editor_Pagination', 'wpv_add_view_pagination' ), 10, 2 );
		add_action( 'wpv_action_wpa_editor_section_filter',			array( 'WPV_Editor_Pagination', 'wpv_add_archive_pagination' ), 10, 3 );
		// AJAX management
		add_action( 'wp_ajax_wpv_update_pagination',				array( 'WPV_Editor_Pagination', 'wpv_update_pagination_callback' ) );
		add_action( 'wp_ajax_wpv_update_archive_pagination',		array( 'WPV_Editor_Pagination', 'wpv_update_pagination_callback' ) );
		// Init properties
		self::$pagination_ajax_effects = array(
			'fade'		=> __('Fade', 'wpv-views'),
			'slideh'	=> __('Slide horizontally', 'wpv-views'),
			'slidev'	=> __('Slide vertically', 'wpv-views'),
			'infinite'	=> __( 'Infinite scrolling', 'wpv-views' )
		);
		self::$pagination_rollover_effects = array(
			// @note slideright and slidedown were deprecated in 2.2
			'fade'					=> __('Fade', 'wpv-views'),
			'slideleft'				=> __('Slide Left', 'wpv-views'),
			'sliderightforward'		=> __('Slide Right', 'wpv-views'),
			'slideup'				=> __('Slide Up', 'wpv-views'),
			'slidedownforward'		=> __('Slide Down', 'wpv-views'),
			//'slideright'			=> __('Slide Right (backwards)', 'wpv-views'),
			//'slidedown'				=> __('Slide Down (backwards)', 'wpv-views'),
		);
		$posts_per_page_options = array();
		for ( $index = 1; $index < 51; $index++ ) {
			$posts_per_page_options[ $index ] = $index;
		}
		self::$pagination_posts_per_page = $posts_per_page_options;

		self::$site_url = get_site_url();
		self::$site_url_force_non_ssl = get_site_url( null, '', 'http' );
	}

	static function wpv_screen_options_pagination( $sections ) {
		$sections['pagination'] = array(
			'name'		=> __( 'Pagination and Sliders Settings', 'wpv-views' ),
			'disabled'	=> false,
		);
		return $sections;
	}

	static function wpv_screen_options_wpa_pagination( $sections ) {
		$sections['pagination'] = array(
			'name'		=> __( 'Pagination Settings', 'wpv-views' ),
			'disabled'	=> true,
		);
		return $sections;
	}

	static function adjust_spinner_image_on_ssl( $spinner_image ) {
		if ( ! is_ssl() ) {
			return $spinner_image;
		}
		if ( 0 !== strpos( $spinner_image, self::$site_url_force_non_ssl ) ) {
			return $spinner_image;
		}

		return set_url_scheme( $spinner_image );
	}

	static function wpv_add_view_pagination( $view_settings, $view_id ) {
		$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'pagination_and_sliders_settings' );

		$ajax_effects			= apply_filters( 'wpv_filter_wpv_pagination_ajax_effects', self::$pagination_ajax_effects, $view_id );
		$rollover_effects		= apply_filters( 'wpv_filter_wpv_pagination_rollover_effects', self::$pagination_rollover_effects, $view_id );

		// Backwards compatibility < 2.2
		if ( $view_settings['pagination']['effect'] == 'slideright' ) {
			$rollover_effects['slideright'] = __( 'Slide Right (backwards)', 'wpv-views' );
		} else if ( $view_settings['pagination']['effect'] == 'slidedown' ) {
			$rollover_effects['slidedown'] = __( 'Slide Down (backwards)', 'wpv-views' );
		}

		$posts_per_page_options	= apply_filters( 'wpv_filter_extend_posts_per_page_options', self::$pagination_posts_per_page );

		if (
			! isset( $view_settings['pagination']['posts_per_page'] )
			|| (
				! apply_filters( 'wpv_filter_framework_has_valid_framework', false )
				&& strpos( $view_settings['pagination']['posts_per_page'], 'FRAME_KEY' ) !== false
			)
		) {
			$view_settings['pagination']['posts_per_page'] = 10;
		}

		$hide = '';
		if (
			isset( $view_settings['sections-show-hide'] )
			&& isset( $view_settings['sections-show-hide']['pagination'] )
			&& 'off' == $view_settings['sections-show-hide']['pagination']
		) {
			$hide = ' hidden';
		}
		?>
		<div class="wpv-setting-container wpv-settings-pagination js-wpv-settings-pagination<?php echo $hide; ?>">
			<div class="wpv-settings-header">
				<h2>
					<?php _e( 'Pagination and Sliders Settings', 'wpv-views' ) ?>
					<i class="icon-question-sign fa fa-question-circle js-display-tooltip"
						data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>"
						data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
					</i>
				</h2>
			</div>
			<div class="wpv-setting js-wpv-setting">
				<ul>
					<li>
						<input id="wpv-pagination-type-disabled" class="js-wpv-pagination-type" type="radio" name="wpv-pagination-type" value="disabled" <?php checked( $view_settings['pagination']['type'] == 'disabled' ); ?> autocomplete="off" />
						<label for="wpv-pagination-type-disabled"><strong><?php _e( 'No pagination', 'wpv-views' ); ?></strong></label>
						<span class="helper-text"><?php _e( 'Display all query results at once.', 'wpv-views' ); ?></span>
					</li>
					<li>
						<input id="wpv-pagination-type-paged" class="js-wpv-pagination-type" type="radio" name="wpv-pagination-type" value="paged" <?php checked( $view_settings['pagination']['type'] == 'paged' ); ?> autocomplete="off" />
						<label for="wpv-pagination-type-paged"><strong><?php _e( 'Pagination enabled with manual transition and page reload', 'wpv-views' ); ?></strong></label>
						<span class="helper-text"><?php _e( 'Display the query results in pages, which visitors will switch.', 'wpv-views' ); ?></span>
					</li>
					<li>
						<input id="wpv-pagination-type-ajaxed" class="js-wpv-pagination-type" type="radio" name="wpv-pagination-type" value="ajaxed" <?php checked( $view_settings['pagination']['type'] == 'ajaxed' ); ?> autocomplete="off" />
						<label for="wpv-pagination-type-ajaxed"><strong><?php _e( 'Pagination enabled with manual transition and AJAX', 'wpv-views' ); ?></strong></label>
						<span class="helper-text"><?php _e( 'Display the query in pages, which visitors will switch without reloading the page.', 'wpv-views' ); ?></span>
					</li>
					<li>
						<input id="wpv-pagination-type-rollover" class="js-wpv-pagination-type" type="radio" name="wpv-pagination-type" value="rollover" <?php checked( $view_settings['pagination']['type'] == 'rollover' ); ?> autocomplete="off" />
						<label for="wpv-pagination-type-rollover"><strong><?php _e( 'Pagination enabled with automatic AJAX transition', 'wpv-views' ); ?></strong></label>
						<span class="helper-text"><?php _e( 'Display the query in pages, which will paginate automatically without reloading the page.', 'wpv-views' ); ?></span>
					</li>
				</ul>
				<div class="wpv-advanced-setting js-wpv-pagination-advanced-settings<?php if ( $view_settings['pagination']['type'] == 'disabled' ) { echo ' hidden'; } ?>">
					<h3><?php _e( 'Pagination options', 'wpv-views' ); ?></h3>
					<ul>
						<li>
							<?php
							$select_structure = '<select class="js-wpv-pagination-posts-per-page" autocomplete="off">';
							foreach ( $posts_per_page_options as $index => $value ) {
								$select_structure .= '<option value="' .  esc_attr( $index ) . '" ' . selected( $view_settings['pagination']['posts_per_page'], $index, false ) . '>' . $value . '</option>';
							}

							$select_structure .= '</select>';
							echo sprintf(
								'<label>%1$s %2$s %3$s</label>',
								__( 'Display', 'wpv-views' ),
								$select_structure,
								__( 'items per page', 'wpv-views' )
							);
							?>
						</li>
						<li class="js-wpv-rollover-pagination-speed-container<?php if ( $view_settings['pagination']['type'] != 'rollover' ) { echo ' hidden'; } ?>">
							<?php
							if ( ! isset( $view_settings['pagination']['speed'] ) ) {
								$view_settings['pagination']['speed'] = '5';
							}
							?>
							<label><?php esc_html_e( 'Show each page for:', 'wpv-views' )?></label>
							<input type="number"
							       name="wpv-pagination-speed"
							       class="js-wpv-rollover-pagination-speed"
							       size="5"
							       value="<?php echo esc_attr( $view_settings['pagination']['speed'] ); ?>"
							       autocomplete="off"
							/> &nbsp;<?php esc_html_e( 'seconds', 'wpv-views' );?>
						</li>
                        <li class="js-wpv-rollover-pagination-pause-on-hover-container<?php if ( $view_settings['pagination']['type'] != 'rollover' ) { echo ' hidden'; } ?>">
	                        <?php $checked = ( isset( $view_settings['pagination']['pause_on_hover'] ) && $view_settings['pagination']['pause_on_hover'] ) ? ' checked="checked"' : '';?>
                            <label>
                                <input type="checkbox" class="js-wpv-ajax-pagination-pause-on-hover" value="on"<?php echo $checked; ?> autocomplete="off" />
		                        <?php _e( 'Pause pagination transition on mouse hover', 'wpv-views' ); ?>
                            </label>
                        </li>
						<li class="js-wpv-ajax-pagination-settings-extra<?php if ( ! in_array( $view_settings['pagination']['type'], array( 'ajaxed', 'rollover' ) ) ) { echo ' hidden'; } ?>">
							<p>
								<label><?php _e('Transition effect:', 'wpv-views')?></label>
								<?php
								if ( ! isset( $view_settings['pagination']['effect'] ) ) {
									$view_settings['pagination']['effect'] = 'fade';
								}
								?>
								<select id="wpv-ajax-pagination-effect" class="js-wpv-ajax-pagination-effect<?php if ( $view_settings['pagination']['type'] == 'rollover' ) { echo ' hidden'; } ?>" autocomplete="off">
									<?php
									foreach ( $ajax_effects as $effect_slug => $effect_title ) {
										?>
										<option value="<?php echo esc_attr( $effect_slug ); ?>" <?php selected( $view_settings['pagination']['effect'], $effect_slug ); ?>><?php echo esc_html( $effect_title ); ?></option>
										<?php
									}
									?>
								</select>
								<select id="wpv-rollover-pagination-effect" class="js-wpv-rollover-pagination-effect<?php if ( $view_settings['pagination']['type'] == 'ajaxed' ) { echo ' hidden'; } ?>" autocomplete="off">
									<?php
									foreach ( $rollover_effects as $effect_slug => $effect_title ) {
										?>
										<option value="<?php echo esc_attr( $effect_slug ); ?>" <?php selected( $view_settings['pagination']['effect'], $effect_slug ); ?>><?php echo esc_html( $effect_title ); ?></option>
										<?php
									}
									?>
								</select>

								<label>
									<?php _e('with duration',  'wpv-views'); ?>
									<?php
									if ( ! isset( $view_settings['pagination']['duration'] ) ) {
										$view_settings['pagination']['duration'] = 500;
									}
									?>
									<input type="number" class="js-wpv-ajax-pagination-duration" value="<?php echo esc_attr( $view_settings['pagination']['duration'] ); ?>" size="5" autocomplete="off" />
								</label>
								<?php _e('miliseconds', 'wpv-views'); ?>
							</p>
							<p>
								<button class="js-wpv-pagination-advanced button-secondary" type="button">
									<?php _e( 'Advanced options', 'wpv-views' ); ?>
									&nbsp;<i class="fa fa-caret-down" aria-hidden="true"></i>
								</button>
							</p>
						</li>
						<li class="js-wpv-pagination-advanced-container hidden">
							<?php
							$global_enable_manage_history = apply_filters( 'wpv_filter_wpv_global_pagination_manage_history_status', true );
							if ( $global_enable_manage_history ) {
								$manage_history = ( isset( $view_settings['pagination']['manage_history'] ) && in_array( $view_settings['pagination']['manage_history'], array( 'on', 'off' ) ) ) ? $view_settings['pagination']['manage_history'] : 'on';
								?>
								<div class="js-wpv-pagination-advanced-history-management<?php if ( $view_settings['pagination']['effect'] == 'infinite' ) { echo ' hidden'; } ?>">
									<h4><?php _e( 'Browser history management', 'wpv-views' ); ?></h4>
									<label>
										<input type="checkbox" class="js-wpv-ajax-pagination-manage-history" value="on"<?php checked( $manage_history, 'on', true ); ?> autocomplete="off" />
										<?php _e('Update the URL of the page when paginating the View',  'wpv-views'); ?>
									</label>
								</div>
								<?php
							}
							?>
							<div class="js-wpv-pagination-advanced-infinite-tolerance<?php if ( $view_settings['pagination']['effect'] != 'infinite' ) { echo ' hidden'; } ?>">
								<h4><?php _e( 'Infinite scrolling tolerance', 'wpv-views' ); ?></h4>
								<label>
									<?php _e( 'Infinite scrolling tolerance, in pixels:', 'wpv-views' ); ?>
									<input type="text" class="js-wpv-ajax-pagination-tolerance" value="<?php echo ( isset( $view_settings['pagination']['tolerance'] ) ) ? $view_settings['pagination']['tolerance'] : ''; ?>" autocomplete="off" />
								</label>
							</div>
							<div class="js-wpv-pagination-advanced-cache">
								<h4><?php _e( 'Cache and preload', 'wpv-views' ); ?></h4>
								<p>
								<?php $checked = ( isset( $view_settings['pagination']['preload_images'] ) && $view_settings['pagination']['preload_images'] ) ? ' checked="checked"' : '';?>
								<label>
									<input type="checkbox" class="js-wpv-ajax-pagination-preload-images" value="on"<?php echo $checked; ?> autocomplete="off" />
									<?php _e('Preload images before transition',  'wpv-views'); ?>
								</label>
								</p>
								<p>
								<?php $checked = ( isset( $view_settings['pagination']['cache_pages'] ) && $view_settings['pagination']['cache_pages'] ) ? ' checked="checked"' : '';?>
								<label>
									<input type="checkbox" class="js-wpv-ajax-pagination-cache-pages" value="on"<?php echo $checked; ?> autocomplete="off" />
									<?php _e('Cache pages',  'wpv-views'); ?>
								</label>
								</p>
								<p>
								<?php $checked = ( isset( $view_settings['pagination']['preload_pages'] ) && $view_settings['pagination']['preload_pages'] ) ? ' checked="checked"' : '';?>
								<label>
									<input type="checkbox" class="js-wpv-ajax-pagination-preload-pages" value="on"<?php echo $checked; ?> autocomplete="off" />
									<?php _e('Pre-load the next and previous pages - avoids loading delays when users move between pages',  'wpv-views'); ?>
								</label>
								</p>
								<p>
								<label><?php _e('Pages to pre-load: ',  'wpv-views'); ?>
									<select class="js-wpv-ajax-pagination-preload-reach" autocomplete="off">
									<?php
										if ( ! isset( $view_settings['pagination']['pre_reach'] ) ) {
											$view_settings['pagination']['pre_reach'] = 1;
										}
										for ( $i = 1; $i < 20; $i++ ) {
											?>
											<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $view_settings['pagination']['pre_reach'], $i ); ?>><?php echo $i; ?></option>
											<?php
										}
										?>
									</select>
								</label>
								</p>
							</div>
							<div class="js-wpv-pagination-advanced-spinner">
								<h4><?php _e('Spinners',  'wpv-views'); ?></h4>
								<?php
								$spinner = isset( $view_settings['pagination']['spinner'] ) ? $view_settings['pagination']['spinner'] : 'builtin';
								switch ( $spinner ) {
									case 'default':
									case 'builtin':
										$spinner = 'builtin';
										break;
									case 'uploaded':
										$spinner = 'uploaded';
										break;
									case 'disabled':
									case 'no':
									default:
										$spinner = 'disabled';
										break;
								}
								?>
								<ul>
									<li>
										<label>
											<input type="radio" class="js-wpv-pagination-spinner" name="wpv-pagination-spinner" value="builtin"<?php checked( $spinner, 'builtin', true ); ?> autocomplete="off" />
											<?php _e('Spinner graphics from Views', 'wpv-views'); ?>
										</label>
										<ul id="wpv-spinner-builtin" class="wpv-spinner-selection wpv-mightlong-list wpv-setting-extra js-wpv-pagination-spinner-builtin<?php if ( $spinner != 'builtin' ) { echo ' hidden'; } ?>">
										<?php
										if ( isset( $view_settings['pagination']['spinner_image'] ) ) {
											$spinner_image = self::adjust_spinner_image_on_ssl( $view_settings['pagination']['spinner_image'] );
										} else {
											$spinner_image = '';
										}
										$available_spinners = array();
										$available_spinners = apply_filters( 'wpv_admin_available_spinners', $available_spinners );
										foreach ( $available_spinners as $av_spinner ) {
										?>
											<li>
												<label>
													<input type="radio" class="js-wpv-pagination-builtin-spinner-image" name="wpv-pagination-spinner-builtin-option" value="<?php echo esc_url( $av_spinner['url'] ); ?>" <?php checked( $spinner_image, $av_spinner['url'] ); ?> autocomplete="off" />
													<img src="<?php echo esc_url( $av_spinner['url'] ); ?>" title="<?php echo esc_attr( $av_spinner['title'] ); ?>" />
												</label>
											</li>
										<?php } ?>
										</ul>
									</li>
									<li>
										<?php
										$spinner_image_uploaded = '';
										if (
											isset( $view_settings['pagination']['spinner_image_uploaded'] )
											&& ! empty( $view_settings['pagination']['spinner_image_uploaded'] )
										) {
											$spinner_image_uploaded = self::adjust_spinner_image_on_ssl( $view_settings['pagination']['spinner_image_uploaded'] );
										}
										?>
										<label>
											<input type="radio" class="js-wpv-pagination-spinner" name="wpv-pagination-spinner" value="uploaded"<?php echo checked( $spinner, 'uploaded', true ); ?> autocomplete="off" />
											<?php _e('My custom spinner graphics', 'wpv-views'); ?>
										</label>
										<p id="wpv-spinner-uploaded" class="wpv-spinner-selection wpv-setting-extra js-wpv-pagination-spinner-uploaded<?php if ( $spinner != 'uploaded' ) { echo ' hidden'; } ?>">
											<input id="wpv-pagination-spinner-image" class="large-text js-wpv-pagination-spinner-image" type="text" value="<?php echo esc_url( $spinner_image_uploaded ); ?>" autocomplete="off" />
											<button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager" data-content="wpv-pagination-spinner-image" data-id="<?php echo esc_attr( $view_id ); ?>"><?php _e('Upload Image', 'wpv-views'); ?></button>

											<img id="wpv-pagination-spinner-image-preview" class="js-wpv-pagination-spinner-image-preview" src="<?php echo esc_url( $spinner_image_uploaded ); ?>" height="16"<?php if ( empty( $spinner_image_uploaded ) ) { echo ' style="display:none;"'; } ?> />
										</p>
									</li>
									<li>
									<?php $checked = ( isset( $view_settings['pagination']['spinner'] ) &&  $view_settings['pagination']['spinner'] == 'no' ) ? ' checked="checked"' : '';?>
										<label>
											<input type="radio" class="js-wpv-pagination-spinner" name="wpv-pagination-spinner" value="disabled"<?php echo checked( $spinner, 'disabled', true ); ?> autocomplete="off" />
											<?php _e('No spinner graphics', 'wpv-views'); ?>
										</label>
									</li>
								</ul>
							</div>
							<div>
								<h4><?php _e('Callback function', 'wpv-views'); ?></h4>
								<p><?php _e('Javascript function to execute after the pagination transition has been completed:', 'wpv-views'); ?></p>
								<ul>
									<li>
										<input id="wpv-pagination-callback-next" class="js-wpv-pagination-callback-next" type="text" name="wpv-pagination-callback-next" value="<?php echo isset( $view_settings['pagination']['callback_next'] ) ? esc_attr( $view_settings['pagination']['callback_next'] ) : ''; ?>" autocomplete="off" />
									</li>
								</ul>
							</div>


						</li>
					</ul>
				</div>
				<div class="js-wpv-toolset-messages"></div>
			</div>
			<span class="update-button-wrap auto-update js-wpv-update-action-wrap">
				<span class="js-wpv-message-container"></span>
				<input type="hidden" data-success="<?php echo esc_attr( __('Updated', 'wpv-views') ); ?>" data-unsaved="<?php echo esc_attr( __('Not saved', 'wpv-views') ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_pagination_nonce' ); ?>" class="js-wpv-pagination-update" />
			</span>

		</div>

	<?php
	}

	static function wpv_add_archive_pagination( $view_settings, $view_id, $user_id ) {
		$section_help_pointer	= WPV_Admin_Messages::edit_section_help_pointer( 'archive_pagination' );

		$ajax_effects			= apply_filters( 'wpv_filter_wpv_pagination_ajax_effects', self::$pagination_ajax_effects, $view_id );
		$posts_per_page_options	= apply_filters( 'wpv_filter_extend_posts_per_page_options', self::$pagination_posts_per_page );

		if (
			! isset( $view_settings['pagination']['posts_per_page'] )
			|| (
				! apply_filters( 'wpv_filter_framework_has_valid_framework', false )
				&& strpos( $view_settings['pagination']['posts_per_page'], 'FRAME_KEY' ) !== false
			)
		) {
			$view_settings['pagination']['posts_per_page'] = 'default';
		}
		?>
		<div class="wpv-setting-container wpv-settings-pagination js-wpv-settings-pagination">
			<div class="wpv-settings-header">
				<h2>
					<?php _e( 'Pagination Settings', 'wpv-views' ) ?>
					<i class="icon-question-sign fa fa-question-circle js-display-tooltip"
						data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>"
						data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
					</i>
				</h2>
			</div>
			<div class="wpv-setting js-wpv-setting">
				<ul>
					<li>
						<input id="wpv-archive-pagination-type-disabled" class="js-wpv-archive-pagination-type" type="radio" name="wpv-archive-pagination-type" value="disabled" <?php checked( $view_settings['pagination']['type'] == 'disabled' ); ?> autocomplete="off" />
						<label for="wpv-archive-pagination-type-disabled"><strong><?php _e( 'No pagination', 'wpv-views' ); ?></strong></label>
						<span class="helper-text"><?php _e( 'Display all items in this archive at once.', 'wpv-views' ); ?></span>
					</li>
					<li>
						<input id="wpv-archive-pagination-type-paged" class="js-wpv-archive-pagination-type" type="radio" name="wpv-archive-pagination-type" value="paged" <?php checked( $view_settings['pagination']['type'] == 'paged' ); ?> autocomplete="off" />
						<label for="wpv-archive-pagination-type-paged"><strong><?php _e( 'Pagination enabled with manual transition and page reload', 'wpv-views' ); ?></strong></label>
						<span class="helper-text"><?php _e( 'Display this archive in pages, which visitors will switch.', 'wpv-views' ); ?></span>
					</li>
					<li>
						<input id="wpv-archive-pagination-type-ajaxed" class="js-wpv-archive-pagination-type" type="radio" name="wpv-archive-pagination-type" value="ajaxed" <?php checked( $view_settings['pagination']['type'] == 'ajaxed' ); ?> autocomplete="off" />
						<label for="wpv-archive-pagination-type-ajaxed"><strong><?php _e( 'Pagination enabled with manual transition and AJAX', 'wpv-views' ); ?></strong></label>
						<span class="helper-text"><?php _e( 'Display this archive in pages, which visitors will switch without reloading the page.', 'wpv-views' ); ?></span>
					</li>
				</ul>
				<div class="wpv-advanced-setting js-wpv-archive-pagination-advanced-settings<?php if ( $view_settings['pagination']['type'] == 'disabled' ) { echo ' hidden'; } ?>">
					<h3><?php _e( 'Pagination options', 'wpv-views' ); ?></h3>
					<ul>
						<li>
							<?php
							$select_structure = '<select class="js-wpv-archive-pagination-posts-per-page" autocomplete="off">'
								. '<option value="default" ' . selected( $view_settings['pagination']['posts_per_page'], 'default' , false ) . '>' . sprintf( __( '%d (default pagination size)', 'wpv-views' ), get_option('posts_per_page') ) . '</option>';
							foreach ( $posts_per_page_options as $index => $value ) {
								$select_structure .= '<option value="' .  esc_attr( $index ) . '" ' . selected( $view_settings['pagination']['posts_per_page'], $index, false ) . '>' . $value . '</option>';
							}

							$select_structure .= '</select>';
							echo sprintf(
								'<label>%1$s %2$s %3$s</label>',
								__( 'Display', 'wpv-views' ),
								$select_structure,
								__( 'items per page', 'wpv-views' )
							);
							?>
							<span class="helper-text js-wpv-archive-pagination-posts-per-page-default" style="margin:0;<?php if ( $view_settings['pagination']['posts_per_page'] != 'default' ) { echo ' display:none;'; } ?>">
								<?php
								echo sprintf(
									__( 'You can change the default pagination size for the site in the %1$sreading options %2$s.', 'wpv-views' ),
									'<a href="' . admin_url( 'options-reading.php' ) . '">',
									'</a>'
								);
								?>
							</span>
						</li>
						<li class="js-wpv-archive-ajax-pagination-settings-extra<?php if ( $view_settings['pagination']['type'] != 'ajaxed' ) { echo ' hidden'; } ?>">
							<p>
								<label><?php _e('Transition effect:', 'wpv-views')?></label>
								<select id="wpv-archive-ajax-pagination-effect" class="js-wpv-archive-ajax-pagination-effect" autocomplete="off">
									<?php
									if ( ! isset( $view_settings['pagination']['effect'] ) ) {
										$view_settings['pagination']['effect'] = 'fade';
									}
									foreach ( $ajax_effects as $effect_slug => $effect_title ) {
										?>
										<option value="<?php echo esc_attr( $effect_slug ); ?>" <?php selected( $view_settings['pagination']['effect'], $effect_slug ); ?>><?php echo esc_html( $effect_title ); ?></option>
										<?php
									}
									?>
								</select>

								<label>
									<?php _e('with duration',  'wpv-views'); ?>
									<?php
									if ( ! isset( $view_settings['pagination']['duration'] ) ) {
										$view_settings['pagination']['duration'] = 500;
									}
									?>
									<input type="text" class="js-wpv-archive-ajax-pagination-duration" value="<?php echo esc_attr( $view_settings['pagination']['duration'] ); ?>" size="5" autocomplete="off" />
								</label>
								<?php _e('miliseconds', 'wpv-views'); ?>
							</p>
							<p>
								<button class="js-wpv-archive-pagination-advanced button-secondary" type="button">
									<?php _e( 'Advanced options', 'wpv-views' ); ?>
									&nbsp;<i class="fa fa-caret-down" aria-hidden="true"></i>
								</button>
							</p>
						</li>
						<li class="js-wpv-archive-pagination-advanced-container hidden">
							<?php
							$global_enable_manage_history = apply_filters( 'wpv_filter_wpv_global_pagination_manage_history_status', true );
							if ( $global_enable_manage_history ) {
								$manage_history = ( isset( $view_settings['pagination']['manage_history'] ) && in_array( $view_settings['pagination']['manage_history'], array( 'on', 'off' ) ) ) ? $view_settings['pagination']['manage_history'] : 'on';
								?>
								<div class="js-wpv-archive-pagination-advanced-history-management<?php if ( $view_settings['pagination']['effect'] == 'infinite' ) { echo ' hidden'; } ?>">
									<h4><?php _e( 'Browser history management', 'wpv-views' ); ?></h4>
									<label>
										<input type="checkbox" class="js-wpv-archive-ajax-pagination-manage-history" value="on"<?php checked( $manage_history, 'on', true ); ?> autocomplete="off" />
										<?php _e('Update the URL of the page when paginating the WordPress Archive',  'wpv-views'); ?>
									</label>
								</div>
								<?php
							}
							?>
							<div class="js-wpv-archive-pagination-advanced-infinite-tolerance<?php if ( $view_settings['pagination']['effect'] != 'infinite' ) { echo ' hidden'; } ?>">
								<h4><?php _e( 'Infinite scrolling tolerance', 'wpv-views' ); ?></h4>
								<label>
									<?php _e( 'Infinite scrolling tolerance, in pixels:', 'wpv-views' ); ?>
									<input type="text" class="js-wpv-archive-ajax-pagination-tolerance" value="<?php echo ( isset( $view_settings['pagination']['tolerance'] ) ) ? $view_settings['pagination']['tolerance'] : ''; ?>" autocomplete="off" />
								</label>
							</div>
							<div class="js-wpv-archive-pagination-advanced-cache">
								<h4><?php _e( 'Cache and preload', 'wpv-views' ); ?></h4>
								<p>
								<?php $checked = ( isset( $view_settings['pagination']['preload_images'] ) && $view_settings['pagination']['preload_images'] ) ? ' checked="checked"' : '';?>
								<label>
									<input type="checkbox" class="js-wpv-archive-ajax-pagination-preload-images" value="on"<?php echo $checked; ?> autocomplete="off" />
									<?php _e('Preload images before transition',  'wpv-views'); ?>
								</label>
								</p>
								<p>
								<?php $checked = ( isset( $view_settings['pagination']['cache_pages'] ) && $view_settings['pagination']['cache_pages'] ) ? ' checked="checked"' : '';?>
								<label>
									<input type="checkbox" class="js-wpv-archive-ajax-pagination-cache-pages" value="on"<?php echo $checked; ?> autocomplete="off" />
									<?php _e('Cache pages',  'wpv-views'); ?>
								</label>
								</p>
								<p>
								<?php $checked = ( isset( $view_settings['pagination']['preload_pages'] ) && $view_settings['pagination']['preload_pages'] ) ? ' checked="checked"' : '';?>
								<label>
									<input type="checkbox" class="js-wpv-archive-ajax-pagination-preload-pages" value="on"<?php echo $checked; ?> autocomplete="off" />
									<?php _e('Pre-load the next and previous pages - avoids loading delays when users move between pages',  'wpv-views'); ?>
								</label>
								</p>
								<p>
								<label><?php _e('Pages to pre-load: ',  'wpv-views'); ?>
									<select class="js-wpv-archive-ajax-pagination-preload-reach" autocomplete="off">
									<?php
										if ( ! isset( $view_settings['pagination']['pre_reach'] ) ) {
											$view_settings['pagination']['pre_reach'] = 1;
										}
										for ( $i = 1; $i < 20; $i++ ) {
											?>
											<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $view_settings['pagination']['pre_reach'], $i ); ?>><?php echo $i; ?></option>
											<?php
										}
										?>
									</select>
								</label>
								</p>
							</div>
							<div class="js-wpv-archive-pagination-advanced-spinner">
								<h4><?php _e('Spinners',  'wpv-views'); ?></h4>
								<?php
								$spinner = isset( $view_settings['pagination']['spinner'] ) ? $view_settings['pagination']['spinner'] : 'builtin';
								switch ( $spinner ) {
									case 'default':
									case 'builtin':
										$spinner = 'builtin';
										break;
									case 'uploaded':
										$spinner = 'uploaded';
										break;
									case 'disabled':
									case 'no':
									default:
										$spinner = 'disabled';
										break;
								}
								?>
								<ul>
									<li>
										<label>
											<input type="radio" class="js-wpv-archive-pagination-spinner" name="wpv-archive-pagination-spinner" value="builtin"<?php checked( $spinner, 'builtin', true ); ?> autocomplete="off" />
											<?php _e('Spinner graphics from Views', 'wpv-views'); ?>
										</label>
										<ul id="wpv-spinner-builtin" class="wpv-spinner-selection wpv-mightlong-list wpv-setting-extra js-wpv-archive-pagination-spinner-builtin<?php if ( $spinner != 'builtin' ) { echo ' hidden'; } ?>">
										<?php
										if ( isset( $view_settings['pagination']['spinner_image'] ) ) {
											$spinner_image = self::adjust_spinner_image_on_ssl( $view_settings['pagination']['spinner_image'] );
										} else {
											$spinner_image = '';
										}
										$available_spinners = array();
										$available_spinners = apply_filters( 'wpv_admin_available_spinners', $available_spinners );
										foreach ( $available_spinners as $av_spinner ) {
										?>
											<li>
												<label>
													<input type="radio" class="js-wpv-archive-pagination-builtin-spinner-image" name="wpv-archive-pagination-spinner-builtin-option" value="<?php echo esc_url( $av_spinner['url'] ); ?>" <?php checked( $spinner_image, $av_spinner['url'] ); ?> autocomplete="off" />
													<img style="background-color: #FFFFFF;" src="<?php echo esc_url( $av_spinner['url'] ); ?>" title="<?php echo esc_attr( $av_spinner['title'] ); ?>" />
												</label>
											</li>
										<?php } ?>
										</ul>
									</li>
									<li>
										<?php
										$spinner_image_uploaded = '';
										if (
											isset( $view_settings['pagination']['spinner_image_uploaded'] )
											&& ! empty( $view_settings['pagination']['spinner_image_uploaded'] )
										) {
											$spinner_image_uploaded = self::adjust_spinner_image_on_ssl( $view_settings['pagination']['spinner_image_uploaded'] );
										}
										?>
										<label>
											<input type="radio" class="js-wpv-archive-pagination-spinner" name="wpv-archive-pagination-spinner" value="uploaded"<?php echo checked( $spinner, 'uploaded', true ); ?> autocomplete="off" />
											<?php _e('My custom spinner graphics', 'wpv-views'); ?>
										</label>
										<p id="wpv-archive-spinner-uploaded" class="wpv-spinner-selection wpv-setting-extra js-wpv-archive-pagination-spinner-uploaded<?php if ( $spinner != 'uploaded' ) { echo ' hidden'; } ?>">
											<input id="wpv-archive-pagination-uploaded-spinner-image" class="large-text js-wpv-archive-pagination-uploaded-spinner-image" type="text" value="<?php echo esc_url( $spinner_image_uploaded ); ?>" autocomplete="off" />
											<button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager" data-content="wpv-archive-pagination-uploaded-spinner-image" data-id="<?php echo esc_attr( $view_id ); ?>"><?php _e('Upload Image', 'wpv-views'); ?></button>

											<img id="wpv-archive-pagination-uploaded-spinner-image-preview" class="js-wpv-archive-pagination-uploaded-spinner-image-preview" src="<?php echo esc_url( $spinner_image_uploaded ); ?>" height="16"<?php if ( empty( $spinner_image_uploaded ) ) { echo ' style="display:none;"'; } ?> />
										</p>
									</li>
									<li>
									<?php $checked = ( isset( $view_settings['pagination']['spinner'] ) &&  $view_settings['pagination']['spinner'] == 'no' ) ? ' checked="checked"' : '';?>
										<label>
											<input type="radio" class="js-wpv-archive-pagination-spinner" name="wpv-archive-pagination-spinner" value="disabled"<?php echo checked( $spinner, 'disabled', true ); ?> autocomplete="off" />
											<?php _e('No spinner graphics', 'wpv-views'); ?>
										</label>
									</li>
								</ul>
							</div>
						</li>
					</ul>
				</div>
				<div class="js-wpv-toolset-messages"></div>
			</div>
			<span class="update-action-wrap auto-update js-wpv-update-action-wrap">
				<span class="js-wpv-message-container"></span>
				<input type="hidden" data-success="<?php echo esc_attr( __( 'Updated', 'wpv-views' ) ); ?>" data-unsaved="<?php echo esc_attr( __( 'Not saved', 'wpv-views' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpv_view_pagination_nonce' ) ); ?>" class="js-wpv-pagination-update" />
			</span>
		</div>
		<?php
	}

	static function wpv_update_pagination_callback() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_pagination_nonce' )
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

		$view_array		= get_post_meta( $_POST["id"], '_wpv_settings', true );
		$settings		= isset( $_POST['settings'] ) ? $_POST['settings'] : array();
		$new_settings	= array();
		$changed		= false;

		$ajax_effects			= apply_filters( 'wpv_filter_wpv_pagination_ajax_effects', self::$pagination_ajax_effects, $_POST["id"] );
		$rollover_effects		= apply_filters( 'wpv_filter_wpv_pagination_rollover_effects', self::$pagination_rollover_effects, $_POST["id"] );

		$new_settings['type']			= ( isset( $settings['type'] ) && in_array( $settings['type'], array( 'disabled', 'paged', 'ajaxed', 'rollover' ) ) ) ? esc_attr( $settings['type'] ) : 'paged';
		$new_settings['posts_per_page']	= isset( $settings['posts_per_page'] ) ? esc_attr( $settings['posts_per_page'] ) : 'default';
		$new_settings['effect']			= ( isset( $settings['effect'] ) && ( isset( $ajax_effects[ $settings['effect'] ] ) || isset( $rollover_effects[ $settings['effect'] ] ) ) ) ? esc_attr( $settings['effect'] ) : 'fade';
		$new_settings['duration']		= isset( $settings['duration'] ) ? (int) $settings['duration'] : 500;
		$new_settings['pause_on_hover']	= ( isset( $settings['pause_on_hover'] ) && $settings['pause_on_hover'] == 'true' ) ? true : false;
		$new_settings['speed']			= isset( $settings['speed'] ) && 0 < (int) $settings['speed'] ? (int) $settings['speed'] : 5;
		$new_settings['manage_history']	= ( isset( $settings['manage_history'] ) && $settings['manage_history'] == 'true' ) ? 'on' : 'off';
		$new_settings['tolerance']		= isset( $settings['tolerance'] ) ? (int) $settings['tolerance'] : '';

		$new_settings['preload_images']	= ( isset( $settings['preload_images'] ) && $settings['preload_images'] != 'true' ) ? false : true;
		$new_settings['cache_pages']	= ( isset( $settings['cache_pages'] ) && $settings['cache_pages'] != 'true' ) ? false : true;
		$new_settings['preload_pages']	= ( isset( $settings['preload_pages'] ) && $settings['preload_pages'] != 'true' ) ? false : true;
		$new_settings['pre_reach']		= isset( $settings['pre_reach'] ) ? (int) $settings['pre_reach'] : 1;

		$new_settings['spinner']		= ( isset( $settings['spinner'] ) && in_array( $settings['spinner'], array( 'builtin', 'uploaded', 'disabled' ) ) ) ? esc_attr( $settings['spinner'] ) : 'builtin';
		$new_settings['spinner_image']	= isset( $settings['spinner_image'] ) ? esc_attr( $settings['spinner_image'] ) : '';
		$new_settings['spinner_image_uploaded']	= isset( $settings['spinner_image_uploaded'] ) ? esc_attr( $settings['spinner_image_uploaded'] ) : '';

		$new_settings['callback_next']	= isset( $settings['callback_next'] ) ? esc_attr( $settings['callback_next'] ) : '';

		foreach ( $new_settings as $new_settings_key => $new_settings_value ) {
			if (
				! isset( $view_array['pagination'][ $new_settings_key ] )
				|| $view_array['pagination'][ $new_settings_key ] != $new_settings_value
			) {
				$view_array['pagination'][ $new_settings_key ] = $new_settings_value;
				$changed = true;
			}
		}

		$deprecated_to_remove = array( 'posts_per_page', 'ajax_pagination', 'rollover' );

		foreach ( $deprecated_to_remove as $deprecated_key ) {
			if ( isset( $view_array[ $deprecated_key ] ) ) {
				unset( $view_array[ $deprecated_key ] );
				$changed = true;
			}
		}

		if ( isset( $view_array['pagination'][0] ) ) {
			unset( $view_array['pagination'][0] );
			$changed = true;
		}

		$deprecated_inner_to_remove = array( 'mode' );

		foreach ( $deprecated_inner_to_remove as $deprecated_key ) {
			if ( isset( $view_array['pagination'][ $deprecated_key ] ) ) {
				unset( $view_array['pagination'][ $deprecated_key ] );
				$changed = true;
			}
		}

		if ( $changed ) {
			update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
			do_action( 'wpv_action_wpv_save_item', $_POST["id"] );
		}
		$data = array(
			'id' => $_POST["id"],
			'message' => __( 'Pagination saved', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

}

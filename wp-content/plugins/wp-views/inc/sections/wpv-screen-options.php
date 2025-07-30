<?php

WPV_Editor_Screen_Options::on_load();

class WPV_Editor_Screen_Options{
	
	static function on_load() {
		// Register the section in the editor pages
		add_action( 'wpv_action_view_editor_screen_options',	array( 'WPV_Editor_Screen_Options', 'wpv_editor_view_screen_options' ), 10, 5 );
		add_action( 'wpv_action_wpa_editor_screen_options',		array( 'WPV_Editor_Screen_Options', 'wpv_editor_wpa_screen_options' ), 10, 5 );
		// AJAX management
		add_action( 'wp_ajax_wpv_save_screen_options',			array( 'WPV_Editor_Screen_Options', 'wpv_save_screen_options_callback' ) );
	}
	
	static function wpv_editor_view_screen_options( $view_settings, $view_layout_settings, $view_id, $user_id, $view ) {
		$view_settings['metasections-hep-show-hide'] = isset( $view_settings['metasections-hep-show-hide'] ) ? $view_settings['metasections-hep-show-hide'] : array();
		?>
		<div id="js-screen-meta-dup" class="metabox-prefs js-screen-meta-dup hidden">
			<div id="js-screen-options-wrap-dup">
				<h5><?php _e('Show on screen', 'wpv-views');?></h5>
				
				<!-- Purpose -->
				<?php 
				if ( 
					! isset( $view_settings['view_purpose'] ) 
					|| $view_settings['view_purpose'] == 'bootstrap-grid' // @note From 1.7, bootstrap-grid purpose is deprecated and hopefully removed, defaults to full
				) {
					$view_settings['view_purpose'] = 'full';
				}
				?>
				<div>
					<label for="wpv-view-purpose"><?php echo __('View purpose', 'wpv-views'); ?></label>
					<select id="wpv-view-purpose" class="js-wpv-purpose" autocomplete="off">
						<?php $purpose_options = array(
							'all'			=> __('Display all results', 'wpv-views'),
							'pagination'	=> __('Display the results with pagination', 'wpv-views'),
							'slider'		=> __('Display the results as a slider', 'wpv-views'),
							'parametric'	=> __('Display the results using a custom search', 'wpv-views'),
							'full'			=> __('Full custom display mode', 'wpv-views')
						);
						// Disabled options ins selector
						$disabled_items = apply_filters('wpv_views_screen_options_purpose_selector_disabled_items', array());

						foreach ( $purpose_options as $opt => $opt_name ) { ?>
							<option <?php selected( $view_settings['view_purpose'], $opt ); ?> <?php disabled( in_array( $opt, $disabled_items ), true, true );?> value="<?php echo esc_attr( $opt ); ?>"><?php echo $opt_name; ?></option>
						<?php } ?>
					</select>
					<input type="hidden" data-nonce="<?php echo wp_create_nonce( 'wpv_view_show_hide_nonce' ); ?>" class="js-wpv-show-hide-update" autocomplete="off" />
				</div>
				
				<div style="padding-bottom:1px;">
					<!-- Query section screen options -->
					<?php
					$sections = array();
					$sections = apply_filters( 'wpv_screen_options_editor_section_query', $sections );
					if ( ! empty( $sections ) ) {
					?>
					<div class="wpv-screen-options-metasection wpv-screen-options-metasection-query js-wpv-screen-options-metasection" data-metasection="wpv-query-section">
						<h6><?php _e('Query section', 'wpv-views'); ?></h6>
						<p class="js-wpv-screen-pref">
							<?php 
							$state = isset( $view_settings['metasections-hep-show-hide']['wpv-query-help'] ) ? $view_settings['metasections-hep-show-hide']['wpv-query-help'] : 'on';
							?>
							<label for="wpv-show-hide-query-help">
								<input 
									type="checkbox" 
									id="wpv-show-hide-query-help" 
									data-metasection="query" 
									class="js-wpv-screen-options-metasection-help js-wpv-show-hide-query-help" 
									value="wpv-query-help" 
									<?php checked( 'on', $state ); ?> 
									autocomplete="off" 
								/>
								<?php echo __('Display generic help for the Query section', 'wpv-views'); ?>
							</label>
						</p>
						<?php
						foreach ( $sections as $key => $values ) {
							$values['state'] = isset( $view_settings['sections-show-hide'][ $key ] ) ? $view_settings['sections-show-hide'][ $key ] : 'on';
							?>
							<span class="js-wpv-screen-pref">
								<label for="wpv-screen-option-<?php echo esc_attr( $key ); ?>">
									<input 
										type="checkbox" 
										id="wpv-screen-option-<?php echo esc_attr( $key ); ?>" 
										class="wpv-screen-options js-wpv-screen-options js-wpv-show-hide js-wpv-show-hide-<?php echo esc_attr( $key ); ?>" 
										value="<?php echo esc_attr( $key ); ?>" 
										<?php checked( 'on', $values['state'] ); ?> 
										<?php disabled( $values['disabled'] ); ?> 
										autocomplete="off" 
									/>
									<?php echo $values['name']; ?>
								</label>
							</span>
						<?php }
						?>
					</div>
					<?php 
					}
					?>
					<!-- Filter section screen options -->
					<?php
					$sections = array();
					$sections = apply_filters( 'wpv_screen_options_editor_section_filter', $sections );
					if ( !empty( $sections ) ) {
					?>
					<div class="wpv-screen-options-metasection wpv-screen-options-metasection-filter js-wpv-screen-options-metasection" data-metasection="wpv-filter-section">
						<h6><?php _e('Filter section', 'wpv-views'); ?></h6>
						<p class="js-wpv-screen-pref">
							<?php 
							$state = isset( $view_settings['metasections-hep-show-hide']['wpv-filter-help'] ) ? $view_settings['metasections-hep-show-hide']['wpv-filter-help'] : 'on';
							?>
							<label for="wpv-show-hide-filter-help">
								<input 
									type="checkbox" 
									id="wpv-show-hide-filter-help" 
									data-metasection="filter" 
									class="js-wpv-screen-options-metasection-help js-wpv-show-hide-filter-help" 
									value="wpv-filter-help" 
									<?php checked( 'on', $state ); ?> 
									autocomplete="off" 
								/>
								<?php echo __('Display generic help for the Filter section', 'wpv-views'); ?>
							</label>
						</p>
						<?php
						foreach ( $sections as $key => $values ) {
							$values['state'] = isset( $view_settings['sections-show-hide'][ $key ] ) ? $view_settings['sections-show-hide'][ $key ] : 'on';
							?>
							<span class="js-wpv-screen-pref">
								<label for="wpv-screen-option-<?php echo esc_attr( $key ); ?>">
									<input 
										type="checkbox" 
										id="wpv-screen-option-<?php echo esc_attr( $key ); ?>" 
										class="wpv-screen-options js-wpv-screen-options js-wpv-show-hide js-wpv-show-hide-<?php echo esc_attr( $key ); ?>" 
										value="<?php echo esc_attr( $key ); ?>" 
										<?php checked( 'on', $values['state'] ); ?> 
										<?php disabled( $values['disabled'] ); ?> 
										autocomplete="off" 
									/>
									<?php echo $values['name']; ?>
								</label>
							</span>
						<?php }
						?>
					</div>
					<?php 
					} 
					?>
					<!-- Layout section screen options -->
					<?php
					$sections = array();
					$sections = apply_filters( 'wpv_screen_options_editor_section_layout', $sections );
					if ( ! empty( $sections ) ) {
					?>
					<div class="wpv-screen-options-metasection wpv-screen-options-metasection-layout js-wpv-screen-options-metasection" data-metasection="wpv-layout-section">
						<h6><?php _e( 'Loop section', 'wpv-views' ); ?></h6>
						<p class="js-wpv-screen-pref">
							<?php
							$state = isset( $view_settings['metasections-hep-show-hide']['wpv-layout-help'] ) ? $view_settings['metasections-hep-show-hide']['wpv-layout-help'] : 'on';
							?>
							<label for="wpv-show-hide-layout-help">
								<input 
									type="checkbox" 
									id="wpv-show-hide-layout-help" 
									data-metasection="layout" 
									class="js-wpv-screen-options-metasection-help js-wpv-show-hide-layout-help" 
									value="wpv-layout-help" 
									<?php checked( 'on', $state ); ?> 
									autocomplete="off" 
								/>
								<?php echo __( 'Display generic help for the Loop section', 'wpv-views'); ?>
							</label>
						</p>
						<?php
						foreach ( $sections as $key => $values ) {
							$values['state'] = isset( $view_settings['sections-show-hide'][ $key ] ) ? $view_settings['sections-show-hide'][ $key ] : 'on';
							?>
							<span class="js-wpv-screen-pref">
								<label for="wpv-screen-option-<?php echo esc_attr( $key ); ?>">
									<input 
										type="checkbox" 
										id="wpv-screen-option-<?php echo esc_attr( $key ); ?>" 
										class="wpv-screen-options js-wpv-screen-options js-wpv-show-hide js-wpv-show-hide-<?php echo esc_attr( $key ); ?>" 
										value="<?php echo esc_attr( $key ); ?>" 
										<?php checked( 'on', $values['state'] ); ?> 
										<?php disabled( $values['disabled'] ); ?> 
										autocomplete="off" 
									/>
									<?php echo $values['name']; ?>
								</label>
							</span>
						<?php }
						?>
					</div>
					<?php
					}
					?>
				</div>
				<p class="js-wpv-toolset-messages"></p>
			</div>
		</div>
		<?php
	}
	
	static function wpv_editor_wpa_screen_options( $view_settings, $view_layout_settings, $view_id, $user_id, $view ) {
		$view_settings['sections-show-hide'] = isset( $view_settings['sections-show-hide'] ) ? $view_settings['sections-show-hide'] : array();
		?>
		<div id="js-screen-meta-dup" class="metabox-prefs js-screen-meta-dup hidden">
			<div id="js-screen-options-wrap-dup">
				<h5><?php _e('Show on screen', 'wpv-views');?></h5>
				
				<!-- Purpose -->
				<?php
				if ( 
					! isset( $view_settings['view_purpose'] ) 
					|| $view_settings['view_purpose'] == 'bootstrap-grid' // @note From 1.7, bootstrap-grid purpose is deprecated and hopefully removed, defaults to full
				) {
					$view_settings['view_purpose'] = 'all';
				}
				?>
				<div>
					<label for="wpv-view-purpose"><?php echo __('WordPress Archive purpose', 'wpv-views'); ?></label>
					<select id="wpv-view-purpose" class="js-wpv-purpose" autocomplete="off">
						<?php $purpose_options = array(
							'all'			=> __('Display the basic archive', 'wpv-views'),
							'parametric'	=> __('Display the archive as a custom search', 'wpv-views'),
						);
						// disabled items in selector
						$disabled_items = apply_filters('wpv_views_archive_screen_options_purpose_selector_disabled_items', array());

						foreach ( $purpose_options as $opt => $opt_name ) { ?>
							<option <?php selected( $view_settings['view_purpose'], $opt ); ?> <?php disabled( in_array( $opt, $disabled_items ), true, true );?> value="<?php echo esc_attr( $opt ); ?>"><?php echo $opt_name; ?></option>
						<?php } ?>
					</select>
				</div>
				
				<div style="padding-bottom:1px;">
					<!-- Query section screen options -->
					<?php
					$sections = apply_filters( 'wpv_screen_options_wpa_editor_section_query', array() );
					if ( ! empty( $sections ) ) {
					?>
					<div class="wpv-screen-options-metasection wpv-screen-options-metasection-query js-wpv-screen-options-metasection" data-metasection="wpv-query-section">
						<h6><?php _e('Loop selection', 'wpv-views'); ?></h6>
						<?php
						foreach ( $sections as $key => $values ) {
							$values['state'] = isset( $view_settings['sections-show-hide'][ $key ] ) ? $view_settings['sections-show-hide'][ $key ] : 'on';
							?>
							<span class="js-wpv-screen-pref">
								<label for="wpv-show-hide-<?php echo esc_attr( $key ); ?>">
									<input 
										data-section="<?php echo esc_attr( $key ); ?>" 
										type="checkbox" 
										id="wpv-show-hide-<?php echo esc_attr( $key ); ?>" 
										class="wpv-screen-options js-wpv-screen-options js-wpv-show-hide js-wpv-show-hide-<?php echo esc_attr( $key ); ?>" 
										value="<?php echo esc_attr( $key ); ?>" 
										<?php checked( 'on', $values['state'] ); ?> 
										<?php disabled( $values['disabled'] ); ?> 
										autocomplete="off" 
									/>
									<?php echo $values['name']; ?>
								</label>
							</span>
						<?php }
						?>
					</div>
					<?php } ?>
					
					<!-- Filter section screen options -->
					<?php
					$sections = apply_filters( 'wpv_screen_options_wpa_editor_section_filter', array() );
					if ( ! empty( $sections ) ) {
					?>
					<div class="wpv-screen-options-metasection wpv-screen-options-metasection-filter js-wpv-screen-options-metasection" data-metasection="wpv-filter-section">
						<h6><?php _e('Filter section', 'wpv-views'); ?></h6>
						<?php
						foreach ( $sections as $key => $values ) {
							$values['state'] = isset( $view_settings['sections-show-hide'][ $key ] ) ? $view_settings['sections-show-hide'][ $key ] : 'on';
							?>
							<span class="js-wpv-screen-pref">
								<label for="wpv-show-hide-<?php echo esc_attr( $key ); ?>">
									<input 
										data-section="<?php echo esc_attr( $key ); ?>" 
										type="checkbox" 
										id="wpv-show-hide-<?php echo esc_attr( $key ); ?>" 
										class="wpv-screen-options js-wpv-screen-options js-wpv-show-hide js-wpv-show-hide-<?php echo esc_attr( $key ); ?>" 
										value="<?php echo esc_attr( $key ); ?>" 
										<?php checked( 'on', $values['state'] ); ?> 
										<?php disabled( $values['disabled'] ); ?> 
										autocomplete="off" 
									/>
									<?php echo $values['name']; ?>
								</label>
							</span>
						<?php }
						?>
					</div>
					<?php } ?>
					
					<!-- Layout output section screen options -->
					<?php
					$sections = apply_filters( 'wpv_screen_options_wpa_editor_section_layout', array() );
					if ( ! empty( $sections ) ) {
					?>
					<div class="wpv-screen-options-metasection wpv-screen-options-metasection-layout js-wpv-screen-options-metasection" data-metasection="wpv-layout-section">
						<h6><?php _e('Output section', 'wpv-views'); ?></h6>
						<?php
						foreach ( $sections as $key => $values ) {
							$values['state'] = isset( $view_settings['sections-show-hide'][ $key ] ) ? $view_settings['sections-show-hide'][ $key ] : 'on';
							?>
							<span class="js-wpv-screen-pref">
								<label for="wpv-screen-option-<?php echo esc_attr( $key ); ?>">
									<input 
										type="checkbox" 
										id="wpv-screen-option-<?php echo esc_attr( $key ); ?>" 
										class="wpv-screen-options js-wpv-screen-options js-wpv-show-hide js-wpv-show-hide-<?php echo esc_attr( $key ); ?>" 
										value="<?php echo esc_attr( $key ); ?>" 
										<?php checked( 'on', $values['state'] ); ?> 
										<?php disabled( $values['disabled'] ); ?> 
										autocomplete="off" 
									/>
									<?php echo $values['name']; ?>
								</label>
							</span>
						<?php }
						?>
					</div>
					<?php } ?>
				
					<p class="js-wpv-toolset-messages"></p>
				
				</div>
			</div>
		</div>
		<?php
	}
	
	/*
	* Screen options save callback function.
	*
	* @todo There may be some deprecated options, e.g. the option for layout-extra in sections-show-hide. These should be
	*     deleted in a future upgrade procedure. See following links for more information:
	*     - https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/193583572/comments#comment_303063628
	*     - https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/193583488/comments
	*
	* @since unknown
	*/
	
	static function wpv_save_screen_options_callback() {
		wpv_ajax_authenticate( 'wpv_view_show_hide_nonce', array( 'parameter_source' => 'post', 'type_of_death' => 'data' ) );
		
		if (
			! isset( $_POST['id'] )
			|| ! is_numeric( $_POST['id'] )
			|| intval( $_POST['id'] ) < 1 
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$view_array = get_post_meta( $_POST['id'], '_wpv_settings', true );
		
		// Sections
		if ( isset( $_POST['visible'] ) ) {
			foreach ( $_POST['visible'] as $visible ) {
				$section = sanitize_text_field( $visible );
				$view_array['sections-show-hide'][$section] = 'on';
			}
		}
		if ( isset( $_POST['hidden'] ) ) {
			foreach ( $_POST['hidden'] as $hidden ) {
				$section = sanitize_text_field( $hidden );
				$view_array['sections-show-hide'][$section] = 'off';
			}
		}
		
		// Help boxes
		if ( isset( $_POST['help_visible'] ) ) {
			foreach ( $_POST['help_visible'] as $visible ) {
				$section = sanitize_text_field( $visible );
				$view_array['metasections-hep-show-hide'][$section] = 'on';
			}
		}
		if ( isset( $_POST['help_hidden'] ) ) {
			foreach ( $_POST['help_hidden'] as $hidden ) {
				$section = sanitize_text_field( $hidden );
				$view_array['metasections-hep-show-hide'][$section] = 'off';
			}
		}
		
		// Purpose
		if ( isset( $_POST['purpose'] ) ) {
			$view_array['view_purpose'] = sanitize_text_field( $_POST['purpose'] );
		}
		update_post_meta( $_POST['id'], '_wpv_settings', $view_array );
		do_action( 'wpv_action_wpv_save_item', $_POST['id'] );
		$data = array(
			'id' => $_POST['id'],
			'message' => __( 'Screen options saved', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}
	
}

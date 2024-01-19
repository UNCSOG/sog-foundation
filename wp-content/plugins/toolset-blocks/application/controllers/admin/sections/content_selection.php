<?php

class WPV_Section_Content_Selection {
	
	private $is_m2m_enabled = false;

	public function __construct() {
		add_filter( 'wpv_screen_options_editor_section_query', array( $this, 'register_in_screen_options' ), 10 );
		add_action( 'wpv_action_view_editor_section_query', array( $this, 'print_section' ), 10, 2 );
	}
	
	/**
	 * Register the section in the Screen Options tab.
	 *
	 * @param $sections (array) sections on the editor screen
	 *
	 * @return $sections
	 *
	 * @since unknown
	 * @since m2m Moved to a method in a proper class
	 */
	public function register_in_screen_options( $sections ) {
		$sections['content-selection'] = array(
			'name'		=> __( 'Content Selection', 'wpv-views' ),
			'disabled'	=> true,
		);
		return $sections;
	}
	
	/**
	 * Print the section.
	 *
	 * @param $view_settings
	 * @param $view_id
	 *
	 * @since unknown
	 * @since m2m Moved to a method in a proper class
	 */
	public function print_section( $view_settings, $view_id ) {
		$this->maybe_m2m_full_init();
		
		$hide = '';
		if (
			isset( $view_settings['sections-show-hide'] )
			&& isset( $view_settings['sections-show-hide']['content-selection'] )
			&& 'off' == $view_settings['sections-show-hide']['content-selection']
		) {
			$hide = ' hidden';
		}
		
		if ( ! isset( $view_settings['query_type'] ) ) {
			$view_settings['query_type'][0] = 'posts';
		}
		?>
		<div class="wpv-setting-container wpv-settings-content-selection js-wpv-no-lock js-wpv-settings-content-selection<?php echo $hide; ?>">
			<?php $this->print_section_header(); ?>
			<div class="wpv-setting js-wpv-setting">
				<?php $this->print_query_type_selector( $view_settings ); ?>
				<?php $this->print_post_options( $view_settings ); ?>
				<?php $this->print_taxonomy_options( $view_settings ); ?>
				<?php $this->print_user_options( $view_settings ); ?>
			</div>
			<span class="update-action-wrap auto-update js-wpv-content-section-action-wrap js-wpv-update-action-wrap">
				<span class="js-wpv-message-container"></span>
				<input type="hidden" data-success="<?php echo esc_attr( __('Updated', 'wpv-views') ); ?>" data-unsaved="<?php echo esc_attr( __('Not saved', 'wpv-views') ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_query_type_nonce' ); ?>" class="js-wpv-query-type-update" />
			</span>
		</div>
		<div class="toolset-alert toolset-alert-lock js-wpv-content-selection-mandatory-warning hidden">
			<p>
				<?php _e( 'You need to select what content to load with this View before you can continue designing the output.', 'wpv-views' ); ?>
			</p>
		</div>
	<?php }
	
	private function maybe_m2m_full_init() {
		$this->is_m2m_enabled = apply_filters( 'toolset_is_m2m_enabled', false );
		if ( $this->is_m2m_enabled ) {
			do_action( 'toolset_do_m2m_full_init' );
		}
	}
	
	private function print_section_header() {
		$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'content_section' );
		?>
		<div class="wpv-settings-header">
			<h2>
				<?php _e('Content Selection', 'wpv-views' ) ?>
				<i class="icon-question-sign fa fa-question-circle js-display-tooltip"
					data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>"
					data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
				</i>
			</h2>
		</div>
		<?php
	}
	
	private function print_query_type_selector( $view_settings ) {
		?>
		<p>
			<?php _e('This View will display:', 'wpv-views'); ?>
			<label for="wpv-settings-cs-query-type-posts">
				<input type="radio" style="margin-left:15px" name="_wpv_settings[query_type][]" id="wpv-settings-cs-query-type-posts" class="js-wpv-query-type" value="posts" <?php checked( $view_settings['query_type'][0], 'posts' ); ?> autocomplete="off" />
				<?php _e('Post types','wpv-views') ?>
			</label>
			<label for="wpv-settings-cs-query-type-taxonomy">
				<input type="radio" style="margin-left:15px" name="_wpv_settings[query_type][]" id="wpv-settings-cs-query-type-taxonomy" class="js-wpv-query-type" value="taxonomy"<?php checked( $view_settings['query_type'][0], 'taxonomy' ); ?> autocomplete="off" />
				<?php _e('Taxonomy','wpv-views') ?>
			</label>
			<label for="wpv-settings-cs-query-type-users">
				<input type="radio" style="margin-left:15px" name="_wpv_settings[query_type][]" id="wpv-settings-cs-query-type-users" class="js-wpv-query-type" value="users"<?php checked( $view_settings['query_type'][0], 'users' ); ?> autocomplete="off" />
				<?php _e('Users','wpv-views') ?>
			</label>
		</p>
		<?php
	}
	
	private function print_post_options( $view_settings ) {
		if ( ! isset( $view_settings['post_type'] ) ) {
			$view_settings['post_type'] = array();
		}
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		?>
		<div class="js-wpv-settings-query-type-posts wpv-settings-query-type-posts<?php echo ( $view_settings['query_type'][0] != 'posts' ) ? ' hidden' : ''; ?>">
		<ul class="wpv-advanced-setting wpv-mightlong-list">
		<?php
			foreach ( $post_types as $post_type_object ) {
			?>
				<li>
					<?php
					$checked = in_array( $post_type_object->name, $view_settings['post_type'] ) ? ' checked="checked"' : '';
					$is_hierarchical = $post_type_object->hierarchical ? 'yes' : 'no';
					$is_hierarchical = ( $post_type_object->name == 'attachment' ) ? 'maybe' : $is_hierarchical;
					?>
					<input type="checkbox" id="wpv-settings-post-type-<?php echo esc_attr( $post_type_object->name ); ?>" name="_wpv_settings[post_type][]" data-hierarchical="<?php echo esc_attr( $is_hierarchical ); ?>" class="js-wpv-query-post-type" value="<?php echo esc_attr( $post_type_object->name ); ?>"<?php echo $checked; ?> autocomplete="off" />
					<label for="wpv-settings-post-type-<?php echo esc_attr( $post_type_object->name ); ?>"><?php echo $post_type_object->labels->name ?></label>
				</li>
			<?php
			}
		?>
		</ul>
		<?php
		if ( $this->is_m2m_enabled ) {
			$rfg_post_types = get_post_types( array( Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP => true ), 'objects' );
			if ( ! empty( $rfg_post_types ) ) {
				$display_info = false;
				?>
				<h3><?php _e( 'Repeatable field groups', 'wpv-views' ); ?></h3>
				<ul class="wpv-advanced-setting wpv-mightlong-list">
				<?php
					foreach ( $rfg_post_types as $post_type_object ) {
						?>
						<li>
							<?php
							$checked = '';
							if ( in_array( $post_type_object->name, $view_settings['post_type'] ) ) {
								$checked = ' checked="checked"';
								$display_info = true;
							}
							$is_hierarchical = $post_type_object->hierarchical ? 'yes' : 'no';
							$is_hierarchical = ( $post_type_object->name == 'attachment' ) ? 'maybe' : $is_hierarchical;
							?>
							<input type="checkbox" id="wpv-settings-post-type-<?php echo esc_attr( $post_type_object->name ); ?>" name="_wpv_settings[post_type][]" data-hierarchical="<?php echo esc_attr( $is_hierarchical ); ?>" class="js-wpv-query-post-type js-wpv-query-post-type-rfg" value="<?php echo esc_attr( $post_type_object->name ); ?>"<?php echo $checked; ?> autocomplete="off" />
							<label for="wpv-settings-post-type-<?php echo esc_attr( $post_type_object->name ); ?>"><?php echo $post_type_object->labels->name ?></label>
						</li>
					<?php
				}
				?>
				</ul>
				<div class="toolset-alert toolset-alert-info js-wpv-settings-query-type-posts-rfg-info"<?php if ( ! $display_info ) { echo ' style="display:none"'; } ?>>
					<h4><i class="fa fa-info-circle fa-lg" aria-hidden="true"></i> <?php _e( 'Displaying repeatable field groups?', 'wpv-views' ); ?> </h4>
					<p>
						<?php _e( 'Repeatable field groups always belong to a given post.', 'wpv-views' ); ?>
					</p>
					<p>
						<?php _e( 'Do not forget to add a <strong>filter by post relationship or repeatable fields group owner</strong> and select the right owner for the groups that you want to display.', 'wpv-views' ); ?>
					</p>
				</div>
				<?php
			}
		}
		?>
		</div>
		<?php
	}
	
	private function print_taxonomy_options( $view_settings ) {
		if ( ! isset( $view_settings['taxonomy_type'] ) ) {
			$view_settings['taxonomy_type'] = array();
		}
		$taxonomies = get_taxonomies( '', 'objects' );
		?>
		<ul class="wpv-settings-query-type-taxonomy wpv-advanced-setting wpv-mightlong-list<?php echo ( $view_settings['query_type'][0] != 'taxonomy' ) ? ' hidden' : ''; ?>">
		<?php 
			$exclude_tax_slugs = array();
			$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
			?>
			<?php foreach ( $taxonomies as $tax_slug => $tax ) { ?>
				<?php
				if ( in_array( $tax_slug, $exclude_tax_slugs ) ) {
					continue; // Take out taxonomies that are in our compatibility black list
				}
				if ( ! $tax->show_ui ) {
					continue; // Only show taxonomies with show_ui set to TRUE
				}
				?>
				<?php
				if ( sizeof( $view_settings['taxonomy_type'] ) == 0 ) { // we need to check at least the first available taxonomy if no one is set
					$view_settings['taxonomy_type'][] = $tax->name;
				}
				$checked = in_array( $tax->name, $view_settings['taxonomy_type'] ) ? ' checked="checked"' : '';
				$is_tax_hierarchical = $tax->hierarchical ? 'yes' : 'no';
				?>
				<li>
					<input type="radio" id="wpv-settings-post-taxonomy-<?php echo esc_attr( $tax->name ); ?>" name="_wpv_settings[taxonomy_type][]" data-hierarchical="<?php echo esc_attr( $is_tax_hierarchical ); ?>" class="js-wpv-query-taxonomy-type" value="<?php echo esc_attr( $tax->name ); ?>"<?php echo $checked; ?> autocomplete="off" />
					<label for="wpv-settings-post-taxonomy-<?php echo esc_attr( $tax->name ); ?>"><?php echo $tax->labels->name ?></label>
				</li>
			<?php 
			} 
		?>
		</ul>
		<?php
	}
	
	private function print_user_options( $view_settings ) {
		if ( ! isset( $view_settings['roles_type'] ) ) {
			$view_settings['roles_type'] = array( 'administrator' );
		}
		global $wp_roles;
		?>
		<ul class="wpv-settings-query-type-users wpv-advanced-setting wpv-mightlong-list<?php echo ( $view_settings['query_type'][0] != 'users' ) ? ' hidden' : ''; ?>">
		<?php 
			foreach( $wp_roles->role_names as $role => $name ) { ?>
				<?php
					$checked = in_array( $role, $view_settings['roles_type'] ) ? ' checked="checked"' : '';

					// Offer checkbox or radio as per WP Version.
					// - checkbox: if 4.4 and above
					// - radio: if less than 4.4
					$ele_type = 'checkbox';

					global $wp_version;

					if ( version_compare( $wp_version, '4.4', '<' ) ) {
						// Offer 'radio' buttons
						$ele_type = 'radio';
					}
				?>
			<li>
				<input type="<?php echo $ele_type; ?>" id="wpv-settings-post-users-<?php echo esc_attr( $role ); ?>" name="_wpv_settings[roles_type][]" class="js-wpv-query-users-type" value="<?php echo esc_attr( $role ); ?>"<?php echo $checked; ?> autocomplete="off" />
				<label for="wpv-settings-post-users-<?php echo esc_attr( $role ); ?>"><?php echo $name; ?></label>
			</li>
			<?php } ?>
			<li>
				<?php $checked = @in_array( 'any', $view_settings['roles_type'] ) ? ' checked="checked"' : ''; ?>
				<input type="<?php echo $ele_type; ?>" id="wpv-settings-post-users-any-role" name="_wpv_settings[roles_type][]" class="js-wpv-query-users-type" value="any"<?php echo $checked; ?> autocomplete="off" />
				<label for="wpv-settings-post-users-any-role"><?php _e( 'Any role', 'wpv-views' ); ?></label>
			</li>
		</ul>
		<?php
	}
}
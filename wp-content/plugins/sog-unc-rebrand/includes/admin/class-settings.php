<?php
/**
 * Plugin settings registration and admin UI.
 *
 * @package SOGUNCRebrand
 */

declare(strict_types=1);

namespace SOG\Rebrand\Admin;

use SOG\Rebrand\Core\Yaml;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {
	/**
	 * Option name for stored plugin settings.
	 */
	public const OPTION_NAME = 'sog_unc_rebrand_settings';

	/**
	 * Register admin settings hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_post_sog_unc_rebrand_export_preset', array( $this, 'handle_export_preset' ) );
		add_action( 'admin_post_sog_unc_rebrand_import_preset', array( $this, 'handle_import_preset' ) );
		add_action( 'admin_post_sog_unc_rebrand_apply_preset', array( $this, 'handle_apply_preset' ) );
	}

	/**
	 * Register the plugin setting for the admin UI.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'sog_unc_rebrand',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_defaults(),
			)
		);
	}

	/**
	 * Register the plugin admin menu and subpages.
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		add_menu_page(
			__( 'SOG-UNC-Rebrand', 'sog-unc-rebrand' ),
			__( 'SOG Rebrand', 'sog-unc-rebrand' ),
			'manage_options',
			'sog-unc-rebrand',
			array( $this, 'render_global_page' ),
			'dashicons-admin-customizer',
			58
		);

		add_submenu_page(
			'sog-unc-rebrand',
			__( 'Global Settings', 'sog-unc-rebrand' ),
			__( 'Global Settings', 'sog-unc-rebrand' ),
			'manage_options',
			'sog-unc-rebrand',
			array( $this, 'render_global_page' )
		);

		add_submenu_page(
			'sog-unc-rebrand',
			__( 'Header Settings', 'sog-unc-rebrand' ),
			__( 'Header', 'sog-unc-rebrand' ),
			'manage_options',
			'sog-unc-rebrand-header',
			array( $this, 'render_header_page' )
		);

		add_submenu_page(
			'sog-unc-rebrand',
			__( 'Footer Settings', 'sog-unc-rebrand' ),
			__( 'Footer', 'sog-unc-rebrand' ),
			'manage_options',
			'sog-unc-rebrand-footer',
			array( $this, 'render_footer_page' )
		);

		add_submenu_page(
			'sog-unc-rebrand',
			__( 'Color Settings', 'sog-unc-rebrand' ),
			__( 'Colors', 'sog-unc-rebrand' ),
			'manage_options',
			'sog-unc-rebrand-colors',
			array( $this, 'render_colors_page' )
		);

		add_submenu_page(
			'sog-unc-rebrand',
			__( 'Utility Bar Settings', 'sog-unc-rebrand' ),
			__( 'Utility Bar', 'sog-unc-rebrand' ),
			'manage_options',
			'sog-unc-rebrand-utility',
			array( $this, 'render_utility_page' )
		);

		add_submenu_page(
			'sog-unc-rebrand',
			__( 'Preset Settings', 'sog-unc-rebrand' ),
			__( 'Presets', 'sog-unc-rebrand' ),
			'manage_options',
			'sog-unc-rebrand-presets',
			array( $this, 'render_presets_page' )
		);
	}

	/**
	 * Render the global settings page.
	 *
	 * @return void
	 */
	public function render_global_page(): void {
		$this->render_settings_shell(
			__( 'Global Settings', 'sog-unc-rebrand' ),
			__( 'Manage shared plugin toggles and display rules.', 'sog-unc-rebrand' ),
			array( $this, 'render_global_sections' )
		);
	}

	/**
	 * Render the header settings page.
	 *
	 * @return void
	 */
	public function render_header_page(): void {
		$this->render_settings_shell(
			__( 'Header Settings', 'sog-unc-rebrand' ),
			__( 'Configure header injection, branding, separators, and bottom navigation.', 'sog-unc-rebrand' ),
			array( $this, 'render_header_sections' )
		);
	}

	/**
	 * Render the footer settings page.
	 *
	 * @return void
	 */
	public function render_footer_page(): void {
		$this->render_settings_shell(
			__( 'Footer Settings', 'sog-unc-rebrand' ),
			__( 'Configure footer injection, content regions, logos, separators, and bottom row behavior.', 'sog-unc-rebrand' ),
			array( $this, 'render_footer_sections' )
		);
	}

	/**
	 * Render the utility bar settings page.
	 *
	 * @return void
	 */
	public function render_utility_page(): void {
		$this->render_settings_shell(
			__( 'Utility Bar Settings', 'sog-unc-rebrand' ),
			__( 'Configure the optional UNC utility bar that appears above the header.', 'sog-unc-rebrand' ),
			array( $this, 'render_utility_sections' )
		);
	}

	/**
	 * Render the color settings page.
	 *
	 * @return void
	 */
	public function render_colors_page(): void {
		$this->render_settings_shell(
			__( 'Color Customization', 'sog-unc-rebrand' ),
			__( 'Manage all header, utility bar, and footer colors in one place.', 'sog-unc-rebrand' ),
			array( $this, 'render_color_sections' )
		);
	}

	/**
	 * Render the preset import/export page.
	 *
	 * @return void
	 */
	public function render_presets_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$presets = $this->get_available_presets();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'SOG-UNC-Rebrand', 'sog-unc-rebrand' ); ?></h1>
			<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php echo esc_attr__( 'SOG Rebrand settings pages', 'sog-unc-rebrand' ); ?>">
				<?php foreach ( $this->get_admin_pages() as $slug => $label ) : ?>
					<a class="nav-tab<?php echo $this->is_current_page( $slug ) ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<p class="description"><?php echo esc_html__( 'Export only the settings that differ from the plugin defaults, import partial YAML presets, or apply built-in presets from the plugin and active theme.', 'sog-unc-rebrand' ); ?></p>

			<?php $this->render_presets_notice(); ?>

			<div class="postbox">
				<div class="inside">
					<h2><?php echo esc_html__( 'Export Current Settings', 'sog-unc-rebrand' ); ?></h2>
					<p><?php echo esc_html__( 'The downloaded YAML contains only the keys that differ from the plugin defaults. Importing that file later will only overwrite the keys present in the preset.', 'sog-unc-rebrand' ); ?></p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'sog_unc_rebrand_export_preset' ); ?>
						<input type="hidden" name="action" value="sog_unc_rebrand_export_preset" />
						<?php submit_button( __( 'Download YAML Preset', 'sog-unc-rebrand' ), 'primary', 'submit', false ); ?>
					</form>
				</div>
			</div>

			<div class="postbox">
				<div class="inside">
					<h2><?php echo esc_html__( 'Import YAML Preset', 'sog-unc-rebrand' ); ?></h2>
					<p><?php echo esc_html__( 'Upload a YAML file that contains a top-level settings map or a settings section. Imported keys are merged into the current configuration and then sanitized using the plugin settings rules.', 'sog-unc-rebrand' ); ?></p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
						<?php wp_nonce_field( 'sog_unc_rebrand_import_preset' ); ?>
						<input type="hidden" name="action" value="sog_unc_rebrand_import_preset" />
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="sog_unc_rebrand_preset_file"><?php echo esc_html__( 'Preset File', 'sog-unc-rebrand' ); ?></label>
									</th>
									<td>
										<input id="sog_unc_rebrand_preset_file" type="file" name="sog_unc_rebrand_preset_file" accept=".yml,.yaml,text/yaml,application/x-yaml" required />
									</td>
								</tr>
							</tbody>
						</table>

						<?php submit_button( __( 'Import Preset', 'sog-unc-rebrand' ), 'secondary', 'submit', false ); ?>
					</form>
				</div>
			</div>

			<div class="postbox">
				<div class="inside">
					<h2><?php echo esc_html__( 'Available Presets', 'sog-unc-rebrand' ); ?></h2>
					<p><?php echo esc_html__( 'Theme and plugin presets are discovered from /sog-rebrand-presets and the plugin /presets directory. Presets can define only a few keys, such as colors, and those values will overlay the current settings.', 'sog-unc-rebrand' ); ?></p>

					<?php if ( empty( $presets ) ) : ?>
						<p><?php echo esc_html__( 'No preset files were found.', 'sog-unc-rebrand' ); ?></p>
					<?php else : ?>
						<?php foreach ( $presets as $preset ) : ?>
							<div class="card">
								<h3><?php echo esc_html( $preset['title'] ); ?></h3>

								<p class="description">
									<?php
									echo esc_html(
										sprintf(
											/* translators: 1: preset source label, 2: number of settings. */
											_n( '%1$s. %2$d setting will be applied.', '%1$s. %2$d settings will be applied.', count( $preset['settings'] ), 'sog-unc-rebrand' ),
											$preset['source_label'],
											count( $preset['settings'] )
										)
									);
									?>
								</p>

								<?php if ( ! empty( $preset['description'] ) ) : ?>
									<p><?php echo esc_html( $preset['description'] ); ?></p>
								<?php endif; ?>

								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( 'sog_unc_rebrand_apply_preset' ); ?>
									<input type="hidden" name="action" value="sog_unc_rebrand_apply_preset" />
									<input type="hidden" name="preset_id" value="<?php echo esc_attr( $preset['id'] ); ?>" />
									<?php submit_button( __( 'Apply Preset', 'sog-unc-rebrand' ), 'secondary', 'submit', false ); ?>
								</form>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the shared page shell.
	 *
	 * @param string   $title Page title.
	 * @param string   $description Page description.
	 * @param callable $renderer Section renderer callback.
	 * @return void
	 */
	private function render_settings_shell( string $title, string $description, callable $renderer ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->get_settings();
		$page     = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'sog-unc-rebrand';
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'SOG-UNC-Rebrand', 'sog-unc-rebrand' ); ?></h1>
			<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php echo esc_attr__( 'SOG Rebrand settings pages', 'sog-unc-rebrand' ); ?>">
				<?php foreach ( $this->get_admin_pages() as $slug => $label ) : ?>
					<a class="nav-tab<?php echo $this->is_current_page( $slug ) ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<p class="description"><?php echo esc_html( $description ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'sog_unc_rebrand' ); ?>
				<input type="hidden" name="<?php echo esc_attr( self::OPTION_NAME . '[settings_page]' ); ?>" value="<?php echo esc_attr( $page ); ?>" />
				<?php call_user_func( $renderer, $settings ); ?>
				<?php submit_button( __( 'Save Settings', 'sog-unc-rebrand' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the global settings sections.
	 *
	 * @param array<string, mixed> $settings Current settings.
	 * @return void
	 */
	public function render_global_sections( array $settings ): void {
		$this->render_section_start(
			__( 'Global Toggles', 'sog-unc-rebrand' ),
			__( 'Header and footer rendering can be controlled independently. The utility bar is a subsection of the header and only renders when both the header and utility bar are enabled. Enable font loading only when those fonts are not already provided. **** Remember to disable the header and footer in the current themes templates to prevent conflicts with other plugins or themes (comment out the get_header / get_footer functions) if you enable them below. ****', 'sog-unc-rebrand' )
		);
		$this->render_checkbox_field( 'header_enabled', __( 'Enable header output', 'sog-unc-rebrand' ), (bool) $settings['header_enabled'] );
		$this->render_checkbox_field( 'footer_enabled', __( 'Enable footer output', 'sog-unc-rebrand' ), (bool) $settings['footer_enabled'] );
		$this->render_checkbox_field( 'utility_bar_enabled', __( 'Enable UNC Utility Bar', 'sog-unc-rebrand' ), (bool) $settings['utility_bar_enabled'] );
		$this->render_checkbox_field( 'load_frontend_fonts', __( 'Load Montserrat and Open Sans fonts', 'sog-unc-rebrand' ), (bool) $settings['load_frontend_fonts'] );
		$this->render_section_end();

		$this->render_section_start(
			__( 'Layout', 'sog-unc-rebrand' ),
			__( 'These shared layout values apply to the plugin header, utility bar, and footer output.', 'sog-unc-rebrand' )
		);
		$this->render_number_field( 'container_width', __( 'Container width (px)', 'sog-unc-rebrand' ), (int) $settings['container_width'] );
		$this->render_number_field( 'container_width_medium_mobile', __( 'Container width for Medium mobile (px)', 'sog-unc-rebrand' ), (int) $settings['container_width_medium_mobile'] );
		$this->render_number_field( 'container_width_small_mobile', __( 'Container width for Small mobile (px)', 'sog-unc-rebrand' ), (int) $settings['container_width_small_mobile'] );
		$this->render_number_field( 'mobile_breakpoint', __( 'Mobile breakpoint (px)', 'sog-unc-rebrand' ), (int) $settings['mobile_breakpoint'] );
		$this->render_section_end();

		$this->render_section_start(
			__( 'Display Rules', 'sog-unc-rebrand' ),
			__( 'These exclusions apply to both header and footer. The stored model supports later expansion to per-region rules.', 'sog-unc-rebrand' )
		);
		$this->render_checkbox_field( 'exclude_front_page', __( 'Exclude front page', 'sog-unc-rebrand' ), (bool) $settings['exclude_front_page'] );
		$this->render_checkbox_field( 'exclude_posts_page', __( 'Exclude blog index', 'sog-unc-rebrand' ), (bool) $settings['exclude_posts_page'] );
		$this->render_checkbox_field( 'exclude_search', __( 'Exclude search results', 'sog-unc-rebrand' ), (bool) $settings['exclude_search'] );
		$this->render_checkbox_field( 'exclude_404', __( 'Exclude 404 pages', 'sog-unc-rebrand' ), (bool) $settings['exclude_404'] );
		$this->render_textarea_field( 'excluded_post_ids', __( 'Excluded content IDs', 'sog-unc-rebrand' ), implode( "\n", array_map( 'strval', $settings['excluded_post_ids'] ) ), __( 'One numeric post ID per line.', 'sog-unc-rebrand' ) );
		$this->render_textarea_field( 'excluded_post_types', __( 'Excluded post types', 'sog-unc-rebrand' ), implode( "\n", $settings['excluded_post_types'] ), __( 'One post type slug per line.', 'sog-unc-rebrand' ) );
		$this->render_textarea_field( 'excluded_templates', __( 'Excluded templates', 'sog-unc-rebrand' ), implode( "\n", $settings['excluded_templates'] ), __( 'One page template file per line.', 'sog-unc-rebrand' ) );
		$this->render_section_end();
	}

	/**
	 * Render the header settings sections.
	 *
	 * @param array<string, mixed> $settings Current settings.
	 * @return void
	 */
	public function render_header_sections( array $settings ): void {
		$defaults = $this->get_defaults();

		$this->render_section_start( __( 'Header Injection', 'sog-unc-rebrand' ) );
		$this->render_select_field( 'header_hook_mode', __( 'Header hook source', 'sog-unc-rebrand' ), (string) $settings['header_hook_mode'], $this->get_hook_mode_options() );
		$this->render_select_field( 'header_known_hook', __( 'Known header hook', 'sog-unc-rebrand' ), (string) $settings['header_known_hook'], $this->get_known_header_hooks(), array( 'condition_field' => 'header_hook_mode', 'condition_value' => 'known' ) );
		$this->render_text_field( 'header_custom_hook', __( 'Custom header hook', 'sog-unc-rebrand' ), (string) $settings['header_custom_hook'], __( 'Used when the hook source is set to Custom.', 'sog-unc-rebrand' ), array( 'condition_field' => 'header_hook_mode', 'condition_value' => 'custom' ) );
		$this->render_number_field( 'header_hook_priority', __( 'Header hook priority', 'sog-unc-rebrand' ), (int) $settings['header_hook_priority'] );
		$this->render_select_field( 'header_core_variant', __( 'Header core variant', 'sog-unc-rebrand' ), (string) $settings['header_core_variant'], $this->get_header_core_variant_options() );
		$this->render_select_field( 'header_logo_link_behavior', __( 'Logo link behavior', 'sog-unc-rebrand' ), (string) $settings['header_logo_link_behavior'], $this->get_logo_link_behavior_options() );
		$this->render_url_field( 'header_logo_custom_url', __( 'Custom logo URL', 'sog-unc-rebrand' ), (string) $settings['header_logo_custom_url'], array( 'condition_field' => 'header_logo_link_behavior', 'condition_value' => 'custom' ) );
		$this->render_section_end();

		$this->render_section_start( __( 'Header Core Content', 'sog-unc-rebrand' ) );
		$this->render_checkbox_field('header_text_links_enabled', __( 'Enable header text links', 'sog-unc-rebrand' ), (bool) $settings['header_text_links_enabled'] );
		$this->render_media_field( 'header_logo_image_url', __( 'Header logo image', 'sog-unc-rebrand' ), (string) $settings['header_logo_image_url'], array( 'condition_field' => 'header_core_variant', 'condition_value' => 'image-logo' ) );
		$this->render_text_field('header_school_name', __( 'School name', 'sog-unc-rebrand' ), (string) $settings['header_school_name']);
		$this->render_text_field('header_text_main', __( 'Simple text main line/site name', 'sog-unc-rebrand' ), (string) $settings['header_text_main']);
		$this->render_text_field('header_text_subtext', __( 'Simple text subtext/description', 'sog-unc-rebrand' ), (string) $settings['header_text_subtext']);
		$this->render_select_field('header_school_name_text_transform', __( 'Header school name text transform', 'sog-unc-rebrand' ), (string) ($settings['header_school_name_text_transform'] ?? $defaults['header_school_name_text_transform']), $this->get_text_transform_style_options());
		$this->render_select_field('header_site_name_text_transform', __( 'Header site name text transform', 'sog-unc-rebrand' ), (string) ($settings['header_site_name_text_transform'] ?? $defaults['header_site_name_text_transform']), $this->get_text_transform_style_options());
		$this->render_select_field('header_site_description_text_transform', __( 'Header site description text transform', 'sog-unc-rebrand' ), (string) ($settings['header_site_description_text_transform'] ?? $defaults['header_site_description_text_transform']), $this->get_text_transform_style_options());
		$this->render_section_end();

		// Search fields
		$this->render_section_start( __( 'Header Search Form', 'sog-unc-rebrand' ) );
		$search_condition = array('condition_field' => 'display_site_search_enabled', 'condition_value' => '1');
		$this->render_checkbox_field( 'display_site_search_enabled', __( 'Display site search', 'sog-unc-rebrand' ), (bool) $settings['display_site_search_enabled'] );
		$this->render_checkbox_field( 'display_site_search_mobile_enabled', __( 'Display site search on mobile', 'sog-unc-rebrand' ), (bool) $settings['display_site_search_mobile_enabled'], $search_condition );
		$this->render_checkbox_field( 'site_search_inline_with_nav', __( 'Show search inline with navigation', 'sog-unc-rebrand' ), (bool) $settings['site_search_inline_with_nav'], $search_condition );
		$this->render_checkbox_field( 'site_search_inline_with_header', __( 'Show search inline with header', 'sog-unc-rebrand' ), (bool) $settings['site_search_inline_with_header'], $search_condition );
		$this->render_text_field( 'site_search_placeholder_text', __( 'Site search placeholder text', 'sog-unc-rebrand' ), (string) $settings['site_search_placeholder_text'], '', $search_condition );
		$this->render_checkbox_field( 'header_search_icon_enabled', __( 'Show search icon in header', 'sog-unc-rebrand' ), (bool) $settings['header_search_icon_enabled'], $search_condition );
		$this->render_text_field( 'header_search_button_text', __( 'Search button text in header', 'sog-unc-rebrand' ), (string) $settings['header_search_button_text'], '', $search_condition );
		$this->render_section_end();

		// Give / donate button fields
		$this->render_section_start(
			__( 'Header Donate Button', 'sog-unc-rebrand' ),
			__( 'Configure the header donate button text, URL, and styling. This only will display depending on the header variant you previously selected.', 'sog-unc-rebrand' )
		);
		$this->render_checkbox_field( 'header_give_button_hide_mobile', __( 'Hide Give button on mobile', 'sog-unc-rebrand' ), (bool) $settings['header_give_button_hide_mobile'] );
		$this->render_text_field( 'header_give_button_text', __( 'Header Give button text', 'sog-unc-rebrand' ), (string) $settings['header_give_button_text'] );
		$this->render_url_field( 'header_give_button_url', __( 'Header Give button URL', 'sog-unc-rebrand' ), (string) $settings['header_give_button_url'] );
		$this->render_checkbox_field( 'header_give_button_new_tab', __( 'Open Give button link in a new tab', 'sog-unc-rebrand' ), (bool) $settings['header_give_button_new_tab'] );
		$this->render_text_field( 'header_give_button_font_family', __( 'Give button font family', 'sog-unc-rebrand' ), (string) $settings['header_give_button_font_family'] );
		$this->render_number_field( 'header_give_button_font_weight', __( 'Give button font weight', 'sog-unc-rebrand' ), (int) $settings['header_give_button_font_weight'] );
		$this->render_text_field( 'header_give_button_font_style', __( 'Give button font style', 'sog-unc-rebrand' ), (string) $settings['header_give_button_font_style'] );
		$this->render_number_field( 'header_give_button_font_size', __( 'Give button font size (px)', 'sog-unc-rebrand' ), (int) $settings['header_give_button_font_size'] );
		$this->render_text_field( 'header_give_button_font_line_height', __( 'Give button font line height', 'sog-unc-rebrand' ), (string) $settings['header_give_button_font_line_height'] );
		$this->render_select_field( 'header_give_button_text_transform', __( 'Give button text transform', 'sog-unc-rebrand' ), (string) ($settings['header_give_button_text_transform'] ?? $defaults['header_give_button_text_transform']), $this->get_text_transform_style_options() );
		$this->render_select_field( 'header_give_button_border_style', __( 'Give button border style', 'sog-unc-rebrand' ), (string) $settings['header_give_button_border_style'], $this->get_border_style_options() );
		$this->render_number_field( 'header_give_button_border_thickness', __( 'Give button border thickness (px)', 'sog-unc-rebrand' ), (int) $settings['header_give_button_border_thickness'] );
		$this->render_number_field( 'header_give_button_border_radius', __( 'Give button border radius (px)', 'sog-unc-rebrand' ), (int) $settings['header_give_button_border_radius'] );
		$this->render_select_field( 'header_give_button_text_decoration', __( 'Give button text decoration', 'sog-unc-rebrand' ), (string) $settings['header_give_button_text_decoration'], $this->get_text_decoration_style_options() );
		$this->render_number_field( 'header_give_button_padding_top', __( 'Give button padding top (px)', 'sog-unc-rebrand' ), (int) $settings['header_give_button_padding_top'] );
		$this->render_number_field( 'header_give_button_padding_right', __( 'Give button padding right (px)', 'sog-unc-rebrand' ), (int) $settings['header_give_button_padding_right'] );
		$this->render_number_field( 'header_give_button_padding_bottom', __( 'Give button padding bottom (px)', 'sog-unc-rebrand' ), (int) $settings['header_give_button_padding_bottom'] );
		$this->render_number_field( 'header_give_button_padding_left', __( 'Give button padding left (px)', 'sog-unc-rebrand' ), (int) $settings['header_give_button_padding_left'] );
		$this->render_section_end();

		// Social Media links
		$this->render_section_start( __( 'Header Social Media Links', 'sog-unc-rebrand' ) );
		$this->render_checkbox_field( 'header_social_links_hide_mobile', __( 'Hide social links on mobile', 'sog-unc-rebrand' ), (bool) $settings['header_social_links_hide_mobile'] );
		$this->render_select_field( 'header_social_links_alignment', __( 'Header social links alignment', 'sog-unc-rebrand' ), (string) $settings['header_social_links_alignment'], $this->get_alignment_options() );
		$this->render_social_links_field( 'header_social_links', __( 'Header social links', 'sog-unc-rebrand' ), $settings['header_social_links'], __( 'Add as many social links as needed. SVG icons should use currentColor when possible so the configured colors apply cleanly.', 'sog-unc-rebrand' ) );
		$this->render_section_end();

		// Navigation Special button fields
		$this->render_section_start(
			__( 'Header Navigation Special Button', 'sog-unc-rebrand' ),
			__( 'Configure the header special button text, URL, and styling. This only will display depending on the header variant you previously selected. This is a unique button that will be inline with the main menu but and be specifically tailor to your design.', 'sog-unc-rebrand' )
		);
		$this->render_text_field( 'header_special_button_text', __( 'Header Special button text', 'sog-unc-rebrand' ), (string) $settings['header_special_button_text'] );
		$this->render_url_field( 'header_special_button_url', __( 'Header Special button URL', 'sog-unc-rebrand' ), (string) $settings['header_special_button_url'] );
		$this->render_checkbox_field( 'header_special_button_new_tab', __( 'Open Special button link in a new tab', 'sog-unc-rebrand' ), (bool) $settings['header_special_button_new_tab'] );
		$this->render_checkbox_field( 'header_special_button_hide_mobile', __( 'Hide Special button on mobile', 'sog-unc-rebrand' ), (bool) $settings['header_special_button_hide_mobile'] );
		$this->render_number_field( 'header_special_button_border_thickness', __( 'Special button border thickness (px)', 'sog-unc-rebrand' ), (int) $settings['header_special_button_border_thickness'] );
		$this->render_number_field( 'header_special_button_border_radius', __( 'Special button border radius (px)', 'sog-unc-rebrand' ), (int) $settings['header_special_button_border_radius'] );
		$this->render_select_field( 'header_special_button_border_style', __( 'Special button border style', 'sog-unc-rebrand' ), (string) $settings['header_special_button_border_style'], $this->get_separator_style_options() );
		$this->render_text_field( 'header_special_font_family', __( 'Special button font family', 'sog-unc-rebrand' ), (string) $settings['header_special_font_family'] );
		$this->render_number_field( 'header_special_font_weight', __( 'Special button font weight', 'sog-unc-rebrand' ), (int) $settings['header_special_font_weight'] );
		$this->render_text_field( 'header_special_font_style', __( 'Special button font style', 'sog-unc-rebrand' ), (string) $settings['header_special_font_style'] );
		$this->render_number_field( 'header_special_font_size', __( 'Special button font size (px)', 'sog-unc-rebrand' ), (int) $settings['header_special_font_size'] );
		$this->render_select_field( 'header_special_text_transform', __( 'Special button text transform', 'sog-unc-rebrand' ), (string) $settings['header_special_text_transform'], $this->get_text_transform_style_options() );
		$this->render_number_field( 'header_special_button_padding_top', __( 'Special button padding top (px)', 'sog-unc-rebrand' ), (int) ($settings['header_special_button_padding_top'] ?? $defaults['header_special_button_padding_top']) );
		$this->render_number_field( 'header_special_button_padding_right', __( 'Special button padding right (px)', 'sog-unc-rebrand' ), (int) ($settings['header_special_button_padding_right'] ?? $defaults['header_special_button_padding_right']) );
		$this->render_number_field( 'header_special_button_padding_bottom', __( 'Special button padding bottom (px)', 'sog-unc-rebrand' ), (int) ($settings['header_special_button_padding_bottom'] ?? $defaults['header_special_button_padding_bottom']) );
		$this->render_number_field( 'header_special_button_padding_left', __( 'Special button padding left (px)', 'sog-unc-rebrand' ), (int) ($settings['header_special_button_padding_left'] ?? $defaults['header_special_button_padding_left']) );
		$this->render_section_end();

		$this->render_section_start(
			__( 'Header Menus', 'sog-unc-rebrand' ),
			__( 'Header menus use the plugin-owned menu locations registered in Appearance > Menus.', 'sog-unc-rebrand' )
		);
		$this->render_checkbox_field( 'header_main_menu_enabled', __( 'Enable header core menu', 'sog-unc-rebrand' ), (bool) $settings['header_main_menu_enabled'] );
		$this->render_checkbox_field( 'header_bottom_nav_enabled', __( 'Enable header bottom navigation', 'sog-unc-rebrand' ), (bool) $settings['header_bottom_nav_enabled'] );
		$this->render_section_end();

		$this->render_section_start( __( 'Header Bottom Navigation', 'sog-unc-rebrand' ), '', array( 'condition_field' => 'header_bottom_nav_enabled', 'condition_value' => '1' ) );
		$this->render_select_field( 'header_bottom_orientation', __( 'Orientation', 'sog-unc-rebrand' ), (string) $settings['header_bottom_orientation'], $this->get_orientation_options() );
		$this->render_select_field( 'header_bottom_alignment', __( 'Alignment', 'sog-unc-rebrand' ), (string) $settings['header_bottom_alignment'], $this->get_alignment_options() );
		$this->render_number_field( 'header_bottom_spacing', __( 'Item spacing (px)', 'sog-unc-rebrand' ), (int) $settings['header_bottom_spacing'] );
		$this->render_select_field( 'header_bottom_mobile_mode', __( 'Mobile navigation mode', 'sog-unc-rebrand' ), (string) $settings['header_bottom_mobile_mode'], $this->get_mobile_menu_mode_options() );
		$this->render_section_end();

		// Header Navigation fields
		$this->render_section_start( __( 'Header Navigation', 'sog-unc-rebrand' ) );
		$this->render_text_field( 'header_navigation_font_family', __( 'Header navigation font family', 'sog-unc-rebrand' ), (string) $settings['header_navigation_font_family'] );
		$this->render_text_field( 'header_navigation_font_style', __( 'Header navigation font style', 'sog-unc-rebrand' ), (string) $settings['header_navigation_font_style'] );
		$this->render_number_field( 'header_navigation_font_weight', __( 'Header navigation font weight', 'sog-unc-rebrand' ), (int) $settings['header_navigation_font_weight'] );
		$this->render_number_field( 'header_navigation_font_size', __( 'Header navigation font size (px)', 'sog-unc-rebrand' ), (int) $settings['header_navigation_font_size'] );
		$this->render_select_field( 'header_navigation_text_transform', __( 'Header navigation text transform', 'sog-unc-rebrand' ), (string) ($settings['header_navigation_text_transform'] ?? $defaults['header_navigation_text_transform']), $this->get_text_transform_style_options() );
		$this->render_select_field( 'header_navigation_text_decoration', __( 'Header navigation text decoration', 'sog-unc-rebrand' ), (string) ($settings['header_navigation_text_decoration'] ?? $defaults['header_navigation_text_decoration']), $this->get_text_decoration_style_options() );
		$this->render_number_field( 'header_navigation_item_padding_top', __( 'Header navigation item (a tag) padding top (px)', 'sog-unc-rebrand' ), (int) $settings['header_navigation_item_padding_top'] );
		$this->render_number_field( 'header_navigation_item_padding_right', __( 'Header navigation item (a tag) padding right (px)', 'sog-unc-rebrand' ), (int) $settings['header_navigation_item_padding_right'] );
		$this->render_number_field( 'header_navigation_item_padding_bottom', __( 'Header navigation item (a tag) padding bottom (px)', 'sog-unc-rebrand' ), (int) $settings['header_navigation_item_padding_bottom'] );
		$this->render_number_field( 'header_navigation_item_padding_left', __( 'Header navigation item (a tag) padding left (px)', 'sog-unc-rebrand' ), (int) $settings['header_navigation_item_padding_left'] );
		$this->render_section_end();

		// Header SubmenuNavigation fields
		$this->render_section_start( __( 'Header Submenu Navigation', 'sog-unc-rebrand' ) );
		$this->render_number_field( 'header_submenu_navigation_item_padding_top', __( 'Header submenu navigation item (a tag) padding top (px)', 'sog-unc-rebrand' ), (int) $settings['header_submenu_navigation_item_padding_top'] );
		$this->render_number_field( 'header_submenu_navigation_item_padding_right', __( 'Header submenu navigation item (a tag) padding right (px)', 'sog-unc-rebrand' ), (int) $settings['header_submenu_navigation_item_padding_right'] );
		$this->render_number_field( 'header_submenu_navigation_item_padding_bottom', __( 'Header submenu navigation item (a tag) padding bottom (px)', 'sog-unc-rebrand' ), (int) $settings['header_submenu_navigation_item_padding_bottom'] );
		$this->render_number_field( 'header_submenu_navigation_item_padding_left', __( 'Header submenu navigation item (a tag) padding left (px)', 'sog-unc-rebrand' ), (int) $settings['header_submenu_navigation_item_padding_left'] );
		$this->render_section_end();

		// Header Mobile Navigation fields
		$this->render_section_start( __( 'Header Mobile Navigation', 'sog-unc-rebrand' ) );
		$this->render_number_field( 'header_mobile_navigation_item_padding_top', __( 'Header mobile navigation item (a tag) padding top (px)', 'sog-unc-rebrand' ), (int) $settings['header_mobile_navigation_item_padding_top'] );
		$this->render_number_field( 'header_mobile_navigation_item_padding_right', __( 'Header mobile navigation item (a tag) padding right (px)', 'sog-unc-rebrand' ), (int) $settings['header_mobile_navigation_item_padding_right'] );
		$this->render_number_field( 'header_mobile_navigation_item_padding_bottom', __( 'Header mobile navigation item (a tag) padding bottom (px)', 'sog-unc-rebrand' ), (int) $settings['header_mobile_navigation_item_padding_bottom'] );
		$this->render_number_field( 'header_mobile_navigation_item_padding_left', __( 'Header mobile navigation item (a tag) padding left (px)', 'sog-unc-rebrand' ), (int) $settings['header_mobile_navigation_item_padding_left'] );
		$this->render_section_end();
	}

	/**
	 * Render the utility bar settings sections.
	 *
	 * @param array<string, mixed> $settings Current settings.
	 * @return void
	 */
	public function render_utility_sections( array $settings ): void {
		$this->render_section_start(
			__( 'Utility Bar', 'sog-unc-rebrand' ),
			__( 'The utility bar is optional and appears as the first row of the header.', 'sog-unc-rebrand' )
		);
		$this->render_checkbox_field( 'utility_bar_menu_fallback_enabled', __( 'Use default UNC links when no menu is assigned', 'sog-unc-rebrand' ), (bool) $settings['utility_bar_menu_fallback_enabled'] );
		$this->render_text_field( 'utility_bar_brand_label', __( 'Utility bar brand label', 'sog-unc-rebrand' ), (string) $settings['utility_bar_brand_label'] );
		$this->render_media_field( 'utility_bar_brand_logo_url', __( 'Utility bar brand logo', 'sog-unc-rebrand' ), (string) ($settings['utility_bar_brand_logo_url'] ?? '') );
		$this->render_number_field( 'utility_bar_brand_logo_width', __( 'Brand logo width (px)', 'sog-unc-rebrand' ), (int) ($settings['utility_bar_brand_logo_width'] ?? 40) );
		$this->render_number_field( 'utility_bar_brand_logo_height', __( 'Brand logo height (px)', 'sog-unc-rebrand' ), (int) ($settings['utility_bar_brand_logo_height'] ?? 35) );
		$this->render_section_end();
	}

	/**
	 * Render the unified color customization sections.
	 *
	 * @param array<string, mixed> $settings Current settings.
	 * @return void
	 */
	public function render_color_sections( array $settings ): void {
		$this->render_section_start(
			__( 'Header Colors', 'sog-unc-rebrand' ),
			__( 'Colors used by the main header row and the lower navigation row.', 'sog-unc-rebrand' )
		);
		$this->render_color_field( 'header_core_background_color', __( 'Header background color', 'sog-unc-rebrand' ), (string) $settings['header_core_background_color'] );
		$this->render_color_field( 'header_school_name_color', __( 'Header school name text color', 'sog-unc-rebrand' ), (string) $settings['header_school_name_color'] );
		$this->render_color_field( 'header_text_color', __( 'Header text (site name) text color', 'sog-unc-rebrand' ), (string) $settings['header_text_color'] );
		$this->render_color_field( 'header_subtext_color', __( 'Header subtext (site description/tagline) text color', 'sog-unc-rebrand' ), (string) $settings['header_subtext_color'] );
		$this->render_color_field( 'header_bottom_background_color', __( 'Header bottom navigation background color', 'sog-unc-rebrand' ), (string) $settings['header_bottom_background_color'] );
		$this->render_color_field( 'header_mobile_menu_background_color', __( 'Header mobile menu background color', 'sog-unc-rebrand' ), (string) $settings['header_mobile_menu_background_color'] );
		$this->render_color_field( 'header_mobile_menu_hover_color', __( 'Header mobile menu hover color', 'sog-unc-rebrand' ), (string) $settings['header_mobile_menu_hover_color'] );
		$this->render_color_field( 'header_mobile_menu_text_color', __( 'Header mobile menu text color', 'sog-unc-rebrand' ), (string) $settings['header_mobile_menu_text_color'] );
		$this->render_color_field( 'header_bottom_text_color', __( 'Header navigation text color', 'sog-unc-rebrand' ), (string) $settings['header_bottom_text_color'] );
		$this->render_color_field( 'header_separator_color', __( 'Header separator color', 'sog-unc-rebrand' ), (string) $settings['header_separator_color'] );
		$this->render_color_field( 'header_give_button_background_color', __( 'Header Give button background color', 'sog-unc-rebrand' ), (string) $settings['header_give_button_background_color'] );
		$this->render_color_field( 'header_give_button_hover_color', __( 'Header Give/Donate button hover color', 'sog-unc-rebrand' ), (string) $settings['header_give_button_hover_color'] );
		$this->render_color_field( 'header_give_button_text_color', __( 'Header Give/Donate button text color', 'sog-unc-rebrand' ), (string) $settings['header_give_button_text_color'] );
		$this->render_color_field( 'header_give_button_border_color', __( 'Header Give/Donate button border color', 'sog-unc-rebrand' ), (string) $settings['header_give_button_border_color'] );
		$this->render_color_field( 'header_special_button_background_color', __( 'Header Special button background color', 'sog-unc-rebrand' ), (string) $settings['header_special_button_background_color'] );
		$this->render_color_field( 'header_special_button_hover_color', __( 'Header Special button hover color', 'sog-unc-rebrand' ), (string) $settings['header_special_button_hover_color'] );
		$this->render_color_field( 'header_special_button_text_color', __( 'Header Special button text color', 'sog-unc-rebrand' ), (string) $settings['header_special_button_text_color'] );
		$this->render_color_field( 'header_special_button_border_color', __( 'Header Special button border color', 'sog-unc-rebrand' ), (string) $settings['header_special_button_border_color'] );
		$this->render_section_end();

		$this->render_section_start(
			__( 'Utility Bar Colors', 'sog-unc-rebrand' ),
			__( 'Colors used by the optional UNC utility bar.', 'sog-unc-rebrand' )
		);
		$this->render_color_field( 'utility_bar_background_color', __( 'Utility bar background color', 'sog-unc-rebrand' ), (string) $settings['utility_bar_background_color'] );
		$this->render_color_field( 'utility_bar_text_color', __( 'Utility bar text color', 'sog-unc-rebrand' ), (string) $settings['utility_bar_text_color'] );
		$this->render_section_end();

		$this->render_section_start(
			__( 'Footer Colors', 'sog-unc-rebrand' ),
			__( 'All footer colors, including headings, links, separators, and muted placeholder text.', 'sog-unc-rebrand' )
		);
		$this->render_color_field( 'footer_background_color', __( 'Footer background color', 'sog-unc-rebrand' ), (string) $settings['footer_background_color'] );
		$this->render_color_field( 'footer_text_color', __( 'Footer body text color', 'sog-unc-rebrand' ), (string) $settings['footer_text_color'] );
		$this->render_color_field( 'footer_heading_color', __( 'Footer headings color', 'sog-unc-rebrand' ), (string) $settings['footer_heading_color'] );
		$this->render_color_field( 'footer_muted_text_color', __( 'Footer muted text and placeholders color', 'sog-unc-rebrand' ), (string) $settings['footer_muted_text_color'] );
		$this->render_color_field( 'footer_link_color', __( 'Footer links color', 'sog-unc-rebrand' ), (string) $settings['footer_link_color'] );
		$this->render_color_field( 'footer_link_hover_color', __( 'Footer link hover color', 'sog-unc-rebrand' ), (string) $settings['footer_link_hover_color'] );
		$this->render_color_field( 'footer_separator_1_color', __( 'Footer separator 1 color', 'sog-unc-rebrand' ), (string) $settings['footer_separator_1_color'] );
		$this->render_color_field( 'footer_separator_2_color', __( 'Footer separator 2 color', 'sog-unc-rebrand' ), (string) $settings['footer_separator_2_color'] );
		$this->render_color_field( 'footer_give_button_background_color', __( 'Give/Donate button background color', 'sog-unc-rebrand' ), (string) $settings['footer_give_button_background_color'] );
		$this->render_color_field( 'footer_give_button_hover_color', __( 'Give/Donate button hover color', 'sog-unc-rebrand' ), (string) $settings['footer_give_button_hover_color'] );
		$this->render_color_field( 'footer_give_button_text_color', __( 'Give/Donate button text color', 'sog-unc-rebrand' ), (string) $settings['footer_give_button_text_color'] );
		$this->render_color_field( 'footer_give_button_border_color', __( 'Give/Donate button border color', 'sog-unc-rebrand' ), (string) $settings['footer_give_button_border_color'] );
		$this->render_section_end();
	}

	/**
	 * Render the footer settings sections.
	 *
	 * @param array<string, mixed> $settings Current settings.
	 * @return void
	 */
	public function render_footer_sections( array $settings ): void {
		$defaults = $this->get_defaults();

		$this->render_section_start(
			__( 'Footer Injection', 'sog-unc-rebrand' ),
			__( 'Configure where the footer outputs by selecting a known hook or entering a custom hook name. The priority controls the order of output relative to other content hooked to the same location.', 'sog-unc-rebrand' )
		);
		$this->render_select_field( 'footer_hook_mode', __( 'Footer hook source', 'sog-unc-rebrand' ), (string) $settings['footer_hook_mode'], $this->get_hook_mode_options() );
		$this->render_select_field( 'footer_known_hook', __( 'Known footer hook', 'sog-unc-rebrand' ), (string) $settings['footer_known_hook'], $this->get_known_footer_hooks(), array( 'condition_field' => 'footer_hook_mode', 'condition_value' => 'known' ) );
		$this->render_text_field( 'footer_custom_hook', __( 'Custom footer hook', 'sog-unc-rebrand' ), (string) $settings['footer_custom_hook'], __( 'Used when the hook source is set to Custom.', 'sog-unc-rebrand' ), array( 'condition_field' => 'footer_hook_mode', 'condition_value' => 'custom' ) );
		$this->render_number_field( 'footer_hook_priority', __( 'Footer hook priority', 'sog-unc-rebrand' ), (int) $settings['footer_hook_priority'] );
		$this->render_section_end();

		$this->render_section_start(
			__( 'Footer Top Text', 'sog-unc-rebrand' ),
			__( 'NOTE: This currently has a bug that affects saving the settings on the footer tab setting\'s page. This section allows you to enable and customize the top text block in the footer, including a heading and rich content area.', 'sog-unc-rebrand' )
		);
		$this->render_checkbox_field( 'footer_top_text_enabled', __( 'Enable top text/WYSIWYG block', 'sog-unc-rebrand' ), (bool) $settings['footer_top_text_enabled'] );
		$this->render_text_field( 'footer_top_text_heading', __( 'Top text heading', 'sog-unc-rebrand' ), (string) $settings['footer_top_text_heading'], '', array( 'condition_field' => 'footer_top_text_enabled', 'condition_value' => '1' ) );
		$this->render_editor_field( 'footer_top_text_content', __( 'Top text content', 'sog-unc-rebrand' ), (string) $settings['footer_top_text_content'], array( 'condition_field' => 'footer_top_text_enabled', 'condition_value' => '1' ) );
		$this->render_section_end();

		$this->render_section_start( __( 'Footer Logos', 'sog-unc-rebrand' ) );
		$this->render_checkbox_field( 'footer_logos_enabled', __( 'Enable footer logo row', 'sog-unc-rebrand' ), (bool) $settings['footer_logos_enabled'] );
		$this->render_select_field( 'footer_logos_orientation', __( 'Logo orientation', 'sog-unc-rebrand' ), (string) $settings['footer_logos_orientation'], $this->get_orientation_options(), array( 'condition_field' => 'footer_logos_enabled', 'condition_value' => '1' ) );
		$this->render_select_field( 'footer_logos_alignment', __( 'Logo alignment', 'sog-unc-rebrand' ), (string) $settings['footer_logos_alignment'], $this->get_alignment_options(), array( 'condition_field' => 'footer_logos_enabled', 'condition_value' => '1' ) );
		$this->render_number_field( 'footer_logos_spacing', __( 'Logo spacing (px)', 'sog-unc-rebrand' ), (int) $settings['footer_logos_spacing'], array( 'condition_field' => 'footer_logos_enabled', 'condition_value' => '1' ) );
		$this->render_checkbox_field( 'footer_logos_mobile_carousel', __( 'Use mobile carousel behavior later', 'sog-unc-rebrand' ), (bool) $settings['footer_logos_mobile_carousel'], array( 'condition_field' => 'footer_logos_enabled', 'condition_value' => '1' ) );
		$this->render_number_field( 'footer_logos_max_height', __( 'Max logo height (px)', 'sog-unc-rebrand' ), (int) $settings['footer_logos_max_height'], array( 'condition_field' => 'footer_logos_enabled', 'condition_value' => '1' ) );
		$this->render_number_field( 'footer_logos_max_total_height', __( 'Max total row height (px)', 'sog-unc-rebrand' ), (int) $settings['footer_logos_max_total_height'], array( 'condition_field' => 'footer_logos_enabled', 'condition_value' => '1' ) );

		for ( $logo_index = 1; $logo_index <= 3; $logo_index++ ) {
			$this->render_subsection_heading(
				sprintf( __( 'Logo %d', 'sog-unc-rebrand' ), $logo_index ),
				array( 'condition_field' => 'footer_logos_enabled', 'condition_value' => '1' )
			);
			$this->render_select_field( 'footer_logo_' . $logo_index . '_type', __( 'Logo type', 'sog-unc-rebrand' ), (string) $settings[ 'footer_logo_' . $logo_index . '_type' ], $this->get_footer_logo_type_options(), array( 'condition_field' => 'footer_logos_enabled', 'condition_value' => '1' ) );
			$this->render_media_field( 'footer_logo_' . $logo_index . '_image_url', __( 'Image', 'sog-unc-rebrand' ), (string) $settings[ 'footer_logo_' . $logo_index . '_image_url' ], array( 'condition_field' => 'footer_logo_' . $logo_index . '_type', 'condition_value' => 'image' ) );
			$this->render_url_field( 'footer_logo_' . $logo_index . '_link_url', __( 'Logo link URL', 'sog-unc-rebrand' ), (string) $settings[ 'footer_logo_' . $logo_index . '_link_url' ], array( 'condition_field' => 'footer_logo_' . $logo_index . '_type', 'condition_value' => 'image,text' ) );
			$this->render_text_field( 'footer_logo_' . $logo_index . '_text_upper', __( 'Text logo upper line', 'sog-unc-rebrand' ), (string) $settings[ 'footer_logo_' . $logo_index . '_text_upper' ], '', array( 'condition_field' => 'footer_logo_' . $logo_index . '_type', 'condition_value' => 'text' ) );
			$this->render_text_field( 'footer_logo_' . $logo_index . '_text_lower', __( 'Text logo lower line', 'sog-unc-rebrand' ), (string) $settings[ 'footer_logo_' . $logo_index . '_text_lower' ], '', array( 'condition_field' => 'footer_logo_' . $logo_index . '_type', 'condition_value' => 'text' ) );
			$this->render_checkbox_field( 'footer_logo_' . $logo_index . '_link_new_tab', __( 'Open link in new tab', 'sog-unc-rebrand' ), (bool) $settings[ 'footer_logo_' . $logo_index . '_link_new_tab' ], array( 'condition_field' => 'footer_logo_' . $logo_index . '_link_url', 'condition_operator' => 'not-empty' ) );
			$this->render_checkbox_field( 'footer_logo_' . $logo_index . '_hide_mobile', __( 'Hide logo on mobile', 'sog-unc-rebrand' ), (bool) $settings[ 'footer_logo_' . $logo_index . '_hide_mobile' ], array( 'condition_field' => 'footer_logo_' . $logo_index . '_type', 'condition_value' => 'image,text' ) );
		}

		$this->render_section_end();

		$this->render_section_start( __( 'Footer Separators', 'sog-unc-rebrand' ) );
		$this->render_subsection_heading( __( 'Separator 1', 'sog-unc-rebrand' ) );
		$this->render_select_field( 'footer_separator_1_style', __( 'Style', 'sog-unc-rebrand' ), (string) $settings['footer_separator_1_style'], $this->get_separator_style_options() );
		$this->render_number_field( 'footer_separator_1_thickness', __( 'Thickness (px)', 'sog-unc-rebrand' ), (int) $settings['footer_separator_1_thickness'] );
		$this->render_checkbox_field( 'footer_separator_1_hide_mobile', __( 'Hide on mobile', 'sog-unc-rebrand' ), (bool) $settings['footer_separator_1_hide_mobile'] );
		$this->render_subsection_heading( __( 'Separator 2', 'sog-unc-rebrand' ) );
		$this->render_select_field( 'footer_separator_2_style', __( 'Style', 'sog-unc-rebrand' ), (string) $settings['footer_separator_2_style'], $this->get_separator_style_options() );
		$this->render_select_field( 'footer_separator_2_style_mobile', __( 'Style (mobile)', 'sog-unc-rebrand' ), (string) $settings['footer_separator_2_style_mobile'], $this->get_separator_style_options() );
		$this->render_number_field( 'footer_separator_2_thickness', __( 'Thickness (px)', 'sog-unc-rebrand' ), (int) $settings['footer_separator_2_thickness'] );
		$this->render_checkbox_field( 'footer_separator_2_hide_mobile', __( 'Hide on mobile', 'sog-unc-rebrand' ), (bool) $settings['footer_separator_2_hide_mobile'] );
		$this->render_section_end();

		$this->render_section_start(
			__( 'Footer Columns', 'sog-unc-rebrand' ),
			__( 'Menus are assigned in Appearance > Menus. WYSIWYG and social content settings here control the plugin output. Column widths should normally total 100.', 'sog-unc-rebrand' )
		);
		$this->render_number_field( 'footer_column_gap', __( 'Column gap (px)', 'sog-unc-rebrand' ), (int) $settings['footer_column_gap'] );
		$this->render_number_field( 'footer_column_2_gap', __( 'Column 2 gap (px)', 'sog-unc-rebrand' ), (int) $settings['footer_column_2_gap'] );

		for ( $column_index = 1; $column_index <= 3; $column_index++ ) {
			$this->render_subsection_heading( sprintf( __( 'Column %d', 'sog-unc-rebrand' ), $column_index ) );
			$this->render_select_field( 'footer_column_' . $column_index . '_mode', __( 'Content mode', 'sog-unc-rebrand' ), (string) $settings[ 'footer_column_' . $column_index . '_mode' ], $this->get_footer_column_mode_options() );
			$this->render_number_field( 'footer_column_' . $column_index . '_width', __( 'Width (%)', 'sog-unc-rebrand' ), (int) $settings[ 'footer_column_' . $column_index . '_width' ] );
			$this->render_checkbox_field( 'footer_column_' . $column_index . '_hide_mobile', __( 'Hide on mobile', 'sog-unc-rebrand' ), (bool) $settings[ 'footer_column_' . $column_index . '_hide_mobile' ] );
			$this->render_checkbox_field( 'footer_column_' . $column_index . '_copyright_enabled', __( 'Enable column 1 to show in the copyright row.', 'sog-unc-rebrand' ), (bool) ( $settings[ 'footer_column_' . $column_index . '_copyright_enabled' ] ?? false ), array( 'condition_field' => 'footer_column_' . $column_index . '_mode', 'condition_value' => 'wysiwyg,menu_wysiwyg,social_wysiwyg,give_wysiwyg,menu_social_wysiwyg,social_give_wysiwyg,menu_give_wysiwyg,menu_social_give_wysiwyg' ) );
			$this->render_text_field( 'footer_column_' . $column_index . '_heading', __( 'Column heading', 'sog-unc-rebrand' ), (string) $settings[ 'footer_column_' . $column_index . '_heading' ], '', array( 'condition_field' => 'footer_column_' . $column_index . '_mode' ) );
			$this->render_editor_field( 'footer_column_' . $column_index . '_content', __( 'WYSIWYG content', 'sog-unc-rebrand' ), (string) $settings[ 'footer_column_' . $column_index . '_content' ], array( 'condition_field' => 'footer_column_' . $column_index . '_mode', 'condition_value' => 'wysiwyg,menu_wysiwyg,social_wysiwyg,give_wysiwyg,menu_social_wysiwyg,social_give_wysiwyg,menu_give_wysiwyg,menu_social_give_wysiwyg' ) );
			$this->render_text_field( 'footer_column_' . $column_index . '_shortcode', __( 'Shortcode', 'sog-unc-rebrand' ), (string) $settings[ 'footer_column_' . $column_index . '_shortcode' ], __( 'Enter a shortcode, e.g. [my_shortcode]', 'sog-unc-rebrand' ), array( 'condition_field' => 'footer_column_' . $column_index . '_mode', 'condition_value' => 'shortcode,menu_shortcode,social_shortcode,give_shortcode,menu_social_shortcode,social_give_shortcode,menu_give_shortcode,menu_social_give_shortcode' ) );
			$this->render_select_field( 'footer_column_' . $column_index . '_heading_alignment', __( 'Column heading alignment', 'sog-unc-rebrand' ), (string) $settings['footer_column_' . $column_index . '_heading_alignment'], $this->get_alignment_options() );
			$this->render_select_field( 'footer_column_' . $column_index . '_heading_text_transform', __( 'Column heading text transform', 'sog-unc-rebrand' ), (string) $settings['footer_column_' . $column_index . '_heading_text_transform'], $this->get_text_transform_style_options() );
			$this->render_select_field( 'footer_column_' . $column_index . '_heading_text_decoration', __( 'Column heading text decoration', 'sog-unc-rebrand' ), (string) $settings['footer_column_' . $column_index . '_heading_text_decoration'], $this->get_text_decoration_style_options() );
		}

		$this->render_section_end();

		// Footer give/donate button and Social Media links
		$this->render_section_start(
			__( 'Footer Give button and Social Media', 'sog-unc-rebrand' ),
			__( 'Configure the gap between the Give button and social media links.', 'sog-unc-rebrand' )
		);
		$this->render_number_field( 'footer_give_social_gap', __( 'Gap between give (donate) button and social links (px)', 'sog-unc-rebrand' ), (int) $settings['footer_give_social_gap'] );
		$this->render_section_end();

		// Footer Social Links fields
		$this->render_section_start(
			__( 'Footer Social Media Links', 'sog-unc-rebrand' ),
			__( 'Configure the social media links that appear in the footer. These should be added in order of importance/priority as they will display in the order entered.', 'sog-unc-rebrand' )
		);
		$this->render_checkbox_field( 'footer_social_links_hide_mobile', __( 'Hide social links on mobile', 'sog-unc-rebrand' ), (bool) $settings['footer_social_links_hide_mobile'] );
		$this->render_checkbox_field( 'footer_social_links_below_columns', __( 'Display social links below columns', 'sog-unc-rebrand' ), (bool) $settings['footer_social_links_below_columns'] );
		$this->render_select_field( 'footer_social_links_orientation', __( 'Orientation', 'sog-unc-rebrand' ), (string) $settings['footer_social_links_orientation'], $this->get_orientation_options() );
		$this->render_select_field( 'footer_social_links_alignment', __( 'Footer social links alignment', 'sog-unc-rebrand' ), (string) $settings['footer_social_links_alignment'], $this->get_alignment_options() );
		$this->render_social_links_field( 'footer_social_links', __( 'Footer social links', 'sog-unc-rebrand' ), $settings['footer_social_links'], __( 'Add as many social links as needed. SVG icons should use currentColor when possible so the configured colors apply cleanly.', 'sog-unc-rebrand' ) );
		$this->render_section_end();

		// Footer Give Button fields
		$this->render_section_start(
			__( 'Footer Donate Button', 'sog-unc-rebrand' ),
			__( 'Configure the footer donate button text, URL, and styling.', 'sog-unc-rebrand' )
		);
		$this->render_checkbox_field( 'footer_give_button_hide_mobile', __( 'Hide give button on mobile', 'sog-unc-rebrand' ), (bool) $settings['footer_give_button_hide_mobile'] );
		$this->render_checkbox_field( 'footer_give_button_below_columns', __( 'Display give button below columns', 'sog-unc-rebrand' ), (bool) $settings['footer_give_button_below_columns'] );
		$this->render_select_field( 'footer_give_button_orientation', __( 'Orientation', 'sog-unc-rebrand' ), (string) $settings['footer_give_button_orientation'], $this->get_orientation_options() );
		$this->render_select_field( 'footer_give_button_alignment', __( 'Footer give button alignment', 'sog-unc-rebrand' ), (string) $settings['footer_give_button_alignment'], $this->get_alignment_options() );
		$this->render_text_field( 'footer_give_button_text', __( 'Give button text', 'sog-unc-rebrand' ), (string) $settings['footer_give_button_text'] );
		$this->render_url_field( 'footer_give_button_url', __( 'Give button URL', 'sog-unc-rebrand' ), (string) $settings['footer_give_button_url'] );
		$this->render_text_field( 'footer_give_button_font_family', __( 'Give button font family', 'sog-unc-rebrand' ), (string) $settings['footer_give_button_font_family'] );
		$this->render_number_field( 'footer_give_button_font_weight', __( 'Give button font weight', 'sog-unc-rebrand' ), (int) $settings['footer_give_button_font_weight'] );
		$this->render_text_field( 'footer_give_button_font_style', __( 'Give button font style', 'sog-unc-rebrand' ), (string) $settings['footer_give_button_font_style'] );
		$this->render_number_field( 'footer_give_button_font_size', __( 'Give button font size (px)', 'sog-unc-rebrand' ), (int) $settings['footer_give_button_font_size'] );
		$this->render_text_field( 'footer_give_button_font_line_height', __( 'Give button font line height', 'sog-unc-rebrand' ), (string) $settings['footer_give_button_font_line_height'] );
		$this->render_checkbox_field( 'footer_give_button_new_tab', __( 'Open give button link in new tab', 'sog-unc-rebrand' ), (bool) $settings['footer_give_button_new_tab'] );
		$this->render_select_field( 'footer_give_button_text_transform', __( 'Give button text transform', 'sog-unc-rebrand' ), (string) ( $settings['footer_give_button_text_transform'] ?? $defaults['footer_give_button_text_transform'] ), $this->get_text_transform_style_options() );
		$this->render_select_field( 'footer_give_button_border_style', __( 'Give button border style', 'sog-unc-rebrand' ), (string) $settings['footer_give_button_border_style'], $this->get_border_style_options() );
		$this->render_number_field( 'footer_give_button_border_thickness', __( 'Give button border thickness (px)', 'sog-unc-rebrand' ), (int) $settings['footer_give_button_border_thickness'] );
		$this->render_number_field( 'footer_give_button_border_radius', __( 'Give button border radius (px)', 'sog-unc-rebrand' ), (int) $settings['footer_give_button_border_radius'] );
		$this->render_select_field( 'footer_give_button_text_decoration', __( 'Give button text decoration', 'sog-unc-rebrand' ), (string) $settings['footer_give_button_text_decoration'], $this->get_text_decoration_style_options() );
		$this->render_number_field( 'footer_give_button_padding_top', __( 'Give button padding top (px)', 'sog-unc-rebrand' ), (int) $settings['footer_give_button_padding_top'] );
		$this->render_number_field( 'footer_give_button_padding_right', __( 'Give button padding right (px)', 'sog-unc-rebrand' ), (int) $settings['footer_give_button_padding_right'] );
		$this->render_number_field( 'footer_give_button_padding_bottom', __( 'Give button padding bottom (px)', 'sog-unc-rebrand' ), (int) $settings['footer_give_button_padding_bottom'] );
		$this->render_number_field( 'footer_give_button_padding_left', __( 'Give button padding left (px)', 'sog-unc-rebrand' ), (int) $settings['footer_give_button_padding_left'] );
		$this->render_section_end();

		$this->render_section_start(
			__( 'Footer Bottom', 'sog-unc-rebrand' ),
			__( 'The footer bottom row is the lowest section of the footer, separated by a horizontal line. It can contain a copyright notice and a menu. The menu will automatically be created if there isn\'t one and it will contain the unc campus accessibility and privacy policy', 'sog-unc-rebrand' )
		);
		$this->render_checkbox_field( 'footer_bottom_enabled', __( 'Enable footer bottom row', 'sog-unc-rebrand' ), (bool) $settings['footer_bottom_enabled'] );
		$this->render_checkbox_field( 'footer_bottom_show_copyright', __( 'Show copyright text', 'sog-unc-rebrand' ), (bool) $settings['footer_bottom_show_copyright'], array( 'condition_field' => 'footer_bottom_enabled', 'condition_value' => '1' ) );
		$this->render_checkbox_field( 'footer_bottom_show_menu', __( 'Show footer bottom menu', 'sog-unc-rebrand' ), (bool) $settings['footer_bottom_show_menu'], array( 'condition_field' => 'footer_bottom_enabled', 'condition_value' => '1' ) );
		$this->render_checkbox_field( 'footer_bottom_hide_mobile', __( 'Hide entire footer bottom row on mobile', 'sog-unc-rebrand' ), (bool) $settings['footer_bottom_hide_mobile'], array( 'condition_field' => 'footer_bottom_enabled', 'condition_value' => '1' ) );
		$this->render_text_field( 'footer_bottom_copyright_text', __( 'Copyright text', 'sog-unc-rebrand' ), (string) $settings['footer_bottom_copyright_text'], __( 'Use {year} as a token for the current year.', 'sog-unc-rebrand' ), array( 'condition_field' => 'footer_bottom_show_copyright', 'condition_value' => '1' ) );
		$this->render_section_end();


	}

	/**
	 * Get merged plugin settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_settings(): array {
		$saved = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$settings = wp_parse_args( $saved, $this->get_defaults() );


		$settings['header_social_links'] = $this->normalize_social_links( $settings['header_social_links'] ?? array() );
		$settings['footer_social_links'] = $this->normalize_social_links( $settings['footer_social_links'] ?? array() );

		return $settings;
	}

	/**
	 * Return the default plugin configuration.
	 *
	 * @return array<string, mixed>
	 */
	public function get_defaults(): array {
		return array(
			'header_enabled'                                => true,
			'utility_bar_enabled'                           => false,
			'load_frontend_fonts'                           => false,
			'container_width'                               => 1280,
			'container_width_medium_mobile'                 => 600,
			'container_width_small_mobile'                  => 480,
			'mobile_breakpoint'                             => 960,
			'header_hook_mode'                              => 'known',
			'header_known_hook'                             => 'wp_body_open',
			'header_custom_hook'                            => '',
			'header_hook'                                   => 'wp_body_open',
			'header_hook_priority'                          => 20,
			'header_core_variant'                           => 'image-logo',
			'header_logo_link_behavior'                     => 'homepage',
			'header_logo_custom_url'                        => '',
			'header_logo_image_url'                         => '',
			'header_school_name'                            => __( 'School of Government', 'sog-unc-rebrand' ),
			'header_text_main'                              => get_bloginfo( 'name' ),
			'header_text_subtext'                           => get_bloginfo( 'description' ),
			'header_text_links_enabled'                     => false,
			'header_separator_style'                        => 'solid',
			'header_separator_color'                        => '#ffffff',
			'header_separator_thickness'                    => 1,
			'header_separator_hide_mobile'                  => false,
			'header_core_background_color'                  => '#1E3A57',
			'header_school_name_color'                      => '#ffffff',
			'header_text_color'                             => '#ffffff',
			'header_subtext_color'                          => '#d0d7e2',
			'header_school_name_text_transform'             => 'none',
			'header_site_name_text_transform'               => 'none',
			'header_site_description_text_transform'        => 'none',
			'header_main_menu_enabled'                      => true,
			'header_bottom_nav_enabled'                     => true,
			'header_navigation_font_family'		            => 'Poppins, sans-serif',
			'header_navigation_font_weight'		            => 600,
			'header_navigation_font_style'		            => 'normal',
			'header_navigation_font_size'		            => 16,
			'header_navigation_text_transform'	            => 'none',
			'header_navigation_text_decoration'	            => 'none',
			'header_navigation_item_padding_top'            => 0,
			'header_navigation_item_padding_right'          => 0,
			'header_navigation_item_padding_bottom'         => 0,
			'header_navigation_item_padding_left'           => 0,
			'header_submenu_navigation_item_padding_top'    => 0,
			'header_submenu_navigation_item_padding_right'  => 0,
			'header_submenu_navigation_item_padding_bottom' => 0,
			'header_submenu_navigation_item_padding_left'   => 0,
			'header_mobile_navigation_item_padding_top'     => 0,
			'header_mobile_navigation_item_padding_right'   => 0,
			'header_mobile_navigation_item_padding_bottom'  => 0,
			'header_mobile_navigation_item_padding_left'    => 0,
			'header_mobile_menu_text_color'		            => '#ffffff',
			'header_mobile_menu_hover_color'	            => '#d0d7e2',
			'header_mobile_menu_background_color'           => '#1E3A57',
			'header_bottom_orientation'                     => 'horizontal',
			'header_bottom_alignment'                       => 'space-between',
			'header_bottom_spacing'                         => 24,
			'header_bottom_mobile_mode'                     => 'hamburger',
			'header_bottom_background_color'                => '#1E3A57',
			'header_bottom_text_color'                      => '#ffffff',
			'display_site_search_enabled'                   => false,
			'display_site_search_mobile_enabled'            => false,
			'site_search_inline_with_nav'                   => false,
			'site_search_inline_with_header'                => false,
			'site_search_placeholder_text'                  => __( 'Search the site...', 'sog-unc-rebrand' ),
			'header_search_icon_enabled'                    => true,
			'header_search_button_text'                     => __( 'Search', 'sog-unc-rebrand' ),
			'header_give_button_text'                       => __( 'Give Now', 'sog-unc-rebrand' ),
			'header_give_button_url'                        => '',
			'header_give_button_background_color'           => '#4b9cd3',
			'header_give_button_hover_color'                => '#1E3A57',
			'header_give_button_text_color'                 => '#ffffff',
			'header_give_button_new_tab'                    => true,
			'header_give_button_text_transform'             => 'none',
			'header_give_button_padding_top' 	            => 14,
			'header_give_button_padding_right'              => 32,
			'header_give_button_padding_bottom'             => 14,
			'header_give_button_padding_left'               => 32,
			'header_give_button_font_family'                => 'Poppins, sans-serif',
			'header_give_button_font_weight'                => 600,
			'header_give_button_font_style'                 => 'normal',
			'header_give_button_font_size'                  => 16,
			'header_give_button_font_line_height'           => '',
			'header_give_button_border_color'               => '#4b9cd3',
			'header_give_button_border_thickness'           => 1,
			'header_give_button_border_radius'              => 70,
			'header_give_button_border_style'               => 'solid',
			'header_give_button_text_decoration'            => 'none',
			'header_give_button_hide_mobile'                => false,
			'header_social_links_alignment'                 => '',
			'header_social_links'                           => array(),
			'header_social_links_hide_mobile'               => false,
			'header_special_button_text'                    => '',
			'header_special_button_url'                     => '',
			'header_special_button_new_tab'                 => false,
			'header_special_button_hide_mobile'             => false,
			'header_special_button_background_color'        => '#4b9cd3',
			'header_special_button_hover_color'             => '#1E3A57',
			'header_special_button_text_color'              => '#ffffff',
			'header_special_button_border_color'            => '#4b9cd3',
			'header_special_button_border_thickness'        => 1,
			'header_special_button_border_radius'           => 0,
			'header_special_button_border_style'            => 'solid',
			'header_special_font_family'                    => 'Poppins, sans-serif',
			'header_special_font_weight'                    => 600,
			'header_special_font_style'                     => 'normal',
			'header_special_font_size'                      => 16,
			'header_special_text_transform'                 => 'capitalize',
			'utility_bar_menu_fallback_enabled'            => true,
			'utility_bar_brand_logo_url'                   => '',
			'utility_bar_brand_logo_width'                 => 40,
			'utility_bar_brand_logo_height'                => 35,
			'utility_bar_brand_label'                      => __( 'The University of North Carolina at Chapel Hill', 'sog-unc-rebrand' ),
			'utility_bar_background_color'                 => '#4b9cd3',
			'utility_bar_text_color'                       => '#1E3A57',
			'footer_enabled'                               => true,
			'footer_hook_mode'                             => 'known',
			'footer_known_hook'                            => 'wp_footer',
			'footer_custom_hook'                           => '',
			'footer_hook'                                  => 'wp_footer',
			'footer_hook_priority'                         => 20,
			'footer_background_color'                      => '#1E3A57',
			'footer_text_color'                            => '#ffffff',
			'footer_heading_color'                         => '#ffffff',
			'footer_link_color'                            => '#ffffff',
			'footer_link_hover_color'                      => '#d0d7e2',
			'footer_muted_text_color'                      => '#d0d7e2',
			'footer_top_text_enabled'                      => false,
			'footer_top_text_heading'                      => __( 'About the School of Government', 'sog-unc-rebrand' ),
			'footer_top_text_content'                      => '',
			'footer_logos_enabled'                         => false,
			'footer_logos_orientation'                     => 'horizontal',
			'footer_logos_alignment'                       => 'center',
			'footer_logos_spacing'                         => 24,
			'footer_logos_mobile_carousel'                 => false,
			'footer_logos_max_height'                      => 80,
			'footer_logos_max_total_height'                => 160,
			'footer_separator_1_style'                     => 'dashed',
			'footer_separator_1_color'                     => '#ffffff',
			'footer_separator_1_thickness'                 => 1,
			'footer_separator_1_hide_mobile'               => false,
			'footer_separator_2_style'                     => 'solid',
			'footer_separator_2_style_mobile'              => 'dashed',
			'footer_separator_2_color'                     => '#ffffff',
			'footer_separator_2_thickness'                 => 1,
			'footer_separator_2_hide_mobile'               => false,
			'footer_column_gap'                            => 41,
			'footer_column_2_gap'                          => 41,
			'footer_social_links_below_columns'            => false,
			'footer_social_links_orientation'              => 'horizontal',
			'footer_social_links_alignment'                => '',
			'footer_social_links'                          => array(),
			'footer_social_links_hide_mobile'              => false,
			'footer_bottom_enabled'                        => true,
			'footer_bottom_show_copyright'                 => true,
			'footer_bottom_show_menu'                      => true,
			'footer_bottom_hide_mobile'                    => false,
			'footer_bottom_copyright_text'                 => __( '© Copyright {year}, The University of North Carolina at Chapel Hill', 'sog-unc-rebrand' ),
			'footer_give_button_orientation'               => 'horizontal',
			'footer_give_button_alignment'                 => '',
			'footer_give_button_text'                      => __( 'Give Now', 'sog-unc-rebrand' ),
			'footer_give_button_url'                       => '',
			'footer_give_button_padding_top' 	           => 14,
			'footer_give_button_padding_right'             => 32,
			'footer_give_button_padding_bottom'            => 14,
			'footer_give_button_padding_left'              => 32,
			'footer_give_button_background_color'          => '#4b9cd3',
			'footer_give_button_hover_color'               => '#1E3A57',
			'footer_give_button_text_color'                => '#ffffff',
			'footer_give_button_new_tab'                   => true,
			'footer_give_button_font_family'               => 'Poppins, sans-serif',
			'footer_give_button_font_weight'               => 600,
			'footer_give_button_font_style'                => 'normal',
			'footer_give_button_font_size'                 => 16,
			'footer_give_button_font_line_height'          => '',
			'footer_give_button_text_transform'            => 'none',
			'footer_give_button_border_color'              => '#4b9cd3',
			'footer_give_button_border_thickness'          => 1,
			'footer_give_button_border_radius'             => 70,
			'footer_give_button_border_style'              => 'solid',
			'footer_give_button_text_decoration'           => 'none',
			'footer_give_button_below_columns'             => false,
			'footer_give_button_hide_mobile'               => false,
			'footer_give_social_gap'                       => 20,
			'excluded_post_ids'                            => array(),
			'excluded_post_types'                          => array(),
			'excluded_templates'                           => array(),
			'exclude_front_page'                           => false,
			'exclude_posts_page'                           => false,
			'exclude_search'                               => false,
			'exclude_404'                                  => false,
			'footer_logo_1_type'                           => 'none',
			'footer_logo_1_image_url'                      => '',
			'footer_logo_1_text_upper'                     => '',
			'footer_logo_1_text_lower'                     => '',
			'footer_logo_1_link_url'                       => '',
			'footer_logo_1_link_new_tab'                   => false,
			'footer_logo_1_hide_mobile'                    => false,
			'footer_logo_2_type'                           => 'none',
			'footer_logo_2_image_url'                      => '',
			'footer_logo_2_text_upper'                     => '',
			'footer_logo_2_text_lower'                     => '',
			'footer_logo_2_link_url'                       => '',
			'footer_logo_2_link_new_tab'                   => false,
			'footer_logo_2_hide_mobile'                    => false,
			'footer_logo_3_type'                           => 'none',
			'footer_logo_3_image_url'                      => '',
			'footer_logo_3_text_upper'                     => '',
			'footer_logo_3_text_lower'                     => '',
			'footer_logo_3_link_url'                       => '',
			'footer_logo_3_link_new_tab'                   => false,
			'footer_logo_3_hide_mobile'                    => false,
			'footer_column_1_mode'                         => 'wysiwyg',
			'footer_column_1_width'                        => 40,
			'footer_column_1_hide_mobile'                  => false,
			'footer_column_1_copyright_enabled'            => true,
			'footer_column_1_content'                      => '',
			'footer_column_1_shortcode'                    => '',
			'footer_column_1_heading'                      => '',
			'footer_column_1_heading_alignment'            => 'left',
			'footer_column_1_heading_text_transform'       => 'none',
			'footer_column_1_heading_text_decoration'      => 'none',
			'footer_column_2_mode'                         => 'none',
			'footer_column_2_width'                        => 30,
			'footer_column_2_hide_mobile'                  => false,
			'footer_column_2_copyright_enabled'            => false,
			'footer_column_2_content'                      => '',
			'footer_column_2_shortcode'                    => '',
			'footer_column_2_heading'                      => '',
			'footer_column_2_heading_alignment'            => 'left',
			'footer_column_2_heading_text_transform'       => 'none',
			'footer_column_2_heading_text_decoration'      => 'none',
			'footer_column_3_mode'                         => 'none',
			'footer_column_3_width'                        => 30,
			'footer_column_3_hide_mobile'                  => false,
			'footer_column_3_copyright_enabled'            => false,
			'footer_column_3_content'                      => '',
			'footer_column_3_shortcode'                    => '',
			'footer_column_3_heading'                      => '',
			'footer_column_3_heading_alignment'            => 'left',
			'footer_column_3_heading_text_transform'       => 'none',
			'footer_column_3_heading_text_decoration'      => 'none',
		);
	}

	/**
	 * Sanitize settings before persistence.
	 *
	 * @param mixed $input Raw settings payload.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ): array {
		$defaults      = $this->get_defaults();
		$input         = is_array( $input ) ? $input : array();
		$settings_page = sanitize_key( $input['settings_page'] ?? 'sog-unc-rebrand' );

		if ( 'sog-unc-rebrand-presets' === $settings_page ) {
			return $this->sanitize_complete_settings( $input, $defaults );
		}

		$saved         = get_option( self::OPTION_NAME, array() );
		$saved         = is_array( $saved ) ? $saved : array();
		$input         = $this->merge_subpage_settings( $settings_page, $saved, $input );

		return $this->sanitize_complete_settings( $input, $defaults );
	}

	/**
	 * Sanitize a complete settings payload before persistence.
	 *
	 * @param array<string, mixed> $input Raw settings payload.
	 * @param array<string, mixed>|null $defaults Optional defaults cache.
	 * @return array<string, mixed>
	 */
	private function sanitize_complete_settings( array $input, ?array $defaults = null ): array {
		$defaults = is_array( $defaults ) ? $defaults : $this->get_defaults();

		$header_hook_mode   = $this->sanitize_enum( $input['header_hook_mode'] ?? '', array_keys( $this->get_hook_mode_options() ), $defaults['header_hook_mode'] );
		$footer_hook_mode   = $this->sanitize_enum( $input['footer_hook_mode'] ?? '', array_keys( $this->get_hook_mode_options() ), $defaults['footer_hook_mode'] );
		$header_known_hook  = $this->sanitize_enum( $input['header_known_hook'] ?? '', array_keys( $this->get_known_header_hooks() ), $defaults['header_known_hook'] );
		$footer_known_hook  = $this->sanitize_enum( $input['footer_known_hook'] ?? '', array_keys( $this->get_known_footer_hooks() ), $defaults['footer_known_hook'] );

		// Sanitize site search fields
		$header_custom_hook = $this->sanitize_hook_name( $input['header_custom_hook'] ?? '' );
		$footer_custom_hook = $this->sanitize_hook_name( $input['footer_custom_hook'] ?? '' );

		$sanitized = array(
			'header_enabled'                       		        => ! empty( $input['header_enabled'] ),
			'utility_bar_enabled'                  		        => ! empty( $input['utility_bar_enabled'] ),
			'load_frontend_fonts'                  		        => ! empty( $input['load_frontend_fonts'] ),
			'container_width'                       		    => $this->sanitize_int_at_least( $input['container_width'] ?? $defaults['container_width'], 320, (int) $defaults['container_width'] ),
			'container_width_medium_mobile'         		    => $this->sanitize_int_at_least( $input['container_width_medium_mobile'] ?? $defaults['container_width_medium_mobile'], 320, (int) $defaults['container_width_medium_mobile'] ),
			'container_width_small_mobile'          		    => $this->sanitize_int_at_least( $input['container_width_small_mobile'] ?? $defaults['container_width_small_mobile'], 320, (int) $defaults['container_width_small_mobile'] ),
			'mobile_breakpoint'                    		        => $this->sanitize_int_at_least( $input['mobile_breakpoint'] ?? $defaults['mobile_breakpoint'], 320, (int) $defaults['mobile_breakpoint'] ),
			'header_hook_mode'                     		        => $header_hook_mode,
			'header_known_hook'                    		        => $header_known_hook,
			'header_custom_hook'                   		        => $header_custom_hook,
			'header_hook'                           	  	    => 'custom' === $header_hook_mode && $header_custom_hook ? $header_custom_hook : $header_known_hook,
			'header_hook_priority'                 		        => absint( $input['header_hook_priority'] ?? $defaults['header_hook_priority'] ),
			'header_core_variant'                  		        => $this->sanitize_enum( $input['header_core_variant'] ?? '', array_keys( $this->get_header_core_variant_options() ), $defaults['header_core_variant'] ),
			'header_logo_link_behavior'            		        => $this->sanitize_enum( $input['header_logo_link_behavior'] ?? '', array_keys( $this->get_logo_link_behavior_options() ), $defaults['header_logo_link_behavior'] ),
			'header_logo_custom_url'                		    => esc_url_raw( (string) ( $input['header_logo_custom_url'] ?? '' ) ),
			'header_logo_image_url'               		        => esc_url_raw( (string) ( $input['header_logo_image_url'] ?? '' ) ),
			'header_school_name'                 		        => sanitize_text_field( $input['header_school_name'] ?? '' ),
			'header_text_main'                    		        => sanitize_text_field( $input['header_text_main'] ?? '' ),
			'header_text_subtext'                 		        => sanitize_text_field( $input['header_text_subtext'] ?? '' ),
			'header_text_links_enabled'          		        => ! empty( $input['header_text_links_enabled'] ),
			'header_separator_style'              		        => $this->sanitize_enum( $input['header_separator_style'] ?? '', array_keys( $this->get_separator_style_options() ), $defaults['header_separator_style'] ),
			'header_separator_color'              		        => $this->sanitize_hex_color_with_default( $input['header_separator_color'] ?? '', $defaults['header_separator_color'] ),
			'header_separator_thickness'           		        => absint( $input['header_separator_thickness'] ?? $defaults['header_separator_thickness'] ),
			'header_separator_hide_mobile'           		    => ! empty( $input['header_separator_hide_mobile'] ),
			'header_core_background_color'          		    => $this->sanitize_hex_color_with_default( $input['header_core_background_color'] ?? '', $defaults['header_core_background_color'] ),
			'header_school_name_color'              		    => $this->sanitize_hex_color_with_default( $input['header_school_name_color'] ?? '', $defaults['header_school_name_color'] ),
			'header_text_color'                      		    => $this->sanitize_hex_color_with_default( $input['header_text_color'] ?? '', $defaults['header_text_color'] ),
			'header_subtext_color'                    		    => $this->sanitize_hex_color_with_default( $input['header_subtext_color'] ?? '', $defaults['header_subtext_color'] ),
			'header_school_name_text_transform'       		    => $this->sanitize_enum( $input['header_school_name_text_transform'] ?? '', array_keys( $this->get_text_transform_style_options() ), $defaults['header_school_name_text_transform'] ),
			'header_site_name_text_transform'        		    => $this->sanitize_enum( $input['header_site_name_text_transform'] ?? '', array_keys( $this->get_text_transform_style_options() ), $defaults['header_site_name_text_transform'] ),
			'header_site_description_text_transform'  		    => $this->sanitize_enum( $input['header_site_description_text_transform'] ?? '', array_keys( $this->get_text_transform_style_options() ), $defaults['header_site_description_text_transform'] ),
			'header_main_menu_enabled'                		    => ! empty( $input['header_main_menu_enabled'] ),
			'header_bottom_nav_enabled'               		    => ! empty( $input['header_bottom_nav_enabled'] ),
			'header_navigation_font_family'		      		    => sanitize_text_field( $input['header_navigation_font_family'] ?? '' ),
			'header_navigation_font_weight'		      		    => absint( $input['header_navigation_font_weight'] ?? $defaults['header_navigation_font_weight'] ),
			'header_navigation_font_style'		      		    => sanitize_text_field( $input['header_navigation_font_style'] ?? '' ),
			'header_navigation_font_size'		      		    => absint( $input['header_navigation_font_size'] ?? $defaults['header_navigation_font_size'] ),
			'header_navigation_text_transform'	      		    => $this->sanitize_enum( $input['header_navigation_text_transform'] ?? '', array_keys( $this->get_text_transform_style_options() ), $defaults['header_navigation_text_transform'] ),
			'	header_navigation_text_decoration'	  		    => $this->sanitize_enum( $input['header_navigation_text_decoration'] ?? '', array_keys( $this->get_text_decoration_style_options() ), $defaults['header_navigation_text_decoration'] ),
			'header_navigation_item_padding_top'				=> absint( $input['header_navigation_item_padding_top'] ?? $defaults['header_navigation_item_padding_top'] ),
			'header_navigation_item_padding_right'				=> absint( $input['header_navigation_item_padding_right'] ?? $defaults['header_navigation_item_padding_right'] ),
			'header_navigation_item_padding_bottom'				=> absint( $input['header_navigation_item_padding_bottom'] ?? $defaults['header_navigation_item_padding_bottom'] ),
			'header_navigation_item_padding_left'				=> absint( $input['header_navigation_item_padding_left'] ?? $defaults['header_navigation_item_padding_left'] ),
			'header_submenu_navigation_item_padding_top'		=> absint( $input['header_submenu_navigation_item_padding_top'] ?? $defaults['header_submenu_navigation_item_padding_top'] ),
			'header_submenu_navigation_item_padding_right'		=> absint( $input['header_submenu_navigation_item_padding_right'] ?? $defaults['header_submenu_navigation_item_padding_right'] ),
			'header_submenu_navigation_item_padding_bottom'		=> absint( $input['header_submenu_navigation_item_padding_bottom'] ?? $defaults['header_submenu_navigation_item_padding_bottom'] ),
			'header_submenu_navigation_item_padding_left'		=> absint( $input['header_submenu_navigation_item_padding_left'] ?? $defaults['header_submenu_navigation_item_padding_left'] ),
			'header_mobile_navigation_item_padding_top'		    => absint( $input['header_mobile_navigation_item_padding_top'] ?? $defaults['header_mobile_navigation_item_padding_top'] ),
			'header_mobile_navigation_item_padding_right'		=> absint( $input['header_mobile_navigation_item_padding_right'] ?? $defaults['header_mobile_navigation_item_padding_right'] ),
			'header_mobile_navigation_item_padding_bottom'		=> absint( $input['header_mobile_navigation_item_padding_bottom'] ?? $defaults['header_mobile_navigation_item_padding_bottom'] ),
			'header_mobile_navigation_item_padding_left'		=> absint( $input['header_mobile_navigation_item_padding_left'] ?? $defaults['header_mobile_navigation_item_padding_left'] ),
			'header_bottom_orientation'              => $this->sanitize_enum( $input['header_bottom_orientation'] ?? '', array_keys( $this->get_orientation_options() ), $defaults['header_bottom_orientation'] ),
			'header_bottom_alignment'                => $this->sanitize_enum( $input['header_bottom_alignment'] ?? '', array_keys( $this->get_alignment_options() ), $defaults['header_bottom_alignment'] ),
			'header_bottom_spacing'                  => absint( $input['header_bottom_spacing'] ?? $defaults['header_bottom_spacing'] ),
			'header_bottom_mobile_mode'              => $this->sanitize_enum( $input['header_bottom_mobile_mode'] ?? '', array_keys( $this->get_mobile_menu_mode_options() ), $defaults['header_bottom_mobile_mode'] ),
			'header_bottom_background_color'         => $this->sanitize_hex_color_with_default( $input['header_bottom_background_color'] ?? '', $defaults['header_bottom_background_color'] ),
			'header_bottom_text_color'               => $this->sanitize_hex_color_with_default( $input['header_bottom_text_color'] ?? '', $defaults['header_bottom_text_color'] ),
			'display_site_search_enabled'            => !empty($input['display_site_search_enabled']),
			'display_site_search_mobile_enabled'     => !empty($input['display_site_search_mobile_enabled']),
			'site_search_inline_with_nav'            => !empty($input['site_search_inline_with_nav']),
			'site_search_inline_with_header'         => !empty($input['site_search_inline_with_header']),
			'site_search_placeholder_text'           => sanitize_text_field($input['site_search_placeholder_text'] ?? $defaults['site_search_placeholder_text']),
			'header_search_icon_enabled'             => !empty($input['header_search_icon_enabled']),
			'header_search_button_text'              => sanitize_text_field($input['header_search_button_text'] ?? $defaults['header_search_button_text']),
			'header_give_button_text'                => sanitize_text_field( $input['header_give_button_text'] ?? $defaults['header_give_button_text'] ),
			'header_give_button_url'                 => esc_url_raw( $input['header_give_button_url'] ?? $defaults['header_give_button_url'] ),
			'header_give_button_background_color'    => $this->sanitize_hex_color_with_default( $input['header_give_button_background_color'] ?? '', $defaults['header_give_button_background_color'] ),
			'header_give_button_hover_color'         => $this->sanitize_hex_color_with_default( $input['header_give_button_hover_color'] ?? '', $defaults['header_give_button_hover_color'] ),
			'header_give_button_text_color'          => $this->sanitize_hex_color_with_default( $input['header_give_button_text_color'] ?? '', $defaults['header_give_button_text_color'] ),
			'header_give_button_new_tab'             => ! empty( $input['header_give_button_new_tab'] ),
			'header_give_button_font_family'         => sanitize_text_field( $input['header_give_button_font_family'] ?? $defaults['header_give_button_font_family'] ),
			'header_give_button_font_weight'         => absint( $input['header_give_button_font_weight'] ?? $defaults['header_give_button_font_weight'] ),
			'header_give_button_font_style'          => sanitize_text_field( $input['header_give_button_font_style'] ?? $defaults['header_give_button_font_style'] ),
			'header_give_button_font_size'           => absint( $input['header_give_button_font_size'] ?? $defaults['header_give_button_font_size'] ),
			'header_give_button_font_line_height'    => sanitize_text_field( $input['header_give_button_font_line_height'] ?? $defaults['header_give_button_font_line_height'] ),
			'header_give_button_text_transform'      => $this->debug_log_enum('header_give_button_text_transform', $input['header_give_button_text_transform'] ?? '', $this->get_text_transform_style_options(), $defaults['header_give_button_text_transform'] ),
			'header_give_button_border_color'        => $this->sanitize_hex_color_with_default( $input['header_give_button_border_color'] ?? '', $defaults['header_give_button_border_color'] ),
			'header_give_button_border_thickness'    => absint( $input['header_give_button_border_thickness'] ?? $defaults['header_give_button_border_thickness'] ),
			'header_give_button_border_radius'       => absint( $input['header_give_button_border_radius'] ?? $defaults['header_give_button_border_radius'] ),
			'header_give_button_border_style'        => $this->sanitize_enum( $input['header_give_button_border_style'] ?? '', $this->get_border_style_options(), $defaults['header_give_button_border_style'] ),
			'header_give_button_text_decoration'     => $this->sanitize_enum( $input['header_give_button_text_decoration'] ?? '', $this->get_text_decoration_style_options(), $defaults['header_give_button_text_decoration'] ),
			'header_give_button_padding_top' 	     => absint( $input['header_give_button_padding_top'] ?? $defaults['header_give_button_padding_top'] ),
			'header_give_button_padding_right'       => absint( $input['header_give_button_padding_right'] ?? $defaults['header_give_button_padding_right'] ),
			'header_give_button_padding_bottom'      => absint( $input['header_give_button_padding_bottom'] ?? $defaults['header_give_button_padding_bottom'] ),
			'header_give_button_padding_left'        => absint( $input['header_give_button_padding_left'] ?? $defaults['header_give_button_padding_left'] ),
			'header_give_button_hide_mobile'         => ! empty( $input['header_give_button_hide_mobile'] ),
			'header_social_links_hide_mobile'        => ! empty( $input['header_social_links_hide_mobile'] ),
			'header_social_links_alignment'          => $this->sanitize_enum( $input['header_social_links_alignment'] ?? '', array_keys( $this->get_alignment_options() ), $defaults['header_social_links_alignment'] ),
			'header_social_links'                    => $this->sanitize_social_links( $input['header_social_links'] ?? array() ),
			'header_special_button_text'             => sanitize_text_field( $input['header_special_button_text'] ?? $defaults['header_special_button_text'] ),
			'header_special_button_url'              => esc_url_raw( $input['header_special_button_url'] ?? $defaults['header_special_button_url'] ),
			'header_special_button_new_tab'          => ! empty( $input['header_special_button_new_tab'] ),
			'header_special_button_hide_mobile'      => ! empty( $input['header_special_button_hide_mobile'] ),
			'header_special_button_background_color' => $this->sanitize_hex_color_with_default( $input['header_special_button_background_color'] ?? '', $defaults['header_special_button_background_color'] ),
			'header_special_button_hover_color'      => $this->sanitize_hex_color_with_default( $input['header_special_button_hover_color'] ?? '', $defaults['header_special_button_hover_color'] ),
			'header_special_button_text_color'       => $this->sanitize_hex_color_with_default( $input['header_special_button_text_color'] ?? '', $defaults['header_special_button_text_color'] ),
			'header_special_button_border_color'     => $this->sanitize_hex_color_with_default( $input['header_special_button_border_color'] ?? '', $defaults['header_special_button_border_color'] ),
			'header_special_button_border_thickness' => absint( $input['header_special_button_border_thickness'] ?? $defaults['header_special_button_border_thickness'] ),
			'header_special_button_border_radius'    => absint( $input['header_special_button_border_radius'] ?? $defaults['header_special_button_border_radius'] ),
			'header_special_button_border_style'     => $this->sanitize_enum( $input['header_special_button_border_style'] ?? '', $this->get_border_style_options(), $defaults['header_special_button_border_style'] ),
			'header_special_font_family'             => sanitize_text_field( $input['header_special_font_family'] ?? $defaults['header_special_font_family'] ),
			'header_special_font_weight'             => absint( $input['header_special_font_weight'] ?? $defaults['header_special_font_weight'] ),
			'header_special_font_style'              => sanitize_text_field( $input['header_special_font_style'] ?? $defaults['header_special_font_style'] ),
			'header_special_font_size'               => absint( $input['header_special_font_size'] ?? $defaults['header_special_font_size'] ),
			'header_special_text_transform'		     => $this->sanitize_enum( $input['header_special_text_transform'] ?? '', $this->get_text_transform_style_options(), $defaults['header_special_text_transform'] ),
			'header_special_button_padding_top'      => absint( $input['header_special_button_padding_top'] ?? $defaults['header_special_button_padding_top'] ),
			'header_special_button_padding_right'    => absint( $input['header_special_button_padding_right'] ?? $defaults['header_special_button_padding_right'] ),
			'header_special_button_padding_bottom'   => absint( $input['header_special_button_padding_bottom'] ?? $defaults['header_special_button_padding_bottom'] ),
			'header_special_button_padding_left'     => absint( $input['header_special_button_padding_left'] ?? $defaults['header_special_button_padding_left'] ),
			'utility_bar_menu_fallback_enabled'      => ! empty( $input['utility_bar_menu_fallback_enabled'] ),
			'utility_bar_brand_label'                => sanitize_text_field( $input['utility_bar_brand_label'] ?? '' ),
			'utility_bar_brand_logo_url'             => esc_url_raw( (string) ( $input['utility_bar_brand_logo_url'] ?? '' ) ),
			'utility_bar_background_color'           => $this->sanitize_hex_color_with_default( $input['utility_bar_background_color'] ?? '', $defaults['utility_bar_background_color'] ),
			'utility_bar_text_color'                 => $this->sanitize_hex_color_with_default( $input['utility_bar_text_color'] ?? '', $defaults['utility_bar_text_color'] ),
			'utility_bar_brand_logo_width'           => absint( $input['utility_bar_brand_logo_width'] ?? $defaults['utility_bar_brand_logo_width'] ),
			'utility_bar_brand_logo_height'          => absint( $input['utility_bar_brand_logo_height'] ?? $defaults['utility_bar_brand_logo_height'] ),
			'footer_enabled'                         => ! empty( $input['footer_enabled'] ),
			'footer_hook_mode'                       => $footer_hook_mode,
			'footer_known_hook'                      => $footer_known_hook,
			'footer_custom_hook'                     => $footer_custom_hook,
			'footer_hook'                            => 'custom' === $footer_hook_mode && $footer_custom_hook ? $footer_custom_hook : $footer_known_hook,
			'footer_hook_priority'                   => absint( $input['footer_hook_priority'] ?? $defaults['footer_hook_priority'] ),
			'footer_background_color'                => $this->sanitize_hex_color_with_default( $input['footer_background_color'] ?? '', $defaults['footer_background_color'] ),
			'footer_text_color'                      => $this->sanitize_hex_color_with_default( $input['footer_text_color'] ?? '', $defaults['footer_text_color'] ),
			'footer_heading_color'                   => $this->sanitize_hex_color_with_default( $input['footer_heading_color'] ?? '', $defaults['footer_heading_color'] ),
			'footer_link_color'                      => $this->sanitize_hex_color_with_default( $input['footer_link_color'] ?? '', $defaults['footer_link_color'] ),
			'footer_link_hover_color'                => $this->sanitize_hex_color_with_default( $input['footer_link_hover_color'] ?? '', $defaults['footer_link_hover_color'] ),
			'footer_muted_text_color'                => $this->sanitize_hex_color_with_default( $input['footer_muted_text_color'] ?? '', $defaults['footer_muted_text_color'] ),
			'footer_top_text_enabled'                => ! empty( $input['footer_top_text_enabled'] ),
			'footer_top_text_heading'                => sanitize_text_field( $input['footer_top_text_heading'] ?? '' ),
			'footer_top_text_content'                => wp_kses_post( $input['footer_top_text_content'] ?? '' ),
			'footer_logos_enabled'                   => ! empty( $input['footer_logos_enabled'] ),
			'footer_logos_orientation'               => $this->sanitize_enum( $input['footer_logos_orientation'] ?? '', array_keys( $this->get_orientation_options() ), $defaults['footer_logos_orientation'] ),
			'footer_logos_alignment'                 => $this->sanitize_enum( $input['footer_logos_alignment'] ?? '', array_keys( $this->get_alignment_options() ), $defaults['footer_logos_alignment'] ),
			'footer_logos_spacing'                   => absint( $input['footer_logos_spacing'] ?? $defaults['footer_logos_spacing'] ),
			'footer_logos_mobile_carousel'           => ! empty( $input['footer_logos_mobile_carousel'] ),
			'footer_logos_max_height'                => absint( $input['footer_logos_max_height'] ?? $defaults['footer_logos_max_height'] ),
			'footer_logos_max_total_height'          => absint( $input['footer_logos_max_total_height'] ?? $defaults['footer_logos_max_total_height'] ),
			'footer_separator_1_style'               => $this->sanitize_enum( $input['footer_separator_1_style'] ?? '', array_keys( $this->get_separator_style_options() ), $defaults['footer_separator_1_style'] ),
			'footer_separator_1_color'               => $this->sanitize_hex_color_with_default( $input['footer_separator_1_color'] ?? '', $defaults['footer_separator_1_color'] ),
			'footer_separator_1_thickness'           => absint( $input['footer_separator_1_thickness'] ?? $defaults['footer_separator_1_thickness'] ),
			'footer_separator_1_hide_mobile'         => ! empty( $input['footer_separator_1_hide_mobile'] ),
			'footer_separator_2_style'               => $this->sanitize_enum( $input['footer_separator_2_style'] ?? '', array_keys( $this->get_separator_style_options() ), $defaults['footer_separator_2_style'] ),
			'footer_separator_2_style_mobile'        => $this->sanitize_enum( $input['footer_separator_2_style_mobile'] ?? '', array_keys( $this->get_separator_style_options() ), $defaults['footer_separator_2_style_mobile'] ),
			'footer_separator_2_color'               => $this->sanitize_hex_color_with_default( $input['footer_separator_2_color'] ?? '', $defaults['footer_separator_2_color'] ),
			'footer_separator_2_thickness'           => absint( $input['footer_separator_2_thickness'] ?? $defaults['footer_separator_2_thickness'] ),
			'footer_separator_2_hide_mobile'         => ! empty( $input['footer_separator_2_hide_mobile'] ),
			'footer_column_gap'                      => absint( $input['footer_column_gap'] ?? $defaults['footer_column_gap'] ),
			'footer_column_2_gap'                    => absint( $input['footer_column_2_gap'] ?? $defaults['footer_column_2_gap'] ),
			'footer_social_links_below_columns'      => ! empty( $input['footer_social_links_below_columns'] ),
			'footer_social_links_orientation'        => $this->sanitize_enum( $input['footer_social_links_orientation'] ?? '', array_keys( $this->get_orientation_options() ), $defaults['footer_social_links_orientation'] ),
			'footer_social_links_alignment'          => $this->sanitize_enum( $input['footer_social_links_alignment'] ?? '', array_keys( $this->get_alignment_options() ), $defaults['footer_social_links_alignment'] ),
			'footer_social_links_hide_mobile'        => ! empty( $input['footer_social_links_hide_mobile'] ),
			'footer_social_links'                    => $this->sanitize_social_links( $input['footer_social_links'] ?? array() ),
			'footer_bottom_enabled'                  => ! empty( $input['footer_bottom_enabled'] ),
			'footer_bottom_show_copyright'           => ! empty( $input['footer_bottom_show_copyright'] ),
			'footer_bottom_show_menu'                => ! empty( $input['footer_bottom_show_menu'] ),
			'footer_bottom_hide_mobile'              => ! empty( $input['footer_bottom_hide_mobile'] ),
			'footer_bottom_copyright_text'           => sanitize_text_field( $input['footer_bottom_copyright_text'] ?? '' ),
			'footer_give_social_gap'                 => absint( $input['footer_give_social_gap'] ?? $defaults['footer_give_social_gap'] ),
			'footer_give_button_alignment'           => $this->sanitize_enum( $input['footer_give_button_alignment'] ?? '', array_keys( $this->get_alignment_options() ), $defaults['footer_give_button_alignment'] ),
			'footer_give_button_orientation'         => $this->sanitize_enum( $input['footer_give_button_orientation'] ?? '', array_keys( $this->get_orientation_options() ), $defaults['footer_give_button_orientation'] ),
			'footer_give_button_text'                => sanitize_text_field( $input['footer_give_button_text'] ?? $defaults['footer_give_button_text'] ),
			'footer_give_button_url'                 => esc_url_raw( $input['footer_give_button_url'] ?? $defaults['footer_give_button_url'] ),
			'footer_give_button_background_color'    => $this->sanitize_hex_color_with_default( $input['footer_give_button_background_color'] ?? '', $defaults['footer_give_button_background_color'] ),
			'footer_give_button_hover_color'         => $this->sanitize_hex_color_with_default( $input['footer_give_button_hover_color'] ?? '', $defaults['footer_give_button_hover_color'] ),
			'footer_give_button_text_color'          => $this->sanitize_hex_color_with_default( $input['footer_give_button_text_color'] ?? '', $defaults['footer_give_button_text_color'] ),
			'footer_give_button_new_tab'             => ! empty( $input['footer_give_button_new_tab'] ),
			'footer_give_button_font_family'         => sanitize_text_field( $input['footer_give_button_font_family'] ?? $defaults['footer_give_button_font_family'] ),
			'footer_give_button_font_weight'         => absint( $input['footer_give_button_font_weight'] ?? $defaults['footer_give_button_font_weight'] ),
			'footer_give_button_font_style'          => sanitize_text_field( $input['footer_give_button_font_style'] ?? $defaults['footer_give_button_font_style'] ),
			'footer_give_button_font_size'           => absint( $input['footer_give_button_font_size'] ?? $defaults['footer_give_button_font_size'] ),
			'footer_give_button_font_line_height'    => sanitize_text_field( $input['footer_give_button_font_line_height'] ?? $defaults['footer_give_button_font_line_height'] ),
			'footer_give_button_text_transform'      => $this->sanitize_enum( $input['footer_give_button_text_transform'] ?? '', array_keys( $this->get_text_transform_style_options() ), $defaults['footer_give_button_text_transform'] ),
			'footer_give_button_border_color'        => $this->sanitize_hex_color_with_default( $input['footer_give_button_border_color'] ?? '', $defaults['footer_give_button_border_color'] ),
			'footer_give_button_border_thickness'    => absint( $input['footer_give_button_border_thickness'] ?? $defaults['footer_give_button_border_thickness'] ),
			'footer_give_button_border_radius'       => absint( $input['footer_give_button_border_radius'] ?? $defaults['footer_give_button_border_radius'] ),
			'footer_give_button_border_style'        => $this->sanitize_enum( $input['footer_give_button_border_style'] ?? '', $this->get_border_style_options(), $defaults['footer_give_button_border_style'] ),
			'footer_give_button_text_decoration'     => $this->sanitize_enum( $input['footer_give_button_text_decoration'] ?? '', $this->get_text_decoration_style_options(), $defaults['footer_give_button_text_decoration'] ),
			'footer_give_button_below_columns'       => ! empty( $input['footer_give_button_below_columns'] ),
			'footer_give_button_padding_top' 	     => absint( $input['footer_give_button_padding_top'] ?? $defaults['footer_give_button_padding_top'] ),
			'footer_give_button_padding_right'       => absint( $input['footer_give_button_padding_right'] ?? $defaults['footer_give_button_padding_right'] ),
			'footer_give_button_padding_bottom'      => absint( $input['footer_give_button_padding_bottom'] ?? $defaults['footer_give_button_padding_bottom'] ),
			'footer_give_button_padding_left'        => absint( $input['footer_give_button_padding_left'] ?? $defaults['footer_give_button_padding_left'] ),
			'footer_give_button_hide_mobile'         => ! empty( $input['footer_give_button_hide_mobile'] ),
			'excluded_post_ids'                      => $this->sanitize_line_delimited_absint_list( $input['excluded_post_ids'] ?? array() ),
			'excluded_post_types'                    => $this->sanitize_line_delimited_key_list( $input['excluded_post_types'] ?? array() ),
			'excluded_templates'                     => $this->sanitize_line_delimited_text_list( $input['excluded_templates'] ?? array() ),
			'exclude_front_page'                     => ! empty( $input['exclude_front_page'] ),
			'exclude_posts_page'                     => ! empty( $input['exclude_posts_page'] ),
			'exclude_search'                         => ! empty( $input['exclude_search'] ),
			'exclude_404'                            => ! empty( $input['exclude_404'] ),
		);

		for ( $logo_index = 1; $logo_index <= 3; $logo_index++ ) {
			$sanitized[ 'footer_logo_' . $logo_index . '_type' ]         = $this->sanitize_enum( $input[ 'footer_logo_' . $logo_index . '_type' ] ?? '', array_keys( $this->get_footer_logo_type_options() ), $defaults[ 'footer_logo_' . $logo_index . '_type' ] );
			$sanitized[ 'footer_logo_' . $logo_index . '_image_url' ]    = esc_url_raw( (string) ( $input[ 'footer_logo_' . $logo_index . '_image_url' ] ?? '' ) );
			$sanitized[ 'footer_logo_' . $logo_index . '_text_upper' ]   = sanitize_text_field( $input[ 'footer_logo_' . $logo_index . '_text_upper' ] ?? '' );
			$sanitized[ 'footer_logo_' . $logo_index . '_text_lower' ]   = sanitize_text_field( $input[ 'footer_logo_' . $logo_index . '_text_lower' ] ?? '' );
			$sanitized[ 'footer_logo_' . $logo_index . '_link_url' ]     = esc_url_raw( (string) ( $input[ 'footer_logo_' . $logo_index . '_link_url' ] ?? '' ) );
			$sanitized[ 'footer_logo_' . $logo_index . '_link_new_tab' ] = ! empty( $input[ 'footer_logo_' . $logo_index . '_link_new_tab' ] );
			$sanitized[ 'footer_logo_' . $logo_index . '_hide_mobile' ]  = ! empty( $input[ 'footer_logo_' . $logo_index . '_hide_mobile' ] );
		}

		for ( $column_index = 1; $column_index <= 3; $column_index++ ) {
			$sanitized[ 'footer_column_' . $column_index . '_mode' ]                    = $this->sanitize_enum( $input[ 'footer_column_' . $column_index . '_mode' ] ?? '', array_keys( $this->get_footer_column_mode_options() ), $defaults[ 'footer_column_' . $column_index . '_mode' ] );
			$sanitized[ 'footer_column_' . $column_index . '_width' ]                   = absint( $input[ 'footer_column_' . $column_index . '_width' ] ?? $defaults[ 'footer_column_' . $column_index . '_width' ] );
			$sanitized[ 'footer_column_' . $column_index . '_hide_mobile' ]             = ! empty( $input[ 'footer_column_' . $column_index . '_hide_mobile' ] );
			$sanitized[ 'footer_column_' . $column_index . '_copyright_enabled' ]       = ! empty( $input[ 'footer_column_' . $column_index . '_copyright_enabled' ] );
			$sanitized[ 'footer_column_' . $column_index . '_content' ]                 = wp_kses_post( $input[ 'footer_column_' . $column_index . '_content' ] ?? '' );
			$sanitized[ 'footer_column_' . $column_index . '_shortcode' ]               = sanitize_text_field( $input[ 'footer_column_' . $column_index . '_shortcode' ] ?? '' );
			$sanitized[ 'footer_column_' . $column_index . '_heading' ]                 = sanitize_text_field( $input[ 'footer_column_' . $column_index . '_heading' ] ?? '' );
			$sanitized[ 'footer_column_' . $column_index . '_heading_alignment' ]       = $this->sanitize_enum( $input[ 'footer_column_' . $column_index . '_heading_alignment' ] ?? '', array_keys( $this->get_alignment_options() ), $defaults[ 'footer_column_' . $column_index . '_heading_alignment' ] );
			$sanitized[ 'footer_column_' . $column_index . '_heading_text_transform' ]  = $this->sanitize_enum( $input[ 'footer_column_' . $column_index . '_heading_text_transform' ] ?? '', array_keys( $this->get_text_transform_style_options() ), $defaults[ 'footer_column_' . $column_index . '_heading_text_transform' ] );
			$sanitized[ 'footer_column_' . $column_index . '_heading_text_decoration' ] = $this->sanitize_enum( $input[ 'footer_column_' . $column_index . '_heading_text_decoration' ] ?? '', array_keys( $this->get_text_decoration_style_options() ), $defaults[ 'footer_column_' . $column_index . '_heading_text_decoration' ] );
		}

		return $sanitized;
	}

	/**
	 * Export the current non-default settings as YAML.
	 *
	 * @return void
	 */
	public function handle_export_preset(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to export presets.', 'sog-unc-rebrand' ) );
		}

		check_admin_referer( 'sog_unc_rebrand_export_preset' );

		$settings = $this->get_settings();
		$diff     = $this->get_changed_settings( $settings );
		$payload  = array(
			'title'       => get_bloginfo( 'name' ) . ' ' . __( 'Preset Export', 'sog-unc-rebrand' ),
			'exported_at' => gmdate( 'c' ),
			'settings'    => $diff,
		);
		$filename = 'sog-rebrand-preset-' . gmdate( 'Y-m-d-His' ) . '.yaml';

		nocache_headers();
		header( 'Content-Type: text/yaml; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		echo Yaml::dump( $payload ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Import a user-uploaded YAML preset.
	 *
	 * @return void
	 */
	public function handle_import_preset(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to import presets.', 'sog-unc-rebrand' ) );
		}

		check_admin_referer( 'sog_unc_rebrand_import_preset' );

		if ( empty( $_FILES['sog_unc_rebrand_preset_file'] ) || ! is_array( $_FILES['sog_unc_rebrand_preset_file'] ) ) {
			$this->redirect_to_presets_page( 'error', __( 'No preset file was uploaded.', 'sog-unc-rebrand' ) );
		}

		$file = $_FILES['sog_unc_rebrand_preset_file'];

		if ( ! empty( $file['error'] ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			$this->redirect_to_presets_page( 'error', __( 'The preset upload could not be processed.', 'sog-unc-rebrand' ) );
		}

		$extension = strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );

		if ( ! in_array( $extension, array( 'yml', 'yaml' ), true ) ) {
			$this->redirect_to_presets_page( 'error', __( 'Preset files must use the .yml or .yaml extension.', 'sog-unc-rebrand' ) );
		}

		$contents = file_get_contents( $file['tmp_name'] );

		if ( false === $contents ) {
			$this->redirect_to_presets_page( 'error', __( 'The uploaded preset file could not be read.', 'sog-unc-rebrand' ) );
		}

		$preset = $this->parse_preset_payload( $contents );

		if ( empty( $preset['settings'] ) ) {
			$this->redirect_to_presets_page( 'error', __( 'The YAML file did not contain any valid preset settings.', 'sog-unc-rebrand' ) );
		}

		$result = $this->apply_preset_settings( $preset['settings'] );

		if ( ! empty( $result['mismatches'] ) ) {
			$this->redirect_to_presets_page(
				'error',
				sprintf(
					/* translators: %s: comma-separated setting keys. */
					__( 'Preset import did not persist these settings: %s', 'sog-unc-rebrand' ),
					implode( ', ', $result['mismatches'] )
				)
			);
		}

		$this->redirect_to_presets_page(
			'success',
			sprintf(
				/* translators: %d: number of applied settings. */
				_n( 'Preset imported successfully. %d setting was applied.', 'Preset imported successfully. %d settings were applied.', count( $result['applied_keys'] ), 'sog-unc-rebrand' ),
				count( $result['applied_keys'] )
			)
		);
	}

	/**
	 * Apply a discovered bundled preset.
	 *
	 * @return void
	 */
	public function handle_apply_preset(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to apply presets.', 'sog-unc-rebrand' ) );
		}

		check_admin_referer( 'sog_unc_rebrand_apply_preset' );

		$preset_id = isset( $_POST['preset_id'] ) ? sanitize_text_field( wp_unslash( $_POST['preset_id'] ) ) : '';
		$presets   = $this->get_available_presets();

		foreach ( $presets as $preset ) {
			if ( $preset['id'] !== $preset_id ) {
				continue;
			}

			$result = $this->apply_preset_settings( $preset['settings'] );

			if ( ! empty( $result['mismatches'] ) ) {
				$this->redirect_to_presets_page(
					'error',
					sprintf(
						/* translators: 1: preset title, 2: comma-separated setting keys. */
						__( 'Applied preset "%1$s", but these settings did not persist: %2$s', 'sog-unc-rebrand' ),
						$preset['title'],
						implode( ', ', $result['mismatches'] )
					)
				);
			}

			$this->redirect_to_presets_page( 'success', sprintf( __( 'Applied preset: %s', 'sog-unc-rebrand' ), $preset['title'] ) );
		}

		$this->redirect_to_presets_page( 'error', __( 'The selected preset could not be found.', 'sog-unc-rebrand' ) );
	}

	/**
	 * Merge partial subpage submissions with saved settings.
	 *
	 * @param string               $settings_page Current settings page slug.
	 * @param array<string, mixed> $saved Saved settings.
	 * @param array<string, mixed> $input Submitted settings.
	 * @return array<string, mixed>
	 */
	private function merge_subpage_settings( string $settings_page, array $saved, array $input ): array {
		$merged     = wp_parse_args( $saved, $this->get_defaults() );
		$field_keys = $this->get_page_field_keys( $settings_page );

		if ( empty( $field_keys ) ) {
			return $input;
		}

		foreach ( $field_keys as $field_key ) {
			if ( array_key_exists( $field_key, $input ) ) {
				$merged[ $field_key ] = $input[ $field_key ];
				continue;
			}

			if ( in_array( $field_key, $this->get_checkbox_field_keys(), true ) ) {
				$merged[ $field_key ] = false;
			}
		}

		$merged['settings_page'] = $settings_page;

		return $merged;
	}

	/**
	 * Get the field keys rendered on a specific admin page.
	 *
	 * @param string $settings_page Settings page slug.
	 * @return array<int, string>
	 */
	private function get_page_field_keys( string $settings_page ): array {
		$global_fields = array(
			'header_enabled',
			'footer_enabled',
			'utility_bar_enabled',
			'load_frontend_fonts',
			'container_width',
			'container_width_medium_mobile',
			'container_width_small_mobile',
			'mobile_breakpoint',
			'exclude_front_page',
			'exclude_posts_page',
			'exclude_search',
			'exclude_404',
			'excluded_post_ids',
			'excluded_post_types',
			'excluded_templates',
		);

		$header_fields = array(
			'header_hook_mode',
			'header_known_hook',
			'header_custom_hook',
			'header_hook_priority',
			'header_core_variant',
			'header_logo_link_behavior',
			'header_logo_custom_url',
			'header_logo_image_url',
			'header_school_name',
			'header_text_main',
			'header_text_subtext',
			'header_school_name_text_transform',
			'header_site_name_text_transform',
			'header_site_description_text_transform',
			'header_separator_style',
			'header_separator_thickness',
			'header_separator_hide_mobile',
			'header_text_links_enabled',
			'header_main_menu_enabled',
			'header_navigation_font_family',
			'header_navigation_font_weight',
			'header_navigation_font_style',
			'header_navigation_font_size',
			'header_navigation_text_transform',
			'header_navigation_text_decoration',
			'header_navigation_item_padding_top',
			'header_navigation_item_padding_right',
			'header_navigation_item_padding_bottom',
			'header_navigation_item_padding_left',
			'header_submenu_navigation_item_padding_top',
			'header_submenu_navigation_item_padding_right',
			'header_submenu_navigation_item_padding_bottom',
			'header_submenu_navigation_item_padding_left',
			'header_mobile_navigation_item_padding_top',
			'header_mobile_navigation_item_padding_right',
			'header_mobile_navigation_item_padding_bottom',
			'header_mobile_navigation_item_padding_left',
			'header_special_button_enabled',
			'header_special_button_text',
			'header_special_button_url',
			'header_special_button_new_tab',
			'header_special_button_hide_mobile',
			'header_special_button_border_thickness',
			'header_special_button_border_radius',
			'header_special_button_border_style',
			'header_special_font_family',
			'header_special_font_weight',
			'header_special_font_style',
			'header_special_font_size',
			'header_special_text_transform',
			'header_special_button_padding_top',
			'header_special_button_padding_right',
			'header_special_button_padding_bottom',
			'header_special_button_padding_left',
			'header_bottom_nav_enabled',
			'header_bottom_orientation',
			'header_bottom_alignment',
			'header_bottom_spacing',
			'header_bottom_mobile_mode',
			'display_site_search_enabled',
			'display_site_search_mobile_enabled',
			'site_search_inline_with_nav',
			'site_search_inline_with_header',
			'site_search_placeholder_text',
			'header_search_icon_enabled',
			'header_search_button_text',
			'header_give_button_text',
			'header_give_button_url',
			'header_give_button_new_tab',
			'header_give_button_padding_top',
			'header_give_button_padding_right',
			'header_give_button_padding_bottom',
			'header_give_button_padding_left',
			'header_give_button_font_family',
			'header_give_button_font_weight',
			'header_give_button_font_style',
			'header_give_button_font_size',
			'header_give_button_font_line_height',
			'header_give_button_border_thickness',
			'header_give_button_border_radius',
			'header_give_button_border_style',
			'header_give_button_text_transform',
			'header_give_button_text_decoration',
			'header_give_button_hide_mobile',
			'header_social_links_hide_mobile',
			'header_social_links',
			'header_social_links_alignment',
		);

		$utility_fields = array(
			'utility_bar_menu_fallback_enabled',
			'utility_bar_brand_label',
			'utility_bar_brand_logo_url',
			'utility_bar_brand_logo_width',
			'utility_bar_brand_logo_height',
		);

		$color_fields = array(
			'header_core_background_color',
			'header_school_name_color',
			'header_text_color',
			'header_subtext_color',
			'header_separator_color',
			'header_bottom_background_color',
			'header_bottom_text_color',
			'header_mobile_menu_text_color',
			'header_mobile_menu_hover_color',
			'header_mobile_menu_background_color',
			'header_give_button_background_color',
			'header_give_button_hover_color',
			'header_give_button_text_color',
			'header_give_button_border_color',
			'header_special_button_background_color',
			'header_special_button_hover_color',
			'header_special_button_text_color',
			'header_special_button_border_color',
			'utility_bar_background_color',
			'utility_bar_text_color',
			'footer_background_color',
			'footer_text_color',
			'footer_heading_color',
			'footer_link_color',
			'footer_link_hover_color',
			'footer_muted_text_color',
			'footer_separator_1_color',
			'footer_separator_2_color',
			'footer_give_button_background_color',
			'footer_give_button_hover_color',
			'footer_give_button_text_color',
			'footer_give_button_border_color',
		);

		$footer_fields = array(
			'footer_hook_mode',
			'footer_known_hook',
			'footer_custom_hook',
			'footer_hook_priority',
			'footer_top_text_enabled',
			'footer_top_text_heading',
			'footer_top_text_content',
			'footer_logos_enabled',
			'footer_logos_orientation',
			'footer_logos_alignment',
			'footer_logos_spacing',
			'footer_logos_mobile_carousel',
			'footer_logos_max_height',
			'footer_logos_max_total_height',
			'footer_separator_1_style',
			'footer_separator_1_thickness',
			'footer_separator_1_hide_mobile',
			'footer_separator_2_style',
			'footer_separator_2_style_mobile',
			'footer_separator_2_color',
			'footer_separator_2_thickness',
			'footer_separator_2_hide_mobile',
			'footer_column_gap',
			'footer_column_2_gap',
			'footer_social_links_below_columns',
			'footer_social_links_hide_mobile',
			'footer_social_links_orientation',
			'footer_social_links_alignment',
			'footer_social_links',
			'footer_bottom_enabled',
			'footer_bottom_show_copyright',
			'footer_bottom_show_menu',
			'footer_bottom_hide_mobile',
			'footer_bottom_copyright_text',
			'footer_give_button_hide_mobile',
			'footer_give_button_text',
			'footer_give_button_url',
			'footer_give_button_new_tab',
			'footer_give_button_padding_top',
			'footer_give_button_padding_right',
			'footer_give_button_padding_bottom',
			'footer_give_button_padding_left',
			'footer_give_button_font_family',
			'footer_give_button_font_weight',
			'footer_give_button_font_style',
			'footer_give_button_font_size',
			'footer_give_button_font_line_height',
			'footer_give_button_border_thickness',
			'footer_give_button_border_radius',
			'footer_give_button_border_style',
			'footer_give_button_text_transform',
			'footer_give_button_text_decoration',
			'footer_give_button_alignment',
			'footer_give_button_orientation',
			'footer_give_button_below_columns',
			'footer_give_social_gap',
		);

		for ( $logo_index = 1; $logo_index <= 3; $logo_index++ ) {
			$footer_fields[] = 'footer_logo_' . $logo_index . '_type';
			$footer_fields[] = 'footer_logo_' . $logo_index . '_image_url';
			$footer_fields[] = 'footer_logo_' . $logo_index . '_text_upper';
			$footer_fields[] = 'footer_logo_' . $logo_index . '_text_lower';
			$footer_fields[] = 'footer_logo_' . $logo_index . '_link_url';
			$footer_fields[] = 'footer_logo_' . $logo_index . '_link_new_tab';
			$footer_fields[] = 'footer_logo_' . $logo_index . '_hide_mobile';
		}

		for ( $column_index = 1; $column_index <= 3; $column_index++ ) {
			$footer_fields[] = 'footer_column_' . $column_index . '_mode';
			$footer_fields[] = 'footer_column_' . $column_index . '_width';
			$footer_fields[] = 'footer_column_' . $column_index . '_hide_mobile';
			$footer_fields[] = 'footer_column_' . $column_index . '_copyright_enabled';
			$footer_fields[] = 'footer_column_' . $column_index . '_content';
			$footer_fields[] = 'footer_column_' . $column_index . '_shortcode';
			$footer_fields[] = 'footer_column_' . $column_index . '_heading';
			$footer_fields[] = 'footer_column_' . $column_index . '_heading_alignment';
			$footer_fields[] = 'footer_column_' . $column_index . '_heading_text_transform';
			$footer_fields[] = 'footer_column_' . $column_index . '_heading_text_decoration';
		}

		$page_fields = array(
			'sog-unc-rebrand'         => $global_fields,
			'sog-unc-rebrand-header'  => $header_fields,
			'sog-unc-rebrand-colors'  => $color_fields,
			'sog-unc-rebrand-utility' => $utility_fields,
			'sog-unc-rebrand-footer'  => $footer_fields,
		);

		return $page_fields[ $settings_page ] ?? array();
	}

	/**
	 * Get all checkbox-backed field keys.
	 *
	 * @return array<int, string>
	 */
	private function get_checkbox_field_keys(): array {
		return array(
			'header_enabled',
			'footer_enabled',
			'utility_bar_enabled',
			'load_frontend_fonts',
			'exclude_front_page',
			'exclude_posts_page',
			'exclude_search',
			'exclude_404',
			'header_separator_hide_mobile',
			'header_text_links_enabled',
			'header_main_menu_enabled',
			'header_bottom_nav_enabled',
			'header_special_button_hide_mobile',
			'header_special_button_enabled',
			'display_site_search_enabled',
			'display_site_search_mobile_enabled',
			'site_search_inline_with_nav',
			'site_search_inline_with_header',
			'header_search_icon_enabled',
			'header_give_button_new_tab',
			'header_special_button_new_tab',
			'header_give_button_hide_mobile',
			'header_social_links_hide_mobile',
			'utility_bar_menu_fallback_enabled',
			'footer_top_text_enabled',
			'footer_logos_enabled',
			'footer_logos_mobile_carousel',
			'footer_bottom_enabled',
			'footer_bottom_show_copyright',
			'footer_bottom_show_menu',
			'footer_bottom_hide_mobile',
			'footer_separator_1_hide_mobile',
			'footer_separator_2_hide_mobile',
			'footer_logo_1_link_new_tab',
			'footer_logo_1_hide_mobile',
			'footer_logo_2_link_new_tab',
			'footer_logo_2_hide_mobile',
			'footer_logo_3_link_new_tab',
			'footer_logo_3_hide_mobile',
			'footer_column_1_copyright_enabled',
			'footer_column_2_copyright_enabled',
			'footer_column_3_copyright_enabled',
			'footer_column_1_hide_mobile',
			'footer_column_2_hide_mobile',
			'footer_column_3_hide_mobile',
			'footer_give_button_new_tab',
			'footer_give_button_below_columns',
			'footer_give_button_hide_mobile',
			'footer_social_links_below_columns',
			'footer_social_links_hide_mobile',
		);
	}

	/**
	 * Render the opening markup for a settings section.
	 *
	 * @param string               $title Section title.
	 * @param string               $description Optional section description.
	 * @param array<string, mixed> $args Optional conditional arguments.
	 * @return void
	 */
	private function render_section_start( string $title, string $description = '', array $args = array() ): void {
		?>
		<div class="postbox" <?php echo $this->get_condition_attributes( $args ); ?>>
			<div class="inside">
				<h2><?php echo esc_html( $title ); ?></h2>
				<?php if ( '' !== $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
				<table class="form-table" role="presentation">
					<tbody>
		<?php
	}

	/**
	 * Render the closing markup for a settings section.
	 *
	 * @return void
	 */
	private function render_section_end(): void {
		?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a subsection heading within a settings table.
	 *
	 * @param string               $title Subsection title.
	 * @param array<string, mixed> $args Optional conditional arguments.
	 * @return void
	 */
	private function render_subsection_heading( string $title, array $args = array() ): void {
		?>
		<tr <?php echo $this->get_condition_attributes( $args ); ?>>
			<td colspan="2">
				<h3><?php echo esc_html( $title ); ?></h3>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param string               $key Field key.
	 * @param string               $label Field label.
	 * @param bool                 $checked Checked state.
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_checkbox_field( string $key, string $label, bool $checked, array $args = array() ): void {
		$field_id = self::OPTION_NAME . '_' . $key;
		?>
		<tr <?php echo $this->get_condition_attributes( $args ); ?>>
			<th scope="row">
				<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input id="<?php echo esc_attr( $field_id ); ?>" type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>" value="1" <?php checked( $checked ); ?> />
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a select field.
	 *
	 * @param string               $key Field key.
	 * @param string               $label Field label.
	 * @param string               $value Current value.
	 * @param array<string, string> $options Available options.
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_select_field( string $key, string $label, string $value, array $options, array $args = array() ): void {
		$field_id = self::OPTION_NAME . '_' . $key;
		?>
		<tr <?php echo $this->get_condition_attributes( $args ); ?>>
			<th scope="row">
				<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>">
					<?php foreach ( $options as $option_value => $option_label ) : ?>
						<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
							<?php echo esc_html( $option_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a text field.
	 *
	 * @param string               $key Field key.
	 * @param string               $label Field label.
	 * @param string               $value Current value.
	 * @param string               $description Optional helper text.
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_text_field( string $key, string $label, string $value, string $description = '', array $args = array() ): void {
		$field_id = self::OPTION_NAME . '_' . $key;
		?>
		<tr <?php echo $this->get_condition_attributes( $args ); ?>>
			<th scope="row">
				<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input id="<?php echo esc_attr( $field_id ); ?>" type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" />
				<?php if ( $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a URL field.
	 *
	 * @param string               $key Field key.
	 * @param string               $label Field label.
	 * @param string               $value Current value.
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_url_field( string $key, string $label, string $value, array $args = array() ): void {
		$field_id = self::OPTION_NAME . '_' . $key;
		?>
		<tr <?php echo $this->get_condition_attributes( $args ); ?>>
			<th scope="row">
				<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input id="<?php echo esc_attr( $field_id ); ?>" type="url" class="regular-text code" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a media picker field with preview controls.
	 *
	 * @param string               $key Field key.
	 * @param string               $label Field label.
	 * @param string               $value Current value.
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_media_field( string $key, string $label, string $value, array $args = array() ): void {
		$field_id = self::OPTION_NAME . '_' . $key;
		?>
		<tr <?php echo $this->get_condition_attributes( $args ); ?>>
			<th scope="row">
				<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<p>
					<input id="<?php echo esc_attr( $field_id ); ?>" type="url" class="regular-text code sog-rebrand__media-input" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" />
				</p>
				<p>
					<button type="button" class="button sog-rebrand__media-select"><?php echo esc_html__( 'Select from Media Library', 'sog-unc-rebrand' ); ?></button>
					<button type="button" class="button sog-rebrand__media-remove"><?php echo esc_html__( 'Remove Image', 'sog-unc-rebrand' ); ?></button>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a number field.
	 *
	 * @param string               $key Field key.
	 * @param string               $label Field label.
	 * @param int                  $value Current value.
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_number_field( string $key, string $label, int $value, array $args = array() ): void {
		$field_id = self::OPTION_NAME . '_' . $key;
		?>
		<tr <?php echo $this->get_condition_attributes( $args ); ?>>
			<th scope="row">
				<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input id="<?php echo esc_attr( $field_id ); ?>" type="number" class="small-text" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" min="0" step="1" />
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a color field.
	 *
	 * @param string               $key Field key.
	 * @param string               $label Field label.
	 * @param string               $value Current value.
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_color_field( string $key, string $label, string $value, array $args = array() ): void {
		$field_id = self::OPTION_NAME . '_' . $key;
		?>
		<tr <?php echo $this->get_condition_attributes( $args ); ?>>
			<th scope="row">
				<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input id="<?php echo esc_attr( $field_id ); ?>" type="text" class="sog-rebrand__color-field" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a textarea field.
	 *
	 * @param string               $key Field key.
	 * @param string               $label Field label.
	 * @param string               $value Current value.
	 * @param string               $description Optional helper text.
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_textarea_field( string $key, string $label, string $value, string $description = '', array $args = array() ): void {
		$field_id = self::OPTION_NAME . '_' . $key;
		?>
		<tr <?php echo $this->get_condition_attributes( $args ); ?>>
			<th scope="row">
				<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<textarea id="<?php echo esc_attr( $field_id ); ?>" class="large-text code" rows="5" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
				<?php if ( $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a TinyMCE field.
	 *
	 * @param string               $key Field key.
	 * @param string               $label Field label.
	 * @param string               $value Current value.
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_editor_field( string $key, string $label, string $value, array $args = array() ): void {
		$editor_id = self::OPTION_NAME . '_' . $key;
		?>
		<tr <?php echo $this->get_condition_attributes( $args ); ?>>
			<th scope="row">
				<label for="<?php echo esc_attr( $editor_id ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<?php
				wp_editor(
					$value,
					$editor_id,
					array(
						'textarea_name' => self::OPTION_NAME . '[' . $key . ']',
						'textarea_rows' => 6,
						'media_buttons' => false,
					)
				);
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render the repeatable social links field group.
	 *
	 * @param string               $key Field key.
	 * @param string               $label Field label.
	 * @param mixed                $links Current link collection.
	 * @param string               $description Optional helper text.
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_social_links_field( string $key, string $label, $links, string $description = '', array $args = array() ): void {
		$links      = $this->normalize_social_links( $links );
		$next_index = count( $links );
		?>
		<tr <?php echo $this->get_condition_attributes( $args ); ?>>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<input type="hidden" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . '][__empty]' ); ?>" value="1" />
				<div data-sog-rebrand-social-links data-next-index="<?php echo esc_attr( (string) $next_index ); ?>">
					<div class="sog-rebrand__social-links-list">
						<?php foreach ( $links as $index => $link ) : ?>
							<?php $this->render_social_link_item_fields( $key, (string) $index, $link ); ?>
						<?php endforeach; ?>
					</div>
					<template class="sog-rebrand__social-links-template">
						<?php $this->render_social_link_item_fields( $key, '__index__', $this->get_default_social_link() ); ?>
					</template>
					<p><button type="button" class="button button-secondary sog-rebrand__social-link-add"><?php echo esc_html__( 'Add Social Link', 'sog-unc-rebrand' ); ?></button></p>
				</div>
				<?php if ( $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render the field controls for a single social link item.
	 *
	 * @param string               $key Parent field key.
	 * @param string               $index Item index.
	 * @param array<string, mixed> $link Current link values.
	 * @return void
	 */
	private function render_social_link_item_fields( string $key, string $index, array $link ): void {
		$name_prefix = self::OPTION_NAME . '[' . $key . '][' . $index . ']';
		?>
		<div class="postbox" data-sog-rebrand-social-link-item>
			<div class="inside">
				<p>
					<strong><?php echo esc_html__( 'Social Link', 'sog-unc-rebrand' ); ?></strong>
					<button type="button" class="button-link-delete sog-rebrand__social-link-remove"><?php echo esc_html__( 'Remove', 'sog-unc-rebrand' ); ?></button>
				</p>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Name', 'sog-unc-rebrand' ); ?></th>
							<td>
								<input type="text" class="regular-text" name="<?php echo esc_attr( $name_prefix . '[name]' ); ?>" data-name-template="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . '][__index__][name]' ); ?>" value="<?php echo esc_attr( (string) $link['name'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Link URL', 'sog-unc-rebrand' ); ?></th>
							<td>
								<input type="url" class="regular-text code" name="<?php echo esc_attr( $name_prefix . '[url]' ); ?>" data-name-template="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . '][__index__][url]' ); ?>" value="<?php echo esc_attr( (string) $link['url'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'SVG Markup', 'sog-unc-rebrand' ); ?></th>
							<td>
								<textarea class="large-text code" rows="6" name="<?php echo esc_attr( $name_prefix . '[svg]' ); ?>" data-name-template="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . '][__index__][svg]' ); ?>"><?php echo esc_textarea( (string) $link['svg'] ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Color', 'sog-unc-rebrand' ); ?></th>
							<td>
								<input type="text" class="sog-rebrand__color-field" name="<?php echo esc_attr( $name_prefix . '[color]' ); ?>" data-name-template="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . '][__index__][color]' ); ?>" value="<?php echo esc_attr( (string) $link['color'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Hover Color', 'sog-unc-rebrand' ); ?></th>
							<td>
								<input type="text" class="sog-rebrand__color-field" name="<?php echo esc_attr( $name_prefix . '[hover_color]' ); ?>" data-name-template="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . '][__index__][hover_color]' ); ?>" value="<?php echo esc_attr( (string) $link['hover_color'] ); ?>" />
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Get known header hooks.
	 *
	 * @return array<string, string>
	 */
	public function get_known_header_hooks(): array {
		return array(
			'wp_body_open'   => __( 'wp_body_open (default)', 'sog-unc-rebrand' ),
			'tha_header_top' => __( 'tha_header_top', 'sog-unc-rebrand' ),
			'get_header'     => __( 'get_header', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Get known footer hooks.
	 *
	 * @return array<string, string>
	 */
	public function get_known_footer_hooks(): array {
		return array(
			'wp_footer'      => __( 'wp_footer (default)', 'sog-unc-rebrand' ),
			'tha_footer_top' => __( 'tha_footer_top', 'sog-unc-rebrand' ),
			'get_footer'     => __( 'get_footer', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Sanitize an integer and enforce a minimum.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $minimum Minimum allowed value.
	 * @param int   $fallback Fallback value.
	 * @return int
	 */
	private function sanitize_int_at_least( $value, int $minimum, int $fallback ): int {
		$sanitized = absint( $value );

		if ( $sanitized < $minimum ) {
			return $fallback;
		}

		return $sanitized;
	}

	/**
	 * Get header core variant options.
	 *
	 * @return array<string, string>
	 */
	private function get_header_core_variant_options(): array {
		return array(
			'image-logo'  => __( 'Image logo', 'sog-unc-rebrand' ),
			'simple-text' => __( 'Simple text', 'sog-unc-rebrand' ),
			'simple-text-vertical' => __( 'Simple text - Vertical w/o line', 'sog-unc-rebrand' ),
			'simple-text-vertical-line' => __( 'Simple text - Vertical w/ line: has site description or tagline below school name', 'sog-unc-rebrand' ),
			'simple-text-vertical-line-alternate' => __( 'Simple text - Vertical w/ line alternate: has site description or tagline above school name', 'sog-unc-rebrand' ),
			'simple-text-vertical-social-no-nav' => __( 'Simple text - Vertical w/ social media and no navigation', 'sog-unc-rebrand' ),
			'simple-text-vertical-no-nav' => __( 'Simple text - Vertical no navigation', 'sog-unc-rebrand' ),
			'simple-text-vertical-social-give' => __( 'Simple text - Vertical w/ social media and give button', 'sog-unc-rebrand' ),
			'simple-text-vertical-search' => __( 'Simple text - Vertical w/ search', 'sog-unc-rebrand' ),
			'simple-text-vertical-nav-search' => __( 'Simple text - Vertical navigation w/ search', 'sog-unc-rebrand' ),
			'simple-text-vertical-nav-inline-school-name' => __( 'Simple text - Vertical navigation inline with school name', 'sog-unc-rebrand' ),
			'simple-text-vertical-line-special-btn' => __( 'Simple text - Vertical w/ line line and special button inline with the navigation and the site name', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Get hook mode options.
	 *
	 * @return array<string, string>
	 */
	private function get_hook_mode_options(): array {
		return array(
			'known'  => __( 'Known hook', 'sog-unc-rebrand' ),
			'custom' => __( 'Custom hook', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Get logo link behavior options.
	 *
	 * @return array<string, string>
	 */
	private function get_logo_link_behavior_options(): array {
		return array(
			'homepage' => __( 'Homepage', 'sog-unc-rebrand' ),
			'none'     => __( 'No link', 'sog-unc-rebrand' ),
			'custom'   => __( 'Custom URL', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Get mobile menu mode options.
	 *
	 * @return array<string, string>
	 */
	private function get_mobile_menu_mode_options(): array {
		return array(
			'hamburger' => __( 'Hamburger menu', 'sog-unc-rebrand' ),
			'stacked'   => __( 'Stacked navigation', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Get flex orientation options.
	 *
	 * @return array<string, string>
	 */
	private function get_orientation_options(): array {
		return array(
			'horizontal' => __( 'Horizontal', 'sog-unc-rebrand' ),
			'vertical'   => __( 'Vertical', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Get alignment options.
	 *
	 * @return array<string, string>
	 */
	private function get_alignment_options(): array {
		return array(
			'flex-start'    => __( 'Start', 'sog-unc-rebrand' ),
			'center'        => __( 'Center', 'sog-unc-rebrand' ),
			'flex-end'      => __( 'End', 'sog-unc-rebrand' ),
			'space-between' => __( 'Space between', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Get separator style options.
	 *
	 * @return array<string, string>
	 */
	private function get_separator_style_options(): array {
		return array(
			'none'  => __( 'None', 'sog-unc-rebrand' ),
			'solid'  => __( 'Solid', 'sog-unc-rebrand' ),
			'dotted' => __( 'Dotted', 'sog-unc-rebrand' ),
			'dashed' => __( 'Dashed', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Get text-transform style options.
	 *
	 * @return array<string, string>
	 */
	private function get_text_transform_style_options(): array {
		return array(
			'none'         => __( 'None', 'sog-unc-rebrand' ),
			'inherit'      => __( 'Inherit', 'sog-unc-rebrand' ),
			'initial'      => __( 'Initial', 'sog-unc-rebrand' ),
			'capitalize'   => __( 'Capitalize', 'sog-unc-rebrand' ),
			'uppercase'    => __( 'Uppercase', 'sog-unc-rebrand' ),
			'lowercase'    => __( 'Lowercase', 'sog-unc-rebrand' ),
			'full-width'   => __( 'Full-width', 'sog-unc-rebrand' ),
			'math-auto'    => __( 'Math Auto', 'sog-unc-rebrand' ),
			'unset'        => __( 'Unset', 'sog-unc-rebrand' ),
			'revert'       => __( 'Revert', 'sog-unc-rebrand' ),
			'revert-layer' => __( 'Revert Layer', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Get text-decoration style options.
	 *
	 * @return array<string, string>
	 */
	private function get_text_decoration_style_options(): array {
		return array(
			'none'       => __( 'None', 'sog-unc-rebrand' ),
			'underline'  => __( 'Underline', 'sog-unc-rebrand' ),
			'overline'   => __( 'Overline', 'sog-unc-rebrand' ),
			'line-through' => __( 'Line Through', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Get border style options.
	 *
	 * @return array<string, string>
	 */
	private function get_border_style_options(): array {
		return array(
			'none'   => __( 'None', 'sog-unc-rebrand' ),
			'solid'  => __( 'Solid', 'sog-unc-rebrand' ),
			'dotted' => __( 'Dotted', 'sog-unc-rebrand' ),
			'dashed' => __( 'Dashed', 'sog-unc-rebrand' ),
			'double' => __( 'Double', 'sog-unc-rebrand' ),
			'inset'  => __( 'Inset', 'sog-unc-rebrand' ),
			'outset' => __( 'Outset', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Get footer logo type options.
	 *
	 * @return array<string, string>
	 */
	private function get_footer_logo_type_options(): array {
		return array(
			'none'  => __( 'Unused', 'sog-unc-rebrand' ),
			'image' => __( 'Image', 'sog-unc-rebrand' ),
			'text'  => __( 'Text logo', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Get footer column mode options.
	 *
	 * @return array<string, string>
	 */
	private function get_footer_column_mode_options(): array {
		return array(
			'none'                       => __( 'Unused', 'sog-unc-rebrand' ),
			'shortcode'                  => __( 'Shortcode', 'sog-unc-rebrand' ),
			'wysiwyg'                    => __( 'WYSIWYG', 'sog-unc-rebrand' ),
			'menu'                       => __( 'Menu', 'sog-unc-rebrand' ),
			'menu_shortcode'             => __( 'Menu + Shortcode', 'sog-unc-rebrand' ),
			'menu_wysiwyg'               => __( 'Menu + WYSIWYG', 'sog-unc-rebrand' ),
			'social'                     => __( 'Social links', 'sog-unc-rebrand' ),
			'social_shortcode'           => __( 'Social links + Shortcode', 'sog-unc-rebrand' ),
			'social_wysiwyg'             => __( 'Social links + WYSIWYG', 'sog-unc-rebrand' ),
			'menu_social'                => __( 'Menu + Social links', 'sog-unc-rebrand' ),
			'menu_social_shortcode'      => __( 'Menu + Social links + Shortcode', 'sog-unc-rebrand' ),
			'menu_social_wysiwyg'        => __( 'Menu + Social links + WYSIWYG', 'sog-unc-rebrand' ),
			'give'                       => __( 'Give button', 'sog-unc-rebrand' ),
			'give_shortcode'             => __( 'Give button + Shortcode', 'sog-unc-rebrand' ),
			'give_wysiwyg'               => __( 'Give button + WYSIWYG', 'sog-unc-rebrand' ),
			'social_give'                => __( 'Social links + Give button', 'sog-unc-rebrand' ),
			'social_give_shortcode'      => __( 'Social links + Give button + Shortcode', 'sog-unc-rebrand' ),
			'social_give_wysiwyg'        => __( 'Social links + Give button + WYSIWYG', 'sog-unc-rebrand' ),
			'menu_give'                  => __( 'Menu + Give button', 'sog-unc-rebrand' ),
			'menu_give_shortcode'        => __( 'Menu + Give button + Shortcode', 'sog-unc-rebrand' ),
			'menu_give_wysiwyg'          => __( 'Menu + Give button + WYSIWYG', 'sog-unc-rebrand' ),
			'menu_social_give'           => __( 'Menu + Social links + Give button', 'sog-unc-rebrand' ),
			'menu_social_give_shortcode' => __( 'Menu + Social links + Give button + Shortcode', 'sog-unc-rebrand' ),
			'menu_social_give_wysiwyg'   => __( 'Menu + Social links + Give button + WYSIWYG', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Get admin page map.
	 *
	 * @return array<string, string>
	 */
	private function get_admin_pages(): array {
		return array(
			'sog-unc-rebrand'         => __( 'Global', 'sog-unc-rebrand' ),
			'sog-unc-rebrand-header'  => __( 'Header', 'sog-unc-rebrand' ),
			'sog-unc-rebrand-footer'  => __( 'Footer', 'sog-unc-rebrand' ),
			'sog-unc-rebrand-colors'  => __( 'Colors', 'sog-unc-rebrand' ),
			'sog-unc-rebrand-utility' => __( 'Utility Bar', 'sog-unc-rebrand' ),
			'sog-unc-rebrand-presets' => __( 'Presets', 'sog-unc-rebrand' ),
		);
	}

	/**
	 * Render a presets page notice from the redirect query string.
	 *
	 * @return void
	 */
	private function render_presets_notice(): void {
		$type    = isset( $_GET['sog_rebrand_notice_type'] ) ? sanitize_key( wp_unslash( $_GET['sog_rebrand_notice_type'] ) ) : '';
		$message = isset( $_GET['sog_rebrand_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['sog_rebrand_notice'] ) ) : '';

		if ( '' === $type || '' === $message ) {
			return;
		}

		$class = 'success' === $type ? 'notice notice-success' : 'notice notice-error';
		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Redirect to the presets page with a status notice.
	 *
	 * @param string $type Notice type.
	 * @param string $message Notice message.
	 * @return void
	 */
	private function redirect_to_presets_page( string $type, string $message ): void {
		$url = add_query_arg(
			array(
				'page'                    => 'sog-unc-rebrand-presets',
				'sog_rebrand_notice_type' => $type,
				'sog_rebrand_notice'      => $message,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Apply a partial preset over the current settings and save the result.
	 *
	 * @param array<string, mixed> $preset_settings Imported preset settings.
	 * @return array{applied_keys: array<int, string>, mismatches: array<int, string>}
	 */
	private function apply_preset_settings( array $preset_settings ): array {
		$current  = $this->get_settings();
		$defaults = $this->get_preset_allowed_defaults();
		$merged   = $current;
		$applied  = array();

		foreach ( $preset_settings as $key => $value ) {
			if ( ! array_key_exists( $key, $defaults ) ) {
				continue;
			}

			$merged[ $key ] = $value;
			$applied[]      = $key;
		}

		$sanitized = $this->sanitize_complete_settings( $merged );
		$persisted = $sanitized;
		$persisted['settings_page'] = 'sog-unc-rebrand-presets';

		update_option( self::OPTION_NAME, $persisted );

		$stored = get_option( self::OPTION_NAME, array() );
		$stored = is_array( $stored ) ? $stored : array();
		$stored = $this->sanitize_complete_settings( $stored, $defaults );

		$mismatches = array();

		foreach ( $applied as $key ) {
			$expected = $sanitized[ $key ] ?? null;
			$actual   = $stored[ $key ] ?? null;

			if ( wp_json_encode( $actual ) !== wp_json_encode( $expected ) ) {
				$mismatches[] = $key;
			}
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'SOG Rebrand preset apply: applied=%s mismatches=%s',
					wp_json_encode( $applied ),
					wp_json_encode( $mismatches )
				)
			);
		}

		return array(
			'applied_keys' => $applied,
			'mismatches'   => $mismatches,
		);
	}

	/**
	 * Return the current settings that differ from defaults.
	 *
	 * @param array<string, mixed> $settings Current settings.
	 * @return array<string, mixed>
	 */
	private function get_changed_settings( array $settings ): array {
		$defaults = $this->get_preset_allowed_defaults();
		$changed  = array();

		foreach ( $defaults as $key => $default_value ) {
			if ( ! array_key_exists( $key, $settings ) ) {
				continue;
			}

			if ( $settings[ $key ] === $default_value ) {
				continue;
			}

			$changed[ $key ] = $settings[ $key ];
		}

		return $changed;
	}

	/**
	 * Parse a preset payload from YAML.
	 *
	 * @param string $yaml YAML payload.
	 * @return array<string, mixed>
	 */
	private function parse_preset_payload( string $yaml ): array {
		$parsed   = Yaml::parse( $yaml );
		$defaults = $this->get_preset_allowed_defaults();

		if ( isset( $parsed['settings'] ) && is_array( $parsed['settings'] ) ) {
			$settings = $parsed['settings'];
		} else {
			$settings = $parsed;
		}

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings = array_intersect_key( $settings, $defaults );

		return array(
			'title'       => isset( $parsed['title'] ) && is_scalar( $parsed['title'] ) ? sanitize_text_field( (string) $parsed['title'] ) : '',
			'description' => isset( $parsed['description'] ) && is_scalar( $parsed['description'] ) ? sanitize_text_field( (string) $parsed['description'] ) : '',
			'settings'    => $settings,
		);
	}

	/**
	 * Discover available bundled preset files.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_available_presets(): array {
		$presets = array();

		foreach ( $this->get_preset_directories() as $source => $directory ) {
			if ( ! is_dir( $directory ) ) {
				continue;
			}

			$files = glob( trailingslashit( $directory ) . '*.{yaml,yml}', GLOB_BRACE );

			if ( false === $files ) {
				continue;
			}

			sort( $files );

			foreach ( $files as $path ) {
				$contents = file_get_contents( $path );

				if ( false === $contents ) {
					continue;
				}

				$preset = $this->parse_preset_payload( $contents );

				if ( empty( $preset['settings'] ) ) {
					continue;
				}

				$title = $preset['title'];

				if ( '' === $title ) {
					$title = ucwords( str_replace( array( '-', '_' ), ' ', pathinfo( $path, PATHINFO_FILENAME ) ) );
				}

				$presets[] = array(
					'id'           => md5( $path ),
					'title'        => $title,
					'description'  => '' !== $preset['description'] ? $preset['description'] : __( 'No description provided.', 'sog-unc-rebrand' ),
					'settings'     => $preset['settings'],
					'source_label' => $this->get_preset_source_label( $source ),
				);
			}
		}

		return $presets;
	}

	/**
	 * Return the settings that are allowed in preset YAML.
	 *
	 * @return array<string, mixed>
	 */
	private function get_preset_allowed_defaults(): array {
		$defaults = $this->get_defaults();

		unset( $defaults['header_hook'], $defaults['footer_hook'] );

		return $defaults;
	}

	/**
	 * Return preset discovery directories.
	 *
	 * @return array<string, string>
	 */
	private function get_preset_directories(): array {
		$directories = array(
			'plugin'      => trailingslashit( SOG_UNC_REBRAND_PATH ) . 'presets',
			'child-theme' => trailingslashit( get_stylesheet_directory() ) . 'sog-rebrand-presets',
		);

		if ( get_template_directory() !== get_stylesheet_directory() ) {
			$directories['parent-theme'] = trailingslashit( get_template_directory() ) . 'sog-rebrand-presets';
		}

		/**
		 * Filter preset discovery directories.
		 *
		 * @param array<string, string> $directories Preset directories keyed by source slug.
		 */
		$directories = apply_filters( 'sog_unc_rebrand_preset_directories', $directories );

		return is_array( $directories ) ? $directories : array();
	}

	/**
	 * Return a human-readable label for a preset source.
	 *
	 * @param string $source Source slug.
	 * @return string
	 */
	private function get_preset_source_label( string $source ): string {
		$labels = array(
			'plugin'       => __( 'Plugin preset', 'sog-unc-rebrand' ),
			'child-theme'  => __( 'Child theme preset', 'sog-unc-rebrand' ),
			'parent-theme' => __( 'Parent theme preset', 'sog-unc-rebrand' ),
		);

		return $labels[ $source ] ?? __( 'Custom preset source', 'sog-unc-rebrand' );
	}

	/**
	 * Determine whether a settings page is current.
	 *
	 * @param string $slug Page slug.
	 * @return bool
	 */
	private function is_current_page( string $slug ): bool {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		return $page === $slug;
	}

	/**
	 * Get conditional display attributes for a field wrapper.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return string
	 */
	private function get_condition_attributes( array $args ): string {
		$attributes = array();

		if ( ! empty( $args['condition_field'] ) ) {
			$attributes[] = 'data-condition-field="' . esc_attr( (string) $args['condition_field'] ) . '"';
		}

		if ( array_key_exists( 'condition_value', $args ) ) {
			$attributes[] = 'data-condition-value="' . esc_attr( (string) $args['condition_value'] ) . '"';
		}

		if ( ! empty( $args['condition_operator'] ) ) {
			$attributes[] = 'data-condition-operator="' . esc_attr( (string) $args['condition_operator'] ) . '"';
		}

		return implode( ' ', $attributes );
	}

	/**
	 * Sanitize a value against allowed choices.
	 *
	 * @param string   $value Candidate value.
	 * @param string[] $allowed Allowed values.
	 * @param string   $fallback Fallback value.
	 * @return string
	 */
	private function sanitize_enum( string $value, array $allowed, string $fallback ): string {
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	/**
	 * Sanitize a hook name while preserving common hook characters.
	 *
	 * @param string $hook Hook name.
	 * @return string
	 */
	private function sanitize_hook_name( string $hook ): string {
		$hook = strtolower( sanitize_text_field( $hook ) );

		return trim( (string) preg_replace( '/[^a-z0-9._-]/', '', $hook ) );
	}

	/**
	 * Sanitize a hex color with a fallback.
	 *
	 * @param string $color Input color.
	 * @param string $fallback Fallback color.
	 * @return string
	 */
	private function sanitize_hex_color_with_default( string $color, string $fallback ): string {
		$sanitized = sanitize_hex_color( $color );

		return $sanitized ? $sanitized : $fallback;
	}

	/**
	 * Normalize social links to the structured storage format.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int, array<string, string>>
	 */
	private function normalize_social_links( $value ): array {
		if ( is_string( $value ) ) {
			$lines = $this->normalize_lines( $value );
			$value = array();

			foreach ( $lines as $line ) {
				$parts = array_map( 'trim', explode( '|', $line, 2 ) );

				if ( 2 !== count( $parts ) ) {
					continue;
				}

				$value[] = array(
					'name'        => $parts[0],
					'url'         => $parts[1],
					'svg'         => '',
					'color'       => '',
					'hover_color' => '',
				);
			}
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $value as $link ) {
			if ( ! is_array( $link ) ) {
				continue;
			}

			$name = '';

			if ( isset( $link['name'] ) && is_scalar( $link['name'] ) ) {
				$name = (string) $link['name'];
			} elseif ( isset( $link['label'] ) && is_scalar( $link['label'] ) ) {
				$name = (string) $link['label'];
			}

			$url = isset( $link['url'] ) && is_scalar( $link['url'] ) ? (string) $link['url'] : '';

			$normalized[] = array(
				'name'        => $name,
				'url'         => $url,
				'svg'         => $this->sanitize_svg_markup( (string) $link['svg'] ),
				'color'       => $this->sanitize_optional_hex_color( (string) $link['color'] ),
				'hover_color' => $this->sanitize_optional_hex_color( (string) $link['hover_color'] ),
			);
		}

		return $normalized;
	}

	/**
	 * Sanitize structured social links.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int, array<string, string>>
	 */
	private function sanitize_social_links( $value ): array {
		$links     = $this->normalize_social_links( $value );
		$sanitized = array();

		foreach ( $links as $link ) {
			$name = sanitize_text_field( (string) $link['name'] );
			$url  = esc_url_raw( (string) $link['url'] );

			if ( '' === $name || '' === $url ) {
				continue;
			}

			$sanitized[] = array(
				'name'        => $name,
				'url'         => $url,
				'svg'         => $this->sanitize_svg_markup( (string) $link['svg'] ),
				'color'       => $this->sanitize_optional_hex_color( (string) $link['color'] ),
				'hover_color' => $this->sanitize_optional_hex_color( (string) $link['hover_color'] ),
			);
		}

		return $sanitized;
	}

	/**
	 * Return the default social link structure.
	 *
	 * @return array<string, string>
	 */
	private function get_default_social_link(): array {
		return array(
			'name'        => '',
			'url'         => '',
			'svg'         => '',
			'color'       => '',
			'hover_color' => '',
		);
	}

	/**
	 * Sanitize an optional hex color.
	 *
	 * @param string $color Candidate color.
	 * @return string
	 */
	private function sanitize_optional_hex_color( string $color ): string {
		$sanitized = sanitize_hex_color( $color );

		return $sanitized ? $sanitized : '';
	}

	/**
	 * Sanitize SVG markup for stored social icons.
	 *
	 * @param string $svg Raw SVG markup.
	 * @return string
	 */
	private function sanitize_svg_markup( string $svg ): string {
		$svg = trim( $svg );

		if ( '' === $svg || false === stripos( $svg, '<svg' ) ) {
			return '';
		}

		return (string) wp_kses( $svg, $this->get_allowed_svg_html() );
	}

	/**
	 * Return the allowed SVG tags and attributes.
	 *
	 * @return array<string, array<string, bool>>
	 */
	private function get_allowed_svg_html(): array {
		return array(
			'svg'            => array(
				'aria-hidden'         => true,
				'class'               => true,
				'fill'                => true,
				'focusable'           => true,
				'height'              => true,
				'id'                  => true,
				'preserveaspectratio' => true,
				'role'                => true,
				'stroke'              => true,
				'stroke-width'        => true,
				'viewbox'             => true,
				'width'               => true,
				'xmlns'               => true,
				'xmlns:xlink'         => true,
			),
			'g'              => array(
				'class'           => true,
				'clip-path'       => true,
				'fill'            => true,
				'fill-opacity'    => true,
				'id'              => true,
				'mask'            => true,
				'opacity'         => true,
				'stroke'          => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-opacity'  => true,
				'stroke-width'    => true,
				'transform'       => true,
			),
			'path'           => array(
				'class'           => true,
				'clip-rule'       => true,
				'd'               => true,
				'fill'            => true,
				'fill-rule'       => true,
				'fill-opacity'    => true,
				'id'              => true,
				'opacity'         => true,
				'stroke'          => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-opacity'  => true,
				'stroke-width'    => true,
				'transform'       => true,
			),
			'circle'         => array(
				'cx'             => true,
				'cy'             => true,
				'fill'           => true,
				'fill-opacity'   => true,
				'id'             => true,
				'opacity'        => true,
				'r'              => true,
				'stroke'         => true,
				'stroke-opacity' => true,
				'stroke-width'   => true,
				'transform'      => true,
			),
			'ellipse'        => array(
				'cx'             => true,
				'cy'             => true,
				'fill'           => true,
				'fill-opacity'   => true,
				'id'             => true,
				'opacity'        => true,
				'rx'             => true,
				'ry'             => true,
				'stroke'         => true,
				'stroke-opacity' => true,
				'stroke-width'   => true,
				'transform'      => true,
			),
			'rect'           => array(
				'fill'           => true,
				'fill-opacity'   => true,
				'height'         => true,
				'id'             => true,
				'opacity'        => true,
				'rx'             => true,
				'ry'             => true,
				'stroke'         => true,
				'stroke-opacity' => true,
				'stroke-width'   => true,
				'transform'      => true,
				'width'          => true,
				'x'              => true,
				'y'              => true,
			),
			'line'           => array(
				'id'              => true,
				'opacity'         => true,
				'stroke'          => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-opacity'  => true,
				'stroke-width'    => true,
				'transform'       => true,
				'x1'              => true,
				'x2'              => true,
				'y1'              => true,
				'y2'              => true,
			),
			'polyline'       => array(
				'fill'            => true,
				'fill-opacity'    => true,
				'id'              => true,
				'opacity'         => true,
				'points'          => true,
				'stroke'          => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-opacity'  => true,
				'stroke-width'    => true,
				'transform'       => true,
			),
			'polygon'        => array(
				'fill'            => true,
				'fill-opacity'    => true,
				'id'              => true,
				'opacity'         => true,
				'points'          => true,
				'stroke'          => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-opacity'  => true,
				'stroke-width'    => true,
				'transform'       => true,
			),
			'defs'           => array(),
			'clippath'       => array(
				'id' => true,
			),
			'mask'           => array(
				'fill'      => true,
				'height'    => true,
				'id'        => true,
				'maskunits' => true,
				'width'     => true,
				'x'         => true,
				'y'         => true,
			),
			'lineargradient' => array(
				'gradienttransform' => true,
				'gradientunits'     => true,
				'id'                => true,
				'x1'                => true,
				'x2'                => true,
				'y1'                => true,
				'y2'                => true,
			),
			'radialgradient' => array(
				'cx'                => true,
				'cy'                => true,
				'fx'                => true,
				'fy'                => true,
				'gradienttransform' => true,
				'gradientunits'     => true,
				'id'                => true,
				'r'                 => true,
			),
			'stop'           => array(
				'offset'       => true,
				'stop-color'   => true,
				'stop-opacity' => true,
			),
			'symbol'         => array(
				'id'      => true,
				'viewbox' => true,
			),
			'use'            => array(
				'height'     => true,
				'href'       => true,
				'transform'  => true,
				'width'      => true,
				'x'          => true,
				'xlink:href' => true,
				'y'          => true,
			),
			'title'          => array(),
			'desc'           => array(),
		);
	}

	/**
	 * Sanitize a line-delimited list of positive integers.
	 *
	 * @param mixed $values Values to sanitize.
	 * @return array<int>
	 */
	private function sanitize_line_delimited_absint_list( $values ): array {
		$values = $this->normalize_lines( $values );
		$values = array_map( 'absint', $values );

		return array_values( array_filter( $values ) );
	}

	/**
	 * Sanitize a line-delimited list of keys.
	 *
	 * @param mixed $values Values to sanitize.
	 * @return array<string>
	 */
	private function sanitize_line_delimited_key_list( $values ): array {
		$values = $this->normalize_lines( $values );
		$values = array_map( 'sanitize_key', $values );

		return array_values( array_filter( $values ) );
	}

	/**
	 * Sanitize a line-delimited list of plain text strings.
	 *
	 * @param mixed $values Values to sanitize.
	 * @return array<string>
	 */
	private function sanitize_line_delimited_text_list( $values ): array {
		$values = $this->normalize_lines( $values );
		$values = array_map( 'sanitize_text_field', $values );

		return array_values( array_filter( $values ) );
	}

	/**
	 * Normalize textarea and array values into a filtered list.
	 *
	 * @param mixed $values Raw value.
	 * @return array<string>
	 */
	private function normalize_lines( $values ): array {
		if ( is_string( $values ) ) {
			$values = preg_split( '/\r\n|\r|\n/', $values );
		}

		$values = is_array( $values ) ? $values : array();
		$values = array_map( 'trim', $values );

		return array_values( array_filter( $values ) );
	}

	/**
	 * Debug wrapper for sanitize_enum to log the value being saved for a specific key.
	 */
	private function debug_log_enum($key, $value, $options, $default) {
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log("[SOG-UNC-Rebrand] Sanitize $key: submitted='" . print_r($value, true) . "' options=" . implode(',', array_keys($options)) . " default='$default'");
		}
		return $this->sanitize_enum($value, $options, $default);
	}
}

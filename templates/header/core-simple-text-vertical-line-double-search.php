<?php
/**
 * Simple text header core template - Vertical w/ Line (has school name, site name, site description and the menu both for desktop and mobile). Has a vertical line separating the school name and site name, and the search is inline with the school name.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 * @var string $school_name
 * @var string $site_name
 * @var string $site_description
 * @var string $logo_url
 * @var string $header_main_menu
 * @var string $header_bottom_menu
 * @var string $mobile_nav_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings           = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();
$school_name        = isset( $args['school_name'] ) ? (string) $args['school_name'] : '';
$site_name          = isset( $args['site_name'] ) ? (string) $args['site_name'] : '';
$site_description   = isset( $args['site_description'] ) ? (string) $args['site_description'] : '';
$logo_url           = isset( $args['logo_url'] ) ? (string) $args['logo_url'] : '';
$header_main_menu   = isset( $args['header_main_menu'] ) ? (string) $args['header_main_menu'] : '';
$header_bottom_menu = isset( $args['header_bottom_menu'] ) ? (string) $args['header_bottom_menu'] : '';
$mobile_nav_id      = isset( $args['mobile_nav_id'] ) ? (string) $args['mobile_nav_id'] : 'sog-rebrand-mobile-nav';

$show_mobile_toggle = ! empty( $header_main_menu ) || ! empty( $header_bottom_menu );

$school_name_style      = isset( $args['school_name_style'] ) ? (string) $args['school_name_style'] : '';
$site_name_style        = isset( $args['site_name_style'] ) ? (string) $args['site_name_style'] : '';
$site_description_style = isset( $args['site_description_style'] ) ? (string) $args['site_description_style'] : '';
$navigation_styles      = isset( $args['navigation_styles'] ) ? (string) $args['navigation_styles'] : '';
?>
<div class="sog-rebrand__header-core sog-rebrand__header-core--simple-text sog-rebrand__header-core--simple-text-vertical-line-double-search">
	<div class="sog-rebrand__inner">
		<div class="sog-rebrand__header-shell">
			<div class="sog-rebrand__brand-cluster sog-rebrand__school-name-search-cluster">
				<div class="sog-rebrand__brand-cluster">
					<p class="sog-rebrand__brand-title school-name" style="<?php echo esc_attr( $school_name_style ); ?>color: var(--sog-rebrand-header-school-name-color)"><?php echo esc_html( $school_name ); ?></p>
				</div>

				<div class="sog-rebrand__brand-cluster">
					<?php if( $settings['display_site_search_enabled'] && $settings['display_site_search_inline_with_header'] ) : ?>
						<?php load_template( SOG_UNC_REBRAND_PATH . 'templates/header/search-form.php', false, array( 'settings' => $settings ) ); ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="sog-rebrand__header-separator<?php echo ! empty( $settings['header_separator_hide_mobile'] ) ? ' sog-rebrand__header-separator--hide-mobile' : ''; ?>" style="<?php echo esc_attr( sprintf( 'border-top:%1$spx %2$s %3$s;padding:%4$spx %5$spx %6$spx %7$spx;', (int) $settings['header_separator_thickness'], esc_attr( (string) $settings['header_separator_style'] ), esc_attr( (string) $settings['header_separator_color'] ), (int) ( $settings['header_separator_padding_top'] ?? 0 ), (int) ( $settings['header_separator_padding_right'] ?? 0 ), (int) ( $settings['header_separator_padding_bottom'] ?? 0 ), (int) ( $settings['header_separator_padding_left'] ?? 0 ) ) ); ?>"></div>

			<div class="sog-rebrand__brand-cluster">
				<?php if ( !empty($settings['header_text_links_enabled']) ) : ?>
					<?php if( $logo_url  ) : ?>
						<a class="sog-rebrand__brand-link" href="<?php echo esc_url( $logo_url ); ?>">
							<span class="sog-rebrand__brand-title site-name" style="<?php echo esc_attr( $site_name_style ); ?>"><?php echo esc_html( $site_name ); ?></span>
						</a>
					<?php endif; ?>
				<?php else : ?>
					<p class="sog-rebrand__brand-title site-name" style="<?php echo esc_attr( $site_name_style ); ?>"><?php echo esc_html( $site_name ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $site_description ) ) : ?>
				<div class="sog-rebrand__brand-cluster">
					<p class="sog-rebrand__brand-title site-tagline site-description" style="<?php echo esc_attr( $site_description_style ); ?>"><?php echo esc_html( $site_description ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( $header_main_menu ) : ?>
				<div class="sog-rebrand__desktop-nav sog-rebrand__navigation-cluster" style="<?php echo $navigation_styles; ?>">
					<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Header menu', 'sog-unc-rebrand' ); ?>">
						<?php echo wp_kses_post( $header_main_menu ); ?>
					</nav>
				</div>
			<?php endif; ?>

			<?php if ( $show_mobile_toggle ) : ?>
				<button class="sog-rebrand__menu-toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $mobile_nav_id ); ?>" style="<?php echo $navigation_styles; ?>">
					<span class="sog-rebrand__menu-toggle-label"><?php echo esc_html__( 'Menu', 'sog-unc-rebrand' ); ?></span>
					<span class="sog-rebrand__menu-toggle-bars" aria-hidden="true"></span>
				</button>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php if ( $header_bottom_menu || ( ! empty( $settings['display_site_search_enabled'] ) && ! empty( $settings['display_site_search_inline_with_nav'] ) ) ) : ?>
	<?php load_template( SOG_UNC_REBRAND_PATH . 'templates/header/bottom-nav.php', false, array( 'settings' => $settings, 'header_bottom_menu' => $header_bottom_menu ) ); ?>
<?php endif; ?>

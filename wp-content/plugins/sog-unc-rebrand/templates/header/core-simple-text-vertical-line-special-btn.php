<?php
/**
 * Simple text header core template - Vertical w/ line and special button inline with the navigation and the site name.
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

$special_btn_classes = 'sog-rebrand__header-special-button';

if (!empty($settings['header_special_button_hide_mobile'])) {
	$special_btn_classes .= ' sog-rebrand__hide-mobile';
}

$special_padding_top    = isset( $settings['header_special_button_padding_top'] ) ? (int) $settings['header_special_button_padding_top'] : (int) ( $settings['header_special_padding_top'] ?? 14 );
$special_padding_right  = isset( $settings['header_special_button_padding_right'] ) ? (int) $settings['header_special_button_padding_right'] : (int) ( $settings['header_special_padding_right'] ?? 32 );
$special_padding_bottom = isset( $settings['header_special_button_padding_bottom'] ) ? (int) $settings['header_special_button_padding_bottom'] : (int) ( $settings['header_special_padding_bottom'] ?? 14 );
$special_padding_left   = isset( $settings['header_special_button_padding_left'] ) ? (int) $settings['header_special_button_padding_left'] : (int) ( $settings['header_special_padding_left'] ?? 32 );

$special_btn_style_parts = array(
	sprintf( '--special-btn-hover-bg:%s;', esc_attr( (string) ( $settings['header_special_button_hover_color'] ?? 'initial' ) ) ),
	sprintf( '--special-btn-color:%s;', esc_attr( (string) ( $settings['header_special_button_text_color'] ?? '#ffffff' ) ) ),
	sprintf( '--special-btn-bg:%s;', esc_attr( (string) ( $settings['header_special_button_background_color'] ?? '#1E3A57' ) ) ),
	sprintf( '--special-btn-border-color:%s;', esc_attr( (string) ( $settings['header_special_button_border_color'] ?? '#1E3A57' ) ) ),
	sprintf( '--special-btn-border-width:%dpx;', (int) ( $settings['header_special_button_border_thickness'] ?? 1 ) ),
	sprintf( '--special-btn-border-radius:%dpx;', (int) ( $settings['header_special_button_border_radius'] ?? 0 ) ),
	sprintf( '--special-btn-border-style:%s;', esc_attr( (string) ( $settings['header_special_button_border_style'] ?? 'solid' ) ) ),
	sprintf( '--special-btn-font-family:%s;', esc_attr( (string) ( $settings['header_special_font_family'] ?? 'Poppins, sans-serif' ) ) ),
	sprintf( '--special-btn-font-size:%spx;', esc_attr( (string) ( $settings['header_special_font_size'] ?? '16' ) ) ),
	sprintf( '--special-btn-font-weight:%s;', esc_attr( (string) ( $settings['header_special_font_weight'] ?? '600' ) ) ),
	// sprintf( '--special-btn-line-height:%s;', esc_attr( (string) ( $settings['header_special_font_line_height'] ?? '1.5rem' ) ) ),
	sprintf( '--special-btn-font-style:%s;', esc_attr( (string) ( $settings['header_special_font_style'] ?? 'normal' ) ) ),
	sprintf( '--special-btn-text-transform:%s;', esc_attr( (string) ( $settings['header_special_text_transform'] ?? 'capitalize' ) ) ),
	sprintf( '--special-btn-padding-top:%dpx;', $special_padding_top ),
	sprintf( '--special-btn-padding-right:%dpx;', $special_padding_right ),
	sprintf( '--special-btn-padding-bottom:%dpx;', $special_padding_bottom ),
	sprintf( '--special-btn-padding-left:%dpx;', $special_padding_left ),
);

$special_btn_style = implode( '', $special_btn_style_parts );
$site_name_style = '';
$school_name_style = '';

if ( ! empty( $settings['header_school_name_text_transform'] ) ) {
	$school_name_style = 'text-transform:' . esc_attr( $settings['header_school_name_text_transform'] ) . ';';
}

if ( ! empty( $settings['header_site_name_text_transform'] ) ) {
	$site_name_style = 'text-transform:' . esc_attr( $settings['header_site_name_text_transform'] ) . ';';
}
?>

<div class="sog-rebrand__header-core sog-rebrand__header-core--simple-text sog-rebrand__header-core--simple-text-vertical-line-special-btn-navigation-name-inline">
	<div class="sog-rebrand__inner">
		<div class="sog-rebrand__header-shell">
			<div class="sog-rebrand__brand-cluster">
				<p class="sog-rebrand__brand-title school-name" style="<?php echo esc_attr( $school_name_style ); ?>color: var(--sog-rebrand-header-school-name-color)"><?php echo esc_html( $school_name ); ?></p>
			</div>

			<div class="sog-rebrand__header-separator<?php echo ! empty( $settings['header_separator_hide_mobile'] ) ? ' sog-rebrand__header-separator--hide-mobile' : ''; ?>" style="<?php echo esc_attr( sprintf( 'border-top:%1$spx %2$s %3$s;', (int) $settings['header_separator_thickness'], esc_attr( (string) $settings['header_separator_style'] ), esc_attr( (string) $settings['header_separator_color'] ) ) ); ?>"></div>

			<div class="sog-rebrand__brand-cluster sog-rebrand__brand-cluster--nav-special-btn-names-inline">
				<div class="sog-rebrand__brand-cluster sog-rebrand__brand-cluster--site-name-description">
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
							<p class="sog-rebrand__brand-title site-tagline site-description"><?php echo esc_html( $site_description ); ?></p>
						</div>
					<?php endif; ?>
				</div>

				<div class="sog-rebrand__desktop-nav">
					<?php if ( $header_main_menu ) : ?>
						<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Header menu', 'sog-unc-rebrand' ); ?>">
							<?php echo wp_kses_post( $header_main_menu ); ?>
						</nav>
					<?php endif; ?>

					<?php
					// Special individual button
					if (
						!empty($settings['header_special_button_text']) &&
						!empty($settings['header_special_button_url'])
					) :
					?>
						<a href="<?php echo esc_url($settings['header_special_button_url']); ?>" class="<?php echo esc_attr($special_btn_classes); ?>" style="<?php echo esc_attr($special_btn_style); ?>" target="<?php echo !empty($settings['header_special_button_new_tab']) ? '_blank' : '_self'; ?>" rel="<?php echo !empty($settings['header_special_button_new_tab']) ? 'noopener noreferrer' : ''; ?>">
							<span><?php echo esc_html($settings['header_special_button_text']); ?></span>
						</a>
					<?php endif; ?>

					<?php if ( $show_mobile_toggle ) : ?>
						<button class="sog-rebrand__menu-toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $mobile_nav_id ); ?>">
							<span class="sog-rebrand__menu-toggle-label"><?php echo esc_html__( 'Menu', 'sog-unc-rebrand' ); ?></span>
							<span class="sog-rebrand__menu-toggle-bars" aria-hidden="true"></span>
						</button>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

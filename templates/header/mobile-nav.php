<?php
/**
 * Mobile navigation drawer template.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 * @var string $header_main_menu
 * @var string $header_bottom_menu
 * @var string $mobile_nav_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings           = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();
$header_main_menu   = isset( $args['header_main_menu'] ) ? (string) $args['header_main_menu'] : '';
$header_bottom_menu = isset( $args['header_bottom_menu'] ) ? (string) $args['header_bottom_menu'] : '';
$mobile_nav_id      = isset( $args['mobile_nav_id'] ) ? (string) $args['mobile_nav_id'] : 'sog-rebrand-mobile-nav';

if ( empty( $header_main_menu ) && empty( $header_bottom_menu ) ) {
	return;
}

$nav_style = sprintf(
	'--sog-rebrand-nav-font-family:%1$s;--sog-rebrand-nav-font-weight:%2$s;--sog-rebrand-nav-font-style:%3$s;--sog-rebrand-nav-font-size:%4$spx;--sog-rebrand-nav-text-transform:%5$s;--sog-rebrand-nav-text-decoration:%6$s;--sog-rebrand-mobile-menu-text-color:%7$s;--sog-rebrand-mobile-menu-hover-color:%8$s;--sog-rebrand-mobile-menu-background-color:%9$s;'
	. '--sog-rebrand-mobile-nav-item-padding-top:%10$spx;--sog-rebrand-mobile-nav-item-padding-right:%11$spx;--sog-rebrand-mobile-nav-item-padding-bottom:%12$spx;--sog-rebrand-mobile-nav-item-padding-left:%13$spx;',
	esc_attr( (string) ( $settings['header_navigation_font_family'] ?? 'Poppins, sans-serif' ) ),
	esc_attr( (string) ( $settings['header_navigation_font_weight'] ?? '600' ) ),
	esc_attr( (string) ( $settings['header_navigation_font_style'] ?? 'normal' ) ),
	esc_attr( (string) ( $settings['header_navigation_font_size'] ?? '16' ) ),
	esc_attr( (string) ( $settings['header_navigation_text_transform'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['header_navigation_text_decoration'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_text_color'] ?? '#ffffff' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_hover_color'] ?? '#d0d7e2' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_background_color'] ?? '#1E3A57' ) ),
	(int) ( $settings['header_mobile_navigation_item_padding_top'] ?? 0 ),
	(int) ( $settings['header_mobile_navigation_item_padding_right'] ?? 0 ),
	(int) ( $settings['header_mobile_navigation_item_padding_bottom'] ?? 0 ),
	(int) ( $settings['header_mobile_navigation_item_padding_left'] ?? 0 )
);
?>
<div id="<?php echo esc_attr( $mobile_nav_id ); ?>" class="sog-rebrand__mobile-nav" style="<?php echo esc_attr( $nav_style ); ?>" hidden>
	<div class="sog-rebrand__inner">
		<?php if ( $header_main_menu ) : ?>
			<div class="sog-rebrand__mobile-nav-section">
				<p class="sog-rebrand__mobile-nav-label"><?php echo esc_html__( 'Primary Navigation', 'sog-unc-rebrand' ); ?></p>
				<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Mobile header navigation', 'sog-unc-rebrand' ); ?>">
					<?php echo wp_kses_post( $header_main_menu ); ?>
				</nav>
			</div>
		<?php endif; ?>

		<?php if ( $header_bottom_menu ) : ?>
			<div class="sog-rebrand__mobile-nav-section">
				<p class="sog-rebrand__mobile-nav-label"><?php echo esc_html__( 'Secondary Navigation', 'sog-unc-rebrand' ); ?></p>
				<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Mobile secondary navigation', 'sog-unc-rebrand' ); ?>">
					<?php echo wp_kses_post( $header_bottom_menu ); ?>
				</nav>
			</div>
		<?php endif; ?>
	</div>
</div>

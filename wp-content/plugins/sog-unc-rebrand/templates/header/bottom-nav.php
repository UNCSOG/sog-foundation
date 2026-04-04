<?php
/**
 * Header bottom navigation template simple no submenu or search.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 * @var string $header_bottom_menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings           = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();
$header_bottom_menu = isset( $args['header_bottom_menu'] ) ? (string) $args['header_bottom_menu'] : '';

$bottom_style = sprintf(
	'--sog-rebrand-header-bottom-bg:%1$s;--sog-rebrand-bottom-gap:%2$spx;--sog-rebrand-bottom-justify:%3$s;--sog-rebrand-nav-font-family:%4$s;--sog-rebrand-nav-font-weight:%5$s;--sog-rebrand-nav-font-style:%6$s;--sog-rebrand-nav-font-size:%7$spx;--sog-rebrand-nav-text-transform:%8$s;--sog-rebrand-nav-text-decoration:%9$s;--sog-rebrand-mobile-menu-text-color:%10$s;--sog-rebrand-mobile-menu-hover-color:%11$s;--sog-rebrand-mobile-menu-background-color:%12$s;'
	. '--sog-rebrand-nav-item-padding-top:%13$spx;--sog-rebrand-nav-item-padding-right:%14$spx;--sog-rebrand-nav-item-padding-bottom:%15$spx;--sog-rebrand-nav-item-padding-left:%16$spx;'
	. '--sog-rebrand-submenu-nav-item-padding-top:%17$spx;--sog-rebrand-submenu-nav-item-padding-right:%18$spx;--sog-rebrand-submenu-nav-item-padding-bottom:%19$spx;--sog-rebrand-submenu-nav-item-padding-left:%20$spx;'
	. '--sog-rebrand-mobile-nav-item-padding-top:%21$spx;--sog-rebrand-mobile-nav-item-padding-right:%22$spx;--sog-rebrand-mobile-nav-item-padding-bottom:%23$spx;--sog-rebrand-mobile-nav-item-padding-left:%24$spx;',
	esc_attr( (string) $settings['header_bottom_background_color'] ),
	(int) $settings['header_bottom_spacing'],
	esc_attr( (string) $settings['header_bottom_alignment'] ),
	esc_attr( (string) ( $settings['header_navigation_font_family'] ?? 'Poppins, sans-serif' ) ),
	esc_attr( (string) ( $settings['header_navigation_font_weight'] ?? '600' ) ),
	esc_attr( (string) ( $settings['header_navigation_font_style'] ?? 'normal' ) ),
	esc_attr( (string) ( $settings['header_navigation_font_size'] ?? '16' ) ),
	esc_attr( (string) ( $settings['header_navigation_text_transform'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['header_navigation_text_decoration'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_text_color'] ?? '#ffffff' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_hover_color'] ?? '#d0d7e2' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_background_color'] ?? '#1E3A57' ) ),
	(int) ( $settings['header_navigation_item_padding_top'] ?? 0 ),
	(int) ( $settings['header_navigation_item_padding_right'] ?? 0 ),
	(int) ( $settings['header_navigation_item_padding_bottom'] ?? 0 ),
	(int) ( $settings['header_navigation_item_padding_left'] ?? 0 ),
	(int) ( $settings['header_submenu_navigation_item_padding_top'] ?? 0 ),
	(int) ( $settings['header_submenu_navigation_item_padding_right'] ?? 0 ),
	(int) ( $settings['header_submenu_navigation_item_padding_bottom'] ?? 0 ),
	(int) ( $settings['header_submenu_navigation_item_padding_left'] ?? 0 ),
	(int) ( $settings['header_mobile_navigation_item_padding_top'] ?? 0 ),
	(int) ( $settings['header_mobile_navigation_item_padding_right'] ?? 0 ),
	(int) ( $settings['header_mobile_navigation_item_padding_bottom'] ?? 0 ),
	(int) ( $settings['header_mobile_navigation_item_padding_left'] ?? 0 )
);
?>
<div class="sog-rebrand__header-bottom" style="<?php echo esc_attr( $bottom_style ); ?>" data-sog-rebrand-orientation="<?php echo esc_attr( (string) $settings['header_bottom_orientation'] ); ?>" data-sog-rebrand-mobile-mode="<?php echo esc_attr( (string) $settings['header_bottom_mobile_mode'] ); ?>">
	<div class="sog-rebrand__inner">
		<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Header bottom navigation', 'sog-unc-rebrand' ); ?>">
			<?php echo wp_kses_post( $header_bottom_menu ); ?>
		</nav>
	</div>
</div>

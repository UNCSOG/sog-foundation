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
	'--sog-rebrand-nav-font-family:%1$s;--sog-rebrand-nav-font-weight:%2$s;--sog-rebrand-nav-font-style:%3$s;--sog-rebrand-nav-font-size:%4$spx;--sog-rebrand-nav-text-transform:%5$s;--sog-rebrand-nav-text-decoration:%6$s;'
	. '--sog-rebrand-nav-item-padding-top:%7$spx;--sog-rebrand-nav-item-padding-right:%8$spx;--sog-rebrand-nav-item-padding-bottom:%9$spx;--sog-rebrand-nav-item-padding-left:%10$spx;'
	. '--sog-rebrand-submenu-nav-item-padding-top:%11$spx;--sog-rebrand-submenu-nav-item-padding-right:%12$spx;--sog-rebrand-submenu-nav-item-padding-bottom:%13$spx;--sog-rebrand-submenu-nav-item-padding-left:%14$spx;--sog-rebrand-submenu-nav-min-width:%15$spx;'
	. '--sog-rebrand-mobile-nav-item-padding-top:%16$spx;--sog-rebrand-mobile-nav-item-padding-right:%17$spx;--sog-rebrand-mobile-nav-item-padding-bottom:%18$spx;--sog-rebrand-mobile-nav-item-padding-left:%19$spx;'
	. '--sog-rebrand-header-bottom-bg:%20$s;--sog-rebrand-bottom-text:%21$s;--sog-rebrand-bottom-hover:%22$s;--sog-rebrand-bottom-text-hover:%23$s;'
	. '--sog-rebrand-mobile-nav-color:%24$s;--sog-rebrand-mobile-nav-hover:%25$s;--sog-rebrand-mobile-nav-bg:%26$s;--sog-rebrand-mobile-nav-text-hover:%27$s;--sog-rebrand-mobile-nav-min-width:%28$spx;'
	. '--sog-rebrand-submenu-bg:%29$s;--sog-rebrand-submenu-text:%30$s;--sog-rebrand-submenu-hover:%31$s;--sog-rebrand-submenu-text-hover:%32$s;'
	. '--sog-rebrand-mobile-nav-level-two-placement:%33$s;--sog-rebrand-mobile-nav-level-two-width:%34$spx;--sog-rebrand-mobile-nav-level-two-item-padding-top:%35$spx;--sog-rebrand-mobile-nav-level-two-item-padding-right:%36$spx;--sog-rebrand-mobile-nav-level-two-item-padding-bottom:%37$spx;--sog-rebrand-mobile-nav-level-two-item-padding-left:%38$spx;'
	. '--sog-rebrand-mobile-back-button-text:%39$s;' // --sog-rebrand-mobile-back-button-icon-glyph:%40$s;--sog-rebrand-mobile-back-button-icon-mode:%41$s;--sog-rebrand-mobile-back-button-icon-family:%42$s;--sog-rebrand-mobile-back-button-icon-pack-font-awesome:%43$s;'
	. '--sog-rebrand-submenu-indicator-color:%40$s;--sog-rebrand-mobile-menu-indicator-color:%41$s;',
	esc_attr( (string) ( $settings['header_navigation_font_family'] ?? 'Poppins, sans-serif' ) ),
	esc_attr( (string) ( $settings['header_navigation_font_weight'] ?? '600' ) ),
	esc_attr( (string) ( $settings['header_navigation_font_style'] ?? 'normal' ) ),
	(int) ( $settings['header_navigation_font_size'] ?? 16 ),
	esc_attr( (string) ( $settings['header_navigation_text_transform'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['header_navigation_text_decoration'] ?? 'none' ) ),
	(int) ( $settings['header_navigation_item_padding_top'] ?? 0 ),
	(int) ( $settings['header_navigation_item_padding_right'] ?? 0 ),
	(int) ( $settings['header_navigation_item_padding_bottom'] ?? 0 ),
	(int) ( $settings['header_navigation_item_padding_left'] ?? 0 ),
	(int) ( $settings['header_submenu_navigation_item_padding_top'] ?? 0 ),
	(int) ( $settings['header_submenu_navigation_item_padding_right'] ?? 0 ),
	(int) ( $settings['header_submenu_navigation_item_padding_bottom'] ?? 0 ),
	(int) ( $settings['header_submenu_navigation_item_padding_left'] ?? 0 ),
	(int) ( $settings['header_submenu_navigation_min_width'] ?? 160 ),
	(int) ( $settings['header_mobile_navigation_item_padding_top'] ?? 0 ),
	(int) ( $settings['header_mobile_navigation_item_padding_right'] ?? 0 ),
	(int) ( $settings['header_mobile_navigation_item_padding_bottom'] ?? 0 ),
	(int) ( $settings['header_mobile_navigation_item_padding_left'] ?? 0 ),
	esc_attr( (string) ( $settings['header_bottom_background_color'] ?? 'transparent' ) ),
	esc_attr( (string) ( $settings['header_bottom_text_color'] ?? '#ffffff' ) ),
	esc_attr( (string) ( $settings['header_bottom_hover_color'] ?? '#f0f4f8' ) ),
	esc_attr( (string) ( $settings['header_bottom_text_hover_color'] ?? '#0056b3' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_text_color'] ?? '#ffffff' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_hover_color'] ?? '#d0d7e2' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_background_color'] ?? '#1E3A57' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_text_hover_color'] ?? '#d0d7e2' ) ),
	(int) ( $settings['header_mobile_navigation_min_width'] ?? 160 ),
	esc_attr( (string) ( $settings['header_submenu_menu_background_color'] ?? '#1E3A57' ) ),
	esc_attr( (string) ( $settings['header_submenu_menu_text_color'] ?? '#ffffff' ) ),
	esc_attr( (string) ( $settings['header_submenu_menu_hover_color'] ?? '#d0d7e2' ) ),
	esc_attr( (string) ( $settings['header_submenu_menu_text_hover_color'] ?? '#d0d7e2' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_level_two_placement'] ?? 'right' ) ),
	(int) ( $settings['header_mobile_menu_level_two_width'] ?? 160 ),
	(int) ( $settings['header_mobile_menu_level_two_item_padding_top'] ?? 0 ),
	(int) ( $settings['header_mobile_menu_level_two_item_padding_right'] ?? 0 ),
	(int) ( $settings['header_mobile_menu_level_two_item_padding_bottom'] ?? 0 ),
	(int) ( $settings['header_mobile_menu_level_two_item_padding_left'] ?? 0 ),
	esc_attr( (string) ( $settings['header_mobile_back_button_text'] ?? 'Back' ) ),
	// esc_attr( (string) ( $settings['header_mobile_back_button_icon_glyph'] ?? '' ) ),
	// esc_attr( (string) ( $settings['header_mobile_back_button_icon_mode'] ?? 'unicode' ) ),
	// esc_attr( (string) ( $settings['header_mobile_back_button_icon_family'] ?? 'none' ) ),
	// esc_attr( (string) ( $settings['header_mobile_back_button_icon_pack_font_awesome'] ?? 'classic' ) ),
	esc_attr( (string) ( $settings['header_submenu_menu_indicator_color'] ?? '#ffffff' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_indicator_color'] ?? '#ffffff' ) ),
);
$nav_style .= '--sog-rebrand-header-site-name-font-size-mobile:' . (int) ( $settings['header_mobile_site_name_font_size'] ?? 20 ) . 'px;';
$nav_style .= '--sog-rebrand-mobile-menu-active-indicator-color:' . esc_attr( (string) ( $settings['header_mobile_menu_active_indicator_color'] ?? '#4B9CD3' ) ) . ';';
$nav_style .= '--sog-rebrand-mobile-menu-text-click:' . esc_attr( (string) ( $settings['header_mobile_menu_text_click_color'] ?? '#AFAFAF' ) ) . ';';
?>
<div id="<?php echo esc_attr( $mobile_nav_id ); ?>" class="sog-rebrand__mobile-nav show-on-mobile" style="<?php echo esc_attr( $nav_style ); ?>" data-sog-rebrand-mobile-back-button-text="<?php echo esc_attr( (string) ( $settings['header_mobile_back_button_text'] ?? 'Back' ) ); ?>" data-sog-rebrand-mobile-back-button-icon-glyph="<?php echo esc_attr( (string) ( $settings['header_mobile_back_button_icon_glyph'] ?? '' ) ); ?>" data-sog-rebrand-mobile-back-button-icon-mode="<?php echo esc_attr( (string) ( $settings['header_mobile_back_button_icon_mode'] ?? 'unicode' ) ); ?>" data-sog-rebrand-mobile-back-button-icon-family="<?php echo esc_attr( (string) ( $settings['header_mobile_back_button_icon_family'] ?? 'none' ) ); ?>" data-sog-rebrand-mobile-back-button-icon-pack-font-awesome="<?php echo esc_attr( (string) ( $settings['header_mobile_back_button_icon_pack_font_awesome'] ?? 'classic' ) ); ?>" hidden>
	<div class="sog-rebrand__inner">
		<?php if ( $header_main_menu ) : ?>
			<div class="sog-rebrand__mobile-nav-section">
				<p class="sog-rebrand__mobile-nav-label hide hidden"><?php echo esc_html__( 'Primary Navigation', 'sog-unc-rebrand' ); ?></p>
				<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Mobile header navigation', 'sog-unc-rebrand' ); ?>">
					<?php echo wp_kses_post( $header_main_menu ); ?>
				</nav>
			</div>
		<?php endif; ?>

		<?php if ( $header_bottom_menu ) : ?>
			<div class="sog-rebrand__mobile-nav-section">
				<p class="sog-rebrand__mobile-nav-label hide hidden"><?php echo esc_html__( 'Secondary Navigation', 'sog-unc-rebrand' ); ?></p>
				<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Mobile secondary navigation', 'sog-unc-rebrand' ); ?>">
					<?php echo wp_kses_post( $header_bottom_menu ); ?>
				</nav>
			</div>
		<?php endif; ?>
	</div>
</div>

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
	'--sog-rebrand-header-bottom-bg:%1$s;--sog-rebrand-header-bottom-text:%2$s;--sog-rebrand-bottom-gap:%3$spx;--sog-rebrand-bottom-justify:%4$s;--sog-rebrand-nav-font-family:%5$s;--sog-rebrand-nav-font-weight:%6$s;--sog-rebrand-nav-font-style:%7$s;--sog-rebrand-nav-font-size:%8$spx;--sog-rebrand-nav-text-transform:%9$s;--sog-rebrand-nav-text-decoration:%10$s;--sog-rebrand-mobile-nav-color:%11$s;--sog-rebrand-mobile-nav-hover:%12$s;--sog-rebrand-mobile-nav-bg:%13$s;--sog-rebrand-mobile-nav-text-hover:%34$s;'
	. '--sog-rebrand-nav-item-padding-top:%14$spx;--sog-rebrand-nav-item-padding-right:%15$spx;--sog-rebrand-nav-item-padding-bottom:%16$spx;--sog-rebrand-nav-item-padding-left:%17$spx;'
	. '--sog-rebrand-submenu-nav-item-padding-top:%18$spx;--sog-rebrand-submenu-nav-item-padding-right:%19$spx;--sog-rebrand-submenu-nav-item-padding-bottom:%20$spx;--sog-rebrand-submenu-nav-item-padding-left:%21$spx;--sog-rebrand-submenu-nav-min-width:%22$spx;'
	. '--sog-rebrand-mobile-nav-item-padding-top:%23$spx;--sog-rebrand-mobile-nav-item-padding-right:%24$spx;--sog-rebrand-mobile-nav-item-padding-bottom:%25$spx;--sog-rebrand-mobile-nav-item-padding-left:%26$spx;'
	. '--sog-rebrand-submenu-bg:%27$s;--sog-rebrand-submenu-text:%28$s;--sog-rebrand-submenu-hover:%29$s;--sog-rebrand-submenu-text-hover:%30$s;--sog-rebrand-bottom-hover:%31$s;--sog-rebrand-bottom-text-hover:%32$s;--sog-rebrand-bottom-text:%33$s;',
	esc_attr( (string) $settings['header_bottom_background_color'] ),
	esc_attr( (string) ( $settings['header_bottom_text_color'] ?? '#ffffff' ) ),
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
	(int) ( $settings['header_submenu_navigation_min_width'] ?? 160 ),
	(int) ( $settings['header_mobile_navigation_item_padding_top'] ?? 0 ),
	(int) ( $settings['header_mobile_navigation_item_padding_right'] ?? 0 ),
	(int) ( $settings['header_mobile_navigation_item_padding_bottom'] ?? 0 ),
	(int) ( $settings['header_mobile_navigation_item_padding_left'] ?? 0 ),
	esc_attr( (string) ( $settings['header_submenu_menu_background_color'] ?? '#1E3A57' ) ),
	esc_attr( (string) ( $settings['header_submenu_menu_text_color'] ?? '#ffffff' ) ),
	esc_attr( (string) ( $settings['header_submenu_menu_hover_color'] ?? '#d0d7e2' ) ),
	esc_attr( (string) ( $settings['header_submenu_menu_text_hover_color'] ?? '#d0d7e2' ) ),
	esc_attr( (string) ( $settings['header_bottom_hover_color'] ?? '#4b9cd3' ) ),
	esc_attr( (string) ( $settings['header_bottom_text_hover_color'] ?? '#d0d7e2' ) ),
	esc_attr( (string) ( $settings['header_bottom_text_color'] ?? '#ffffff' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_text_hover_color'] ?? '#d0d7e2' ) )
);
?>
<div class="sog-rebrand__header-bottom hide-on-small" style="<?php echo esc_attr( $bottom_style ); ?>" data-sog-rebrand-orientation="<?php echo esc_attr( (string) $settings['header_bottom_orientation'] ); ?>" data-sog-rebrand-mobile-mode="<?php echo esc_attr( (string) $settings['header_bottom_mobile_mode'] ); ?>">
	<div class="sog-rebrand__inner">
		<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Header bottom navigation', 'sog-unc-rebrand' ); ?>">
			<?php echo wp_kses_post( $header_bottom_menu ); ?>
		</nav>

		<?php if( ($settings['header_special_button_enabled'] && !empty( $settings['header_special_button_url'] )) || ($settings['display_site_search_enabled'] && $settings['display_site_search_inline_with_nav']) ) : ?>
			<div class="sog-rebrand__desktop-buttons-search-cluster">
				<?php if( $settings['header_special_button_enabled'] && !empty( $settings['header_special_button_url'] ) ) : ?>
					<?php load_template( SOG_UNC_REBRAND_PATH . 'templates/header/special-button.php', false, array( 'settings' => $settings ) ); ?>
				<?php endif; ?>

				<?php if( $settings['display_site_search_enabled'] && $settings['display_site_search_inline_with_nav'] ) : ?>
					<?php load_template( SOG_UNC_REBRAND_PATH . 'templates/header/search-form.php', false, array( 'settings' => $settings ) ); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>


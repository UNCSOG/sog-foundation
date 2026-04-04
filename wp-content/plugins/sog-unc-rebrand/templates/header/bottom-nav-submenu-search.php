<?php
/**
 * Header bottom navigation template that has submenus and a search form inline with the navigation.
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
<div class="sog-rebrand__header-bottom sog-rebrand__bottom-navigation-submenu-search" style="<?php echo esc_attr( $bottom_style ); ?>" data-sog-rebrand-orientation="<?php echo esc_attr( (string) $settings['header_bottom_orientation'] ); ?>" data-sog-rebrand-mobile-mode="<?php echo esc_attr( (string) $settings['header_bottom_mobile_mode'] ); ?>">
	<div class="sog-rebrand__inner">
		<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Header bottom navigation', 'sog-unc-rebrand' ); ?>">
			<?php echo wp_kses_post( $header_bottom_menu ); ?>
		</nav>

		<?php if( $settings['display_site_search_enabled'] ) : ?>
			<div class="sog-rebrand__site-search-container">
				<?php $placeholder = ! empty( $settings['site_search_placeholder_text'] ) ? esc_attr( $settings['site_search_placeholder_text'] ) : esc_attr__( 'Search the site...', 'sog-unc-rebrand' ); ?>

				<form role="search" method="get" class="sog-rebrand__site-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="sog-rebrand-site-search-field"><?php esc_html_e( 'Search for:', 'sog-unc-rebrand' ); ?></label>
					<input type="search" id="sog-rebrand-site-search-field" class="sog-rebrand__site-search-field" placeholder="<?php echo $placeholder; ?>" value="<?php echo get_search_query(); ?>" name="s" />
					<button type="submit" class="sog-rebrand__site-search-submit" aria-label="<?php esc_attr_e( 'Submit search', 'sog-unc-rebrand' ); ?>">
						<?php if( ! empty( $settings['header_search_icon_enabled'] ) ) : ?>
							<span class="dashicons dashicons-search hide hidden" aria-hidden="true"></span>
							<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10" fill="none">
								<path d="M7.1868 4.06319C7.1868 3.23425 6.85759 2.43926 6.2716 1.85311C5.6856 1.26696 4.89082 0.93766 4.0621 0.93766C3.23338 0.93766 2.4386 1.26696 1.85261 1.85311C1.26662 2.43926 0.937408 3.23425 0.937408 4.06319C0.937408 4.89214 1.26662 5.68713 1.85261 6.27328C2.4386 6.85943 3.23338 7.18873 4.0621 7.18873C4.89082 7.18873 5.6856 6.85943 6.2716 6.27328C6.85759 5.68713 7.1868 4.89214 7.1868 4.06319ZM6.58334 7.24929C5.892 7.79821 5.01514 8.12639 4.0621 8.12639C1.81818 8.12639 0 6.30772 0 4.06319C0 1.81867 1.81818 0 4.0621 0C6.30602 0 8.12421 1.81867 8.12421 4.06319C8.12421 5.01648 7.79611 5.89359 7.24734 6.58511L9.86232 9.20079C10.0459 9.38442 10.0459 9.68134 9.86232 9.86301C9.67874 10.0447 9.3819 10.0466 9.20027 9.86301L6.58334 7.24929Z" fill="#999999"/>
							</svg>
						<?php else : ?>
							<span class="sog-rebrand__site-search-submit-text"><?php echo esc_attr( $settings['site_search_submit_text'] ); ?></span>
						<?php endif; ?>
					</button>
				</form>
			<?php endif; ?>
		</div>
	</div>
</div>

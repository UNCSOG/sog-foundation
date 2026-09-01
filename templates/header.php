<?php
/**
 * Header template orchestrator.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();

$school_name      = ! empty( $settings['header_school_name'] ) ? (string) $settings['header_school_name'] : 'School of Government';
$site_name        = ! empty( $settings['header_text_main'] ) ? (string) $settings['header_text_main'] : get_bloginfo( 'name' );
$site_description = (string) $settings['header_text_subtext'];
$social_links     = is_array( $settings['header_social_links'] ?? null ) ? $settings['header_social_links'] : array();
$logo_url         = '';
$mobile_nav_id    = 'sog-rebrand-mobile-nav-' . wp_unique_id();

if ( 'homepage' === $settings['header_logo_link_behavior'] ) {
	$logo_url = home_url( '/' );
} elseif ( 'custom' === $settings['header_logo_link_behavior'] && ! empty( $settings['header_logo_custom_url'] ) ) {
	$logo_url = (string) $settings['header_logo_custom_url'];
}

$header_main_menu = ! empty( $settings['header_main_menu_enabled'] ) ? wp_nav_menu(
	array(
		'theme_location' => 'sog-rebrand-header-main',
		'container'      => false,
		'menu_class'     => 'sog-rebrand__menu sog-rebrand__menu--header-main',
		'fallback_cb'    => false,
		'echo'           => false,
	)
) : '';

$header_bottom_menu = ! empty( $settings['header_bottom_nav_enabled'] ) ? wp_nav_menu(
	array(
		'theme_location' => 'sog-rebrand-header-bottom',
		'container'      => false,
		'menu_class'     => 'sog-rebrand__menu sog-rebrand__menu--header-bottom',
		'fallback_cb'    => false,
		'echo'           => false,
	)
) : '';

$header_style = sprintf(
	'--sog-rebrand-max-width:%1$spx;--sog-rebrand-mobile-breakpoint:%2$d;--sog-rebrand-header-bg:%3$s;--sog-rebrand-header-text:%4$s;--sog-rebrand-subtext-color:%5$s;--sog-rebrand-header-bottom-bg:%6$s;--sog-rebrand-header-bottom-text:%7$s;--sog-rebrand-header-separator-color:%8$s;--sog-rebrand-max-width-medium-mobile:%9$spx;--sog-rebrand-max-width-small-mobile:%10$spx;--sog-rebrand-header-school-name-color:%11$s;',
	(int) $settings['container_width'],
	(int) $settings['mobile_breakpoint'],
	esc_attr( (string) $settings['header_core_background_color'] ),
	esc_attr( (string) $settings['header_text_color'] ),
	esc_attr( (string) $settings['header_subtext_color'] ),
	esc_attr( (string) $settings['header_bottom_background_color'] ),
	esc_attr( (string) $settings['header_bottom_text_color'] ),
	esc_attr( (string) $settings['header_separator_color'] ),
	isset($settings['container_width_medium_mobile']) ? (int) $settings['container_width_medium_mobile'] : 600,
	isset($settings['container_width_small_mobile']) ? (int) $settings['container_width_small_mobile'] : 375,
	esc_attr( (string) $settings['header_school_name_color'] ),
);
$header_style .= '--sog-rebrand-header-site-name-font-size-mobile:' . (int) ( $settings['header_mobile_site_name_font_size'] ?? 20 ) . 'px;';
$header_style .= '--sog-rebrand-header-school-name-font-size-mobile:' . (int) ( $settings['header_mobile_school_name_font_size'] ?? 16 ) . 'px;';
$header_style .= '--sog-rebrand-header-site-description-font-size-mobile:' . (int) ( $settings['header_mobile_site_description_font_size'] ?? 14 ) . 'px;';

$variant = isset($settings['header_core_variant']) ? (string)$settings['header_core_variant'] : 'image-logo';

// Map variant to template file
$variant_templates = array(
	'image-logo'                                              => 'core-image-logo.php',
	'simple-text'                                             => 'core-simple-text.php',
	'simple-text-vertical'                                    => 'core-simple-text-vertical.php',
	'simple-text-vertical-line'                               => 'core-simple-text-vertical-line.php',
	'simple-text-vertical-line-alternate'                     => 'core-simple-text-vertical-line-alternate.php',
	'simple-text-vertical-line-site-name-school-name-tagline' => 'core-simple-text-vertical-line-site-name-school-name-tagline.php',
	'simple-text-vertical-social-no-nav'                      => 'core-simple-text-vertical-social-no-nav.php',
	'simple-text-vertical-no-nav'                             => 'core-simple-text-vertical-no-nav.php',
	'simple-text-vertical-social-give'                        => 'core-simple-text-vertical-social-give.php',
	'simple-text-vertical-nav-search'                         => 'core-simple-text-vertical-nav-search.php',
	'simple-text-vertical-nav-inline-school-name'             => 'core-simple-text-vertical-nav-inline-school-name.php',
	'simple-text-vertical-line-nav-inline-site-name'          => 'core-simple-text-vertical-line-nav-inline-site-name.php',
	'simple-text-vertical-line-special-btn'                   => 'core-simple-text-vertical-line-special-btn.php',
	'simple-text-vertical-line-double-search'                 => 'core-simple-text-vertical-line-double-search.php',
	'simple-text-vertical-line-tagline-school-name-site-name' => 'core-simple-text-vertical-line-tagline-school-name-site-name.php',
);
$variant_template = isset($variant_templates[$variant]) ? $variant_templates[$variant] : 'core-image-logo.php';

// Build per-element inline style strings passed to child templates.
$school_name_style_parts = array();
if ( ! empty( $settings['header_school_name_text_transform'] ) ) {
	$school_name_style_parts[] = 'text-transform:' . esc_attr( (string) $settings['header_school_name_text_transform'] ) . ';';
}
if ( ! empty( $settings['header_school_name_text_decoration'] ) ) {
	$school_name_style_parts[] = '--sog-rebrand-header-school-name-text-decoration:' . esc_attr( (string) $settings['header_school_name_text_decoration'] ) . ';';
	$school_name_style_parts[] = 'text-decoration:' . esc_attr( (string) $settings['header_school_name_text_decoration'] ) . ';';
}
if ( ! empty( $settings['header_school_name_font_family'] ) ) {
	$school_name_style_parts[] = 'font-family:' . esc_attr( (string) $settings['header_school_name_font_family'] ) . ';';
}
if ( ! empty( $settings['header_school_name_font_weight'] ) ) {
	$school_name_style_parts[] = 'font-weight:' . esc_attr( (string) $settings['header_school_name_font_weight'] ) . ';';
}
if ( ! empty( $settings['header_school_name_font_style'] ) ) {
	$school_name_style_parts[] = 'font-style:' . esc_attr( (string) $settings['header_school_name_font_style'] ) . ';';
}
if ( ! empty( $settings['header_school_name_font_size'] ) ) {
	$school_name_style_parts[] = 'font-size:' . (int) $settings['header_school_name_font_size'] . 'px;';
}
if ( ! empty( $settings['header_mobile_school_name_font_size'] ) ) {
	$school_name_style_parts[] = '--sog-rebrand-header-school-name-font-size-mobile:' . (int) $settings['header_mobile_school_name_font_size'] . 'px;';
}
if ( '' !== ( $settings['header_school_name_line_height'] ?? '' ) ) {
	$school_name_style_parts[] = 'line-height:' . esc_attr( (string) $settings['header_school_name_line_height'] ) . ';';
}
if ( ! empty( $settings['header_school_name_padding_top'] ) ) {
	$school_name_style_parts[] = '--sog-rebrand-header-school-name-padding-top:' . (int) $settings['header_school_name_padding_top'] . 'px;';
	$school_name_style_parts[] = 'padding-top:' . (int) $settings['header_school_name_padding_top'] . 'px;';
}
if ( ! empty( $settings['header_school_name_padding_right'] ) ) {
	$school_name_style_parts[] = '--sog-rebrand-header-school-name-padding-right:' . (int) $settings['header_school_name_padding_right'] . 'px;';
	$school_name_style_parts[] = 'padding-right:' . (int) $settings['header_school_name_padding_right'] . 'px;';
}
if ( ! empty( $settings['header_school_name_padding_bottom'] ) ) {
	$school_name_style_parts[] = '--sog-rebrand-header-school-name-padding-bottom:' . (int) $settings['header_school_name_padding_bottom'] . 'px;';
	$school_name_style_parts[] = 'padding-bottom:' . (int) $settings['header_school_name_padding_bottom'] . 'px;';
}
if ( ! empty( $settings['header_school_name_padding_left'] ) ) {
	$school_name_style_parts[] = '--sog-rebrand-header-school-name-padding-left:' . (int) $settings['header_school_name_padding_left'] . 'px;';
	$school_name_style_parts[] = 'padding-left:' . (int) $settings['header_school_name_padding_left'] . 'px;';
}
$school_name_style = implode( '', $school_name_style_parts );

$site_name_style_parts = array();
if ( ! empty( $settings['header_site_name_text_transform'] ) ) {
	$site_name_style_parts[] = 'text-transform:' . esc_attr( (string) $settings['header_site_name_text_transform'] ) . ';';
}
if ( ! empty( $settings['header_site_name_text_decoration'] ) ) {
	$site_name_style_parts[] = '--sog-rebrand-header-site-name-text-decoration:' . esc_attr( (string) $settings['header_site_name_text_decoration'] ) . ';';
	$site_name_style_parts[] = 'text-decoration:' . esc_attr( (string) $settings['header_site_name_text_decoration'] ) . ';';
}
if ( ! empty( $settings['header_site_name_font_family'] ) ) {
	$site_name_style_parts[] = 'font-family:' . esc_attr( (string) $settings['header_site_name_font_family'] ) . ';';
}
if ( ! empty( $settings['header_site_name_font_weight'] ) ) {
	$site_name_style_parts[] = 'font-weight:' . esc_attr( (string) $settings['header_site_name_font_weight'] ) . ';';
}
if ( ! empty( $settings['header_site_name_font_style'] ) ) {
	$site_name_style_parts[] = 'font-style:' . esc_attr( (string) $settings['header_site_name_font_style'] ) . ';';
}
if ( ! empty( $settings['header_site_name_font_size'] ) ) {
	$site_name_style_parts[] = 'font-size:' . (int) $settings['header_site_name_font_size'] . 'px;';
}
if ( ! empty( $settings['header_mobile_site_name_font_size'] ) ) {
	$site_name_style_parts[] = '--sog-rebrand-header-site-name-font-size-mobile:' . (int) $settings['header_mobile_site_name_font_size'] . 'px;';
}
if ( '' !== ( $settings['header_site_name_line_height'] ?? '' ) ) {
	$site_name_style_parts[] = 'line-height:' . esc_attr( (string) $settings['header_site_name_line_height'] ) . ';';
}
if ( ! empty( $settings['header_site_name_padding_top'] ) ) {
	$site_name_style_parts[] = '--sog-rebrand-header-site-name-padding-top:' . (int) $settings['header_site_name_padding_top'] . 'px;';
	$site_name_style_parts[] = 'padding-top:' . (int) $settings['header_site_name_padding_top'] . 'px;';
}
if ( ! empty( $settings['header_site_name_padding_right'] ) ) {
	$site_name_style_parts[] = '--sog-rebrand-header-site-name-padding-right:' . (int) $settings['header_site_name_padding_right'] . 'px;';
	$site_name_style_parts[] = 'padding-right:' . (int) $settings['header_site_name_padding_right'] . 'px;';
}
if ( ! empty( $settings['header_site_name_padding_bottom'] ) ) {
	$site_name_style_parts[] = '--sog-rebrand-header-site-name-padding-bottom:' . (int) $settings['header_site_name_padding_bottom'] . 'px;';
	$site_name_style_parts[] = 'padding-bottom:' . (int) $settings['header_site_name_padding_bottom'] . 'px;';
}
if ( ! empty( $settings['header_site_name_padding_left'] ) ) {
	$site_name_style_parts[] = '--sog-rebrand-header-site-name-padding-left:' . (int) $settings['header_site_name_padding_left'] . 'px;';
	$site_name_style_parts[] = 'padding-left:' . (int) $settings['header_site_name_padding_left'] . 'px;';
}
$site_name_style = implode( '', $site_name_style_parts );

$site_description_style_parts = array();
if ( ! empty( $settings['header_site_description_text_transform'] ) ) {
	$site_description_style_parts[] = 'text-transform:' . esc_attr( (string) $settings['header_site_description_text_transform'] ) . ';';
}
if ( ! empty( $settings['header_site_description_text_decoration'] ) ) {
	$site_description_style_parts[] = '--sog-rebrand-header-site-description-text-decoration:' . esc_attr( (string) $settings['header_site_description_text_decoration'] ) . ';';
	$site_description_style_parts[] = 'text-decoration:' . esc_attr( (string) $settings['header_site_description_text_decoration'] ) . ';';
}
if ( ! empty( $settings['header_site_description_font_family'] ) ) {
	$site_description_style_parts[] = 'font-family:' . esc_attr( (string) $settings['header_site_description_font_family'] ) . ';';
}
if ( ! empty( $settings['header_site_description_font_weight'] ) ) {
	$site_description_style_parts[] = 'font-weight:' . esc_attr( (string) $settings['header_site_description_font_weight'] ) . ';';
}
if ( ! empty( $settings['header_site_description_font_style'] ) ) {
	$site_description_style_parts[] = 'font-style:' . esc_attr( (string) $settings['header_site_description_font_style'] ) . ';';
}
if ( ! empty( $settings['header_site_description_font_size'] ) ) {
	$site_description_style_parts[] = 'font-size:' . (int) $settings['header_site_description_font_size'] . 'px;';
}
if ( ! empty( $settings['header_mobile_site_description_font_size'] ) ) {
	$site_description_style_parts[] = '--sog-rebrand-header-site-description-font-size-mobile:' . (int) $settings['header_mobile_site_description_font_size'] . 'px;';
}
if ( '' !== ( $settings['header_site_description_line_height'] ?? '' ) ) {
	$site_description_style_parts[] = 'line-height:' . esc_attr( (string) $settings['header_site_description_line_height'] ) . ';';
}
if ( ! empty( $settings['header_site_description_padding_top'] ) ) {
	$site_description_style_parts[] = '--sog-rebrand-header-site-description-padding-top:' . (int) $settings['header_site_description_padding_top'] . 'px;';
	$site_description_style_parts[] = 'padding-top:' . (int) $settings['header_site_description_padding_top'] . 'px;';
}
if ( ! empty( $settings['header_site_description_padding_right'] ) ) {
	$site_description_style_parts[] = '--sog-rebrand-header-site-description-padding-right:' . (int) $settings['header_site_description_padding_right'] . 'px;';
	$site_description_style_parts[] = 'padding-right:' . (int) $settings['header_site_description_padding_right'] . 'px;';
}
if ( ! empty( $settings['header_site_description_padding_bottom'] ) ) {
	$site_description_style_parts[] = '--sog-rebrand-header-site-description-padding-bottom:' . (int) $settings['header_site_description_padding_bottom'] . 'px;';
	$site_description_style_parts[] = 'padding-bottom:' . (int) $settings['header_site_description_padding_bottom'] . 'px;';
}
if ( ! empty( $settings['header_site_description_padding_left'] ) ) {
	$site_description_style_parts[] = '--sog-rebrand-header-site-description-padding-left:' . (int) $settings['header_site_description_padding_left'] . 'px;';
	$site_description_style_parts[] = 'padding-left:' . (int) $settings['header_site_description_padding_left'] . 'px;';
}
$site_description_style = implode( '', $site_description_style_parts );

$_header_small_bp                       = (int) ( $settings['container_width_small_mobile'] ?? 480 );
$_school_name_line_height_small_mobile  = (string) ( $settings['header_school_name_line_height_small_mobile'] ?? '' );
$_site_name_line_height_small_mobile    = (string) ( $settings['header_site_name_line_height_small_mobile'] ?? '' );
$_site_desc_line_height_small_mobile    = (string) ( $settings['header_site_description_line_height_small_mobile'] ?? '' );
$_has_header_mobile_lh = '' !== $_school_name_line_height_small_mobile || '' !== $_site_name_line_height_small_mobile || '' !== $_site_desc_line_height_small_mobile;

$navigation_styles = esc_attr( sprintf( '--sog-rebrand-nav-font-family:%1$s;--sog-rebrand-nav-font-weight:%2$s;--sog-rebrand-nav-font-style:%3$s;--sog-rebrand-nav-font-size:%4$spx;--sog-rebrand-nav-text-transform:%5$s;--sog-rebrand-nav-text-decoration:%6$s;'
	. '--sog-rebrand-nav-item-padding-top:%7$spx;--sog-rebrand-nav-item-padding-right:%8$spx;--sog-rebrand-nav-item-padding-bottom:%9$spx;--sog-rebrand-nav-item-padding-left:%10$spx;'
	. '--sog-rebrand-submenu-nav-item-padding-top:%11$spx;--sog-rebrand-submenu-nav-item-padding-right:%12$spx;--sog-rebrand-submenu-nav-item-padding-bottom:%13$spx;--sog-rebrand-submenu-nav-item-padding-left:%14$spx;--sog-rebrand-submenu-nav-min-width:%15$spx;'
	. '--sog-rebrand-mobile-nav-item-padding-top:%16$spx;--sog-rebrand-mobile-nav-item-padding-right:%17$spx;--sog-rebrand-mobile-nav-item-padding-bottom:%18$spx;--sog-rebrand-mobile-nav-item-padding-left:%19$spx;'
	. '--sog-rebrand-header-bottom-bg:%20$s;--sog-rebrand-bottom-text:%21$s;--sog-rebrand-bottom-hover:%22$s;--sog-rebrand-bottom-text-hover:%23$s;'
	. '--sog-rebrand-mobile-nav-color:%24$s;--sog-rebrand-mobile-nav-hover:%25$s;--sog-rebrand-mobile-nav-bg:%26$s;--sog-rebrand-mobile-nav-text-hover:%27$s;--sog-rebrand-mobile-nav-min-width:%28$spx;'
	. '--sog-rebrand-submenu-bg:%29$s;--sog-rebrand-submenu-text:%30$s;--sog-rebrand-submenu-hover:%31$s;--sog-rebrand-submenu-text-hover:%32$s;'
	. '--sog-rebrand-mobile-nav-level-two-placement:%33$s;--sog-rebrand-mobile-nav-level-two-width:%34$spx;--sog-rebrand-mobile-nav-level-two-item-padding-top:%35$spx;--sog-rebrand-mobile-nav-level-two-item-padding-right:%36$spx;--sog-rebrand-mobile-nav-level-two-item-padding-bottom:%37$spx;--sog-rebrand-mobile-nav-level-two-item-padding-left:%38$spx;'
	. '--sog-rebrand-submenu-indicator-color:%39$s;--sog-rebrand-mobile-menu-indicator-color:%40$s;',
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
	esc_attr( (string) ( $settings['header_submenu_menu_indicator_color'] ?? '#ffffff' ) ),
	esc_attr( (string) ( $settings['header_mobile_menu_indicator_color'] ?? '#ffffff' ) ),
) );
$navigation_styles .= '--sog-rebrand-header-site-name-font-size-mobile:' . (int) ( $settings['header_mobile_site_name_font_size'] ?? 20 ) . 'px;';
$navigation_styles .= '--sog-rebrand-header-school-name-font-size-mobile:' . (int) ( $settings['header_mobile_school_name_font_size'] ?? 16 ) . 'px;';
$navigation_styles .= '--sog-rebrand-header-site-description-font-size-mobile:' . (int) ( $settings['header_mobile_site_description_font_size'] ?? 14 ) . 'px;';
$navigation_styles .= '--sog-rebrand-mobile-menu-active-indicator-color:' . esc_attr( (string) ( $settings['header_mobile_menu_active_indicator_color'] ?? '#4B9CD3' ) ) . ';';
$navigation_styles .= '--sog-rebrand-bottom-text-click:' . esc_attr( (string) ( $settings['header_bottom_text_click_color'] ?? '#AFAFAF' ) ) . ';';
$navigation_styles .= '--sog-rebrand-mobile-menu-text-click:' . esc_attr( (string) ( $settings['header_mobile_menu_text_click_color'] ?? '#AFAFAF' ) ) . ';';

if ( $_has_header_mobile_lh ) : ?>
	<style>
		@media (max-width: <?php echo (int) $_header_small_bp; ?>px) {
			.sog-rebrand__header[data-sog-rebrand-component="header"] {
				<?php if ( '' !== $_school_name_line_height_small_mobile ) : ?>
					& .sog-rebrand__brand-title.school-name { line-height: <?php echo esc_html( $_school_name_line_height_small_mobile ); ?> !important; }
				<?php endif; ?>

				<?php if ( '' !== $_site_name_line_height_small_mobile ) : ?>
					& .sog-rebrand__brand-title.site-name { line-height: <?php echo esc_html( $_site_name_line_height_small_mobile ); ?> !important; }
				<?php endif; ?>

				<?php if ( '' !== $_site_desc_line_height_small_mobile ) : ?>
					& .sog-rebrand__brand-title.site-description { line-height: <?php echo esc_html( $_site_desc_line_height_small_mobile ); ?> !important; }
				<?php endif; ?>
			}
		}
	</style>
<?php endif; ?>

<header class="sog-rebrand__header" data-sog-rebrand-component="header"
	data-sog-rebrand-variant="<?php echo esc_attr( (string) $settings['header_core_variant'] ); ?>"
	data-sog-rebrand-mobile-breakpoint="<?php echo esc_attr( (string) (int) $settings['mobile_breakpoint'] ); ?>"
	data-sog-rebrand-max-width-medium-mobile="<?php echo esc_attr(isset($settings['container_width_medium_mobile']) ? (int)$settings['container_width_medium_mobile'] : 600); ?>"
	data-sog-rebrand-max-width-small-mobile="<?php echo esc_attr(isset($settings['container_width_small_mobile']) ? (int)$settings['container_width_small_mobile'] : 375); ?>"
	style="<?php echo esc_attr( $header_style ); ?>">
	<?php if ( ! empty( $settings['utility_bar_enabled'] ) ) : ?>
		<?php load_template( SOG_UNC_REBRAND_PATH . 'templates/partials/utility-bar.php', false, array( 'settings' => $settings ) ); ?>
	<?php endif; ?>

	<?php
	load_template(
		SOG_UNC_REBRAND_PATH . 'templates/header/' . $variant_template,
		false,
		array(
			'settings'               => $settings,
			'school_name'            => $school_name,
			'site_name'              => $site_name,
			'site_description'       => $site_description,
			'logo_url'               => $logo_url,
			'header_main_menu'       => $header_main_menu,
			'header_bottom_menu'     => $header_bottom_menu,
			'mobile_nav_id'          => $mobile_nav_id,
			'navigation_styles'      => $navigation_styles,
			'school_name_style'      => $school_name_style,
			'site_name_style'        => $site_name_style,
			'site_description_style' => $site_description_style,
			'social_links'           => $social_links,
		)
	);
	?>

	<?php
	load_template(
		SOG_UNC_REBRAND_PATH . 'templates/header/mobile-nav.php',
		false,
		array(
			'settings'           => $settings,
			'header_main_menu'   => $header_main_menu,
			'header_bottom_menu' => $header_bottom_menu,
			'mobile_nav_id'      => $mobile_nav_id,
		)
	);
	?>
</header>

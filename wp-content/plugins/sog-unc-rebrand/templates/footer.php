<?php
/**
 * Footer template orchestrator.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();

$social_links = is_array( $settings['footer_social_links'] ) ? $settings['footer_social_links'] : array();

$footer_style = sprintf(
	'--sog-rebrand-max-width:%1$spx;--sog-rebrand-mobile-breakpoint:%2$d;--sog-rebrand-footer-bg-start:%3$s;--sog-rebrand-footer-bg-end:%3$s;--sog-rebrand-footer-overlay:transparent;--sog-rebrand-footer-text:%4$s;--sog-rebrand-footer-heading-text:%5$s;--sog-rebrand-footer-link-text:%6$s;--sog-rebrand-footer-link-hover:%7$s;--sog-rebrand-footer-muted-text:%8$s;--sog-rebrand-footer-gap:%9$spx;--sog-rebrand-footer-column-2-gap:%10$spx;--sog-rebrand-max-width-medium-mobile:%11$spx;--sog-rebrand-max-width-small-mobile:%12$spx;--footer_give_social_gap:%13$spx;'
	.'--footer-column-1-heading-alignment:%14$s;--footer-column-1-heading-text-transform:%15$s;--footer-column-1-heading-text-decoration:%16$s;'
	.'--footer-column-1-menu-font-family:%17$s;--footer-column-1-menu-font-weight:%18$s;--footer-column-1-menu-font-style:%19$s;--footer-column-1-menu-font-size:%20$spx;--footer-column-1-menu-text-transform:%21$s;--footer-column-1-menu-text-decoration:%22$s;'
	.'--footer-column-2-heading-alignment:%23$s;--footer-column-2-heading-text-transform:%24$s;--footer-column-2-heading-text-decoration:%25$s;'
	.'--footer-column-2-menu-font-family:%26$s;--footer-column-2-menu-font-weight:%27$s;--footer-column-2-menu-font-style:%28$s;--footer-column-2-menu-font-size:%29$spx;--footer-column-2-menu-text-transform:%30$s;--footer-column-2-menu-text-decoration:%31$s;'
	.'--footer-column-3-heading-alignment:%32$s;--footer-column-3-heading-text-transform:%33$s;--footer-column-3-heading-text-decoration:%34$s;'
	.'--footer-column-3-menu-font-family:%35$s;--footer-column-3-menu-font-weight:%36$s;--footer-column-3-menu-font-style:%37$s;--footer-column-3-menu-font-size:%38$spx;--footer-column-3-menu-text-transform:%39$s;--footer-column-3-menu-text-decoration:%40$s;'
	.'--footer-column-1-content-font-family:%41$s;--footer-column-1-content-font-weight:%42$s;--footer-column-1-content-font-style:%43$s;--footer-column-1-content-font-size:%44$spx;'
	.'--footer-bottom-copyright-text-font-family:%45$s;--footer-bottom-copyright-text-font-weight:%46$s;--footer-bottom-copyright-text-font-style:%47$s;--footer-bottom-copyright-text-font-size:%48$spx;--footer-bottom-copyright-text-line-height:%49$s;--footer-bottom-copyright-text-transform:%50$s;--footer-bottom-copyright-text-decoration:%51$s;--footer-bottom-copyright-text-padding-top:%52$spx;--footer-bottom-copyright-text-padding-right:%53$spx;--footer-bottom-copyright-text-padding-bottom:%54$spx;--footer-bottom-copyright-text-padding-left:%55$spx;'
	.'--footer-bottom-copyright-links-font-family:%56$s;--footer-bottom-copyright-links-font-weight:%57$s;--footer-bottom-copyright-links-font-style:%58$s;--footer-bottom-copyright-links-font-size:%59$spx;--footer-bottom-copyright-links-line-height:%60$s;--footer-bottom-copyright-links-transform:%61$s;--footer-bottom-copyright-links-decoration:%62$s;--footer-bottom-copyright-links-padding-top:%63$spx;--footer-bottom-copyright-links-padding-right:%64$spx;--footer-bottom-copyright-links-padding-bottom:%65$spx;--footer-bottom-copyright-links-padding-left:%66$spx;',
	(int) $settings['container_width'],
	(int) $settings['mobile_breakpoint'],
	esc_attr( (string) $settings['footer_background_color'] ),
	esc_attr( (string) $settings['footer_text_color'] ),
	esc_attr( (string) $settings['footer_heading_color'] ),
	esc_attr( (string) $settings['footer_link_color'] ),
	esc_attr( (string) $settings['footer_link_hover_color'] ),
	esc_attr( (string) $settings['footer_muted_text_color'] ),
	(int) $settings['footer_column_gap'],
	(int) $settings['footer_column_2_gap'],
	isset($settings['container_width_medium_mobile']) ? (int) $settings['container_width_medium_mobile'] : 600,
	isset($settings['container_width_small_mobile']) ? (int) $settings['container_width_small_mobile'] : 375,
	(int) $settings['footer_give_social_gap'],
	// Footer column 1 heading and menu styles
	esc_attr( (string) ( $settings['footer_column_1_heading_alignment'] ?? 'left' ) ),
	esc_attr( (string) ( $settings['footer_column_1_heading_text_transform'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_column_1_heading_text_decoration'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_column_1_menu_font_family'] ?? 'Open Sans, sans-serif' ) ),
	(int) ( $settings['footer_column_1_menu_font_weight'] ?? 500 ),
	esc_attr( (string) ( $settings['footer_column_1_menu_font_style'] ?? 'normal' ) ),
	(int) ( $settings['footer_column_1_menu_font_size'] ?? 16 ),
	esc_attr( (string) ( $settings['footer_column_1_menu_text_transform'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_column_1_menu_text_decoration'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_column_2_heading_alignment'] ?? 'left' ) ),
	esc_attr( (string) ( $settings['footer_column_2_heading_text_transform'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_column_2_heading_text_decoration'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_column_2_menu_font_family'] ?? 'Open Sans, sans-serif' ) ),
	(int) ( $settings['footer_column_2_menu_font_weight'] ?? 500 ),
	esc_attr( (string) ( $settings['footer_column_2_menu_font_style'] ?? 'normal' ) ),
	(int) ( $settings['footer_column_2_menu_font_size'] ?? 16 ),
	esc_attr( (string) ( $settings['footer_column_2_menu_text_transform'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_column_2_menu_text_decoration'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_column_3_heading_alignment'] ?? 'left' ) ),
	esc_attr( (string) ( $settings['footer_column_3_heading_text_transform'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_column_3_heading_text_decoration'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_column_3_menu_font_family'] ?? 'Open Sans, sans-serif' ) ),
	(int) ( $settings['footer_column_3_menu_font_weight'] ?? 500 ),
	esc_attr( (string) ( $settings['footer_column_3_menu_font_style'] ?? 'normal' ) ),
	(int) ( $settings['footer_column_3_menu_font_size'] ?? 16 ),
	esc_attr( (string) ( $settings['footer_column_3_menu_text_transform'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_column_3_menu_text_decoration'] ?? 'none' ) ),
	// Footer column 1 content font styles
	esc_attr( (string) ( $settings['footer_column_1_content_font_family'] ?? 'Montserrat, sans-serif' ) ),
	(int) ( $settings['footer_column_1_content_font_weight'] ?? 600 ),
	esc_attr( (string) ( $settings['footer_column_1_content_font_style'] ?? 'normal' ) ),
	(int) ( $settings['footer_column_1_content_font_size'] ?? 20 ),
	// Footer bottom copyright text styles
	esc_attr( (string) ( $settings['footer_bottom_copyright_text_font_family'] ?? 'Montserrat, sans-serif' ) ),
	(int) ( $settings['footer_bottom_copyright_text_font_weight'] ?? 400 ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_text_font_style'] ?? 'normal' ) ),
	(int) ( $settings['footer_bottom_copyright_text_font_size'] ?? 16 ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_text_line_height'] ?? '1.87806rem' ) ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_text_transform'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_text_decoration'] ?? 'none' ) ),
	(int) ( $settings['footer_bottom_copyright_text_padding_top'] ?? 0 ),
	(int) ( $settings['footer_bottom_copyright_text_padding_right'] ?? 16 ),
	(int) ( $settings['footer_bottom_copyright_text_padding_bottom'] ?? 8 ),
	(int) ( $settings['footer_bottom_copyright_text_padding_left'] ?? 16 ),
	// Footer bottom copyright links styles
	esc_attr( (string) ( $settings['footer_bottom_copyright_links_font_family'] ?? 'Montserrat, sans-serif' ) ),
	(int) ( $settings['footer_bottom_copyright_links_font_weight'] ?? 400 ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_links_font_style'] ?? 'normal' ) ),
	(int) ( $settings['footer_bottom_copyright_links_font_size'] ?? 16 ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_links_line_height'] ?? '2.5rem' ) ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_links_transform'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_links_decoration'] ?? 'underline' ) ),
	(int) ( $settings['footer_bottom_copyright_links_padding_top'] ?? 0 ),
	(int) ( $settings['footer_bottom_copyright_links_padding_right'] ?? 16 ),
	(int) ( $settings['footer_bottom_copyright_links_padding_bottom'] ?? 8 ),
	(int) ( $settings['footer_bottom_copyright_links_padding_left'] ?? 16 )

);

// Conditionally append menu line-height custom properties — only when a value is stored.
foreach ( array( 1, 2, 3 ) as $col_lh_index ) {
	$col_lh = (string) ( $settings[ 'footer_column_' . $col_lh_index . '_menu_line_height' ] ?? '' );
	if ( '' !== $col_lh ) {
		$footer_style .= '--footer-column-' . $col_lh_index . '-menu-line-height:' . esc_attr( $col_lh ) . ';';
	}
}

$footer_columns = array(
	1 => array(
		'menu'     => wp_nav_menu(
			array(
				'theme_location' => 'sog-rebrand-footer-1',
				'container'      => false,
				'menu_class'     => 'sog-rebrand__menu sog-rebrand__menu--footer',
				'fallback_cb'    => false,
				'echo'           => false,
			)
		),
		'menu2'    => wp_nav_menu(
			array(
				'theme_location' => 'sog-rebrand-footer-1-second',
				'container'      => false,
				'menu_class'     => 'sog-rebrand__menu sog-rebrand__menu--footer',
				'fallback_cb'    => false,
				'echo'           => false,
			)
		),
		'mode'     => (string) $settings['footer_column_1_mode'],
		'width'    => (int) $settings['footer_column_1_width'],
		'hidden'   => ! empty( $settings['footer_column_1_hide_mobile'] ),
		'content'  => (string) $settings['footer_column_1_content'],
		'heading'  => (string) $settings['footer_column_1_heading'],
		'heading2' => (string) ( $settings['footer_column_1_heading_2'] ?? '' ),
	),
	2 => array(
		'menu'     => wp_nav_menu(
			array(
				'theme_location' => 'sog-rebrand-footer-2',
				'container'      => false,
				'menu_class'     => 'sog-rebrand__menu sog-rebrand__menu--footer',
				'fallback_cb'    => false,
				'echo'           => false,
			)
		),
		'menu2'    => wp_nav_menu(
			array(
				'theme_location' => 'sog-rebrand-footer-2-second',
				'container'      => false,
				'menu_class'     => 'sog-rebrand__menu sog-rebrand__menu--footer',
				'fallback_cb'    => false,
				'echo'           => false,
			)
		),
		'mode'     => (string) $settings['footer_column_2_mode'],
		'width'    => (int) $settings['footer_column_2_width'],
		'hidden'   => ! empty( $settings['footer_column_2_hide_mobile'] ),
		'content'  => (string) $settings['footer_column_2_content'],
		'heading'  => (string) $settings['footer_column_2_heading'],
		'heading2' => (string) ( $settings['footer_column_2_heading_2'] ?? '' ),
	),
	3 => array(
		'menu'     => wp_nav_menu(
			array(
				'theme_location' => 'sog-rebrand-footer-3',
				'container'      => false,
				'menu_class'     => 'sog-rebrand__menu sog-rebrand__menu--footer',
				'fallback_cb'    => false,
				'echo'           => false,
			)
		),
		'menu2'    => wp_nav_menu(
			array(
				'theme_location' => 'sog-rebrand-footer-3-second',
				'container'      => false,
				'menu_class'     => 'sog-rebrand__menu sog-rebrand__menu--footer',
				'fallback_cb'    => false,
				'echo'           => false,
			)
		),
		'mode'     => (string) $settings['footer_column_3_mode'],
		'width'    => (int) $settings['footer_column_3_width'],
		'hidden'   => ! empty( $settings['footer_column_3_hide_mobile'] ),
		'content'  => (string) $settings['footer_column_3_content'],
		'heading'  => (string) $settings['footer_column_3_heading'],
		'heading2' => (string) ( $settings['footer_column_3_heading_2'] ?? '' ),
	),
);

$footer_bottom_menu = wp_nav_menu(
	array(
		'theme_location' => 'sog-rebrand-footer-bottom',
		'container'      => false,
		'menu_class'     => 'sog-rebrand__menu sog-rebrand__menu--footer-bottom',
		'fallback_cb'    => false,
		'echo'           => false,
	)
);
?>
<footer class="sog-rebrand__footer" data-sog-rebrand-component="footer"
	data-sog-rebrand-mobile-breakpoint="<?php echo esc_attr( (string) (int) $settings['mobile_breakpoint'] ); ?>"
	data-sog-rebrand-max-width-medium-mobile="<?php echo esc_attr(isset($settings['container_width_medium_mobile']) ? (int)$settings['container_width_medium_mobile'] : 600); ?>"
	data-sog-rebrand-max-width-small-mobile="<?php echo esc_attr(isset($settings['container_width_small_mobile']) ? (int)$settings['container_width_small_mobile'] : 375); ?>"
	style="<?php echo esc_attr( $footer_style ); ?>">
	<div class="sog-rebrand__inner">
		<?php
		load_template(
			SOG_UNC_REBRAND_PATH . 'templates/footer/logos-row.php',
			false,
			array(
				'settings' => $settings,
			)
		);
		?>

		<?php
		load_template(
			SOG_UNC_REBRAND_PATH . 'templates/footer/core-row.php',
			false,
			array(
				'settings'       => $settings,
				'social_links'   => $social_links,
				'footer_columns' => $footer_columns,
			)
		);
		?>

		<?php
		load_template(
			SOG_UNC_REBRAND_PATH . 'templates/footer/bottom-row.php',
			false,
			array(
				'settings'           => $settings,
				'footer_bottom_menu' => $footer_bottom_menu,
			)
		);
		?>
	</div>
</footer>

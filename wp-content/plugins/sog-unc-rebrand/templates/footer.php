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
	'--sog-rebrand-max-width:%1$spx;--sog-rebrand-mobile-breakpoint:%2$d;--sog-rebrand-footer-bg-start:%3$s;--sog-rebrand-footer-bg-end:%3$s;--sog-rebrand-footer-overlay:transparent;--sog-rebrand-footer-text:%4$s;--sog-rebrand-footer-heading-text:%5$s;--sog-rebrand-footer-link-text:%6$s;--sog-rebrand-footer-link-hover:%7$s;--sog-rebrand-footer-muted-text:%8$s;--sog-rebrand-footer-gap:%9$spx;--sog-rebrand-footer-column-2-gap:%10$spx;--sog-rebrand-max-width-medium-mobile:%11$spx;--sog-rebrand-max-width-small-mobile:%12$spx;--footer_give_social_gap:%13$spx;',
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
	(int) $settings['footer_give_social_gap']
);

$footer_columns = array(
	1 => array(
		'menu'    => wp_nav_menu(
			array(
				'theme_location' => 'sog-rebrand-footer-1',
				'container'      => false,
				'menu_class'     => 'sog-rebrand__menu sog-rebrand__menu--footer',
				'fallback_cb'    => false,
				'echo'           => false,
			)
		),
		'mode'    => (string) $settings['footer_column_1_mode'],
		'width'   => (int) $settings['footer_column_1_width'],
		'hidden'  => ! empty( $settings['footer_column_1_hide_mobile'] ),
		'content' => (string) $settings['footer_column_1_content'],
		'heading' => (string) $settings['footer_column_1_heading'],
	),
	2 => array(
		'menu'    => wp_nav_menu(
			array(
				'theme_location' => 'sog-rebrand-footer-2',
				'container'      => false,
				'menu_class'     => 'sog-rebrand__menu sog-rebrand__menu--footer',
				'fallback_cb'    => false,
				'echo'           => false,
			)
		),
		'mode'    => (string) $settings['footer_column_2_mode'],
		'width'   => (int) $settings['footer_column_2_width'],
		'hidden'  => ! empty( $settings['footer_column_2_hide_mobile'] ),
		'content' => (string) $settings['footer_column_2_content'],
		'heading' => (string) $settings['footer_column_2_heading'],
	),
	3 => array(
		'menu'    => wp_nav_menu(
			array(
				'theme_location' => 'sog-rebrand-footer-3',
				'container'      => false,
				'menu_class'     => 'sog-rebrand__menu sog-rebrand__menu--footer',
				'fallback_cb'    => false,
				'echo'           => false,
			)
		),
		'mode'    => (string) $settings['footer_column_3_mode'],
		'width'   => (int) $settings['footer_column_3_width'],
		'hidden'  => ! empty( $settings['footer_column_3_hide_mobile'] ),
		'content' => (string) $settings['footer_column_3_content'],
		'heading' => (string) $settings['footer_column_3_heading'],
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

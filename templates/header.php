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

$variant = isset($settings['header_core_variant']) ? (string)$settings['header_core_variant'] : 'image-logo';
// Map variant to template file
$variant_templates = array(
	'image-logo' => 'core-image-logo.php',
	'simple-text' => 'core-simple-text.php',
	'simple-text-vertical' => 'core-simple-text-vertical.php',
	'simple-text-vertical-line' => 'core-simple-text-vertical-line.php',
	'simple-text-vertical-line-alternate' => 'core-simple-text-vertical-line-alternate.php',
	'simple-text-vertical-social-no-nav' => 'core-simple-text-vertical-social-no-nav.php',
	'simple-text-vertical-no-nav' => 'core-simple-text-vertical-no-nav.php',
	'simple-text-vertical-social-give' => 'core-simple-text-vertical-social-give.php',
	'simple-text-vertical-search' => 'core-simple-text-vertical-search.php',
	'simple-text-vertical-nav-search' => 'core-simple-text-vertical-nav-search.php',
	'simple-text-vertical-nav-inline-school-name' => 'core-simple-text-vertical-nav-inline-school-name.php',
	'simple-text-vertical-line-special-btn' => 'core-simple-text-vertical-line-special-btn.php',
);
$variant_template = isset($variant_templates[$variant]) ? $variant_templates[$variant] : 'core-image-logo.php';
?>
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
			'settings'           => $settings,
			'school_name'        => $school_name,
			'site_name'          => $site_name,
			'site_description'   => $site_description,
			'logo_url'           => $logo_url,
			'header_main_menu'   => $header_main_menu,
			'header_bottom_menu' => $header_bottom_menu,
			'mobile_nav_id'      => $mobile_nav_id,
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

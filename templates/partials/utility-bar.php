<?php
/**
 * Utility bar template.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();

$utility_menu = wp_nav_menu(
	array(
		'theme_location' => 'sog-rebrand-utility-bar',
		'container'      => false,
		'menu_class'     => 'sog-rebrand__menu sog-rebrand__menu--utility',
		'fallback_cb'    => false,
		'echo'           => false,
	)
);

$utility_style = sprintf(
	'--sog-rebrand-utility-bg:%1$s;--sog-rebrand-utility-text:%2$s;',
	esc_attr( (string) $settings['utility_bar_background_color'] ),
	esc_attr( (string) $settings['utility_bar_text_color'] )
);

$default_links = array(
	array(
		'label' => __( 'Accessibility', 'sog-unc-rebrand' ),
		'url'   => 'https://www.unc.edu/about/accessibility/',
	),
	array(
		'label' => __( 'Events', 'sog-unc-rebrand' ),
		'url'   => 'https://www.unc.edu/events/',
	),
	array(
		'label' => __( 'Libraries', 'sog-unc-rebrand' ),
		'url'   => 'https://library.unc.edu/',
	),
	array(
		'label' => __( 'Maps', 'sog-unc-rebrand' ),
		'url'   => 'https://maps.unc.edu/',
	),
	array(
		'label' => __( 'Departments', 'sog-unc-rebrand' ),
		'url'   => 'https://www.unc.edu/a-z/',
	),
	array(
		'label' => __( 'ConnectCarolina', 'sog-unc-rebrand' ),
		'url'   => 'https://connectcarolina.unc.edu/',
	),
	array(
		'label' => __( 'UNC Search', 'sog-unc-rebrand' ),
		'url'   => 'https://www.unc.edu/search',
	),
);
?>
<div class="sog-rebrand__utility-bar" data-sog-rebrand-component="utility-bar" style="<?php echo esc_attr( $utility_style ); ?>">
	<div class="sog-rebrand__inner">
		<div class="sog-rebrand__utility-shell">
			<a class="sog-rebrand__utility-brand" href="https://www.unc.edu/">
				<?php if ( ! empty( $settings['utility_bar_brand_logo_url'] ) ) : ?>
					<img class="sog-rebrand__utility-brand-icon" src="<?php echo esc_url( $settings['utility_bar_brand_logo_url'] ); ?>" alt="" style="<?php echo esc_attr( sprintf( '--sog-rebrand-utility-brand-logo-width:%1$spx;--sog-rebrand-utility-brand-logo-height:%2$spx;', (int) $settings['utility_bar_brand_logo_width'], (int) $settings['utility_bar_brand_logo_height'] ) ); ?>" />
				<?php endif; ?>

				<span class="sog-rebrand__utility-brand-lockup"><?php echo esc_html( (string) $settings['utility_bar_brand_label'] ); ?></span>
				<span class="sog-rebrand__visually-hidden"><?php echo esc_html__( 'University of North Carolina at Chapel Hill', 'sog-unc-rebrand' ); ?></span>
			</a>

			<?php if ( $utility_menu ) : ?>
				<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Utility bar menu', 'sog-unc-rebrand' ); ?>">
					<?php echo wp_kses_post( $utility_menu ); ?>
				</nav>
			<?php elseif ( ! empty( $settings['utility_bar_menu_fallback_enabled'] ) ) : ?>
				<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Default UNC utility links', 'sog-unc-rebrand' ); ?>">
					<ul class="sog-rebrand__menu sog-rebrand__menu--utility">
						<?php foreach ( $default_links as $link ) : ?>
							<li class="menu-item">
								<a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>
		</div>
	</div>
</div>

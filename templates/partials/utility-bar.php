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

$utility_style = implode(
	';',
	array(
		'--sog-rebrand-utility-bg:'                            . esc_attr( (string) ( $settings['utility_bar_background_color'] ?? '#4b9cd3' ) ),
		'--sog-rebrand-utility-text:'                          . esc_attr( (string) ( $settings['utility_bar_text_color'] ?? '#1E3A57' ) ),
		'--sog-rebrand-utility-height:'                        . (int) ( $settings['utility_bar_height'] ?? 22 ) . 'px',
		'--sog-rebrand-utility-margin-top:'                    . (int) ( $settings['utility_bar_margin_top'] ?? 0 ) . 'px',
		'--sog-rebrand-utility-margin-right:'                  . (int) ( $settings['utility_bar_margin_right'] ?? 0 ) . 'px',
		'--sog-rebrand-utility-margin-bottom:'                 . (int) ( $settings['utility_bar_margin_bottom'] ?? 0 ) . 'px',
		'--sog-rebrand-utility-margin-left:'                   . (int) ( $settings['utility_bar_margin_left'] ?? 0 ) . 'px',
		'--sog-rebrand-utility-padding-top:'                   . (int) ( $settings['utility_bar_padding_top'] ?? 0 ) . 'px',
		'--sog-rebrand-utility-padding-right:'                 . (int) ( $settings['utility_bar_padding_right'] ?? 0 ) . 'px',
		'--sog-rebrand-utility-padding-bottom:'                . (int) ( $settings['utility_bar_padding_bottom'] ?? 0 ) . 'px',
		'--sog-rebrand-utility-padding-left:'                  . (int) ( $settings['utility_bar_padding_left'] ?? 0 ) . 'px',
		'--sog-rebrand-utility-brand-logo-width:'              . (int) ( $settings['utility_bar_brand_logo_width'] ?? 40 ) . 'px',
		'--sog-rebrand-utility-brand-logo-height:'             . (int) ( $settings['utility_bar_brand_logo_height'] ?? 35 ) . 'px',
		'--sog-rebrand-utility-brand-label-font-family:'       . esc_attr( (string) ( $settings['utility_bar_brand_label_font_family'] ?? 'inherit' ) ),
		'--sog-rebrand-utility-brand-label-font-weight:'       . (int) ( $settings['utility_bar_brand_label_font_weight'] ?? 400 ),
		'--sog-rebrand-utility-brand-label-font-style:'        . esc_attr( (string) ( $settings['utility_bar_brand_label_font_style'] ?? 'normal' ) ),
		'--sog-rebrand-utility-brand-label-font-size:'         . (int) ( $settings['utility_bar_brand_label_font_size'] ?? 16 ) . 'px',
		'--sog-rebrand-utility-brand-label-text-transform:'    . esc_attr( (string) ( $settings['utility_bar_brand_label_text_transform'] ?? 'none' ) ),
		'--sog-rebrand-utility-brand-label-text-decoration:'   . esc_attr( (string) ( $settings['utility_bar_brand_label_text_decoration'] ?? 'none' ) ),
		'--sog-rebrand-utility-brand-label-padding-top:'       . (int) ( $settings['utility_bar_brand_label_padding_top'] ?? 0 ) . 'px',
		'--sog-rebrand-utility-brand-label-padding-right:'     . (int) ( $settings['utility_bar_brand_label_padding_right'] ?? 0 ) . 'px',
		'--sog-rebrand-utility-brand-label-padding-bottom:'    . (int) ( $settings['utility_bar_brand_label_padding_bottom'] ?? 0 ) . 'px',
		'--sog-rebrand-utility-brand-label-padding-left:'      . (int) ( $settings['utility_bar_brand_label_padding_left'] ?? 0 ) . 'px',
		'--sog-rebrand-utility-menu-text-color:'               . esc_attr( (string) ( $settings['utility_bar_menu_text_color'] ?? 'inherit' ) ),
		'--sog-rebrand-utility-menu-text-hover:'               . esc_attr( (string) ( $settings['utility_bar_menu_text_hover_color'] ?? 'inherit' ) ),
		'--sog-rebrand-utility-menu-text-click:'               . esc_attr( (string) ( $settings['utility_bar_menu_text_click_color'] ?? 'inherit' ) ),
		'--sog-rebrand-utility-menu-hover-bg:'                 . esc_attr( (string) ( $settings['utility_bar_menu_hover_color'] ?? 'transparent' ) ),
		'--sog-rebrand-utility-menu-active-bg:'                . esc_attr( (string) ( $settings['utility_bar_menu_active_color'] ?? 'transparent' ) ),
		'--sog-rebrand-utility-menu-click-bg:'                 . esc_attr( (string) ( $settings['utility_bar_menu_click_color'] ?? 'transparent' ) ),
		'--sog-rebrand-utility-menu-separator-color:'          . esc_attr( (string) ( $settings['utility_bar_menu_separator_color'] ?? 'currentColor' ) ),
		'--sog-rebrand-utility-menu-border-color:'             . esc_attr( (string) ( $settings['utility_bar_menu_border_color'] ?? 'currentColor' ) ),
		'--sog-rebrand-utility-menu-font-family:'              . esc_attr( (string) ( $settings['utility_bar_menu_font_family'] ?? 'inherit' ) ),
		'--sog-rebrand-utility-menu-font-weight:'              . (int) ( $settings['utility_bar_menu_font_weight'] ?? 400 ),
		'--sog-rebrand-utility-menu-font-style:'               . esc_attr( (string) ( $settings['utility_bar_menu_font_style'] ?? 'normal' ) ),
		'--sog-rebrand-utility-menu-font-size:'                . (int) ( $settings['utility_bar_menu_font_size'] ?? 16 ) . 'px',
		'--sog-rebrand-utility-menu-text-transform:'           . esc_attr( (string) ( $settings['utility_bar_menu_text_transform'] ?? 'none' ) ),
		'--sog-rebrand-utility-menu-text-decoration:'          . esc_attr( (string) ( $settings['utility_bar_menu_text_decoration'] ?? 'none' ) ),
		'--sog-rebrand-utility-menu-alignment:'                . esc_attr( (string) ( $settings['utility_bar_menu_alignment'] ?? 'end' ) ),
		'--sog-rebrand-utility-menu-item-padding-top:'         . (int) ( $settings['utility_bar_menu_item_padding_top'] ?? 5 ) . 'px',
		'--sog-rebrand-utility-menu-item-padding-right:'       . (int) ( $settings['utility_bar_menu_item_padding_right'] ?? 20 ) . 'px',
		'--sog-rebrand-utility-menu-item-padding-bottom:'      . (int) ( $settings['utility_bar_menu_item_padding_bottom'] ?? 5 ) . 'px',
		'--sog-rebrand-utility-menu-item-padding-left:'        . (int) ( $settings['utility_bar_menu_item_padding_left'] ?? 20 ) . 'px',
		'--sog-rebrand-utility-menu-item-separator-style:'     . esc_attr( (string) ( $settings['utility_bar_menu_item_separator_style'] ?? 'solid' ) ),
		'--sog-rebrand-utility-menu-item-separator-thickness:' . (int) ( $settings['utility_bar_menu_item_separator_thickness'] ?? 1 ) . 'px',
		'--sog-rebrand-utility-menu-separator-style:'          . esc_attr( (string) ( $settings['utility_bar_menu_separator_style'] ?? 'solid' ) ),
		'--sog-rebrand-utility-menu-separator-thickness:'      . (int) ( $settings['utility_bar_menu_separator_thickness'] ?? 1 ) . 'px',
		'--sog-rebrand-utility-menu-separator-margin-top:'     . (int) ( $settings['utility_bar_menu_separator_margin_top'] ?? 5 ) . 'px',
		'--sog-rebrand-utility-menu-separator-margin-bottom:'  . (int) ( $settings['utility_bar_menu_separator_margin_bottom'] ?? 5 ) . 'px',
		'--sog-rebrand-utility-menu-separator-margin-left:'    . (int) ( $settings['utility_bar_menu_separator_margin_left'] ?? 10 ) . 'px',
		'--sog-rebrand-utility-menu-separator-margin-right:'   . (int) ( $settings['utility_bar_menu_separator_margin_right'] ?? 10 ) . 'px',
		'--sog-rebrand-utility-menu-border-style:'             . esc_attr( (string) ( $settings['utility_bar_menu_border_style'] ?? 'solid' ) ),
		'--sog-rebrand-utility-menu-border-thickness:'         . (int) ( $settings['utility_bar_menu_border_thickness'] ?? 1 ) . 'px',
		'--sog-rebrand-utility-menu-border-margin-top:'        . (int) ( $settings['utility_bar_menu_border_margin_top'] ?? 5 ) . 'px',
		'--sog-rebrand-utility-menu-border-margin-bottom:'     . (int) ( $settings['utility_bar_menu_border_margin_bottom'] ?? 5 ) . 'px',
		'--sog-rebrand-utility-menu-border-margin-left:'       . (int) ( $settings['utility_bar_menu_border_margin_left'] ?? 10 ) . 'px',
		'--sog-rebrand-utility-menu-border-margin-right:'      . (int) ( $settings['utility_bar_menu_border_margin_right'] ?? 10 ) . 'px',
	)
) . ';';

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
<div class="sog-rebrand__utility-bar" data-sog-rebrand-component="utility-bar"
	data-utility-hide-mobile="<?php echo ! empty( $settings['utility_bar_hide_mobile'] ) ? '1' : '0'; ?>"
	data-utility-brand-logo-hide-mobile="<?php echo ! empty( $settings['utility_bar_brand_logo_hide_mobile'] ) ? '1' : '0'; ?>"
	data-utility-hide-label-mobile="<?php echo ! empty( $settings['utility_bar_hide_label_mobile'] ) ? '1' : '0'; ?>"
	data-utility-menu-separator-enabled="<?php echo ! empty( $settings['utility_bar_menu_separator_enabled'] ) ? '1' : '0'; ?>"
	data-utility-menu-separator-hide-mobile="<?php echo ! empty( $settings['utility_bar_menu_separator_hide_mobile'] ) ? '1' : '0'; ?>"
	data-utility-menu-border-enabled="<?php echo ! empty( $settings['utility_bar_menu_border_enabled'] ) ? '1' : '0'; ?>"
	data-utility-menu-border-hide-mobile="<?php echo ! empty( $settings['utility_bar_menu_border_hide_mobile'] ) ? '1' : '0'; ?>"
	data-utility-menu-orientation="<?php echo esc_attr( (string) ( $settings['utility_bar_menu_orientation'] ?? 'horizontal' ) ); ?>"
	style="<?php echo esc_attr( $utility_style ); ?>">
	<div class="sog-rebrand__inner">
		<div class="sog-rebrand__utility-shell">
			<a class="sog-rebrand__utility-brand" href="https://www.unc.edu/">
				<?php if ( ! empty( $settings['utility_bar_brand_logo_url'] ) ) : ?>
					<img class="sog-rebrand__utility-brand-icon" src="<?php echo esc_url( $settings['utility_bar_brand_logo_url'] ); ?>" alt="" style="width:<?php echo (int) ( $settings['utility_bar_brand_logo_width'] ?? 40 ); ?>px;height:<?php echo (int) ( $settings['utility_bar_brand_logo_height'] ?? 35 ); ?>px;" />
				<?php endif; ?>

				<span class="sog-rebrand__utility-brand-lockup sog-rebrand__utility-brand-lockup--desktop"><?php echo esc_html( (string) ( $settings['utility_bar_brand_label'] ?? '' ) ); ?></span>
				<?php if ( ! empty( $settings['utility_bar_brand_label_mobile'] ) ) : ?>
					<span class="sog-rebrand__utility-brand-lockup sog-rebrand__utility-brand-lockup--mobile"><?php echo esc_html( (string) $settings['utility_bar_brand_label_mobile'] ); ?></span>
				<?php endif; ?>
				<span class="sog-rebrand__visually-hidden"><?php echo esc_html__( 'University of North Carolina at Chapel Hill', 'sog-unc-rebrand' ); ?></span>
			</a>

			<?php if ( $utility_menu ) : ?>
				<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Utility bar menu', 'sog-unc-rebrand' ); ?>"
					data-utility-menu-item-separator="<?php echo esc_attr( (string) ( $settings['utility_bar_menu_item_separator'] ?? '|' ) ); ?>">
					<?php echo wp_kses_post( $utility_menu ); ?>
				</nav>
			<?php elseif ( ! empty( $settings['utility_bar_menu_fallback_enabled'] ) ) : ?>
				<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Default UNC utility links', 'sog-unc-rebrand' ); ?>"
					data-utility-menu-item-separator="<?php echo esc_attr( (string) ( $settings['utility_bar_menu_item_separator'] ?? '|' ) ); ?>">
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

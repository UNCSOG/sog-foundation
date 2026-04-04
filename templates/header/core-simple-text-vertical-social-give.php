<?php
/**
 * Simple text header core template - Vertical w/ Social Media and w/ give button.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 * @var string $school_name
 * @var string $site_name
 * @var string $site_description
 * @var string $logo_url
 * @var string $header_main_menu
 * @var string $header_bottom_menu
 * @var string $mobile_nav_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings           = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();
$school_name        = isset( $args['school_name'] ) ? (string) $args['school_name'] : '';
$site_name          = isset( $args['site_name'] ) ? (string) $args['site_name'] : '';
$site_description   = isset( $args['site_description'] ) ? (string) $args['site_description'] : '';
$logo_url           = isset( $args['logo_url'] ) ? (string) $args['logo_url'] : '';
$header_main_menu   = isset( $args['header_main_menu'] ) ? (string) $args['header_main_menu'] : '';
$header_bottom_menu = isset( $args['header_bottom_menu'] ) ? (string) $args['header_bottom_menu'] : '';
$mobile_nav_id      = isset( $args['mobile_nav_id'] ) ? (string) $args['mobile_nav_id'] : 'sog-rebrand-mobile-nav';

$show_mobile_toggle = ! empty( $header_main_menu ) || ! empty( $header_bottom_menu );
$header_give_button_style_parts = array(
	sprintf( '--give-btn-hover-bg:%s;', esc_attr( (string) $settings['header_give_button_hover_color'] ) ),
	sprintf( '--give-btn-color:%s;', esc_attr( (string) $settings['header_give_button_text_color'] ) ),
	sprintf( '--give-btn-bg:%s;', esc_attr( (string) $settings['header_give_button_background_color'] ) ),
	sprintf( '--give-btn-border-color:%s;', esc_attr( (string) $settings['header_give_button_border_color'] ) ),
	sprintf( '--give-btn-border-width:%dpx;', (int) $settings['header_give_button_border_thickness'] ),
	sprintf( '--give-btn-border-radius:%dpx;', (int) $settings['header_give_button_border_radius'] ),
	sprintf( '--give-btn-border-style:%s;', esc_attr( (string) $settings['header_give_button_border_style'] ) ),
	sprintf( '--give-btn-font-family:%s;', esc_attr( (string) $settings['header_give_button_font_family'] ) ),
	sprintf( '--give-btn-text-transform:%s;', esc_attr( (string) $settings['header_give_button_text_transform'] ) ),
	sprintf( '--give-btn-padding-top:%dpx;', (int) $settings['header_give_button_padding_top'] ),
	sprintf( '--give-btn-padding-right:%dpx;', (int) $settings['header_give_button_padding_right'] ),
	sprintf( '--give-btn-padding-bottom:%dpx;', (int) $settings['header_give_button_padding_bottom'] ),
	sprintf( '--give-btn-padding-left:%dpx;', (int) $settings['header_give_button_padding_left'] ),
);

if ( '' !== (string) $settings['header_give_button_font_size'] ) {
	$header_give_button_style_parts[] = sprintf( '--give-btn-font-size:%spx;', esc_attr( (string) $settings['header_give_button_font_size'] ) );
}

if ( '' !== (string) $settings['header_give_button_font_weight'] ) {
	$header_give_button_style_parts[] = sprintf( '--give-btn-font-weight:%s;', esc_attr( (string) $settings['header_give_button_font_weight'] ) );
}

if ( '' !== (string) $settings['header_give_button_font_style'] ) {
	$header_give_button_style_parts[] = sprintf( '--give-btn-font-style:%s;', esc_attr( (string) $settings['header_give_button_font_style'] ) );
}

if ( '' !== (string) $settings['header_give_button_font_line_height'] ) {
	$header_give_button_style_parts[] = sprintf( '--give-btn-line-height:%s;', esc_attr( (string) $settings['header_give_button_font_line_height'] ) );
}

$header_give_button_style = implode( '', $header_give_button_style_parts );
$site_name_style = '';
$school_name_style = '';

if ( ! empty( $settings['header_school_name_text_transform'] ) ) {
	$school_name_style = 'text-transform:' . esc_attr( $settings['header_school_name_text_transform'] ) . ';';
}

if ( ! empty( $settings['header_site_name_text_transform'] ) ) {
	$site_name_style = 'text-transform:' . esc_attr( $settings['header_site_name_text_transform'] ) . ';';
}

?>

<div class="sog-rebrand__header-core sog-rebrand__header-core--simple-text sog-rebrand__header-core--simple-text-vertical-social-no-navigation">
	<div class="sog-rebrand__inner">
		<div class="sog-rebrand__header-shell">
			<div class="sog-rebrand__brand-cluster">
				<?php // var_dump($logo_url, $settings['header_text_links_enabled']);

				if ( !empty($settings['header_text_links_enabled']) ) : ?>
					<?php if( $logo_url  ) : ?>
						<a class="sog-rebrand__brand-link" href="<?php echo esc_url( $logo_url ); ?>">
							<span class="sog-rebrand__brand-title site-name" style="<?php echo esc_attr( $site_name_style ); ?>"><?php echo esc_html( $site_name ); ?></span>
						</a>
					<?php endif; ?>
				<?php else : ?>
					<p class="sog-rebrand__brand-title site-name" style="<?php echo esc_attr( $site_name_style ); ?>"><?php echo esc_html( $site_name ); ?></p>
				<?php endif; ?>
			</div>

			<div class="sog-rebrand__brand-cluster">
				<p class="sog-rebrand__brand-title school-name" style="<?php echo esc_attr( $school_name_style ); ?>color: var(--sog-rebrand-header-school-name-color)"><?php echo esc_html( $school_name ); ?></p>
			</div>

			<?php if ( ! empty( $site_description ) ) : ?>
				<div class="sog-rebrand__brand-cluster">
					<p class="sog-rebrand__brand-title site-tagline site-description"><?php echo esc_html( $site_description ); ?></p>
				</div>
			<?php endif; ?>

			<div class="sog-rebrand__desktop-nav">
				<?php if ( $header_main_menu ) : ?>
					<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Header menu', 'sog-unc-rebrand' ); ?>">
						<?php echo wp_kses_post( $header_main_menu ); ?>
					</nav>
				<?php endif; ?>
			</div>

			<?php if ( $show_mobile_toggle ) : ?>
				<button class="sog-rebrand__menu-toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $mobile_nav_id ); ?>">
					<span class="sog-rebrand__menu-toggle-label"><?php echo esc_html__( 'Menu', 'sog-unc-rebrand' ); ?></span>
					<span class="sog-rebrand__menu-toggle-bars" aria-hidden="true"></span>
				</button>
			<?php endif; ?>

			<?php if ( ! empty( $social_links ) ) : ?>
			   <ul class="sog-rebrand__menu sog-rebrand__menu--social social-header<?php echo !empty($settings['header_social_links_hide_mobile']) ? ' sog-rebrand__hide-mobile' : ''; ?>" data-sog-rebrand-alignment="<?php echo esc_attr( (string) $settings['header_social_links_alignment'] ); ?>" role="list">
					<?php foreach ( $social_links as $social_link ) : ?>
						<?php
						$link_name = isset( $social_link['name'] ) ? (string) $social_link['name'] : ( isset( $social_link['label'] ) ? (string) $social_link['label'] : '' );
						$icon_svg  = isset( $social_link['svg'] ) ? (string) $social_link['svg'] : '';
						$has_icon = '' !== $icon_svg;
						?>

						<li class="menu-item">
							<a
								href="<?php echo esc_url( $social_link['url'] ); ?>"
								class="sog-rebrand__social-link<?php echo $has_icon ? '' : ' sog-rebrand__social-link--text'; ?>"
								aria-label="<?php echo esc_attr( $link_name ); ?>"
							><?php
								if ( $has_icon ) {
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup was sanitized before storage.
									echo $icon_svg;
								} else {
									echo esc_html( $link_name );
								}
							?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $settings['footer_give_button_url'] ) ) : ?>
				<div class="sog-rebrand__footer-give-button-container<?php echo !empty($settings['header_give_button_hide_mobile']) ? ' sog-rebrand__hide-mobile' : ''; ?>" data-sog-rebrand-orientation="<?php echo esc_attr( (string) $settings['footer_give_button_orientation'] ); ?>" data-sog-rebrand-alignment="<?php echo esc_attr( (string) $settings['footer_give_button_alignment'] ); ?>">
					<a href="<?php echo esc_url( (string) $settings['footer_give_button_url'] ); ?>" class="sog-rebrand__footer-give-button" style="<?php echo esc_attr( $footer_give_button_style ); ?>"target="<?php echo !empty($settings['footer_give_button_new_tab']) ? '_blank' : '_self'; ?>" rel="<?php echo !empty($settings['footer_give_button_new_tab']) ? 'noopener noreferrer' : ''; ?>">
						<span class="sog-rebrand__footer-give-button-text"><?php echo esc_html( (string) $settings['footer_give_button_text'] ); ?></span>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

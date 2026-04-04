<?php
/**
 * Simple text header core template - Vertical w/ Social Media and w/o Navigation.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 * @var string $school_name
 * @var string $site_name
 * @var string $site_description
 * @var string $logo_url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings           = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();
$school_name        = isset( $args['school_name'] ) ? (string) $args['school_name'] : '';
$site_name          = isset( $args['site_name'] ) ? (string) $args['site_name'] : '';
$site_description   = isset( $args['site_description'] ) ? (string) $args['site_description'] : '';
$logo_url           = isset( $args['logo_url'] ) ? (string) $args['logo_url'] : '';

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
		</div>
	</div>
</div>

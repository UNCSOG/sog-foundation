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

$school_name_style      = isset( $args['school_name_style'] ) ? (string) $args['school_name_style'] : '';
$site_name_style        = isset( $args['site_name_style'] ) ? (string) $args['site_name_style'] : '';
$site_description_style = isset( $args['site_description_style'] ) ? (string) $args['site_description_style'] : '';
$social_links           = isset( $args['social_links'] ) && is_array( $args['social_links'] ) ? $args['social_links'] : array();
?>
<div class="sog-rebrand__header-core sog-rebrand__header-core--simple-text sog-rebrand__header-core--simple-text-vertical-social-no-navigation">
	<div class="sog-rebrand__inner">
		<div class="sog-rebrand__header-shell">
			<div class="sog-rebrand__brand-cluster">
				<?php if ( !empty($settings['header_text_links_enabled']) ) : ?>
					<?php if( $logo_url  ) : ?>
						<a class="sog-rebrand__brand-link" href="<?php echo esc_url( $logo_url ); ?>">
							<span class="sog-rebrand__brand-title site-name" style="<?php echo esc_attr( $site_name_style ); ?>"><?php echo esc_html( $site_name ); ?></span>
						</a>
					<?php endif; ?>
				<?php else : ?>
					<p class="sog-rebrand__brand-title site-name" style="<?php echo esc_attr( $site_name_style ); ?>"><?php echo esc_html( $site_name ); ?></p>
				<?php endif; ?>
			</div>

			<div class="sog-rebrand__header-separator<?php echo ! empty( $settings['header_separator_hide_mobile'] ) ? ' sog-rebrand__header-separator--hide-mobile' : ''; ?>" style="<?php echo esc_attr( sprintf( 'border-top:%1$spx %2$s %3$s;padding:%4$spx %5$spx %6$spx %7$spx;', (int) $settings['header_separator_thickness'], esc_attr( (string) $settings['header_separator_style'] ), esc_attr( (string) $settings['header_separator_color'] ), (int) ( $settings['header_separator_padding_top'] ?? 0 ), (int) ( $settings['header_separator_padding_right'] ?? 0 ), (int) ( $settings['header_separator_padding_bottom'] ?? 0 ), (int) ( $settings['header_separator_padding_left'] ?? 0 ) ) ); ?>"></div>

			<div class="sog-rebrand__brand-cluster">
				<p class="sog-rebrand__brand-title school-name" style="<?php echo esc_attr( $school_name_style ); ?>color: var(--sog-rebrand-header-school-name-color)"><?php echo esc_html( $school_name ); ?></p>
			</div>

			<?php if ( ! empty( $site_description ) ) : ?>
				<div class="sog-rebrand__brand-cluster">
					<p class="sog-rebrand__brand-title site-tagline site-description" style="<?php echo esc_attr( $site_description_style ); ?>"><?php echo esc_html( $site_description ); ?></p>
				</div>
			<?php endif; ?>

			<?php load_template( SOG_UNC_REBRAND_PATH . 'templates/header/social-media.php', null, array( 'settings' => $settings, 'social_links' => $social_links ) ); ?>
		</div>
	</div>
</div>

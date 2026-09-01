<?php
/**
 * Social media
 * footer core template.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 * @var array<int, array<string, string>> $social_links
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings       = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();
$social_links   = isset( $args['social_links'] ) && is_array( $args['social_links'] ) ? $args['social_links'] : array();
$orientation    = isset( $settings['footer_social_links_orientation'] ) ? (string) $settings['footer_social_links_orientation'] : '';
$alignment    = isset( $settings['footer_social_links_alignment'] ) ? (string) $settings['footer_social_links_alignment'] : '';

if ( ! empty( $social_links ) ) : ?>
    <ul class="sog-rebrand__menu sog-rebrand__menu--social social-footer<?php echo ! empty( $settings['footer_bottom_hide_mobile'] ) ? ' sog-rebrand__footer-column--hide-mobile' : ''; ?>"<?php if ( '' !== $orientation ) : ?> data-sog-rebrand-orientation="<?php echo esc_attr( $orientation ); ?>"<?php endif; ?> <?php if ( '' !== $alignment ) : ?> data-sog-rebrand-alignment="<?php echo esc_attr( $alignment ); ?>"<?php endif; ?> role="list">
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

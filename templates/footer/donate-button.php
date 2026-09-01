<?php
/**
 * Donate button footer core template.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();

if ( empty( $settings['footer_give_button_enabled'] ) ) {
	return;
}

$footer_give_button_style_parts = array(
	sprintf( '--give-btn-hover-bg:%s;', esc_attr( (string) $settings['footer_give_button_hover_color'] ) ),
	sprintf( '--give-btn-color:%s;', esc_attr( (string) $settings['footer_give_button_text_color'] ) ),
	sprintf( '--give-btn-hover-color:%s;', esc_attr( (string) $settings['footer_give_button_text_hover_color'] ) ),
	sprintf( '--give-btn-bg:%s;', esc_attr( (string) $settings['footer_give_button_background_color'] ) ),
	sprintf( '--give-btn-border-color:%s;', esc_attr( (string) $settings['footer_give_button_border_color'] ) ),
	sprintf( '--give-btn-border-width:%dpx;', (int) $settings['footer_give_button_border_thickness'] ),
	sprintf( '--give-btn-border-radius:%dpx;', (int) $settings['footer_give_button_border_radius'] ),
	sprintf( '--give-btn-border-style:%s;', esc_attr( (string) $settings['footer_give_button_border_style'] ) ),
	sprintf( '--give-btn-font-family:%s;', esc_attr( (string) $settings['footer_give_button_font_family'] ) ),
	sprintf( '--give-btn-text-transform:%s;', esc_attr( (string) $settings['footer_give_button_text_transform'] ) ),
	sprintf( '--give-btn-text-decoration:%s;', esc_attr( (string) $settings['footer_give_button_text_decoration'] ) ),
	sprintf( '--give-btn-padding-top:%dpx;', (int) $settings['footer_give_button_padding_top'] ),
	sprintf( '--give-btn-padding-right:%dpx;', (int) $settings['footer_give_button_padding_right'] ),
	sprintf( '--give-btn-padding-bottom:%dpx;', (int) $settings['footer_give_button_padding_bottom'] ),
	sprintf( '--give-btn-padding-left:%dpx;', (int) $settings['footer_give_button_padding_left'] ),
);

if ( '' !== (string) $settings['footer_give_button_font_size'] ) {
	$footer_give_button_style_parts[] = sprintf( '--give-btn-font-size:%spx;', esc_attr( (string) $settings['footer_give_button_font_size'] ) );
}

if ( '' !== (string) $settings['footer_give_button_font_weight'] ) {
	$footer_give_button_style_parts[] = sprintf( '--give-btn-font-weight:%s;', esc_attr( (string) $settings['footer_give_button_font_weight'] ) );
}

if ( '' !== (string) $settings['footer_give_button_font_style'] ) {
	$footer_give_button_style_parts[] = sprintf( '--give-btn-font-style:%s;', esc_attr( (string) $settings['footer_give_button_font_style'] ) );
}

if ( '' !== (string) $settings['footer_give_button_font_line_height'] ) {
	$footer_give_button_style_parts[] = sprintf( '--give-btn-line-height:%spx;', esc_attr( (string) $settings['footer_give_button_font_line_height'] ) );
}

$footer_give_button_style = implode( '', $footer_give_button_style_parts );

if ( ! empty( $settings['footer_give_button_url'] ) ) : ?>
	<div class="sog-rebrand__footer-give-button-container<?php echo ! empty( $settings['footer_give_button_hide_mobile'] ) ? ' sog-rebrand__footer-column--hide-mobile' : ''; ?>" data-sog-rebrand-orientation="<?php echo esc_attr( (string) $settings['footer_give_button_orientation'] ); ?>" data-sog-rebrand-alignment="<?php echo esc_attr( (string) $settings['footer_give_button_alignment'] ); ?>" data-sog-rebrand-below-columns="<?php echo ! empty( $settings['footer_give_button_below_columns'] ) ? '1' : '0'; ?>" data-sog-rebrand-give-social-gap="<?php echo esc_attr( (string) (int) $settings['footer_give_social_gap'] ); ?>">
		<a href="<?php echo esc_url( (string) $settings['footer_give_button_url'] ); ?>" class="sog-rebrand__footer-give-button" style="<?php echo esc_attr( $footer_give_button_style ); ?>" target="<?php echo ! empty( $settings['footer_give_button_new_tab'] ) ? '_blank' : '_self'; ?>" rel="<?php echo ! empty( $settings['footer_give_button_new_tab'] ) ? 'noopener noreferrer' : ''; ?>">
			<span class="sog-rebrand__footer-give-button-text"><?php echo esc_html( (string) $settings['footer_give_button_text'] ); ?></span>
		</a>
	</div>
<?php endif; ?>

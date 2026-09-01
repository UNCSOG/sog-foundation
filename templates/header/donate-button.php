<?php
/**
 * Donate button header core template.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();

$header_give_button_style_parts = array(
	sprintf( '--give-btn-hover-bg:%s;', esc_attr( (string) ( $settings['header_give_button_hover_color'] ?? 'initial' ) ) ),
	sprintf( '--give-btn-color:%s;', esc_attr( (string) ( $settings['header_give_button_text_color'] ?? '#ffffff' ) ) ),
	sprintf( '--give-btn-hover-color:%s;', esc_attr( (string) ( $settings['header_give_button_text_hover_color'] ?? '#ffffff' ) ) ),
	sprintf( '--give-btn-bg:%s;', esc_attr( (string) ( $settings['header_give_button_background_color'] ?? '#1E3A57' ) ) ),
	sprintf( '--give-btn-border-color:%s;', esc_attr( (string) ( $settings['header_give_button_border_color'] ?? '#1E3A57' ) ) ),
	sprintf( '--give-btn-border-width:%dpx;', (int) ( $settings['header_give_button_border_thickness'] ?? 1 ) ),
	sprintf( '--give-btn-border-radius:%dpx;', (int) ( $settings['header_give_button_border_radius'] ?? 0 ) ),
	sprintf( '--give-btn-border-style:%s;', esc_attr( (string) ( $settings['header_give_button_border_style'] ?? 'solid' ) ) ),
	sprintf( '--give-btn-font-family:%s;', esc_attr( (string) ( $settings['header_give_button_font_family'] ?? 'Poppins, sans-serif' ) ) ),
	sprintf( '--give-btn-text-transform:%s;', esc_attr( (string) ( $settings['header_give_button_text_transform'] ?? 'capitalize' ) ) ),
	sprintf( '--give-btn-text-decoration:%s;', esc_attr( (string) ( $settings['header_give_button_text_decoration'] ?? 'none' ) ) ),
	sprintf( '--give-btn-padding-top:%dpx;', (int) ( $settings['header_give_button_padding_top'] ?? 14 ) ),
	sprintf( '--give-btn-padding-right:%dpx;', (int) ( $settings['header_give_button_padding_right'] ?? 32 ) ),
	sprintf( '--give-btn-padding-bottom:%dpx;', (int) ( $settings['header_give_button_padding_bottom'] ?? 14 ) ),
	sprintf( '--give-btn-padding-left:%dpx;', (int) ( $settings['header_give_button_padding_left'] ?? 32 ) ),
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

if ( ! empty( $settings['header_give_button_enabled'] ) && ! empty( $settings['header_give_button_url'] ) ) : ?>
	<div class="sog-rebrand__header-give-button-container<?php echo ! empty( $settings['header_give_button_hide_mobile'] ) ? ' sog-rebrand__hide-mobile' : ''; ?>">
		<a href="<?php echo esc_url( (string) $settings['header_give_button_url'] ); ?>" class="sog-rebrand__header-give-button" style="<?php echo esc_attr( $header_give_button_style ); ?>" target="<?php echo ! empty( $settings['header_give_button_new_tab'] ) ? '_blank' : '_self'; ?>" rel="<?php echo ! empty( $settings['header_give_button_new_tab'] ) ? 'noopener noreferrer' : ''; ?>">
			<span class="sog-rebrand__header-give-button-text"><?php echo esc_html( (string) $settings['header_give_button_text'] ); ?></span>
		</a>
	</div>
<?php endif; ?>

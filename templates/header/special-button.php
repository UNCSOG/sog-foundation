<?php
/**
 * Special button template for header.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();

$special_btn_classes = 'sog-rebrand__header-special-button';

if ( ! empty( $settings['header_special_button_hide_mobile'] ) ) {
    $special_btn_classes .= ' sog-rebrand__hide-mobile';
}

$special_padding_top    = isset( $settings['header_special_button_padding_top'] ) ? (int) $settings['header_special_button_padding_top'] : (int) ( $settings['header_special_padding_top'] ?? 14 );
$special_padding_right  = isset( $settings['header_special_button_padding_right'] ) ? (int) $settings['header_special_button_padding_right'] : (int) ( $settings['header_special_padding_right'] ?? 32 );
$special_padding_bottom = isset( $settings['header_special_button_padding_bottom'] ) ? (int) $settings['header_special_button_padding_bottom'] : (int) ( $settings['header_special_padding_bottom'] ?? 14 );
$special_padding_left   = isset( $settings['header_special_button_padding_left'] ) ? (int) $settings['header_special_button_padding_left'] : (int) ( $settings['header_special_padding_left'] ?? 32 );

$special_btn_style_parts = array(
    sprintf( '--special-btn-hover-bg:%s;', esc_attr( (string) ( $settings['header_special_button_hover_color'] ?? 'initial' ) ) ),
    sprintf( '--special-btn-color:%s;', esc_attr( (string) ( $settings['header_special_button_text_color'] ?? '#ffffff' ) ) ),
    sprintf( '--special-btn-hover-color:%s;', esc_attr( (string) ( $settings['header_special_button_text_hover_color'] ?? '#ffffff' ) ) ),
    sprintf( '--special-btn-bg:%s;', esc_attr( (string) ( $settings['header_special_button_background_color'] ?? '#1E3A57' ) ) ),
    sprintf( '--special-btn-border-color:%s;', esc_attr( (string) ( $settings['header_special_button_border_color'] ?? '#1E3A57' ) ) ),
    sprintf( '--special-btn-border-width:%dpx;', (int) ( $settings['header_special_button_border_thickness'] ?? 1 ) ),
    sprintf( '--special-btn-border-radius:%dpx;', (int) ( $settings['header_special_button_border_radius'] ?? 0 ) ),
    sprintf( '--special-btn-border-style:%s;', esc_attr( (string) ( $settings['header_special_button_border_style'] ?? 'solid' ) ) ),
    sprintf( '--special-btn-font-family:%s;', esc_attr( (string) ( $settings['header_special_font_family'] ?? 'Poppins, sans-serif' ) ) ),
    sprintf( '--special-btn-font-size:%spx;', esc_attr( (string) ( $settings['header_special_font_size'] ?? '16' ) ) ),
    sprintf( '--special-btn-font-weight:%s;', esc_attr( (string) ( $settings['header_special_font_weight'] ?? '600' ) ) ),
    sprintf( '--special-btn-font-style:%s;', esc_attr( (string) ( $settings['header_special_font_style'] ?? 'normal' ) ) ),
    sprintf( '--special-btn-text-transform:%s;', esc_attr( (string) ( $settings['header_special_text_transform'] ?? 'capitalize' ) ) ),
    sprintf( '--special-btn-padding-top:%dpx;', $special_padding_top ),
    sprintf( '--special-btn-padding-right:%dpx;', $special_padding_right ),
    sprintf( '--special-btn-padding-bottom:%dpx;', $special_padding_bottom ),
    sprintf( '--special-btn-padding-left:%dpx;', $special_padding_left ),
);

$special_btn_style = implode( '', $special_btn_style_parts );

// Special individual button
if (
    ! empty( $settings['header_special_button_text'] ) &&
    ! empty( $settings['header_special_button_url'] )
) :
?>
    <a href="<?php echo esc_url( $settings['header_special_button_url'] ); ?>" class="<?php echo esc_attr( $special_btn_classes ); ?>" style="<?php echo esc_attr( $special_btn_style ); ?>" target="<?php echo ! empty( $settings['header_special_button_new_tab'] ) ? '_blank' : '_self'; ?>" rel="<?php echo ! empty( $settings['header_special_button_new_tab'] ) ? 'noopener noreferrer' : ''; ?>">
        <span><?php echo esc_html( $settings['header_special_button_text'] ); ?></span>
    </a>
<?php endif; ?>
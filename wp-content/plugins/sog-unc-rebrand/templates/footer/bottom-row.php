<?php
/**
 * Footer bottom row template.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 * @var string $footer_bottom_menu
 * @var array<int, array<string, mixed>> $footer_columns
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings           = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();
$footer_bottom_menu = isset( $args['footer_bottom_menu'] ) ? (string) $args['footer_bottom_menu'] : '';
$footer_columns     = isset( $args['footer_columns'] ) && is_array( $args['footer_columns'] ) ? $args['footer_columns'] : array();
$first_column       = isset( $footer_columns[1] ) && is_array( $footer_columns[1] ) ? $footer_columns[1] : array();
$menu_modes         = isset( $args['menu_modes'] ) && is_array( $args['menu_modes'] ) ? $args['menu_modes'] : array( 'menu', 'menu_shortcode', 'menu_wysiwyg', 'menu_social', 'menu_give', 'menu_social_give', 'menu_social_shortcode', 'menu_social_wysiwyg', 'menu_give_shortcode', 'menu_give_wysiwyg', 'menu_social_give_shortcode', 'menu_social_give_wysiwyg' );
$wysiwyg_modes      = array( 'wysiwyg', 'menu_wysiwyg', 'social_wysiwyg', 'give_wysiwyg', 'menu_social_wysiwyg', 'social_give_wysiwyg', 'menu_give_wysiwyg', 'menu_social_give_wysiwyg' );
$shortcode_modes    = array( 'shortcode', 'menu_shortcode', 'social_shortcode', 'give_shortcode', 'menu_social_shortcode', 'social_give_shortcode', 'menu_give_shortcode', 'menu_social_give_shortcode' );

if ( empty( $settings['footer_bottom_enabled'] ) ) {
	return;
}

$separator_2_thickness    = (int) $settings['footer_separator_2_thickness'];
$separator_2_style        = (string) $settings['footer_separator_2_style'];
$separator_2_style_mobile = (string) $settings['footer_separator_2_style_mobile'];
$separator_2_color        = (string) $settings['footer_separator_2_color'];
$separator_2_hide_mobile  = ! empty( $settings['footer_separator_2_hide_mobile'] );
$separator_2_margin_top   = (int) ( $settings['footer_separator_2_margin_top'] ?? 24 );
$separator_2_margin_bottom = (int) ( $settings['footer_separator_2_margin_bottom'] ?? 24 );

$first_column_enabled                 = ! empty( $settings['footer_column_1_copyright_enabled'] ) || ! empty( $first_column['footer_column_1_copyright_enabled'] );
$first_column_hidden                  = ! $first_column_enabled || ! empty( $settings['footer_column_1_hide_mobile'] );
$first_column_mode                    = isset( $first_column['mode'] ) ? (string) $first_column['mode'] : (string) ( $settings['footer_column_1_mode'] ?? '' );
$first_column_content                 = isset( $first_column['content'] ) ? (string) $first_column['content'] : (string) ( $settings['footer_column_1_content'] ?? '' );
$first_column_heading                 = isset( $first_column['heading'] ) ? (string) $first_column['heading'] : (string) ( $settings['footer_column_1_heading'] ?? '' );
$first_column_heading_text_transform  = (string) ( $settings['footer_column_1_heading_text_transform'] ?? 'none' );
$first_column_heading_text_decoration = (string) ( $settings['footer_column_1_heading_text_decoration'] ?? 'none' );
$first_column_menu                    = isset( $first_column['menu'] ) ? $first_column['menu'] : '';
// $give_social_gap      = (int) ( $settings['footer_give_social_gap'] ?? 16 );

$copyright_text_style  = sprintf(
	'font-family:%1$s;font-weight:%2$s;font-style:%3$s;font-size:%4$spx;line-height:%5$s;text-transform:%6$s;text-decoration:%7$s;padding:%8$spx %9$spx %10$spx %11$spx;',
	esc_attr( (string) ( $settings['footer_bottom_copyright_text_font_family'] ?? 'Montserrat, sans-serif' ) ),
	(int) ( $settings['footer_bottom_copyright_text_font_weight'] ?? 400 ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_text_font_style'] ?? 'normal' ) ),
	(int) ( $settings['footer_bottom_copyright_text_font_size'] ?? 16 ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_text_line_height'] ?? '1.87806rem' ) ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_text_transform'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_text_decoration'] ?? 'none' ) ),
	(int) ( $settings['footer_bottom_copyright_text_padding_top'] ?? 0 ),
	(int) ( $settings['footer_bottom_copyright_text_padding_right'] ?? 16 ),
	(int) ( $settings['footer_bottom_copyright_text_padding_bottom'] ?? 8 ),
	(int) ( $settings['footer_bottom_copyright_text_padding_left'] ?? 16 )
);

$copyright_links_style = sprintf(
	'--footer-bottom-links-font-family:%1$s;--footer-bottom-links-font-weight:%2$s;--footer-bottom-links-font-style:%3$s;--footer-bottom-links-font-size:%4$spx;--footer-bottom-links-line-height:%5$s;--footer-bottom-links-transform:%6$s;--footer-bottom-links-decoration:%7$s;--footer-bottom-links-padding-top:%8$spx;--footer-bottom-links-padding-right:%9$spx;--footer-bottom-links-padding-bottom:%10$spx;--footer-bottom-links-padding-left:%11$spx;',
	esc_attr( (string) ( $settings['footer_bottom_copyright_links_font_family'] ?? 'Montserrat, sans-serif' ) ),
	(int) ( $settings['footer_bottom_copyright_links_font_weight'] ?? 400 ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_links_font_style'] ?? 'normal' ) ),
	(int) ( $settings['footer_bottom_copyright_links_font_size'] ?? 16 ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_links_line_height'] ?? '2.5rem' ) ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_links_transform'] ?? 'none' ) ),
	esc_attr( (string) ( $settings['footer_bottom_copyright_links_decoration'] ?? 'underline' ) ),
	(int) ( $settings['footer_bottom_copyright_links_padding_top'] ?? 0 ),
	(int) ( $settings['footer_bottom_copyright_links_padding_right'] ?? 16 ),
	(int) ( $settings['footer_bottom_copyright_links_padding_bottom'] ?? 8 ),
	(int) ( $settings['footer_bottom_copyright_links_padding_left'] ?? 16 )
);
?>

<?php if ( !$separator_2_hide_mobile && '' !== $separator_2_style && '' !== $separator_2_color && '' !== $separator_2_thickness  ) : ?>
	<div id="footer-separator-desktop" data-separator-style-desktop="<?php echo esc_attr( $separator_2_style ); ?>" class="sog-rebrand__footer-separator sog-rebrand__footer-separator--desktop sog-rebrand__footer-separator--medium-mobile" style="<?php echo esc_attr( sprintf( 'border-top:%1$spx %2$s %3$s;margin-top:%4$spx;margin-bottom:%5$spx;', $separator_2_thickness, $separator_2_style, $separator_2_color, $separator_2_margin_top, $separator_2_margin_bottom ) ); ?>"></div>
<?php endif; ?>

<?php if ( !$separator_2_hide_mobile && '' !== $separator_2_style_mobile ) : ?>
	<div id="footer-separator-mobile" data-separator-style-small-mobile="<?php echo esc_attr( $separator_2_style_mobile ); ?>" class="sog-rebrand__footer-separator sog-rebrand__footer-separator--small-mobile" style="<?php echo esc_attr( sprintf( 'border-top:%1$spx %2$s %3$s;margin-top:%4$spx;margin-bottom:%5$spx;', $separator_2_thickness, $separator_2_style_mobile, $separator_2_color, $separator_2_margin_top, $separator_2_margin_bottom ) ); ?>"></div>
<?php endif; ?>

<script>
// jQuery required for this snippet
jQuery(function($) {
	function toggleFooterSeparators() {
		if (window.matchMedia('(max-width: 782.98px)').matches) {
			$('#footer-separator-desktop').hide();
			$('#footer-separator-mobile').show();
		} else {
			$('#footer-separator-desktop').show();
			$('#footer-separator-mobile').hide();
		}
	}
	toggleFooterSeparators();
	$(window).on('resize', toggleFooterSeparators);
});
</script>

<section class="sog-rebrand__footer-row sog-rebrand__footer-row--bottom<?php echo ! empty( $settings['footer_bottom_hide_mobile'] ) ? ' sog-rebrand__footer-row--bottom-hide-mobile' : ''; ?>">
	<div class="sog-rebrand__footer-bottom-bar">
		<?php if ( ! empty( $settings['footer_bottom_show_copyright'] ) ) : ?>
			<p class="sog-rebrand__copyright" style="<?php echo esc_attr( $copyright_text_style ); ?>">
				<?php echo esc_html( str_replace( '{year}', wp_date( 'Y' ), (string) $settings['footer_bottom_copyright_text'] ) ); ?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $settings['footer_bottom_show_menu'] ) && $footer_bottom_menu ) : ?>
			<nav class="sog-rebrand__nav" style="<?php echo esc_attr( $copyright_links_style ); ?>" aria-label="<?php echo esc_attr__( 'Footer bottom navigation', 'sog-unc-rebrand' ); ?>">
				<?php echo wp_kses_post( $footer_bottom_menu ); ?>
			</nav>
		<?php endif; ?>

		<?php if ( ! $first_column_hidden && empty( $settings['footer_bottom_hide_mobile'] ) && ( ( in_array( $first_column_mode, $wysiwyg_modes, true ) && '' !== $first_column_content ) || ( in_array( $first_column_mode, $shortcode_modes, true ) && '' !== $first_column_content ) || in_array( $first_column_mode, $menu_modes, true ) ) ) : ?>
			<section class="sog-rebrand__footer-column <?php echo empty($settings['footer_column_1_copyright_enabled']) ? 'sog-rebrand__footer-column--show-small-mobile-only' : ' sog-rebrand__footer-column--hide-medium-mobile'; ?>">
				<?php if ( in_array( $first_column_mode, $wysiwyg_modes, true ) && '' !== $first_column_content ) : ?>
					<div class="sog-rebrand__footer-richtext"><?php echo wp_kses_post( $first_column_content ); ?></div>
				<?php endif; ?>

				<?php if ( in_array( $first_column_mode, $shortcode_modes, true ) && '' !== $first_column_content ) : ?>
					<div class="sog-rebrand__footer-richtext">
						<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output is trusted admin input.
						echo do_shortcode( $first_column_content ); ?>
					</div>
				<?php endif; ?>

				<?php if ( in_array( $first_column_mode, $menu_modes, true ) ) : ?>
					<?php if ( '' !== $first_column_heading ) : ?>
						<h3 class="sog-rebrand__footer-column-heading" style="text-transform:<?php echo esc_attr( $first_column_heading_text_transform ); ?>;text-decoration:<?php echo esc_attr( $first_column_heading_text_decoration ); ?>;"><?php echo esc_html( $first_column_heading ); ?></h3>
					<?php endif; ?>

					<?php if ( $first_column_menu ) : ?>
						<?php echo wp_kses_post( $first_column_menu ); ?>
					<?php else : ?>
						<p class="sog-rebrand__placeholder"><?php echo esc_html( sprintf( /* translators: %d: column number */ __( 'Assign a menu to footer column %d.', 'sog-unc-rebrand' ), 1 ) ); ?></p>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( in_array( $first_column_mode, $wysiwyg_modes, true ) && '' === $first_column_content ) : ?>
					<p class="sog-rebrand__placeholder"><?php echo esc_html( sprintf( /* translators: %d: column number */ __( 'Add WYSIWYG content for footer column %d in plugin settings.', 'sog-unc-rebrand' ), 1 ) ); ?></p>
				<?php endif; ?>

				<?php if ( in_array( $first_column_mode, $shortcode_modes, true ) && '' === $first_column_content ) : ?>
					<p class="sog-rebrand__placeholder"><?php echo esc_html( sprintf( /* translators: %d: column number */ __( 'Add a shortcode for footer column %d in plugin settings.', 'sog-unc-rebrand' ), 1 ) ); ?></p>
				<?php endif; ?>
			</section>
		<?php endif; ?>
	</div>
</section>

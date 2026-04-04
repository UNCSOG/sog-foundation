<?php
/**
 * Footer logos row template.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();

$separator_1_thickness    = (int) $settings['footer_separator_1_thickness'];
$separator_1_style        = (string) $settings['footer_separator_1_style'];
$separator_1_style_mobile = (string) ( $settings['footer_separator_1_style_mobile'] ?? $settings['footer_separator_1_style'] );
$separator_1_color        = (string) $settings['footer_separator_1_color'];
$separator_1_hide_mobile  = ! empty( $settings['footer_separator_1_hide_mobile'] );

if ( empty( $settings['footer_logos_enabled'] ) ) {
	return;
}
?>

<section class="sog-rebrand__footer-row sog-rebrand__footer-row--logos">
	<div class="sog-rebrand__footer-logos" data-sog-rebrand-orientation="<?php echo esc_attr( (string) $settings['footer_logos_orientation'] ); ?>" data-sog-rebrand-alignment="<?php echo esc_attr( (string) $settings['footer_logos_alignment'] ); ?>" style="<?php echo esc_attr( sprintf( '--sog-rebrand-logo-gap:%1$spx;--sog-rebrand-logo-max-height:%2$spx;--sog-rebrand-logo-max-total-height:%3$spx;', (int) $settings['footer_logos_spacing'], (int) $settings['footer_logos_max_height'], (int) $settings['footer_logos_max_total_height'] ) ); ?>">
		<?php
		$wrap_first_two = false;
		$logo_count = 0;

		for ( $i = 1; $i <= 3; $i++ ) {
			if ( 'none' !== $settings[ 'footer_logo_' . $i . '_type' ] ) {
				$logo_count++;
			}
		}

		$wrap_first_two = $logo_count > 2;
		$printed = 0;

		if ( $wrap_first_two ) { ?>
			<div class="logo-site-name-container sog-rebrand__footer-logo-item" style="<?php echo esc_attr( sprintf( '--sog-rebrand-logo-gap-2:%1$spx;', (int) $settings['footer_logos_spacing'] ) ); ?>">
		<?php }

		for ( $logo_index = 1; $logo_index <= 3; $logo_index++ ) :
			if ( 'none' === $settings[ 'footer_logo_' . $logo_index . '_type' ] ) {
				continue;
			}

			$printed++;

			if ( $wrap_first_two && $printed == 3 ) {
				echo '</div>';
			}
		   ?>

			<div class="sog-rebrand__footer-logo-item<?php echo ! empty( $settings[ 'footer_logo_' . $logo_index . '_hide_mobile' ] ) ? ' sog-rebrand__footer-logo-item--hide-mobile' : ''; ?>">
				<?php
				$logo_link_open  = '';
				$logo_link_close = '';

				if ( ! empty( $settings[ 'footer_logo_' . $logo_index . '_link_url' ] ) ) {
					$target          = ! empty( $settings[ 'footer_logo_' . $logo_index . '_link_new_tab' ] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
					$logo_link_open  = '<a class="sog-rebrand__footer-logo-link" href="' . esc_url( (string) $settings[ 'footer_logo_' . $logo_index . '_link_url' ] ) . '"' . $target . '>';
					$logo_link_close = '</a>';
				}

				echo wp_kses_post( $logo_link_open );
				?>

				<?php if ( 'image' === $settings[ 'footer_logo_' . $logo_index . '_type' ] && ! empty( $settings[ 'footer_logo_' . $logo_index . '_image_url' ] ) ) : ?>
					<img class="sog-rebrand__footer-logo-image" src="<?php echo esc_url( (string) $settings[ 'footer_logo_' . $logo_index . '_image_url' ] ); ?>" alt="" />
				<?php else : ?>
					<div class="sog-rebrand__footer-text-logo">
						<?php if ( ! empty( $settings[ 'footer_logo_' . $logo_index . '_text_upper' ] ) ) : ?>
							<span class="sog-rebrand__footer-text-logo-upper"><?php echo esc_html( (string) $settings[ 'footer_logo_' . $logo_index . '_text_upper' ] ); ?></span>
						<?php endif; ?>

						<?php if ( ! empty( $settings[ 'footer_logo_' . $logo_index . '_text_lower' ] ) ) : ?>
							<span class="sog-rebrand__footer-text-logo-lower"><?php echo esc_html( (string) $settings[ 'footer_logo_' . $logo_index . '_text_lower' ] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php echo wp_kses_post( $logo_link_close ); ?>
			</div>
		<?php endfor; ?>
	</div>
</section>

<?php if ( '' !== $separator_1_style && '' !== $separator_1_color && '' !== $separator_1_thickness ) : ?>
	<div id="footer-separator1-desktop" class="sog-rebrand__footer-separator sog-rebrand__footer-separator--desktop-top sog-rebrand__footer-separator--medium-mobile-top" style="<?php echo esc_attr( sprintf( 'border-top:%1$spx %2$s %3$s;', (int) $settings['footer_separator_1_thickness'], esc_attr( (string) $settings['footer_separator_1_style'] ), esc_attr( (string) $settings['footer_separator_1_color'] ) ) ); ?>"></div>
<?php endif; ?>

<div id="footer-separator1-mobile" class="sog-rebrand__footer-separator sog-rebrand__footer-separator--small-mobile-top sog-rebrand__footer-separator--hide-mobile sog-rebrand__footer-separator--small-mobile-transparent" style="border-top: 1px solid transparent !important;"></div>

<script>
jQuery(function($) {
  function toggleFooterSeparator1() {
    if (window.matchMedia('(max-width: 768.98px)').matches) {
      $('#footer-separator1-desktop').hide();
      $('#footer-separator1-mobile').show();
    } else {
      $('#footer-separator1-desktop').show();
      $('#footer-separator1-mobile').hide();
    }
  }
  toggleFooterSeparator1();
  $(window).on('resize', toggleFooterSeparator1);
});
</script>
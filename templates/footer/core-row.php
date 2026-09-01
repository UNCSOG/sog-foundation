<?php
/**
 * Footer core row template.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 * @var array<int, array<string, string>> $social_links
 * @var array<int, array<string, mixed>> $footer_columns
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings       = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();
$social_links   = isset( $args['social_links'] ) && is_array( $args['social_links'] ) ? $args['social_links'] : array();
$footer_columns = isset( $args['footer_columns'] ) && is_array( $args['footer_columns'] ) ? $args['footer_columns'] : array();

if ( empty( $settings['footer_logos_enabled'] ) ) :
	?>
	<div class="sog-rebrand__footer-separator<?php echo ! empty( $settings['footer_separator_1_hide_mobile'] ) ? ' sog-rebrand__footer-separator--hide-mobile' : ''; ?>" style="<?php echo esc_attr( sprintf( 'border-top:%1$spx %2$s %3$s;margin-top:%4$spx;margin-bottom:%5$spx;', (int) $settings['footer_separator_1_thickness'], esc_attr( (string) $settings['footer_separator_1_style'] ), esc_attr( (string) $settings['footer_separator_1_color'] ), (int) ( $settings['footer_separator_1_margin_top'] ?? 24 ), (int) ( $settings['footer_separator_1_margin_bottom'] ?? 24 ) ) ); ?>"></div>
	<?php
endif;

$first_column   = isset( $footer_columns[1] ) ? $footer_columns[1] : null;
$remaining_cols = array();
$footer_grid_style = '';
$footer_columns_group_style = '';

foreach ( $footer_columns as $col_id => $col ) {
	if ( $col_id > 1 ) {
		$remaining_cols[ $col_id ] = $col;
	}
}

if ( null !== $first_column && ! empty( $first_column['width'] ) ) {
	$footer_grid_style = sprintf( '--sog-rebrand-footer-column-width:%d%%;', (int) $first_column['width'] );
}

$column_2_width = isset( $footer_columns[2]['width'] ) ? (int) $footer_columns[2]['width'] : 0;
$column_3_width = isset( $footer_columns[3]['width'] ) ? (int) $footer_columns[3]['width'] : 0;

if ( 'unused' === ( $footer_columns[2]['mode'] ?? '' ) ) {
	$column_2_width = 0;
}

if ( 'unused' === ( $footer_columns[3]['mode'] ?? '' ) ) {
	$column_3_width = 0;
}

if ( $column_2_width > 0 || $column_3_width > 0 ) {
	$footer_columns_group_style = sprintf(
		'--sog-rebrand-footer-column-2-width:%1$d%%;--sog-rebrand-footer-column-3-width:%2$d%%;',
		$column_2_width > 0 ? $column_2_width : 50,
		$column_3_width > 0 ? $column_3_width : 50
	);
}

$show_right_side                  = ! empty( $remaining_cols ) || ! empty( $social_links );
$remaining_cols_all_unused        = ! empty( $remaining_cols ) && count( array_filter( $remaining_cols, fn( $col ) => ( $col['mode'] ?? '' ) !== 'unused' ) ) === 0;
$menu_modes                       = array( 'menu', 'menus', 'menu_shortcode', 'menu_wysiwyg', 'menu_social', 'menu_give', 'menu_social_give', 'menu_social_shortcode', 'menu_social_wysiwyg', 'menu_give_shortcode', 'menu_give_wysiwyg', 'menu_social_give_shortcode', 'menu_social_give_wysiwyg' );
$social_modes                     = array( 'social', 'social_shortcode', 'social_wysiwyg', 'social_give', 'menu_social', 'menu_social_give', 'menu_social_shortcode', 'menu_social_wysiwyg', 'social_give_shortcode', 'social_give_wysiwyg', 'menu_social_give_shortcode', 'menu_social_give_wysiwyg' );
$give_modes                       = array( 'give', 'give_shortcode', 'give_wysiwyg', 'social_give', 'menu_give', 'menu_social_give', 'social_give_shortcode', 'social_give_wysiwyg', 'menu_give_shortcode', 'menu_give_wysiwyg', 'menu_social_give_shortcode', 'menu_social_give_wysiwyg' );
$wysiwyg_modes                    = array( 'wysiwyg', 'menu_wysiwyg', 'social_wysiwyg', 'give_wysiwyg', 'menu_social_wysiwyg', 'social_give_wysiwyg', 'menu_give_wysiwyg', 'menu_social_give_wysiwyg' );
$shortcode_modes                  = array( 'shortcode', 'menu_shortcode', 'social_shortcode', 'give_shortcode', 'menu_social_shortcode', 'social_give_shortcode', 'menu_give_shortcode', 'menu_social_give_shortcode' );
$column_1_heading_text_transform  = (string) ( $settings['footer_column_1_heading_text_transform'] ?? 'none' );
$column_1_heading_text_decoration  = (string) ( $settings['footer_column_1_heading_text_decoration'] ?? 'none' );
$column_1_content_font_family      = (string) ( $settings['footer_column_1_content_font_family'] ?? 'Montserrat, sans-serif' );
$column_1_content_font_weight      = (int) ( $settings['footer_column_1_content_font_weight'] ?? 600 );
$column_1_content_font_style       = (string) ( $settings['footer_column_1_content_font_style'] ?? 'normal' );
$column_1_content_font_size        = (int) ( $settings['footer_column_1_content_font_size'] ?? 20 );
$column_1_content_font_style_attr  = sprintf( 'font-family:%1$s;font-weight:%2$d;font-style:%3$s;font-size:%4$dpx;', esc_attr( $column_1_content_font_family ), $column_1_content_font_weight, esc_attr( $column_1_content_font_style ), $column_1_content_font_size );

// $column_1_gap    = (int) ( $settings['footer_column_1_gap'] ?? 41 );
$give_social_gap = (int) ( $settings['footer_give_social_gap'] ?? 16 );
$has_any_give_mode = false;

foreach ( $footer_columns as $col ) {
	if ( isset( $col['mode'] ) && in_array( $col['mode'], $give_modes, true ) ) {
		$has_any_give_mode = true;
		break;
	}
}

$show_inline_give_button = $has_any_give_mode
	&& empty( $settings['footer_give_button_below_columns'] )
	&& ! empty( $settings['footer_give_button_text'] )
	&& ! empty( $settings['footer_give_button_url'] );

$show_below_give_button = $has_any_give_mode
	&& ! empty( $settings['footer_give_button_below_columns'] )
	&& ! empty( $settings['footer_give_button_text'] )
	&& ! empty( $settings['footer_give_button_url'] );

?>
<section class="sog-rebrand__footer-row sog-rebrand__footer-row--core">
	<?php if ( ! empty( $settings['footer_top_text_enabled'] ) && ( ! empty( $settings['footer_top_text_heading'] ) || ! empty( $settings['footer_top_text_content'] ) ) ) : ?>
		<div class="sog-rebrand__footer-intro">
			<?php if ( ! empty( $settings['footer_top_text_heading'] ) ) : ?>
				<h2 class="sog-rebrand__footer-heading"><?php echo esc_html( (string) $settings['footer_top_text_heading'] ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $settings['footer_top_text_content'] ) ) : ?>
				<div class="sog-rebrand__footer-richtext"><?php echo wp_kses_post( do_shortcode( (string) $settings['footer_top_text_content'] ) ); ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="sog-rebrand__footer-grid"<?php echo $footer_grid_style ? ' style="' . esc_attr( $footer_grid_style ) . '"' : ''; ?>>
		<?php if ( null !== $first_column ) : ?>
			<section class="sog-rebrand__footer-column sog-rebrand__footer-column--1 sog-rebrand__footer-column--show-medium-up<?php echo !empty($settings['footer_bottom_hide_mobile']) ? ' sog-rebrand__footer-column--hide-mobile' : ''; ?>" data-sog-rebrand-alignment="<?php echo esc_attr( (string) ( $settings['footer_column_1_alignment'] ?? 'center' ) ); ?>">
				<?php if ( in_array( $first_column['mode'], $wysiwyg_modes, true ) && ! empty( $first_column['content'] ) ) : ?>
					<div class="sog-rebrand__footer-richtext" style="<?php echo esc_attr( $column_1_content_font_style_attr ); ?>"><?php echo wp_kses_post( $first_column['content'] ); ?></div>
				<?php endif; ?>

				<?php if ( in_array( $first_column['mode'], $shortcode_modes, true ) && ! empty( $first_column['content'] ) ) : ?>
					<div class="sog-rebrand__footer-richtext" style="<?php echo esc_attr( $column_1_content_font_style_attr ); ?>">
						<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output is trusted admin input.
						echo do_shortcode( $first_column['content'] ); ?>
					</div>
				<?php endif; ?>

				<?php if ( in_array( $first_column['mode'], $menu_modes, true ) ) : ?>
					<?php if ( ! empty( $first_column['heading'] ) ) : ?>
						<h3 class="sog-rebrand__footer-column-heading" style="text-transform:<?php echo esc_attr( $column_1_heading_text_transform ); ?>;text-decoration:<?php echo esc_attr( $column_1_heading_text_decoration ); ?>;"><?php echo esc_html( $first_column['heading'] ); ?></h3>
					<?php endif; ?>

					<?php if ( $first_column['menu'] ) : ?>
						<?php echo wp_kses_post( $first_column['menu'] ); ?>
					<?php else : ?>
						<p class="sog-rebrand__placeholder"><?php echo esc_html( sprintf( /* translators: %d: column number */ __( 'Assign a menu to footer column %d.', 'sog-unc-rebrand' ), 1 ) ); ?></p>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( 'menus' === $first_column['mode'] ) : ?>
					<?php $first_column_heading2 = isset( $first_column['heading2'] ) ? (string) $first_column['heading2'] : ''; ?>

					<?php if ( ! empty( $first_column_heading2 ) ) : ?>
						<h3 class="sog-rebrand__footer-column-heading" style="text-transform:<?php echo esc_attr( $column_1_heading_text_transform ); ?>;text-decoration:<?php echo esc_attr( $column_1_heading_text_decoration ); ?>;"><?php echo esc_html( $first_column_heading2 ); ?></h3>
					<?php endif; ?>

					<?php $first_column_menu2 = isset( $first_column['menu2'] ) ? $first_column['menu2'] : ''; ?>

					<?php if ( $first_column_menu2 ) : ?>
						<?php echo wp_kses_post( $first_column_menu2 ); ?>
					<?php else : ?>
						<p class="sog-rebrand__placeholder"><?php echo esc_html( sprintf( /* translators: %d: column number */ __( 'Assign a second menu to footer column %d.', 'sog-unc-rebrand' ), 1 ) ); ?></p>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( in_array( $first_column['mode'], $wysiwyg_modes, true ) && empty( $first_column['content'] ) ) : ?>
					<p class="sog-rebrand__placeholder"><?php echo esc_html( sprintf( /* translators: %d: column number */ __( 'Add WYSIWYG content for footer column %d in plugin settings.', 'sog-unc-rebrand' ), 1 ) ); ?></p>
				<?php endif; ?>

				<?php if ( in_array( $first_column['mode'], $shortcode_modes, true ) && empty( $first_column['content'] ) ) : ?>
					<p class="sog-rebrand__placeholder"><?php echo esc_html( sprintf( /* translators: %d: column number */ __( 'Add a shortcode for footer column %d in plugin settings.', 'sog-unc-rebrand' ), 1 ) ); ?></p>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php if ( $show_right_side ) : ?>
			<div class="sog-rebrand__footer-right">
				<?php if ( ! empty( $remaining_cols ) ) : ?>
					<div class="sog-rebrand__footer-columns-group<?php echo $remaining_cols_all_unused ? ' column-empty' : ' column-has-content'; ?>"<?php echo $footer_columns_group_style ? ' style="' . esc_attr( $footer_columns_group_style ) . '"' : ''; ?>>
						<?php foreach ( $remaining_cols as $column_id => $column ) :

							$column_heading_text_transform  = (string) ( $settings[ 'footer_column_' . $column_id . '_heading_text_transform' ] ?? 'none' );
							$column_heading_text_decoration = (string) ( $settings[ 'footer_column_' . $column_id . '_heading_text_decoration' ] ?? 'none' );
							// $column_gap = (int) ( $settings[ 'footer_column_' . $column_id . '_gap' ] ?? 41 );
							?>

							<section class="sog-rebrand__footer-column sog-rebrand__footer-column--<?php echo (int) $column_id; ?><?php echo ! empty( $settings['footer_bottom_hide_mobile'] ) ? ' sog-rebrand__footer-column--hide-mobile' : ''; ?>" data-sog-rebrand-alignment="<?php echo esc_attr( (string) ( $settings['footer_column_' . $column_id . '_alignment'] ?? 'center' ) ); ?>">
								<?php if ( ! empty( $column['heading'] ) ) : ?>
									<h3 class="sog-rebrand__footer-column-heading" style="text-transform:<?php echo esc_attr( $column_heading_text_transform ); ?>;text-decoration:<?php echo esc_attr( $column_heading_text_decoration ); ?>;"><?php echo esc_html( $column['heading'] ); ?></h3>
								<?php endif; ?>

								<?php if ( in_array( $column['mode'], $menu_modes, true ) ) : ?>
									<?php if ( $column['menu'] ) : ?>
										<?php echo wp_kses_post( $column['menu'] ); ?>
									<?php else : ?>
										<p class="sog-rebrand__placeholder"><?php echo esc_html( sprintf( /* translators: %d: column number */ __( 'Assign a menu to footer column %d.', 'sog-unc-rebrand' ), (int) $column_id ) ); ?></p>
									<?php endif; ?>
								<?php endif; ?>

								<?php if ( 'menus' === $column['mode'] ) : ?>
									<?php $col_heading2 = isset( $column['heading2'] ) ? (string) $column['heading2'] : ''; ?>

									<?php if ( ! empty( $col_heading2 ) ) : ?>
										<h3 class="sog-rebrand__footer-column-heading" style="text-transform:<?php echo esc_attr( $column_heading_text_transform ); ?>;text-decoration:<?php echo esc_attr( $column_heading_text_decoration ); ?>;"><?php echo esc_html( $col_heading2 ); ?></h3>
									<?php endif; ?>

									<?php $col_menu2 = isset( $column['menu2'] ) ? $column['menu2'] : ''; ?>

									<?php if ( $col_menu2 ) : ?>
										<?php echo wp_kses_post( $col_menu2 ); ?>
									<?php else : ?>
										<p class="sog-rebrand__placeholder"><?php echo esc_html( sprintf( /* translators: %d: column number */ __( 'Assign a second menu to footer column %d.', 'sog-unc-rebrand' ), (int) $column_id ) ); ?></p>
									<?php endif; ?>
								<?php endif; ?>

								<?php if ( in_array( $column['mode'], $wysiwyg_modes, true ) && ! empty( $column['content'] ) ) : ?>
									<div class="sog-rebrand__footer-richtext"><?php echo wp_kses_post( $column['content'] ); ?></div>
								<?php endif; ?>

								<?php if ( in_array( $column['mode'], $shortcode_modes, true ) && ! empty( $column['content'] ) ) : ?>
									<div class="sog-rebrand__footer-richtext">
										<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output is trusted admin input.
										echo do_shortcode( $column['content'] ); ?>
									</div>
								<?php endif; ?>

								<?php if ( in_array( $column['mode'], $wysiwyg_modes, true ) && empty( $column['content'] ) ) : ?>
									<p class="sog-rebrand__placeholder"><?php echo esc_html( sprintf( /* translators: %d: column number */ __( 'Add WYSIWYG content for footer column %d in plugin settings.', 'sog-unc-rebrand' ), (int) $column_id ) ); ?></p>
								<?php endif; ?>

								<?php if ( in_array( $column['mode'], $shortcode_modes, true ) && empty( $column['content'] ) ) : ?>
									<p class="sog-rebrand__placeholder"><?php echo esc_html( sprintf( /* translators: %d: column number */ __( 'Add a shortcode for footer column %d in plugin settings.', 'sog-unc-rebrand' ), (int) $column_id ) ); ?></p>
								<?php endif; ?>

								<?php if ( in_array( $column['mode'], $social_modes, true ) ) : ?>
									<?php load_template( SOG_UNC_REBRAND_PATH . 'templates/footer/social-media.php', null, array( 'settings' => $settings, 'social_links' => $social_links ) ); ?>
								<?php endif; ?>

								<?php if ( in_array( $column['mode'], $give_modes, true ) && $show_inline_give_button ) : ?>
									<?php load_template( SOG_UNC_REBRAND_PATH . 'templates/footer/donate-button.php', null, array( 'settings' => $settings ) ); ?>
								<?php endif; ?>
							</section>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $social_links ) && ! empty( $settings['footer_social_links_below_columns'] ) ) : ?>
					<?php load_template( SOG_UNC_REBRAND_PATH . 'templates/footer/social-media.php', null, array( 'settings' => $settings, 'social_links' => $social_links ) ); ?>
				<?php endif; ?>

				<?php if ( $show_below_give_button ) : ?>
					<?php load_template( SOG_UNC_REBRAND_PATH . 'templates/footer/donate-button.php', null, array( 'settings' => $settings ) ); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

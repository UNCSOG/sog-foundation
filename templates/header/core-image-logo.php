<?php
/**
 * Image logo header core template.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 * @var string $site_name
 * @var string $logo_url
 * @var string $header_main_menu
 * @var string $header_bottom_menu
 * @var string $mobile_nav_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings           = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();
$site_name          = isset( $args['site_name'] ) ? (string) $args['site_name'] : '';
$logo_url           = isset( $args['logo_url'] ) ? (string) $args['logo_url'] : '';
$header_main_menu   = isset( $args['header_main_menu'] ) ? (string) $args['header_main_menu'] : '';
$header_bottom_menu = isset( $args['header_bottom_menu'] ) ? (string) $args['header_bottom_menu'] : '';
$mobile_nav_id      = isset( $args['mobile_nav_id'] ) ? (string) $args['mobile_nav_id'] : 'sog-rebrand-mobile-nav';

$show_mobile_toggle = ! empty( $header_main_menu ) || ! empty( $header_bottom_menu );
?>
<div class="sog-rebrand__header-core sog-rebrand__header-core--image">
	<div class="sog-rebrand__inner">
		<div class="sog-rebrand__header-shell">
			<div class="sog-rebrand__brand-cluster">
				<?php if ( ! empty( $settings['header_logo_image_url'] ) ) : ?>
					<?php if ( $logo_url ) : ?>
						<a class="sog-rebrand__brand-link" href="<?php echo esc_url( $logo_url ); ?>">
							<img class="sog-rebrand__brand-image" src="<?php echo esc_url( (string) $settings['header_logo_image_url'] ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" />
						</a>
					<?php else : ?>
						<img class="sog-rebrand__brand-image" src="<?php echo esc_url( (string) $settings['header_logo_image_url'] ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" />
					<?php endif; ?>
				<?php else : ?>
					<span class="sog-rebrand__brand-title"><?php echo esc_html( $site_name ); ?></span>
				<?php endif; ?>
			</div>

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
		</div>
	</div>
</div>

<?php if ( $header_bottom_menu ) : ?>
	<?php load_template( SOG_UNC_REBRAND_PATH . 'templates/header/bottom-nav.php', false, array( 'settings' => $settings, 'header_bottom_menu' => $header_bottom_menu ) ); ?>
<?php endif; ?>

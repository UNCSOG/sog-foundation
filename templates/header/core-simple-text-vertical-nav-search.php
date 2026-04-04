<?php
/**
 * Simple text header core template - Vertical w/o Line.
 *
 * @package SOGUNCRebrand
 *
 * @var array<string, mixed> $settings
 * @var string $school_name
 * @var string $site_name
 * @var string $site_description
 * @var string $logo_url
 * @var string $header_main_menu
 * @var string $header_bottom_menu
 * @var string $mobile_nav_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings           = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();
$school_name        = isset( $args['school_name'] ) ? (string) $args['school_name'] : '';
$site_name          = isset( $args['site_name'] ) ? (string) $args['site_name'] : '';
$site_description   = isset( $args['site_description'] ) ? (string) $args['site_description'] : '';
$logo_url           = isset( $args['logo_url'] ) ? (string) $args['logo_url'] : '';
$header_main_menu   = isset( $args['header_main_menu'] ) ? (string) $args['header_main_menu'] : '';
$header_bottom_menu = isset( $args['header_bottom_menu'] ) ? (string) $args['header_bottom_menu'] : '';
$mobile_nav_id      = isset( $args['mobile_nav_id'] ) ? (string) $args['mobile_nav_id'] : 'sog-rebrand-mobile-nav';

$show_mobile_toggle = ! empty( $header_main_menu ) || ! empty( $header_bottom_menu );
$site_name_style = '';
$school_name_style = '';

if ( ! empty( $settings['header_school_name_text_transform'] ) ) {
	$school_name_style = 'text-transform:' . esc_attr( $settings['header_school_name_text_transform'] ) . ';';
}

if ( ! empty( $settings['header_site_name_text_transform'] ) ) {
	$site_name_style = 'text-transform:' . esc_attr( $settings['header_site_name_text_transform'] ) . ';';
}
?>
<div class="sog-rebrand__header-core sog-rebrand__header-core--simple-text sog-rebrand__header-core--simple-text-vertical">
	<div class="sog-rebrand__inner">
		<div class="sog-rebrand__header-shell">
			<div class="sog-rebrand__brand-cluster">
				<p class="sog-rebrand__brand-title school-name" style="<?php echo esc_attr( $school_name_style ); ?>color: var(--sog-rebrand-header-school-name-color)"><?php echo esc_html( $school_name ); ?></p>
			</div>

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

			<?php if ( ! empty( $site_description ) ) : ?>
				<div class="sog-rebrand__brand-cluster">
					<p class="sog-rebrand__brand-title site-tagline site-description"><?php echo esc_html( $site_description ); ?></p>
				</div>
			<?php endif; ?>

			<div class="sog-rebrand__desktop-nav">
				<?php if ( $header_main_menu ) : ?>
					<nav class="sog-rebrand__nav" aria-label="<?php echo esc_attr__( 'Header menu', 'sog-unc-rebrand' ); ?>">
						<?php echo wp_kses_post( $header_main_menu ); ?>
					</nav>

					<div class="sog-rebrand__site-search-container<?php echo empty($settings['display_site_search_enabled']) ? ' sog-rebrand__hide' : ''; ?>">
						<?php if ( ! empty( $settings['display_site_search_enabled'] ) ) :?>
							<?php $placeholder = ! empty( $settings['site_search_placeholder_text'] ) ? esc_attr( $settings['site_search_placeholder_text'] ) : esc_attr__( 'Search the site...', 'sog-unc-rebrand' ); ?>

							<form role="search" method="get" class="sog-rebrand__site-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
								<label class="screen-reader-text" for="sog-rebrand-site-search-field"><?php esc_html_e( 'Search for:', 'sog-unc-rebrand' ); ?></label>
								<input type="search" id="sog-rebrand-site-search-field" class="sog-rebrand__site-search-field" placeholder="<?php echo $placeholder; ?>" value="<?php echo get_search_query(); ?>" name="s" />
								<button type="submit" class="sog-rebrand__site-search-submit" aria-label="<?php esc_attr_e( 'Submit search', 'sog-unc-rebrand' ); ?>">
									<?php if( ! empty( $settings['header_search_icon_enabled'] ) ) : ?>
										<span class="dashicons dashicons-search hide hidden" aria-hidden="true"></span>
										<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10" fill="none">
											<path d="M7.1868 4.06319C7.1868 3.23425 6.85759 2.43926 6.2716 1.85311C5.6856 1.26696 4.89082 0.93766 4.0621 0.93766C3.23338 0.93766 2.4386 1.26696 1.85261 1.85311C1.26662 2.43926 0.937408 3.23425 0.937408 4.06319C0.937408 4.89214 1.26662 5.68713 1.85261 6.27328C2.4386 6.85943 3.23338 7.18873 4.0621 7.18873C4.89082 7.18873 5.6856 6.85943 6.2716 6.27328C6.85759 5.68713 7.1868 4.89214 7.1868 4.06319ZM6.58334 7.24929C5.892 7.79821 5.01514 8.12639 4.0621 8.12639C1.81818 8.12639 0 6.30772 0 4.06319C0 1.81867 1.81818 0 4.0621 0C6.30602 0 8.12421 1.81867 8.12421 4.06319C8.12421 5.01648 7.79611 5.89359 7.24734 6.58511L9.86232 9.20079C10.0459 9.38442 10.0459 9.68134 9.86232 9.86301C9.67874 10.0447 9.3819 10.0466 9.20027 9.86301L6.58334 7.24929Z" fill="#999999"/>
										</svg>
									<?php else : ?>
										<span class="sog-rebrand__site-search-submit-text"><?php echo esc_attr( $settings['site_search_submit_text'] ); ?></span>
									<?php endif; ?>
								</button>
							</form>
						<?php endif; ?>
					</div>
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

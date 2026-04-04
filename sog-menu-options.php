<?php
/**
 * Plugin Name: SOG Menu Options
 * Description: Adds a target field to WordPress menu items so custom values like _blank can be saved directly.
 * Version: 1.0.0
 * Author: Lindsay Hoyt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SOG_Menu_Options {
	public static function init() {
		add_filter( 'wp_setup_nav_menu_item', array( __CLASS__, 'setup_menu_item' ) );
		add_action( 'wp_nav_menu_item_custom_fields', array( __CLASS__, 'render_target_field' ), 10, 5 );
		add_action( 'wp_update_nav_menu_item', array( __CLASS__, 'save_target_field' ), 10, 2 );
	}

	public static function setup_menu_item( $menu_item ) {
		if ( ! isset( $menu_item->sog_target ) ) {
			$menu_item->sog_target = get_post_meta( $menu_item->ID, '_menu_item_target', true );
		}

		return $menu_item;
	}

	public static function render_target_field( $item_id, $menu_item ) {
		$value = '';

		if ( isset( $menu_item->sog_target ) ) {
			$value = $menu_item->sog_target;
		}
		?>
		<p class="description description-wide sog-menu-item-target-field">
			<label for="edit-menu-item-sog-target-<?php echo esc_attr( $item_id ); ?>">
				<?php esc_html_e( 'Target', 'sog-menu-options' ); ?><br />
				<input
					type="text"
					id="edit-menu-item-sog-target-<?php echo esc_attr( $item_id ); ?>"
					class="widefat code edit-menu-item-custom"
					name="menu-item-sog-target[<?php echo esc_attr( $item_id ); ?>]"
					value="<?php echo esc_attr( $value ); ?>"
					placeholder="_self"
				/>
				<span class="description"><?php esc_html_e( 'Examples: _self, _blank, _parent, _top', 'sog-menu-options' ); ?></span>
			</label>
		</p>
		<?php
	}

	public static function save_target_field( $menu_id, $menu_item_db_id ) {
		unset( $menu_id );

		if ( ! isset( $_POST['menu-item-sog-target'][ $menu_item_db_id ] ) ) {
			delete_post_meta( $menu_item_db_id, '_menu_item_target' );
			return;
		}

		$target = sanitize_text_field( wp_unslash( $_POST['menu-item-sog-target'][ $menu_item_db_id ] ) );

		if ( '' === $target ) {
			delete_post_meta( $menu_item_db_id, '_menu_item_target' );
			return;
		}

		update_post_meta( $menu_item_db_id, '_menu_item_target', $target );
	}
}

SOG_Menu_Options::init();

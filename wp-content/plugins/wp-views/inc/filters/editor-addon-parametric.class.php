<?php

// @todo this needs a complete $_POST data sanitization on several methods
// @todo Move this to a proper AJAX callback

class Editor_addon_parametric {

	public function __construct() {
		add_action( 'wp_ajax_wpv_suggest_postmeta_default_label',		array( $this, 'wpv_suggest_postmeta_default_label' ) );
		add_action( 'wp_ajax_nopriv_wpv_suggest_postmeta_default_label',	array( $this, 'wpv_suggest_postmeta_default_label' ) );
	}

	// @todo Check whethr we do want to have suggest for the default_label attribute for osteta filters
	function wpv_suggest_postmeta_default_label() {
		$field = isset( $_REQUEST['field'] ) ? wpv_esc_like( $_REQUEST['field'] ) : '';
		if ( !empty( $field ) ) {

			$needs_db_query = true;
			$input_type = isset( $_REQUEST['type'] ) ? esc_sql( $_REQUEST['type'] ) : '';
			$nice_name = explode('wpcf-', $field);
			$id = isset( $nice_name[1] ) ? $nice_name[1] : $field;
			$types_options = get_option( 'wpcf-fields', array() );

			if( $types_options && !empty( $types_options ) && isset( $types_options[ $id ] ) && is_array( $types_options[ $id ] ) ) {

				$field_options = $types_options[ $id ];
				$field_real_type = isset( $field_options['type'] ) ? $field_options['type'] : '';

				if ( isset( $field_options['data']['options'] ) ) {

					if ( $input_type == 'select' ) {
						$field_lowercase = isset( $field_options['name'] ) ? strtolower( $field_options['name'] ) : $id;
						echo sprintf( __( 'Select one %s', 'wpv-views' ), $field_lowercase ) . "\n";
						echo sprintf( __( 'Any %s', 'wpv-views' ), $field_lowercase ) . "\n";
					}

					switch ( $field_real_type ) {

						case 'checkboxes':
							foreach ( $field_options['data']['options'] as $key => $option ) {
								if ( isset( $option['display'] ) && ( $option['display'] == 'value' ) ) {
									$title = isset( $option['display_value_selected'] ) ? $option['display_value_selected'] : $option['title'];
									$title = $option['title'];
								} else {
									$title = $option['title'];
								}
								echo $title . "\n";
								$needs_db_query = false;
							}
							break;

						case 'select':
							if ( isset( $field_options['data']['options']['default'] ) ) {
								unset( $field_options['data']['options']['default'] );
							}
							foreach ( $field_options['data']['options'] as $key => $option ) {
								$title = isset( $option['title'] ) ? $option['title'] : $option['value'];
								echo $title . "\n";
								$needs_db_query = false;
							}
							break;

						default:
							if ( isset( $field_options['data']['options']['default'] ) ) {
								unset($field_options['data']['options']['default']);
							}
							$display_option = isset( $field_options['data']['display'] ) ? $field_options['data']['display'] : 'db';
							foreach ( $field_options['data']['options'] as $key => $option ) {
								if ( $display_option == 'value' ) {
									$title = isset( $option['display_value'] ) ? $option['display_value'] : $option['title'];
								} else {
									$title = $option['title'];
								}
								echo $title . "\n";
								$needs_db_query = false;
							}
							break;
					}
				}
			}

			if ( $needs_db_query ) {
				if ( $input_type == 'select' ) {
					echo sprintf( __( 'Select one %s', 'wpv-views' ), $field ) . "\n";
					echo sprintf( __( 'Any %s', 'wpv-views' ), $field ) . "\n";
				}

				global $wpdb;
				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
						WHERE meta_key = %s
						ORDER BY meta_value
						LIMIT 0, 20",
						$field
					)
				);
				foreach ( $results as $row ) {
					echo $row->meta_value . "\n";
				}
			}
		}
		die();
	}

}

new Editor_addon_parametric();

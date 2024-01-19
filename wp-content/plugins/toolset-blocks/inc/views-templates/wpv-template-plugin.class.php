<?php
require WPV_PATH_EMBEDDED . '/inc/views-templates/wpv-template.class.php';

class WPV_template_plugin extends WPV_template
{

    function init() {
		parent::init();
	}


    // FIXME: Move to new upgrade routines - see issue views-15
    function clear_legacy_view_settings() {
        global $wpdb;

        $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta}
				WHERE meta_key = %s",
				'_views_template_new_type'
			)
		);
    }

    // FIXME: Move to new upgrade routines - see issue views-15
    function legacy_view_settings( $options ) {
        global $wpdb;

        $view_tempates_new = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta}
				WHERE meta_key = %s",
				'_views_template_new_type'
			)
		);

        foreach ( $view_tempates_new as $template_for_new ) {
            $value = unserialize( $template_for_new->meta_value );
            if ( $value ) {
                foreach ( $value as $type => $status ) {
                    if ( $status ) {
                        $options['views_template_for_' . $type] = $template_for_new->post_id;
                    }
                }
            }
        }

        return $options;
    }


	/**
	 * TODO Purpose of this method is unclear to me.
     *
     * @deprecated Most probably deprecated - since 1.10.
	 * @param $options
	 * @return mixed
	 */
    function submit( $options ) {
        $this->clear_legacy_view_settings();

        foreach ( $_POST as $index => $value ) {
            if ( strpos( $index, 'views_template_loop_' ) === 0 ) {
                $options[$index] = $value;
            }
            if ( strpos( $index, 'views_template_for_' ) === 0 ) {
                $options[$index] = $value;
            }
            if ( strpos( $index, 'views_template_archive_for_' ) === 0 ) {
                $options[$index] = $value;
            }
        }

        if ( isset( $_POST['wpv_theme_function'] ) ) {
            $options['wpv_theme_function'] = $_POST['wpv_theme_function'];
            $options['wpv_theme_function_debug'] = isset( $_POST['wpv_theme_function_debug'] ) && $_POST['wpv_theme_function_debug'];
        }

        return $options;
    }


}


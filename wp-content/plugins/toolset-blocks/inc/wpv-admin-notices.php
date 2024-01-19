<?php

/**
* wpv-admin-notices.php
*
* Handle admin notices
*
* Admin notices can belong to one on two types: user admin notices or global admin notices.
*     User admin notices are dismissed in a usermeta field _wpv_dismissed_notices
*     Gobal admin notices are dismissed in an option setting _wpv_global_dismissed_notices
*
* @since 1.6.2
*/

if ( defined( 'WPT_ADMIN_NOTICES' ) ) {
    return;
}

define( 'WPT_ADMIN_NOTICES', true );

/**
* WPToolset_Admin_Notices
*
* Methods for handling admin notices
*
* @since 1.6.2
*/

class WPToolset_Admin_Notices {

	protected static $instance = null;

	public $has_notices = false;

	/**
	 * Get or generate an instance of WPToolset_Admin_Notices
	 *
	 * @return null|WPToolset_Admin_Notices
	 *
	 * @since 2.2.1
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new WPToolset_Admin_Notices();
		}
		return self::$instance;
	}

	/**
	 * WPToolset_Admin_Notices constructor.
	 *
	 * @since 1.6.2
	 */
	function __construct() {

		$this->has_notices                              = false;

		add_action( 'plugins_loaded',                   array( $this, 'ignore_admin_notice' ) );

		add_action( 'init',                             array( $this, 'register_admin_notices' ) );
		add_action( 'admin_notices',                    array( $this, 'display_admin_notices' ) );

		add_action( 'wp_ajax_wpv_dismiss_notice',       array( $this, 'ajax_dismiss_notice' ) );
		add_action( 'admin_footer',                     array( $this, 'dismiss_admin_notices_assets') );

		// API hooks
		add_action( 'wpv_action_wpv_dismiss_notice',    array( $this, 'dismiss_notice' ) );
		add_filter( 'wpv_filter_wpv_is_dismissed_notice',   array( $this, 'is_dismissed_notice' ), 10, 2 );

		add_filter( 'removable_query_args', array( $this, 'removable_query_args') );

	}

	/**
	* register_admin_notices
	*
	* Here we hook our internal notices
	*
	* @since 1.6.2
	*/

	function register_admin_notices() {
		// Global notice about release notes
		// @deprecated 2.0
		// add_filter( 'wptoolset_filter_admin_notices', array( $this, 'release_notes' ) );
	}

	/**
	 * Display an admin notice on each release, linking to the https://toolset.com Version page
	 *
	 * @param array $notices Notices to display
	 * @return array
	 * @since 1.10
	 * @deprecated 2.0 Keep for reference
	 */
	function release_notes( $notices ) {
		global $pagenow;
		if (
			current_user_can( 'activate_plugins' )
			&& $pagenow == 'plugins.php'
		) {
			$dismissed_notices = get_option( '_wpv_global_dismissed_notices', array() );
			if (
				! is_array( $dismissed_notices )
				|| empty( $dismissed_notices )
			) {
				$dismissed_notices = array();
			}
			if ( isset( $dismissed_notices['wpv_release_notes_onetwelve'] ) ) {
				return $notices;
			} else {
				$notice_text = '<p>'
					. '<i class="icon-views-logo fa fa-wpv-custom ont-color-orange ont-icon-24" style="margin-right:5px;vertical-align:-2px;"></i>'
					. __( 'This version of Views includes major updates and improvements.', 'wpv-views' )
					. ' <a href="'
					. 'https://toolset.com/version/views-1-12/?utm_source=viewsplugin&utm_campaign=views&utm_medium=release-notes-admin-notice&utm_term=Views 1.12 release notes'
					. '" class="button button-primary button-primary-toolset" target="_blank">'
					. __( 'Views 1.12 release notes', 'wpv-views' )
					. '</a>';

				global $wp_version;
				if ( version_compare( $wp_version, '4.2.2', '<' ) ) {
				$notice_text .= '  <a class="button button-secondary js-wpv-dismiss" href="' . esc_url( add_query_arg( array( 'wpv_dismiss_global_notice' => 'wpv_release_notes_onetwelve' ) ) ) . '">'
					. __( 'Dismiss', 'wpv-views' )
					. '</a>';
				}

				$notice_text .= '</p>';
				$args = array(
					'notice_class' => 'notice notice-success updated is-dismissible js-wpv-is-dismissible-notice',
					'notice_text' => $notice_text,
					'notice_type' => 'global'
				);
				$notices['wpv_release_notes_onetwelve'] = $args;
			}
		}
		return $notices;
	}

	/**
	* display_admin_notices
	*
	* Displays admin notices hooked into the wptoolset_filter_admin_notices filter
	*
	* @since 1.6.2
	*/

	function display_admin_notices() {

		$notices = array();
		/*
		* wptoolset_filter_admin_notices
		*
		* Filter to pass admin notices
		*
		* $notices is an array with the format:
		*	'notice_id' => $notice_data = array()
		*
		* $notice_data is an array with the format:
		*	'notice_class' 	=> 'update'|'error'|custom (string) (defaults to 'update')
		*	'notice_text' 	=> (string) (mandatory) (localized on origin)
		* 	'notice_type' 	=> 'global'|'user'
		*/
		$notices = apply_filters( 'wptoolset_filter_admin_notices', $notices );

		if (
			is_array( $notices )
			&& count( $notices ) > 0
		) {
			$this->has_notices = true;
			if ( ! wp_script_is( 'underscore' ) ) {
				wp_enqueue_script( 'underscore' );
			}
			foreach ( $notices as $notice_id => $notice_data ) {
				if ( is_array( $notice_data ) ) {
					$notice_data_defaults = array(
						'notice_class' => 'updated',
						'notice_text' => '',
						'notice_type' => 'global'
					);
					$notice_data = wp_parse_args( $notice_data, $notice_data_defaults );

					echo '<div id="' . $notice_id . '" data-type="' . esc_attr( $notice_data['notice_type'] ) . '" class="message ' . esc_attr( $notice_data['notice_class'] ) . '">';
					echo $notice_data['notice_text'];
					echo "</div>";
				}
			}
		}
	}

	/**
	* ignore_admin_notice
	*
	* Ignores admin notices based on URL parameters
	*
	* @since 1.6.2
	*/

	function ignore_admin_notice() {
		$notice_type = '';
		$dismissed_notices = '';
		$notice_id = '';

		if ( isset( $_GET['wpv_wpv_if_changes_ignore'] ) && 'yes' == $_GET['wpv_wpv_if_changes_ignore'] ) {
			global $current_user;
			$user_id = $current_user->ID;
			add_user_meta( $user_id, 'wpv_wpv_if_changes_ignore_notice', 'true', true );
		}

		/**
		* General case
		*
		* Dismisses user and global admin notices based on a URL parameter
		*
		* @since 1.7.0
		*/
		// Get global dissmiss notice, no matter if send via $_GET or $_POST.
		$dismiss_global_notice = isset( $_REQUEST['wpv_dismiss_global_notice'] ) ?
			$_REQUEST['wpv_dismiss_global_notice'] :
			null;

		if ( isset( $_GET['wpv_dismiss_user_notice'] ) ) {
			global $current_user;
			$user_id = $current_user->ID;
			$notice_type = 'user';
			$notice_id = $_GET['wpv_dismiss_user_notice'];
			$dismissed_notices = get_user_meta( $user_id, '_wpv_dismissed_notices', true );
		} else if ( $dismiss_global_notice ) {
			$notice_type = 'global';
			$notice_id = $dismiss_global_notice;
			$dismissed_notices = get_option( '_wpv_global_dismissed_notices', array() );
		}
		$notice_id = sanitize_key( $notice_id );
		if ( empty( $notice_id ) ) {
			return;
		}
		if ( ! is_array( $dismissed_notices ) || empty( $dismissed_notices ) ) {
			$dismissed_notices = array();
		}
		$dismissed_notices[ $notice_id ] = 'yes';
		if ( $notice_type == 'user' ) {
			update_user_meta( $user_id, '_wpv_dismissed_notices', $dismissed_notices );
			// @todo remove this on Views 1.8 or 1.9, once we can be almost sure this entry has been deleted or when performing an upgrade routine
			delete_user_meta( $user_id, 'wpv_1304_types_notice' );
			delete_user_meta( $user_id, 'wpv_1304_cred_notice' );
		} else if ( $notice_type == 'global' ) {
			update_option( '_wpv_global_dismissed_notices', $dismissed_notices );
		}
	}

	/**
	* dismiss_admin_notices_assets
	*
	* Adds the javascript and CSS needed for AJAX dismiss the admin notices, as well as the button-primary-toolset styles
	*
	* @since 1.9
	*/

	function dismiss_admin_notices_assets() {
		if ( $this->has_notices ) {
			?>
			<script type="text/javascript">
                jQuery( function( $ ) {
					var WPViews = WPViews || {};
                    WPViews.dismissible_notice_nonce = "<?php echo wp_create_nonce( 'wpv_ajax_dismiss_admin_notice' ); ?>";

                    _.defer( function($) {
                        $( '.js-wpv-is-dismissible-notice' ).each( function () {
                            var thiz = $( this ),
							button = $( 'button.notice-dismiss', thiz ),
							notice = thiz.attr( 'id' ),
							type = thiz.data( 'type' );
                            button.on( 'click', function( event ) {
                                var data = {
                                    nonce: WPViews.dismissible_notice_nonce,
                                    action: 'wpv_dismiss_notice',
                                    notice: notice,
									type: type
                                };
                                $.post( ajaxurl, data, function( response ) {

                                }, 'json')
								.fail( function( xhr, error ) {
									//console.error( arguments );
								});
                            })

                        });
                    }, $ );
                });
            </script>
			<style>
			.wp-core-ui .button-primary-toolset {
				background: #f6921e;
				border-color: #EF6223;
				-webkit-box-shadow: inset 0 1px 0 rgba(239, 239, 239, 0.5), 0 1px 0 rgba(0,0,0,.15);
				box-shadow: inset 0 1px 0 rgba(239, 239, 239, 0.5), 0 1px 0 rgba(0,0,0,.15);
				color: #fff;
				text-decoration: none;
				text-shadow: 0 -1px 1px #EF6223, 1px 0 1px #EF6223, 0 1px 1px #EF6223, -1px 0 1px #EF6223;
			}

			.wp-core-ui .button-primary-toolset.hover,
			.wp-core-ui .button-primary-toolset:hover,
			.wp-core-ui .button-primary-toolset.focus,
			.wp-core-ui .button-primary-toolset:focus {
				background: #EF6223;
				border-color: #EF6223;
				-webkit-box-shadow: inset 0 1px 0 rgba(239, 239, 239, 0.5);
				box-shadow: inset 0 1px 0 rgba(239, 239, 239, 0.5);
				color: #fff;
			}

			.wp-core-ui .button-primary-toolset.focus,
			.wp-core-ui .button-primary-toolset:focus {
				border-color: #EF6223;
				-webkit-box-shadow: inset 0 1px 0 rgba(120,200,230,0.6), 1px 1px 2px rgba(0,0,0,0.4);
				box-shadow: inset 0 1px 0 rgba(120,200,230,0.6), 1px 1px 2px rgba(0,0,0,0.4);
			}

			.wp-core-ui .button-primary-toolset.active,
			.wp-core-ui .button-primary-toolset.active:hover,
			.wp-core-ui .button-primary-toolset.active:focus,
			.wp-core-ui .button-primary-toolset:active {
				background: #f6921e;
				border-color: #EF6223;
				color: rgba(255,255,255,0.95);
				-webkit-box-shadow: inset 0 1px 0 rgba(0,0,0,0.1);
				box-shadow: inset 0 1px 0 rgba(0,0,0,0.1);
			}

			.wp-core-ui .notice .button-primary-toolset.active,
			.wp-core-ui .notice .button-primary-toolset.active:hover,
			.wp-core-ui .notice .button-primary-toolset.active:focus,
			.wp-core-ui .notice .button-primary-toolset:active {
				vertical-align: baseline;
			}

			.wp-core-ui .button-primary-toolset[disabled],
			.wp-core-ui .button-primary-toolset:disabled,
			.wp-core-ui .button-primary-toolset.disabled {
				color: #94cde7 !important;
				background: #298cba !important;
				border-color: #1b607f !important;
				-webkit-box-shadow: none !important;
				box-shadow: none !important;
				text-shadow: 0 -1px 0 rgba(0,0,0,0.1) !important;
				cursor: default;
			}
			</style>
			<?php
		}
	}

	/**
	* ajax_dismiss_notice
	*
	* Callback for the AJAX action to dismiss a notice
	*
	* @since 1.9
	*/

	function ajax_dismiss_notice() {
        if (
			$_POST
			&& isset( $_POST['notice'] )
			&& wp_verify_nonce( $_POST['nonce'], 'wpv_ajax_dismiss_admin_notice' )
		) {
			$notice_data = array(
				'type'  => isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'global',
				'id'    => isset( $_POST['notice'] ) ? sanitize_text_field( $_POST['notice'] ) : ''
			);
			$this->dismiss_notice( $notice_data );
			$data = array(
				'message' => __( 'Admin notice dismissed', 'wpv-views' )
			);
			wp_send_json_success( $data );
        } else {
			$data = array(
				'message' => __( 'Wrong nonce', 'wpv-views' )
			);
			wp_send_json_error( $data );
        }
    }

	/**
	 * Manually dismiss a notice, given its data.
	 *
	 * @param $notice_data
	 */
	public function dismiss_notice( $notice_data ) {

        $defaults = array(
            'type'  => 'global',
	        'id'    => ''
        );

        $notice_data = wp_parse_args( $notice_data, $defaults );

        if ( empty( $notice_data['id'] ) ) {
            return;
        }

        switch ( $notice_data['type'] ) {
	        case 'user':
		        global $current_user;
		        $user_id = $current_user->ID;
		        $dismissed_notices = get_user_meta( $user_id, '_wpv_dismissed_notices', true );
		        if ( ! is_array( $dismissed_notices ) || empty( $dismissed_notices ) ) {
			        $dismissed_notices = array();
		        }
		        $dismissed_notices[ $notice_data['id'] ] = 'yes';
		        update_user_meta( $user_id, '_wpv_dismissed_notices', $dismissed_notices );
	            break;
	        case 'global':
	        default:
		        $dismissed_notices = get_option( '_wpv_global_dismissed_notices', array() );
		        if ( ! is_array( $dismissed_notices ) || empty( $dismissed_notices ) ) {
			        $dismissed_notices = array();
		        }
		        $dismissed_notices[ $notice_data['id'] ] = 'yes';
		        update_option( '_wpv_global_dismissed_notices', $dismissed_notices );
	            break;
        }

    }

    public function is_dismissed_notice( $status, $notice_data = array() ) {

	    $defaults = array(
		    'type'  => 'global',
		    'id'    => ''
	    );

	    $notice_data = wp_parse_args( $notice_data, $defaults );

	    if ( empty( $notice_data['id'] ) ) {
		    return $status;
	    }

	    switch ( $notice_data['type'] ) {
		    case 'user':
			    global $current_user;
			    $user_id = $current_user->ID;
			    $dismissed_notices = get_user_meta( $user_id, '_wpv_dismissed_notices', true );
			    if ( ! is_array( $dismissed_notices ) || empty( $dismissed_notices ) ) {
				    $dismissed_notices = array();
			    }
			    $status = isset( $dismissed_notices[ $notice_data['id'] ] );
			    break;
		    case 'global':
		    default:
			    $dismissed_notices = get_option( '_wpv_global_dismissed_notices', array() );
			    if ( ! is_array( $dismissed_notices ) || empty( $dismissed_notices ) ) {
				    $dismissed_notices = array();
			    }
		        $status = isset( $dismissed_notices[ $notice_data['id'] ] );
			    break;
	    }

        return $status;

	}

	public function removable_query_args( $args ) {
		$args[] = 'wpv_dismiss_global_notice';
		return $args;
	}

}

WPToolset_Admin_Notices::get_instance();

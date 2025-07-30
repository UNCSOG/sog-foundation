<?php

add_action('init', array('WPDDL_Messages', 'initialise_class') );

class WPDDL_Messages
{
    static $dismiss_notice_string = 'wpddl-dismissible-notice';
    static $release_option_name = 'wpddl_layouts_activation_notice_shown';
    static $message_error = '<div class="alert-no-post-content toolset-alert toolset-alert-error">%s</div>';
    static $message_info = '<div class="alert-no-post-content toolset-alert">%s</div>';
    static $message_warning = '<div class="alert-no-post-content toolset-alert toolset-alert-warning">%s</div>';
    static $message_update = '<div class="notice notice-success inline is-dismissible wpddl-dismissible-notice" data-option="%s"><p><i class="icon-layouts-logo ont-color-orange ont-icon-24"></i><span class="text-span">%s</span></p></div>';
    /**
     * Enqueued admin notices.
     *
     * @var array Array of associative arrays with keys 'type' and 'message'. Value of 'type' can be any of those
     *     accepted by WPDDL_Messages::display_message.
     */
    protected static $admin_notices = array();

    public static function initialise_class(){
        add_action('wp_ajax_'.self::$dismiss_notice_string, array(__CLASS__, 'wpddl_dismissible_notice') );
        add_action('admin_print_scripts', array(__CLASS__, 'admin_print_scripts') );
    }

    public static function views_missing_message()
    {
        return sprintf(self::$message_error, __('The Views plugin should be activated to display this layout.', 'ddl-layouts'));
    }

    public static function cred_missing_message()
    {
        return sprintf(self::$message_error, __('The Toolset Forms plugin should be activated to display this layout.', 'ddl-layouts'));
    }

    public static function cred_form_missing_message()
    {
        return sprintf(self::$message_error, __('The Post Form could not be found.', 'ddl-layouts'));
    }

    public static function archive_page_needed_message()
    {
        return sprintf(self::$message_error, __('A WordPress Archive page should be used to display this layout.', 'ddl-layouts'));
    }

    public static function display_message($type, $message)
    {
        switch ($type) {
            case 'error':
                return sprintf(self::$message_error, $message);
                break;
            case 'warning':
                return sprintf(self::$message_warning, $message);
                break;
            case 'info':
                return sprintf(self::$message_info, $message);
                break;
            default:
                return sprintf(self::$message_info, $message);
        }
    }

    public static function release_message(){
            self::dismissible_notice(
                self::$release_option_name,
                sprintf( __('This version of Layouts includes major updates and improvements. <a href="%s" class="button button-primary button-primary-toolset" target="_blank">Layouts %s release notes</a>', 'ddl-layouts'), WPDDL_NOTES_URL, WPDDL_VERSION )
            );
    }

    public static function dismiss_notices_script(){
        ob_start();?>
            <script type="text/javascript">
                jQuery(function ($) {
                    var wpdd_dismissible_notice_nonce = "<?php echo wp_create_nonce( self::$dismiss_notice_string ); ?>";

                    _.defer(function ($) {
                        $('.wpddl-dismissible-notice').each(function () {
                            var $button = $('button.notice-dismiss', $(this)), option = $(this).data('option');
                            $button.on('click', function (event) {
                                var data = {
                                    'wpddl-dismissible-notice': wpdd_dismissible_notice_nonce,
                                    action: "<?php echo  self::$dismiss_notice_string; ?>",
                                    option: option,
                                    option_value: 1
                                };
                                $.post(ajaxurl, data, function ( response ) {
                                        if( response && response.Data && response.Data.error ){
                                            console.info( 'Error', response.Data.error );
                                        }
                                }, 'json')
                                    .fail(function(xhr, error){
                                            console.error( arguments );
                                    });
                            })

                        });
                    }, $);
                });
            </script>
        <?php
        echo ob_get_clean();
    }

    public static function wpddl_dismissible_notice(){
        if( $_POST && wp_verify_nonce($_POST[self::$dismiss_notice_string], self::$dismiss_notice_string) ){
            global $current_user ;

            $user_id = $current_user->ID;

            add_user_meta($user_id, $_POST['option'], $_POST['option_value'], true);

            die( wp_json_encode( array( 'Data' => array('message' => $_POST['option_value'] ) ) ) );
        } else {
            die( wp_json_encode( array( 'Data' => array( 'error' => __("Nonce problem", 'ddl-layouts') ) ) ) );
        }
    }

    public static function dismissible_notice($option, $message)
    {
        printf( self::$message_update, $option, $message );

        add_action('admin_footer', array(__CLASS__, 'dismiss_notices_script') );
    }

    public static function admin_print_scripts(){
        global $pagenow, $wpddlayout;
        $wpddlayout->enqueue_styles('toolset-notifications-css');
        ob_start();
        ?>

                    <style type="text/css" media="screen">

                            .wpddl-dismissible-notice{
                                margin-left:0px;
                                margin-top:10px;
                            }
                            .wpddl-dismissible-notice .text-span{
                                vertical-align: -3px;
                                margin-left:5px;
                            }
                            .wpddl-dismissible-notice .button-primary-toolset{margin-left:5px;}
                    </style>

        <?php
        echo ob_get_clean();
    }

    /**
     * Enqueue an admin notice to be displayed at the right time.
     *
     * @param string $type Can be any of values accepted by WPDDL_Messages::display_message.
     * @param string $message Text of the message. Needs to be already sanitized!
     * @param bool $wrap_p Determines if p tag should be added around the message. Default is true
     */
    public static function add_admin_notice( $type, $message, $wrap_p = true ) {
        if( empty( self::$admin_notices ) ) {
            add_action( 'admin_notices', array( __CLASS__, 'print_admin_notices' ) );
        }
        self::$admin_notices[] = array( 'type' => $type, 'message' => $message, 'wrap_p' => (bool)$wrap_p );
    }


    /**
     * Print enqueued admin notices.
     */
    public static function print_admin_notices() {
        foreach( self::$admin_notices as $notice ) {

            $message = wpddl_getarr( $notice, 'message' );

            if( wpddl_getarr( $notice, 'wrap_p', true ) ) {
                $message = sprintf( '<p>%s</p>', $message );
            }

            printf( '<div class="%s">%s</div>', wpddl_getarr( $notice, 'type', 'error' ), $message );
        }
    }

}
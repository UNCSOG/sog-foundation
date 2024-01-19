<?php
if (!defined('ABSPATH'))
    die('Security check');
if (!current_user_can(CRED_CAPABILITY)) {
    die('Access Denied');
}

// include needed files
$wp_list_table = CRED_Loader::get('TABLE/UserForms');
$doaction = $wp_list_table->current_action();

$url = CRED_CRED::getNewUserFormLink();
$form_id = '';
$form_name = '';
$form_type = '';
$post_type = '';
$form_content = '';
$fields = '';

// Handle Table Action
if ($doaction) {
    $forms_model = CRED_Loader::get('MODEL/UserForms');

    switch ($doaction) {
        case 'delete-selected':
            if (isset($_REQUEST['checked']) && is_array($_REQUEST['checked'])) {
                if (check_admin_referer('cred-bulk-selected-action', 'cred-bulk-selected-field')) {
                    foreach ($_REQUEST['checked'] as $form_id) {
                        $forms_model->deleteForm((int) $form_id);
                    }
                }
            }
            break;

        case 'clone-selected':
            if (isset($_REQUEST['checked']) && is_array($_REQUEST['checked'])) {
                if (check_admin_referer('cred-bulk-selected-action', 'cred-bulk-selected-field')) {
                    foreach ($_REQUEST['checked'] as $form_id) {
                        $forms_model->cloneForm((int) $form_id);
                    }
                }
            }
            break;

        case 'delete':
            if (!isset($_REQUEST['id'])) {
                break;
            }

            $form_id = (int) $_REQUEST['id'];
            if (check_admin_referer('delete-form_' . $form_id, '_wpnonce')) {
                $forms_model->deleteForm($form_id);
            }
            break;

        case 'clone':
            if (!isset($_REQUEST['id'])) {
                break;
            }

            $form_id = (int) $_REQUEST['id'];
            if (check_admin_referer('clone-form_' . $form_id, '_wpnonce')) {
                if (array_key_exists('cred_form_title', $_REQUEST) &&
                        !empty($_REQUEST['cred_form_title'])) {
                    $cred_form_title = trim(urldecode($_REQUEST['cred_form_title']));
                    $forms_model->cloneForm($form_id, $cred_form_title);
                } else {
                    $forms_model->cloneForm($form_id);
                }
            }
            break;
    }

    $redurl = "?page=" . $_REQUEST['page'];
    if (headers_sent()) {
        //die("Redirect failed. Please click on this link: <a href=...>");
        echo "<script language='javascript'>window.location='{$redurl}';</script>";
        die();
    } else {
        exit(wp_redirect("?page=" . $_REQUEST['page']));
    }
    //exit();
}
?>
<div class="cred_overlay_loader"></div>
<div class="wrap">
    <h1><?php _e('User Forms', 'wp-cred'); ?><a class="add-new-h2" href="<?php echo $url; ?>"><?php _e('Add New', 'wp-cred'); ?></a>
    </h1>
    <form id="list" action="" method="post" style="margin-bottom: 3em;">
        <?php
        if (function_exists('wp_nonce_field'))
            wp_nonce_field('cred-bulk-selected-action', 'cred-bulk-selected-field');
        $wp_list_table->prepare_items();
        $wp_list_table->display();
        ?>
    </form>
    <table class="wpcf-types-form-table widefat js-wpcf-slugize-container">
        <thead>
            <tr>
                <th><?php _e('Manage non-Toolset User Fields with Toolset Forms', 'wp-cred'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <p>
                        <?php _e('You can manage non-Toolset User Fields by setting field type and assign field attributes. Then you can use them in Toolset Forms.', 'wp-cred'); ?>
                        <?php
                        $fields_control_url = esc_url(
                                add_query_arg(
                                        array('page' => 'CRED_User_Fields'), admin_url('admin.php')
                                )
                        );
                        ?>
                        <a class="button" href="<?php echo $fields_control_url; ?>"><?php _e('Manage non-Toolset User Fields', 'wp-cred'); ?></a>
                        <?php
                        $documentation_link_args = array(
                            'utm_source'	=> 'plugin',
                            'utm_campaign'	=> 'forms',
                            'utm_medium'	=> 'gui',
                            'utm_term'		=> 'Check our documentation'
                        );
                        $documentation_link = add_query_arg( $documentation_link_args, CRED_DOC_LINK_NON_TOOLSET_FIELDS_CONTROL );
                        echo sprintf(
                            '<a href="%1$s" title="%2$s" target="_blank">%3$s %4$s</a>.',
                            esc_url( $documentation_link ),
                            esc_attr( __( 'Check our documentation', 'wp-cred' ) ),
                            __( 'Check our documentation', 'wp-cred' ),
                            '<i class="fa fa-external-link"></i>'
                        );
                        ?>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script type='text/javascript'>
    /* <![CDATA[ */
    (function (window, $, cred, undefined) {
        $(function () {
            var $win = $(window), overlay_loader = $('.cred_overlay_loader');
            overlay_loader.hide();
            $('form#list').on('click', 'a.submitexport', function (event) {
                event.preventDefault();
                var linkHref = cred.route($(this).attr('href'), {ajax: 1}, false);
                $.fileDownload(linkHref, {
                    successCallback: function () {
                        overlay_loader.hide();
                    },
                    beforeDownloadCallback: function () {
                        overlay_loader.css({'background-position': 'center ' + (0.5 * $win.height() + $win.scrollTop()) + 'px'}).show();
                    },
                    failCallback: function () {
                        overlay_loader.hide();
                        cred.gui.Popups.alert({
                            message: '<?php echo esc_js(__('An error occurred please try again', 'wp-cred')); ?>',
                            class: 'cred-dialog'
                        });
                    }
                });
                return false; //this is critical to stop the click event which will trigger a normal file download!
            });

            $('form#list').on('click', 'a.submitdelete', function (event) {
                var linkHref = $(this).attr('href');
                cred.gui.Popups.confirm({
                    message: '<?php echo esc_js(__("Are you sure that you want to delete this user form?", "wp-cred")); ?>',
                    class: 'cred-dialog',
                    buttons: [cred.settings.locale.Yes, cred.settings.locale.No],
                    primary: cred.settings.locale.Yes,
                    callback: function (button) {
                        if (button == cred.settings.locale.Yes)
                        {
                            document.location = linkHref;
                        }

                    }
                });
                event.preventDefault();
                return false;
            });

            $('form#list').on('click', 'a.cred-export-all', function (event) {
                event.preventDefault();
                var linkHref = cred.route($(this).attr('href'), {ajax: 1}, false);
                $.fileDownload(linkHref, {
                    successCallback: function () {
                        overlay_loader.hide();
                    },
                    beforeDownloadCallback: function () {
                        overlay_loader.css({'background-position': 'center ' + (0.5 * $win.height() + $win.scrollTop()) + 'px'}).show();
                    },
                    failCallback: function () {
                        overlay_loader.hide();
                        cred.gui.Popups.alert({
                            message: '<?php echo esc_js(__('An error occurred please try again', 'wp-cred')); ?>',
                            class: 'cred-dialog'
                        });
                    }
                });
                return false; //this is critical to stop the click event which will trigger a normal file download!
            });

            $('form#list').submit(function (event) {
                var action = $('form#list select[name="action"]').val();
                // get controls at bottom
                if (-1 == action)
                    action = $('form#list select[name="action2"]').val();

                var checked = $(this).find('input[name="checked[]"]').filter(':checked');

                if (action == 'export-selected')
                {
                    // nothing selected to export
                    if (!checked.length)
                    {
                        event.preventDefault();
                        return false;
                    }

                    // prevent action from submission, it conflicts with ajax action param
                    $('form#list select[name="action"]').attr('disabled', 'disabled');
                    event.preventDefault();

                    $.fileDownload('<?php echo CRED_CRED::route('/Forms/exportSelected?type=user&ajax=1'); ?>', {
                        successCallback: function () {
                            $('form#list select[name="action"]').removeAttr('disabled');
                            overlay_loader.hide();
                        },
                        beforeDownloadCallback: function () {
                            overlay_loader.css({'background-position': 'center ' + (0.5 * $win.height() + $win.scrollTop()) + 'px'}).show();
                        },
                        failCallback: function () {
                            $('form#list select[name="action"]').removeAttr('disabled');
                            overlay_loader.hide();
                            cred.gui.Popups.alert({
                                message: '<?php echo esc_js(__('An error occurred please try again', 'wp-cred')); ?>',
                                class: 'cred-dialog'
                            });
                        },
                        httpMethod: "POST",
                        data: $(this).serialize()
                    });
                    return false; //this is critical to stop the click event which will trigger a normal file download!
                } else if (action == 'delete-selected')
                {
                    // nothing selected to delete
                    if (!checked.length)
                    {
                        event.preventDefault();
                        return false;
                    }

                    cred.gui.Popups.confirm({
                        message: '<?php echo esc_js(__("Are you sure that you want to delete the selected user forms?", "wp-cred")); ?>',
                        class: 'cred-dialog',
                        buttons: [cred.settings.locale.Yes, cred.settings.locale.No],
                        primary: cred.settings.locale.Yes,
                        callback: function (button) {
                            if (button == cred.settings.locale.Yes)
                            {
                                $('form#list').off('submit').trigger( 'submit' );
                            }

                        }
                    });
                    event.preventDefault();
                    return false;
                } else
                    return true;
            });
        });
    })(window, jQuery, cred_cred);
    /* ]]> */
</script>

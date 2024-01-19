<?php
final class Access_Post_Helper
{
/*
 * Post functions.
 */

 public static function init()
 {
    add_action('add_meta_boxes', array(__CLASS__, 'wpcf_access_post_add_meta_boxes'));
    add_action('save_post', array(__CLASS__, 'wpcf_access_post_save'));
    add_action('load-post.php', array(__CLASS__, 'wpcf_access_post_init'));
    add_action('load-post-new.php', array(__CLASS__, 'wpcf_access_post_init'));
    //add_action('load-post.php', 'wpcf_access_admin_post_page_load_hook');
    //add_action('load-post-new.php', 'wpcf_access_admin_post_page_load_hook');
 }


/**
 * Init function.
 */
public static function wpcf_access_post_init()
{
    $access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
    $areas = $access_settings->wpcf_access_get_areas();
    if (!empty($areas))
    {
        TAccess_Loader::loadAsset('STYLE/wpcf-access-dev', 'wpcf-access');
        TAccess_Loader::loadAsset('STYLE/types-suggest-dev', 'types-suggest');
        TAccess_Loader::loadAsset('SCRIPT/wpcf-access-dev', 'wpcf-access');
        TAccess_Loader::loadAsset('SCRIPT/types-suggest-dev', 'types-suggest');
        add_thickbox();

        self::wpcf_access_admin_post_page_load_hook();
    }
}

/**
 * Registers meta boxes.
 *
 * @global type $post
 */
public static function wpcf_access_post_add_meta_boxes()
{
    global $post;
	$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
	$areas = $access_settings->wpcf_access_get_areas();
    if (!empty($areas))
    {
        $access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
        $roles = $access_settings->wpcf_get_editable_roles();
        //add_action('admin_footer', 'wpcf_access_suggest_js'); // this callback does not seem to exist
        foreach ($areas as $area) {
            // Add meta boxes
            add_meta_box('wpcf-access-' . $area['id'], $area['name'],
                    array(__CLASS__, 'wpcf_access_post_meta_box'), $post->post_type, 'advanced',
                    'high', array($area, $roles));
        }
    }
}

/**
 * Renders meta boxes.
 *
 * @param \WP_Post $post
 * @param array $args
 */
public static function wpcf_access_post_meta_box( $post, $args ) {
    $access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
    $meta = $access_settings->getAccessMeta($post->ID); //get_post_meta($post->ID, '_types_access', true);
    $area = $args['args'][0];
    $roles = $args['args'][1]; //wpcf_get_editable_roles();
    $output = '';
    $groups = array();
    $groups = apply_filters('types-access-show-ui-group', $groups, $area['id']);
    foreach ($groups as $group) {
        $output .= '<div class="wpcf-access-type-item">';
        $output .= '<div class="wpcf-access-mode">';
        $caps = array();
        $caps = apply_filters('types-access-show-ui-cap', $caps, $area['id'],
                $group['id']);
        $saved_data = array();
        foreach ($caps as $cap_slug => $cap) {
            if (isset($cap['default_role'])) {
                $caps[$cap_slug]['role'] = $cap['role'] = $cap['default_role'];
            }
            $saved_data[$cap['cap_id']] =
                        is_array($meta) && isset($meta[$area['id']][$group['id']]['permissions'][$cap['cap_id']]) ?
                        $meta[$area['id']][$group['id']]['permissions'][$cap['cap_id']] : array('role' => $cap['role']);
        }
        if (isset($cap['style']) && $cap['style'] == 'dropdown') {

        } else {

            $output .= \OTGS\Toolset\Access\Viewmodels\PermissionsGui::get_instance()->permissions_table($roles, $saved_data, $caps,
                    $area['id'], $group['id']);
        }
        $output .= '</div>';
        $output .= '</div>';
    }
    echo $output;
}

/**
 * Save post hook.
 *
 * @param int $post_id
 */
public static function wpcf_access_post_save( $post_id )
{
	$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
	$areas = $access_settings->wpcf_access_get_areas();
    foreach ($areas as $area) {
        $groups = array();
        $groups = apply_filters('types-access-show-ui-group', $groups,
                $area['id']);
        foreach ($groups as $group) {
            $caps = array();
            $caps = apply_filters('types-access-cap', $caps, $area['id'],
                    $group['id']);
            foreach ($caps as $cap) {
                do_action('types-access-process-ui-result', $area['id'],
                        $group['id'], $cap['cap_id']);
            }
        }
    }
    if ( isset($_POST['types_access']) && !empty($_POST['types_access'])) {
        $access_settings->updateAccessMeta($post_id, sanitize_text_field($_POST['types_access']));
    } else {
        $access_settings->deleteAccessMeta($post_id);
    }
}

/**
 * Post edit page hook.
 */
public static function wpcf_access_admin_post_page_load_hook()
{
    if (!current_user_can('edit_posts')) {
        add_action('admin_footer', array(__CLASS__, 'wpcf_access_admin_edit_post_js'));
    }
}

/**
 * Post edit page JS.
 */
public static function wpcf_access_admin_edit_post_js()
{
    $preview_txt = addslashes(__("Preview might not work. Try right clicking on button and select 'Open in new tab'.",
                    'wpcf-access'));

    ?>
    <script type="text/javascript">
        jQuery(function(){
            jQuery('#post-preview').after('<div style="color:Red;clear:both;"><?php echo $preview_txt; ?></div>');
        });
    </script>
    <?php
}

/**
 * Post edit page JS.
 */
public static function wpcf_access_post_no_publish_js()
{

    ?>
    <script type="text/javascript">
        jQuery(function(){
            jQuery('#publish').attr('disabled', 'disabled').attr('readonly', 'readonly');
        });
    </script>
    <?php
}

}
// init on load
Access_Post_Helper::init();

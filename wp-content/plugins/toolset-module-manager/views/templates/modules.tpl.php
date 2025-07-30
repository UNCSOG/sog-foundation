<?php
// deny access
if( !defined('ABSPATH') ) die('Security check');
if(!current_user_can(MODMAN_CAPABILITY)) die('Access Denied');

if ( ! function_exists( 'modman_validated_module_data' ) ) {
    // auxilliary rendering functions
    function modman_validated_module_data($module_data) {

        //Custom Post Types
        if (defined('_TYPES_MODULE_MANAGER_KEY_')) {
            $types_module_manager_key=_TYPES_MODULE_MANAGER_KEY_;
        } else {
            $types_module_manager_key='types';
        }

        //Types Groups
        if (defined('_GROUPS_MODULE_MANAGER_KEY_')) {
            $groups_module_manager_key=_GROUPS_MODULE_MANAGER_KEY_;
        } else {
            $groups_module_manager_key='groups';
        }

        //Taxonomies
        if (defined('_TAX_MODULE_MANAGER_KEY_')) {
            $tax_module_manager_key=_TAX_MODULE_MANAGER_KEY_;
        } else {
            $tax_module_manager_key='taxonomies';
        }

        if ((is_array($module_data)) && (!(empty($module_data)))) {

            //Handle Types
            if (isset($module_data['types'])) {
                $types=$module_data['types'];
                if ((is_array($types)) && (!(empty($types)))) {

                    //Loop through the Types module and ensure ID is correct
                    foreach ($types as $posttype_looped=>$posttype_info) {
                        if (isset($posttype_info['id'])) {
                            $post_type_info_id_data=$posttype_info['id'];
                            if (!(strpos($post_type_info_id_data, '12'.$types_module_manager_key.'21') !== false )) {
                                //Not correct, adjust
                                $post_type_id_info='12' . $types_module_manager_key . '21' . $posttype_looped;
                                $module_data['types'][$posttype_looped]['id']=$post_type_id_info;
                            }
                        }
                    }
                }
            }

            //Handle Groups
            if (isset($module_data['groups'])) {
                $groups=$module_data['groups'];
                if ((is_array($groups)) && (!(empty($groups)))) {
                    //Loop through the Groups module and ensure ID is correct
                    foreach ($groups as $groups_looped=>$group_info) {
                        if (isset($group_info['id'])) {
                            $group_info_id_data=$group_info['id'];
                            if (!(strpos($group_info_id_data, '12'.$groups_module_manager_key.'21') !== false )) {
                                //Retrieved Groups ID
                                $groups_id=get_typesgroupsid_by_slug($group_info_id_data);
                                if ($groups_id) {
                                    $group_id_info='12' . $groups_module_manager_key . '21' . $groups_id;
                                    $module_data['groups'][$groups_looped]['id']=$group_id_info;
                                }
                            }
                        }
                    }
                }
            }

            //Handle Taxonomies
            if (isset($module_data['taxonomies'])) {
                $taxonomies=$module_data['taxonomies'];
                if ((is_array($taxonomies)) && (!(empty($taxonomies)))) {

                    //Loop through the Tax module and ensure ID is correct
                    foreach ($taxonomies as $tax_looped=>$tax_info) {
                        if (isset($tax_info['id'])) {
                            $tax_info_id_data=$tax_info['id'];

                            if (!(strpos($tax_info_id_data, '12'.$tax_module_manager_key.'21') !== false )) {
                                //Not correct, adjust
                                $tax_id_info='12' . $tax_module_manager_key . '21' . $tax_looped;
                                $module_data['taxonomies'][$tax_looped]['id']=$tax_id_info;
                            }
                        }
                    }
                }
            }
        }

        return $module_data;
    }



    function get_typesgroupsid_by_slug($group_info_id_data) {
        global $wpdb;
        $posts_table=$wpdb->prefix."posts";
        $typesgroups_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $posts_table WHERE     post_name = %s AND post_type ='wp-types-group'",$group_info_id_data));

        if (!(empty($typesgroups_id))) {
            return $typesgroups_id;
        } else {
            return FALSE;
        }
    }
    function modman_list_module_elements( $module, $module_data, &$cnt, &$items )
    {
        $modid=preg_replace('/\s+/', '_', $module);
        $output='';
        ob_start();?>
        <div id='<?php echo $modid; ?>' class='modules-sortables'><?php
        if (!empty($module_data))
        {
            $module_data= modman_validated_module_data($module_data);
            foreach ($module_data as $plugin=>$elements)
            {
                // bypass internal data
                if ('__module_info__'==$plugin) continue;

                $style='';
                $icon = '';
                /*  Instead of adding inline styles we should add an additional class to element,
                    and define different icons for different Classes in CSS file.
                    We should avoid inline styles unless they are really needed.
                    In this case they are not. */
                if ( isset($items[$plugin]['info']['icon']) )
                    $style="style='padding-left:23px;background:url({$items[$plugin]['info']['icon']}) no-repeat 5px 50%'";
                if (isset($items[$plugin]['info']['icon_css']))
                    $icon = $items[$plugin]['info']['icon_css'];

                if (!empty($elements))
                {
                    foreach ($elements as $ii=>$element)
                    {
                        $item_available=false;
                        if (!empty($items[$plugin]) && !empty($items[$plugin]['items']))
                        {
                            foreach ($items[$plugin]['items'] as $_item_)
                            {
                                // current module element is available in currently registered items
                                // Fix Notice: Undefined index: id
                                // Make sure they are set!

                                if ((isset($element['id'])) && (isset($_item_['id']))) {
                                    if ($_item_['id']==$element['id'])
                                    {
                                        $item_available=true;

                                        // display current element details, bypass frozen element details
                                        if (isset($_item_['title']))
                                        {
                                            $elements[$ii]['title'] = $_item_['title'];
                                            $element['title'] = $_item_['title'];
                                        }
                                        if (isset($_item_['details']))
                                        {
                                            $elements[$ii]['details'] = $_item_['details'];
                                            $element['details'] = $_item_['details'];
                                        }

                                        break;
                                    }
                                }

                            }
                        }

                        if ((isset($element['title'])) && (!(empty($element['title'])))) {

                            $item_available=true;

                        }

                        $class='module';
                        if (!$item_available)
                            $class.=' item-not-available';

                        //Fix notices on undefine id

                        if (isset($element['id'])) {
                           $id=$element['id'].'_'.++$cnt;
                        }

                        ?>
                        <?php if ((isset($id)) && ($class!='module item-not-available')) {?>
                        <div id='<?php echo $id; ?>' class='<?php echo $class; ?>'>
                           <?php
                              if ((isset($element['title'])) && (!(empty($element['title'])))) {
                           ?>
                            <div class="module-top">
                                <div class="module-title-action"></div>
                                <div class="module-title">

                                    <h4 title="<?php echo esc_attr($element['title']); ?>">
                                        <i class="<?php echo $icon; ?>"></i>
                                        <span><?php echo $element['title'] ?></span>
                                        <span class="in-module-title"></span>
                                        <?php if(!$item_available): ?>
                                        <i class="icon-question-sign"></i>
                                        <?php endif; ?>
                                    </h4>
                                </div>
                                <a href='javascript:;' title="<?php echo esc_attr(__('Click for details','module-manager')); ?>" class="sidebar-name-arrow"></a>
                            </div>
                            <?php } ?>
                            <div class="module-inside">
                                <div style='display:none;height:0' class='module-data'>
                                    <span style='display:none' class='module-plugin'><?php echo $plugin; ?></span>
                                    <span style='display:none' class='module-item'><?php if (isset($element['id'])) { echo $element['id']; } ?></span>
                                </div>
                            </div>
                            <div class="module-description"><?php
                                if (isset($element['details']))
                                {
                                    echo stripslashes($element['details']);
                                }
                            ?></div>
                        </div>
                        <?php
                        }
                    }
                }
            }
        }
        ?></div>
        <?php
        $output.=ob_get_clean();
        echo $output;
    }

    function modman_list_items( $items, $plugin, $icon, $icon_css = null, $echo = true, $draggable = true )
    {
        $output='';
        ob_start();
        foreach ($items as $item)
        {
            $id=$item['id'].'_'.'__cnt__';
            $style='';
            $icon_style = '';
            if (isset($icon))
                $style="style='padding-left:23px;'";
            if( isset($icon_css) && null !== $icon_css )
                $icon_style = $icon_css;
            $module_classes = 'module';
            if ( ! $draggable ) {
                $module_classes .= ' non-draggable';
            }
            ?>
            <div id='<?php echo $id; ?>' class="<?php echo $module_classes; ?>" >
                <div class="module-top">
                    <div class="module-title-action"></div>
                    <div class="module-title" <?php //echo $style; ?>>

                    <h4 title="<?php echo esc_attr($item['title']); ?>"><i class="<?php echo $icon_style; ?>"></i><span><?php echo $item['title'] ?></span><span class="in-module-title"></span></h4>
                    </div>
                    <a href='javascript:;' title="<?php echo esc_attr(__('Click for details','module-manager')); ?>" class="sidebar-name-arrow"></a>
                </div>
                <div class="module-inside">
                    <div style='display:none;height:0' class='module-data'>
                        <span style='display:none' class='module-plugin'><?php echo $plugin; ?></span>
                        <span style='display:none' class='module-item'><?php echo $item['id'] ?></span>
                    </div>
                </div>
                <div class="module-description"><?php
                if (isset($item['details']))
                {
                    echo stripslashes($item['details']);
                }
                ?></div>
            </div><?php
        }
        $output.=ob_get_clean();
        if ( $echo ) {
            echo $output;
        } else {
            return $output;
        }
    }
}
?>
<?php
    // Why? because functions and HTML code should be in different files, and I need only the functions.
    if ( ! isset( $onlyfunctions ) ) {
?>
<!-- templates -->
<script id='module-template' type='text/module-template'>
    <div class="modules-holder-wrap">
        <div class="sidebar-name">
            <div class="sidebar-name-arrow"><br /></div>
            <h3>%%__MOD_NAME__%%</h3>
        </div>
        <div class="modules-holder-wrap-inside">
            <div id='%%__MOD_ID__%%' class='modules-sortables'>
            </div>
            <div class="module-controls">
            <div class="modulemanager-description">
                <div class="module-title"><h4><?php _e('Module Description','module-manager'); ?><a href='javascript:;' title="<?php echo esc_attr(__('Click for description','module-manager')); ?>" style='position:relative;float:none;margin:0 0 0 10px;display:inline-block;vertical-align:middle;' class="sidebar-name-arrow"></a></h4></div>
                <textarea class="module-info-description" rows="4"></textarea>
            </div>
                <a href='javascript:;' class='button module-remove'><?php _e('Remove','module-manager'); ?></a>
                <a href='javascript:;' class='button-primary module-export'><?php _e('Export','module-manager'); ?></a>
            </div>
        </div>
    </div>
</script>
<!-- /templates -->

<div id='modman-needs-save' class='updated' style="display:none;" ><p><?php _e('Settings changed, you need to resave before leaving the page','module-manager'); ?></p></div>
<div class="row">
<div class="modules-liquid-left row">
    <div id="modules-left" class="row">
        <div id='available-modules' class='modules-holder-wrap'>
            <h3><?php _e('Available Items','module-manager'); ?></h3>
            <div class="module-holder">
                <div class="description">
                    <p><?php _e('Drag elements from here to a module on the right to group them to modules. Drag elements back here to remove them from modules.','module-manager'); ?></p>
                    <p class="modman-search-wrap">
                        <i class="icon-search"></i><input type='text' placeholder='<?php echo esc_attr(__('Search','module-manager')); ?>' class='modman-search' value='' onkeyup="ModuleManager.filter(this.value);" />
                    </p>
                </div>

                <div id="module-list"><?php
                foreach ( $items as $section => $_items ) {
	                if ( 'Demo Content' === $_items['info']['title'] ) continue;
                	?>
                    <div class="modman-module-section">
                        <h4><?php echo esc_html( $_items['info']['title'] ); ?></h4>
                        <div class="modman-module-section-inner">
                        <?php modman_list_items( $_items['items'], $section, (isset($_items['info']['icon']))?$_items['info']['icon']:null, (isset($_items['info']['icon_css']))?$_items['info']['icon_css']:null ); ?>
                        </div>
                    </div>
                <?php }  ?>
                <br class="clear" />
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modules-liquid-right" style='position:relative;'>
    <div id="modules-right">
        <h3><?php _e('Modules','module-manager'); ?></h3>
        <?php
        $i = 0;
        $cnt=0;
        if ($modules && !empty($modules))
        {
            foreach ( $modules as $module_title=>$mdata )
            {
                $wrap_class = 'modules-holder-wrap';
                if ( $i ) $wrap_class .= ' closed'; ?>
                <div class="<?php echo esc_attr( $wrap_class ); ?>">
                    <div class="sidebar-name">
                        <div class="sidebar-name-arrow"></div>
                        <h3><?php echo esc_html( $module_title ); ?></h3>
                    </div>
                    <div class="modules-holder-wrap-inside">
                        <?php modman_list_module_elements( $module_title, $mdata, $cnt, $items ); ?>
                        <div class="module-controls">
                            <div class="modulemanager-description">
                                <div class="module-title">
                                    <h4><?php _e('Module Description','module-manager'); ?>
                                        <a href='javascript:;' title="<?php echo esc_attr(__('Click for description','module-manager')); ?>" style='' class="sidebar-name-arrow"></a>
                                    </h4>
                                </div>
                                <textarea class="module-info-description" rows="4"><?php
                                    if (isset($mdata[MODMAN_MODULE_INFO]) && isset($mdata[MODMAN_MODULE_INFO]['description']))
                                    {
                                        echo stripslashes($mdata[MODMAN_MODULE_INFO]['description']);
                                    }
                                ?></textarea>
                            </div>
                            <?php ;
                                if ( isset( $mdata['__module_info__']['documentation'] ) && ! empty( $mdata['__module_info__']['documentation'] ) ) {
                                    echo '<p><a class="modman_outer_link" href="' . $mdata['__module_info__']['documentation'] . '" target="_blank">'. __('View module documentation','module-manager') . '</a></p>';
                                }
                            ?>
                            <a href='javascript:;' class='button module-remove'><?php _e('Remove','module-manager'); ?></a>
                            <a href='javascript:;' class='button button-primary module-export'><?php _e('Export','module-manager'); ?></a>
                        </div>
                    </div>
                </div>
            <?php $i++;
            }
        } ?>
    </div>

    <div class="modules-main-controls-container">
        <?php wp_nonce_field('modman-save-modules-action', 'modman-save-modules-field'); ?>
        <a href='javascript:;' onclick="ModuleManager.addNew();"  class='button button-large modman-add-module'><?php _e('Add New', 'module-manager'); ?></a>
        <div class="ajax-feedback-container">
            <img src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" class="ajax-feedback" title="" alt="" />
            <input type='button' class='button button-primary button-large' value='<?php echo esc_attr(__('Save Modules', 'module-manager')); ?>' onclick="ModuleManager.save();" />
        </div>
    </div>

</div>
</div>
<br class="clear" />
<input id='__module_cnt__' type='hidden' value='<?php echo $cnt; ?>' />
<?php } ?>

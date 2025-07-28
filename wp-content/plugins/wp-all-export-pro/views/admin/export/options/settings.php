<?php
if (!defined('ABSPATH')) {
    die();
}
?>
<div class="wpallexport-collapsed wpallexport-section wpallexport-settings-page">
    <div class="wpallexport-content-section">
        <div class="wpallexport-collapsed-header">
            <h3><?php esc_html_e('Advanced Options', 'wp_all_export_plugin'); ?></h3>
        </div>
        <div class="wpallexport-collapsed-content">
            <div class="wpallexport-collapsed-content-inner">
                <table class="form-table">
                    <tr>
                        <td colspan="3">

                            <div class="wpallexport-no-realtime-options <?php if ($post['enable_real_time_exports']) { ?> wpae-hidden <?php } ?>"  >
                                <div class="input">
                                    <label for="records_per_request"><?php esc_html_e('In each iteration, process', 'wp_all_export_plugin'); ?>
                                        <input type="text" name="records_per_iteration" class="wp_all_export_sub_input records_per_iteration"
                                               value="<?php echo esc_attr($post['records_per_iteration']) ?>"/> <?php esc_html_e('records', 'wp_all_export_plugin'); ?>
                                    </label>
                                    <span>
                                    <a href="#help" class="wpallexport-help upper"
                                       title="<?php esc_html_e('WP All Export must be able to process this many records in less than your server\'s timeout settings. If your export fails before completion, to troubleshoot you should lower this number.', 'wp_all_export_plugin'); ?>">?</a>
							    </span>
                                </div>

                            </div>

                            <?php
                            $cpt_initial = $post['cpt'];
                            $cpt_name = is_array($post['cpt']) ? reset($post['cpt']) : $post['cpt'];
                            if ('advanced' !== $post['export_type']) {
                                if ($cpt_name !== 'taxonomies') {

                                    if ($cpt_name === 'users') {
                                        $cpt_name = 'user';
                                    }

                                    $display_verb = 'created';
                                    $display_cpt_name = $cpt_name;
                                    $tooltip_cpt_name = strtolower(wp_all_export_get_cpt_name($cpt_initial));

                                    if ($display_cpt_name === 'shop_order') {
                                        $display_cpt_name = 'WooCommerce Order';
                                        $display_verb = 'completed';
                                    }

                                    if ($display_cpt_name === 'shop_customer') {
                                        $display_cpt_name = 'WooCommerce Customer';
                                        $display_verb = 'created';
                                    }

                                    if($display_cpt_name === 'custom_wpae-gf-addon') {
                                        $display_cpt_name = 'Gravity Forms Entry';
                                    }

                                    if ($display_cpt_name === 'comments') {
                                        $display_cpt_name = 'comment';
                                    }

                                    ?>
                                    <div class="input">

                                        <input type="hidden" id="wpae-post-name" value="<?php echo $display_cpt_name; ?>" />
                                        <input type="hidden" name="enable_real_time_exports" value="0"/>
                                        <input type="checkbox"
                                               id="enable_real_time_exports" <?php if ((isset($post['xml_template_type']) && $post['xml_template_type'] == XmlExportEngine::EXPORT_TYPE_GOOLE_MERCHANTS) || $cpt_name === 'shop_customer') { ?> disabled="disabled" <?php } ?>
                                               name="enable_real_time_exports"
                                               value="1" <?php echo $post['enable_real_time_exports'] ? 'checked="checked"' : '' ?> />
                                        <label for="enable_real_time_exports"><?php esc_html_e('Export each ' . esc_html($display_cpt_name) . ' in real time as they are ' . esc_html($display_verb), 'wp_all_export_plugin') ?></label>
                                        <span>

                                        <?php
                                        if (isset($post['xml_template_type']) && $post['xml_template_type'] == XmlExportEngine::EXPORT_TYPE_GOOLE_MERCHANTS) { ?>
                                            <a href="#help" class="wpallexport-help"
                                            title="<?php esc_html_e('This feature is not available for GMC exports.', 'wp_all_export_plugin'); ?>"
                                            >?</a>
                                        <?php } else if ('shop_customer' === $cpt_name) { ?>
                                            <a href="#help" class="wpallexport-help"
                                            title="<?php esc_html_e('This feature is not available for customer exports. A Users export with user role filter should be used instead.', 'wp_all_export_plugin'); ?>"
                                            >?</a>
                                        <?php } else { ?>

                                        <?php } ?>

                                        </span>

                                    </div>

                                    <div class="input wpallexport_realtime_show_bom <?php if (!$post['enable_real_time_exports']) { ?> wpae-hidden <?php } ?>" >
                                        <div>
                                            <p>
                                                This export will run every time a new
                                                <?php echo esc_html($display_cpt_name); ?>
                                                is added that meets the filter requirements you've configured.
                                            </p>

                                            <div class="admin-page-link">
                                                <?php echo wp_all_export_generate_link('Read more about real time exports', 'https://www.wpallimport.com/documentation/how-to-run-real-time-exports/');?>
                                            </div>

                                        </div>
                                    </div>
                                <?php }
                            } ?>

                            <div class="wpallexport-no-realtime-options <?php if ($post['enable_real_time_exports']) { ?> wpae-hidden <?php } ?>">
                                <div class="input">
                                    <input type="hidden" name="export_only_new_stuff" value="0"/>
                                    <input type="checkbox" id="export_only_new_stuff" name="export_only_new_stuff"
                                           value="1" <?php echo $post['export_only_new_stuff'] ? 'checked="checked"' : '' ?> />
                                    <label for="export_only_new_stuff"><?php printf(esc_html__('Only export %s once', 'wp_all_export_plugin'), empty($post['cpt']) ? esc_html__('records', 'wp_all_export_plugin') : wp_all_export_get_cpt_name($post['cpt'], 2, $post)); ?></label>
                                    <span>
                                    <a href="#help" class="wpallexport-help"
                                       title="<?php esc_html_e('If re-run, this export will only include records that have not been previously exported.', 'wp_all_export_plugin'); ?>">?</a>
							    </span>
                                </div>
                                <div class="input">
                                    <input type="hidden" name="export_only_modified_stuff" value="0"/>
                                    <input type="checkbox"
                                        <?php if (in_array('users', $post['cpt']) || in_array('taxonomies', $post['cpt']) || in_array('shop_customer', $post['cpt']) || ($post['export_type'] === 'advanced' && $post['wp_query_selector'] === 'wp_user_query')) { ?> disabled="disabled" <?php } ?>
                                           id="export_only_modified_stuff" name="export_only_modified_stuff"
                                           value="1" <?php echo $post['export_only_modified_stuff'] ? 'checked="checked"' : '' ?> />
                                    <label for="export_only_modified_stuff"><?php printf(__('Only export %s that have been modified since last export', 'wp_all_export_plugin'), empty($post['cpt']) ? esc_html__('records', 'wp_all_export_plugin') : esc_html__(wp_all_export_get_cpt_name($post['cpt'], 2, $post))); ?></label>
                                    <span>
                                    <a href="#help" class="wpallexport-help"
                                        <?php
                                        if (in_array('users', $post['cpt']) || ($post['export_type'] === 'advanced' && $post['wp_query_selector'] === 'wp_user_query')) { ?>
                                            title="<?php esc_html_e('This feature is not available for user exports.', 'wp_all_export_plugin'); ?>"
                                        <?php } else if (in_array('taxonomies', $post['cpt'])) { ?>
                                            title="<?php esc_html_e('This feature is not available for taxonomies exports.', 'wp_all_export_plugin'); ?>"
                                        <?php } else if (in_array('shop_customer', $post['cpt'])) { ?>
                                            title="<?php esc_html_e('This feature is not available for customer exports.', 'wp_all_export_plugin'); ?>"
                                        <?php } else { ?>
                                            title="<?php esc_html_e('If re-run, this export will only include records that have been modified since last export run.', 'wp_all_export_plugin'); ?>"
                                        <?php } ?>
                                    >?</a>
                                </span>
                                </div>
                            </div>

                            <?php if (in_array('shop_customer', $post['cpt'])) { ?>
                                <div class="input">
                                    <input type="hidden" name="export_only_customers_that_made_purchases"
                                           value="0"/>
                                    <input type="checkbox" id="export_only_customers_that_made_purchases"
                                           name="export_only_customers_that_made_purchases"
                                           value="1" <?php echo $post['export_only_customers_that_made_purchases'] ? 'checked="checked"' : '' ?> />
                                    <label for="export_only_customers_that_made_purchases"><?php esc_html_e('Only export customers who have made a purchase', 'wp_all_export_plugin'); ?></label>
                                    <span>
                                            <a href="#help" class="wpallexport-help"
                                               title="<?php esc_html_e('If enabled, only customers who have actually made purchases will be exported.', 'wp_all_export_plugin'); ?>">?</a>
                                        </span>
                                </div>
                            <?php } ?>
                            <div class="input">
                                <input type="hidden" name="include_bom" value="0"/>
                                <input type="checkbox" id="include_bom" name="include_bom"
                                       value="1" <?php echo $post['include_bom'] ? 'checked="checked"' : '' ?> />
                                <label for="include_bom"><?php esc_html_e('Include BOM to enable non-ASCII characters in Excel', 'wp_all_export_plugin') ?></label>
                                <span>
                                    <a href="#help" class="wpallexport-help"
                                       title="<?php esc_html_e('The BOM will help some programs like Microsoft Excel read your export file if it contains non-ASCII characters. These can include curly quotation marks or non-English characters such as umlauts.', 'wp_all_export_plugin'); ?>">?</a>
                                </span>
                            </div>
                            <div class="wpallexport-no-realtime-options <?php if ($post['enable_real_time_exports']) { ?> wpae-hidden<?php } ?>" >
                                <div class="input">
                                    <input type="hidden" name="creata_a_new_export_file" value="0"/>
                                    <input type="checkbox" id="creata_a_new_export_file" name="creata_a_new_export_file"
                                           value="1" <?php echo $post['creata_a_new_export_file'] ? 'checked="checked"' : '' ?> />
                                    <label for="creata_a_new_export_file"><?php esc_html_e('Create a new file each time export is run', 'wp_all_export_plugin') ?></label>
                                    <span>
                                    <a href="#help" class="wpallexport-help"
                                       title="<?php esc_html_e('If disabled, the export file will be overwritten every time this export run.', 'wp_all_export_plugin'); ?>">?</a>
							        </span>
                                </div>

                                <div class="input">
                                    <input type="hidden" name="do_not_generate_file_on_new_records" value="0"/>
                                    <input type="checkbox" id="do_not_generate_file_on_new_records"
                                           name="do_not_generate_file_on_new_records"
                                           value="1" <?php echo $post['do_not_generate_file_on_new_records'] ? 'checked="checked"' : '' ?> />
                                    <label for="do_not_generate_file_on_new_records"><?php esc_html_e('Do not generate an export file if there are no records to export', 'wp_all_export_plugin') ?></label>
                                    <span>
                                    <a href="#help" class="wpallexport-help"
                                       title="<?php esc_html_e('If there are no records, an empty export file won\'t be generated.', 'wp_all_export_plugin'); ?>">?</a>
							        </span>
                                </div>

                                <?php if ($post['export_to'] == 'csv') { ?>
                                    <div class="input">
                                        <input type="hidden" name="split_large_exports" value="0"/>
                                        <input type="checkbox" id="split_large_exports" name="split_large_exports"
                                               class="switcher"
                                               value="1" <?php echo $post['split_large_exports'] ? 'checked="checked"' : '' ?> />
                                        <label for="split_large_exports"><?php esc_html_e('Split large exports into multiple files', 'wp_all_export_plugin') ?></label>
                                        <span class="switcher-target-split_large_exports pl17">
                                            <div class="input pl17">
                                                <label for="records_per_request"><?php esc_html_e('Limit export to', 'wp_all_export_plugin'); ?></label> <input
                                                        type="text" name="split_large_exports_count"
                                                        class="wp_all_export_sub_input"
                                                        value="<?php echo esc_attr($post['split_large_exports_count']) ?>"/> <?php esc_html_e('records per file', 'wp_all_export_plugin'); ?>
                                            </div>
                                        </span>
                                    </div>
                                    <?php
                                }
                                ?>

                                <?php
                                if (current_user_can(PMXE_Plugin::$capabilities)) {
                                    ?>
                                    <div class="input">
                                        <input type="hidden" name="allow_client_mode" value="0"/>
                                        <input type="checkbox" id="allow_client_mode" name="allow_client_mode"
                                               value="1" <?php echo $post['allow_client_mode'] ? 'checked="checked"' : '' ?> />
                                        <label for="allow_client_mode"><?php esc_html_e('Allow non-admins to run this export in Client Mode', 'wp_all_export_plugin') ?></label>
                                        <span>
                                    <a href="#help" class="wpallexport-help"
                                       title="<?php esc_html_e('When enabled, users with access to Client Mode will be able to run this export and download the export file. Go to All Export > Settings to give users access to Client Mode'); ?>">?</a>
							    </span>
                                    </div>
                                <?php } ?>
                            </div>

                            <br>
                            <hr>

                            <?php
                            if (current_user_can(PMXE_Plugin::$capabilities)) {
                                ?>
                                <p class="wpae-align-right">
                                <div class="input">
                                    <label class="save_import_as_label" for="save_import_as"><?php esc_html_e('Export Name:', 'wp_all_export_plugin'); ?></label>
                                    <input class="friendly-name" type="text" name="friendly_name"
                                           title="<?php esc_html_e('Save Export Name...', 'pmxi_plugin') ?>"
                                           value="<?php echo wp_all_export_clear_xss($post['friendly_name'] ? esc_attr($post['friendly_name']) : esc_attr($this->getFriendlyName($post))) ?>"/>
                                </div>
                                </p>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>	
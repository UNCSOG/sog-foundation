<?php
function asBytes($ini_v) {
    $ini_v = trim($ini_v);
    $s = array('g'=> 1<<30, 'm' => 1<<20, 'k' => 1<<10);
    return intval($ini_v) * ($s[strtolower(substr($ini_v,-1))] ? $s[strtolower(substr($ini_v,-1))] : 1);
}
?>

<div class="ddl-settings-wrap">
    <div class="ddl-settings">

        <div class="ddl-settings-header">
            <h3><?php _e('Import layouts from local file', 'ddl-layouts'); ?></h3>
        </div>

        <div class="ddl-settings-content">

            <h4><?php _e('Settings', 'ddl-layouts'); ?>:</h4>

            <form method="post" action="<?php echo admin_url('admin.php'); ?>?page=toolset-export-import&tab=dd_layout_import_export" enctype="multipart/form-data" name="import-layouts" id="import-layouts">
                <ul>
                    <li>
                        <input id="layouts-overwrite" type="checkbox" name="layouts-overwrite"/>
                        <label
                            for="layouts-overwrite"><?php _e('Overwrite any layout if it already exists', 'ddl-layouts'); ?></label>
                    </li>
                    <li>
                        <input id="layouts-delete" type="checkbox" name="layouts-delete"/>
                        <label
                            for="layouts-delete"><?php _e('Delete any existing layouts that are not in the import', 'ddl-layouts'); ?></label>
                    </li>

                    <li>
                        <input id="overwrite-layouts-assignment" type="checkbox"
                               name="overwrite-layouts-assignment"/>
                        <label
                            for="overwrite-layouts-assignment"><?php _e('Overwrite layout assignments', 'ddl-layouts'); ?></label>
                    </li>
                </ul>

                <h4><?php _e('Select a .zip, .ddl, .json or .css file to import from your computer', 'ddl-layouts'); ?>
                    :</h4>

                <p>
                    <label for="upload-layouts-file"><?php _e('Upload file', 'ddl-layouts'); ?>:</label>
                    <input type="file" id="upload-layouts-file" name="import-file"/>
                </p>

                <p class="alignright">
                    <input id="layouts-show-log" type="checkbox" name="layouts-show-log" class="hidden" />
                    <label for="layouts-show-log" id="layouts-show-log-label" class="hidden"><?php _e('Show log', 'ddl-layouts'); ?></label>
                    <input id="ddl-import" class="button-primary" type="submit"
                           value="<?php _e('Import', 'ddl-layouts'); ?>" name="ddl-import"/>
                </p>
                <input type="hidden" value="dll_import_layouts" name="action" />
                <input type="hidden" value="<?php echo asBytes(ini_get('upload_max_filesize'))?>" id="import_max_upload_size"/>
                <?php wp_nonce_field('layouts-import-nonce', 'layouts-import-nonce'); ?>
            </form>

            <div class="import-layouts-messages"></div>

        </div>
    </div>
</div>

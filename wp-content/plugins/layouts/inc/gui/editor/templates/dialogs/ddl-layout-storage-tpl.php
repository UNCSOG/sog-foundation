<script type="text/html" id="ddl-layout-storage-tpl">
    <div id="js-dialog-dialog-container">
        <div class="ddl-dialog-content" id="js-dialog-content-dialog">
            <p><?php _e( 'This is how the Layout is stored in the database. If you want to copy this layout to a different page, select the entire text and paste it into a different layout. Don\'t edit the text manually, as it will most likely result in a broken layout.', 'ddl-layouts'); ?></p>
            <textarea rows="12" class="ddl-json-object-textarea" id="js-layouts-storage-json-object"></textarea>
            <div class="js-layout-storage-confirmation-area notice notice-warning notice-alt" style="display:none;">
                <div class="js-layout-storage-info-message"></div>
                <br>
                <input type="checkbox" name="confirm_layout_storage_update" id="ddl-confirm-layout-storage-update" class="js-confirm-layout-storage-update" value="1">
                <label for="ddl-confirm-layout-storage-update"><?php _e('I Understand');?></label>
            </div>
        </div>
    </div>
</script>
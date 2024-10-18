<div class="wrap">
    <h1>
        <?php _e('Layouts CSS and JS Editor','ddl-layouts');?>
    </h1>
    <div class="js-css-editor-message-container js-ddl-message-container dd-message-container"></div>
    <div id="editor_tabs">
        <ul>
            <li><a href="#css_tab"><span><?php _e('CSS Editor','ddl-layouts');?></span></a></li>
            <li><a href="#js_tab"><span><?php _e('JavaScript Editor','ddl-layouts');?></span></a></li>
        </ul>
        <div id="css_tab">
             <div class="ddl-settings-wrap ddl-css-edit-wrap">
                <div class="ddl-settings-wrap ddl-css-header-wrap">
                    <div class="ddl-settings">
                        <div class="ddl-settings-content">
                            <p><?php _e('This is a CSS editor. You can add CSS rules here and they will be included on every page in the site\'s front-end. This is like editing the theme\'s "styles.css", just without having to edit physical files.', 'ddl-layouts'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="ddl-settings">

                    <div class="ddl-settings-content ddl-css-edit-content">

                        <div class="js-code-editor code-editor layout-css-editor">
                            <div class="code-editor-toolbar js-code-editor-toolbar">
                                <ul>
                                    <li></li>
                                </ul>
                            </div>
                            <!-- THERE SHOULDN'T BE ANY NEW LINE IN TEXT AREA TAG OTHERWISE CREATES A VISUAL BUG -->
                            <ul class="codemirror-bookmarks js-codemirror-bookmarks"></ul>
                            <textarea name="ddl-default-css-editor"
                                      id="ddl-default-css-editor"
                                      class="js-ddl-css-editor-area ddl-default-css-editor"><?php WPDDL_CSSEditor::print_layouts_css(); ?></textarea>
                            <p class="wp-caption-text alignleft"><?php _e('CTRL+Space: Display class names and IDs.', 'ddl-layouts'); ?></p>
                        </div>

                        

                        <p class="update-button-wrap">
                            <span class="js-wpv-messages"></span>
                            <button class="js-layout-css-save button button-secondary" disabled="disabled">
                                <?php _e('Save', 'ddl-layouts'); ?>
                            </button>
                        </p>

                    </div>

                    <div class="clear"></div>
                </div>
            </div>
        </div>
        <div id="js_tab">
            <div class="ddl-settings-wrap ddl-css-edit-wrap">
                <div class="ddl-settings-wrap ddl-css-header-wrap">
                    <div class="ddl-settings">
                        <div class="ddl-settings-content">
                            <p><?php _e('This is a JavaScript editor. You can add JavaScript code here and they will be included on every page in the site\'s front-end. This is like editing the theme\'s "custom.js", just without having to edit physical files.', 'ddl-layouts'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="ddl-settings">

                    <div class="ddl-settings-content ddl-css-edit-content">



                        <div class="js-code-js-editor code-editor layout-css-editor">
                            <div class="code-editor-toolbar js-code-editor-toolbar">
                                <ul>
                                    <li></li>
                                </ul>
                            </div>
                            <!-- THERE SHOULDN'T BE ANY NEW LINE IN TEXT AREA TAG OTHERWISE CREATES A VISUAL BUG -->
                            <ul class="codemirror-bookmarks js-codemirror-bookmarks"></ul>
                            <textarea name="ddl-default-js-editor"
                                      id="ddl-default-js-editor"
                                      class="js-ddl-js-editor-area ddl-default-js-editor"><?php WPDDL_CSSEditor::print_layouts_js(); ?></textarea>
                        </div>

                        <p class="update-button-wrap">
                            <span class="js-wpv-messages"></span>
                            <button class="js-layout-js-save button button-secondary" disabled="disabled">
                                <?php _e('Save', 'ddl-layouts'); ?>
                            </button>
                        </p>

                    </div>

                    <div class="clear"></div>
                </div>
            </div>
        </div>
    </div>
    
</div>
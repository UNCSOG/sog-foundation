<script class="js-cell js-item-detail" type="text/html">

    <div class="item-details col{{ columnCount }} js-cell-details">

        <div class="item-header">
            <h4 class="js-item-header js-item-name">
                {{ cellName }}
            </h4>

            <div class="item-actions">
                <a href="#" class="js-close-cell-details">
                    <span class="item-close dashicons dashicons-no-alt js-item-close"> </span>
                </a>
            </div>
        </div>

        <# if (cellPreview) { #>
            <div class="item-description">
                <p class="js-element-preview-box-message message-container"></p>
                <p class="item-desc js-item-desc" data-name="{{ cellType }}">
                    {{ cellDescription }}
		            <p class="ddl-show-cell-details-option-wrap">
			            <input type="checkbox" name="ddl-do-not-show-cell-details" class="ddl-js-do-not-show-cell-details" id="ddl-do-not-show-cell-details">
			            <label for="ddl-do-not-show-cell-details"><?php _e( "Don't show this explanation (to enable again, go to Toolset->Settings->Layouts)", "ddl-layouts" );?></label>
		            </p>
                </p>

            </div>

            <div class="item-body class-{{ cellType }}">
                <p>
                    <img class="item-preview js-item-preview"
                         data-name="{{ cellType }}"
                         src="{{ cellPreview }}"
                         alt="{{ cellDescription }}"
                        >
                </p>
                <#
                    if( typeof DDLayout_settings_editor !== 'undefined' && +DDLayout_settings_editor.is_embedded === 1 && cellType === 'child-layout' ){#>

                    <a href="#" class="create-disabled-a js-show-cell-dialog js-item-details-data"
                       data-cell-type="{{cellType}}"
                       data-dialog-title-create="{{dialogTitleCreate}}"
                       data-dialog-title-edit="{{dialogTitleEdit}}"
                       data-allow-multiple="{{allowMultiple}}"
                       data-cell-name="{{cellName}}"
                       data-cell-description="{{cellDescription}}"
                       data-displays-post-content="{{displaysPostContent}}"
                       data-has-settings="{{hasSettings}}"
                       data-disabled="disabled"
                        >
                    <span class="item-insert-disabled js-item-insert"><?php _e('Insert cell', 'ddl-layouts'); ?>
                        <span class="dashicons dashicons-arrow-right-alt ddl-dialog-create-icon-arrow"></span>
                    </span>
                    </a>

                    <a href="#" class="ddl-open-promotional-message js-open-promotional-message promo-message-create-dialog js-open-promotional-message-button"><?php _e('Enable creating layouts', 'ddl-layouts'); ?></a>

                    <# } else{ #>

                        <a href="#" class="js-show-cell-dialog js-item-details-data"
                           data-cell-type="{{cellType}}"
                           data-dialog-title-create="{{dialogTitleCreate}}"
                           data-dialog-title-edit="{{dialogTitleEdit}}"
                           data-allow-multiple="{{allowMultiple}}"
                           data-cell-name="{{cellName}}"
                           data-cell-description="{{cellDescription}}"
                           data-displays-post-content="{{displaysPostContent}}"
                           data-has-settings="{{hasSettings}}"
                            >
                    <span class="item-insert js-item-insert"><?php _e('Insert cell', 'ddl-layouts'); ?>
                        <span class="dashicons dashicons-arrow-right-alt ddl-dialog-create-icon-arrow"></span>
                    </span>
                        </a>
                        <#   }
                            #>

            </div>
            <# } else { #>
                <div class="item-description item-description-no-preview">
                    <p class="js-element-preview-box-message message-container"></p>
                    <p class="item-desc js-item-desc" data-name="{{ cellType }}">
                        {{ cellDescription }}
                    </p>
                    <div class="item-body-no-preview">
                        <a href="#" class="js-show-cell-dialog js-item-details-data"
                           data-cell-type="{{cellType}}"
                           data-dialog-title-create="{{dialogTitleCreate}}"
                           data-dialog-title-edit="{{dialogTitleEdit}}"
                           data-allow-multiple="{{allowMultiple}}"
                           data-cell-name="{{cellName}}"
                           data-cell-description="{{cellDescription}}"
                           data-displays-post-content="{{displaysPostContent}}"
                           data-has-settings="{{hasSettings}}">
                            <span class="item-insert js-item-insert"><?php _e('Insert cell', 'ddl-layouts'); ?>
                                <span class="dashicons dashicons-arrow-right-alt ddl-dialog-create-icon-arrow"></span>
                            </span>
                        </a>
                    </div>
                </div>

                <# } #>

    </div>

</script>
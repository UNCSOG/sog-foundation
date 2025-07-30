<div id="wrapper-element-box-type">
    <div class="ddl-dialog" id="ddl-select-element-box">

        <div class="ddl-dialog-header">
            <h2 class="js-dialog-title"><?php _e( 'Layout cell types', 'ddl-layouts' ) ?></h2>
            <div class="ddl-create-dialog-search-wrap">
                <div class="ddl-create-dialog-search-wrap-inner">
                    <input class="js-cells-tree-search cells-tree-search" type="text"
                           data-default-val="<?php _e('Search', 'ddl-layouts'); ?>&hellip;"
                           data-message-container=".js-cells-tree-message"
                           data-target="#js-cells-tree"
                           value="<?php _e('Search', 'ddl-layouts'); ?>&hellip;"
                        ><i class="fa fa-search icon-search js-ddl-search-lens"></i>
                </div>
            </div>
            <i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
        </div>

        <div class="ddl-dialog-content ddl-dialog-element-select js-ddl-dialog-element-select">
            
    		<p class="js-element-box-message-container message-container"></p>

            <ul class="grid js-cells-grid cells-list-grid-wrap" id="js-cells-tree">

                <?php
                $category_count = 1;
                foreach ($this->cell_categories as $cell_category) :
                    include 'js/templates/ddl-create-cell-category-element.tpl.php';
                    $category_count++;
                    endforeach;
                ?>
            </ul>

            <div class="js-cells-tree-message" data-message-text="<?php _e( 'Nothing found', 'ddl-layouts' ); ?>"></div>
        </div> <!-- .ddl-dialog-element-select -->
        <?php wp_nonce_field('wp_nonce_edit_css', 'wp_nonce_edit_css'); ?>
    </div> <!-- .ddl-dialog -->
</div>
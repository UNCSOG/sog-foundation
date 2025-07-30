<?php
global $wpddlayout;
$cell_categories = $wpddlayout->get_cell_categories();
?>

<div id="wrapper-element-box-type">

    <div class="ddl-dialog" id="ddl-select-element-box">
        <div class="ddl-dialog-header">
            <h2 class="js-dialog-title"><?php _e( 'Layout cell types', 'ddl-layouts' ) ?></h2>
            <i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
        </div>

        <div class="ddl-dialog-content ddl-dialog-element-select js-ddl-dialog-element-select">

            <p>
                <input class="js-cells-tree-search" type="text"
                    data-default-val="<?php _e('Search', 'ddl-layouts'); ?>&hellip;"
                    data-message-container=".js-cells-tree-message"
                    data-target="#js-cells-tree"
                    value="<?php _e('Search', 'ddl-layouts'); ?>&hellip;"
                >
            </p>
    		<p class="js-element-box-message-container message-container"></p>

            <ul class="tree" id="js-cells-tree">

                <?php foreach ($cell_categories as $cell_category): ?>
                    <li class="js-tree-category">
                        <h3 class="tree-category-title js-tree-category-title">
                            <i class="fa fa-minus-square-o icon-collapse-alt js-tree-toggle" data-expanded="true" data-text-expanded="<?php _e('Collapse','ddl-layouts') ?>" data-text-collapsed="<?php _e('Expand','ddl-layouts') ?>" title="<?php _e('Expand','ddl-layouts') ?>"></i>

                            <?php echo $cell_category['name']; ?>
                        </h3>
                        <ul class="js-tree-category-items">

                            <?php foreach ($wpddlayout->get_cell_types() as $cell_type): ?>
                                <?php
                                    $cell_info = $wpddlayout->get_cell_info($cell_type);
                                    $has_preview = false;
                                    $has_description = false;
                                    if ( (isset($cell_info['preview-image-url']) && $cell_info['preview-image-url']) ) {
                                        $has_preview = true;
                                    }
                                    if ( (isset($cell_info['description']) && $cell_info['description']) ) {
                                        $has_description = true;
                                    }
                                ?>
                                <?php if ($cell_info['category'] == $cell_category['name']): ?>

                                    <li class="js-tree-category-item">
                                        <p class="item-name-wrap js-item-name-wrap">
                                            <a href="#" class="js-show-cell-dialog"
                                                    data-cell-type="<?php echo $cell_type; ?>"
                                                    data-dialog-title-create="<?php echo $cell_info['dialog-title-create']; ?>"
                                                    data-dialog-title-edit="<?php echo $cell_info['dialog-title-edit']; ?>"
                                                    data-allow-multiple="<?php echo $cell_info['allow-multiple'] ? 'true' : 'false'; ?>"
													data-cell-name="<?php echo $cell_info['name']; ?>"
													data-cell-description="<?php echo $cell_info['description'];?>"
                                                    data-displays-post-content="<?php echo isset( $cell_info['displays-post-content'] ) && $cell_info['displays-post-content'] == true ? 'true' : 'false'; ?>"
                                                >
                                                <?php if (isset($cell_info['cell-image-url']) && $cell_info['cell-image-url']) : ?>
                                                    <i class="item-type-icon <?php echo $cell_info['cell-image-url']; ?>"></i>
                                                <?php elseif (isset($cell_info['icon-url']) && $cell_info['icon-url']) : ?>
                                                    <img class="item-type-icon" src="<?php echo $cell_info['icon-url']; ?>" alt="<?php echo $cell_info['name']; ?>">
                                                <?php endif; ?>
                                                <span class="js-item-name <?php if ( $has_preview ): ?>js-show-item-preview<?php endif; ?>" data-target="<?php echo $cell_type; ?>"><?php echo $cell_info['name']; ?></span>
                                            </a>
                                            <?php if ( $has_description ): ?>
                                                <i class="fa fa-info-circle icon-info-sign js-show-item-desc" data-target="<?php echo $cell_type; ?>"></i>
                                            <?php endif; ?>
                                        </p>
                                        <?php if ( $has_description ): ?>
                                            <p class="item-desc js-item-desc" data-name="<?php echo $cell_type; ?>">
                                                <?php echo $cell_info['description']; ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ( $has_preview ): ?>
                                            <img class="item-preview js-item-preview" data-name="<?php echo $cell_type; ?>" src="<?php echo $cell_info['preview-image-url'] ?>" alt="<?php echo $cell_info['name']; ?>">
                                        <?php endif; ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>

            </ul>

            <div class="js-cells-tree-message" data-message-text="<?php _e( 'Nothing found', 'ddl-layouts' ); ?>"></div>

        </div> <!-- .ddl-dialog-element-select -->

        <div class="ddl-dialog-footer">
            <?php wp_nonce_field('wp_nonce_edit_css', 'wp_nonce_edit_css'); ?>
            <button class="button js-edit-dialog-close"><?php _e('Cancel','ddl-layouts') ?></button>
        </div>
    </div> <!-- .ddl-dialog -->
</div>
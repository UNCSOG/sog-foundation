<li class="grid-category js-tree-category">

    <h3 class="tree-category-title js-tree-category-title js-tree-toggle tree-toggle-element fa" data-expanded="true">
     <!--   <i class="fa fa-minus-square-o icon-collapse-alt"
           data-expanded="true"
           data-text-expanded="<?php _e('Collapse', 'ddl-layouts') ?>"
           data-text-collapsed="<?php _e('Expand', 'ddl-layouts') ?>"
           title="<?php _e('Expand', 'ddl-layouts') ?>">
        </i> -->
        <?php
        $category_icon_class = '';

        if (isset($cell_category['cell-image-url']) && $cell_category['cell-image-url']) {
            $category_icon_class = $cell_category['cell-image-url'];
        } elseif (isset($cell_category['icon-url']) && $cell_category['icon-url']) {
            $category_icon_class = $cell_category['icon-url'];
        }
        echo $cell_category['name']; ?>
    </h3>

    <div class="js-tree-category-items ddl-tree-category-items">
        <div class="category-row js-category-row clearfix">
            <?php
            $count = 0;
            $col = 0;
            $row = 1;
            $max_items_per_row = 5;

            foreach ($this->cell_types as $cell_type) :

                if ($cell_type != 'ddl_missing_cell_type') {

                    $cell_info = $wpddlayout->get_cell_info($cell_type);
                    $has_preview = false;
                    $has_description = false;
                    $has_icon = false;

                    if ((isset($cell_info['description']) && $cell_info['description'])) {
                        $has_description = true;
                    }
                    if ($cell_info['category'] == $cell_category['name']) :
                        $count++;
                        $col++;
                        $mod = ($count % $max_items_per_row == 0) ? 0 : $max_items_per_row - $count % $max_items_per_row;
                        ?>

                        <?php if ($count > $max_items_per_row && $mod === 4) :
                        $row++;
                        ?>

                        <div class="category-row js-category-row clearfix">

                        <?php endif;?>

                        <?php include 'ddl-create-cell-element.tpl.php';?>


                        <?php
                        if ($col == $max_items_per_row) :
                        $col = 0;
                        ?>
                        </div> <!-- .js-category-row -->
                        <div class="js-cell-tpl-container-<?php echo $category_count; ?>-<?php echo $row; ?>"><!-- cell's details tpl container --></div>
                        <?php
                        endif;
                        ?>
                    <?php endif; ?>
                <?php } ?>
            <?php endforeach; ?>
        </div>   <!-- .js-category-row-external -->

        <?php if( $count < $max_items_per_row || $row > 1 ): ?>
        <div class="js-cell-tpl-container-<?php echo $category_count; ?>-<?php echo $row; ?>"><!-- cell's details tpl container --></div>
        <?php endif;?>
</li>

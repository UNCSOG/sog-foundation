<?php
global $wpddlayout;
$icon = $this->get_cell_icon( $cell_info['cell-image-url'] );
$has_default_icon = $this->has_icon_default();
$disabled_cell_class_name = '';
if ( in_array( $cell_type, apply_filters( 'ddl-disabled_cells_on_content_layout', array() ) ) ) {
    $disabled_cell_class_name = 'ddl-disable-cell-for-content-layout';
}
?>

<script type="text/javascript">
    var DDL_LayoutCellInfoObject = DDL_LayoutCellInfoObject || {};
    if( DDL_LayoutCellInfoObject.hasOwnProperty("<?php echo $cell_type; ?>") === false ){
        DDL_LayoutCellInfoObject["<?php echo $cell_type; ?>"] = "<?php echo $cell_info['allow-multiple'] ? 'yes' : 'no'; ?>";
    }
</script>

<div class="grid-category-item js-grid-category-item js-tree-category-item <?php echo $disabled_cell_class_name;?>">
    <a href="#" class="js-render-cell-tpl js-show-item-desc ddl-show-item-desc"
       data-column-count="<?php echo $col; ?>"
       data-cell-type="<?php echo $cell_type; ?>"
       data-dialog-title-create="<?php echo isset($cell_info['dialog-title-create'])?$cell_info['dialog-title-create']:''; ?>"
       data-dialog-title-edit="<?php echo isset($cell_info['dialog-title-edit'])?$cell_info['dialog-title-edit']:''; ?>"
       data-allow-multiple="<?php echo $cell_info['allow-multiple']? 'true' : 'false'; ?>"
       data-cell-name="<?php echo $cell_info['name']; ?>"
       data-cell-description="<?php echo $cell_info['description']; ?>"
       data-displays-post-content="<?php echo isset( $cell_info['displays-post-content'] ) && $cell_info['displays-post-content'] == true ? 'true' : 'false'; ?>"
       data-cell-preview="<?php echo isset($cell_info['preview-image-url'])?$cell_info['preview-image-url']:''; ?>"
       data-cell-cat-count="<?php echo $category_count;?>"
       data-tooltip-text="<?php echo $cell_info['name']; ?>"
       data-row-count="<?php echo $row; ?>"
       data-has-settings="<?php echo isset( $cell_info['has_settings'] ) && $cell_info['has_settings'] === false ? 'false' : 'true'; ?>"
       data-disabled="<?php echo $cell_type === 'child-layout' && $wpddlayout->is_embedded() ? 'disabled' : 'enabled'; ?>"
        >

        <div class="ddl-icon">
            <img src="<?php echo $icon;?>" class="ddl-icon-img" alt=""/>
            <div class="js-item-name ddl-cell-item-name" data-target="<?php echo $cell_type; ?>"><?php echo trim( rtrim( $cell_info['name'] ) ); ?></div>

        </div>


    </a>

</div>

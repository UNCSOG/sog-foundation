<?php
// register the container



class WPDD_layout_container_factory extends WPDD_layout_cell_factory
{
    public function get_cell_info($template)
    {
        $template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH.'grid-cell.svg';
        $template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'grid-of-cells_expand-image2.png';
        $template['name'] = __('Grid of cells (split cells into rows for grids and sidebars)', 'ddl-layouts');
        $template['description'] = __('Split cells into several rows and columns. Use also for single-column grids, like sidebars.', 'ddl-layouts');
        $template['button-text'] = __('Assign Grid of cells', 'ddl-layouts');
        $template['dialog-title-create'] = __('Create new Grid of cells', 'ddl-layouts');
        $template['dialog-title-edit'] = __('Edit Grid of cells', 'ddl-layouts');
        $template['dialog-template'] = $this->_dialog_template();
        $template['category'] = __('Layout structure', 'ddl-layouts');
        $template['has_settings'] = false;
        return $template;
    }

    protected function _dialog_template()
    {
        global $wpddl_features;
        $hide = $wpddl_features->is_feature('fixed-layout') ? '' : ' class="hidden" ';
        ob_start();
        ?>
        <ul class="ddl-form">
            <li>
                <fieldset <?php echo $hide; ?> >
                    <legend><?php _e('Grid type:', 'ddl-layouts'); ?></legend>
                    <div class="fields-group ddl-form-inputs-inline">
                        <label class="radio" for="cell_nested_type_fixed">
                            <input type="radio" name="cell_nested_type"
                                   class="js-layout-type-selector js-layout-type-selector-fixed"
                                   id="cell_nested_type_fixed" value="fixed" checked>
                            <?php _e('Fixed', 'ddl-layouts'); ?>
                        </label>
                        <label class="radio" for="cell_nested_type_fluid">
                            <input type="radio" name="cell_nested_type"
                                   class="js-layout-type-selector js-layout-type-selector-fluid"
                                   id="cell_nested_type_fluid" value="fluid">
                            <?php _e('Fluid', 'ddl-layouts'); ?>
                        </label>

                        <p class="toolset-alert toolset-alert-info js-diabled-fixed-rows-info">
                            <?php _e('Only fluid rows are allowed here because the parent row or layout are fluid.', 'ddl-layouts'); ?>
                        </p>
                    </div>
                </fieldset>
                <p class="desc js-grid-fixed-message"><?php _e('In fixed-width mode, the width of the grid determines the number of columns.', 'ddl-layouts'); ?></p>
            </li>
            <li class="js-fluid-grid-designer">
                <fieldset>
                    <legend><?php _e('Grid size', 'ddl-layouts'); ?>:</legend>
                    <div class="fields-group">
                        <div id="js-fluid-grid-slider-horizontal" class="horizontal-slider"></div>
                        <div id="js-fluid-grid-slider-vertical" class="vertical-slider"></div>
                        <div class="grid-designer-wrap">
                            <div class="grid-info-wrap">
                                <span id="js-fluid-grid-info-container" class="grid-info"></span>
                            </div>
                            <div id="js-fluid-grid-designer" class="grid-designer js-fluid-grid-designer-el"
                                 data-rows="2"
                                 data-cols="4"
                                 data-max-cols="12"
                                 data-max-rows="4"
                                 data-slider-horizontal="#js-fluid-grid-slider-horizontal"
                                 data-slider-vertical="#js-fluid-grid-slider-vertical"
                                 data-info-container="#js-fluid-grid-info-container"
                                 data-message-container="#js-fluid-grid-message-container"
                                 data-fluid="true">
                            </div>
                        </div>
                        <div id="js-fluid-grid-message-container"></div>
                    </div>
                </fieldset>
            </li>
            <li class="js-fixed-grid-designer">
                <fieldset>
                    <legend><?php _e('Choose number of rows', 'ddl-layouts'); ?></legend>
                    <div class="fields-group">
                        <div id="js-fixed-grid-slider-vertical" class="vertical-slider"></div>
                        <div class="grid-designer-wrap">
                            <div class="grid-info-wrap">
                                <span id="js-fixed-grid-info-container" class="grid-info"></span>
                            </div>
                            <div id="js-fixed-grid-designer" class="grid-designer"
                                 data-rows="2"
                                 data-max-rows="4"
                                 data-slider-vertical="#js-fixed-grid-slider-vertical"
                                 data-info-container="#js-fixed-grid-info-container"
                                 data-message-container="#js-fixed-grid-message-container">
                            </div>
                        </div>
                        <div id="js-fixed-grid-message-container"></div>
                    </div>
                </fieldset>
            </li>
            <li class="extra-top">

                <span></span><a class="fieldset-inputs" href="<?php echo WPDLL_LEARN_ABOUT_GRIDS; ?>" target="_blank">
                    <?php _e('Learn about creating and using grids', 'ddl-layouts'); ?> &raquo;
                </a></span>
            </li>
        </ul>

        <?php
        return ob_get_clean();
    }

}
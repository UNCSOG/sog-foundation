<ul class="ddl-form">
	<li>
		<label
			for="<?php the_ddl_name_attr('navigation_style'); ?>"><?php _e('Tabs navigation style', 'ddl-layouts') ?>
			:</label>
		<div class="display-inline">
            <p>
		<input type="radio" name="<?php the_ddl_name_attr('navigation_style'); ?>"
               value="<?php echo WPDD_layouts_layout_tabs::$navigation_t;?>" checked> <span class="label"><?php _e('Tabs', 'ddl-layouts');?></span></p>
		<p><input type="radio" name="<?php the_ddl_name_attr('navigation_style'); ?>"
			    value="<?php echo WPDD_layouts_layout_tabs::$navigation_p;?>" > <span class="label"><?php _e('Buttons', 'ddl-layouts');?></span></p>
        </div>
	</li>
	<li>
		<label for="<?php the_ddl_name_attr('justified'); ?>"><?php printf(__('Width of the %stabs%s', 'ddl-layouts') , '<span class="tabs-style">', '</span>'); ?>:</label>
        <div class="display-inline">
            <p><input type="radio" name="<?php the_ddl_name_attr('justified'); ?>" value="<?php echo WPDD_layouts_layout_tabs::$width_text;?>" checked><span class="label"><?php printf(__('Each %stab%s its own width to fit its text', 'ddl-layouts'), '<span class="tab-style">', '</span>' );?></span></p>
            <p><input type="radio" name="<?php the_ddl_name_attr('justified'); ?>" value="<?php echo WPDD_layouts_layout_tabs::$width_justified;?>"><span class="label"><?php printf(__('All %stabs%s the same width', 'ddl-layouts') , '<span class="tabs-style">', '</span>' );?></span></p>
        </div>
    </li>
    <li class="ddl-tabs-stacked">
        <label for="<?php the_ddl_name_attr('stacked'); ?>"><?php _e('Orientation of the buttons', 'ddl-layouts') ?>:</label>
        <div class="display-inline">
        <p><input type="radio" name="<?php the_ddl_name_attr('stacked'); ?>" value="<?php echo WPDD_layouts_layout_tabs::$navigation_h;?>" checked><span class="label"><?php _e('Horizontal', 'ddl-layouts');?></span></p>
        <p><input type="radio" name="<?php the_ddl_name_attr('stacked'); ?>" value="<?php echo WPDD_layouts_layout_tabs::$navigation_v;?>" ><span class="label"><?php _e('Vertical', 'ddl-layouts');?></span></p>
        </div>
    </li>

    <li>
        <label for="<?php the_ddl_name_attr('fade'); ?>"><?php printf( __('Visual effect when switching %stabs%s', 'ddl-layouts'), '<span class="tabs-style">', '</span>' );?>:</label>
        <div class="display-inline">
    	<p><input type="radio" name="<?php the_ddl_name_attr('fade'); ?>" value="no_fade" checked/>
            <span class="label"><?php _e('Immediate switch', 'ddl-layouts'); ?></span></p>
        <p><input type="radio" name="<?php the_ddl_name_attr('fade'); ?>" value="fade" />
            <span class="label"><?php _e('Smooth fade', 'ddl-layouts'); ?></span></p>
        </div>
    </li>
    <li><p>
            <a class="fieldset-inputs" href="<?php echo WPDLL_TABS_CELL_HELP; ?>" target="_blank">
                <?php _e('Working with tabs', 'ddl-layouts'); ?> &raquo;
            </a>
        </p>
    </li>

</ul>
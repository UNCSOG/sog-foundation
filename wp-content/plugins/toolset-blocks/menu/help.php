<div class="wrap toolset-views">

	<h1><?php _e('Views Help', 'wpv-views') ?></h1>

    <div class="wpv-views-help-page">

       <div class="header">
            <h2><?php _e('Building Websites with Views','wpv-views'); ?></h2>
            <p><?php _e('Views plugin lets you design single pages, display content from the database and customize standard listing pages.','wpv-views'); ?></p>
            <p><?php _e('Here are the things that you can create with Views plugin:','wpv-views'); ?></p>
       </div>

        <div class="types-of-views">
            <ul>

                <li>

                    <div class="img-wrap">
                        <img src="<?php echo (WPV_URL . '/res/img/help-page-views-normal.jpg'); ?>">
                    </div>
                    <h3><?php _e('Views','wpv-views'); ?></h3>
                    <p class="desc">
                        <?php _e('A View loads content from the database and displays it anyway you choose. Use Views to create content lists, sliders, parametric searches and more.
','wpv-views'); ?>
                    </p>
                    <p>
                        <a class="button-primary" href="<?php echo admin_url('admin.php?page=views'); ?>"><?php _e('Create a new View','wpv-views'); ?></a>
                    </p>

                </li>

                <li>
                	<div class="img-wrap">
                		<img src="<?php echo (WPV_URL . '/res/img/help-page-ct.jpg'); ?>">
                	</div>
                	<h3><?php _e('Content Templates','wpv-views'); ?></h3>
                    <p class="desc">
                    	<?php _e('Content Templates let you design single pages using fields, taxonomy and HTML. With Content Templates, you can design the output for posts, pages and custom post types.','wpv-views'); ?>
                    </p>
                    <p>
                    	<a class="button-primary" href="<?php echo admin_url('admin.php?page=view-templates'); ?>"><?php _e('Create a new Content Template') ?></a>
                    </p>
                </li>

                <li>
                	<div class="img-wrap">
                		<img src="<?php echo (WPV_URL . '/res/img/help-page-views-archive.jpg'); ?>">
                	</div>
                	<h3><?php _e('WordPress Archives','wpv-views'); ?></h3>
                    <p class="desc">
                    	<?php _e('WordPress Archives let you customize standard listing pages. You will be able to customize the blog, custom post archives, taxonomy pages and other standard listing pages.','wpv-views'); ?>
                    </p>
                    <p>
                    	<a class="button-primary" href="<?php echo admin_url('admin.php?page=view-archives'); ?>"><?php _e('Create a new WordPress Archive') ?></a>
                    </p>
                </li>

            </ul>
        </div>
    </div>

	<h2 style="margin-top:3em;"><?php _e('Debug information', 'wpv-views'); ?></h2>
	<p><?php
	printf(
		__( 'For retrieving debug information if asked by a support person, use the <a href="%s">debug information</a> page.', 'wpv-views' ),
		admin_url('admin.php?page=toolset-debug-information')
	);
	?></p>
</div>

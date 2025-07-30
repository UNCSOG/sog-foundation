<?php
global $wpddlayout;
?>

<div class="js-change-wrap-box">
    <h2 class="js-change-layout-use-section-title change-layout-use-section-title-outer" data-group="2">
        <span  class="change-layout-use-section-title js-collapse-group-individual">
            <?php _e('Individual pages:', 'ddl-layouts'); ?>
        </span>
        <i id="js-individual-post-lists" class="fa fa-caret-down js-collapse-group-individual change-layout-use-section-title-icon-collapse"></i>
    </h2>

<div class="individual-pages-wrap hidden">

    <div class="notice inline notice-warning notice-alt notice_without_margine">
        <p>
            <?php _e('A better way to create a layout for a single page is to edit that page and click on the button  "Content Layout Editor".','dd-layouts');?><br>
            <a href="https://toolset.com/documentation/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts" target="_blank">
                <?php printf(__( 'Learn about designing individual pages with Layouts %s', 'dd-layouts' ), '<i class="fa fa-external-link" aria-hidden="true"></i>');?>
            </a>
        </p>
    </div>

    <div class="js-individual-pages-assigned individual-pages-assigned">
        <?php echo $this->print_single_posts_assigned_section( $current );?>
    </div>

    <?php $unique_id = uniqid(); ?>
    <div class="js-individual-popup-tabs">

        <ul>
            <li><a href="#js-ddl-individual-most-recent-<?php echo $unique_id; ?>"><?php _e('Most Recent', 'ddl-layouts'); ?></a></li>
            <li><a href="#js-ddl-individual-view-all-<?php echo $unique_id; ?>"><?php _e('View All', 'ddl-layouts'); ?></a></li>
            <li><a href="#js-ddl-individual-search-<?php echo $unique_id; ?>"><?php _e('Search', 'ddl-layouts'); ?></a></li>
        </ul>

        <div class="ddl-popup-tab-full ddl-individual-tab" id="js-ddl-individual-most-recent-<?php echo $unique_id; ?>">
            <?php
            $post_type = isset( $_POST['post_type'] ) ? $_POST['post_type'] : 'page';
            $count = isset( $_POST['count'] ) ? $_POST['count'] : 12;
            $search = isset( $_POST['search'] ) ? $_POST['search'] : '';
            $sort = isset($_POST['sort']) ? $_POST['sort'] : false;
            echo $wpddlayout->individual_assignment_manager->get_posts_checkboxes($post_type, $count, $search, $sort);
            ?>
        </div>
        <!-- .ddl-popup-tab -->

        <div class="ddl-popup-tab-full ddl-individual-tab" id="js-ddl-individual-view-all-<?php echo $unique_id; ?>">
            <?php
            $count = isset( $_POST['count'] ) ? $_POST['count'] : DDL_MAX_NUM_POSTS;
            echo $wpddlayout->individual_assignment_manager->get_posts_checkboxes($post_type, $count, $search, $sort); ?>
        </div>
        <!-- .ddl-popup-tab -->

        <div class="ddl-popup-tab-full ddl-individual-tab" id="js-ddl-individual-search-<?php echo $unique_id; ?>">
            <input class="js-individual-quick-search ddl-individual-quick-search" type="search"
                   id="ddl-individual-search" value="" title="Search" autocomplete="off"
                   placeholder="<?php _e('Search', 'ddl-layouts'); ?>" />
            <span class="desc individual-quick-search-desc"><?php _e('You can enter the page title or paste the URL of a page, copied from the site\'s front-end.', 'ddl-layouts'); ?></span>
            <div id="ddl-individual-search-results-<?php echo $unique_id; ?>"></div>
        </div>
        <!-- .ddl-popup-tab -->

    </div>
    <!-- .js-individual-popup-tabs -->
    <div class="ddl-single-assignments-box-controls">

    <ul class="ddl-single-assignments-posts-who-controls">
        <li><input type="radio" id="ddl-individual-post-type-page" name="ddl-individual-post-type"
                   value="page" checked/><?php _e('Show only pages', 'ddl-layouts'); ?></li>
        <li><input type="radio" id="ddl-individual-post-type-any" name="ddl-individual-post-type"
                   value="any"/><?php _e('Show all content types', 'ddl-layouts'); ?></li>
    </ul>
        <ul class="ddl-single-assignments-box-lang-controls">
            <li>
                <?php do_action('ddl-add-wpml-custom-switcher');?>
            </li>
        </ul>
    </div>
    <div style="text-align: right;" class="js-individual-posts-update-wrap">
        <button data-group="<?php echo WPDD_Layouts_IndividualAssignmentManager::INDIVIDUAL_POST_ASSIGN_CHECKBOXES_NAME; ?>" class="button-secondary js-connect-to-layout js-buttons-change-update"><?php _e('Update', 'ddl-layouts'); ?></button>
    </div>

    <?php wp_nonce_field('wp_nonce_individual-pages-assigned', 'wp_nonce_individual-pages-assigned'); ?>
</div>

</div>

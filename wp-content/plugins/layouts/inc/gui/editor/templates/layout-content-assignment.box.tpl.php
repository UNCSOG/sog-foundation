<?php $products_found = false; ?>
<div class="layout-content-assignment js-layout-content-assignment" id="js-print_where_used_links">

    <?php if ($lists !== null): ?>
        <script type="text/javascript">
            DDLayout.local_settings = DDLayout.local_settings || {};
            DDLayout.local_settings.is_layout_assigned = true;
            DDLayout.PostTypes.ajax_done = false;
            DDLayout.local_settings.list_where_used = <?php echo wp_json_encode($lists);?>
        </script>
        <div class="assignment-list-in-editor-wrap">
            <ul>

                <?php
                // post types block
                if (property_exists($lists, 'post_types') && is_array($lists->post_types)):
                    echo __('<p class="where-used-title-wrap">This layout is used for these post types:</p>', 'ddl-layouts' );
                    foreach ($lists->post_types as $post_type):
                        $post_type = (object) $post_type;

                        if( property_exists($post_type, 'post_type') === false){
                            $post_type->post_type = 'undefined';
                            $post_type->missing = 0;
                        }

                        if ($post_type->post_type == 'product') {
                            $products_found = true;
                        }
                        $type = get_post_type_object($post_type->post_type);
                        if( is_null($type) ){
                            $type = new stdClass();
                            $type->name = $post_type->post_type;
                            $type->labels = new stdClass();
                            $type->labels->name = $post_type->post_type;
                        }
                        $is_to_be_batched = $this->main->post_types_manager->get_post_type_was_batched( $current, $post_type->post_type ) === false;
                        $checked = $this->main->post_types_manager->post_type_is_in_layout($type->name, $current) ? 'checked' : '';
                        ?>
                        <li>
                            <?php if (
                                ($post_type->missing !== 0) && ($post_type->post_num === $post_type->missing)):
                                ?>

                                <span class="has-to-be-batched"><?php echo $type->labels->name; ?> </span>
                                <?php
                            else:
                                $show = $this->get_x_posts_of_type($post_type->post_type, $current, 1);
                                if (null !== $show) {
                                    // echo $type->labels->name;
                                    foreach ($show as $post_of_type):
                                        ?>
                                        <div class="list-where-used-item js-list-where-used-item">
                                            <a href="<?php echo $this->ddl_get_post_type_batched_preview_permalink( $post_type->post_type, $post_of_type->ID ); ?>"
                                               target="_blank"><?php echo $type->labels->name;?></a>

                                            <div class="list-where-used-item-controls js-list-where-used-item-controls">
                                            <span class="list-where-used-item-small"><a
                                                    href="<?php echo site_url();?>/wp-admin/edit.php?post_type=<?php echo $post_type->post_type; ?>"
                                                    target="_blank">Edit</a></span> |
                                            <span class="list-where-used-item-small"><a
                                                    href="<?php echo $this->ddl_get_post_type_batched_preview_permalink($post_type->post_type, $post_of_type->ID ); ?>"
                                                    target="_blank">View</a></span>
                                            </div>
                                        </div>
                                        <?php
                                    endforeach;
                                } else {
                                    ?>
                                    <span class="has-to-be-batched"><?php echo $type->labels->name; ?></span>
                                <?php } ?>
                            <?php endif; ?>
                            <?php $this->main->post_types_manager->print_apply_to_all_link_in_layout_editor($type, $checked, $current); ?>
                        </li>
                        <?php
                    endforeach;
                endif; ?>
            </ul>
            <ul>
                <?php
                // archives block
                if (property_exists($lists, 'loops')):
                    echo __('<p class="where-used-title-wrap">This layout is used for these loops:</p>', 'ddl-layouts' );
                    foreach ($lists->loops as $loop):
                        $loop = (object)$loop;
                    
                        if($loop->name ==='layouts_home-blog-page' && (get_option( 'page_for_posts' ) || get_option('page_on_front') )){
                            $loop->title = $loop->title." <i>".__('(Please select "Your latest posts" from Reading option)')."</i>";
                            $loop->href = "#"; 
                        } else if($loop->name ==='layouts_home-static-blog-page' && !get_option( 'page_for_posts' )){
                            $loop->title = $loop->title." <i>".__("(Please select page from Reading option)")."</i>";
                            $loop->href = "#"; 
                        } else if($loop->name ==='layouts_home-home-page' && !get_option('page_on_front') ){
                            $loop->title = $loop->title." <i>".__("(Please select page from Reading option)")."</i>";
                            $loop->href = "#";
                        }
                        
                        ?>

                        <?php if ($loop->href && $loop->href != '#'): ?>
                            <li><a href="<?php echo $loop->href ?>" target="_blank"><?php echo $loop->title; ?></a></li>
                        <?php else: ?>
                            <li><?php echo $loop->title; ?> - <?php _e('(No previews available)', 'ddl-layouts'); ?></li>
                        <?php endif; ?>

                        <?php
                    endforeach;
                endif; ?>
            </ul>
            <ul class="js-dd-layouts-where-used" id="js-dd-layouts-where-used">
                <?php
                // single posts block
                if (property_exists($lists, 'posts') && ($total_count !=0)):
                    echo __('<p class="where-used-title-wrap">This layout is used for these posts:</p>', 'ddl-layouts' );
                    foreach ($lists->posts as $post):
                        if ($post->post_type == 'product') {
                            $products_found = true;
                        }
                        if( !$post->post_title || $post->post_title === '' ){
                            $post->post_title = sprintf( __('%sno title%s', 'ddl-layouts'), '&lpar;', '&rpar;' );
                        }
                        ?>
                        <li>
                            <div class="list-where-used-item js-list-where-used-item">
                                <a href="<?php echo $post->permalink ?>" target="_blank"><?php echo $post->post_title; ?></a>
                                <div class="list-where-used-item-controls js-list-where-used-item-controls">
                                <span class="list-where-used-item-small">
                                    <a href="<?php echo get_edit_post_link( $post->ID); ?>" target="_blank">Edit</a>
                                </span> |
                                <span class="list-where-used-item-small">
                                    <a href="<?php echo get_permalink($post->ID) ?>" target="_blank">View</a>
                                </span>
                                </div>
                            </div>
                        </li>
                        <?php
                    endforeach;
                endif; ?>

            </ul>




        </div>
    <?php else:

    ?>

        <script type="text/javascript">
            DDLayout.local_settings = DDLayout.local_settings || {};
            DDLayout.local_settings.is_layout_assigned = false;
            DDLayout.local_settings.list_where_used = null;
        </script>

    <?php endif;
    $button_data = array(
        'nonce' => wp_create_nonce('load-assign-dialog-nonce')
    );
    ?>

    <?php
    if ($products_found) {
        $post_type = new stdClass();
        $post_type->name = 'product';
        $woocommerce_support_message = $this->main->post_types_manager->check_layout_template_for_woocommerce( $post_type );
        if ( $woocommerce_support_message ) {
            ?>
            <div class="layout-content-assignment-woo-message-wrap js-layout-content-assignment-woo-message-wrap">
                <p class="toolset-alert toolset-alert-warning">
                    <?php echo $woocommerce_support_message; ?>
                </p>
            </div>
            <?php
        }
    }
    ?>

    <div class="layout-content-assignment-button-wrap">
        <?php if(($count_pages > self::AMOUNT_OF_POSTS_TO_SHOW) && ($total_count === self::AMOUNT_OF_POSTS_TO_SHOW)):?>
            <button id="layout-content-assignment-load-more-button" data-textnotall="<?php _e('Show less...', 'ddl-layouts'); ?>" data-textall="<?php _e('Show all...', 'ddl-layouts'); ?>" data-amount_button="not_all" data-amount="-1" class="button button-small js-show-all"><?php _e('Show more...', 'ddl-layouts'); ?></button>
        <?php endif;?>
        <span data-object="<?php echo htmlspecialchars(wp_json_encode($button_data)); ?>"
                id="layout-content-assignment-button" class="js-layout-content-assignment-button button button-large"
                name="layout-content-assignment-button">
            <?php _e('Change how this layout is used', 'ddl-layouts'); ?>
        </span>
    </div>
    <div class="js-where-used-box-messages where-used-box-messages"></div>
</div>
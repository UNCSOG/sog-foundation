<?php
global $wpddlayout;
$products_found = false; 
// single posts block
if (property_exists($lists, 'posts')):
    echo __('<p class="where-used-title-wrap">This layout is used for these posts:</p>', 'ddl-layouts' );
    foreach ($lists->posts as $post):
        if ($post->post_type == 'product') {
            $products_found = true;
        }
        if( !$post->post_title || $post->post_title === '' ){
            $post->post_title = sprintf( __('%sno title%s', 'ddl-layouts'), '&lpar;', '&rpar;' );
        }
        if ($wpddlayout->post_types_manager->post_type_is_in_layout($post->post_type, $current) === false):
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
        endif;
    endforeach;
endif; ?>

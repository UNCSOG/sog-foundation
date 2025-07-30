<?php
global $wpddlayout;
if( DDL_GroupedLayouts::NUMBER_OF_ITEMS  < $found_posts ):

    $class = $amount == DDL_GroupedLayouts::NUMBER_OF_ITEMS ? 'fa fa-caret-down' : 'fa fa-caret-up';
?>
<i class="<?php echo $class;?> show-all-posts-assigned-in-dialog js-show-all-posts-assigned-in-dialog"></i>
<?php endif; ?>
<ul class="individual-pages-list">
    <?php foreach($posts as $post): ?>
        <?php if (!(in_array($post->post_type, $post_types) && $wpddlayout->post_types_manager->get_post_type_was_batched( $layout_id, $post->post_type ))): ?>
            <li><?php echo $wpddlayout->individual_assignment_manager->encode_title($post->post_title); ?> <input type="checkbox"  value="<?php echo $post->ID; ?>"  class="js-remove-individual-page-item hidden"></li>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>
<div class="wpddl-remove-single-posts-buttons js-wpddl-remove-single-posts-buttons">
    <button class="wpddl-remove-single-posts-enable js-wpddl-remove-single-posts-enable button"><?php _e( 'Remove from list', 'ddl-layouts')?></button>
    <button class="wpddl-remove-single-posts js-wpddl-remove-single-posts button hidden" disabled="disabled"><?php _e( 'Remove selected', 'ddl-layouts')?></button>
    <button class="wpddl-remove-single-posts-cancel js-wpddl-remove-single-posts-cancel button hidden"><?php _e( 'Cancel', 'ddl-layouts')?></button>
</div>
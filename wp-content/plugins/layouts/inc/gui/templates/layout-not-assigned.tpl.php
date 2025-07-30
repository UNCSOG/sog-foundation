<?php
do_action('ddl-enqueue_styles', 'ddl-front-end');
$header =  __( 'This page doesn\'t have a template layout', 'ddl-layouts' );
$learn_link = WPDDL_DISPLAY_POST_TYPES_LEARN;
$learn_anchor = __( "Using Layouts as Templates for Contents", 'ddl-layouts' );

$button_data = apply_filters( 'ddl_generate_assignment_button', array() );
$assign_layout_link = ( isset( $button_data ) && $button_data['menu_link']) ? $button_data['menu_link'] : '';
$menu_title = ( isset( $button_data ) && $button_data['menu_title']) ? $button_data['menu_title'] : '';


get_header();
?>

<div class="ddl_na_panel ddl_na_panel-default not-assigned ">
    <div class="ddl_na_panel-heading"><?php echo $header; ?></div>
    <div class="ddl_na_panel-body">
        <div class="not-assigned-body">

            <p><?php echo WPDDL_Templates_Settings::getInstance()->get_default_message(); ?></p>

            <?php if( user_can_assign_layouts() ): ?>

            <a class="btn btn-lg btn-primary"
               href="<?php echo $assign_layout_link; ?>"
               title="<?php _e( "Layouts", 'ddl-layouts' ); ?>"><?php _e( $menu_title, 'ddl-layouts' ); ?>
            </a>

            <hr>
            <small>
                <?php printf(__("Also, you can see all the layouts in this site, edit and create new ones from %sToolset->Layouts%s.",'ddl-layouts'),'<a href="'.admin_url("admin.php?page=dd_layouts").'">','</a>');?>
            </small>
            <?php endif; ?>
        </div>

    </div>
    <?php if( user_can_assign_layouts() ): ?>
    <div class="ddl_na_panel-footer ddl_na_panel-footer-sm text-center">
        <?php _e( "You can see this message because you are logged in as a user who can assign Layouts. <br>Your visitors won't see this message.", 'ddl-layouts' ); ?>
    </div>
    <?php endif; ?>
</div>

<?php get_footer();?>
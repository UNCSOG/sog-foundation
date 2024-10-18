<div class="wrap">
	<h1><?php _e('Layouts Help', 'ddl-layouts'); ?></h1>

    <h3><?php _e('Documentation and Support', 'ddl-layouts'); ?></h3>
    <ul>
        <li><?php printf('<a target="_blank" href="https://toolset.com/documentation/legacy-features/toolset-layouts/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts"><strong>%s</strong></a>'.__(' - everything you need to know about using Layouts', 'ddl-layouts'),__('User Guides', 'ddl-layouts')); ?></li>
        <li><?php printf('<a target="_blank" href="https://toolset.com/support/support-forum-archive/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts"><strong>%s</strong></a>'.__(' - online help by support staff', 'ddl-layouts'),__('Support forum', 'ddl-layouts') ); ?></li>
    </ul>
    <h3 style="margin-top:2em;"><?php _e('Debug information', 'ddl-layouts'); ?></h3>
    <p><?php
    printf(
    __( 'For retrieving debug information if asked by a support person, use the <a href="%s">debug information</a> page.', 'ddl-layouts' ),
    admin_url('admin.php?page=dd_layouts_debug')
    );
?></p>
    <h3 style="margin-top:2em;"><?php _e('Change log', 'ddl-layouts'); ?></h3>
    <p>
    <?php
	echo sprintf( __('For more information about changes in Layouts version '.WPDDL_VERSION.'. please read <a href="%s" target="_blank">Layouts %s release notes</a>', 'ddl-layouts'), WPDDL_NOTES_URL.'?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=css-styling-tab&utm_term=help-link', WPDDL_VERSION )
    ?>
    </p>
    <h3 style="margin-top:2em;"><?php _e('Troubleshoot', 'ddl-layouts'); ?></h3>
	<p><?php
    printf(
    __( 'For solving known issues, use the <a href="%s">troubleshoot</a> page.', 'ddl-layouts' ),
    admin_url('admin.php?page=dd_layouts_troubleshoot')
    );
?></p>

</div>

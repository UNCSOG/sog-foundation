<div id="wizard-instructions">
    <h2 class="cred-wizard-header">Instructions</h2>
    <div class="toolset-help toolset-help-sm">
        <div class="toolset-help-content">
            <h1><?php echo __( 'Post Forms Instructions', 'wp-cred' ); ?></h1>
            <p><?php echo __( 'Toolset Post Forms can create new posts or edit existing posts from the front-end.', 'wp-cred' ); ?></p>

            <h2><?php echo __( 'Forms for creating new content', 'wp-cred' ); ?></h2>
            <p><?php echo __( 'You can insert the forms for creating new posts into any page on your site.', 'wp-cred' ); ?></p>

            <h2><?php echo __( 'Forms for editing content', 'wp-cred' ); ?></h2>
            <p><?php echo __( 'Using the forms for editing existing posts requires a bit different workflow:', 'wp-cred' ); ?></p>

            <ol>
                <li><?php echo __( 'Create a form that edits posts (using this very wizard).', 'wp-cred' ); ?></li>
                <li><?php echo __( 'Create an “editing-mode” template and insert the form into it.', 'wp-cred' ); ?></li>
                <li><?php echo __( 'Link to this template to allow editing the selected content using the form.', 'wp-cred' ); ?></li>
            </ol>

            <p><?php echo __( 'To learn how to do this, read the documentation on', 'wp-cred' ); ?> <a
                        href="<?php echo CRED_CRED::$help[ 'displaying_cred_editing_forms' ][ 'link' ]; ?>"
                        alt="<?php echo CRED_CRED::$help[ 'displaying_cred_editing_forms' ][ 'text' ]; ?>"
                        target="_blank"><?php echo CRED_CRED::$help[ 'displaying_cred_editing_forms' ][ 'text' ]; ?></a>.
            </p>
        </div>
        <div class="toolset-help-sidebar"></div>
    </div>
</div>
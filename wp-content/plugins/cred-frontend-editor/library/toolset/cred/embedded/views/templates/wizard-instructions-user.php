<div id="wizard-instructions">
    <h2 class="cred-wizard-header">Instructions</h2>
    <div class="toolset-help toolset-help-sm">
        <div class="toolset-help-content">
            <h1><?php echo __( 'User Forms Instructions', 'wp-cred' ); ?></h1>
            <p><?php echo __( 'You can create Toolset User Forms for registering new users or editing existing users.', 'wp-cred' ); ?></p>

            <h2><?php echo __( 'Registering new users or editing the currently-logged-in user', 'wp-cred' ); ?></h2>
            <ol>
                <li><?php echo __( 'Create the form using this wizard.', 'wp-cred' ); ?></li>
                <li><?php echo __( 'Insert a form into any page.', 'wp-cred' ); ?></li>
            </ol>

            <h2><?php echo __( 'Editing other users', 'wp-cred' ); ?></h2>
            <ol>
                <li><?php echo __( 'Create the form using this wizard.', 'wp-cred' ); ?></li>
                <li><?php echo __( 'Create an "editing-mode" template and insert the form into it.', 'wp-cred' ); ?></li>
                <li><?php echo __( 'Link to this template to allow editing the selected user with the form.', 'wp-cred' ); ?></li>
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
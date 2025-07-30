<div>
    <div id="toolset-templates-settings" class="wpv-setting-container js-wpv-setting-container">

        <div class="wpv-setting">
            <p>
                <?php _e( "You can set from here a default fallback to display a resource when layout is not assigned to it.", 'ddl-layouts' ); ?>
            </p>

            <div>
                <div class="settings-item-wrap">
                <label for="ddl-default-display-blank"><?php _e( "A blank page with a message:", 'ddl-layouts' ); ?><input type="radio" name="<?php echo self::DEFAULT_OPTION;?>" id="ddl-default-display-blank" class="js-ddl-default-display-blank <?php echo self::DEFAULT_OPTION;?>" value="1" <?php echo apply_filters('ddl-default-template-option-checked', 1, $this->get_default_value());?>  <?php echo apply_filters('ddl-default-template-disabled-radio', '', 1 );?> /></label>
                    <?php $class = !apply_filters('ddl-default-template-option-checked', 1, $this->get_default_value() ) ? 'hidden' : '';?>
                    <div class="ddl-message-textarea-wrap <?php echo $class;?>">
                            <textarea cols="30" rows="5" name="<?php echo self::DEFAULT_MESSAGE;?>" id="ddl-default-display-message"><?php echo apply_filters('ddl-get_template_default_message', '');?></textarea>
                        </div>
                </div>
                <div class="settings-item-wrap">
                <label for="ddl-default-display-theme"><?php _e( "What the theme would output", 'ddl-layouts' ); ?><input type="radio" name="<?php echo self::DEFAULT_OPTION;?>" id="ddl-default-display-theme" class="js-ddl-default-display-blank <?php echo self::DEFAULT_OPTION;?>" value="2"  <?php echo apply_filters('ddl-default-template-option-checked', 2, $this->get_default_value());?>  <?php echo apply_filters('ddl-default-template-disabled-radio', '', 2 );?> /></label>
                </div>
                <div class="settings-item-wrap">
                    <label for="ddl-default-display-layout"><?php _e( "The content using a following 'default' Layout", 'ddl-layouts' ); ?><input type="radio" name="<?php echo self::DEFAULT_OPTION;?>" id="ddl-default-display-layout" class="js-ddl-default-display-blank <?php echo self::DEFAULT_OPTION;?>" value="3"  <?php echo apply_filters('ddl-default-template-option-checked', 3, $this->get_default_value());?> <?php echo apply_filters('ddl-default-template-disabled-radio', '', 3 );?> /></label>
                    <?php $class = apply_filters('ddl-default-template-option-checked', 3, $this->get_default_value()) === '' ? 'hidden' : '';?>
                    <div class="ddl-message-textarea-wrap <?php echo $class;?>">
                            <select name="<?php echo self::DEFAULT_LAYOUT;?>" id="<?php echo self::DEFAULT_LAYOUT;?>" class="js-<?php echo self::DEFAULT_LAYOUT;?>">
                                    <?php echo $this->layouts_options();?>
                                </select>
                        </div>
                </div>
                <div class="settings-item-wrap">
                    <p><?php printf(__( "%sSet this option for:%s", 'ddl-layouts' ), '', '' ); ?></p>
                    <label for="<?php echo self::TEMPLATE_OPTION_USER;?>"><span class="radio-label"><?php _e( "All users", 'ddl-layouts' ); ?></span><input <?php echo apply_filters( 'ddl-default-template-option-checked', 2, $this->get_default_user() );?> type="radio" name="<?php echo self::TEMPLATE_OPTION_USER;?>" value="2" /><span class="radio-label"><?php _e( "Layouts admin", 'ddl-layouts' ); ?></span><input <?php echo apply_filters( 'ddl-default-template-option-checked', 1, $this->get_default_user() );?> type="radio" name="<?php echo self::TEMPLATE_OPTION_USER;?>" value="1" /></label>
                </div>
            </div>
            <?php
            wp_nonce_field( 'ddl_template_settings_nonce', 'ddl_template_settings_nonce' );
            ?>

            <p class="update-button-wrap">
                <span class="js-wpv-messages"></span>
                <button class="js-template_settings-save button-secondary" disabled>
                    <?php _e( 'Save', 'ddl-layouts' ); ?>
                </button>
            </p>

            <div class="template-settings-messages-wrap"></div>

        </div>

    </div>
</div>

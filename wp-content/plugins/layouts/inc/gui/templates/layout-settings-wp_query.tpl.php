<div>
    <div id="toolset-admin-bar-settings" class="wpv-setting-container js-wpv-setting-container">

        <div class="wpv-setting">
            <p>
                <?php _e( "When you use a caching plugin, it's important to clear the cache after editing layouts. You can set the maximal number of pages to refresh after saving a layout. If the layout is used on more pages than this maximum, the cache clearing will not be done automatically and you will need to clear cache manually. This option is designed to prevent long delays when editing layouts that display many pages.", 'ddl-layouts' ); ?>
            </p>
            <p>
                <label>
                    <?php _e( " Maximum pages to refresh", 'ddl-layouts' ); ?>
                    <input type="text" name="ddl-max-posts-num" id="js-ddl-max-posts-num" class="js-ddl-max-posts-num" value="<?php echo self::$max_posts_num_option;?>"  />

                </label>
            </p>
            <?php
            wp_nonce_field( 'ddl_max-posts-num_nonce', 'ddl_max-posts-num_nonce' );
            ?>

            <!--<p class="update-button-wrap">
                <span class="js-wpv-messages"></span>
                <button class="js-max-posts-num-save button-secondary" disabled="disabled">
                    <?php _e( 'Save', 'ddl-layouts' ); ?>
                </button>
            </p>-->

        </div>
    </div>
</div>

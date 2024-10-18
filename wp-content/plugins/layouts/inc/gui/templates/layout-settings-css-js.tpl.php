<div>
	<div id="toolset-admin-bar-settings" class="wpv-setting-container js-wpv-setting-container">

		<div class="wpv-setting">
			<p>
				<?php _e( "Select how Toolset should load the CSS and JavaScript code added using Layouts.", 'ddl-layouts' ); ?>
			</p>
			<div>
                <p><strong><?php _e(' Load custom CSS added using Layouts:', 'ddl-layouts');?></strong></p>
                <label for="js-<?php echo $css_option_name;?>-<?php echo $layouts_scripts_global_options_values["no"];?>">
                    <input class="no-margin" type="radio" name="<?php echo $css_option_name;?>" id="js-<?php echo $css_option_name;?>-<?php echo $layouts_scripts_global_options_values["no"];?>" value="<?php echo $layouts_scripts_global_options_values["no"];?>" <?php if( $css_option_value === $layouts_scripts_global_options_values["no"] ):?> checked <?php endif;?>>
					<?php _e( "Only on pages using layouts", 'ddl-layouts' ); ?>
                </label>
                <span class="indent-8"><label for="js-<?php echo $css_option_name;?>-<?php echo $layouts_scripts_global_options_values["yes"];?>">
                    <input class="no-margin" type="radio" name="<?php echo $css_option_name;?>" id="js-<?php echo $css_option_name;?>-<?php echo $layouts_scripts_global_options_values["yes"];?>" value="<?php echo $layouts_scripts_global_options_values["yes"];?>" <?php if( $css_option_value === $layouts_scripts_global_options_values["yes"] ):?> checked <?php endif;?>>
					<?php _e( "On all pages", 'ddl-layouts' ); ?>
                    </label></span>
                <p><strong><?php _e(' Load custom JS added using Layouts:', 'ddl-layouts');?></strong></p>
                <label for="js-<?php echo $js_option_name;?>-<?php echo $layouts_scripts_global_options_values["no"];?>">
                    <input class="no-margin" type="radio" name="<?php echo $js_option_name;?>" id="js-<?php echo $js_option_name;?>-<?php echo $layouts_scripts_global_options_values["no"];?>" value="<?php echo $layouts_scripts_global_options_values["no"];?>" <?php if( $css_option_value === $layouts_scripts_global_options_values["no"] ):?> checked <?php endif;?>>
					<?php _e( " Only on pages using layouts", 'ddl-layouts' ); ?>
                </label>
                <span class="indent-8"><label for="js-<?php echo $js_option_name;?>-<?php echo $layouts_scripts_global_options_values["yes"];?>">
                    <input class="no-margin" type="radio" name="<?php echo $js_option_name;?>" id="js-<?php echo $js_option_name;?>-<?php echo $layouts_scripts_global_options_values["yes"];?>" value="<?php echo $layouts_scripts_global_options_values["yes"];?>" <?php if( $css_option_value === $layouts_scripts_global_options_values["yes"] ):?> checked <?php endif;?>>
					<?php _e( "On all pages", 'ddl-layouts' ); ?>
                    </label></span>
			</div>
			<?php
			wp_nonce_field( 'ddl_css-js_nonce', 'ddl_css-js_nonce' );
			?>

		</div>
	</div>
</div>

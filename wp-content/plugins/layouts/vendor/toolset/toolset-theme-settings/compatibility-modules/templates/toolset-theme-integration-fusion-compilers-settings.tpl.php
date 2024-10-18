<div class="toolset-advanced-setting">
			<p>
				<?php _e( "Enable/disable Fusion Compilers to enhance front-end rendering performance. For Avada Settings edited with Toolset to take effect, Fusion Compilers must be disabled.", 'wp-views' ); ?>
			</p>
			<p>
				<label>
					<input type="checkbox" name="toolset-fusion-compilers" id="js-toolset-fusion-compilers" class="js-toolset-fusion-compilers" value="1" <?php checked( $fusion_compilers_on ); ?> autocomplete="off" />
					<?php _e( " Enable Fusion Compilers", 'ddl-layouts' ); ?>&nbsp;&nbsp;<span class="js-wpv-messages js-toolset-fusion-compilers-message"></span>
				</label>
			</p>
			<?php
			wp_nonce_field( 'toolset_fusion-compilers_nonce', 'toolset_fusion-compilers_nonce' );
			?>
</div>
<div>
	<div id="toolset-admin-bar-settings" class="wpv-setting-container js-wpv-setting-container">

		<div class="wpv-setting">
			<p>
				<?php _e( "Select whether the dialog for inserting cells displays cell information.", 'ddl-layouts' ); ?>
			</p>
			<p>

				<label for="js-ddl-show-cell-details">
					<input type="radio" name="ddl-ddl-show-cell-details" id="js-ddl-show-cell-details" value="yes" <?php if( $option_value === "yes" ):?> checked <?php endif;?>>
					<?php _e( "Show a detailed description for cells before inserting", 'ddl-layouts' ); ?>
				</label>
				<br>
				<label for="js-ddl-hide-cell-details">
					<input type="radio" name="ddl-ddl-show-cell-details" id="js-ddl-hide-cell-details" value="no" <?php if( $option_value === "no" ):?> checked <?php endif;?>>
					<?php _e( "Insert cells on the first click without showing the detailed description", 'ddl-layouts' ); ?>
				</label>

			</p>
			<?php
			wp_nonce_field( 'ddl_cell-details_nonce', 'ddl_cell-details_nonce' );
			?>

		</div>
	</div>
</div>

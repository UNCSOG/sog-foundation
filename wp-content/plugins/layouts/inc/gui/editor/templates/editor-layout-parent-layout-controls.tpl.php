<span id='non-private-layout-elements' <?php if(isset($_GET['layout_type']) && $_GET['layout_type']==='private'):?>style='display:none;'<?php endif;?> >

					<span id='edit-layout-button'><a href='#post_name' class='button button-small hide-if-no-js js-edit-layout-settings'><?php _e( 'Set parent layout', 'ddl-layouts' ); ?></a></span>

					<span id='is-parent-layout-button' class='js-is-parent-layout-button is-parent js-has-parent hidden'><i class='fa fa-home icon-home-parent is-parent'></i><?php _e( 'Default parent layout', 'ddl-layouts' ); ?></span>
					<span id='set-as-parent-layout-button' class='js-set-as-parent-layout-button hidden js-has-parent'><a href='#post_name' class='button button-small hide-if-no-js js-set-as-parent-layout'><i class='fa fa-home icon-home-parent'></i><?php _e( 'Set as default parent', 'ddl-layouts' ); ?></a></span>
				</span>
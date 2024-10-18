<script type="text/html" id="ddl-bootstrap-column-size-tpl">
	<div id="js-dialog-dialog-container">
		<div class="ddl-dialog-content" id="js-dialog-content-dialog">
			<p><?php _e( 'Please select the Boostrap column width for this Layout:', 'ddl-layouts'); ?></p>
			<table id="ddl-bootstrap-column-sizes-table" class="ddl-layout-settings-table js-ddl-bootstrap-column-sizes-table">
				<thead>
				<tr>
					<th><?php _e( 'Size', 'ddl-layouts' ); ?></th>
					<th><?php _e( 'Column size', 'ddl-layouts' ); ?></th>
					<th><?php _e( 'For screen size', 'ddl-layouts' ); ?></th>
				</tr>
				</thead>
				<tbody>

                <tr><td><input checked value="{{{site_default}}}" type="radio" name="ddl-column-prefix" id="no_default_prefix" class="js-no_default_prefix site_default_prefix" /><label><?php _e('Site default', 'ddl-layouts'); ?></label></td>
                <td><span class="ddl-text">{{{site_default}}}*</span></td>
                <td><span class="ddl-text">{{{prefixes_data[site_default].size}}}</span></td>
                <tr><td colspan="3"></td></tr>
                <# var default_label = "<?php _e( '(site default)', 'ddl-layouts' ); ?>";
                        _.each( prefixes_data, function( data, prefix, list ){
                        var checked = '', label = data.label, el_class = '';
                        if( prefix === site_default ){
                            checked = 'checked';
                            //label = label + ' ' + default_label;
                            el_class = 'class="js-is-default-prefix"';
                        } #>

                <tr>
                    <td><input type="radio" value="{{{prefix}}}" name="ddl-column-prefix" {{{el_class}}}><label>{{{ label }}}</label></td>
                    <td><span class="ddl-text">{{{prefix}}}*</span></td>
                    <td><span class="ddl-text">{{{data.size}}}</span></td>
                </tr>

                <# });#>
				</tbody>
			</table>
			<p><?php _e( 'Note: The options available depend on the version of Bootstrap chosen for this site.', 'ddl-layouts' ); ?></p>
            <p><a href="<?php echo WPDDL_BOOTSTRAP_GRID_SIZE;?>" alt="Help about Bootstrap column widths"><?php _e( 'Help about Bootstrap column widths', 'ddl-layouts' ); ?>  <i class="fa fa-external-link" aria-hidden="true"></i></a></p>
		</div>
	</div>
</script>

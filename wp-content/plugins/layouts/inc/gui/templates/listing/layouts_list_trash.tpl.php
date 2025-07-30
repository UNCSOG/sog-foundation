<?php
global $wpddlayout;
?>
<div class="js-ddl-message-container dd-message-container"></div>

<div class="wrap js-dd-layout-listing">
	<div id="icon-edit fa fa-pencil-square-o" class="icon32 icon32-posts-dd_layouts"></div>
	<h1>
		<?php _e('Layouts', 'ddl-layouts');?>
		<?php do_action('ddl_create_layout_button'); ?>
		<span class="subtitle">

					</span>

	</h1>

	<ul class="subsubsub" style="clear:left"><!-- links to lists WPA in different statuses -->
		<li><a name="publish" href="<?php echo admin_url('admin.php'); ?>?page=dd_layouts&amp;status=publish"<?php if ( $this->get_arg('post_status') == 'publish' && !isset( $_GET["search"] ) ) echo ' class="current"'; ?>><?php _e('Published', 'ddl-layouts'); ?></a> (<span class="count-published"><?php echo  $this->get_count_published(); ?></span>) | </li>
		<li><a name="trash" href="<?php echo admin_url('admin.php'); ?>?page=dd_layouts&amp;status=trash"<?php if ( $this->get_arg('post_status') == 'trash' && !isset( $_GET["search"] ) ) echo ' class="current"'; ?>><?php _e('Trash', 'ddl-layouts'); ?></a> (<span class="count-trash"><?php echo $this->get_count_trash(); ?></span>)</li>
	</ul>



	<div class="tablenav top">
		<div class="alignleft actions bulkactions">
			<select name="select-bulk-action" class="js-select-bulk-action">
				<option value="-1" selected="selected"><?php _e('Bulk Actions', 'ddl-layouts');?></option>
				<?php if( $this->get_count_what() === 'trash' ): ?>
					<option value="trash"><?php _e('Move to Trash', 'ddl-layouts');?></option>
				<?php elseif( $this->get_count_what() === 'publish' ): ?>
					<option value="publish"><?php _e('Restore', 'ddl-layouts');?></option>
					<option value="delete"><?php _e('Delete permanently', 'ddl-layouts');?></option>
				<?php endif; ?>

			</select>

			<?php
			$data = array(

				'trash_nonce' => wp_create_nonce( 'layout-select-trash-nonce' ),
				'delete_nonce' => wp_create_nonce( 'layout-delete-layout-nonce' )
			);
			?>

			<input data-object="<?php echo htmlspecialchars( wp_json_encode( $data ) ); ?>" type="submit" name="" id="doaction" class="button action js-do-bulk-action" value="<?php _e('Apply', 'ddl-layouts');?>">
		</div>

		<div class="alignright">
			<p class="search-box">
				<input type="hidden" name="page" value="dd_layouts" />
				<input type="hidden" name="status" value="<?php echo $this->get_arg('post_status');?>" />
				<label class="screen-reader-text" for="post-search-input"><?php _e('Search','ddl-layouts'); ?>:</label>
				<input type="search" id="post-search-input" name="search" value="<?php _e('Search','ddl-layouts'); ?>" />
				<!--   <input type="submit" name="" id="search-submit" class="button" value="<?php echo htmlentities( __('Search Layouts','ddl-layouts'), ENT_QUOTES ); ?>" /> -->
				<input type="hidden" name="paged" value="1" />
			</p>
		</div>

	</div>
	<input type="hidden" name="page" value="dd_layouts" />
	<input type="hidden" name="status" value="<?php echo $this->get_arg('post_status');?>" />
	<input type="hidden" name="paged" value="1" />
	<table class="wp-list-table widefat posts dd-layouts-list js-listing-table" cellspacing="0">
		<thead>
		<tr>
			<th scope="col" id="bulk-action-all" class="manage-column column-bulk-actions js-column-bulk-actions" style=""><div class="listing-heading-inner-wrap"><input type="checkbox" class="js-select-all-layouts select-all-layouts" name="bulk_select" /></div>&nbsp;</th>
			<th scope="col" id="id" class="toolset-admin-listing-col-id column-id" style=""><a href="" class="js-views-list-sort views-list-sort<?php echo $this->column_active; ?>" data-orderby="id"><?php _e('ID','ddl-layouts') ?> <i class="icon-sort-by-attributes fa fa-sort-amount<?php  echo $this->column_sort_now === 'DESC' ? '-desc' : '-asc'; ?> js-icon-sort-id icon-sort"></i></a></th>
			<th scope="col" id="title" class="toolset-admin-listing-col-title column-title" style=""><a href="" class="js-views-list-sort views-list-sort<?php echo $this->column_active; ?>" data-orderby="title"><?php _e('Title','ddl-layouts') ?> <i class="fa-sort-alpha-asc fa icon-sort-by-alphabet<?php  echo $this->column_sort_now === 'DESC' ? '-desc' : '-asc'; ?> js-icon-sort-title icon-sort"></i></a></th>
			<?php if( $this->get_arg('post_status') == 'publish'):?>
				<th scope="col" id="used-on" class="manage-column column-used-on" style=""><?php _e('Used on', 'ddl-layouts');?></th>
			<?php endif;?>
			<th scope="col" id="date" class="toolset-admin-listing-col-date column-date" style=""><a href="" class="js-views-list-sort views-list-sort<?php echo $this->column_active; ?>" data-orderby="date"><?php _e('Date', 'ddl-layouts' ); ?> <i class="icon-sort-by-attributes fa fa-sort-amount<?php echo $this->column_sort_date_now === 'DESC' ? '-desc' : '-asc'; ?> js-icon-sort-date icon-sort"></i></a></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<th scope="col" id="bulk-action-all-foot" class="manage-column column-bulk-actions js-column-bulk-actions" style=""><div class="listing-heading-inner-wrap"><input type="checkbox" class="js-select-all-layouts select-all-layouts" name="bulk_select" /></div>&nbsp;</th>
			<th scope="col" id="id-foot" class="toolset-admin-listing-col-id column-id" style=""><?php _e('ID', 'ddl-layouts');?></th>
			<th scope="col" id="title-foot" class="toolset-admin-listing-col-title column-title" style=""><?php _e('Title', 'ddl-layouts');?></th>
			<?php if( $this->get_arg('post_status') == 'publish'):?>
				<th scope="col" id="used-on-foot" class="manage-column column-used-on" style=""><?php _e('Used on', 'ddl-layouts');?></th>
			<?php endif;?>
			<th scope="col" id="date-foot" class="toolset-admin-listing-col-date column-date" style=""><?php _e('Date', 'ddl-layouts');?></th>
		</tr>
		</tfoot>


	</table>
	<div class="clear"></div>


	<textarea id="listing-hidden-content" name="layouts-hidden-content"  class="js-hidden-json-textarea hidden-json-textarea" <?php if(!WPDDL_DEBUG) echo 'style="display:none"'; ?>><?php echo $init_json_listing; ?></textarea>
</div>

<script type="text/html" id="ddl-dialog-assign-layout-to-post-type">
	<div class="ddl-dialog-header">
		<h2><?php _e('Do you want to apply to all?','ddl-layouts'); ?></h2>
		<i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
	</div>
	<div class="ddl-dialog-content">
		<p>
			{{ ddl.message }}
		</p>
		<# if( ddl.template_exists === false ){ #>
		<p class=" toolset-alert toolset-alert-warning">
			<?php echo sprintf(__("A template file that supports layouts is not available for the %s post type.", 'ddl-layouts'), '<strong><# ddl.singular #></strong>') ?><br>
			<?php printf(__('Please check %shere%s to see how to set one up.', 'ddl-layouts'), '<a href="' . WPDLL_LEARN_ABOUT_SETTING_UP_TEMPLATE . '" target="_blank">', '</a>'); ?>
		</p>
		<# } #>
	</div>

	<div class="ddl-dialog-footer">
		<button class="button js-edit-dialog-close"><?php _e('Cancel', 'ddl-layouts') ?></button>
		<button class="button button-primary js-ddl-update-posts-process" data-in-listing-page="yes">
			<?php echo sprintf(__('Update %s now', 'ddl-layouts'), '{{{ ddl.label }}}') ?></button>
	</div>
</script>

<script type="text/html" id="ddl-layout-to-meta-confirm-box">

	<div class="ddl-dialog-header">
		<h2><?php _e('Success', 'ddl-layouts');?></h2>
		<i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
	</div>
	<div class="ddl-dialog-content">
		<?php printf(__('All %s were updated.', 'ddl-layouts'), '{{{ ddl.label }}}'); ?>
	</div>
	<div class="ddl-dialog-footer">
		<button class="button js-edit-dialog-close close-change-use"><?php _e('Close', 'ddl-layouts'); ?></button>
	</div>

</script>

<div class="ddl-dialogs-container">
	<div class="ddl-dialog auto-width" id="ddl-dialog-assign-layout-to-post-type-wrap"></div>
</div>

<div class="ddl-dialogs-container">
	<div id="ddl-layout-to-meta-confirm-box-wrap" class="ddl-dialog"></div>
</div>
<div id="tab-single">
	<table class="wp-list-table widefat posts dd-layouts-list js-listing-table" cellspacing="0">
		<thead id="single">
		<tr>
			<th scope="col" id="single-bulk-action-all" class="manage-column column-bulk-actions js-column-bulk-actions" style=""><div class="listing-heading-inner-wrap"><input type="checkbox" class="js-select-all-layouts select-all-layouts" name="bulk_select" /></div>&nbsp;</th>
			<th scope="col" id="single-id" class="toolset-admin-listing-col-id column-id" style=""><a href="" class="js-views-list-sort views-list-sort<?php echo $this->column_active; ?>" data-orderby="id"><?php _e('ID','ddl-layouts') ?> <i class="icon-sort-by-attributes fa fa-sort-amount<?php  echo $this->column_sort_now === 'DESC' ? '-desc' : '-asc'; ?> js-icon-sort-id icon-sort"></i></a></th>
			<th scope="col" id="single-title" class="toolset-admin-listing-col-title column-title" style=""><a href="" class="js-views-list-sort views-list-sort<?php echo $this->column_active; ?>" data-orderby="title"><?php _e('Title','ddl-layouts') ?> <i class="fa-sort-alpha-asc fa icon-sort-by-alphabet<?php  echo $this->column_sort_now === 'DESC' ? '-desc' : '-asc'; ?> js-icon-sort-title icon-sort"></i></a></th>
			<?php if( $this->get_arg('post_status') == 'publish'):?>
				<th scope="col" id="single-used-on" class="manage-column column-used-on" style=""><?php _e('Used on', 'ddl-layouts');?></th>
			<?php endif;?>
			<th scope="col" id="single-date" class="toolset-admin-listing-col-date column-date" style=""><a href="" class="js-views-list-sort views-list-sort<?php echo $this->column_active; ?>" data-orderby="date"><?php _e('Date', 'ddl-layouts' ); ?> <i class="icon-sort-by-attributes fa fa-sort-amount<?php echo $this->column_sort_date_now === 'DESC' ? '-desc' : '-asc'; ?> js-icon-sort-date icon-sort"></i></a></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<th scope="col" id="single-bulk-action-all-foot" class="manage-column column-bulk-actions js-column-bulk-actions" style=""><div class="listing-heading-inner-wrap"><input type="checkbox" class="js-select-all-layouts select-all-layouts" name="bulk_select" /></div>&nbsp;</th>
			<th scope="col" id="single-id-foot" class="toolset-admin-listing-col-id column-id" style=""><?php _e('ID', 'ddl-layouts');?></th>
			<th scope="col" id="single-title-foot" class="toolset-admin-listing-col-title column-title" style=""><?php _e('Title', 'ddl-layouts');?></th>
			<?php if( $this->get_arg('post_status') == 'publish'):?>
				<th scope="col" id="single-used-on-foot" class="manage-column column-used-on" style=""><?php _e('Used on', 'ddl-layouts');?></th>
			<?php endif;?>
			<th scope="col" id="single-date-foot" class="toolset-admin-listing-col-date column-date" style=""><?php _e('Date', 'ddl-layouts');?></th>
		</tr>
		</tfoot>


	</table>

	<div class="clear"></div>

</div>
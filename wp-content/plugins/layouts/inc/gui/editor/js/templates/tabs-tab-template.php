<script type="text/html" id="tabs-tab-template">
	<# if ( invisibility === undefined || invisibility === false ) { #>
	<div class="row-toolbar js-row-toolbar">

		<span class="js-element-name element-name tab-title"><# print( unescape( name ) ) #></span>
		<div class="row-actions js-row-actions">
			<i class="icon-pencil fa fa-pencil js-row-edit js-row-edit-icon" data-tooltip-text="Edit tab"></i>  <?php // TODO: Localize data attribute  ?>
			<i class="fa fa-remove icon-remove icon-remove-enabled js-row-remove js-row-remove-icon" data-tooltip-text="Remove tab"></i>  <?php // TODO: Localize data attribute  ?>
		</div>
	</div>
	<# } #>
	<div class="row row-{{layout_type}} container-row-view">

	</div>
	<# if ( invisibility === undefined || invisibility === false ) { #>
	<p class="add-row">
		<button class="button-secondary add-row-button js-add-row js-highlight-row<#if ( layout_type == 'fixed' ) { #> add-row-button-fixed<# } else { #> add-row-button-fluid<# } #>" type="button"><i class="icon-plus fa fa-plus"></i></button><#if ( layout_type == 'fluid' ) { #><button class="button-secondary js-show-add-tab-menu js-highlight-row add-row-menu-toggle" type="button"><i class="fa fa-bars js-icon-caret"></i></button><# } #>
	</p>
	<# } #>
</script>

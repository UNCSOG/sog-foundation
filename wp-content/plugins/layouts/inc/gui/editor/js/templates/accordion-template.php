<script type="text/html" id="accordion-template">
	<# if ( invisibility === undefined || invisibility === false ) { #>
	<div class="row-toolbar js-row-toolbar">
		<i class="fa fa-arrows icon-move js-move-row"></i>
		<span class="js-element-name element-name"><# print( unescape( name ) ) #></span>
		<div class="row-actions js-row-actions">
			<i class="icon-pencil fa fa-pencil js-container-edit" data-tooltip-text="Edit accordion"></i> <?php // TODO: Localize data attribute  ?>
			<i class="fa fa-remove icon-remove js-container-remove" data-tooltip-text="Remove accordion"></i> <?php // TODO: Localize data attribute  ?>
		</div>
	</div>
	<# } #>

	<div class="cell-content container-content">
		<div class="container-rows js-container-rows accordion-panel js-accordion-panel">

		</div>
	</div>

</script>
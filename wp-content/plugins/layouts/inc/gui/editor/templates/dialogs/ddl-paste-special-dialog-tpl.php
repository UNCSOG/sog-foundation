<!-- PASTE FAILS NOT COMPATIBLE -->
<script type="text/html" id="ddl-paste-special-dialog-tpl">
	<# if( kind === 'Panel'){kind = 'Accordion';}#>
		<div id="js-dialog-dialog-container">
			<div class="ddl-dialog-content" id="js-dialog-content-dialog">
				<span class="dialog-alert"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span>
				<span class="dialog-content"><?php printf(
						__('The element you want to paste has to be copied in a %s%s%s structure, it is not compatible here.', 'ddl-layouts'), '<strong>', '{{{kind}}}', '</strong>');
					?></span>
			</div>
		</div>
</script>
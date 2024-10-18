<!-- PASTE FAILS MULTIPLE NOT ALLOWED  -->
<script type="text/html" id="ddl-paste-cell-forbidden-dialog-tpl">
	<div id="js-dialog-dialog-container">
		<div class="ddl-dialog-content" id="js-dialog-content-dialog">
			<span class="dialog-alert"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span>
			<span class="dialog-content"><?php printf(
					__('Do you really need another %s%s%s cell in %s%s%s layout? This cell can appear only once in each layout, so you cannot paste the selected row into this layout. If you select a different row, that doesn’t include this kind of cell, you will be able to paste it.', 'ddl-layouts'), '<strong>', '{{{forbidden_type}}}', '</strong>', '<strong>', '{{{layout_name}}}', '</strong>');
				?></span>
		</div>
	</div>
</script>
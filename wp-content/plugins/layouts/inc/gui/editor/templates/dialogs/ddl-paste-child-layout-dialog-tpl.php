<!-- CHILD LAYOUT -->
<script type="text/html" id="ddl-paste-child-layout-dialog-tpl">
	<div id="js-dialog-dialog-container">
		<div class="ddl-dialog-content" id="js-dialog-content-dialog">
			<span class="dialog-alert"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span>
			<span class="dialog-content"><?php printf(
					__('You just added a child layout cell to %s%s%s layout. Now, you need to create the layout that will appear inside this cell.
.', 'ddl-layouts'), '<strong>', '{{{layout_name}}}', '</strong>');
				?></span>
		</div>
	</div>
</script>
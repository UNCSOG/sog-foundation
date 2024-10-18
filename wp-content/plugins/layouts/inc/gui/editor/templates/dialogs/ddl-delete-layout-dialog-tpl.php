<!-- ASSIGN  -->
<script type="text/html" id="ddl-delete-layout-dialog-tpl">
	<div id="js-dialog-dialog-container">
		<div class="ddl-dialog-content" id="js-dialog-content-dialog">
			<span class="dialog-alert"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span>
			<span class="dialog-content"><?php printf(
					__('%s is assigned to render WordPress resources in front-end and cannot be deleted. To delete it remove assignments first and try again.', 'ddl-layouts'), '{{{layout_name}}}');
				?></span>
		</div>
	</div>
</script>
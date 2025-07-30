<script type="text/html" id="ddl-remove-assigments-dialog-tpl">
	<div id="js-dialog-dialog-container">
		<div class="ddl-dialog-content" id="js-dialog-content-dialog">
			<span class="dialog-alert-child-cell"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span>
			<span class="dialog-content">
                        <?php printf(
	                        __('Putting Child Layout cell will make this Layout a parent layout. %s Parent layouts cannot be assigned to any content. %s %s Do you want to remove content assignment of this layout?%s', 'ddl-layouts'),'<br>','<br><br>','<strong>','</strong>');
                        ?>
                    </span>

		</div>
	</div>
</script>
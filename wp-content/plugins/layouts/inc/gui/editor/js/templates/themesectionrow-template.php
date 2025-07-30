<script type="text/html" id="themesectionrow-template">

	<div class="row-toolbar js-row-toolbar">
		<i class="fa fa-arrows-v icon-resize-vertical js-move-row"></i>
		<span class="js-element-name element-name"><# print( unescape( name ) ) #></span>
		<div class="row-actions js-row-actions">
			<i class="icon-pencil fa fa-pencil js-row-edit js-row-edit-icon" data-tooltip-text="<?php _e('Edit row', 'ddl-layouts'); ?>"></i>
			<i class="fa fa-remove icon-remove icon-remove-enabled js-row-remove js-row-remove-icon" data-tooltip-text="<?php _e('Remove row', 'ddl-layouts'); ?>"></i>
		</div>
	</div>

	<div class="row js-row row-{{layout_type}} row-{{ kind.toLowerCase() }}">
		<div class="theme-section-preview">
			<?php _e('Custom row', 'ddl-layouts'); ?> - {{ DDLayout.themeSectionsRow_data[type] }}
		</div>

	</div>

	<p class="add-row">
		<?php // Do not add a line break between buttons! ?>
		<button class="button-secondary js-highlight-row js-add-row add-row-button" type="button"><i class="icon-plus fa fa-plus"></i></button><button class="button-secondary js-highlight-row js-show-add-special-row-menu add-row-menu-toggle" type="button"><i class="fa fa-caret-down js-icon-caret"></i></button>
	</p>
</script>
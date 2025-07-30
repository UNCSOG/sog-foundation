<?php ?>
<script type="text/html" id="tpl-toolset-frontend-cell">
    <div class="ddl-frontend-editor-overlay ddl-frontend-editor-overlay-cell">
        <div class="ddl-block-overlay-header">
            <div class="ddl-block-overlay-title">{{{ kind }}}: {{{ name }}}</div>
            <div class="ddl-block-overlay-actions">
                <i class="ddl-block-settings fa fa-pencil ddl-tip" title="Edit cell" data-action="edit-element"></i>
                <i class="ddl-block-settings fa fa-wrench ddl-tip" title="Show hierarchy" data-action="list-settings"></i>
            </div>
        </div>
    </div>
</script>

<script type="text/html" id="tpl-toolset-frontend-cell-nothing">
<# if( is_not_editable ) { #>
                        <span class="fa-stack fa ddl-block-settings ddl-tip" title="Edit cell" data-action="edit-element">
                            <i class="fa fa-square-o fa-stack-2x" data-action="edit-element" title="Edit cell"></i>
                            <i class="fa fa-pencil fa-stack-1x" title="Edit cell" data-action="edit-element"></i>
                        </span>
    <# } else { #>
        <i class="ddl-block-settings fa fa-pencil ddl-tip" title="Edit cell" data-action="edit-element"></i>

        <# } #>
</script>
<?php ?>
<script type="text/html" id="tpl-toolset-frontend-context-menu">
    <div class="ddl-element-action-panel">
        <div class="ddl-element-action-panel-content">
            <# _.each(layout_names, function( layout_name, index ){ #>

            <div class="title layout_title_{{{index}}}" title="{{{layout_name.title}}}">
                {{{ layout_name.name }}}
            </div>
                <#
                    });
                    #>
            <ul class="items inactive js-hierarchy"></ul>
        </div>
    </div>
</script>
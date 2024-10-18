<script type="text/html" id="ddl-element-tooltip">
    <p>
	    <# if ( tag !== "" ) { #>
	    <b><?php _e('HTML Tag: ','ddl-layouts');?></b> {{{tag}}}<br>
	    <# } #>
	    <# if ( additionalCssClasses !== "" ) { #>
		<b><?php _e('Class: ','ddl-layouts');?></b> {{{additionalCssClasses}}}<br>
	    <# } #>
	    <# if ( cssId !== "" ) { #>
	    <b><?php _e('ID: ','ddl-layouts');?></b> {{{cssId}}}
	    <# } #>
    </p>
</script>
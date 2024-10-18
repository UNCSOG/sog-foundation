<div class="wrap">
	<h2><?php _e('Layouts Troubleshoot', 'ddl-layouts'); ?></h2>
    <p><?php
    _e( 'There are some common issues related to Layouts that can be resolved here.', 'ddl-layouts' );
	?></p>
    <h3><?php _e('Pagination links on archive cells', 'ddl-layouts'); ?></h3>
    <p><?php
	_e( 'On a previous version of Layouts, pagination links were added automatically to WordPress Archive loop cells in a way that can not be manually removed.', 'ddl-layouts');
    ?></p>
	<p><?php
	_e( 'Click the button below to remove those pagination links.', 'ddl-layouts');
    ?></p>
	<p>
	<button id="js-remove-layouts-loop-pagination-links" class="button button-primary" data-nonce="<?php echo wp_create_nonce( 'ddl_remove_layouts_loop_pagination_links' ); ?>"><?php
	_e( 'Remove archive pagination links', 'ddl-layouts');
	?></button>
	</p>

</div>

<script>
jQuery( document ).on( 'click', '#js-remove-layouts-loop-pagination-links', function() {
	var thiz = jQuery( this ),
	data = {
		action: 'ddl_remove_layouts_loop_pagination_links',
		wpnonce: thiz.data( 'nonce' )
	};
	thiz
		.addClass( 'button-secondary' )
		.removeClass( 'button-primary' )
		.prop( 'disabled', true );
	jQuery.post( ajaxurl, data, function( response ) {
		var ok_feedback = jQuery('<span style="color:green;font-weight:bold;margin-left:10px;display:none;"><?php _e( 'Done', 'ddl-layouts'); ?></span>').insertAfter(thiz).fadeIn('fast');
		setTimeout( function() {
			ok_feedback.fadeOut('fast');
		}, 1000);
	}, 'json' )
	.fail( function( jqXHR, textStatus, errorThrown ) {
		console.log( "Error: ", textStatus, errorThrown );
	})
	.always( function() {
		thiz
			.removeClass( 'button-secondary' )
			.addClass( 'button-primary' )
			.prop( 'disabled', false );
	});
});
</script>
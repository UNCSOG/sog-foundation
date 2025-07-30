( function ( $, wpma ) {
	"use strict";

	const $doc = $( document );
	const { restUrl, restNonce } = wpma;

	// select/deselect all table rows
	$doc.on( "click", '.wdm-wpma-select-all', function () {
		const checked = this.checked;
		$( 'table[class*="mail-queue_page"] input[name="id[]"],.wdm-wpma-select-all' ).each( function () {
			this.checked = checked;
		});
	});

	// dynamically load message when opening a details element for the first time
	$doc.on( "click", '[data-wdm-wpma-list-message-toggle]',function () {
		const $btn = $( this );
		const id = $btn.attr( "data-wdm-wpma-list-message-toggle" );
		$btn.attr( "data-wdm-wpma-list-message-toggle", null );
		$.get( `${restUrl}wpma/v1/message/${id}`, { _wpnonce: restNonce } ).always( function ( response, status ) {
			if ( status === "success" && response.status === "ok" ) {
				$( '[data-wdm-wpma-list-message-content]', $btn.closest( 'details' ) ).html( response.data.html );
			} else {
				const responseData = response.responseJSON || response.data;
				console.log( responseData );
				alert( "There was an error loading the message." );
			}
		});
	});

}) ( jQuery, wpma );

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

	// status/date filters: auto-submit form on change
	function wdmWpmaSubmitFilters ( el ) {
		const form = el.closest( "form" );
		if ( form ) { form.submit(); }
	}
	$doc.on( "change", ".wdm-wpma-autofilter", function () {
		wdmWpmaSubmitFilters( this );
	});
	// search: submit on Enter via JS, not native (native would fire bulk "Apply", the first submit button)
	$doc.on( "keydown", "#wdm-wpma-search-input", function ( e ) {
		if ( e.key === "Enter" ) {
			e.preventDefault();
			wdmWpmaSubmitFilters( this );
		}
	});

	// sending-rate notice: show when settings exceed 20/min (minutes) or 1 per 2s (seconds)
	function wdmWpmaCheckSendingRate () {
		const notice = document.getElementById( "wdm-wpma-rate-notice" );
		if ( !notice ) { return; } // settings tab only
		const amount   = parseInt( $( '[name="wdm_wpma_settings[queue_amount]"]' ).val(), 10 );
		const interval = parseInt( $( '[name="wdm_wpma_settings[queue_interval]"]' ).val(), 10 );
		const unit     = $( '[name="wdm_wpma_settings[queue_interval_unit]"]' ).val();
		let over = false;
		if ( amount > 0 && interval > 0 ) { // invalid/empty input → no warning
			const limit = unit === "seconds" ? 0.5 : 20; // per second / per minute
			over = amount / interval > limit;
		}
		notice.hidden = !over;
	}
	$doc.on( "input change", '[name="wdm_wpma_settings[queue_amount]"],[name="wdm_wpma_settings[queue_interval]"],[name="wdm_wpma_settings[queue_interval_unit]"]', wdmWpmaCheckSendingRate );
	$( wdmWpmaCheckSendingRate ); // settings may already be over the limit on load

	// lazy-load message body on first details open
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

(function( $ ) {
	var timeoutDoUpdate = null;
	var scriptDataNode = document.getElementById( 'toolset_common_es_data' );
	

	if ( scriptDataNode ) {
		var ScriptData = JSON.parse( WPV_Toolset.Utils.editor_decode64( scriptDataNode.textContent ) );
	}
	wp.apiFetch.use( wp.apiFetch.createNonceMiddleware( ScriptData.wp_rest_nonce ) );

	$( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );

	/* Append block id to selector */
	$( document ).on( 'change', '.js-wpv-add-block-id-to-selectors', ( e ) => {
		var isChecked = e.target.checked ? 1 : 0;

		var errorContainer = $( '.tces-settings-add-block-id-to-selectors-error' );
		wp.apiFetch( {
			path: ScriptData[ 'Route/ToolsetSettings' ],
			method: 'POST',
			data: {
				action: 'add-block-id-to-selectors',
				'is-checked': isChecked,
			},
		} ).then(
			( result ) => {
				if( result.error ) {
					errorContainer.find('.notice').html( result.error );
					errorContainer.show();
					return;
				}
				errorContainer.hide();
				$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
			}
		).catch(
			( error ) => {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			}
		);
	} );

	/* Responsive Breakpoints */
	$( document ).on( 'change input', '.js-wpv-rwd-device', ( e ) => {
		clearTimeout( timeoutDoUpdate );
		timeoutDoUpdate = setTimeout( () => doUpdate( $( e.target ) ), 800 );
	} );

	function doUpdate( input ) {
		var errorContainer = $( '.tces-settings-rwd-error' );
		var devices = {};
		$( 'input.js-wpv-rwd-device' ).each( function() {
			devices[ $( this ).data( 'device-key' ) ] = { maxWidth: parseInt( $( this ).val(), 10 ) };
		} );

		wp.apiFetch( {
			path: ScriptData[ 'Route/ToolsetSettings' ],
			method: 'POST',
			data: {
				action: 'update-devices-max-width',
				devices,
			},
		} ).then(
			( result ) => {
				if( result.error ) {
					errorContainer.find('.notice').html( result.error );
					errorContainer.show();
					return;
				}
				errorContainer.hide();
				$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
			}
		).catch(
			( error ) => {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			}
		);
	}
} )( jQuery );


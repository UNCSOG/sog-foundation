/**
 * This will load on the Add or Edit Post pages if the wp-components dependency is available (WordPress 5.0+ or
 * Gutenberg plugin active) and add an element to the top bar.
 *
 * @since 2.5.2
 */
import ReactDOM from 'react-dom';
import PrivateLayoutCreateButton from './components/PrivateLayoutCreateButton';
import '../css/create-button.css';

/**
 *
 * @type {*|{}}
 */
const DDLayout = window.DDLayout || {};

// noinspection JSUnresolvedVariable
const PrivateLayoutCreateButtonSettings = window.PrivateLayoutCreateButtonSettings || {};
/**
 *
 * @constructor
 */
DDLayout.PrivateLayoutCreateButtonMain = function() {
	const settings = PrivateLayoutCreateButtonSettings,
		{ hasPrivateLayout, isPrivateLayoutInUse, postId, editorUrl, userCanEditPrivate, postType } = settings;

	this.init = () => {
		deferAddingButton( deferAddingButton );
	};

	const addButton = function() {
		const $editorToolbar = jQuery( '#editor.block-editor__container .edit-post-header-toolbar' );
		if ( $editorToolbar.length === 0 ) {
			return;
		}

		// Manually append the placeholder for our component.
		const placeholderId = 'layouts-create-private-layout-button';
		$editorToolbar.append( `<div id="${ placeholderId }"></div>` );

		ReactDOM.render( <PrivateLayoutCreateButton data-toolbar-item={ true } hasPrivateLayout={ hasPrivateLayout }
			isPrivateLayoutInUse={ isPrivateLayoutInUse } postId={ postId } editorUrl={ editorUrl }
			userCanEditPrivate={ userCanEditPrivate } postType={ postType } />, document.querySelector( '#' + placeholderId ) );
	};

	/**
	 * Recursive method based on time very much like setInterval()
	 * It may be necessary to defer the operation several times until the editor is ready.
	 *
	 * @param {function} deferCallback Callback for trying again if the post editor is not initialized yet.
	 */
	const deferAddingButton = function( deferCallback ) {
		const $editor = jQuery( '#editor.block-editor__container' );
		if ( $editor.length === 0 ) {
			// This most probably means we're in the classic editor already.
			return;
		}

		const $editorToolbar = $editor.find( '.edit-post-header-toolbar' );
		if ( $editorToolbar.length === 0 ) {
			setTimeout( _.partial( deferCallback, deferCallback ), 1 );
			return;
		}

		addButton();
	};
};

// In the block editor, show a button inside a metabox for creating or re-connect a private layout.
jQuery( function() {
	const privateLayoutCreateButtonMain = new DDLayout.PrivateLayoutCreateButtonMain();
	privateLayoutCreateButtonMain.init();
} );

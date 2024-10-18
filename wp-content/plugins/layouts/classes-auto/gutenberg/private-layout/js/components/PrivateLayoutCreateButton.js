/**
 * @since 2.5.2
 * @author Riccardo Strobbia
 * A wrapper for a button to create a private layout or re-connect an existing one and redirect to its editor
 */
import { Button } from '@wordpress/components';

const { Component } = wp.element;
const { __ } = wp.i18n;

const DDLayout = window.DDLayout;
const Toolset = window.Toolset;

class PrivateLayoutCreateButton extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			hasPrivateLayout: !! this.props.hasPrivateLayout,
			isPrivateLayoutInUse: !! this.props.isPrivateLayoutInUse,
			editorUrl: this.props.editorUrl,
			postId: this.props.postId,
			postType: this.props.postType,
			userCanEditPrivate: this.props.userCanEditPrivate === '1',
		};

		this.handleClick.bind( this );
		Toolset.hooks.addAction( 'ddl_private_layout_usage_stopped', this.stopUsingPrivateLayoutCallback );
		Toolset.hooks.addAction( 'ddl_private_layout_created', this.updatePrivateLayoutStates, 10, 1 );
	}

	stopUsingPrivateLayoutCallback = () => {
		this.setState( { isPrivateLayoutInUse: false } );
	};

	handleClick = ( event ) => {
		event.preventDefault();
		if ( ! this.state.userCanEditPrivate ) {
			return false;
		}
		if ( this.state.hasPrivateLayout && ! this.state.isPrivateLayoutInUse ) {
			this.setState( { isPrivateLayoutInUse: true } );
			this.updatePrivateLayout( event );
		} else if ( ! this.state.hasPrivateLayout && ! this.state.isPrivateLayoutInUse ) {
			this.createPrivateLayout( event );
		}
	};

	createPrivateLayout = ( event ) => {
		if ( _.isObject( DDLayout ) && DDLayout.new_layout_dialog instanceof DDLayout.NewLayoutDialog ) {
			DDLayout.new_layout_dialog.privateLayoutNewTop( event );
		} else {
			const createLayout = new DDLayout.NewLayoutDialog();
			createLayout.privateLayoutNewTop( event );
		}
	};

	updatePrivateLayoutStates = ( layoutId ) => {
		if ( typeof layoutId !== 'undefined' && layoutId !== 0 ) {
			this.setState( { hasPrivateLayout: true, isPrivateLayoutInUse: true } );
		}
	};

	updatePrivateLayout = ( event ) => {
		const updateManager = new DDLayout.UseLayoutsAsPageBuilderManager( jQuery, jQuery( event.target ) );
		updateManager.update_status( event );
	};

	/**
	 * @return {string}
	 */
	getClassNames = () => {
		const classes = 'layouts-create-private-layout-button-wrap';

		return this.state.isPrivateLayoutInUse ? classes + ' hidden' : classes;
	};

	/**
	 * @return {*}
	 */
	getIconButton = () => {
		if ( this.state.userCanEditPrivate ) {
			return ( <Button
				isSecondary
				data-layout_type="private"
				data-layout_id={ this.state.postId }
				data-post_type={ this.state.postType }
				data-content_id={ this.state.postId }
				data-editor="editor"
				onClick={ this.handleClick }
				data-toolbar-item={ true }
				className="button-primary-toolset js-layout-private-use"
			>{ __( 'Content Layout Editor', 'ddl-layouts' ) }</Button> );
		} else {
			return ( <Button
				isSecondary
				onClick={ this.doNothing }
				data-toolbar-item={ true }
				className="button-primary-toolset js-layout-private-use"
				disabled
			>{ __( 'Content Layout Editor', 'ddl-layouts' ) }</Button> );
		}
	};

	doNothing = ( event ) => {
		event.preventDefault();
		console.log( __( 'User doesn\'t have the rights to edit layouts', 'ddl-layouts' ) );
		return false;
	};

	/**
	 *
	 * @return {*}
	 */
	render() {
		return (
			<div data-toolbar-item={ true } className={ this.getClassNames() }>
				{ this.getIconButton() }</div>
		);
	}
}

export default PrivateLayoutCreateButton;

/**
 * Manage the post form editor toolbar.
 *
 * @see Toolset.CRED.FieldsControlPrototype
 *
 * @since m2m
 * @package CRED
 */

var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.PostFieldsControl = function( $ ) {
	Toolset.CRED.FieldsControlPrototype.call( this, $ );

    var self = this;

	/**
	 * Get script domain.
	 *
	 * @since 2.1
	 */
	self.getDomain = function() {
		return 'post';
    };

    /**
	 * Get currently affected post type.
	 *
	 * @since 2.1
	 */
	self.getPostType = function() {
		return $( '#cred_custom_posts' ).val();
    };

	/**
	 * Initialize localization strings.
	 *
	 * @since 2.1
	 */
	self.initI18n = function() {
		this.i18n = cred_post_fields_control_i18n;
		return this;
	};

	self.init();

};

Toolset.CRED.PostFieldsControl.prototype = Object.create( Toolset.CRED.FieldsControlPrototype.prototype );

jQuery( function( $ ) {
	new Toolset.CRED.PostFieldsControl( $ );
});

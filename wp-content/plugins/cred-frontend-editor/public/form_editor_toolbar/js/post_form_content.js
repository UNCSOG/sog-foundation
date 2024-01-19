/**
 * Manage the post form editor toolbar.
 *
 * @see Toolset.CRED.EditorToolbarPrototype
 *
 * @since m2m
 * @package CRED
 */

var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.PostFormsContentEditorToolbar = function( $ ) {
	Toolset.CRED.EditorToolbarPrototype.call( this );

	var self = this;

	/**
	 * Initialize localization strings.
	 *
	 * @since 2.1
	 */
	self.initI18n = function() {
		self.i18n = cred_post_form_content_editor_toolbar_i18n;
		return self;
	};

	/**
	 * Init cache. Maybe populate it with fields for the currenty selected object key.
	 *
	 * @since 2.3.1
	 */
	self.initCache = function() {
		self.fieldsCache = _.has( self.i18n, 'initialCache' ) ? self.i18n.initialCache : {};
		return self;
	};

	/**
	 * Init Toolset hooks.
	 *
	 * @uses Toolset.hooks
	 * @since 2.1
	 */
	Toolset.CRED.PostFormsContentEditorToolbar.prototype.initHooks = function() {
		self.constructor.prototype.initHooks.call( self );

		Toolset.hooks.addFilter( 'toolset-filter-shortcode-gui-cred_field-computed-attribute-values', self.adjustAttributes, 10 );
		Toolset.hooks.addFilter( 'toolset-filter-shortcode-gui-cred_field-crafted-shortcode', self.adjustCraftedShortcode, 10 );

		return self;
	};

	/**
	 * Get the current form slug.
	 *
	 * @return string
	 * @since 2.2.1
	 */
	self.getFormSlug = function() {
		return $( '#post_name' ).val();
	};

	/**
	 * Get the object key to manipulate fields for.
	 *
	 * @return string
	 * @since 2.1
	 */
	self.getObjectKey = function() {
		return $( '#cred_post_type' ).val();
	};

	/**
	 * Adjust the attributes for the taxonomy fields.
	 *
	 * @param {object} attributes
	 *
	 * @return {object}
	 *
	 * @since 2.1
	 */
	self.adjustTaxonomyAtributes = function( attributes ) {
		attributes.add_new = false;
		attributes.show_popular = false;
		if ( _.has( attributes, 'display' ) ) {
			switch ( attributes.display ) {
				case 'select':
					attributes.single_select = 'true';
					break;
				case 'multiselect':
					attributes.display = 'select';
					break;
			}
		}
		return attributes;
	};

	/**
	 * Adjust the form shortcode attributes when generated as an individual field.
	 *
	 * @param {object} attributes
	 * @param {object} data
	 *
	 * @return {object}
	 *
	 * @since 2.1
	 */
	self.adjustAttributes = function( attributes, data ) {
		if (
			_.has( data.rawAttributes, 'force_type' )
			&& 'taxonomy' == data.rawAttributes.force_type
		) {
			attributes = self.adjustTaxonomyAtributes( attributes );
		}

		attributes.scaffold_field_id = false;

		return attributes;
	};

	/**
	 * Maybe generate auxiliar shortcodes for the taxonomy fields.
	 *
	 * @param {object} data
	 *
	 * @return {string}
	 *
	 * @since 2.1
	 */
	self.maybeExtendTaxonomyCraftedShortcode = function( data ) {
		var rawAttributes = data.rawAttributes,
			outcome = '';

		// Manage "add new" for hierarchical taxonomies
		if (
			_.has( rawAttributes, 'add_new' )
			&& 'yes' == rawAttributes.add_new
		) {
			outcome += '[cred_field field="' + rawAttributes.field + '_add_new" taxonomy="' + rawAttributes.field + '" type="add_new"]';
		}

		// Manage "show popular" for flat taxonomies
		if (
			_.has( rawAttributes, 'show_popular' )
			&& 'yes' == rawAttributes.show_popular
		) {
			outcome += '[cred_field field="' + rawAttributes.field + '_popular" taxonomy="' + rawAttributes.field + '" type="show_popular"]';
		}

		return outcome;
	};

	/**
	 * Adjust the crafted string in some cases for special shortcodes when generated as an individual field.
	 *
	 * @param {string} shortcodeString
	 * @param {object} data
	 *
	 * @return {string}
	 *
	 * @since 2.1
	 */
	self.adjustCraftedShortcode = function( shortcodeString, data ) {
		if (
			! _.has( data.rawAttributes, 'force_type' )
			|| 'taxonomy' == data.rawAttributes.force_type
		) {
			var maybeExtended = self.maybeExtendTaxonomyCraftedShortcode( data );
			if ( '' != maybeExtended ) {
				shortcodeString += "\n" + maybeExtended;
			}
		}

		return shortcodeString;
	};

	self.init();

};

Toolset.CRED.PostFormsContentEditorToolbar.prototype = Object.create( Toolset.CRED.EditorToolbarPrototype.prototype );

jQuery( function( $ ) {
	new Toolset.CRED.PostFormsContentEditorToolbar( $ );
});

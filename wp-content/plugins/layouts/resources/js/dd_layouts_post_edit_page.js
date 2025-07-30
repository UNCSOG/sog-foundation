if ( typeof DDLayout == 'undefined' ) {
    DDLayout = {};
}

var DDLayout = DDLayout || {};

DDLayout.templateSelector = function ( $ ) {
    var self = this;
    var $selector_populated = false;

    self.init = function () {
        self._stop_using_template_layout();
        self._handle_layout_change();
        self._update_selected_layout();
        self._on_layout_selector_open();
        _.defer(function () {
            self._hide_content_template_selector_on_init();
        });
    };

    self._handle_layout_change = function ( event ) {

        var $AssignedLayoutID = jQuery( '#js-assigned-layout-id' );

        jQuery('.js-edit-layout-template').attr('href', jQuery('.js-edit-layout-template').data('href') + $AssignedLayoutID.val());

        jQuery( '.js-edit-layout-template' ).attr( 'href', jQuery('.js-edit-layout-template' ).data( 'href' ) + $AssignedLayoutID.val() );

        jQuery( '#ddl-js-layout-template-name' ).change(function() {

            var $editLayoutTempalate = jQuery( '.js-edit-layout-template' );


            var layout_id = jQuery( '#ddl-js-layout-template-name option:selected' ).data( 'id' );
			// Option with value 0 has no id attribute
			layout_id = layout_id ? layout_id : 0;
            if ( layout_id != '0' ) {
                $editLayoutTempalate.attr( 'href', $editLayoutTempalate.data( 'href' ) + layout_id );
                $AssignedLayoutID.val( layout_id );
                self._disable_content_template( true );
            } else {
                jQuery( '.js-edit-layout-template' ).hide();
                self._disable_content_template( false );
            }

        });
    };

    self._stop_using_template_layout = function(){
        jQuery( '.js_ddl_stop_using_this_template_layout' ).click( function( event ) {
            event.preventDefault();

            // do ajax request to update layout for this single page
            var data = {
                'action': 'ddl_stop_using_template_layout',
                'post_id': DDLayout_settings_post_edit.DDL_JS.post.ID,
                'wpnonce': DDL_Private_layout.private_layout_nonce,
            };
            jQuery.post( ajaxurl, data, function( response ) {

                // hide overlay here
                if( DDLayout_settings_post_edit.DDL_JS.post.private_layout_in_use !== 'yes' ){
                    jQuery( '.js-ddl-dummy-container' ).hide( 'slow' );
                }

                jQuery( '.ddl-layout-selected' ).hide();
                jQuery( '.js-dd-layout-selector' ).show();

                jQuery( '#ddl-js-layout-template-name option[value="-1"]' ).attr( 'selected', 'selected' );
				self._disable_content_template(false);

                jQuery( '#ddl-js-layout-template-name' ).trigger( 'chosen:updated' );

            }, 'json');

        });
    };

    self._on_layout_selector_open = function () {


        var selector = document.getElementById( 'ddl-js-layout-template-name' );
        if (selector === null) {
            return;
        }

        var no_results_msg = '';
        if(typeof(DDLayout_settings_post_edit) !== 'undefined' && DDLayout_settings_post_edit.DDL_JS.no_items_found_message ){
            no_results_msg = DDLayout_settings_post_edit.DDL_JS.no_items_found_message;
        }


        $( '#ddl-js-layout-template-name' ).chosen({
            no_results_text: no_results_msg,
        });

        if ( typeof(DDLayout_settings_post_edit) !== 'undefined' && DDLayout_settings_post_edit && DDLayout_settings_post_edit.DDL_JS.layout !== null ) {
            $('#ddl-js-layout-template-name').append( '<option selected data-id="' + DDLayout_settings_post_edit.DDL_JS.layout.id + '" value="' + DDLayout_settings_post_edit.DDL_JS.layout.slug + '">' + DDLayout_settings_post_edit.DDL_JS.layout.name + '</option>');
            $('#ddl-js-layout-template-name').trigger( 'chosen:updated' );
        }

        $('#ddl-js-layout-template-name').on( 'chosen:showing_dropdown', function ( evt, params ) {

            if ( $selector_populated === true ) {
                return;
            }

            $( '.chosen-single' ).prepend( '<span id="chosen_spinner" class="wpv-spinner"></span>' );

            var data = {
                'action': 'ddl_load_selector_items',
                'post_id': DDLayout_settings_post_edit.DDL_JS.post.ID,
                'wpnonce': DDL_Private_layout.private_layout_nonce,
            };

            jQuery.post(ajaxurl, data, function (response) {
                var json = response;


                $( '#ddl-js-layout-template-name' ).find('option')
                    .remove()
                    .end()
                    .append("<option value='-1' data-id='-1'>Don't use a layout</option>");
                $( '#ddl-js-layout-template-name' ).trigger("chosen:updated");

                jQuery.each(json, function (key, value) {

                    var selected = '';
                    if (
                        DDLayout_settings_post_edit.DDL_JS.layout !== null &&
                        DDLayout_settings_post_edit.DDL_JS.layout.id === value.template_id
                    ) {
                        selected = 'selected';
                    }

                    $( '#ddl-js-layout-template-name' )
                        .append( '<option ' + selected + ' data-id="' + value.template_id + '" value="' + value.post_name + '">' + value.title + '</option>' );
                });
            }, 'json').promise().done(function () {
                $( '#chosen_spinner' ).remove();
                $( '#ddl-js-layout-template-name' ).trigger( 'chosen:updated' );
            });
            $selector_populated = true;
        });



    };

    self._update_selected_layout = function(){
        jQuery( '.js-confirm-template-layout-change' ).click(function(event) {
            event.preventDefault();

            var selected_option = jQuery( '#ddl-js-layout-template-name :selected' );
            if( selected_option.val() === '-1' ){
                return;
            }

            // do ajax request and update layout for this page
            if( selected_option.val() !=='0' ){
                var data = {
                    'action': 'ddl_update_template_layout',
                    'layout_slug': selected_option.val(),
                    'post_id': DDLayout_settings_post_edit.DDL_JS.post.ID,
                    'layout_id': selected_option.data( 'id' ),
                    'additional_data': selected_option.data( 'object' ),
                    'wpnonce' : DDL_Private_layout.private_layout_nonce,
                };

                jQuery.post( ajaxurl, data, function( response ) {

                    // check layout type and decide is overlay necessary
                    WPV_Toolset.Utils.eventDispatcher.trigger( 'ddl-layout-template-name-changed', selected_option.val() );

                    jQuery( '.js-dd-layout-selector' ).hide();
                    jQuery( '.ddl-layout-selected' ).show();
                    jQuery( '#js_selected_layout_template_name' ).text( selected_option.text() );
                }, 'json' );
            }

        });
    };

    self._hide_content_template_selector_on_init = function () {
        var layout_id = jQuery('#ddl-js-layout-template-name').find('option:selected').data('id');
        // cast to integer and assign 0 if no value
        layout_id = layout_id ? +layout_id : 0;
        // if 0 or -1 no layout is in use
        self._disable_content_template(layout_id > 0);
    };

    self._disable_content_template = function ( state ) {
        // Views does not add the Content Template selection metabox when Layouts is active and using a Content Layout
        if ( jQuery('select#views_template').length == 0 ) {
            return;
        }
        if ( state ) {

            jQuery( 'select#views_template' ).hide();

            if ( !jQuery('.js-ct-disable').length ) {
                jQuery('<p class="toolset-alert toolset-alert-warning js-ct-disable">' +
                    DDLayout_settings_post_edit.strings.content_template_diabled +
                    '</p>' ).insertAfter( 'select#views_template' );
            }
        } else {

            jQuery( 'select#views_template' ).show();

            if ( jQuery('.js-ct-disable').length ) {
                jQuery( '.js-ct-disable' ).remove();
            }
        }
    };

    self.init();
};

jQuery(function ( $ ) {
    _.defer(function () {
        if ( Toolset.hooks.applyFilters('ddl-init-template-selector-on-ready', true) ) {
            DDLayout.template_selector = new DDLayout.templateSelector($);
        }
    });
});


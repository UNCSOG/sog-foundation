var DDLayout = DDLayout || {};

DDLayout.DDL_BootstrapSizeSettings = function (model) {
    var self = this,
        layout = model,
        $button = jQuery('.js-ddl-bootstrap-base-button'),
        dialog_selector = '#ddl-bootstrap-column-size-tpl',
        $radio = null,
        no_default = false,
        dialog = null,
        $default = null;

    self.init = function () {
        self.events_on();
    };

    self.events_on = function () {
        $button.on('click', self.dialog_open);
    };


    self.dialog_open = function () {
        dialog = new DDLayout.DialogView({
            title: DDLayout_settings.DDL_JS.strings.bootstrap_dialog_title,
            modal: true,
            resizable: false,
            draggable: false,
            position: {my: "center", at: "center", of: window},
            width: 400,
            autoOpen:false,
            selector: dialog_selector,
            template_object: {
                layout_name: layout.get('name'),
                prefixes_data: DDLayout_settings.DDL_JS.column_prefixes_data,
                site_default: DDLayout_settings.DDL_JS.column_prefix_default
            },
            buttons: [
                {
                    text: DDLayout_settings.DDL_JS.strings.cancel,
                    icons: {},
                    class: 'cancel button button-secondary',
                    click: function () {
                        jQuery(this).ddldialog("close");
                    }
                },
                {
                    text: DDLayout_settings.DDL_JS.strings.apply,
                    icons: {},
                    class: 'button button-primary bs-settings-apply',
                    click: function () {
                        DDLayout.ddl_admin_page.take_undo_snapshot();
                        var value = jQuery('input[name="ddl-column-prefix"]:checked').val();
                        model.set( 'column_prefix', value );
                        DDLayout.ddl_admin_page.add_snapshot_to_undo();
                        jQuery(this).ddldialog("close");
                    }
                }
            ]
        });

        dialog.$el.parent().addClass('ddl-bootstrap-dialog-settings');

        dialog.$el.on('ddldialogclose', function ( event ) {
            dialog.remove();
        });

        dialog.$el.on('ddldialogopen', self.dialog_open_callback);

        dialog.dialog_open();
    };

    /**
     * @deprecated
     * @param value
     */
    self.setPrefixToAllCells = function( value ){
        var cells = DDLayout.models.cells.Layout.getCells( model );
        return _.map( cells, function(cell){
            cell.set( 'column_prefix', value );
        });
    };

    self.dialog_open_callback = function( event ){
        var prefix = model.get('column_prefix'),
            $radios = jQuery('input[name="ddl-column-prefix"]');

            no_default = model.get('no_default_prefix');

        $default = jQuery( '.js-is-default-prefix' )

        $radio = jQuery('.js-no_default_prefix');

        $radio.prop( 'checked', no_default === false ).trigger('change');

        self.handle_use_default_value( $radio );

        $radios.on('change', self.handle_use_default );

        if( no_default ){
            self.handle_radios_default( prefix );
        }
    };

    self.handle_radios_default = function( value ){

        if( typeof value === 'undefined' ){
            return;
        }

        var $radios = jQuery('input[name="ddl-column-prefix"]');

        $radios.each(function(){
            if( jQuery(this).val() === value ){
                jQuery(this).prop( 'checked', true );
            } else {
                jQuery(this).prop( 'checked', false );
            }
        });
    };

    self.handle_use_default = function( event ){
        self.handle_use_default_value( jQuery(this) );
    };

    self.handle_use_default_value = function( $el ){
        if( $el.is(':checked') && $el.hasClass('site_default_prefix') ){

            model.set( 'no_default_prefix', false );

        } else {

            model.set( 'no_default_prefix', true );
        }
    };

    _.bindAll( self, 'dialog_open', 'dialog_open_callback', 'handle_radios_default' );

    self.init();

};
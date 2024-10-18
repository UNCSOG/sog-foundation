var DDLayout = DDLayout || {};

DDLayout.ChangeLayoutUseHelper = function ($) {
    "use strict";
    var self = this
        , post_types_change_button = null
        , archives_change_button = null
        , cache = {}
        , track_option_cache = {}
        , $bulk_types_checkboxes
        , $message_div, track_open_close = {}
        , handlers = {}
        , activeGroup = 'post_types';

    self.layoutOptions = null;
    self._current_layout = null;
    self.has_message = false;

    self._has_loop_cell = false;
    self._has_post_content_cell = false;

    self.current_dialog = null;


    self.POST_TYPES_OPTION = DDLayout_settings.DDL_OPN.POST_TYPES_OPTION;
    self.BULK_ASSIGN_POST_TYPES_OPTION = DDLayout_settings.DDL_OPN.BULK_ASSIGN_POST_TYPES_OPTION;

    self.init = function () {

        self.eventDispatcher.listenTo(self.eventDispatcher, 'ddl-dialog-ass-open', self.dialog_before_load);
        self.eventDispatcher.listenTo(self.eventDispatcher, 'ddl-dialog-ass-load', self.handle_on_open );
        self.eventDispatcher.listenTo(self.eventDispatcher, 'ddl-dialog-ass-complete', self.dialog_after_load );

        self.eventDispatcher.listenTo(self.eventDispatcher, 'assignment_dialog_close', self.clean_up_dialog_events);

        self.eventDispatcher.listenTo(self.eventDispatcher, 'checkboxes_changed', self.handle_checkboxes_change);
        self.eventDispatcher.listenTo(self.eventDispatcher, 'data_sent_to_server', self.ajax_response_callback);
    };

    self.dialog_before_load = function( dialog, layout_id, args ){
        self.current_dialog = dialog;
        self.setGroupData( args );
        self.set_current_layout( layout_id );
        self.set_layout_has_cells_of_type(args);
        self.set_posts_list_handler(layout_id);
        self.set_ui( dialog );
    };

    self.setGroupData = function( args ){
        activeGroup = args.hasOwnProperty( 'activeGroup' ) ? args.activeGroup : activeGroup;
    };

    self.handle_on_open = function (dialog, layout_id, args) {
        self.init_dialog( dialog );
    };

    self.dialog_after_load = function(dialog, layout_id, args){
        self.handle_button_on_open();
        DDLayout.changeLayoutUseHelper.get_current_post_list_handler().set_more_or_less(false);
    };

    self.init_dialog = function( dialog ){
        self.setInitialStateLayoutOptions(dialog);
        self.set_bulk_types_checkboxes(dialog);
        self.setChangeEvents();
        self.set_change_event_for_extras();
        self.build_cache();
    };

    self.set_ui = function( dialog ){
        self.collapse_group(dialog);
        self.dialog_opens(dialog);
        self.dismissPostContentCellWarning(dialog);
        self.set_buttons(dialog);
        self.get_current_post_list_handler().manage_assigned_posts_visual(dialog);
        $message_div = jQuery('div.dialog-change-use-messages');
    };

    self.set_posts_list_handler = function(layout_id){

        if( !handlers[layout_id] ){
            handlers[layout_id] = new DDLayout.PostAssignedInDialogHandler( layout_id );
        }
    };

    self.get_current_post_list_handler = function(){
        return  handlers[self._current_layout];
    };

    self.set_layout_has_cells_of_type = function (args) {
        if (typeof args !== 'undefined') {
            self._has_loop_cell = args.has_post_loop_cell;
            self._has_post_content_cell = args.has_content_cell;
        }
    };

    self.ajax_response_callback = function( container, name, html, callback )
    {
        // do something before
        if( name === null ) return;

        self.set_track_option_cache(name, false);

        // if you saved post types options reset cache also for
        // bulk posts in post type options
        if( name === self.POST_TYPES_OPTION )
        {
            self.set_track_option_cache( self.BULK_ASSIGN_POST_TYPES_OPTION, false );
            self.hide_bulk_types_checkboxes();
        }


        if( self.track_option_cache_true() === true )
        {
            self.reset_message();
        }


        self.handle_html_reload( html, name );

        Toolset.hooks.doAction( 'ddl-wpml-refresh', container, self._current_layout, [] );

        if( callback && callback instanceof Function ){
            callback.call(self, arguments);
        }
    };

    self.handle_html_reload = function( html, name ){

            if( !html ) return;

            var $dialog_content = $('.js-ddl-dialog-content', self.current_dialog),
                checked = self.take_checkboxes_snapshot();

            $dialog_content.empty().html( html.dialog );

            if( html.hasOwnProperty('list') && jQuery('.js-where-used-ui').is('div') ){
                jQuery('.js-where-used-ui').empty().html( html.list );
            }

            DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('change-layout-use-reload', self.current_dialog, self.get_current_layout(), true );

            self.set_ui( self.current_dialog );
            self.init_dialog( self.current_dialog );

            _.defer(self.recheck_checkboxes, name, checked );
    };

    self.take_checkboxes_snapshot = function(){
        var $checkboxes = self.current_dialog.find('input[type="checkbox"]')
            , snapshot =[];

        $checkboxes.each(function(index, el){
                var checked = {
                    name:$(el).prop('name'),
                    value:$(el).prop('value'),
                    checked: $(el).prop('checked')
                };
                snapshot.push( checked );
        });

        return snapshot;
    };

    self.recheck_checkboxes = function( n,c ){
            var name = n, checked = c, cache_it = [];

        _.each(checked, function(el, index, list){

                if( el.name !== name ){

                    if( 'individual_posts_assign' === el.name )
                    {
                        var checkbox = $('.js-individual-popup-tabs').find("[aria-expanded=true]").find( ':checkbox[name="'+el.name+'"]').filter('[value="'+el.value+'"]');

                        if( checkbox.length > 0 && checkbox.prop('checked') !== el.checked && cache_it.indexOf( checkbox.val() ) === -1 ){
                            cache_it.push( checkbox.val() )
                            checkbox.prop( 'checked', el.checked).trigger('change');
                        }

                    } else if( 'post_types_apply_all' == el.name && name != 'post_types' ) {

                        var checkbox = self.current_dialog.find( ':checkbox[name="'+el.name+'"]').filter('[value="'+el.value+'"]')
                            , $relative = self._checkboxes.filter(function(){
                                return checkbox.val( ) === $(this).val();
                            });

                        if( checkbox.length > 0 && checkbox.prop('checked') !== el.checked ){

                            if( $relative.prop( 'checked') === true ){
                                checkbox.prop( 'checked', el.checked).trigger('change');
                            } else {
                                checkbox.prop( 'checked', false ).trigger('change')
                            }
                        }

                    } else if( 'post_types_apply_all' !== el.name ) {
                        var checkbox = self.current_dialog.find( ':checkbox[name="'+el.name+'"]').filter('[value="'+el.value+'"]');

                        if( checkbox.length > 0 /*&& checkbox.prop('checked') !== el.checked */ ){

                            checkbox.prop( 'checked', el.checked).trigger('change');

                        }
                    }
                }
        });

        // this is hack fix to make sure buttons are disabled
        jQuery('.button-secondary', self.current_dialog).prop('disabled', true);

        Toolset.hooks.removeFilter('ddl-is_current_layout_assigned');
        Toolset.hooks.addFilter('ddl-is_current_layout_assigned', function(bool){
                var remove_checkboxes = jQuery('.js-remove-individual-page-item', self.current_dialog).length,
                    assigned = _.filter(checked, function(item){
                        return item.checked === true && item.name !== 'post_types_apply_all';
                    }).length + remove_checkboxes;

            return assigned > 0;
        });

        self.afterReloadCheckboxes();
    };

    self.afterReloadCheckboxes = function( ){
        var tab = activeGroup;

        if( tab === 'free' ) return;

        if( Toolset.hooks.applyFilters('ddl-is_current_layout_assigned', false ) === false ){
            self.markUnassignedTabForChanges( 1 );
        }
    };

    self.markUnassignedTabForChanges = function( group ){
        var $tab = jQuery('div#layout-listing-tabs ul li[data-group="'+group+'"]');
        $tab.append('<i class="fa fa-exclamation-triangle js-tab-alert-icon tab-alert-icon"></i>');
    };

    self.get_post_types_to_batch = function()
    {
        return self.getLayoutOption(self.BULK_ASSIGN_POST_TYPES_OPTION);
    };

    self.hide_bulk_types_checkboxes = function()
    {
        $bulk_types_checkboxes.each(function(){
                if(  $(this).prop('checked') === true ){
                    $(this).parent('label').fadeOut();
                }
        });
    };

    self.reset_checkboxes_visibility = function(){
        $bulk_types_checkboxes.each(function(){
            $(this).parent('label').hide();
        });
    };

    self.set_bulk_types_checkboxes = function(dialog)
    {

        $bulk_types_checkboxes = $( '.js-ddl-post-content-apply-all-checkbox', dialog );

        $bulk_types_checkboxes.each(function(index, check){
                var $me = $(check)
                    , data = $me.data('object')
                    , assigned = data.assigned
                    , count = data.count
                    , total = data.total
                    , batched = data.batched, $relative;

            $relative = self._checkboxes.filter(function(){
                    return $me.val( ) === $(this).val();
            });

            if( $relative.prop('checked') === true
                && ( batched === false || batched === true && total > count )
                && $me.parent('label').hasClass('do_not_show_at_all') === false
            ){

                $me.prop(assigned, false).parent('label').show();
            }

            self.setLayoutOption(jQuery(check).val(), jQuery(check).is(':checked'), jQuery(check).prop('name'));
        });
    };

    self.make_select_bulk_assign_option_visible = function(target, what)
    {
        var $el = target.parent().next('label');

        if( $el.hasClass('do_not_show_at_all') ) return;

        if ( what === false  ) {
            $el.fadeOut('slow');
        }
        else{
            $el.fadeIn('slow');
        }
    };

    self.get_creation_extras = function(options){

        var post_types = _.intersection( self.get_post_types_to_batch(), self.getLayoutOption(self.POST_TYPES_OPTION) );

        if( post_types.length > 0 )
        {
            return {
                post_types : post_types
            };
        } else {
            return null;
        }

        return null;
    };

    self.reset_message = function(){
        self.has_message = false;
        $message_div.wpvToolsetMessage('destroy');
    };

    self.build_cache = function () {
        var  temp = {}
            , elements = {}
            , undefined;

        // copy arrays into temporary object
        self.setTrackOptionCacheForCurrent();

        _.each(DDLayout_settings.DDL_OPN, function(value, key, list){

            elements[key] = self.getLayoutOption( value );

            if( !self.getCurrentTrackOptionCacheOption(value) || self.getCurrentTrackOptionCacheOption(value) === false )
            {
                temp[value] = elements[key] ? elements[key] : [];

                self.set_track_option_cache(value, false);
            } else {
                temp[value] = cache && cache['layout_'+self.get_current_layout()] && cache['layout_'+self.get_current_layout()][value] ? cache['layout_'+self.get_current_layout()][value] : undefined;
            }
        });

        if( cache === null ) cache = {};

        if( typeof cache['layout_'+self.get_current_layout()] === 'undefined' ){
            cache['layout_'+self.get_current_layout()] = {};
        } else {
            cache['layout_'+self.get_current_layout()] = null;
        }

        // shallow copy the temporary object to cache a loose reference of the original object
        cache['layout_'+self.get_current_layout()] = jQuery.extend( true, cache['layout_'+self.get_current_layout()], temp );

        // release
        temp = null;
    };

    self.setTrackOptionCacheForCurrent = function()
    {
        if( typeof track_option_cache['layout_'+self.get_current_layout()] === 'undefined' )
        {
            track_option_cache['layout_'+self.get_current_layout()] = {};
        }
    };

    self.getCurrentTrackOptionCache = function( )
    {
        return track_option_cache['layout_'+self.get_current_layout()];
    };

    self.getCurrentTrackOptionCacheOption = function( name )
    {
        var ret = track_option_cache['layout_'+self.get_current_layout()];
        return ret[name];
    };

    // track if there are messages for other groups
    self.track_option_cache_true = function()
    {
        var check_true = _.filter(self.getCurrentTrackOptionCache(), function(v) {return v === true})
            , size = _.size( check_true );

         return size === 0;
    };

    self.set_track_option_cache = function( name, value )
    {
        track_option_cache['layout_'+self.get_current_layout()][name] = value;
    };

    self.get_cache = function (name) {

        if( typeof cache['layout_'+self.get_current_layout()] === 'undefined' ) return null;

        if (typeof name === 'undefined') return cache['layout_'+self.get_current_layout()];

        var ret = cache['layout_'+self.get_current_layout()];

        return ret[name];
    };

    self.set_buttons = function (dialog) {
        post_types_change_button = jQuery('.js-post-types-options', dialog)
        archives_change_button = jQuery('.js-save-archives-options', dialog);
    };

    self.handle_button_on_open = function () {
        post_types_change_button.prop('disabled', true).removeClass('button-primary').addClass('button-secondary');
        archives_change_button.prop('disabled', true).removeClass('button-primary').addClass('button-secondary');
    };

    self.set_change_event_for_extras = function () {
        $(document).on('change', '.js-ddl-post-content-apply-all-checkbox', function (event) {
            event.stopImmediatePropagation();

            self.setLayoutOption(jQuery(this).val(), jQuery(this).is(':checked'), jQuery(this).prop('name'));
           // console.log( jQuery(this).parent().parent().parent(), jQuery(this).prop('name') )
            self.eventDispatcher.trigger('checkboxes_changed', jQuery(this).parent().parent().parent(), jQuery(this).prop('name'));
        });
    };

    self.handle_checkboxes_change = function ( ul, name, length ) {

        var $update = ul.find('.js-buttons-change-update');

        if ( self.enable_disable_button_on_change(name) === false || ( typeof length !== 'undefined' && length > 0 ) ) {

            self.set_track_option_cache( name, true );

            $update.prop('disabled', false).removeClass('button-secondary').addClass('button-primary');

            if (self.has_message === false) {

                $message_div.wpvToolsetMessage({
                    text: $message_div.data('text'),
                    stay: true,
                    close: false,
                    type: 'notice'
                });
                self.has_message = true;
                self.update_close_button('text-cancel');
            }

        }
        else {

            self.set_track_option_cache( name, false );

            $update.prop('disabled', true).removeClass('button-primary').addClass('button-secondary');

            if( self.track_option_cache_true() === true )
            {
                $message_div.wpvToolsetMessage('destroy');
                self.has_message = false;
                self.update_close_button('text-close');
            }
        }
    };

    self.update_close_button = function( data ) {
        var btn_dialog_close = self.current_dialog.find('button.js-edit-dialog-close');
        var update_prop = 'text';

        if( btn_dialog_close.length <= 0 ) {
            btn_dialog_close = self.current_dialog.find('input.js-edit-dialog-close');
            update_prop = 'value';
        }

        switch(update_prop) {
            case 'text':
                btn_dialog_close.text(btn_dialog_close.data(data))
                break;

            default:
                btn_dialog_close.attr(update_prop, btn_dialog_close.data(data));
                break;
        }
    }

    self.get_caret_siblings_checked_count = function (caret) {
        var $caret = caret, $ul = $caret.parent().parent().find('ul'),
            $checked = $ul.find('input[type="checkbox"]:checked').not('input[name="post_types_apply_all"]');

        return $checked.length;
    };

    self.get_current_layout = function () {
        return self._current_layout;
    };

    self.set_current_layout = function (layout_id) {
        self._current_layout = +layout_id;
    };

    self.manageOptions = function (opt, what_to_do, input_name) {
        var self = this,
            name = input_name,
            add = what_to_do,
            option = opt;

        // create object if not already created
        if (self.layoutOptions === null) {
            self.layoutOptions = {};
        }

        // create object for this layout (this is necessary in listing page where the same instance is handling multiple layouts)
        if (typeof self.layoutOptions["layout_" + self.get_current_layout()] === 'undefined') {
            self.layoutOptions["layout_" + self.get_current_layout()] = {};
        }

        // create array for specific group if not already crated
        if (typeof self.layoutOptions["layout_" + self.get_current_layout()][name] === 'undefined') {
            self.layoutOptions["layout_" + self.get_current_layout()][name] = [];
        }

        // add item to layout if not already present
        if (add === true && self.layoutOptions["layout_" + self.get_current_layout()][name] && self.layoutOptions["layout_" + self.get_current_layout()][name].indexOf(option) === -1) {
            self.layoutOptions["layout_" + self.get_current_layout()][name].push(option);
        }

        // remove item if unchecked
        else if (add === false) {
            self.layoutOptions["layout_" + self.get_current_layout()][name] = _.without(self.layoutOptions["layout_" + self.get_current_layout()][name], option)
        }
    };


    self.getLayoutOption = function (name) {
        var self = this;
        return self.layoutOptions && self.layoutOptions.hasOwnProperty( "layout_" + self.get_current_layout() ) ? self.layoutOptions[ "layout_" + self.get_current_layout()][name] : null;
    };

    self.getLayoutOptions = function()
    {
        return self.layoutOptions && self.layoutOptions.hasOwnProperty( "layout_" + self.get_current_layout() ) ? self.layoutOptions[ "layout_" + self.get_current_layout()] : null;
    };

    self.setLayoutOption = function (option, add, name) {
        self.manageOptions(option, add, name);
    };

    self.setLayoutOptions = function( options )
    {
        if( self.layoutOptions === null ) return;

        self.layoutOptions[ "layout_" + self.get_current_layout()] = options;
    };

    self.setInitialStateLayoutOptions = function (dialog) {
        self._checkboxes = jQuery('.js-ddl-post-type-checkbox', self.current_dialog );

        self._checkboxes.each(function (i) {
            if (jQuery(this).is(':checked')) {
                self.setLayoutOption(jQuery(this).val(), jQuery(this).is(':checked'), jQuery(this).prop('name'));
            }
        });
    };

    self.setChangeEvents = function () {
        jQuery(document).on('change', '.js-ddl-post-type-checkbox', function (event) {
            event.stopImmediatePropagation();

            self.setLayoutOption(jQuery(this).val(), jQuery(this).is(':checked'), jQuery(this).prop('name'));

            if( 'post_types' === jQuery(this).prop('name') ){

                var data = jQuery(this).data('object'), assigned = data.assigned, batched = data.batched;

                if( jQuery(this).is(':checked') && assigned === 'checked' && batched === false ){
                    self.make_select_bulk_assign_option_visible( jQuery(this), true );
                } else {
                    self.make_select_bulk_assign_option_visible( jQuery(this), jQuery(this).is(':checked') && self.enable_disable_button_on_change(self.POST_TYPES_OPTION) === false );
                }
            }

            self.eventDispatcher.trigger('checkboxes_changed', jQuery(this).parent().parent().parent(), jQuery(this).prop('name') );
        });
    };

    self.enable_disable_button_on_change = function (name) {
        var check = self.getLayoutOption(name) ,
            cache_here = self.get_cache(name);

        if (typeof check == 'undefined' || check === null ) check = [];
        if (typeof cache_here === 'undefined' || cache_here === null ) cache_here = [];

        var equals = _.isEqual( check.sort(), cache_here.sort() );

        if( 'post_types_apply_all' === name ){
            var option = self.getLayoutOption('post_types'),
                related_cache = self.get_cache('post_types')
                , related_equals = _.isEqual( related_cache.sort(), option.sort() );

            return equals && related_equals;
        }

        // arrays should sorted to be compared
        return equals;
    };

    // turn off events handling when dialog closes
    self.clean_up_dialog_events = function () {
        self.reset_carets();
        self.clean_up_options_data();
        self.reset_message();
        self.reset_checkboxes_visibility();
        jQuery(document).off('change', '.js-ddl-post-type-checkbox', false);
        jQuery(document).off('click', '.js-post-types-options', false);
        jQuery(document).off('click', '.js-save-archives-options', false);
        jQuery(document).off('change', '.js-ddl-post-content-apply-all-checkbox', false);
        self._checkboxes = [];
        Toolset.hooks.doAction('ddl-wpml-cleanup');
    };

    // clear all checkboxes related data
    // (this is meant for Editor page since we don't have a view.render() method there - the benefits of using MVC :-) )
    self.clean_up_options_data = function( )
    {
        var cache = self.get_cache();

        self._checkboxes.each(function ( i, v ){
            var name = jQuery(v).prop('name'), value = jQuery(v).val();
            if( cache && cache.hasOwnProperty(name) && cache[name].indexOf(value) === -1 )
            {
                jQuery(v).prop( 'checked', false );
            }
        });

        self.setLayoutOptions( {} );
        cache = null;
    };


    self.dismissPostContentCellWarning = function ($context) {
        var $dismiss_post_content_warning = $('.js-dismiss-alert-message-post-content', $context)
            , $dismiss_loop_warning = $('.js-dismiss-alert-message-loop', $context)
            , $checkboxes_post_content = $('.js-ddl-post-content-checkbox', $context)
            , $checkboxes_archive_loop = $('.js-ddl-archive-loop-checkbox', $context)
            , class_warning_types = 'post-types-list-in-layout-editor-alerted'
            , class_warning_loops = 'post-loops-list-in-layout-editor-alerted'
            , layout_id = self.get_current_layout();


        $(document).on('click', '.js-dismiss-alert-message-post-content', function (event, set) {
            event.preventDefault();
            var $me = $(this);

            $me.parent().parent('div').parent('li').parent('ul').removeClass(class_warning_types);
            $me.parent().parent('div').slideUp('fast');
            //   $update_post_type.prop('disabled', false);
            $checkboxes_post_content.each(function (v) {
                $(this).prop('disabled', false);
                if (set === undefined) jQuery.jStorage.set('dismiss_alert_post_content_' + layout_id, 'yes')
            });
        });

        $(document).on('click', '.js-dismiss-alert-message-loop', function (event, set) {
            event.preventDefault();
            var $me = $(this);

            $me.parent().parent('div').parent('li').parent('ul').removeClass(class_warning_loops);
            $me.parent().parent('div').slideUp('fast');
            //   $update_archives.prop('disabled', false);
            $checkboxes_archive_loop.each(function (v) {
                $(this).prop('disabled', false);
                if (set === undefined) jQuery.jStorage.set('dismiss_alert_loop_' + layout_id, 'yes')
            });
        });


        if (jQuery.jStorage.get('dismiss_alert_post_content_' + layout_id) === 'yes' || ( self._has_post_content_cell === true && adminpage == 'toolset_page_dd_layouts_edit')) {
            $dismiss_post_content_warning.parent().parent('div').hide();
            $dismiss_post_content_warning.parent().parent('div').parent('li').parent('ul').removeClass(class_warning_types);
            //    $update_post_type.prop('disabled', false);
            $checkboxes_post_content.each(function (v) {
                $(this).prop('disabled', false);
            });
        }
        else if (jQuery.jStorage.get('dismiss_alert_post_content_' + layout_id) !== 'yes' && ( self._has_post_content_cell === false && adminpage == 'toolset_page_dd_layouts_edit')) {
            $dismiss_post_content_warning.parent().parent('div').show();
            $dismiss_post_content_warning.parent().parent('div').parent('li').parent('ul').addClass(class_warning_types);
            //    $update_post_type.prop('disabled', false);
            $checkboxes_post_content.each(function (v) {
                $(this).prop('disabled', true);
            });
        }

        if (jQuery.jStorage.get('dismiss_alert_loop_' + layout_id) === 'yes' || ( self._has_loop_cell === true && adminpage == 'toolset_page_dd_layouts_edit' )) {

            $dismiss_loop_warning.parent().parent('div').hide();
            $dismiss_loop_warning.parent().parent('div').parent('li').parent('ul').removeClass(class_warning_loops);
            //    $update_archives.prop('disabled', false);
            $checkboxes_archive_loop.each(function (v) {
                $(this).prop('disabled', false);
            });
        }
        else if (jQuery.jStorage.get('dismiss_alert_loop_' + layout_id) !== 'yes' && ( self._has_loop_cell === false && adminpage == 'toolset_page_dd_layouts_edit' )) {
            $dismiss_loop_warning.parent().parent('div').show();
            $dismiss_loop_warning.parent().parent('div').parent('li').parent('ul').addClass(class_warning_loops);
            //    $update_archives.prop('disabled', false);
            $checkboxes_archive_loop.each(function (v) {
                $(this).prop('disabled', true);
            });
        }


        if ($('.js-ddl-archive-loop-checkbox:checked', $context).length > 0) {
            $dismiss_loop_warning.trigger('click', 'no');
        }

        if ($('.js-ddl-post-content-checkbox:checked', $context).length > 0) {
            $dismiss_post_content_warning.trigger('click', 'no');
        }

    };

    self.collapse_group = function (dialog) {
        var caret = jQuery('.js-collapse-group-in-dialog,.js-collapse-group-individual', dialog);

        caret.map(function (index, el) {

            jQuery(el).data('open', false);
            if( !jQuery(el).data('section') ){
                jQuery(el).data('section', jQuery( jQuery(el).parent().next().find('input:first-child')[0]).prop('name') );
            }
        });

        jQuery(document).on('click', '.js-collapse-group-in-dialog,.js-collapse-group-individual', function (event) {

            var $me = jQuery(this)
                , $ul = $me.parent().next();

            if ($me.prop('tagName') != 'I') {
                $me = $me.next();
            }

            if ($me.data('open')) {
                $me.removeClass('fa-caret-up').addClass('fa-caret-down');
                $ul.slideUp('fast', function (event) {
                    $me.data('open', false);
                    track_open_close[$me.data('section')] = $me.data('open');
                });
            }
            else {
                $me.removeClass('fa-caret-down').addClass('fa-caret-up');
                $ul.slideDown('fast', function (event) {
                    $me.data('open', true);
                    track_open_close[$me.data('section')] = $me.data('open');
                });
            }
        });

    };

    self.dialog_opens = function (dialog) {
        self.open_close(dialog);
    };

    self.open_close = function (dialog) {
        var undefined;
        var $number_of_opened_sections = 0;
        self.carets = jQuery('.js-collapse-group-in-dialog', dialog);

        self.carets.each(function (i) {
            var $me = jQuery(this)
                , $ul = $me.parent().parent().find('ul'),
                open_or_close = false;

            if( typeof track_open_close[$me.data('section')] === 'undefined' ){
                open_or_close = self.get_caret_siblings_checked_count(jQuery(this)) > 0;
            } else{
                open_or_close = track_open_close[$me.data('section')];
            }


            if ( open_or_close ) {

                $ul.removeClass('hidden');
                $me.data('open', true);
                $me.removeClass('fa-caret-down').addClass('fa-caret-up');
                $number_of_opened_sections++;

            } else {
                $ul.addClass('hidden');
                $me.data('open', false);
                $me.removeClass('fa-caret-up').addClass('fa-caret-down');
            }
        });

        // check individual pages section
        var $individual_pages = jQuery('.individual-pages-list li');
        if($individual_pages.length > 0){
            $('.individual-pages-wrap').removeClass('hidden');
            $('#js-individual-post-lists').removeClass('fa-caret-down').addClass('fa-caret-up');
            $number_of_opened_sections++;
        }

        if($number_of_opened_sections === 0){
            $('#js-multiple-pages-list').removeClass('hidden');
            $('#js-multiple-pages-section-caret').removeClass('fa-caret-down').addClass('fa-caret-up');
        }

    };

    self.reset_carets = function(){
        var undefined;
        self.carets.each(function (i){
            var $me = jQuery(this);
            track_open_close[$me.data('section')] = undefined;
        });
    };

    self.init();
};

DDLayout.ChangeLayoutUseHelper.prototype.eventDispatcher = _.extend({}, Backbone.Events);

DDLayout.ChangeLayoutUseHelper.manageSpinner = {
    spinnerContainer: jQuery('<div class="spinner ajax-loader">'),
    addSpinner: function (target) {
        var self = this;
        jQuery(target).parent().insertAtIndex(0,
            self.spinnerContainer.css({float: 'none', display: 'inline-block', marginTop: '4px'})
        );
    },
    removeSpinner: function () {
        this.spinnerContainer.hide().remove();
    }
};

DDLayout.PostAssignedInDialogHandler = function(layout, dialog){
    var self = this,
        more = false,
        $caret,
        $container,
        class_more = 'fa-caret-up',
        class_less='fa-caret-down';

    self.loader = null;

    self.AMOUNT = 5;

    var set_more=function(){
        self.set_amount( -1 );
        self.set_more_or_less( true );
        $caret.removeClass(class_more).addClass(class_less);
    };

    var set_less=function(){
        self.set_amount( 5 );
        self.set_more_or_less( false );
        $caret.removeClass(class_less).addClass(class_more)
    };

    var more_or_less = function(){
        if( more ){

            set_less()
        } else {

            set_more();
        }

    };


    self.init = function () {
        self.layout = layout;
        DDLayout.changeLayoutUseHelper.eventDispatcher.listenTo(
            DDLayout.changeLayoutUseHelper.eventDispatcher,
            'ddl-posts-checkboxes-loaded-and-initialised',
            self.display_no_more_pages_message
        );
    };


    self.manage_assigned_posts_visual = function ( dialog ) {
        self.dialog = dialog;
        $container = jQuery( '.js-individual-pages-assigned', self.dialog );
        self.loader = new WPV_Toolset.Utils.Loader();
        init_handler();
    };

    var init_handler = function(){
        $caret = jQuery('.js-show-all-posts-assigned-in-dialog', self.dialog);
        $caret.on( 'click', self.get_data_from_server );
    };

    self.reload_handlers = function(){
        init_handler();
        DDLayout.individual_assignment_manager._get_posts_for_layout();
    };

    var handle_message_no_pages_display = function(){

        var $ul = jQuery('.ddl-posts-check-list', self.dialog),
            $check_li = jQuery('.js-ddl-message-no-more-posts');

        var has_posts_to_be_assigned = function(){
            return $ul.children('li').length > 0;
        };

	var has_posts_already_assigned = function(){
            return jQuery('ul.individual-pages-list').children('li').length > 0;
        };

        var get_message_value = function(){
            var post_type = jQuery('input[name="ddl-individual-post-type"]:checked').val();

	    if(has_posts_already_assigned() === false && has_posts_to_be_assigned() === false){
		if( post_type === 'any' ){
		    return DDLayout_settings.DDL_JS.strings.no_more_posts_in_db;
		} else {
		    return DDLayout_settings.DDL_JS.strings.no_more_pages_in_db;
		}
	    }


            if( post_type === 'any' ){
                return DDLayout_settings.DDL_JS.strings.no_more_posts;
            } else {
                return DDLayout_settings.DDL_JS.strings.no_more_pages;
            }

            return DDLayout_settings.DDL_JS.strings.no_more_pages;
        };

        if( $check_li.is('li') ){
            $check_li.remove();
        }
        if( has_posts_to_be_assigned() === false ){
            var $li = jQuery('<li class="ddl-message-no-more-posts js-ddl-message-no-more-posts" />');
            $li.text( get_message_value() );
            $ul.append( $li );
        }
    };

    self.display_no_more_pages_message = function(event){
        var display_no_more_pages_message = _.once( handle_message_no_pages_display );
        display_no_more_pages_message();
    };

    self.show_loader = function(){
        self.loader.loadShow( jQuery('i.js-collapse-group-individual').parent() ).css({
            float:'right'
        });
    };

    self.hide_loader = function(){
        self.loader.loadHide();
    };

    self.get_data_from_server = function (event) {

        self.show_loader();

        more_or_less( );

        var params = {
            wpnonce: jQuery('#wp_nonce_individual-pages-assigned').attr('value'),
            action: 'ddl_fetch_post_for_layout',
            layout_id: self.layout,
            single_amount_to_show_in_dialog: self.get_amount()
        };

        WPV_Toolset.Utils.do_ajax_post(params, {
            success: function (response) {

                self.hide_loader();

                var data = response.Data;

                if( data.hasOwnProperty('posts') ){
                    $container.empty().html( data.posts );
                    self.reload_handlers();
                }
            },
            error:function(response){
                self.hide_loader();
                console.error( response );
            }
        });
    };

    self.get_amount = function(){
        return self.AMOUNT;
    };

    self.set_more_or_less = function( bool ){
            more = bool;
    };

    self.set_amount = function( amount ){
        self.AMOUNT = amount;
    }

    self.init();
};


(function ($) {
    $(function () {
        DDLayout.changeLayoutUseHelper = new DDLayout.ChangeLayoutUseHelper($);
    })
}(jQuery));

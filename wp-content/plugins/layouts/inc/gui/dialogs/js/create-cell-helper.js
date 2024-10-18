var DDLayout = DDLayout || {};

DDLayout.CreateCellHelper = function ($) {
    var self = this,
        $button = $('.js-show-item-desc'),
        $dialog_content = $('.js-ddl-dialog-element-select'),
        templateHTML = $('.js-item-detail').html(),
        //    $dialog_messages = $('.js-element-box-message-container'),
        $message_container = null,
        $create_button = null,
        layout_instance = null,
        $search_input = $('.js-cells-tree-search');

    self.$target = null;
    self.$container = null;
    self.$close_preview = null;
    self._remove_tooltip = null;

    self.disallow_click = false;

    self.do_highlight_search = true;

    self._timer = null;
    self.keys_handler = null;

    self.disabled_cells_on_private_layout = DDLayout_settings.DDL_JS.strings.not_allowed_content_layout_cells;

    self.init = function () {
        self.dialog_loader();
        WPV_Toolset.Utils.eventDispatcher.listenTo(WPV_Toolset.Utils.eventDispatcher, 'ddl-create-cell-dialog-preview-opens', self.scroll_handler);
    };


    self.scroll_handler = function( $preview ){

        var $box = $preview, goto_pos = 0;

        if( $box[0] ){
            goto_pos = $box[0].offsetParent.offsetTop - 26;
        }

        $dialog_content.animate({
            scrollTop: goto_pos,
            duration: 1200,
            specialEasing: {
                scrollTop: "easeInOutSine"
            }
        });
    };

    self.dialog_loader = function(){

        jQuery(document).on('cbox_open', function(event){

        });

        jQuery(document).on('cbox_load', function(event){
            Toolset.hooks.addFilter('ddl-creation-dialog-opened', function(){
                return DDLayout.ddl_admin_page.is_creation_dialog_box;
            });
        });

        jQuery(document).on('cbox_complete', function(event){
            layout_instance = DDLayout.ddl_admin_page.instance_layout_view.model;

            if( Toolset.hooks.applyFilters('ddl-is_private_layout') === true ){
                jQuery( '.ddl-disable-cell-for-content-layout' ).css( 'opacity', '0.5' );
            }

            if( layout_instance.creating_cell === true ){
                self.do_highlight_search = true;
                _.defer( self.init_bindings, event );
                self.fix_names_subtitles();
                return true;
            } else {

                return false;
            }
        });

        jQuery(document).on('cbox_closed', function(event){
            self.reset_vars(event);
        });

        jQuery(document).on('cbox_cleanup', function(event){
            DDLayout.ddl_admin_page.is_creation_dialog_box = false;
        });
    };

    self.handle_promo_message_link = function(){
        $(document).on('mouseup', '.js-open-promotional-message-button', function(event){
            layout_instance.creating_cell = false;
        });
    };

    self.reset_vars = function(event){

        $button.off('mouseup', handlerUp);
        self.destroy_key_press();
        self.close_preview();
        $('.js-item-name').removeHighlight();
        // start counting from 0 all the time you open the dialog
        clearTimeout( self._timer );
        if( self.keys_handler ){
            self.keys_handler.clear_all();
            self.keys_handler = null;
        }
        DDLayout.ddl_admin_page.instance_layout_view.model.creating_cell = false;
    };

    self.set_search_highlight = function(){
        if( self.do_highlight_search ){
            highlight_search();
        }
    };

    var highlight_search = function(){
        jQuery('.js-ddl-search-lens').effect( "pulsate", {times:2, easing:'easeInExpo'}, 1800 );
        $search_input.effect( "highlight", {easing:'easeInExpo'}, 2000 );

    };

    self.init_bindings = function(event){
        self.keys_handler = DDLayout.CreateCellKeysHelper.call(self, $, layout_instance, $dialog_content);
        $button.on('mouseup', handlerUp);
        $button.on('dblclick', handlerDouble);
        self.handle_promo_message_link();
        self.registerKeyPress();
        clear_search();
        var shake = _.bind(self.set_search_highlight, self);
        self._timer = _.delay( shake, 10000 );
    };

    var handlerDouble = function( event ){
        event.preventDefault();
        event.stopImmediatePropagation();
        $('.js-show-cell-dialog', self.$container).trigger('click');
    };

    var handlerUp = function(event, from_key){
        event.preventDefault();
        event.stopImmediatePropagation();
        if(  self.$target && _.isEqual(self.$target, $(this) ) === false ){
            close_preview( event );
        }

        self.$target = $(this);

        if( self.keys_handler && typeof from_key === 'undefined' ){
            self.keys_handler.set_position( self.$target );
        }

        var
            data = $(this).data(),
            template = _.template( templateHTML),
            compiled = template( data),
            cat_count = $(this).data('cell-cat-count'),
            row_count = $(this).data('row-count');


        self.$container = $('.js-cell-tpl-container-'+cat_count+'-'+row_count).eq( 0 );


        if ( $(this).data('is-active') ) {
            close_preview(event, 400);
        } else {
            handle_open(this, compiled);
        }
    };

    var handle_open = function(that, compiled){
        self.make_cells_lighter(0.5);
        $(that).css({
            boxShadow:'0 0 0 1px #5b9dd9,0 0 2px 1px rgba(30,140,190,.8)'
        });
        $(that).data('is-active', true);
        self.$container.html(compiled);
        self.$close_preview = $('.js-close-cell-details', self.$container );
        self.$close_preview.on('click', close_preview);
        self.manage_no_multiple_cells();

        // disable views archive in case of editing private layout
        if( Toolset.hooks.applyFilters('ddl-is_private_layout') === true ){
            self.disable_specific_cell_for_private_layout();
        }


        self.manage_disabled_cells();
        WPV_Toolset.Utils.eventDispatcher.trigger('ddl-create-cell-dialog-preview-opens', $( '.js-cell-details', self.$container ) );
    };

    self.make_cells_lighter = function(opacity, duration ){

        $('.js-show-item-desc').each(function(index){
            if( _.isEqual( self.$target, $(this) ) === false ){
                $(this).fadeTo( duration , opacity, function() {
                    //console.log('malcom malcom semper malcom', $(this))
                });
            }
        });
    };

    var close_preview = function(event){
        event.preventDefault();
        if( self.$target === null || self.$container === null ) return;


        self.$target.css({
            boxShadow:'none'
        });
        self.reset_manage_multiple();
        self.reset_disabled_cells();
        self.make_cells_lighter(1, 100);
        self.$target.data('is-active', false);
        self.$target.removeClass('active');
        self.$container.empty();
        self.$target = null;
        self.$close_preview.off('click', close_preview);
    };

    self.close_preview = function () {
        var e = jQuery.Event( "click" );
        e.currentTarget = self.$close_preview;
        e.target = self.$close_preview;
        close_preview(e);
    };

    self.get_create_button = function(){
        return $create_button;
    };

    var handlerOver = function( event ){
        self.manageCellTooltip( $(this), 'show');
    };

    var handlerOut = function( event ){
        $el.toolsetTooltip('hide');
    };

    self.manageCellTooltip = function( $el, toggle ){
        $el.toolsetTooltip();
    };

    var clear_search = function(){
        $('.js-cells-tree-search').on('mouseup', function(){
            if( $(this).val() === $(this).data('default-val') ){
                $(this).val('');
            }
        })
    };

    self.show_new_dialog = function( event, handler, dialog ){
        if( self.disallow_click === true ) return;
        self.reset_vars(event);
        dialog._show_new_dialog( handler, dialog );
    };

    self.disable_specific_cell_for_private_layout = function(){
        var current_cell_type = self.$target.data('cell-type');

        if(jQuery.inArray( current_cell_type, self.disabled_cells_on_private_layout ) > -1) {

            $message_container = $('.js-element-preview-box-message', self.$container);
            self.disallow_click = true;
            $create_button = $('.js-show-cell-dialog', self.$container);

            $create_button.fadeTo(100, 0.5);

            var message_text = '';

            // refactor this, avoid to using if-else here
            if ( current_cell_type === 'post-loop-views-cell' ) {
                message_text = DDLayout_settings.DDL_JS.strings.you_cant_use_archive_cell;
            } else if( current_cell_type === 'comments-cell' ){
                message_text = DDLayout_settings.DDL_JS.strings.you_cant_use_comments_cell;
            } else {
                message_text = DDLayout_settings.DDL_JS.strings.you_cant_use_this_cell;
            }

            $message_container.wpvToolsetMessage({
                text: message_text,
                type: 'warning',
                stay: true,
                close: false,
                onOpen: function () {

                },
                onClose: function () {

                }
            }).css('margin-bottom', '10px');

            jQuery( '.js-item-desc', self.$container ).hide();
            jQuery( '.item-body', self.$container ).hide();
            jQuery( '.item-body-no-preview', self.$container ).hide();

        }
    }

    self.manage_no_multiple_cells = function(){

        var allow_multiple = self.$target.data('allow-multiple');

        if( allow_multiple ) return allow_multiple;

        var cell_type = self.$target.data('cell-type'),
            cell_exists = layout_instance.has_cell_of_type(cell_type);

        if( allow_multiple == false && cell_exists == true ){
            $message_container = $('.js-element-preview-box-message', self.$container);
            self.disallow_click = true;
            $create_button = $('.js-show-cell-dialog', self.$container );

            $create_button.fadeTo(100,0.5);

            $message_container.wpvToolsetMessage({
                text: DDLayout_settings.DDL_JS.strings.only_one_cell,
                type: 'warning',
                stay: true,
                close: false,
                onOpen: function() {

                },
                onClose: function() {

                }
            }).css('margin-bottom', '10px');
        }
    };

    self.manage_disabled_cells = function(){
        var disabled = self.$target.data('disabled');

        if( disabled !== "disabled" ){
            return true;
        } else if(  disabled === "disabled" ){
            self.disallow_click = true;
            return false;
        }
    };

    self.reset_disabled_cells = function(){
        self.disallow_click = false;
    };

    self.reset_manage_multiple = function(){

        if( $message_container == null ){
            return;
        } else {
            self.disallow_click = false;


            if( null !== $create_button ){

                $create_button.fadeTo(100, 1, function(){
                    $create_button = null;
                });
            }

            $message_container.wpvToolsetMessage('wpvMessageRemove');
            $message_container = null;
        }
    };

    self.fix_names_subtitles = function(){
        var $cell_name = $('.js-item-name');

        $cell_name.each(function(index, el){
            if( $(el).text().indexOf('(') !== -1 ){
                var chunks = $(el).text().split('(')
                    , $wrap = $('<div class="item-name-subtitle" />');
                $wrap.text( chunks[1].slice(0,-1) );
                $(el).text( chunks[0]).append( $wrap );
            }
        })
    };

    self.handle_search_key_down = function(event){
        event.stopImmediatePropagation();
        self.do_highlight_search = false;
        // pull in the new value
        var searchTerm = $(this).val();

        // remove any old highlighted terms
        $('.js-item-name').removeHighlight();

        // disable highlighting if empty
        if ( searchTerm ) {
            // highlight the new term
            $('.js-item-name').highlight( searchTerm );
        }
    };

    self.registerKeyPress = function(){
        jQuery(document).on( 'keyup change', '.js-cells-tree-search', self.handle_search_key_down );
    };

    self.destroy_key_press = function()
    {
        jQuery(document).off( 'keyup change', self.handle_search_key_down );
    };

    self.init();
};

DDLayout.CreateCellKeysHelper = function($, layout, $dialog){
    var self = this,
        $dialog_content,
        layout_instance;

    self.$target = null;
    self.rows = [];
    self._current = -1;

    self.init = function(layout, $dialog){
        layout_instance = layout;
        $dialog_content = $dialog;
        self.build_rows();
        self.init_key_bindings();
        $('.js-cells-tree-search').on('focus', reset_cursor );
    };

    self.clear_all = function(){
        $('.js-cells-tree-search').off('focus', reset_cursor );

        try{
            KeyboardJS.clear.key('left');
            KeyboardJS.clear.key('up');
            KeyboardJS.clear.key('right');
            KeyboardJS.clear.key('down');
            KeyboardJS.clear.key('enter');
            KeyboardJS.clear.key('escape');
        } catch( e ){
            console.log( e.message );
        }

    };

    var reset_cursor = function(){
        self._current = -1;
    };

    self.init_key_bindings = function(){
        try{
            KeyboardJS.on('left', onDownCallback);
            KeyboardJS.on('up', onDownCallback);
            KeyboardJS.on('right', onDownCallback);
            KeyboardJS.on('down', onDownCallback);
            KeyboardJS.on('enter', onDownCallback);
            KeyboardJS.on('escape', onDownCallback);
        } catch( e ){
            console.log( e.message );
        }

    };

    var onDownCallback = function(event, key, combo){
        if( ! DDLayout.ddl_admin_page.instance_layout_view.model.creating_cell ){
            KeyboardJS.clear(combo);
            return;
        }

        /* FIX FOR FIREFOX BUG */
        if( key.length === 2 && key[1] !== combo && combo === 'up'){
            return;
        }


        if( self.Keyboard[combo] && self.Keyboard[combo] instanceof  Function ){
            try{
                var current_item = self.Keyboard[combo].call(self);

            }catch( e ){
                // console.log( e.message );
                return;
            }

            if( current_item ){
                var $button = current_item.$button;
                $('.js-cells-tree-search').trigger('blur');
                $button.trigger('mouseup', event);
            } else{
                return false;
            }
        }

    };

    self.get_length = function(){
        return self.rows.length;
    };

    self.build_rows = function(){
        $('.js-category-row').each(function(index, item){
            self.rows.push( new DDLayout.CreateDialogRow($, item, index) );
        });
    };

    self.Keyboard = {
        'escape':function(){

            if( self.rows[self._current]  ){
                var current = self.rows[self._current].current();
                if( current.$button.data('is-active') ){
                    return self.rows[self._current].current();
                } else {
                    $.colorbox.close();
                    return false;
                }
            }else {
                $.colorbox.close();
                return false;
            }
        },
        'left' : function(){

            if( self._current === -1 ){
                self._current = self.get_length() - 1;
            }
            if( self.rows[self._current].has_visible_items() ){
                return self.rows[self._current].previous();
            } else {
                return self.Keyboard.up.call(self);
            }
        },
        'right':function(){

            if( self._current === -1 ){
                self._current = 0;
            }
            if( self.rows[self._current].has_visible_items() ) {
                return self.rows[self._current].next();
            } else {
                return self.Keyboard.down.call(self);
            }
        },
        'up': function(){
            self._current--;

            if( self._current < -1 ){
                reset_cursor();
                return false;
            }
            if( self._current === -1 ){
                var current = self.rows[0].current();
                if( current ){
                    self.rows[0].current().$button.trigger('mouseup');
                }
                $('.js-cells-tree-search').trigger('focus');

                return null;
            }
            if( self.rows[self._current].has_visible_items() ){
                return self.rows[self._current].current();
            } else {
                try{
                    return self.Keyboard.up.call(self);
                } catch(e){
                    //  console.log(e.message, self._current, self.rows[self._current]);
                    return false;
                }

            }
        },
        'down':function(){
            self._current++;

            if( self._current === self.get_length() ){
                self._current = self.get_length() -1;
                return null;
            }
            if( self.rows[self._current].has_visible_items() ){
                return self.rows[self._current].current()
            } else {
                try{
                    return self.Keyboard.down.call(self);
                } catch( e ){
                    //  console.log(e.message, self._current, self.rows[self._current]);
                    return false;
                }
            }
        },
        'enter':function(){

            if( self._current === -1 ){
                self._current = 0;
            }
            if( self.rows[self._current]  ){
                var current = self.rows[self._current].current();
                if( current.$button.data('is-active') ){
                    $('.js-show-cell-dialog', self.$container).trigger('click');
                    return false;
                } else {
                    return self.rows[self._current].current();
                }
            }
        }
    };

    self.set_position = function ($item) {
        var $row_el = $item.closest('.js-category-row'),
            current_row = _.findWhere(self.rows, {"$el": $row_el[0]});

        if (current_row instanceof DDLayout.CreateDialogRow) {
            self._current = self.rows.indexOf(current_row);
            var current_item = _.filter(current_row.get_items(), function (v) {
                return v.$button[0] === $item[0];
            });

            if (current_item.length) {
                current_item = current_item[0];
                var index = current_row.get_items().indexOf(current_item);
                current_row.set_current(index)
            }
        }
    };

    self.init(layout, $dialog);

    return self;
};

DDLayout.CreateDialogRow = function($, el, index ){
    var self = this;

    self.$el = el;
    self.items = [];
    self._index = index;
    self.length = 0;
    self._current = -1;

    self.init = function(){
        self.build_items();
        self.length = self.items.length;
    };

    self.build_items = function(){
        var $els = $('.js-grid-category-item', self.$el);
        $els.each(function(index, item){
            self.items.push( new DDLayout.CreateDialogItem($, item, index, self) );
        });
    };

    self.set_current = function( index ){
        self._current = index;
    };

    self.get_length = function(){
        return self.length;
    };

    self.get_items = function(){
        return self.items;
    };

    self.next = function(){
        if( self.has_visible_items() === false ) return null;

        self._current++;
        self.rows[self._current].has_visible_items
        if( self._current === self.get_length() ){
            self.rewind();
        }

        if( self.items[self._current].is_visible() ){
            return self.items[self._current];
        } else {
            try{
                if( self.has_visible_items() ){
                    return self.next();
                }
            } catch( e ){
                // console.log(e.message, self._current, self.items[self._current] )
            }
        }
    };

    self.current = function(){
        if( self.has_visible_items() === false ) return null;

        if( self.items[self._current] && self.items[self._current].is_visible() ){
            return self.items[self._current];
        } else {
            self.rewind();
            if( self.items[self._current].is_visible() ){
                return self.items[self._current];
            } else {
                try{
                    return self.next();
                } catch( e ){
                    //console.log(e.message, self._current, self.items[self._current] )
                }
            }
        }
    };

    self.rewind = function(){
        self._current = 0;
    };

    self.previous = function(){
        if( self.has_visible_items() === false ) return null;

        self._current--;

        if( self._current === -1 ){
            self._current = self.get_length() - 1;
        };
        if( self.items[self._current].is_visible() ){
            return self.items[self._current];
        } else {
            try{
                if( self.has_visible_items() ) {
                    return self.previous();
                }
            } catch( e ){
                // console.log(e.message, self._current, self.items[self._current] )
            }
        }
    };

    self.has_visible_items = function(){
        return _.filter(self.get_items(), function(v){ return v.is_visible() }).length > 0;
    };

    self.init( );
};

DDLayout.CreateDialogItem = function($, el, index, row){
    var self = this;

    self.el = el;
    self._index = index;
    self.row = row;
    self.$button = $('.js-show-item-desc', self.el );

    self.get_index = function(){
        return self._index;
    };

    self.is_first = function(){
        return self.is_visible() && self._index === 0;
    };

    self.is_last = function(){
        return self.is_visible() && self.get_index( ) + 1 === self.row.get_length();
    };

    self.is_visible = function(){
        return $(self.el).is(':visible');
    };
};

(function($) {
    DDLayout.create_cell_helper = {};
    DDLayout.CreateCellHelper.call(DDLayout.create_cell_helper, $);
})(jQuery);

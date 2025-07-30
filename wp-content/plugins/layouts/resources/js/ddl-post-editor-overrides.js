var DDLayout = DDLayout || {};

DDLayout._templateSettings = DDLayout._templateSettings || {
    escape: /\{\{([^\}]+?)\}\}(?!\})/g,
    evaluate: /<#([\s\S]+?)#>/g,
    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g
};

DDLayout.PostEditorOverrides = function ($) {
    var self = this,
        $editor_wrap = $('#postdivrich'),
        $message_hide_wrap = $('.js-ddl-post-content-message-in-post-editor'),
        $message_show_wrap = null,
        $js_ddl_switch_layout = null,
        $js_ddl_stop_using_layouts = null,
        $dummy_container = $('<div class="ddl-dummy-container js-ddl-dummy-container" />'),
        $overlay = $('<div class="ddl-post-editor-overlay js-ddl-post-editor-overlay toolset-alert" />'),
        $overlay_non_transparent = $('<div class="ddl-overlay-non-transparent js-ddl-overlay-non-transparent toolset-alert" />'),
        $hide_editor = $('.js-ddl-hide-editor'),
        $hide_overlay = $('.js-ddl-show-editor'),
        post, layout, private_layout, current_template = DDLayout_settings.DDL_JS.current_template;

    self._has_post_content = DDLayout_settings.DDL_JS.post.has_post_content_cell;
    self._has_private_layout = DDLayout_settings.DDL_JS.post.has_private_layout;
    self._private_layout_in_use = DDLayout_settings.DDL_JS.post.private_layout_in_use;

    self.init = function () {
        // _.templateSettings.variable = "ddl";

        if( $('#content_ifr').length > 0 || $('textarea#content.wp-editor-area').length > 0 ){
            self.manage_post_content_cell_in_post_editor();
            WPV_Toolset.Utils.eventDispatcher.listenTo( WPV_Toolset.Utils.eventDispatcher, 'ddl-layout-template-name-changed', self.set_up_from_outer_select);
            WPV_Toolset.Utils.eventDispatcher.listenTo( WPV_Toolset.Utils.eventDispatcher, 'ddl-post-editor-loaded-first', self.set_visibility_on_ready);
        }
    };

    self.set_up_from_outer_select = function( layout_slug ){

        var layouts = DDLayout_settings.DDL_JS.layouts,
            current = _.where(layouts, {slug: layout_slug })[0];

        self.set_layout( current );
        if(self._has_private_layout && DDLayout_settings.DDL_JS.post.private_layout_in_use){
            self.set_private_layout(self._has_private_layout);
        }

        self._has_post_content = current && current.has_post_content_cell;
        if( self._has_post_content && DDLayout_settings.DDL_JS.post.private_layout_in_use !== 'yes' ){
            self.remove_overlay();
            $message_hide_wrap.hide();
        } else {
            self.empty_overlay();
            self.hide_editor_on_ready();
        }

    };

    self.get_template = function()
    {
        return current_template;
    };

    self.set_current_template_from_option_value = function( combined_name ){

        if( !combined_name ){

            current_template = combined_name;

        } else {

            var template = combined_name.split(' in ');

            if( template[1] ){
                current_template = template[1];
            }
        }
    };

    self.hide_editor_on_ready = function ( ready ) {

        if(!self.get_layout() && DDLayout_settings.DDL_JS.post.private_layout_in_use === false) return;

        if(DDLayout_settings.DDL_JS.post.private_layout_in_use){
            var template = $("#js-ddl-post-content-message-in-post-editor-private-tpl").html();
        } else {
            var template = $("#js-ddl-post-content-message-in-post-editor-tpl").html();
        }


        var message_template = $('#js-ddl-post-content-message-in-post-editor-html').html()
            , template_data = {}, switch_manager, stop_using_layouts;

        template_data.post = self.get_post();

        if(DDLayout_settings.DDL_JS.post.private_layout_in_use){
            template_data.layout = self.get_private_layout();
        } else {
            template_data.layout = self.get_layout();
        }
        self._has_post_content = template_data.layout.has_post_content_cell;
        $overlay_non_transparent.html( WPV_Toolset.Utils._template(template, template_data, DDLayout._templateSettings) );

        $message_hide_wrap.html( WPV_Toolset.Utils._template( message_template, self.get_layout(), DDLayout._templateSettings ) );

        $overlay.addClass("ddl-overlay-for-post-type-"+post.post_type);
        $overlay_non_transparent.addClass("ddl-overlay-non-transparent-for-post-type-"+post.post_type);
        $editor_wrap.css("position", "relative");
        $dummy_container.append( $overlay, $overlay_non_transparent  );
        $editor_wrap.append( $dummy_container);

        self.set_overlay_heigtht();

        $message_show_wrap = $('.js-ddl-post-content-show-post-post-editor-wrap');
        $js_ddl_switch_layout = $('.js-ddl-switch-layout-button');
        switch_manager = new DDLayout.SwitchLayoutManager($, $js_ddl_switch_layout);

        $js_ddl_stop_using_layouts = $('.js-ddl-stop-using-layouts-button');
        stop_using_layouts = new DDLayout.StopUsingLayoutsManager($, $js_ddl_stop_using_layouts);

        if( ready === true ) {
            WPV_Toolset.Utils.eventDispatcher.trigger( 'ddl-post-editor-loaded-first' );
        } else {
            self.set_visibility_on_ready();
        }

    };

    self.set_overlay_heigtht = function(){
        var check_height = $('#content_ifr')[0] || $('textarea#content.wp-editor-area')[0],
			// make sure we do not cover WPML language options radio, but we abundantly cover the tinyMCE textarea when WPML is not active if WP version is lower than 5.0.3, if it's 5.0.3 or higher make sure it doesn't cover the new metaboxes GUI
			dummyContainerHeight = +DDLayout_settings.DDL_JS.wpVersion503 === -1 ? DDLayout_settings.DDL_JS.isWpmlActive ? '97%' : '109%' : '96.5%';

        // Adjustment for when the editor has a height higher than default (empty), without overflowing the viewport.
        $dummy_container.height(dummyContainerHeight);

        if( check_height.offsetHeight >= $('body')[0].offsetHeight ){
            var $info = $('.ddl-post-content-editor-layout-info'), $show_wrap = $('.ddl-post-content-show-post-post-editor-wrap');
            $info.css('top','5%');
            $show_wrap.css('top','5%');
        }
    };

    self.animate_overlay = function(action, speed, callback, args){

        var params = {
            'show': [1, 0.6, 1],
            'hide': [0, 0, 0],
            'slow' : [500, 600, 800],
            'fast': [300, 300, 300],
            'very': [100, 200, 300]
        };

        if( params[action][0] > 0 ){
            $dummy_container.show();
        }

        $dummy_container.animate({
            opacity: params[action][0]
        }, params[speed][0], function () {

            $overlay.animate({
                opacity: params[action][1],
                specialEasing: {
                    background: "easeOutBounce"
                }
            }, params[speed][1]);

            $overlay_non_transparent.animate({
                opacity: params[action][2],
                specialEasing: {
                    background: "easeOutBounce"
                }
            }, params[speed][2], function(){

            });

            if( typeof callback !== 'undefined' && typeof callback == 'function'){
                callback.apply( self, args );
            }

            if( params[action][0] === 0 ){
                $dummy_container.hide();
            }

        });
    };


    self.empty_overlay = function(){
        $dummy_container.empty().hide();
    };

    self.remove_overlay = function(){
        $dummy_container.remove();
    };

    self.set_overlay = function(){
        $overlay = $('<div class="ddl-post-editor-overlay js-ddl-post-editor-overlay toolset-alert" />');
        $overlay_non_transparent = $('<div class="ddl-overlay-non-transparent js-ddl-overlay-non-transparent" />');
        $dummy_container = $('<div class="ddl-dummy-container js-ddl-dummy-container" />');
    };

    self.show_editor = function () {

        $(document).on('click', '.js-ddl-show-editor', function () {

            self.animate_overlay('hide', 'fast', function(){
                $message_hide_wrap.show(300);
                jQuery.jStorage.set( self.get_post().ID, {'ddl-overlay-hide': true}  );
            });
        });

    };

    self.hide_editor = function () {
        $(document).on('click', '.js-ddl-hide-editor', function () {

            $message_hide_wrap.fadeOut(300, function () {
                self.animate_overlay('show', 'very');
                jQuery.jStorage.set( self.get_post().ID, {'ddl-overlay-hide': false}  );
            });
        });
    }

    self.manage_post_content_cell_in_post_editor = function () {
        self.set_post( DDLayout_settings.DDL_JS.post );

        self.set_layout( DDLayout_settings.DDL_JS.layout );
        if(self._has_private_layout){
            self.set_private_layout(self._has_private_layout);
        }
        self.show_editor();
        self.hide_editor();

        if ( self._has_post_content === false || self._private_layout_in_use) {
            if(self._has_private_layout && self._private_layout_in_use){
                // force overlay in case if private layout is active

                jQuery.jStorage.set( self.get_post().ID, {'ddl-overlay-hide': false}  );
                _.defer(self.hide_editor_on_ready, false);
            } else {
                _.defer(self.hide_editor_on_ready, true);
            }
        }

    };

    self.set_visibility_on_ready = function(){

        if( self.get_settings() === false ){
            self.animate_overlay('show', 'slow');
        } else{
            $message_hide_wrap.fadeIn(300, function(){
                $dummy_container.hide();
            });
        }
    };

    self.get_settings = function()
    {
        var settings = jQuery.jStorage.get( self.get_post().ID );

        settings = settings && settings['ddl-overlay-hide'] === true ? true : false;

        return settings;
    };

    self.set_post = function( p ){
        post = p;
    };

    self.get_post = function(){
        return post;
    };

    self.set_layout = function( l ){
        layout = l;
    };

    self.get_layout = function(){
        return layout;
    };

    self.set_private_layout = function( l ){
        private_layout = l;
    };
    self.get_private_layout = function(){
        return private_layout;
    };

    self.init();

};

DDLayout.SwitchLayoutManager = function($, $button ){
    var self = this,
        current = DDLayout.post_editor_overrides.get_layout(),
        post = DDLayout.post_editor_overrides.get_post(),
        $message = null,
        layouts = null,
        $combined = $('#js-combined-layout-template-name'), $select;

    //  self = _.extend(self, Backbone.Events);

    self.trigger = $button;

    _.extend( DDLayout.SwitchLayoutManager.prototype, new DDLayout.Dialogs.Prototype(jQuery) );

    self.init = function(){
        //_.templateSettings.variable = "ddl";

        self.trigger.on('click', self.open_dialog);

        WPV_Toolset.Utils.loader = new WPV_Toolset.Utils.Loader();
    };

    self.open_dialog = function(event){
        event.preventDefault();

        layouts = DDLayout_settings.DDL_JS.layouts;

        var template = $("#js-ddl-post-content-switch-layout-dialog-html").html();
        ////console.log('current to tpl', current.name, current.slug );
        $("#js-ddl-post-content-switch-layout-dialog-wrap").html( WPV_Toolset.Utils._template( template, {layouts : layouts, current : current, post : post}, DDLayout._templateSettings ) );

        jQuery.colorbox({
            href: '#js-ddl-post-content-switch-layout-dialog-wrap',
            inline: true,
            open: true,
            closeButton:false,
            fixed: true,
            top: false,
            width:"400px",
            onComplete: function() {
                self.init_toolset_select2_box();
                self.update_layout();
            },
            onCleanup: function() {
                $select.toolset_select2('close');
            }
        });
	};

	self.escapeMarkup = function( m ) {
		return $.fn.toolset_select2_original.defaults.defaults.escapeMarkup( m );
	}

    self.init_toolset_select2_box = function(){
        $select = $('.js-ddl-switch-layout');

        function format(state) {
            if( state.css == 'cell-content-template')
            {
                return '<div class="div-option-icon cell-content-template"><i class="item-type-icon icon-views-logo ont-color-orange ont-icon-16"></i>' + self.escapeMarkup( state.text ) + '';
            } else if( state.css == 'cell-post-content'){
                return '<div class="div-option-icon cell-post-content"><i class="item-type-icon icon-file-text fa fa-file-text"></i>' + self.escapeMarkup( state.text ) + '';
            } else if( state.css == 'cell-content-template-no-body' ){
                return '<div class="div-option-icon cell-content-template"><i class="item-type-icon ont-color-orange disabled-icon icon-views-logo ont-icon-16"></i>' + self.escapeMarkup( state.text ) + '';
            }
            else {
                return '<div class="div-option-icon no-icon">' + self.escapeMarkup( state.text );
            }

        }

        $select.toolset_select2({
            templateResult: format,
            templateSelection: format,
            width: '100%',
			height:'30px',
			// Disable escaping the markup because we need teplates for results and selection.
			// Note that we are escaping both formats manually so this should be safe.
            escapeMarkup: function(m) { return m; }
        });
    };

    self.update_layout = function()
    {
        var $save = $('.js-switch-layout-button-save');

        $message = $('.switch-layout-message-container');

        $save.on('click', function(event){
            event.preventDefault();
            var  value = $select.find('option:selected').val()
                , selected = _.where(layouts, {slug: value })[0];

            if( value === current.slug )
            {
                $message.wpvToolsetMessage({
                    text:DDLayout_settings.DDL_JS.message_same + ' ' + post.post_title,
                    type: 'message',
                    stay: false,
                    close: false,
                    onOpen: function() {
                        jQuery('html').addClass('toolset-alert-active');
                    },
                    onClose: function() {
                        jQuery('html').removeClass('toolset-alert-active');
                    }
                });

                return;
            }
            else{
                $message.wpvToolsetMessage('destroy');
                current = selected;


                if( post.post_type === 'page' )
                {
                    self.set_combined_new_val();
                }
                else{
                    //js-layout-template-name
                    self.set_select_layout_new_val();
                }

                if( selected ){

                    var data = {
                        'action': 'ddl_update_template_layout',
                        'layout_slug': selected.slug,
                        'post_id': DDLayout_settings.DDL_JS.post.ID,
                        'layout_id': selected.id,
                        'wpnonce' : DDL_Private_layout.private_layout_nonce
                    };

                    jQuery.post(ajaxurl, data, function( response ) {
                        WPV_Toolset.Utils.eventDispatcher.trigger( 'ddl-layout-template-name-changed', current.slug );
                        jQuery( '#js_selected_layout_template_name' ).text( current.name );
                        jQuery('.js-edit-layout-template').attr('href', jQuery('.js-edit-layout-template').data('href') + current.id );
                    }, 'json');
                }



                jQuery.colorbox.close()
                //self.open_dialog_confirm(event);
            }
        });
    };

    self.set_select_layout_new_val = function(){
        var $layout_select = jQuery( '#ddl-js-layout-template-name' );
        $layout_select.val(current.slug);
        $layout_select.trigger('change');
    };

    self.get_combined_name = function()
    {
        if( !DDLayout.post_editor_overrides.get_template() || DDLayout.post_editor_overrides.get_template() === '' ){
            DDLayout.post_editor_overrides.set_current_template_from_option_value( $combined.find(':selected').val() );
        }

        return current.slug +' in ' +  DDLayout.post_editor_overrides.get_template();
    };

    self.set_combined_new_val = function(){
        $combined.val( self.get_combined_name() );
        $combined.trigger('change', self.get_combined_name() );
    };


    // deprecated
    self.open_dialog_confirm = function(event){
        event.preventDefault();

        var template = $("#js-ddl-post-content-switch-layout-dialog-confirm-html").html();

        $("#js-ddl-post-content-switch-layout-dialog-confirm-wrap").html( WPV_Toolset.Utils._template( template, {current : current, post : post}, DDLayout._templateSettings ) );

        jQuery.colorbox({
            href: '#js-ddl-post-content-switch-layout-dialog-confirm-wrap',
            inline: true,
            open: true,
            closeButton:false,
            fixed: true,
            top: false,
            onComplete: function() {

            },
            onCleanup: function() {

            }
        });

    };

    self.init();
};


DDLayout.PostEditorOverlayOverrides = function ($) {
    var self = this,
        $editor_wrap = $('#postdivrich'),
        $dummy_container = $('.ddl-dummy-container.js-ddl-dummy-container'),
        $overlay_non_transparent = $('.ddl-overlay-non-transparent.js-ddl-overlay-non-transparent');

    self.set_overlay_height = function(){
        var editor_height = $editor_wrap.outerHeight();

      //  $dummy_container.height(editor_height + 'px');

        var non_trans_pos = $overlay_non_transparent.position();
        var non_trans_height = $dummy_container.height();

        if( non_trans_pos ) {
            non_trans_height = $dummy_container.height() - non_trans_pos.top;
        }

      //  $overlay_non_transparent.height(non_trans_height + 'px');
    };

    self.set_overlay_height();
};



(function ($) {
    $(function () {
        DDLayout.post_editor_overrides = {};
        DDLayout.PostEditorOverrides.call(DDLayout.post_editor_overrides, $);
    });

    $(window).on('load', function(){
        DDLayout.post_editor_overlay_overrides = {};
        DDLayout.PostEditorOverlayOverrides.call(DDLayout.post_editor_overlay_overrides, $);
    });
}(jQuery));

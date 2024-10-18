var DDLayout = DDLayout || {};

DDLayout.ImageBoxCell = function($){
        var self = this,
            $ul = $('.js-form-image-box-wrap'),
            $markup = $('.ddl-markup-controls'),
            $button = $('.js-ddl-media-imagebox'),
            $preview_box = $('.ddl-imagebox-cell-preview-wrap'),
            dialog_object = null,
            current_view = null,
            current_model = null,
            image_url = '',
            cell_content = null,
            $alignment = $('input[name="ddl-layout-image_alignment"]'),
            $link_url = $('input[name="ddl-layout-image_link_url"]'),
            $link_to = $('select[name="ddl-layout-image_link_to"]'),
            $img_effect = $('select[name="ddl-layout-display_responsive_image"]'),
            $org_w = $('input[name="ddl-layout-box_image_org_w"]'),
            $org_h = $('input[name="ddl-layout-box_image_org_h"]'),
            $custom_size_properties = $('.custom_size_property'),
            $image_size = $('select[name="ddl-layout-image_size"]'),
            $custom_w = $('input[name="ddl-layout-custom_image_width"]'),
            $custom_h = $('input[name="ddl-layout-custom_image_height"]'),
            max_height = 200,
            max_width = 300;

    self.$media_url = null;
    self.site_url = DDLayout_settings.DDL_JS.site_url;
    self.attachment_id = null;

    self.init = function(){
        jQuery(document).on('imagebox-cell.dialog-open', self._dialog_open);
        jQuery(document).on('imagebox-cell.dialog-close', self._dialog_close);
    };

    self._dialog_open = function( event, content, dialog ){
        self.$media_url = $('.js-ddl-media-url');
        dialog_object = dialog;

        if( dialog_object.is_new_cell() == false ){
            current_view = dialog_object.get_target_cell_view();
            current_model = current_view.model;
            cell_content = current_model.get('content');
            image_url = cell_content.box_image;
            self.attachment_id = cell_content.attachment_box_image;
        } else {
            cell_content = {};
        }
    //    console.log( cell_content, self.attachment_id, cell_content.attachment_box_image)
        self.handle_form_elements_visibility( );
        self.set_events();
        self.toggle_allignment_active();
    };

    self.img_effect = function(){
        $img_effect.off('change', self.set_img_effect);
        $img_effect.on('change', self.set_img_effect);
    };

    self.set_img_effect = function(event){
        $('img[title="preview"]').prop('class', '').addClass( $(this).val() );
    };

    self.set_initial_effect = function( effect, target ){
        var effect = typeof effect === 'undefined' ? $img_effect.val() : effect,
            $target = target || $('img[title="preview"]');
        $target.prop('class', '').addClass( effect );
    };

    self.set_events = function(){
        self.$media_url.on('change', self.url_change);
        $link_to.on('change', self.handle_link_to_change);
        $(document).on('change', 'input[name="ddl-layout-attachment_box_image"]',self.listen_attachment_id_change);
        $image_size.on('change', self.image_size_change);
    };

    self.turn_off_events = function(){
        self.$media_url.off('change', self.url_change);
        $link_to.off('change', self.handle_link_to_change);
        $alignment.off('change', self.toggle_alignment_class);
        $(document).off('change', 'input[name="ddl-layout-attachment_box_image"]',self.listen_attachment_id_change);
    };

    self.toggle_allignment_active = function(){
        $('.ddl-image_alignment-group').find('input[type="radio"]').each(function(){

            if(  cell_content.image_alignment === $(this).val() ){
                $(this).prop( 'checked', true).parent('label').addClass('active');
            } else if( cell_content.image_alignment !== $(this).val() ) {
                $(this).prop( 'checked', false).parent('label').removeClass('active');
            }

        });
        $alignment.on('click', self.toggle_alignment_class);
    };

    self.listen_attachment_id_change = function(event){
        //console.log( event.target, $(this).val() )
        self.attachment_id = $(this).val();
        self.handle_form_elements_visibility( );
    };

    self.toggle_alignment_class = function(event) {
        var clicked = $(this).val();
        if ( $(this).is(':checked') ) {
            $(this).prop('checked', true).parent('label').addClass('active');
        }

        $('.ddl-image_alignment-group').find('input[type="radio"]').each(function(){
                if( clicked !== $(this).val() ){
                    $(this).prop('checked', false);
                    $(this).parent('label').removeClass('active');
                }
        });
    };

    self.set_preview = function( args ){
        var args = args || {},
            img = typeof args.url === 'undefined' ? self.get_image_url() : args.url,
            $img = $('<img alt="preview" title="preview" src="'+img+'" class="ddl-preview-image-box" />').css('visibility', 'hidden'),
            $append = args.append ? args.append : $preview_box,

        max_w = typeof args.mw === 'undefined' ? max_width : args.mw,
        max_h = typeof args.mh === 'undefined' ? max_height : args.mh;

        $append.empty();
        $append.append( $img );

        _.delay(function(){

            var height = $img[0].offsetHeight, width = $img[0].offsetWidth;
            $org_h.val( height );
            $org_w.val( width );

            if( height > width ){
                var ratio = width / height,
                    h = max_h > DDLayout_settings.DDL_JS.media_settings.height ? max_h : DDLayout_settings.DDL_JS.media_settings.height;
                    h = h < height ? h : height;
                $img.height( h );
                $img.width( h * ratio );

            } else {

                var ratio = height / width,
                    w = max_w > DDLayout_settings.DDL_JS.media_settings.width ? max_w : DDLayout_settings.DDL_JS.media_settings.width;
                w = w < width ? w : width;
                $img.width( w );
                $img.height( w * ratio );

            }

            $img.css('visibility', 'visible');

        }, 200);

        self.img_effect( args.effect, $img );
    };

    self.url_change = function(event, obj){
        var value = $(this).val();
        image_url = value;
    };

    self.link_to_val = function(){
        return $link_url.val();
    };

    self.set_link_to_val = function( val ){
        $link_url.val( val).trigger('change');
    };

    self.has_image = function(){
        return self.get_image_url() !== '';
    };

    self.get_image_url = function(){
        return image_url;
    };

    self.get_attachment_id = function(){
        return self.attachment_id;
    };

    self.handle_form_elements_visibility = function(  ){

        if( self.has_image() ){
            set_for_edit();

        } else{
            set_for_anew( );
        }
    };

    self.build_link_to_options = function(){
       // console.log( self.attachment_id, self.get_attachment_id())
            var urls = {
                'Media File' : self.get_image_url(),
                'Attachment Page' : self.site_url + '/?attachment_id=' + self.get_attachment_id(),
                'Custom URL' : 'custom',
                'None' : ''
            }, html = '', value = self.link_to_val();

        _.each(urls, function(v, k){
                var selected = '';

            if( v === value && k !== 'Custom URL' ){

                selected = 'selected="selected"';
            }
            else if( typeof value !== 'undefined' &&
                        value !== '' &&
                        value !== urls['Media File'] &&
                        value !== urls['Attachment Page'] &&
                         k === 'Custom URL'){
                v = value;
                selected = 'selected="selected"';
            } else if( ( typeof value === 'undefined' || value === '' ) && k === 'None' ){
                
                selected = 'selected="selected"';
            }

            html += '<option value="'+v+'" '+selected+'>'+k+'</option>';

        });

        $link_to.empty().append( html).val(value);
    };

    self.handle_link_to_change = function( event ){
        var value = $(this).val() === 'custom' ? '' : $(this).val();
        self.set_link_to_val( value );
    };

    self.image_size_change = function(event){
        if($image_size.val() == "Custom"){
            $custom_size_properties.show();
        }else{
            $custom_size_properties.hide();
        }
    };

    var set_for_edit = function( ){
        var $inputs = $ul.find('input'),
            $labels = $ul.find('label'),
            $spans = $ul.find('.label'),
            $selects = $ul.find('select'),
            $textareas = $ul.find('textarea');

        $inputs.show();
        $labels.show();
        $textareas.show();
        $selects.show();
        $markup.show();
        $spans.show();
        $('li', $ul).show();
        $('.js-ddl-media-field-edit').addClass('ddl-border-bottom');
        self.set_preview(  {
            mw: max_width,
            mh: max_height
        }  );
        self.build_link_to_options();

        $button.removeClass('ddl-button-media-huge button-primary').text( DDLayout_settings.DDL_JS.strings.image_box_change).addClass('button-secondary');
        self.set_initial_effect();

        if($image_size.val() != 'Custom'){
            $custom_size_properties.hide();
        }
    };

    var set_for_anew = function(){
        var $inputs = $ul.find('input'),
            $labels = $ul.find('label'),
            $spans = $ul.find('.label'),
            $selects = $ul.find('select'),
            $textareas = $ul.find('textarea');

        $inputs.hide();
        $labels.hide();
        $textareas.hide();
        $selects.hide();
        $markup.hide();
        $spans.hide();
        $('li', $ul).not('.js-ddl-media-field-edit').hide();
        $('.js-ddl-media-field-edit').removeClass('ddl-border-bottom');
        $button.addClass('ddl-button-media-huge button-secondary').text( DDLayout_settings.DDL_JS.strings.image_box_choose).addClass('button-primary');
    };

    self._dialog_close = function () {
            var undefined;
            dialog_object = null;
            current_view = null;
            current_model = null;
            image_url = '';
            self.set_link_to_val( '' );
            $preview_box.empty();
            $link_to.empty();
            self.turn_off_events();
    };

    self.init();
};

DDLayout.ImageBoxCell.prototype.returnImagePreviewAsHtmlString = function( params ){
    var height = params.org_h,
        width = params.org_w,
        w = DDLayout_settings.DDL_JS.preview_width >= width ? width : DDLayout_settings.DDL_JS.preview_width,
        h = DDLayout_settings.DDL_JS.preview_height >= height ? height : DDLayout_settings.DDL_JS.preview_height,
        url = params.url,
        align = params.align,
        effect = params.effect,
        align_text = '';

    if( height > width ){
        var ratio = width / height;
        w = h * ratio;
    } else {

        var ratio = height / width;
        h = w * ratio;
    }

    if( align === 'right' || align === 'left' ){
        effect += ' ddl-align-'+align;
    }

    return {
            img: '<img ' + align_text + ' src="' + url + '" width="' + w + '" height="' + h + '" class="' + effect + '" />',
            h: h,
            w: w,
            url:url,
            effect:effect,
            align:align
        };
};

jQuery(document).on('DLLayout.admin.ready', function($){
    DDLayout.image_box = new DDLayout.ImageBoxCell(jQuery);
});
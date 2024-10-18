var DDLayout = DDLayout || {};

DDLayout.LayoutsToolsetSupport = function($){

};

DDLayout.LayoutsToolsetSupport.prototype.operate_extra_controls = function( $root, $append_to ){
    var data = this.fetch_extra_controls( $root );
    var controls = data.controls;

    jQuery( $append_to ).append(controls);
    jQuery('#'+$root+' .js-ddl-tag-name').val(data.tag).trigger('change');
    jQuery('#'+$root+' .js-edit-css-id').css('width', '555px');

    var classes = typeof TOOLSET_IN_IFRAME_SETTINGS !== 'undefined' && TOOLSET_IN_IFRAME_SETTINGS.layouts_css_properties ? TOOLSET_IN_IFRAME_SETTINGS.layouts_css_properties.additionalCssClasses : '';
    var array_with_classes = (Array.isArray(data.css)) ? data.css : [];

    var chosen_args = {
        'width': "555px",
        'no_results_text': 'Press Enter to add new entry:',
        'display_selected_options': false,
        'display_disabled_options': false
    };

    _.defer(function(){
        jQuery( 'select.js-toolset-chosen-select-iframe', jQuery('#'+$root) ).toolset_chosen_multiple_css_classes( chosen_args, _.union(classes, array_with_classes) , array_with_classes );

    });


    jQuery('#'+$root+' #ddl-default-edit-cell-name').val(data.name);

    return data;
};

DDLayout.LayoutsToolsetSupport.prototype.fetch_extra_controls = function(who){
        return null;
};

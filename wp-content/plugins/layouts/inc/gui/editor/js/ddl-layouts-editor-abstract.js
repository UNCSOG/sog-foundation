// Makes sure _ is not Undefined, although surprisingly following this comparison list: https://github.com/lodash/lodash/wiki/Migrating
// we are working with Underscore rather than Lodash, some clarification to WP/Gutenberg dev team would be needed
// !!! This is NOT working in a separate file or in any dependency, it MUST be declared at the top of the application point of entry file !!!
window._ = window._ || window.lodash;

var DDLayout = DDLayout || {};

DDLayout.AdminPageAbstract = function(){
    var self = this;
};

_.extend(DDLayout.AdminPageAbstract.prototype, Backbone.Events);

DDLayout.AdminPageAbstract.prototype.instance_layout_view = null;
DDLayout.AdminPageAbstract.prototype.undo_redo = null;
DDLayout.AdminPageAbstract.prototype.key_handler = null;
DDLayout.AdminPageAbstract.prototype.breadcrumbs = null;
DDLayout.AdminPageAbstract.prototype._new_cell_target = null;
DDLayout.AdminPageAbstract.prototype._default_dialog = null;
DDLayout.AdminPageAbstract.prototype._row_dialog = null;
DDLayout.AdminPageAbstract.prototype._theme_section_row_dialog = null;
DDLayout.AdminPageAbstract.prototype._container_dialog = null;
DDLayout.AdminPageAbstract.prototype._save_state = null;

DDLayout.AdminPageAbstract.prototype._add_cell = null;
DDLayout.AdminPageAbstract.prototype.is_slug_edited = false;

DDLayout.AdminPageAbstract.prototype.element_name_editable_now = [];
DDLayout.AdminPageAbstract.prototype.is_in_editable_state = false;
DDLayout.AdminPageAbstract.prototype.is_private_layout = false;

DDLayout.AdminPageAbstract.prototype.open_cell_dialog = function(mode, cell){
    this.before_open_dialog();
    this.dialog.show(mode, cell);
};

DDLayout.AdminPageAbstract.prototype.show_default_dialog = function (mode, cell_view) {
    this._default_dialog.show(mode, cell_view);
};

DDLayout.AdminPageAbstract.prototype.clean_up_default_dialog = function () {
    this._default_dialog.clean_up();
};

DDLayout.AdminPageAbstract.prototype.show_row_dialog = function (mode, row_view) {
    if (!this._row_dialog) {
        this._row_dialog = new DDLayout.RowDialog(jQuery, row_view);
    }
    this.before_open_dialog();
    this._row_dialog.show(mode, row_view);
};

DDLayout.AdminPageAbstract.prototype.show_tab_dialog = function (mode, row_view) {
    if (!this._tab_dialog) {
        this._tab_dialog = new DDLayout.TabDialog(jQuery, row_view);
    }
    this.before_open_dialog();
    this._tab_dialog.show( mode, row_view );
};

DDLayout.AdminPageAbstract.prototype.show_accordion_dialog = function (mode, row_view) {
    if (!this._accordion_dialog) {
        this._accordion_dialog = new DDLayout.AccordionDialog(jQuery, row_view);
    }
    this.before_open_dialog();
    this._accordion_dialog.show( mode, row_view );
};

DDLayout.AdminPageAbstract.prototype.show_panel_dialog = function( mode, row_view ){
    if (!this._panel_dialog) {
        this._panel_dialog = new DDLayout.PanelDialog(jQuery, row_view);
    }
    this.before_open_dialog();
    this._panel_dialog.show( mode, row_view );
}

DDLayout.AdminPageAbstract.prototype.show_tabs_dialog = function( mode, tabs){

    if (!this._tabs_dialog) {
        this._tabs_dialog = new DDLayout.TabsDialog(jQuery, tabs);
    }
    this.before_open_dialog();
    this._tabs_dialog.show( mode, tabs );
};

DDLayout.AdminPageAbstract.prototype.show_theme_section_row_dialog = function( mode, row_view, caller )
{
    if (!this._theme_section_row_dialog) {
        this._theme_section_row_dialog = new DDLayout.ThemeSectionRowDialog(jQuery);
    }
    this.before_open_dialog();
    this._theme_section_row_dialog.show( mode, row_view, caller );
};

DDLayout.AdminPageAbstract.prototype.show_container_dialog = function( mode, container_view)
{
    if (!this._container_dialog) {
        this._container_dialog = new DDLayout.ContainerDialog();
    }
    this.before_open_dialog();
    this._container_dialog.show(mode, container_view);
};

DDLayout.AdminPageAbstract.prototype.replace_selected_cell = function (new_cell, new_width, avoid_render) {
    return true;
};

DDLayout.AdminPageAbstract.prototype.take_undo_snapshot = function() {
    var modelJSON = this.get_layout_as_JSON();

    try{
        this.undo_redo.take_undo_snapshot(modelJSON);
    } catch ( e ){
        console.log( e.message )
    }
};

DDLayout.AdminPageAbstract.prototype.add_snapshot_to_undo = function() {
    try{
        this.undo_redo.add_snapshot_to_undo();
    }catch ( e ){
        console.log( e.message )
    }
};

DDLayout.AdminPageAbstract.prototype.save_undo = function() {
    var modelJSON = this.get_layout_as_JSON();

    try{
        this.undo_redo.save_undo( modelJSON );
    }catch ( e ){
        console.log( e.message )
    }
};

DDLayout.AdminPageAbstract.prototype.do_undo = function() {
    try{
        this.undo_redo.handle_undo();
    } catch( e ){
        console.log( e.message );
    }
};

DDLayout.AdminPageAbstract.prototype.do_redo = function() {
    try{
        this.undo_redo.handle_redo();
    } catch( e ){
        console.log( e.message );
    }
};

DDLayout.AdminPageAbstract.prototype.get_layout = function () {
    try{
        return this.instance_layout_view.model;
    } catch( e ){
        console.log( e.message );
        return null;
    }
};

DDLayout.AdminPageAbstract.prototype.get_layout_as_JSON = function() {
    try{
        return this.instance_layout_view.getLayoutModelToJs();
    } catch( e ){
        console.log( 'This is not a blocking error: ', e.message );
        return null;
    }
};

DDLayout.AdminPageAbstract.prototype.replace_selected_cell = function (new_cell, new_width, avoid_render) {
    try{
        this.instance_layout_view.eventDispatcher.trigger('replace_selected_cell', new_cell, new_width, avoid_render);
    } catch( e ){
        console.log( e.message );
    }
};

DDLayout.AdminPageAbstract.prototype.getLayoutType = function()
{
    try{
        return this.instance_layout_view.getLayoutType();
    } catch( e ){
        console.log( e.message );
        return 'fluid';
    }
};

DDLayout.AdminPageAbstract.prototype.set_layout = function(layout) {
    var layout_model = this.get_layout();

    if( !layout_model ) return;

    layout_model.parse(layout);
    layout_model.populate_self_on_first_load(layout);
    this.render_all();
};

DDLayout.AdminPageAbstract.prototype.save_layout = function (callback) {
    try{
        this.instance_layout_view.saveLayout(null, callback);
    } catch( e ){
        console.log( e.message );
    }
};

DDLayout.AdminPageAbstract.prototype.render_all = function ( options ) {
    try{
        this.instance_layout_view.render( options );
    }catch( e ){
        console.log( e.message );
    }
};

DDLayout.AdminPageAbstract.prototype.set_parent_layout = function ( parent_layout ) {
    this.save_undo();

    var layout = this.get_layout();

    if( !layout ) return;

    layout.set_parent_layout( parent_layout );
};

DDLayout.AdminPageAbstract.prototype.get_parent_layout = function () {
    return this.get_layout().get_parent_layout();
};

DDLayout.AdminPageAbstract.prototype.set_save_required = function () {
    this._save_state.set_save_required();
};

DDLayout.AdminPageAbstract.prototype.clear_save_required = function () {
    this._save_state.clear_save_required();
};

DDLayout.AdminPageAbstract.prototype.is_save_required = function () {
    return this._save_state.is_save_required();
};


DDLayout.AdminPageAbstract.prototype.getUrlParameter = function(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

DDLayout.AdminPageAbstract.prototype.init_wpml_vars = function( layout ){
    var self = this;
    if (DDLayout.unique_id_created) {
        try{
            this.instance_layout_view.saveViaAjax({silent:true});
        } catch( e ){
            console.log( e.message );
        }
    } else {
        if (jQuery('#js-dd-layouts-lang-wrap').length && jQuery('#js-dd-layouts-lang-wrap').html().trim() == '') {
            // If there's no WPML translation info then register the strings and refresh
            self.update_wpml_state(layout.get('id'), true);
        }
    }
};

DDLayout.AdminPageAbstract.prototype.update_wpml_state = function (layout_id, register_strings) {

    if( DDLayout_settings.DDL_JS.wpml_is_active === false ){
        return;
    }

    this.wpml_handler.update_wpml_state(layout_id, register_strings);
};

DDLayout.AdminPageAbstract.prototype.is_integrated_theme = function( ){

    try{
        return DDLayout_settings.DDL_JS.is_integrated_theme;
    } catch( e ){
        console.log( e.message );
        return false;
    }
};
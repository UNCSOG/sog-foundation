Toolset.CRED.AssociationFormsEditor.ExtraEditor = function( id, slug, selector, allow_quicktags ){
    var extra_type = {'js':'javascript', 'css':'css'};
    this.id = id;
    this.slug = slug;
    this.selector = selector;
    this.allow_quicktags = allow_quicktags;
    this.mode = extra_type[slug];
    this.editor = null;
    this.has_changed = false;

    this.value = null;

    _.bindAll( this, 'refreshEditor' );
};

Toolset.CRED.AssociationFormsEditor.ExtraEditor.prototype.buildExtraEditor = function(){

    WPV_Toolset.CodeMirror_instance[this.selector] = icl_editor.codemirror(this.selector, true, this.mode);

    this.editor = WPV_Toolset.CodeMirror_instance[this.selector];

    this.editorReset();

    if(this.allow_quicktags) {
        var quicktags_slug = this.selector + '_quicktags';
        this[quicktags_slug] = quicktags( { id: this.selector, buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );
        WPV_Toolset.add_qt_editor_buttons( this[quicktags_slug], this.editor );
    }

    this.setChange();

    return this;
};

Toolset.CRED.AssociationFormsEditor.ExtraEditor.prototype.getExtraEditor = function(){
    return this.editor;
};


Toolset.CRED.AssociationFormsEditor.ExtraEditor.prototype.editorReset = function(){
    try{
        this.setEditorValue( document.getElementById( this.selector ).value );
    } catch( e ){
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
        return this;
    }
};

Toolset.CRED.AssociationFormsEditor.ExtraEditor.prototype.editorResetValue = function(){
    try{
        this.value = this.getEditorValue();
        // in case editor value has been validated, set the normalised value back to the GUI
        this.setEditorValue(this.value);
        this.has_changed = false;
    } catch( e ){
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }
};

Toolset.CRED.AssociationFormsEditor.ExtraEditor.prototype.refreshEditor = function(){
    try{
        this.getExtraEditor().refresh();
        this.getExtraEditor().focus();
    } catch( e ){
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }

};

Toolset.CRED.AssociationFormsEditor.ExtraEditor.prototype.getEditorValue = function(){

    var value = '';

    try{
        value = this.getExtraEditor().getValue();
    } catch( e ){
        console.log( e.message );
        value = '';
    }
    return this.removeScript( value );
};

Toolset.CRED.AssociationFormsEditor.ExtraEditor.prototype.removeScript = function( value ){
      if( value && _.isString( value ) && this.slug !== 'js' ){
          value = value.replace(/<script[^>]*>(?:(?!<\/script>)[^])*<\/script>/g, "");
      }

      return value;
};

Toolset.CRED.AssociationFormsEditor.ExtraEditor.prototype.setEditorValue = function( value ){
    try{
        value = this.removeScript( value );
        this.getExtraEditor().setValue( value );
        this.value = value;
        _.defer( this.refreshEditor );

    } catch( e ){
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }
};

Toolset.CRED.AssociationFormsEditor.ExtraEditor.prototype.setChange  = function(){

    var self = this;

    this.getExtraEditor().on('update', function( instance, changeObj ){
        var current_val = instance.getValue();

        if( self.value === current_val ){
            self.has_changed = false;
        } else {
            self.has_changed = true;
        }
    });
};

Toolset.CRED.AssociationFormsEditor.ExtraEditor.prototype.hasChanged = function(){
    return this.has_changed;
};

Toolset.CRED.AssociationFormsEditor.ExtraEditor.prototype.destroy = function(){
    WPV_Toolset.CodeMirror_instance[this.selector] = null;
    window.iclCodemirror[this.selector] = null;
};



Toolset.CRED.AssociationFormsEditor.EditorFactory = function(){
    var self = this,
        editors = {};

    self.setEditor = function( id, slug, selector, allow_quicktags ){
        /* Every editor is a singleton */
        if( self.editorExists( id, slug ) === false ){
            var extra = new Toolset.CRED.AssociationFormsEditor.ExtraEditor( id, slug, selector, allow_quicktags );
            editors[slug+'_'+id] = extra.buildExtraEditor();
        }
        return editors[slug+'_'+id];
    };

    self.editorExists = function( id, slug ){
        return typeof editors[slug+'_'+id] !== 'undefined' && editors[slug+'_'+id] !== null;
    };

    self.getEditor = function( id, slug ){
        return editors[slug+'_'+id];
    };

    self.getEditors = function(){
        return editors;
    };

    self.getEditorValue = function( id, slug ) {
        var value = '';
        try{
            value = self.getEditor( id, slug ).getEditorValue();
        } catch( e ){
            console.log(e.message);
            value =  '';
        }
        return value;
    };

    self.someHasChanged = function(){
        return _.some(self.getEditors(), function( editor){
            return editor.hasChanged() === true;
        });
    };

    self.resetEditors = function(){
        _.map(self.getEditors(), function( v){
            v.editorResetValue();
        });
    };

    self.destroy = function(){
        _.map(editors, function(v){
            v.destroy();
        });
        editors = {};
    };
};
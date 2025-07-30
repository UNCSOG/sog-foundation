DDLayout.views.TabsTabView = DDLayout.views.ContainerRowView.extend({
    initialize: function (options) {
        DDLayout.views.ContainerRowView.prototype.initialize.call(this, options);

        var self = this;

        _.bindAll(self, 'listen_model_change_name', 'listen_model_change_additionalCssClasses', 'listen_model_change_cssId', 'listen_model_change_tag', 'listen_model_change_disabled');

        this.model.listenTo(this.model, 'change:name', self.listen_model_change_name);
        this.model.listenTo(this.model, 'change:tag', self.listen_model_change_tag);
        this.model.listenTo(this.model, 'change:cssId', self.listen_model_change_cssId);
        this.model.listenTo(this.model, 'change:additionalCssClasses', self.listen_model_change_additionalCssClasses);
        this.model.listenTo(this.model, 'change:disabled', self.listen_model_change_disabled);
        //disabled
    },
    listen_model_change_name: function ( model, new_value, obj ) {
        var self = this,
            prev = model.previousAttributes(),
            old_value = prev.name,
            $id = self.$el.attr('id');

        if ( old_value === new_value ) {
            return;
        }

        jQuery('a[href="#' + $id + '"]').text(new_value);

        return model;
    },
    listen_model_change_disabled:function( model, new_value, obj ){
        var self = this,
            prev = model.previousAttributes(),
            old_value = prev.disabled
            $id = self.$el.attr('id');

        if ( old_value === new_value ) {
            return;
        }

        if( new_value ){
            jQuery('a[href="#' + $id + '"]').parent().removeClass('active');
            jQuery('a[href="#' + $id + '"]').parent().addClass('disabled');
        } else {
            jQuery('a[href="#' + $id + '"]').parent().addClass('active');
            jQuery('a[href="#' + $id + '"]').parent().removeClass('disabled');
        }
    },
    listen_model_change_tag: function ( model, new_value, obj ) {
        var self = this,
            prev = model.previousAttributes(),
            old_value = prev.tag,
            $row = self.$el.children('.row').first();

        if ( old_value === new_value ) {
            return;
        }

        var $newElement = jQuery('<' + new_value + '/>');

        _.each($row[0].attributes, function(attribute) {
            $newElement.attr(attribute.name, attribute.value);
        });

        $row.wrapInner($newElement).children().first().unwrap();
    },
    listen_model_change_cssId: function ( model, new_value, obj ) {
        var self = this,
            prev = model.previousAttributes(),
            $row = self.$el.children('.row').first(),
            old_value = prev.cssId;

        if ( old_value === new_value ) {
            return;
        }

        if ( old_value && !new_value ) {
            $row.removeAttr('id');
        } else {
            $row.attr('id', new_value)
        }
    },
    listen_model_change_additionalCssClasses: function ( model, new_value, obj ) {
        var self = this,
            prev = model.previousAttributes(),
            old_value = prev.additionalCssClasses,
            $row = self.$el.children('.row').first();

        if ( old_value === new_value ) {
            return;
        }

        if( !new_value &&  $row.hasClass(old_value) ){
            return;
        } else if( !new_value &&  !$row.hasClass(old_value) ){
            $row.attr( 'class', 'row' );
        } else {
            $row.addClass( new_value )
        }
    }
});

if( Backbone && Backbone.Model )
{
    Backbone.Model.prototype._super = function(funcName){
        if( funcName === undefined ) return null;
        return this.constructor.prototype[funcName].apply(this, _.rest(arguments) );
    };
    // nested models!  Might just override the internal representation of this...
    _.extend(Backbone.Model.prototype, {
        // Version of toJSON that traverses nested models
        toJSON: function() {
            if (this._isSerializing) {
                return this.id || this.cid;
            }
            this._isSerializing = true;
            var json = _.clone(this.attributes);
            _.each(json, function(value, name) {
                _.isFunction((value || "").toJSON) && (json[name] = value.toJSON());
            });
            this._isSerializing = false;
            return json;
        }
    });

    _.extend(Backbone.Collection.prototype, {
        // Version of toJSON that traverses nested models in collectionså
        toJSON: function() {
            return this.map(function(model){ return model.toJSON(); });
        }
    });
}
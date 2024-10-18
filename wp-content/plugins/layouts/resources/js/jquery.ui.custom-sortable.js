// Based on jQuery UI Sortable 1.10.4.
// Patched to be compatible with jQuery 3.5

/*!
 * jQuery UI Sortable 1.10.4
 * http://jqueryui.com
 *
 * Copyright 2013 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/sortable/
 *
 * Depends:
 *	jquery.ui.core.js
 *	jquery.ui.mouse.js
 *	jquery.ui.widget.js
 */
(function( $, undefined ) {

function isOverAxis( x, reference, size ) {
	return ( x >= reference ) && ( x < ( reference + size ) );
}

function isFloating(item) {
	return (/left|right/).test(item.css("float")) || (/inline|table-cell/).test(item.css("display"));
}

$.widget("ui.customSortable", $.ui.cellSortable, {
	version: "1.10.4",
	widgetEventPrefix: "csort",
	ready: false,
	_intersectsWithPointer: function(item) {
        var verticalDirection = this._getDragVerticalDirection(),
            horizontalDirection = this._getDragHorizontalDirection(),
			isOverElementWidth = false;

		if (horizontalDirection == 'right') {
			isOverElementWidth = isOverAxis(this.positionAbs.left + this.helperProportions.width, item.left + item.width/3, item.width);
		} else if (horizontalDirection == 'left') {
			isOverElementWidth = isOverAxis(this.positionAbs.left, item.left - item.width/2, item.width);
		}

        if (!isOverElementWidth) {
            return false;
        }

        return ( ((horizontalDirection && horizontalDirection === "right") || verticalDirection === "down") ? 2 : 1 )

    },
    _mouseCapture: function(event, overrideHandle) {
        var currentItem = null,
            validHandle = false,
            that = this;

        if (this.reverting) {
            return false;
        }

        if(this.options.disabled || this.options.type === "static") {
            return false;
        }

        //We have to refresh the items data once first
        this._refreshItems(event);

        //Find out if the clicked node (or one of its parents) is a actual item in this.items
        $(event.target).parents().each(function() {
            if($.data(this, that.widgetName + "-item") === that) {
                currentItem = $(this);
                return false;
            }
        });
        if($.data(event.target, that.widgetName + "-item") === that) {
            currentItem = $(event.target);
        }

        if(!currentItem) {
            return false;
        }

        if( currentItem && currentItem.data('view') && currentItem.data('view') instanceof DDLayout.views.ContainerView )
        {
                if( jQuery(event.target).is('.js-move-row') === false ) return;
                //if( jQuery(event.target).is('.js-row-container') === false ) return;
        }

        if(this.options.handle && !overrideHandle) {
            $(this.options.handle, currentItem).find("*").addBack().each(function() {
                if(this === event.target) {
                    validHandle = true;
                }
            });
            if(!validHandle) {
                return false;
            }
        }

        this.currentItem = currentItem;
        this._removeCurrentsFromItems();
        return true;

    }
});

}(jQuery) );

// preview-manager.js


DDLayout.PreviewManager = function($)
{
	var self = this;
	self._cell_heights = {};
	self._views = {};
	self._rerender = false;
	self._ignore_reset = false;

	var compact_button, compact_mode = false;


	self.init = function() {
		self._rerender = false;
		self._ignore_reset = false;

		compact_button = $('#compact-cell-view');
		compact_button.on('click', handle_compact_button)

		if (DDLayout_settings.DDL_JS.compact_display_mode) {
			compact_mode = true;
			compact_button.val(compact_button.data('normal'));
		}
	};

	handle_compact_button = function () {
		compact_mode = !compact_mode;

		if (compact_mode) {
			compact_button.val(compact_button.data('normal'));
		} else {
			compact_button.val(compact_button.data('compact'));
		}
		DDLayout.ddl_admin_page.render_all();

        var params = {
            compact_display_nonce : DDLayout_settings.DDL_JS.compact_display_nonce,
            action : 'ddl_compact_display_mode',
			mode : compact_mode
        };
        WPV_Toolset.Utils.do_ajax_post(params, {success:function(response){
		}});

	}

	self.reset = function () {

		if ( !self._ignore_reset ) {
			self._cell_heights = {};
			self._rerender = false;
		}

		self._ignore_reset = false;

		compact_button.hide();

	}

	self.get_preview_height = function (view) {

		var cid = view.model.cid;

		if (!self._rerender) {
			var preview_height = self._get_preview_height_of_cell(view);

			if (preview_height > 0) {
				self._cell_heights[cid] = preview_height;
				self._views[cid] = view;
			}
		}
		return self._cell_heights[cid];
	}

	self._get_preview_height_of_cell = function (view) {
		var preview_height = 0;

        if( view.model.get('cell_type') === 'cell-text' ){
            preview_height = view.$el.height();
        } else {
            var style = 'position: absolute !important; top: -1000 !important; ';
            var $target = view.$el.clone().
                attr( 'style', style ).
                appendTo( 'body' );
            var main_width = view.model.get('width') * view.model.get('row_divider') * 50; //jQuery(view.$el).width();
            $target.width(main_width);
            $target.css({display: 'block'});
            $('.cell-content', $target).children().each( function () {

                preview_height += $(this).height();

            });

            $target.remove();
        }

		if (compact_mode) {
			if (preview_height > 120) {
				compact_button.show();
				preview_height = 120;
				view.$el.addClass('cell-preview-fadeout');
			}
		} else {
			if (preview_height > 150) {
				compact_button.show();
			}
		}

		return preview_height + 4;
	}

	self.recheck_sizes = function (event) {
		if (!$.isEmptyObject(self._cell_heights)) {
			for (var key in self._views) {
				if (self._views.hasOwnProperty(key)) {
					var view = self._views[key];

					var preview_height = self._get_preview_height_of_cell(view);

					if (preview_height != self._cell_heights[key]) {

						self._cell_heights[key] = preview_height;
						self._rerender = true;
						self._ignore_reset = true;
					}

				}
			}

			if (self._rerender) {
				DDLayout.ddl_admin_page.render_all();
			}
		}
	}

	self.init();

};


DDLayout.preview_manager = new DDLayout.PreviewManager(jQuery);


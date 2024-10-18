// Row menu.js

DDLayout.RowTooltip = function()
{
	var self = this;

	self.init = function() {

		self.$menu = null;
		self.$button = null;
		self.$icon = null;
		self._row_view = null;
        self._open = false;

		jQuery(document).on('open_row_context_menu open_special_row_context_menu open_tab_context_menu open_panel_context_menu', '.js-show-add-row-menu, .js-show-add-special-row-menu, .js-show-add-tab-menu, .js-show-add-panel-menu', function(e, current_row) {

			e.stopImmediatePropagation();

			if ( e.type === 'open_row_context_menu' ) {
				self.$menu = jQuery('.js-add-row-menu');
			}
			else if ( e.type === 'open_special_row_context_menu' ) {
				self.$menu = jQuery('.js-add-special-row-menu');
			}
			else if ( e.type === 'open_tab_context_menu' ) {
				self.$menu = jQuery('.js-add-special-tab-menu');
			}
			else if ( e.type === 'open_panel_context_menu' ) {
				self.$menu = jQuery('.js-add-special-panel-menu');
			}

			self._row_view = current_row;

			self.$button = jQuery(e.target);

			if ( jQuery(e.target).is('.js-icon-caret') ) {
				self.$icon = jQuery(e.target);
			}
			else {
				self.$icon = self.$button.find('.js-icon-caret');
			}

			if ( ! self.$menu.data('is-visible') && self._row_view !== null ) {
				self.showMenu(e);
                DDLayout.ddl_admin_page.duplicator.initElementsStatus();
			}
			else {
				self.hideMenu(e);
			}

		});

		//the context menu item deafult
		jQuery( '.js-add-row' ).on('click', function(event){
			event.stopImmediatePropagation();
			
			var cellWidth = jQuery(this).data('cell-width');
			var rowType = jQuery(this).data('row-type');

			if ( rowType === 'normal-row' ) {

				if ( cellWidth ) {
					self._add_row( cellWidth );
				}
				else {
					var count = self._row_view.model.collection.length + 1;
					self._row_view.addRow( self._row_view.model.get('kind')+' '+count, '', self._row_view.model.get('layout_type') );
				}

			}
			else if ( rowType === 'theme-section-row' ) {

				DDLayout.ddl_admin_page.show_theme_section_row_dialog( 'add', self._row_view, this );
			}
            else if ( rowType === 'copy-row' || rowType === 'paste-row') {

                DDLayout.ddl_admin_page.duplicator.handleAction( self._row_view, rowType, this);
            }

			self.hideMenu(event);
		});

		jQuery(document).on('mousedown', function(e) {
			if ( self.$menu && self.$menu.data('is-visible') ) {
				if ( !jQuery(e.target).hasClass('js-add-row-item') ) {
					self.hideMenu(e);
				}
			}
		});

	};

	self._add_row = function (row_divider) {
		var count = self._row_view.model.collection.length + 1;

		var layout_type = self._row_view.model.get('layout_type');
		if (self._row_view instanceof DDLayout.views.ContainerRowView) {
			layout_type = 'fluid';
		}
		if (layout_type == 'fixed') {
			// check that the layout has 12 columns
			var layout = DDLayout.ddl_admin_page.get_layout();
			if (layout.get_width() != 12) {
				layout_type = 'fluid';
			}
		}

		self._row_view.addRow( self._row_view.model.get('kind')+' '+count, '', layout_type, row_divider );
	};

	self.showMenu = function(e) {

		var $parent = self.$menu.parent();

		if ( ! $parent.is('body') ) {
			self.$menu
				.detach()
				.appendTo('body')
				.hide()
				.fadeIn('fast');
		}

		self.$menu.css({
			top: self.$icon.offset().top + 20,
			//left: self.$icon.offset().left + 20
			left:self.$icon.offset().left - self.$menu.width() + 12
		});
		self.$menu
			.fadeIn('fast')
			.data('is-visible', true);
	};

	self.hideMenu = function(e) {

		if ( typeof(e) !== 'undefined' ) {
			self.$menu
				.hide()
				.data('is-visible', false);

		}
	};

	self.hide = function(e) {
		if ( !e || (e && !jQuery(e.target).is(self.$button)) ) {
			self.hideMenu();
		}
	};

	self.init();
};
DDLayout.HtmlAttributesHandler = function()
{
	var self = this,
		$message_container = jQuery('.js-css-editor-message-container'),
		layouts_properties = DDLayout_settings.DDL_JS.layouts_css_properties,
		layouts_classes = layouts_properties && layouts_properties.hasOwnProperty('additionalCssClasses')  ? layouts_properties.additionalCssClasses : [],
		layouts_ids = layouts_properties && layouts_properties.hasOwnProperty('cssId')  ? layouts_properties.cssId : [];

	self.cell = null;
	self.$cssClassEl = null;
	self.$cssIdEl = null;

	self.text_area_id = '',

	self.css_did_change = false;
	self._id_exists = false;

	self._id_init_val = '';

	self.events_set = false;

	self._css_edit_tab_initialized = false;

	self.is_css_enabled = DDLayout_settings.DDL_JS.is_css_enabled;

	self.init = function()
	{
		self._css_edit_tab_initialized = false;

		jQuery(document).on( 'cbox_complete', '.js-popup-tabs', function( event, args ) {
			self.$cssClassEl = args ? args.cssClassEl : self.$cssClassEl;
			self.$cssIdEl = args ? args.cssIdEl : self.$cssIdEl;
			self._id_init_val = self.$cssIdEl ? self.$cssIdEl.val() : null;
			self._target = jQuery('#ddl-theme-section-row-edit').data('row_view') || jQuery('#ddl-row-edit').data('row_view') || jQuery('#ddl-default-edit').data('cell_view')  || jQuery('#ddl-container-edit').data('container_view') || DDLayout.ddl_admin_page.get_new_target_cell();

			self._uid = self._target.model.cid;

			self._css_edit_tab_initialized = true;

		});

		jQuery(document).on( 'activate_tab', '.js-popup-tabs', function( event, args ) {

			if( args.tabIndex !== 1 ) return false;

			jQuery(document).on('color_box_closes', function(event)
			{
				clean_all_up();
			});

		});

        WPV_Toolset.Utils.eventDispatcher.listenTo(WPV_Toolset.Utils.eventDispatcher, 'color_box_closed', self.clean_up_display_options_screen );
	};

	var setUpSelectForClasses = function(){
		var classes = layouts_classes;
		self.$cssClassEl.toolset_select2({
			tags:  true,
			tokenSeparators: [",", " "],
			multiple : 'multiple',
			data:classes,
			'width': "555px"
		});
	};

	var clean_up_message = function ()
	{
		$message_container.wpvToolsetMessage('destroy');
		self.$cssIdEl.removeClass('invalid');
		jQuery('.js-edit-css-class').removeClass('invalid');

	};

	var clean_all_up = function()
	{
		clean_up_message();

		self._css_edit_tab_initialized = false;
	};

	self.setChangeEvents = function()
	{
		if( self.events_set === false )
		{
			self.$cssClassEl.on('change', function(event){
				$message_container.wpvToolsetMessage('wpvMessageRemove');

				if( self.$cssClassEl.val() ){

					jQuery('.js-edit-css-class').removeClass('invalid');
				}
			});

			self.$cssClassEl.on('toolset_select2-removed', function(event){

			});

			self.$cssIdEl.on('focus', function(event){
					jQuery(this).data('prev-value', jQuery(this).val() );
			});


			self.$cssIdEl.on('change', function(event){
				if( jQuery(this).val() != '' && self.check_id_exists( jQuery(this), jQuery(this).val() ) === false )
				{

					jQuery(this).data('prev-value', undefined );
				}
				else if( jQuery(this).val() == '' )
				{

					jQuery(this).data('prev-value', undefined );
				}
			});

			self.$cssIdEl.on('keyup', function(e) {
				if (e.key != "Enter") {
					clean_up_message();
				}
			});

			self.events_set = true;
		}

	};

	self.check_id_exists = function( $me, value )
	{
		if (!self._css_edit_tab_initialized) return true;

		if( $me === null || !$me || _.isEmpty( value ) || self._id_init_val === value ) return true;

		var find = WPV_Toolset.Utils.flatten_filter_by_key( DDLayout.ddl_admin_page.get_layout_as_JSON(), {}, false, 'cssId');

		if( value && find.indexOf( value ) !== -1 && self._id_init_val !== value )
		{
			$me.addClass('invalid');

			$message_container.wpvToolsetMessage({
				text: DDLayout_settings.DDL_JS.strings.id_duplicate,
				stay: true,
				close: true,
				type: 'info'
			});

			self._id_exists = true;
			return false;
		}
		else
		{
			self._id_exists = false;
			clean_up_message();
			return true;
		}


		return true;
	};


	self.init();
};

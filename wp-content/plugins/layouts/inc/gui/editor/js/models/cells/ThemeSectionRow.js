DDLayout.models.cells.ThemeSectionRow = DDLayout.models.abstract.Element.extend({
	defaults: {
		cssClass: '',
		kind: 'ThemeSectionRow',
		type: '',
		layout_type: ''
	},
	compound: '',
	has_cells: function () {
		return false;
	},
	//these methods to have same interface of a Row
	getWidth: function () {
		return DDLayout.ddl_admin_page.get_layout().get_width();
	},
	isEmpty: function () {
		return _.isEmpty(this.get('type'));
	},
	setLayoutType: function (type) {
		this.set('layout_type', type);
	},
	getLayoutType: function () {
		return this.get('layout_type');
	},
	get_parent_width: function (row) {
		return 0;
	},
	get_empty_space_to_right_of_cell: function (cell) {
		return -1;
	},

	find_cell_of_type: function (cell_type) {
		return false;
	},

	changeWidth: function (new_width) {
		return;
	},
	getMinWidth: function () {
		return 1;
	}
});
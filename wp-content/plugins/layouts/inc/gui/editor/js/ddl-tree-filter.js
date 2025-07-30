// ddl-tree-filter.js

DDLayout.treeFilter = function()
{
	var self = this;

	self.init = function () {

		jQuery( function () {

			self._manageLayoutTrees();
			self._displayTreeElementsInfo();
			self._displayTreeElementsPreview();

			var tree_elements = ['.js-breadcrumbs-tree-search', '.js-cells-tree-search'];

			for (var i = 0; i < tree_elements.length; i++ ) {
				self._manageTreeFilter( jQuery(tree_elements[i]) );
			}
		});
	};

	self._treesActions = {
		expandTree : function( $this ) {
			var textExpanded = $this.data('text-expanded');
			var textCollapsed = $this.data('text-collapsed');
			var $list = $this
				.closest('.js-tree-category')
				.find('.js-tree-category-items');

			$list.slideDown('fast');
			$this
				.removeClass('fa-plus-square-o')
				.addClass('fa-minus-square-o')
				.data('expanded', true)
				.prop('title', textExpanded);
		},
		collapseTree : function( $this ) {
			var textExpanded = $this.data('text-expanded');
			var textCollapsed = $this.data('text-collapsed');
			var $list = $this
				.closest('.js-tree-category')
				.find('.js-tree-category-items');

			$list.slideUp('fast');
			$this
				.removeClass('fa-minus-square-o')
				.addClass('fa-plus-square-o')
				.data('expanded', false)
				.prop('title', textCollapsed);
		}
	};

	self._manageLayoutTrees = function () {
		// Cells category tree

		jQuery('.js-tree-toggle').on('click', function(e) {

			e.preventDefault();
			var $this = jQuery(this);

			if ( $this.data('expanded') ) {
				self._treesActions.collapseTree( $this );
			}
			else {
				self._treesActions.expandTree( $this );
			}

			return false;

		});

	};

	self._displayTreeElementsInfo = function () {

		jQuery('.js-show-item-desc').on('click', function(e) {

			if( typeof(DDLayout_settings) !== 'undefined' && DDLayout_settings && DDLayout_settings.DDL_JS.show_cell_details === 'no' ){
				jQuery( '.js-item-details-data', jQuery( '.class-'+jQuery( this ).data( 'cellType' ) ) ).trigger( 'click' );
				return;
			}

            self._doNotShowItemDesc();

			jQuery(this).toggleClass('active');
			var target = jQuery(this).data('target');
			var $desc = jQuery('.js-item-desc').filter(function() {
				return jQuery(this).data('name') === target;
			});

			$desc.toggle();
		});
	};

    self._doNotShowItemDesc = function () {
        jQuery( '.ddl-js-do-not-show-cell-details' ).change( function(){
            var option_status = ( jQuery( this ).is( ':checked' )) ? 'no' : 'yes';
			DDLayout.ddl_admin_page.trigger( 'layout_show_cell_details', option_status );
        })
    };

	self._displayTreeElementsPreview = function () {

		var $preview;

		jQuery('.js-show-item-preview').hoverIntent(
			function(event) { // mouseover

				var $this = jQuery(this);
				var target = $this.data('target');
				var $popupWindowContent = jQuery('.js-ddl-dialog-element-select');

				$preview = jQuery('.js-item-preview').filter(function() {
					return jQuery(this).data('name') === target;
				});

			//	console.log( 'offset top ', arguments, event.pageY, target, jQuery('[data-target="'+target+'"]').offset().top, $popupWindowContent.offset().top  )

				$preview.css({
					'top':110,
					'left': $popupWindowContent.offset().left + $popupWindowContent.width() - $preview.outerWidth() - 30
				});
				$preview.fadeIn('fast');

				$this.one('click', function(e) { // FF fix
					$this.trigger('mouseenter');
					$this.trigger('mouseleave');
				});

			},
			function() { // mouseleave
				if ( typeof($preview) !== 'undefined' ) {
					$preview.hide();
				}
			}
		);

	};

	self._manageTreeFilter = function( $searchEl ) {

		var $ = jQuery,
			$searchInput = $searchEl,
			$searchInputDefaultVal = $searchInput.data('default-val'),
			$listRoot = $( $searchInput.data('target') ),
			$listCategoriesTitles = $listRoot.find('.js-tree-category-title'),
			$listItemsWrap = $listRoot.find('.js-tree-category-items'),
			$listItems = $listRoot.find('.js-tree-category-item'),
			$listItemsNames = $listRoot.find('.js-item-name'),
			$messageContainer = $( $searchInput.data('message-container') ); // massage-container string have to be valid jQuery selector


		// This event is called on cbox_complete in CellView.js
		$(document).on('focus_search_input', function() {
			$searchInput
				.focus()
				.val( $searchInputDefaultVal ); // set focus on input
		});

        var is_searching = function(){
            return $searchInput.val() !== $searchInputDefaultVal && $searchInput.val() !== '';
        };

		// Expand tree on focus
		$searchInput.on('focus', function() {
			self._treesActions.expandTree( $('.js-tree-toggle') );
			$searchInput.val( $searchInputDefaultVal );
		});

		// Clear the input on focus
		$searchInput.on('focus keydown', function() {

			var val = $(this).val();
			if ( val === $searchInputDefaultVal ) {
				$(this).val('');
			}

		});

		// Restore "search" text on blur the input is empty
		$searchInput.on('blur', function() {

			var val = $(this).val();
			if ( val === '' ) {
				$(this).val( $searchInputDefaultVal );
			}

		});

		// Search on keyup
		$searchInput.on('keyup focus', function() {

			var val = $(this).val().toLowerCase();
			var $searchResults = $listItemsNames.filter(function() { // return matching elements
				var desc = '',
					title = $(this).text().toLowerCase();
				if ( jQuery( this ).closest('.js-show-item-desc').length > 0 ) {
					desc = jQuery( this ).closest('.js-show-item-desc').data( 'cellDescription').toLowerCase();
				}
				return title.indexOf( val ) >= 0 || desc.indexOf( val ) >= 0;
			});

			// Show all LI elements, and reset all the attributes
			$listItems
				.show()
				.data('result', false)
				.data('contains-results', false)
				.removeClass('contains-search-results')
				.removeClass('last');

			$listCategoriesTitles.show(); // Show all categories titles
            $listCategoriesTitles.closest('.js-tree-category').show();

			$messageContainer.empty();

			$searchResults // Mark results
				.closest('.js-tree-category-item')
				.data('result', true);

			$listItemsWrap.each(function() { // Loop through all UL elements

				var $allElements = $(this).children('.js-tree-category-item'); // All LI elements

				var resultsCount = $allElements.filter(function() {
					return $(this).data('result');
				}).length;

				if ( resultsCount > 0 ) { // If there's at least one result
					$(this)
						.parents('.js-tree-category-item')
						.data('contains-results', true);
				}

			});

            $listItems.hide();

			$listItems.each(function() { // Loop through all LI items

				if ( $(this).data('result') ) { // Show results
					$(this).show();
                    if( is_searching() ){
                        DDLayout.create_cell_helper.close_preview();
                    }
				}

				else if ( $(this).data('contains-results') ) {  // Show elements containing search results
					$(this).show();
					$(this).addClass('contains-search-results'); // but make them look different
				}

			});

			$listItemsWrap.each(function(e, index) { // Loop through all UL elements once again

				var $allElements = $(this).find('.js-tree-category-item');

				if ( $allElements.filter(':visible').length === 0 ) { // If there's no visible item in the category

					    var $title = $(this).prev('.js-tree-category-title');
                        $title.hide(); // hide the category title

                        $(this).closest('.js-tree-category').hide();
				}
				else {

					$(this)
						.find('.js-tree-category-item')
						.not(':hidden')
						.last()
						.addClass('last'); // add 'last' class for the last visible item

				}

			});

			var $visibleItems = $listItems.filter(':visible');

			if ( $visibleItems.length === 0 ) { // Display "no results" message
				$messageContainer.wpvToolsetMessage({
					text: $messageContainer.data('message-text'),
					type:'info',
					stay: true,
					close: false,
					fadeIn: 0
				});
			}

		});

	};

	self.init();
};


/**
* Views Post Relationship Filter GUI - script
*
* Adds basic interaction for the Post Relationship Filter
*
* @package Views
*
* @since 1.7.0
*
* @todo 100% of the wpv_filter_relationship_action AJAX callback can be processed dynamically with self.cache.relationships
*/


var WPViews = WPViews || {};

/*
We're adding two additional parameters for case when this function is called inside the Gutenberg View Editor
id - is View ID since it couldn't be extracted from the DOM
selected_post_types - A list of post types selected in Content selection screen (they also couldn't be extracted from the DOM)
 */
WPViews.PostRelationshipFilterGUI = function( $, id, selected_post_types ) {

	var self = this;

	self.i18n = wpv_post_relationship_filter_i18n;

	self.cache = {
		relationships: {},
		labels: self.i18n.data.post_types_info
	};

	//Save post types
	self.selected_post_types = selected_post_types;

	self.view_id = id || $( '.js-post_ID' ).val();
	// if ( self.view_id === undefined ) {
	// 	// this means we're inside the Gutenberg View editor, but doing initialization too early
	// 	return;
	// }

	self.spinner = '<span class="wpv-spinner ajax-loader"></span>&nbsp;&nbsp;';

	self.messages = {
		postMissing: '<p class="js-wpv-filter-post-relationship-missing js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-info-circle" aria-hidden="true"></i> ' + self.i18n.messages.post_missing + '</p>',
		postNotRelated: '<p class="js-wpv-filter-post-relationship-unrelated js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-info-circle" aria-hidden="true"></i> ' + self.i18n.messages.post_not_related + '</p>',
		postNotRelatedLegacy: '<p class="js-wpv-filter-post-relationship-unrelated js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-info-circle" aria-hidden="true"></i> ' + self.i18n.messages.post_not_related_legacy + '</p>',
		noFurtherAncestors: '<p class="js-wpv-filter-post-relationship-no-further-ancestors toolset-alert toolset-alert-error" style="margin:10px 0"><i class="fa fa-info-circle" aria-hidden="true"></i> ' + self.i18n.messages.no_further_ancestors + '</p>'
	};

	self.post_row = '.js-wpv-filter-row-post-relationship';
	self.post_options_container_selector = '.js-wpv-filter-post-relationship-options';
	self.post_summary_container_selector = '.js-wpv-filter-post-relationship-summary';
	self.post_messages_container_selector = '.js-wpv-filter-row-post-relationship .js-wpv-filter-toolset-messages';
	self.post_edit_open_selector = '.js-wpv-filter-post-relationship-edit-open';
	self.post_close_save_selector = '.js-wpv-filter-post-relationship-edit-ok';

	self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();

	//--------------------
	// Functions for post relationship
	//--------------------

	//--------------------
	// Events for post relationship
	//--------------------

	// Open the edit box and rebuild the current values; show the close/save button-primary
	// TODO maybe the show() could go to the general file

	$( document ).on( 'click', self.post_edit_open_selector, function() {
		self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
		$( self.post_close_save_selector ).show();
		$( self.post_row ).addClass( 'wpv-filter-row-current' );
	});

	// Track changes in options

	$( document ).on( 'change keyup input cut paste', self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select', function() {
		$( this ).removeClass( 'filter-input-error' );
		$( self.post_close_save_selector ).prop( 'disabled', false );
		WPViews.query_filters.clear_validate_messages( self.post_row );
		if ( self.post_current_options != $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_relationship', action: 'add' } );
			$( self.post_close_save_selector )
				.addClass('button-primary js-wpv-section-unsaved')
				.removeClass('button-secondary')
				.html(
					WPViews.query_filters.icon_save + $( self.post_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_relationship', action: 'remove' } );
			$( self.post_close_save_selector )
				.addClass('button-secondary')
				.removeClass('button-primary js-wpv-section-unsaved')
				.html(
					WPViews.query_filters.icon_edit + $( self.post_close_save_selector ).data('close')
				);
			$( self.post_close_save_selector )
				.parent()
					.find( '.unsaved' )
					.remove();
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	});

	// Save filter options

	self.save_filter_post_relationship = function( event, propagate ) {
		var thiz = $( self.post_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.post_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_relationship', action: 'remove' } );

		if ( self.post_current_options == $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.post_row );
			thiz.hide();
		} else {
			var valid = WPViews.query_filters.validate_filter_options( '.js-filter-post-relationship' );
			if ( valid ) {
				var action = thiz.data( 'saveaction' ),
				nonce = thiz.data('nonce'),
				$spinnerContainer = $( self.spinner ).insertBefore( thiz ).show(),
				error_container = thiz
					.closest( '.js-filter-row' )
						.find( '.js-wpv-filter-toolset-messages' );
				self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
				var data = {
					action:			action,
					id:				self.view_id,
					filter_options:	self.post_current_options,
					wpnonce:		nonce
				};
				$.ajax( {
					type:		"POST",
					url:		ajaxurl,
					dataType:	"json",
					data:		data,
					success:	function( response ) {
						if ( response.success ) {
							$( document ).trigger( 'js_event_wpv_query_filter_saved', [ 'post_relationship' ] );
							$( self.post_close_save_selector )
								.addClass('button-secondary')
								.removeClass('button-primary js-wpv-section-unsaved')
								.html(
									WPViews.query_filters.icon_edit + $( self.post_close_save_selector ).data( 'close' )
								);
							$( self.post_summary_container_selector ).html( response.data.summary );
							WPViews.query_filters.close_and_glow_filter_row( self.post_row, 'wpv-filter-saved' );
							Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-parametric-search-hints', response.data.parametric );
							$( document ).trigger( event );
							if ( propagate ) {
								$( document ).trigger( 'js_wpv_save_section_queue' );
							} else {
								$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
							}
						} else {
							Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: error_container} );
							if ( propagate ) {
								$( document ).trigger( 'js_wpv_save_section_queue' );
							}
						}
					},
					error:		function( ajaxContext ) {
						console.log( "Error: ", textStatus, errorThrown );
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_relationship' );
						if ( propagate ) {
							$( document ).trigger( 'js_wpv_save_section_queue' );
						}
					},
					complete:	function() {
						$spinnerContainer.remove();
						thiz
							.prop( 'disabled', false )
							.hide();
					}
				});
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_relationship' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}
	};

	$( document ).on( 'click', self.post_close_save_selector, function() {
		self.save_filter_post_relationship( 'js_event_wpv_save_filter_post_relationship_completed', false );
	});

	// Remove filter from the save queue an clean cache

	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( 'post_relationship' == filter_type ) {
			self.post_current_options = '';
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_relationship', action: 'remove' } );
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-get-parametric-search-hints' );
		}
	});

	//--------------------
	// Manage post relationship filter mode for specific post
	//--------------------

	/**
	 * Update the relationship selector when the queried post types change, if m2m is enabled
	 *
	 * Tries to keep the previous value, if any.
	 * Triggers a change event, so a chain reaction starts,
	 * and the role, the post types, and specific post ID selectors get properly populated.
	 *
	 * @since m2m
	 * @todo this can also be managed with the cache
	 */
	$( document ).on( 'change', '.js-wpv-query-post-type, .js-wpv-query-post-type input, .js-wpv-settings-archive-loop input', function() {
		if ( ! self.i18n.is_enabled_m2m ) {
			return;
		}
		var $dataholder = $( '.js-wpv-query-type-update' ),
			$messagesContainer = $dataholder.parents( '.js-wpv-update-action-wrap' ).find( '.js-wpv-message-container' ),
			unsavedMessage = $dataholder.data( 'unsaved' ),
			nonce = $dataholder.data( 'nonce' ),
			wpvQueryPostItems = self.getPostTypesSelected(),
			queryType = Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-type', 'posts' );

		var data = {
			action: self.i18n.ajaxaction.filter_relationship_action.action,
			wpv_action: 'update_post_type_list',
			id: self.view_id,
			query_type: queryType,
			post_type_slugs: wpvQueryPostItems,
			wpnonce: self.i18n.ajaxaction.filter_relationship_action.nonce
		};

		$.ajax({
			type: "POST",
			dataType: "json",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				var $new_select = $( response.data.relationship_combo );
				if ( $new_select.find( 'option' ).length > 0 ) {
					var $select = $( 'select[name=post_relationship_slug]' ),
						oldValue = $select.val();
					$select.html( $new_select.html() );
					if ( $select.find( 'option' ).length === 0 ) {
						$select.prop( 'disabled', true );
					} else {
						$select.prop( 'disabled', false );
						if (
							'' != oldValue
							&& '-1' != oldValue
							&& $select.find( 'option[value=' + oldValue + ']' ).length > 0
						) {
							$select.val( oldValue ).trigger( 'change' );
						} else {
							$select.trigger( 'change' );
						}
					}
				}
			},
			error: function (ajaxContext) {
				$messagesContainer
					.wpvToolsetMessage({
						text:unsavedMessage,
						type:'error',
						inline:true,
						stay:true
					});
				console.log( "Error: ", ajaxContext.responseText );
			},
			complete: function() {
				// Do nothing for now
			}
		});
	});

	/**
	 * Get the selected post types for this View or WPA.
	 *
	 * @since m2m
	 * @todo Transform this into a common hook callback in the main filters script so it is available for all View filters..
	 */
	self.getPostTypesSelected = function() {
		var queryMode = Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-mode', 'normal' ),
			postTypesSelected = [];

		switch( queryMode ) {
			case 'normal':
				var $postTypesContainer = $( '.js-wpv-query-post-type:checked, .js-wpv-query-post-type input:checked' );
				$postTypesContainer.each(function() {
					postTypesSelected.push(jQuery(this).val());
				});
				break;
			case 'archive':
				postTypesSelected = [];
				$( '.js-wpv-settings-archive-loop input:checked' ).map( function() {
					var $currentInput = $( this );
					switch ( $currentInput.data( 'type' ) ) {
						case 'native':
							break;
						case 'taxonomy':
							var taxonomyPostTypes = $( '.js-wpv-apply-post-types-to-archive-loop-tracker' )
								.filter( '[data-type="taxonomy"][data-name="' + $currentInput.data( 'name' ) + '"]' )
								.data( 'selected' );
							postTypesSelected = _.union( postTypesSelected, taxonomyPostTypes );
							break;
						case 'post_type' :
							postTypesSelected.push( $currentInput.data( 'name' ) );
							break;
					}
				});
				postTypesSelected = _.compact( postTypesSelected );
				break;
		}

		return Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-post-types-selected', postTypesSelected );
	};

	/**
	 * Adjust the role selector according to the selected post types and selected relationship, if m2m is enabled
	 *
	 * If the relationship is not set, hide the role selector as we will query by any role side.
	 * If the relationship is set, update the role selector with the right post type labels.
	 * If a role does not match any selected post type, disable it.
	 *
	 * Note that we might list posts on both sides of a relationship,
	 * so both roles might need to be enabled.
	 *
	 * @since m2m
	 */
	self.initRoleSelectorLabels = function() {
		if ( ! self.i18n.is_enabled_m2m ) {
			return self;
		}

		if ( $( 'select[name=post_relationship_slug]' ).length == 0 ) {
			return self;
		}

		var $relationshipSelect = $( 'select[name=post_relationship_slug]' ),
			$roleSelect = $( 'select[name=post_relationship_role]' ),
			$relationshipOptionSelected = $relationshipSelect.find( 'option:selected' )
			relationship = $relationshipSelect.val();

		switch( relationship ) {
			case '':
			case '-1':
				$roleSelect
					.hide()
					.find( 'option' )
						.remove();
				// Fake a child option to match the PHP implementation
				$roleSelect.append( $( '<option>', {
					value: 'child',
					text: 'CHILD'
				}));
				$( '.js-wpv-post-relationship-role-any' ).show();
				break;
			default:
				$( '.js-wpv-post-relationship-role-any' ).hide();
				var relationshipRoles = {
					parent: $relationshipOptionSelected.data( 'relationship-parent' ),
					child: $relationshipOptionSelected.data( 'relationship-child' ),
					intermediary: $relationshipOptionSelected.data( 'relationship-intermediary' )
				},
				postTypesSelected = self.getPostTypesSelected();

				$roleSelect
					.find( 'option' )
						.remove();

				if ( _.intersection( postTypesSelected, relationshipRoles.parent ).length > 0 ) {
					$roleSelect.append( $( '<option>', {
						value: 'parent',
						text: self.getRolesSelectorLabel( relationshipRoles.parent )
					}));
				}

				if ( _.intersection( postTypesSelected, relationshipRoles.child ).length > 0 ) {
					$roleSelect.append( $( '<option>', {
						value: 'child',
						text: self.getRolesSelectorLabel( relationshipRoles.child )
					}));
				}

				if ( _.intersection( postTypesSelected, relationshipRoles.intermediary ).length > 0 ) {
					$roleSelect.append( $( '<option>', {
						value: 'intermediary',
						text: self.getRolesSelectorLabel( relationshipRoles.intermediary )
					}));
				}

				if ( $roleSelect.find( 'option' ).length > 1 ) {
					$roleSelect.show();
					$( '.js-wpv-post-relationship-role-any' ).hide();
				} else {
					$roleSelect.hide();
					$( '.js-wpv-post-relationship-role-any' ).show();
				}
				break;
		}


		return self;
	};

	self.getRolesSelectorLabel = function( postTypes ) {
		var labels = [];
		_.each( postTypes, function( type, typeIndex, typeList ) {
			labels.push( self.cache.labels[ type ].labelSingular );
		});

		return labels.join( '/' );
	};

	/**
	 * Update the role selector when changing the relationship value, if m2m is enabled
	 * Trigger a change event so the post type and post ID selectors get updated properly.
	 *
	 * @since m2m
	 */
	$( document ).on( 'change', 'select[name=post_relationship_slug]', function() {
		if ( ! self.i18n.is_enabled_m2m ) {
			return;
		}

		self.initRoleSelectorLabels();

		$( 'select[name=post_relationship_role]' ).trigger( 'change' );
	});

	/**
	 * Update the specific post ID selector when the role selector changes, if m2m is enabled
	 *
	 * Note that when there is onlt one valid post type to select, we pre-select it and trigger a chnage event,
	 * so the post ID selector gets properly updated
	 *
	 * @since m2m
	 */
	$( document ).on( 'change', 'select[name=post_relationship_role]', function() {
		if ( ! self.i18n.is_enabled_m2m ) {
			return;
		}

		var $dataholder = $( '.js-wpv-query-type-update' ),
			relationshipSlug = $( 'select[name=post_relationship_slug]' ).val(),
			relationshipRole = $( 'select[name=post_relationship_role]' ).val(),
			rolesToOffer = [],
			requestedRole = ( 'child' == relationshipRole ) ? 'parent' : 'child',
			postTypesSelected = self.getPostTypesSelected(),
			nonce = $dataholder.data( 'nonce' ),
			$postTypeContainer = $( '#wpv_post_relationship_post_type' ),
			oldPostType = $postTypeContainer.val();

		if (
			null == relationshipRole
			|| undefined == relationshipRole
			|| '' == relationshipRole
		) {
			if ( $( 'select[name=post_relationship_id]' ).length > 0 ) {
				$( 'select[name=post_relationship_id]' ).prop( 'disabled', true );
			}

			$postTypeContainer.find( 'option' ).remove();
			$postTypeContainer.append( $( '<option>', {
				value: '',
				text: self.i18n.messages.no_post_type_found
			}));
			$postTypeContainer
				.prop( 'disabled', false )
				.trigger( 'change' );

			self.managePostRelationshipFilterWarning();

			return;
		}

		if ( '' == relationshipSlug ) {
			rolesToOffer.push( 'parent' );
			rolesToOffer.push( 'child' );
			rolesToOffer.push( 'intermediary' );
		} else {
			switch( relationshipRole ) {
				case 'parent':
					rolesToOffer.push( 'child' );
					rolesToOffer.push( 'intermediary' );
					break;
				case 'child':
					rolesToOffer.push( 'parent' );
					rolesToOffer.push( 'intermediary' );
					break;
				case 'intermediary':
					rolesToOffer.push( 'parent' );
					rolesToOffer.push( 'child' );
					break;
			}
		}

		$postTypeContainer.prop( 'disabled', true );
		if ( $( 'select[name=post_relationship_id]' ).length > 0 ) {
			$( 'select[name=post_relationship_id]' ).prop( 'disabled', true );
		}

		$postTypeContainer.find( 'option' ).remove();

		self.maybeRequestRelationshipsData();

		if ( '' == relationshipSlug ) {
			var availableRelationships = [];
			$( 'select[name=post_relationship_slug] option' ).each( function() {
				availableRelationships.push( $( this ).attr( 'value' ) );
			});
			if ( _.isEmpty( availableRelationships) ) {
				$postTypeContainer.append( $( '<option>', {
					value: '',
					text: self.i18n.messages.no_post_type_found
				}));
			} else {
				$postTypeContainer.append( $( '<option>', {
					value: '',
					text: self.i18n.messages.select_a_post_type
				}));
				var availablePostTypes = [];
				_.each( availableRelationships, function( relationshipToOffer, relationsiopIndex, relationshipList ) {
					if (
						_.has( self.cache.relationships, relationshipToOffer )
						&& 'post_reference_field' != self.cache.relationships[ relationshipToOffer ].origin
					) {
						_.each( rolesToOffer, function( roleToOffer, roleToOfferIndex, roleToOfferList ) {
							_.each( self.cache.relationships[ relationshipToOffer ]['roles'][ roleToOffer ], function( postType, postTypeIndex, postTypeList ) {
								if ( _.indexOf( availablePostTypes, postType ) < 0 ) {
									$postTypeContainer.append( $( '<option>', {
										value: postType,
										text: _.has( self.cache.labels, postType ) ? self.cache.labels[ postType ].labelSingular : postType
									}));
									availablePostTypes.push( postType );
								}
							});
						});
					}
				});
			}
		} else if ( ! _.has( self.cache.relationships, relationshipSlug ) ) {
			$postTypeContainer.append( $( '<option>', {
				value: '',
				text: self.i18n.messages.no_post_type_found
			}));
		} else {
			$postTypeContainer.append( $( '<option>', {
				value: '',
				text: self.i18n.messages.select_a_post_type
			}));
			_.each( rolesToOffer, function( roleToOffer, roleToOfferIndex, roleToOfferList ) {
				_.each( self.cache.relationships[ relationshipSlug ]['roles'][ roleToOffer ], function( postType, postTypeIndex, postTypeList ) {
					$postTypeContainer.append( $( '<option>', {
						value: postType,
						text: _.has( self.cache.labels, postType ) ? self.cache.labels[ postType ].labelSingular : postType
					}));
				});
			});
		}

		$postTypeContainer.prop( 'disabled', false );

		if (
			'' != oldPostType
			&& $postTypeContainer.find( 'option[value=' + oldPostType + ']' ).length > 0
		) {
			if ( $( 'select[name=post_relationship_id]' ).length > 0 ) {
				$( 'select[name=post_relationship_id]' ).prop( 'disabled', false );
			}
			$postTypeContainer
				.val( oldPostType )
				.trigger( 'change' );
		} else if ( $postTypeContainer.find( 'option' ).filter( function() { return '' != this.value }).length == 1  ) {
			$postTypeContainer.find( 'option' ).filter( function() { return '' != this.value }).prop( 'selected', true );
			$postTypeContainer.trigger( 'change' );
		} else {
			$postTypeContainer.trigger( 'change' );
		}

		// Refresh alert summary.
		self.managePostRelationshipFilterWarning();
	});

	/**
	 * Turn the post ID selector into a toolset_select2 instance, retrieving the posts via AJAX.
	 *
	 * @since m2m
	 */
	self.initPostIdSelector = function() {
		if ( $( 'select[name=post_relationship_id]' ).length == 0 ) {
			return self;
		}
		var $postRelationshipId = $( 'select[name=post_relationship_id]' );

		$postRelationshipIdSelect2 = $postRelationshipId
			.addClass( 'js-wpv-toolset_select2-inited' )
			.css(
				{ 'min-width': '100px' }
			)
			.toolset_select2({
				allowClear: true,
				width: 'resolve',
				dropdownAutoWidth: true,
				maximumSelectionSize: 1,
				ajax: {
					url: ajaxurl,
					dataType: 'json',
					method: 'post',
					delay: 250,
					data: function( params ) {
						return {
							action: self.i18n.ajaxaction.select2_suggest_posts_by_title.action,
							s: params.term,
							page: params.page,
							postType: $( '#wpv_post_relationship_post_type' ).val(),
							loadRecent: true,
							wpnonce: self.i18n.ajaxaction.select2_suggest_posts_by_title.nonce
						};
					},
					processResults: function( originalResponse, params ) {
						var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
						params.page = params.page || 1;
						if ( response.success ) {
							return {
								results: response.data,
							};
						}
						return {
							results: [],
						};
					},
					cache: false
				}
			});

		var $postRelationshipIdSelect2Instance = $postRelationshipIdSelect2.data( 'toolset_select2' );
		$postRelationshipIdSelect2Instance
			.$dropdown
			.addClass( 'toolset_select2-dropdown-in-setting' );

		$postRelationshipIdSelect2Instance
			.$container
			.addClass( 'toolset_select2-container-in-setting' );

		return self;
	};

	/**
	 * Update posts selector when changing the specific post type option
	 * Cache options to prevent multiple AJAX calls for the same post type
	 *
	 * @since unknown
	 * @since m2m Restore known post ID values when they match in the new set, and turn it into a toolset_select2 instance.
	 */
	$( document ).on( 'change', '.js-post-relationship-post-type', function() {
		var post_type = $( '.js-post-relationship-post-type' ).val();

		// Destroy the toolset_select2 instance, if any, and remove the post ID selector.
		if ( $( 'select[name=post_relationship_id]' ).length > 0 ) {
			$( 'select.js-wpv-toolset_select2-inited[name=post_relationship_id]' ).toolset_select2( 'destroy' );
			$( 'select[name=post_relationship_id]' ).remove();
		}

		if (
			'' == post_type
			|| -1 == post_type
		) {
			return;
		}

		$( '.js-post-relationship-post-type' ).after(
			$( '<select id="post_relationship_id" name="post_relationship_id" data-placeholder="' + self.i18n.messages.select_one + '"></select>' )
		);

		self.initPostIdSelector();
	});

	//--------------------
	// Manage post relationship filter restrictions
	//--------------------

	/**
	 * Manage warning messages when the loop-included post types are not in any relationship.
	 *
	 * @since 2.1
	 */

	self.managePostRelationshipFilterWarning = function() {
		var query_mode					= Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-mode', 'normal' ),
		orphan_post_types_selected		= [],
		orphan_loops_selected			= [],
		post_relationship_filter_row	= $( self.post_row ),
		auxiliar_string					= '',
		auxiliar_warning				= '';

		self.maybeRequestRelationshipsData();

		if ( ! post_relationship_filter_row.length ) {
			return self;
		}

		if ( 'normal' == query_mode ) {
			$( '.js-wpv-filter-post-relationship-missing, .js-wpv-filter-post-relationship-unrelated' ).remove();
			if ( $('.js-wpv-query-post-type:checked, .js-wpv-query-post-type input:checked').length || self.selected_post_types ) {
				// Universal function (works in both Legacy and Gutenberg editors)
				// to check if we should show the warning message to user
				var addToWarning = function ( post_type ) {
					var result = true;
					_.each( self.cache.relationships, function( relationship, relationshipSlug ) {
						if (
							_.contains( relationship.roles.parent, post_type )
							|| _.contains( relationship.roles.child, post_type )
							|| _.contains( relationship.roles.intermediary, post_type )
						) {
							result = false;
						}
					});
					return result;
				};

				if (self.selected_post_types) { // if we're inside the Gutenberg
					orphan_post_types_selected = self.selected_post_types.reduce(
						( orphanPostTypes, postType ) => {
							if ( addToWarning( postType.value ) ) {
								orphanPostTypes.push( postType.value );
							}
							return orphanPostTypes;
						},
						[]
					);
				} else { // we're inside the Legacy editor - let's check the DOM tree
					orphan_post_types_selected = $('.js-wpv-query-post-type:checked, .js-wpv-query-post-type input:checked').map( function() {
						var $postTypeInput = $( this );
						if (addToWarning($postTypeInput.val())) {
							return $postTypeInput
								.parent( 'li' )
									.find( 'label' )
										.html();
						}
					}).get();
				}
				if ( orphan_post_types_selected.length > 0 ) {
					auxiliar_string = orphan_post_types_selected.join( ', ' );
					auxiliar_warning = self.i18n.is_enabled_m2m
						? self.messages.postNotRelated
						: self.messages.postNotRelatedLegacy;
					auxiliar_warning = auxiliar_warning.replace( '%s', auxiliar_string );
					$( auxiliar_warning ).prependTo( self.post_row ).show();
				}
			} else {
				$( self.messages.postMissing ).prependTo( self.post_row ).show();
			}
		}

		return self;
	};

	// Content selection section saved event

	$( document ).on( 'js_event_wpv_query_type_options_saved', '.js-wpv-query-type-update', function( event, queryType ) {
		self.managePostRelationshipFilterWarning();
	});

	// Filter creation event

	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if ( filter_type == 'post_relationship' ) {
			self.managePostRelationshipFilterWarning();
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_relationship', action: 'add' } );
		}
		if ( filter_type == 'parametric-all' ) {
			self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
		}
	});

	$( document ).on( 'js_event_wpv_save_section_loop_selection_completed', function( event ) {
		self.managePostRelationshipFilterWarning();
	});

	self.getFilterData = function( filterData ) {
		var $filterContainer = $( '#wpv-filter-post-relationship' );

		filterData = _.extend( filterData, {
			relationship: $( 'select[name=post_relationship_slug]', $filterContainer ).val(),
			role: $( 'select[name=post_relationship_role]', $filterContainer ).val(),
			mode: $( '.js-post-relationship-mode:checked', $filterContainer ).val()
		});

		return filterData;
	};

	//--------------------
	// Frontend search filters
	//--------------------

	self.adjustControlPostRelationshipDialog = function( data ) {
		self.adjustControlPostRelationshipAncestorsSelector( data )
			.adjustControlPostRelationshipGuiPerType();
	};

	self.getRelationshipsData = function() {
		var data = {
			action: self.i18n.ajaxaction.get_relationships_data.action,
			wpnonce: self.i18n.ajaxaction.get_relationships_data.nonce
		};

		return $.ajax({
			type:		"GET",
			url:		ajaxurl,
			dataType:	"json",
			async:      false,
			data:		data
		});
	};

	// TODO this needs to be abstracted to the main editor script,
	// as filtering and sorting by related posts data needs this
	self.maybeRequestRelationshipsData = function() {
		if ( ! self.relationshipsDataCached ) {
			self.getRelationshipsData()
				.done( function( response ) {
					if ( response.success ) {
						self.cache.relationships = response.data.relationships;
						self.relationshipsDataCached = true;
					}
				});
		}
	};

	self.getAncestorsTreeData = function( ancestors ) {
		var relationshipPieces = ancestors.split( '>' ),
			relationshipTreeData = [];

		_.each( relationshipPieces, function( piece, index, list ) {
			var pieceData = piece.split( '@' );
			if ( _.size( pieceData ) == 2 ) {
				var type = pieceData[0],
					pieceRelationship = pieceData[1].split( '.' );

				relationshipTreeData.push( {
					type: type,
					relationship: pieceRelationship[0],
					role: pieceRelationship[1]
				} );
			} else {
				relationshipTreeData.push( {
					type: piece,
					relationship: '',
					role: 'parent'
				} );
			}
		});

		return relationshipTreeData;
	};

	self.adjustControlPostRelationshipAncestorsSelector = function( data ) {

		var $ancestorInput = $( '#wpv-control-post-relationship-ancestors' ),
			$spinnerContainer = $( self.spinner ).insertBefore( $ancestorInput ).show(),
			ancestorRoot = self.getPostTypesSelected(),
			usedPostTypes = $.merge( [], ancestorRoot );

		$ancestorInput
			.hide()
			.closest( '.js-wpv-shortcode-gui-attribute-wrapper-for-ancestors' )
				.addClass( 'wpv-editable-list' );

		if (
			_.has( data, 'overrides' )
			&& _.has( data.overrides, 'attributes' )
			&& _.has( data.overrides.attributes, 'ancestors' )
		) {
			var selectedAncestorsTree = data.overrides.attributes.ancestors,
				relationshipTreeData = self.getAncestorsTreeData( selectedAncestorsTree ).reverse();

			_.each( relationshipTreeData, function( ancestorData, index, list ) {
				var templateData = {
						branchRoot: ancestorRoot,
						isM2mEnabled: self.i18n.is_enabled_m2m,
						ancestors: []
					};
				_.each( ancestorRoot, function( ancestorSlug, innerIndex, innerList ) {
					var ancestorRelationships = self.getAncestorRelationships( ancestorSlug, ancestorData, usedPostTypes );
					templateData.ancestors = _.union( templateData.ancestors, ancestorRelationships );
				});
				var templateOutcome = self.templates.ancestorNode( templateData );
				ancestorRoot = [ ancestorData.type ];
				usedPostTypes.push( ancestorData.type )
				$ancestorInput.before( templateOutcome );
			});

		} else {
			var templateData = {
					branchRoot: ancestorRoot,
					isM2mEnabled: self.i18n.is_enabled_m2m,
					ancestors: []
				};

			_.each( ancestorRoot, function( ancestorSlug, innerIndex, innerList ) {
				var ancestorRelationships = self.getAncestorRelationships( ancestorSlug, {}, usedPostTypes );
				templateData.ancestors = _.union( templateData.ancestors, ancestorRelationships );
			});
			var templateOutcome = self.templates.ancestorNode( templateData );
			$ancestorInput.before( templateOutcome );
		}

		$spinnerContainer.remove();
		$( '.js-wpv-filter-post-relationship-ancestor-node-item' )
			.first()
				.find( '.js-wpv-filter-post-relationship-ancestor-node-remove' )
					.css( 'visibility', 'hidden' );
		self.updateSearchFilterAncestorsButtons();

		return self;
	};

	self.relationshipsDataCached = false;

	self.getAncestorRelationships = function( ancestorSlug, currentAncestorSelected, usedPostTypes ) {
		self.maybeRequestRelationshipsData();

		var ancestorRelationships = [];

		currentAncestorSelected = _.extend( { type: '', role: '', relationship: '' }, currentAncestorSelected );

		_.each( self.cache.relationships, function( relationship, relationshipSlug ) {

			var isSelectedItem = {
				parent: function( maybeParent ) {
					return (
						maybeParent === currentAncestorSelected.type
						&& 'parent' === currentAncestorSelected.role
						&& (
							relationship.type === currentAncestorSelected.relationship
							|| (
								'' === currentAncestorSelected.relationship
								&& relationship.isLegacy
							)
						)
					);
				},
				child: function( maybeChild ) {
					return (
						maybeChild === currentAncestorSelected.type
						&& 'child' === currentAncestorSelected.role
						&& relationship.type === currentAncestorSelected.relationship
					);
				},
				intermediary: function( maybeIntermediary ) {
					return (
						maybeIntermediary === currentAncestorSelected.type
						&& 'intermediary' === currentAncestorSelected.role
						&& relationship.type === currentAncestorSelected.relationship
					);
				}
			};

			var processRole = function( role, relElement, index ) {
				if ( _.contains( usedPostTypes, relElement ) ) {
					return;
				}
				ancestorRelationships.push({
					relationship: relationship.type,
					relationshipLabel: relationship.label,
					relationshipLabelSingular: relationship.labelSingular,
					relationshipIsLegacy: relationship.isLegacy,
					relationshipOrigin: relationship.origin,
					type: relElement,
					label: _.has( self.cache.labels, relElement ) ? self.cache.labels[ relElement ].label : relElement,
					labelSingular: _.has( self.cache.labels, relElement ) ? self.cache.labels[ relElement ].labelSingular : relElement,
					role: role,
					selected: isSelectedItem[ role ]( relElement )
				});
			};


			if ( _.contains( relationship.roles.parent, ancestorSlug ) ) {
				_.each( relationship.roles.child, _.partial( processRole, 'child' ) );
				_.each( relationship.roles.intermediary, _.partial( processRole, 'intermediary' ) );
			}
			if ( _.contains( relationship.roles.child, ancestorSlug ) ) {
				_.each( relationship.roles.parent, _.partial( processRole, 'parent' ) );
				_.each( relationship.roles.intermediary, _.partial( processRole, 'intermediary' ) );
			}
			if ( _.contains( relationship.roles.intermediary, ancestorSlug ) ) {
				_.each( relationship.roles.parent, _.partial( processRole, 'parent' ) );
				_.each( relationship.roles.child, _.partial( processRole, 'child' ) );
			}
		});

		return ancestorRelationships;
	};

	// Adjust the hidden input with the right ancestors tree
	$( document ).on( 'change', '.js-wpv-filter-post-relationship-ancestor-node', function() {
		var $selector = $( this ),
			$selectorRow = $selector.closest( '.js-wpv-filter-post-relationship-ancestor-node-item' );

		$selector.removeClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );

		$selectorRow.nextAll( '.js-wpv-filter-post-relationship-ancestor-node-item' ).remove();

		self.updateSearchFilterAncestorsInput()
			.updateSearchFilterAncestorsButtons();
	});

	$( document ).on( 'mouseenter mouseleave', '.js-wpv-filter-post-relationship-ancestor-node-remove', function( e ) {
		var $button = $( this ),
			$buttonRow = $button.closest( '.js-wpv-filter-post-relationship-ancestor-node-item' );

		switch( e.type ) {
			case 'mouseenter':
				$buttonRow.nextAll( '.js-wpv-filter-post-relationship-ancestor-node-item' ).addClass( 'wpv-editable-list-item-deleted' );
				$buttonRow.addClass( 'wpv-editable-list-item-deleted' );
				break;
			case 'mouseleave':
				$buttonRow.nextAll( '.js-wpv-filter-post-relationship-ancestor-node-item' ).removeClass( 'wpv-editable-list-item-deleted' );
				$buttonRow.removeClass( 'wpv-editable-list-item-deleted' );
				break;
		}

	});

	$( document ).on( 'click', '.js-wpv-filter-post-relationship-ancestor-node-remove', function() {
		var $button = $( this ),
			$buttonRow = $button.closest( '.js-wpv-filter-post-relationship-ancestor-node-item' );


		$buttonRow.nextAll( '.js-wpv-filter-post-relationship-ancestor-node-item' ).remove();
		$buttonRow.remove();

		self.updateSearchFilterAncestorsInput()
			.updateSearchFilterAncestorsButtons();
	});

	$( document ).on( 'click', '.js-wpv-filter-post-relationship-ancestor-node-add', function() {
		var $lastAncestorSelector = $( '.js-wpv-filter-post-relationship-ancestor-node' ).last(),
			$lastAncestorAddButton = $( this );

		if ( '' == $lastAncestorSelector.val() ) {
			return;
		}

		var $ancestorInput = $( '#wpv-control-post-relationship-ancestors' ),
			templateData = {
				branchRoot: [ lastAncestor ],
				isM2mEnabled: self.i18n.is_enabled_m2m,
				ancestors: {}
			},
			selectedAncestorsTree = $ancestorInput.val(),
			relationshipTreeData = self.getAncestorsTreeData( selectedAncestorsTree ).reverse(),
			ancestorRoot = self.getPostTypesSelected(),
			treeTypes = _.pluck( relationshipTreeData, 'type' ),
			usedPostTypes = $.merge( [], ancestorRoot ),
			lastAncestor = _.last( treeTypes );

		usedPostTypes = $.merge( usedPostTypes, treeTypes );
		templateData.ancestors = self.getAncestorRelationships( lastAncestor, {}, usedPostTypes );

		if ( _.isEmpty( templateData.ancestors ) ) {
			$lastAncestorAddButton.prop( 'disabled', true );
			$ancestorInput.before( $( self.messages.noFurtherAncestors ) );
			setTimeout( function () {
				$( '.js-wpv-filter-post-relationship-no-further-ancestors' ).fadeOut( 'slow', function() {
					$( this ).remove();
				});
			}, 1500 );
			return;
		}

		var templateOutcome = self.templates.ancestorNode( templateData );
		$ancestorInput.before( templateOutcome );

		self.updateSearchFilterAncestorsButtons();
	});

	self.updateSearchFilterAncestorsButtons = function() {
		$( '.js-wpv-filter-post-relationship-ancestor-node-add' ).hide();
		var $lastAncestor = $( '.js-wpv-filter-post-relationship-ancestor-node-item' ).last(),
			$lastAncestorAddButton = $lastAncestor.find( '.js-wpv-filter-post-relationship-ancestor-node-add' )

		$lastAncestorAddButton.show();
		if ( '' == $lastAncestor.find( '.js-wpv-filter-post-relationship-ancestor-node' ).val() ) {
			$lastAncestorAddButton.prop( 'disabled', true );
		} else {
			$lastAncestorAddButton.prop( 'disabled', false );
		}

		return self;
	};

	self.updateSearchFilterAncestorsInput = function() {
		var inputValue = '',
			inputNodes = [];

		$( '.js-wpv-filter-post-relationship-ancestor-node' ).each( function() {
			if ( '' == $( this ).val() ) {
				return false;
			}
			inputNodes.push( $( this ).val() );
		});

		inputValue = inputNodes
			.reverse()
			.join( '>' );

		$( '#wpv-control-post-relationship-ancestors' ).val( inputValue );

		// Clean error on no further ancestors defined
		$( '.js-wpv-filter-post-relationship-no-further-ancestors' ).remove();

		return self;
	};

	self.add_control_structure_on_insert = function( placeholder, shortcode_data ) {

		var shortcode_name			= shortcode_data.name,
			shortcode_atts			= shortcode_data.attributes,
			shortcode_raw_atts		= shortcode_data.raw_attributes,
			shortcode_content		= shortcode_data.content,
			shortcode_string		= shortcode_data.shortcode,
			shortcode_gui_action	= Toolset.hooks.applyFilters( 'wpv-filter-wpv-shortcodes-gui-get-gui-action', 'insert' ),
			shortcode_atts_relationship = [ 'url_param', 'ancestors', 'format' ],
			shortcode_atts_ancestor = [ 'type', 'default_label', 'format', 'orderby', 'order', 'output', 'style', 'class', 'label_style', 'label_class' ],
			shortcode_relationship = '',
			shortcode_ancestor = '',
			shortcode_ancestors_attributes = '',
			filter_ancestors = ( _.has( shortcode_atts, 'ancestors' ) ) ? shortcode_atts.ancestors.split( '>' ) : [],
			control_type = '',
			output_type = '',
			post_types_reference = wpv_parametric_i18n.data.post_type_labels,
			shortcode_ancestor_start;

		shortcode_relationship += '[wpv-control-post-relationship';

		if ( _.has( shortcode_raw_atts, 'type' ) ) {
			control_type = shortcode_raw_atts.type;
		}
		if ( _.has( shortcode_raw_atts, 'output' ) ) {
			output_type = shortcode_raw_atts.output;
		}

		_.each( shortcode_atts, function( value, key ) {
			if (
				$.inArray( key, shortcode_atts_relationship ) !== -1
				&& value !== false
			) {
				shortcode_relationship += " " + key + '="' + value + '"';
			}
			if (
				$.inArray( key, shortcode_atts_ancestor ) !== -1
				&& value !== false
			) {
				shortcode_ancestors_attributes += " " + key + '="' + value + '"';
			}
		});

		shortcode_relationship += ']';

		shortcode_relationship += "\n";

		_.each( filter_ancestors, function( ancestor, index ) {
			shortcode_ancestor = '[wpv-control-post-ancestor';
			shortcode_ancestor += shortcode_ancestors_attributes;
			shortcode_ancestor += ' ancestor_type="' + ancestor + '"';
			shortcode_ancestor += ']';

			var ancestorType = ancestor.split( '@' )[0],
				shortcode_label = _.has( self.cache.labels, ancestorType ) ? ( '[wpml-string context="wpv-views"]' + self.cache.labels[ ancestorType ].labelSingular + '[/wpml-string]' ) : '';

			// This is how the element name is generated in WPV_Shortcode_Control_Post_Ancestor::get_value().
			var inputElementName = (filter_ancestors.length - 1 === index) ? 'wpv-relationship-filter' : 'wpv-relationship-filter-' + ancestorType;

			if ( 'bootstrap' === output_type ) {
				shortcode_ancestor = '<div class="form-group">' + '\n\t' + '<label for="' + inputElementName + '">' + shortcode_label + '</label>' + '\n\t' + shortcode_ancestor + '\n' + '</div>';
			} else {
				shortcode_ancestor_start = '';
				if ( index > 0 ) {
					shortcode_ancestor_start = '\n';
				}
				shortcode_ancestor = shortcode_ancestor_start + '\t' + shortcode_label + '\n\t' + shortcode_ancestor;
			}

			shortcode_relationship += shortcode_ancestor;
		}, this);

		shortcode_relationship += "\n" + '[/wpv-control-post-relationship]';

		return shortcode_relationship;
	};

	self.validateAncestorsSelector = function( valid ) {
		if ( valid ) {
			return valid;
		}
		if ( '' !== $( '#wpv-control-post-relationship-ancestors' ).val() ) {
			return valid;
		}
		$( '.js-wpv-filter-post-relationship-ancestor-node' )
			.first()
				.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
		return valid;
	};

	self.adjustControlPostRelationshipGuiPerType = function() {
		var value_type = $( '#wpv-control-post-relationship-type' ).val();

		switch( value_type ) {
			case 'select':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideUp( 'fast' );
				break;
			case 'multi-select':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideUp( 'fast' );
				break;
			case 'radios':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideDown( 'fast' );
				break;
			case 'checkboxes':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideDown( 'fast' );
				break;
		}

		return self;
	};

	$( document ).on( 'change', '#wpv-control-post-relationship-type', function() {
		self.adjustControlPostRelationshipGuiPerType();
	});

	self.filterControlPostRelationshipComputedAttributes = function( attributePairs ) {
		if ( _.has( attributePairs, 'type' ) ) {
			switch( attributePairs.type ) {
				case 'select':
					attributePairs.label_class = false;
					attributePairs.label_style = false;
					break;
				case 'multi-select':
					attributePairs.default_label = false;
					attributePairs.label_class = false;
					attributePairs.label_style = false;
					break;
				case 'radios':

					break;
				case 'checkboxes':
					attributePairs.default_label = false;
					break;
			}
		}
		attributePairs.value_compare = false;
		attributePairs.shortcode_label = false;
		return attributePairs;
	};

	/**
	 * Clears events and hooks.
	 *
	 * Put here all the events and hooks that need to be removed when the module is off-loaded.
	 *
	 * @returns {WPViews.PostRelationshipFilterGUI}
	 */
	self.clear_events_and_hooks = function() {
		$( document ).off( 'click', self.post_close_save_selector );
		return self;
	}

	//--------------------
	// Init hooks
	//--------------------

	self.initHooks = function() {
		// Register the filter saving action
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', {
			handle:		'save_filter_post_relationship',
			callback:	self.save_filter_post_relationship,
			event:		'js_event_wpv_save_filter_post_relationship_completed'
		});

		Toolset.hooks.addFilter( 'wpv-filter-wpv-get-post-relationship-filter-data', self.getFilterData );

		Toolset.hooks.addAction(
			'wpv-action-wpv-shortcodes-gui-after-open-wpv-control-post-relationship-shortcode-dialog',
			self.adjustControlPostRelationshipDialog,
			20
		);

		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-wpv-control-post-relationship-computed-attributes-pairs',
			self.filterControlPostRelationshipComputedAttributes
		);

		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-add-control-structure-on-insert-for-wpv-control-post-relationship',
			self.add_control_structure_on_insert,
			10
		);

		Toolset.hooks.addFilter(
			'wpv-filter-wpv-shortcodes-gui-wpv-control-post-relationship-validate-shortcode',
			self.validateAncestorsSelector
		);

		/**
		 * Clears events and hooks.
		 */
		Toolset.hooks.addAction( 'wpv-action-wpv-filter-clear-events-and-hooks', self.clear_events_and_hooks );

		return self;
	};

	self.templates = {};

	self.initTemplates = function() {
		self.templates.ancestorNode = wp.template( 'wpv-admin-filter-post-relationship-ancestor_node' );

		return self;
	};

	//--------------------
	// Init
	//--------------------

	self.init = function() {
		self.managePostRelationshipFilterWarning()
			.initTemplates()
			.initHooks()
			.initRoleSelectorLabels()
			.initPostIdSelector();
	};

	self.init();

};

jQuery( document ).ready( function( $ ) {
    WPViews.post_relationship_filter_gui = new WPViews.PostRelationshipFilterGUI( $ );
});

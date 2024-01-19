<?php
/**
 * Top bar for Content Template edit page
 *
 * @package Views
 * @since 2.7
 */

/*
*************************************************************************
Request WPV_Content_Template properties for the JS side
*************************************************************************
*/

add_filter( 'wpv_ct_editor_request_properties', 'wpv_ct_editor_top_bar_request_properties' );

/**
 * @param array $property_names
 * @return array
 */
function wpv_ct_editor_top_bar_request_properties( $property_names ) {
	return array_merge( $property_names, array( 'title', 'slug', 'description_raw' ) );
}

/*
*************************************************************************
Localize the bar in JS
*************************************************************************
*/

add_filter( 'wpv_ct_editor_localize_script', 'wpv_ct_editor_top_bar_localize_script' );

/**
 * @param array $l10n_data
 * @return array
 */
function wpv_ct_editor_top_bar_localize_script( $l10n_data ) {
	$l10n_data['title_section'] = array(
		'saved' => __( 'Title, slug and description updated.', 'wpv-views' ),
		'unsaved' => __( 'Title, slug and description not saved.', 'wpv-views' ),
		'ptr_section' => array(
			'title' => __( 'Title and description', 'wpv-views' ),
			'paragraphs' => array(
				__( 'The name of the Content Template is used for you, to identify it. The name is not displayed anywhere on the site.', 'wpv-views' ),
			),
		),
		'title_and_slug_used' => __( 'Both title and slug are being already used by another Content Template. Please use other values.', 'wpv-views' ),
		'value_already_used_exception_code' => WPV_RuntimeExceptionWithMessage::EXCEPTION_VALUE_ALREADY_USED,
	);
	return $l10n_data;
}

/*
*************************************************************************
Render section content
*************************************************************************
*/

add_action( 'wpv_ct_editor_top_bar', 'wpv_ct_editor_render_top_bar', 10 );

/**
 * @param WPV_Content_Template $ct
 */
function wpv_ct_editor_render_top_bar( $ct ) {
	?>
	<div id="js-wpv-general-actions-bar" class="wpv-settings-save-all wpv-general-actions-bar wpv-setting-container js-wpv-general-actions-bar">
		<div id="titlediv" class="js-wpv-settings-title-and-desc">
			<h1 class="wp-heading-inline">
				<?php echo esc_html( __( 'Edit Content Template', 'wpv-views' ) ); ?>
			</h1>
			<div id="titlewrap" class="js-wpv-titlewrap">
				<span id="title-alt">
					<?php echo esc_html( $ct->title ); ?><i class="fa fa-pencil"></i>
				</span>
				<label class="screen-reader-text js-title-reader" id="title-prompt-text" for="title">
					<?php echo esc_html( __( 'Enter title here', 'wpv-views' ) ); ?>
				</label>
				<input id="title" name="title" type="text" size="30" autocomplete="off" style="display:none"
					data-bind="textInput: title"/>
				<button class="button-secondary button button-large"
						data-bind="enable: isSaveAllButtonEnabled,
						attr: { class: isSaveAllButtonEnabled() ? 'button-primary' : 'button-secondary' },
						click: saveAllProperties">
					<?php
					/* translators: Label of the button to save the Content Template */
					echo esc_html( __( 'Save the Content Template', 'wpv-views' ) );
					?>
				</button>
				<span class="spinner ajax-loader" data-bind="spinnerActive: isAnySectionUpdating"></span>
			</div>
		</div>

		<div id="save-form-actions">
			<label>
				<?php
				/* translators: Label for the slug input for a Content Template */
				echo esc_html( __( 'Slug:', 'wpv-views' ) );
				?>
				<!--suppress HtmlFormInputWithoutLabel -->
				<input id="wpv-slug" name="slug" type="text" class="regular-text"
					data-bind="textInput: slugAccepted"/>
			</label>
			<?php
			if ( $ct->can_be_trashed ) {
				?>
				<a href="#" class="submit-trash" data-bind="click: trashAction, disable: isTrashing">
					<?php
					/* translators: Label for the link to trash a Content Template */
					echo esc_html( __( 'Move to trash', 'wpv-views' ) );
					?>
				</a>
				<span class="spinner ajax-loader" data-bind="spinnerActive: isTrashing"></span>
				<?php
			}
			?>
		</div>

		<div id="describe-actions" style="margin-top:7px;display:flex;line-height:27px;width:100%;">
			<label>
				<?php
				/* translators: Label for the input to save a description for a Content Template */
				echo esc_html( __( 'Description:', 'wpv-views' ) );
				?>
			</label>
			<div class="wpv-description-wrap" style="flex:1;padding-left:5px;">
				<span id="description-alt"
					<?php if ( '' === $ct->description_raw ) {
						echo ' style="display:none"';
					} ?>>
					<span data-bind="text: descriptionAccepted"></span>
					<i class="fa fa-pencil"></i>
				</span>
				<textarea
					id="wpv-description"
					name="description"
					class="js-wpv-description"
					style="width:100%;resize:none;<?php
					if ( '' !== $ct->description_raw ) {
						echo 'display:none;';
					} ?>"
					data-bind="textInput: descriptionAccepted">
				</textarea>
			</div>
		</div>

		<div class="wpv-message-container js-wpv-message-container"></div>
	</div>
	<?php

}

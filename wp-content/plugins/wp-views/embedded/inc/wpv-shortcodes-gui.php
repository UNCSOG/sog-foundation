<?php

/**
* wpv-shortcodes-gui.php
*
* All callback actions to display popups to set options for our Views shortcodes go here
*
* @package Views
*
* @since unknown
*/

/**
* ----------------------------------------------------------------------
## Parametric search ##
* ----------------------------------------------------------------------
*/

/**
* wpv_ajax_wpv_insert_view_dialog_callback
*
* Dialog for inserting a View form, loaded from a jQuery UI AJAX call
*
* @param $_GET['view_id']
* @param $_GET['view_title']
* @param $_GET['orig_id']
* @param $_GET['_wpnonce']
*
* @since 1.4
*/

add_action( 'wp_ajax_wpv_view_form_popup', 'wpv_ajax_wpv_insert_view_dialog_callback' );

function wpv_ajax_wpv_insert_view_dialog_callback() {
	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wpv_editor_callback' ) ) {
		die();
	}

	global $wpdb, $WP_Views, $wp_version;

	$view_id = intval( $_GET['view_id'] );
	$view_title = sanitize_text_field( $_GET['view_title'] );
	$view_name = sanitize_text_field( $_GET['view_name'] );
	$orig_id = intval( $_GET['orig_id'] );

	$has_extra_attributes = get_view_allowed_attributes( $view_id );

	$has_parametric_search = $WP_Views->does_view_have_form_controls( $view_id );
	$has_submit = false;
	$view_settings = $WP_Views->get_view_settings( $view_id );
	if ( ! isset( $view_settings['query_type'][0] ) ) {
		$query_type = 'posts';
	} else {
		$query_type = $view_settings['query_type'][0];
	}
	if ( isset( $view_settings['filter_meta_html'] ) ) {
		$filter_meta_html = $view_settings['filter_meta_html'] ;
	} else {
		$filter_meta_html = '';
	}
	if ( strpos( $filter_meta_html, '[wpv-filter-submit' ) !== false ) {
		$has_submit = true;
	}
	?>
	<div id="js-wpv-shortcode-gui-dialog-tabs" class="toolset-shortcodes__tabs js-toolset-shortcode-gui-tabs js-wpv-shortcode-gui-tabs">
		<ul>
			<?php if ( $has_parametric_search ) { ?>
			<li><a href="#js-wpv-insert-view-parametric-search-container"><?php _e( 'Custom search', 'wpv-views' ); ?></a></li>
			<?php } ?>
			<li><a href="#js-wpv-insert-view-override-container"><?php _e( 'Override settings', 'wpv-views' ); ?></a></li>
			<?php if ( ! empty( $has_extra_attributes ) ) { ?>
			<li><a href="#js-wpv-insert-view-extra-attributes-container"><?php _e( 'Query filters', 'wpv-views' ); ?></a></li>
			<?php } ?>
			<li><a href="#js-wpv-insert-view-cache-attributes-container"><?php _e( 'Caching', 'wpv-views' ); ?></a></li>
		</ul>
		<input type="hidden" id="js-wpv-view-shortcode-gui-dialog-view-title" value="<?php echo esc_attr( $view_name ); ?>" />
		<div id="js-wpv-insert-view-override-container" class="wpv-insert-view-override-container js-wpv-insert-view-override-container" style="overflow:hidden">
			<h2><?php _e( 'Override some View basic settings', 'wpv-views' ); ?></h2>
			<p>
				<span style="color:red">*</span><?php _e( 'View default settings will be applied for empty inputs.', 'wpv-views' ); ?>
			</p>
			<ul>
				<li>
					<label for="wpv-insert-view-shortcode-limit" class="label-alignleft"><?php _e( 'Limit', 'wpv-views' ); ?></label>
					<input type="text" id="wpv-insert-view-shortcode-limit" class="regular-text js-wpv-insert-view-shortcode-limit js-wpv-has-placeholder" data-type="numberextended" placeholder="<?php echo esc_attr( __( 'Numerical value', 'wpv-views' ) ); ?>" data-placeholder="<?php echo esc_attr( __( 'Numerical value', 'wpv-views' ) ); ?>" />
					<span class="wpv-helper-text">
						<?php echo __( 'Get only some results. -1 means no limit', 'wpv-views' ); ?>
					</span>
				</li>
				<li>
					<label for="wpv-insert-view-shortcode-offset" class="label-alignleft"><?php _e( 'Offset', 'wpv-views' ); ?></label>
					<input type="text" id="wpv-insert-view-shortcode-offset" class="regular-text js-wpv-insert-view-shortcode-offset js-wpv-has-placeholder" data-type="number" placeholder="<?php echo esc_attr( __( 'Numerical value', 'wpv-views' ) ); ?>" data-placeholder="<?php echo esc_attr( __( 'Numerical value', 'wpv-views' ) ); ?>" />
					<span class="wpv-helper-text">
						<?php echo __( 'Skip some results. 0 means skip nothing', 'wpv-views' ); ?>
					</span>
				</li>
				<li>
					<?php
					$placeholder = '';
					$helper = '';
					switch ( $query_type ) {
						case 'posts':
							$placeholder = __( 'ID, date, author, title, post_type or field-slug', 'wpv-views' );
							$helper = __( 'You can sort by a custom field simply using the value <em>field-xxx</em> where xxx is the custom field slug.', 'wpv-views' );
							break;
						case 'taxonomy':
							$placeholder = __( 'id, count, name, slug or taxonomy-field-slug', 'wpv-views' );
							if ( version_compare( $wp_version, '4.5', '<' ) ) {
								$helper = __( 'Use values like <em>id</em>, <em>count</em>, <em>name</em> or <em>slug</em>.', 'wpv-views' );
							} else {
								$helper = __( 'You can sort by a term field simply using the value <em>taxonomy-field-xxx</em> where xxx is the term field slug.', 'wpv-views' );
							}
							break;
						case 'users':
							$placeholder = __( 'user_email, user_login, user-field-slug...', 'wpv-views' );
							$helper = __( 'Use values like <em>user_email</em>, <em>user_login</em>, <em>display_name</em>, <em>user_nicename</em>, <em>user_url</em>, <em>user_registered</em> or <em>include</em>. You can sort by a usermeta field simply using the value <em>user-field-xxx</em> where xxx is the user field slug.', 'wpv-views' );
							break;
					}
					?>
					<label for="wpv-insert-view-shortcode-orderby" class="label-alignleft"><?php _e( 'Order by', 'wpv-views' ); ?></label>
					<input type="text" id="wpv-insert-view-shortcode-orderby" class="regular-text js-wpv-insert-view-shortcode-orderby js-wpv-has-placeholder" data-type="holdon" placeholder="<?php echo esc_attr( $placeholder ); ?>" data-placeholder="<?php echo esc_attr( $placeholder ); ?>" />
					<span class="wpv-helper-text">
						<?php
						echo __( 'Change how the results will be ordered.', 'wpv-views' ) . '<br />';
						echo $helper;
						?>
					</span>
				</li>
				<li>
					<label for="wpv-insert-view-shortcode-order" class="label-alignleft"><?php _e( 'Order', 'wpv-views' ); ?></label>
					<select id="wpv-insert-view-shortcode-order" class="wpv-regular-select js-wpv-insert-view-shortcode-order">
						<option value=""><?php _e( 'Default setting', 'wpv-views' ); ?></option>
						<option value="asc"><?php _e( 'Ascending', 'wpv-views' ); ?></option>
						<option value="desc"><?php _e( 'Descending', 'wpv-views' ); ?></option>
					</select>
					<span class="wpv-helper-text">
						<?php echo __( 'Change the order of the results.', 'wpv-views' ); ?>
					</span>
				</li>
				<li class="js-wpv-insert-view-shortcode-orderby_as-setting" style="display:none">
					<label for="wpv-insert-view-shortcode-orderby_as" class="label-alignleft"><?php _e( 'Order by as', 'wpv-views' ); ?></label>
					<select id="wpv-insert-view-shortcode-orderby_as" class="wpv-regular-select js-wpv-insert-view-shortcode-orderby_as">
						<option value=""><?php _e( 'Default setting', 'wpv-views' ); ?></option>
						<option value="string"><?php _e( 'String', 'wpv-views' ); ?></option>
						<option value="numeric"><?php _e( 'Numeric', 'wpv-views' ); ?></option>
					</select>
					<span class="wpv-helper-text">
						<?php echo __( 'Change how to manage the sorting by a field.', 'wpv-views' ); ?>
					</span>
				</li>
				<?php
				if (
					in_array( $query_type, array( 'posts' ) )
					&& ! version_compare( $wp_version, '4.0', '<' )
				) {
					$placeholder = __( 'ID, date, author or title', 'wpv-views' );
				?>
				<li class="wpv-insert-views-shortcode-advanced-toggler js-wpv-insert-views-shortcode-advanced-toggler">
					<span>
						<i class="fa fa-caret-down" aria-hidden="true"></i>
						<?php _e( 'Secondary sorting', 'wpv-views' ); ?>
					</span>
				</li>
				<li class="js-wpv-insert-view-shortcode-orderby_second-setting js-wpv-insert-views-shortcode-advanced-wrapper hidden">
					<label for="wpv-insert-view-shortcode-orderby_second" class="label-alignleft"><?php _e( 'Secondary Order by', 'wpv-views' ); ?></label>
					<select id="wpv-insert-view-shortcode-orderby_second" class="wpv-regular-select js-wpv-insert-view-shortcode-orderby_second" data-type="holdon">
						<option value=""><?php _e( 'No secondary sorting', 'wpv-views' ); ?></option>
						<option value="post_date"><?php _e('Post date', 'wpv-views'); ?></option>
						<option value="post_title"><?php _e('Post title', 'wpv-views'); ?></option>
						<option value="ID"><?php _e('Post ID', 'wpv-views'); ?></option>
						<option value="post_author"><?php _e('Post author', 'wpv-views'); ?></option>
						<option value="post_type"><?php _e('Post type', 'wpv-views'); ?></option>
					</select>
					<span class="wpv-helper-text">
						<?php
						echo __( 'Change how the results that share the same value on the orderby setting will be ordered.', 'wpv-views' ) . '<br />';
						//echo $helper;
						?>
					</span>
				</li>
				<li class="js-wpv-insert-view-shortcode-order_second-setting js-wpv-insert-views-shortcode-advanced-wrapper hidden">
					<label for="wpv-insert-view-shortcode-order_second" class="label-alignleft"><?php _e( 'Secondary Order', 'wpv-views' ); ?></label>
					<select id="wpv-insert-view-shortcode-order_second" class="wpv-regular-select js-wpv-insert-view-shortcode-order_second" disabled="disabled">
						<option value=""><?php _e( 'Default setting', 'wpv-views' ); ?></option>
						<option value="asc"><?php _e( 'Ascending', 'wpv-views' ); ?></option>
						<option value="desc"><?php _e( 'Descending', 'wpv-views' ); ?></option>
					</select>
					<span class="wpv-helper-text">
						<?php echo __( 'Change the secondary order of the results.', 'wpv-views' ); ?>
					</span>
				</li>
				<?php
				}
				?>
			</ul>
		</div>
		<?php if ( ! empty( $has_extra_attributes ) ) { ?>
		<div id="js-wpv-insert-view-extra-attributes-container" class="wpv-insert-view-extra-attributes-container js-wpv-insert-view-extra-attributes-container">
			<h2><?php _e( 'Fill in some Query Filters settings', 'wpv-views' ); ?></h2>
			<p>
				<?php _e( 'This View can be filtered by the values listed here.', 'wpv-views' ); ?>
			</p>
			<ul>
			<?php
			foreach ( $has_extra_attributes as $attr_settings ) {
				?>
				<li>
					<label for="wpv-insert-view-shortcode-extra-attribute-<?php echo $attr_settings['filter_type']; ?>" class="label-alignleft"><?php echo $attr_settings['filter_label']; ?></label>
					<input type="text"
						id="wpv-insert-view-shortcode-extra-attribute-<?php echo $attr_settings['filter_type']; ?>"
						class="regular-text js-wpv-insert-view-shortcode-extra-attribute js-wpv-has-placeholder"
						placeholder="<?php echo $attr_settings['placeholder']; ?>"
						data-placeholder="<?php echo $attr_settings['placeholder']; ?>"
						data-attribute="<?php echo $attr_settings['attribute']; ?>"
						data-type="<?php echo $attr_settings['expected']; ?>" />
					<span class="wpv-helper-text"><?php echo $attr_settings['description']; ?></span>
				</li>
				<?php
			}
			?>
			</ul>
		</div>
		<?php } ?>
		<?php if ( $has_parametric_search ) { ?>
		<div id="js-wpv-insert-view-parametric-search-container" class="wpv-insert-view-parametric-search-container js-wpv-insert-view-parametric-search-container">
			<h2><?php _e( 'Custom search settings', 'wpv-views' ); ?></h2>
			<div class="js-wpv-insert-view-form-display-container">
				<p><strong><?php _e( 'What do you want to include here?', 'wpv-views' ); ?></strong></p>
				<ul>
					<li>
						<label for="wpv-filter-form-display-both">
							<input id="wpv-filter-form-display-both" value="both" type="radio" name="wpv-insert-view-form-display" class="js-wpv-insert-view-form-display" checked="checked" />
							<?php _e('Both the search form and results', 'wpv-views'); ?>
						</label>
						<span class="wpv-helper-text"><?php _e( 'This will display the full View.', 'wpv-views' ); ?></span>
					</li>
					<li>
						<label for="wpv-filter-form-display-form">
							<input id="wpv-filter-form-display-form" value="form" type="radio" name="wpv-insert-view-form-display" class="js-wpv-insert-view-form-display" />
							<?php _e('Only the search form', 'wpv-views'); ?>
						</label>
						<span class="wpv-helper-text"><?php _e( 'This will display just the form, you can then select where to display the results.', 'wpv-views' ); ?></span>
					</li>
					<li>
						<label for="wpv-filter-form-display-results">
							<input id="wpv-filter-form-display-results" value="results" type="radio" name="wpv-insert-view-form-display" class="js-wpv-insert-view-form-display" />
							<?php _e('Only the search results', 'wpv-views'); ?>
						</label>
						<span class="wpv-helper-text"><?php _e( 'This will display just the results, you need to add the form elsewhere targeting this page.', 'wpv-views' ); ?></span>
					</li>
				</ul>
			</div>
			<div class="js-wpv-insert-view-form-target-container wpv-advanced-setting" style="display:none">
				<p><strong><?php _e( 'Where do you want to display the results of this search?', 'wpv-views' ); ?></strong></p>
				<?php if ( ! $has_submit ) { ?>
				<span class="toolset-alert toolset-error">
					<?php _e( 'The form in this View does not have a submit button, so you can only display the results on this same page.', 'wpv-views' ); ?>
				</span>
				<?php } ?>
				<ul>
					<li>
						<label for="wpv-filter-form-target-self">
							<input id="wpv-filter-form-target-self" value="self" type="radio" name="wpv-insert-view-form-target" class="js-wpv-insert-view-form-target" checked="checked" />
							<?php _e('In other place on this same page', 'wpv-views'); ?>
						</label>
					</li>
					<li>
						<label for="wpv-filter-form-target-other" <?php if ( ! $has_submit ) { ?>style="color:#999"<?php } ?>>
							<input id="wpv-filter-form-target-other" <?php disabled( $has_submit, false ); ?> value="other" type="radio" name="wpv-insert-view-form-target" class="js-wpv-insert-view-form-target" />
							<?php _e('On another page', 'wpv-views'); ?>
						</label>
					</li>
				</ul>
				<div class="js-wpv-insert-view-form-target-set-container wpv-setting-extra" style="display:none;">
					<ul>
						<li>
							<label for="wpv-insert-view-form-target-set-existing">
								<input id="wpv-insert-view-form-target-set-existing" value="existing" type="radio" name="wpv-insert-view-form-target-set" class="js-wpv-insert-view-form-target-set" checked="checked" />
								<?php _e( 'Use an existing page', 'wpv-views' ); ?>
							</label>
							<div class="js-wpv-insert-view-form-target-set-existing-extra" style="margin:5px 0 0 20px;">
								<input class="js-wpv-insert-view-form-target-set-existing-title" type="text" name="wpv-insert-view-form-target-set-existing-title" placeholder="<?php echo esc_attr( __( 'Type the title of the page', 'wpv-views' ) ); ?>" value="" />
								<input class="js-wpv-insert-view-form-target-set-existing-id" type="hidden" name="wpv-insert-view-form-target-set-existing-id" value="" />
								<div class="js-wpv-insert-view-form-target-set-actions" style="display:none;margin-top: 5px;">
									<?php _e( 'Be sure to complete the setup:', 'wpv-views' ); ?><br />
									<a href="#" target="_blank" class="button-primary js-wpv-insert-view-form-target-set-existing-link" data-origid="<?php echo $orig_id; ?>" data-viewid="<?php echo $view_id; ?>" data-editurl="<?php echo admin_url( 'post.php' ); ?>?post="><?php _e( 'Add the search results to this page', 'wpv-views' ); ?></a>
									<a href="#" class="button-secondary js-wpv-insert-view-form-target-set-discard"><?php _e( 'Not now', 'wpv-views' ); ?></a>
								</div>
							</div>
						</li>
						<li>
							<label for="wpv-insert-view-form-target-set-create">
								<input id="wpv-insert-view-form-target-set-create" value="create" type="radio" name="wpv-insert-view-form-target-set" class="js-wpv-insert-view-form-target-set" />
								<?php _e( 'Create a new page', 'wpv-views' ); ?>
							</label>
							<div class="js-wpv-insert-view-form-target-set-create-extra" style="display:none;margin:5px 0 0 20px;">
								<input class="js-wpv-insert-view-form-target-set-create-title" type="text" name="wpv-insert-view-form-target-set-extra-title" placeholder="<?php echo esc_attr( __( 'Title for the new page', 'wpv-views' ) ); ?>" value="" />
								<button class="button-secondary js-wpv-insert-view-form-target-set-create-action" disabled="disabled" data-viewid="<?php echo $view_id; ?>" data-nonce="<?php echo wp_create_nonce('wpv_create_form_target_page_nonce'); ?>"><?php _e( 'Create page', 'wpv-views' ); ?></button>
							</div>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<?php } ?>
		<div id="js-wpv-insert-view-cache-attributes-container" class="wpv-insert-view-cache-attributes-container js-wpv-insert-view-cache-attributes-container" style="overflow:hidden">
			<h2><?php _e( 'View caching settings', 'wpv-views' ); ?></h2>
			<p>
				<?php
					echo __( 'Views results can be cached if they meet some requirements, so they are displayed faster in the frontend.', 'wpv-views' )
						//. WPV_MESSAGE_SPACE_CHAR
						//. __( 'We try our best to only cache those Views that have a fixed output, and we clear the cache as soon as there is any change.', 'wpv-views' )
						. WPV_MESSAGE_SPACE_CHAR
						. __( 'However, you might want to disable this feature for the current View', 'wpv-views' )
					;
				?>
			</p>
			<ul>
				<li>
					<label for="wpv-insert-view-shortcode-cache-on">
						<input type="radio" name="wpv-insert-view-shortcode-cache" value="" class="js-wpv-insert-view-shortcode-cache" id="wpv-insert-view-shortcode-cache-on" checked="checked" />
						<?php _e( 'Use the cached version if it is available', 'wpv-views' ); ?>
					</label>
					<span class="wpv-helper-text">
						<?php
						echo __( 'This will return the cached output in case this View supports it.', 'wpv-views' );
						?>
					</span>
				</li>
				<li>
					<label for="wpv-insert-view-shortcode-cache-off">
						<input type="radio" name="wpv-insert-view-shortcode-cache" value="off" class="js-wpv-insert-view-shortcode-cache" id="wpv-insert-view-shortcode-cache-off" />
						<?php _e( 'Always generate the View output', 'wpv-views' ); ?>
					</label>
					<span class="wpv-helper-text"><?php _e( 'This will perform the whole View query and generate the output on-the-fly.', 'wpv-views' ); ?></span>
				</li>
			</ul>
		</div>
		<div class="wpv-filter-toolset-messages js-wpv-filter-toolset-messages"></div>
	</div>
	<?php
	die();
}

function wpv_shortcode_gui_dialog_render_field( $key, $data, $shortcode, $post_type ) {
	$content = '';
	if ( ! isset( $data['type'] ) ) {
		return $content;
	}
	$id = $shortcode;
	if ( 'grouped' == $data['type'] ) {
		$content .= sprintf(
			'<div class="wpv-shortcode-gui-attribute-group js-wpv-shortcode-gui-attribute-group js-wpv-shortcode-gui-attribute-group-for-%s" data-type="%s" data-group="%s">',
			esc_attr( $key ),
			esc_attr( $data['type'] ),
			esc_attr( $key )
		);
	} else {
		$id = sprintf(
			'%s-%s',
			$shortcode,
			$key
		);
		$content .= sprintf(
			'<div class="wpv-shortcode-gui-attribute-wrapper js-wpv-shortcode-gui-attribute-wrapper js-wpv-shortcode-gui-attribute-wrapper-for-%s" data-type="%s" data-attribute="%s" data-default="%s">',
			esc_attr( $key ),
			esc_attr( $data['type'] ),
			esc_attr( $key ),
			isset( $data['default'] ) ? esc_attr( $data['default'] ) : ''
		);
	}

	$attr_value = isset( $data['default'] ) ? $data['default'] : '';
	$attr_value = isset( $data['default_force'] ) ? $data['default_force'] : $attr_value;

	$classes = array('js-shortcode-gui-field');
	$required = '';
	if (
		isset( $data['required'] )
		&& $data['required']
	) {
		$classes[] = 'js-wpv-shortcode-gui-required';
		$required = ' <span>- ' . esc_html( __( 'required', 'wpv-views' ) ) . '</span>';
	}
	if ( isset( $data['label'] ) ) {
		$content .= sprintf(
			'<h3>%s%s</h3>',
			esc_html( $data['label'] ),
			$required
		);
	}
	if ( isset( $data['pseudolabel'] ) ) {
		$content .= sprintf(
			'<span class="wpv-shortcode-gui-attribute-pseudolabel">%s%s</span>',
			esc_html( $data['pseudolabel'] ),
			$required
		);
	}
	/**
	 * require
	 */
	if ( isset($data['required']) && $data['required']) {
		$classes[] = 'js-required';
	}
	/**
	 * Filter of options
	 *
	 * This filter allow to manipulate of radio/select field options.
	 * Filter is 'wpv_filter_wpv_shortcodes_gui_api_{shortode}_options'
	 *
	 * @param array $options for description see param $options in
	 * wpv_filter_wpv_shortcodes_gui_api filter.
	 *
	 * @param string $type field type
	 *
	 */
	if ( isset( $data['options'] ) ) {
		$data['options'] = apply_filters( 'wpv_filter_wpv_shortcodes_gui_api_' . $id . '_options', $data['options'], $data['type'] );
	}

	$attribute_content = wpv_shortcode_gui_dialog_render_attribute( $id, $data, $classes, $post_type );
	if ( empty( $attribute_content ) ) {
		return '';
	}

	$content .= $attribute_content;

	$desc_and_doc = array();
	if ( isset( $data['description'] ) ) {
		$desc_and_doc[] = esc_html( $data['description'] );
	}
	if ( isset( $data['documentation'] ) ) {
		$desc_and_doc[] = sprintf(
			__( 'Specific documentation: %s', 'wpv-views' ),
			$data['documentation']
		);
	}
	if ( ! empty( $desc_and_doc ) ) {
		$content .= '<p class="description">' . implode( '<br />', $desc_and_doc ) . '</p>';
	}
	$content .= '</div>';
	return $content;
}


/**
* wpv_shortcode_gui_dialog_render_attribute
*
* Render the options for each shortcode attribute in the dialog
*
* @param $id 			(string) 		Format {shortcode}-{attribute_key}(?-value)
* @param $data 			(array) 		Data for the current attribute
* @param $classes 		(array) 		Classnames to be applied to the current attribute form elements
* @param $post_type		(object|null) 	Post type object that the current post belongs to, null otherwise
*
* @return Print the shortcode attribute options
*
* @since 1.9
*/

function wpv_shortcode_gui_dialog_render_attribute( $id, $data = array(), $classes = array(), $post_type = null ) {
    if (
		isset( $data['classes'] )
		&& is_array( $data['classes'] )
	) {
        $classes = array_merge( $classes, $data['classes'] );
    }
	$attr_value = isset( $data['default'] ) ? $data['default'] : '';
	$attr_value = isset( $data['default_force'] ) ? $data['default_force'] : $attr_value;
    $content = '';
	$toolset_ajax = Toolset_Ajax::get_instance();
    /**
     * produce code
     */
    switch( $data['type'] ) {
	case 'info':
		if ( isset( $data['content'] ) ) {
			$content .= '<p class="' . esc_attr( $id ) . '">'
				. $data['content']
				. '</p>';
		}
		break;
	case 'message':
		if ( isset( $data['content'] ) ) {
			$content .= '<div class="' . esc_attr( $id ) . '">'
				. $data['content']
				. '</div>';
		}
		break;
	case 'number':
	case 'integer':
    case 'text':
    case 'url':
        $classes[] = 'large-text';
		if ( isset( $data['placeholder'] ) ) {
			$classes[] = 'js-wpv-shortcode-gui-attribute-has-placeholder';
		}
        $content .= sprintf(
            '<input id="%s" type="text" data-type="%s" placeholder="%s" data-placeholder="%s" class="%s" value="%s"%s />',
            esc_attr( $id ),
            esc_attr( $data['type'] ),
            isset( $data['placeholder'] ) ? esc_attr( $data['placeholder'] ) : '',
			isset( $data['placeholder'] ) ? esc_attr( $data['placeholder'] ) : '',
            esc_attr( implode( ' ', $classes ) ),
            esc_attr( $attr_value ),
			isset( $data['hide'] ) && $data['hide'] ? ' style="display:none"' : ''
        );
        break;
    case 'textarea':
        $classes[] = 'large-text';
		if ( isset( $data['placeholder'] ) ) {
			$classes[] = 'js-wpv-shortcode-gui-attribute-has-placeholder';
		}
        $content .= sprintf(
            '<textarea id="%s" data-type="textarea" placeholder="%s" data-placeholder="%s" class="%s" %s>%s</textarea>',
            esc_attr( $id ),
            isset( $data['placeholder'] ) ? esc_attr( $data['placeholder'] ) : '',
			isset( $data['placeholder'] ) ? esc_attr( $data['placeholder'] ) : '',
            esc_attr( implode( ' ', $classes ) ),
			isset( $data['hide'] ) && $data['hide'] ? ' style="display:none"' : '',
            esc_attr( $attr_value )
        );
        break;
    case 'suggest':
        $classes[] = 'large-text';
        $classes[] = 'js-wpv-shortcode-gui-suggest';
		if ( isset( $data['placeholder'] ) ) {
			$classes[] = 'js-wpv-shortcode-gui-attribute-has-placeholder';
		}
        $content .= sprintf(
            '<input id="%s" type="text" data-type="suggest" data-action="%s" placeholder="%s" data-placeholder="%s" class="%s" value="%s"%s />',
            esc_attr( $id ),
            isset( $data['action'] ) ? esc_attr( $data['action'] ) : '',
            isset( $data['placeholder'] ) ? esc_attr( $data['placeholder'] ) : '',
			isset( $data['placeholder'] ) ? esc_attr( $data['placeholder'] ) : '',
            esc_attr( implode( ' ', $classes ) ),
            esc_attr( $attr_value ),
            isset( $data['hide'] ) && $data['hide'] ? ' style="display:none"' : ''
        );
        break;
    case 'fixed':
        $classes[] = 'large-text';
        $classes[] = 'js-wpv-shortcode-gui-fixed';
        $content .= sprintf(
            '<input id="%s" type="text" data-type="%s" class="%s" value="%s" disabled="disabled"%s />',
            esc_attr( $id ),
            esc_attr( $data['type'] ),
            esc_attr( implode( ' ', $classes ) ),
            esc_attr( $attr_value ),
			isset( $data['hide'] ) && $data['hide'] ? ' style="display:none"' : ''
        );
        break;
    case 'radio':
		$list_class = isset( $data['class'] ) ? ( ' class="' . esc_attr( $data['class'] ) . '"' ) : '';
        $content .= sprintf(
            '<ul id="%s"%s>',
            esc_attr( $id ),
			$list_class
        );
        foreach ( $data['options'] as $option_value => $option_label ) {
            if ( 'custom-combo' == $option_value ) {
				$classes[] = 'js-wpv-shortcode-gui-attribute-custom-combo-pointer';
                $content .= sprintf(
                    '<li class="custom-combo js-wpv-shortcode-gui-attribute-custom-combo"><label><input type="radio" name="%s" value="%s" class="%s"%s />%s</label>',
                    esc_attr( $id ),
                    esc_attr( $option_value ),
                    esc_attr( implode( ' ', $classes ) ),
                    $option_value == $attr_value ? ' checked="checked"' : '',
                    esc_html( $option_label['label'] )
                );
                $option_label['classes'] = array(
					'custom-combo-target',
					'js-wpv-shortcode-gui-attribute-custom-combo-target',
					'js-shortcode-gui-field'
				);
				if ( isset( $option_label['required'] ) ) {
					$option_label['classes'][] = 'js-wpv-shortcode-gui-required';
				}
                $option_label['hide'] = true;
                $content .= wpv_shortcode_gui_dialog_render_attribute( $id.'-value', $option_label );
                $content .= '</li>';
            } else {
                $content .= sprintf(
                    '<li><label><input type="radio" name="%s" value="%s" class="%s"%s />%s</label></li>',
                    esc_attr( $id ),
                    esc_attr( $option_value ),
                    esc_attr( implode( ' ', $classes ) ),
                    $option_value == $attr_value ? ' checked="checked"' : '',
                    esc_html( $option_label )
                );
            }
        }
        $content .= '</ul>';
        break;
	case 'radiohtml':
        $list_class = isset( $data['class'] ) ? ( ' class="' . esc_attr( $data['class'] ) . '"' ) : '';
        $content .= sprintf(
            '<ul id="%s"%s>',
            esc_attr( $id ),
			$list_class
        );
        foreach ( $data['options'] as $option_value => $option_label ) {
            if ( 'custom-combo' == $option_value ) {
				$classes[] = 'js-wpv-shortcode-gui-attribute-custom-combo-pointer';
                $content .= sprintf(
                    '<li class="custom-combo js-wpv-shortcode-gui-attribute-custom-combo"><label><input type="radio" name="%s" value="%s" class="%s"%s />%s</label>',
                    esc_attr( $id ),
                    esc_attr( $option_value ),
                    esc_attr( implode( ' ', $classes ) ),
                    $option_value == $attr_value ? ' checked="checked"' : '',
                    $option_label['label']
                );
                $option_label['classes'] = array(
					'custom-combo-target',
					'js-wpv-shortcode-gui-attribute-custom-combo-target',
					'js-shortcode-gui-field'
				);
				if ( isset( $option_label['required'] ) ) {
					$option_label['classes'][] = 'js-wpv-shortcode-gui-required';
				}
                $option_label['hide'] = true;
                $content .= wpv_shortcode_gui_dialog_render_attribute( $id.'-value', $option_label );
                $content .= '</li>';
            } else {
                $content .= sprintf(
                    '<li><label><input type="radio" name="%s" value="%s" class="%s"%s />%s</label></li>',
                    esc_attr( $id ),
                    esc_attr( $option_value ),
                    esc_attr( implode( ' ', $classes ) ),
                    $option_value == $attr_value ? ' checked="checked"' : '',
                    $option_label
                );
            }
        }
        $content .= '</ul>';
        break;
    case 'select':
        $content .= sprintf(
            '<select id="%s" class="%s"%s>',
            esc_attr( $id ),
            esc_attr( implode( ' ', $classes ) ),
			isset( $data['hide'] ) && $data['hide'] ? ' style="display:none"' : ''
        );
        foreach ( $data['options'] as $option_value => $option_label ) {
            $content .= sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $option_value ),
                $option_value == $attr_value ? ' selected="selected"' : '',
                esc_html( $option_label )
            );
        }
        $content .= '</select>';
		break;
	case 'multiselect-select2':
		$classes[] = 'js-wpv-shortcode-gui-field-select2';
		$attr_value = toolset_ensarr( $attr_value );
		$content .= sprintf(
            '<select id="%s" class="%s"%s multiple="multiple">',
            esc_attr( $id ),
            esc_attr( implode( ' ', $classes ) ),
			isset( $data['hide'] ) && $data['hide'] ? ' style="display:none"' : ''
        );
        foreach ( $data['options'] as $option_value => $option_label ) {
            $content .= sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $option_value ),
                in_array( $option_value, $attr_value, true ) ? ' selected="selected"' : '',
                esc_html( $option_label )
            );
        }
        $content .= '</select>';
		break;
	case 'grouped':
		$content .= '<ul class="wpv-shortcode-gui-dialog-item-grouped">';
		foreach ( $data['fields'] as $grouped_field_attribute => $grouped_field_data ) {
			$grouped_field_content = wpv_shortcode_gui_dialog_render_field( $grouped_field_attribute, $grouped_field_data, $id, $post_type );
			if ( ! empty( $grouped_field_content ) ) {
				$content .= '<li>' . $grouped_field_content . '</li>';
			}
		}
		$content .= '</ul>';
		break;
    case 'post':
		$content .= sprintf(
            '<ul id="%s">',
            esc_attr( $id )
        );

        $content .= '<li class="wpv-shortcode-gui-item-selector-option">';
		$content .= '<label for="wpv-shortcode-gui-item-selector-post-id-current">';
        $content .= '<input type="radio" class="js-wpv-shortcode-gui-item-selector" id="wpv-shortcode-gui-item-selector-post-id-current" name="wpv_shortcode_gui_object_id" value="current" checked="checked" />';
        $content .=  __( 'The current post being displayed either directly or in a View loop', 'wpv-views' );
        $content .= '</label>';
        $content .= '</li>';

		/**
		 * $current_page in Views
		 */
        if ( in_array( toolset_getget('get_page'), array( 'views-editor' ) ) ) {
            $content .= '<li class="wpv-shortcode-gui-item-selector-option">';
			$content .= '<label for="wpv-shortcode-gui-item-selector-post-id-current_page">';
            $content .= '<input type="radio" class="js-wpv-shortcode-gui-item-selector" id="wpv-shortcode-gui-item-selector-post-id-current_page" name="wpv_shortcode_gui_object_id" value="$current_page" />';
            $content .= __( 'The page where this View is shown', 'wpv-views' );
            $content .= '</label>';
            $content .= '</li>';
        }

		/**
		 * Hierarchical
		 */
        if (
			is_null( $post_type )
			|| (
				is_object( $post_type )
				&& isset( $post_type->hierarchical )
				&& $post_type->hierarchical
			)
		) {
            $content .= '<li class="wpv-shortcode-gui-item-selector-option">';
			$content .= '<label for="wpv-shortcode-gui-item-selector-post-id-parent">';
            $content .= '<input type="radio" class="js-wpv-shortcode-gui-item-selector" id="wpv-shortcode-gui-item-selector-post-id-parent" name="wpv_shortcode_gui_object_id" value="$parent" />';
            $content .= __( 'The parent of the current post in the same post type, set by WordPress hierarchical relationship', 'wpv-views' );
            $content .= '</label>';
            $content .= '</li>';
        }

        /**
		 * Types relationships
		 */
		if( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			// Legacy relationships
			$current_post_type_parents = array();
			$custom_post_types_relations = get_option( 'wpcf-custom-types', array() );

			if ( is_null( $post_type ) ) {
				foreach ( $custom_post_types_relations as $cptr_key => $cptr_data ) {
					if ( isset( $cptr_data['post_relationship']['has'] ) ) {
						$current_post_type_parents[] = $cptr_key;
					}
					if (
						isset( $cptr_data['post_relationship']['belongs'] )
						&& is_array( $cptr_data['post_relationship']['belongs'] )
					) {
						$this_belongs = array_keys( $cptr_data['post_relationship']['belongs'] );
						foreach ( $this_belongs as $this_belongs_candidate ) {
							if ( isset( $custom_post_types_relations[ $this_belongs_candidate ] ) ) {
								$current_post_type_parents[] = $this_belongs_candidate;
							}
						}
					}
				}
			} else if (
				is_object( $post_type )
				&& isset( $post_type->slug )
			) {
				// Fix legacy problem, when child CPT has no parents itself, but parent CPT has children
				foreach ( $custom_post_types_relations as $cptr_key => $cptr_data ) {
					if (
						isset( $cptr_data['post_relationship']['has'] )
						&& in_array( $post_type->slug, array_keys( $cptr_data['post_relationship']['has'] ) )
					) {
						$current_post_type_parents[] = $cptr_key;
					}
				}
				if ( isset( $custom_post_types_relations[$post_type->slug] ) ) {
					$current_post_type_data = $custom_post_types_relations[$post_type->slug];
					if (
						isset( $current_post_type_data['post_relationship'] )
						&& ! empty( $current_post_type_data['post_relationship'] )
						&& isset( $current_post_type_data['post_relationship']['belongs'] )
					) {
						foreach ( array_keys( $current_post_type_data['post_relationship']['belongs'] ) as $cpt_in_relation) {
							// Watch out! WE are not currently clearing the has and belongs entries of the relationships when deleting a post type
							// So make sure the post type does exist
							if ( isset( $custom_post_types_relations[ $cpt_in_relation ] ) ) {
								$current_post_type_parents[] = $cpt_in_relation;
							}
						}
					}
				}
			}

			$current_post_type_parents = array_values( $current_post_type_parents );
			$current_post_type_parents = array_unique( $current_post_type_parents );

			if ( ! empty( $current_post_type_parents) ) {
				$content .= '<li class="wpv-shortcode-gui-item-selector-option wpv-shortcode-gui-item-selector-has-related js-wpv-shortcode-gui-item-selector-has-related">';
				$content .= '<label for="wpv-shortcode-gui-item-selector-post-id-related">';
				$content .= '<input type="radio" class="js-wpv-shortcode-gui-item-selector" id="wpv-shortcode-gui-item-selector-post-id-related" name="wpv_shortcode_gui_object_id" value="related" />';
				$content .= __( 'The parent of the current post in another post type, set by a Types relationship', 'wpv-views' );
				$content .= '</label>';
				$content .= '<div class="wpv-shortcode-gui-item-selector-is-related js-wpv-shortcode-gui-item-selector-is-related" style="display:none">';
				$content .= '<ul class="wpv-advanced-setting wpv-mightlong-list" style="padding-top:15px;margin:5px 0 10px;">';
				$first = true;
				foreach ( $current_post_type_parents as $slug  ) {
					$content .= '<li>';
					$content .= sprintf( '<label for="wpv-shortcode-gui-item-selector-post-relationship-id-%s">', $slug );
					$content .= sprintf(
						'<input type="radio" name="related_object" id="wpv-shortcode-gui-item-selector-post-relationship-id-%s" value="$%s" %s />',
						$slug,
						$slug,
						checked( $first, true, false )
					);
					$content .= $custom_post_types_relations[$slug]['labels']['singular_name'];
					$content .= '</label>';
					$content .= '</li>';
					$first = false;
				}
				$content .= '</ul>';
				$content .= '</div>';
				$content .= '</li>';
			}
		} else {
			// m2m relationships
			// Make sure m2m classes are registered in the autoloader
			do_action( 'toolset_do_m2m_full_init' );
			$query = new Toolset_Relationship_Query_V2();

			// Note that we can not use $query->do_if() because it actually runs both branches
			// and one of them expects $current_post_type->name to exist
            if ( $post_type instanceof WP_Post_Type ) {
				$relationship_definitions = $query
					->add(
						$query->do_or(
							$query->has_domain_and_type( $post_type->name, Toolset_Element_Domain::POSTS ),
							$query->intermediary_type( $post_type->name )
						)
					)
					->add( $query->origin( null ) )
		            ->get_results();
            } else {
	            $relationship_definitions = $query
					->add( $query->origin( null ) )
		            ->get_results();
            }

			$relationship_definitions_per_origin = array(
				Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD => array(),
				Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD => array(),
				Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD => array(),
			);

			$relationship_section_title_per_cardinality = array(
				'one-to-one' => __( '%s (one-to-one relationship)', 'wpv-views' ),
				'one-to-many' => __( '%s (one-to-many relationship)', 'wpv-views' ),
				'many-to-many' => __( '%s (many-to-many relationship)', 'wpv-views' ),
			);

			foreach( $relationship_definitions as $relationship_definition ) {
				$relationship_cardinality = $relationship_definition->get_cardinality();
				$origin = $relationship_definition->get_origin();

				$relationship_definitions_per_origin[ $origin->get_origin_keyword() ][] = $relationship_definition;
			}

			if ( ! empty( $relationship_definitions_per_origin[ Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD ] ) ) {
				$content .= '<li class="wpv-shortcode-gui-item-selector-option wpv-shortcode-gui-item-selector-has-related js-wpv-shortcode-gui-item-selector-has-related">';
				$content .= '<label for="wpv-shortcode-gui-item-selector-post-id-related">';
				$content .= '<input type="radio" class="js-wpv-shortcode-gui-item-selector" id="wpv-shortcode-gui-item-selector-post-id-related" name="wpv_shortcode_gui_object_id" value="related" />';
				$content .= __( 'A post related to the current post, set by a Types relationship', 'wpv-views' );
				$content .= '</label>';
				$content .= '<div class="wpv-advanced-setting wpv-shortcode-gui-item-selector-is-related js-wpv-shortcode-gui-item-selector-is-related" style="display:none;margin-left:20px;">';
				foreach ( $relationship_definitions_per_origin[ Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD ] as $relationship_definition  ) {
					$cardinality = $relationship_definition->get_cardinality()->get_type();

					$content .= '<div style="margin:5px 0 0;">';
					$content .= '<h3>' . sprintf(
						$relationship_section_title_per_cardinality[ $cardinality ],
						$relationship_definition->get_display_name()
					) . '</h3>';
					$content .= '<ul>';
					$relationship_selectors_factory = new Toolset_Shortcode_Attr_Item_Gui_Factory(
						$relationship_definition, $post_type, 'related_object'
					);
					$relationship_selectors = $relationship_selectors_factory->get_options();
					foreach( $relationship_selectors as $relationship_selector_option ) {
						$content .= '<li style="display:inline-block;width:31%;vertical-align:top;margin-right:1%;">' . $relationship_selector_option . '</li>';
					}
					$content .= '</ul></div>';
				}
				$content .= '</div>';
				$content .= '</li>';
			}

			if ( ! empty( $relationship_definitions_per_origin[ Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD ] ) ) {
				$content .= '<li class="wpv-shortcode-gui-item-selector-option wpv-shortcode-gui-item-selector-has-related js-wpv-shortcode-gui-item-selector-has-related">';
				$content .= '<label for="wpv-shortcode-gui-item-selector-post-id-referenced">';
				$content .= '<input type="radio" class="js-wpv-shortcode-gui-item-selector" id="wpv-shortcode-gui-item-selector-post-id-referenced" name="wpv_shortcode_gui_object_id" value="referenced" />';
				$content .= __( 'A post related to the current post, set by a Types post reference field', 'wpv-views' );
				$content .= '</label>';
				$content .= '<div class="wpv-shortcode-gui-item-selector-is-related js-wpv-shortcode-gui-item-selector-is-related" style="display:none">';
				$content .= '<ul class="wpv-advanced-setting wpv-mightlong-list" style="padding-top:15px;margin:5px 0 10px;">';
				foreach ( $relationship_definitions_per_origin[ Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD ] as $relationship_definition  ) {
					$relationship_selectors_factory = new Toolset_Shortcode_Attr_Item_Gui_Factory(
						$relationship_definition, $post_type, 'referenced_object'
					);
					$relationship_selectors = $relationship_selectors_factory->get_options();
					foreach( $relationship_selectors as $relationship_selector_option ) {
						$content .= '<li>' . $relationship_selector_option . '</li>';
					}
				}
				$content .= '</ul>';
				$content .= '</div>';
				$content .= '</li>';
			}

			if ( ! empty( $relationship_definitions_per_origin[ Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD ] ) ) {
				$content .= '<li class="wpv-shortcode-gui-item-selector-option wpv-shortcode-gui-item-selector-has-related js-wpv-shortcode-gui-item-selector-has-related">';
				$content .= '<label for="wpv-shortcode-gui-item-selector-post-id-grouprepeated">';
				$content .= '<input type="radio" class="js-wpv-shortcode-gui-item-selector" id="wpv-shortcode-gui-item-selector-post-id-grouprepeated" name="wpv_shortcode_gui_object_id" value="grouprepeated" />';
				$content .= __( 'The post owner of the Types repeatable group field', 'wpv-views' );
				$content .= '</label>';
				$content .= '<div class="wpv-shortcode-gui-item-selector-is-related js-wpv-shortcode-gui-item-selector-is-related" style="display:none">';
				$content .= '<ul class="wpv-advanced-setting wpv-mightlong-list" style="padding-top:15px;margin:5px 0 10px;">';
				foreach ( $relationship_definitions_per_origin[ Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD ] as $relationship_definition  ) {
					$relationship_selectors_factory = new Toolset_Shortcode_Attr_Item_Gui_Factory(
						$relationship_definition, $post_type, 'grouprepeated_object'
					);
					$relationship_selectors = $relationship_selectors_factory->get_options();
					foreach( $relationship_selectors as $relationship_selector_option ) {
						$content .= '<li>' . $relationship_selector_option . '</li>';
					}
				}
				$content .= '</ul>';
				$content .= '</div>';
				$content .= '</li>';
			}

		}

		/**
		 * Specific post selection
		 */

        $content .= '<li class="wpv-shortcode-gui-item-selector-option wpv-shortcode-gui-item-selector-has-related js-wpv-shortcode-gui-item-selector-has-related">';
		$content .= '<label for="wpv-shortcode-gui-item-selector-post-id">';
        $content .= '<input type="radio" class="js-wpv-shortcode-gui-item-selector" id="wpv-shortcode-gui-item-selector-post-id" name="wpv_shortcode_gui_object_id" value="object_id" />';
        $content .= __( 'A specific post (search by title)', 'wpv-views' );
        $content .= '</label>';
        $content .= '<div class="wpv-shortcode-gui-item-selector-is-related js-wpv-shortcode-gui-item-selector-is-related" style="display:none">';
        $content .= '<label for="wpv-shortcode-gui-item-selector-post-id-object_id"></label>';
        $content .= '<select '
			. 'id="wpv-shortcode-gui-item-selector-post-id-object_id" '
			. 'class="js-wpv-shortcode-gui-field-ajax-select2" '
			. 'name="specific_object_id" '
			. 'data-action="' . esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE ) ) . '" '
			. 'data-prefill="' . esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_GET_POST_BY_ID ) ) . '" '
			. 'data-nonce="' . wp_create_nonce( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE ) . '" '
			. 'data-prefill-nonce="' . wp_create_nonce( Toolset_Ajax::CALLBACK_GET_POST_BY_ID ) . '" '
			. 'data-placeholder="' . esc_attr( __( 'Search for a post by title', 'wpv-views' ) ). '"'
			. '>'
			. '</select>';
        $content .= '</div>';
        $content .= '</li>';

	    /**
	     * Specific post selection by ID
	     */
	    $content .= '<li class="wpv-shortcode-gui-item-selector-option wpv-shortcode-gui-item-selector-has-related js-wpv-shortcode-gui-item-selector-has-related">';
	    $content .= '<label for="wpv-shortcode-gui-item-selector-post-id-raw">';
	    $content .= '<input type="radio" class="js-wpv-shortcode-gui-item-selector" id="wpv-shortcode-gui-item-selector-post-id-raw" name="wpv_shortcode_gui_object_id" value="object_id_raw" />';
	    $content .= __( 'A specific post (set by post ID)', 'wpv-views' );
	    $content .= '</label>';
	    $content .= '<div class="wpv-shortcode-gui-item-selector-is-related js-wpv-shortcode-gui-item-selector-is-related" style="display:none">';
	    $content .= '<label for="wpv-shortcode-gui-item-selector-post-id-object_id-raw"></label>';
	    $content .= '<input type="number" placeholder="' . esc_attr( __( 'Please enter post ID', 'wpv-views' ) ) . '" id="wpv-shortcode-gui-item-selector-post-id-object_id-raw" class="regular-text" name="specific_object_id_raw">';
	    $content .= '</div>';
	    $content .= '</li>';

		$content .= '</ul>';
        $content .= '<p class="description">';
        $content .= sprintf(
            __( 'Learn about displaying content from parent and other posts in the %sdocumentation page%s.', 'wpv-views' ),
            '<a href="https://toolset.com/course-lesson/displaying-related-posts/?utm_source=plugin&utm_medium=gui&utm_campaign=blocks" target="_blank">',
            '</a>'
        );
        $content .= '</p>';

        break;
	case 'user':
		$content .= sprintf(
            '<ul id="%s">',
            esc_attr( $id )
        );

        $content .= '<li class="wpv-shortcode-gui-item-selector-option">';
		$content .= '<label for="wpv-shortcode-gui-item-selector-user-id-current">';
        $content .= '<input type="radio" class="js-wpv-shortcode-gui-item-selector" id="wpv-shortcode-gui-item-selector-user-id-current" name="wpv_shortcode_gui_object_id" value="current" checked="checked" />';
        $content .=  __( 'The current user or the one being displayed in a View loop', 'wpv-views' );
        $content .= '</label>';
        $content .= '</li>';

		/**
		 * Specific user selection
		 */

        $content .= '<li class="wpv-shortcode-gui-item-selector-option wpv-shortcode-gui-item-selector-has-related js-wpv-shortcode-gui-item-selector-has-related">';
		$content .= '<label for="wpv-shortcode-gui-item-selector-user-id">';
        $content .= '<input type="radio" class="js-wpv-shortcode-gui-item-selector" id="wpv-shortcode-gui-item-selector-user-id" name="wpv_shortcode_gui_object_id" value="object_id" />';
        $content .= __( 'A specific user', 'wpv-views' );
        $content .= '</label>';
        $content .= '<div class="wpv-shortcode-gui-item-selector-is-related js-wpv-shortcode-gui-item-selector-is-related" style="display:none">';
        //$content .= '<label for="wpv-shortcode-gui-item-selector-user-id-object_id"></label>';
        $content .= '<select '
			. 'id="wpv-shortcode-gui-item-selector-user-id-object_id" '
			. 'class="js-wpv-shortcode-gui-field-ajax-select2" '
			. 'name="specific_object_id" '
			. 'data-action="' . esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_USERS ) ) . '" '
			. 'data-prefill="' . esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_GET_USER_BY_ID ) ) . '" '
			. 'data-nonce="' . wp_create_nonce( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_USERS ) . '" '
			. 'data-prefill-nonce="' . wp_create_nonce( Toolset_Ajax::CALLBACK_GET_USER_BY_ID ) . '" '
			. 'data-placeholder="' . esc_attr( __( 'Search for a user', 'wpv-views' ) ). '"'
			. '>'
			. '</select>';
        $content .= '</div>';
        $content .= '</li>';

		$content .= '</ul>';
        $content .= '<p class="description">';
        $content .= sprintf(
            __( 'Learn about displaying content from specific users in the %sdocumentation page%s.', 'wpv-views' ),
            '<a href="https://toolset.com/course-lesson/displaying-related-posts/?utm_source=plugin&utm_medium=gui&utm_campaign=blocks" target="_blank">',
            '</a>'
        );
        $content .= '</p>';

		break;
    case 'callback':
        if ( isset($data['callback']) && is_callable($data['callback']) ) {
            $content .= call_user_func($data['callback'], $id, $data, $classes, $post_type);
            break;
        } else {
            $content .= sprintf(__('Wrong callback function: %s.', 'wpv-views'), $data['callback']);
        }
        break;
    default:
        break;
    }
    return $content;
}

/**
 * Sanitize additional forced data for a shortcode, used to render its attributes dialog GUI.
 *
 * Sanitize both inital parameters and override parameters passe to the AJAX method used to generate the dialog
 * when adding or editing a shortcode.
 *
 * @param $data array
 *     content		string	Optional. The shortcode content. Sanitize with sanitize_text_field.
 *     attributes	array	Optional. A series of pairs attribute_key->attribute_value, both are sanitized with sanitize_text_field.
 *
 * @since 2.3.0
 */
function wpv_sanitize_shortcode_forced_data( $data ) {

	$data_sanitized = array();

	if ( isset( $data['content'] ) ) {
		$data_sanitized['content'] = sanitize_text_field( $data['content'] );
	}

	if ( isset( $data['attributes'] ) ) {
		if ( is_array( $data['attributes'] ) ) {
			$data_sanitized['attributes'] = array();
			foreach ( $data['attributes'] as $att_key => $att_value ) {
				$att_key_sanitized = sanitize_text_field( $att_key );
				$data_sanitized['attributes'][ $att_key_sanitized ] = sanitize_text_field( $att_value );
			}
		} else {
			$data_sanitized['attributes'] = array();
		}
	}

	return $data_sanitized;

}

add_action('wp_ajax_wpv_shortcode_gui_dialog_create', 'wp_ajax_wpv_shortcode_gui_dialog_create');

/**
 * Render the dialog for setting shortcodes attributes.
 *
 * @since 1.9.0
 * @since 2.3.0 Proper JSON responses.
 * @since 2.3.0 Pass additional $parameters and $overrides GETed values to the shortcode GUI callback,
 *     so we can properly set dialog titles when editing a shortcode with multiple usages.
 */
function wp_ajax_wpv_shortcode_gui_dialog_create() {
    if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wpv_editor_callback' ) ) {
        $data = array(
			'message' => __( 'Security: wrong nonce', 'wpv-views' )
		);
		wp_send_json_error( $data );
    }
    if (
		! isset( $_GET['shortcode'] )
		|| empty( $_GET['shortcode'] )
	) {
        $data = array(
			'message' => __( 'Unknown shortcode', 'wpv-views' )
		);
		wp_send_json_error( $data );
    }

	$shortcode		= sanitize_text_field( $_GET['shortcode'] );
	$parameters		= isset( $_GET['parameters'] ) ? $_GET['parameters'] : array();
	$overrides		= isset( $_GET['overrides'] ) ? $_GET['overrides'] : array();

	$parameters		= wpv_sanitize_shortcode_forced_data( $parameters );
	$overrides		= wpv_sanitize_shortcode_forced_data( $overrides );

	$gui_action		= isset( $_GET['gui_action'] ) ? sanitize_text_field( $_GET['gui_action'] ) : '';

	/**
	 * Get list of shortcodes with GUI data.
	 *
	 * @param array $views_shortcodes
	 *
	 * @since 1.9.0
	 */
	$views_shortcodes_gui_data	= apply_filters( 'wpv_filter_wpv_shortcodes_gui_data', array() );

    if ( ! isset( $views_shortcodes_gui_data[ $shortcode ] ) ) {
        $data = array(
			'message' => __( 'Unknown shortcode', 'wpv-views' )
		);
		wp_send_json_error( $data );
    }

	$shortcode_data = $views_shortcodes_gui_data[ $shortcode ];
	if (
		isset( $shortcode_data['callback'] )
		&& is_callable( $shortcode_data['callback'] )
	) {
		$options = call_user_func( $shortcode_data['callback'], $parameters, $overrides );
	} else {
		$data = array(
			'message' => __( 'Unknown shortcode', 'wpv-views' )
		);
		wp_send_json_error( $data );
	}

    /**
	* Post selection tab
	*/
    if (
		isset( $options['post-selection'] )
		&& $options['post-selection']
	) {
        if ( ! isset($options['attributes'] ) ) {
            $options['attributes'] = array();
        }
		if( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			// m2m disabled
			$options['attributes']['post-selection'] = array(
				'label'  => __( 'Post selection', 'wpv-views' ),
				'header' => __( 'Display data for:', 'wpv-views' ),
				'fields' => array(
					'id' => array(
						'type' => 'post'
					),
				),
			);
		} else {
			// m2m enabled
			$options['attributes']['post-selection'] = array(
				'label'  => __( 'Post selection', 'wpv-views' ),
				'header' => __( 'Display data for:', 'wpv-views' ),
				'fields' => array(
					'item' => array(
						'type' => 'post'
					),
				),
			);
		}
    }

	/**
	* User selection tab
	*/
    if (
		isset( $options['user-selection'] )
		&& $options['user-selection']
	) {
        if ( ! isset($options['attributes'] ) ) {
            $options['attributes'] = array();
        }
        $options['attributes']['user-selection'] = array(
            'label'		=> __( 'User selection', 'wpv-views' ),
            'header'	=> __( 'Display data for:', 'wpv-views' ),
            'fields'	=> array(
                'id'	=> array(
                    'type'	=> 'user'
                ),
            ),
        );
    }

    /**
	* If post_id was passed, get the current post type object
	*/
    $post_id = 0;
    if ( isset( $_GET['post_id'] ) ) {
        $post_id = intval( $_GET['post_id'] );
    }
    $post_type = null;
    if ( $post_id ) {
		$post_type_slug = get_post_type( $post_id );
		if ( ! in_array( $post_type_slug, array( 'view', 'view-template', 'cred-form', 'cred-user-form', 'dd_layouts' ) ) ) {
			$post_type = get_post_type_object( $post_type_slug );
		}
    }

	ob_start();

    echo '<input type="hidden" value="' . esc_attr( $shortcode ) . '" class="wpv-shortcode-gui-shortcode-name js-wpv-shortcode-gui-shortcode-name" />';
	if (
		isset( $options['additional_data'] )
		&& is_array( $options['additional_data'] )
	) {
		foreach ( $options['additional_data'] as $add_data_key => $add_data_value ) {
			echo '<span class="wpv-shortcode-gui-attribute-wrapper js-wpv-shortcode-gui-attribute-wrapper js-wpv-shortcode-gui-attribute-wrapper-for-' . esc_attr( $add_data_key ) . '" data-attribute="' . esc_attr( $add_data_key ) . '" data-type="param">';
			echo '<input name="' . esc_attr( $add_data_key ) . '" value="' . esc_attr( $add_data_value ) . '" disabled="disabled" type="hidden">';
			echo '</span>';
		}
	}

    echo '<div id="js-wpv-shortcode-gui-dialog-tabs" class="toolset-shortcodes__tabs js-toolset-shortcode-gui-tabs js-wpv-shortcode-gui-tabs">';
    $tabs = '';
    $content = '';
    foreach( $options['attributes'] as $group_id => $group_data ) {
		$group_fields = toolset_getarr( $group_data, 'fields', array() );
		$group_content = toolset_getarr( $group_data, 'content', false );

		if (
			empty( $group_fields )
			&& false === $group_content
		) {
			// Some shortcodes might define empty groups for some React-development assumptions.
			// For example, search filter shortcodes are supposed to include a 'display-options' fields group, even if empty.
			continue;
		}

        $tabs .= sprintf(
            '<li><a href="#%s-%s">%s</a></li>',
            esc_attr( $shortcode ),
            esc_attr( $group_id ),
            esc_html( $group_data['label'] )
        );
        $content .= sprintf(
			'<div id="%s-%s">',
			esc_attr( $shortcode ),
			esc_attr( $group_id )
		);
        if ( isset( $group_data['header'] ) ) {
            $content .= sprintf(
				'<h2>%s</h2>',
				esc_html( $group_data['header'] )
			);
        }
        /**
         * add fields
         */
        foreach ( $group_data['fields'] as $key => $data ) {
			$content .= wpv_shortcode_gui_dialog_render_field( $key, $data, $shortcode, $post_type );
		}
		if ( isset( $group_data['content'] ) ) {
			if ( isset( $group_data['content']['hidden'] ) ) {
				$content .= '<span class="wpv-shortcode-gui-content-wrapper js-wpv-shortcode-gui-content-wrapper" style="display:none">';
				$content .= sprintf(
					'<input id="shortcode-gui-content-%s" type="text" class="large-text js-wpv-shortcode-gui-content" />',
					esc_attr( $shortcode )
				);
				$content .= '</span>';
			} else {
				$content .= '<div class="wpv-shortcode-gui-content-wrapper js-wpv-shortcode-gui-content-wrapper">';
				$content .= sprintf(
					'<h3>%s</h3>',
					esc_html( $group_data['content']['label'] )
				);
				$default = '';
				if(
					isset( $group_data['content']['default'] )
					&& !empty( $group_data['content']['default'] )
				) {
					$default = esc_attr( $group_data['content']['default'] );
				}
				if (
					isset( $group_data['content']['type'] )
					&& $group_data['content']['type'] == 'textarea'
				) {
					$content .= sprintf(
						'<textarea id="shortcode-gui-content-%s" type="text" class="large-text js-wpv-shortcode-gui-content">%s</textarea>',
						esc_attr( $shortcode ),
						$default
					);
				} else {
					$default = !empty( $default ) ? 'value="' . $default . '"' : $default;
					$content .= sprintf(
						'<input id="shortcode-gui-content-%s" type="text" class="large-text js-wpv-shortcode-gui-content" %s />',
						esc_attr( $shortcode ),
						$default
					);
				}
				$desc_and_doc = array();
				if ( isset( $group_data['content']['description'] ) ) {
					$desc_and_doc[] = $group_data['content']['description'];
				}
				if ( isset( $group_data['content']['documentation'] ) ) {
					$desc_and_doc[] = sprintf(
						__( 'Specific documentation: %s', 'wpv-views' ),
						$group_data['content']['documentation']
					);
				}
				if ( ! empty( $desc_and_doc ) ) {
					$content .= '<p class="description">' . implode( '<br />', $desc_and_doc ) . '</p>';
				}
				$content .= '</div>';
			}
		}
		$tab_metadata = '';
		if ( isset( $group_data['description'] ) ) {
			$tab_metadata .= '<p class="description">' . esc_html( $group_data['description'] ) . '</p>';
		}
		if ( isset( $group_data['documentation'] ) ) {
			$tab_metadata .= '<p class="description tab-documentation">' . $group_data['documentation'] . '</p>';
		}
		if ( ! empty( $tab_metadata ) ) {
			$content .= '<div class="tab-metadata">' . $tab_metadata . '</div>';
		}
        $content .= '</div>';
    }
    printf(
		'<ul class="js-wpv-shortcode-gui-tabs-list">%s</ul>',
		$tabs
	);
    echo $content;
	echo '<div class="wpv-filter-toolset-messages js-wpv-filter-toolset-messages"></div>';
	echo '</div>';

	$dialog_content = ob_get_clean();
	$data = array(
		'dialog'	=> $dialog_content,
		'title'		=> $options['name']
	);
	wp_send_json_success( $data );
}

// -------------------------------
// Suggest callbacks
// -------------------------------

add_action('wp_ajax_wpv_suggest_wpv_post_body_view_template', 'wpv_suggest_wpv_post_body_view_template');
add_action('wp_ajax_nopriv_wpv_suggest_wpv_post_body_view_template', 'wpv_suggest_wpv_post_body_view_template');

function wpv_suggest_wpv_post_body_view_template() {
	global $wpdb, $sitepress;
	$values_to_prepare = array();
	$wpml_join = $wpml_where = "";
	if (
		isset( $sitepress )
		&& function_exists( 'icl_object_id' )
	) {
		$content_templates_translatable = $sitepress->is_translated_post_type( 'view-template' );
		if ( $content_templates_translatable ) {
			$wpml_current_language = $sitepress->get_current_language();
			$wpml_join = " JOIN {$wpdb->prefix}icl_translations icl_t ";
			$wpml_where = " AND p.ID = icl_t.element_id AND icl_t.language_code = %s AND icl_t.element_type LIKE 'post_%' ";
			$values_to_prepare[] = $wpml_current_language;
		}
	}

	$exclude_loop_templates = '';
	$exclude_loop_templates_ids = wpv_get_loop_content_template_ids();
	// Be sure not to include the current CT when editing one
	if ( isset( $_REQUEST['wpv_suggest_wpv_post_body_view_template_exclude'] ) ) {
		$exclude_loop_templates_ids[] = $_REQUEST['wpv_suggest_wpv_post_body_view_template_exclude'];
	}
	if ( count( $exclude_loop_templates_ids ) > 0 ) {
		$exclude_loop_templates_ids_sanitized = array_map( 'esc_attr', $exclude_loop_templates_ids );
		$exclude_loop_templates_ids_sanitized = array_map( 'trim', $exclude_loop_templates_ids_sanitized );
		// is_numeric + intval does sanitization
		$exclude_loop_templates_ids_sanitized = array_filter( $exclude_loop_templates_ids_sanitized, 'is_numeric' );
		$exclude_loop_templates_ids_sanitized = array_map( 'intval', $exclude_loop_templates_ids_sanitized );
		if ( count( $exclude_loop_templates_ids_sanitized ) > 0 ) {
			$exclude_loop_templates = " AND p.ID NOT IN ('" . implode( "','" , $exclude_loop_templates_ids_sanitized ) . "') ";
		}
	}
	$values_to_prepare[] = 'view-template';
	$values_to_prepare[] = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
	$values_to_prepare[] = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
	$view_tempates_available = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT p.ID, p.post_name, p.post_title
			FROM {$wpdb->posts} p {$wpml_join}
			WHERE p.post_status = 'publish'
			{$wpml_where}
			AND p.post_type = %s
			AND (
				p.post_title LIKE %s
				OR p.post_name LIKE %s
			)
			{$exclude_loop_templates}
			ORDER BY p.post_title
			LIMIT 5",
			$values_to_prepare
		)
	);
	foreach ( $view_tempates_available as $row ) {
		echo $row->post_title . "\n";
	}
	die();
}


add_action('wp_ajax_wpv_suggest_wpv_post_field_name', 'wpv_suggest_wpv_post_field_name');
add_action('wp_ajax_nopriv_wpv_suggest_wpv_post_field_name', 'wpv_suggest_wpv_post_field_name');

// @todo please avoid hidden fields!!
// Then do an array filter on the stored array of hidden fields that should be shown
function wpv_suggest_wpv_post_field_name() {
	global $wpdb;
	$meta_key_q = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
	$cf_keys = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT meta_key
			FROM {$wpdb->postmeta}
			WHERE meta_key LIKE %s
			ORDER BY meta_key
			LIMIT 5",
			$meta_key_q
		)
	);
	foreach ( $cf_keys as $key ) {
		echo $key . "\n";
	}
	die();
}

add_action('wp_ajax_wpv_suggest_wpv_taxonomy_field_name', 'wpv_suggest_wpv_taxonomy_field_name');
add_action('wp_ajax_nopriv_wpv_suggest_wpv_taxonomy_field_name', 'wpv_suggest_wpv_taxonomy_field_name');

// @todo please avoid hidden fields!!
// Then do an array filter on the stored array of hidden fields that should be shown
function wpv_suggest_wpv_taxonomy_field_name() {
	global $wp_version;
	if ( version_compare( $wp_version, '4.4' ) < 0 ) {
		echo '';
		die();
	}
	global $wpdb;
	$meta_key_q = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
	$cf_keys = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT meta_key
			FROM {$wpdb->termmeta}
			WHERE meta_key LIKE %s
			ORDER BY meta_key
			LIMIT 5",
			$meta_key_q
		)
	);
	foreach ( $cf_keys as $key ) {
		echo $key . "\n";
	}
	die();
}

add_action('wp_ajax_wpv_suggest_wpv_user_field_name', 'wpv_suggest_wpv_user_field_name');
add_action('wp_ajax_nopriv_wpv_suggest_wpv_user_field_name', 'wpv_suggest_wpv_user_field_name');

// @todo please avoid hidden fields!!
// Then do an array filter on the stored array of hidden fields that should be shown
function wpv_suggest_wpv_user_field_name() {
	global $wpdb;
	$meta_key_q = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
	$cf_keys = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT meta_key
			FROM {$wpdb->usermeta}
			WHERE meta_key LIKE %s
			ORDER BY meta_key
			LIMIT 5",
			$meta_key_q
		)
	);
	foreach ( $cf_keys as $key ) {
		echo $key . "\n";
	}
	die();
}

/**
* wpv_suggest_form_targets
*
* Suggest for WPML string shortcode context, from a suggest callback
*
* @since 1.4
*/

add_action('wp_ajax_wpv_suggest_form_targets', 'wpv_suggest_form_targets');
add_action('wp_ajax_nopriv_wpv_suggest_form_targets', 'wpv_suggest_form_targets');

function wpv_suggest_form_targets() {
	global $wpdb, $sitepress;
	$trans_join = '';
	$trans_where = '';
	$values_to_prepare = array();
	$title_q = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
	$values_to_prepare[] = $title_q;
	$included_post_type_slugs_where = '';
    $included_post_type_slugs = array();
    $included_post_type_slugs = apply_filters( 'wpv_admin_include_post_type_slugs', $included_post_type_slugs );
	if ( count( $included_post_type_slugs ) > 0 ) {
        $included_post_type_slugs_count = count( $included_post_type_slugs );
		$included_post_type_slugs_placeholders = array_fill( 0, $included_post_type_slugs_count, '%s' );
		$included_post_type_slugs_flat = implode( ",", $included_post_type_slugs_placeholders );
		foreach ( $included_post_type_slugs as $included_post_type_slugs_item ) {
			$values_to_prepare[] = $included_post_type_slugs_item;
		}
		$included_post_type_slugs_where = "AND post_type IN ({$included_post_type_slugs_flat})";
	}
	if ( isset( $sitepress ) && function_exists( 'icl_object_id' ) ) {
		$current_lang_code = $sitepress->get_current_language();
		$trans_join = " JOIN {$wpdb->prefix}icl_translations icl_t ";
		$trans_where = " AND ID = icl_t.element_id AND icl_t.language_code = %s ";
		$values_to_prepare[] = $current_lang_code;
	}
	$results = $wpdb->get_results(
		$wpdb->prepare( "
            SELECT ID, post_title
            FROM {$wpdb->posts} {$trans_join}
            WHERE post_title LIKE '%s'
			{$included_post_type_slugs_where}
			AND post_status='publish'
			{$trans_where}
            ORDER BY post_title ASC
			LIMIT 5",
			$values_to_prepare
		)
	);
	foreach ($results as $row) {
		echo $row->post_title . " [#" . $row->ID . "]\n";
	}
	die();
}

/**
 * Generates the li items for the Post field section of the shortcodes GUI, on demand
 *
 * @since 1.10.0
 * @since 2.3.0 Review the items HTML structure to match the Fields and Views dialog refactor.
 * @since 2.3.2 Return just an array of fields, and build the structure in JavaScript, so this can be reused on other methods.
 */

add_action( 'wp_ajax_wpv_shortcodes_gui_load_post_fields_on_demand', 'wpv_shortcodes_gui_load_post_fields_on_demand' );

function wpv_shortcodes_gui_load_post_fields_on_demand() {
	$cf_keys = apply_filters( 'wpv_filter_wpv_get_postmeta_keys', array() );
	$native_fields = array();
	foreach ( $cf_keys as $cf_key ) {
		if ( ! wpv_is_types_custom_field( $cf_key ) ) {
			$native_fields[] = $cf_key;
		}
	}
	$data = array(
		'fields' => $native_fields
	);
	wp_send_json_success( $data );
}

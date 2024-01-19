<?php

namespace OTGS\Toolset\CRED\Model\FormEditorToolbar;

/**
 * Helper for the post forms content editor toolbar.
 *
 * @since 2.1
 */
abstract class Helper {

    /**
     * Items to populate and return back:
     * basic fields, plus fields belonging to each of the domains.
     *
     * @var array
     *
     * @since 2.1
     */
    protected $items = array();

    /**
     * List of field types that do not support being prefilled with a value or urlparam attribute.
     *
     * @var array
     *
     * @since 2.1
     */
    protected $field_types_without_value_and_url_options = array(
        'post', 'audio', 'video', 'file', 'image', 'checkboxes', 'checkbox', 'skype'
    );

    /**
     * Populate and return the list of items.
     *
     * @return array
     *
     * @since 2.1
     */
    abstract public function populate_items();

    /**
     * Populate the list of basic fields.
     *
     * @since 2.1
     */
    abstract protected function populate_basic_fields();

    /**
     * Populate the options for a field for prefilling with a value or urlparam attribute.
     *
     * @param array $options
     *
     * @return array
     *
     * @since 2.1
     */
    protected function get_value_and_url_options( $options = array() ) {
        $options['valueAndUrl'] = array(
            'type'   => 'group',
            'fields' => array(
                'value' => array(
					/* translators: Label for the option to set a default value on some fields used in frontend forms */
                    'label' => __( 'Field default value', 'wp-cred' ),
                    'type'  => 'text',
					'defaultValue' => '',
					/* translators: Description for the option to set a default value on some fields used in frontend forms */
                    'description' => __( 'Set a default value for this field', 'wp-cred' )
                ),
                'urlparam' => array(
					/* translators: Label for the option to set a value on some fields used in frontend forms, coming from an URL parameter */
                    'label' => __( 'Set default value from an URL parameter', 'wp-cred' ),
                    'type'  => 'text',
					'defaultValue' => '',
					/* translators: Description for the option to set a value on some fields used in frontend forms, coming from an URL parameter */
                    'description' => __( 'Listen to this URL parameter to set the default value', 'wp-cred' )
                )
            )
        );
        return $options;
	}

	/**
	 * Get options for the preview attribute on media fields.
	 *
	 * @param string $field_type
	 * @return array
	 * @since 2.4
	 */
	protected function get_preview_options( $field_type = 'file' ) {
		$options = array(
			'preview' => array(
				/* translators: Label for the option to set the preview format for media fields in frontend forms */
				'label' => __( 'How should this field be previewed', 'wp-cred' ),
				'type' => 'radio',
				'options' => $this->get_preview_mode_options_per_field_type( $field_type ),
				'defaultValue' => $this->get_default_preview_mode_by_field_type( $field_type ),
			)
		);

		if ( 'image' === $field_type ) {
			$options['previewsize'] = array(
				'type' => 'select',
				'options' => array(
					'thumbnail' => __( 'Use an image thumbnail', 'wp-cred' ),
					'full' => __( 'Use the full image', 'wp-cred' ),
				),
				'defaultForceValue' => 'thumbnail',
			);
		}

		return $options;
	}

	/**
	 * Get valid options for the preview attribute based on the media field type.
	 *
	 * @param string $field_type
	 * @return array
	 * @since 2.4
	 */
	protected function get_preview_mode_options_per_field_type( $field_type = 'file' ) {
		$options = array(
			/* translators: Label for the option to preview media fields in frontend forms by displaying the URL of the field value */
			'url' => __( 'As the complete URL of the file', 'wp-cred' ),
			/* translators: Label for the option to preview media fields in frontend forms by displaying the filename of the field value */
			'filename' => __( 'As the filename', 'wp-cred' ),
		);
		if ( 'image' === $field_type ) {
			/* translators: Label for the option to preview image fields in frontend forms by displaying a thumbnail */
			$options['img'] = __( 'As an image HTML tag', 'wp-cred' );
		}
		return $options;
	}

	/**
	 * Get the default value for the preview attribute based on the media field type.
	 *
	 * @param string $field_type
	 * @return string
	 * @since 2.4
	 */
	protected function get_default_preview_mode_by_field_type( $field_type = 'file' ) {
		switch ( $field_type ) {
			case 'image':
				return 'img';
			default:
				return 'filename';
		}
	}

    /**
     * Populate the options for a field for sorting and filtering its options.
     *
     * Used on post selector fields (post reference, or hierarchical, legacy or m2m parents)
     * to sort the available options and filter them by author.
     *
     * For backwards compatibility, post and user forms use the "order" attribute for the orderby query argument,
     * and the "ordering" attribute for the order query argument.
     *
     * @param array $options
     * @param string $orderby_slug
     * @param string $order_slug
     *
     * @return array
     *
     * @since 2.1
     */
    protected function get_sorting_and_author_options( $options = array(), $orderby_slug = 'orderby', $order_slug = 'order' ) {
        $options['sortingGroup'] = array(
            'type'   => 'group',
            'fields' => array(
                $orderby_slug => array(
					/* translators: Label for the criteria to use to sort the set of options for a field */
                    'label' => __( 'Order by', 'wp-cred' ),
                    'type' => 'radio',
                    'options'      => array(
						/* translators: Label of the option to sort a field options by title */
						'title' => __( 'Order options by title', 'wp-cred' ),
						/* translators: Label of the option to sort a field options by ID */
						'ID' => __( 'Order options by ID', 'wp-cred' ),
						/* translators: Label of the option to sort a field options by date */
                        'date' => __( 'Order options by date', 'wp-cred' )
                    ),
                    'defaultValue' => 'title'
                ),
                $order_slug => array(
					/* translators: Label for the direction criteria to use to sort the set of options for a field */
                    'label' => __( 'Order', 'wp-cred' ),
                    'type' => 'radio',
                    'options'      => array(
						/* translators: Label of the option to sort a field options by ascending direction */
						'asc' => __( 'Ascending', 'wp-cred' ),
						/* translators: Label of the option to sort a field options by descending direction */
                        'desc' => __( 'Descending', 'wp-cred' )
                    ),
                    'defaultValue' => 'asc'
                )
            )
        );
        $options['author'] = array(
			/* translators: Label for the option to filter available valus on a frontend form field by author */
            'label' => __( 'Filtering by author', 'wp-cred' ),
            'type' => 'radio',
            'options'      => array(
				/* translators: Label for the option to only include options created by the current user in a frontend form field */
				'$current' => __( 'Get only options by the current author', 'wp-cred' ),
				/* translators: Label for the option to only include options from any author in a frontend form field */
                '' => __( 'Get options by any author', 'wp-cred' )
            ),
            'defaultValue' => ''
        );
        return $options;
    }

	/**
	 * Add labels option to fields
	 *
	 * @since 2.3
	 */
	protected function add_label_option_to_fields() {
		foreach ( $this->items as $type => $fields ) {
			foreach ( $fields as $key => $field ) {
				$this->items[ $type ][ $key ]['options']['label'] = array(
					/* translators: Label of the option to include a label for a field in a frontend form */
					'label' => __( 'Label', 'wp-cred' ),
					'type' => 'text',
					'defaultForceValue' => $field['label'],
					/* translators: Description of the option to include a label for a field in a frontend form, please keep the &lt;label&gt; part */
					'description' => __( 'A &lt;label&gt; included in the Form\'s HTML', 'wp-cred' ),
				);
			}
		}
	}
}

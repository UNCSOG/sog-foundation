<?php

namespace Wpae\AddonAPI;

/**
 * @property-read string $type
 * @property-read string $label
 * @property-read string $key
 * @property-read mixed $value
 * @property-read array $subfields
 */
class PMXE_Addon_Field {
    public array $data;
    public $settings;
    public $elName;
    public $elNameNs = null;
    public $phpFunction = null;

    public $local_value = null;

    // Subfields
    public ?PMXE_Addon_Field $parent = null;
    public $repeater_row_index = null;

    public PMXE_Addon_Resolver $resolver;

    public function __construct(
        array $data,
        PMXE_Addon_Resolver $resolver,
        $settings,
        $elName,
        $elNameNs = null,
        $phpFunction = null,
        // Subfields
        ?PMXE_Addon_Field $parent = null,
        $repeater_row_index = null
    ) {
        $this->data        = $data;
        $this->resolver    = $resolver;
        $this->settings    = $settings;
        $this->elName      = $elName;
        $this->elNameNs    = $elNameNs;
        $this->phpFunction = $phpFunction;
        // Subfields
        $this->parent             = $parent;
        $this->repeater_row_index = $repeater_row_index;
    }

    public function __get( $name ) {
        if ( $name === 'value' ) {
            return $this->getValue();
        }

        return $this->data[ $name ] ?? null;
    }

    public function getImplode() {
        if ( ! $this->parent ) {
            return \XmlExportEngine::$implode;
        }

        $parent_delimiter = $this->parent->getImplode();
        return $parent_delimiter === '|' ? ',' : '|';
    }

    /**
     * @return mixed
     */
    protected function getValue() {
        // Use local value if set and resolve value otherwise.
        return $this->local_value ?? $this->resolver->resolveFieldValue( $this );
    }

    /*
     * Convert the field's value to a string.
     * Only strings can be inserted into a CSV file.
     *
     * @return string
     */
    public function toString() {
        if ( $this->multiple ) {
            return is_array( $this->value ) ?
                implode( $this->getImplode(), $this->value ) :
                $this->value;
        }

        if ( ! is_string( $this->value ) ) {
            return json_encode( $this->value );
        }

        return $this->value;
    }

    /*
     * Format the exported value for safe viewing in the browser
     *
     * @return string
     */
    public function toHtml() {
        return trim( preg_replace( '~[\r\n]+~', ' ', htmlspecialchars( $this->toString() ) ) );
    }

    /*
     * Export Methods
     */

    /**
     * @param array $article
     * @param mixed $value
     * @param \PMXE_XMLWriter $xmlWriter
     *
     * @return array
     */
    public function exportXml( $article, $value, $xmlWriter ) {
        $exported_value        = $this->runPhpFunction( $this->toString() );
        $element_open_response = $xmlWriter->beginElement( $this->elNameNs, $this->elName, null );

        if ( $element_open_response ) {
            $xmlWriter->writeData( $exported_value, $this->elName );
            $xmlWriter->closeElement();
        }

        return $article;
    }


    /**
     * @param array $article
     * @param mixed $value
     *
     * @return array
     */
    public function exportCustomXml( $article, $value, $write = true ) {
        $formatted_values = [];

        if ( is_array( $value ) ) {
            foreach ( $value as $key => $item ) {
                if ( ! is_array( $item ) ) {
                    // Ensure we have valid subelement names.
                    $key                                            = is_numeric( $key ) ? 'el_' . $key : $key;
                    $formatted_values[ $this->elName . '_' . $key ] = maybe_serialize( $item ); // Serialize here as a failsafe for highly complex data.
                }
            }
        } else {
            $formatted_values = $value;
        }

        $exported_value = $this->runPhpFunction( $formatted_values ); // TODO: re-evaluate above defaults with more examples.

        // By default we write the values to $article and return it.
        // But if !$write we return the list of subfields we built instead.
        if ( $write ) {
            wp_all_export_write_article( $article, $this->elName, $exported_value );

            return $article;
        } else {
            return $exported_value;
        }
    }

    /**
     * @param array $article
     * @param string $value
     *
     * @return array
     */
    public function exportCsv( $article, $value, $preview = false ) {
        $exported_value = $this->runPhpFunction( $preview ? $this->toHtml() : $this->toString() );
        wp_all_export_write_article( $article, $this->elName, $exported_value );

        return $article;
    }

    /**
     * This method can modify fields in the current row
     *
     * @param array $article
     * @param \PMXE_XMLWriter $xmlWriter
     * @param bool $preview
     *
     * @return array|mixed
     */
    public function modifyArticle( $article, $xmlWriter, $preview = false ) {
        $is_xml_export        = PMXE_Addon_Exporter::isXmlExport( $xmlWriter );
        $is_custom_xml_export = PMXE_Addon_Exporter::isCustomXmlExport();

        if ( $is_custom_xml_export ) {
            return $this->exportCustomXml( $article, $this->value );
        } else if ( $is_xml_export ) {
            return $this->exportXml( $article, $this->value, $xmlWriter );
        }

        return $this->exportCsv( $article, $this->value, $preview );
    }

    /*
     * This method can add new rows to the export file
     *
     * @param array $baseArticle
     *
     * @return array
     */
    public function appendNewArticles( $baseArticle ) {
        return [];
    }

    /*
     * Add new CSV headers to the export file.
     *
     * @return array
     */
    public function getHeaders() {
        return [
            $this->elName => $this->elName,
        ];
    }

    public function getInvertedDelimiter() {
        return apply_filters(
            'wp_all_export_repeater_delimiter',
            \XmlExportEngine::$exportOptions['delimiter'] == ',' ? '|' : ',', \XmlExportEngine::$exportID
        );
    }

    public function getSubfieldKey( $subfield ) {
        return $this->elName . '_' . $subfield['key'];
    }

    public function runPhpFunction( $value ) {
        return pmxe_filter( $value, $this->phpFunction );
    }

    /**
     * Used by WP All Export to generate the import template to Bulk Edit/Migrate.
     *
     * @param array $field
     * @param string $name
     * @param string $field_tpl_key
     * @param string $implode_delimiter
     * @param boolean $is_xml_template
     *
     * @return string
     */
    public static function getImportTemplate($field, $name, $field_tpl_key, $implode_delimiter, $is_xml_template) {
        return '{' . $field_tpl_key . '}';
    }

    /**
     * Get the field's class
     *
     * @param array $field
     * @param mixed $settings
     * @param string $elName
     * @param string|null $elNameNs
     * @param string|null $phpFunction
     * @param PMXE_Addon_Field|null $parent
     * @param mixed|null $repeater_row_index
     *
     * @return PMXE_Addon_Field
     */
    public static function from(
        $field,
        PMXE_Addon_Resolver $resolver,
        $settings,
        $elName,
        $elNameNs = null,
        $phpFunction = null,
        // Subfields
        PMXE_Addon_Field $parent = null,
        $repeater_row_index = null,
        $resolve = true
    ) {
	    $extra_fields = $resolver->addon->fields;
        $class        = getFieldClass( $field, $extra_fields );

        if ( $resolve ) {
            $class = $resolver->addon->resolveFieldClass( $field, $class );
        }

        return new $class(
            $field,
            $resolver,
            $settings,
            $elName,
            $elNameNs,
            $phpFunction,
            $parent,
            $repeater_row_index,
        );
    }
}

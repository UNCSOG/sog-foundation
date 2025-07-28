<?php

namespace Wpae\AddonAPI;

class PMXE_Addon_Repeater_Field extends PMXE_Addon_Field {

    public function getValue() {
        $val = parent::getValue() ?: [];

        return array_values( $val );
    }

    public function isOnePerLine() {
        return $this->settings['repeater_field_item_per_line'] ?? false;
    }

    public function shouldFillEmptyColumns() {
        return $this->settings['repeater_field_fill_empty_columns'] ?? false;
    }

    public function getSubField( $key, $index ) {
        foreach ( $this->subfields as $subfield ) {
            if ( $subfield['key'] === $key ) {
                return PMXE_Addon_Field::from(
                    $subfield,
                    $this->resolver,
                    $this->settings,
                    $this->elName . '_' . $key,
                    $this->elNameNs,
                    $this->phpFunction,
                    $this,
                    $index,
                );
            }
        }

        return null;
    }

    public function getArticleByIndex( $row, $rowIndex ) {
        $formatted_row = [];
        foreach ( $row as $itemName => $itemValue ) {
            $subfield = $this->getSubField( $itemName, $rowIndex );
            if ( ! $subfield ) {
                continue;
            } // There could be extraneous data in the row that is not a subfield.
            $formatted_row[ $subfield->elName ] = $subfield->toString();
        }

        return $formatted_row;
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
     * @return array
     */
    public static function getImportTemplate( $field, $name, $field_tpl_key, $implode_delimiter, $is_xml_template ) {
        if ( $is_xml_template ) {
            $template = [
                'mode'    => 'variable-xml',
                'foreach' => '{' . $field_tpl_key . '/row}',
                'rows'    => []
            ];
        } else {
            $template = [
                'mode'      => 'variable-csv',
                'separator' => $implode_delimiter,
                'rows'      => []
            ];
        }

		// We need to use this inverted delimiter for the sub fields so they don't use the same delimiter as the repeater section itself.
		$inverted_delimiter = $implode_delimiter == ',' ? '|' : ',';

        if ( ! empty( $field['subfields'] ) ) {
            foreach ( $field['subfields'] as $sub_field ) {
                $sub_field_tpl_key  = $name . '_' . strtolower( $sub_field['key'] );
                $sub_field_class    = getFieldClass($sub_field);
                $sub_field_template = $sub_field_class::getImportTemplate($sub_field, $sub_field['key'], $sub_field_tpl_key, $inverted_delimiter, $is_xml_template );
                $template['rows']['0'][$sub_field['key']] = $sub_field_template;

	            if ( is_subclass_of( $sub_field_class, '\Wpae\AddonAPI\PMXE_Addon_Switcher_Field' ) ) {

		            add_filter("pmxe_get_{$field['addon']}_addon_api_template_options_".$field['key'], function($templateOptions) use ($field,$sub_field){
			            $templateOptions[ $sub_field['addon'] . '_switchers' ][ $field['key'] ]['rows']['0'][ $sub_field['key'] ] = 'no';
						return $templateOptions;
		            });
	            }

            }
        }

        return $template;
    }

    /*
     * Empty on purpose, repeaters are handled differently in `modifyArticle` and `appendNewArticles` below.
     */
    public function toString() {
        return '';
    }

    public function exportXml( $article, $rows, $xmlWriter ) {
        $elName = normalizeElementName( $this->elName );
        $xmlWriter->beginElement( $this->elNameNs, $elName, null );

        foreach ( $rows as $rowIndex => $row ) {
            $xmlWriter->startElement( "row" );

            foreach ( $row as $itemName => $itemValue ) {
                $subfield = $this->getSubField( $itemName, $rowIndex );
                if ( ! $subfield ) {
                    continue;
                }
                $subfield_value = $subfield->toString();
                $subfield->exportXml( $article, $subfield_value, $xmlWriter );
            }

            $xmlWriter->closeElement();
        }

        $xmlWriter->closeElement();

        return $article;
    }

    public function exportCsv($article, $value, $preview = false)
    {
        $rows      = $this->value;
        $delimiter = $this->getInvertedDelimiter();

        // Create one row per subfield value
        if ( $this->isOnePerLine() ) {
            $first_row     = array_shift( $rows );
            $formatted_row = $this->getArticleByIndex( $first_row, 0 );

            return array_merge( $article, $formatted_row );
        }

        // Merge all values into one row per subfield
        $formatted_row = [];

        foreach ( $rows as $rowIndex => $row ) {
            foreach ( $row as $itemName => $itemValue ) {
                $subfield = $this->getSubField( $itemName, $rowIndex );
                if ( ! $subfield ) {
                    continue;
                }
                $formatted_row[ $subfield->elName ][] =  $preview ? $subfield->toHtml() : $subfield->toString();
            }
        }

        foreach ( $formatted_row as $element_name => $value ) {
            wp_all_export_write_article( $article, $element_name, implode( $delimiter, $value ) );
        }

        return $article;
    }

    public function modifyArticle( $article, $xmlWriter, $preview = false ) {
        $rows                 = $this->value;
        $is_xml_export        = PMXE_Addon_Exporter::isXmlExport( $xmlWriter );
        $is_custom_xml_export = PMXE_Addon_Exporter::isCustomXmlExport();

        // Custom XML exports are handled differently.
        if ( $is_custom_xml_export ) {
            return $this->exportCustomXml( $article, serialize( $rows ) );
        }

        if ( $is_xml_export ) {
            $this->exportXml( $article, $rows, $xmlWriter );

            return $article;
        }

        return $this->exportCsv( $article, $this->value, $preview );
    }

    /*
     * Duplicate the base article for each row in the repeater.
     */
    public function appendNewArticles( $baseArticle ) {
        if ( ! $this->isOnePerLine() ) {
            return [];
        }

        $rows     = $this->value;
        $articles = [];
        array_shift( $rows );

        foreach ( $rows as $rowIndex => $row ) {
            $formatted_row = $this->getArticleByIndex( $row, $rowIndex + 1 );

            $articles[] = [
                'content'  => $this->shouldFillEmptyColumns() ?
                    array_merge( $baseArticle, $formatted_row ) :
                    $formatted_row,
                'settings' => $this->settings
            ];
        }

        return $articles;
    }

    /*
     * Add subfields as headers to the export file.
     */
    public function getHeaders() {
        $headers = [
            "-{$this->elName}",
        ];

        foreach ( $this->subfields as $subfield ) {
            $headers[] = $this->elName . '_' . $subfield['key'];
        }

        return $headers;
    }
}

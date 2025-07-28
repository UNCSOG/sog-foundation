<?php

namespace Wpae\AddonAPI;

class PMXE_Addon_Exporter {
    public $appended_articles = [];
    public PMXE_Addon_Base $addon;

    public function __construct( PMXE_Addon_Base $addon ) {
        $this->addon = $addon;
    }

    public static function isCustomXmlExport() {
        $options = \XmlExportEngine::$exportOptions;

        return $options['export_to'] == 'xml' && in_array( $options['xml_template_type'], [ 'custom' ] );
    }

    public static function isXmlExport( $xmlWriter ) {
        $options = \XmlExportEngine::$exportOptions;

        return ! empty( $xmlWriter ) && $options['export_to'] == 'xml' &&
               ! in_array( $options['xml_template_type'], [ 'custom', 'XmlGoogleMerchants' ] );
    }

    public function getFieldSettings( $exportOptions, $id ) {
        if ( empty( $exportOptions['cc_settings'][ $id ] ) ) {
            return false;
        }

        $data = json_decode( $exportOptions['cc_settings'][ $id ], true );
        if ( $data ) {
            return $data;
        }

        return $exportOptions['cc_settings'][ $id ];
    }

    /* 
     * Called by the Engine to export a specific field
     */
    public function run(
        $article,
        $fieldData,
        $exportOptions,
        $ID,
        $entry,
        $entryID,
        $xmlWriter,
        $elementName,
        $elementNameNs,
        $phpFunction,
        $preview
    ) {
        $settings = $this->getFieldSettings( $exportOptions, $ID );
        $resolver = new PMXE_Addon_Resolver( $this->addon, $entry, $entryID );

        $field = PMXE_Addon_Field::from(
            $fieldData,
            $resolver,
            $settings,
            $elementName,
            $elementNameNs,
            $phpFunction
        );

        // Change the value of an field inside the article
        $article = $field->modifyArticle( $article, $xmlWriter, $preview );

        // Add new articles to the export file (usually for repeater fields)
        if ( isset($exportOptions['export_to']) && $exportOptions['export_to'] == 'csv' ) {
            $new_articles = $field->appendNewArticles( $article );

            if ( ! empty( $new_articles ) ) {
                $this->appended_articles = array_merge( $this->appended_articles, $new_articles );
            }
        }

        return $article;
    }

    // Append the new articles to the export file
    public function filterCsvRows( $articles, $options, $exportId ) {
        if ( ! empty( $this->appended_articles ) && $options['export_to'] == 'csv' ) {
            $base_article = $articles[ count( $articles ) - 1 ];

            foreach ( $this->appended_articles as $article ) {
                if ( $article['settings']['repeater_field_fill_empty_columns'] ) {
                    foreach ( $article['content'] as $key => $value ) {
                        unset( $base_article[ $key ] );
                    }

                    $articles[] = @array_merge( $base_article, $article['content'] );
                } else {
                    $articles[] = $article['content'];
                }
            }

            $this->appended_articles = [];
        }

        return $articles;
    }

    public function getHeaders(
        $headers,
        $fieldData,
        $exportOptions,
        $entryID,
        $elementName
    ) {
        $settings = $this->getFieldSettings( $exportOptions, $entryID );
        $resolver = new PMXE_Addon_Resolver( $this->addon, null, null );

        $field = PMXE_Addon_Field::from(
            $fieldData,
            $resolver,
            $settings,
            $elementName
        );

        $field_headers = $field->getHeaders();
        $field_headers = is_array( $field_headers ) ? $field_headers : [ $field_headers ];

        // Append the field's headers if present
        if ( ! empty( $field_headers ) ) {
            foreach ( $field_headers as $header ) {
                // Remove the header if it starts with a dash
                if ( strpos( $header, '-' ) === 0 && in_array( substr( $header, 1 ), $headers ) ) {
                    $header_index = array_search( substr( $header, 1 ), $headers );
                    if ( $header_index !== false ) {
                        unset( $headers[ $header_index ] );
                    }
                } else if ( ! in_array( $header, $headers ) ) {
                    $headers[] = $header;
                }
            }
        }

        return $headers;
    }
}

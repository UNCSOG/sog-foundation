<?php

namespace Wpae\AddonAPI;

class PMXE_Addon_Media_Field extends PMXE_Addon_Field {

    public function getMediaId( $value ) {
        if ( is_numeric( $value ) ) {
            return $value;
        } elseif ( is_array( $value ) ) {
            if ( isset( $value['id'] ) ) {
                return $value['id'];
            }
            if ( isset( $value['ID'] ) ) {
                return $value['ID'];
            }

            return '';
        } elseif ( is_string( $value ) ) {
            $id = attachment_url_to_postid( $value );

            return $id > 0 ? $id : '';
        }

        return '';
    }

    public function getMediaUrl( $value ) {
        return wp_get_attachment_url( $this->getMediaId( $value ) );
    }

    public function getFileName( $value ) {
        $url = $this->getMediaUrl( $value );
        if ( empty( $url ) ) {
            return '';
        }
        $path = parse_url( $url, PHP_URL_PATH );

        return basename( $path );
    }

    public function toString() {
        $format = $this->settings['value_format'] ?? 'url';

        switch ( $format ) {
            case 'id':
                return $this->getMediaId( $this->value );
            case 'filename':
                return $this->getFileName( $this->value );
            default:
                return $this->getMediaUrl( $this->value );
        }
    }

    public function exportCustomXml( $article, $value, $write = true ) {

        $this->local_value = $value;

        $formatted_values = $this->toString();

        $exported_value = $this->runPhpFunction( $formatted_values );

        // By default we write the values to $article and return it.
        // But if !$write we return the list of subfields we built instead.
        if ( $write ) {
            wp_all_export_write_article( $article, $this->elName, $exported_value );

            return $article;
        } else {
            return $exported_value;
        }
    }

	public static function getImportTemplate( $field, $name, $field_tpl_key, $implode_delimiter, $is_xml_template ) {
		return [
			'search_in_media' => 1,
			'url'         => '{' . $field_tpl_key . '}'
		];
	}
}

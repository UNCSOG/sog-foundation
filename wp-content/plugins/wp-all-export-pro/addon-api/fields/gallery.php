<?php

namespace Wpae\AddonAPI;

class PMXE_Addon_Gallery_Field extends PMXE_Addon_Media_Field {

	public function toString() {
		$format = $this->settings['value_format'] ?? 'url';
		$urls   = is_string( $this->value ) ? explode( ',', $this->value ) : $this->value;

		if ( empty( $urls ) ) {
			return '';
		}

		$urls = array_map( function ( $item ) use ( $format ) {
			switch ( $format ) {
				case 'id':
					return $this->getMediaId( $item );
				case 'filename':
					return $this->getFileName( $item );
				default:
					return $this->getMediaUrl( $item );
			}
		}, $urls );

		return implode( $this->getImplode(), $urls );
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
			'delim'           => $implode_delimiter,
			'gallery'         => '{' . $field_tpl_key . '}'
		];
	}
}

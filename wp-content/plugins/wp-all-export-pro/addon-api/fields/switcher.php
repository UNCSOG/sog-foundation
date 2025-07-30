<?php

namespace Wpae\AddonAPI;

class PMXE_Addon_Switcher_Field extends PMXE_Addon_Field {

	public static function getImportTemplate( $field, $name, $field_tpl_key, $implode_delimiter, $is_xml_template ) {
		if ( $is_xml_template ) {
			if ( $implode_delimiter == "|" ) {
				$field_template = '[str_replace("|", ",",{' . $field_tpl_key . '})]';
			} else {
				$field_template = '{' . $field_tpl_key . '}';
			}
		} else {
			$field_tpl_key = str_replace( "[1]", "", $field_tpl_key );

			if ( $implode_delimiter == "|" ) {
				$field_template = '[str_replace("|", ",",{' . $field_tpl_key . '[1]})]';
			} else {
				$field_template = '{' . $field_tpl_key . '[1]}';
			}
		}

		return $field_template;
	}

}
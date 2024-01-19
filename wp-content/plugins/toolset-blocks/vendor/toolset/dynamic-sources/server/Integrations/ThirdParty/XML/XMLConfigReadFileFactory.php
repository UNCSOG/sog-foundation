<?php

namespace Toolset\DynamicSources\Integrations\ThirdParty\XML;

/**
 * Factory for the XMLConfigReadFile class.
 *
 * @codeCoverageIgnore
 */
class XMLConfigReadFileFactory {
	/** @var XMLConfigValidate */
	private $xml_config_validate;

	/** @var XML2Array */
	private $xml_2_array;

	/**
	 * XMLConfigReadFileFactory constructor.
	 *
	 * @param XMLConfigValidate $xml_config_validate
	 * @param XML2Array         $xml_2_array
	 */
	public function __construct( XMLConfigValidate $xml_config_validate, XML2Array $xml_2_array ) {
		$this->xml_config_validate = $xml_config_validate;
		$this->xml_2_array = $xml_2_array;
	}

	/**
	 * Creates an "XMLConfigReadFile" object.
	 *
	 * @param string $file
	 *
	 * @return XMLConfigReadFile
	 */
	public function create_xml_config_read_file( $file ) {
		return new XMLConfigReadFile( $file, $this->xml_config_validate, $this->xml_2_array );
	}
}

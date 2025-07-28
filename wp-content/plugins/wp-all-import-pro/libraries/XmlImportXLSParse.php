<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class PMXI_XLSParser{

	public $csv_path;	

	public $_filename;	

	public $targetDir;

	public $xml;

	public function __construct($path, $targetDir = false){

		$this->_filename = $path;
		
		$wp_uploads = wp_upload_dir();		

		$this->targetDir = ( ! $targetDir ) ? wp_all_import_secure_file($wp_uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::UPLOADS_DIRECTORY ) : $targetDir;
	}

	public function parse(){		

        $tmpname = wp_unique_filename($this->targetDir,  preg_replace('%\W(xls|xlsx)$%i', ".csv", basename($this->_filename)));
        
        $this->csv_path = $this->targetDir  . '/' . wp_all_import_url_title($tmpname);               

        return $this->toXML();
	}

	protected function toXML(){

		// Include the PhpSpreadsheet library (autoloaded by Composer)
		$objSpreadsheet = IOFactory::load($this->_filename);

		// Allow filters to modify the Spreadsheet object
		$objSpreadsheet = apply_filters('wp_all_import_phpexcel_object', $objSpreadsheet, $this->_filename);

		// Set the CSV delimiter; allow filters to modify it
		$spreadsheetDelimiter = ",";
		$spreadsheetDelimiter = apply_filters('wp_all_import_phpexcel_delimiter', $spreadsheetDelimiter, $this->_filename);

		// Create a CSV writer and set the settings
		$objWriter = IOFactory::createWriter($objSpreadsheet, 'Csv');
		$objWriter->setDelimiter($spreadsheetDelimiter)
		          ->setEnclosure('"')
		          ->setLineEnding("\r\n")
		          ->setSheetIndex(0)
		          ->save($this->csv_path);

        include_once(PMXI_Plugin::ROOT_DIR . '/libraries/XmlImportCsvParse.php');

        $this->xml = new PMXI_CsvParser( array( 'filename' => $this->csv_path, 'targetDir' => $this->targetDir ) );

        @unlink($this->csv_path);

		return $this->xml->xml_path;

	}
}
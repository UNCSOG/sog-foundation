<?php

namespace Wpae\Integrations;

class CodeBox
{
	
	static function isUsingCodeBox($functions)
	{
		return (strpos($functions, '\Wpae\Integrations\CodeBox::runSnippet(') !== false);
	}
	
	/**
	 * @return boolean
	 */
	static function isCodeBoxInstalled()
	{
		return class_exists('\Wpcb2\Api\Api');
	}
	
	// API for WPCodeBox To build
	/**
	 * @param string $code
	 * @return int
	 */
	static function saveSnippetToCodeBox($code)
	{
		if( !self::isCodeBoxInstalled() ) {
			return 0;
		}

		$wpcbApi = new \Wpcb2\Api\Api();

		try {
			$snippetId = $wpcbApi->createSnippet(
				[
					'code' => $code,
					'codeType' => ['value' => 'php'],
					'minify' => false,
					'title' => 'WP All Export Function Editor ' . date('Y-m-d H:i:s'),
					'priority' => 10,
					'conditions' => [],
					'description' => 'This code has been copied from WP All Export\'s Function Editor. It will be called automatically when any export runs. Do not delete.',
					'location' => '',
					'runType' => ['value' => 'never'],
				]
			);
			return $snippetId;
		} catch (\Exception $e) {
			return 0;
		}
	}

	/**
	 * @param int $id
	 */
	static function runSnippet($id)
	{
		if( !self::isCodeBoxInstalled() ) {
			return 0;
		}
		
		$wpcbApi = new \Wpcb2\Api\Api();
		$wpcbApi->runSnippet($id);
	}
	
	static function revertToFunctionsFile(){
		$uploads   = wp_upload_dir();
		$functions = $uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_EXPORT_UPLOADS_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'functions.php';
		$functions = apply_filters( 'export_functions_file_path', $functions );
		$backupFunctions = str_replace('.php', '_backup.php', $functions);
		
		if( file_exists( $backupFunctions ) ){
			rename( $backupFunctions, $functions );
		}
	}
	
	static function requireFunctionsFile(){
		$uploads   = wp_upload_dir();
		$functions = $uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_EXPORT_UPLOADS_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'functions.php';
		$functions = apply_filters( 'export_functions_file_path', $functions );
		$backupFunctions = str_replace('.php', '_backup.php', $functions);

		if( file_exists( $functions ) ){
			$content = file_get_contents( $functions );
			if( self::isUsingCodeBox($content) && !self::isCodeBoxInstalled() ){
				if( file_exists( $backupFunctions ) ){
					require_once $backupFunctions;
				}
			}else {
				require_once $functions;
			}
		}
	}
}
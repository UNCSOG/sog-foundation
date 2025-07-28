<?php

class PMXE_Download
{

	static public function zip($file_name)
	{
		self::sendFile('Content-Type: application/zip; Content-Length: '. filesize($file_name), $file_name);
	}

	static public function xls($file_name)
	{
		self::sendFile("Content-Type: application/vnd.ms-excel; charset=UTF-8", $file_name);
	}

    static public function xlsx($file_name)
    {
        self::sendFile("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8", $file_name);
    }

	static public function csv($file_name)
	{
       self::sendFile("Content-Type: text/plain; charset=UTF-8", $file_name);
	}

	static public function txt($file_name)
	{
	    self::sendFile("Content-Type: text/plain; charset=UTF-8", $file_name);
	}

	static public function xml($file_name)
	{
        self::sendFile("Content-Type: application/xhtml+xml; charset=UTF-8", $file_name);
	}

	static public function sendFile($header, $file_name)
    {
        // If we are testing don't send it as an attachment.
        if (php_sapi_name() != 'cli-server') {
            header($header);
            header("Content-Disposition: attachment; filename=\"" . basename($file_name) . "\"");
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
        }
        while (ob_get_level()) {
            ob_end_clean();
        }

        readfile($file_name);
        die;
    }

}
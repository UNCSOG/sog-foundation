<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

function pmxe_prepend($string, $orig_filename)
{
    $context = stream_context_create();
    $orig_file = fopen($orig_filename, 'r', 1, $context);

    $temp_filename = tempnam(sys_get_temp_dir(), 'php_prepend_');
    file_put_contents($temp_filename, $string);
    file_put_contents($temp_filename, $orig_file, FILE_APPEND);

    fclose($orig_file);
    unlink($orig_filename);
    rename($temp_filename, $orig_filename);
}

function pmxe_pmxe_after_export($export_id, $export, $file = false)
{
    if (!empty(PMXE_Plugin::$session) and PMXE_Plugin::$session->has_session()) {
        PMXE_Plugin::$session->set('file', '');
        PMXE_Plugin::$session->save_data();
    }

    if (!$export->isEmpty()) {

        $export->set(
            array(
                'registered_on' => current_time('mysql', 1),
            )
        )->save();

        $splitSize = $export->options['split_large_exports_count'];

        $exportOptions = $export->options;
        // remove previously genereted chunks
        if (!empty($exportOptions['split_files_list']) and !$export->options['creata_a_new_export_file']) {
            foreach ($exportOptions['split_files_list'] as $file) {
                @unlink($file);
            }
        }

        $is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

        if (!$is_secure_import) {
            $filepath = get_attached_file($export->attch_id);
        } else {
            $filepath = wp_all_export_get_absolute_path($export->options['filepath']);
        }

        $is_export_csv_headers = apply_filters('wp_all_export_is_csv_headers_enabled', true, $export->id);

        if (isset($export->options['include_header_row'])) {
            $is_export_csv_headers = $export->options['include_header_row'];
        }

        $removeHeaders = false;

        $removeHeaders = apply_filters('wp_all_export_remove_csv_headers', $removeHeaders, $export->id);

        // Remove headers row from CSV file
        if ((empty($is_export_csv_headers) && @file_exists($filepath) && $export->options['export_to'] == 'csv' && $export->options['export_to_sheet'] == 'csv') || $removeHeaders) {

            $tmp_file = str_replace(basename($filepath), 'iteration_' . basename($filepath), $filepath);
            copy($filepath, $tmp_file);
            $in = fopen($tmp_file, 'r');
            $out = fopen($filepath, 'w');

            $headers = fgetcsv($in, 0, XmlExportEngine::$exportOptions['delimiter']);

            if (is_resource($in)) {
                $lineNumber = 0;
                while (!feof($in)) {
                    $data = fgetcsv($in, 0, XmlExportEngine::$exportOptions['delimiter']);
                    if (empty($data)) continue;
                    $data_assoc = array_combine($headers, array_values($data));
                    $line = array();
                    foreach ($headers as $header) {
                        $line[$header] = (isset($data_assoc[$header])) ? $data_assoc[$header] : '';
                    }
                    if (!$lineNumber && XmlExportEngine::$exportOptions['include_bom']) {
                        fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                        XmlCsvExport::getCsvWriter()->writeCsv($out, $line, XmlExportEngine::$exportOptions['delimiter']);
                    } else {
                        XmlCsvExport::getCsvWriter()->writeCsv($out, $line, XmlExportEngine::$exportOptions['delimiter']);
                    }
                    apply_filters('wp_all_export_after_csv_line', $out, XmlExportEngine::$exportID);
                    $lineNumber++;
                }
                fclose($in);
            }
            fclose($out);
            @unlink($tmp_file);
        }

        $preCsvHeaders = false;
        $preCsvHeaders = apply_filters('wp_all_export_pre_csv_headers', $preCsvHeaders, $export->id);

        if ($preCsvHeaders) {
            pmxe_prepend($preCsvHeaders . "\n", $filepath);
        }

        // Split large exports into chunks
        if ($export->options['split_large_exports'] and $splitSize < $export->exported) {

            $exportOptions['split_files_list'] = array();

            if (@file_exists($filepath)) {

                switch ($export->options['export_to']) {
                    case 'xml':

                        require_once PMXE_ROOT_DIR . '/classes/XMLWriter.php';

                        switch ($export->options['xml_template_type']) {
                            case 'XmlGoogleMerchants':
                            case 'custom':
                                // Determine XML root element
                                $main_xml_tag = false;
                                preg_match_all("%<[\w]+[\s|>]{1}%", $export->options['custom_xml_template_header'], $matches);
                                if (!empty($matches[0])) {
                                    $main_xml_tag = preg_replace("%[\s|<|>]%", "", array_shift($matches[0]));
                                }
                                // Determine XML recond element
                                $record_xml_tag = false;
                                preg_match_all("%<[\w]+[\s|>]{1}%", $export->options['custom_xml_template_loop'], $matches);
                                if (!empty($matches[0])) {
                                    $record_xml_tag = preg_replace("%[\s|<|>]%", "", array_shift($matches[0]));
                                }

                                $xml_header = PMXE_XMLWriter::preprocess_xml($export->options['custom_xml_template_header']);
                                $xml_footer = PMXE_XMLWriter::preprocess_xml($export->options['custom_xml_template_footer']);

                                break;

                            default:
                                $main_xml_tag = apply_filters('wp_all_export_main_xml_tag', $export->options['main_xml_tag'], $export->id);
                                $record_xml_tag = apply_filters('wp_all_export_record_xml_tag', $export->options['record_xml_tag'], $export->id);
                                $xml_header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . "\n" . "<" . $main_xml_tag . ">";
                                $xml_footer = "</" . $main_xml_tag . ">";
                                break;

                        }

                        $records_count = 0;
                        $chunk_records_count = 0;
                        $fileCount = 1;

                        $feed = $xml_header;

                        if ($export->options['xml_template_type'] == 'custom') {
                            $outputFileTemplate = str_replace(basename($filepath), str_replace('.xml', '', basename($filepath)) . '-{FILE_COUNT_PLACEHOLDER}.xml', $filepath);
                            $exportOptions['split_files_list'] = wp_all_export_break_into_files($record_xml_tag, -1, $splitSize, file_get_contents($filepath), null, $outputFileTemplate);

                            // Remove first file which just contains the empty data tag
                            @unlink($exportOptions['split_files_list'][0]);
                            array_shift($exportOptions['split_files_list']);
                        } else {
                            $file = new PMXE_Chunk($filepath, array('element' => $record_xml_tag, 'encoding' => 'UTF-8'));
                            // loop through the file until all lines are read
                            while ($xml = $file->read()) {

                                if (!empty($xml)) {
                                    $records_count++;
                                    $chunk_records_count++;
                                    $feed .= $xml;
                                }

                                if ($chunk_records_count == $splitSize or $records_count == $export->exported) {
                                    $feed .= "\n" . $xml_footer;
                                    $outputFile = str_replace(basename($filepath), str_replace('.xml', '', basename($filepath)) . '-' . $fileCount++ . '.xml', $filepath);
                                    file_put_contents($outputFile, $feed);
                                    if (!in_array($outputFile, $exportOptions['split_files_list']))
                                        $exportOptions['split_files_list'][] = $outputFile;
                                    $chunk_records_count = 0;
                                    $feed = $xml_header;
                                }
                            }
                        }

                        break;
                    case 'csv':
                        $in = fopen($filepath, 'r');

                        $rowCount = 0;
                        $fileCount = 1;
                        $headers = fgetcsv($in);
                        while (!feof($in)) {
                            $data = fgetcsv($in);
                            if (empty($data)) continue;
                            if (($rowCount % $splitSize) == 0) {
                                if ($rowCount > 0) {
                                    fclose($out);
                                }
                                $outputFile = str_replace(basename($filepath), str_replace('.csv', '', basename($filepath)) . '-' . $fileCount++ . '.csv', $filepath);
                                if (!in_array($outputFile, $exportOptions['split_files_list']))
                                    $exportOptions['split_files_list'][] = $outputFile;

                                $out = fopen($outputFile, 'w');
                            }
                            if ($data) {
                                if (($rowCount % $splitSize) == 0) {
                                    XmlCsvExport::getCsvWriter()->writeCsv($out, $headers, XmlExportEngine::$exportOptions['delimiter']);
                                }
                                XmlCsvExport::getCsvWriter()->writeCsv($out, $data, XmlExportEngine::$exportOptions['delimiter']);
                            }
                            $rowCount++;
                        }
                        fclose($in);
                        fclose($out);

                        // convert splitted files into XLS format
                        if (!empty($exportOptions['split_files_list']) && !empty($export->options['export_to_sheet']) and $export->options['export_to_sheet'] != 'csv') {
	                        foreach ($exportOptions['split_files_list'] as $key => $file) {
		                        $reader = IOFactory::createReader('Csv');
		                        // If the files use a delimiter other than a comma (e.g., a tab), then configure the reader
		                        $reader->setDelimiter($export->options['delimiter']);
		                        // Load the file into a Spreadsheet object
		                        $spreadsheet = $reader->load($file);
		                        $enableRtl = apply_filters('wp_all_export_enable_rtl', false, $export->id);

		                        if ($enableRtl) {
			                        $spreadsheet->getActiveSheet()->setRightToLeft(true);
		                        }

		                        switch ($export->options['export_to_sheet']) {
			                        case 'xls':
				                        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
				                        $writer->save(str_replace(".csv", ".xls", $file));
				                        $exportOptions['split_files_list'][$key] = str_replace(".csv", ".xls", $file);
				                        break;
			                        case 'xlsx':
				                        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
				                        $writer->save(str_replace(".csv", ".xlsx", $file));
				                        $exportOptions['split_files_list'][$key] = str_replace(".csv", ".xlsx", $file);
				                        break;
		                        }
		                        @unlink($file);
	                        }
                        }

                        break;

                    default:

                        break;
                }

                $export->set(array('options' => $exportOptions))->save();
            }
        }

        if($export->isRte()) {

            $file_data = [];
            $row_data = [];
            if ($export->options['export_to'] == 'csv') {
                $f = fopen($filepath, 'r');

                $headers = fgetcsv($f, null, $export->options['delimiter']);

                while(!feof($f)) {
                    $data = fgetcsv($f, null, $export->options['delimiter']);
                    $currentLine = [];
                    if(is_array($data)) {
                        foreach ($data as $key => $value) {
                            $currentLine[$headers[$key]] = $value;
                        }
                    }
                    if(!empty($currentLine)) {
                        $row_data[] = $currentLine;

                    }

                }

                $file_data = $row_data[0];
                if(count($row_data) > 1) {

                    $repeating_values = call_user_func_array('array_intersect', $row_data);

                    foreach ($repeating_values as $key => $value ) {

                        $file_data[$key] = $value;
                    }

                }

                $file_data['data'] = $row_data;

                fclose($f);

            } else {

                $rteXmlData = file_get_contents($filepath);
                $file_data = simplexml_load_string($rteXmlData, "SimpleXMLElement", LIBXML_NOCDATA);
            }

            $export->set('rte_last_row', json_encode($file_data))->save();
        }

        // convert CSV to XLS
        if (@file_exists($filepath) and $export->options['export_to'] == 'csv' && !empty($export->options['export_to_sheet']) and $export->options['export_to_sheet'] != 'csv') {
	        $reader = IOFactory::createReader('Csv');
			// If the file uses a delimiter other than a comma (e.g., a tab), then configure the reader
	        $reader->setDelimiter($export->options['delimiter']);
			// Load the file into a Spreadsheet object
	        $spreadsheet = $reader->load($filepath);

	        $enableRtl = apply_filters('wp_all_export_enable_rtl', false, $export->id);

	        if ($enableRtl) {
		        $spreadsheet->getActiveSheet()->setRightToLeft(true);
	        }

	        switch ($export->options['export_to_sheet']) {
		        case 'xls':
			        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
			        $writer->save(str_replace(".csv", ".xls", $filepath));
			        @unlink($filepath);
			        $filepath = str_replace(".csv", ".xls", $filepath);
			        break;
		        case 'xlsx':
			        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
			        $writer->save(str_replace(".csv", ".xlsx", $filepath));
			        @unlink($filepath);
			        $filepath = str_replace(".csv", ".xlsx", $filepath);
			        break;
	        }

            $exportOptions = $export->options;
            $exportOptions['filepath'] = wp_all_export_get_relative_path($filepath);
            $export->set(array('options' => $exportOptions))->save();

            $is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

            if (!$is_secure_import) {
                $wp_uploads = wp_upload_dir();
                $wp_filetype = wp_check_filetype(basename($filepath), null);
                $attachment_data = array(
                    'guid' => $wp_uploads['baseurl'] . '/' . _wp_relative_upload_path($filepath),
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($filepath)),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                if (!empty($export->attch_id)) {
                    $attach_id = $export->attch_id;
                    $attachment = get_post($attach_id);
                    if ($attachment) {
                        update_attached_file($attach_id, $filepath);
                        wp_update_attachment_metadata($attach_id, $attachment_data);
                    } else {
                        $attach_id = wp_insert_attachment($attachment_data, PMXE_Plugin::$session->file);
                    }
                }
            }

        }

		// Remove empty columns.
	    if(!empty($export->options['csv_omit_empty_columns']) && $export->options['export_to'] == 'csv' && $export->options['export_to_sheet'] == 'csv' && @file_exists($filepath)){
		    // Create a temporary file
		    $tempFile = tempnam(sys_get_temp_dir(), 'temp_csv');

		    // Open the input file for reading and the temporary file for writing
		    $in = fopen($filepath, 'r');
		    $out = fopen($tempFile, 'w');

		    if ($in === FALSE || $out === FALSE) {
			    // TODO: Add error handling.
		    }

		    // Read the header row.
		    $header = fgetcsv($in, 0, XmlExportEngine::$exportOptions['delimiter']);
		    $columnCount = count($header);
		    $nonEmptyColumns = array_fill(0, $columnCount, false);

		    // Determine non-empty columns by scanning through each data row.
		    while (($row = fgetcsv($in, 0, XmlExportEngine::$exportOptions['delimiter'])) !== FALSE) {
			    foreach ($row as $index => $value) {
				    if (!empty($value)) {
					    $nonEmptyColumns[$index] = true;
				    }
			    }
		    }

		    // Close and reopen the input file for reading from the beginning
		    fclose($in);
		    $in = fopen($filepath, 'r');

		    // Write the header row with filtered columns.
		    $header = fgetcsv($in, 0, XmlExportEngine::$exportOptions['delimiter']);
		    $filteredHeader = array_filter($header, function($key) use ($nonEmptyColumns) {
			    return $nonEmptyColumns[$key];
		    }, ARRAY_FILTER_USE_KEY);
		    fputcsv($out, array_values($filteredHeader), XmlExportEngine::$exportOptions['delimiter']);

		    // Write the filtered data rows.
		    while (($row = fgetcsv($in, 0, XmlExportEngine::$exportOptions['delimiter'])) !== FALSE) {
			    $filteredRow = array_filter($row, function($key) use ($nonEmptyColumns) {
				    return $nonEmptyColumns[$key];
			    }, ARRAY_FILTER_USE_KEY);
			    fputcsv($out, array_values($filteredRow), XmlExportEngine::$exportOptions['delimiter']);
		    }

		    // Close the files.
		    fclose($in);
		    fclose($out);

		    // Overwrite the original file with our updated version.
		    rename($tempFile, $filepath);
	    }

        // make a temporary copy of current file
        if (empty($export->parent_id) and @file_exists($filepath) and @copy($filepath, str_replace(basename($filepath), '', $filepath) . 'current-' . basename($filepath))) {
            $exportOptions = $export->options;
            $exportOptions['current_filepath'] = str_replace(basename($filepath), '', $filepath) . 'current-' . basename($filepath);
            $export->set(array('options' => $exportOptions))->save();
        }

        $generateBundle = apply_filters('wp_all_export_generate_bundle', true);

        if ($generateBundle) {

            // genereta export bundle
            $export->generate_bundle();

            if (!empty($export->parent_id)) {
                $parent_export = new PMXE_Export_Record();
                $parent_export->getById($export->parent_id);
                if (!$parent_export->isEmpty()) {
                    $parent_export->generate_bundle(true);
                }
            }
        }

        $rte_subscriptions = get_option('rte_zapier_subscribe', array());
        $subscriptions = get_option('zapier_subscribe', array());


        if ((!empty($subscriptions) || !empty($rte_subscriptions)) && empty($export->parent_id)) {

            $wp_uploads = wp_upload_dir();

            $fileurl = str_replace($wp_uploads['basedir'], $wp_uploads['baseurl'], $filepath);

			// Add file edit time parameter to avoid caching of linked files by Cloudflare (or other caches).
	        $fileurl = add_query_arg('v', filemtime($filepath), $fileurl);

            if ($export->isRte()) {

                $file_data = json_decode($export->rte_last_row, true);
                if(!is_array($file_data)) {
                    $file_data = [];
                }

                $exportData = array(
                    'website_url' => home_url(),
                    'export_id' => $export->id,
                    'export_name' => $export->friendly_name,
                    'file_name' => basename($filepath)
                );

                $response = array_merge($file_data, $exportData);

                if (file_exists($filepath)) {
                    $response['export_file_url'] = $fileurl;
                    $response['status'] = 200;
                    $response['message'] = 'OK';
                } else {
                    $response['status'] = 300;
                    $response['message'] = 'File doesn\'t exist';
                }

                // send exported data to zapier.com
                foreach ($rte_subscriptions as $exportId => $zapier) {
                    if($exportId != $export->id) continue;

                    foreach($zapier as $targetUrl) {
                        wp_remote_post($targetUrl['target_url'], array(
                                'method' => 'POST',
                                'timeout' => 45,
                                'redirection' => 5,
                                'httpversion' => '1.0',
                                'blocking' => false,
                                'headers' => array(
                                    'Content-Type' => 'application/json'
                                ),
                                'body' => "[" . json_encode($response) . "]",
                                'cookies' => array()
                            )
                        );
                    }
                }
            } else {

                $response = array(
                    'website_url' => home_url(),
                    'export_id' => $export->id,
                    'export_name' => $export->friendly_name,
                    'file_name' => basename($filepath),
                    'file_type' => wp_all_export_get_export_format($export->options),
                    'post_types_exported' => empty($export->options['cpt']) ? $export->options['wp_query'] : implode(',', $export->options['cpt']),
                    'export_created_date' => $export->registered_on,
                    'export_last_run_date' => date('Y-m-d H:i:s'),
                    'export_trigger_type' => empty($_GET['export_key']) ? 'manual' : 'cron',
                    'records_exported' => $export->exported,
                    'export_file' => ''
                );

                if (file_exists($filepath)) {
                    $response['export_file_url'] = $fileurl;
                    $response['status'] = 200;
                    $response['message'] = 'OK';
                } else {
                    $response['export_file_url'] = '';
                    $response['status'] = 300;
                    $response['message'] = 'File doesn\'t exist';
                }

                // send exported data to zapier.com
                foreach ($subscriptions as $zapier) {
                    if (empty($zapier['target_url'])) continue;

                    wp_remote_post($zapier['target_url'], array(
                            'method' => 'POST',
                            'timeout' => 45,
                            'redirection' => 5,
                            'httpversion' => '1.0',
                            'blocking' => true,
                            'headers' => array(
                                'Content-Type' => 'application/json'
                            ),
                            'body' => "[" . json_encode($response) . "]",
                            'cookies' => array()
                        )
                    );
                }
            }

        }


        // clean session
        if (!empty(PMXE_Plugin::$session) and PMXE_Plugin::$session->has_session()) {
            PMXE_Plugin::$session->clean_session($export->id);
        }
    }
}
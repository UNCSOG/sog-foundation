<?php

function pmxe_wp_ajax_save_functions(){

	if ( ! check_ajax_referer( 'wp_all_export_secure', 'security', false )){
		exit( json_encode(array('html' => esc_html__('Security check', 'wp_all_export_plugin'))) );
	}

	if ( ! current_user_can( PMXE_Plugin::$capabilities ) ){
		exit( json_encode(array('html' => esc_html__('Security check', 'wp_all_export_plugin'))) );
	}

	$uploads   = wp_upload_dir();
	$functions = $uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_EXPORT_UPLOADS_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'functions.php';
	$functions = apply_filters( 'wp_all_export_functions_file_path', $functions );
	$input = new PMXE_Input();
	
	$post = $input->post('data', '');
	$post_to_validate = '';

	// Encode any string parenthesis to avoid validation issues.
	if(!empty($post)){
		$post_to_validate = pmxe_encode_parenthesis_within_strings($post);
	}

	$response = wp_remote_post('https://phpcodechecker.com/check/beta.php', array(
		'body' => array(
			'body' => $post_to_validate,
			'phpversion' => PHP_MAJOR_VERSION
		)
	));

	if (is_wp_error($response))
	{
		$error_message = $response->get_error_message();   		
   		exit(json_encode(array('result' => false, 'msg' => $error_message))); die;
	}
	else
	{
		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (!empty($body['errors']))
		{
			$error_response = '';
			foreach($body['results'] as $result){
				if(!empty($result['found']) && !empty($result['message'])){
					$error_response .= $result['message'].'<br/>';
				}
			}
			exit(json_encode(array('result' => false, 'msg' => $error_response))); die;
		}
		elseif(empty($body['errors']))
		{
			if (strpos($post, "<?php") === false || strpos($post, "?>") === false)
			{
				exit(json_encode(array('result' => false, 'msg' => __('PHP code must be wrapped in "&lt;?php" and "?&gt;"', 'wp_all_export_plugin')))); die;	
			}	
			else
			{
				file_put_contents($functions, $post);
			}					
		}
        elseif(empty($body)){
            file_put_contents($functions, $post);
        }
	}	

	exit(json_encode(array('result' => true, 'msg' => __('File has been successfully updated.', 'wp_all_export_plugin')))); die;
}
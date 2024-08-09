<?php
namespace sog_settings;

require_once("sog_settings_functions.php");

if( !session_id() ) {
	session_start();
}

if(isset($_POST['generic_update']) && !empty($_POST['generic_update'])) {
			$table_name=$_POST['generic_update'][0] ?? null;
			$id=$_POST['generic_update'][1] ?? null;
			$field_name=$_POST['generic_update'][2] ?? null;
			$value=$_POST['generic_update'][3] ?? null;
			if (!$value) {$value=null;} //need this for something but can't remember what for
			$si=$_POST['generic_update'][4] ?? null;
			$id_column_name=$_POST['generic_update'][5] ?? null;
			// $perm_to_check=get_permission_from_md5($_POST['generic_update'][6]);
			$other=$_POST['generic_update'][7] ?? null;
			$is_check=$_POST['generic_update'][8] ?? null;

			$allow=1;

			if ($allow) { //if permission
				if ($si=="s" or $si=="ss") {
					if ($si=="s") {
						$result=update_generic_table_s(array("table_name"=>$table_name,"field_name"=>$field_name,"id"=>$id,"value"=>$value,"id_column_name"=>$id_column_name));
						$response_array['result'] = $result; 
						$response_array['arr'] = array("table_name"=>$table_name,"field_name"=>$field_name,"id"=>$id,"value"=>$value,"id_column_name"=>$id_column_name); 
					}else{
						$result=update_generic_table_ss(array("table_name"=>$table_name,"field_name"=>$field_name,"id"=>$id,"value"=>$value,"id_column_name"=>$id_column_name));
					}
					if ($result['status']=="success") {
						$use_the_log=1;
						$response_array['status'] = 'success'; 
						$response_array['generic_update_date_modified'] = date("n/j/y g:i:s A");
						
					}else {
						$response_array['status'] = 'fail'; 
						if ($result['error_message']) {
							$response_array['message'] = "Uh-oh, something went wrong.  The error is :".$result['error_message'];
						}else{
							$response_array['message'] = 'Oops.  Something is not right in sql, call IT and give them code 1045 ('.$table_name.').'; 
						}
					}
				}elseif ($si=="i" or $si=="is") {
						if ($si=="is") {
							$result=update_generic_table_is(array("table_name"=>$table_name,"field_name"=>$field_name,"id"=>$id,"value"=>$value,"id_column_name"=>$id_column_name));
						}else{
							$result=update_generic_table_i(array("table_name"=>$table_name,"field_name"=>$field_name,"id"=>$id,"value"=>$value,"id_column_name"=>$id_column_name));
						}
					if ($result) {
						$use_the_log=1;
						$response_array['status'] = 'success'; 
						$response_array['generic_update_date_modified'] = date("n/j/y g:i:s A");; 
					}else {
						$response_array['status'] = 'fail'; 
						$response_array['message'] = 'Oops.  Something is not right in sql, call IT and give them code 1045 ('.$table_name.').'; 
					}

				}else{
					$response_array['status'] = 'fail'; 
					$response_array['message'] = 'Uh-Oh'; 
				}

			}else {
				$response_array['status'] = 'fail'; 
				if (!isset($response_array['message'])) {
					$response_array['message'] = 'Uh-Oh, there is a problem with a permission.  Call IT and give them code 1044 ('.$table_name.').'; 
				}
			}

    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}

//************  *****************/
if(isset($_POST['sog_settings_load_menu_section']) && !empty($_POST['sog_settings_load_menu_section'])) {
	$slug=$_POST['sog_settings_load_menu_section'][0];

	ob_start();
		if (function_exists("sog_settings\display_".$slug)) {
			call_user_func_array("sog_settings\display_".$slug,array(null));
		}else{
			$response_array['error_message'] = 'Hmm, something went wrong.  I couldn\'t find that data.'; 
		}
	$html = ob_get_clean();

	$response_array['html'] = $html; 
	$response_array['slug'] = $slug; 
	if ($html) {
		$response_array['status'] = 'success'; 
	}else{
		$response_array['status'] = 'fail'; 
	}

    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}

if(isset($_POST['generic_table_add_new']) && !empty($_POST['generic_table_add_new'])) {
	$table_name=$_POST['generic_table_add_new'][0];
	$field_name=$_POST['generic_table_add_new'][1];
	$value=$_POST['generic_table_add_new'][2];
	if (!$value) {$value=null;} //need this for something but can't remember what for
	$si=$_POST['generic_table_add_new'][3];
	$permission_csv=$_POST['generic_table_add_new'][4];
	$db=$_POST['generic_table_add_new'][5];
	$reload_with_ajax=$_POST['generic_table_add_new'][6];
	$use_generic_manage=$_POST['generic_table_add_new'][7];
	$allow_duplicates=$_POST['generic_table_add_new'][8];
	$table_display_name=$_POST['generic_table_add_new'][9];
	$ignore_last_inserted_id=$_POST['generic_table_add_new'][10];

	$permission_arr=[];

	$failed_allowed_tags=null;
	$original_value=$value;

	$allow=1;
	
	//check if being used.
	$being_used=get_generic_sql(array("table_name"=>$table_name,"fields"=>$field_name,"limit"=>"limit 1","where"=>"and ".$field_name."=\"".$value."\""));

	
	// $response_array['being_used'] = $being_used; 
	// $response_array['allow_duplicates'] = $allow_duplicates; 
	// $response_array['allow'] = $allow; 
	// $response_array['si'] = $si; 

	if (!$failed_allowed_tags) {
		if ($allow) {
			if (!$allow_duplicates and $being_used) {
				$response_array['status'] = 'fail'; 
				$response_array['message'] = $value.' already exists.'; 
				$response_array['show_alert'] = $value.' already exists.'; 
			}else{
				if ($si=="s" or $si=="ss") {
					if ($si=="s") {
						$new_id=add_row_generic_table_s(array("field_name"=>$field_name,"table_name"=>$table_name,"value"=>$value,"ignore_last_inserted_id"=>$ignore_last_inserted_id));
						if ($new_id) {
							$use_the_log=1;
						}
					}
				}
				$response_array['table_display_name'] = $table_display_name; 
				$response_array['table_name'] = $table_name; 
				if ($new_id and $reload_with_ajax) {
					ob_start();
						if ($use_generic_manage) {
							display_setting_manage(array("table_display_name"=>$table_display_name,"table_name"=>$table_name));
						}else{
							$get_fx="get_".$table_name."_data";
							$display_fx="display_".$table_name."_table";
							if (function_exists($get_fx) and function_exists($display_fx)) {
							$response_array['get_fx'] = $get_fx;
							$response_array['display_fx'] = $display_fx;
								$table_data=call_user_func_array($get_fx,array(array("order_by"=>"id desc")));
								call_user_func_array($display_fx,array(array("table_data"=>$table_data)));
							}
						}
					$html = ob_get_clean();
					$response_array['html'] = $html;
					$response_array['status'] = 'success'; 
				}else{
					$response_array['status'] = 'fail'; 
					$response_array['message'] = 'Something went wrong in SQL'; 
				}
			}

		}else {
			$response_array['status'] = 'fail'; 
			$response_array['message'] = 'Invalid Permissions'; 
		}
	}else {
		$response_array['status'] = 'fail'; 
		$response_array['show_alert'] = "Sorry, the only HTML tags allowed are ".rtrim($_SESSION['setting_3'],',')."."; 
	}

		if ($use_the_log) {
			// $the_log=the_log(array(
			// 				"name"=>"generic_table_add_new",
			// 				"description"=>$table_name,
			// 				"the_item_id"=>$field_name,
			// 				"the_value"=>$value,
			// 				"other"=>$new_id,
			// 				));
		}

header('Content-type: application/json');
echo json_encode($response_array);


exit;
}

if(isset($_POST['sog_settings_delete']) && !empty($_POST['sog_settings_delete'])) {
	$table_name=$_POST['sog_settings_delete'][0];
	$id=$_POST['sog_settings_delete'][1];
	$id_column_name=$_POST['sog_settings_delete'][2];
	$permission_csv=$_POST['sog_settings_delete'][3];
	$db=$_POST['sog_settings_delete'][4];
	$change_status_on_delete=$_POST['sog_settings_delete'][5];
	$permission_arr=[];

	$permission_arr=explode(",",$permission_csv);
	$allow=1;

	if ($allow) {
		if ($change_status_on_delete) {
			// $response_array['delete'] = 'status';
			$delete_result=update_generic_table_i(array("table_name"=>$table_name,"id"=>$id,"id_column_name"=>$id_column_name,"field_name"=>"status","value"=>0));
		}else{
			$delete_result=delete_generic_table_row(array("table_name"=>$table_name,"id"=>$id,"id_column_name"=>$id_column_name));
			$response_array['delete_result'] = $delete_result; 
			
		}
		if ($delete_result['status']=="success") {


			$response_array['status'] = 'success'; 
			
				// $the_log=the_log(array(
				// 	"name"=>"sog_settings_delete",
				// 	"description"=>$table_name,
				// 	"the_item_id"=>$id_column_name,
				// 	"the_value"=>$id,
				// ));

		}else{
			$response_array['status'] = 'fail'; 
		}
	}else {
		$response_array['status'] = 'fail'; 
	}

$response_array['delete_result'] = $delete_result; 
$response_array['allow'] = $allow; 

header('Content-type: application/json');
echo json_encode($response_array);
exit;
}

//************  *****************/
if(isset($_POST['sog_settings_update_setting']) && !empty($_POST['sog_settings_update_setting'])) {
	$setting_id=$_POST['sog_settings_update_setting'][0] ?? null;
	$value=$_POST['sog_settings_update_setting'][1] ?? null;
	$is_bool=$_POST['sog_settings_update_setting'][2] ?? null;
	if (!$value) {$value=null;}

	//make change
	$result=update_generic_table_s(array("table_name"=>"sog_settings","field_name"=>"value","id"=>$setting_id,"value"=>$value,"id_column_name"=>"id"));

	
	if ($result['status']=="success") {
		$response_array['status'] = 'success';

		//if worked then set session variable to match
		$response_array['result'] = $result; 
		$_SESSION['sog_setting_'.$setting_id]=$value;
	}else{
		$response_array['status'] = 'fail'; 
	}

    header('Content-type: application/json');
    echo json_encode($response_array);
	// usleep(500000);// 
	exit;
}




















































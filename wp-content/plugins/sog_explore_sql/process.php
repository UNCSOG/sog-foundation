<?php
namespace sog_explore;

if( !session_id() ) {
	session_start();
}

require_once("sog_explore_functions.php");

$_SESSION['db_to_use']=get_db_creds(null)['db_to_use'];
// $response_array['db_to_use'] = $_SESSION['db_to_use'];


if(isset($_POST['sog_ex_report_generic_update']) && !empty($_POST['sog_ex_report_generic_update'])) {
			$table_name=$_POST['sog_ex_report_generic_update'][0] ?? null;
			$field_name_hash=$_POST['sog_ex_report_generic_update'][1] ?? null;
			$value=$_POST['sog_ex_report_generic_update'][2] ?? null;
			if (!$value) {$value=null;} //need this for something but can't remember what for
			$primary_key_field=$_POST['sog_ex_report_generic_update'][3] ?? null;
			$primary_key_value=$_POST['sog_ex_report_generic_update'][4] ?? null;
			$last_datetime_checked=$_POST['sog_ex_report_generic_update'][5] ?? null;

			//get field name from salted hash, this ensures someone doesnt put this field name hash into the dom of another table and try to update that
			$this_field=get_field_name_from_hash(array("table_name"=>$table_name,"field_name_hash"=>$field_name_hash))[0];
			$response_array['this_field'] = $this_field;

			$problem=false;

			//check if allow access to this page
			if (allow_access_to_sog_explore()){

			}else{
				$problem=true;
				$response_array['alert_message'] = "Sorry, you do not have access to this table.";
			}


			//check if exists by trying to get this field, also holds the value before the change.
			$confirm_keys_and_values=confirm_keys_and_values(array("table_name"=>$this_field['table_name'],"field_name"=>$this_field['field_name'],
			"primary_key_field"=>$primary_key_field,"primary_key_value"=>$primary_key_value));
			$response_array['confirm_keys_and_values'] = $confirm_keys_and_values;


			//if this is the sog_ex_explore_log table do not allow editing, if they got here, someone is trying to hack into the log table.  rain fire.
			if ($table_name=="sog_ex_explore_log"){
				//set flags
				$problem=true;
				$response_array['attempted_to_edit_log'] = 1;

				//remove any edit fields from save
				$delete_saved="update sog_ex_explore_user_choices set edit_fields=null where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
				generic_sql_query(array("sql"=>$delete_saved));


				$response_array['restore_result']['value'] = $confirm_keys_and_values['new_array'][0][$this_field['field_name']];
				$response_array['alert_message'] = "Listen here, you know you're allowed to do this, why do you keep trying.";
			}

			//check if this table allows editing, as it may have changed from the time the page loaded until now.
			$allow_update=get_generic_sql(array("table_name"=>"sog_ex_report_tables","fields"=>"allow_update",
			"where"=>"and fk_table_name='".$table_name."' and allow_update=1"));
			if (isset($allow_update) and $allow_update){

			}else{
				$problem=true;
				$response_array['restore_result']['value'] = $confirm_keys_and_values['new_array'][0][$this_field['field_name']];
				$response_array['alert_message'] = "Sorry, you do not have access to edit this table.";
			}

			//now check if they had stale data when they submitted
			$check_if_stale_data=check_if_stale_data(array("last_datetime_checked"=>$last_datetime_checked,"table_name"=>$table_name));
			$response_array['check_if_stale_data'] = $check_if_stale_data;

			//get setting to check for stale
			$stale_check_option=get_option("sog_ex_stale_check_option");

			if ($check_if_stale_data['is_stale'] and $stale_check_option){
				$problem=true;
				$response_array['restore_result']['value'] = $confirm_keys_and_values['new_array'][0][$this_field['field_name']];
			}else{
				//if option is off
			}

			if (!$problem){
				//get primary key fields for this table from db
				$keys=get_primary_key(array("table_name"=>$table_name));

				//sort so can compare with primary keys user passed in
				sort($keys);

				//turn keys user passed in to array so can compare to keys from db
				$primary_key_field_array=explode(",",$primary_key_field);

				//sort to compare with primry keys from db
				sort($primary_key_field_array); //so can compare with array

				//make the check
				if ($primary_key_field_array==$keys){
					$keys_match=true;
				}else{
					$keys_match=false;
				}
				// $response_array['keys_match'] = $keys_match;

				//if keys match, can continue
				if ($keys_match){
					//if keys match, then ok to use field names they passed in so they can be in the same order as the values passed in

					if ($confirm_keys_and_values['num_rows']==1){

						//a valid record exists with these keys and values, ok to go ahead and update this value
						$result=sog_ex_report_generic_update(array("table_name"=>$table_name,"field_name"=>$this_field['field_name'],
							"primary_key_field"=>$primary_key_field,"primary_key_value"=>$primary_key_value,"value"=>$value));
						$response_array['result'] = $result;

						//if result is success, need to then check if it matches what they submitted
						//get value that is now stored in db
						$new_db_value=confirm_keys_and_values(array("table_name"=>$this_field['table_name'],"field_name"=>$this_field['field_name'],
						"primary_key_field"=>$primary_key_field,"primary_key_value"=>$primary_key_value,"value_to_check"=>$value));
						$response_array['new_db_value'] = $new_db_value;

						//compare that value to what user submitted
						if (isset($new_db_value) and $new_db_value['new_array'][0][$this_field['field_name']]==$value){
							//all good, value was replaced
							//add old and new value to log file
							$new_value=$new_db_value['new_array'][0][$this_field['field_name']];
							$old_value=$confirm_keys_and_values['new_array'][0][$this_field['field_name']];

							//add to log file if from remote db
							$remote_db_option=get_option("sog_ex_remote_db_option");
							$remote_db_option_label=get_option("sog_ex_remote_db_option_label");

							if ($remote_db_option){
								$db_creds=get_db_creds_remote(null);
								if (isset($db_creds)){

									//remove password from logfile
									unset($db_creds['password']);

									//add label
									$db_creds=array("label"=>$remote_db_option_label) + $db_creds;

									$db_creds_json=json_encode($db_creds);
								}
							}else{
								$db_creds_json="localhost";
							}

							$the_log=the_log(array(
								"action"=>"update_record",
								"table_name"=>$table_name,
								"field_name"=>$this_field['field_name'],
								"primary_key_field"=>$primary_key_field,
								"primary_key_value"=>$primary_key_value,
								"old_value"=>$old_value,
								"new_value"=>$new_value,
								"description"=>$db_creds_json,
								"other"=>null,
							));


						}else{
							//new value does not match, alert and replace with data selected when verifying the field
							$restore_result=sog_ex_report_generic_update(array("table_name"=>$table_name,"field_name"=>$this_field['field_name'],
								"primary_key_field"=>$primary_key_field,"primary_key_value"=>$primary_key_value,"value"=>$confirm_keys_and_values['new_array'][0][$this_field['field_name']]));
							$response_array['restore_result'] = $restore_result;
							$response_array['restore_result']['value'] = $confirm_keys_and_values['new_array'][0][$this_field['field_name']];

							$result['status'] = "fail";
							$result['error_message'] = "That data did not save properly, most likely the formatting is wrong. The original value will be restored.";
							$response_array['alert_message'] = "Invalid data \nOK to restore original value. \nCancel to edit and try again.";
						}


					}else{
						$response_array['status'] = "fail";
						$response_array['message'] = "Could not locate a record with those primary keys and values";
					}

				}else{
					$response_array['status'] = "fail";
					$response_array['message'] = "The primay key fields do not match";
				}
			}else{
				$response_array['status'] = "fail";
				$response_array['message'] = "Not allowed to do that.";
			}

			if (isset($result) and $result['status']=="success") {
				$use_the_log=1;
				$response_array['status'] = 'success';
				$response_array['generic_update_date_modified'] = date("n/j/y g:i:s A");

				//this function checks for stale date before it will get status of success
				$response_array['last_datetime_checked'] = date('Y-m-d H:i:s');

			}else {
				$response_array['status'] = 'fail';
				if (isset($result) and $result['error_message']) {
					$response_array['fail_message'] = "Uh-oh, something went wrong. ".$result['error_message'];
				}else{
					$response_array['fail_message'] = 'Oops.  Something is not right in sql, call IT and give them code 1045 ('.$table_name.').';
				}
			}

	$response_array['problem'] = $problem;

    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
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


			//prevent editing of log file
			if ($table_name=="sog_ex_report_tables" and $field_name=="allow_update" and $id=="sog_ex_explore_log"){
				$allow=0;
				$response_array['show_alert'] = "Umm nope, you can't edit the log table.";
			}elseif ($table_name=="sog_ex_report_tables" and $field_name=="allow_update"){
				//check if keys exist in case they want to allow update
				$keys=get_primary_key(array("table_name"=>$id));
				$response_array['keys'] = $keys;

				if (isset($keys) and $keys){
				}else{
					$allow=0;
					$response_array['show_alert'] = "This table does not have a primary key.";
				}
			}

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

				//if this is removing the allow_update check, and value is 1 and update was success
				if ($table_name=="sog_ex_report_tables" and $field_name=="allow_update" and !$value and $result['status']=="success"){

					//then clear out the edit fields for all users on that table.
					$delete_saved_edit_fields_sql="update sog_ex_explore_user_choices set edit_fields=null where fk_table_name='".$id."';";
					$delete_saved_edit_fields=generic_sql_query(array("sql"=>$delete_saved_edit_fields_sql));
					$response_array['delete_saved_edit_fields'] = $delete_saved_edit_fields;
					$response_array['delete_saved_edit_fields_sql'] = $delete_saved_edit_fields_sql;

				}
			}else {
				$response_array['status'] = 'fail';
				if (!$response_array['message']) {
					$response_array['message'] = 'Uh-Oh, there is a problem with a permission.  Call IT and give them code 1044 ('.$table_name.').';
				}
			}

    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}


//****************************/
if(isset($_POST['load_manage_dataset']) && !empty($_POST['load_manage_dataset'])) {
	$table=$_POST['load_manage_dataset'][0] ?? null;
	$display_name=$_POST['load_manage_dataset'][1] ?? null;

	if ($table) {
		$response_array['status'] = 'success';
		ob_start();
			display_manage_table_html(array("table_display_name"=>$display_name,"table_name"=>$table));
		$html = ob_get_clean();
		$response_array['html'] = $html;
	}

    header('Content-type: application/json');
    echo json_encode($response_array);

	// usleep(1250000);// pauses for .25 seconds
	exit;
}

//************  *****************/
if(isset($_POST['generic_load_menu_section']) && !empty($_POST['generic_load_menu_section'])) {
	$slug=$_POST['generic_load_menu_section'][0];

	ob_start();
		if (function_exists("sog_explore\display_".$slug)) {
			call_user_func_array("sog_explore\display_".$slug,array(null));
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

//****************************/
if(isset($_POST['process_report_request']) && !empty($_POST['process_report_request'])) {
	$table_name=$_POST['process_report_request'][0] ?? null;
	$field_names=$_POST['process_report_request'][1] ?? null;
	$filters=$_POST['process_report_request'][2] ?? null;
	$report_limit=$_POST['process_report_request'][3] ?? null;
	$groupings=$_POST['process_report_request'][4] ?? null;
	$fields_to_edit=$_POST['process_report_request'][5] ?? null;
	$auto_save=$_POST['process_report_request'][6] ?? null;
	$this_is_initial_page_load=$_POST['process_report_request'][7] ?? null;
	$db=$_POST['process_report_request'][8] ?? null;

	if ($_ENV['PANTHEON_SITE_NAME']=="sog-dwi"){
		//specific to dwi site - if there is no db passed in, then use one from options
		if (!$db){
			$db=get_option("sog_ex_remote_db_option_db_name");
		}
	}


	//check permissions as they may have changed since this page loaded.this is permission by role
	//will need to build more granular permission for DW tables
    if (allow_access_to_sog_explore()){
		$allow=1;
	}else{
		$allow=0;
		$response_array['alert_message'] = "Sorry, you do not have access to this table.";
	}

	//check if allowed to update, as they may have changed since this page last loaded
	$allow_update=get_generic_sql(array("table_name"=>"sog_ex_report_tables","fields"=>"allow_update",
	"where"=>"and fk_table_name='".$table_name."' and allow_update=1"));

	//check if allowed to update based on table sog_ex_report_tables
	if (isset($allow_update) and $allow_update) {
		$allowed_to_edit=true;
	}

	if ($_ENV['PANTHEON_SITE_NAME']=="sog-dwi"){
		/*
			Specific to DWI ONLY
			since the dwi site can handle multiple schema's but the sog_explore_sql plugin cannot, check if its the proper db
			to edit the table they must be either WP administrator (current_user_can('administrator'))
			or have their onyen hard coded into the "details" tab on the DWI page
			this overrides the plugin generic setting that allows editing
		*/

		//first get dwi field onyens allowed to edit this table
		$onyen_csv=get_generic_sql(array("table_name"=>"dwi_update_table_perm","fields"=>"onyen_csv",
		"where"=>"and fk_table_name='".$table_name."' and fk_schema_name='".$db."'"));
		// echo "data<pre>";print_r($data);echo "</pre>";
		// $response_array['onyen_csv'] = $onyen_csv;

		//initialize array
		$onyen_array=[];

		//split into array
		if (isset($onyen_csv) and is_array($onyen_csv)){
			//remove spaces
			$onyen_csv_no_spaces = str_replace(' ', '', $onyen_csv[0]['onyen_csv']);

			//turn into array
			$onyen_array=explode(",",$onyen_csv_no_spaces);
		}

		//check if they are in array
		if (in_array($_SESSION['sog_explore_user_login'],$onyen_array) or current_user_can('administrator')){

		}else{
			//since noty allowed to edit, clear out any edit fields that may have been chosen
			$fields_to_edit=[];
		}

		//if dwi site check if $_GET['db'] = to remote db from options
		//get db name from remote settings
		$sog_ex_remote_db_option_db_name=get_option("sog_ex_remote_db_option_db_name");

		//check for matching db
		if ($db and $sog_ex_remote_db_option_db_name and $db!=$sog_ex_remote_db_option_db_name){
			//since the db var exists but it is not the same as the db from options
			//remove ability to update regardless what it gets from sog_ex_report_tables
			$fields_to_edit=[];
		}
	}

	// $response_array['onyen_array'] = $onyen_array;


	if ($allow) {
		if (is_array($groupings)){
			$groupings = array_filter($groupings); //removes empty strings
		}

		//gather their saved choices for use throughout this function
		$user_saved_choices=get_generic_sql(array("table_name"=>"sog_ex_explore_user_choices",
		"fields"=>"fields_chosen, group_by_fields,edit_fields, sort_sql ",
		"where"=>"	and fk_username='".$_SESSION['sog_explore_user_login']."'
					and fk_table_name='".$table_name."'"
				));

		//if this is from initial page load, then get field names from sql so they are in the correct order
		if ($this_is_initial_page_load) {
			//convert back into array
			$fields_from_save=json_decode($user_saved_choices[0]['fields_chosen']);

			//replace submitted field names with this array
			$field_names=$fields_from_save;
		}

		//check if admin, if so show sql
		if( current_user_can('administrator') ) {
			$show_sql=1;
		}


		// only save is asked to
		if ($auto_save){

			//set auto save as wp_option
			$update_auto_save_option=update_option("sog_ex_auto_save_".$_SESSION['sog_explore_user_login'],1);

			//########################## To save Filters ##########################
			//remove existing filters from database so if the filter passed in is now blank,
			//this wouldn't know to update the db since it doesnt have anything to loop through
			$delete_saved="update sog_ex_explore_filter set value=null, value_like=null where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
			generic_sql_query(array("sql"=>$delete_saved));

			//split filters into array	["and number=\\'51013\\'"],["and size_in_b like \\'%2%\\'"]
			//loop through each
			foreach ($filters as $filter) {
				$temp='';
				$temp_arr=[];

				//remove slashes
				$temp=stripslashes($filter);  //"and number='461387'" or "and size_in_b like '%2%'"

				//cehck for =
				if (str_contains($temp,"=")){
					//contains = so is filter
					//split on =
					$temp_arr=explode("=",$temp); //[and number] ['461387']

					//split first part to get field name
					$field_name=explode(" ",$temp_arr[0])[1];

					//second part is value
					$filter_value=$temp_arr[1];

					//strip single quotes
					$filter_value = str_replace("'", "", $filter_value);

					//taking advantage of generic function to store this value
					$filter_update_sql=update_generic_table_ss(array("table_name"=>"sog_ex_explore_filter","field_name"=>"value","value"=>$filter_value,
					"id_column_name"=>"fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."' and field_name","id"=>$field_name));

					$response_array['filter_update_sql'][] = $filter_update_sql;

					//since this filter is being called with =, need to null out value_like from table, since it wont get updated, because it doesn't exist
					$delete_value_like_sql="update sog_ex_explore_filter set value_like=null where fk_table_name='".$table_name."' and field_name='".$field_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
					generic_sql_query(array("sql"=>$delete_value_like_sql));


				}elseif (str_contains($temp,"like")){
					//no =, so must be like filter
					//split on like
					$temp_arr=explode("like",$temp); //[and size_in_b] ['%2%']

					//split first part to get field name
					$field_name=explode(" ",$temp_arr[0])[1];

					//split second part is value
					$filter_value=$temp_arr[1];

					//strip single quotes
					$filter_value = str_replace(array("'","%"), "", $filter_value);

					//taking advantage of generic function to store this value
					$filter_update_sql=update_generic_table_ss(array("table_name"=>"sog_ex_explore_filter","field_name"=>"value_like","value"=>$filter_value,
					"id_column_name"=>"fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."' and field_name","id"=>$field_name));

					$response_array['filter_update_sql'][] = $filter_update_sql;

					//since this filter is being called with like, need to null out value from table, since it wont get updated, because it doesn't exist
					$delete_value_like_sql="update sog_ex_explore_filter set value=null where fk_table_name='".$table_name."' and field_name='".$field_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
					generic_sql_query(array("sql"=>$delete_value_like_sql));

				}

			}

			//########################## To save fields chosen, edit fields, and grouping fields ##########################
			//save user choices for pre populating choices next time they load this table
			$save_fields_chosen=update_generic_table_sss(array("table_name"=>"sog_ex_explore_user_choices","field_name"=>"fields_chosen","value"=>json_encode($field_names),
				"id_column_name"=>"fk_username","id"=>$_SESSION['sog_explore_user_login'],"id_column_name_2"=>"fk_table_name","id_2"=>$table_name));

			//save group by choices to sog_ex_explore_user_choices
			$save_groups_chosen=update_generic_table_sss(array("table_name"=>"sog_ex_explore_user_choices","field_name"=>"group_by_fields","value"=>json_encode($groupings),
				"id_column_name"=>"fk_username","id"=>$_SESSION['sog_explore_user_login'],"id_column_name_2"=>"fk_table_name","id_2"=>$table_name));

			//save edit choices to sog_ex_explore_user_choices
			$save_edits_chosen=update_generic_table_sss(array("table_name"=>"sog_ex_explore_user_choices","field_name"=>"edit_fields","value"=>json_encode($fields_to_edit),
				"id_column_name"=>"fk_username","id"=>$_SESSION['sog_explore_user_login'],"id_column_name_2"=>"fk_table_name","id_2"=>$table_name));


		}else{
			//set auto save as wp_option
			$update_auto_save_option=delete_option("sog_ex_auto_save_".$_SESSION['sog_explore_user_login']);

			//not saving, null out any entries
			$delete_saved="update sog_ex_explore_filter set value=null, value_like=null where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
			generic_sql_query(array("sql"=>$delete_saved));

			//and from sog_ex_explore_user_choices
			$delete_saved="update sog_ex_explore_user_choices set fields_chosen=null, group_by_fields=null, edit_fields=null, sort_sql=null where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
			generic_sql_query(array("sql"=>$delete_saved));

			//this will delete record, but since it only get's created when choosing table, will need to re-insert row as needed, may just change bacl to nulling out fields
			// $delete_saved="delete from sog_ex_explore_filter where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
			// generic_sql_query(array("sql"=>$delete_saved));
			// $delete_saved="delete from sog_ex_explore_user_choices where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
			// generic_sql_query(array("sql"=>$delete_saved));


		}

		//check if asking for all records
		if (!$report_limit) {
			//check if option is set
			$subset_limit_option=get_option("sog_ex_subset_limit_option");
			if (!$subset_limit_option){
				$limit_to_use="100";
			}else{
				$limit_to_use=$subset_limit_option;
			}
		}elseif ($report_limit=="All") {
			$limit_to_use="";
		}

		$fk_schema_name="";

		ob_start();
			if ($field_names) {
				//check for saved order for this table
				$saved_sort=$user_saved_choices[0]['sort_sql'] ?? "";
				$response_array['saved_sort'] = $saved_sort;

				//if saved order was on count column, and this containts no groupings, that means there will be no count column to order by, remove the saved sort
				if (!$groupings and str_contains($saved_sort,"_count")){
					//set it to null for this report
					$saved_sort=null;

					//then remove from db for future requests
					$delete_saved_sort="update sog_ex_explore_user_choices set sort_sql=null where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
					// generic_sql_query(array("sql"=>$delete_saved_sort));
				}

				//pass in string table_name and array of field_names
				$table_data=get_report_data(array("field_names"=>$field_names,"table_name"=>$table_name,"filters"=>$filters,"limit"=>$limit_to_use,"groupings"=>$groupings,"order"=>$saved_sort));

				$response_array['table_data'] = $table_data;
				$response_array['sql'] = $table_data['sql'];
				$response_array['table_data_count'] = count($table_data['table_data'] ?? []);
	// echo "table_data<pre>";print_r($table_data);echo "</pre>";
	// return;

				if (isset($table_data) and $table_data['table_data']) {
					// echo build_simple_table(array("table"=>$table_data['table_data'],"class"=>"bg-white table table-sm table-bordered table-striped table-hover report_data_table"));
					echo build_sog_ex_table(array("table"=>$table_data['table_data'],"class"=>"bg-white table table-sm table-bordered table-striped table-hover report_data_table",
						"keys"=>$table_data['keys'],"fields_to_edit"=>$fields_to_edit,"table_name"=>$table_name));

					if (isset($show_sql) and $show_sql==1){
						?>
							<div class="row pt-3 mt-3 border-top output_select_query">
								<div class="col">
									<div class="h4">
										MySQL Select Query
									</div>
									<div class="sql_output_here p-2 bg-muted border text-danger">
									</div>
								</div>
							</div>
							<div class="row pt-3 mt-3 border-top output_update_query">
								<div class="col">
									<div class="h4">
										MySQL Update Query
									</div>
									<div class="sql_output_here p-2 bg-muted border text-danger">

									</div>
								</div>
							</div>
						<?php
					}
				}else{
					echo "<div class='report_no_data text-center my-2 h3'>
							No Data
						</div>";
				}

				//now get count without limit
				$table_data_no_limit=get_report_data(array("field_names"=>$field_names,"fk_schema_name"=>$fk_schema_name,"table_name"=>$table_name,"filters"=>$filters,"limit"=>"","groupings"=>$groupings,"order"=>$saved_sort));
				$response_array['table_data_no_limit_count'] = count($table_data_no_limit['table_data']);
			}else{
				echo "<div class='report_no_data text-center my-2 h5'>
						Choose fields to get started.
					</div>";
				$table_data_no_limit=[];
			}

		$response_array['last_datetime_checked'] = date('Y-m-d H:i:s');
		$response_array['status'] = 'success';
	}else{
		//no access
		$response_array['status'] = 'fail';
	}


	$html = ob_get_clean();
	$response_array['html'] = $html;

	//requried
	$response_array['field_names'] = $field_names;
	$response_array['limit_to_use'] = $limit_to_use;

	//just for debugging
	// $response_array['save_fields_chosen'] = $save_fields_chosen;
	// $response_array['save_edits_chosen'] = $save_edits_chosen;
	// $response_array['save_groups_chosen'] = $save_groups_chosen;
	// $response_array['fields_from_save'] = $fields_from_save;
	// $response_array['filters'] = $filters;
	// $response_array['groupings'] = $groupings;
	// $response_array['fields_to_edit'] = $fields_to_edit;
	// $response_array['elapsed_time'] = $elapsed_time;
	// $response_array['table_name'] = $table_name;
	// $response_array['save_auto_save'] = $save_auto_save;
	// $response_array['report_limit'] = $report_limit;
	// $elapsed_time=(microtime(true) - $GLOBALS['time']);

	$response_array['sql_for_display'] = $table_data_no_limit['sql_for_display'] ??  null;

	$response_array['sog_ex'] = "sog_ex";

	// usleep(3000000);// 3000000=3 seconds
	header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}

//****************************/
if(isset($_POST['select_report_table']) && !empty($_POST['select_report_table'])) {
	$table_name=$_POST['select_report_table'][0] ?? null;
	$db=$_POST['select_report_table'][1] ?? null;

	//create entry into sog_ex_explore_user_choices so can update later
	$sql="insert ignore into sog_ex_explore_user_choices (fk_username,fk_table_name) values ('".$_SESSION['sog_explore_user_login']."','".$table_name."')";
	$add_user_entry=generic_sql_query(array("sql"=>$sql));

	$response_array['sql'] = $sql;
	$response_array['add_user_entry'] = $add_user_entry;

	if ($_ENV['PANTHEON_SITE_NAME']=="sog-dwi"){
		//specific to dwi site - if there is no db passed in, then use one from options
		if (!$db){
			$db=get_option("sog_ex_remote_db_option_db_name");
		}
	}


	ob_start();
		build_report_options(array("table_name"=>$table_name,"db"=>$db));
	$html = ob_get_clean();
	$response_array['html'] = $html;

	if (1) {
		$response_array['status'] = 'success';

	}else{
		$response_array['status'] = 'fail';
		$response_array['alert_message'] = "Hmm, I had trouble finding their advising page";
	}
    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}


//****************************/
if(isset($_POST['add_explore_filter']) && !empty($_POST['add_explore_filter'])) {
	$table_name=$_POST['add_explore_filter'][0] ?? null;
	$field_name=$_POST['add_explore_filter'][1] ?? null;
	$do_what=$_POST['add_explore_filter'][2] ?? null;
	$data_type=$_POST['add_explore_filter'][3] ?? null;

	//determing if field is string or in for doing
	$int_types=array("int","smallint","tinyint","mediumint","bigint");
	if (in_array(strtolower($data_type),$int_types)) {
		$si="i";
	}else{
		$si="s";
	}

	if ($table_name) {
		if ($do_what=="add") {
			$sql="insert ignore into sog_ex_explore_filter (fk_table_name, field_name, si, fk_username)
				values ('".$table_name."','".$field_name."','".$si."','".$_SESSION['sog_explore_user_login']."')
			";
			$add_filter=generic_sql_query(array("sql"=>$sql));
			$response_array['add_filter'] = $add_filter;
		}else{
			$sql="delete from sog_ex_explore_filter
				where 1=1
				and fk_table_name='".$table_name."'
				and field_name='".$field_name."'
				and fk_username='".$_SESSION['sog_explore_user_login']."'
			";
			$remove_filter=generic_sql_query(array("sql"=>$sql));
			$response_array['remove_filter'] = $remove_filter;
		}


		//now create filters
		ob_start();
			display_report_field_filters(array("table_name"=>$table_name));
		$html = ob_get_clean();
		$response_array['filters_html'] = $html;

	}

	if (1) {
		$response_array['status'] = 'success';

	}else{
		$response_array['status'] = 'fail';
		$response_array['alert_message'] = "Hmm, I had trouble finding their advising page";
	}
    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}


//************  *****************/
if(isset($_POST['select_all_none_choose_tables']) && !empty($_POST['select_all_none_choose_tables'])) {
	$which=$_POST['select_all_none_choose_tables'][0] ?? null;

	if ($which=="all") {
		$sql="update sog_ex_report_tables set use_this=1";
	}elseif ($which=="none") {
		$sql="update sog_ex_report_tables set use_this=0";
	}
	$update=generic_sql_query(array("sql"=>$sql));
	$response_array['update'] = $update;

	if ($update['status']=="success") {
		$response_array['status'] = 'success';
	}else{
		$response_array['status'] = 'fail';
	}

    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}

//************  *****************/
if(isset($_POST['save_explore_roles']) && !empty($_POST['save_explore_roles'])) {
	$report_array_of_roles=$_POST['save_explore_roles'][0] ?? null;

	if (isset($report_array_of_roles) and is_array($report_array_of_roles)) {
		$value=implode(",",$report_array_of_roles);
		$update_option=update_option( $option="sog_ex_save_explore_roles", $value );
		$response_array['update_option'] = $update_option;
	}
	if ($update_option) {
		$response_array['status'] = 'success';
	}else{
		$response_array['status'] = 'fail';
	}


    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}


//************  *****************/
if(isset($_POST['save_stale_check_option']) && !empty($_POST['save_stale_check_option'])) {
	$value=$_POST['save_stale_check_option'][0] ?? null;
	$seconds=(int)$_POST['save_stale_check_option'][1] ?? null;
	$subset_limit=(int)$_POST['save_stale_check_option'][2] ?? null;

	if ($seconds==0 or !$seconds){
		//if there is no entry or it's 0 then record as 0
		$ms=0;
		$return_seconds=$ms;
	}elseif(is_int($seconds) and $seconds>=5){
		//if greater than 5, then use as is
		$ms=$seconds*1000;
		$return_seconds=$seconds;
	}else{
		//default to 0
		$ms=0;
		$return_seconds=$ms;
	}

	if ($subset_limit==0 or !$subset_limit){
		//if there is no entry, default to 100
		$subset_limit=100;
		$return_subset_limit=$subset_limit;
	}elseif(is_int($subset_limit) and $subset_limit>=0){
		//if over 0, use it
		$subset_limit=$subset_limit;
		$return_subset_limit=$subset_limit;
	}else{
		//default to 100
		$subset_limit=100;
		$return_subset_limit=$subset_limit;
	}

	//save checkbox
	$stale_check_option=update_option( $option="sog_ex_stale_check_option", $value );
	$response_array['stale_check_option'] = $stale_check_option;

	//save ms
	$stale_check_option_seconds=update_option( $option="sog_ex_stale_check_option_seconds", $ms );
	$response_array['stale_check_option_seconds'] = $stale_check_option_seconds;
	$response_array['return_seconds'] = $return_seconds; //this is so the screen will reflect what was actually saved

	//save subset limit
	$subset_limit_option=update_option( $option="sog_ex_subset_limit_option", $subset_limit );
	$response_array['subset_limit_option'] = $subset_limit_option;
	$response_array['return_subset_limit'] = $return_subset_limit; //this is so the screen will reflect what was actually saved




	if (1) {
		$response_array['status'] = 'success';
	}else{
		$response_array['status'] = 'fail';
	}


    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}

//************  *****************/
if(isset($_POST['save_remote_db_option']) && !empty($_POST['save_remote_db_option'])) {
	$value=$_POST['save_remote_db_option'][0] ?? null;
	$hostname=$_POST['save_remote_db_option'][1] ?? null;
	$port=(int)$_POST['save_remote_db_option'][2] ?? null;
	$username=$_POST['save_remote_db_option'][3] ?? null;
	$password=$_POST['save_remote_db_option'][4] ?? null;
	$db_name=$_POST['save_remote_db_option'][5] ?? null;
	$label=$_POST['save_remote_db_option'][6] ?? null;

	//save checkbox
	$remote_db_option=update_option( $option="sog_ex_remote_db_option", $value );
	$response_array['remote_db_option'] = $remote_db_option;

	//save options
	$remote_db_option_hostname=update_option( $option="sog_ex_remote_db_option_hostname", $hostname );
	// $response_array['remote_db_option_hostname'] = $remote_db_option_hostname;

	$remote_db_option_port=update_option( $option="sog_ex_remote_db_option_port", $port );
	// $response_array['remote_db_option_port'] = $remote_db_option_port;

	$remote_db_option_username=update_option( $option="sog_ex_remote_db_option_username", $username );
	// $response_array['remote_db_option_username'] = $remote_db_option_username;

	$remote_db_option_password=update_option( $option="sog_ex_remote_db_option_password", $password );
	// $response_array['remote_db_option_password'] = $remote_db_option_password;

	$remote_db_option_db_name=update_option( $option="sog_ex_remote_db_option_db_name", $db_name );
	// $response_array['remote_db_option_db_name'] = $remote_db_option_db_name;

	$remote_db_option_db_name=update_option( $option="sog_ex_remote_db_option_label", $label );
	// $response_array['remote_db_option_label'] = $remote_db_option_label;

	//need to check if remote db exists with saved data.
	$check_valid_remote_db=db_connect_remote_check_only(array("hostname"=>$hostname,"port"=>$port,"username"=>$username,"password"=>$password,"db_name"=>$db_name));
	$response_array['check_valid_remote_db'] = $check_valid_remote_db;

	if ($check_valid_remote_db['status']=="success") {
		$response_array['status'] = 'success';
	}else{
		//remove use this option from options
		$remote_db_option=update_option( $option="sog_ex_remote_db_option", 0 );
		$response_array['status'] = 'fail';
	}


    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}

//************  *****************/
if(isset($_POST['sog_ex_restore_value']) && !empty($_POST['sog_ex_restore_value'])) {
	$log_id=$_POST['sog_ex_restore_value'][0] ?? null;

	//cast as int so next function will only have an int to work with
	$log_id=(int)$log_id;

	$sog_ex_report_restore_value=sog_ex_report_restore_value($log_id);
	$response_array['sog_ex_report_restore_value'] = $sog_ex_report_restore_value;

	if ($sog_ex_report_restore_value['status']=="success") {
		$response_array['status'] = 'success';
	}else{
		$response_array['status'] = 'fail';
	}


    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}

//************  *****************/
if(isset($_POST['check_if_stale_data']) && !empty($_POST['check_if_stale_data'])) {
	$last_datetime_checked=$_POST['check_if_stale_data'][0] ?? null;
	$table_name=$_POST['check_if_stale_data'][1] ?? null;

	$check_if_stale_data=check_if_stale_data(array("last_datetime_checked"=>$last_datetime_checked,"table_name"=>$table_name));
	if (!$check_if_stale_data){
		$check_if_stale_data['is_stale']=null;
	}
	$response_array['check_if_stale_data'] = $check_if_stale_data;


	if (1) {
		$response_array['status'] = 'success';
	}else{
		$response_array['status'] = 'fail';
	}


    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}


//************  *****************/
if(isset($_POST['save_sort_column']) && !empty($_POST['save_sort_column'])) {
	$table_name=$_POST['save_sort_column'][0] ?? null;
	$field_name=$_POST['save_sort_column'][1] ?? null;
	$asc_desc=$_POST['save_sort_column'][2] ?? null;

	if ($asc_desc=="ascending"){
		$asc_desc_sql="asc";
	}elseif($asc_desc=="descending"){
		$asc_desc_sql="desc";
	}else{
		$asc_desc_sql="asc";
	}

	if ($field_name and $table_name){
		$sort_sql="order by ".$field_name." ".$asc_desc_sql;
		//save edit choices to sog_ex_explore_user_choices
		$save_sort=update_generic_table_sss(array("table_name"=>"sog_ex_explore_user_choices","field_name"=>"sort_sql","value"=>$sort_sql,
			"id_column_name"=>"fk_username","id"=>$_SESSION['sog_explore_user_login'],"id_column_name_2"=>"fk_table_name","id_2"=>$table_name));
	}

	if (1) {
		$response_array['status'] = 'success';
	}else{
		$response_array['status'] = 'fail';
	}


    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}



//************  *****************/
if(isset($_POST['reset_table_settings']) && !empty($_POST['reset_table_settings'])) {
	$table_name=$_POST['reset_table_settings'][0] ?? null;


	//not saving, null out any entries
	$delete_saved_filters="update sog_ex_explore_filter set value=null, value_like=null where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
	$del_1=generic_sql_query(array("sql"=>$delete_saved_filters));

	//and from sog_ex_explore_user_choices
	$delete_saved="update sog_ex_explore_user_choices set fields_chosen=null, group_by_fields=null, edit_fields=null, sort_sql=null where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
	$del_2=generic_sql_query(array("sql"=>$delete_saved));

	if ($del_1['status']=="success" and $del_2['status']=="success") {
		$response_array['status'] = 'success';
	}else{
		$response_array['status'] = 'fail';
	}


    header('Content-type: application/json');
    echo json_encode($response_array);
	exit;
}




















































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

			//get field name from salted hash, this ensures someone doesnt put this field name hash into the dom of another table and try to update that
			$this_field=get_field_name_from_hash(array("table_name"=>$table_name,"field_name_hash"=>$field_name_hash))[0]; 
			// $response_array['this_field'] = $this_field; 

			$allow=1;

			//if this is the sog_ex_explore_log table do not allow editing, if they got here, someone is trying to hack into the log table.  rain fire.
			if ($table_name=="sog_ex_explore_log"){
				//set flags
				$allow=0;
				$response_array['attempted_to_edit_log'] = 1;

				//remove any edit fields from save
				$delete_saved="update sog_ex_explore_user_choices set edit_fields=null where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
				generic_sql_query(array("sql"=>$delete_saved));


				$confirm_keys_and_values=confirm_keys_and_values(array("table_name"=>$this_field['table_name'],"field_name"=>$this_field['field_name'],
					"primary_key_field"=>$primary_key_field,"primary_key_value"=>$primary_key_value));
				$response_array['confirm_keys_and_values'] = $confirm_keys_and_values;
				$response_array['restore_result']['value'] = $confirm_keys_and_values['new_array'][0][$this_field['field_name']];
				$response_array['alert_message'] = "Listen here, you know you're allowed to do this, why do you keep trying.";
			}

			if ($allow){
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
					//attempt to make select of field_name using the primary keys passed in, confirming that there is ONE entry from the db
					$confirm_keys_and_values=confirm_keys_and_values(array("table_name"=>$this_field['table_name'],"field_name"=>$this_field['field_name'],
						"primary_key_field"=>$primary_key_field,"primary_key_value"=>$primary_key_value));
					$response_array['confirm_keys_and_values'] = $confirm_keys_and_values;

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

							$the_log=the_log(array(
								"action"=>"update_record",
								"table_name"=>$table_name,
								"field_name"=>$this_field['field_name'],
								"primary_key_field"=>$primary_key_field,
								"primary_key_value"=>$primary_key_value,
								"old_value"=>$old_value,
								"new_value"=>$new_value,
								"description"=>null,
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

			if ($result['status']=="success") {
				$use_the_log=1;
				$response_array['status'] = 'success'; 
				$response_array['generic_update_date_modified'] = date("n/j/y g:i:s A");
			}else {
				$response_array['status'] = 'fail'; 
				if ($result['error_message']) {
					$response_array['message'] = "Uh-oh, something went wrong. ".$result['error_message'];
				}else{
					$response_array['message'] = 'Oops.  Something is not right in sql, call IT and give them code 1045 ('.$table_name.').'; 
				}
			}

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

	$response_array['_POST'] = $_POST; 

	if (is_array($groupings)){
		$groupings = array_filter($groupings); //removes empty strings
	}

	//if this is from initial page load, then get field names from sql so they are in the correct order
	if ($this_is_initial_page_load) {
		$user_saved_choices=get_generic_sql(array("table_name"=>"sog_ex_explore_user_choices",
		"fields"=>"fields_chosen, group_by_fields,edit_fields ",
		"where"=>"	and fk_username='".$_SESSION['sog_explore_user_login']."'
					and fk_table_name='".$table_name."'"
				));
		//convert back into array
		$fields_from_save=json_decode($user_saved_choices[0]['fields_chosen']);

		//replace submitted field names with this array
		$field_names=$fields_from_save;
	}

	// only save is asked to
	if ($auto_save){

		//set cookie to true 
		setcookie($cookie_name="report_auto_save", $cookie_value=1, time() + (86400 * 365), "/"); // 86400 = 1 day

		//########################## To save Filters ##########################
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
		//set cookie to true 
		setcookie($cookie_name="report_auto_save", "", -1, "/"); // 86400 = 1 day

		//not saving, null out any entries
		$delete_saved="update sog_ex_explore_filter set value=null, value_like=null where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
		generic_sql_query(array("sql"=>$delete_saved));

		//and from sog_ex_explore_user_choices
		$delete_saved="update sog_ex_explore_user_choices set fields_chosen=null, group_by_fields=null, edit_fields=null where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
		generic_sql_query(array("sql"=>$delete_saved));

		//this will delete record, but since it only get's created when choosing table, will need to re-insert row as needed, may just change bacl to nulling out fields
		// $delete_saved="delete from sog_ex_explore_filter where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
		// generic_sql_query(array("sql"=>$delete_saved));
		// $delete_saved="delete from sog_ex_explore_user_choices where fk_table_name='".$table_name."' and fk_username='".$_SESSION['sog_explore_user_login']."';";
		// generic_sql_query(array("sql"=>$delete_saved));


	}

	if (!$report_limit) {
		$limit_to_use="100";
	}elseif ($report_limit=="All") {
		$limit_to_use="";
	}

	$fk_schema_name="";

	ob_start();
		if ($field_names) {
		//pass in string table_name and array of field_names
			$table_data=get_report_data(array("field_names"=>$field_names,"table_name"=>$table_name,"filters"=>$filters,"limit"=>$limit_to_use,"groupings"=>$groupings));
			$response_array['sql'] = $table_data['sql']; 
			// $response_array['table_data'] = $table_data; 
			$response_array['table_data_count'] = count($table_data['table_data']); 

			if ($table_data['table_data']) {
				// echo build_simple_table(array("table"=>$table_data['table_data'],"class"=>"bg-white table table-sm table-bordered table-striped table-hover report_data_table"));
				echo build_sog_ex_table(array("table"=>$table_data['table_data'],"class"=>"bg-white table table-sm table-bordered table-striped table-hover report_data_table",
					"keys"=>$table_data['keys'],"fields_to_edit"=>$fields_to_edit,"table_name"=>$table_name));
			}else{
				echo "<div class='report_no_data text-center my-2 h3'>
						No Data
					</div>";
			}

			//now get count without limit
			$table_data_no_limit=get_report_data(array("field_names"=>$field_names,"fk_schema_name"=>$fk_schema_name,"table_name"=>$table_name,"filters"=>$filters,"limit"=>"","groupings"=>$groupings));
			$response_array['table_data_no_limit_count'] = count($table_data_no_limit['table_data']); 
		}else{
			echo "<div class='report_no_data text-center my-2 h5'>
					Choose fields to get started.
				</div>";
			$table_data_no_limit=[];
		}

	$html = ob_get_clean();
	$response_array['html'] = $html; 

	// $response_array['save_fields_chosen'] = $save_fields_chosen; 
	// $response_array['save_edits_chosen'] = $save_edits_chosen; 
	// $response_array['save_groups_chosen'] = $save_groups_chosen; 
	// $response_array['fields_from_save'] = $fields_from_save; 
	// $response_array['filters'] = $filters; 
	// $response_array['groupings'] = $groupings; 
	// $response_array['fields_to_edit'] = $fields_to_edit; 
	// $elapsed_time=(microtime(true) - $GLOBALS['time']);
	// $response_array['elapsed_time'] = $elapsed_time;
	// $response_array['table_name'] = $table_name; 
	$response_array['field_names'] = $field_names; 
	// $response_array['limit_to_use'] = $limit_to_use; 
	// $response_array['report_limit'] = $report_limit; 

	$response_array['sql_used'] = $table_data_no_limit['sql'] ??  null;

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
if(isset($_POST['select_report_table']) && !empty($_POST['select_report_table'])) {
	$table_name=$_POST['select_report_table'][0] ?? null;

	//create entry into sog_ex_explore_user_choices so can update later
	$sql="insert ignore into sog_ex_explore_user_choices (fk_username,fk_table_name) values ('".$_SESSION['sog_explore_user_login']."','".$table_name."')";
	$add_user_entry=generic_sql_query(array("sql"=>$sql));

	$response_array['sql'] = $sql; 
	$response_array['add_user_entry'] = $add_user_entry; 

	ob_start();
		build_report_options(array("table_name"=>$table_name));
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
		$update_option=update_option( $option="save_explore_roles", $value );
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
















































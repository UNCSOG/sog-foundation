<?php 

if( !session_id() ) {
	session_start();
}

ini_set('display_errors', 0);
// error_reporting(E_ERROR);
// error_reporting(E_ALL);


function sog_explore_admin_page(){
	
	sog_ex_update_tables();

	
	$menu_items=array(
					array("name"=>"Start Here","slug"=>"sog_ex_setting_start_here"),
					// array("name"=>"Settings","slug"=>"sog_ex_setting_settings"),
					array("name"=>"Choose Tables","slug"=>"sog_ex_setting_choose_tables"),
					// array("name"=>"Behind the Curtain","slug"=>"sog_ex_setting_data"),
					);
	
?>
	
	<div class="row my-2">
		<div class="col">
			<h1 class="text-center">
				SOG Database Explorer
			</h1>
			<h6 class="text-center">
				UNC School of Government
			</h6>
		</div>
	</div>
	<div class="row m-4">
		<div class="col-sm-3 col-xl-2 py-2 bg-white sog_ex_settings_menu border">
			<?php foreach ($menu_items as $menu) { ?>
				<div class="btn border  shadow-sm sog_ex_menu_button my-1"
					data-slug="<?php echo $menu['slug'];?>"
				>
					<?php echo $menu['name'];?>
				</div>
			<?php } ?>
		</div>
		<div id="display_section" class="col-sm-9 col-xl-10 border settings_col">
		</div>
	</div>

<?php	
}

function display_sog_ex_setting_choose_tables(){
?>
	<div class="row my-3 section"
		data-slug="sog_ex_setting_choose_tables"
	>
		<div class="col my-3">
			<div class="h3 text-center">
				Choose Tables from the Database
			</div>
			<div class="small text-center">
				The Display Name column will allow you to override the table name.
			</div>
		</div>
	</div>
	<div class="row my-2">
		<div class="col">
			Select <span class="sog_ex_select_all_none_choose_tables clickable" data-which="all">All</span> / <span class="sog_ex_select_all_none_choose_tables clickable" data-which="none">None</span>
		</div>
	</div>
		<?php
			$sortable=0;
			// $add_row_0="name";
			// $third_field="";
			// $add_new_row=1;
			// $field_class=array(null);
			$input_col=array(2);
			$checkbox_col=array(0);
			$update_i_s_ss="ss";
			// $delete_column=1;
			$sog_ex_skinny_col=array(0);
			// $skip_col=array(1,3);
			$id_column_name="name";
			$col_names=array("Include", "Name", "Display Name");
			$table_data=sog_explore_get_generic_sql(array("table_name"=>"sog_ex_report_tables","fields"=>"use_this, name, display_name","order_by"=>"order by name","where"=>"and status=1"));

			sog_ex_display_generic_manage_table(array("table_data"=>$table_data,
					"field_class"=>$field_class,
					"delete_column"=>$delete_column,
					"input_col"=>$input_col,
					"checkbox_col"=>$checkbox_col,
					"textarea_col"=>$textarea_col,
					"table_display_name"=>$data['table_display_name'],
					"col_names"=>$col_names,
					"add_row_0"=>$add_row_0,
					"sog_ex_skinny_col"=>$sog_ex_skinny_col,
					"sortable"=>$sortable,
					"skip_col"=>$skip_col,
					"use_generic_manage"=>1,
					"table_name"=>"sog_ex_report_tables",
					"add_new_row"=>$add_new_row,
					"id_column_name"=>$id_column_name,
					"update_i_s_ss"=>$update_i_s_ss,
					)
			);
		?>

<?php
}

function display_sog_ex_setting_start_here(){
?>
	<div class="row my-3 section"
		data-slug="sog_ex_setting_start_here"
	>
		<div class="col my-3">
			<div class="h3 text-center">
				Getting Started
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col">
			<div class="my-2">
				<h5>
					What is this?
				</h5>
				This gives you read only access to the entire WordPress database.
			</div>
			<div class="my-2">
				<h5>
					How do I use this?
				</h5>
				<ol>
					<li>First thing you need to do is choose which tables you want to display.  
						Click the button on the left labeled "Choose Tables" to see all the tables within the database.
					<li>Add this shortcode to any page or post. <code>[sog_explore_sql]</code>
				</ol>
			</div>
		</div>
	</div>
	<div class="row my-3">
		<div class="col">
			<ol>
				
			</ol>
		</div>
	</div>

<?php
}

function display_sog_ex_setting_data(){
?>
	<div class="row my-3 section"
		data-slug="data"
	>
		<div class="col my-3">
			<div class="h3 text-center">
				Data Tables
			</div>
			<div class="form-text text-center">
				<i>Changes made here will affect these items <b><u>everywhere</b></u></i>
			</div>
		</div>
		<?php sog_ex_display_manage_table_select(null);?>
	</div>

<?php
}

function sog_ex_display_manage_table_select($data) {
		$datasets=array(
			array("slug"=>"sog_ex_report_tables","name"=>"Choose DB Tables"),
			// array("slug"=>"sog_ex_setting","name"=>"Settings"),
			);
			usort($datasets, "sort_by_alpha"); //sort results desc on similar %
?>
	<div class="row">
		<div class="col-md-4 offset-md-4 text-start">
			<div class="input-group mb-3">
				<select class="form-select sog_ex_load_manage_dataset">
					<option value="">Choose Table
					<?php foreach ($datasets as $dataset) {?>
						<option value="<?php echo $dataset['slug'];?>"
							data-display_name="<?php echo $dataset['name'];?>"
							data-ct="<?php echo $dataset['ct'];?>"
						><?php echo $dataset['name'];?>
					<?php } ?>
				</select>
				<div class="px-3 py-2 bg-secondary text-white sog_ex_reload_manage_table clickable" title="Refresh Table">
					<i class="fas fa-sync-alt"></i>
				</div>
			</div>
		</div>
	</div>
	<div class="manage_table_wrapper">
		<?php
		
		?>

	</div>

<?php	
}

function sog_ex_display_manage_table_html($data) {
	$delete_column=1;
	if ($data['table_name']=="sog_ex_report_tables") {
		$sortable=0;
		// $add_row_0="name";
		// $third_field="";
		$add_new_row=1;
		$field_class=array("sog_explore_choose_table");
		$input_col=array(1,2,3,4);
		$checkbox_col=array(0);
		$update_i_s_ss="ss";
		// $delete_column=1;
		// $sog_ex_skinny_col=array(0);
		// $skip_col=array(1,3);
		$id_column_name="name";
		$col_names=array("Use", "Name", "Display Name","Description");
		$helper_text="";
		$table_data=sog_explore_get_generic_sql(array("table_name"=>$data['table_name'],"fields"=>"use_this, name, display_name, description","order_by"=>"order by name","where"=>"and status=1"));
	}else{
		$sortable=0;
		$add_row_0="name";
		$third_field="";
		$add_new_row=1;
		$input_col=array(1,2);
		// $checkbox_col=array(2);
		$sog_ex_skinny_col=array(0);
		// $skip_col=array();
		$col_names=array("ID","Name","Description");
		$table_data=sog_explore_get_generic_sql(array("table_name"=>$data['table_name'],"fields"=>"id,name,description","order_by"=>"order by id desc"));
	}
?>
	<div class="row mt-4">
		<div class="col h5 text-center">
			<?php echo $data['table_display_name'];?>
		</div>
		<div class="small text-center">
			<?php echo $helper_text;?>
		</div>
	</div>
	<div class="row my-2">
		<div class="col-sm-12 manage_dataset_output">
		<?php
			if (!$download_only) {
				sog_ex_display_generic_manage_table(array("table_data"=>$table_data,
													"field_class"=>$field_class,
													"delete_column"=>$delete_column,
													"input_col"=>$input_col,
													"checkbox_col"=>$checkbox_col,
													"textarea_col"=>$textarea_col,
													"table_display_name"=>$data['table_display_name'],
													"col_names"=>$col_names,
													"add_row_0"=>$add_row_0,
													"sog_ex_skinny_col"=>$sog_ex_skinny_col,
													"sortable"=>$sortable,
													"skip_col"=>$skip_col,
													"use_generic_manage"=>1,
													"table_name"=>$data['table_name'],
													"add_new_row"=>$add_new_row,
													"id_column_name"=>$id_column_name,
													"update_i_s_ss"=>$update_i_s_ss,
													)
											);
			}
				// echo sog_explore_build_simple_table(array("table"=>$table_data,"class"=>"generic_table_download_only"));
		?>

		</div>
	</div>
<?php
}

function sog_ex_display_generic_manage_table($data) {
	// echo "<pre>";print_r($all_log_actions);echo "</pre>";
	$table_options=array(	"data"=>$data['table_data'], //this holds the array of data returned from sql
							"id"=>$data['table_name']."_table", //the html id of the table
							"class"=>"table table-bordered table-sm table-hover report_data_table", //the classes for this table
							"field_class"=>$data['field_class'],
							"input_col"=>$data['input_col'], //which rows beginning with 0 will have input fields
							"textarea_col"=>$data['textarea_col'], //which rows beginning with 0 will have input fields
							"checkbox_col"=>$data['checkbox_col'], //which rows beginning with 0 will have input fields
							"col_names"=>$data['col_names'], //names of columns for header row
							"id_column_name"=>$data['id_column_name'], //which col name from data is unique id col
							"db"=>$_SESSION['db_to_use'], //the name of the db
							"table_name"=>$data['table_name'], //the name of the table to be updated
							// "permission"=>array(1,2), //array of permission id's
							// "reload_on_add_row"=>1, //reload_on_add_row or reload_with_ajax or neither will do nothing
							"reload_with_ajax"=>1, 
							"add_new_row"=>$data['add_new_row'], //which col, beginning with 0, columns 1=name //only for string input fields for now
							"add_row_0"=>$data['add_row_0'], 
							"table_display_name"=>$data['table_display_name'], 
							"delete_col"=>$data['delete_column'], //Should a delete column be included
							"delete_class"=>"generic_table_delete", //Should a delete column be included
							"change_status_on_delete"=>0, //change status to 0 instead of deleting
							"closest"=>"tr",
							"sog_ex_skinny_col"=>$data['sog_ex_skinny_col'],
							// "wide_row"=>1, //which col has the description and should be longer.
							"form_control"=>1, //should form-control be added to inputs
							"table_sort_head"=>1, 
							"sortable"=>$data['sortable'], //drag and drop sortable
							"use_span_instead_of_input"=>1, //for non input cells
							"use_filter"=>true,
							"wrapping_class"=>".generic_input_table_wrapper",
							"use_generic_manage"=>$data['use_generic_manage'],
							"skip_col"=>$data['skip_col'],
							"allow_duplicates"=>false,
							"use_add_new_button"=>false,
							"update_i_s_ss"=>$data['update_i_s_ss'],
	);
	 $table_html=sog_ex_build_table_with_inputs($table_options);
	 echo $table_html;
}

function display_sog_ex_setting_settings(){
// $settings=get_setting_data(null);
// echo "<pre>";print_r($settings);echo "</pre>";
?>
	<div class="my-3 row"
		data-slug="setting_feedback"
	>
				<div class="col-md-12">
					<div class="h3 text-center">
						Settings
					</div>
				</div>
				<div class="col">
					<table class="table table-bordered">
						<?php foreach ($settings as $setting) {?>
							<?php if ($previous_category != $setting['category']) { ?>
								<tr class="bg-unc_navy text-white">
									<td colspan=3 class="h5">
										<?php echo $setting['category'];?>
									 </td>
								</tr>
							<?php } ?>
							<tr>
								<td>
									<?php echo $setting['id'];?>
								</td>
								<td class="text-center">
										<?php if ($setting['is_bool']) {?>
											<label class="checkbox-inline" ></label>
												<input type="checkbox" class="update_setting xform-check-input" 
													data-setting_id="<?php echo $setting['id'];?>"
													<?php if ($setting['value']) { echo "checked";}?>
													data-is_bool="<?php echo $setting['is_bool'];?>"
													>
										<?php }else { ?>
												<input  type="text" class="form-control update_setting" value="<?php echo htmlspecialchars($setting['value']);?>"
													data-setting_id="<?php echo $setting['id'];?>"
													>
										<?php } ?>
								</td>
								<td class="">
											<div class="" title="<?php echo $setting['id'];?>">
												<?php echo $setting['name'];?>
											</div>
											<div class="small">
												<?php echo $setting['description'];?>
											</div>
										</span>
											

								</td>
							</tr>
							<?php $previous_category=$setting['category'];?>
						<?php } ?>
					</table>	
				</div>
	</div>

<?php
}

function sog_ex_get_setting_data($data) {
	$fx=__FUNCTION__;
	if ($data['order_by']) {
		$order_by="order by ".$data['order_by'];
	}else{
		$order_by="order by category, sort";	
	}
	if ($data['in_dom']) {
		$in_dom_sql="and in_dom=1";
	}else{
		$in_dom_sql="";
	}
	$new_array=[];
	$con = sog_explore_db_connect(null) or die("Couldn't connect to db.");

	$sql="SELECT id,name, is_bool, is_num, value, category, sort, description
		FROM setting
		where 1=?
		$in_dom_sql
		$order_by
	";
	// return $sql;
	$place_holder=1;
	$stmt = $con->prepare($sql) or die ("couldnt prepare ".$fx." ".$sql);
	$stmt -> bind_param("i",$place_holder) or die ("couldnt bind ".$fx);
	$stmt->execute() or die ("couldnt execute ".$fx);
	$sql_result = $stmt->get_result();
	$num_rows = mysqli_num_rows($sql_result);
	$cols=[];while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
		$new_array[] = $row;
	}
return $new_array;
}

function sog_ex_update_tables() {
	$all_tables=sog_explore_get_tables(null);
	// echo "all_tables<pre>";print_r($all_tables);echo "</pre>";
	$sql="insert ignore into sog_ex_report_tables (name) values ";
	foreach ($all_tables as $table) { 
		$table_name=$table['TABLE_NAME'] ?: $table['table_name'];
		$sql.="('".$table_name."'),";
	}
	$sql = rtrim($sql, ',');
	// echo $sql;
	sog_explore_generic_sql_query(array("sql"=>$sql));
}

function sog_ex_create_sql_tables(){
	// $con = sog_explore_db_connect(null) or die("Couldn't connect to db.");
	
	$sql_sog_ex_setting="
		CREATE TABLE IF NOT EXISTS sog_ex_setting (
			id int NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			is_bool int DEFAULT NULL,
			is_num int DEFAULT NULL,
			value text,
			description text,
			category varchar(50) DEFAULT NULL,
			in_dom int DEFAULT NULL,
			sort float(6,2) DEFAULT NULL,
			status int DEFAULT '1',
			date_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		);
		";
	$sql_sog_ex_report_tables="
		CREATE TABLE IF NOT EXISTS sog_ex_report_tables (
			name varchar(100) NOT NULL,
			use_this int(11) null,
			description text,
			display_name varchar(255) DEFAULT NULL,
			sort_order decimal(4,2) DEFAULT NULL,
			status int DEFAULT '1',
			date_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (name)
		);
		";
	$sql_sog_ex_explore_filter="

		CREATE TABLE IF NOT EXISTS sog_ex_explore_filter (
			name varchar(100) NOT NULL COMMENT 'sql table name',
			field_name varchar(50) NOT NULL,
			si varchar(1) DEFAULT 's',
			fk_username varchar(50) NOT NULL,
			status int DEFAULT '1',
			date_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (name,field_name,fk_username)
		);
	";
	$return['sog_ex_setting']=sog_explore_generic_sql_query(array("sql"=>$sql_sog_ex_setting));
	$return['sql_sog_ex_report_tables']=sog_explore_generic_sql_query(array("sql"=>$sql_sog_ex_report_tables));
	$return['sql_sog_ex_explore_filter']=sog_explore_generic_sql_query(array("sql"=>$sql_sog_ex_explore_filter));

	// if (mysqli_multi_query($con,$sql)) {
	// }else{
	// }
return $return;	
}

function display_sog_explore_sql($atts){

	display_sog_explore_report_builder();

?>
	<div class="row">
		<div class="col">
		</div>
	</div>
<?php
}


function sog_explore_get_report_data($data) {
	$fx=__FUNCTION__;
	$new_array=[];
	
	$selects=implode(",",$data['field_names']);

	$wheres=implode(" ",$data['filters']);
	$wheres=stripslashes($wheres);

	$from=$data['table_name'];
	
	// $wheres="";

	$order="";

	if ($data['limit']) {
		$limit="limit ".$data['limit'];
	}else{
		$limit="";
	}
	
	
	$con = sog_explore_db_connect(null) or die("Couldn't connect to db.");
	$sql="
		select ".$selects." 
		from ".$from." 
		where 1=?
		".$wheres." 
		".$order." 
		".$limit."

	";
	$return['sql']=$sql;
	$place_holder=1;
	$stmt = $con->prepare($sql) or die ("couldnt prepare ".$fx." ".$sql);
	$stmt -> bind_param("i",$place_holder) or die ("couldnt bind ".$fx."-".$sql);
	$stmt->execute() or die ("couldnt execute ".$fx);
	$sql_result = $stmt->get_result();
	$num_rows = mysqli_num_rows($sql_result);
	$cols=[];while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
		$new_array[] = $row;
	}
	$return['table_data']=$new_array;
return $return;
}

function display_sog_explore_report_builder(){

	if ($_GET['x_debug']=='drop' and $_SESSION['sog_explore_user_login']=='darren') {
		$sql_drop="DROP TABLE IF EXISTS sog_ex_setting;";
		$drop[]=sog_explore_generic_sql_query(array("sql"=>$sql_drop));
		$sql_drop="DROP TABLE IF EXISTS sog_ex_report_tables;";
		$drop[]=sog_explore_generic_sql_query(array("sql"=>$sql_drop));
		$sql_drop="DROP TABLE IF EXISTS sog_ex_explore_filter;";
		$drop[]=sog_explore_generic_sql_query(array("sql"=>$sql_drop));

		echo "drop<pre>";print_r($drop);echo "</pre>";
	}
	if ($_GET['x_debug']=='create' and $_SESSION['sog_explore_user_login']=='darren') {
		$create=sog_ex_create_sql_tables();
		echo "create<pre>";print_r($create);echo "</pre>";
	}
	
	//dropdown to choose table
	$report_tables=sog_explore_get_tables_to_use(null);
	// echo "report_tables<pre>";print_r($report_tables);echo "</pre>";

	

?>
	<div class="report_builder p-2">
		<div class="row">
			<div class="col-4 offset-4">
				<label class="form-label">Table</label>
				<select class="form-control sog_ex_select_report_table">
					<option value="">
					<?php foreach ($report_tables as $table) { ?>
						<option value="<?php echo $table['name'];?>"><?php echo $table['table_name'];?>
					<?php } ?>
				</select>
			</div>
		</div>
		<div class="row">
			<div class="col report_fields_here">
			</div>
		</div>
		<div class="row pt-3 mt-3 border-top ">
			<?php sog_explore_display_report_show_all_options(); ?>
			<div class="col sog_ex_report_table_here p-2">
			</div>
		</div>
	</div>
<?php
}


function sog_explore_display_report_show_all_options() { 
	// echo "data<pre>";print_r($data);echo "</pre>";
?>
	<div class="sog_ex_hide_until_limit_ready">
		<div class="row sog_ex_report_showing_subset">
			<div class="col">
				This is a subset showing only <span class="report_count_number sog_ex_report_sample_number"></span> records of the <span class="report_count_number sog_ex_report_total_number"></span> total records.
				<span class="btn bg-success text-white sog_ex_report_get_all_records">
					Get me all records!
				</span>
			</div>
		</div>
		<div class="row sog_ex_report_showing_all my-2">
			<div class="col">
				Showing all <span class="report_count_number sog_ex_report_total_number"></span> records.
			</div>
		</div>
	</div>

<?php
}

function sog_explore_build_report_options($data){
	//$data['table_name']
	$fields=sog_explore_get_table_fields(array("table_name"=>$data['table_name']));

	// echo "data<pre>";print_r($fields);echo "</pre>";
	sog_explore_display_report_field_checkboxes(array("fields"=>$fields,"table_name"=>$data['table_name']));
	
	echo "<div class='sog_ex_explore_filters_here'>";
		sog_explore_display_report_field_filters(array("table_name"=>$data['table_name'],"where_based_on_other_filters"=>$data['where_based_on_other_filters']));
	echo "</div>";
	// display_report_limit_options();
?>

<?php
}

function sog_explore_get_tables_to_use($data) {
	$fx=__FUNCTION__;
	$con = sog_explore_db_connect(null) or die("Couldn't connect.");
	$sql=" SELECT name, use_this, description, display_name, COALESCE(display_name,name) as table_name
		FROM sog_ex_report_tables
		WHERE 1=?
		and use_this=1
		and status=1
		order by sort_order,name

	
	";
	// echo $sql."-".$_SESSION['db_to_use'];
	$place_holder=1;
	$stmt = $con->prepare($sql) or die("Couldn't prepare ".$fx.". ".$sql); 
	$stmt -> bind_param("i",$place_holder) or die("Couldn't bind ".$fx." ."); 
	$stmt->execute() or die("Couldn't execute ".$fx." ."); 
	$sql_result = $stmt->get_result();
	$num_rows = mysqli_num_rows($sql_result);	
	$cols=[];while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
		$new_array[] = $row;
	}	
return $new_array;
}

function sog_explore_get_tables($data) {
	$con = sog_explore_db_connect(null) or die("Couldn't connect.");
	$sql=" SELECT table_name 
			FROM information_schema.tables
			where 1=?
			and table_schema=?

	
	";
	// echo $sql."-".$_SESSION['db_to_use'];
	$place_holder=1;
	$stmt = $con->prepare($sql);
	$stmt -> bind_param("is",$place_holder,$_SESSION['db_to_use']);
	$stmt->execute();
	$sql_result = $stmt->get_result();
	$num_rows = mysqli_num_rows($sql_result);	
	$cols=[];while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
		$new_array[] = $row;
	}	
return $new_array;
}

function sog_explore_get_table_fields($data) {
		// echo "<pre>";print_r($_SESSION);echo "</pre>";

	$fx=__FUNCTION__;
	$new_array=[];
	$con = sog_explore_db_connect(null) or die("Couldn't connect to db.");
	
	if (1) {
		$order="order by COLUMN_NAME";
	}else{
		$order="";
	}

	$sql="
		SELECT COLUMN_NAME, DATA_TYPE
		FROM INFORMATION_SCHEMA.COLUMNS
		WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
		$order
		;
	";
	// return $sql."-".$data['table_name']."-".$_SESSION['db_to_use'];
	$place_holder=1;
	$stmt = $con->prepare($sql) or die ("couldnt prepare ".$fx." ".$sql);
	$stmt -> bind_param("ss",$_SESSION['db_to_use'],$data['table_name']) or die ("couldnt bind ".$fx);
	$stmt->execute() or die ("couldnt execute ".$fx);
	$sql_result = $stmt->get_result();
	$num_rows = mysqli_num_rows($sql_result);
	$cols=[];while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
		$new_array[] = $row;
	}

return $new_array;
}

function sog_explore_display_report_field_filters($data) { 
	// echo "data<pre>";print_r($data);echo "</pre>";
	
	$filters=sog_explore_get_generic_sql(array("table_name"=>"sog_ex_explore_filter",
	"fields"=>"name, field_name, si, fk_username",
	"order_by"=>" order by date_modified",
	"where"=>"	and fk_username='".$_SESSION['sog_explore_user_login']."'
				and name='".$data['table_name']."'
				and status=1 "));
	// echo "filters<pre>";print_r($filters);echo "</pre>";

	
?>
	<?php if ($filters) { ?>
		<div class="report_filter_wrapper">
			<div class="row mt-2">
				<div class="col-3">
					<h4>
						Choose Filters
						<?php sog_ex_create_help_block("<br>The drop down searches for an exact match.  <br>The input field searches by partial match."); ?>
					</h4>
				</div>
			</div>
			<div class="report_filters py-2">
				<?php foreach ($filters as $filter) { ?>
					<?php
						$where_based_on_other_filters=implode(" ",$data['array_where_based_on_other_filters']);
						$order="order by ".$filter['field_name'];
						$values=sog_explore_get_generic_sql(array("table_name"=>$data['table_name'],
						// "where"=>$where_based_on_other_filters, //this will filter the chocies based on the other filters
						"fields"=>$filter['field_name'],
						"order_by"=>$order,
						"group_by"=>"group by ".$filter['field_name']));
						// echo "values<pre>";print_r($values);echo "</pre>";
						// echo $where_based_on_other_filters;
						?>
					
					<div class="report_filters">
						<label class="form-label">
						<?php 
							// optionally beautify name
							$name_to_display=$filter['field_name'];
							if (1) {
								$name_to_display=sog_explore_beautify_field_name(array("field_name"=>$filter['field_name']));
							}else{
							}
							echo $name_to_display;
						?>
						</label>
						<select class="form-control sog_ex_explore_filter"
							id="filter_<?php echo $filter['field_name'];?>"
							data-filter_field_name="<?php echo $filter['field_name'];?>"
							data-si="<?php echo $filter['si'];?>"
						>
							<option value="">Select <?php echo $name_to_display;?>
							<?php foreach ($values as $value) { ?>
								<?php 
									//this will make the dropdown only show 1 value, which works but sometimes you want to be able to switch the choice without first cancelling the choice
									if (count($values)==1) {
										// $selected="selected";
									}else{
										// $selected="";
									}
										?>
								<option <?php echo $selected;?> value="<?php echo $value[$filter['field_name']];?>"><?php echo $value[$filter['field_name']]; ?>
							<?php } ?>
						</select>
						<div class="">
							<input class="form-control mt-1 sog_ex_explore_filter_like" type="text" placeholder="Text Filter"
								id="filter_<?php echo $filter['field_name'];?>"
								data-filter_field_name="<?php echo $filter['field_name'];?>"
								data-si="<?php echo $filter['si'];?>"
							>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
	<?php } ?>

<?php
}

function sog_explore_display_report_field_checkboxes($data) { 
	// echo "data<pre>";print_r($data);echo "</pre>";

	//get filters so i know which icons should be green
	$filters=sog_explore_get_generic_sql(array("table_name"=>"sog_ex_explore_filter",
	"fields"=>"group_concat(field_name) as field_names_csv",
	"where"=>"	and fk_username='".$_SESSION['sog_explore_user_login']."'
				and name='".$data['table_name']."'
				and status=1 "));
	$filters_array=explode(",",$filters[0]['field_names_csv']);
	// echo "filters_array<pre>";print_r($filters_array);echo "</pre>";

?>
	<div class="report_checkbox_wrapper table_info"
		data-table_name="<?php echo $data['table_name'];?>"
		data-fk_schema_name="<?php echo $data['db_name'];?>"
	>
		<div class="row mt-2">
			<div class="col-3">
				<h4>
					Choose Fields <span class="text-danger">*</span>
				</h4>
			</div>
			<div class="col-3 offset-6">
				<input type="text" class="form-control sog_explore_filter_this_generic_span" placeholder="Filter Fields"
					data-wrapper=".report_checkbox_wrapper"
				>
			</div>
		</div>
		<div class="report_checkboxes py-2"
			data-table_name="<?php echo $data['table_name'];?>"
		>
			<?php foreach ($data['fields'] as $field) { ?>
				<?php 
					if (in_array($field['COLUMN_NAME'],$filters_array)) {
						$icon_color="text-success";
					}else{
						$icon_color="text-secondary";
					}
				?>
				<div class="form-check filter_row">
					<input class="form-check-input sog_ex_report_checkbox" type="checkbox" value="<?php echo $data['table_name'].".`".$field['COLUMN_NAME'];?>`">
					<label class="filter_this" title="<?php echo $field['COLUMN_NAME'];?>">
						<?php echo $field['COLUMN_NAME'];?>	
					</label>
						<i class="fas fa-filter <?php echo $icon_color;?> sog_ex_add_explore_filter clickable"
							data-field_name="<?php echo $field['COLUMN_NAME'];?>"
							data-data_type="<?php echo $field['DATA_TYPE'];?>"
						></i>		
				</div>
			<?php } ?>
		</div>
	</div>

<?php
}

function sog_explore_beautify_field_name($data) {
	//show display name if exists, if not split name on _ and capatilize first letter
	//change to lower case
	$temp_name=strtolower($data['field_name']);
	
	//split on _
	$temp_name=explode("_",$temp_name);
	
	//put back together with spaces
	$temp_name=implode(" ",$temp_name);
	
	//capatilize each word
	$name_to_display=ucwords($temp_name);
return $name_to_display;
}

function sog_explore_build_simple_table($data) {
	if ($data['table']) {
		$table_html="";
		$headers=array_keys($data['table'][0]);
		$table_html.="<table id='".$data['id']."' class='".$data['class']."'>";
			$table_html.= "<thead>";
				$table_html.= "<tr>";
					if ($data['ordinal_column']) {
						$table_html.="<th>#</th>";
					}
					foreach ($headers as $header) {
						$table_html.= "<th>";
							$table_html.= $header;
						$table_html.= "</th>";
					}
				$table_html.= "</tr>";
			$table_html.= "</thead>";
			$table_html.= "<tbody>";
					foreach ($data['table']as $key=>$rows) {
						$table_html.= "<tr>";
							if ($data['ordinal_column']) {
								$ordinal_number=$key+1;
								$table_html.="<td>".$ordinal_number."</td>";
							}
							foreach ($rows as $row) {
								$table_html.= "<td>";
									$table_html.= $row;
								$table_html.= "</td>";
							}
						$table_html.= "</tr>";
					}
			$table_html.= "</tbody>";
		$table_html.= "</table>";
	}else{
		$table_html=null;
	}
return $table_html;
}

function sog_ex_build_table_with_inputs($options) {
	// echo "<pre>options";print_r($options);echo "</pre>";

	$add_link=$options['add_link'];if (!is_array($add_link)) {$add_link=[];} //not coded yet
	$add_new_row=$options['add_new_row'];
	$add_row_0=$options['add_row_0'];
	$allow_duplicates=$options['allow_duplicates'];
	$change_status_on_delete=$options['change_status_on_delete'];
	$checkbox_col=$options['checkbox_col'];if (!is_array($checkbox_col)) {$checkbox_col=[];}
	$class=$options['class'];
	$closest=$options['closest'];
	$col_names=$options['col_names'];
	$field_class=$options['field_class'];if (!is_array($field_class)) {$field_class=[];}
	$db=$options['db'];
	$delete=$options['delete_col'];
	$delete_class=$options['delete_class'];
	$id=$options['id'];
	$id_column_name=$options['id_column_name'];if (!$id_column_name) {$id_column_name="id";}
	$ignore_last_inserted_id=$options['ignore_last_inserted_id'];
	$input_col=$options['input_col'];if (!is_array($input_col)) {$input_col=[];}
	$order_column=$options['order_column'];
	$order_direction=$options['order_direction'];
	$permission=$options['permission'];if (!is_array($permission)) {$permission=[];}
	$prevent_update=$options['prevent_update'];if (!is_array($prevent_update)) {$prevent_update=[];}
	$reload_on_add_row=$options['reload_on_add_row'];
	$reload_with_ajax=$options['reload_with_ajax'];
	$sog_ex_skinny_col=$options['sog_ex_skinny_col'];if (!is_array($sog_ex_skinny_col)) {$sog_ex_skinny_col=[];}
	$skip_col=$options['skip_col'];if (!is_array($skip_col)) {$skip_col=[];}
	$sortable=$options['sortable'];
	$table=$options['data'];if (!is_array($table[0])) {$table[0]=[];}
	$table_name=$options['table_name'];
	$table_display_name=$options['table_display_name'];
	$table_sort_head=$options['table_sort_head'];
	$td_listener_class=$options['td_listener_class'];
	$title=$options['title'];
	$update_i_s_ss=$options['update_i_s_ss'];
	$use_filter=$options['use_filter'];
	$use_generic_manage=$options['use_generic_manage'];
	$use_add_new_button=$options['use_add_new_button'];
	$use_span_instead_of_input=$options['use_span_instead_of_input'];
	$wide_row=$options['wide_row'];if (!is_array($wide_row)) {$wide_row=[];}
	$wrapping_class=$options['wrapping_class'];
	if ($options['table_sort_head']) {$table_sort_head="table_sort_head";$th_clickable="clickable";}
	if ($options['form_control']) {$form_control="form-control";}
	if ($sortable) {$sortable_wrapper="build_form_sortable";}else{$sortable_wrapper="";}
	
	foreach ($permission as $perm) {
		$perm_arr_of_hash[]=$perm;
	}
	if (!$update_i_s_ss) {$update_i_s_ss="s";}
	if (!is_array($perm_arr_of_hash)) {$perm_arr_of_hash=[];}
	$perm_csv_of_hash=implode(",",$perm_arr_of_hash);

	$table_html="";
	$headers=array_keys($table[0]);
	$table_html.="<div class='generic_input_table_wrapper'>";
		if ($use_filter and $wrapping_class) {
			$table_html.='
					<div class="row my-2">
						<div class="col-md-4 offset-md-4">
							<div class="input-group">
								<input type="text" placeholder="Instant Filter"
									class="form-control sog_ex_filter_this_generic_input w-50 mx-auto"
									data-wrapper="'.$wrapping_class.'"
								>
								<span class="input-group-text  bg-primary text-white">
									<i class="fas fa-search"></i>
								</span>
							</div>
						</div>
					</div>
				';
			
		}
		$table_html.="<table id='".$id."' class='generic_input_table ".$class."' data-initial_sort='".$order_column."' data-order_direction='".$order_direction."'>";
			$table_html.= "<thead>";
				$table_html.= "<tr>";
					$th_count=0;
					foreach ($headers as $header) {
						if (!in_array($th_count,$skip_col)) {
							$sog_ex_skinny_col_style="";
							if (in_array($th_count,$sog_ex_skinny_col)) {$sog_ex_skinny_col_style="sog_ex_skinny_col";}
							// if (in_array($th_count,$wide_row)) {$style_width=" width:200px; ";}
							$table_html.= "<th style='' class='".$sog_ex_skinny_col_style." ".$table_sort_head." ".$th_clickable."'
								data-has_add_new='".$add_new_row."'
							>";
								if ($col_names[$th_count]) {
									$table_html.= $col_names[$th_count];
								}else{
									$table_html.= $header;
								}
							if ($table_sort_head) {
								$table_html.="<i class='fad fa-sort text-muted ps-2'></i>";
							}
							$table_html.= "</th>";
						}
						$th_count++;
					}
					if ($delete) {
						$table_html.="<th class='sog_ex_skinny_col'></th>";
					}
				$table_html.= "</tr>";
			$table_html.= "</thead>";
			$table_html.= "<tbody class=' ".$sortable_wrapper." '
				data-db='".$db."'  
				data-table_name='".$table_name."' 
				data-id_column_name='".$id_column_name."' 
			>";
			if ($add_new_row) {
				$table_html.="<tr class='add_new'>";
					$headers_count=count($headers)-count($skip_col);
					for ($x=0;$x<$headers_count;$x++) {
						$table_html.="<td>";
								if ($x==$add_new_row) {
									$table_html.='
									<div class="input-group">
										<input type=text title="Add New '.$col_names[$x].'" placeholder="Add New '.$col_names[$x].'" 
											class="generic_table_add_new '.$form_control.'" 
											data-allow_duplicates="'.$allow_duplicates.'" 
											data-use_generic_manage="'.$use_generic_manage.'" 
											data-si="s" 
											data-db="'.$db.'" 
											data-table_name="'.$table_name.'" 
											data-field_name="'.$headers[$x].'" 
											data-col_num="'.$add_new_row.'" 
											data-reload_on_add_row="'.$reload_on_add_row.'" 
											data-reload_with_ajax="'.$reload_with_ajax.'" 
											data-p="'.$perm_csv_of_hash.'" 
											data-ignore_last_inserted_id="'.$ignore_last_inserted_id.'" 
										>';
										if ($use_add_new_button) {
											$table_html.='
											<span class="input-group-text add_new_item bg-white px-2 py-0 clickable">
												<i class="bi bi-plus-square-fill text-success bg-white"></i>
											</span>
											';
										}
										$table_html.='
									</div>';
								}
						$table_html.="</td>";
					}
					if ($delete) {
						$table_html.="<td></td>";
					}

				$table_html.="</tr>";
			}
					foreach ($table as $rows) {
						$table_html.= "<tr class='filter_row ".$table_name."_row_".$rows[$id_column_name]."'>";
							$col_count=0;
							foreach ($rows as $row) {
								if (!in_array($col_count,$skip_col)) {
									$sog_ex_skinny_col_style="";
									if (in_array($col_count,$sog_ex_skinny_col)) {$sog_ex_skinny_col_style="sog_ex_skinny_col";}
									$col_name=$headers[$col_count];
									$value=htmlspecialchars($row);
									$id_value=$rows[$id_column_name];if (!$id_value){$id_value="no_id_value_".mt_rand(1,1000000);}
									$table_html.= "<td class='filter_this ".$sog_ex_skinny_col_style."'>";
									if ($col_count==0 and $sortable) {
										$table_html.='<span class="sort_handle"
										data-'.$id_column_name.'='.$id_value.'
										>
										<i class="bi bi-grip-horizontal"></i></span>';
									}
										if (in_array($col_count,$input_col)) {
											$table_html.='<input type=text title="'.$value.'" class=" sog_ex_generic_update '.$td_listener_class.' '.$field_class[$col_count].' '.$form_control.'"
															data-si="'.$update_i_s_ss.'" 
															data-db="'.$db.'" 
															data-table_name="'.$table_name.'" 
															data-field_name="'.$col_name.'" 
															data-id_column_name="'.$id_column_name.'" 
															data-'.$id_column_name.'="'.$id_value.'" 
															data-p="'.$perm_csv_of_hash.'" 
															id="'.$id.'_'.$col_name.'_'.$id_value.'" 
															value="'.$value.'"
															aria-label="'.$col_names[$col_count].'"
															><span class="sort_helper_hidden">'.$value.'</span>';
										}elseif (in_array($col_count,$prevent_update)) {
											$table_html.='<input type=text title="'.$value.'" class="  '.$td_listener_class.' '.$form_control.'"
															readonly disabled
															value="'.$value.'"
															aria-label="'.$col_names[$col_count].'"
															><span class="sort_helper_hidden">'.$value.'</span>';
										}elseif (in_array($col_count,$checkbox_col)) {
											if ($row) {$checked="checked";}else{$checked="";}
											$table_html.='<input type=checkbox class=" form-check-input sog_ex_generic_update '.$td_listener_class.' '.$field_class[$col_count].' "
															title="'.$col_name.'" 
															'.$checked.'
															data-si="'.$update_i_s_ss.'" 
															data-'.$col_name.'="'.$value.'" 
															data-db="'.$db.'" 
															data-table_name="'.$table_name.'" 
															data-field_name="'.$col_name.'" 
															data-id_column_name="'.$id_column_name.'" 
															data-'.$id_column_name.'="'.$id_value.'" 
															id="'.$id.'_'.$col_name.'_'.$id_value.'" 
															data-p="'.$perm_csv_of_hash.'" 
															value="1"
															aria-label="'.$col_names[$col_count].'"
															><span class="sort_helper_hidden">'.$value.'</span>';
										}else{
											if ($use_span_instead_of_input) {
												if ($col_names[$col_count]="Count") {
													$title_value=end($rows);
												}else{
													$title_value=$value;
												}
												$table_html.= '<span class="sort_helper_hidden">'.$value.'</span><span title="'.htmlspecialchars($title_value, ENT_QUOTES, 'UTF-8').'">'.$row.'</span>';
											}else{
												$table_html.= '<input class="form-control" title="'.$title_value.'" type=text value="'.$value.'"
															aria-label="'.$col_names[$col_count].'"
												>';
											}
										}
									$table_html.= "</td>";
								}
								$col_count++;
							}
							if ($delete) {
								$table_html.='<td class="text-center sog_ex_skinny_col"><i class="bi bi-trash '.$delete_class.' clickable"';
												if ($change_status_on_delete) {
													$table_html.='title="This will be removed from view but can be restored later."';
												}else{
													$table_html.='title="This completely deletes this record from the database."';
												}
												$table_html.='
													data-change_status="'.$change_status_on_delete.'" 
													data-closest="'.$closest.'" 
													data-db="'.$db.'" 
													data-table_name="'.$table_name.'" 
													data-'.$col_name.'="'.$value.'" 
													data-id_column_name="'.$id_column_name.'" 
													data-p="'.$perm_csv_of_hash.'" 
													data-'.$id_column_name.'="'.$id_value.'" 
													value="'.$value.'"
												></i></td>';
							}
							
						$table_html.= "</tr>";
					}
			$table_html.= "</tbody>";
			$table_html.= "<tfoot>";
			$table_html.= "</tfoot>";
		$table_html.= "</table>";
	$table_html.="</div>";
	if (!$table[0]) {
			$table_html='<input type=text title="Add New" placeholder="Add New" 
			class="generic_table_add_new '.$form_control.'" 
			data-si="s" 
			data-db="'.$db.'" 
			data-use_generic_manage="'.$use_generic_manage.'" 
			data-table_name="'.$table_name.'" 
			data-table_display_name="'.$table_display_name.'" 
			data-field_name="'.$add_row_0.'" 
			data-col_num="'.$add_new_row.'" 
			data-reload_on_add_row="'.$reload_on_add_row.'" 
			data-reload_with_ajax="'.$reload_with_ajax.'" 
			data-p="'.$perm_csv_of_hash.'" 
			data-ignore_last_inserted_id="'.$ignore_last_inserted_id.'" 
			>';

	}
return $table_html;
}

function sog_explore_generic_sql_query($data) {
		//checking if this field is valid is done in spede process
		$con = sog_explore_db_connect(null) or die("Couldn't connect.");
		if ($con->query($data['sql'])){
			$return['status']="success";
		}else {
			$return['status']="fail";
			$return['error_message']=mysqli_error($con);
		}
return $return;
}


function sog_explore_get_generic_sql($data) {
	$fx=__FUNCTION__;
	$con = sog_explore_db_connect(null) or die("Couldn't connect. ".$db_name."-".$fx);
	$sql=" SELECT ".$data['fields']." 
			FROM ".$data['table_name']." 
			WHERE 1=?
			".$data['where']." 
			".$data['group_by']."
			".$data['order_by']."
			".$data['limit']."
	";
	// echo $sql;
	$place_holder=1;
	$stmt = $con->prepare($sql) or die("Couldn't prepare ".$fx.". ".$sql); 
	$stmt -> bind_param("i",$place_holder) or die("Couldn't bind ".$fx." ."); 
	$stmt->execute() or die("Couldn't execute ".$fx." ."); 
	$sql_result = $stmt->get_result();
	$num_rows = mysqli_num_rows($sql_result);
	$cols=[];while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
		$new_array[] = $row;
	}

return $new_array;
	
}

function sog_explore_db_connect($db_name) {
	// $value=debug_backtrace()[1]['function'];
	// echo "<br>".$value;
	// array_push($_SESSION['calling_from'],$value);	
	
    if(!isset($connection)) {
		//gets set in top of far.php
		//db name is ignored since now using inside wp
		$creds=sog_explore_get_db_creds(null);
		$localhost=$creds['localhost'];
		$username=$creds['username'];
		$password=$creds['password'];
		$db_to_use=$creds['db_to_use'];

		$connection = mysqli_connect($localhost,$username,$password,$db_to_use);
    }
    if($connection === false) {
        echo mysqli_connect_error(); 
    }
    return $connection;
}

function sog_explore_get_db_creds($data) {
// echo "xxxxxxxxxx".getcwd();
	//session kept getting other local sessions like mpacp
	if (0 and $_SESSION['db_localhost']) {
		$return['localhost']=$_SESSION['db_localhost'];
		$return['username']=$_SESSION['db_username'];
		$return['password']=$_SESSION['db_password'];
		$return['db_to_use']=$_SESSION['db_to_use'];
	}else{
		if (file_exists("../../../wp-load.php")){
			require_once("../../../wp-load.php");
		}
		if (file_exists("../../wp-load.php")){
			require_once("../../wp-load.php");
		}
		if (file_exists("../wp-load.php")){
			require_once("../wp-load.php");
		}
		if (file_exists("wp-load.php")){
			require_once("wp-load.php");
		}
		if (file_exists("/wp-load.php")){
			require_once("/wp-load.php");
		}
		$get_defined_constants = get_defined_constants();
		$return['localhost'] = $get_defined_constants['DB_HOST']; 
		$return['username'] = $get_defined_constants['DB_USER']; 
		$return['password'] = $get_defined_constants['DB_PASSWORD']; 
		$return['db_to_use'] = $get_defined_constants['DB_NAME']; 
		
	}
return $return;
}

function sog_ex_update_generic_table_s($data) {
		$con = sog_explore_db_connect(null) or die("Couldn't connect to db. - ".__FUNCTION__);
		$sql="update ".$data['table_name']."
		set ".$data['field_name']."=?
		where ".$data['id_column_name']."=?
		";
		// return $sql."-".$data['value']."-".$data['id'];
		$stmt = $con->prepare($sql) or die("Couldn't prepare update_generic_table_s-".$sql."-".$data['field_name']."-".$data['id']."-".$data['value']."-".$data['id_column_name']); 
		$stmt -> bind_param("si",$data['value'],$data['id']) or die("Couldn't bind update_generic_table_s ."); 
		if ($stmt->execute()) {
			$return['status']="success";
		}else {
			$return['status']="fail";
			$return['error_message']=mysqli_error($con);
		}
return $return;	
}

function sog_ex_update_generic_table_ss($data) {
		$con = sog_explore_db_connect(null) or die("Couldn't connect to db. - ".__FUNCTION__);
		$sql="update ".$data['table_name']."
		set ".$data['field_name']."=?
		where ".$data['id_column_name']."=?
		";
		// return $sql."-".$data['value']."-".$data['id'];
		$stmt = $con->prepare($sql) or die("Couldn't prepare update_generic_table_s-".$sql."-".$data['field_name']."-".$data['id']."-".$data['value']."-".$data['id_column_name']); 
		$stmt -> bind_param("ss",$data['value'],$data['id']) or die("Couldn't bind update_generic_table_s ."); 
		if ($stmt->execute()) {
			$return['status']="success";
		}else {
			$return['status']="fail";
			$return['error_message']=mysqli_error($con);
		}
return $return;	
}

function sog_ex_update_generic_table_sss($data) {
	$fx=__FUNCTION__;
		$con = sog_explore_db_connect(null) or die("Couldn't connect to db. - ".__FUNCTION__);
		$sql="update ".$data['table_name']."
		set ".$data['field_name']."=?
		where ".$data['id_column_name']."=?
		and ".$data['id_column_name_2']."=?
		";
		// return $sql."-".$data['value']."-".$data['id']."-".$data['id_2'];
		$stmt = $con->prepare($sql) or die("Couldn't prepare ".$fx."-".$sql."-".$data['field_name']."-".$data['id']."-".$data['value']."-".$data['id_column_name']); 
		$stmt -> bind_param("sss",$data['value'],$data['id'],$data['id_2']) or die("Couldn't bind update_generic_table_s ."); 
		if ($stmt->execute()) {
			$return['status']="success";
		}else {
			$return['status']="fail";
			$return['error_message']=mysqli_error($con);
		}
return $return;	
}

function sog_ex_update_generic_table_i($data) {
		$con = sog_explore_db_connect(null) or die("Couldn't connect to db. - ".__FUNCTION__);
		$sql="update ".$data['table_name']."
		set ".$data['field_name']."=?
		where ".$data['id_column_name']."=?
		";
		// return $sql."-".$data['value']."-".$data['id'];
		// exit;
		$stmt = $con->prepare($sql) or die("Couldn't prepare update_generic_table_i sql:".$sql."-".$data['field_name']."-".$data['id']."-".$data['value']."-".$data['id_column_name']."-".$data['db_name']); 
		$stmt -> bind_param("ii",$data['value'],$data['id']) or die("Couldn't bind update_generic_table_i. value:".$data['value']." id:".$data['id']); 
		if ($stmt->execute()) {
			$return['status']="success";
		}else {
			$return['error_message']=mysqli_error($con);
		}
return $return;	
}

function sog_ex_update_generic_table_is($data) {
	//for value of i but id is s
		$con = sog_explore_db_connect(null) or die("Couldn't connect to db. - ".__FUNCTION__);
		$sql="update ".$data['table_name']."
		set ".$data['field_name']."=?
		where ".$data['id_column_name']."=?
		";
		// return $sql."-".$data['value']."-".$data['id'];
		$stmt = $con->prepare($sql) or die("Couldn't prepare update_generic_table_s-".$sql."-".$data['field_name']."-".$data['id']."-".$data['value']."-".$data['id_column_name']); 
		$stmt -> bind_param("is",$data['value'],$data['id']) or die("Couldn't bind update_generic_table_s ."); 
		if ($stmt->execute()) {
			$return['status']="success";
		}else {
			$return['status']="fail";
			$return['error_message']=mysqli_error($con);
		}
return $return;	
}

function sog_ex_create_help_block($text) {
//fas fa-info-circle
echo "<span class='far fa-question-circle sog_ex_help_info_sign clickable mx-1' title='Click for help.'></span>";
echo "<span class='sog_ex_help-block'>";
	echo "<span class='me-1 badge bg-primary'>Help</span>";
	echo $text;
echo "</span>";
}













































?>
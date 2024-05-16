<?php 

namespace sog_explore;

if( !session_id() ) {
	session_start();
}

ini_set('display_errors', 0);
// error_reporting(E_ERROR);
// error_reporting(E_ALL);


function create_default_page(){

	//create page to hold shortcode
	$page_name="SOG Database Explorer";

	$posts = get_posts(
		array(
			'post_type'              => 'page',
			'title'                  => $page_name,
			'numberposts'            => 1,
		)
	);
	if ( ! empty( $posts ) ) {
		$page_got_by_title = $posts[0];
	} else {
		$page_got_by_title = null;
	}

	// Check if the page already exists
	 if(empty($page_got_by_title)) {
		  $page_id = wp_insert_post(
			 array(
			 'post_author'    => 1,
			 'post_title'     => ucwords($page_name),
			 'post_name'      => strtolower(str_replace(' ', '-', trim($page_name))),
			 'post_status'    => 'publish',
			 'post_content'   => '[sog_explore_sql]',
			 'post_type'      => 'page',
			 )
		 );
		 //store new page id in options
		 $update_option=update_option("sog_ex_default_page_id", $page_id );
	 }else{
		 $update_option=update_option("sog_ex_default_page_id", $page_got_by_title->ID );
	 }
}


function admin_page(){

    //create sql tables needed for plugin
	create_sql_tables();

    //create default page for db explorer
    create_default_page();

	//compare tables in local db with tables this plugin has access to
	update_tables();

	//if admin, then can see everything
	if( current_user_can('administrator') ) {
		$menu_items=array(
			array("name"=>"Start Here","slug"=>"setting_start_here"),
			// array("name"=>"Settings","slug"=>"setting_settings"),
			array("name"=>"Choose Tables","slug"=>"setting_choose_tables"),
			// array("name"=>"Behind the Curtain","slug"=>"setting_data"),
			);
	}else{
		//if not, only instructions page
		$menu_items=array(
			array("name"=>"Start Here","slug"=>"setting_start_here"),
			);
	}

	
	
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
		<div class="col-sm-3 col-xl-2 py-2 bg-white settings_menu border">
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

function display_setting_choose_tables(){
	$field_class=[];
	$delete_column="";
	$input_col=[];
	$checkbox_col=[];
	$textarea_col=[];
	$col_names=[];
	$add_row_0="";
	$skinny_col=[];
	$sortable="";
	$skip_col="";
	$add_new_row="";
	$id_column_name="";
	$update_i_s_ss=[];


	$sog_ex_default_page_id=get_option( $option="sog_ex_default_page_id") ?? null;
	if ($sog_ex_default_page_id) {
		$sog_ex_default_page_url=get_page_link($sog_ex_default_page_id);
	}


?>
	<div class="row my-3 section"
		data-slug="setting_choose_tables"
	>
		<div class="col my-3">
			<div class="h3 text-center">
				Choose Tables from the Database
			</div>
			<div class="text-center">
				<?php if ($sog_ex_default_page_url){ ?>
					<a href="<?php echo $sog_ex_default_page_url;?>">Go To Explorer</a>.
				<?php } ?>
			</div>
			<div class="small text-center">
				The Display Name column will allow you to override the table name.
			</div>
			<div class="small text-center text-danger my-1">
				<strong>Warning</strong>: Allowing a table to be updated can cause critical errors.  Changes will be written directly to the database.
			</div>
		</div>
	</div>
	<div class="row my-2">
		<div class="col">
			Select <span class="select_all_none_choose_tables clickable" data-which="all">All</span> / <span class="select_all_none_choose_tables clickable" data-which="none">None</span>
		</div>
	</div>
		<?php
			$sortable=0;
			// $add_row_0="name";
			// $third_field="";
			// $add_new_row=1;
			// $field_class=array(null);
			$input_col=array(3);
			$checkbox_col=array(0,1);
			$update_i_s_ss="ss";
			// $delete_column=1;
			$skinny_col=array(0);
			// $skip_col=array(1,3);
			$id_column_name="fk_table_name";
			$col_names=array("Include", "Allow Update", "Name", "Display Name");
			$table_data=get_generic_sql(array("table_name"=>"sog_ex_report_tables","fields"=>"use_this, allow_update, fk_table_name, display_name","order_by"=>"order by fk_table_name","where"=>"and status=1"));
			// echo "table_data<pre>";print_r($table_data);echo "</pre>";

			display_generic_manage_table(array("table_data"=>$table_data,
					"field_class"=>$field_class,
					"delete_column"=>$delete_column,
					"input_col"=>$input_col,
					"checkbox_col"=>$checkbox_col,
					"textarea_col"=>$textarea_col,
					"col_names"=>$col_names,
					"add_row_0"=>$add_row_0,
					"skinny_col"=>$skinny_col,
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

function display_setting_start_here(){
	//get roles for this site to output in checkboxes
	global $wp_roles;
	$roles = $wp_roles->get_names();

	//get previously saved roles with access
    $save_explore_roles=get_option( $option="sog_ex_save_explore_roles");
    if (isset($save_explore_roles)){
        //turn unto array
        $allowed_roles=explode(",",$save_explore_roles);
	}

	$sog_ex_default_page_id=get_option( $option="sog_ex_default_page_id") ?? null;
	if ($sog_ex_default_page_id) {
		$sog_ex_default_page_url=get_page_link($sog_ex_default_page_id);
	}

	$stale_check_option=get_option("sog_ex_stale_check_option");
	if ($stale_check_option){
		$stale_check_option_checked="checked";
	}else{
		$stale_check_option_checked="";
	}

	$stale_check_option_ms=get_option("sog_ex_stale_check_option_seconds");
	if (isset($stale_check_option_ms) and $stale_check_option_ms>0){
		$stale_check_option_seconds=$stale_check_option_ms/1000;
	}else{
		$stale_check_option_seconds=0;
	}

	$subset_limit_option=get_option("sog_ex_subset_limit_option");
	if (isset($subset_limit_option) and $subset_limit_option>0){
		$subset_limit_option=$subset_limit_option;
	}else{
		$subset_limit_option=100;
	}

	//remote db
	$remote_db_option=get_option("sog_ex_remote_db_option");
	if ($remote_db_option){
		$remote_db_option_checked="checked";
	}else{
		$remote_db_option_checked="";
	}

	$remote_db_pieces=array("label","hostname","port","username","password","db_name");
	$remote_db_option_="";

	foreach ($remote_db_pieces as $key){
		$remote_db_option=get_option("sog_ex_remote_db_option_".$key);
		if (isset($remote_db_option) and $remote_db_option){
			${$remote_db_option_ . $key . "checked"}="checked";
		}else{
			${$remote_db_option_ . $key . "checked"}="";
		}
	}

?>
	<div class="row my-3 section"
		data-slug="setting_start_here"
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
				This gives you read/write access to the entire WordPress database for this installation.  You can optionally connect to other databases but some are finicky and all features may not be available.
			</div>
			<div class="my-2">
				<h5>
					How do I use this?
				</h5>
				<ol>
					<?php 	if( current_user_can('administrator') ) { ?>
						<li>First thing you need to do is choose which tables you want to display.  
						Click the button on the left labeled "Choose Tables" to see all the tables within the database.
						<li>Add this shortcode <code>[sog_explore_sql]</code> to any page or post.
						<?php if ($sog_ex_default_page_url){ ?>
							Or use the default page <a href="<?php echo $sog_ex_default_page_url;?>">here</a>.
						<?php } ?>
					<?php }else{ ?>
						<?php if ($sog_ex_default_page_url){ ?>
							<li>Access it <a href="<?php echo $sog_ex_default_page_url;?>">here</a>.
						<?php } ?>
					<?php } ?>
				</ol>
			</div>
			<?php 	if( current_user_can('administrator') ) { ?>
				<div class="my-2">
					<h5>
						Who can view this?
					</h5>
					<ol>
						<li>Using the choices below, check which roles should have access.  
							<br>These users will also have the ability to edit any table marked with "Allow Update" on the Choose Table screen.
						<div class="mt-2">
							<?php foreach ($wp_roles->roles as $key=>$value) { ?>
								<?php if (in_array($key,$allowed_roles)){$checked="checked";}else{$checked="";}?>
								<div class="form-check">
									<input class="form-check-input mt-1 choose_explore_role" type="checkbox" <?php echo $checked;?> value="<?php echo $key;?>">
										<label class="form-check-label" for="flexCheckDefault">
											<?php echo $value['name'];?>
										</label>
									</div>
							<?php } ?>
						</div>
						<button class="save_explore_roles px-3">
							Save Access
						</button>

					</ol>
				</div>
				<div class="my-2">
					<h5>
						Check for stale data.
					</h5>
					<ul>
						<li>
						<div class="form-check mt-3">
							<input class="form-check-input mt-1" id="stale_check_option" type="checkbox" <?php echo $stale_check_option_checked;?> value="1">
							<label class="form-check-label" for="flexCheckDefault">
								With this checked the program will alert you when the data needs to be refreshed when attempting to make an update.  
								It will work regardless of the frequency seconds below.
								With this not checked, it will save your data, overwriting the data that was changed after you loaded the table.
							</label>
						</div>
						<div class="form mt-3">
							<input class="form-input mt-1 rounded" id="stale_check_option_seconds" type="input" value="<?php echo $stale_check_option_seconds;?>">
							<label class="form-label">
								How frequently should it check for stale data, in seconds. 
							</label>
							<div class="mb-2">
							Minimum 5 seconds.  This interval will only check when the page/tab has focus.
							</div>
						</div>
						<div class="form mt-3">
							<input class="form-input mt-1 rounded" id="subset_limit" type="input" value="<?php echo $subset_limit_option;?>">
							<label class="form-label">
								Number of records to return intially.  Defaults to 100.  Larger numbers may slow down results.
							</label>
						</div>
						<button class="save_stale_check_option px-3">
							Save Options
						</button>
					</ul>

				</div>
				<div class="my-2">
					<h5>
						Remote Database
					</h5>
					<ul>
						<li>
						<div class="form-check mt-3">
							<input class="form-check-input mt-1" id="remote_db_option" type="checkbox" <?php echo $remote_db_option_checked;?> value="1">
							<label class="form-check-label" for="flexCheckDefault">
								Use remote database.  If this is checked, the connection below will be used.  If connecting to a Pantheon database you may have to "wake up" the site first, otherwise this will likely fail.
							</label>
						</div>
						<?php foreach ($remote_db_pieces as $key){ ?>
							<div class="form mt-3">
								<input class="form-input mt-1 rounded" id="remote_db_option_<?php echo $key;?>" type="<?php if ($key=="password"){echo "password";}else{echo "input";} ?>" value="<?php echo get_option("sog_ex_remote_db_option_".$key);?>">
								<label class="form-label">
									<?php echo ucfirst($key);?>
								</label>
							</div>
						<?php } ?>
						<button class="save_remote_db_option px-3 my-2">
							Save Remote DB Options
						</button>
						(Page will reload)
						<div id="save_remote_db_message"></div>
					</ul>

				</div>
			<?php } ?>
			<?php 	if( current_user_can('administrator') ) { ?>
				<div class="my-2 border-top pt-2">
					<h5>
						IT Only
					</h5>
					<ol>
						<li>If you want to see what data moves back and forth in the console, you can add debug=1 to the url.
						<li>It will check for stale data every X seconds (controlled above) as well as whenever you edit a field.  If any record in that table has been updated between the time 
							you first loaded the page and now, it will create an alert and prompt you to refresh the table. To refresh the table you can
							<ol>
								<li>Reload the browser
								<li>Choose a table in the drop down.
								<li>Toggle a field checkbox, edit icon, or group icon.
								<li>Click the green "Refresh Table" button on the top right of the table.
							</ol>
						<li>If you were to disable editing a table while someone still has that table open in a browser with edit fields turned on, 
							the server will NOT let the edit happen and will remove the edit input fields, displaying them as a normal table cell.
						<li>The URL stores the table name so you can bookmark URL's for easy sharing or loading.
						<li>If you are an admin on the WordPress site you will have the option to see exactly what sql is being executed with each action.  It displays at the bottom.
						<li>Every update made to a table creates a log entry and can be viewed in the sog_ex_explore_log table. There is also the option to restore a value that has been changed while viewing the log table.
								<br><strong>Note:</strong> Currently all updates get logged to the local database, as of now the ability to restore a value does not work on a remote database.
						<li>Some updates to the plugin require you to load the admin page. Just loading the page will trigger the actions, nothing else needs to be done.
						<li>Some tables take longer to load than others.  To avoid waiting for a new load with each check of a checkbox, you can enable to the option to "Load with button only."  
							With this checked, the table will not be loaded until you click the green "Go" button above the field checkboxes.
						<li>Database Tables
							<ul>
								<li>sog_ex_explore_user_choices - Holds the users saved fields chosen, edit fields, and group by fields so they will load whenever they load that table.
								<li>sog_ex_explore_filter - Holds the user choices for filters for each table.
								<li>sog_ex_report_tables - Tells the plugin which tables should load in the dropdown for users to view.
								<li>wp_options - The plugin uses the built in WP option functions to store the above settings.
							</ul>

					</ol>
					<h5>
						DWI Website
					</h5>
					<h6>
						To enable editing on the DWI site there are additional steps that must be taken.
			</h6>
					<ol>
						<li>You must first connect to the DW by using the Remote Database section on the Start Here page.
						<li>Then go to the Choose Tables page and select which tables will use this plugin and will also have the ability to be updated.  The database you choose on this screen will be the only one this plugin will be able to update.
						<li>On the Details tab for each table is a checkbox to enable the permissions by Onyen.  Check that box and enter Onyens separated by a comma in the field below.
							If the checkbox is disabled it may be because there is no primary key.  You can try to update the site by going to The Mothership and updating tables.
							If the onyen is not saving, toggle the checkbox then try again.
					</ol>
				</div>
			<?php } ?>
		</div>
	</div>
	<div class="row my-3">
		<div class="col">
		</div>
	</div>

<?php
}

function display_setting_data(){
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
		<?php display_manage_table_select(null);?>
	</div>

<?php
}

function display_manage_table_select($data) {
		$datasets=array(
			array("slug"=>"report_tables","name"=>"Choose DB Tables"),
			// array("slug"=>"setting","name"=>"Settings"),
			);
			usort($datasets, "sort_by_alpha"); //sort results desc on similar %
?>
	<div class="row">
		<div class="col-md-4 text-start">
			<div class="input-group mb-3">
				<select class="form-select load_manage_dataset">
					<option value="">Choose Table
					<?php foreach ($datasets as $dataset) {?>
						<option value="<?php echo $dataset['slug'];?>"
							data-display_name="<?php echo $dataset['name'];?>"
							data-ct="<?php echo $dataset['ct'];?>"
						><?php echo $dataset['name'];?>
					<?php } ?>
				</select>
				<div class="px-3 py-2 bg-secondary text-white reload_manage_table clickable" title="Refresh Table">
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

function display_manage_table_html($data) {
	$delete_column=1;
	if ($data['table_name']=="sog_ex_report_tables") {
		$sortable=0;
		// $add_row_0="name";
		// $third_field="";
		$add_new_row=1;
		$field_class=array("choose_table");
		$input_col=array(1,2,3,4);
		$checkbox_col=array(0);
		$update_i_s_ss="ss";
		// $delete_column=1;
		$skinny_col=array(0);
		// $skip_col=array(1,3);
		$id_column_name="name";
		$col_names=array("Use", "Name", "Display Name","Description");
		$helper_text="";
		$table_data=get_generic_sql(array("table_name"=>$data['table_name'],"fields"=>"use_this, fk_table_name, display_name, description","order_by"=>"order by name","where"=>"and status=1"));
	}else{
		$sortable=0;
		$add_row_0="name";
		$third_field="";
		$add_new_row=1;
		$input_col=array(1,2);
		// $checkbox_col=array(2);
		$skinny_col=array(0);
		// $skip_col=array();
		$col_names=array("ID","Name","Description");
		$table_data=get_generic_sql(array("table_name"=>$data['table_name'],"fields"=>"id,name,description","order_by"=>"order by id desc"));
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
				display_generic_manage_table(array("table_data"=>$table_data,
													"field_class"=>$field_class,
													"delete_column"=>$delete_column,
													"input_col"=>$input_col,
													"checkbox_col"=>$checkbox_col,
													"textarea_col"=>$textarea_col,
													"table_display_name"=>$data['table_display_name'],
													"col_names"=>$col_names,
													"add_row_0"=>$add_row_0,
													"skinny_col"=>$skinny_col,
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
				// echo build_simple_table(array("table"=>$table_data,"class"=>"generic_table_download_only"));
		?>

		</div>
	</div>
<?php
}

function display_generic_manage_table($data) {
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
							"table_display_name"=>$data['table_display_name'] ?? "", 
							"delete_col"=>$data['delete_column'], //Should a delete column be included
							"delete_class"=>"generic_table_delete", //Should a delete column be included
							"change_status_on_delete"=>0, //change status to 0 instead of deleting
							"closest"=>"tr",
							"skinny_col"=>$data['skinny_col'],
							// "wide_row"=>1, //which col has the description and should be longer.
							"form_control"=>1, //should form-control be added to inputs
							// "table_sort_head"=>1, 
							"sortable"=>$data['sortable'], //drag and drop sortable
							"use_span_instead_of_input"=>1, //for non input cells
							"use_filter"=>false,
							"wrapping_class"=>".generic_input_table_wrapper",
							"use_generic_manage"=>$data['use_generic_manage'],
							"skip_col"=>$data['skip_col'],
							"allow_duplicates"=>false,
							"use_add_new_button"=>false,
							"update_i_s_ss"=>$data['update_i_s_ss'],
	);
	 $table_html=build_table_with_inputs($table_options);
	 echo $table_html;
}

function display_setting_settings(){
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

function get_setting_data($data) {
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
	$con = db_connect(null) or die("Couldn't connect to db.");

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

function update_tables() {

	//get tables from sog_ex_report_tables and if it no longer exists in schema, delete from sog_ex_report_tables 
	$current_sog_ex_tables_sql=get_generic_sql(array("fields"=>"fk_table_name","table_name"=>"sog_ex_report_tables")); 
	$current_sog_ex_tables_simple_array = array_column($current_sog_ex_tables_sql, 'fk_table_name'); 
 
	//check if using remote db
	$remote_db_option=get_option("sog_ex_remote_db_option");
	$sog_ex_remote_db_option_db_name=get_option("sog_ex_remote_db_option_db_name");

	if ($remote_db_option){
		//check if remote db is valid
		$creds=get_db_creds_remote($data);
		$valid_remote_db=db_connect_remote_check_only($creds);

		if ($valid_remote_db['status']=="success"){
			//get tables from this wp db 
			$wp_db_tables=get_tables_remote(array("sog_ex_remote_db_option_db_name"=>$sog_ex_remote_db_option_db_name)); 
		}else{
			echo "<div>Unable to connect to remote database.  Reverting to local database.</div>";
			echo "<div>".$valid_remote_db['error_message']."</div>";

			//turn option off
			update_option( $option="sog_ex_remote_db_option",0 );
			$remote_db_option=0;

			//revert to local
			$wp_db_tables=get_tables(null); 
		}
	}else{
		//get tables from this wp db 
		$wp_db_tables=get_tables(null); 
	}

	// echo "<pre>wp_db_tables";print_r($wp_db_tables);echo "</pre>"; 


	if (isset($wp_db_tables) and $wp_db_tables) {

	
		$wp_db_tables_simple_array = array_column($wp_db_tables, 'table_name'); 

		//get values from array2 that are not in array 1 array_diff($array2, $array1); 
		//get names in sog_ex_tables that are not in wp_db tables 
		$diff = array_diff($current_sog_ex_tables_simple_array, $wp_db_tables_simple_array); 

		// echo "current_sog_ex_tables_simple_array<pre>";print_r($current_sog_ex_tables_simple_array);echo "</pre>"; 
		// echo "wp_db_tables_simple_array<pre>";print_r($wp_db_tables_simple_array);echo "</pre>"; 
		// echo "diff<pre>";print_r($diff);echo "</pre>"; 

		//delete tables that no longer exist 
		if (isset($diff) and count($diff)>0) { 
			//create csv with single quotes 
			$tables="'".implode("','",$diff)."'"; 
			$delete_sql="delete from sog_ex_report_tables where fk_table_name in (".$tables.")"; 
			// echo  "<br>".$delete_sql;
			$delete_sql_result=generic_sql_query(array("sql"=>$delete_sql)); 
			// echo "delete_sql_result<pre>";print_r($delete_sql_result);echo "</pre>"; 
		}

		//now process tables
		if ($remote_db_option){
			//get tables from this wp db 
			$all_tables=get_tables_remote(array("sog_ex_remote_db_option_db_name"=>$sog_ex_remote_db_option_db_name)); 
		}else{
			//get tables from this wp db 
			$all_tables=get_tables(null); 
		}
		
		// echo "all_tables<pre>";print_r($all_tables);echo "</pre>";
		$sql="insert ignore into sog_ex_report_tables (fk_table_name) values ";
		foreach ($all_tables as $table) { 
			$table_name=$table['table_name'];
			$sql.="('".$table_name."'),";
		}
		$sql = rtrim($sql, ',');
		// echo $sql;
		generic_sql_query(array("sql"=>$sql));
	}
}

function display_report_builder($atts){
	
	//dropdown to choose table
	$report_tables=get_tables_to_use(null);
	// echo "report_tables<pre>";print_r($report_tables);echo "</pre>";

	//get auto save from saved options
	$update_auto_save_option=get_option("sog_ex_auto_save_".$_SESSION['sog_explore_user_login']);
	if (isset($update_auto_save_option) and $update_auto_save_option){
		$auto_save_checked="checked";
	}else{
		$auto_save_checked="";
	}


	$auto_save_checked="checked";  //this will default this checkbox on, may will turn into a setting later
	$auto_check_primary_keys_checked="checked";  //this will default this checkbox on, may will turn into a setting later
	$auto_move_primary_keys_checked="checked";  //this will default this checkbox on, may will turn into a setting later
	$show_sql_checked="checked";  //this will default this checkbox on, may will turn into a setting later
	$click_to_load_checked="";  //this will default this checkbox off, may will turn into a setting later

	$stale_check_option_ms=get_option("sog_ex_stale_check_option_seconds");

	//get db name
	$sog_ex_remote_db_option_db_name=get_option("sog_ex_remote_db_option_db_name");

	//if hiding table select then just display the table name instead of the dropdown
	if (isset($atts['hide_table_dropdown']) and $atts['hide_table_dropdown']) {
		$show_hide_table_dropdown="d-none";
		$show_hide_table_name="";
	}else{
		$show_hide_table_dropdown="";
		$show_hide_table_name="d-none";
	}


?>
	<div class="report_builder p-2 mx-auto"
		data-stale_check_option_ms="<?php echo $stale_check_option_ms;?>"
	>
		<div class="row sog_ex_report_header_row"> 
			<div class="col-4">
				<label class="form-label ">Database</label>
				<select class="form-control select_report_db d-none">
					<option value="">Not Working Yet
				</select>
				<div class="fw-bold">
					<?php echo $sog_ex_remote_db_option_db_name;?>
				</div>
			</div>
			<div class="col-4">
				<label class="form-label">Table</label>
				<a href="<?php echo admin_url('admin.php?page=sog_explore_menu_slug'); ?>"><i title="Go to Settings" class="far fa-cog text-secondary"></i></a>
				<select class="form-control select_report_table <?php echo $show_hide_table_dropdown;?>">
					<option value="">
					<?php foreach ($report_tables as $table) { ?>
						<option value="<?php echo $table['fk_table_name'];?>"><?php echo $table['table_name'];?>
					<?php } ?>
				</select>
				<div class="selected_table_name <?php echo $show_hide_table_name;?>">
				</div>
			</div>
			<div class="col-4">
				<div class="fw-bold text-center">
					Options
				</div>
				<div class="form-check" title="This allows you to make multiple selections before actually loading the table.  Sometimes helpful when loading a large table or using over a slow connection.">
					<label class="form-check-label">Load with button only.</label>
					<input class="form-check-input" type="checkbox" id="click_to_load" <?php echo $click_to_load_checked;?>>
				</div>
				<div class="form-check d-none">
					<label class="form-check-label">Auto move primary key on edit.</label>
					<input class="form-check-input" type="checkbox" id="auto_move_primary_keys" <?php echo $auto_move_primary_keys_checked;?>>
				</div>
				<div class="form-check d-none">
					<label class="form-check-label">Automatically check primary keys when edit is chosen.</label>
					<input class="form-check-input" type="checkbox" id="auto_check_primary_keys" <?php echo $auto_check_primary_keys_checked;?>>
				</div>
				<div class="form-check d-none">
					<label class="form-check-label">Save my choices.</label>
					<input class="form-check-input" type="checkbox" id="auto_save" <?php echo $auto_save_checked;?>>
				</div>
				<?php if( current_user_can('administrator') ) { ?>
					<div class="form-check">
						<label class="form-check-label">Show executed sql.</label>
						<input class="form-check-input" type="checkbox" id="show_sql" <?php echo $show_sql_checked;?>>
					</div>
				<?php } ?>
			</div>
		</div>
		<div class="row">
			<div class="col report_fields_here">
			</div>
		</div>
		<div class="row pt-3 mt-3 border-top ">
			<?php display_report_show_all_options(); ?>

			<div class="col report_table_here p-2">
				<!-- Table is loaded here -->
			</div>

		</div>
	</div>
<?php
}

function get_report_data($data) {
	$fx=__FUNCTION__;
	$new_array=[];
	
	$return['data_sent_to_get_report_data']=$data;

	$selects=implode(", ",$data['field_names']);

	$wheres=implode(" ",$data['filters']);
	$wheres=stripslashes($wheres);

	$from=$data['table_name'];
	
	$keys=get_primary_key(array("table_name"=>$data['table_name']));
	$return['keys']=$keys;

	$count_sql="";
	$group_by="";

	if (isset($data['groupings']) and !empty($data['groupings'])) {
		$group_by="group by ".implode(",",$data['groupings']);
		$count_sql=", count(*) as _count";
		$return['data_sent_to_get_report_data']=$data;

	}else{
		$group_by="";
		$count_sql="";
	}

	if ($data['limit']) {
		$limit="limit ".$data['limit'];
	}else{
		$limit="";
	}

	if ($data['order']) {
		$order=$data['order'];
	}else{
		$order="";
	}
	
	
	//check if using remote db
	$remote_db_option=get_option("sog_ex_remote_db_option");
	$sog_ex_remote_db_option_db_name=get_option("sog_ex_remote_db_option_db_name");

	if ($remote_db_option){
		$con = db_connect_remote(null) or die("Couldn't connect to db.");
	}else{
		$con = db_connect(null) or die("Couldn't connect to db.");
	}

	$sql="
		select 
			".$selects." 
			".$count_sql." 
		from ".$from." 
		where 1=?
		".$wheres." 
		".$group_by." 
		".$order." 
		".$limit."

	";
	$return['sql']=$sql;
	// return $return;

	$sql_for_display="
		select 
			".$selects." 
			".$count_sql." 
		from ".$from." 
		where 1=1
		".$wheres." 
		".$group_by." 
		".$order." 
		".$limit."

	";
	$return['sql_for_display']=$sql_for_display;

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

function display_report_show_all_options() { 
	// echo "data<pre>";print_r($data);echo "</pre>";
?>
	<div class="xhide_until_limit_ready d-none">
		<div class="row report_showing_subset">
			<div class="col">
				This is a subset showing only <span class="report_count_number report_sample_number"></span> records of the <span class="report_count_number report_total_number"></span> total records.
				<span class="btn bg-success text-white report_get_all_records">
					Get me all records!
				</span>
			</div>
		</div>
		<div class="row report_showing_all my-2">
			<div class="col">
				Showing all <span class="report_count_number report_total_number"></span> records.
			</div>
		</div>
	</div>

	<div class="hide_until_limit_ready">
		<div class="row ">
			<div class="col report_showing_subset">
				This is a subset showing only <span class="report_count_number report_sample_number"></span> records of the <span class="report_count_number report_total_number"></span> total records.
				<span class="btn bg-success text-white report_get_all_records">
					Get me all records!
				</span>
			</div>
			<div class="col-6 report_showing_all">
				Showing all <span class="report_count_number report_total_number"></span> records.
			</div>
			<div class="col-6 text-end">
				<div class="clickable refresh_table_button text-success">
					Refresh Table <i class="refresh_table_icon fas fa-sync"></i>
				</div>
			</div>
		</div>
	</div>

<?php
}

function build_report_options($data){
	//$data['table_name']
	$fields=get_table_fields(array("table_name"=>$data['table_name']));

	$keys=get_primary_key(array("table_name"=>$data['table_name']));
	// echo "fields<pre>";print_r($fields);echo "</pre>";

	//initial setting
	$allowed_to_edit=false;

	//find out if the table can be updated acdcording to the "choose table" page in the plugin admin
	$allow_update=get_generic_sql(array("table_name"=>"sog_ex_report_tables","fields"=>"allow_update",
	"where"=>"and fk_table_name='".$data['table_name']."' and allow_update=1"));

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
		"where"=>"and fk_table_name='".$data['table_name']."' and fk_schema_name='".$data['db']."'")); 
		// echo "data<pre>";print_r($data);echo "</pre>";

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
			$allowed_to_edit=true;
		}else{
			$allowed_to_edit=false;
		}

		//if dwi site check if $_GET['db'] = to remote db from options
		//get db name from remote settings
		$sog_ex_remote_db_option_db_name=get_option("sog_ex_remote_db_option_db_name");

		//check for matching db
		if ($data['db'] and $sog_ex_remote_db_option_db_name and $data['db']!=$sog_ex_remote_db_option_db_name){
			//since the db var exists but it is not the same as the db from options
			//remove ability to update regardless what it gets from sog_ex_report_tables
			$allowed_to_edit=false;
		}
	}


	//display the chexkbox section
	display_report_field_checkboxes(array("fields"=>$fields,"keys"=>$keys,"table_name"=>$data['table_name'],"allow_update"=>$allowed_to_edit));
	
	echo "<div class='explore_filters_here'>";
		display_report_field_filters(array("table_name"=>$data['table_name'],"where_based_on_other_filters"=>$data['where_based_on_other_filters'] ?? []));
	echo "</div>";

	echo "<div class='explore_grouping_here'>";

	echo "</div>";

	echo "<div class='report_messages_for_edits'>";

	echo "</div>";
	// display_report_limit_options();
?>

<?php
}

function check_if_stale_data($data){
	$fx=__FUNCTION__;
	$con = db_connect(null) or die("Couldn't connect.");
	$sql=" SELECT COALESCE(CONVERT_TZ(date_modified,'UTC','US/Eastern'),date_modified) as date_of_last_change,
	if (COALESCE(CONVERT_TZ(date_modified,'UTC','US/Eastern'),date_modified)>?,1,0) as is_stale,
		TIMEDIFF(COALESCE(CONVERT_TZ(date_modified,'UTC','US/Eastern'),date_modified),?) as dif_hms,
		TIMESTAMPDIFF(SECOND,COALESCE(CONVERT_TZ(date_modified,'UTC','US/Eastern'),date_modified),?) as dif_seconds,
		COALESCE(CONVERT_TZ(now(),'UTC','US/Eastern'),now()) as datetime_now

	FROM sog_ex_explore_log
	WHERE 1=?
	and table_name=?
	and (action='update_record' or action='restore_field')
	order by id desc 
	limit 1

	
	";
	// echo $sql."-".$_SESSION['db_to_use'];
	$place_holder=1;
	$stmt = $con->prepare($sql) or die("Couldn't prepare ".$fx.". ".$sql); 
	$stmt -> bind_param("sssis",$data['last_datetime_checked'],$data['last_datetime_checked'],$data['last_datetime_checked'],$place_holder,$data['table_name']) or die("Couldn't bind ".$fx." ."); 
	$stmt->execute() or die("Couldn't execute ".$fx." ."); 
	$sql_result = $stmt->get_result();
	$num_rows = mysqli_num_rows($sql_result);	
	$cols=[];while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
		$new_array = $row;
	}	
return $new_array ?? null;
}

function get_tables_to_use($data) {
	$fx=__FUNCTION__;
	$con = db_connect(null) or die("Couldn't connect.");
	$sql=" SELECT fk_table_name, use_this, description, display_name, COALESCE(display_name,fk_table_name) as table_name, allow_update
		FROM sog_ex_report_tables
		WHERE 1=?
		and use_this=1
		and status=1
		order by sort_order,fk_table_name

	
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

function get_tables($data) {
	$con = db_connect(null) or die("Couldn't connect.");
	$sql=" SELECT table_name as table_name
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

function get_tables_remote($data) {
	$con = db_connect_remote(null) or die("Couldn't connect.");
	$sql=" SELECT table_name as table_name
			FROM information_schema.tables
			where 1=?
			and table_schema=?

	
	";
// return $data;
	$place_holder=1;
	$stmt = $con->prepare($sql);
	$stmt -> bind_param("is",$place_holder,$data['sog_ex_remote_db_option_db_name']);
	$stmt->execute();
	$sql_result = $stmt->get_result();
	$num_rows = mysqli_num_rows($sql_result);	
	$cols=[];while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
		$new_array[] = $row;
	}	
return $new_array;
}

function get_table_fields($data) {
		// echo "<pre>";print_r($_SESSION);echo "</pre>";

	$fx=__FUNCTION__;
	$new_array=[];

	//check if using remote db
	$remote_db_option=get_option("sog_ex_remote_db_option");
	$sog_ex_remote_db_option_db_name=get_option("sog_ex_remote_db_option_db_name");

	if ($remote_db_option){
		$con = db_connect_remote(null) or die("Couldn't connect to db.");
		$db_to_use=$sog_ex_remote_db_option_db_name;
	}else{
		$con = db_connect(null) or die("Couldn't connect to db.");
		$db_to_use=$_SESSION['db_to_use'];
	}
	
	if (1) {
		$order="order by COLUMN_NAME";
	}else{
		$order="";
	}

	$sql="
		SELECT COLUMN_NAME, IS_NULLABLE as is_nullable, DATA_TYPE as data_type,
		COLUMN_DEFAULT as column_default, CHARACTER_MAXIMUM_LENGTH as character_maximum_length,
		COLUMN_TYPE, EXTRA
		FROM INFORMATION_SCHEMA.COLUMNS
		WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
		$order
		;
	";
	// return $sql."-".$data['table_name']."-".$_SESSION['db_to_use'];
	$place_holder=1;
	$stmt = $con->prepare($sql) or die ("couldnt prepare ".$fx." ".$sql);
	$stmt -> bind_param("ss",$db_to_use,$data['table_name']) or die ("couldnt bind ".$fx);
	$stmt->execute() or die ("couldnt execute ".$fx);
	$sql_result = $stmt->get_result();
	$num_rows = mysqli_num_rows($sql_result);
	$cols=[];while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
		$new_array[] = $row;
	}

return $new_array;
}

function display_report_field_filters($data) { 
	// echo "data<pre>";print_r($data);echo "</pre>";

	//check if using remote db
	$remote_db_option=get_option("sog_ex_remote_db_option");

	if ($remote_db_option){
		
	}else{
		
	}
	
	//get the filters they had saved
	$filters=get_generic_sql(array("return_only"=>0,"table_name"=>"sog_ex_explore_filter",
	"fields"=>"fk_table_name, field_name, si, fk_username, value, value_like",
	"order_by"=>" order by date_modified",
	"where"=>"	and fk_username='".$_SESSION['sog_explore_user_login']."'
				and fk_table_name='".$data['table_name']."'
				and status=1 "));
	// echo "filters<pre>";print_r($filters);echo "</pre>";
	
?>
	<?php if ($filters) { ?>
		<div class="report_filter_wrapper">
			<div class="row mt-2 report_choice_header">
				<div class="col-3">
					<h4>
						Choose Filters
						<?php create_help_block("<br>The drop down searches for an exact match.  <br>The input field searches by partial match."); ?>
					</h4>
				</div>
			</div>
			<div class="report_filters py-2">
				<?php foreach ($filters as $filter) { ?>
					<?php
						$order="order by `".$filter['field_name']."`";

						//this groups all values for this field to create a unique list
						$values=get_generic_sql(array("return_only"=>0,"remote_db_option"=>$remote_db_option,"table_name"=>$data['table_name'],
						"fields"=>"`".$filter['field_name']."`",
						"order_by"=>$order,
						"where"=>"and `".$filter['field_name']."` is not null",
						"group_by"=>"group by `".$filter['field_name']."`"));
						// echo "values<pre>";print_r($values);echo "</pre>";
						?>
					
					<div class="report_filters table_info"
						data-table_name="<?php echo $data['table_name'];?>"
					>
						<label class="form-label">
						<?php 
							// optionally beautify name
							$name_to_display=$filter['field_name'];
							if (1) {
								$name_to_display=beautify_field_name(array("field_name"=>$filter['field_name']));
							}else{
							}
							echo $name_to_display;
						?>
						<i class="fas fa-times-circle text-danger add_explore_filter remove_filter_via_button clickable"
							title="Remove this filter"
							data-field_name="<?php echo $filter['field_name'];?>"
							data-data_type="<?php echo $filter['si'];?>"

						></i>		

						</label>

						<select class="form-control explore_filter"
							id="filter_<?php echo $filter['field_name'];?>"
							data-filter_field_name="<?php echo $filter['field_name'];?>"
							data-si="<?php echo $filter['si'];?>"
						>
							<option value="">Select <?php echo $name_to_display;?>
							<?php foreach ($values as $value) { ?>
								<?php 
									if ($filter['value']==$value[$filter['field_name']]) {
										$selected="selected";
									}else{
										$selected="";
									}
										?>
								<option <?php echo $selected;?> value="<?php echo htmlspecialchars($value[$filter['field_name']] ?? "");?>"><?php echo htmlspecialchars($value[$filter['field_name']] ?? ""); ?>
							<?php } ?>
						</select>
						<div class="">
							<input class="form-control mt-1 explore_filter_like" type="text" placeholder="Text Filter"
								id="filter_like_<?php echo $filter['field_name'];?>"
								data-filter_field_name="<?php echo $filter['field_name'] ?? "";?>"
								data-si="<?php echo $filter['si'] ?? "";?>"
								value="<?php echo trim($filter['value_like'] ?? "") ?? "";?>"
							>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
	<?php } ?>

<?php
}

function display_report_field_checkboxes($data) { 

	// echo "data<pre>";print_r($data['fields']);echo "</pre>";

	//get filters so i know which icons should be green
	$filters=get_generic_sql(array("table_name"=>"sog_ex_explore_filter",
	"fields"=>"group_concat(field_name) as field_names_csv",
	"where"=>"	and fk_username='".$_SESSION['sog_explore_user_login']."'
				and fk_table_name='".$data['table_name']."'
				and status=1 "));
	$filters_array=explode(",",$filters[0]['field_names_csv'] ?? "");


	//get user saved groups, edits, and checked from db
	$user_saved_choices=get_generic_sql(array("table_name"=>"sog_ex_explore_user_choices",
	"fields"=>"fields_chosen, group_by_fields,edit_fields, sort_sql ",
	"where"=>"	and fk_username='".$_SESSION['sog_explore_user_login']."'
				and fk_table_name='".$data['table_name']."'"
			));
	// echo "user_saved_choices<pre>";print_r($user_saved_choices);echo "</pre>";

	//turn json back into arrays
	if (isset($user_saved_choices[0]['fields_chosen']) and $user_saved_choices[0]['fields_chosen']){
		$saved_fields=json_decode($user_saved_choices[0]['fields_chosen']);
	}

	if (isset($user_saved_choices[0]['edit_fields']) and $user_saved_choices[0]['edit_fields']){
		$saved_edits=json_decode($user_saved_choices[0]['edit_fields']);
	}

	if (isset($user_saved_choices[0]['group_by_fields']) and $user_saved_choices[0]['group_by_fields']){
		$saved_groups=json_decode($user_saved_choices[0]['group_by_fields']);
	}


	if (isset($saved_fields) and $saved_fields){
		//create temp vairables to hold strings and arrays
		$temp_fields_string="";
		$temp_fields_array=[];

		//need to change each piece of array from table_name.`field_name` to field_name
		foreach ($saved_fields as $field){									//table_name.`field_name`
			$temp_fields_string=explode(".",$field)[1];						//`field_name`
			$temp_fields_string=str_replace("`","",$temp_fields_string);	//field_name
			$temp_fields_array[]=$temp_fields_string;						//store in temp array
		}

		//put back into array to be checking later
		$saved_fields=$temp_fields_array;

		// echo "saved_fields<pre>";print_r($saved_fields);echo "</pre>";
		// echo "saved_edits<pre>";print_r($saved_edits);echo "</pre>";
		// echo "saved_groups<pre>";print_r($saved_groups);echo "</pre>";
	}

	
	$temp_array=[];
	$filter_icon_class="";
	$group_icon_color="";

	//determine if the table allows updating at all
	if ($data['allow_update']){
		//classes for all//none bar
		$edit_all_none_title="Toggle editing on all fields.";
		$edit_all_none_class="";
	}else{
		//classes for all//none bar
		$edit_all_none_title="This table does not allow editing.";
		$edit_all_none_class="text-muted";
	}

?>
	<div class="report_checkbox_wrapper table_info"
		data-table_name="<?php echo $data['table_name'] ?? "";?>"
		data-fk_schema_name="<?php echo $data['db_name'] ?? "";?>"
		
	>
		<div class="row mt-2 report_choice_header">
			<div class="col-3">
				<h4>
					Choose Fields <span class="text-danger">*</span>
				</h4>
			</div>
			<div class="col-3 offset-6 d-none">
				<input type="text" class="form-control sog_explore_filter_this_generic_span" placeholder="Filter Fields"
					data-wrapper=".report_checkbox_wrapper"
				>
			</div>
		</div>
		<div class="row p-1 rounded my-2 all_none_row">
			<div class="col-2 pt-2">
				<span class="all_none_label ">Select</span> <span class="report_check_all clickable  text-decoration-underline text-primary">All</span> / <span class="report_check_none clickable  text-decoration-underline text-primary">None</span>
			</div>
			<div class="col-3 pt-2 text-right <?php echo $edit_all_none_class;?>" title="<?php echo $edit_all_none_title;?>">
				<span class="all_none_label ">Edit</span>
				<span class="report_edit_all_selected clickable  text-decoration-underline text-primary">Selected</span>
				/
				<span class="report_edit_all clickable  text-decoration-underline text-primary">All</span>
				 /
				 <span class="report_edit_none clickable  text-decoration-underline text-primary">None</span>
			</div>
			<div class="col-2 text-right">
				<div class="btn btn-success text-center text-white refresh_table_button go_button">
					Go <i class="refresh_table_icon small fal fa-sync ms-2"></i>
				</div>
			</div>
			<div class="col-3 offset-2 text-right">
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
					//determine if part of primary key
					if (in_array($field['COLUMN_NAME'],$data['keys'])) {
						$key_html="<i title='This is a primary key' class='primary_key_icon fas fa-key fa_icon text-warning'></i>";
						$primary_key_class="is_primary_key";
					}else{
						$key_html="";
						$primary_key_class="";
					}

					//######################## handle filters //########################
					//to determing if already chosen
					if (in_array($field['COLUMN_NAME'],$filters_array)) {
						$icon_color="fas text-primary filter_is_active";
					}else{
						$icon_color="far text-secondary";
					}

					//######################## handle checkboxes //########################
					//to determing if checkbox saved from last time
					if (isset($saved_fields) and is_array($saved_fields) and in_array($field['COLUMN_NAME'],$saved_fields)) {
						$checked="checked";
					}else{
						$checked="";
					}

					//######################## handle edit icons //########################
					//to determing if edit saved from last time
					if (isset($saved_edits) and is_array($saved_edits) and in_array($field['COLUMN_NAME'],$saved_edits)) {
						$edit_icon_class_from_save="text-primary fas edit_active";
					}else{
						$edit_icon_class_from_save="far text-secondary";
					}

					//######################## handle group icons //########################
					//to determing if group was saved from last time
					if (isset($saved_groups) and is_array($saved_groups) and in_array($field['COLUMN_NAME'],$saved_groups)) {
						$group_icon_class_from_save="text-primary fas grouped_active";
					}else{
						$group_icon_class_from_save="text-secondary far";
					}

					// do not allow filter on field names with spaces
					$temp_array=explode(" ",$field['COLUMN_NAME']);
					if (count($temp_array)>1) {
						$filter_title="This field is not filterable, likely it is a custom field, there may be another table that has this field that you can use.";
						$group_title="This field can not be grouped, likely it is a custom field, there may be another table that has this field that you can use.";
						$filter_icon_class="sog_ex_unavailable";
						$group_icon_class="sog_ex_unavailable";
					}else{
						$filter_title="Toggle this filter";
						$group_title="Group by this field";
						$filter_icon_class="sog_ex_available add_explore_filter clickable";
						$group_icon_class="sog_ex_available add_explore_group clickable";
					}

					if ($data['allow_update']){
						// classes for icons with checkboxes
						$edit_icon_title="Click here to edit this field.";
						$edit_icon_class="sog_ex_available clickable edit_this_field";
					}else{
						// classes for icons with checkboxes
						$edit_icon_title="This table does not allow editing.";
						$edit_icon_class="sog_ex_unavailable";
					}

					// if this is also the primary key, then override above code
					if (in_array($field['COLUMN_NAME'],$data['keys'])){
						$edit_icon_title="This is a primary key and cannont be edited.";
						$edit_icon_class="sog_ex_unavailable";
					}


				?>
				<div class="form-check sog_ex_field_row filter_row"
					data-field_name="<?php echo $field['COLUMN_NAME'] ?? "";?>"
					data-data_type="<?php echo $field['DATA_TYPE'] ?? "";?>"
				>
						<i class="fa-filter <?php echo $icon_color;?> <?php echo $filter_icon_class;?>"
							title="<?php echo $filter_title;?>"
							data-field_name="<?php echo $field['COLUMN_NAME'] ?? "";?>"
							data-data_type="<?php echo $field['DATA_TYPE'] ?? "";?>"
						></i>		
						<i class="far fa-layer-group <?php echo $group_icon_color;?> <?php echo $group_icon_class;?> <?php echo $group_icon_class_from_save;?>"
							title="<?php echo $group_title;?>"
							data-field_name="<?php echo $field['COLUMN_NAME'] ?? "";?>"
							data-data_type="<?php echo $field['DATA_TYPE'] ?? "";?>"
						></i>		
						<i class="fa-pencil edit_icon <?php echo $edit_icon_class;?> <?php echo $edit_icon_class_from_save;?>"
							title="<?php echo $edit_icon_title;?>"
						></i>		
						<i class="far fa-info-circle fa_icon"
							title="Type: <?php echo $field['COLUMN_TYPE'];?> | Empty: <?php echo $field['is_nullable'];?> | Default: <?php echo $field['column_default'];?> | Extra: <?php echo $field['EXTRA'];?>"
						></i>		
					<input class="form-check-input report_checkbox <?php echo $primary_key_class;?>" type="checkbox" value="<?php echo $data['table_name'].".`".$field['COLUMN_NAME'];?>`"
						title="Toggle field in the table" <?php echo $checked;?>
					>
					<label class="filter_this" title="<?php echo $field['COLUMN_NAME'];?>">
						<?php echo $field['COLUMN_NAME'];?>	
					</label>
					<?php echo $key_html;?>
				</div>
			<?php } ?>
		</div>
	</div>

<?php
}

function beautify_field_name($data) {
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

function build_simple_table($data) {
	if (isset($data['id'])) {$id=$data['id'];}else{$id="";}
	if (isset($data['class'])) {$class=$data['class'];}else{$class="";}
	if (isset($data['ordinal_column'])) {$ordinal_column=$data['ordinal_column'];}else{$ordinal_column="";}
	if ($data['table']) {
		$table_html="";
		$headers=array_keys($data['table'][0]);
		$table_html.="<table id='".$id."' class='".$class."'>";
			$table_html.= "<thead>";
				$table_html.= "<tr>";
					if ($ordinal_column) {
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
							if ($ordinal_column) {
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

function build_sog_ex_table($data) {
	// echo "<pre>data";print_r($data);echo "</pre>";
	/*
		need field name in td and th 
		need record id in tr
	*/

	//which log entries will allow a restore button 
	$allow_on_action=array("update_record","restore_field");

	//if this is the log table then create the restore button
	if ($data['table_name']=="sog_ex_explore_log"){
		$restore_option=1;
		$restore_button_html="<i title='restore' class='fad fa-trash-undo-alt sog_ex_log_restore_value_button clickable'></i>";

	}else{
		$restore_option=0;
		$restore_button_html="";
	}

	if (isset($data['id'])) {$id=$data['id'];}else{$id="";}
	if (isset($data['class'])) {$class=$data['class'];}else{$class="";}
	if ($data['table']) {
		//get primary keys for this table
		// $keys=get_primary_key(array("table_name"=>$data['table_name']));
		// echo "<pre>keys";print_r($keys);echo "</pre>";


		//get col names for header
		$headers=array_keys($data['table'][0]);
		?>
			<table id="<?php echo $id;?>" class="<?php echo $class;?>"
				data-table_name="<?php echo $data['table_name'];?>"
			>
				<thead>
					<?php foreach ($headers as $header) { ?>
						<?php
							if (in_array($header,$data['keys'])) {
								$key_icon="<i title='This is a primary key' class='primary_key_col_icon fas fa-key fa_icon text-warning'></i>";
							}else{
								$key_icon="";
							}
						?>
						<th class="sog_ex_col_header" data-field_name="<?php echo $header;?>">
						<?php echo $header;?>
						<?php echo $key_icon;?>
						</th>
					<?php } ?>
					<?php if ($data['table_name']=="sog_ex_explore_log"){ ?>
						<th>
							
						</th>
					<?php } ?>
				</thead>
				<tbody>
					<?php foreach ($data['table'] as $table_rows => $rows) { ?>
						<?php //echo "<pre>";print_r($rows);echo "</pre>"; ?>
						<?php 
							$key_values=[];
							//need to get the values of each primary key
							foreach ($data['keys'] as $key_field_name){
								if (isset($rows[$key_field_name])){
									$key_values[]=$rows[$key_field_name];
								}
							}
						?>

						<tr class="sog_ex_row"
							data-primary_key_field="<?php echo implode(",",$data['keys']);?>"
							data-primary_key_value="<?php echo implode(",",$key_values);?>"
						>
							<?php foreach ($rows as $sub_key=>$sub_row) { ?>
								<td class="" data-field_name="<?php echo $sub_key;?>">
									<?php if (!in_array($sub_key,$data['keys']) and  in_array($sub_key,$data['fields_to_edit'] ?? [])) { //check if this is in fields_to_edit ?>
										<input type="text" value="<?php echo htmlspecialchars($sub_row ?? ""); ?>" class="form-control sog_ex_report_generic_update"
											data-field_name_hash="<?php echo create_salted_hash_of($sub_key);?>"
										>
										<span class="sort_helper_hidden"><?php echo $sub_row; ?></span>
									<?php }else{ ?>
										<?php echo $sub_row; ?>
									<?php } ?>
			
								</td>
							<?php } ?>
							<?php if ($data['table_name']=="sog_ex_explore_log"){ ?>
								<td class="text-center">
									<?php
									//as of now, restore only works for changes made to looalhost and remote_db_option is not on
									
									//check if using remote db
									$remote_db_option=get_option("sog_ex_remote_db_option");

									if (	$restore_option 
												and $rows['action']=="update_record" 
												and count($key_values)
												and !$remote_db_option 
												and $sub_key=="description" 
												and $sub_row=="localhost"
												){	//check if restore option is true, if this an update_record entry, and if the primary key has been selected ?>
											<?php echo $restore_button_html;?>
									<?php } ?>
								</td>
							<?php } ?>

						</tr>
					<?php } ?>
				</tbody>
			</table>
		<?php
	}else{
		echo "no data";
	}
}

function build_table_with_inputs($options) {
	// echo "<pre>options";print_r($options);echo "</pre>";

	$add_link=$options['add_link'] ?? null ;if (!is_array($add_link)) {$add_link=[];} //not coded yet
	$add_new_row=$options['add_new_row'];
	$add_row_0=$options['add_row_0'];
	$allow_duplicates=$options['allow_duplicates'];
	$change_status_on_delete=$options['change_status_on_delete'];
	$checkbox_col=$options['checkbox_col'] ?? null;if (!is_array($checkbox_col)) {$checkbox_col=[];}
	$class=$options['class'];
	$closest=$options['closest'];
	$col_names=$options['col_names'];
	$field_class=$options['field_class'] ?? null;if (!is_array($field_class)) {$field_class=[];}
	$db=$options['db'] ?? null;
	$delete=$options['delete_col'];
	$delete_class=$options['delete_class'];
	$id=$options['id'];
	$id_column_name=$options['id_column_name'];if (!$id_column_name) {$id_column_name="id";}
	$ignore_last_inserted_id=$options['ignore_last_inserted_id'] ?? null;
	$input_col=$options['input_col'];if (!is_array($input_col)) {$input_col=[];}
	$order_column=$options['order_column'] ?? null;
	$order_direction=$options['order_direction'] ?? null;
	$permission=$options['permission'] ?? null;if (!is_array($permission)) {$permission=[];}
	$prevent_update=$options['prevent_update'] ?? null;if (!is_array($prevent_update)) {$prevent_update=[];}
	$reload_on_add_row=$options['reload_on_add_row'] ?? null;
	$reload_with_ajax=$options['reload_with_ajax'];
	$skinny_col=$options['skinny_col'] ?? null;if (!is_array($skinny_col)) {$skinny_col=[];}
	$skip_col=$options['skip_col'] ?? null;if (!is_array($skip_col)) {$skip_col=[];}
	$sortable=$options['sortable'];
	$table=$options['data'] ?? null;if (!isset($table[0])) {$table[0]=[];}
	$table_name=$options['table_name'];
	$table_display_name=$options['table_display_name'];
	$table_sort_head=$options['table_sort_head'] ?? null;
	$td_listener_class=$options['td_listener_class'] ?? null;
	$title=$options['title'] ?? null;
	$update_i_s_ss=$options['update_i_s_ss'] ?? null;
	$use_filter=$options['use_filter'] ?? null;
	$use_generic_manage=$options['use_generic_manage'] ?? null;
	$use_add_new_button=$options['use_add_new_button'];
	$use_span_instead_of_input=$options['use_span_instead_of_input'];
	$wide_row=$options['wide_row'] ?? null;if (!is_array($wide_row)) {$wide_row=[];}
	$wrapping_class=$options['wrapping_class'];
	if (isset($options['table_sort_head']) and $options['table_sort_head']) {$table_sort_head="table_sort_head";$th_clickable="clickable";}else{$table_sort_head="";$th_clickable="";}
	if ($options['form_control']) {$form_control="form-control";}else{$form_control="";}
	if ($sortable) {$sortable_wrapper="build_form_sortable";}else{$sortable_wrapper="";}
	
	foreach ($permission as $perm) {
		$perm_arr_of_hash[]=$perm;
	}
	if (!$update_i_s_ss) {$update_i_s_ss="s";}

	if (isset($perm_arr_of_hash) and $perm_arr_of_hash) {
		if (!is_array($perm_arr_of_hash)) {
			$perm_arr_of_hash=[];
		}
		$perm_csv_of_hash=implode(",",$perm_arr_of_hash);
	}else{
		$perm_csv_of_hash="";
	}

	$table_html="";
	$headers=array_keys($table[0]);
	$table_html.="<div class='generic_input_table_wrapper'>";
		if ($use_filter and $wrapping_class) {
			$table_html.='
					<div class="row my-2">
						<div class="col-md-4 offset-md-4">
							<div class="input-group">
								<input type="text" placeholder="Instant Filter"
									class="form-control filter_this_generic_input w-50 mx-auto"
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
							$skinny_col_style="";
							if (in_array($th_count,$skinny_col)) {$skinny_col_style="sog_ex_skinny_col";}
							// if (in_array($th_count,$wide_row)) {$style_width=" width:200px; ";}
							$table_html.= "<th style='' class='".$skinny_col_style." ".$table_sort_head." ".$th_clickable."'
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
									$skinny_col_style="";
									if (in_array($col_count,$skinny_col)) {$skinny_col_style="sog_ex_skinny_col";}
									$col_name=$headers[$col_count] ?? "";
									$value=htmlspecialchars($row ?? "");
									$id_value=$rows[$id_column_name];if (!$id_value){$id_value="no_id_value_".mt_rand(1,1000000);}
									$table_html.= "<td class='filter_this ".$skinny_col_style."'>";
									if ($col_count==0 and $sortable) {
										$table_html.='<span class="sort_handle"
										data-'.$id_column_name.'='.$id_value.'
										>
										<i class="bi bi-grip-horizontal"></i></span>';
									}
									if (isset($field_class[$col_count])) {
										$class_to_use=$field_class[$col_count];
									}else{
										$class_to_use="";
									}
									if (!isset($col_names[$col_count])) {
										$col_names[$col_count]="";
									}
										if (in_array($col_count,$input_col)) {
											$table_html.='<input type=text title="'.$value.'" class=" sog_ex_generic_update '.$td_listener_class.' '.$class_to_use.' '.$form_control.'"
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
											$table_html.='<input type=checkbox class=" form-check-input sog_ex_generic_update '.$td_listener_class.' '.$class_to_use.' "
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
												$table_html.= '<span class="sort_helper_hidden">'.$value.'</span><span title="'.htmlspecialchars($title_value ?? "", ENT_QUOTES, 'UTF-8').'">'.$row.'</span>';
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

function generic_sql_query($data) {
		//checking if this field is valid is done in spede process
		$con = db_connect(null) or die("Couldn't connect.");
		if ($con->query($data['sql'])){
			$return['status']="success";
		}else {
			$return['status']="fail";
			$return['error_message']=mysqli_error($con);
		}
return $return;
}


function get_generic_sql($data) {
	$fx=__FUNCTION__;

	//check if using remote db
	if (isset($data['remote_db_option']) and $data['remote_db_option']){
		$con = db_connect_remote(null) or die("Couldn't connect to db.");
	}else{
		$con = db_connect(null) or die("Couldn't connect to db.");
	}
	
	$new_array=[];

	if (isset($data['where'])) {$where=$data['where'];}else{$where="";}
	if (isset($data['group_by'])) {$group_by=$data['group_by'];}else{$group_by="";}
	if (isset($data['order_by'])) {$order_by=$data['order_by'];}else{$order_by="";}
	if (isset($data['limit'])) {$limit=$data['limit'];}else{$limit="";}


	$sql=" SELECT ".$data['fields']." 
			FROM ".$data['table_name']." 
			WHERE 1=?
			".$where."  
			".$group_by." 
			".$order_by." 
			".$limit." 
	";
	if (isset($data['return_only']) and $data['return_only']){
		return $sql;
	}
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

function db_connect($db_name) {
	// $value=debug_backtrace()[1]['function'];
	// echo "<br>".$value;
	// array_push($_SESSION['calling_from'],$value);	
	
    if(!isset($connection)) {
		//gets set in top of far.php
		//db name is ignored since now using inside wp
		$creds=get_db_creds(null);
		$localhost=$creds['localhost'];
		$username=$creds['username'];
		$password=$creds['password'];
		$db_to_use=$creds['db_to_use'];

		$connection = mysqli_connect($localhost,$username,$password,$db_to_use);
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    }
    if($connection === false) {
        echo mysqli_connect_error(); 
    }
    return $connection;
}

function get_db_creds($data) {

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


function db_connect_remote($data) {

	//this is for cert in neve child theme
	// $sog_ssl_path=get_stylesheet_directory()."/cert/us-east-1-bundle.pem";

	//this is for cert in same folder as plugin
	$sog_ssl_path=__DIR__."/cert/us-east-1-bundle.pem";

    if(!isset($connection)) {
		//gets set in top of far.php
		//db name is ignored since now using inside wp
		$creds=get_db_creds_remote(null);
		$hostname=$creds['hostname'];
		$username=$creds['username'];
		$password=$creds['password'];
		$port=intval($creds['port']);

		if (isset($data['ignore_db_name']) and $data['ignore_db_name']){
			$db_name=null;
		}else{
			$db_name=$creds['db_name'];
		}

		$connection = mysqli_init();
		if (is_pantheon() and str_contains($hostname,"sogmysql")) {
			$connection->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
			$connection->ssl_set(NULL, NULL, $sog_ssl_path, NULL, NULL); // Key, cert, ca, capath, cipher
		}
		$connection->real_connect($hostname,$username,$password,$db_name,$port);

		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    }
    if($connection === false) {
        echo mysqli_connect_error(); 
    }
    return $connection;
}

function db_connect_remote_check_only($data) {

	//this is for cert in neve child theme
	// $sog_ssl_path=get_stylesheet_directory()."/cert/us-east-1-bundle.pem";

	//this is for cert in same folder as plugin
	$sog_ssl_path=__DIR__."/cert/us-east-1-bundle.pem";

	try {
		//check if hostname is valid by pinging first
		if (1){

			$connection = mysqli_init();
			if (is_pantheon() and str_contains($data['hostname'],"sogmysql")) {
				$connection->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
				$connection->ssl_set(NULL, NULL, $sog_ssl_path, NULL, NULL); // Key, cert, ca, capath, cipher
			}
			$connection->real_connect($data['hostname'],$data['username'],$data['password'],null,intval($data['port']));

			$return['here1']="here";
			if (mysqli_connect_errno()) {
				$return['here2']="not here";
				$return['error_message']=mysqli_connect_error();
				$return['status']="fail";
			}else{
				$return['here3']="maybe here";
				//connection is good, now check for database
				$con = db_connect_remote(array("ignore_db_name"=>1)) or die("Couldn't connect to db.");
				$sql="SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?";
				// return $sql." ".$data['db_name'];
				$place_holder=1;
				$stmt = $con->prepare($sql) or die("Couldn't prepare ".$fx.". ".$sql. " ".$data['db_name']); 
				$stmt -> bind_param("s",$data['db_name']) or die("Couldn't bind ".$fx." ."); 
				$stmt->execute() or die("Couldn't execute ".$fx." ."); 
				$sql_result = $stmt->get_result();
				$num_rows = mysqli_num_rows($sql_result);
				$cols=[];while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
					$new_array[] = $row;
				}
				// $return['new_array']=$new_array;

				if ($num_rows) {
					$return['status']="success";
				}else{
					$return['status']="fail";
					$return['error_message']="Database '".$data['db_name']."' does not exist.";
				}
			}
	
		}else{
			$return['status']="fail";
			$return['error_message']="Hostname unreachable ".$data['hostname'];
		}

		
	} catch (mysqli_sql_exception $e) {
		$return['e']=$e;
		$return['catch']="fail catch";
	}
	
return $return;
}

function get_db_creds_remote($data) {

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

	$return['hostname']=get_option("sog_ex_remote_db_option_hostname");
	$return['username']=get_option("sog_ex_remote_db_option_username");
	$return['password']=get_option("sog_ex_remote_db_option_password");
	$return['db_name']=get_option("sog_ex_remote_db_option_db_name");
	$return['port']=get_option("sog_ex_remote_db_option_port");

	return $return;
}

function update_generic_table_s($data) {
		$con = db_connect(null) or die("Couldn't connect to db. - ".__FUNCTION__);
		$sql="update ".$data['table_name']."
		set ".$data['field_name']."=?
		where ".$data['id_column_name']."=?
		";
		// return $sql."-".$data['value']."-".$data['id'];
		$stmt = $con->prepare($sql) or die("Couldn't prepare update_generic_table_s-".$sql."-".$data['field_name']."-".$data['id']."-".$data['value']."-".$data['id_column_name']); 
		$stmt -> bind_param("si",$data['value'],$data['id']) or die("Couldn't bind update_generic_table_s ."); 
		if ($stmt->execute()) {
			$return['status']="success";
			// $return['sql']=$sql."-".$data['value']."-".$data['id'];
		}else {
			$return['status']="fail";
			$return['error_message']=mysqli_error($con);
		}
return $return;	
}

function update_generic_table_ss($data) {
		$con = db_connect(null) or die("Couldn't connect to db. - ".__FUNCTION__);
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

function update_generic_table_sss($data) {
	$fx=__FUNCTION__;
		$con = db_connect(null) or die("Couldn't connect to db. - ".__FUNCTION__);
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
	$return['sql']=$sql;
return $return;	
}

function update_generic_table_i($data) {
		$con = db_connect(null) or die("Couldn't connect to db. - ".__FUNCTION__);
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

function update_generic_table_is($data) {
	//for value of i but id is s
		$con = db_connect(null) or die("Couldn't connect to db. - ".__FUNCTION__);
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

function create_help_block($text) {
//fas fa-info-circle
echo "<span class='far fa-question-circle sog_ex_help_info_sign clickable mx-1' title='Click for help.'></span>";
echo "<span class='help-block'>";
	echo "<span class='me-1 badge bg-primary'>Help</span>";
	echo $text;
echo "</span>";
}

function add_column($data) {
	$fx=__FUNCTION__;

	$new_array=[];
	$con = db_connect(null) or die("Couldn't connect to db.");

	//chcek if column exists
	$col_name=$data['col_name'];
	$table_name=$data['table_name'];
	$attributes=$data['attributes'];
	$sql="SELECT $col_name
		FROM $table_name
		where 1=?
		limit 1
	";
	// echo $sql;
	$place_holder=1;
	try {
		$con->prepare($sql);
		//new column must exist, so do nothing
	} catch (\mysqli_sql_exception $e) {
		//new col does not exist, so add it
		$test_add_col=generic_sql_query(array("sql"=>"alter table ".$table_name." add column ".$col_name." ".$attributes));
	}

}

function get_primary_key($data) {
	$fx=__FUNCTION__;

	//check if using remote db
	$remote_db_option=get_option("sog_ex_remote_db_option");

	if ($remote_db_option){
		$con = db_connect_remote(null) or die("Couldn't connect to db.");
	}else{
		$con = db_connect(null) or die("Couldn't connect to db.");
	}

	$new_array=[];

	$sql="SHOW KEYS FROM ".$data['table_name']." WHERE 1=? and Key_name = 'PRIMARY'";
	// return $sql;
	$place_holder=1;
	$stmt = $con->prepare($sql) or die("Couldn't prepare ".$fx.". ".$sql); 
	$stmt -> bind_param("i",$place_holder) or die("Couldn't bind ".$fx." ."); 
	$stmt->execute() or die("Couldn't execute ".$fx." ."); 
	$sql_result = $stmt->get_result();
	$num_rows = mysqli_num_rows($sql_result);
	$cols=[];while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
		$new_array[] = $row['Column_name'];
	}
return $new_array;
}

function create_salted_hash_of($thing){
	//this creates an md5 string based on the item in question and the salt.
	//the salt is created when first logging in by combining their username and time()
	return md5($thing.$_SESSION['salt']);	
}

function get_field_name_from_hash($data) {
	$new_array=[];

	//check if using remote db
	$remote_db_option=get_option("sog_ex_remote_db_option");
	$sog_ex_remote_db_option_db_name=get_option("sog_ex_remote_db_option_db_name");

	if ($remote_db_option){
		$con = db_connect_remote(null) or die("Couldn't connect to db.");
		$db_name=$sog_ex_remote_db_option_db_name;
	}else{
		$con = db_connect(null) or die("Couldn't connect to db.");
		$db_name=$_SESSION['db_to_use'];
	}
	
	$sql=" select column_name as field_name, table_name as table_name
			from information_schema.columns
			where 1=?
			and table_schema=?
			and table_name=?
			and md5(concat(column_name,?))=?
	";
	// return $sql."-".$db_name;
	$place_holder=1;
	$stmt = $con->prepare($sql) or die("Couldn't prepare get_db_table_field_name_from_hash ."); 
	$stmt -> bind_param("issss",$place_holder,$db_name,$data['table_name'],$_SESSION['salt'],$data['field_name_hash']) or die("Couldn't bind get_db_table_field_name_from_hash ."); 
	$stmt->execute() or die("Couldn't execute get_db_table_field_name_from_hash ."); 
	$sql_result = $stmt->get_result() or die("Couldn't getresult get_db_table_field_name_from_hash ."); 
	$num_rows = mysqli_num_rows($sql_result);
	$cols=[];while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
		$new_array[] = $row;
	}
		// echo "<pre>";print_r($new_array);echo "</pre>";

return $new_array;
}

function sog_ex_report_generic_update($data){
	$fx=__FUNCTION__;

	//the primary key fields and values have already been verified so ok to use them NOT in a prepared statement
	$primary_key_field_array=explode(",",$data['primary_key_field']);
	$primary_key_value_array=explode(",",$data['primary_key_value']);

	//cerate the sql where clause building from key and value array.
	$where_sql="";
	for ($x=0;$x<count($primary_key_field_array);$x++) {
		$where_sql.="and ".$primary_key_field_array[$x]."='".$primary_key_value_array[$x]."'";
	}


	//check if using remote db
	$remote_db_option=get_option("sog_ex_remote_db_option");
	$sog_ex_remote_db_option_db_name=get_option("sog_ex_remote_db_option_db_name");

	if ($remote_db_option){
		$con = db_connect_remote(null) or die("Couldn't connect to db.");
	}else{
		$con = db_connect(null) or die("Couldn't connect to db.");
	}
	
	$sql="update ".$data['table_name']."
	set ".$data['field_name']."=?
	where 1=1
	".$where_sql."
	";

	$sql_for_display="update ".$data['table_name']."
	set ".$data['field_name']."='".$data['value']."'
	where 1=1
	".$where_sql."
	";

	$return['sql']=$sql." - ".$data['value'];
	$return['sql_for_display']=$sql_for_display;

	try {
		$stmt = $con->prepare($sql) or die("Couldn't prepare ".$fx.". ".$sql); 
		$stmt -> bind_param("s",$data['value']) or die("Couldn't bind ".$fx.". ".$sql." - ".$data['value']); 
		if ($stmt->execute()) {
			$return['status']="success";
		}else {
			$return['status']="fail";
			$return['error_message']=mysqli_error($con);
		}
	} catch (\mysqli_sql_exception $e) {
		
	}
return $return ?? null;
}

function sog_ex_report_restore_value($log_id){
	$fx=__FUNCTION__;
	//if log id is int, proceed
	if (isset($log_id) and is_int($log_id)) {

		//the log table id is passed in, from that get all the other data
		// adding where clause to only allow restoring from a log entry with action of update_record
		$log_data=get_generic_sql(array("table_name"=>"sog_ex_explore_log","fields"=>"*","where"=>"and id=".$log_id." and action='update_record'"));
		
		if (isset($log_data) and $log_data){
			$return['log_data']=$log_data;
			
			//check if exists by trying to get this field, also holds the value before the change in case it needs to be restored
			$value_before_changing=confirm_keys_and_values(array("table_name"=>$log_data[0]['table_name'],"field_name"=>$log_data[0]['field_name'],
			"primary_key_field"=>$log_data[0]['primary_key_field'],"primary_key_value"=>$log_data[0]['primary_key_value']));
			$return['value_before_changing']=$value_before_changing;
	

			//the primary key fields and values have already been verified so ok to use them NOT in a prepared statement
			$primary_key_field_array=explode(",",$log_data[0]['primary_key_field']);
			$primary_key_value_array=explode(",",$log_data[0]['primary_key_value']);

			//cerate the sql where clause building from key and value array.
			$where_sql="";
			for ($x=0;$x<count($primary_key_field_array);$x++) {
				$where_sql.="and ".$primary_key_field_array[$x]."='".$primary_key_value_array[$x]."'";
			}


			$con = db_connect(null) or die("Couldn't connect to db. - ".__FUNCTION__);
			$sql="update ".$log_data[0]['table_name']."
			set ".$log_data[0]['field_name']."=?
			where 1=1
			".$where_sql."
			";
			$return['sql']=$sql." - ".$log_data[0]['old_value'];

			try {
				$stmt = $con->prepare($sql) or die("Couldn't prepare ".$fx.". ".$sql); 
				$stmt -> bind_param("s",$log_data[0]['old_value']) or die("Couldn't bind ".$fx); 
				if ($stmt->execute()) {
					$return['status']="success";
				}else {
					$return['status']="fail";
					$return['error_message']=mysqli_error($con);
				}
			} catch (\mysqli_sql_exception $e) {
				
			}
		}else{
			$return['message']="Couldn't find that record in the log table";
		}
	}else{
		$return['status']="fail";
	}

	//create log entry
	if ($return['status']=="success") {

		//get value that is now stored in db
		$new_db_value=confirm_keys_and_values(array("table_name"=>$log_data[0]['table_name'],"field_name"=>$log_data[0]['field_name'],
		"primary_key_field"=>$log_data[0]['primary_key_field'],"primary_key_value"=>$log_data[0]['primary_key_value'],"value_to_check"=>$value ?? null));
		$return['new_db_value'] = $new_db_value;

		//compare value that now exists to what its supposed to be now, which is the value from the log file being restored
		if (isset($new_db_value) and $new_db_value['new_array'][0][$log_data[0]['field_name']]==$log_data[0]['old_value']){
			//all good, value was replaced
			$return['all_good'] = "update worked";

			//add old and new value to log file
			$new_value=$new_db_value['new_array'][0][$log_data[0]['field_name']];
			$old_value=$value_before_changing['new_array'][0][$log_data[0]['field_name']];

			$the_log=the_log(array(
				"action"=>"restore_field",
				"table_name"=>$log_data[0]['table_name'],
				"field_name"=>$log_data[0]['field_name'],
				"primary_key_field"=>$log_data[0]['primary_key_field'],
				"primary_key_value"=>$log_data[0]['primary_key_value'],
				"old_value"=>$old_value,
				"new_value"=>$new_value,
				"description"=>null,
				"other"=>null,
			));
			$return['the_log'] = $the_log;
		
			}else{
				$return['all_good'] = "update did NOT work so restoring";
				//new value does not match, alert and replace with data selected when verifying the field
				$restore_result=sog_ex_report_generic_update(array("table_name"=>$log_data[0]['table_name'],"field_name"=>$log_data[0]['field_name'],
					"primary_key_field"=>$log_data[0]['primary_key_field'],"primary_key_value"=>$log_data[0]['primary_key_value'],"value"=>$value_before_changing['new_array'][0][$this_field['field_name']]));
				$return['restore_result'] = $restore_result;
				$return['restore_result']['value'] = $value_before_changing['new_array'][0][$this_field['field_name']];

				$result['status'] = "fail";
				$result['error_message'] = "That data did not save properly, most likely the formatting is wrong. The original value will be restored.";
				$result['alert_message'] = "That data did not save properly, most likely the formatting is wrong. The original value will be restored.";
			}
	}

	return $return ?? null;
}

function confirm_keys_and_values($data) {
	$fx=__FUNCTION__;
	// primary_key_field
	// primary_key_value

	$new_array=[];
	$where_sql="";

	$primary_key_field_array=explode(",",$data['primary_key_field']);
	$primary_key_value_array=explode(",",$data['primary_key_value']);

	for ($x=0;$x<count($primary_key_field_array);$x++) {
		$where_sql.="and ".$primary_key_field_array[$x]."=? ";
	}

	//if value_to_check is passed in then add to the where clause to further refine the search
	if (isset($data['value_to_check']) and $data['value_to_check']){
		$where_sql.="and ".$data['field_name']."='".$data['value_to_check']."'";
	}
	

	//check if using remote db
	$remote_db_option=get_option("sog_ex_remote_db_option");
	$sog_ex_remote_db_option_db_name=get_option("sog_ex_remote_db_option_db_name");

	if ($remote_db_option){
		$con = db_connect_remote(null) or die("Couldn't connect to db.");
	}else{
		$con = db_connect(null) or die("Couldn't connect to db.");
	}
	
	$sql="select ".$data['field_name']." 
	from ".$data['table_name']."
	where 1=1
	$where_sql
	";
	$place_holder=1;
	
	try {
		$stmt = $con->prepare($sql); 

		if (count($primary_key_field_array)==1) {
			$stmt -> bind_param("s",$primary_key_value_array[0]) or die("Couldn't bind ".$fx." ."); 
			$binds[]=$primary_key_value_array[0];
		}elseif (count($primary_key_field_array)==2) {
			$stmt -> bind_param("ss",$primary_key_value_array[0],$primary_key_value_array[1]) or die("Couldn't bind ".$fx." ."); 
			$binds[]=$primary_key_value_array[0];
			$binds[]=$primary_key_value_array[1];
		}elseif (count($primary_key_field_array)==3) {
			$stmt -> bind_param("sss",$primary_key_value_array[0],$primary_key_value_array[1],$primary_key_value_array[2]) or die("Couldn't bind ".$fx." ."); 
			$binds[]=$primary_key_value_array[0];
			$binds[]=$primary_key_value_array[1];
			$binds[]=$primary_key_value_array[2];
		}elseif (count($primary_key_field_array)==4) {
			$stmt -> bind_param("ssss",$primary_key_value_array[0],$primary_key_value_array[1],$primary_key_value_array[2],$primary_key_value_array[3]) or die("Couldn't bind ".$fx." ."); 
			$binds[]=$primary_key_value_array[0];
			$binds[]=$primary_key_value_array[1];
			$binds[]=$primary_key_value_array[2];
			$binds[]=$primary_key_value_array[3];
		}else{
			return "More than 4 bindings";
		}

		$stmt->execute() or die("Couldn't execute ".$fx." ."); 
		$sql_result = $stmt->get_result();
		$num_rows = mysqli_num_rows($sql_result);
		$cols=[];while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
			$new_array[] = $row;
		}
		$return['num_rows']=$num_rows;
		$return['sql']=$sql;
		// $return['binds']=$binds;
		$return['new_array']=$new_array;
	} catch (\mysqli_sql_exception $e) {
		
	}
return $return ?? null;

}

function the_log($data) {
	if (isset($data['onyen']) and $data['onyen']) {
		$onyen=$data['onyen'];
	}else{
		$onyen=$_SESSION['sog_explore_user_login'];
	}
	
	
	$fx=__FUNCTION__;
	$con = db_connect(null) or die("Couldn't connect to db. - ".__FUNCTION__);
	
	$sql="insert into sog_ex_explore_log (action, table_name, field_name, primary_key_field, primary_key_value, old_value, new_value, description, other, onyen)
			values (?,?,?,?,?,?,?,?,?,?)
		";
	$stmt = $con->prepare($sql)or die("Couldn't prepare ".$fx." ".$sql);
	$stmt -> bind_param("ssssssssss",	$data['action'],$data['table_name'],$data['field_name'],
										$data['primary_key_field'],$data['primary_key_value'],
										$data['old_value'],$data['new_value'],$data['description'],
										$data['other'],$onyen)or die("Couldn't bind ".$fx." .");
	if ($stmt->execute()) {
		return 1;
	}else {
		return mysqli_error($con);
	}
}

function is_pantheon(){
	if (isset($_ENV['PANTHEON_SITE_NAME']) and isset($_ENV['PANTHEON_ENVIRONMENT'])) {
		return true;
	}else{
		return false;
	}

}

function httpPost($url, $data){
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_REFERER, $_SERVER['SERVER_NAME']);
	// curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); //need over 5 for cityworks to work
	curl_setopt($curl, CURLOPT_TIMEOUT, 10); //timeout in seconds
    $response = curl_exec($curl);
	if($response === false) {
        echo "<br>Error Number: ".curl_errno($curl);
        echo "<br>Error String: ".curl_error($curl);
    }
    curl_close($curl);
    return $response;
}

function httpGet($url, $data){
	if ($data) {
		$parameters = http_build_query($data);
		$url=$url."?".$parameters;
	}
   $curl = curl_init($url);

	curl_setopt($curl, CURLOPT_REFERER, $_SERVER['SERVER_NAME']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_VERBOSE, true);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5); 
	curl_setopt($curl, CURLOPT_TIMEOUT, 5); //timeout in seconds
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($curl);
	$info = curl_getinfo($curl);
	// echo "<pre>";print_r($info);echo "</pre>";

	if($response === false) {
        $response="<br>Error Number:".curl_errno($curl)."<Br>Error String:".curl_error($curl);
    }
    curl_close($curl);
return $response;
}

function httpPing($url){
   $curl = curl_init($url);

	curl_setopt($curl, CURLOPT_REFERER, $_SERVER['SERVER_NAME']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_VERBOSE, true);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2); 
	curl_setopt($curl, CURLOPT_TIMEOUT, 2); //timeout in seconds
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($curl);
	$return['info'] = curl_getinfo($curl);

	// echo "<pre>";print_r($info);echo "</pre>";

	if($response === false) {
        $return['error_messgae']="<br>Error Number:".curl_errno($curl)."<Br>Error String:".curl_error($curl);
    }
    curl_close($curl);
return $return;
}

function create_sql_tables(){
	// $con = db_connect(null) or die("Couldn't connect to db.");
	
	$sql_report_tables="
		CREATE TABLE IF NOT EXISTS sog_ex_report_tables (
			fk_table_name varchar(100) NOT NULL,
			use_this int(1) null,
			allow_update int(1) null,
			description text,
			display_name varchar(255) DEFAULT NULL,
			sort_order decimal(4,2) DEFAULT NULL,
			status int DEFAULT '1',
			date_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (fk_table_name)
		);
		";
	$sql_explore_filter="

		CREATE TABLE IF NOT EXISTS sog_ex_explore_filter (
			fk_table_name varchar(100) NOT NULL COMMENT 'sql table name',
			field_name varchar(50) NOT NULL,
			value varchar(255) NULL,
			si varchar(1) DEFAULT 's',
			fk_username varchar(50) NOT NULL,
			status int DEFAULT '1',
			date_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (fk_table_name,field_name,fk_username)
		);
	";

	$sog_ex_explore_user_choices="

		CREATE TABLE IF NOT EXISTS sog_ex_explore_user_choices (
			fk_username varchar(50) NOT NULL,
            fk_table_name varchar(100) not null,
            fields_chosen text null,
			group_by_fields text NULL,
			edit_fields text NULL,
			sort_sql varchar(255) null,
			date_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (fk_username,fk_table_name)
		);
	";
	$sog_ex_explore_log_table="

		CREATE TABLE IF NOT EXISTS sog_ex_explore_log (
			id int(11) NOT NULL AUTO_INCREMENT,
			action varchar(255) DEFAULT NULL,
			table_name varchar(255) DEFAULT NULL,
			field_name varchar(255) DEFAULT NULL,
			primary_key_field varchar(255) DEFAULT NULL,
			primary_key_value varchar(255) DEFAULT NULL,
			old_value text null,
			new_value text null,
			description text DEFAULT NULL,
			other text DEFAULT NULL,
			onyen varchar(50) DEFAULT NULL,
			date_modified timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
			PRIMARY KEY (id)
		);
	";

	$return['sql_report_tables']=generic_sql_query(array("sql"=>$sql_report_tables));
	$return['sql_explore_filter']=generic_sql_query(array("sql"=>$sql_explore_filter));
	$return['sog_ex_explore_user_choices']=generic_sql_query(array("sql"=>$sog_ex_explore_user_choices));
	$return['sog_ex_explore_log_table']=generic_sql_query(array("sql"=>$sog_ex_explore_log_table));

	//need to add column allow_update
	$return['adding_allow_update_col']=add_column(array("col_name"=>"allow_update","table_name"=>"sog_ex_report_tables","attributes"=>"int(1) null after use_this"));
	$return['adding_value_like_col']=add_column(array("col_name"=>"value_like","table_name"=>"sog_ex_explore_filter","attributes"=>"varchar(255) null after value"));
	$return['adding_allow_insert_col']=add_column(array("col_name"=>"allow_insert","table_name"=>"sog_ex_report_tables","attributes"=>"int(1) null after allow_update"));
	$return['adding_allow_insert_col']=add_column(array("col_name"=>"sort_sql","table_name"=>"sog_ex_explore_user_choices","attributes"=>"varchar(255) null after edit_fields"));


return $return;	
}






































?>
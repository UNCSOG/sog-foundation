<?php

namespace sog_settings;

// Exit if accessed directly.
defined('ABSPATH') || exit;

ini_set('display_errors', 0);
// error_reporting(E_ERROR);
// error_reporting(E_ALL);

if(!session_id()) {
    session_start();
}

function is_pantheon()
{
    if (isset($_ENV['PANTHEON_SITE_NAME']) and isset($_ENV['PANTHEON_ENVIRONMENT'])) {
        return true;
    } else {
        return false;
    }

}

function settings_page()
{

    //create sql tables needed for plugin
    create_sql_tables();

    load_settings_into_session();

    $menu_items = [
        ['name' => 'Instructions', 'slug' => 'setting_instructions'],
        ['name' => 'Settings', 'slug' => 'setting_settings'],
        ['name' => 'Manage Settings', 'slug' => 'setting_manage'],
    ];

    ?>

	<div class="row my-2">
		<div class="col">
			<h1 class="text-center">
				SOG Settings
			</h1>
			<h6 class="text-center">
				UNC School of Government
			</h6>
		</div>
	</div>
	<div class="row m-4">
		<div class="col-sm-3 col-xl-2 py-2 bg-white settings_menu border">
			<?php foreach ($menu_items as $menu) { ?>
				<div class="btn border  shadow-sm sog_settings_menu_button my-1"
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

function display_setting_manage($data)
{
    // echo "<pre>";print_r($data);echo "</pre>";

    //manually setting some of this data
    $data['table_name'] = 'sog_settings';
    $data['table_display_name'] = 'SOG Settings';

    //initialize variables
    $delete_column = 1;
    $download_only = 0;
    $prevent_update = [];
    $textarea_col = [];
    $skip_col = [];
    $id_column_name = '';
    $helper_text = '';
    $field_class = '';
    $input_col = [];
    $checkbox_col = [];
    $col_names = [];
    $add_row_0 = '';
    $skinny_col = [];
    $sortable = 0;
    $add_new_row = 0;
    $update_i_s_ss = '';

    $sortable = 0;
    $add_row_0 = 'name';
    $third_field = '';
    $add_new_row = 1;
    $input_col = [1, 3, 4, 5, 6];
    $checkbox_col = [2];
    $skinny_col = [0];
    $skip_col = [];
    $col_names = ['ID', 'Name', 'Is Bool', 'Value', 'Category', 'Sort', 'Description'];
    $table_data = get_generic_sql(['table_name' => $data['table_name'], 'fields' => 'id, name, is_bool, value, category, sort, description', 'order_by' => 'order by category,sort,id desc']);

    ?>

<div class="row mt-4">
		<div class="col h5 ">
			<?php echo $data['table_display_name'];?>
		</div>
		<div class="small ">
			<?php echo $helper_text;?>
		</div>
	</div>
	<div class="row my-2">
		<div class="col-sm-12 manage_dataset_output">
		<?php
                    display_generic_manage_table(['table_data' => $table_data,
                        'field_class' => $field_class,
                        'delete_column' => $delete_column,
                        'input_col' => $input_col,
                        'checkbox_col' => $checkbox_col ?? '',
                        'prevent_update' => $prevent_update,
                        'textarea_col' => $textarea_col,
                        'table_display_name' => $data['table_display_name'],
                        'col_names' => $col_names,
                        'add_row_0' => $add_row_0,
                        'skinny_col' => $skinny_col,
                        'sortable' => $sortable,
                        'skip_col' => $skip_col,
                        'use_generic_manage' => 1,
                        'table_name' => $data['table_name'],
                        'add_new_row' => $add_new_row,
                        'id_column_name' => $id_column_name,
                        'update_i_s_ss' => $update_i_s_ss,
                    ]
                    );
    ?>

		</div>
	</div>


<?php
}

function display_setting_settings()
{
    $settings = get_setting_data(null);
    // echo "<pre>";print_r($settings);echo "</pre>";

    $previous_category = '';

    ?>
        <div class="my-3 row"
            data-slug="setting_feedback"
        >
                    <div class="col-md-12">
                        <div class="h3 text-center">
                            Settings
                        </div>
                    </div>
                    <?php
                        if ($_SESSION['mpacp_is_demo_site']) {
                            ?>
                                <div class="row">
                                    <div class="col text-center">
                                        <div class="text-danger">
                                            DEMO Site.
                                            <br>Any changes you make here will ONLY be for the DEMO site.
                                            <br>They will NOT be overwritten with the next sync.
                                        </div>
                                    </div>
                                </div>
                            <?php
                        }
    ?>
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
                                                    <input type="checkbox" class="sog_settings_update_setting xform-check-input"
                                                        data-setting_id="<?php echo $setting['id'];?>"
                                                        <?php if ($setting['value']) {
                                                            echo 'checked';
                                                        }?>
                                                        data-is_bool="<?php echo $setting['is_bool'];?>"
                                                        >
                                            <?php } else { ?>
                                                    <input  type="text" class="form-control sog_settings_update_setting" value="<?php echo htmlspecialchars($setting['value'] ?? '');?>"
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
                                <?php $previous_category = $setting['category'];?>
                            <?php } ?>
                        </table>
                    </div>
        </div>

    <?php
}

function display_setting_instructions()
{
    ?>
        <div class="row">
            <div class="col">
                <div>
                    <div>
                        These settings do not do anything by themselves.  They serve as a location to be referenced in other parts of the website or other applications.
                    </div>
                    <div>
                        To use on this website they can be retreived in a few ways.
                    </div>
                </div>
                <ul>
                    <li><strong>Session Variable</strong> - They are stored in the global $_SESSION variable and can for example setting #2 can be retrieved like this <code>$_SESSION['sog_setting_2']</code>.
                    This is the fastest way to use a setting especially if you need it inside a loop, making the fx call (defined below) is inefficient in a loop.
                    <br>If you look in the code you will see that I am outputing the value of Demo Test Setting 2, feel free to change it then return to this page. <?php echo $_SESSION['sog_setting_2'];?>
                    <li><strong>Function Call for Boolean Setting</strong> - You can get the value of a boolean setting by calling <code>is_setting(1)</code> which will return true or false or will output 1 or 0. Setting 1: <?php echo is_setting(1);?>
                    <li><strong>Function Call for Text Setting</strong> - You can get the value of a setting by calling <code>get_setting(2)</code> which will return the value of the setting. Value of setting 2: <?php echo get_setting(2);?>
                    <li><strong>Directly from SQL</strong> - The settings are stored in a sql table called sog_settings in the WP DB.  The field names are visible on the Manage Settings page.
                    <li><strong>Elsewhere in WP</strong> - You can use the settings from the <code>$_SESSION</code> variable anywhere, but if you want to call the functions <code>is_setting</code> or <code>get_setting</code>
					you will have to use the namsspace.
					<br>Inside another plugin you will need to add <code>use sog_settings;</code> at the top of the file and then prepend the name space. Outisde a plugin you just need to prepend it with the name space lke this <code>sog_settings\get_setting(2);</code>
                </ul>
				<div>
					To Create additional tabs on the left.
				</div>
                <ul>
                    <li>Find the function <code>settings_page()</code> and add an additional array to the <code>menu_items</code> array.
                    <li>Create a function that begins with <code>display_</code> and then add the slug.
					<li>For example, the slug for this page is <code>setting_instructions</code>, and this content is in a function called <code>display_setting_instructions()</code>.
                </ul>
            </div>
        </div>
<?php
}

function get_setting_data($data)
{
    $fx = __FUNCTION__;
    if (isset($data['order_by']) and $data['order_by']) {
        $order_by = 'order by ' . $data['order_by'];
    } else {
        $order_by = 'order by category, sort';
    }
    if (isset($data['in_dom']) and $data['in_dom']) {
        $in_dom_sql = 'and in_dom=1';
    } else {
        $in_dom_sql = '';
    }
    $new_array = [];
    $con = db_connect(null) or die("Couldn't connect to db.");

    $sql = "SELECT id,name, is_bool, value, category, sort, description
            FROM sog_settings
            where 1=?
            $in_dom_sql
            $order_by
        ";
    // return $sql;
    $place_holder = 1;
    $stmt = $con->prepare($sql) or die('couldnt prepare ' . $fx . ' ' . $sql . ' ' . $con->error);
    $stmt -> bind_param('i', $place_holder) or die('couldnt bind ' . $fx);
    $stmt->execute() or die('couldnt execute ' . $fx);
    $sql_result = $stmt->get_result();
    $num_rows = mysqli_num_rows($sql_result);
    $cols = [];
    while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
        $new_array[] = $row;
    }
    return $new_array;
}

function create_sql_tables()
{
    // $con = db_connect(null) or die("Couldn't connect to db.");

    $sql_settings_table = "
        CREATE TABLE IF NOT EXISTS sog_settings (
            id int NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            is_bool int DEFAULT NULL,
            value text,
            description text,
            category varchar(50) DEFAULT NULL,
            sort float(6,2) DEFAULT NULL,
            status int DEFAULT '1',
            date_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
		);

		";

    $return['sql_report_tables'] = generic_sql_query(['sql' => $sql_settings_table]);

    //need to add column allow_update
    $return['adding_allow_update_col'] = add_column(['col_name' => 'allow_update', 'table_name' => 'sog_ex_report_tables', 'attributes' => 'int(1) null after use_this']);

    return $return;
}

function add_column($data)
{
    $fx = __FUNCTION__;

    $new_array = [];
    $con = db_connect(null) or die("Couldn't connect to db.");

    //chcek if column exists
    $col_name = $data['col_name'];
    $table_name = $data['table_name'];
    $attributes = $data['attributes'];
    $sql = "SELECT $col_name
		FROM $table_name
		where 1=?
		limit 1
	";
    // echo $sql;
    $place_holder = 1;
    try {
        $con->prepare($sql);
        //new column must exist, so do nothing
    } catch (\mysqli_sql_exception $e) {
        //new col does not exist, so add it
        $test_add_col = generic_sql_query(['sql' => 'alter table ' . $table_name . ' add column ' . $col_name . ' ' . $attributes]);
    }

}

function get_generic_sql($data)
{

    // $value=debug_backtrace()[1]['function'];

    $fx = __FUNCTION__;
    $con = db_connect(null) or die("Couldn't connect to db. " . $data['db_name'] . '-' . $fx);

    $new_array = [];

    if (isset($data['where'])) {
        $where = $data['where'];
    } else {
        $where = '';
    }
    if (isset($data['group_by'])) {
        $group_by = $data['group_by'];
    } else {
        $group_by = '';
    }
    if (isset($data['order_by'])) {
        $order_by = $data['order_by'];
    } else {
        $order_by = '';
    }
    if (isset($data['limit'])) {
        $limit = $data['limit'];
    } else {
        $limit = '';
    }

    $sql = ' SELECT ' . $data['fields'] . '
			FROM ' . $data['table_name'] . '
			WHERE 1=?
			' . $where . '
			' . $group_by . '
			' . $order_by . '
			' . $limit . '
	';
    // echo $data['x'].$sql;
    // return $sql;
    $place_holder = 1;
    $stmt = $con->prepare($sql) or die("Couldn't prepare get_generic_sql. " . $sql . ' - ' . $con->error);
    $stmt -> bind_param('i', $place_holder) or die("Couldn't bind get_generic_sql .");
    $stmt->execute() or die("Couldn't execute get_generic_sql .");
    $sql_result = $stmt->get_result();
    $num_rows = mysqli_num_rows($sql_result);
    $cols = [];
    while ($row = $sql_result->fetch_array(MYSQLI_ASSOC)) {
        $new_array[] = $row;
    }

    return $new_array;

}

function generic_sql_query($data)
{
    //checking if this field is valid is done in spede process
    $con = db_connect(null) or die("Couldn't connect.");
    if ($con->query($data['sql'])) {
        $return['status'] = 'success';
    } else {
        $return['status'] = 'fail';
        $return['error_message'] = mysqli_error($con);
    }
    return $return;
}

function display_generic_manage_table($data)
{
    // echo "<pre>";print_r($all_log_actions);echo "</pre>";
    $table_options = [	'data' => $data['table_data'], //this holds the array of data returned from sql
        'id' => $data['table_name'] . '_table', //the html id of the table
        'class' => 'table table-bordered table-sm table-hover', //the classes for this table
        'field_class' => $data['field_class'], //which rows beginning with 0 will have input fields
        'input_col' => $data['input_col'], //which rows beginning with 0 will have input fields
        'prevent_update' => $data['prevent_update'], //which rows beginning with 0 will have input fields
        'textarea_col' => $data['textarea_col'], //which rows beginning with 0 will have input fields
        'checkbox_col' => $data['checkbox_col'], //which rows beginning with 0 will have input fields
        'col_names' => $data['col_names'], //names of columns for header row
        'id_column_name' => $data['id_column_name'], //which col name from data is unique id col
        'db' => $_SESSION['db_to_use'], //the name of the db
        'table_name' => $data['table_name'], //the name of the table to be updated
        // "permission"=>array(1,2), //array of permission id's
        // "reload_on_add_row"=>1, //reload_on_add_row or reload_with_ajax or neither will do nothing
        'reload_with_ajax' => 1,
        'add_new_row' => $data['add_new_row'], //which col, beginning with 0, columns 1=name //only for string input fields for now
        'add_row_0' => $data['add_row_0'],
        'table_display_name' => $data['table_display_name'],
        'delete_col' => $data['delete_column'], //Should a delete column be included
        'delete_class' => 'sog_settings_delete', //Should a delete column be included
        'change_status_on_delete' => 0, //change status to 0 instead of deleting
        'closest' => 'tr',
        'skinny_col' => $data['skinny_col'],
        // "wide_row"=>1, //which col has the description and should be longer.
        'form_control' => 1, //should form-control be added to inputs
        'table_sort_head' => 1,
        'sortable' => $data['sortable'], //drag and drop sortable
        'use_span_instead_of_input' => 1, //for non input cells
        'use_filter' => false,
        'wrapping_class' => '.generic_input_table_wrapper',
        'use_generic_manage' => $data['use_generic_manage'],
        'skip_col' => $data['skip_col'],
        'update_i_s_ss' => $data['update_i_s_ss'],
        'allow_duplicates' => false,
        'use_add_new_button' => false,
    ];
    $table_html = build_table_with_inputs($table_options);
    echo $table_html;
}

function build_table_with_inputs($options)
{
    // echo "<pre>options";print_r($options);echo "</pre>";

    $add_link = $options['add_link'] ?? null ;
    if (!is_array($add_link)) {
        $add_link = [];
    } //not coded yet
    $add_new_row = $options['add_new_row'];
    $add_row_0 = $options['add_row_0'];
    $allow_duplicates = $options['allow_duplicates'] ?? null;
    $change_status_on_delete = $options['change_status_on_delete'];
    $checkbox_col = $options['checkbox_col'] ?? null;
    if (!is_array($checkbox_col)) {
        $checkbox_col = [];
    }
    $class = $options['class'];
    $closest = $options['closest'];
    $col_names = $options['col_names'];
    $field_class = $options['field_class'] ?? null;
    if (!is_array($field_class)) {
        $field_class = [];
    }
    $db = $options['db'] ?? null;
    $delete = $options['delete_col'];
    $delete_class = $options['delete_class'];
    $id = $options['id'];
    $id_column_name = $options['id_column_name'];
    if (!$id_column_name) {
        $id_column_name = 'id';
    }
    $ignore_last_inserted_id = $options['ignore_last_inserted_id'] ?? null;
    $input_col = $options['input_col'];
    if (!is_array($input_col)) {
        $input_col = [];
    }
    $order_column = $options['order_column'] ?? null;
    $order_direction = $options['order_direction'] ?? null;
    $permission = $options['permission'] ?? null;
    if (!is_array($permission)) {
        $permission = [];
    }
    $prevent_update = $options['prevent_update'] ?? null;
    if (!is_array($prevent_update)) {
        $prevent_update = [];
    }
    $reload_on_add_row = $options['reload_on_add_row'] ?? null;
    $reload_with_ajax = $options['reload_with_ajax'];
    $skinny_col = $options['skinny_col'] ?? null;
    if (!is_array($skinny_col)) {
        $skinny_col = [];
    }
    $skip_col = $options['skip_col'] ?? null;
    if (!is_array($skip_col)) {
        $skip_col = [];
    }
    $sortable = $options['sortable'];
    $table = $options['data'] ?? null;
    if (!isset($table[0])) {
        $table[0] = [];
    }
    $table_name = $options['table_name'];
    $table_display_name = $options['table_display_name'];
    $table_sort_head = $options['table_sort_head'];
    $td_listener_class = $options['td_listener_class'] ?? null;
    $title = $options['title'] ?? null;
    $update_i_s_ss = $options['update_i_s_ss'] ?? null;
    $use_filter = $options['use_filter'] ?? null;
    $use_generic_manage = $options['use_generic_manage'] ?? null;
    $use_add_new_button = $options['use_add_new_button'];
    $use_span_instead_of_input = $options['use_span_instead_of_input'];
    $wide_row = $options['wide_row'] ?? null;
    if (!is_array($wide_row)) {
        $wide_row = [];
    }
    $wrapping_class = $options['wrapping_class'];
    if ($options['table_sort_head']) {
        $table_sort_head = 'table_sort_head';
        $th_clickable = 'clickable';
    }
    if ($options['form_control']) {
        $form_control = 'form-control';
    }
    if ($sortable) {
        $sortable_wrapper = 'build_form_sortable';
    } else {
        $sortable_wrapper = '';
    }

    foreach ($permission as $perm) {
        $perm_arr_of_hash[] = $perm;
    }
    if (!$update_i_s_ss) {
        $update_i_s_ss = 's';
    }

    if (isset($perm_arr_of_hash) and $perm_arr_of_hash) {
        if (!is_array($perm_arr_of_hash)) {
            $perm_arr_of_hash = [];
        }
        $perm_csv_of_hash = implode(',', $perm_arr_of_hash);
    } else {
        $perm_csv_of_hash = '';
    }

    $table_html = '';
    $headers = array_keys($table[0]);
    $table_html .= "<div class='generic_input_table_wrapper'>";
    if ($use_filter and $wrapping_class) {
        $table_html .= '
					<div class="row">
						<div class="col-md-4 offset-md-4">
							<div class="input-group">
								<input type="text" placeholder="Instant Filter"
									class="form-control filter_this_generic_input w-50 mx-auto"
									data-wrapper="' . $wrapping_class . '"
								>
								<span class="input-group-text  bg-unc_navy text-white">
									<i class="bi bi-search"></i>
								</span>
							</div>
						</div>
					</div>
				';

    }
    $table_html .= "<table id='" . $id . "' class='generic_input_table " . $class . "' data-initial_sort='" . $order_column . "' data-order_direction='" . $order_direction . "'>";
    $table_html .= '<thead>';
    $table_html .= '<tr>';
    $th_count = 0;
    foreach ($headers as $header) {
        if (!in_array($th_count, $skip_col)) {
            $skinny_col_style = '';
            if (in_array($th_count, $skinny_col)) {
                $skinny_col_style = 'skinny_col';
            }
            // if (in_array($th_count,$wide_row)) {$style_width=" width:200px; ";}
            $table_html .= "<th style='' class='" . $skinny_col_style . ' ' . $table_sort_head . ' ' . $th_clickable . "'
								data-has_add_new='" . $add_new_row . "'
							>";
            if ($col_names[$th_count]) {
                $table_html .= $col_names[$th_count];
            } else {
                $table_html .= $header;
            }
            if ($table_sort_head) {
                $table_html .= "<i class='bi bi-sort-down text-muted ps-2'></i>";
            }
            $table_html .= '</th>';
        }
        $th_count++;
    }
    if ($delete) {
        $table_html .= "<th class='skinny_col'></th>";
    }
    $table_html .= '</tr>';
    $table_html .= '</thead>';
    $table_html .= "<tbody class=' " . $sortable_wrapper . " '
				data-db='" . $db . "'
				data-table_name='" . $table_name . "'
				data-id_column_name='" . $id_column_name . "'
			>";
    if ($add_new_row) {
        $table_html .= "<tr class='add_new'>";
        $headers_count = count($headers) - count($skip_col);
        for ($x = 0;$x < $headers_count;$x++) {
            $table_html .= '<td>';
            if ($x == $add_new_row) {
                $table_html .= '
									<div class="input-group">
										<input type=text title="Add New ' . $col_names[$x] . '" placeholder="Add New ' . $col_names[$x] . '"
											class="sog_settings_table_add_new ' . $form_control . '"
											data-allow_duplicates="' . $allow_duplicates . '"
											data-use_generic_manage="' . $use_generic_manage . '"
											data-si="s"
											data-db="' . $db . '"
											data-table_name="' . $table_name . '"
											data-field_name="' . $headers[$x] . '"
											data-col_num="' . $add_new_row . '"
											data-reload_on_add_row="' . $reload_on_add_row . '"
											data-reload_with_ajax="' . $reload_with_ajax . '"
											data-p="' . $perm_csv_of_hash . '"
											data-ignore_last_inserted_id="' . $ignore_last_inserted_id . '"
										>';
                if ($use_add_new_button) {
                    $table_html .= '
											<span class="input-group-text add_new_item bg-white px-2 py-0 clickable">
												<i class="bi bi-plus-square-fill text-success bg-white"></i>
											</span>
											';
                }
                $table_html .= '
									</div>';
            }
            $table_html .= '</td>';
        }
        if ($delete) {
            $table_html .= '<td></td>';
        }

        $table_html .= '</tr>';
    }
    foreach ($table as $rows) {
        if (!isset($rows[$id_column_name])) {
            $rows[$id_column_name] = '';
        }
        $table_html .= "<tr class='filter_row " . $table_name . '_row_' . $rows[$id_column_name] . "'>";
        $col_count = 0;
        foreach ($rows as $row) {
            if (!in_array($col_count, $skip_col)) {
                $skinny_col_style = '';
                if (in_array($col_count, $skinny_col)) {
                    $skinny_col_style = 'skinny_col';
                }
                $col_name = $headers[$col_count] ?? '';
                $value = htmlspecialchars($row ?? '');
                $id_value = $rows[$id_column_name];
                if (!$id_value) {
                    $id_value = 'no_id_value_' . mt_rand(1, 1000000);
                }
                $table_html .= "<td class='filter_this " . $skinny_col_style . "'>";
                if ($col_count == 0 and $sortable) {
                    $table_html .= '<span class="sort_handle"
										data-' . $id_column_name . '=' . $id_value . '
										>
										<i class="bi bi-grip-horizontal"></i></span>';
                }
                if (isset($field_class[$col_count])) {
                    $class_to_use = $field_class[$col_count];
                } else {
                    $class_to_use = '';
                }
                if (!isset($col_names[$col_count])) {
                    $col_names[$col_count] = '';
                }
                if (in_array($col_count, $input_col)) {
                    $table_html .= '<input type=text title="' . $value . '" class=" sog_settings_generic_update ' . $td_listener_class . ' ' . $class_to_use . ' ' . $form_control . '"
															data-si="' . $update_i_s_ss . '"
															data-db="' . $db . '"
															data-table_name="' . $table_name . '"
															data-field_name="' . $col_name . '"
															data-id_column_name="' . $id_column_name . '"
															data-' . $id_column_name . '="' . $id_value . '"
															data-p="' . $perm_csv_of_hash . '"
															id="' . $id . '_' . $col_name . '_' . $id_value . '"
															value="' . $value . '"
															aria-label="' . $col_names[$col_count] . '"
															><span class="sort_helper_hidden">' . $value . '</span>';
                } elseif (in_array($col_count, $prevent_update)) {
                    $table_html .= '<input type=text title="' . $value . '" class="  ' . $td_listener_class . ' ' . $form_control . '"
															readonly disabled
															value="' . $value . '"
															aria-label="' . $col_names[$col_count] . '"
															><span class="sort_helper_hidden">' . $value . '</span>';
                } elseif (in_array($col_count, $checkbox_col)) {
                    if ($row) {
                        $checked = 'checked';
                    } else {
                        $checked = '';
                    }
                    $table_html .= '<input type=checkbox class=" form-check-input sog_settings_generic_update ' . $td_listener_class . ' ' . $class_to_use . ' "
															title="' . $col_name . '"
															' . $checked . '
															data-si="i"
															data-' . $col_name . '="' . $value . '"
															data-db="' . $db . '"
															data-table_name="' . $table_name . '"
															data-field_name="' . $col_name . '"
															data-id_column_name="' . $id_column_name . '"
															data-' . $id_column_name . '="' . $id_value . '"
															id="' . $id . '_' . $col_name . '_' . $id_value . '"
															data-p="' . $perm_csv_of_hash . '"
															value="1"
															aria-label="' . $col_names[$col_count] . '"
															><span class="sort_helper_hidden">' . $value . '</span>';
                } else {
                    if ($use_span_instead_of_input) {
                        if ($col_names[$col_count] = 'Count') {
                            $title_value = end($rows);
                        } else {
                            $title_value = $value;
                        }
                        $table_html .= '<span class="sort_helper_hidden">' . $value . '</span><span title="' . htmlspecialchars($title_value ?? '', ENT_QUOTES, 'UTF-8') . '">' . $row . '</span>';
                    } else {
                        $table_html .= '<input class="form-control" title="' . $title_value . '" type=text value="' . $value . '"
															aria-label="' . $col_names[$col_count] . '"
												>';
                    }
                }
                $table_html .= '</td>';
            }
            $col_count++;
        }
        if ($delete) {
            $table_html .= '<td class="text-center skinny_col"><i class="far fa-trash-alt ' . $delete_class . ' clickable"';
            if ($change_status_on_delete) {
                $table_html .= 'title="This will be removed from view but can be restored later."';
            } else {
                $table_html .= 'title="This completely deletes this record from the database."';
            }
            $table_html .= '
													data-change_status="' . $change_status_on_delete . '"
													data-closest="' . $closest . '"
													data-db="' . $db . '"
													data-table_name="' . $table_name . '"
													data-' . $col_name . '="' . $value . '"
													data-id_column_name="' . $id_column_name . '"
													data-p="' . $perm_csv_of_hash . '"
													data-' . $id_column_name . '="' . $id_value . '"
													value="' . $value . '"
												></i></td>';
        }

        $table_html .= '</tr>';
    }
    $table_html .= '</tbody>';
    $table_html .= '<tfoot>';
    $table_html .= '</tfoot>';
    $table_html .= '</table>';
    $table_html .= '</div>';
    if (!$table[0]) {
        $table_html = '<input type=text title="Add New" placeholder="Add New"
			class="sog_settings_table_add_new ' . $form_control . '"
			data-si="s"
			data-db="' . $db . '"
			data-use_generic_manage="' . $use_generic_manage . '"
			data-table_name="' . $table_name . '"
			data-table_display_name="' . $table_display_name . '"
			data-field_name="' . $add_row_0 . '"
			data-col_num="' . $add_new_row . '"
			data-reload_on_add_row="' . $reload_on_add_row . '"
			data-reload_with_ajax="' . $reload_with_ajax . '"
			data-p="' . $perm_csv_of_hash . '"
			data-ignore_last_inserted_id="' . $ignore_last_inserted_id . '"
			>';

    }
    return $table_html;
}

function db_connect($data)
{
    // $value=debug_backtrace()[1]['function'];
    // array_push($_SESSION['calling_from'],$value);

    if(!isset($connection)) {
        $creds = get_db_creds(null);
        // echo "<pre>creds";print_r($creds);echo "</pre>";

        $localhost = $creds['localhost'];
        $username = $creds['username'];
        $password = $creds['password'];
        $db_to_use = $creds['db_to_use'];

        $connection = mysqli_connect($localhost, $username, $password, $db_to_use);
    }
    if($connection === false) {
        echo mysqli_connect_error();
    }
    return $connection;
}

function create_db_creds()
{
    $_SESSION['db_localhost'] = DB_HOST;
    $_SESSION['db_username'] = DB_USER;
    $_SESSION['db_password'] = DB_PASSWORD;
    $_SESSION['db_to_use'] = DB_NAME;
}

function get_db_creds($data)
{

    //this running as else is slwing thigns down, think only reason was from multiple local sessions
    if ($_SESSION['db_localhost']) {
        $return['localhost'] = $_SESSION['db_localhost'];
        $return['username'] = $_SESSION['db_username'];
        $return['password'] = $_SESSION['db_password'];
        $return['db_to_use'] = $_SESSION['db_to_use'];
    } else {
        if (file_exists('../../../wp-load.php')) {
            require_once('../../../wp-load.php');
        }
        if (file_exists('../../wp-load.php')) {
            require_once('../../wp-load.php');
        }
        if (file_exists('../wp-load.php')) {
            require_once('../wp-load.php');
        }
        if (file_exists('wp-load.php')) {
            require_once('wp-load.php');
        }
        if (file_exists('/wp-load.php')) {
            require_once('/wp-load.php');
        }
        $get_defined_constants = get_defined_constants();
        $return['localhost'] = $get_defined_constants['DB_HOST'];
        $return['username'] = $get_defined_constants['DB_USER'];
        $return['password'] = $get_defined_constants['DB_PASSWORD'];
        $return['db_to_use'] = $get_defined_constants['DB_NAME'];
    }
    return $return;
}

function update_generic_table_s($data)
{
    $con = db_connect(null) or die("Couldn't connect to db. - " . __FUNCTION__);
    $sql = 'update ' . $data['table_name'] . '
    set ' . $data['field_name'] . '=?
    where ' . $data['id_column_name'] . '=?
    ';
    // return $sql."-".$data['value']."-".$data['id'];
    $stmt = $con->prepare($sql) or die("Couldn't prepare update_generic_table_s-" . $sql . '-' . $data['field_name'] . '-' . $data['id'] . '-' . $data['value'] . '-' . $data['id_column_name']);
    $stmt -> bind_param('si', $data['value'], $data['id']) or die("Couldn't bind update_generic_table_s .");
    if ($stmt->execute()) {
        $return['status'] = 'success';
        // $return['sql']=$sql."-".$data['value']."-".$data['id'];
    } else {
        $return['status'] = 'fail';
        $return['error_message'] = mysqli_error($con);
    }
    return $return;
}

function update_generic_table_ss($data)
{
    $con = db_connect(null) or die("Couldn't connect to db. - " . __FUNCTION__);
    $sql = 'update ' . $data['table_name'] . '
    set ' . $data['field_name'] . '=?
    where ' . $data['id_column_name'] . '=?
    ';
    // return $sql."-".$data['value']."-".$data['id'];
    $stmt = $con->prepare($sql) or die("Couldn't prepare update_generic_table_s-" . $sql . '-' . $data['field_name'] . '-' . $data['id'] . '-' . $data['value'] . '-' . $data['id_column_name']);
    $stmt -> bind_param('ss', $data['value'], $data['id']) or die("Couldn't bind update_generic_table_s .");
    if ($stmt->execute()) {
        $return['status'] = 'success';
    } else {
        $return['status'] = 'fail';
        $return['error_message'] = mysqli_error($con);
    }
    return $return;
}

function update_generic_table_i($data)
{
    $con = db_connect(null) or die("Couldn't connect to db. - " . __FUNCTION__);
    $sql = 'update ' . $data['table_name'] . '
    set ' . $data['field_name'] . '=?
    where ' . $data['id_column_name'] . '=?
    ';
    // return $sql."-".$data['value']."-".$data['id'];
    // exit;
    $stmt = $con->prepare($sql) or die("Couldn't prepare update_generic_table_i sql:" . $sql . '-' . $data['field_name'] . '-' . $data['id'] . '-' . $data['value'] . '-' . $data['id_column_name'] . '-' . $data['db_name']);
    $stmt -> bind_param('ii', $data['value'], $data['id']) or die("Couldn't bind update_generic_table_i. value:" . $data['value'] . ' id:' . $data['id']);
    if ($stmt->execute()) {
        $return['status'] = 'success';
    } else {
        $return['error_message'] = mysqli_error($con);
    }
    return $return;
}

function update_generic_table_is($data)
{
    //for value of i but id is s
    $con = db_connect(null) or die("Couldn't connect to db. - " . __FUNCTION__);
    $sql = 'update ' . $data['table_name'] . '
    set ' . $data['field_name'] . '=?
    where ' . $data['id_column_name'] . '=?
    ';
    // return $sql."-".$data['value']."-".$data['id'];
    $stmt = $con->prepare($sql) or die("Couldn't prepare update_generic_table_s-" . $sql . '-' . $data['field_name'] . '-' . $data['id'] . '-' . $data['value'] . '-' . $data['id_column_name']);
    $stmt -> bind_param('is', $data['value'], $data['id']) or die("Couldn't bind update_generic_table_s .");
    if ($stmt->execute()) {
        $return['status'] = 'success';
    } else {
        $return['status'] = 'fail';
        $return['error_message'] = mysqli_error($con);
    }
    return $return;
}

function add_row_generic_table_s($data)
{
    //checking if this field is valid is done in spede process
    $con = db_connect(null) or die("Couldn't connect to db. - " . __FUNCTION__);
    $sql = 'insert into ' . $data['table_name'] . ' (' . $data['field_name'] . ')
        values (?)
    ';
    $stmt = $con->prepare($sql) or die("Couldn't prepare add_row_generic_table_s ." . $sql . '-' . $data['value']);
    $stmt -> bind_param('s', $data['value']) or die("Couldn't bind add_row_generic_table_s .");
    if ($data['ignore_last_inserted_id']) {
        if ($stmt->execute()) {
            return true;
        } else {
            // return mysqli_error($con);
            return false;
        }
    } else {
        // return $sql."-".$data['value'];
        $stmt->execute() or die("Couldn't execute add_row_generic_table_s " . $sql . '-' . $data['value']);
        $last_inserted_id = $con->insert_id or die("Couldn't get last_inserted_id add_row_generic_table_s ignore_last_inserted_id=" . $data['ignore_last_inserted_id']);
        return $last_inserted_id;
    }
}

function delete_generic_table_row($data)
{
    // echo "<pre>";print_r($data);echo "</pre>";
    if (isset($data['where']) and $data['where']) {
        $where = $data['where'];
    } else {
        $where = '';
    }
    $con = db_connect(null) or die("Couldn't connect to db. - " . __FUNCTION__);
    $sql = 'delete
			from ' . $data['table_name'] . '
			where ' . $data['id_column_name'] . '=?
			' . $where . '
		';
    $return['sql'] = $sql . $data['id'];
    $stmt = $con->prepare($sql) or die("Couldn't prepare delete_generic_table_row " . $sql . '-' . $data['id']);
    $stmt -> bind_param('s', $data['id']) or die("Couldn't bind delete_generic_table_row .");
    if ($stmt->execute()) {
        $return['status'] = 'success';
    } else {
        $return['error_message'] = mysqli_error($con);
    }
    return $return;
}

function load_settings_into_session()
{

    //this allows them to used without making a function call to sql each time.
    $settings = get_setting_data(null);
    foreach ($settings as $setting) {
        $_SESSION['sog_setting_' . $setting['id']] = get_setting($setting['id']);
    }
}

function is_setting($setting_id)
{
    $con = db_connect(null) or die("Couldn't connect to db. - " . __FUNCTION__);
    $sql = 'select value
			from sog_settings
			where sog_settings.id=?
			and sog_settings.value=1
			and sog_settings.is_bool=1
			limit 1
		';
    // echo $sql;
    $stmt = $con->prepare($sql) or die("Couldn't prepare is_setting .");
    $stmt -> bind_param('i', $setting_id) or die("Couldn't bind is_setting .");
    $stmt->execute() or die("Couldn't execute is_setting .");
    $sql_result = $stmt->get_result();
    $num_rows = mysqli_num_rows($sql_result);
    $cols = [];
    while ($row = $sql_result->fetch_array(MYSQLI_BOTH)) {
        if ($num_rows) {
            return true;
        } else {
            return false;
        }
    }
}

function get_setting($setting_id)
{
    $value = debug_backtrace()[1]['function'];
    $con = db_connect(null) or die("Couldn't connect to db. - " . __FUNCTION__);
    $sql = 'select value
			from sog_settings
			where sog_settings.id=?
			limit 1
		';
    $stmt = $con->prepare($sql) or die("Couldn't prepare get_setting . " . $sql . '-' . $setting_id . '-fx=' . $value);
    $stmt -> bind_param('i', $setting_id) or die("Couldn't bind get_setting .");
    $stmt->execute() or die("Couldn't execute get_setting .");
    $sql_result = $stmt->get_result();
    $num_rows = mysqli_num_rows($sql_result);
    $cols = [];
    while ($row = $sql_result->fetch_array(MYSQLI_BOTH)) {
        return $row['value'];
    }
}

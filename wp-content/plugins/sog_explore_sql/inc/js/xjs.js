(function($) {

var report_array_of_fields=[];
var report_array_of_fields_order=[];
var report_array_of_filters=[];
var report_array_of_groups=[];
var report_array_of_edits=[];
var last_datetime_checked="";
var stale_check_interval;
var stale_check_interval_ms;
var check_for_stale_active=false;

var this_is_initial_page_load=1;

$(document).ready(function() {

	//get ms from options
	var get_stale_check_interval_ms=$(".report_builder").data("stale_check_option_ms");

	//check if valid integer
	if (parseInt(get_stale_check_interval_ms)==0){
		check_for_stale_active=false;
		stale_check_interval_ms=null;
	}else if (parseInt(get_stale_check_interval_ms)>=5000){
		stale_check_interval_ms=parseInt(get_stale_check_interval_ms);
		check_for_stale_active=true;
	}else{
		stale_check_interval_ms=5000;
		check_for_stale_active=true;
	}

	if (getParameterByName("debug",null)){
		console.log("stale interval: " +stale_check_interval_ms);
	}

	var t=$("#fields_chosen_order_from_save").data("fields");
	// console.log(t);
	// console.log(t.split(","));
	

	//this will call the report which will check if this button was pushed, and if so clear limit
	$(this).on("click", '.report_get_all_records',process_report_request);

	//when choosing fields this will modify global variable of checked fields then call the report
	//changing this to just checking box then when calling report it will loop through each and determine if checked
	$(this).on("click", '.report_checkbox',function(){
		var field_name=$(this).val();

		//just check, dont do anything else.  the process_report_request will find all that are checked.
		if (!$(this).is(":checked")){
			//input is not checked so remove edit if exists
			$(this).closest(".sog_ex_field_row").find(".edit_this_field")
			.removeClass("text-primary fas edit_active").addClass("text-secondary far");

			//remove from ordering array
			index = report_array_of_fields_order.indexOf($(this).val());
			report_array_of_fields_order.splice(index, 1);
		}else{
			//add to global array just for ordering purposes.
			report_array_of_fields_order.push(field_name);
		}
		//now run the report
		process_report_request();
	});

	//changing this to toggle a class grouped_active, then when calling report it will loop through each edit_active and add to global array
	$(this).on("click", '.add_explore_group',function(){
		var elem=$(this);
		var field_name=elem.data("field_name");
		if (elem.hasClass("grouped_active")) {
			elem.addClass("text-secondary far").removeClass("text-primary fas grouped_active");
			index = report_array_of_groups.indexOf(field_name);
			report_array_of_groups.splice(index, 1);
		}else{
			// report_array_of_groups.push(field_name);
			elem.addClass("text-primary fas grouped_active").removeClass("text-secondary far");
			// console.log("adding "+field_name);
			// console.log(report_array_of_groups);
			}

			//now run the report
		process_report_request();
	});

	//if click on edit this field, add to global variable and process report request
	//changing this to toggle a class edit_active, then when calling report it will loop through each edit_active and add to global array
	$(this).on("click", '.edit_this_field',function(){
		//process_report_edit_field
		$(this).toggleClass("edit_active text-secondary far text-primary fas");

		//display edit warning if this is active
		if ($(this).hasClass("edit_active")){
			display_edit_warning();
		}

		//now run the report
		process_report_request();
	});

	//process check All
	$(this).on("click", '.report_check_all',function(){
		//clear global array to avoide duplicates 
		report_array_of_fields=[];

		//clear ordering array since they are checking all.
		report_array_of_fields_order=[];

		//now check each box
		$(".report_builder .report_checkbox").prop("checked", true);

		//then process the report
		process_report_request();

	});

	//process check None
	$(this).on("click", '.report_check_none',function(){
		//clear global arrays
		report_array_of_fields=[];
		report_array_of_fields_order=[];

		//find all checkboxes and uncheck them
		$(".report_builder .report_checkbox").prop("checked", false);

		//also need to remove the edit_active, since that can't be on if the box isnt checked
		$(".edit_active").removeClass("text-primary fas edit_active").addClass("text-secondary far");

		//then process the report
		process_report_request();
	});

	//process edit All
	$(this).on("click", '.report_edit_all',function(){

		// go through each edit icon and add/remove appropriate classes
		$(".edit_this_field").each(function(){
			$(this).addClass("text-primary fas edit_active").removeClass("text-secondary far");
		});

		//since the primary keys do not have the edit_this_field, they also need to be checked
		//this will check all boxes, no need to try and single out which ones to check
		$(".report_builder .report_checkbox").prop("checked", true);

		//display edit warning - it knows if the cookie is present
		display_edit_warning();

		//then process the report
		process_report_request();

	});

	//process edit only those selected
	$(this).on("click", '.report_edit_all_selected',function(){

		// go through each edit icon and add/remove appropriate classes
		$(".edit_this_field").each(function(){
			if ($(this).closest(".sog_ex_field_row").find(".report_checkbox").is(":checked")){
				$(this).addClass("text-primary fas edit_active").removeClass("text-secondary far");
			}
		});

		//display edit warning - it knows if the cookie is present
		display_edit_warning();

		//then process the report
		process_report_request();

	});

	//process edit None
	$(this).on("click", '.report_edit_none',turn_off_edits);

	$(this).on("click", '.remove_grouping_field',function(){
		//remove grouping from global array
		var field_name=$(this).data("field_name");
		var elem=$(".add_explore_group[data-field_name='"+field_name+"']");
		elem.addClass("text-secondary far").removeClass("text-primary fas grouped_active");
		index = report_array_of_groups.indexOf(field_name);
		report_array_of_groups.splice(index, 1);

		//remove the span with group name
		$(this).remove();

		//get rid of leading and trailing commas
		var temp_grouping_html=$(".grouping_fields").html();
		var temp_grouping_html = temp_grouping_html.replace(/(^,)|(,$)/g, "");
		temp_grouping_html=temp_grouping_html.replace(", ,",", ");
		$(".grouping_fields").html(temp_grouping_html);

		//then runs the report 
		process_report_request();
	});

	$(this).on("change", '.explore_filter',function(){
		//if selecting something, then remove text from input field
		$(this).closest(".report_filters").find(".explore_filter_like").val("");

		//when changing filters this first updates the global variable for filters
		process_report_filter();

		//then runs the report 
		process_report_request();
	});

	$(this).on("keyup", '.explore_filter_like',function(){
		//if typing something in, remove from dropdown too.
		$(this).closest(".report_filters").find(".explore_filter").val("");

		//when changing filters this first updates the global variable for filters
		process_report_filter();

		//then runs the report 
		process_report_request();
	});

	//when changing the limit it will call the report, the report fx will read the global variables for filters and fields
	$(this).on("change", '.report_limit',function(){
		if (report_array_of_fields.length) {
			process_report_request();
		}
	});

	//if query variable for table exists, set the drop down and load that table
	var table_name=getParameterByName("table_name",null);
	if (table_name){
		//update select and trigger change		
		$(".select_report_table").val(table_name);
		select_report_table($(".select_report_table"));
	}

	//if change table dropdown load that table
	$(this).on("change", '.select_report_table',function(){
		if ($(this).val()) {
			select_report_table($(this));
		}else{
			
		}
	});

	$(this).on("click", '.reload_manage_table', function(){
		$(".load_manage_dataset").change();
	});

	$(this).on("change",".sog_ex_generic_update",function(){
		sog_ex_generic_update($(this));
	});

	$(this).on("change",".sog_ex_report_generic_update",function(){
		sog_ex_report_generic_update($(this));
	});

	$(this).on("click", '.report_data_table th', save_sort_column);
	$(this).on("click", '.refresh_table_button', process_report_request);
	$(this).on("click", '.sog_ex_menu_button', generic_load_menu_section);
	$(this).on("keyup", '.filter_this_generic_input',filter_this_generic_input);
	$(this).on("click", '.select_all_none_choose_tables', select_all_none_choose_tables);
	$(this).on("change", '.load_manage_dataset', load_manage_dataset);
	$(this).on("keyup", '.sog_explore_filter_this_generic_span',sog_explore_filter_this_generic_span);
	$(this).on("click", '.add_explore_filter',add_explore_filter);
	$(this).on("click", '.save_explore_roles',save_explore_roles);
	$(this).on("click", '.save_stale_check_option',save_stale_check_option);
	$(this).on("click", '#auto_save',process_report_request);
	$(this).on("click", '.reset_table_settings',reset_table_settings);
	$(this).on("click", '.sog_ex_log_restore_value_button',function(){
		//get old value to show to user
		var log_id=$(this).closest("tr").data("primary_key_value");
		if (log_id){
			//only proceed if the ID column is chosen
			var old_value=$(this).closest("tr").find("td[data-field_name=old_value]").text().trim();
			if (confirm("This will restore this field to "+old_value+".")) {
				sog_ex_restore_value($(this));
			}	
		}else{
			var bs_alert_message="<div class='alert alert-danger restore_key_alert'>"+
				"In order to restore a value you must have the primary key field visible."+
			"</div>";
			$(".report_table_here").prepend(bs_alert_message);

		}

	});
	
   $(this).on("click", '.sog_ex_help_info_sign',function(){
	   $(this).siblings(".help-block").slideToggle("fast",function() {
			if ($(this).is(':visible')) {
				$(this).css('display','block');
			}
	   });
   });

});

///////////// ************ Functions **********************/////////////////

function turn_off_edits(){

	// go through each edit icon and add appropriate classes, and remove appropriate classes
	$(".edit_this_field").each(function(){
		$(this).removeClass("text-primary fas edit_active").addClass("text-secondary far");
	});
	//also remove edit_active that may have been there from before
	$(".edit_active").removeClass("text-primary fas edit_active").addClass("text-secondary far");

	//then process the report
	process_report_request();
}

function display_edit_warning(options){
	//there is a cookie called sog_ex_edit_warning_seen which will exist if they have already seen this, no need to show again.
	var sog_ex_edit_warning_seen_cookie=getCookie("sog_ex_edit_warning_seen");

	//set the message
	var message="WARNING: Be very careful when making edits directly to the database.";

	//only do this if the cookie doent already exist
	if (!sog_ex_edit_warning_seen_cookie){
		var html_message="<div id='sog_ex_edit_warning' class='sog_ex_edit_warning' style='display:none'>"+message+"</div>";
		
		//remove older ones that are still lingering
		$(".sog_ex_edit_warning").remove();

		$(".report_builder").prepend(html_message);

		$( "#sog_ex_edit_warning" ).dialog({
			title: "Warning",
			resizable: false,
			height: "auto",
			width: 400,
			modal: true,
			open: function() {
				//removes the overlay background
				// $('.ui-widget-overlay').remove();

				//change or hide the clsoe button
				$(".ui-dialog-titlebar-close").remove();

				//change button style
				$(".ui-dialog-buttonpane").find('button').eq(0).addClass('bg-success text-white rounded');
				$(".ui-dialog-buttonpane").find('button').eq(1).addClass('bg-secondary text-white rounded');

				//change title style
				$(".ui-dialog-titlebar").addClass('text-danger');

			}, 
			buttons: {
			"I understand": function() {
				//allow them to continue
				$( this ).dialog( "close" );

				//set cookie so they don't see this for a while
				setCookie("sog_ex_edit_warning_seen",1,30);
			},
			"Cancel": function() {
				$( this ).dialog( "close" );
				//remove edit options.
				turn_off_edits();

				//remove the cookie if exists because it needs to display again for this user
				eraseCookie("sog_ex_edit_warning_seen");
			}
			}
		});
	}

}

function select_report_table(elem){
	var data_to_send = [];
	var table_name=elem.val();
	data_to_send.push(table_name);
	if (getParameterByName("debug",null)){
		console.log(data_to_send);
	}
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {select_report_table:data_to_send},
		beforeSend: function(){
			sog_explore_fixed_spinner_arrows_html();
		},
		success: function(output) {
				if (getParameterByName("debug",null)){
					console.log(output);
				}
				
				if(output.status == "success"){
				 $(".report_fields_here").html(output.html);
				 //clear global arrays for checkboxes and filters.  If there are saved options, they will load from from server
				 report_array_of_fields=[];
				 report_array_of_filters=[];
				 report_array_of_groups=[];
				 report_array_of_edits=[];
 				 $(".report_table_here").html("");
 				 $(".hide_until_limit_ready").hide();

				 //update query parameter with table name
				 var current_url = window.location.href;
				 var new_url=updateQueryStringParameter(current_url, "table_name",elem.val());
				 window.history.pushState({}, document.title, new_url);
		 
				 //attempt to run report
				 process_report_request();

			 }else{
				if (output.alert_message) {
					alert(output.alert_message);
				}
				// console.log("status is NOT success");
				// // console.log(output);
			 }
		},
		error: function () {
			// console.log("error in ajax call");
		},
		complete: function(){
			$(".sog_explore_fixed_spinner_arrows").remove();

			//only show warning if there are fields marked to be edited
			if ($(".edit_active").length){
				display_edit_warning();
			}
		}
	});
}

function add_explore_filter(){
	// console.log(report_array_of_filters);

	//need to get values of existing filter selected and add back when this is done.
	//this is because the dropdowns come from the server but they do not have the selected values
	//if the values get stored in sql, then perhaps they will come back with the values
	var all_filter_values=[];
	$(".explore_filter").each(function(){
		var filter_values=[];
		var filter_id=$(this).attr("id");
		var value=$(this).val();
		filter_values.push(filter_id);
		filter_values.push(value);
		all_filter_values.push(filter_values);
	});

	//get values of filter input "like" fields
	var all_filter_like_values=[];
	$(".explore_filter_like").each(function(){
		var filter_like_values=[];
		var filter_like_id=$(this).attr("id");
		var value=$(this).val();
		filter_like_values.push(filter_like_id);
		filter_like_values.push(value);
		all_filter_like_values.push(filter_like_values);
	});

	var elem=$(this);

	//load spinner 
	sog_explore_fixed_spinner_arrows_html();

	if (elem.hasClass("filter_is_active") || elem.hasClass("remove_filter_via_button")) {
		//if is already selected them remove from list
		var do_what="remove";
	}else{
		var do_what="add";
	}

	var data_to_send = [];
	var table_name=elem.closest(".table_info").data("table_name");
	var field_name=elem.data("field_name");
	var data_type=elem.data("data_type");
	data_to_send.push(table_name);
	data_to_send.push(field_name);
	data_to_send.push(do_what);
	data_to_send.push(data_type);
	if (getParameterByName("debug",null)){
		console.log(data_to_send);
	}
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {add_explore_filter:data_to_send},
		beforeSend: function(){
		},
		success: function(output) {
			if (getParameterByName("debug",null)){
				console.log(output);
			}
			if(output.status == "success"){
				 $(".explore_filters_here").html(output.filters_html);
				if (do_what=="add") {
					//adding a filter option
					elem.removeClass("text-secondary far").addClass("text-primary fas filter_is_active");
				}else{
					//removing a filter option

					//this adjusts classes on filter icon
					elem.removeClass("text-primary fas filter_is_active").addClass("text-secondary far");
					
					//if came from button then also need to find filter icon and adjust it's class
					$(".add_explore_filter[data-field_name='"+field_name+"']").removeClass("text-primary fas filter_is_active").addClass("text-secondary far");
				}
			 }else{
				if (output.alert_message) {
					alert(output.alert_message);
				}
				// console.log("status is NOT success");
				// // console.log(output);
			 }
		},
		error: function () {
			// console.log("error in ajax call");
		},
		complete: function(){
			$(".sog_explore_fixed_spinner_arrows").remove();
			
			//set the vales of the filters
			for (var x=0;x<all_filter_values.length;x++) {
				$("#"+all_filter_values[x][0]).val(all_filter_values[x][1]);
			}

			//set the vales of the filter like inputs
			for (var x=0;x<all_filter_like_values.length;x++) {
				$("#"+all_filter_like_values[x][0]).val(all_filter_like_values[x][1]);
			}
			if (do_what=="remove") {
				//since they are removing a filter should re run report after this removed the dropdown from the page
				process_report_request();
			}
			
		}
	});
}

function process_report_filter(){
	//empty array 
	report_array_of_filters=[];

	//get all filter dropdown choices on page
	$(".explore_filter").each(function(){
		var elem=$(this);
		var filter_string="";
		var filter_field_name=elem.data("filter_field_name");
		var si=elem.data("si");
		var filter_field_value=elem.find(":selected").val();
		if (filter_field_value) {
			if (si=="s") {
				filter_string="and "+filter_field_name+"='"+filter_field_value+"'";
			}else{
				filter_string="and "+filter_field_name+"="+filter_field_value;
			}
			//add values to global array
			report_array_of_filters.push(filter_string);
		}
	});

	//get all filter input "likes" on page
	$(".explore_filter_like").each(function(){
		var elem=$(this);
		var filter_string="";
		var filter_field_name=elem.data("filter_field_name");
		var si=elem.data("si");
		var filter_field_value=elem.val();
		if (filter_field_value) {
			//for like requests using % they must be wrapped in quotes regardless if type is int or string
			if (si=="s") {
				filter_string="and "+filter_field_name+" like '%"+filter_field_value+"%'";
			}else{
				filter_string="and "+filter_field_name+" like '%"+filter_field_value+"%'";
			}

			//add values to global array
			report_array_of_filters.push(filter_string);
		}
	});
	// console.log(report_array_of_filters);
}


function process_report_checkbox(){
	//changing this to loop through all checkboxes and if checked add to global arrray 
	//this adds each field to the array in the order they are displayed on the page.

	//clear out global array first
	report_array_of_fields=[];
	$(".report_builder .report_checkbox").each(function(){
		var elem=$(this);
		if ($(this).is(":checked")) {
			report_array_of_fields.push(elem.val());
		}

	});

	//to reorder them into the order they were clicked, match order to the global array report_array_of_fields_order
	report_array_of_fields.sort((a, b) => report_array_of_fields_order.indexOf(a) - report_array_of_fields_order.indexOf(b));
	
}

function process_report_edit_field(){
	//clear out global array first
	report_array_of_edits=[];

	//loop through all edit icons and look for edit_active and add that field name to global array
	$(".report_builder .edit_active").each(function(){
		var field_name=$(this).closest(".sog_ex_field_row").data("field_name");
		report_array_of_edits.push(field_name);

		//then ensure checkbox is checked and add to global array
		var checkbox=$(this).closest(".sog_ex_field_row").find(".report_checkbox");
		if (!checkbox.is(":checked")) {
			checkbox.prop("checked", true);
			report_array_of_fields.push(field_name);
		}

	});

	
}

function process_report_group(){
	//clear out global array first
	report_array_of_groups=[];

	$(".grouped_active").each(function(){
		var elem=$(this);
		var field_name=elem.data("field_name");
		report_array_of_groups.push(field_name);
	});

	//build grouping display
	if (report_array_of_groups.length>0){
		var grouping_html="<span class='fw-bold'>Grouped by: </span><span class='grouping_fields'>";

		//loop through and output groupings
		var temp_grouping_html="";
		for (var x=0;x<report_array_of_groups.length;x++) {
			temp_grouping_html+="<span title='Remove Grouping' class='grouping_field remove_grouping_field' data-field_name='"+report_array_of_groups[x]+"'>"
				+report_array_of_groups[x]
			+"</span>, ";
		}

		//remove trailing comma
		temp_grouping_html = temp_grouping_html.replace(/,\s*$/, ""); 
		
		//output html for groupings
		$(".explore_grouping_here").html(grouping_html+temp_grouping_html + "</span>"); //closing span to grouping_fields

		//since there is a grouping, hide edit icons
		$(".edit_this_field").css("opacity", ".1");

	}else{
		$(".explore_grouping_here").html("");

		//since there is not a grouping, show edit icons
		$(".edit_this_field").css("opacity", "");
	}
	
	// console.log(report_array_of_groups);
}

function process_report_request(){
	var data_to_send = [];
	
	//load spinner  - not doing since added refresh table
	// sog_explore_fixed_spinner_arrows_html();

	//this gets from dropdown, which has been hidden for now
	var table_name=$(".select_report_table").val();


	//check for all checked boxes, if so add them to the global array
	process_report_checkbox();

	//check if there are any filters, if so add them to the global array
	process_report_filter();

	//check if there are any edit fileds active if so add them to the global array
	process_report_edit_field();

	//check if there are any grouped fileds active if so add them to the global array
	process_report_group();

	//remove group by message before checking if needed
	$(".groub_by_with_edit_message").remove();

	//if there are any group bys then there can NOT be any edit fields
	if (report_array_of_groups.length>0){
		//using different variable so not to empty global
		var x_report_array_of_edits=[];

		//check to see if there are also edits
		if (report_array_of_edits.length>0){
			//display group by edit message if needed
			if (report_array_of_edits.length>0){
				$(".explore_grouping_here").append("<div class='text-danger groub_by_with_edit_message'>Fields cannot be edited if a group by is chosen.</div>");
			}
		}
		

	}else{
		//if there are no groupings then edits can be what they normally would be
		var x_report_array_of_edits=report_array_of_edits;
	}

	//if there are any edits then all primary keys must be chosen
	if (!$("#auto_check_primary_keys").is(":checked")) {
		//this is long way that provides warning
		if (x_report_array_of_edits.length>0){
			var all_keys=[];
			var keys_checked=[];
			var keys_not_checked=[];
			$(".primary_key_icon").each(function(){

				//check if checked
				if ($(this).closest(".sog_ex_field_row").find(".report_checkbox").is(":checked")){
					//add field name to array
					keys_checked.push($(this).closest(".sog_ex_field_row").data("field_name"));

					//remove css invalid
					$(this).closest(".sog_ex_field_row").find(".report_checkbox").removeClass("is-invalid");
				}else{
					//add field name to array
					keys_not_checked.push($(this).closest(".sog_ex_field_row").data("field_name"));

					//add css invalid to checkbox
					$(this).closest(".sog_ex_field_row").find(".report_checkbox").addClass("is-invalid");
				}
		
				//gather up all primary keys into array
				all_keys.push($(this).closest(".sog_ex_field_row").find(".report_checkbox").val());
		
			});
		
			//now that keys are accounted for, give error messages
			if (keys_not_checked.length>0){
				//loop through unchecekd and create warning message
				var key_missing_message="";
				for (var x=0;x<keys_not_checked.length;x++) {
					key_missing_message+="<div class='text-danger key_missing_message'><span class='fw-bold'>"+keys_not_checked[x]+"</span> must be checked in order to edit a field.</div>";
				}

				//output the error message
				$(".report_messages_for_edits").html(key_missing_message);

				//using 2nd variable so not to empty global so will still work when grouping goes to 0
				var x_report_array_of_edits=[];

			}else{
				//let edits array be what is asked for
				var x_report_array_of_edits=report_array_of_edits;
				$(".report_messages_for_edits").html("");

				//loop thorugh all primary keys to move to beginning of checkboxes array
				for (var y=0;y<all_keys.length;y++) {
					//get position this key is
					index = report_array_of_fields.indexOf(all_keys[y]);

					//cut it out
					report_array_of_fields.splice(index, 1);

					//put it back in the beginning
					report_array_of_fields.unshift(all_keys[y]);
				}
			}
		}else{
			//there are no edits
			//remove edit warning messages
			$(".report_messages_for_edits").html("");

			//remove invalid from inputs
			$(".report_checkbox.is-invalid").removeClass("is-invalid");

		}
	}else{
		//shorter way, probably determined by a setting
		var all_keys=[];

		//if there are any edit fields chosen
		if (x_report_array_of_edits.length>0){
			//find each primary
			$(".is_primary_key").each(function(){
				//check the box and remove invalid class if exists
				$(this).prop("checked",true).removeClass("is-invalid");

				//remove warning messages
				$(".key_missing_message").remove();
				//gather up all primary keys into array so i can re sort them in the fields array to put them in beginning
				all_keys.push($(this).val());

			});

			//re-run process_report_checkbox to add this to the global array
			process_report_checkbox();

			//only move primary key if box is checked
			if ($("#auto_move_primary_keys").is(":checked")) {

					//loop thorugh all primary keys to move to beginning of checkboxes array
				for (var y=0;y<all_keys.length;y++) {
					//get position this key is
					index = report_array_of_fields.indexOf(all_keys[y]);

					//cut it out
					report_array_of_fields.splice(index, 1);

					//put it back in the beginning
					report_array_of_fields.unshift(all_keys[y]);
				}
			}
			
		}

	}

	var report_limit=$(".report_limit").val();
	
	//this check to see if get all was clicked
	if ($(this).hasClass("report_get_all_records")){
		var report_get_all_records=true;
		var original_text=$(this).html();
		$(this).html("Thinking...");
		report_limit="All";
	}

	//check to make sure there no duplicates in any of the global arrays
	report_array_of_fields = [...new Set(report_array_of_fields)];
	report_array_of_filters = [...new Set(report_array_of_filters)];
	report_array_of_groups = [...new Set(report_array_of_groups)];
	x_report_array_of_edits = [...new Set(x_report_array_of_edits)];

	//if there are no filters being used, add an empty string to the global array.  then store in separate just for sending to not mess up actual global arrays which get checked for other purposes
	var x_report_array_of_filters=[];
	var x_report_array_of_groups=[];
	if (!report_array_of_filters.length) {x_report_array_of_filters.push("");}else{x_report_array_of_filters=report_array_of_filters;}
	if (!report_array_of_groups.length) {x_report_array_of_groups.push("");}else{x_report_array_of_groups=report_array_of_groups;}
	if (!x_report_array_of_edits.length) {x_report_array_of_edits.push("");}else{x_report_array_of_edits=x_report_array_of_edits;}


	//check if auto save is selected
	if ($("#auto_save").is(":checked")) {
		var auto_save=1;
	}else{
		var auto_save=0;
	}

	data_to_send.push(table_name); 
	data_to_send.push(report_array_of_fields); 	//gets from global variable
	data_to_send.push(x_report_array_of_filters);	//gets from global variable
	data_to_send.push(report_limit);
	data_to_send.push(x_report_array_of_groups);	//gets from global variable
	data_to_send.push(x_report_array_of_edits);	
	data_to_send.push(auto_save);	
	data_to_send.push(this_is_initial_page_load);	
	if (getParameterByName("debug",null)){
		console.log(data_to_send);
	}
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {process_report_request:data_to_send},
		beforeSend: function(){
			//hide refresh table button, will turn on later if table is returned
			$(".refresh_table").show();
			$(".refresh_table_icon").addClass("fa-spin");

		},
		success: function(output) {
			if (getParameterByName("debug",null)){
				console.log(output);
			}
			if(output.status == "success"){
				 //output new filters if needed
				 $(".explore_filters_here").html(output.filters_html);
				 
				 $(".report_table_here").html(output.html);
					load_report_datatable();
				 $(".report_sample_number").text(output.limit_to_use);
				 $(".report_total_number").text(output.table_data_no_limit_count);

				 if (report_limit=="All") {
					//If limit is set to all, then hide subset and show showing all message
					$(".report_showing_subset").hide();
					$(".report_showing_all").show();
					$(".report_dt_button").show();
				 }else if (output.table_data_count>0 && output.table_data_count==output.table_data_no_limit_count) {
					//regardless of limit button, if the number of records is same as count of all, then showing all
					$(".report_showing_subset").hide();
					$(".report_showing_all").show();
					$(".report_dt_button").show();
				 }else if (output.table_data_count>0 && output.table_data_count<output.table_data_no_limit_count){
					//if there are results and they are less than the total without limit, this must be a subset
					$(".report_showing_subset").show();
					$(".report_showing_all").hide();
					$(".report_dt_button").hide();
				 }else{
					$(".report_showing_subset").hide();
					$(".report_showing_all").show();
					$(".report_dt_button").hide();
				 }

				 //subset and all are set, but if there are no checked boxes then don't show anything about limit
				 if (report_array_of_fields.length && output.table_data_count>0){
				 	$(".hide_until_limit_ready").show();
				 }

				//if this is the initial page load, the order of fields was set from sql
				//all future reqests will run normally
				if (this_is_initial_page_load){
				
					//need to replace report_array_of_fields_order with the order from sql
					//if there are field names, then store in report_array_of_fields_order otherwise it needs to be an empty array so it will work later.
					report_array_of_fields_order=output.field_names || [];

					//turn this_is_initial_page_load off as the first report has been run, all future reports can ignore this flag
					this_is_initial_page_load=0;
				}

				//show refresh table button
				$(".refresh_table_button").show()
				$(".refresh_table_icon").removeClass("fa-spin");

				//output select query
				if (output.sql_for_display && $("#show_sql").is(":checked")) {
					$(".output_select_query").show();
					$(".output_select_query .sql_output_here").html("<pre>"+output.sql_for_display+"</pre>");
				}else{
					$(".output_select_query").hide();
				}

				//hide update query since just performed a select query
				$(".output_update_query").hide();
				
				//update the global array that holds when the last time this got data from the db 
				last_datetime_checked=output.last_datetime_checked;

				//remove message if exists
				$(".stale_check_message").remove();

				//set timer to check for stale data
				clearInterval(stale_check_interval);
				stale_check_interval = setInterval(check_if_stale_data, stale_check_interval_ms);
		}else{
				if (output.alert_message) {
					alert(output.alert_message);
				}
				// console.log("status is NOT success");
				// // console.log(output);
			 }
		},
		error: function () {
			console.log("error in ajax call");

			var reset_message="<div class='alert alert-danger reset_message'>"+
				"Uh oh, something went wrong getting that data. "+
				"Click <span class='reset_table_settings clickable' data-table_name='"+table_name+"'><u>here</u></span>"+
				" to attempt a recovery."+
				"</div>";
			$(".report_table_here").prepend(reset_message);

		},
		complete: function(){

			$(".sog_explore_fixed_spinner_arrows").remove();

			if (report_get_all_records) {
				$(".report_get_all_records").html(original_text);
			}



		}
	});
}

function save_explore_roles(){
	var report_array_of_roles=[];
	$(".choose_explore_role").each(function(){
		if ($(this).is(":checked")) {
			report_array_of_roles.push($(this).val());
		}
	})

	var data_to_send = [];
	data_to_send.push(report_array_of_roles); 
	if (getParameterByName("debug",null)){
		console.log(data_to_send);
	}
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {save_explore_roles:data_to_send},
		beforeSend: function(){
		},
		success: function(output) {
			if (getParameterByName("debug",null)){
				console.log(output);
			}
			 if(output.status == "success"){
				save_flash($(".save_explore_roles"));
			 }else{
				if (output.alert_message) {
					alert(output.alert_message);
				}
			 }
		},
	});
}

function reset_table_settings(){
	var table_name=$(this).data("table_name");
	var data_to_send = [];
	data_to_send.push(table_name); 
	if (getParameterByName("debug",null)){
		console.log(data_to_send);
	}
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {reset_table_settings:data_to_send},
		beforeSend: function(){
			$(".reset_message").remove();
		},
		success: function(output) {
			if (getParameterByName("debug",null)){
				console.log(output);
			}
			 if(output.status == "success"){
				process_report_request();
			}else{
				if (output.alert_message) {
					alert(output.alert_message);
				}
			 }
		},
	});
}


function save_stale_check_option(){
	if ($("#stale_check_option").is(":checked")) {
		var value=1;
	}else{
		var value=0;
	}
	var data_to_send = [];
	data_to_send.push(value); 
	data_to_send.push($("#stale_check_option_seconds").val()); 
	data_to_send.push($("#subset_limit").val()); 
	if (getParameterByName("debug",null)){
		console.log(data_to_send);
	}
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {save_stale_check_option:data_to_send},
		beforeSend: function(){
		},
		success: function(output) {
			if (getParameterByName("debug",null)){
				console.log(output);
			}
			 if(output.status == "success"){
				//set seconds what it's stored at, could be different than what user submitted.
				$("#stale_check_option_seconds").val(output.return_seconds);
				$("#subset_limit").val(output.return_subset_limit);
				save_flash($(".save_stale_check_option"));
			 }else{
				if (output.alert_message) {
					alert(output.alert_message);
				}
			 }
		},
	});
}


function save_sort_column(){
	var elem=$(this);
	console.log(elem);
	var field_name=elem.data("field_name");
	var asc_desc=elem.attr("aria-sort");
	var table_name=elem.closest("table").data("table_name");
	var data_to_send = [];
	data_to_send.push(table_name); 
	data_to_send.push(field_name); 
	data_to_send.push(asc_desc); 
	if (getParameterByName("debug",null)){
		console.log(data_to_send);
	}
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {save_sort_column:data_to_send},
		beforeSend: function(){
		},
		success: function(output) {
			if (getParameterByName("debug",null)){
				console.log(output);
			}
			 if(output.status == "success"){
				
			 }else{
				if (output.alert_message) {
					alert(output.alert_message);
				}
			 }
		},
	});
}

function check_if_stale_data(){
	//only do this if tab is focused and is turned 
	if (document.hasFocus() && check_for_stale_active){
		var table_name=getParameterByName("table_name",null);

		var data_to_send = [];
		data_to_send.push(last_datetime_checked); 
		data_to_send.push(table_name); 
		if (getParameterByName("debug",null)){
			console.log(data_to_send);
		}
			$.ajax({ url: plugin_dir()+'/process.php',
			type: 'POST',
			data    : {check_if_stale_data:data_to_send},
			beforeSend: function(){
			},
			success: function(output) {
				if (getParameterByName("debug",null)){
					console.log(output);
				}
				if(output.status == "success"){
					if (output.check_if_stale_data.is_stale){
						//you have stale data
						console.log("you have stale data, click refresh");
						var stale_options=[];
						stale_options['browser_alert']=false;
						process_is_stale(stale_options);
					}

				}else{
					if (output.alert_message) {
						alert(output.alert_message);
					}
				}
			},
		});
	}
}

function process_is_stale(stale_options){

	//disable all edits by removing class sog_ex_report_generic_update which triggers the update
	$(".sog_ex_report_generic_update").removeClass("sog_ex_report_generic_update");

	var bs_alert_message="<div class='alert alert-warning stale_check_message'>"+
				"This table was modified by another user.  You must refresh the table if you want to edit any record."+
			"</div>";
	$(".report_table_here").prepend(bs_alert_message);

	if (stale_options['browser_alert']) {
		//let them know they must refresh
		alert("This table was modified by another user.  You must refresh the table if you want to edit any record.");
	}

	//clear the interval timer so it doesnt keep on going
	clearInterval(stale_check_interval);
}

function sog_ex_report_generic_update(elem) {
	var fx_name=arguments.callee.name;
	var data_to_send = [];

	if (!elem) {
		var elem=$(this);
	}

	var table_name=elem.closest("table").data("table_name");
	var field_name_hash=elem.data("field_name_hash");
	var value = elem.val(); 

	var primary_key_field=elem.closest(".sog_ex_row").data("primary_key_field");
	var primary_key_value=elem.closest(".sog_ex_row").data("primary_key_value");

	data_to_send.push(table_name); 
	data_to_send.push(field_name_hash);
	data_to_send.push(value);
	data_to_send.push(primary_key_field);
	data_to_send.push(primary_key_value);
	data_to_send.push(last_datetime_checked);
	if (getParameterByName("debug",null)){
		console.log(data_to_send);
	}
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {sog_ex_report_generic_update:data_to_send},
		  beforeSend: function(){
			elem.removeClass("is-invalid");

			//stop checking for stale data and restart after this finishes
			clearInterval(stale_check_interval);
		},
		success: function(output) {
			if (getParameterByName("debug",null)){
				console.log(output);
			}
			if(output.status == "success"){
				save_flash(elem);
				if (output.alert_message) {
					alert(output.alert_message);
				}

				//update the global array that holds when the last time this got data from the db 
				last_datetime_checked=output.last_datetime_checked;

				//output update query if exists
				if (output.result.sql_for_display && $("#show_sql").is(":checked")) {
					$(".output_update_query").show();
					$(".output_update_query .sql_output_here").html("<pre>"+output.result.sql_for_display+"</pre>");
				}else{
					$(".output_update_query").hide();
				}

				//remove message if exists
				$(".stale_check_message").remove();

				//restart interval to check for stale date
				clearInterval(stale_check_interval);
				stale_check_interval = setInterval(check_if_stale_data, stale_check_interval_ms);

			}else{

				//if this was an attempt to edit the log file
				if (output.attempted_to_edit_log){
					$(".edit_active").removeClass("edit_active");
					location.reload();
					exit;
				}

				//if there was stale data
				if (output.check_if_stale_data.is_stale){
					var stale_options=[];
					stale_options['browser_alert']=true;
					process_is_stale(stale_options);
				}else{
					//if there is a value to restore
					if (output.restore_result.value){
						//there is a restore value
						if (confirm(output.alert_message)) {
							elem.val(output.restore_result.value);
						}else{
							elem.addClass("is-invalid");
						}
					}


				}
			}
		},
		error: function () {
			// console.log("error in ajax call");
		},
		complete: function(){
			//hide select query since just performed a select query
			$(".output_select_query").hide();
		}
	});

}

function sog_ex_generic_update(elem) {
	var fx_name=arguments.callee.name;
	var data_to_send = [];

	if (!elem) {
		var elem=$(this);
	}

	var table_name=elem.data("table_name");
	var field_name=elem.data("field_name");
	var value_data=elem.data("chosen_id");

	if (elem.is(":checkbox")) {
		if (elem.is(":checked")) {
			var is_check = 1;
			var value = 1;
		} else {
			var is_check = 0;
			var value = 0;
		}
	} else if (value_data) {
		var value = value_data; 
		// console.log("in value_data " + value);
	} else {
		var value = elem.val(); 
		// value = value.replace(/"/g, "'"); //replace all double quotes with single
	}

	var id_column_name=elem.data("id_column_name");
	var id=elem.data(id_column_name);
	var si=elem.data("si");
	var permission=elem.data("p");
	var needs_refresh=elem.data("needs_refresh");
	var other=elem.data("other");
	data_to_send.push(table_name); 
	data_to_send.push(id);
	data_to_send.push(field_name);
	data_to_send.push(value);
	data_to_send.push(si);
	data_to_send.push(id_column_name);
	data_to_send.push(permission);
	data_to_send.push(other);
	data_to_send.push(is_check);

	if (getParameterByName("debug",null)){
		console.log(data_to_send);
	}

	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {generic_update:data_to_send},
		  beforeSend: function(){
		},
		success: function(output) {
				if (getParameterByName("debug",null)){
					console.log(output);
				}
					 if(output.status == "success"){
				 $(".generic_update_date_modified").html(output.generic_update_date_modified);
				 save_flash($(".generic_update_date_modified"));
				save_flash(elem);
				elem.closest('tr').fadeOut(150).fadeIn(150);
				if (needs_refresh) {
					location.reload();
					exit;
				}
				if (output.show_alert) {
					alert(output.show_alert);
				}
				if (output.update_html) {
					$(output.this_div).html(output.update_html);
					save_flash(output.flash_this);
				}
				if (other=="update_admin_course_buttons" && output.new_admin_course_buttons) {
					var button_html=output.new_admin_course_buttons;
					button_html = $.parseHTML( output.new_admin_course_buttons );

					//create temp holder
					$("body").prepend("<div id='replacement_wrapper'></div>");
	
					$("#replacement_wrapper").html(button_html);
					var new_html=$("#replacement_wrapper").find(".load_course_buttons").html();
					$(".load_course_buttons").html(new_html);
					
					//remove temp div
					$("#replacement_wrapper").remove();

				}
			 }else{
				if (output.show_alert) {
					alert(output.show_alert);
				}
				// console.log("status is NOT success "+fx_name);
				// // console.log(output);
			 }
		},
		error: function () {
			// console.log("error in ajax call");
		},
		complete: function(){
			
		}
	});
}

function load_manage_dataset(){
	var elem=$(this)
	var table=elem.val()
	var display_name=elem.find("option:selected").data("display_name");
	var ct=elem.find("option:selected").data("ct");
	var data_to_send = [];
	data_to_send.push(table);
	data_to_send.push(display_name);
	// // console.log(data_to_send);
	if (table) {
	$.ajax({ url: plugin_dir()+'/process.php',
			type: 'POST',
			data    : {load_manage_dataset:data_to_send},
			  beforeSend: function(){
				//call spinner and remove when done
				sog_explore_fixed_spinner_arrows_html();
			},
			success: function(output) {
				// console.log(output);
				 if(output.status == "success"){
					 if (output.html) {
						 $(".manage_table_wrapper").html(output.html);
					 }
				 }else{
					if (output.alert_message) {
						alert(output.alert_message);
					}
					// console.log("status is NOT success");
					// // console.log(output);
				 }
			},
			error: function () {
				// console.log("error in ajax call");
			},
			complete: function(){
				$(".sog_explore_fixed_spinner_arrows").remove();
				load_report_datatable();
			}
		});
	}
}

function sog_ex_restore_value(elem){
	var id=elem.closest("tr").data("primary_key_value");
	var data_to_send = [];
	data_to_send.push(id);
	if (getParameterByName("debug",null)){
		console.log(data_to_send);
	}
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {sog_ex_restore_value:data_to_send},
			beforeSend: function(){
			//call spinner and remove when done
			// sog_explore_fixed_spinner_arrows_html();
		},
		success: function(output) {
			if (getParameterByName("debug",null)){
				console.log(output);
			}
			if(output.status == "success"){
					save_flash(elem);

					//now rerun to get newly added restore log entry
					process_report_request();
				}else{
				if (output.alert_message) {
					alert(output.alert_message);
				}
				// console.log("status is NOT success");
				// // console.log(output);
				}
		},
		error: function () {
			// console.log("error in ajax call");
		},
		complete: function(){
			$(".sog_explore_fixed_spinner_arrows").remove();
		}
	});
}

function generic_load_menu_section() {
	//call spinner and remove when done
	sog_explore_fixed_spinner_arrows_html();

	var fx_name=arguments.callee.name;
	var data_to_send = [];
	var elem=$(this);
	var slug=elem.data("slug");
	data_to_send.push(slug);
	// console.log(data_to_send);
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {generic_load_menu_section:data_to_send},
		  beforeSend: function(){
			  
		},
		success: function(output) {
			// console.log(output);
			 if(output.status == "success"){
				$("#display_section").html(output.html);
			 }else{
				$("#display_section").html(output.error_message);
				// console.log("status is NOT success "+fx_name);
				// // console.log(output);
			 }
		},
		error: function () {
			// console.log("error in ajax call");
		},
		complete: function(){ 
			$(".sog_explore_fixed_spinner_arrows").remove();
			load_report_datatable();

		}
	});
}

function filter_this_generic_input(){
	var filter=$(this).val().toLowerCase();
	var wrapper=$(this).data("wrapper");
	var generic_filter_count;
	if (filter.length) {
		$(this).closest(wrapper).find(".filter_row").hide();
		$(this).closest(wrapper).find(".filter_this").each(function(){
			var sort_helper_hidden=$(this).find(".sort_helper_hidden").html();
			var val=$(this).find("input").val()+$(this).find("textarea").val();
			if (sort_helper_hidden) {
				if (sort_helper_hidden.toLowerCase().includes(filter)){
					$(this).closest(".filter_row").show();
				}
			}
			if (val) {
				if (val.toLowerCase().includes(filter)){
					$(this).closest(".filter_row").show();
				}
			}
		});
		$(".generic_filter_count").html($(wrapper+" .filter_row:visible").length);
	}else{
		$(wrapper+" .filter_row").show();
	}
}

function select_all_none_choose_tables(){
	var data_to_send = [];
	var elem=$(this)
	var which=elem.data("which");
	data_to_send.push(which);
	// console.log(data_to_send);
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {select_all_none_choose_tables:data_to_send},
		beforeSend: function(){
			sog_explore_fixed_spinner_arrows_html();
		},
		success: function(output) {
			// console.log(output);
			 if(output.status == "success"){
				 $(".menu_button[data-slug='setting_choose_tables']").click();
			 }else{
				if (output.alert_message) {
					alert(output.alert_message);
				}
			 }
		},
		complete: function(){
			$(".sog_explore_fixed_spinner_arrows").remove();
		}
	});
}

function load_report_datatable(){
	$(".report_data_table").DataTable().destroy();
	var report_table=$(".report_data_table").DataTable({
		// "dom": 'lifrtpB',
		// "dom": 'fBt',
		"dom": '<"top d-flex justify-content-between"<B><""><""f>>t<"bottom d-flex justify-content-between"<""B><""p>>',
		"lengthMenu": [[50,100,500,-1], [50,100,500,"All"]],
		language: {
			'search' : 'Filter Results' /*Empty to remove the label*/
		},
		 "order": [],
		"autoWidth": false,
		responsive: false,
		buttons: [
				{ extend: 'csv', text: 'Download <i class="fas fa-download"></i>', title: 'Export', className: 'text-white bg-primary report_dt_button'},
				// { extend: 'copy', text: 'Copy', className: 'btn-primary report_dt_button' },
				// { extend: 'pdf', text: 'PDF', className: 'report_dt_button' },
		],
		columnDefs: [
				// { width: "5%", targets: 0 },
		],
		"initComplete": function () {
			var button=$(this).closest(".dataTables_wrapper ").find(".dt-buttons  .btn");
			button.removeClass("btn-secondary");
			$(this).show();
        }
	});	
}

function sog_explore_filter_this_generic_span(){
	var filter=$(this).val().toLowerCase();
	var wrapper=$(this).data("wrapper");
	if (filter.length) {
		$(this).closest(wrapper).find(".filter_row").hide();
		$(this).closest(wrapper).find(".filter_this").each(function(){
			if ($(this).html().toLowerCase().includes(filter) ){
				$(this).closest(".filter_row").show();
			}
		});
	}else{
		$(wrapper+" .filter_row").show();
	}
}

function plugin_dir(){
	var dir=sog_explore_vars.plugin_path;
	// console.log(dir);
	return dir;
}

function sog_explore_fixed_spinner_arrows_html(){
	var html='<div class="sog_explore_fixed_spinner_arrows"><img src="'+plugin_dir()+'/images/spinner/blue_arrow.svg"></div>';
	$("body").prepend(html);
}

function spinner_arrows_html(){
	return '<div class="text-center spinner_arrows"><img src="'+plugin_dir()+'/images/spinner/blue_arrow.svg"></div>';
}

function save_flash(this_div) {
	if (this_div) {
		$(this_div).fadeOut(150).fadeIn(150);
	} else {
		
	}
 }

 function setCookie(name,value,days) {
    var expires = "";

    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }

    document.cookie = name + "=" + (value || "")  + expires + "; path=/; SameSite=Strict";
}

function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');

    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }

    return null;
}

function eraseCookie(name) {   
    var expires = "";
	var date = new Date();
	date.setTime(date.getTime() - (1));
	expires = "; expires=" + date.toUTCString();
    document.cookie = name + "=" + ("0" || "")  + expires + "; path=/";
}

function updateQueryStringParameter(uri, key, value) {
	if (!uri) {
	  uri = window.location.href;
	}

	var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
	var separator = uri.indexOf('?') !== -1 ? "&" : "?";
	
	if (uri.match(re)) {
		return uri.replace(re, '$1' + key + "=" + value + '$2');
	} else {
		return uri + separator + key + "=" + value;
	}
}

function getParameterByName(name, url) {
	if (!url) {
	  url = window.location.href;
	}

	name = name.replace(/[\[\]]/g, "\\$&");
	var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
		results = regex.exec(url);

	if (!results) return null;
	if (!results[2]) return '';

	return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function removeURLParameter(parameter,url) {
	if (!url) {
	  url = window.location.href;
	}    //prefer to use l.search if you have a location/link object

    var urlparts= url.split('?');

    if (urlparts.length>=2) {
        var prefix= encodeURIComponent(parameter)+'=';
        var pars= urlparts[1].split(/[&;]/g);

        //reverse iteration as may be destructive
        for (var i= pars.length; i-- > 0;) {    
            //idiom for string.startsWith
            if (pars[i].lastIndexOf(prefix, 0) !== -1) {  
                pars.splice(i, 1);
            }
        }

        url= urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : "");
        return url;
    } else {
        return url;
    }
}


//################## Eggs #####################
function trigger_wyd(){
	animateDiv_Rand('.far',2000);
	animateDiv_Rand('.fas',2000);
	animateDiv_Rand('li',1000);
	setTimeout(function() { 
		animateDiv_Rand('label',2000);
		animateDiv_Rand('td',2000);
	}, 2000);
	insert_what_you_do();
	var num_elems=Math.floor(Math.random() * 30) + 5;
	var items=shuffle($("div")).slice(0, num_elems);
	for (var x = 0; x < items.length; x++) {
		animate_element(items[x],2000);
	}
}
			//******************* Eggs Functions ************************//
			function shuffle(array) {
				var m = array.length, t, i;
		
				// While there remain elements to shuffle…
				while (m) {
		
				// Pick a remaining element…
				i = Math.floor(Math.random() * m--);
		
				// And swap it with the current element.
				t = array[m];
				array[m] = array[i];
				array[i] = t;
				}
		
				return array;
			}
		
			function matrix_animation(){
		
				$("*").css({"margin":0,"padding":0});
				$("body").css("background","black");
				$("canvas").css({"display":"block","position":"fixed","top":0,"z-index":99999,"width":"unset","height":"unset"});
				//needs html <canvas id="c"></canvas> which is on bottom of cdb.php
				var c = document.getElementById("c");
				var ctx = c.getContext("2d");
		
				//making the canvas full screen
				c.height = window.innerHeight;
				c.width = window.innerWidth;
		
				//chinese characters - taken from the unicode charset
				var chinese ="DARREN IS NEO";
				// var chinese="DARREN IS NEO田由甲申甴电甶男甸甹町画甼甽甾甿畀畁畂畃畄畅畆畇畈畉畊畋界畍畎畏畐畑";
				//converting the string into an array of single characters
				chinese = chinese.split("");
		
				var font_size = 10;
				var columns = c.width / font_size; //number of columns for the rain
				//an array of drops - one per column
				var drops = [];
				//x below is the x coordinate
				//1 = y co-ordinate of the drop(same for every drop initially)
				for (var x = 0; x < columns; x++) drops[x] = 1;
		
				//drawing the characters
				function draw() {
					//Black BG for the canvas
					//translucent BG to show trail
					ctx.fillStyle = "rgba(0, 0, 0, 0.05)";
					ctx.fillRect(0, 0, c.width, c.height);
		
					ctx.fillStyle = "#0F0"; //green text
					ctx.font = font_size + "px arial";
					//looping over drops
					for (var i = 0; i < drops.length; i++) {
					//a random chinese character to print
					var text = chinese[Math.floor(Math.random() * chinese.length)];
					//x = i*font_size, y = value of drops[i]*font_size
					ctx.fillText(text, i * font_size, drops[i] * font_size);
		
					//sending the drop back to the top randomly after it has crossed the screen
					//adding a randomness to the reset to make the drops scattered on the Y axis
					if (drops[i] * font_size > c.height && Math.random() > 0.975)
					drops[i] = 0;
		
					//incrementing Y coordinate
					drops[i]++;
					}
				}
				setInterval(draw, 15);
		
			}
			
			function catch_me(elem,speed){
				// elem.css("position","relative");
				elem.attr('style', 'position: relative !important');
				elem.animate({
					left:(Math.random()*400)+"px",
					top:(Math.random()*380)+"px",
				},speed).css("z-index",1000);	
			}
		
			function insert_what_you_do(){
				var new_div="<farley class='farley'><img src='"+sog_explore_vars['plugin_path']+"inc/images/whatyoudo.gif'></div>";
				$("body").append(new_div);
				$(".farley").hide();
				$(".farley").delay(000).fadeIn(500);
			}
		
			function animateDiv_Rand(myclass,speed){
				$(myclass).css("position","fixed");
				$(myclass).each(function(){
					var newq = makeNewPosition();
					$(this).animate({ top: newq[0], left: newq[1] }, newq[2],   function(){
						animateDiv(myclass,speed);        
					});
				});
			};
		
			function animateDiv(myclass,speed){
				$(myclass).css("position","fixed");
				var newq = makeNewPosition();
				$(myclass).animate({ top: newq[0], left: newq[1] }, speed,   function(){
					animateDiv(myclass,speed);        
				});
			};
			
			function animate_element(element,speed){
				elem = $(element);
				elem.css("position","fixed");
				var newq = makeNewPosition();
				elem.animate({ top: newq[0], left: newq[1] }, speed,   function(){
					animate_element(elem,speed);        
				});
			};
		
			function makeNewPosition(){
				//this gets called from animateDiv and animateDiv_Rand
				var h = $(window).height() - 50;
				var w = $(window).width() - 50;
				
				var nh = Math.floor(Math.random() * h);
				var nw = Math.floor(Math.random() * w);
				var rspeed=Math.floor(Math.random() * 4000);
				return [nh,nw,rspeed];    
				
			}
  
  
})(jQuery);
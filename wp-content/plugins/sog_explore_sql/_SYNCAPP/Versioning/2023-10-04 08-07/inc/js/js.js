(function($) {

var sog_ex_report_array_of_fields=[];
var sog_ex_report_array_of_filters=[];

$(document).ready(function() {

	//this will call the report which will check if this button was pushed, and if so clear limit
	$(this).on("click", '.sog_ex_report_get_all_records',sog_ex_process_report_request);

	//when choosing fields this will modify global variable of checked fields then call the report
	$(this).on("click", '.sog_ex_report_checkbox',process_sog_ex_report_checkbox);

	$(this).on("change", '.sog_ex_explore_filter',function(){
		//when changing filters this first updates the global variable for filters
		sog_ex_process_report_filter();

		//then runs the report 
		sog_ex_process_report_request();
	});

	$(this).on("keyup", '.sog_ex_explore_filter_like',function(){
		//when changing filters this first updates the global variable for filters
		sog_ex_process_report_filter();

		//then runs the report 
		sog_ex_process_report_request();
	});

	//when changing the limit it will call the report, the report fx will read the global variables for filters and fields
	$(this).on("change", '.sog_ex_report_limit',function(){
		if (sog_ex_report_array_of_fields.length) {
			sog_ex_process_report_request();
		}
	});

	$(this).on("click", '.sog_ex_reload_manage_table', function(){
		$(".sog_ex_load_manage_dataset").change();
	});

	$(this).on("change",".sog_ex_generic_update",function(){
		sog_ex_generic_update($(this));
	});

	$(this).on("change", '.sog_ex_select_report_table',function(){
		if ($(this).val()) {
			sog_ex_select_report_table($(this));
		}else{
			
		}
	});
	$(this).on("click", '.sog_ex_menu_button', sog_ex_generic_load_menu_section);
	$(this).on("keyup", '.sog_ex_filter_this_generic_input',sog_ex_filter_this_generic_input);
	$(this).on("click", '.sog_ex_select_all_none_choose_tables', sog_ex_select_all_none_choose_tables);
	$(this).on("change", '.sog_ex_load_manage_dataset', sog_ex_load_manage_dataset);
	$(this).on("keyup", '.sog_explore_filter_this_generic_span',sog_explore_filter_this_generic_span);
	$(this).on("click", '.sog_ex_add_explore_filter',sog_ex_add_explore_filter);
	
   $(this).on("click", '.sog_ex_help_info_sign',function(){
	   $(this).siblings(".sog_ex_help-block").slideToggle("fast",function() {
			if ($(this).is(':visible')) {
				$(this).css('display','block');
			}
	   });
   });
	});

///////////// ************ Functions **********************/////////////////

function sog_ex_filter_this_generic_input(){
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

function sog_ex_select_all_none_choose_tables(){
	var data_to_send = [];
	var elem=$(this)
	var which=elem.data("which");
	data_to_send.push(which);
	// console.log(data_to_send);
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {sog_ex_select_all_none_choose_tables:data_to_send},
		beforeSend: function(){
			sog_explore_fixed_spinner_arrows_html();
		},
		success: function(output) {
			console.log(output);
			 if(output.status == "success"){
				 $(".sog_ex_menu_button[data-slug='sog_ex_setting_choose_tables']").click();
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

function sog_ex_select_report_table(elem){
	var data_to_send = [];
	var table_name=elem.val();
	data_to_send.push(table_name);
	// console.log(data_to_send);
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {sog_ex_select_report_table:data_to_send},
		beforeSend: function(){
			sog_explore_fixed_spinner_arrows_html();
		},
		success: function(output) {
			console.log(output);
			 if(output.status == "success"){
				 $(".report_fields_here").html(output.html);
				 
				 //clear global arrays for checkboxes and filters
				 sog_ex_report_array_of_fields=[];
				 sog_ex_report_array_of_filters=[];
 				 $(".sog_ex_report_table_here").html("");
 				 $(".sog_ex_hide_until_limit_ready").hide();

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
	console.log(data_to_send);
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {generic_update:data_to_send},
		  beforeSend: function(){
		},
		success: function(output) {
				console.log(output);
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

function sog_ex_load_manage_dataset(){
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
			data    : {sog_ex_load_manage_dataset:data_to_send},
			  beforeSend: function(){
				//call spinner and remove when done
				sog_explore_fixed_spinner_arrows_html();
			},
			success: function(output) {
				console.log(output);
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

function sog_ex_generic_load_menu_section() {
	//call spinner and remove when done
	sog_explore_fixed_spinner_arrows_html();

	var fx_name=arguments.callee.name;
	var data_to_send = [];
	var elem=$(this);
	var slug=elem.data("slug");
	data_to_send.push(slug);
	console.log(data_to_send);
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {sog_ex_generic_load_menu_section:data_to_send},
		  beforeSend: function(){
			  
		},
		success: function(output) {
			console.log(output);
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
		}
	});
}

function sog_ex_add_explore_filter(){
	// console.log(sog_ex_report_array_of_filters);

	//need to get values of existing filter selected and add back when this is done.
	var all_filter_values=[];
	$(".sog_ex_explore_filter").each(function(){
		var filter_values=[];
		var filter_id=$(this).attr("id");
		var value=$(this).val();
		filter_values.push(filter_id);
		filter_values.push(value);
		all_filter_values.push(filter_values);
	});

	var elem=$(this);

	//load spinner 
	sog_explore_fixed_spinner_arrows_html();

	if (elem.hasClass("text-success")) {
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
	console.log(data_to_send);
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {sog_ex_add_explore_filter:data_to_send},
		beforeSend: function(){
		},
		success: function(output) {
			console.log(output);
			 if(output.status == "success"){
				 $(".sog_ex_explore_filters_here").html(output.filters_html);
				if (do_what=="add") {
					elem.removeClass("text-secondary").addClass("text-success");
				}else{
					elem.removeClass("text-success").addClass("text-secondary");
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
			// sog_ex_process_report_request();
			
		}
	});
}

function sog_ex_process_report_filter(){
	//empty array 
	sog_ex_report_array_of_filters=[];

	//get all filtes on page
	$(".sog_ex_explore_filter").each(function(){
		var elem=$(this);
		var filter_string="";
		var filter_field_name=elem.data("filter_field_name");
		var si=elem.data("si");
		var filter_field_value=elem.val();
		if (filter_field_value) {
			if (si=="s") {
				filter_string="and "+filter_field_name+"='"+filter_field_value+"'";
			}else{
				filter_string="and "+filter_field_name+"="+filter_field_value;
			}
			
			sog_ex_report_array_of_filters.push(filter_string);
		}
	});

	//get all filter input likes on page
	$(".sog_ex_explore_filter_like").each(function(){
		var elem=$(this);
		var filter_string="";
		var filter_field_name=elem.data("filter_field_name");
		var si=elem.data("si");
		var filter_field_value=elem.val();
		if (filter_field_value) {
			if (si=="s") {
				filter_string="and "+filter_field_name+" like '%"+filter_field_value+"%'";
			}else{
				filter_string="and "+filter_field_name+" like %"+filter_field_value+"%";
			}
			
			sog_ex_report_array_of_filters.push(filter_string);
		}
	});
	console.log(sog_ex_report_array_of_filters);
}

function process_sog_ex_report_checkbox(){
	var data_to_send = [];
	var elem=$(this);
		if ($(this).is(":checked")) {
			sog_ex_report_array_of_fields.push(elem.val());
		}else{
			index = sog_ex_report_array_of_fields.indexOf(elem.val());
			sog_ex_report_array_of_fields.splice(index, 1);
		}
	// console.log(sog_ex_report_array_of_fields);
	sog_ex_process_report_request();
}

function sog_ex_process_report_request(){
	var data_to_send = [];
	
	//load spinner 
	sog_explore_fixed_spinner_arrows_html();

	//this gets from dropdown, which has been hidden for now
	var table_name=$(".sog_ex_select_report_table").val();

	var sog_ex_report_limit=$(".sog_ex_report_limit").val();
	
	//this check to see if get all was clicked
	if ($(this).hasClass("sog_ex_report_get_all_records")){
		var sog_ex_report_get_all_records=true;
		var original_text=$(this).html();
		$(this).html("Thinking...");
		sog_ex_report_limit="All";
	}
	//check if there are any fitlers
	sog_ex_process_report_filter();
	
	if (!sog_ex_report_array_of_filters.length) {
		sog_ex_report_array_of_filters.push("");
	}

	data_to_send.push(table_name); 
	data_to_send.push(sog_ex_report_array_of_fields); //gets from global variable
	data_to_send.push(sog_ex_report_array_of_filters);
	data_to_send.push(sog_ex_report_limit);
	console.log(data_to_send);
	$.ajax({ url: plugin_dir()+'/process.php',
		type: 'POST',
		data    : {sog_ex_process_report_request:data_to_send},
		beforeSend: function(){
		},
		success: function(output) {
			console.log(output);
			 if(output.status == "success"){
				 //output new filters if needed
				 $(".sog_ex_explore_filters_here").html(output.filters_html);
				 
				 $(".sog_ex_report_table_here").html(output.html);
					load_report_datatable();
				 $(".sog_ex_report_sample_number").text(output.limit_to_use);
				 $(".sog_ex_report_total_number").text(output.table_data_no_limit_count);
				 if (sog_ex_report_limit=="All") {
					$(".sog_ex_report_showing_subset").hide();
					$(".sog_ex_report_showing_all").show();
					$(".sog_ex_report_dt_button").show();
				 }else if (output.table_data_count==output.table_data_no_limit_count) {
					$(".sog_ex_report_showing_subset").hide();
					$(".sog_ex_report_showing_all").show();
					$(".sog_ex_report_dt_button").show();
				 }else{
					$(".sog_ex_report_showing_subset").show();
					$(".sog_ex_report_showing_all").hide();
					$(".sog_ex_report_dt_button").hide();
				 }
				 $(".sog_ex_hide_until_limit_ready").show();
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

			if (sog_ex_report_get_all_records) {
				$(".sog_ex_report_get_all_records").html(original_text);
			}
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
				{ extend: 'csv', text: 'Download <i class="fas fa-download"></i>', title: 'Export', className: 'text-white bg-primary sog_ex_report_dt_button'},
				// { extend: 'copy', text: 'Copy', className: 'btn-primary sog_ex_report_dt_button' },
				// { extend: 'pdf', text: 'PDF', className: 'sog_ex_report_dt_button' },
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
	console.log(filter);
	console.log(wrapper);
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

})(jQuery);
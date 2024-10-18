(function($) {
	$(document).ready(function() {

		$(this).on("change",".sog_settings_generic_update",function(){
			sog_settings_generic_update($(this));
		});

		$(this).on("click", '.sog_settings_menu_button', sog_settings_load_menu_section);
		$(this).on("change", '.sog_settings_table_add_new',sog_settings_table_add_new);
		$(this).on("change", ".sog_settings_update_setting", sog_settings_update_setting);

		$(this).on("click", '.sog_settings_delete,.sog_settings_delete',function(){
			if ($(this).data("confirm_message")) {
				var confirm_delete=confirm($(this).data("confirm_message"));
			}else{
				var confirm_delete=confirm("Are you sure you want to delete this.");
			}
			if (confirm_delete) {
				sog_settings_delete($(this));
			}
		});



	});

	///////////// ************ Functions **********************/////////////////

	function sog_settings_update_setting() {
		var fx_name=arguments.callee.name;
		var elem=$(this);
		var data_to_send = [];
		var setting_id=elem.data("setting_id");
		var is_bool=elem.data("is_bool");
		var value;
		if (is_bool) {
			if (elem.is(":checked")) {
				value=1;
			}else{
				value=null;
			}
		}else {
			value=elem.val();
		}
		data_to_send.push(setting_id);
		data_to_send.push(value);
		data_to_send.push(is_bool);
		// // console.log(data_to_send);
		$.ajax({ url: plugin_dir()+'/process.php',
			type: 'POST',
			data    : {sog_settings_update_setting:data_to_send},
			beforeSend: function(){
			},
			success: function(output) {
				// // console.log(output);
				if(output.status == "success"){
					save_flash(elem);
				}else{
					if (output.alert_message) {
						alert(output.alert_message);
					}
					// console.log("status is NOT success "+fx_name);
					// // console.log(output);
				}
			},
			error: function (request, status, error) {
				// console.log("error in ajax call");
				// console.log(error);
			},
			complete: function(){
			}
	});
	}

	function sog_settings_generic_update(elem) {
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
					// elem.closest('tr').fadeOut(150).fadeIn(150);
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

	function sog_settings_table_add_new() {
		var fx_name=arguments.callee.name;
		var data_to_send = [];
		elem=$(this);
		var reload=elem.data("reload_on_add_row");
		var reload_with_ajax=elem.data("reload_with_ajax");
		var table_name=elem.data("table_name");
		var field_name=elem.data("field_name");
		var p=elem.data("p");
		var db=elem.data("db");
		var si=elem.data("si");
		var col_num=elem.data("col_num");
		var use_generic_manage=elem.data("use_generic_manage");
		var allow_duplicates=elem.data("allow_duplicates");
		var table_display_name=elem.data("table_display_name");
		var ignore_last_inserted_id=elem.data("ignore_last_inserted_id");
		var value=elem.val();
		var new_row;
		var table_id=$(this).closest('table').attr('id');
		var num_of_cols=$("#"+table_id+" > tbody > tr:first > td").length;
		// // console.log("num_of_cols"+num_of_cols);
		data_to_send.push(table_name);
		data_to_send.push(field_name);
		data_to_send.push(value);
		data_to_send.push(si);
		data_to_send.push(p);
		data_to_send.push(db);
		data_to_send.push(reload_with_ajax);
		data_to_send.push(use_generic_manage);
		data_to_send.push(allow_duplicates);
		data_to_send.push(table_display_name);
		data_to_send.push(ignore_last_inserted_id);
		if (getParameterByName("debug",null)){
			console.log(data_to_send);
		}
	if (value) {
			$.ajax({ url: plugin_dir()+'/process.php',
				type: 'POST',
				data    : {generic_table_add_new:data_to_send},
				beforeSend: function(){
					elem.prop("disabled",true).prop("readonly",true);
				},
				success: function(output) {
						if (getParameterByName("debug",null)) {
							console.log(output);
						}
						if(output.status == "success"){
						if (reload) {
							location.reload();
						}else if (reload_with_ajax){
							elem.closest("#display_section").html(output.html);
						}else{
						}
					}else{
						// console.log(output);
						// console.log("status is NOT success "+fx_name);
						if (output.show_alert) {
							alert(output.show_alert);
						}

						// //// console.log(output.message);
						// // console.log(output);
					}
				},
				error: function () {
					// console.log("error in ajax call");
				},
				complete: function(){
					elem.prop("disabled",false).prop("readonly",false);

				}
			});
		}
	}

	function sog_settings_load_menu_section() {
		//call spinner and remove when done

		var fx_name=arguments.callee.name;
		var data_to_send = [];
		var elem=$(this);
		var slug=elem.data("slug");
		data_to_send.push(slug);
		console.log(data_to_send);
		$.ajax({ url: plugin_dir()+'/process.php',
			type: 'POST',
			data    : {sog_settings_load_menu_section:data_to_send},
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
			}
		});
	}

	function sog_settings_delete(elem) {
		var fx_name=arguments.callee.name;
		var data_to_send = [];
		// var elem=$(this);
		var reload=elem.data("reload");
		var closest=elem.data("closest");
		var table_name=elem.data("table_name");
		var col_num=elem.data("col_num");
		var id_column_name=elem.data("id_column_name");
		var db=elem.data("db");
		var p=elem.data("p");
		var id=elem.data(id_column_name);
		var change_status=elem.data("change_status");
		var empty=elem.data("empty");
		data_to_send.push(table_name);
		data_to_send.push(id);
		data_to_send.push(id_column_name);
		data_to_send.push(p);
		data_to_send.push(db);
		data_to_send.push(change_status);
		if (getParameterByName("debug",null)){
			console.log(data_to_send);
		}
		$.ajax({ url: plugin_dir()+'/process.php',
			type: 'POST',
			data    : {sog_settings_delete:data_to_send},
			beforeSend: function(){

			},
			success: function(output) {
				if (getParameterByName("debug",null)){
					console.log(output);
				}
				if(output.status == "success"){
					if (output.reload) {
						location.reload(output.reload);
					}else if (reload) {
						location.reload();
					}else {
						if (empty) {
							elem.closest(closest).fadeOut(300, function(){ $(this).html("");});
						}else{
							elem.closest(closest).fadeOut(300, function(){ $(this).remove();});
						}
					}
				}else{
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

	function save_flash(this_div) {
		if (this_div) {
			$(this_div).fadeOut(150).fadeIn(150);
		} else {

		}
	}

	function plugin_dir(){
		var dir=sog_settings_vars.plugin_path;
		// console.log(dir);
		return dir;
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
})(jQuery);

jQuery(document).on("ready", function() {
	jQuery('#enroll_learndash_courses').select2();
	jQuery('#enroll_learndash_groups').select2();

	if(typeof manage_enrollment_learndash != "object")
		return;

    jQuery('#manage_enrollment_learndash select.en_select2').select2({width: "100%"});

	jQuery("#select_all").on("change", function() {
		jQuery("tr[data-enrollment] [type=checkbox]:not([disabled])").prop('checked', jQuery("#select_all").is(":checked"));
		manage_enrollment_learndash_update_checked_count();
	});

	jQuery("#manage_enrollment_learndash_table table").click(function(e) {
		if(jQuery(e.target).attr("type") == "checkbox") {
			manage_enrollment_learndash_update_checked_count();
		}
	});

	jQuery('#users [role="searchbox"]').keypress(function(e) {
		manage_enrollment_learndash_handle_users_keypress(e, ",");
		manage_enrollment_learndash_handle_users_keypress(e, " ");
	});
	jQuery('#users [role="searchbox"]').on("keyup change", function(e) {
		if( e.which == 13 )
		manage_enrollment_learndash_filter_users_list();
		else
		setTimeout( () => manage_enrollment_learndash_filter_users_list(), 1000 );
	});

	if(typeof manage_enrollment_learndash.uploaded_data == "object" && manage_enrollment_learndash.uploaded_data.length > 0) {
		//jQuery("#manage_enrollment_learndash #course").hide();

		jQuery.each(manage_enrollment_learndash.uploaded_data, function(i, data) {
			manage_enrollment_learndash_add_row(data, i+1);
		});
	}

	jQuery(".grassblade_learndash_activate_plugin").on("click", function() {
		grassblade_learndash_activate_plugin(jQuery(this).data("url"));
		jQuery(this).data("url", "");
	});
});
function manage_enrollment_learndash_update_checked_count() {
	jQuery("#process_enrollments .count, #process_unenrollments .count, #check_enrollments .count").text(" (" + jQuery("#manage_enrollment_learndash_table input[type=checkbox]:not(#select_all):checked").length + ")");
}
function manage_enrollment_learndash_show_user_selection(show) {
	if(show) {
		jQuery('#users').show();
	}
	else
	{
		jQuery('#users').hide();
	}
}
function manage_enrollment_learndash_filter_users_list() {
	var string = jQuery('#users [role="searchbox"]').val().trim();
	if(typeof window.manage_enrollment_learndash_filter_users_string  == "string" && window.manage_enrollment_learndash_filter_users_string == string)
	return;

	window.manage_enrollment_learndash_filter_users_string = string;

	if( string == "" ) {
		jQuery("#users option").show();
	}
	else {
		var select = "";
		var count = 0;
		jQuery("#users option").each(function(i, option) {
			if( jQuery(option).val() == "" ) {
				select = jQuery(option);
				jQuery(select).html("Updating...");
				select.show();
				return;
			}

			if( jQuery(option).text().toLowerCase().indexOf(string) != -1 ) {
				jQuery(option).show();
				count++;
			}
			else
			jQuery(option).hide();
		});
		if( select ) {
			setTimeout(function(){ jQuery(select).html(" --- Select a User --- (" + count + ")"); }, 200);
		}
	}
}
function manage_enrollment_learndash_handle_users_keypress(e, splitter) {
    	//console.log(e.which, jQuery("#user_ids").val(), jQuery('#users [role="searchbox"]').val());
    	if(e.which == 32 || e.which == 13) {
			var values = jQuery("#user_ids").val();
			if(values == null)
				values = [];

			var string = jQuery('#users [role="searchbox"]').val().trim();
			var updated = false;
			var input_items = string.split(splitter);
			jQuery.each(input_items, function(i, v) {
				if(v != 0) {
					var str = v.trim().toLowerCase();

					var value = jQuery("#user_ids option[value='" + str + "']").val();

					if(typeof value == "undefined")
					var value = jQuery("#user_ids option[data-user_login='" + str + "']").val();

					if(typeof value == "undefined") {
						var value = jQuery("#user_ids option[data-user_email='" + str + "']").val();
					}
					if(value != undefined) {
						updated = true;
						values[values.length] = value;
						jQuery("#user_ids").val(value).trigger("change");
						delete( input_items[i] );
					}
				}
				else
				{
					delete( input_items[i] );
				}
			});
			if( updated ) {
				jQuery('#users [role="searchbox"]').val(input_items.filter(function(el) { return el; }).join(splitter));
			}
    	}
    }
function manage_enrollment_learndash_course_selected(context) {
	if(jQuery(context).val())
	jQuery("#group_id").val("").trigger('change');

	var group_id = jQuery("#group_id").val();
	var course_id = jQuery("#course_id").val();

	if( (group_id == "" || group_id == null) && (course_id == "" || course_id == null) ) {
		jQuery("#manage_enrollment_learndash #upload_csv").show();
		return;
	}
	else
		jQuery("#manage_enrollment_learndash #upload_csv").hide();

	//show user list.
	manage_enrollment_learndash_show_user_selection(true);
}
function manage_enrollment_learndash_group_selected(context) {
	if(jQuery(context).val())
	jQuery("#course_id").val("").trigger('change');

	var group_id = jQuery("#group_id").val();
	var course_id = jQuery("#course_id").val();

	if( (group_id == "" || group_id == null) && (course_id == "" || course_id == null) ) {
		jQuery("#manage_enrollment_learndash #upload_csv").show();
		return;
	}
	else
		jQuery("#manage_enrollment_learndash #upload_csv").hide();

	//show user list.
	manage_enrollment_learndash_show_user_selection(true);
}

function manage_enrollment_learndash_users_selected(context) {
	var course_id = jQuery("#manage_enrollment_learndash #course_id").val();
	var group_id = jQuery("#manage_enrollment_learndash #group_id").val();

	//console.log(jQuery("#users select").val());
	var user_ids = jQuery("#users select").val();
	user_ids = (typeof user_ids != "object" && user_ids * 1 > 0)? [user_ids]:user_ids;

	var sno = jQuery("#manage_enrollment_learndash_table table tr:last-child .sno").text()*1 + 1;

	if(typeof user_ids == "object" && user_ids != null && user_ids.length > 0)
	jQuery.each(user_ids, function(i, user_id) {
		if( user_id > 0 ) {
			var data = {course_id:course_id, group_id:group_id, user_id: user_id};
			sno += manage_enrollment_learndash_add_row(data, sno);
		}
	});

	jQuery("#users select").val("");
}
function manage_enrollment_learndash_add_row(data, sno) {
	var course_id 	= (typeof data.course_id == "undefined")? "":data.course_id;
	var group_id 	= (typeof data.group_id == "undefined")? "":data.group_id;
	var user_id 	= (typeof data.user_id == "undefined")? "":data.user_id;
	var status 		= (typeof data.status == "string")? data.status:"Not Processed";

	if( user_id == "" || user_id == null || (course_id == "" || course_id == null) && (group_id == "" || group_id == null) )
		return false;

	var key = "enrollment_" + course_id + "_" + group_id + "_" + user_id;
	data["row_id"] = key;

	var row = "<tr id='" + key + "' data-enrollment='" + JSON.stringify(data) + "'>";

	if(jQuery("#manage_enrollment_learndash_table #" + key).length == 0)
	{
		var user_label = jQuery("#users option[value=" + user_id+ "]").text();
		row += "<td>" + "<input type='checkbox' name='" + key + "'>" + "</td>";
		row += "<td class='sno'>" + sno + "</td>";
		row += "<td>" + user_label + "</td>";
		row += "<td>" + manage_enrollment_learndash_get_label("course", course_id) + "</td>";
		row += "<td>" + manage_enrollment_learndash_get_label("group", group_id) + "</td>";
		row += "<td>" + manage_enrollment_learndash_get_buttons(data) + "</td>";
		row += "<td class='status'>" + status + "</td>";

		jQuery("#manage_enrollment_learndash_table table").append(row);
		manage_enrollment_learndash_update_total_count();

		return true;
	}

	return false;
}
function manage_enrollment_learndash_update_total_count() {
	jQuery("#manage_enrollment_learndash_table #list_count .count").text(jQuery("#manage_enrollment_learndash_table tr").length - 1);
}
function manage_enrollment_learndash_get_buttons(data) {
	return " <a onclick='manage_enrollment_learndash_enroll(this)' class='button-secondary'>Enroll</a> " + " <a onclick='manage_enrollment_learndash_unenroll(this)' class='button-secondary'>Un-enroll</a> " + " <a onclick='manage_enrollment_learndash_check_enrollment(this)' class='button-secondary'>Check Enrollment</a> " +  " <a onclick='manage_enrollment_learndash_remove(this);' class='button-secondary'> X </a> ";
}
function manage_enrollment_learndash_remove(context) {
	jQuery(context).closest("tr").attr("data-status", "remove");

	setTimeout(function() {
		jQuery(context).closest("tr").remove();
		manage_enrollment_learndash_update_checked_count();
		manage_enrollment_learndash_update_total_count();
	}, 600);
}
function manage_enrollment_learndash_enroll(selected) {

	if( jQuery("#manage_enrollment_learndash_table tr[data-status=processing]").length > 0 )
	{
		alert("Please wait for current queue to complete.");
		return;
	}

	var enrollment_data = [];

	if( selected != undefined )
		var selected_enrollments = jQuery(selected).closest("tr");
	else
		var selected_enrollments = jQuery("#manage_enrollment_learndash_table input[type=checkbox]:not(#select_all):checked").closest("tr");

	selected_enrollments.attr("data-status", "waiting");
	selected_enrollments.find(".status").text("Waiting...");

	var processing_enrollments = selected_enrollments.slice(0, 10);

	processing_enrollments.each(function(i, context) {
		enrollment_data[i] = jQuery(context).data("enrollment");

		jQuery(context).attr("data-status", "processing");
		jQuery(context).find(".status").text("Processing...");
		jQuery(context).find("input[type=checkbox]").prop("checked", false).prop("disabled", true);
	});

	if(typeof enrollment_data != "object" || enrollment_data == null || enrollment_data.length == 0) {
		alert("Nothing to process.");
		return;
	}


	var data = {
		"action" : "manage_enrollment_learndash_enroll",
		"data" : enrollment_data,
	};
	jQuery.post(manage_enrollment_learndash.ajax_url, data)
	.done(function( data ) {
		//console.error(data);

		if(typeof data.data == "object")
		jQuery.each(data.data, function(i, data) {
			var context = "#" + data.row_id;
			if( data.status == 1 )
				jQuery(context).closest("tr").attr("data-status", "processed");
			else
				jQuery(context).closest("tr").attr("data-status", "failed");

			if(typeof data.message == "string")
				jQuery(context).closest("tr").find(".status").text(data.message);
			else
				jQuery(context).closest("tr").find(".status").text("Invalid Response");
		});
	})
	.fail(function(xhr, status, error) {
		//console.log(xhr, status, error);
	//	jQuery(context).closest("tr").find(".status").text("Request Failed");
		processing_enrollments.find(".status").text("Failed Request");
		processing_enrollments.attr("data-status", "failed");
	})
	.always(function() {
		manage_enrollment_learndash_update_checked_count();

		setTimeout(function() {

			var waiting = jQuery("#manage_enrollment_learndash_table tr[data-status=waiting]");
			if(waiting.length > 0)
			manage_enrollment_learndash_enroll( waiting );
			else if( selected == undefined )
			alert("All Completions Processed.");

		}, 500);
	});
}
function manage_enrollment_learndash_unenroll(selected) {

	if( jQuery("#manage_enrollment_learndash_table tr[data-status=processing]").length > 0 )
	{
		alert("Please wait for current queue to complete.");
		return;
	}

	var enrollment_data = [];

	if( selected != undefined )
		var selected_enrollments = jQuery(selected).closest("tr");
	else
		var selected_enrollments = jQuery("#manage_enrollment_learndash_table input[type=checkbox]:not(#select_all):checked").closest("tr");

	selected_enrollments.attr("data-status", "waiting");
	selected_enrollments.find(".status").text("Waiting...");

	var processing_enrollments = selected_enrollments.slice(0, 10);

	processing_enrollments.each(function(i, context) {
		enrollment_data[i] = jQuery(context).data("enrollment");

		jQuery(context).attr("data-status", "processing");
		jQuery(context).find(".status").text("Processing...");
		jQuery(context).find("input[type=checkbox]").prop("checked", false).prop("disabled", true);
	});

	if(typeof enrollment_data != "object" || enrollment_data == null || enrollment_data.length == 0) {
		alert("Nothing to process.");
		return;
	}


	var data = {
		"action" : "manage_enrollment_learndash_unenroll",
		"data" : enrollment_data,
	};
	jQuery.post(manage_enrollment_learndash.ajax_url, data)
	.done(function( data ) {
		//console.error(data);

		if(typeof data.data == "object")
		jQuery.each(data.data, function(i, data) {
			var context = "#" + data.row_id;
			if( data.status == 1 )
				jQuery(context).closest("tr").attr("data-status", "failed");
			else
				jQuery(context).closest("tr").attr("data-status", "processed");

			if(typeof data.message == "string")
				jQuery(context).closest("tr").find(".status").text(data.message);
			else
				jQuery(context).closest("tr").find(".status").text("Invalid Response");
		});
	})
	.fail(function(xhr, status, error) {
		//console.log(xhr, status, error);
	//	jQuery(context).closest("tr").find(".status").text("Request Failed");
		processing_enrollments.find(".status").text("Failed Request");
		processing_enrollments.attr("data-status", "failed");
	})
	.always(function() {
		manage_enrollment_learndash_update_checked_count();

		setTimeout(function() {

			var waiting = jQuery("#manage_enrollment_learndash_table tr[data-status=waiting]");
			if(waiting.length > 0)
			manage_enrollment_learndash_unenroll( waiting );
			else if( selected == undefined )
			alert("All Completions Processed.");

		}, 500);
	});
}
function manage_enrollment_learndash_check_enrollment(selected) {

	if( jQuery("#manage_enrollment_learndash_table tr[data-status=processing]").length > 0 )
	{
		alert("Please wait for current queue to complete.");
		return;
	}

	var enrollment_data = [];

	if( selected != undefined )
		var selected_enrollments = jQuery(selected).closest("tr");
	else
		var selected_enrollments = jQuery("#manage_enrollment_learndash_table input[type=checkbox]:not(#select_all):checked").closest("tr");

	selected_enrollments.attr("data-status", "waiting");
	selected_enrollments.find(".status").text("Waiting...");

	var processing_enrollments = selected_enrollments.slice(0, 10);

	processing_enrollments.each(function(i, context) {
		enrollment_data[i] = jQuery(context).data("enrollment");

		jQuery(context).attr("data-status", "processing");
		jQuery(context).find(".status").text("Processing...");
		jQuery(context).find("input[type=checkbox]").prop("checked", false).prop("disabled", true);
	});

	if(typeof enrollment_data != "object" || enrollment_data == null || enrollment_data.length == 0) {
		alert("Nothing to process.");
		return;
	}

	var data = {
		"action" : "manage_enrollment_learndash_check_enrollment",
		"data" : enrollment_data
	};
	jQuery.post(manage_enrollment_learndash.ajax_url, data)
	.done(function( data ) {
		//console.error(data);

		if(typeof data.data == "object")
		jQuery.each(data.data, function(i, data) {
			var context = "#" + data.row_id;
			if( data.status == 1 )
				jQuery(context).closest("tr").attr("data-status", "checked");
			else
				jQuery(context).closest("tr").attr("data-status", "failed");

			if(typeof data.message == "string")
				jQuery(context).closest("tr").find(".status").text(data.message);
			else
				jQuery(context).closest("tr").find(".status").text("Invalid Response");

			jQuery(context).find("input[type=checkbox]").prop("disabled", false);
		});

		jQuery("#manage_enrollment_learndash_table tr[data-status=processing]").find(".status").text("Unknown Response");
		jQuery("#manage_enrollment_learndash_table tr[data-status=processing]").attr("data-status", "failed");
		jQuery("#manage_enrollment_learndash_table tr[data-status=processing] input[type=checkbox]").prop("disabled", false);

	})
	.fail(function(xhr, status, error) {
		//console.log(xhr, status, error);
	//	jQuery(context).closest("tr").find(".status").text("Request Failed");
		processing_enrollments.find(".status").text("Failed Request");
		processing_enrollments.attr("data-status", "failed");
		processing_enrollments.find("input[type=checkbox]").prop("disabled", false);
	})
	.always(function() {
		manage_enrollment_learndash_update_checked_count();

		setTimeout(function() {

			var waiting = jQuery("#manage_enrollment_learndash_table tr[data-status=waiting]");
			if(waiting.length > 0)
			manage_enrollment_learndash_check_enrollment( waiting );
			else if( selected == undefined )
			alert("All requests processed.");

		}, 500);
	});
}
function manage_enrollment_learndash_get_label(name, id) {
	if(id == "" || id == null)
		return "";

	switch(name) {
		case "course":
			return id + ". " + jQuery("#course_id option[value=" + id + "]").text();
		case "group":
			return id + ". " + jQuery("#group_id option[value=" + id + "]").text();
		default:
			return "";
	}
}
function manage_enrollment_learndash_get_enrolled_users() {
	var group_id = jQuery("#group_id").val();
	var course_id = jQuery("#course_id").val();

	if(group_id == "" && course_id == "")
		return;


	var data = {
		"action" : "manage_enrollment_learndash_get_enrolled_users",
		"course_id" : course_id,
		"group_id" : group_id,
	};
	jQuery.post(manage_enrollment_learndash.ajax_url, data)
	.done(function( data ) {
		//console.error(data);
		var old_sno = jQuery("#manage_enrollment_learndash_table tr:last .sno").text()*1;
		var sno = 0;
		if(typeof data.data == "object")
		jQuery.each(data.data, function(i, user_id) {
			var d = {
				user_id: user_id,
				group_id: data.group_id,
				course_id: data.course_id,
				status: "Enrolled"
			};
			manage_enrollment_learndash_add_row(d, old_sno + ++sno);
		});

		if(sno > 0)
			alert("Found " + sno + " users.");
		else
			alert("No users found");
	});
}
function grassblade_learndash_activate_plugin(url) {
	jQuery.get(url, function(data) {
		window.location.reload();
	});
	return false;
}

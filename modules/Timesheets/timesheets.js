if(typeof Object.prototype.toJSON != 'undefined')
	delete Object.prototype.toJSON;

var request_id = 0; // for JSON requests

TimesheetEditor = new function() {

var editor = this;

this.id = 'TimesheetEditor';
this.module = '';
this.editable = true;
this.form = null;
this.hours = {};
this.hours_order = [];
this.extra_order = [];
this.unique_index = 0;
this.totals = {
	hours: 0,
	approved: 0
};
this.date_start = null;
this.date_end = null;
this.tabindex = 30;
this.status_strings = {};
	
this.init = function(allow_edit, ts_period, date_start, date_end, rows, timesheet) {
	this.editable = !! allow_edit;
	this.period = ts_period;
	this.date_start = date_start;
	this.date_end = date_end;
	this.rows = rows;
	this.timesheet = timesheet;
};

this.setup = function() {
	var hasSubmit = this.form[this.form.id + '_submit'];
	if (hasSubmit) {
		if (this.timesheet.status == 'approved') {
			hasSubmit.style.display = 'none';
		}
		if (this.timesheet.status == 'submitted') hasSubmit.innerHTML = mod_string('LBL_UNSUBMIT_BUTTON_LABEL');
	}
	if (this.editable) {
		this.form.total_hours.disabled = true;
		if(this.form.date_starting)
			this.form.date_starting.value = this.date_start.print(cal_date_format);

        setTimeout(function() {TimesheetEditor.lock_elements();}, 1000);
		this.last_user = this.form.assigned_user_id.value;
	}
	this.status_strings = SUGAR.language.get('app_list_strings', 'booked_hours_status_dom');
	this.set_hours(this.rows);
	this.record_id = this.form.record.value;
	if (this.editable) {
		if(! this.record_id)
			this.start_date_changed(this.date_start, true);
		//this.setOnSubmitEvent();
	}
}

this.lock_elements = function() {
    var elems = ['status', 'date_starting', 'date_ending', 'name'];
    var elem = null;

    for (var i = 0; i < elems.length; i++) {
        elem = SUGAR.ui.getFormInput('DetailForm', elems[i]);
        if (elem)
            elem.setDisabled();
    }
};

this.retrieve_hours = function(date_start, date_end) {
	var user_id = this.form.assigned_user_id.value;
	if(! user_id.length) {
		this.clear_hours();
		return;
	}
	var params = 'timesheet_id=' + encodeURI(this.form.record.value);
	params += '&user_id=' + encodeURI(user_id);
	params += '&date_start=' + date_start + '&date_end=' + date_end;
	try {
		call_json_method(
			'Booking',
			'get_booked_hours',
			params,
			'hours_result',
			fetched_hours
		);
	} catch (e) {
		alert(e);
	}
}

this.on_user_changed = function() {
	/*if (!confirm(mod_string('CONFIRM_CHANGE_USER'))) {
		this.form.assigned_user_id.value = this.last_user;
		return false;
	}
	this.last_user = this.form.assigned_user_id.value;*/
	this.on_range_changed();
	return true;
}

this.on_range_changed = function() {
	var start = this.date_start.print('%Y-%m-%d');
	var end = this.date_end.print('%Y-%m-%d');
	this.retrieve_hours(start, end);
}

this.start_date_disabled = function(date) {
	switch(this.period) {
		case 1:
			return date.getDay() != 0;
		case 2:
			var wn = date.getWeekNumber();
			return date.getDay() != 0 || (wn % 2) != 0;
		case 3:
			return date.getDate() != 1 && date.getDate() != 15;
		case 4:
			return date.getDate() != 1;
	}
}

this.end_date_disabled = function(date) {
	var days = date.getMonthDays(date.getMonth());
	switch(this.period) {
		case 1:
			return date.getDay() != 6;
		case 2:
			var wn = date.getWeekNumber();
			return !(date.getDay() == 6 && (wn % 2==0));
		case 3:
			return date.getDate() != 14 && date.getDate() != days;
		case 4:
			return date.getDate() != days;
	}
}

this.change_period = function(offset) {
	var date = this.date_start;
	var new_date = date;
	switch(this.period) {
		case 1:
			new_date = new Date(date.getFullYear(), date.getMonth(), date.getDate()+7*offset);
			break;
		case 2:
			new_date = new Date(date.getFullYear(), date.getMonth(), date.getDate()+14*offset);
			break;
		case 3:
			var day = date.getDate();
			var month = date.getMonth();
			if(day == 1) {
				day = 1;
				if(offset < 0) month --;
			}
			else {
				day = 1;
				if(offset > 0) month ++;
			}
			new_date = new Date(date.getFullYear(), month, day);
			break;
		case 4:
			var month = date.getMonth();
			new_date = new Date(date.getFullYear(), month+offset, 1);
			break;
	}
	this.start_date_changed(new_date);
}

this.start_date_changed = function(date, no_reload) {
	this.date_start = new Date(date);
	this.form.date_starting.value = date.print(cal_date_format);
	var new_end = this.date_start;
	switch(this.period) {
		case 1:
			new_end = new Date(date.getFullYear(), date.getMonth(), date.getDate()+6);
			break;
		case 2:
			new_end = new Date(date.getFullYear(), date.getMonth(), date.getDate()+13);
			break;
		case 3:
			var day = date.getDate();
			if (day > 14) day = date.getMonthDays(date.getMonth());
			else day = 14;
			new_end = new Date(date.getFullYear(), date.getMonth(), day);
			break;
		case 4:
			var day = date.getMonthDays(date.getMonth());
			new_end = new Date(date.getFullYear(), date.getMonth(), day);
			break;
	}
	this.date_end = new_end;
	this.form.date_ending.value = new_end.print(cal_date_format);
	if (!no_reload)
		this.on_range_changed();
}

this.end_date_changed = function(date) {
	this.date_end = new Date(date);
	this.form.date_ending.value = date.print(cal_date_format);
	var new_start = this.date_end;
	switch(this.period) {
		case 1:
			new_start = new Date(date.getFullYear(), date.getMonth(), date.getDate()-6);
			break;
		case 2:
			new_start = new Date(date.getFullYear(), date.getMonth(), date.getDate()-13);
			break;
		case 3:
			var day = date.getDate();
			if (day < 15) day = 1;
			else day = 15;
			new_start = new Date(date.getFullYear(), date.getMonth(), day);
			break;
		case 4:
			new_start = new Date(date.getFullYear(), date.getMonth(), 1);
			break;
	}
	this.date_start = new_start;
	this.form.date_starting.value = new_start.print(cal_date_format);
	this.on_range_changed();
}

/* not used
this.get_spanned_dates = function() {
	var startDate = new Date(this.date_start);
	var endDate = this.date_end;
	while(startDate <= endDate) {
		dates.push(startDate);
		startDate = new Date(startDate.getTime() + 86400100);
	}
	return dates;
}*/

this.set_hours = function(rows) {
	if(typeof rows == 'object') {
		this.hours_order = deep_clone(rows.order);
		this.hours = deep_clone(rows.hours);
		if(! this.hours_order.length)
			this.hours_order = [];
		if(rows.extra && rows.extra.length)
			this.extra_order = deep_clone(rows.extra);
		else
			this.extra_order = [];
	}
	this.draw();
}

this.clear_hours = function() {
	this.hours_order = [];
	this.extra_order = [];
	this.hours = {};
	this.draw();
}

this.iter_hours = function(callback, extra) {
	var ord = extra ? this.extra_order : this.hours_order;
	for(var idx = 0; idx < ord.length; idx++) {
		callback(idx, this.hours[ord[idx]], extra);
	}
}

this.update_totals = function() {
	this.totals = { hours: 0, approved: 0 };
	this.iter_hours(
		function(idx, line) {
			var h = parseFloat(get_default(line.quantity, 0));
			editor.totals.hours += h;
			if(line.status == 'approved')
				editor.totals.approved += h;
		}
	);
	var total_f = document.getElementById('total_hours');
	if(total_f)
		total_f.innerHTML = stdFormatNumber(this.totals.hours);
}

this.parse_date_time = function(db_date, db_time) {
	var day, time;
	if(db_date) {
		day = db_date.split('-');
		if (day.length > 2) {
			var day_time = day[2].split(' ');
			day[2] = day_time[0];
			if (!db_time) {
				db_time = day_time[1];
			}
		}
	}
	else
		day = [0, 1, 0];
	if(db_time)
		time = db_time.split(':');
	else
		time = [0, 0, 0];
	var dt = new Date(day[0], day[1]-1, day[2], time[0], time[1], time[2]);
	return dt;
}

this.format_date = function(db_date) {
	var dt = this.parse_date_time(db_date);
	return dt.print(mod_string('LBL_LINE_DATE_FORMAT'));
}

this.format_time = function(db_date) {
	var dt = this.parse_date_time(db_date);
	return dt.print(cal_time_format);
}

this.draw = function() {
	var buttonsDiv = $('approve_buttons');
	if (buttonsDiv && this.timesheet.can_approve) {
		buttonsDiv.style.display = '';
	}
	var tbl = document.getElementById('hours_tbody');
	var cellCls = (tbl.parentNode.className == 'tabForm') ? 'dataLabel' : 'tabDetailViewDF';
	if(! tbl) return;
	while(tbl.rows.length)
		tbl.removeChild(tbl.rows[0]);
	var rowidx = 0;
	var lastdate = '';
	var drew = 0;
	var drawLine = function(idx, line, extra) {
		if(line.date_start != lastdate) {
			var row = tbl.insertRow(rowidx ++);
			var cell = row.insertCell(0);
			cell.className = cellCls;
			cell.colSpan = 6;
			cell.textAlign = 'left';
			var hdr_date = editor.format_date(line.date_start);
			var span = document.createElement('span');
			span.style.fontWeight = 'bold';
			span.appendChild(document.createTextNode(hdr_date));
			cell.appendChild(span);
			lastdate = line.date_start;
		}
		
		var row = tbl.insertRow(rowidx ++);
		var cellidx = 0;
				
		var cell = row.insertCell(cellidx ++);
		cell.className = cellCls;
		cell.style.paddingRight = '1em';
		cell.width = '15%';
		cell.style.textAlign = 'left';
		if (editor.timesheet.can_approve) {
			if(! line.check) {
				line.check = new SUGAR.ui.CheckInput('approve_'+line.id,
					{name: 'approve_hours[]', submit_value: line.id, init_value: line.status == 'approved'});
				line.check.render();
				SUGAR.ui.registerInput(editor.form, line.check);
			}
			cell.appendChild(line.check.elt);
			editor.form.appendChild(line.check.field);
			cell.appendChild(nbsp());
		}
		var time = editor.format_time(line.date_start);
		cell.appendChild(document.createTextNode(time));
		
		var cell = row.insertCell(cellidx ++);
		cell.className = cellCls;
		cell.style.paddingRight = '1em';
		cell.width = '10%';
		cell.style.textAlign = 'left';
        var hours = line.quantity_formatted;
		cell.appendChild(document.createTextNode(hours));
		
		cell = row.insertCell(cellidx ++);
		cell.className = cellCls;
		cell.width = '30%';
		img = createElement2('div', {className: 'input-icon theme-icon module-Booking'});
		cell.appendChild(img);
		cell.appendChild(nbsp());
		var lbl = document.createTextNode(line.name);
		if(! editor.editable) {
			var href = 'index.php?module=Booking&action=DetailView&record=' + line.id;
			lbl = createElement2('a', {href: href, className: 'tabDetailViewDFLink'}, lbl);
		}
		cell.appendChild(lbl);
		
		cell = row.insertCell(cellidx ++);
		cell.className = cellCls;
		cell.width = '30%';
		if(line.related_type && line.related_name) {
			img = createElement2('div', {className: 'input-icon theme-icon module-' + line.related_type});
			cell.appendChild(img);
			cell.appendChild(nbsp());
			lbl = document.createTextNode(line.related_name);
			if(! editor.editable) {
				var href = 'index.php?module=' + line.related_type + '&action=DetailView&record=' + line.related_id;
				lbl = createElement2('a', {href: href, className: 'tabDetailViewDFLink'}, lbl);
			}
			cell.appendChild(lbl);
		} else {
			cell.appendChild(document.createTextNode(mod_string('LBL_NO_RELATED')));
		}
		if(line.parent_name) {
			cell.appendChild(document.createElement('br'));
			cell.appendChild(nbsp(6));
			var sm = document.createElement('small');
			sm.appendChild(document.createTextNode(line.parent_name));
			cell.appendChild(sm);
		}

		cell = row.insertCell(cellidx ++);
		cell.className = cellCls;
		cell.width = '10%';
		//cell.style.textAlign = 'center';
		cell.noWrap = 'nowrap';
		var stat = editor.status_strings[line.status];
		if (!stat) stat = '';
		cell.appendChild(document.createTextNode(stat));

		if(editor.editable) {
			cell = row.insertCell(cellidx ++);
			cell.className = cellCls;
			cell.width = '10%';
			cell.style.textAlign = 'right';
			cell.noWrap = 'nowrap';
			var img, lbl;
			var link = document.createElement('a');
			if(! extra) {
				img = get_icon_image('remove');
				lbl = app_string('LBL_REMOVE');
				link.onclick = function() {
					editor.remove_hours(line.id);
					return false;
				}
			}
			else {
				img = get_icon_image('insert');
				lbl = app_string('LBL_INS');
				link.onclick = function() {
					editor.add_extra_hours(line.id);
					return false;
				}
			}
			link.className = 'listViewTdToolsS1';
			link.href = '#';
			link.appendChild(img);
			link.appendChild(document.createTextNode(' '+img.title));
			cell.appendChild(link);
		}
	};
	this.iter_hours(drawLine);
	if(! tbl.rows.length) {
		var row = tbl.insertRow(rowidx ++);
		var cell = row.insertCell(0);
		cell.className = cellCls;
		cell.style.textAlign = 'center';
		cell.style.fontStyle = 'italic';
		cell.appendChild(document.createTextNode(mod_string('LBL_NO_BOOKED_HOURS')));
		cell.colSpan = 6;
	}
	
	tbl = document.getElementById('extra_hours_tbody');
	if(! tbl) return;
	while(tbl.rows.length)
		tbl.removeChild(tbl.rows[0]);
	rowidx = 0;
	lastdate = '';
	this.iter_hours(drawLine, true);
	var ext_div = document.getElementById('extra_hours_div');
	if(ext_div)
		ext_div.style.display = tbl.rows.length ? 'block' : 'none';
	this.update_totals();
}

this.add_extra_hours = function(id) {
	var found;
	for(var idx = 0; idx < this.extra_order.length; idx++) {
		if(this.extra_order[idx] == id) {
			found = idx;
			break;
		}
	}
	if(! isset(found))
		return;
	this.extra_order.splice(found, 1);
	this.add_in_order(id);
	this.draw();
}

this.remove_hours = function(id) {
	var found;
	for(var idx = 0; idx < this.hours_order.length; idx++) {
		if(this.hours_order[idx] == id) {
			found = idx;
			break;
		}
	}
	if(! isset(found))
		return;
	this.hours_order.splice(found, 1);
	this.add_in_order(id, true);
	this.draw();
}

this.add_in_order = function(id, extra) {
	var start = this.parse_date_time(this.hours[id].date_start, this.hours[id].time_start);
	var target = extra ? this.extra_order : this.hours_order;
	var idx = 0;
	for(; idx < target.length; idx++) {
		if(! this.hours[target[idx]])
			continue;
		var dt = this.parse_date_time(this.hours[target[idx]].date_start, this.hours[target[idx]].time_start);
		if(dt > start)
			break;
	}
	target.splice(idx, 0, id);
}

this.beforeSubmitForm = function(form) {
	TimesheetEditor.setFormValues(form);
}

this.validateForm = function() {
    var form = document.DetailForm;
    if (form.submit_timesheet)
        form.submit_timesheet.value = '';
    return SUGAR.ui.sendForm(form, {'record_perform':'validate'});
}

this.setFormValues = function(form) {
    if (form.line_items) {
        var ret = { order: this.hours_order, hours: this.hours, extra: this.extra_order };
        form.line_items.value = JSON.stringify(ret);
        form.hours_data_updated.value = '1';
    }
}

this.submit_unsubmit = function() {
	if (this.timesheet.status == 'submitted') this.form.submit_timesheet.value = '2';
	else this.form.submit_timesheet.value = '1';
    return SUGAR.ui.sendForm(this.form, {'record_perform':'save'});
}

this.approveSelected = function() {
	this.form.approve_action.value='approveSelected';
    SUGAR.ui.sendForm(this.form, {'record_perform':'save'});
	return false;
}

this.approveAll = function() {
	this.form.approve_action.value='approveAll';
    SUGAR.ui.sendForm(this.form, {'record_perform':'save'});
	return false;
}

this.rejectAll = function() {
	this.form.approve_action.value='rejectAll';
    SUGAR.ui.sendForm(this.form, {'record_perform':'save'});
	return false;
}

this.check_save = function() {
	var date1 = this.date_start.print('%Y-%m-%d')
	var date2 = this.date_end.print('%Y-%m-%d')
	try {
		call_json_method(
			'Timesheets',
			'check_date_range',
			'record='+encodeURI(this.form.record.value)+'&date_starting='+encodeURI(date1) + '&date_ending='+encodeURI(date2) + '&assigned_user_id=' + encodeURI(this.form.assigned_user_id.value),
			'check_result',
			range_checked
		);
	} catch (e) {
		alert(e);
	}
	return false;
}

this.final_save = function() {
	if(this.editable) {
		var ret = { order: this.hours_order, hours: this.hours, extra: this.extra_order };
		this.form.hours_data.value = JSON.stringify(ret);
		this.form.hours_data_updated.value = '1';
	}
	this.form.submit();
}

}(); // TimesheetEditor


function fetched_hours(doc, result) {
	TimesheetEditor.set_hours(json_objects['hours_result']);
}

function range_checked() {
	if (json_objects['check_result'])
		TimesheetEditor.final_save();
	else
		alert(mod_string('MSG_DUPLICATE_PERIOD'));
}

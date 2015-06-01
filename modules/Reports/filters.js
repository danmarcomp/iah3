/*****************************************************************************
 * The contents of this file are subject to The Long Reach Corporation
 * Software License Version 1.0 ("License"); You may not use this file except
 * in compliance with the License. You may obtain a copy of the License at
 * <http://www.thelongreach.com/swlicense.html>
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations under
 * the License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) The Long Reach Corporation copyright notice,
 * (ii) the "Powered by SugarCRM" logo, and
 * (iii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is:
 *    Info At Hand Add-on Module to SugarCRM Open Source project.
 * The Initial Developer of this Original Code is The Long Reach Corporation
 * and it is Copyright (C) 2004-2007 by The Long Reach Corporation;
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2005 SugarCRM, Inc.;
 * All Rights Reserved.
*****************************************************************************/

// create singleton instance
filter_editor = new function() {

this.filters = [];
var the_editor = this;

// setup data
this.filter_types = {
	'int': 'numeric', 'num': 'numeric', 'float': 'numeric', 'currency': 'numeric',
	'email': 'text', 'phone': 'text', 'relate': 'text', 'char': 'text', 'varchar': 'text', 'text': 'text',
	'date': 'date', 'datetime': 'date',
	'name': 'name',
	'enum': 'enum',
	'multienum' : 'multienum',
	'bool': 'bool',
	'user_name': 'user',
	'assigned_user_name': 'user'
};

this.filter_modes = {
	numeric: [
		{ name: 'equals', fields: { value: { type: 'num_text' } } },
		{ name: 'lt', fields: { value: { type: 'num_text' } } },
		{ name: 'gt', fields: { value: { type: 'num_text' } } },
		{ name: 'between', fields: {
			min: { type: 'num_text', suffix: mod_string('LBL_AND') }, max: { type: 'num_text' } } },
		{ name: 'not_equal', fields: { value: { type: 'num_text' } } }
	],
	text: [
		{ name: 'equals', fields: { value: { type: 'text' } } },
		{ name: 'contains', fields: { value: { type: 'text' } } },
		{ name: 'starts_with', fields: { value: { type: 'text' } } },
		{ name: 'ends_with', fields: { value: { type: 'text' } } },
		{ name: 'not_equal', fields: { value: { type: 'text' } } },
		{ name: 'not_contain', fields: { value: { type: 'text' } } }
	],
	name: [
		//{ name: 'id_equals', fields: { id: { type: 'popup' } } },
		{ name: 'equals', fields: { value: { type: 'text' } } },
		{ name: 'contains', fields: { value: { type: 'text' } } },
		{ name: 'starts_with', fields: { value: { type: 'text' } } },
		{ name: 'ends_with', fields: { value: { type: 'text' } } },
		{ name: 'not_equal', fields: { value: { type: 'text' } } },
		{ name: 'not_contain', fields: { value: { type: 'text' } } }
	],
	date: [
		{ name: 'on_date', fields: { value: { type: 'date' } } },
		{ name: 'before_date', fields: { value: { type: 'date' } } },
		{ name: 'after_date', fields: { value: { type: 'date' } } },
		{ name: 'between_dates', fields: {
			min: { type: 'date', suffix: mod_string('LBL_AND') }, max: { type: 'date' } } },
		{ name: 'not_on_date', fields: { value: { type: 'date' } } },
		{ name: 'period', fields: {
			num_periods : { type: 'num_text' },
			period : { type: 'date_period_select', suffix: mod_string('LBL_STARTING') },
			offset : { type: 'num_text', suffix: mod_string('LBL_PERIODS') },
			offset_type : { type: 'before_after_select', suffix: mod_string('LBL_TODAY') } } }
	],
	bool: [
		{ name: 'true', fields: {} },
		{ name: 'false', fields: {} }
	],
	multienum: [
		{ name: 'any_of', fields: { values: { type: 'multi_select' } } },
		{ name: 'all_of', fields: { values: { type: 'multi_select' } } },
		{ name: 'equals', fields: { values: { type: 'multi_select' } } }
	],
	'enum': [
		{ name: 'equals', fields: { value: { type: 'select' } } },
		{ name: 'one_of', fields: { values: { type: 'multi_select' } } }
	],
	user: [
		{ name: 'current_user', fields: {} },
		{ name: 'other_users', fields: {} },
		{ name: 'equals', fields: { value: { type: 'user_select' } } },
		{ name: 'one_of', fields: { values: { type: 'user_multi_select' } } }
	]
};


this.init = function(init_data) {
	if(! init_data) init_data = {};
	if(typeof(init_data.filters) != 'undefined')
		this.filters = init_data.filters;
}

this.show_filters = function() {
	var table = document.getElementById('reportFiltersTable');
	var rowCount = 0;
	for(var i = table.rows.length - 1; i >= rowCount; i--)
		table.deleteRow(i);
	var filter_rows = {};
	for(var idx in this.filters) {
		var filter = this.filters[idx];
		var cellCount = 0;
		if(report_editor.run_method == 'fixed' && typeof(filter.user_config) != 'undefined' && filter.user_config == 'false')
			continue;

		var tr = table.insertRow(rowCount++);
		var leftTd = tr.insertCell(cellCount++);
		leftTd.className = "dataLabel"; leftTd.width = "20%";
		var name = report_editor.get_field_name(filter.field);
		var srcname = report_editor.get_source_name(report_editor.fields[filter.field].source);
		leftTd.appendChild(document.createTextNode(srcname+': '+name));
			
		var filter_editor_cell = tr.insertCell(cellCount++);
		filter_editor_cell.className = "dataField"; filter_editor_cell.width = "65%";
		filter_editor_cell.appendChild(this.make_filter_editor_table(idx, filter));
		
		if(report_editor.run_method == 'interactive') {
			filter_editor_cell.width = "50%";
			var td = tr.insertCell(cellCount++);
			td.className = "dataLabel"; td.width = "15%";
			td.style.textAlign = 'center'; td.noWrap = true;
			chk = document.createElement('input');
			chk.type = 'checkbox'; chk.className = 'checkbox';
			chk.filter = idx;
			chk.onclick = function() { the_editor.filters[this.filter].user_config = this.checked ? 'true' : 'false' };
			td.appendChild(chk);
			if(typeof(filter.user_config) == 'undefined' || filter.user_config == 'true')
				chk.checked = true;
			td.appendChild(document.createTextNode(' '+mod_string('LBL_USER_CONFIG')+' '));
		}
		
		var rightTd = tr.insertCell(cellCount++);
		rightTd.className = "dataLabel"; rightTd.width = "10%";
		remove = document.createElement('input');
		remove.type = 'button'; remove.className = 'button';
		remove.filter = idx; remove.value = mod_string('LBL_REMOVE_LABEL');
		remove.onclick = function() { the_editor.remove_filter(this) };
		rightTd.appendChild(remove);
		
		filter_rows[idx] = tr;
	}
	if(!rowCount) {
		var tr = table.insertRow(rowCount++);
		var td = tr.insertCell(0);
		td.className = "dataLabel";
		td.appendChild(document.createTextNode(mod_string('LBL_NO_FILTERS')));
	}
}

this.make_filter_editor_table = function(filter_idx, filter) {
	var table = document.createElement('table');
	var rowCount = 0;
	table.border = 0; table.cellpadding = 0; table.cellspacing = 0; table.width = '100%';
	var tr = table.insertRow(0);
	var td = tr.insertCell(0);
	td.setAttribute('nowrap', 'nowrap');
	
	var modeSelect = document.createElement('select');
	modeSelect.filter = filter_idx;
	var curMode = null;
	var curIdx = -1;
	for(var idx in this.filter_modes[filter.type]) {
		var mode = this.filter_modes[filter.type][idx];
		var opt = document.createElement('option');
		opt.value = mode.name;
		opt.text = mod_string('LBL_FILTER_'+mode.name.toUpperCase());
		try {
			modeSelect.add(opt, null);
		} catch(ex) {
			modeSelect.add(opt);
		}
		if(filter.mode == mode.name) {
			opt.selected = true;
			curMode = mode;
		}
	}
	modeSelect.onchange = this.set_filter_mode;
	td.appendChild(modeSelect);
	td.appendChild(document.createTextNode('\u00a0'));

	if(curMode == null) {
		curMode = this.filter_modes[filter.type][0];
		filter.mode = curMode.name;
	}
	
	for(var name in curMode.fields) {
		var fld = curMode.fields[name];
		if(fld.type == 'text' || fld.type == 'num_text') {
			var input = document.createElement('input');
			input.filter = filter_idx; input.name = name; 
			input.size = (fld.type == 'text') ? 10 : 5;
			input.onchange = this.set_filter_field;
			if(typeof(filter[name]) != 'undefined' && filter[name] != null)
				input.value = filter[name];
			td.appendChild(input);
		}
		else if(fld.type == 'date') {
			var input = document.createElement('input');
			input.filter = filter_idx; input.name = name; input.size = 15;
			//input.onchange = this.set_filter_field;
			input.readOnly = true;
			input.id = 'filter'+filter_idx+'_'+name;
			if(typeof(filter[name]) != 'undefined' && filter[name] != null) {
				var d = new Date(replaceAll(filter[name], '-', '/'));
				if(d)  input.value = d.print(cal_date_format); // Date.print defined in jscalendar/calendar.js
			}
			td.appendChild(input);
			td.appendChild(document.createTextNode('\u00a0'));
			cal_img = document.createElement('img');
			cal_img.src = 'themes/'+user_theme+'/images/jscalendar.gif';
			cal_img.width = 16; cal_img.height = 18; cal_img.alt = '';
			cal_img.id = input.id+'_trigger'; cal_img.align = 'absmiddle';
			td.appendChild(cal_img);
			Calendar.setup ({
				inputFieldObj : input, ifFormat : cal_date_format,
				onClose: function(cal) { cal.hide(); the_editor.set_filter_date_field(cal.inputField, cal.date); },
				showsTime : false, buttonObj : cal_img, singleClick : true, step : 1,
				enableReadOnly: true
			});
			
		}
		else if(fld.type == 'select' || fld.type == 'multi_select'
				|| fld.type == 'user_select' || fld.type == 'user_multi_select'
				|| fld.type == 'before_after_select' || fld.type == 'date_period_select') {
			var select = document.createElement('select');
			select.filter = filter_idx; select.name = name;
			var selected = {};
			
			if(fld.type == 'multi_select' || fld.type == 'user_multi_select') {
				select.size = 4;
				select.multiple = true;
				select.style.verticalAlign = 'middle';
				if(typeof(filter[name]) == 'object')
					for(var idx in filter[name]) {
						val = filter[name][idx];
						if(val == null) val = '';
						selected[val] = true;
					}
			}
			else if(typeof(filter[name]) != 'undefined')
				selected[filter[name]] = true;
			select.onchange = this.set_filter_select_field;
			
			var report_fld = report_editor.fields[filter.field];

			if(fld.type == 'before_after_select') {
				report_fld.options_keys = ['before', 'after'];
				report_fld.options_values = [mod_string('LBL_BEFORE'), mod_string('LBL_AFTER')];
			}
			
			if(fld.type == 'date_period_select') {
				report_fld.options_keys = ['day', 'week', 'month', 'quarter', 'fiscal_quarter', 'year', 'fiscal_year'];
				report_fld.options_values = [mod_string('LBL_DAY'), mod_string('LBL_WEEK'), mod_string('LBL_MONTH'), mod_string('LBL_QUARTER'), mod_string('LBL_FISCAL_QUARTER'),  mod_string('LBL_YEAR'), mod_string('LBL_FISCAL_YEAR')];
			}

			if(fld.type == 'user_select' || fld.type == 'user_multi_select') {
				report_fld.options_keys = []; report_fld.options_values = [];
				var users = GLOBAL_REGISTRY.all_users;
				for(var idx in users) {
					report_fld.options_keys.push(users[idx].id);
					report_fld.options_values.push(users[idx].user_name);
				}
			}
			
			var selval = null;
			for(var idx = 0; idx < report_fld.options_keys.length; idx++) {
				var opt = document.createElement('option');
				opt.value = report_fld.options_keys[idx];
				opt.text = report_fld.options_values[idx];
				try {
					select.add(opt, null);
				} catch(ex) {
					select.add(opt);
				}
				if(selected[opt.value]) {
					opt.selected = true;
					selval = opt.value;
				}
			}
			if(select.multiple) { // work around a bug in IE by generating raw html..
				var html = '<select multiple="multiple" size="4" name="'+name+'" onfocus="this.filter = '+filter_idx+'; this.onchange = filter_editor.set_filter_select_field;" style="vertical-align:middle">';
				for(var idx = 0; idx < select.options.length; idx++) {
					opt = select.options[idx];
					sel = selected[opt.value] ? ' selected="selected"' : '';
					html += '<option value="'+opt.value+'"'+sel+'>'+opt.text+'</option>';
				}
				html += '</select>';
				select = document.createElement('span');
				select.innerHTML = html;
			}
			else {
				// save default selection in filter
				if(typeof(filter[name]) == 'undefined') {
					if(selval == null && report_fld.options_keys.length)
						selval = report_fld.options_keys[0];
					if(selval != null)
						filter[name] = selval;
				}
			}
			td.appendChild(select);
		}
		else if(fld.type == 'popup') {
			var input = document.createElement('input');
			input.filter = filter_idx; input.name = name; input.size = 25;
			input.onchange = this.set_filter_field;
			//...
			td.appendChild(input);
		}
		if(typeof(fld.suffix) != 'undefined')
			td.appendChild(document.createTextNode(' '+fld.suffix+' '));
		else
			td.appendChild(document.createTextNode('\u00a0'));
	}
	return table;
}

this.set_filter_mode = function() {
	the_editor.filters[this.filter].mode = this.value;
	the_editor.show_filters();
}
this.set_filter_date_field = function(obj, val) {
	this.filters[obj.filter][obj.name] = val.print('%Y-%m-%d');
}
this.set_filter_select_field = function() {
	var value = this.value;
	if(this.multiple) {
		value = [];
		for(var idx = 0; idx < this.options.length; idx++)
			if(this.options[idx].selected)
				value.push(this.options[idx].value);
	}
	the_editor.filters[this.filter][this.name] = value;
}
this.set_filter_field = function() {
	the_editor.filters[this.filter][this.name] = this.value;
}

this.add_filter = function(btn) {
	var field = report_editor.fields[btn.field];
	var filt_type = this.filter_types[field.type];
	if(typeof(filt_type != 'undefined')) {
		filter = { 'field': btn.field, 'type': filt_type };
		this.filters[this.filters.length] = filter;
		this.show_filters();
	}
}

this.remove_filters_by_field = function(fldId) {
	new_filters = [];
	for(var idx = 0; idx < this.filters.length; idx++) {
		if(this.filters[idx].field != fldId)
			new_filters.push(this.filters[idx]);
	}
	this.filters = new_filters;
	this.show_filters();
}

this.remove_filter = function(btn) {
	this.filters.splice(btn.filter, 1);
	this.show_filters();
}

this.pre_submit_form = function(form) {
	form._filters.value = JSON.stringify(this.filters);
	return true;
}

}; // end filter_editor

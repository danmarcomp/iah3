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

// jsolait library prevents Objects from being used as hashes
delete Object.prototype.toJSON;

// create singleton instance
report_editor = new function() {

// these are filled in by the calling page
this.all_sources = {};
this.primary_source;
this.sources = {};
this.sources_order = [];
this.fields = {};
this.fields_order = [];
this.totals = {};
this.totals_order = [];
this.sort_order = [];
this.run_method = 'fixed';
this.chart_type = '';
this.chart_title = '';
this.chart_description = '';
this.chart_series = '';
this.svg_charts = false;

// used at runtime
this.fields_select;
this.field_editor_cell;
this.source_rows;
this.source_aliases = {};
this.currentField = null;
this.advancedBrowser = (navigator.userAgent.toLowerCase().indexOf('firefox') != -1);
var the_editor = this;

function get_display_formats(field) {
	var fmts = [''];
	var format = field.type;
	if(!blank(field.format))
		format = field.format;
	switch(format) {
		case 'float':
		case 'double':
		case 'currency':
			fmts.push('rounded');
		case 'num':
		case	'int':
			fmts.push('thousands', 'millions');
			break;
		case 'user_name':
		case 'assigned_user_name':
			fmts.push('full_name');
			break;
		case 'date':
		case 'dateonly':
		case 'datetime':
			fmts.push('days_past', 'days_future');
			break;
	}
	return fmts;
}

function html_unescape(s) {
	if(typeof(s) != 'string')
		return s;
	return replaceAll(s, '&amp;', '&');
}
// Array.indexOf may not be available
function in_array(val, arr) {
	for(var i = 0; i < arr.length; i++)
		if(arr[i] == val)
			return true;
	return false;
}

this.init = function(no_edit, init_data) {
	var data = ['primary_source', 'sources', 'sources_order',
		'fields', 'fields_order', 'totals', 'totals_order', 'sort_order',
		'chart_type', 'chart_title', 'chart_rollover', 'chart_description', 'chart_series',
		'chart_options'];
	if(blank(no_edit))
		data.push('run_method');
	if(! init_data) init_data = {};
	for(var idx = 0; idx < data.length; idx++) {
		var v = data[idx];
		var reg_v = (v == 'fields' ? 'report_fields' : v);
		if(isdef(init_data[reg_v]))
			this[v] = init_data[reg_v];
	}
	for(var idx = 0; idx < this.sources_order.length; idx++) {
		var src = this.sources[this.sources_order[idx]];
		if(src.parent && src.field_name)
			this.source_aliases[src.parent+'.'+src.field_name] = this.sources_order[idx];
	}
	if (! this.chart_options || typeof(this.chart_options) != 'object') {
		this.chart_options = {};
	}
	filter_editor.init(init_data);
}

this.set_run_method = function(newmode, update) {
	this.run_method = newmode;
	if(! blank(update))
		filter_editor.show_filters();
}

this.set_primary_module = function(module) {
	this.sources = {};
	var defn = this.all_sources[module];
	var data = {type: defn.type, module: module, 'name_translated': defn.name_translated, 'required': 'true'};
	var name = module.substring(0, 4).toLowerCase();
	if(name == 'case') name = 'cas';
	this.sources[name] = data;
	this.primary_source = name;
	this.sources_order = [this.primary_source];
	this.fields = {};
	this.fields_order = [];
	this.sort_order = [];
	this.filters = [];
	filter_editor.filters = [];
	this.show_sources();
}

this.get_source_name = function(name) {
	var src = this.sources[name];
	var src_name = src.name_translated;
	if(src.link_type == 'one')
		src_name = this.sources[src.parent].name_translated + ' - ' + src_name;
	return src_name;
}

this.get_field_name = function(name) {
	var fld = this.fields[name];
	if(!blank(fld.display_name))
		return html_unescape(fld.display_name);
	var name = html_unescape(fld.name_translated);
	if(!blank(fld.format_translated))
		name += ' ('+html_unescape(fld.format_translated)+')';
	return name;
}

this.get_total_name = function(name) {
	var sum = this.totals[name];
	if(!blank(sum.display_name))
		return sum.display_name;
	return sum.name_translated;
}

this.show_sources = function() {
	var table = document.getElementById('reportFieldsTable');
	var rowCount = 0;
	for(var i = table.rows.length - 1; i >= rowCount; i--)
		table.deleteRow(i);
	this.source_rows = {};
	for(var idx in this.sources_order) {
		name = this.sources_order[idx];
		if(this.sources[name].type == 'link' && this.sources[name].link_type == 'one')
			continue; // hide links to single objects
		
		var tr = table.insertRow(rowCount++);
		leftTd = tr.insertCell(0);
		leftTd.className = "dataLabel"; leftTd.width = "27%";
		leftTd.appendChild(this.make_source_editor_table(name));
		
		if(idx == 0) {
			var midTd = tr.insertCell(1);
			midTd.className = "dataLabel"; midTd.width = "36%";
			midTd.appendChild(this.make_fields_select());
			this.update_fields_select();
			this.fields_select.selectedIndex = -1;
			
			this.field_editor_cell = tr.insertCell(2);
			this.field_editor_cell.className = "dataLabel"; this.field_editor_cell.width = "37%";
			this.field_editor_cell.style.textAlign = 'right';
			this.field_editor_cell.appendChild(this.make_field_editor_table(null, 0));
		}
		
		this.source_rows[name] = tr;
	}
	if(rowCount) {
		// must set rowSpan after creating the rows
		midTd.rowSpan = rowCount;
		this.field_editor_cell.rowSpan = rowCount;
	}
	else {
		var tr = table.insertRow(rowCount++);
		var td = tr.insertCell(0);
		td.className = "dataLabel";
		td.appendChild(document.createTextNode(mod_string('LBL_NO_SOURCES')));
	}
	filter_editor.show_filters();
	this.show_sort_order();
	this.show_chart_options();
}

this.make_source_editor_table = function(srcname) {
	var table = document.createElement('table');
	var rowCount = 0;
	table.border = 0; table.cellpadding = 0; table.cellspacing = 0; table.width = '100%';
	
	var tr = table.insertRow(rowCount++);
	var td = tr.insertCell(0);
	td.className = 'dataLabel'; td.colSpan = 2;
	var hdr = document.createElement('h5');
	hdr.className = 'dataLabel';
	hdr.style.paddingBottom = '0';
	hdr.appendChild(document.createTextNode(this.get_source_name(srcname)));
	td.appendChild(hdr);
	
	if(this.sources[srcname].parent) {
		tr = table.insertRow(rowCount++);
		td = tr.insertCell(0);
		td.className = 'dataLabel';
		td.appendChild(document.createTextNode(mod_string('LBL_PARENT')));
		td = tr.insertCell(1);
		td.className = 'dataField';
		parent_src = this.sources[this.sources[srcname].parent];
		td.appendChild(document.createTextNode(parent_src.name_translated));
	}
	
	if(this.sources[srcname].type != 'primary' && this.sources[srcname].type != 'union') {
		tr = table.insertRow(rowCount++);
		td = tr.insertCell(0);
		td.className = 'dataLabel'; td.noWrap = true;
		td.appendChild(document.createTextNode(mod_string('LBL_DISPLAY')));
		td = tr.insertCell(1);
		td.className = 'dataField';
		var displayStyle = document.createElement('select');
		displayStyle.target = 'display'; displayStyle.source = srcname;
		displayStyle.onchange = this.source_value_setter;
		var styles = ['joined', 'nested'];
		for(var idx = 0; idx < styles.length; idx++) {
			var style = styles[idx];
			var opt = document.createElement('option');
			opt.value = style;
			opt.text = mod_string('LBL_ROWS_'+style.toUpperCase());
			try {
				displayStyle.add(opt, null);
			} catch(ex) {
				displayStyle.add(opt);
			}
			if(this.sources[srcname].display == style)
				opt.selected = true;
		}
		td.appendChild(displayStyle);
		
		/*tr = table.insertRow(rowCount++);
		td = tr.insertCell(0);
		td.className = 'dataLabel'; td.width = "30%";
		td.appendChild(document.createTextNode(mod_string('LBL_REQUIRED')));
		td = tr.insertCell(1);
		td.className = 'dataField'; td.width = "70%";
		var required = document.createElement('input');
		required.type = 'checkbox'; required.className = 'checkbox';
		required.target = 'required'; required.source = srcname;
		required.onclick = this.source_value_setter;
		if(this.sources[srcname].type == 'primary') {
			required.disabled = true;
			required.checked = true;
		}
		else
			required.checked = (this.sources[srcname].required == 'true');
		td.appendChild(required);*/
	}
	
	tr = table.insertRow(rowCount++);
	td = tr.insertCell(0);
	td.className = "dataLabel"; td.colSpan = 2; td.noWrap = true;
	var addFields = document.createElement('input');
	addFields.type = 'button'; addFields.className = 'button';
	addFields.source = srcname; addFields.value = mod_string('LBL_ADD_FIELDS_LABEL');
	addFields.onclick = function() { the_editor.add_fields(this, 'fields') };
	td.appendChild(addFields);
	
	td.appendChild(document.createTextNode(' '));
	var addTotals = document.createElement('input');
	addTotals.type = 'button'; addTotals.className = 'button';
	addTotals.source = srcname; addTotals.value = mod_string('LBL_ADD_TOTALS_LABEL');
	addTotals.onclick = function() { the_editor.add_fields(this, 'totals') };
	td.appendChild(addTotals);
	
	if(this.sources[srcname].type == 'primary') {
		var have_related = false;
		for(var name in this.sources) {
			var src = this.sources[name];
			if(src.type == 'link' && src.link_type != 'one' && src.parent == srcname)
				have_related = true;
		}
		if(! have_related) {
			//td.appendChild(document.createTextNode(' '));
			td.appendChild(document.createElement('p'));
			var addRelated = document.createElement('input');
			addRelated.type = 'button'; addRelated.className = 'button';
			addRelated.source = srcname; addRelated.value = mod_string('LBL_ADD_RELATED_LABEL');
			addRelated.onclick = function() { the_editor.add_related_source(this) };
			td.appendChild(addRelated);
		}
	}
	else if (this.sources[srcname].type != 'union') {
		td.appendChild(document.createTextNode(' '));	
		var remove = document.createElement('input');
		remove.type = 'button'; remove.className = 'button';
		remove.source = srcname; remove.value = mod_string('LBL_REMOVE_LABEL');
		remove.onclick = function() { the_editor.remove_related_source(this.source) };
		td.appendChild(remove);
	}
	
	return table;

}

this.make_fields_select = function() {
	this.fields_select = document.createElement('select');
	this.fields_select.size = 14; this.fields_select.multiple = true;
	this.fields_select.style.width = '100%';
	if(this.advancedBrowser)
		this.fields_select.style.height = '100%';
	this.fields_select.onchange = function() { the_editor.on_select_fields(this); }
	
	var table = document.createElement('table');
	var rowCount = 0;
	table.border = 0; table.cellpadding = 0; table.cellspacing = 0; table.width = '80%';
	
	var tr = table.insertRow(rowCount++);
	td = tr.insertCell(0);
	td.className = 'dataField'; td.width = '85%';
	td.appendChild(this.fields_select);
	
	td = tr.insertCell(1);
	td.className = 'dataField'; td.width = '15%'; td.style.verticalAlign = 'top';
	var img = document.createElement('img');
	img.src = 'themes/'+user_theme+'/images/uparrow_big.gif';
	img.border = 0; img.width = 16; img.height = 16; img.alt = '';
	img.style.marginBottom = '1px';
	img.onclick = function() { the_editor.move_fields('up'); }
	//img.style.cursor = 'pointer';
	td.appendChild(img);
	img = document.createElement('img');
	img.src = 'themes/'+user_theme+'/images/downarrow_big.gif';
	img.border = 0; img.width = 16; img.height = 16; img.alt = '';
	img.style.marginTop = '1px';
	img.onclick = function() { the_editor.move_fields('down'); }
	//img.style.cursor = 'pointer';
	td.appendChild(document.createElement('br'));
	td.appendChild(img);
	
	return table;
}

this.on_select_fields = function(lst) {
	var selgrp = '';
	// restrict selection to items in a group
	for(var idx = 0; idx < lst.childNodes.length; idx++) {
		var optgrp = lst.childNodes[idx];
		for(var oidx = 0; oidx < optgrp.childNodes.length; oidx++) {
			var option = optgrp.childNodes[oidx];
			if(option.selected) {
				if(selgrp == '') selgrp = optgrp.label;
				else if(selgrp != optgrp.label)
					option.selected = false;
			}
		}
	}
	this.update_field_controls(lst);
}

this.update_fields_select = function(selectFields) {
	var selected = {};
	if(isset(selectFields)) {
		for(var idx = 0; idx < selectFields.length; idx++)
			selected[selectFields[idx]] = true;
	}
	else {
		for(var idx = 0; idx < this.fields_select.options.length; idx++) {
			var fldid = this.fields_select.options[idx].value;
			if(this.fields_select.options[idx].selected)
				selected[fldid] = true;
		}
	}
	this.fields_select.options.length = 0;
	while(this.fields_select.childNodes.length > 0)
		this.fields_select.removeChild(this.fields_select.firstChild);
	
	var joinedRows = true;
	var multiSource = false;
	for(var idx = 0; idx < this.sources_order.length; idx++) {
		var src = this.sources[this.sources_order[idx]];
		if(src.type != 'primary') {
			if(src.display == 'nested' || (src.type == 'link' && src.link_type != 'one'))
				joinedRows = false;
			multiSource = true;
		}
	}
	var optgroups = {};
	var optgrplist = [];
	var grp_grouped_fields = null;
	var grp_normal_fields = null;
	var grp_hidden_fields = document.createElement('optgroup');
	grp_hidden_fields.label = mod_string('LBL_QUERY_ONLY_FIELDS');
	var grp_totals = document.createElement('optgroup');
	grp_totals.label = mod_string('LBL_REPORT_TOTALS');
	for(var idx = 0; idx < this.sources_order.length; idx++) {
		var srcname = this.sources_order[idx];
		var src = this.sources[srcname];
		if(joinedRows) {
			if(grp_grouped_fields == null) {
				grp_grouped_fields = document.createElement('optgroup');
				grp_grouped_fields.label = mod_string('LBL_GROUPED_FIELDS');
				grp_normal_fields = document.createElement('optgroup');
				grp_normal_fields.label = mod_string('LBL_REPORT_FIELDS');
				optgrplist.push(grp_grouped_fields);
				optgrplist.push(grp_normal_fields);
			}
			optgroups[srcname] = { grouped: grp_grouped_fields, normal: grp_normal_fields, query_only: grp_hidden_fields };
		}
		else {
			var g = document.createElement('optgroup');
			g.label = mod_string('LBL_GROUPED_FIELDS');
			if(multiSource) g.label += ' - ' + src.name_translated;
			optgrplist.push(g);
			var s = document.createElement('optgroup');
			s.label = mod_string('LBL_REPORT_FIELDS');
			if(multiSource) s.label += ' - ' + src.name_translated;
			optgrplist.push(s);
			optgroups[srcname] = { grouped: g, normal: s, query_only: grp_hidden_fields };
		}
	}
	optgrplist.push(grp_totals);
	optgrplist.push(grp_hidden_fields);
	
	for(var idx = 0; idx < this.fields_order.length; idx++) {
		var name = this.fields_order[idx];
		var field = this.fields[name];
		var opt = document.createElement('option');
		opt.value = name;
		var disp_name = this.get_field_name(name);
		
		if(multiSource && (joinedRows || field.display == 'query_only') && this.advancedBrowser) {
			opt.appendChild(document.createTextNode(disp_name));
			var src = document.createElement('span');
			src.style.color = 'gray';
			srctext = ' - ' + this.sources[field.source].name_translated;
			src.appendChild(document.createTextNode(srctext));
			opt.appendChild(src);
		}
		else
			opt.text = disp_name;
		if(field.display == 'hidden' || field.display == 'query_only')
			opt.style.color = 'gray';
		try {
			this.fields_select.add(opt, null);
		} catch(ex) {
			this.fields_select.add(opt);
		}
		
		var grp = field.display;
		if(grp == 'hidden') grp = 'normal';
		optgroups[field.source][grp].appendChild(opt);
		
		if(selected[field.ID])
			opt.selected = true;
	}
	
	for(var idx = 0; idx < this.totals_order.length; idx++) {
		var name = this.totals_order[idx];
		var sum = this.totals[name];
		var opt = document.createElement('option');
		opt.value = name;
		var disp_name = this.get_total_name(name);
		
		if(multiSource && this.advancedBrowser) {
			opt.appendChild(document.createTextNode(disp_name));
			var src = document.createElement('span');
			src.style.color = 'gray';
			srctext = ' - ' + this.sources[this.fields[sum.field].source].name_translated;
			src.appendChild(document.createTextNode(srctext));
			opt.appendChild(src);
		}
		else
			opt.text = disp_name;
		opt.is_total = true;
		try {
			this.fields_select.add(opt, null);
		} catch(ex) {
			this.fields_select.add(opt);
		}
		
		grp_totals.appendChild(opt);
		
		if(selected[sum.ID])
			opt.selected = true;
	}
	
	for(var idx=0; idx < optgrplist.length; idx++)
		if(optgrplist[idx].childNodes.length)
			this.fields_select.appendChild(optgrplist[idx]);
	
	this.show_chart_options();
}

this.make_field_editor_table = function(field, selectCount, is_total) {
	var table = document.createElement('table');
	var rowCount = 0;
	table.border = 0; table.cellpadding = 0; table.cellspacing = 0; table.width = '100%';
	
	if(field == null) {
		this.currentField = null;
		// may choose to show or hide controls when no field is selected
		//return table;
	} else
		this.currentField = field.ID;
	
	is_total = isset(is_total) && is_total
	
	var tr = table.insertRow(rowCount++);
	var td = tr.insertCell(0);
	td.className = 'dataLabel'; td.width = '30%';
	td.appendChild(document.createTextNode(mod_string('LBL_NAME')));
	td = tr.insertCell(1);
	td.className = 'dataField'; td.width = '70%'; td.align = 'left';
	if(field != null)
		td.appendChild(document.createTextNode(field.name_translated));

	tr = table.insertRow(rowCount++);
	td = tr.insertCell(0);
	td.className = 'dataLabel'; td.noWrap = true;
	td.appendChild(document.createTextNode(mod_string('LBL_DISPLAY_NAME')));
	td = tr.insertCell(1);
	td.className = 'dataField'; td.align = 'left';
	var displayName = document.createElement('input');
	displayName.type = 'text'; displayName.size = 20;
	displayName.target = 'display_name'; displayName.is_total = is_total;
	displayName.onchange = this.field_value_setter;
	if(field != null) {
		if(!blank(field.display_name))
			displayName.value = field.display_name;
		displayName.field = field.ID;
	}
	else if(field == null)
		displayName.disabled = true;
	td.appendChild(displayName);
	
	tr = table.insertRow(rowCount++);
	td = tr.insertCell(0);
	td.className = 'dataLabel';
	td.appendChild(document.createTextNode(mod_string('LBL_SOURCE')));
	td = tr.insertCell(1);
	td.className = 'dataField'; td.align = 'left';
	if(field != null) {
		var src = is_total ? this.fields[field.field].source : field.source;
		td.appendChild(document.createTextNode(this.get_source_name(src)));
	}
	
	tr = table.insertRow(rowCount++);
	td = tr.insertCell(0);
	td.className = 'dataLabel';
	td.appendChild(document.createTextNode(mod_string('LBL_TYPE')));
	td = tr.insertCell(1);
	td.className = 'dataField'; td.align = 'left';
	if(field != null) {
		var val = html_unescape(field.type_translated);
		if(!blank(field.format))
			val += ' ('+html_unescape(field.format_translated)+')';
		td.appendChild(document.createTextNode(val));
	}
	
	if(!is_total) {
		tr = table.insertRow(rowCount++);
		td = tr.insertCell(0);
		td.className = 'dataLabel'; td.noWrap = true;
		td.appendChild(document.createTextNode(mod_string('LBL_DISPLAY')));
		td = tr.insertCell(1);
		td.className = 'dataField'; td.align = 'left';
		var displayStyle = document.createElement('select');
		displayStyle.target = 'display';
		displayStyle.onchange = this.field_value_setter;
		var styles = ['normal', 'grouped', 'hidden', 'query_only'];
		for(var idx = 0; idx < styles.length; idx++) {
			var style = styles[idx];
			var opt = document.createElement('option');
			opt.value = style;
			opt.text = mod_string('LBL_FIELD_'+style.toUpperCase());
			try {
				displayStyle.add(opt, null);
			} catch(ex) {
				displayStyle.add(opt);
			}
			if(field != null && field.display == style)
				opt.selected = true;
		}
		if(field != null)
			displayStyle.field = field.ID;
		else
			displayStyle.disabled = true;
		td.appendChild(displayStyle);
	}
	
	if(field != null) {
		if(is_total)
			var fmts = get_display_formats(this.fields[field.field]);
		else
			var fmts = get_display_formats(field);
		if(fmts.length > 1) {
			var displayFormat = document.createElement('select');
			displayFormat.target = 'display_format';
			displayFormat.onchange = this.field_value_setter;
			displayFormat.is_total = is_total;
			var selval = blank(field.display_format) ? '' : field.display_format;
			for(var idx = 0; idx < fmts.length; idx++) {
				var opt = document.createElement('option');
				opt.value = fmts[idx];
				opt.text = mod_string('LBL_FORMAT_'+(fmts[idx] == '' ? 'none' : fmts[idx]).toUpperCase());
				try {
					displayFormat.add(opt, null);
				} catch(ex) {
					displayFormat.add(opt);
				}
				if(opt.value == selval)
					opt.selected = true;
			}
			displayFormat.field = field.ID;
			tr = table.insertRow(rowCount++);
			td = tr.insertCell(0);
			td.className = 'dataLabel'; td.noWrap = true;
			td.appendChild(document.createTextNode(mod_string('LBL_FORMAT')));
			td = tr.insertCell(1);
			td.className = 'dataField'; td.align = 'left';
			td.appendChild(displayFormat);
		}
	}
	
	tr = table.insertRow(rowCount++);
	td = tr.insertCell(0);
	td.className = 'dataLabel'; td.noWrap = true;
	td.appendChild(document.createTextNode(mod_string('LBL_WIDTH')));
	td = tr.insertCell(1);
	td.className = 'dataField'; td.width = '60%'; td.align = 'left';
	var width = document.createElement('input');
	width.type = 'text'; width.size = 3;
	width.target = 'width'; width.is_total = is_total;
	width.onchange = this.field_value_setter;
	if(field != null) {
		if(!blank(field.width))
			width.value = field.width;
		width.field = field.ID;
	}
	else if(field == null)
		width.disabled = true;
	td.appendChild(width);
	td.appendChild(document.createTextNode(' %'));
	
	if(!is_total) {
		tr = table.insertRow(rowCount++);
		td = tr.insertCell(0);
		td.className = 'dataLabel'; td.noWrap = true;
		td.appendChild(document.createTextNode(mod_string('LBL_SORTED')));
		td = tr.insertCell(1);
		td.className = 'dataField'; td.align = 'left';
		var chk = document.createElement('input');
		chk.type = 'checkbox'; chk.className = 'checkbox';
		if(field != null) {
			chk.field = field.ID;
			chk.checked = in_array(field.ID, this.sort_order);
			chk.onclick = function() {
				if(this.checked) {
					the_editor.sort_order.push(this.field);
					the_editor.show_sort_order();
				}
				else	the_editor.remove_sorts_by_field(this.field);
			};
		}
		else
			chk.disabled = true;
		td.appendChild(chk);
	}
	
	/*if(field != null && typeof(field.summations) == 'object') {
		tr = table.insertRow(rowCount++);
		td = tr.insertCell(0);
		td.className = 'dataLabel'; td.noWrap = true;
		td.appendChild(document.createTextNode(mod_string('LBL_SUMMATION')));
		td = tr.insertCell(1);
		td.className = 'dataField';
		var sums = ['count', 'sum', 'avg', 'min', 'max', 'stddev'];
		for(var sum in field.summations) {
			var chk = document.createElement('input');
			chk.type = 'checkbox'; chk.className = 'checkbox';
			chk.field = field.ID; chk.name = sum;
			chk.checked = (field.summations[sum] == 'true');
			chk.onclick = function() {
				the_editor.add_remove_summation(this.field, this.name, this.checked);
			};
			td.appendChild(chk);
			td.appendChild(document.createTextNode('\u00a0'+mod_string('LBL_TOTALS_'+sum.toUpperCase())+' '));
		}
	}*/
	
	tr = table.insertRow(rowCount++);
	td = tr.insertCell(0);
	td.className = "dataLabel"; td.colSpan = 2; td.noWrap = true;

	if(field != null && !is_total) {
		if(isset(filter_editor.filter_types[field.type])) {
			var addFilter = document.createElement('input');
			addFilter.type = 'button'; addFilter.className = 'button';
			addFilter.field = field.ID; addFilter.value = mod_string('LBL_ADD_FILTER_LABEL');
			addFilter.onclick = function() { filter_editor.add_filter(this) };
			td.appendChild(addFilter);
			td.appendChild(document.createTextNode('\u00a0'));
		}
	}
	
	if(selectCount) {
		var remove = document.createElement('input');
		remove.type = 'button'; remove.className = 'button';
		remove.value = mod_string('LBL_REMOVE_LABEL');
		remove.onclick = function() { the_editor.remove_selected_fields() };
		td.appendChild(remove);
	}

	return table;
}

/*this.add_remove_summation = function(fld, sum, add) {
	this.fields[fld].summations[sum] = (add ? 'true' : 'false');
}*/

this.show_sort_order = function() {
	var table = document.getElementById('reportSortingTable');
	var rowCount = 0;
	for(var i = table.rows.length - 1; i >= rowCount; i--)
		table.deleteRow(i);
	var order = [];
	for(var idx=0; idx < this.sort_order.length; idx++) // remove any invalid fields first
		if(isset(this.fields[this.sort_order[idx]]))
			order.push(this.sort_order[idx]);
	this.sort_order = order;
	for(var idx=0; idx < this.sort_order.length; idx++) {
		var fldId = this.sort_order[idx];
		var tr = table.insertRow(rowCount++);
		var td = tr.insertCell(0);
		td.className = 'dataLabel'; td.width = '30%';
		var hdr = document.createElement('h5');
		hdr.className = 'dataLabel';
		var name = this.get_field_name(fldId);
		var srcname = this.get_source_name(this.fields[fldId].source);
		td.appendChild(document.createTextNode(srcname+': '+name));
		//td.appendChild(hdr);
		
		td = tr.insertCell(1);
		td.className = 'dataField'; td.width = '40%';
		order = document.createElement('select');
		order.field = fldId; order.sortidx = idx;
		var opts = ['asc', 'desc'];
		for(var optidx=0; optidx < opts.length; optidx++) {
			opt = document.createElement('option');
			opt.value = opts[optidx];
			opt.text = mod_string('LBL_SORT_'+opt.value.toUpperCase());
			try {
				order.add(opt, null);
			} catch(ex) {
				order.add(opt);
			}
		}
		if(isset(this.fields[fldId].sort_order) && this.fields[fldId].sort_order == 'desc')
			order.selectedIndex = 1;
		else
			order.selectedIndex = 0;
		order.onchange = function() { the_editor.fields[this.field].sort_order = this.value; };
		td.appendChild(order);
		
		td = tr.insertCell(2);
		td.className = "dataLabel"; td.width = "20%";
		var img = null;
		if(idx > 0) {
			img = document.createElement('img');
			img.src = 'themes/'+user_theme+'/images/uparrow_big.gif';
			img.border = 0; img.width = 16; img.height = 16; img.alt = '';
			img.sortidx = idx;
			img.onclick = function() { the_editor.move_sort_priority(this.sortidx, -1); }
			td.appendChild(img);
		}
		if(idx + 1 < this.sort_order.length) {
			img = document.createElement('img');
			img.src = 'themes/'+user_theme+'/images/downarrow_big.gif';
			img.border = 0; img.width = 16; img.height = 16; img.alt = '';
			img.sortidx = idx;
			img.onclick = function() { the_editor.move_sort_priority(this.sortidx, +1); }
			td.appendChild(img);
		}
		
		td = tr.insertCell(3);
		td.className = "dataLabel"; td.width = "10%";
		var remove = document.createElement('input');
		remove.type = 'button'; remove.className = 'button';
		remove.field = fldId; remove.sortidx = idx;
		remove.value = mod_string('LBL_REMOVE_LABEL');
		remove.onclick = function() {
			the_editor.sort_order.splice(this.sortidx, 1);
			the_editor.show_sort_order();
			if(the_editor.currentField == this.field)
				the_editor.update_field_controls(the_editor.fields_select);
		};
		td.appendChild(remove);
	}
	if(this.sort_order.length == 0) {
		var tr = table.insertRow(rowCount++);
		var td = tr.insertCell(0);
		td.className = "dataLabel";
		td.appendChild(document.createTextNode(mod_string('LBL_NO_SORTED_COLUMNS')));
	}
}

this.source_value_setter = function() {
	var src_fields = {'display': 1, 'required': 1};
	if(this.target in src_fields) {
		var val = '';
		if(this.type == 'checkbox')
			val = this.checked ? 'true' : 'false';
		else
			val = this.value;
		the_editor.sources[this.source][this.target] = val;
		if(this.target == 'display')
			the_editor.update_fields_select();
	}
}

this.field_value_setter = function() {
	var fld_fields = {'display_name': 1, 'display': 1, 'width': 1, 'display_format': 1};
	if(this.target in fld_fields) {
		var val = '';
		if(this.type == 'checkbox')
			val = this.checked ? 'true' : 'false';
		else
			val = this.value;
		if(isset(this.is_total) && this.is_total)
			the_editor.totals[this.field][this.target] = val;
		else
			the_editor.fields[this.field][this.target] = val;
	}
	the_editor.update_fields_select();
	filter_editor.show_filters();
	the_editor.show_sort_order();
	the_editor.show_chart_options();
}

this.update_field_controls = function(lst) {
	var selectCount = 0;
	var selectIndex = 0;
	for(var idx = 0; idx < lst.options.length; idx++) {
		if(lst.options[idx].selected) {
			selectCount++;
			selectIndex = idx;
		}
	}
	var field = null;
	var is_total = false;
	if(selectCount == 1) {
		is_total = isset(lst.options[selectIndex].is_total);
		if(is_total)
			field = this.totals[lst.value];
		else
			field = this.fields[lst.value];
	}
	this.field_editor_cell.removeChild(this.field_editor_cell.firstChild);
	this.field_editor_cell.appendChild(this.make_field_editor_table(field, selectCount, is_total));
}

this.add_related_source = function(btn) {
	var src = this.primary_source;
	if(btn.source)
		src = btn.source;
	var popup_data = {
		//call_back_function: "return_new_source",
		form_name: "EditView",
		field_to_name_array: {
			'bean_name': 'bean_name',
			'field_name': 'field_name',
			'name_translated': 'name_translated' ,
			'link_module': 'link_module',
			'link_module_translated': 'link_module_translated',
			'link_type': 'link_type',
			'link_type_translated': 'link_type_translated'
		},
		passthru_data: {
			parent: src
		}
	};
	var filter = '';
	if(this.sources[src].bean_name)
		filter = "bean_name=" + this.sources[src].bean_name;
	else
		filter = "module_name=" + this.sources[src].module;
	var title = mod_string('LBL_SELECT_SOURCE');
	open_popup_inline(title, "Reports", 600, 400, "&type=sources&source_name="+src+"&"+filter, true, false, popup_data);
}


this.return_new_source = function(data) {
	if(data.parent.indexOf('.') != -1) {
		var origParent = data.parent;
		data.parent = this.source_aliases[origParent];
		data.ID = data.parent + '.' + data.ID.substring(origParent.length + 1);
	}
	if(data.ID.indexOf('.') != -1) {
		var origId = data.ID;
		if(isset(this.source_aliases[origId]))
			return;
		var shortname = data.field_name;
		var name = shortname;
		var i = 1;
		while(isset(this.sources[name]))
			name = shortname + (i++);
		data.ID = name;
		this.source_aliases[origId] = name;
	}
	data.required = 'true';
	this.sources_order[this.sources_order.length] = data.ID;
	this.sources[name] = deep_clone(data);
	if(data.type == 'link' && data.link_type == 'one')
		this.sources[name].display = 'joined';
	else
		this.sources[name].display = 'nested';
	this.show_sources();
}

this.remove_related_source = function(source) {
	for(var idx = 0; idx < this.fields_order.length; idx++) {
		var name = this.fields_order[idx];
		if(this.fields[name].source == source) {
			this.fields_order.splice(idx--, 1);
			delete this.fields[name];
			filter_editor.remove_filters_by_field(name);
			this.remove_totals_by_field(name);
			this.remove_sorts_by_field(name);
		}
	}
	var children = [];
	for(var idx = 0; idx < this.sources_order.length; idx++)
		if(this.sources_order[idx] == source)
			this.sources_order.splice(idx--, 1);
		else if(this.sources[this.sources_order[idx]].parent == source)
			children.push(this.sources_order[idx]);
	for(var idx in this.source_aliases)
		if(this.source_aliases[idx] == source)
			delete this.source_aliases[idx];
	for(var idx = 0; idx < children.length; idx++)
		this.remove_related_source(children[idx]);
	delete this.sources[source];
	this.show_sources();
}

this.return_new_fields = function(new_fields, new_order, display) {
	var added = [];
	for(var idx = 0; idx < new_order.length; idx++)
		added.push(this.return_new_field(new_fields[new_order[idx]], display, true));
	this.update_fields_select(added);
	this.update_field_controls(this.fields_select);
}

this.return_new_field = function(fld, display, noupdate) {
	if(fld.source.indexOf('.') != -1) {
		var origSrc = fld.source;
		fld.source = this.source_aliases[origSrc];
		fld.ID = fld.source + '.' + fld.name;
	}
	if(blank(this.fields[fld.ID])) {
		var idx = this.fields_order.length;
		if(this.currentField != null) {
			for(idx = 0; idx < this.fields_order.length; idx++)
				if(this.fields_order[idx] == this.currentField)
					break;
		}
		this.fields_order.splice(idx, 0, fld.ID);
		fld.display = blank(display) ? 'normal' : display;
		this.fields[fld.ID] = deep_clone(fld);
		if(blank(noupdate)) {
			this.update_fields_select([fld.ID]);
			this.update_field_controls(this.fields_select);
		}
	}
	return fld.ID;
}

this.return_new_totals = function(new_totals, new_order) {
	var added = [];
	for(var idx = 0; idx < new_order.length; idx++)
		added.push(this.return_new_total(new_totals[new_order[idx]], true));
	this.update_fields_select(added);
	this.update_field_controls(this.fields_select);
}

this.return_new_total = function(sum, noupdate) {
	if(sum.source.indexOf('.') != -1) {
		var origSrc = sum.source;
		sum.field = sum.field.replace(sum.source, this.source_aliases[origSrc]);
		sum.source = this.source_aliases[origSrc];
		sum.ID = sum.field + ':' + sum.type;
	}
	if(blank(this.totals[sum.ID])) {
		var idx = this.totals_order.length;
		if(this.currentField != null) {
			for(idx = 0; idx < this.totals_order.length; idx++)
				if(this.totals_order[idx] == this.currentField)
					break;
		}
		this.totals_order.splice(idx, 0, sum.ID);
		sum.display = 'normal';
		this.totals[sum.ID] = deep_clone(sum);
		if(blank(noupdate)) {
			this.update_fields_select([sum.ID]);
			this.update_field_controls(this.fields_select);
		}
	}
	return sum.ID;
}

this.add_fields = function(btn, type) {
	var src = this.primary_source;
	if(btn.source)
		src = btn.source;
	var popup_data = {
		form_name: "EditView",
		field_to_name_array: {
		},
		passthru_data: {
			parent: src
		}
	};
	var filter = '';
	if(this.sources[src].bean_name)
		filter = "bean_name=" + this.sources[src].bean_name;
	else
		filter = "module_name=" + this.sources[src].module;
	var title = mod_string('LBL_SEARCH_FIELDS_TITLE');
	open_popup_inline(title, "Reports", 600, 400, "&type="+type+"&source_name="+src+"&"+filter, true, false, popup_data);
}

this.move_fields = function(dir) {
	var selected = {};
	var in_group = {};
	var lst = this.fields_select;
	var found;
	var is_total = false;
	for(var i = 0; i < lst.childNodes.length; i++) {
		var optgroup = lst.childNodes[i];
		found = false;
		for(var j = 0; j < optgroup.childNodes.length; j++) {
			var opt = optgroup.childNodes[j];
			in_group[opt.value] = true;
			if(opt.selected) {
				selected[opt.value] = true;
				found = true;
				if(!blank(opt.is_total))
					is_total = true;
			}
		}
		if(!found) 
			in_group = {};
		else	break;
	}
	
	var ord = is_total ? this.totals_order.slice() : this.fields_order.slice();
	var new_ord = [];
	if(dir == 'up')
		ord.reverse();
	var prev_in_grp = -1;
	for(i = 0; i < ord.length; i++)
		if(! selected[ord[i]] && in_group[ord[i]]) {
			new_ord[new_ord.length] = ord[i];
			if(i - prev_in_grp > 1)
				new_ord = new_ord.concat(ord.slice(prev_in_grp + 1, i));
			prev_in_grp = i;
		}
	new_ord = new_ord.concat(ord.slice(prev_in_grp + 1))
	if(dir == 'up')
		new_ord.reverse();
	if(is_total)
		this.totals_order = new_ord;
	else
		this.fields_order = new_ord;
	this.update_fields_select();
}

this.remove_selected_fields = function() {
	var lst = this.fields_select;
	var selected = [];
	var is_total = false;
	for(var idx = 0; idx < lst.options.length; idx++)
		if(lst.options[idx].selected) {
			selected.push(lst.options[idx].value);
			if(!blank(lst.options[idx].is_total))
				is_total = true;
		}
	var fn = is_total ? 'remove_total' : 'remove_field';
	for(var idx = 0; idx < selected.length; idx++)
		this[fn](selected[idx], idx != selected.length - 1);
}

this.remove_field = function(field, noupdate) {
	for(var idx = this.fields_order.length - 1; idx >= 0; idx--)
		if(this.fields_order[idx] == field)
			this.fields_order.splice(idx, 1);
	delete this.fields[field];
	filter_editor.remove_filters_by_field(field);
	this.remove_sorts_by_field(field);
	this.remove_totals_by_field(field);
	if(blank(noupdate) || noupdate == false) {
		this.update_fields_select();
		this.update_field_controls(this.fields_select);
	}
}

this.remove_total = function(total, noupdate) {
	for(var idx = this.totals_order.length - 1; idx >= 0; idx--)
		if(this.totals_order[idx] == total)
			this.totals_order.splice(idx, 1);
	delete this.totals[total];
	if(blank(noupdate) || noupdate == false) {
		this.update_fields_select();
		this.update_field_controls(this.fields_select);
	}
}

this.move_sort_priority = function(idx, offset) {
	var moveto = Math.max(0, Math.min(idx+offset, this.sort_order.length-1));
	this.sort_order.splice(moveto, 0, this.sort_order.splice(idx, 1)[0]);
	this.show_sort_order();
}

this.remove_sorts_by_field = function(fldId) {
	for(var idx=0; idx < this.sort_order.length; idx++)
		if(this.sort_order[idx] == fldId)
			this.sort_order.splice(idx--, 1);
	this.show_sort_order();
}

this.remove_totals_by_field = function(fldId) {
	var to_remove = [];
	for(var idx=0; idx < this.totals_order.length; idx++)
		if(this.totals[this.totals_order[idx]].field == fldId)
			to_remove.push(this.totals_order[idx]);
	for(var idx = 0; idx < to_remove.length; idx++)
		this.remove_total(to_remove[idx], idx != to_remove.length - 1);
}

this.set_chart_type = function(newtype) {
	if(blank(this.chart_type)) {
		this.chart_title = mod_string('LBL_DEFAULT_CHART_TITLE');
		this.chart_description = mod_string('LBL_DEFAULT_CHART_DESCRIPTION');
		this.chart_rollover = mod_string('LBL_DEFAULT_CHART_ROLLOVER');
	}
	this.chart_type = newtype;
	this.show_chart_options();
}

this.show_chart_options = function() {
	var table = document.getElementById('chartOptionsTable');
	var rowCount = 0;
	for(var i = table.rows.length - 1; i >= rowCount; i--)
		table.deleteRow(i);
	var grp_field = '';
	for(var idx = 0; idx < this.fields_order.length; idx++) {
		if(this.fields[this.fields_order[idx]].display == 'grouped') {
			grp_field = this.fields_order[idx];
			break;
		}
	}
	if(this.totals_order.length && grp_field != '') {
		function add_cell(tr, caption, value) {
			var td = tr.insertCell(tr.cells.length);
			td.className = "dataLabel"; td.width = "15%";
			td.appendChild(document.createTextNode(caption));
			td = tr.insertCell(tr.cells.length);
			td.className = "dataField"; td.width = "35%";
			td.style.verticalAlign='top';
			if (value) {
				td.appendChild(value);
			}
			return td;
		}
		
		var tr = table.insertRow(rowCount++);
		var selectType = document.createElement('select');
		selectType.onchange = function() { the_editor.set_chart_type(this.value); }
		var types = ['', 'hbar', 'vbar', 'pie', 'line'];
		if(this.svg_charts)
			types.push('area');
		for(var idx = 0; idx < types.length; idx++) {
			var t = types[idx];
			var opt = document.createElement('option');
			opt.value = t;
			opt.text = mod_string('LBL_CHART_'+(t == '' ? 'NONE' : t.toUpperCase()));
			try {
				selectType.add(opt, null);
			} catch(ex) {
				selectType.add(opt);
			}
			if(this.chart_type == t)
				opt.selected = true;
		}
		add_cell(tr, mod_string('LBL_CHART_TYPE'), selectType);
		
		if(this.chart_type != '') {
			var titleInput = document.createElement('input');
			titleInput.type = 'text'; titleInput.size = '40';
			titleInput.className = 'dataField';
			titleInput.value = this.chart_title;
			titleInput.onchange = function() { the_editor.chart_title = this.value; }
			add_cell(tr, mod_string('LBL_CHART_TITLE'), titleInput);
			
			tr = table.insertRow(rowCount++);
			var dataSeries = document.createElement('select');
			var found = false;
			for(var idx = 0; idx < this.totals_order.length; idx++) {
				var totalid = this.totals_order[idx];
				var total = this.totals[totalid];
				var opt = document.createElement('option');
				opt.value = totalid;
				opt.text = blank(total.display_name) ? total.name_translated : total.display_name;
				try {
					dataSeries.add(opt, null);
				} catch(ex) {
					dataSeries.add(opt);
				}
				//for (var xx in total) alert('chart_series :' + this.chart_series + ', index:' + xx + ', value:' + total[xx]);
				if(this.chart_series == total.ID) {
					opt.selected = true;
					found = true;
				}
			}
			if(!found) {
				this.chart_series = dataSeries.options[0].value;
				dataSeries.options[0].selected = true;
			}
			dataSeries.onchange = function() { the_editor.chart_series = this.value; }
			add_cell(tr, mod_string('LBL_CHART_DATA_SERIES'), dataSeries);
			
			var rolloverInput = document.createElement('input');
			rolloverInput.size = '40';
			rolloverInput.className = 'dataField';
			rolloverInput.value = this.chart_rollover;
			rolloverInput.onchange = function() { the_editor.chart_rollover = this.value; }
			add_cell(tr, mod_string('LBL_CHART_ROLLOVER'), rolloverInput);
			
			tr = table.insertRow(rowCount++);


			if(this.svg_charts) {
				var td = add_cell(tr, mod_string('LBL_CHART_OPTIONS'));
	
				if (this.chart_type != 'line' && this.chart_type != 'area') {
					var cb = document.createElement('input');
					cb.setAttribute('type', 'checkbox');
					cb.setAttribute('class', 'checkbox');
					cb.setAttribute('id', 'chart_option_3d');
					if (this.chart_options['3d']) {
						cb.setAttribute('checked', 'checked');
					}
					cb.onclick = function() {report_editor.chart_options['3d'] = this.checked;};
					var lbl = createElement2('label');
					lbl.appendChild(cb);
					lbl.appendChild(document.createTextNode(' ' + mod_string('LBL_3D')));
					td.appendChild(lbl);
					td.appendChild(document.createElement('br'));
				}
				
				if (this.chart_type == 'vbar' || this.chart_type == 'hbar') {
					var cb = document.createElement('input');
					cb.setAttribute('type', 'checkbox');
					cb.setAttribute('class', 'checkbox');
					cb.setAttribute('id', 'chart_option_stacked');
					if (this.chart_options['stacked']) {
						cb.setAttribute('checked', 'checked');
					}
					cb.onclick = function() {report_editor.chart_options['stacked'] = this.checked;};
					var lbl = createElement2('label');
					lbl.appendChild(cb);
					lbl.appendChild(document.createTextNode(' ' + mod_string('LBL_STACKED')));
					td.appendChild(lbl);
					td.appendChild(document.createElement('br'));
				}
	
				if (this.chart_type == 'pie') {
					var cb = document.createElement('input');
					cb.setAttribute('type', 'checkbox');
					cb.setAttribute('class', 'checkbox');
					cb.setAttribute('id', 'chart_option_percent');
					if (this.chart_options['percent']) {
						cb.setAttribute('checked', 'checked');
					}
					cb.onclick = function() {report_editor.chart_options['percent'] = this.checked;};
					var lbl = createElement2('label');
					lbl.appendChild(cb);
					lbl.appendChild(document.createTextNode(' ' + mod_string('LBL_PERCENT')));
					td.appendChild(lbl);
					td.appendChild(document.createElement('br'));
	
					var cb = document.createElement('input');
					cb.setAttribute('type', 'checkbox');
					cb.setAttribute('class', 'checkbox');
					cb.setAttribute('id', 'chart_option_exploded');
					if (this.chart_options['exploded']) {
						cb.setAttribute('checked', 'checked');
					}
					cb.onclick = function() {report_editor.chart_options['exploded'] = this.checked;};
					var lbl = createElement2('label');
					lbl.appendChild(cb);
					lbl.appendChild(document.createTextNode(' ' + mod_string('LBL_EXPLODED')));
					td.appendChild(lbl);
					td.appendChild(document.createElement('br'));
				}
			}
			
			var descInput = document.createElement('textarea');
			descInput.rows = '2'; descInput.cols = '30';
			descInput.className = 'dataField';
			descInput.value = this.chart_description;
			descInput.onchange = function() { the_editor.chart_description = this.value; }
			add_cell(tr, mod_string('LBL_CHART_DESCRIPTION'), descInput);


		}
		else
			add_cell(tr, '\u00a0', document.createTextNode('\u00a0'));
	}
	else {
		this.chart_type = '';
		var tr = table.insertRow(rowCount++);
		var td = tr.insertCell(0);
		td.className = "dataLabel";
		td.appendChild(document.createTextNode(mod_string('LBL_NOT_CHARTABLE')));
	}
}

this.pre_submit_form = function(form) {
	var json_copy = ['sources', 'sources_order', 'fields', 'fields_order', 'totals', 'totals_order', 'sort_order', 'chart_options'];
	var straight_copy = ['chart_type', 'chart_title', 'chart_rollover', 'chart_description', 'chart_series'];
	for(var idx = 0; idx < json_copy.length; idx++) {
		var f = json_copy[idx];
		form['_'+f].value = JSON.stringify(this[f]);
	}
	for(var idx = 0; idx < straight_copy.length; idx++) {
		var f = straight_copy[idx];
		form[f].value = this[f];
	}
	filter_editor.pre_submit_form(form);
	return true;
}

}; // end report_editor

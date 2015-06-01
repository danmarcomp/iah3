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
 *****************************************************************************
 * $Id: recur.js 7294 2010-06-11 20:15:19Z andrew $
 * File Description:
 * Contributor(s):
*****************************************************************************/


Array.prototype.removeAll = function(val) {
	for(var idx = 0; idx < this.length; idx++)
		if(this[idx] == val)
			this.splice(idx, 1);
}
Array.prototype.uniqueValues = function(val) {
	var valset = {};
	for(var idx = 0; idx < this.length; idx++)
		valset[this[idx]] = 1;
	var vals = [];
	for(var val in valset)
		vals.push(val);
	return vals;
}
function parseIntBlank(val, def) {
	val = parseInt(val);
	if(isNaN(val))
		return get_default(def, '');
	return val;
}

if(isset(Object.prototype.toJSON))
	delete Object.prototype.toJSON; // from jsolait - adding methods to Object.prototype is bad


var recur_editors = {};
var uniq_elt_id = 0;

function make_dropdown(options, value, editor, cat, rowidx) {
	var sel = document.createElement('select');
	sel.className = 'dataField';
	for(var oidx in options) {
		var opt = document.createElement('option');
		opt.value = oidx;
		opt.text = options[oidx];
		try {
			sel.add(opt, null);
		}
		catch(e) {
			sel.add(opt);
		}
		if(oidx == value)
			opt.selected = true;
	}
	var oldval = value;
	sel.onchange = function() { editor.onchange(this, cat, oldval, rowidx); oldval = this.value; }
	sel.id = 'sel_'+(uniq_elt_id ++);
	return sel;
}

function make_checkbox(value, label, checked, editor, cat, rowidx) {
	var uniqid = "cb_"+(uniq_elt_id ++);
	var cb = document.createElement('input');
	cb.type = 'checkbox'; cb.className = 'checkbox';
	cb.value = value;
	cb.name = uniqid;
	var oldval = value;
	cb.onchange = function() { editor.onchange(this, cat, oldval, rowidx); oldval = this.value; }
	if(checked)
		cb.checked = true;
	var lbl = document.createElement('label');
	lbl.className = 'dataLabel';
	lbl.appendChild(document.createTextNode(' '+label));
	lbl.setAttribute('for', uniqid);
	var div = document.createElement('div');
	div.style.whiteSpace = 'nowrap';
	div.appendChild(cb);
	div.appendChild(lbl);
	return div;
}

function checkbox_list(options, selected, editor, cat, rowidx) {
	var div = document.createElement('div');
	div.className = 'recur_month_list';
	for(var val in options) {
		var checked = false;
		for(var sidx = 0; sidx < selected.length; sidx++)
			if(selected[sidx] == val) {
				checked = true;
				break;
			}
		var chkdiv = make_checkbox(val, options[val], checked, editor, cat, rowidx);
		chkdiv.style.display = 'inline';
		chkdiv.style.padding = '0 0.25em';
		div.appendChild(chkdiv);
		div.appendChild(document.createTextNode(' '));
	}
	return div;
}

function make_textinput(value, size, editor, cat, rowidx) {
	var inp = document.createElement('input');
	inp.className = 'dataField';
	inp.value = value;
	inp.size = size;
	var oldval = value;
	inp.onchange = function() { editor.onchange(this, cat, oldval, rowidx); oldval = this.value; }
	inp.id = 'inp_'+(uniq_elt_id ++);
	return inp;
}

function make_text(value) {
	var sp = document.createElement('span');
	sp.className = 'dataLabel';
	sp.appendChild(document.createTextNode(value));
	return sp;
}

function make_calendar(input, editor, cat, rowidx) {
	cal_img = document.createElement('img');
	cal_img.src = 'themes/'+user_theme+'/images/jscalendar.gif';
	cal_img.width = 16; cal_img.height = 18; cal_img.alt = '';
	cal_img.id = input.id+'_trigger'; cal_img.align = 'absmiddle';
			
    var cal_input = new SUGAR.ui.CalendarInput(input.id+'-calendar', {input_field: input});
	cal_img.onclick = function(evt) { cal_input.setup(); return cal_input.showPopup();};
	return cal_img;
}

function make_link(text, onclick, title, icon) {
	var link = document.createElement('a');
	link.style.whiteSpace = 'nowrap';
	link.href = '#';
	link.onclick = function() { onclick(); return false; }
	title = get_default(title, '');
	link.title = title;
	link.className = 'utilsLink';
	if(isset(icon) && icon != '') {
		var img = createElement2('div', {className: 'input-icon '+icon});
		link.appendChild(img);
		link.appendChild(nbsp());
	}
	link.appendChild(document.createTextNode(text));
	return link;
}

function translate(str)	{
	return mod_string(str, 'Recurrence');
}

// create singleton
var recur_schedule = new function() {
	this.rules = [];
	var me = this;
	var maxoccur = 5;
	this.remove_rule = function(idx) {
		me.rules.splice(idx, 1);
		me.show();
	}
	this.add_rule = function() {
		me.rules.push({ freq: 'WEEKLY' });
		me.show();
	}
	this.add_restriction = function() {
		me.rules.push({ freq: 'WEEKLY', is_restriction: 1 });
		me.show();
	}
	this.pdiv = null;


	this.display = function(rules_input)
	{
		this.save_to_field = rules_input;
		var outer = createElement2('div', {className: 'dialog-content'}),
			bbar = createElement2('div', {className: 'button-bar form-top opaque'}),
			table = createElement2('table', {className: 'tabForm', style: "width: 100%"}),
			row = table.insertRow(0),
			cell = row.insertCell(0);
		cell.className = 'dataField';
		//outer.style.padding = '5px';
		var b1 = new SUGAR.ui.ButtonInput(null, {
			onclick: function() { recur_schedule.save(); },
			label: app_string('LBL_SAVE_BUTTON_LABEL'),
			icon: 'icon-accept'
		});
		bbar.appendChild(b1.render());
		bbar.appendChild(nbsp());
		var b2 = new SUGAR.ui.ButtonInput(null, {
			onclick: function() { recur_schedule.close_popup(); },
			label: app_string('LBL_CANCEL_BUTTON_LABEL'),
			icon: 'icon-cancel'
		});
		bbar.appendChild(b2.render());
		var inner = document.createElement('div');
		cell.appendChild(inner);
		outer.appendChild(bbar);
		outer.appendChild(table);
		this.decode_rules(rules_input.value);
		this.popup = new SUGAR.ui.Dialog('recur_editor', {content_elt: outer, width: '500px', title: translate('LBL_RECURRENCE_FORM_TITLE')});
		this.show(inner);
		this.popup.render();
		this.popup.show();
	};

	this.show = function(pdiv) {
		if(isset(pdiv))
			this.pdiv = pdiv;
		else
			pdiv = this.pdiv;
		pdiv.innerHTML = '';
		for(var idx = 0; idx < this.rules.length; idx++) {
			if(idx > 0)
				pdiv.appendChild(document.createElement('hr'));
			var r = new recur_rule(this, idx, this.rules[idx], pdiv);
			r.show();
		}
		if(idx < maxoccur) {
			if(idx > 0)
				pdiv.appendChild(document.createElement('hr'));
			var add = make_link(translate('LBL_ADD_RECURRENCE'), this.add_rule, '', 'icon-add');
			pdiv.appendChild(add);
			pdiv.appendChild(document.createTextNode(' \u00A0 '));
			var add2 = make_link('add restriction', this.add_restriction, '', 'icon-add');
			//pdiv.appendChild(add2);
			//var tst = make_link('test', function() { me.rules = test_rules(); me.show(); }, '', 'plus_inline.gif', 12, 12);
			//pdiv.appendChild(tst);
		}
		SUGAR.popups.reposition();
	}
	this.load = function(ruleset) {
		this.rules = ruleset;
	}
	this.save = function(elt) {
		if(isset(elt))
			this.save_to_field = elt;
		if(!isset(this.save_to_field))
			return;
		this.save_to_field.value = this.encode_rules();
		this.close_popup();
	};
	this.close_popup = function()
	{
		if (this.popup) {
			this.popup.hide();
			this.popup.destroy();
			delete this.popup;
		}
	};
	this.check_dates = function(elt) {
		var rulestr = this.encode_rules();
		var frm = this.save_to_field.form;
		var dtstart = isset(frm.date_start) ? frm.date_start.value : frm.due_date.value;
		return window.open("index.php?module=Recurrence&action=CheckDates&to_pdf=true&dtstart="+escape(dtstart)+"&rules="+escape(rulestr), "", "width=500,height=300,resizable=1,scrollbars=1");
	}
	this.encode_rules = function() {
		return JSON.stringify(this.rules);
	};
	this.decode_rules = function(rules)
	{
		try {
			this.rules = JSON.parse(rules);
		} catch (e) {
			this.rules = [];
		}
	};
}();

var recur_rule = function(schedule, rule_idx, params, pdiv) {
	var active_editors = {};
	this.default_modes = {};
	this.idx = rule_idx;
	var default_editors = {
		DAILY: ['freq', 'bymonth', 'until'],
		WEEKLY: ['freq', 'byday', 'until'],
		MONTHLY: ['freq', 'byday', 'bymonth', 'until'],
		YEARLY: ['freq', 'bymonth', 'byday', 'until']
	};
	this.params = params;
	//alert(JSON.stringify(params));
	var div = document.createElement('div');
	div.className = 'recur_rule';
	pdiv.appendChild(div);
	
	this.reset = function(freq) {
		this.params = {freq: freq};
		schedule.rules[this.idx] = this.params;
		this.show();
	}
	
	this.show = function() {
		div.innerHTML = '';
		var controls = document.createElement('div');
		controls.style.cssFloat = 'right';
		controls.style.textAlign = 'right';
		var del = createElement2('div', {className: 'input-icon icon-delete active-icon'});
		del.onclick = function() { schedule.remove_rule(rule_idx); };
		controls.appendChild(del);
		div.appendChild(controls);
		active_editors = {};
		var freq = get_default(this.params.freq, '');
		for(var idx = 0; idx < default_editors[freq].length; idx++) {
			id = default_editors[freq][idx];
			active_editors[id] = new recur_editors[id](this, div, this.default_modes[id]);
			active_editors[id].show();
		}
	}
}

recur_editors.freq = function(rule, pdiv, mode) {
	var opts = {
		'DAILY': translate('LBL_DAYS'),
		'WEEKLY': translate('LBL_WEEKS'),
		'MONTHLY': translate('LBL_MONTHS'),
		'YEARLY': translate('LBL_YEARS')
	};
	var interval = get_default(rule.params.interval, '1');
	var freq = get_default(rule.params.freq, '');
	this.onchange = function(elt, cat, oldval, rowidx) {
		if(cat == 'ival')
			rule.params.interval = interval = ''+parseIntBlank(elt.value, '1');
		else if(cat == 'freq')
			rule.reset(elt.value);
		rule.show();
	}
	var div = document.createElement('div');
	div.className = 'recur_row recur_freq';
	pdiv.appendChild(div);
	this.show = function() {
		if(get_default(rule.params.is_restriction, '0') == 1) {
			var text = make_text(translate('LBL_DOESNOT_OCCUR'));
			text.className += ' error';
			div.appendChild(text);
			div.appendChild(make_text(translate('LBL_EVERY')));
		}
		else
			div.appendChild(make_text(translate(rule.idx == 0 ? 'LBL_OCCURS_EVERY' : 'LBL_ALSO_OCCURS')));
		var input = make_textinput(interval, 2, this, 'ival');
		//input.readOnly = true;
		div.appendChild(input);
		var selfreq = make_dropdown(opts, freq, this, 'freq');
		//selfreq.disabled = true;
		div.appendChild(selfreq);
	}
}

recur_editors.until = function(rule, pdiv, mode) {
	var until = get_default(rule.params.until, '');
	var until_date = '';
	var until_time = '23:59:59';
	if(until == '')
		until = null;
	else {
		until = new Date(until.replace(/-/g, '/')); // must be in yyyy/mm/dd format
		until_date = until.print('%Y-%m-%d');
	}
	var count = get_default(rule.params.count, '');
	function updateUntil() {
		if(until_date == '') {
			rule.params.until = '';
			until = null;
		}
		else {
			rule.params.until = until_date + ' ' + until_time;
			until = new Date(rule.params.until.replace(/-/g, '/'));
		}
	}
	this.onchange = function(elt, cat, oldval, rowidx) {
		if(cat == 'until_cal') {
			until_date = oldval.print('%Y-%m-%d');
			updateUntil();
		}
		else if(cat == 'count') {
			rule.params.count = count = parseIntBlank(elt.value);
			elt.value = count;
		}
	}
	var div = document.createElement('div');
	div.className = 'recur_row recur_until';
	pdiv.appendChild(div);
	this.show = function() {
		div.appendChild(make_text(translate('LBL_ENDING_ON')));
		var show_date = (until == null ? '' : until.print(cal_date_format));
		var date_input = make_textinput(show_date, 10, this, 'until_date');
		date_input.readOnly = true;
		div.appendChild(date_input);
		div.appendChild(document.createTextNode(' '));
		div.appendChild(make_calendar(date_input, this, 'until_cal'));
		//div.appendChild(document.createTextNode(' '));
		//div.appendChild(make_textinput(until_time, 5, this, 'until_time'));
		
		// removed count limit
		div.appendChild(make_text(translate('LBL_WITH_LIMIT')));
		div.appendChild(make_textinput(count, 3, this, 'count'));
		div.appendChild(make_text(translate('LBL_OCCURENCES')));
	}
}

recur_editors.bymonth = function(rule, pdiv, mode) {
	if(!isset(mode))
		mode = {};
	var values = get_default(rule.params.bymonth, '').split(',');
	var month_opts = {
		'1': translate('LBL_JANUARY'),
		'2': translate('LBL_FEBRUARY'),
		'3': translate('LBL_MARCH'),
		'4': translate('LBL_APRIL'),
		'5': translate('LBL_MAY'),
		'6': translate('LBL_JUNE'),
		'7': translate('LBL_JULY'),
		'8': translate('LBL_AUGUST'),
		'9': translate('LBL_SEPTEMBER'),
		'10': translate('LBL_OCTOBER'),
		'11': translate('LBL_NOVEMBER'),
		'12': translate('LBL_DECEMBER')
	};
	var opts = {
		'': translate('LBL_EVERY_MONTH'),
		'SEL': translate('LBL_SELECTED_MONTHS')
	};
	if(rule.params.freq == 'MONTHLY')
		opts[''] = translate('LBL_EACH_MONTH');
	if(rule.params.freq == 'YEARLY') {
		delete opts[''];
		if(values[0] == '') {
			values = [1];
			rule.params.bymonth = '1';
		}
	}
	var all_opts = {};
	for(var idx in opts) all_opts[idx] = opts[idx];
	for(var idx in month_opts) all_opts[idx] = month_opts[idx];
	
	var div = document.createElement('div');
	div.className = 'recur_row recur_bymonth';
	pdiv.appendChild(div);
	this.onchange = function(elt, cat, oldval, rowidx) {
		var redraw = false;
		if(cat == 'mo_dd') {
			if(elt.value == 'SEL') {
				values = [1,2,3,4,5,6,7,8,9,10,11,12];
				redraw = true;
			}
			else {
				values = [elt.value];
				if(oldval == 'SEL')
					redraw = true;
			}
		}
		else if(cat == 'mo_chk') {
			if(elt.checked)
				values.push(elt.value);
			else
				values.removeAll(elt.value);
		}
		rule.params.bymonth = values.uniqueValues().join(',');
		if(redraw)
			rule.show();
	}
	this.show = function() {
		div.innerHTML = '';
		var show_indiv = (rule.params.freq == 'YEARLY' || rule.params.freq == 'DAILY');
		var lbl = translate(show_indiv ? 'LBL_IN' : 'LBL_OF');
		div.appendChild(make_text(lbl));
		if(values.length > 1) {
			div.appendChild(make_dropdown(show_indiv ? all_opts : opts, 'SEL', this, 'mo_dd'));
			div.appendChild(checkbox_list(month_opts, values, this, 'mo_chk'));
		}
		else if(show_indiv)
			div.appendChild(make_dropdown(all_opts, values[0], this, 'mo_dd'));
		else
			div.appendChild(make_dropdown(values[0] == '' ? opts : all_opts, values[0], this, 'mo_dd'));
	}
}

recur_editors.byday = function(rule, pdiv, mode) {
	if(!isset(mode))
		mode = {};
	var rows = [];
	var rowidx = 0;
	var found = {};
	var default_editpos = (rule.params.freq == 'MONTHLY' || rule.params.freq == 'YEARLY');
	
	var val_pat = /^(-?[0-9]+)?(MO|TU|WE|TH|FR|SA|SU)$/;
	var vals = get_default(rule.params.byday, '').split(',');
	for(var idx = 0; idx < vals.length; idx++) {
		var m = vals[idx].match(val_pat);
		if(m != null) {
			var curidx = rowidx;
			if(!default_editpos)
				m[1] = '';
			if(!isset(found[m[1]])) {
				found[m[1]] = rowidx;
				rows[rowidx++] = { pos: m[1], values: [] };
			}
			else
				curidx = found[m[1]];
			rows[curidx].values.push(m[2]);
		}
	}
	function parseIntList(s) {
		var vals = s.replace(/\s/, '').split(/[,;]/);
		var cleanvals = [];
		for(var idx = 0; idx < vals.length; idx++) {
			var ival = parseInt(vals[idx]);
			if(!isNaN(ival))
				cleanvals.push(ival);
		}
		return cleanvals.uniqueValues().sort(function(a,b) { return a - b; });
	}
	var monthdays = get_default(rule.params.bymonthday, '');
	if(monthdays != '') {
		var row = { pos: 'MD', values: parseIntList(monthdays) };
		rows.push(row);
	}
	var yeardays = get_default(rule.params.byyearday, '');
	if(yeardays != '') {
		var row = { pos: 'YD', values: parseIntList(yeardays) };
		rows.push(row);
	}
	var dd_opts = {
		'': translate('LBL_EACH'),
		'MD': translate('LBL_DAY_NUMBERS'),
		'1': translate('LBL_FIRST'),
		'2': translate('LBL_SECOND'),
		'3': translate('LBL_THIRD'),
		'4': translate('LBL_FOURTH'),
		'-1': translate('LBL_LAST')
	};
	var wd_opts = {
		'SU': translate('LBL_SU'),
		'MO': translate('LBL_MO'),
		'TU': translate('LBL_TU'),
		'WE': translate('LBL_WE'),
		'TH': translate('LBL_TH'),
		'FR': translate('LBL_FR'),
		'SA': translate('LBL_SA')
	};
	var default_weekday = 'MO';
	if(window.opener && window.opener.document.forms.EditView) {
		var dtst = window.opener.document.forms.EditView.date_start.value;
		var c = new Calendar;
		c.date = new Date();
		c.setDate = function(d) { c.date = d; }
		c.parseDate(dtst, cal_date_format);
		var day = c.date.getDay();
		var i = 0;
		for(var idx in wd_opts) {
			if(i++ == day)
				default_weekday = idx;
		}
	}
	var div = document.createElement('div');
	div.className = 'recur_byday';
	pdiv.appendChild(div);
	function update_value() {
		var valset = {};
		for(var rowidx = 0; rowidx < rows.length; rowidx++) {
			var row = rows[rowidx];
			if(!isset(valset[row.pos]))
				valset[row.pos] = {};
			for(var idx = 0; idx < row.values.length; idx++)
				valset[row.pos][row.values[idx]] = 1;
		}
		var vals = [];
		var mday = [], yday = [];
		for(var pos in valset)
			for(var val in valset[pos]) {
				if(pos != '' && isset(valset['']) && valset[''][val])
					continue; // remove redundant entries
				if(pos == 'MD')
					mday.push(val);
				else if(pos == 'YD')
					yday.push(val);
				else
					vals.push(''+pos+val);
			}
		rule.params.byday = vals.join(',');
		rule.params.bymonthday = mday.join(',');
		rule.params.byyearday = yday.join(',');
	}
	if(!rows.length) {
		rows.push({ pos: '', values: [default_weekday] });
		update_value();
	}
	this.onchange = function(elt, cat, oldval, rowidx) {
		var row = rows[rowidx];
		var do_show = false;
		if(cat == 'pos') {
			if(row.pos == elt.value)
				return;
			if(elt.value == 'MD' || elt.value == 'YD')
				row.values = ['1'];
			else if(row.pos == 'MD' || row.pos == 'YD')
				row.values = [default_weekday];
			row.pos = elt.value;
			do_show = true;
		}
		else if(cat == 'mday') {
			row.values = parseIntList(elt.value);
			elt.value = row.values.join(', ');
		}
		else if(cat == 'wd_chk') {
			if(elt.checked)
				row.values.push(elt.value);
			else
				row.values.removeAll(elt.value);
		}
		else if(cat == 'wd_dd')
			row.values = [elt.value];
		update_value();
		if(do_show)
			this.show();
	}
	this.remove_row = function(idx) {
		rows.splice(idx, 1);
		this.show();
	}
	this.show = function() {
		div.innerHTML = '';
		var me = this;
		for(var rowidx = 0; rowidx < rows.length; rowidx++) {
			var rdiv = document.createElement('div');
			rdiv.className = 'recur_row';
			var row = rows[rowidx];
			rdiv.appendChild(make_text(translate(rowidx == 0 ? 'LBL_ON' : 'LBL_AND_ON')));
			if(row.pos || default_editpos || rows.length > 1) {
				if(rowidx > 0) {
					var remidx = rowidx;
					var rem = make_link('', function() { me.remove_row(remidx); }, '', 'minus_inline.gif', 12, 12);
					rdiv.appendChild(rem);
					rdiv.appendChild(document.createTextNode(' '));
				}
				rdiv.appendChild(make_dropdown(dd_opts, row.pos, this, 'pos', rowidx));
				rdiv.appendChild(document.createTextNode(' '));
			}
			if(row.pos == 'MD')
				rdiv.appendChild(make_textinput(row.values.join(', '), 10, this, 'mday', rowidx));
			else if(row.values.length > 1 || row.multiple)
				rdiv.appendChild(checkbox_list(wd_opts, row.values, this, 'wd_chk', rowidx));
			else {
				// disabled dropdown
				rdiv.appendChild(make_dropdown(wd_opts, row.values[0], this, 'wd_dd', rowidx));
				//rdiv.appendChild(make_text(wd_opts[row.values[0]]));
			}
			div.appendChild(rdiv);
		}
		
	}
}


function test_rules() { return [
	{freq: 'DAILY', interval: '15'},
	{freq: 'WEEKLY', interval: '4', byday: 'MO,TU,FR'},
	{freq: 'MONTHLY', interval: '6', byday: '1MO'},
	{freq: 'MONTHLY', bymonth: '', byday: '-1MO,3TU,3FR,WE,TH'},
	{freq: 'YEARLY', bymonth: '1', bymonthday: '1'},
	{freq: 'YEARLY', bymonth: '10,11', bymonthday: '1'}
];
}
	
function test() {
	var pattern = 1;
	var root = document.getElementById('recurrence');
	//recur_schedule.rules = test_rules;
	recur_schedule.show(root);
}
//test();

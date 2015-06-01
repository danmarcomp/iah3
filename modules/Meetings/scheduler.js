// vim: autoindent:smartindent:
/**
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 */

//////////////////////////////////////////////////
// class: SugarWidgetScheduler
// widget to display the meeting scheduler
//
//////////////////////////////////////////////////


ScheduleViewer = new function() {
var UI = SUGAR.ui,
	Sched = this;

this.hours = 9;
this.segments = 4;
this.attends = [];
this.orig_attends_hash = {};
this.timeslots = [];
this.hash_format = '%Y%m%d%H%M%S';
this.dialog = 0;

this.init = function(id, attends, params) {
	this.id = id;
	this.elt = null;
	this.table = null;
	if(! YLang.isArray(attends)) attends = [];
	this.attends = attends;
	this.timeslots = [];
    if (params.hasOwnProperty('dialog'))
        this.dialog = params.dialog;
}

this.setup = function() {
	this.elt = $(this.id);
	this.date_start_input = UI.getFormInput(this.form, 'date_start');
	this.user_input = UI.getFormInput(this.form, 'assigned_user');
	this.duration_input = UI.getFormInput(this.form, 'duration');
	if(! this.user_input || ! this.date_start_input || ! this.duration_input)
		return;
	this.addHooks();
	UI.onInitForm(this.form, function() { Sched.setOriginal(); Sched.render(); });
}

this.setOriginal = function() {
	this.orig_attends_hash = {};
	if(this.form.record.value) {
		for(var i = 0; i < this.attends.length; i++)
			this.orig_attends_hash[this.attends[i].id] = 1;
		this.orig_start = this.getStartDate();
		this.orig_start_ts = Math.floor(this.orig_start.getTime() / 1000);
		this.orig_duration = this.getDuration();
	}
}

this.getStartDate = function() {
	var dt = this.date_start_input.getValue(true);
	if(! dt) dt = new Date();
	return dt;
}

this.getDuration = function() {
	return this.duration_input.getValue();
}

this.initTimeSlots = function() {
	var segment_len = 3600/this.segments,
		dt_start = this.getStartDate(),
		duration = this.getDuration() * 60,
		hours_before = Math.floor((this.hours - 1) / 2),
		curdate, have_start, have_end, have_orig_start, have_orig_end, start_ts, ts, obj;
	
	start_ts = Math.floor(dt_start.getTime() / 1000);
	curdate = new Date(dt_start.getFullYear(), dt_start.getMonth(), dt_start.getDate(), dt_start.getHours()-hours_before, 0, 0);
	this.timeslots = [];
	
	for(var i=0; i < this.hours*this.segments; i++) {
		ts = Math.floor(curdate.getTime() / 1000);
		obj = {hash: curdate.print(this.hash_format), ts: ts, date_obj: curdate};
		if(! have_start && start_ts <= ts) {
			obj.is_start = true;
			have_start = true;
		}
		if(! have_end && start_ts + duration <= ts) {
			obj.is_end = true;
			have_end = true;
		}
		if(! have_orig_start && this.orig_start_ts && this.orig_start_ts <= ts) {
			obj.is_orig_start = true;
			have_orig_start = true;
		}
		if(have_orig_start && ! have_orig_end && this.orig_start_ts + this.orig_duration*60 <= ts) {
			obj.is_orig_end = true;
			have_orig_end = true;
		}
		this.timeslots.push(obj);

		curdate = new Date((ts + segment_len)*1000);
	}
}

this.addHooks = function() {
	var self = this,
		updfn = function() { self.timeUpdated(); };
	if(this.form) {
		UI.attachInputEvent(this.date_start_input, 'onchange', updfn);
		UI.attachInputEvent(this.duration_input, 'onchange', updfn);
		UI.attachInputEvent(this.user_input, 'onchange', function() { self.userUpdated(); });
	}
}

this.timeUpdated = function() {
	this.initTimeSlots();
	this.clearFreeBusy();
	this.renderTable();
}

this.userUpdated = function() {
	if(this.checkAssignedUser()) {
		this.loadFreeBusy();
		this.renderBody();
	}
}

this.checkAssignedUser = function() {
	var uid = this.user_input.getKey(), display = this.user_input.getValue();
	if(! uid || ! display) return;
	for(var i = 0; i < this.attends.length; i++) {
		if(this.attends[i].module == 'Users' && this.attends[i].id == uid)
			return false;
	}
	this.attends.splice(0, 0, {module: 'Users', id: uid, _display: display});
	return true;
}

this.addRow = function(row, module) {
	if(! row || ! row.id) return;
	if(module) row.module = module;
	for(var i = 0; i < this.attends.length; i++) {
		if(this.attends[i].module == row.module && this.attends[i].id == row.id)
			return;
	}
	this.attends.push(row);
	this.loadFreeBusy();
	this.renderBody();
}

this.deleteRow = function(index) {
	// FIXME - enforce assigned user
	this.attends.splice(index, 1);
	this.renderTable();
}


this.render = function() {
	this.initTimeSlots();
	this.checkAssignedUser();
	this.loadFreeBusy();
	this.renderTable();
}

this.renderTable = function() {
	if(! this.elt)
		return;
	
	if(! this.table) {
		this.table = createElement2('table', {className: 'schedulerTable', width: '100%', border: 0, cellSpacing: 0});
		this.thead = createElement2('thead', null, null, this.table);
		this.tbody = this.table.tBodies[0];
		if(! this.tbody) this.tbody = createElement2('tbody', null, null, this.table);
		YEvent.addListener(this.table, 'selstart', function(evt) { YEvent.preventDefault(evt); }, this, true);
	}
    if(! this.table.parentNode || isIE) {
		this.elt.appendChild(this.table);
    }

	UI.clearChildNodes(this.thead);
	var tr = this.thead.insertRow(0),
		top_date = this.getStartDate().print(getDateFormat().print_format);
	tr.className = 'schedulerTopRow';

    if (! this.dialog) {
        createElement2('td', {height: 20, align: 'center', className: 'schedulerTopDateCell', colSpan: this.hours*this.segments+2}, top_date, tr);
        tr = this.thead.insertRow(1);
    }

	tr.className = 'schedulerTimeRow';
	createElement2('td', {className: 'schedulerAttendeeHeaderCell', width: '20%'}, nbsp(), tr);
	
	var time_format = getTimeFormat().print_format,
		time_nomerid = time_format.replace(/%[pP]/, ''),
		hr, format, time, w = Math.round(100*75/this.hours, 2)/100 + '%';
	
	for(var i=0; i < this.hours; i++) {
		hr = this.timeslots[i*this.segments].date_obj.getHours();
		if(i == 0 || hr == 0 || hr == 12)
			format = time_format;
		else
			format = time_nomerid;
		time = this.timeslots[i*this.segments].date_obj.print(format);
		createElement2('td', {className: 'schedulerTimeCell', colSpan: this.segments, width: w}, time, tr);
	}
	
	createElement2('td', {className: 'schedulerDeleteHeaderCell', width: '5%'}, nbsp(), tr);
	
	this.renderBody();
}


this.cellEvent = function(evt) {
	var tgt = YEvent.getTarget(evt);
	if(evt && tgt) {
		if(evt.type == 'mousedown') {
			this.startDrag(tgt.cellIndex);
			YEvent.preventDefault(evt);
		}
		else if(evt.type == 'mouseover') {
			if(isset(this.selStart)) {
				this.selEnd = tgt.cellIndex;
				this.selMaxLen = Math.max(this.selMaxLen, Math.abs(this.selEnd - this.selStart));
				this.highlightRange();
			}
		}
	}
}

this.startDrag = function(index) {
	this.selStart = index;
	this.selEnd = index;
	this.selMaxLen = 0;
	this.highlightRange();
	YEvent.addListener(document, 'mouseup', this.dragEvent, this, true);
	YEvent.addListener(document, 'keydown', this.dragEvent, this, true);
}

this.dragEvent = function(evt) {
	if(evt.type == 'mouseup' || (evt.type == 'keydown' && evt.keyCode == 27)) {
		YEvent.removeListener(document, 'mouseup', this.dragEvent);
		YEvent.removeListener(document, 'keydown', this.dragEvent);
		if(evt.type == 'keydown') {
			this.selStart = null;
			this.highlightRange();
		} else {
			if(this.selMaxLen) {
				var duration = (Math.abs(this.selStart - this.selEnd) + 1) * 60 / this.segments;
				this.duration_input.setValue(duration, true);
			}
			var date = this.timeslots[Math.min(this.selStart, this.selEnd)-1].date_obj;
			this.selStart = null;
			this.date_start_input.setValue(date);
		}
	}
}

this.highlightRange = function() {
	var i, j, cells, start = this.selStart, end = this.selEnd || 0;
	if(! isset(start)) start = end = -1;
	if(end < start) { i = end; end = start; start = i; }
	for(i = 0; i < this.tbody.rows.length; i++) {
		cells = this.tbody.rows[i].cells;
		for(j = 0; j < cells.length; j++) {
			SUGAR.ui.addRemoveClass(cells[j], 'selected', j >= start && j <= end);
		}
	}
}


this.renderBody = function() {
	var i, j, img, row, tr, cell;
	UI.clearChildNodes(this.tbody);
	
	for(i = 0; i < this.attends.length; i++) {
		row = this.attends[i];
		tr = this.tbody.insertRow(i);
		tr.className = 'schedulerAttendeeRow';

		img = (row.module == 'Users' ? 'bean-User' : 'module-'+row.module);
		img = createElement2('div', {className: 'input-icon theme-icon '+img, style: 'margin-right: 0.5em'});
		createElement2('td', {className: 'schedulerAttendeeCell'}, [img, row._display], tr);

		for(j=0; j < this.timeslots.length; j++) {
			cell = createElement2('td', {className: 'schedulerSlotCellHour', ts: j}, '', tr);
			YEvent.addListener(cell, 'mousedown', this.cellEvent, this, true);
			YEvent.addListener(cell, 'mouseover', this.cellEvent, this, true);
		}
		this.renderFreeBusy(i);
		
		img = createElement2('div', {idx: i, className: 'input-icon icon-delete active-icon',
			onclick: function() { Sched.deleteRow(this.idx); }});
		createElement2('td', {className: 'schedulerAttendeeDeleteCell'}, img, tr);
	}
}


this.renderFreeBusy = function(index) {
	var row = this.tbody.rows[index],
		attend = this.attends[index],
		in_current, in_orig, className, bgColor, busy, td;
	if(! row || ! attend) return;
	
	for(var i=0; i < this.timeslots.length; i++) {
		className = 'schedulerSlotCellHour active';
		if(this.timeslots[i].is_start) {
			in_current = true;
			className += ' startTime';
		}
		if(this.timeslots[i].is_end) {
			in_current = false;
			className += ' endTime';
		}
		if(this.timeslots[i].is_orig_start) {
			in_orig = true;
		}
		if(this.timeslots[i].is_orig_end) {
			in_orig = false;
		}

		if(attend.busy) {
			busy = attend.busy[this.timeslots[i].hash];
			if(in_orig && this.orig_attends_hash[attend.id]) {
				if(busy && ! in_current) busy --;
				else if(busy == 1) busy = 0;
			}
			else if(in_current && busy)
				busy ++;
		} else
			busy = 0;
		
		if(busy > 1)
			className += ' conflict';
		else if(busy)
			className += ' busy';
		else if(in_current)
			className += ' current';

		td = row.cells[i+1];		
		td.className = className;
	}
}


this.clearFreeBusy = function(full) {
	for(var i = 0; i < this.attends.length; i++) {
		if(full) this.attends[i].busy_result = null;
		this.loadFreeBusySlots(i);
	}
}


this.loadFreeBusy = function() {
	for(var i = 0; i < this.attends.length; i++) {
		if(! this.attends[i].busy)
			this.loadFreeBusyRow(i);
	}
}


this.loadFreeBusyRow = function(index) {
	var row = this.attends[index], params = {};
	if(! row || ! row.module || ! row.id) return;
	var query_params = {};
    if (row.module == 'Resources') {
        query_params['resource_id'] = row.id;
    } else {
        query_params['user_id'] = row.id;
    }
	var req = new SUGAR.conn.JSONRequest('get_freebusy_lines', {status_msg: ''}, query_params);
	req.passthru = {module: row.module, id: row.id};
	req.fetch(function() { Sched.returnFreeBusy(this.passthru.module, this.passthru.id, this.getResult().result); });
}


this.returnFreeBusy = function(module, id, result) {
	var i, j, row, rowIndex, ts;
	for(i = 0; i < this.attends.length; i++) {
		if(this.attends[i].module == module && this.attends[i].id == id) {
			row = this.attends[i];
			rowIndex = i;
		}
	}
	if(! row || ! result) return;
	row.busy_result = result;
	this.loadFreeBusySlots(rowIndex);
	this.renderFreeBusy(rowIndex);
}

this.loadFreeBusySlots = function(rowIndex) {
	var row = this.attends[rowIndex], result, starts = [], ends = [];
	if(! row || ! (result = row.busy_result)) return;
	row.busy = {};
	for(i = 0; i < result.length; i++) {
		starts.push(Math.floor((new Date(result[i][0])).getTime() / 1000));
		ends.push(Math.floor((new Date(result[i][1])).getTime() / 1000));
	}
	for(i = 0; i < this.timeslots.length; i++) {
		ts = this.timeslots[i].ts;
		for(j = 0; j < starts.length; j++) {
			if(starts[j] <= ts && ends[j] > ts) {
				row.busy[this.timeslots[i].hash] = (row.busy[this.timeslots[i].hash] || 0) + 1;
			}
		}
	}
}

this.showContacts = function(data) {
    var filter = '';
    var parent_inp = SUGAR.ui.getFormInput(data.form_name, 'parent');
    if (parent_inp) {
        if (parent_inp.getModule() == 'Accounts' && parent_inp.getKey() != '')
            filter = '&primary_account_id=' + parent_inp.getKey();
    }
    open_popup("Contacts", 600, 400, "&hide_clear_button=true" + filter, true, false, data)
}

this.beforeSubmitForm = function() {
	if(! this.form) return;
	var row, i;
	for(var i=0; i < this.attends.length; i++) {
		row = this.attends[i];
		if(row.module == 'Users')
			this.form.user_invitees.value += row.id + ",";
		else if(row.module == 'Contacts')
			this.form.contact_invitees.value += row.id + ",";
		else if(row.module == 'Resources')
			this.form.resource_invitees.value += row.id + ",";
	}
}

return this;

}();


function scheduleReturnRow(data) {
	if(data) {
		var row = deep_clone(data.name_to_value_array);
		row.module = data.module;
		var sched = SUGAR.ui.getFormInput(data.form_name, data.passthru_data.field_id);
		if(sched) sched.addRow(row);
	}
}


/******************************************************************************
* The contents of this file are subject to the CareBrains Software End User
* License Agreement ('License') which can be viewed at
* http://www.sugarforum.jp/download/cbieula.shtml
* By installing or using this file, You have unconditionally agreed to the
* terms and conditions of the License, and You may not use this file except in
* compliance with the License.  Under the terms of the license, You shall not,
* among other things: 1) sublicense, resell, rent, lease, redistribute, assign
* or otherwise transfer Your rights to the Software, and 2) use the Software
* for timesharing or service bureau purposes such as hosting the Software for
* commercial gain and/or for the benefit of a third party.  Use of the Software
* may be subject to applicable fees and any use of the Software without first
* paying applicable fees is strictly prohibited.
* Your Warranty, Limitations of liability and Indemnity are expressly stated
* in the License.  Please refer to the License for the specific language
* governing these rights and limitations under the License.
*****************************************************************************/

var CalendarCtrl = function() {
	return {
		isDashlet: false,
		dashletId: '',
		form_name: 'form_calendar',
		currentCls: '',
		
		globalInitCalendar: function(calendarCls, async, dashletId, params) {
			this.currentCls = calendarCls;
			if(! blank(dashletId)) {
				CalendarCtrl.isDashlet = true;
				CalendarCtrl.dashletId = dashletId;
			}
			if(! async && ! CalendarCtrl.isDashlet) {
				var navTag = SUGAR.util.checkNavigationTag('Calendar');
				if(navTag) {
					var params = parseQueryString(navTag);
					CalendarCtrl.reloadFormWithParams(params, CalendarCtrl.form_name);
					return;
				}
			}
            var self = this;
            window.setTimeout(function() {self.initRefInputs();}, 300);
			if(calendarCls && window[calendarCls])
				window[calendarCls].initCalendar(params);
		},
		
		initRefInputs: function() {
			var sel_user = SUGAR.ui.getFormInput('settings_form', 'user_name_selected');
			if(sel_user) {
				sel_user.onchange = function(k, v) { if(k) CalendarCtrlBase.setUserId(k); }
				sel_user.popup_callback = 'CalendarCtrlBase.popupReturnUser';
				sel_user.popup_return_multiple = true;
			}
			var sel_res = SUGAR.ui.getFormInput('settings_form', 'res_name_selected');
			if(sel_res) {
				sel_res.onchange = function(k, v) { if(k) CalendarCtrlBase.setResourceId(k); }
				sel_res.popup_callback = 'CalendarCtrlBase.popupReturnResource';
				sel_res.popup_return_multiple = true;
			}
		},
		
		showCalendarBody: function(data, callbackFunc) {
			window.setTimeout('ajaxStatus.hideStatus()', 500);
			if(! data)
				return;
			try {
				$("calendar_body").innerHTML = SUGAR.util.disableInlineScripts(data.responseText);
				SUGAR.util.evalScript(data.responseText, callbackFunc);
			} catch (err) {
				console.error(err.message);
			}
		},
		
		reloadWithParams: function(params, callbackFunc) {
			params.forDashlet = CalendarCtrl.isDashlet;
			params.dashletId = CalendarCtrl.dashletId;
			var qstr = encodeQueryString(params);
			var success = function(data) {
				CalendarCtrl.showCalendarBody(data, callbackFunc);
			}
			SUGAR.util.maskDiv('calendar_body');
			if(! CalendarCtrl.isDashlet)
				SUGAR.util.setNavigationTag(qstr, 'Calendar');
			var postData = "module=Calendar&action=asyncCalendarBody&"+qstr;
			SUGAR.conn.asyncRequest(null, {method: 'POST', postData: postData}, success);
		},
		asyncCalendarBody:function(viewType, targetDate, callbackFunc) {
			return this.reloadWithParams({view_type: viewType, target_date: targetDate}, callbackFunc);
		},
		
		reloadFormWithParams: function(params, formName, callbackFunc) {
			var form = document.forms[formName];
			if(! form) return;
			if(! params) params = {};
			for(var argName in params)
				if(isset(params[argName])) {
					if(form.elements[argName])
						form.elements[argName].value = params[argName];
					else {
						var elt = createElement2('input',
							{type: 'hidden', name: argName, value: params[argName]});
						form.appendChild(elt);
					}
				}
			if(! CalendarCtrl.isDashlet) {
				var qstr = encodeQueryString(params);
				SUGAR.util.setNavigationTag(qstr, 'Calendar');
			}
            return this.asyncCalendarBodyWithForm(formName, callbackFunc);
		},
		asyncCalendarBodyWithForm:function(formName, callbackFunc) {
			var success = function(data) {
				CalendarCtrl.showCalendarBody(data, callbackFunc);
			}
			SUGAR.util.maskDiv('calendar_body');
			SUGAR.conn.sendForm(document.forms[formName], null, success);
		},
		
		reload:function(viewType, params) {
			if(viewType == 'day') {
				DayCalendarCtrl.reloadCalendar(params);
			} else if(viewType == 'week') {
				WeekCalendarCtrl.reloadCalendar(params);
			} else if(viewType == 'month') {
				MonthCalendarCtrl.reloadCalendar(params);
			} else if(viewType == 'year') {
				YearCalendarCtrl.reloadCalendar(params);
			} else if(viewType == 'team_day') {
				TeamDayCalendarCtrl.reloadCalendar(params);
			} else if(viewType == 'team_week') {
				TeamWeekCalendarCtrl.reloadCalendar(params);
			} else if(viewType == 'resource_day') {
				ResourceDayCalendarCtrl.reloadCalendar(params);
			} else if(viewType == 'resource_week') {
				ResourceWeekCalendarCtrl.reloadCalendar(params);
			}
		}
	};
}();


var CalendarCtrlBase = new function() {
	this.view_type = '';
	this.timer = null;
	this.inited = false;
	
	var isIE = navigator.appVersion.match(/MSIE (.\..)/);
	this.isOldIE = (isIE && isIE[1] < 8);
	this.lastHover = null;
	this.waitRemoveHover = true;
	
	this.addControl = function(ctl) {
		ctl.cal = this;
		SUGAR.ui.registerInput(CalendarCtrl.form_name, ctl);
	};
	this.endHover = function(elt) {
		YAHOO.util.Dom.removeClass(elt, 'hover');
		if(! this.isOldIE)
			YAHOO.util.Dom.removeClass(elt, 'can_expand');
	};
	this.checkEndHover = function() {
		if(! this.waitRemoveHover || ! this.lastHover)
			return;
		this.endHover(this.lastHover);
		this.lastHover = null;
		this.waitRemoveHover = false;
	};
	this.ov = function(elt) {
		if(this.lastHover == elt) {
			if(this.waitRemoveHover)
				this.waitRemoveHover = false;
		}
		else if(this.lastHover) {
			this.endHover(this.lastHover);
			this.lastHover = null;
		}
		YAHOO.util.Dom.addClass(elt, 'hover');
		if(! this.isOldIE) {
			if(YAHOO.util.Dom.hasClass(elt, 'calendar_activity_box'))
				YAHOO.util.Dom.addClass(elt, 'can_expand');
		}
	};
	this.ot = function(elt, timeout) {
		if(typeof(timeout) == 'undefined')
			timeout = 300;
		if(! timeout)
			this.endHover(elt);
		else {
			this.checkEndHover();
			this.lastHover = elt;
			this.waitRemoveHover = true;
			var self = this;
			window.setTimeout(function() {self.checkEndHover();}, timeout);
		}
	};
	this.initCalendar = function(params) {
		this.activities = [];
		this.modules = [];
		if(params) {
			this.start_hour = params.start_hour;
			this.end_hour = params.end_hour;
			if(params.activities && this.addActivity) {
				for(var i = 0; i < params.activities.length; i++)
					this.addActivity(params.activities[i].id, params.activities[i].module);
			}
		}
	};
	this.initJsCalendar = function(callback) {
		var cal_inp = new SUGAR.ui.CalendarInput('', {
			form: document.forms[CalendarCtrl.form_name],
			name: 'target_date',
			date_format: '%Y-%m-%d',
			attach_target: 'select_day',
			onchange: callback,
			target_offset: {top: 3, align: 'right'}
		});
		cal_inp.setup();
	};		
	this.reloadCalendar = function(params) {
		CalendarCtrl.reloadFormWithParams(params, CalendarCtrl.form_name);
	};
	this.moveCalendar = function(targetDate) {
		this.reloadCalendar({view_type: this.view_type, target_date: targetDate});
	};	
	this.applyMode = function(mode) {
		if (! mode) mode = "activities";

		var formCalendar = document.forms[CalendarCtrl.form_name];
		var baseMode = formCalendar.view_mode.value;
		var userType = 'user';
		
		var targetType = 'user';
		if (mode == 'resources') targetType = 'resource';
		var params = {view_mode: mode, target_type: targetType};
		this.reloadCalendar(params);
	};
	this.showMoreList = function(targ) {
		//var width = $("div_main_settings").offsetWidth;
		//$("div_more_list").style.width = width + "px";		
		SUGAR.popups.showPopup(targ, 'div_more_list');
	};
	this.hideMoreList = function(targ) {
		SUGAR.popups.hidePopup('div_more_list');
	};
	this.toggleFlag = function(settingsForm, name) {
		var elt = settingsForm.elements[name];
		if(elt) {
			elt.value = elt.value ? 0 : 1;
			this.applySettings(settingsForm);
		}
	};
	this.applySettings = function(settingsForm) {
		var i, elt, params = {},
			inputs = ['display_meetings', 'display_calls', 'display_tasks',
				'display_project_tasks', 'display_events', 'display_leave'];
		for(i = 0; i < inputs.length; i++) {
			elt = settingsForm.elements[inputs[i]];
			if(elt) {
				if(elt.type == 'checkbox')
					params[inputs[i]] = elt.checked ? 1 : 0;
				else
					params[inputs[i]] = elt.value;
			}
		}
		this.reloadCalendar(params);
	};
	this.showUserForm = function(targ) {
		SUGAR.popups.showPopup(targ, 'div_select_user');
	};			
	this.hideUserForm = function() {
		SUGAR.popups.hidePopup('div_select_user');
	};
	this.setCurrentUser = function(settingsForm) {
		var params = {
			target_type: 'user',
			target_user_type: 'user',
			user_id: '',
			target_user_id: '',
			selected_targets: ''				
		};
		this.reloadCalendar(params);
	};
	this.popupReturnUser = function(popup_data) {
		set_return(popup_data);

		CalendarCtrlBase.applyUser($('settings_form'), popup_data);
	};
	this.applyUser = function(settingsForm, popupData) {
        if(popupData) {
            if(popupData.selection_list) {
                var uids = [];
                var pK = popupData.primary_key || 'id';
                for(var k in popupData.selection_list) {
                    uids.push(popupData.selection_list[k][pK]);
                }
                if(! uids.length)
                    return;
                settingsForm.user_id_selected.value = uids.join(',');
            }
        }
        this.setUserId(settingsForm.user_id_selected.value);
	};
	this.setUserId = function(userId, targetUserType) {
		var formCalendar = document.forms[CalendarCtrl.form_name];
		var mode = formCalendar.view_mode.value;

        var params = {
            target_type: 'user',
            target_user_type: targetUserType || 'user',
            user_id: userId,
            target_user_id: userId,
            selected_targets: userId
        };
        this.reloadCalendar(params);
	};
	this.showResourceForm = function(targ) {
		SUGAR.popups.showPopup(targ, 'div_select_resource');
	};			
	this.hideResourceForm = function() {
		SUGAR.popups.hidePopup('div_select_resource');
	};
	this.popupReturnResource = function(popup_data) {
		set_return(popup_data);
		CalendarCtrlBase.applyResource($('settings_form'), popup_data);
	};
	this.applyResource = function(settingsForm, popupData) {
        if(popupData) {
            if(popupData.selection_list) {
                var uids = [];
                var pK = popupData.primary_key || 'id';
                for(var k in popupData.selection_list) {
                    uids.push(popupData.selection_list[k][pK]);
                }
                if(! uids.length)
                    return;
                settingsForm.res_id_selected.value = uids.join(',');
            }
        }
        this.setResourceId(settingsForm.res_id_selected.value);
	};
	this.setResourceId = function(resourceId) {
		var targetType = 'resource';
		var form_calendar = document.forms[CalendarCtrl.form_name];
		var baseTargetType = form_calendar.target_type.value;
		var baseTargetId = form_calendar.target_id.value;

		var params = {
			selected_targets: resourceId,
			resource_id: resourceId,
			target_type: targetType
		};
		this.reloadCalendar(params);
		
	};
	this.showProjectForm = function(targ) {
		SUGAR.popups.showPopup(targ, 'div_select_project');		
	};
	this.hideProjectForm = function() {
		SUGAR.popups.hidePopup('div_select_project');
	};
	this.applyProject = function(settingsForm) {
		this.setProjectId(settingsForm.project_id_selected.value);
	};
	this.setProjectId = function(projectId, projectType) {
		var targetType = "user";
		var form_calendar = document.forms[CalendarCtrl.form_name];
		if(! projectId)
			projectType = 'all';
		
		var params = {
			target_type: targetType,
			project_id: projectId,
			project_type: projectType || 'project'
		};
		this.reloadCalendar(params);		
	};
    this.showTimesheetForm = function(targ) {
        SUGAR.popups.showPopup(targ, 'div_select_timesheet');
    };
    this.hideTimesheetForm = function() {
        SUGAR.popups.hidePopup('div_select_timesheet');
    };
    this.applyTimesheet = function(filterForm) {
        var id = '';
        var timesheetsRadios = filterForm.select_timesheets;
        if (timesheetsRadios.length == undefined) {
            id = timesheetsRadios.value;
        } else {
            for (var i=0; i < timesheetsRadios.length; i++) {
                if (timesheetsRadios[i].checked) {
                    id = timesheetsRadios[i].value;
                    break;
                }
            }
        }
        var params = {timesheet_id: id};
        this.reloadCalendar(params);
    };
    this.submitTimesheet = function(id, message) {
        if (id != null && id != '') {
            var _confirm = confirm(message);
            if (_confirm) {
                document.location.href = 'index.php?module=Timesheets&action=Save&record=' +id+ '&submit_timesheet=1';
                return true;
            } else {
                return false;
            }
        }
    };
    this.approveHours = function(id, operation, message) {
        if (id != null && id != '') {
            var _confirm = confirm(message);
            if (_confirm) {
                var appAction = 'all';
                if (operation == 'reject') appAction = 'reject-all';
                document.location.href = 'index.php?module=Timesheets&action=ApproveHours&record=' +id+ '&approve_action=' + appAction;
                return true;
            } else {
                return false;
            }
        }
    };
	this.toggleSelectTargetType = function(viewMode, entry) {
		var divMain = $("div_"+entry);
		var divLabel = $("div_"+entry+"_label");
		var view = (viewMode == 'all') ? "none" : "";
		divMain.style.display = view;
		divLabel.style.display = view;		
	};
    this.addGridEntry = function(settingsForm, type) {        
        var params = {};
        if (type == "resource") {
            var resId = settingsForm.res_id_selected.value;
            params = {grid_res_id: resId};
        } else {
            var userId = settingsForm.user_id_selected.value;
            params = {grid_user_id: userId};
        }
        this.reloadCalendar(params);
    };
    this.deleteGridEntry = function(id, type) {
        var params = {};
        if (type == "resource") {
            params = {grid_res_id_del: id};
        } else {
            params = {grid_user_id_del: id};
        }
        this.reloadCalendar(params);
    };
	this.extendTo = function(cls, map) {
		cls.prototype = this;
		for(var i in this)
			cls[i] = this[i];
		for(var i in map)
			cls[i] = map[i];
	};
};


//////////////////////////////////////////////////////////////////
// for Day calendar
//////////////////////////////////////////////////////////////////
var DayCalendarCtrl = new function() {
 	var zIndexWork;
	var startCell = null;

    this.activities = [];
    this.modules = [];

	CalendarCtrlBase.extendTo(this, {
		view_type: 'day',

        addActivity:function(id, module) {
            DayCalendarCtrl.activities.push(id);
            DayCalendarCtrl.modules[id] = module;
        },

        makeDragable:function() {
            if (this.activities.length > 0) {
                var slots = [], players = [], Event = YAHOO.util.Event, DDM = YAHOO.util.DDM, div;
                
                DDM.unregAll();

                //Set d'n'd targets
                slots.push(new YAHOO.util.DDTarget("calendar_all_day_content", "all_day_slots"));

                for (var hour = this.start_hour; hour <= this.end_hour; hour++) {
                    slots.push(new YAHOO.util.DDTarget("calendar_hour_content-" + hour, "timeslots"));
                    slots.push(new YAHOO.util.DDTarget("calendar_hour_content_half-" + hour, "timeslots"));
                }

                for (var i = 0; i < this.activities.length; i++) {
                    players[i] = new YAHOO.example.DDPlayer('div_act_' + this.activities[i], "timeslots");
                    if (this.modules[this.activities[i]] == 'Meetings')
                        players[i].addToGroup("all_day_slots");
                    div = $('div_act_' + this.activities[i]);
                    if(div) {
                    	YDom.addClass(div, 'drag-outer');
                    	div.appendChild(createElement2('div', {className: 'drag-handle hover-show'},
                    			createElement2('div', {className: 'input-icon'})
                    		));
                    }
                }

                if ( document.getElementById("ddmode")) {
                    DDM.mode = document.getElementById("ddmode").selectedIndex;
                    Event.on("ddmode", "change", function(e) {
                        YAHOO.util.DDM.mode = this.selectedIndex;
                    });
                }
            }
        },

        mouseDown:function(table, e) {
			if (!e) var e = window.event;
			if( (e.which || e.button) != 1) return;

			this.startCell = e.srcElement? e.srcElement: e.target;
			if(this.startCell.tagName != "TD"){
				this.startCell = null;
				return;
			}
			this.mouseMove(table, e);
		},

		mouseUp:function(table, e) {
			if (!e) var e = window.event;

			var endCell = e.srcElement?e.srcElement:e.target;
			if(!(endCell.tagName=="TD" && this.startCell)) {
				return false;
			}

			var from = DayCalendarCtrl.getCellPos(table, this.startCell);
			var to = DayCalendarCtrl.getCellPos(table, endCell);
			if(!from || !to) {
				return false;
			}
			this.startCell = null;

			var target_id = document.forms[CalendarCtrl.form_name].target_id.value;
			var target_type = document.forms[CalendarCtrl.form_name].target_type.value;
			var target_date = document.forms[CalendarCtrl.form_name].target_date.value;
            var timesheet_id = document.forms[CalendarCtrl.form_name].timesheet_id.value;
			var mode = document.forms[CalendarCtrl.form_name].view_mode.value;
			var target_user_id = "";
			var target_res_id = "";
			if(target_type == 'user') {
				target_user_id = target_id;
			} else if (target_type == 'resource') {
				target_res_id = target_id;
			}

			var topRow = from.row;
			var bottomRow = to.row;
			if(from.row > to.row) {
				topRow = to.row;
				bottomRow = from.row;
			}

			var startHour = Math.floor(topRow / 2);
			var startMin = 0;
			if(topRow % 2 == 1) {
				startMin = 30;
			}
			var diff = bottomRow - topRow + 1;
			var durHour = Math.floor(diff / 2);
			var durMin = 0;
			if(diff % 2 == 1) {
				durMin = 30;
			}
			if (mode != 'projects') {
				var is_daylong = (from.is_daylong || to.is_daylong) ? 1 : 0;
                if (mode != 'timesheets') {
				    MeetingsEditView.showNew(endCell, target_user_id, target_res_id, target_date, startHour, startMin, durHour, durMin, is_daylong);
                } else {
                    HoursEditView.showNew(endCell, target_user_id, timesheet_id, target_date, startHour, startMin, durHour, durMin);
                }
			}
			var x, y, cells;
			for(y=0; y<table.rows.length; y++){
				row = table.rows[y];
				for(x=1; x<row.cells.length; x++)
					YAHOO.util.Dom.removeClass(row.cells[x], "markRow");
			}
		},

		mouseMove:function (table, e){
			if (!e) var e = window.event;

			var endCell = e.srcElement?e.srcElement:e.target;
			if(!(endCell.tagName=="TD" && this.startCell)) {
				return false;
			}

			var from = DayCalendarCtrl.getCellPos(table, this.startCell);
			var to = DayCalendarCtrl.getCellPos(table, endCell);
			if(!from || !to) {
				return false;
			}

			var x=1, y, cells;
			for(y=0; y<table.rows.length; y++){
				row = table.rows.item(y);
				if((from.rawRow-y)*(y-to.rawRow)>=0) {
					YAHOO.util.Dom.addClass(row.cells[x], "markRow");
				} else {
					YAHOO.util.Dom.removeClass(row.cells[x], "markRow");
				}
			}
		},

		getCellPos:function(table, cell){
			var pos = new Object();
			if(cell.nodeName == "TD") {
				var x, y, cells;
				for(y=0; y<table.rows.length; y++){
					row = table.rows.item(y);
					for(x=0; x<row.cells.length; x++){
						if(row.cells.item(x) == cell){
							if (y < 1) {
								pos.is_daylong = true;
							}
							pos.rawRow = y;
							pos.row = y + Math.floor(dayStartHour) * 2 - 1;
							pos.col = x;
							return pos;
						}
					}
				}
			}
			return null;
		},

		initCalendar: function(acts) {
			CalendarCtrlBase.initCalendar.call(this, acts);
			var div_calendar = $("div_calendar");
			div_calendar.scrollTop=320;

			this.initJsCalendar(function() {
				DayCalendarCtrl.moveCalendar(this.getDateValue());
			});
			this.makeDragable();
        },

		asyncCalendarBody:function(targetDate, targetId, targetType, teamId) {
			var params = {
				view_type: this.view_type,
				target_date: targetDate, target_id: targetId,
				target_type: targetType, target_team_id: teamId};
			DayCalendarCtrl.reloadCalendar(params);
        },

		toggleTaskList:function(isViewList) {
			var div_task_count = $("div_task_count");
			var div_task_list = $("div_task_list");
			if(isViewList) {
				div_task_count.style.display = "none";
				div_task_list.style.display = "";
			} else {
				div_task_count.style.display = "";
				div_task_list.style.display = "none";
			}
		}
	});
}();

//////////////////////////////////////////////////////////////////
// for week calendar
//////////////////////////////////////////////////////////////////
var WeekCalendarCtrl = new function() {
	var zIndexWork;
	var dds;
	var toDayWeekIndex = -1;
	var firstDate = "";
	var weekDays;
	var summaryTimeout;

	this.activities = [];
    this.modules = [];

    CalendarCtrlBase.extendTo(this, {
		view_type: 'week',

        addActivity:function(id, module) {
            WeekCalendarCtrl.activities.push(id);
            WeekCalendarCtrl.modules[id] = module;
        },

        makeDragable:function() {
            if (this.activities.length > 0) {
                var slots = [], players = [], Event = YAHOO.util.Event, DDM = YAHOO.util.DDM, day_index = 0, div;
                
                DDM.unregAll();

                //Set d'n'd targets
                if(this.weekDays)
                for (day_index = 0; day_index < this.weekDays.length; day_index++) {
                    slots.push(new YAHOO.util.DDTarget("calendar_all_day_content-" + this.weekDays[day_index], "all_day_slots"));
                }

                for (var hour = this.start_hour; hour <= this.end_hour; hour++) {
                	if(this.weekDays)
                    for (day_index = 0; day_index < this.weekDays.length; day_index++) {
                        slots.push(new YAHOO.util.DDTarget("calendar_hour_content-" +hour+ '-' +this.weekDays[day_index], "timeslots"));
                        slots.push(new YAHOO.util.DDTarget("calendar_hour_content_half-" +hour+ '-' +this.weekDays[day_index], "timeslots"));
                    }
                }

                for (var i = 0; i < this.activities.length; i++) {
                    players[i] = new YAHOO.example.DDPlayer('div_act_' + this.activities[i], "timeslots");
                    if (this.modules[this.activities[i]] == 'Meetings')
                        players[i].addToGroup("all_day_slots");
                    div = $('div_act_' + this.activities[i]);
                    if(div) {
                    	YDom.addClass(div, 'drag-outer');
                    	div.appendChild(createElement2('div', {className: 'drag-handle hover-show'},
                    			createElement2('div', {className: 'input-icon'})
                    		));
                    }
                }

                if ( document.getElementById("ddmode")) {
                    DDM.mode = document.getElementById("ddmode").selectedIndex;
                    Event.on("ddmode", "change", function(e) {
                        YAHOO.util.DDM.mode = this.selectedIndex;
                    });
                }
            }
        },

        displaySummary : function(offset) {
			var target_id = 'summary_details_' + offset;
			var divs = document.getElementsByName('summary_details');
			for (var i in divs) {
				var div = divs[i];
				try {
					if (div.id == target_id) {
						div.style.zIndex = 1000;
						div.style.display = '';
					} else {
						div.style.display = 'none';
					}
				} catch(e) {
				}
			}
			if (this.summaryTimeout) {
				window.clearTimeout(this.summaryTimeout);
				this.summaryTimeout = null;
			}
		},

		summaryLeaved : function() {
			this.summaryTimeout = window.setTimeout('WeekCalendarCtrl.displaySummary(-1)', 500);
		},

		mouseDown:function(table, e) {
			if (!e) var e = window.event;
				
			this.startCell = e.srcElement? e.srcElement: e.target;
			if(this.startCell.tagName != "TD"){
				this.startCell = null;
				return;
			}
			WeekCalendarCtrl.mouseMove(table, e);
		},
		
		mouseUp:function(table, e) {
			if (!e) var e = window.event;

			var endCell = e.srcElement?e.srcElement:e.target;
			if(!(endCell.tagName=="TD" && this.startCell)) {
				return false;
			}
				
			var from = WeekCalendarCtrl.getCellPos(table, this.startCell);
			var to = WeekCalendarCtrl.getCellPos(table, endCell);
			if(!from || !to) {
				return false;
			}
			this.startCell = null;
			
			if(to.col == 0 || from.col == 0 || from.rawRow == 0) {
				return false;
			}
			
			var target_id = document.forms[CalendarCtrl.form_name].target_id.value;
			var target_type = document.forms[CalendarCtrl.form_name].target_type.value;
			var mode = document.forms[CalendarCtrl.form_name].view_mode.value;						
			var target_date = WeekCalendarCtrl.weekDays[(from.col-1)];
            var timesheet_id = document.forms[CalendarCtrl.form_name].timesheet_id.value;

			var target_user_id = "";
			var target_res_id = "";
			if(target_type == 'user') {
				target_user_id = target_id;
			} else if (target_type == 'resource') {
				target_res_id = target_id;
			}

			var topRow = from.row;
			var bottomRow = to.row;
			if(from.row > to.row) {
				topRow = to.row;
				bottomRow = from.row;
			}
			
			var startHour = Math.floor(topRow / 2);
			var startMin = 0;
			if(topRow % 2 == 1) {
				startMin = 30;
			}
			var diff = bottomRow - topRow + 1; 
			var durHour = Math.floor(diff / 2);
			var durMin = 0;
			if(diff % 2 == 1) {
				durMin = 30;
			}
			if (mode != "projects") {			
				var is_daylong = (from.is_daylong || to.is_daylong) ? 1 : 0;
                if (mode != 'timesheets') {
				    MeetingsEditView.showNew(endCell, target_user_id, target_res_id, target_date, startHour, startMin, durHour, durMin, is_daylong);
                } else {
                    HoursEditView.showNew(endCell, target_user_id, timesheet_id, target_date, startHour, startMin, durHour, durMin);
                }
			}
			var x, y, cells;
			for(y=0; y<table.rows.length; y++){
				row = table.rows[y];
				for(x=1; x<row.cells.length; x++)
					YAHOO.util.Dom.removeClass(row.cells[x], "markRow");
			}
		},
		
		mouseMove:function (table, e){
			if (!e) var e = window.event;
				
			var endCell = e.srcElement?e.srcElement:e.target;
			if(!(endCell.tagName=="TD" && this.startCell)) {
				return false;
			}
				
			var from = WeekCalendarCtrl.getCellPos(table, this.startCell);
			var to = WeekCalendarCtrl.getCellPos(table, endCell);
			if(!from || !to) {
				return false;
			}
			if(from.col != to.col) {
				to.col = from.col;
			}
			
			var x, y, cells;
			for(y=1; y<table.rows.length; y++){ //skip weekday row
				row = table.rows.item(y);
				//x=1:except hour row
				for(x=1; x<row.cells.length; x++){
					if((from.rawRow-y)*(y-to.rawRow)>=0 && (from.col-x)*(x-to.col)>=0) {
						YAHOO.util.Dom.addClass(row.cells[x], "markRow");
					} else {
						YAHOO.util.Dom.removeClass(row.cells[x], "markRow");
					}
				}
			}
		},
		
		getCellPos:function(table, cell){
			var pos = new Object();
			if(cell.nodeName == "TD") {
				var x, y, cells;
				for(y=0; y<table.rows.length; y++){
					row = table.rows.item(y);
					for(x=0; x<row.cells.length; x++){
						if(row.cells.item(x) == cell){
							if (y < 2) {
								pos.is_daylong = true;
							}
							pos.rawRow = y;
							pos.row = y + Math.floor(dayStartHour) * 2 - 2;
							pos.col = x;
							return pos;
						}
					}
				}
			}
			return null;
		},
		
		initCalendar:function(acts) {
			CalendarCtrlBase.initCalendar.call(this, acts);
			var div_calendar = $("div_calendar");
			div_calendar.scrollTop = 320;
			
			var formCalendar = document.forms[CalendarCtrl.form_name];
			WeekCalendarCtrl.toDayWeekIndex = parseInt(formCalendar.today_weekindex.value);
			WeekCalendarCtrl.firstDate = formCalendar.week_first_date.value;

			WeekCalendarCtrl.weekDays = Array();
			for(var i = 0; i < 7; i++) {
				var elt = 'week_date_' + i;
				if(formCalendar[elt])
					WeekCalendarCtrl.weekDays[i] = formCalendar[elt].value;
			}

            this.initJsCalendar(function() {
				WeekCalendarCtrl.moveCalendar(this.getDateValue());
            });
            
            this.makeDragable();
        },
		
		asyncCalendarBody:function(targetDate, targetId, targetType, teamId) {
			var params = {
				view_type: this.view_type, target_date: targetDate,
				target_id: targetId, selected_targets: targetId,
				target_type: targetType, target_team_id: teamId};
			WeekCalendarCtrl.reloadCalendar(params);
		}
	});
}();


//////////////////////////////////////////////////////////////////
// for Month calendar
//////////////////////////////////////////////////////////////////
var MonthCalendarCtrl = new function() {
	CalendarCtrlBase.extendTo(this, {
		view_type: 'month',

		initCalendar:function() {
			CalendarCtrlBase.initCalendar.call(this);
			this.initJsCalendar(function() {
				MonthCalendarCtrl.moveCalendar(this.getDateValue());
			});
		},
		
		asyncCalendarBody:function(targetDate, userId, teamId) {
			var params = {
				view_type: this.view_type, target_date: targetDate,
				target_user_id: userId, target_team_id: teamId};
			MonthCalendarCtrl.reloadCalendar(params);
		}
	});
}();

//////////////////////////////////////////////////////////////////
// for Year calendar
//////////////////////////////////////////////////////////////////
var YearCalendarCtrl = new function() {
 	var zIndexWork;	
	
	CalendarCtrlBase.extendTo(this, {
		view_type: 'year',

		initCalendar:function() {
			CalendarCtrlBase.initCalendar.call(this);
			this.initJsCalendar(function() {
				YearCalendarCtrl.moveCalendar(this.getDateValue());
			});
		},
		
		asyncCalendarBody:function(targetDate, targetId, targetType, teamId) {
			var params = {
				view_type: this.view_type, target_date: targetDate,
				target_id: targetId, target_type: targetType,
				target_team_id: teamId};
			YearCalendarCtrl.reloadCalendar(params);
		}
	});
}();

//////////////////////////////////////////////////////////////////
// for Team Day calendar
//////////////////////////////////////////////////////////////////
var TeamDayCalendarCtrl = new function() {

	CalendarCtrlBase.extendTo(this, {
		view_type: 'team_day',

		initCalendar:function() {
			CalendarCtrlBase.initCalendar.call(this);
			this.initJsCalendar(function() {
				CalendarCtrl.asyncCalendarBody('team_day', this.getDateValue(), TeamDayCalendarCtrl.initCalendar);
			});
		},
		
		asyncCalendarBody:function(targetDate, teamId) {
			var params = {
				view_type: this.view_type,
				target_date: targetDate,
				target_team_id: teamId};
			TeamDayCalendarCtrl.reloadCalendar(params);
		},

		teamOnChange:function(teamId) {
			TeamDayCalendarCtrl.reloadCalendar({target_team_id: teamId});
		}
		
	});
}();



//////////////////////////////////////////////////////////////////
// for Team Week calendar
//////////////////////////////////////////////////////////////////
var TeamWeekCalendarCtrl = new function() {
	CalendarCtrlBase.extendTo(this, {
		view_type: 'team_week',

		initCalendar:function() {
			CalendarCtrlBase.initCalendar.call(this);
			this.initJsCalendar(function() {
				TeamWeekCalendarCtrl.moveCalendar(this.getDateValue());
			});
		},
		
		teamPopup:function() {
			var popupdata = {
				'call_back_function':'TeamWeekCalendarCtrl.setTeamReturn',
				'form_name':'form_calendar',
				'field_to_name_array':{
					'id':'target_team_id',
					'name':'span_team_name'
				}
			};
		},
		
		setTeamReturn:function(popup_reply_data) {
			var form_name = popup_reply_data.form_name;
			var name_to_value_array = popup_reply_data.name_to_value_array;
			
			for (var the_key in name_to_value_array) {
				if(the_key == 'toJSON') {
					/* just ignore */
				} else if(the_key == 'target_team_id') {
					var element = $(the_key);
					element.value = name_to_value_array[the_key];
				} else if(the_key == 'span_team_name') {
					var element = $(the_key);
					element.innerHTML = name_to_value_array[the_key];
				}
			}
			TeamWeekCalendarCtrl.reloadCalendar();
		},
		
		asyncCalendarBody:function(targetDate, teamId) {
			var params = {
				view_type: this.view_type,
				target_date: targetDate,
				target_team_id: teamId};
			TeamWeekCalendarCtrl.reloadCalendar(params);
		},
		
		teamOnChange:function(teamId) {
			document.forms[CalendarCtrl.form_name].target_team_id.value = teamId;
			TeamWeekCalendarCtrl.reloadCalendar();
		}
	});
}();


//////////////////////////////////////////////////////////////////
// for Resource Day calendar
//////////////////////////////////////////////////////////////////
var ResourceDayCalendarCtrl = new function() {
	CalendarCtrlBase.extendTo(this, {
		view_type: 'resource_day',

		initCalendar:function() {
			CalendarCtrlBase.initCalendar.call(this);
			this.initJsCalendar(function() {
				//CalendarCtrl.asyncCalendarBody('resource_day', this.getDateValue(), ResourceDayCalendarCtrl.initCalendar);
				ResourceDayCalendarCtrl.moveCalendar(this.getDateValue());
			});
		},
		
		asyncCalendarBody:function(targetDate) {
			var params = {view_type: this.view_type, target_date: targetDate};
			ResourceDayCalendarCtrl.reloadCalendar(params);
		},
		
		resTypeOnChange:function(resType) {
			ResourceDayCalendarCtrl.reloadCalendar({target_res_type: resType});
		}
	});
}();

//////////////////////////////////////////////////////////////////
// for Resource Week calendar
//////////////////////////////////////////////////////////////////
var ResourceWeekCalendarCtrl = new function() {
	CalendarCtrlBase.extendTo(this, {
		view_type: 'resource_week',

		initCalendar:function() {
			CalendarCtrlBase.initCalendar.call(this);
			this.initJsCalendar(function() {
				ResourceWeekCalendarCtrl.moveCalendar(this.getDateValue());
			});
		},
		
		resTypeOnChange:function(resType) {
			ResourceDayCalendarCtrl.reloadCalendar({target_res_type: resType});
		},
		
		asyncCalendarBody:function(targetDate, teamId) {
			var params = {
				view_type: this.view_type,
				target_date: targetDate,
				target_team_id: teamId};
			ResourceWeekCalendarCtrl.reloadCalendar(params);
		}
	});
}();



var UsersCalendarCtrl = function() {
	return {
		ov:function(e) {
			YAHOO.util.Dom.addClass(e, 'hover');
		},
		ot:function(e) {
			YAHOO.util.Dom.removeClass(e, 'hover');
		}
	};
}();


var setPrintLink = function(elt) {
	var printUrl = $('print_url');
	if (printUrl) {
		if(elt && (elt = $(elt)))
			elt.href = printUrl.value;
		else {
			var printLinks = document.getElementsByName('print_link');
			for  (var i in printLinks) {
				printLinks[i].href =  printUrl.value;
			}
		}
	}
};


var CalUserSelect = function(id, params) {
	if(! params) params = {};
	params.icon_key = 'icon';
	params.options = {
		width: 220,
		keys: ['', 'select'],
		values: [
			{label: mod_string('LBL_ME', 'Calendar'), icon: 'icon-user'},
			{label: app_string('LBL_FILTER_OWNER_SELECT'), icon: 'icon-popup'}]};
	if(params.all_users) {
		params.options.keys.splice(0, 0, 'all');
		params.options.values.splice(0, 0, {label: mod_string('LBL_ALL_USERS_BUTTON_LABEL', 'Calendar'), icon: 'icon-users'});
	}
	if(params.user_id == 'all')
		params.init_value = 'all';
	else if(params.user_id && params.user_name && params.user_name != 'Me') {
		params.options.keys.splice(1, 0, params.user_id);
		params.options.values.splice(1, 0, {label: params.user_name, icon: 'icon-user'});
		params.init_value = params.user_id;
	} else
		params.init_value = '';
	SUGAR.ui.SelectInput.call(this, id, params);
};
YLang.extend(CalUserSelect, SUGAR.ui.SelectInput, {
	userUpdate: function(key, value) {
		this.update(key, value);
	},
	update: function(key, value, silent) {
		if(key == 'select')
			this.showSelectUser();
		else if(key == 'all')
			this.cal.setUserId('', 'all');
		else
			this.cal.setUserId(key);
	},
	showSelectUser: function() {
		var request_data = {
				form_name: this.form.getAttribute('id') || this.form.getAttribute('name'),
				call_back_function: this.popup_callback || 'ui_popup_return',
				passthru_data: {ui_input_name: this.name || this.id},
				field_to_name_array: this.field_to_name || {id: 'id', label: 'name'}
			},
			params = {module: 'Users', request_data: request_data, inline: true};
		open_popup_window(params);
	}
});


var CalResSelect = function(id, params) {
	if(! params) params = {};
	params.icon_key = 'icon';
	params.options = {
		width: 220,
		keys: ['', 'select'],
		values: [
			{label: mod_string('LBL_RESOURCE_ALL', 'Calendar'), icon: 'theme-icon bean-Resource'},
			{label: mod_string('LBL_SELECT_RESOURCE', 'Calendar'), icon: 'icon-popup'}]};
	if(params.res_id && params.res_name) {
		params.options.keys.splice(1, 0, params.res_id);
		params.options.values.splice(1, 0, {label: params.res_name, icon: 'theme-icon bean-Resource'});
		params.init_value = params.res_id;
	} else
		params.init_value = '';
	SUGAR.ui.SelectInput.call(this, id, params);
};
YLang.extend(CalResSelect, SUGAR.ui.SelectInput, {
	userUpdate: function(key, value) {
		this.update(key, value);
	},
	update: function(key, value, silent) {
		if(key == 'select')
			this.showSelectRes();
		else {
			this.cal.setResourceId(key || '');
		}
	},
	showSelectRes: function() {
		var request_data = {
				form_name: this.form.getAttribute('id') || this.form.getAttribute('name'),
				call_back_function: this.popup_callback || 'ui_popup_return',
				passthru_data: {ui_input_name: this.name || this.id},
				field_to_name_array: this.field_to_name || {id: 'id', label: 'name'}
			},
			params = {module: 'Resources', request_data: request_data, inline: true, min_width: 700};
		open_popup_window(params);
	}
});


var CalProjectSelect = function(id, params) {
	if(! params) params = {};
	params.icon_key = 'icon';
	params.options = {
		width: 220,
		keys: ['', 'select'],
		values: [
			{label: mod_string('LBL_PROJECT_ALL', 'Calendar'), icon: 'theme-icon bean-Project'},
			{label: mod_string('LBL_SELECT_PROJECT', 'Calendar'), icon: 'icon-popup'}]};
	if(params.proj_id && params.proj_name) {
		params.options.keys.splice(1, 0, params.proj_id);
		params.options.values.splice(1, 0, {label: params.proj_name, icon: 'theme-icon bean-Project'});
		params.init_value = params.proj_id;
	} else
		params.init_value = '';
	SUGAR.ui.SelectInput.call(this, id, params);
};
YLang.extend(CalProjectSelect, SUGAR.ui.SelectInput, {
	userUpdate: function(key, value) {
		this.update(key, value);
	},
	update: function(key, value, silent) {
		if(key == 'select')
			this.showSelectProject();
		else {
			this.cal.setProjectId(key || '');
		}
	},
	showSelectProject: function() {
		var request_data = {
				form_name: this.form.getAttribute('id') || this.form.getAttribute('name'),
				call_back_function: this.popup_callback || 'ui_popup_return',
				passthru_data: {ui_input_name: this.name || this.id},
				field_to_name_array: this.field_to_name || {id: 'id', label: 'name'}
			},
			params = {module: 'Project', request_data: request_data, inline: true, min_width: 700};
		open_popup_window(params);
	}
});

(function() {

    YAHOO.example.DDPlayer = function(id, sGroup, config) {
        YAHOO.example.DDPlayer.superclass.constructor.apply(this, arguments);
        this.initPlayer(id, sGroup, config);
    };

    YAHOO.extend(YAHOO.example.DDPlayer, YAHOO.util.DDProxy, {

        TYPE: "DDPlayer",
        calCtrl: '',

        initPlayer: function(id, sGroup, config) {
            if (!id) {
                return;
            }

            var el = this.getDragEl();
            YAHOO.util.Dom.setStyle(el, "borderColor", "transparent");
            YAHOO.util.Dom.setStyle(el, "opacity", 0.76);

            // specify that this is not currently a drop target
            this.isTarget = false;
            
            this.calCtrl = window[CalendarCtrl.currentCls];
            this.type = YAHOO.example.DDPlayer.TYPE;
            this.slot = null;

            this.startPos = YAHOO.util.Dom.getXY( this.getEl() );

            this.initConstraints();
        },

        initConstraints: function() {
            el = this.getEl();
            //Get the top, right, bottom and left positions
            var region = YAHOO.util.Dom.getRegion('calendar_outer_table');
            //Get the xy position of it
            var xy = YAHOO.util.Dom.getXY(el);
            var height = parseInt(YAHOO.util.Dom.getStyle(el, 'height'), 10);
            //Set left to x minus left
            var left = xy[0] - region.left - 50;

            var right_index = 205;
            var top_index = 0

            if (this.calCtrl.view_type == 'week') {
                right_index = 117;
                top_index = 22;
            }

            //Set right to right minus x minus width
            var right = region.right - xy[0] - right_index;
            //Set top to y minus top
            var top = xy[1] - region.top - top_index;
            //Set bottom to bottom minus y minus height
            var bottom = region.bottom - xy[1] - height;
            //Set the constraints based on the above calculations
            this.setXConstraint(left, right);
            this.setYConstraint(top, bottom);
        },

        startDrag: function(x, y) {
            var Dom = YAHOO.util.Dom;

            var dragEl = this.getDragEl();
            var clickEl = this.getEl();

            dragEl.innerHTML = clickEl.innerHTML;
            dragEl.className = clickEl.className;

            Dom.setStyle(dragEl, "color",  Dom.getStyle(clickEl, "color"));
            Dom.setStyle(dragEl, "backgroundColor", Dom.getStyle(clickEl, "backgroundColor"));

            Dom.setStyle(clickEl, "opacity", 0.1);
        },

        getTargetDomRef: function(oDD) {
            if (oDD.player) {
                return oDD.player.getEl();
            } else {
                return oDD.getEl();
            }
        },

        endDrag: function(e) {
            // reset the linked element styles
            var elt = this.getEl();
            YAHOO.util.Dom.setStyle(elt, "opacity", 1);
            var dndCalendarObject = this.calCtrl;

            if (YAHOO.util.Dom.getStyle(elt, "width") == '400px')
                YAHOO.util.Dom.setStyle(elt, "width", '200px');

            function ready() {
                var row = this.getResult();
                if (row && row.result == 'ok')
                    dndCalendarObject.reloadCalendar();
            }
            if (dndCalendarObject) {
                var target = this.slot;
                if (target) {
                    var id = elt.id;
                    var activity_id = id.replace('div_act_', '');
                    var query_params = {'activity_module': dndCalendarObject.modules[activity_id], 'time_param': target.id, 'activity_id': activity_id};
                    var req = new SUGAR.conn.JSONRequest('move_calendar_activity', {status_msg: app_string('LBL_LOADING')}, query_params);
                    req.fetch(ready);
                }
            }
        },

        onDragDrop: function(e, id) {
            // get the drag and drop object that was targeted
            var oDD;

            if ("string" == typeof id) {
                oDD = YAHOO.util.DDM.getDDById(id);
            } else {
                oDD = YAHOO.util.DDM.getBestMatch(id);
            }

            var el = this.getEl();

            // check if the slot has a player in it already
            if (oDD.player) {
                // check if the dragged player was already in a slot
                if (this.slot) {
                    // check to see if the player that is already in the
                    // slot can go to the slot the dragged player is in
                    if ( YAHOO.util.DDM.isLegalTarget(oDD.player, this.slot) ) {
                        YAHOO.log("swapping player positions", "info", "example");
                        this.slot.player = oDD.player;
                        oDD.player.slot = this.slot;
                    } else {
                        YAHOO.log("moving player in slot back to start", "info", "example");
                        YAHOO.util.Dom.setXY(oDD.player.getEl(), oDD.player.startPos);
                        this.slot.player = null;
                        oDD.player.slot = null
                    }
                }
            } else {
                // Move the player into the emply slot
                // I may be moving off a slot so I need to clear the player ref
                if (this.slot) {
                    this.slot.player = null;
                }
            }

            if (this.calCtrl.view_type == 'day') {
                YAHOO.util.Dom.setX(el, YAHOO.util.Dom.getX( this.getDragEl() ));
                YAHOO.util.Dom.setY(el, YAHOO.util.Dom.getY( oDD.getEl() ));
            } else {
                YAHOO.util.DDM.moveToEl(el, oDD.getEl());
            }

            this.slot = oDD;
            this.slot.player = this;
        },

        swap: function(el1, el2) {
            var Dom = YAHOO.util.Dom;
            var pos1 = Dom.getXY(el1);
            var pos2 = Dom.getXY(el2);
            Dom.setXY(el1, pos2);
            Dom.setXY(el2, pos1);
        },

        onDragOver: function(e, id) { },

        onDrag: function(e, id) { }

    });
})();

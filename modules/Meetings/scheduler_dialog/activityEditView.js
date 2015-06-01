var MeetingsEditView = function() {
	var module = 'Meetings';
    var form;
    var tabs;
	var popupDialog;

	return {
		
		initEditView:function() {
            MeetingsEditView.form = SUGAR.ui.getForm('asyncEditView');
            MeetingsEditView.tabs = SUGAR.ui.getFormInput(MeetingsEditView.form, 'form-tabs');
            if (MeetingsEditView.tabs)
                MeetingsEditView.tabs.enableDisableTab('Booking', true);

            MeetingsEditView.module = MeetingsEditView.form.edit_module.value;

            if (MeetingsEditView.module == 'Meetings') {
                var duration_inp = SUGAR.ui.getFormInput(MeetingsEditView.form, 'duration');
                if (duration_inp)
                    duration_inp.all_day_field.onchange = function(evt) { MeetingsEditView.toggleDayLong(); }

                if (MeetingsEditView.form.record.value == '')
                    MeetingsEditView.toggleDayLong();
            }
		},
		
		show:function(targetElementName, module, activityId, userId, dateStart, timeHourStart, timeMinuteStart, resourceId) {
			this.showDialog({
				module: module,
				record: activityId,
				assigned_user_id: userId,
				resource_id: resourceId,
				date_start: dateStart,
				time_hour_start: timeHourStart,
				time_minute_start: timeMinuteStart
			});
		},

		showNew:function(targetElement, userId, resourceId, dateStart, timeHourStart, timeMinuteStart, durHour, durMin, is_daylong, user_ids, resource_ids) {
			this.showDialog({
				assigned_user_id: userId,
				resource_id: resourceId,
				date_start: dateStart,
				date_is_gmt: 1,
				time_hour_start: timeHourStart,
				time_minute_start: timeMinuteStart,
				duration_hours: durHour,
				duration_minutes: durMin,
				is_daylong: is_daylong,
				user_ids: user_ids,
				resource_ids: resource_ids
			});
		},
		
		showDialog: function(params) {
			var module = params.module || window.defaultEditModule || 'Meetings';
			if (module != 'Meetings' && module != 'Calls')
				return;
			params.module = 'Meetings';
			params.action = 'activityEditView';
			params.edit_model = MeetingsEditView.getModel(module);
			this.setupPopupDialog();
			
			function ready(data) {
				MeetingsEditView.initEditView();
				if(params.resource_id) MeetingsEditView.tabs.enableDisableTab('Call', true);

                if (MeetingsEditView.form.record.value != '') {
                    MeetingsEditView.tabs.enableDisableTab('Meeting', true);
                    MeetingsEditView.tabs.enableDisableTab('Call', true);
                }
			}

			this.popupDialog.fetchContent('async.php', {method: 'POST', postData: params}, ready, true);
		},

        getModel:function(module) {
            var model = 'Meeting';
            if(module == 'Calls') model = 'Call';
            return model;
        },

        setupPopupDialog:function() {
            if(! this.popupDialog) {
                this.popupDialog = new SUGAR.ui.Dialog('activityEditView', { width: '661px', resizable: false, draggable: true });
                this.popupDialog.onshow = function() {
                    MeetingsEditView.toggleButtons(true);
                    var name = document.asyncEditView && document.asyncEditView.name;
                    if(name && name.focus && ! name.value) name.focus();
                }
            }
        },

        setTitle:function(title) {
            if(this.popupDialog)
                this.popupDialog.setTitle(title || '');
        },

        toggleButtons:function(state) {
            var targetForm = window.document.asyncEditView;
            if(! targetForm) return;
            //duplicate check
            for (i = 0; i < targetForm.elements.length; i++) {
                var elt = targetForm.elements[i];
                if(elt.type == 'button' || elt.type == 'submit') {
                    targetForm.elements[i].disabled = state ? false : true;
                }
            }
        },
        
		hide:function() {
			if(this.popupDialog) {
				this.popupDialog.close();
				this.popupDialog = null;
			}
		},
		
		save:function() {
			var vresult;
			if(! (vresult = SUGAR.ui.validateForm(MeetingsEditView.form)).status) {
				SUGAR.ui.alertInvalidForm(vresult);
				return false;
			}
			
			var success = function(data) {
				var result = null;
                MeetingsEditView.hide();
				try {
					var viewType = window.document.form_calendar.view_type.value;
					CalendarCtrl.reload(viewType);
				} catch (err) {
                    document.location.reload(true);
				}
			};
			
			MeetingsEditView.toggleButtons();
            var resultDiv = this.popupDialog.getContentElement();

            return SUGAR.ui.sendForm(MeetingsEditView.form, {record_perform: 'save'}, {no_validate: true, resultDiv: resultDiv, receiveCallback: success}, false, false);
		},

		duplicate:function() {
            function ready() {
                MeetingsEditView.initEditView();
            }
            var resultDiv = this.popupDialog.getContentElement();
            SUGAR.ui.sendForm(MeetingsEditView.form, {record_perform: 'duplicate'}, {no_validate: true, resultDiv: resultDiv, receiveCallback: ready}, false, false);
		},

		deleteActivity:function() {
			var success = function(data) {
				MeetingsEditView.hide();
				try {
					var viewType = window.document.form_calendar.view_type.value;
					CalendarCtrl.reload(viewType);
				} catch (err) {
                    document.location.reload(true);
				}
			};
			
			var msgConfirm = SUGAR.language.get('app_strings', 'NTC_DELETE_CONFIRMATION_MULTIPLE');
			if(confirm(msgConfirm) == false) {
				return false;
			}
			
			MeetingsEditView.toggleButtons();
			
            var resultDiv = this.popupDialog.getContentElement();
            SUGAR.ui.sendForm(MeetingsEditView.form, {record_perform: 'delete'}, {no_validate: true, resultDiv: resultDiv, receiveCallback: success}, false, false);
		},

        switchTab:function(model) {
            function ready() {
                MeetingsEditView.initEditView();
            }
            MeetingsEditView.form.edit_model.value = model;
            var resultDiv = this.popupDialog.getContentElement();
            SUGAR.ui.sendForm(MeetingsEditView.form, null, {no_validate: true, resultDiv: resultDiv, receiveCallback: ready}, false, false);
        },

        toggleDayLong:function() {
            var duration_inp = SUGAR.ui.getFormInput(MeetingsEditView.form, 'duration');
            if (duration_inp) {
                var chkDayLong = duration_inp.getAllDay();
                var isDisable = (chkDayLong == 1);

                var date_start_inp = SUGAR.ui.getFormInput(MeetingsEditView.form, 'date_start');
                date_start_inp.time_field.disabled = isDisable;
                duration_inp.time_field.disabled = isDisable;

                var new_time_start = orig_start_time;
                var new_duration = orig_duration;

                if(isDisable) {
                    orig_start_time = date_start_inp.getTimeValue();
                    orig_duration = duration_inp.getValue();
                    new_time_start = day_start_time;
                    new_duration = day_duration;
                }

                date_start_inp.setTimeValue(new_time_start, true);
                duration_inp.setValue(new_duration);
            }
        }
	};
}();

var HoursEditView = function() {
	return {
		
		initEditView:function(form_id) {
            HoursEditView.form = SUGAR.ui.getForm(form_id);
            var tab = SUGAR.ui.getFormInput(HoursEditView.form, 'form-tabs');
            if(tab) {
                tab.enableDisableTab('Meeting', true);
                tab.enableDisableTab('Call', true);
                tab.enableDisableTab('Booking', true);
            }
            SUGAR.ui.onInitForm(this.form, function() { HoursEditView.initExtra(); });
		},
        setRelatedFilters:function(change_type) {
            //var rel = SUGAR.ui.getFormInput(HoursEditView.form, 'related');
            var rel = SUGAR.ui.getFormInput(HoursEditView.form, 'related_id');
            if (rel instanceof SUGAR.ui.RefInput) {
                if (! change_type) {
                    var func = rel.module_field.onchange;
                    rel.module_field.onchange = function() {func(); HoursEditView.setRelatedFilters(true)};
                }
                var rel_type = rel.getModule();
                var filter = [];
                var assigned = $('assigned_user_id');

                if (rel_type == 'ProjectTask') {
                    filter[0] = {param: 'status', value: 'In Progress'};
                    filter[1] = {param: 'project_phase', value: 'Active - In Progress'};
                    
                    if (assigned && assigned.value != '') {
                        filter[2] = {param: 'user_id', value: assigned.value};
                        filter[3] = {param: 'user_status', value: 'Active'};
                    }
                } else if (rel_type == 'Cases') {
                    var account = SUGAR.ui.getFormInput(HoursEditView.form, 'account');
                    if (account) {
                        filter[0] = {param: 'account_id', value: account.getKey()};
                    }
                }
                rel.add_filters = filter;
            }
            else if(rel) {
				SUGAR.ui.attachFormInputEvent(this.form, 'related_id', 'onchange',
					function(k, v) {
						SUGAR.ui.getFormInput(HoursEditView.form, 'account').update(v.account_id, v.account, true);
						HoursEditView.setTaskRates();
					}
				);
			}
        },
        initExtra:function() {
        	var input, price_fields = ['billing_rate', 'paid_rate', 'billing_total', 'paid_total'];
        	for(var i = 0; i < price_fields.length; i++) {
        		input = SUGAR.ui.getFormInput(this.form, price_fields[i]);
        		if(input) input.onchange = function() { HoursEditView.updateAmount(this); }
        	}
        	SUGAR.ui.attachFormInputEvent(this.form, 'quantity', 'onchange',
				function() { HoursEditView.updateQuantity(); });
        	SUGAR.ui.attachFormInputEvent(this.form, 'booking_category_id', 'onchange',
				function() { HoursEditView.setRates(this.getValue()); });
        	SUGAR.ui.attachFormInputEvent(this.form, 'account', 'onchange',
				function() { HoursEditView.setRelatedFilters(); });
			SUGAR.ui.attachFormInputEvent(this.form, 'assigned_user', 'onchange',
				function() {HoursEditView.setTaskRates();});
			this.setRelatedFilters();
        },
		show:function(targetElementName, activityId, userId, dateStart, timeHourStart, timeMinuteStart, timesheetId) {
			if(! isset(userId)) {
				userId = "";
			}
			if(isset(dateStart) && dateStart !== "") {
				dateStart = "&date_start="+dateStart;
			} else {
				dateStart = "";
			}
			if(isset(timeHourStart) && timeHourStart !== "") {
				timeHourStart = "&time_hour_start="+timeHourStart;
			} else {
				timeHourStart = "";	
			}
			if(isset(timeMinuteStart) && timeMinuteStart !== "") {
				timeMinuteStart = "&time_minute_start="+timeMinuteStart;
			} else {
				timeMinuteStart = "";
			}
			if(isset(timesheetId) && timesheetId !== "") {
				timesheetId = "&timesheet_id="+timesheetId;
			} else {
				timesheetId = "";
			}
			
			function ready() { HoursEditView.initEditView('asyncEditView'); }
			this.setupPopupDialog();
			var postData = "module=Booking&assigned_user_id="+userId+"&action=bookingEditView&to_pdf=1&record="+activityId + dateStart + timeHourStart + timeMinuteStart+timesheetId;
			this.popupDialog.fetchContent("async.php", {method: 'POST', postData: postData}, ready, true);
		},

        showNew:function(targetElement, userId, timesheetId, dateStart, timeHourStart, timeMinuteStart, durHour, durMin, related) {
            if(! isset(userId)) {
                userId = "";
            }
            if(isset(dateStart) && dateStart !== "") {
                dateStart = "&date_start="+dateStart;
            } else {
                dateStart = "";
            }
            if(isset(timeHourStart) && timeHourStart !== '') {
                timeHourStart = "&time_hour_start="+timeHourStart;
            } else {
                timeHourStart = "";
            }
            if(isset(timeMinuteStart) && timeMinuteStart !== '') {
                timeMinuteStart = "&time_minute_start="+timeMinuteStart;
            } else {
                timeMinuteStart = "";
            }
            if(isset(durHour) && durHour !== '') {
                durHour = "&duration_hours="+durHour;
            } else {
                durHour = "";
            }
            if(isset(durMin) && durMin !== '') {
                durMin = "&duration_minutes="+durMin;
            } else {
                durMin = "";
            }
            if(isset(timesheetId) && timesheetId !== "") {
                timesheetId = "&timesheet_id="+timesheetId;
            } else {
                timesheetId = "";
            }
            var relatedId = '';
            var relatedType = ''
            if(isset(related)) {
				if(related.id)
					relatedId = "&related_id="+related.id;
				if(related.type)
					relatedType = "&related_type="+related.type;
			}

            function ready() { HoursEditView.initEditView('asyncEditView'); }
            this.setupPopupDialog();
            var postData = "module=Booking&assigned_user_id="+userId+"&action=bookingEditView&to_pdf=1&date_is_gmt=1"
                + dateStart + timeHourStart + timeMinuteStart+timesheetId+durHour+durMin+relatedId+relatedType;
            this.popupDialog.fetchContent('async.php', {method: 'POST', postData: postData}, ready, true);
        },

		setupPopupDialog:function() {
			if(! this.popupDialog) {
				this.popupDialog = new SUGAR.ui.Dialog('bookingEditView', { width: '670px', resizable: false, draggable: true });
				this.popupDialog.onshow = function() {
					var name = document.asyncEditView.name;
					if(name && name.focus && ! name.value) name.focus();
				}
			}
		},
		
		setTitle:function(title) {
			if(this.popupDialog)
				this.popupDialog.setTitle(title || '');
		},

        swapAdditional:function() {
            var content = $('add_content');
            if (content.style.display == 'none') {
                content.style.display = ''
            } else {
                content.style.display = 'none';
            }
            SUGAR.popups.reposition();
        },

		hide:function() {
			if(this.popupDialog) {
				this.popupDialog.close();
				this.popupDialog = null;
			}
		},
		
		save:function() {
            var success = function(data) {
                var result = null;
                HoursEditView.hide();
                try {
                    var viewType = window.document.form_calendar.view_type.value;
                    CalendarCtrl.reload(viewType);
                } catch (err) {
                    var msg = SUGAR.language.get('app_strings', 'NTC_HOURS_BOOKED');;
                    alert(msg);
                    //document.location.reload(true);
                }
            };
            var resultDiv = this.popupDialog.getContentElement();
            return SUGAR.ui.sendForm(HoursEditView.form, {record_perform: 'save'}, {resultDiv: resultDiv, receiveCallback: success}, false, false);
		},

        duplicate:function() {
            function ready() {
                HoursEditView.initEditView('asyncEditView');
            }
            var resultDiv = this.popupDialog.getContentElement();
            SUGAR.ui.sendForm(HoursEditView.form, {record_perform: 'duplicate'}, {no_validate: true, resultDiv: resultDiv, receiveCallback: ready}, false, false);
        },

        deleteActivity:function() {
            var success = function(data) {
                HoursEditView.hide();
                try {
                    var viewType = window.document.form_calendar.view_type.value;
                    CalendarCtrl.reload(viewType);
                } catch (err) {
                    document.location.reload(true);
                }
            };

            var msgConfirm = SUGAR.language.get('app_strings', 'NTC_DELETE_CONFIRMATION');

            if(confirm(msgConfirm) == false) {
                return false;
            }

            var resultDiv = this.popupDialog.getContentElement();
            SUGAR.ui.sendForm(HoursEditView.form, {record_perform: 'delete'}, {no_validate: true, resultDiv: resultDiv, receiveCallback: success}, false, false);
        },

        updatedRelatedType:function(fld) {
            fld.form.related_id.value = '';
            fld.form.related_name.value = '';
            HoursEditView.updateAccountEdit();
        },
        
        updateAccountEdit:function() {
        	var form = document.forms.asyncEditView;
        	if(form.related_id.value) {
        		form.account_select.style.display = 'none';
        		form.account_name.readOnly = true;
        	} else {
        		form.account_select.style.display = '';
        		form.account_name.readOnly = false;
        	}
        },

        updateQuantity:function() {
            var qty_inp = SUGAR.ui.getFormInput(HoursEditView.form, 'quantity');
            var qty = qty_inp.getValue() / 60;
            if(! qty) return;

            var rate1_inp = SUGAR.ui.getFormInput(HoursEditView.form, 'billing_rate');
            var rate1 = stdUnformatNumber(rate1_inp.getValue());
            var rate2_inp = SUGAR.ui.getFormInput(HoursEditView.form, 'paid_rate');
            var rate2 = stdUnformatNumber(rate2_inp.getValue());

            if(rate1) {
                var total1_inp = SUGAR.ui.getFormInput(HoursEditView.form, 'billing_total');
                total1_inp.field.value = rate1 * qty;
            }

            if(rate2) {
                var total2_inp = SUGAR.ui.getFormInput(HoursEditView.form, 'paid_total');
                total2_inp.field.value = rate2 * qty;
            }
        },

		update_guard: false,

        updateAmount:function(price_elem) {
            var name = price_elem.name;

            if (name == 'billing_rate' || name == 'billing_total') {
                currency_inp_id = 'billing_currency_id';
            } else {
                currency_inp_id = 'paid_currency_id';
            }

            var currency_inp = SUGAR.ui.getFormInput(HoursEditView.form, currency_inp_id);
            var currency_id = currency_inp.getValue();
            var amount_inp = SUGAR.ui.getFormInput(HoursEditView.form, name);
            var decimals = currency_inp.getDecimals();

            var id = amount_inp.field.name.split('_');
            var other;
            var newval;

            var val = stdUnformatNumber(amount_inp.getValue());
            var qty_inp = SUGAR.ui.getFormInput(HoursEditView.form, 'quantity');
            var qty = qty_inp.getValue() / 60;
            if(! qty || val === '') return;

            if(id[1] == 'rate') {
                other = 'total';
                newval = val * qty;
            } else {
                other = 'rate';
                newval = val / qty;
            }
            new_val = stdFormatNumber(restrictDecimals(newval, decimals));

            other = SUGAR.ui.getFormInput(HoursEditView.form, id[0] + '_' + other);
			if (!this.update_guard) {
				this.update_guard = true;
		        other.setValue(newval);
				this.update_guard = false;
			}

        },

        setTaskRates:function() {
			var ready = function()
			{
                var row = this.getResult();
				if (row.result != 'ok')
					return;
                var rate = SUGAR.ui.getFormInput(HoursEditView.form, 'paid_rate');
				rate.setValue(row['hourly_cost']);
				HoursEditView.updateQuantity();
                SUGAR.ui.getFormInput(HoursEditView.form, 'paid_currency_id').setValue(row['currency_id']);
			}
			if (this.form.related_type.value == 'ProjectTask') {
				var task_id = SUGAR.ui.getFormInput(this.form, 'related_id').getValue();
				var user_id = SUGAR.ui.getFormInput(this.form, 'assigned_user').getKey();
			    var query_params = {task_id: task_id, user_id : user_id};
				var req = new SUGAR.conn.JSONRequest('get_user_task_rates', {status_msg: app_string('LBL_SAVING')}, query_params);
	            req.fetch(ready);
			}
		},

        setRates:function(category_id) {
            function ready() {
                var row = this.getResult();
                if(row.result == 'ok') {
                    var cat_fields = ['billing_rate', 'billing_currency_id', 'paid_rate', 'paid_currency_id', 'tax_code_id'];
                    var name = '';
                    var input = null;

                    for (var i = 0; i < cat_fields.length; i++) {
                        name = cat_fields[i];
                        if (isset(row[name])) {
                            input = SUGAR.ui.getFormInput(HoursEditView.form, name);
                            if (input) {
                                input.setValue(row[name]);
                                if (typeof(input.handleChanged) == 'function')
                                    input.handleChanged();
                            }
                        }
                    }
                }
                return false;
            }

            var query_params = {category_id: category_id};
            var req = new SUGAR.conn.JSONRequest('get_rates_by_cat', {status_msg: app_string('LBL_SAVING')}, query_params);
            req.fetch(ready);
        }
	};
}();

window.sqsAsyncInit = true;

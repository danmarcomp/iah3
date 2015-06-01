function vacationInit(form) {
	SUGAR.ui.attachFormInputEvent(form, 'leave_type', 'onchange', setStatus);
	SUGAR.ui.attachFormInputEvent(form, 'days', 'onchange', function() { calcEndDate(this.form); });
	SUGAR.ui.attachFormInputEvent(form, 'date_start', 'onchange', afterStartDateUpdate);
	SUGAR.ui.attachFormInputEvent(form, 'date_end', 'onchange', function() { checkEndDate(this.form); });
}

function setStatus() {
	var type = this.getValue();
    var status_select = SUGAR.ui.getFormInput(this.form, 'status');

    if (type == 'vacation') {
        status_select.setValue('planned');
    } else if (type == 'sick') {
        status_select.setValue('days_taken');
    }
}

function checkEndDate(form) {
    var start_date = SUGAR.ui.getFormInput(form, 'date_start').getValue(true);
    var end_date = SUGAR.ui.getFormInput(form, 'date_end').getValue(true);

    if (start_date) {
        if (end_date >= start_date) {
            calcDays(start_date, end_date, form);
        } else {
            SUGAR.ui.getFormInput(form, 'date_end').clear();
        }
    }
}

function afterStartDateUpdate() {
    var end_date = SUGAR.ui.getFormInput(this.form, 'date_end').getValue(true);
    var days = SUGAR.ui.getFormInput(this.form, 'days').getValue(true);

    if (end_date) {
        checkEndDate(this.form);
    } else if (days) {
        calcEndDate(this.form);
    }
}

function calcEndDate(form) {
	var days = SUGAR.ui.getFormInput(form, 'days').getValue(true);
	var start_date = SUGAR.ui.getFormInput(form, 'date_start').getValue(true);
	
    if (days > 0 && start_date) {
        var end = new Date(start_date);
        end.setDate(end.getDate() + Math.floor(days - 0.01));

        var end_date_input = SUGAR.ui.getFormInput(form, 'date_end');
        if (end_date_input)
            end_date_input.setValue(end, true);
    }
}

function calcDays(start_date, end_date, form) {
    var result = '';

    if (end_date >= start_date)
        result = 1 + Math.round((end_date.getTime() - start_date.getTime()) / 1000 / 60 / 60 / 24);

    var days_input = SUGAR.ui.getFormInput(form, 'days');
    if (days_input)
        days_input.setValue(result, true);
}

function initRecSvcs(form) {
	$('DetailFormbilling_day-input').readOnly = true;
	$('DetailForminvoice_value-input').readOnly = true;
	$('DetailFormtotal_sales-input').readOnly = true;
	$('DetailFormbalance_due-input').readOnly = true;
	SUGAR.ui.attachFormInputEvent(form, 'start_date', 'onchange', setDates);
	SUGAR.ui.attachFormInputEvent(form, 'frequency', 'onchange', setDates);
	SUGAR.ui.attachFormInputEvent(form, 'quote', 'onchange', setQuote);
	var quote = SUGAR.ui.getFormInput(form, 'quote');
	if(quote)
		quote.addExtraReturnFields({
            'amount_usdollar': 'amount_usdollar',
            'purchase_order_num': 'purchase_order_num',
            'account': 'billing_account',
            'account_id': 'billing_account_id',
		});
	var cat = SUGAR.ui.getFormInput(form, 'booking_category');
	if(cat) cat.addFilter({param: 'booking_class', value: 'services-monthly'});
	//setDates();
}


function setDates() {
    var startDateInp = SUGAR.ui.getFormInput(this.form, 'start_date'),
    	freq = SUGAR.ui.getFormInput(this.form, 'frequency'),
    	startDate = startDateInp ? startDateInp.getValue(true) : null,
		billDay = SUGAR.ui.getFormInput(this.form, 'billing_day');
	if (startDate && freq) {
		if(billDay)
			billDay.setValue(startDate.getDate());

		var endDate = new Date(startDate),
			delta,
			m = startDate.getMonth(),
			y = startDate.getFullYear(),
			d = startDate.getDate(),
			frequency = freq.getValue();

		if (frequency == 'annually') {
			delta = 12;
		} else if (frequency == 'quarterly') {
			delta = 3;
		} else {
			delta = 1;
		}

		m += delta;
		if (m > 11) {
			m -=12;
			y++;
		}
		if (d == 1) {
			m--;
			if (m < 0) {
				m = 11;
				y--;
			}
			var dummy = new Date(y, m, 1);
			endDate = new Date(y, m, dummy.getMonthDays());
		} else {
			d--;
			var dummy = new Date(y, m, 1);
			if (d > dummy.getMonthDays()) d = dummy.getMonthDays();
			endDate = new Date(y, m, d);
		}

		$("end_date_prev").style.display = '';
		$("end_date_next").style.display = '';
		this.form.end_date.value = endDate.print(getDateFormat().print_format);
		$("end_date_view").innerHTML = this.form.end_date.value;
	} else {
		if(billDay && billDay.getValue())
			billDay.setValue('');
		$("end_date_prev").style.display = 'none';
		$("end_date_next").style.display = 'none';
		this.form.end_date.value = '';
		$("end_date_view").innerHTML = '';
		return false;
	}
}

function changeDate(operation, form) {
	var svcsForm = SUGAR.ui.getForm(form);
	var add = (operation == 'add'),
		endDate = parseDateString(svcsForm.end_date.value),
		startDateInp = SUGAR.ui.getFormInput(svcsForm, 'start_date'),
		freq = SUGAR.ui.getFormInput(svcsForm, 'frequency'),
		startDate = startDateInp ? startDateInp.getValue(true) : null;
	if (endDate && startDate && freq) {
		var y = endDate.d.getFullYear(),
			m = endDate.d.getMonth(),
			d = startDate.getDate(),
			frequency = freq.getValue();

		if (frequency == 'annually') {
			y += (add ? 1 : -1);
		} else {
			var delta;
			if (frequency == 'quarterly') delta = 3;
			else delta = 1;

			m += (add ? delta : -delta);
			if (m > 11) {
				m -= 12;
				y++;
			}
			if (m < 0) {
				m += 12;
				y--;
			}
		}

		var newEndDate = new Date(y, m, 1);
		if (d == 1) {
			newEndDate = new Date(y, m, newEndDate.getMonthDays());
		} else {
			d--;
			var nDays = newEndDate.getMonthDays();
			if (d > nDays) d = nDays;
			newEndDate = new Date(y, m, d);
		}

		if (newEndDate < startDate) return false;

		svcsForm.end_date.value = newEndDate.print(getDateFormat().print_format);
		$("end_date_view").innerHTML = svcsForm.end_date.value;
	} else {
		return false;
	}
}

function setQuote(key, value) {
    var decimals = 2;
	if(key) {
        if (isdef(value.id)) {
            var amount = parseFloat(value.amount_usdollar),
        		invInp = SUGAR.ui.getFormInput(this.form, 'invoice_value'),
        		poNum = SUGAR.ui.getFormInput(this.form, 'purchase_order_num'),
        		account = SUGAR.ui.getFormInput(this.form, 'account');
        	if(invInp)
            	invInp.setValue(stdFormatNumber(amount, decimals, decimals));
            if(isdef(value.purchase_order_num) && poNum)
                poNum.setValue(value.purchase_order_num);
            if(account && value.account_id)
            	account.update(value.account_id, value.account);
        }
	}
}

PaymentEditor = new (function() {

var editor = this;

var count = 1;
var invoices = {};
var credit_notes = {};

var selectButtonTitle = app_string('LBL_SELECT_BUTTON_TITLE');
var selectButtonKey = app_string('LBL_SELECT_BUTTON_KEY');
var selectButtonValue = app_string('LBL_SELECT_BUTTON_LABEL');
var deleteButtonName = 'remove_line';
var deleteButtonConfirm = mod_string('NTC_CONFIRM_LINE_REMOVE');

var invoice_already_referenced = mod_string('MSG_ALREADY_REFERENCED');
var credit_already_referenced = mod_string('MSG_CREDIT_ALREADY_REFERENCED');
var payment_cant_be_processed = mod_string('MSG_CANT_PROCESS');

var no_invoices = mod_string('MSG_NO_INVOICES');
var no_credit_notes = mod_string('MSG_NO_CREDIT_NOTES');

this.id = 'PaymentEditor';
this.editable = true;
this.payment_module = 'Payments';
/*
	this.currency_input = null;
	this.account_input = null;
	this.amount_input = null;
	this.type_input = null;
	this.account_balance_usd = 0;
	this.original_amount_usd = 0;
	this.original_account_id = 0;
*/

this.init = function(params) {
	if(params) YLang.augmentObject(this, params, true);
}

function hasAttribute(element, attr) {
    if (element.hasAttribute) return element.hasAttribute(attr);
    return (typeof(element.getAttribute(attr)) == typeof(''));
}

this.add_hidden_field = function(name, value, credit_note, parent) {
	var elt = document.createElement('input');
    var cnt = count;
    var input = new SUGAR.ui.HiddenInput(name + '_' + cnt, {name: name + '[' + cnt + ']', init_value: value});
	SUGAR.ui.registerInput(this.form, input);
	(parent || this.form).appendChild(input.render());
	return input;
}

this.get_field = function(name, count) {
	if(isset(count)) name += '[' + count + ']';
	return SUGAR.ui.getFormInput(this.form, name);
}
this.remove_field = function(name, count) {
	if(isset(count)) name += '[' + count + ']';
	return SUGAR.ui.unregisterInput(this.form, name);
}

this.addRow = function(invoice_info, focus) {
	var is_credit = !! invoice_info.is_credit;
	var credit_note = !! invoice_info.credit_note;
	var prefix = credit_note ? 'credit' : 'invoice';
	var table = credit_note ? $(this.credit_table_id) : $(this.table_id);

	var row = table.insertRow(table.rows.length);
	var rowName = 'item_row_' + count;
	row.id = rowName;
	row.tableId = table.id;
	row.count = count;
	
	var invoice_fields = [
		'id', 'name', 'currency_id'
	];
	var num_fields = [
		'allocated', 'allocated_usd',
		'amount_usd', 'amount_due', 'amount_due_usd'
	];
	for(var i = 0; i < invoice_fields.length; i++)
		if(! isset(invoice_info[invoice_fields[i]]))
			invoice_info[invoice_fields[i]] = '';
	for(var i = 0; i < num_fields.length; i++)
		if(! isset(invoice_info[num_fields[i]]))
			invoice_info[num_fields[i]] = 0.0;
	if(! isset(invoice_info['exchange_rate']))
		invoice_info['exchange_rate'] = 1;
	invoices[invoice_info.id] = count;
	
	var cell1 = row.insertCell(row.cells.length);
	cell1.className = 'dataLabel';

	var cnt = count;
	var rel_module = 'Invoice';
	var account_field = 'billing_account';
	if(is_credit) {
		rel_module = 'CreditNotes';
        account_field = 'billing_account';
	} else if(this.payment_module == 'PaymentsOut') {
		rel_module = 'Bills';
		account_field = 'supplier';
	}
	var filter = [
		{param: account_field, value: this.account_input ? this.account_input.getValue() : ''}
	];

    var field_map = {};
	field_map['invoice_id'] = 'id';
	field_map['invoice_name'] = 'name';
	field_map['invoice_no'] = 'full_number';
	field_map['invoice_currency_id'] = 'currency_id';
	field_map['invoice_exchange_rate'] = 'exchange_rate';
	field_map['invoice_amount_due_usd'] = 'amount_due_usdollar';

	if(! is_credit) {
		field_map['account'] = account_field;
		field_map['account_id'] = account_field + '_id';
		field_map['account_balance'] = account_field + '.balance';
		field_map['account_balance_payable'] = account_field + '.balance_payable';
		field_map['account_currency_id'] = account_field + '.currency_id';
	}

    var attrs = {
		field_id: prefix + '_name_' + cnt,
		name: prefix + '_name[' + cnt + ']',
		key_name: prefix + '_id[' + cnt + ']',
		key_id: prefix + '_id_' + cnt,
		module: rel_module,
		width: '25em',
		onchange: function(key, value, passthru) { editor.returnLineItem(key, value, passthru, prefix); },
		extra_fields: field_map,
		add_filters: filter,
		popup_passthru: {
			item: cnt,
			is_credit: is_credit
		},
		disabled: ! this.editable
	};

	var invoice_ref = this.createRefInput(prefix + '_ref_' + cnt, invoice_info.id, invoice_info.name, attrs, cnt);
	cell1.appendChild(invoice_ref.render());

	var allocated = rounded(invoice_info.allocated, decimals);
	var balance = rounded(invoice_info.amount_due, decimals);
	var due = rounded(parseFloat(allocated) + parseFloat(balance), decimals);
	var alloc_usd = this.add_hidden_field(prefix+'_allocated_usd', invoice_info.allocated_usd, is_credit);
	var exc_rate = this.add_hidden_field(prefix+'_exchange_rate', stdFormatNumber(invoice_info.exchange_rate), is_credit);
	var currency = this.add_hidden_field(prefix+'_currency_id', invoice_info.currency_id, is_credit);
	var amount_due = this.add_hidden_field(prefix+'_amount_due', due, is_credit);
	var exc_button = new SUGAR.ui.ButtonInput(null, {compact: true, icon: 'input-icon icon-exchangerate'});
	exc_button.render();
	
	var line_currency = new SUGAR.ui.CurrencySelect(prefix+'_currency_input_'+cnt, {
		name: prefix+'_currency_input['+cnt+']',
		rate_button: exc_button.elt, rate_field: exc_rate, field: currency.elt, elt: createElement2('div')});
	SUGAR.ui.registerInput(this.form, line_currency);
	line_currency.updateDisplay();
	var decimals = line_currency.getDecimals();
	
	var cell2 = row.insertCell(row.cells.length);
	cell2.appendChild(document.createTextNode(mod_string('LBL_APPLY_PAYMENT')+' '));
	cell2.noWrap = 'nowrap';
	cell2.className = 'dataLabel';
	var attrs = {
		name: prefix+'_allocated[' + cnt + ']',
		size: 10,
		init_value: stdFormatNumber(allocated, decimals, decimals),
		format: 'currency',
		decimals: decimals,
		onchange: function(ev) {
			var rate = line_currency.getRate(true);
			alloc_usd.setValue(this.getValue(true) / rate);
			editor.calculate(table.id);
		},
		disabled: ! invoice_info.id || ! this.editable
	};
	var textE1 = new SUGAR.ui.TextInput(prefix+'_allocated_' + cnt, attrs);
	SUGAR.ui.registerInput(this.form, textE1);
	var elt = textE1.render();
	elt.count = cnt;
	cell2.appendChild(elt);
	
	var cell3 = row.insertCell(row.cells.length);
	cell3.noWrap = 'nowrap';
	cell3.className = 'dataLabel';
	cell3.appendChild(document.createTextNode(mod_string('LBL_BALANCE')+' '));
	var attrs = {
		name: prefix+'_balance['+cnt+']',
		size: 10,
		init_value: stdFormatNumber(balance, decimals, decimals),
		format: 'currency',
		decimals: decimals,
		disabled: true
	};
	var textE1 = new SUGAR.ui.TextInput(prefix+'_balance_'+cnt, attrs);
	SUGAR.ui.registerInput(this.form, textE1);
	cell3.appendChild(textE1.render());
	
	var cell4 = row.insertCell(row.cells.length);
	cell4.noWrap = 'nowrap';
	cell4.className = 'dataLabel';
	cell4.style.fontWeight = 'bold';
	cell4.id = prefix+'_currency_'+cnt;
	var cname = line_currency.getShortName(null, true);
	cell4.appendChild(document.createTextNode(cname));
	
	var cell5 = row.insertCell(row.cells.length);
	cell5.noWrap = 'nowrap';
	cell5.className = 'dataLabel';
	cell5.id = prefix+'_rate_editor_'+cnt;
	if(this.editable)
		cell5.appendChild(exc_button.elt);

	var cell6 = row.insertCell(row.cells.length);
	if(this.editable) {
		var onclick = function(){
			if (confirm(deleteButtonConfirm)) { 
				editor.deleteRow(this.count, this.tableId, this.rowId); 
				editor.calculate(this.tableId);
			}
		}, elt = createElement2('div',
			{className: 'input-icon icon-delete active-icon', onclick: onclick,
			 id: 'delete_'+prefix+'_row' + cnt, rowId: invoice_info.id,
			 tableId: table.id, count: cnt, name: deleteButtonName});
		cell6.appendChild(elt);
	}
	
	count ++;
	
	if(focus) invoice_ref.focus();
}

this.changeAccountFilter = function(account, form) {
    this.setInvoiceFilter(account, form, false);
    this.setInvoiceFilter(account, form, true);
}

this.setInvoiceFilter = function(account, form, is_credit) {
    var filter_name = 'billing_account';
    var field_name = 'invoice_name';
    var count_field = count;

    var inp = null;

    for (var i = 1; i < count_field; i++) {
        inp = SUGAR.ui.getFormInput(form, field_name + '[' +i+ ']');
        if (inp) {
            if (inp.getModule() == 'Bills')
                filter_name = 'supplier';

            inp.add_filters = [];
            inp.addFilter({param: filter_name, value: account});
        }
    }
}

this.createRefInput = function(id, key, value, attrs) {
	if(! attrs) attrs = {};
    attrs.init_key = key;
    attrs.init_value = value;
    var input = new SUGAR.ui.RefInput(id, attrs);
    SUGAR.ui.registerInput(this.form, input);
    return input;
}

this.deleteRow = function(id, table_id, rowId) {
	var table = $(table_id);
	var rows = table.rows;
	var prefix = table_id == this.table_id ? 'invoice' : 'credit';
	var looking_for = 'delete_'+prefix+'_row' + id;
	for(i = 0 ; i < rows.length; i++){
		cells = rows[i].cells;
		for(var j = 5 ; j < rows[i].cells.length; j++){
			cell = rows[i].cells[j];
			children = cell.childNodes;
			for(var k = 0 ; k < children.length; k++){
				var child = children[k];
				if(child.nodeType == 1 && hasAttribute(child, 'id')){
					if(child.getAttribute('id') == looking_for){
                        delete invoices[rowId];
                        table.deleteRow(i);
						this.remove_field(prefix + '_name', id);
						this.remove_field(prefix + '_currency_input', id);
						this.remove_field(prefix + '_allocated', id);
						this.remove_field(prefix + '_allocated_usd', id);
						this.remove_field(prefix + '_balance', id);
						this.remove_field(prefix + '_amount_due', id);
						return;
					}
				}
			}
		}
	}
}

this.returnLineItem = function(key, value, passthru, prefix) {
	if(! value) value = {};
	if(! passthru || ! passthru.item) return false;
	var item = passthru.item;
	
	// fixme - check for duplicate invoices
	// alert(invoice_already_referenced || credit_already_referenced)
	
    var currency = this.get_field(prefix + '_currency_input', item);
    currency.setValueAndRate(value.invoice_currency_id, value.invoice_exchange_rate, true);
	$(prefix + '_currency_' + item).innerHTML = currency.getShortName(null, true);
	var amount_due = value.invoice_amount_due_usd;
	var amount_inp = this.get_field(prefix + '_allocated', item);
	this.get_field(prefix + '_allocated_usd', item).setValue(amount_due);
	this.get_field(prefix + '_amount_due', item).setValue(amount_due);
	amount_inp.decimals = currency.getDecimals();
	amount_inp.setValue(amount_due * currency.getRate(true));
	amount_inp.setDisabled(false);
	
    if (item == 1 && ! passthru.is_credit) {
    	this.account_input.update(value.account_id, value.account, true);
        this.setAccountExt(value.account_currency, value.account_balance, value.account_balance_payable);
    } else {
        this.calculate(this.table_id);
    }
}


this.get_total_alloc_usd = function(table_id) {
	var total = 0;
	var prefix = table_id == this.table_id ? 'invoice' : 'credit',
		c = count;
	for (var i = 1; i < c; i ++) {
		var cur_amount = this.get_field(prefix + '_allocated_usd', i);
		if(cur_amount)
			total += parseFloat(cur_amount.getValue() || 0);
	}
	return total;
}

this.get_total_alloc_base = function(table_id, upd_balance) {
	var total = 0;
	var currency_id = this.currency_input.getValue();
	var exchange_rate = this.currency_input.getRate(true);
	if (exchange_rate == 0 || exchange_rate == '') exchange_rate = 1;
	var prefix = table_id == this.table_id ? 'invoice' : 'credit',
		c = count;
	for (var i = 1; i < c; i ++) {
		var line_currency = this.get_field(prefix + '_currency_input', i)
		var cur_amount = this.get_field(prefix + '_allocated', i);
		var cur_amount_usd = this.get_field(prefix + '_allocated_usd', i);
		if (cur_amount && cur_amount_usd) {
			var amount = cur_amount.getValue(true);
			if (line_currency.getValue() != currency_id) {
				total += parseFloat(cur_amount_usd.getValue() || 0) * exchange_rate ;
			} else {
				total += amount;
			}
			if(upd_balance) {
				var decimals = line_currency.getDecimals();
				var balance = this.get_field(prefix + '_balance', i);
				var due = this.get_field(prefix + '_amount_due', i);
				if(balance && due) {
					var newBalance = rounded(due.value, decimals) - rounded(amount, decimals) ;
					balance.value = stdFormatNumber(newBalance, decimals, decimals);
				}
			}
		}
	}
	return total;
}

this.payment_amount_changed = function() {
	var paymentCurrency = this.currency_input.getValue();
	var decimals = this.currency_input.getDecimals();
	var amount_field, amount_usd_field, line_currency;
	for (var i=1; i < count; i++) {
		var cur_amount = this.get_field('invoice_allocated', i);
		if (cur_amount) {
			if(amount_field) {
				amount_field = null; // do not update allocation
				break;
			}
			amount_field = cur_amount;
			amount_usd_field = this.get_field('invoice_allocated_usd', i);
			line_currency = this.get_field('invoice_currency_input', i);
		}
	}
	if(amount_field) {
		var decimals = line_currency.getDecimals();
		var pay_amount = SUGAR.ui.getFormInput(this.form, 'amount');
		var amount_total = pay_amount.getValue(true);
		amount_usd_field.setValue(amount_total / line_currency.getRate(true));
		amount_field.setValue(stdFormatNumber(rounded(amount_total, decimals), decimals, decimals));
	}
	this.calculate(this.table_id);
}

this.currency_changed = function() {
    this.payment_amount_changed();
}

function rounded(val,places) {
	if(! isset(places)) places = 2;
	return parseFloat(val).toFixed(places);
}

this.check_amount_allocations = function() {
	var currency_id = this.currency_input.getValue();
	var exchange_rate = this.currency_input.getRate(true);
	var decimals = this.currency_input.getDecimals();
	var amount = this.amount_input.getValue(true);
	var invoices_amount = rounded(this.get_total_alloc_base(this.table_id), decimals);
	if (invoices_amount != rounded(amount, decimals)) {
		alert(payment_cant_be_processed);
		return false;
	}
}

this.calculate = function(table_id) {
	var currency_id = this.currency_input.getValue();
	var exchange_rate = this.currency_input.getRate(true);
	var decimals = this.currency_input.getDecimals();
	var usd_decimals = this.currency_input.getDecimals('-99');
	var payment_amount = this.amount_input.getValue(true);
	var sign = this.direction == 'credit' ? -1 : 1;
	
	var totalAmt = rounded(this.get_total_alloc_base(table_id, table_id == this.table_id), decimals);
	
	var totalAmtUsd = this.get_total_alloc_usd(table_id);
	var totalAllocField = table_id == this.table_id ? $('totalAllocated') : $('totalCredits');
	totalAllocField.innerHTML = stdFormatNumber(totalAmt, decimals, decimals);

	var enterAmount = payment_amount;
	if(enterAmount != parseFloat(totalAmt))
		totalAllocField.className = 'error';
	else
		totalAllocField.className = '';
	
	$('totalCurrency').innerHTML = this.currency_input.getShortName(currency_id, true);
	
	if (currency_id != '-99') {
		$('totalAllocatedUsd').innerHTML = stdFormatNumber(rounded(totalAmtUsd, usd_decimals), usd_decimals, usd_decimals);
		$('totalCurrencyUsd').innerHTML = this.currency_input.getShortName('-99', true);
		$('dollar_totals').style.display='';
	} else {
		$('dollar_totals').style.display='none';
	}
	
	var oldBalanceUsd = parseFloat(this.account_balance_usd);
	var originalAmountUsd = parseFloat(this.original_amount_usd);
	var newBalanceUsd;
	if (! this.account_input || this.account_input.getKey() == this.original_account_id) {
		newBalanceUsd = oldBalanceUsd + originalAmountUsd * sign - parseFloat(totalAmtUsd) * sign;
	} else {
		newBalanceUsd = parseFloat(oldBalanceUsd) - parseFloat(totalAmtUsd) * sign;
	}

	var currencyName = this.currency_input.getShortName(currency_id, true);
	var newBalance = newBalanceUsd * this.currency_input.getRate(true);
	$('balance').innerHTML = stdFormatNumber(rounded(newBalance, decimals), decimals, decimals);
	$('balance_currency').innerHTML = currencyName;
}

this.setAccountExt = function(currency_id, balance, balance_payable) {
    if (this.payment_module == 'PaymentsOut') {
        this.account_balance_usd = balance_payable;
    } else {
        this.account_balance_usd = balance;
    }

    var amount = this.amount_input.getValue(true);
    if (amount > 0) {
        this.payment_amount_changed();
    } else {
        this.calculate(this.table_id);
    }
}

this.account_changed = function(id, name, passthru) {
    if (isset(name))
        this.setAccountExt(name['currency_id'], name['balance'], name['balance_payable']);
}

this.type_changed = function() {
    if (this.type_input.getValue() == 'Credit Note') {
        this.amount_input.setValue(0);
        this.amount_input.setDisabled(true);
    } else if (this.amount_input.disabled) {
        this.amount_input.setDisabled(false);
    }
}

this.setup = function() {
	this.currency_input = SUGAR.ui.getFormInput(this.form, 'currency_id');
	this.account_input = SUGAR.ui.getFormInput(this.form, 'account');
	this.amount_input = SUGAR.ui.getFormInput(this.form, 'amount');
	this.type_input = SUGAR.ui.getFormInput(this.form, 'payment_type');

    this.addAccountExtra();
    
    SUGAR.ui.attachFormInputEvent(this.currency_input, 'onchange', function() { editor.currency_changed(); });
    SUGAR.ui.attachFormInputEvent(this.type_input, 'onchange', function() { editor.type_changed() });
	SUGAR.ui.attachInputEvent(this.amount_input, 'onchange', function() { editor.payment_amount_changed(); });
	
	SUGAR.ui.onInitForm(this.form, function() { editor.render(); });
}

this.render = function() {
	if (! this.editable)
		this.disableFields();
	if(this.init_lines) {
		for(var i = 0; i < this.init_lines.length; i++)
			this.addRow(editor.init_lines[i]);
	}
	this.calculate(this.table_id);
}

this.beforeSubmitForm = function() {
	return this.check_amount_allocations();
}

this.disableFields = function() {
    this.amount_input.setDisabled();
    this.currency_input.setDisabled();
    if(this.account_input)
		this.account_input.setDisabled();
}

this.addAccountExtra = function() {
    if(! this.account_input) return;
    this.account_input.addExtraReturnFields({'currency_id': 'currency_id', 'balance': 'balance', 'balance_payable': 'balance_payable'});
    if(this.payment_module == 'PaymentsOut') {
        var filter = [];
        filter[0] = {param: 'is_supplier', value: 1};
        this.account_input.add_filters = filter;
    }
    SUGAR.ui.attachInputEvent(this.account_input, 'onchange', function() { editor.account_changed(); });
}

return this;

})();
// end PaymentEditor

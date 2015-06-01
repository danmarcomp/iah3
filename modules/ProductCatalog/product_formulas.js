
function form_field(form_name, field_name) {
	return SUGAR.ui.getFormInput(form_name, field_name);
}

function input_num_value(field, params) {
	if(field) {
		return field.getValue(true);
	}
	return 0;
}

function disable_input(field) {
	if(! field) return;
	field.setDisabled(true);
}

function enable_input(field) {
	field.setDisabled(false);
}

function calculated_price(formula, values) {
	switch(formula) {
		case 'Profit Margin':
			if(! values.percentage) return null;
			return values.cost / (1 - (Math.min(values.percentage, 99.0)/100));
		case 'Markup Over Cost':
			if(! values.percentage) return null;
			return values.cost * (1 + (values.percentage / 100));
		case 'Discount from List':
			if(! values.percentage) return null;
			return values.list_price * (1 - (values.percentage/100));
		case 'Percent of Selling Price':
			// for support price only
			if(! values.unit_price || ! values.percentage) return null;
			return values.unit_price * values.percentage / 100;
		case 'Same As List':
			return values.list_price;
		default:
			return null;
	}
}

function visi(form_name, field, disable) {
    var elem = form_field(form_name, field);
    if(! elem) return;
    if (disable)
        elem.value = '';
    elem.disabled = disable;
}

function enforce_formula(form, no_upd_support) {
	form = SUGAR.ui.getForm(form);
	if(! form) return;
	var form_name = form.getAttribute('name');
	try {
		var formula = form_field(form, 'pricing_formula');
		if(! formula) return;
		var purchase = form_field(form, 'purchase_price');
		var percentage = form_field(form, 'ppf_perc');
		
		var values = {};
		values.cost = input_num_value(form_field(form, 'cost'));
		values.list_price = input_num_value(form_field(form, 'list_price'));
		values.purchase_price = input_num_value(purchase);
		values.percentage = input_num_value(percentage);
		
		disable_input(purchase);
		visi(form_name, 'ppf_perc', false);
		
		switch(formula.getValue()) {
			case '':
			case 'Fixed Price':
				enable_input(purchase);
				visi(form_name, 'ppf_perc', true);
				break;			
			case 'Profit Margin':
				if(values.percentage > 99)
					percentage.setValue(99, true);
				break;
			case 'Markup Over Cost':
			case 'Discount from List':
				break;
			case 'Same As List':
				visi(form_name, 'ppf_perc', true);
				break;
			default:
				alert("Error: unsupported pricing formula");
				enable_input(purchase);
				visi(form_name, 'ppf_perc', true);
				break;
		}
		
		var upd_value = calculated_price(formula.getValue(), values);
		if(upd_value !== null)
			purchase.setValue(upd_value, true);
		
		if(blank(no_upd_support))
			enforce_support_formula(form_name);
		
		return upd_value;
	}
	catch(e) {
		alert( "Exception: " + e );
	}
}

// Support price calculation
function enforce_support_formula(form) {
	form = SUGAR.ui.getForm(form);
	if(! form) return;
	var form_name = form.getAttribute('name');
	try {
		var formula = form_field(form, 'support_price_formula');
		var selling = form_field(form, 'support_selling_price');
		var percentage = form_field(form, 'support_ppf_perc');
		
		var values = {};
		values.cost = input_num_value(form_field(form, 'support_cost'));
		values.list_price = input_num_value(form_field(form, 'support_list_price'));
		values.selling_price = input_num_value(selling);
		values.unit_price = input_num_value(form_field(form, 'purchase_price'));
		values.percentage = input_num_value(percentage);
		
		disable_input(selling);
		visi(form_name, 'support_ppf_perc', false);
		
		switch(formula.getValue()) {
			case '':
			case 'Fixed Price':
				enable_input(selling);
				visi(form_name, 'support_ppf_perc', true);
				break;			
			case 'Profit Margin':
				if(values.percentage > 99)
					percentage.setValue(99, true);
				break;
			case 'Markup Over Cost':
			case 'Discount from List':
				break;
			case 'Same As List':
				visi(form_name, 'support_ppf_perc', true);
				break;
			case 'Percent of Selling Price':
				break;
			default:
				alert("Error: unsupported support price formula");
				enable_input(selling);
				visi(form_name, 'support_ppf_perc', true);
				break;
		}
		
		var upd_value = calculated_price(formula.getValue(), values);
		if(upd_value !== null)
			selling.setValue(upd_value, true);
		return upd_value;
	}
	catch(e) {
		alert( "Exception: " + e );
	}
}

function update_decimals(form) {
	var fields = [
		'cost', 'list_price', 'purchase_price',
		'support_cost', 'support_list_price', 'support_selling_price'];
	for(var i = 0; i < fields.length; i++) {
		var inp = SUGAR.ui.getFormInput(form, fields[i]);
		if(inp && ! inp.isBlank()) inp.setValue(inp.getValue(true), true);
	}
}


// vim: set autoindent smartindent :

var warned = false;
function app_list_strings_ord(idx, remove, keep) {
	var ret = {order: [], values: {}};
	if(isset(LANGUAGE.app_list_strings[idx])) {
		ret = deep_clone(LANGUAGE.app_list_strings[idx]);
		if (remove) {
			for (var i in remove) {
				delete ret[remove[i]];
			}
		}
		if (keep) {
			var kept = {order: [], values: {}};
			for (var i in keep) {
				if (ret.values[keep[i]]) {
					kept.values[keep[i]] = ret.values[keep[i]];
					kept.order.push(keep[i]);
				}
			}
			ret = kept;
		}
	}
	return ret;
}


// singleton
TallyEditor = new function() {

var editor = this;

this.id = 'TallyEditor';
this.module = '';
this.form_name = '';
this.editable = true;
this.editable_events = false;
this.form = null;
this.info = {};
this.groups = {};
this.groups_order = [];
this.unique_index = 0;
this.totals = {
	subtotal: 0.0,
	shipping: 0.0,
	discounts: 0.0,
	taxes: 0.0,
	total: 0.0
};
this.elements = {};
this.add_product_description = false; // config setting
this.add_assembly_description = false; // config setting
this.tabIndex = 30;
this.default_tax_code_id = '';
this.default_discount_id = '';

this.catalogOnly = true;
this.fixedPrices = true;
this.manualDiscounts = false;
this.standardDiscounts = false;
this.productCosts = false;
this.canAddRelatedEvents = false;
this.add_booked_hours_comments = false;
this.show_pretax_totals = false;


this.init = function(module, groups, editable) {
	this.module = module;
	this.isPO = (this.module == 'PurchaseOrders' || this.module == 'Bills' || this.module == 'CreditNotes');
	this.editable = !!editable;
	this.elements = {};

	if(groups.data && groups.order) {
		this.groups = deep_clone(groups.data);
		if (this.groups.length < 1) {
			this.groups = {};
		}
		this.groups_order = deep_clone(groups.order);
	}
	if(! this.groups_order.length)
		this.groups_order = [];
	for(var idx in this.groups) {
		var grp = this.groups[idx];
		if(! grp.lines_order || ! grp.lines_order.push)
			grp.lines_order = [];
		if(! grp.adjusts_order || ! grp.adjusts_order.push)
			grp.adjusts_order = [];

		if (YLang.isArray(grp.lines)) {
			grp.lines = {};
		}	
		if (YLang.isArray(grp.adjusts)) {
			grp.adjusts = {};
		}
		grp.taxes_order = [];
		grp.discounts_order = [];
		if(grp.pricing_percentage)
			grp.pricing_percentage = parseFloat(grp.pricing_percentage);
		if(! isset(grp.pricing_method))
			grp.pricing_method = '';
		grp.subtotal = parseFloat(grp.subtotal);
		grp.total = parseFloat(grp.total);
		grp.shipping = 0.0;
		grp.shipping_tax_class_id = this.default_tax_shipping ? (this.default_shipping_tax_code || 'all') : '-99';
		grp.shipping_adj_id = '';
		var has_attrs = [];
		for(var jdx = 0; jdx < grp.lines_order.length; jdx++) {
			var line_id = grp.lines_order[jdx];
			var line = grp.lines[line_id];
			line.quantity = parseFloat(line.quantity);
			line.ext_quantity = parseFloat(line.ext_quantity);
			line.sum_of_components = parseInt(line.sum_of_components);
			line.depth = parseInt(line.depth);

			if (!line.adjusts && !line.sum_of_components && !line.is_comment) {
				line.adjusts = {};
			}
			else {
				for(var kdx in line.adjusts) {
					var adj = line.adjusts[kdx];
					adj.amount = parseFloat(adj.amount);
					adj.rate = parseFloat(adj.rate);
				}
			}
			for(var kdx = 0; kdx < this.price_columns.length; kdx++)
				line[this.price_columns[kdx]] = parseFloat(line[this.price_columns[kdx]]);
			if(this.get_line_pricing_method(grp.pricing_method, line) == '')
				line.custom_unit_price = line.unit_price;
			if(line.attributes_meta)
				has_attrs.push(line_id);
		}
		for(var jdx = 0; jdx < grp.adjusts_order.length; jdx++) {
			var adj_id = grp.adjusts_order[jdx];
			var adj = grp.adjusts[adj_id];
			adj.editable = parseInt(adj.editable);
			adj.rate = parseFloat(adj.rate);
			adj.amount = parseFloat(adj.amount);
			if(adj.type == 'TaxedShipping' || adj.type == 'UntaxedShipping') {
				grp.shipping_adj_id = adj_id;
				grp.shipping = adj.amount;
				if (adj.type == 'UntaxedShipping')
					adj.tax_class_id = '-99';
				else {
					if (!adj.tax_class_id)
						adj.tax_class_id = 'all';
				}
				grp.shipping_tax_class_id = adj.tax_class_id;
			}
			else if(adj.related_type == 'TaxRates' && !adj.line_id) {
				grp.taxes_order.push(adj_id);
			}
			else if(adj.related_type == 'Discounts') {
				grp.discounts_order.push(adj_id);
			}
		}
		for(var idx = 0; idx < has_attrs.length; idx++)
			this.check_line_attributes(grp.lines[has_attrs[idx]]);
	}
}

this.setup = function() {
	this.form_name = this.form.getAttribute('name');
	this.add_hooks();
	this.recalculate(true);
	SUGAR.ui.onInitForm(this.form, function() { TallyEditor.post_setup(); });
}

this.post_setup = function() {
	this.redraw_all();

	if(this.editable && ! this.groups_order.length && this.add_initial_group) {
		this.add_group();
	 	// this.add_line_item(this.groups_order[0], {'related_type': 'ProductCatalog'});
	}
	
	if(this.init_popup_options) {
		var cid = this.get_currency_id();
		this.init_popup_options.display_currency_id = cid;
		open_popup_window(this.init_popup_options);
		this.init_popup_options = null;
	}
}

this.add_hooks = function() {
	var bill_acct = SUGAR.ui.getFormInput(this.form, 'billing_account'),
		ship_acct = SUGAR.ui.getFormInput(this.form, 'shipping_account'),
		supplier = SUGAR.ui.getFormInput(this.form, 'supplier');
	var acct_fields = {
			'billing_address_street': 'billing_address_street',
			'billing_address_city': 'billing_address_city',
			'billing_address_state': 'billing_address_state',
			'billing_address_postalcode': 'billing_address_postalcode',
			'billing_address_country': 'billing_address_country',
			'shipping_address_street': 'shipping_address_street',
			'shipping_address_city': 'shipping_address_city',
			'shipping_address_state': 'shipping_address_state',
			'shipping_address_postalcode': 'shipping_address_postalcode',
			'shipping_address_country': 'shipping_address_country',
			'shipping_provider_id' : 'default_shipper_id',
			'terms': 'default_terms',
			'currency_id': 'currency_id',
			'exchange_rate': 'exchange_rate',
			'tax_code_id': 'tax_code_id',
			'raw_balance': 'account_balance',
			'account_credit_limit' :'credit_limit_usd',
			'discount_id' : 'default_discount_id',
			'tax_information': 'tax_information',
			'account_popups': 'account_popups',
			'sales_popup': 'sales_popup'};
	if(bill_acct) {
		bill_acct.addExtraReturnFields(acct_fields);
		SUGAR.ui.attachInputEvent(bill_acct, 'onchange', function(k, v) {
			TallyEditor.setAccount(v, 'billing', true);
		});
	}
	if(ship_acct) {
		ship_acct.addExtraReturnFields(acct_fields);
		SUGAR.ui.attachInputEvent(ship_acct, 'onchange', function(k, v) {
			TallyEditor.setAccount(v, 'shipping', true);
		});
	}
	if (supplier) {
		supplier.addExtraReturnFields({'terms': 'default_purchase_terms'});
		SUGAR.ui.attachInputEvent(supplier, 'onchange', function(k, v) {
			SUGAR.ui.setFormInputValues(this.form, v);
		});
	}

	this.currency_input = SUGAR.ui.getFormInput(this.form, 'currency_id');
	SUGAR.ui.attachInputEvent(this.currency_input, 'onrateupdate', function(old_rate, new_rate) {
		TallyEditor.update_exchange_rate(old_rate, new_rate);
	});
	if(! this.editable && this.currency_input)
		this.currency_input.setDisabled();
}

this.make_editable = function(events_only) {
	if(! this.editable) {
		this.currency_input.setDisabled(false);
        var tax_exempt = SUGAR.ui.getFormInput(this.form, 'tax_exempt');
		if (tax_exempt) tax_exempt.setDisabled(false);
		if (!events_only) {
			this.editable = true;
		} else {
			this.editable_events = true;
		}
		this.recalculate(true);
		this.redraw_all();
	}
}

// -- Group management

this.group_template = {
	name: '',
	status: '',
	pricing_method: '',
	pricing_percentage: '0.0',
	lines: {},
	lines_order: [],
	adjusts: {},
	adjusts_order: [],
	taxes_order: [],
	discounts_order: [],
	shipping_tax_class_id : '',
	shipping: 0.0,
	shipping_adj_id: '',
	subtotal: 0.0,
	total: 0.0
};
this.new_group_defaults = {
	status: 'Draft',
	group_type: 'products',
	// set during init
	taxes: [],
	discounts: []
};

this.line_template = {
	quantity: 1,
	ext_quantity: 1,
	sum_of_components: 0,
	name: '',
	parent_id: '',
	tax_class_id: '',
	mfr_part_no: '',
	vendor_part_no: '',
	cost_price: 0.0,
	list_price: 0.0,
	unit_price: 0.0,
	std_unit_price: 0.0,
	ext_price: 0.0,
	pricing_adjust_id: null,
	adjusts: {}
};

this.price_columns = [
	'cost_price',
	'list_price',
	'unit_price',
	'std_unit_price',
	'ext_price'
];


this.add_group = function(group_info) {
	var group = deep_clone(this.group_template);
	var new_group = false;
	if(! YLang.isObject(group_info))
		new_group = true;
	else
		for(var k in group_info) group[k] = group_info[k];
	if(blank(group.id))
		group.id = 'newgroup~' + (this.unique_index ++);
	var prevgrp = null;
	if(this.groups_order.length) {
		var previd = this.groups_order[this.groups_order.length - 1];
		prevgrp = this.groups[previd];
	}
	this.groups[group.id] = group;
	this.groups_order.push(group.id);

	if(new_group) {
		function copy_adj(adj) {
			var ret = deep_clone(adj);
			ret.id = 'newadj~' + (editor.unique_index ++);
			ret.amount = 0.0;
			return ret;
		}
		if(prevgrp) {
			// copy values from last group
			group.status = prevgrp.status;
			group.group_type = prevgrp.group_type;
			group.pricing_method = prevgrp.pricing_method;
			group.pricing_percentage = prevgrp.pricing_percentage;
			for(var idx = 0; idx < prevgrp.taxes_order.length; idx++) {
				var adj = copy_adj(prevgrp.adjusts[prevgrp.taxes_order[idx]]);
				group.adjusts[adj.id] = adj;
				group.adjusts_order.push(adj.id);
				group.taxes_order.push(adj.id);
			}
			for(var idx = 0; idx < prevgrp.discounts_order.length; idx++) {
				var adj = copy_adj(prevgrp.adjusts[prevgrp.discounts_order[idx]]);
				group.adjusts[adj.id] = adj;
				group.adjusts_order.push(adj.id);
				group.discounts_order.push(adj.id);
			}
			group.shipping_tax_class_id = prevgrp.shipping_tax_class_id;
		}
		else {
			for(var idx in this.new_group_defaults) {
				if(blank(group[idx])) {
					if(YLang.isObject(this.new_group_defaults[idx]))
						group[idx] = deep_clone(this.new_group_defaults[idx]);
					else
						group[idx] = this.new_group_defaults[idx];
				}
			}
			group.shipping_tax_class_id = this.default_tax_shipping ? (this.default_shipping_tax_code || 'all') : '-99';
		}
		if(this.default_discount_id && ! group.discounts_order.length) {
			var adj = copy_adj(this.get_discount_adjustment(this.default_discount_id));
			group.discounts_order.push(adj.id);
			group.adjusts_order.push(adj.id);
			group.adjusts[adj.id] = adj;
		}
		if(! group.shipping_adj_id) {
			this.update_group_field(group.id, 'shipping_tax_class_id', group.shipping_tax_class_id);
		}
	}
	if(group.add_lines) {
		for(var i = 0; i < group.add_lines.length; i++) {
			this.add_line_item(group.id, this.load_line_item(group.add_lines[i]), true, false);
		}
	}
	this.recalc_group(group.id);
	this.redraw_all();
}


this.redraw_group = function(group_id, no_insert) {
	var container = this.get_element([group_id, 'container']);
	var do_append = false;
	if(! container) {
		container = document.createElement('div');
		this.add_element([group_id, 'container'], container);
		do_append = true;
	}
	container.innerHTML = '';

	var group_info = this.groups[group_id];
	if(! group_info)  return;

	container.appendChild(document.createElement('hr'));

	var header = this.render_group_header_table(group_id);
	container.appendChild(header);
	this.add_element([group_id, 'header'], header);

	var hr = createElement2('hr', {style: {borderStyle: 'dotted', borderWidth: '1px 0 0 0', height: 0, width: '99%'}});
	container.appendChild(hr);

	var lines = createTable();
	var hdr_row = lines.insertRow(lines.rows.length);
	this.render_line_items_header_row(group_info, hdr_row);
	for(var idx = 0; idx < group_info.lines_order.length; idx++) {
		var line_id = group_info.lines_order[idx];
		var line = group_info.lines[line_id];
		var ptable = lines;
		var rowoffs = 0;
		var is_last = 0;
		if(line.depth) {
			ptable = this.get_line_items_subtable(group_id, line.parent_id);
			if(! ptable)
				continue;
			rowoffs = -1;
			is_last = 1;
			for(var jdx = idx+1; jdx < group_info.lines_order.length; jdx++) {
				var l = group_info.lines[group_info.lines_order[jdx]];
				if(l.parent_id == line.parent_id) {
					is_last = 0;
					break;
				}
			}
		}

		var body_row = ptable.insertRow(ptable.rows.length + rowoffs);
		this.render_line_item_row(body_row, group_id, line_id, is_last);
		this.add_element([group_id, 'lines', line_id, 'row'], body_row);
		if(line.sum_of_components) {
			var parts_row = lines.insertRow(lines.rows.length);
			var subtbl = this.render_line_items_subtable(group_id, line_id);
			var cell = createTableCell({colSpan: this.isPO ? 9 : 10});
			cell.appendChild(subtbl);
			parts_row.appendChild(cell);
			this.add_element([group_id, 'lines', line_id, 'parts_row'], parts_row);
		}
		if(line.related_type == 'ProductCatalog' || line.related_type == 'Assemblies') {
			var attrs_row = ptable.insertRow(ptable.rows.length + rowoffs);
			this.add_element([group_id, 'lines', line_id, 'attrs_row'], attrs_row);
			if(has_product_attributes(line)) {
				this.render_attrs_row(attrs_row, group_id, line_id, is_last);
			}
			else {
				attrs_row.style.display = 'none';
			}
		}
		if(! line.sum_of_components && !line.is_comment && group_info.group_type != 'service'
				&& !this.isPO && this.module != 'Shipping' && this.module != 'Receiving') {
			var pricing_row = ptable.insertRow(ptable.rows.length + rowoffs);
			this.render_line_item_pricing_row(pricing_row, group_id, line_id, is_last);
			this.add_element([group_id, 'lines', line_id, 'pricing_row'], pricing_row);
			if (this.module == 'Invoice') {
				var canedit = this.editable || this.editable_events;
				if((canedit && this.canAddRelatedEvents) || ! blank(line.event_session_id)) {
					var event_row = ptable.insertRow(ptable.rows.length + rowoffs);
					this.render_line_item_event_row(event_row, group_id, line_id, is_last);
					this.add_element([group_id, 'lines', line_id, 'event_row'], event_row);
				}
			}
		}

		if (this.module != 'Shipping' && this.module != 'Receiving' && has_taxes(line)) {                  
			var taxes_row = ptable.insertRow(ptable.rows.length + rowoffs);
			this.add_element([group_id, 'lines', line_id, 'taxes_row'], taxes_row);
			this.render_taxes_row(taxes_row, group_id, line_id, is_last);
		}

		if (this.add_serials && !line.is_comment && 
			(this.module == 'Shipping' || this.module == 'Receiving'
			|| this.module == 'Invoice' || this.module == 'SalesOrders'
			)
		) {
			var serial_row = ptable.insertRow(ptable.rows.length + rowoffs);
			this.render_serial_row(serial_row, group_id, line_id, is_last);
			this.add_element([group_id, 'lines', line_id, 'serial_row'], serial_row);
		}
		if (this.editable && !line.is_comment && !line.sum_of_components && this.module != 'Shipping' && this.module != 'Receiving') {
            if (false)  { //disabled
            	var buttons_row = ptable.insertRow(ptable.rows.length + rowoffs);
				var attrs = {};
				if(line.depth && (!is_last || this.editable)) {
					var bgStyle = treeNodeBg('dots');
					attrs.style = bgStyle;
				}
				var cell = createTableCell(attrs);
				buttons_row.appendChild(cell);
				var cell = createTableCell();
				buttons_row.appendChild(cell);
				var cell = createTableCell();
				buttons_row.appendChild(cell);
				this.add_row_buttons(cell, group_id, line_id);
			}
		}
		if(! this.editable || idx < group_info.lines_order.length - 1) {
			var spacer_row = ptable.insertRow(ptable.rows.length + rowoffs);
			this.add_element([group_id, 'lines', line_id, 'spacer_row'], spacer_row);
			var attrs = {};
			if(line.depth && (!is_last || this.editable)) {
				var bgStyle = treeNodeBg('dots');
				attrs.style = bgStyle;
			}
			var cell = createTableCell(attrs);
			spacer_row.appendChild(cell);
			cell.style.lineHeight = '5px';
			cell.appendChild(nbsp());
			spacer_row.appendChild(cell);
		}
	}
	container.appendChild(lines);
	this.add_element([group_id, 'lines'], lines);

	var hr = createElement2('hr', {style: {borderStyle: 'dotted', borderWidth: '1px 0 0 0', height: 0, width: '99%'}});
	container.appendChild(hr);

	var footer = this.render_group_footer_table(group_id);
	container.appendChild(footer);
	this.add_element([group_id, 'footer'], footer);

	if(do_append && !no_insert) {
		var top_level = $('tally_groups');
		top_level.appendChild(container);
	}
	return container;
}

this.add_row_buttons = function(cell, group_id, line_id) {
	var grp = this.groups[group_id];
	if (!grp) return;
	var line = grp.lines[line_id];
	if (!line) return;
	var onclick = function() {
		editor.add_line_adjustment(line, {related_type: 'TaxRates', editable: 1, type: 'StandardTax', line_id: line_id, name:''});
		editor.recalc_group(group_id);
		editor.redraw_group(group_id);
		return false;
	}
	var iconlink = createIconLink('insert', 'LBL_ADD_TAX', onclick);
	cell.appendChild(iconlink);
}

this.redraw_group_footer = function(group_id) {
	var top = this.get_element([group_id, 'container']);
	var foot = this.get_element([group_id, 'footer']);
	var foot2 = this.render_group_footer_table(group_id);
	if(top && foot) {
		top.insertBefore(foot2, foot);
		top.removeChild(foot);
		this.add_element([group_id, 'footer'], foot2);
	}

	var grp = this.groups[group_id];
}

this.remove_group = function(group_id) {
	this.groups_order.remove(group_id);
	delete this.groups[group_id];
	var tbl = this.get_element([group_id, 'container'])
	if(tbl) {
		var top_level = $('tally_groups');
		top_level.removeChild(tbl);
	}
	this.remove_elements(group_id);
	this.redraw_all();
	return true;
}


// -- Line item management

this.add_line_item = function(group_id, spec, no_redraw, focus) {
	var grp = this.groups[group_id];
	if(! grp)  return;
	if(blank(spec.id))
		spec.id = 'newline~' + (this.unique_index ++);
	spec.depth = 0;
	var inspos = 0;
	if(spec.parent_id) {
		var found = 0;
		for(var idx = 0; idx < grp.lines_order.length; idx++) {
			var plid = grp.lines_order[idx];
			if(grp.lines[plid].id == spec.parent_id) {
				found = 1;
				if(grp.lines[plid].sum_of_components)
					spec.depth = grp.lines[plid].depth + 1;
			}
			else if(grp.lines[plid].parent_id == spec.parent_id) {
				found = 1;
			}
			else if(found)
				break;
			inspos++;
		}
	}
	else
		inspos = grp.lines_order.length;
	grp.lines_order.splice(inspos, 0, spec.id);
	var newline;
	if(spec.related_type == 'Notes')
		newline = { is_comment: 1 };
	else
		newline = deep_clone(this.line_template);
	if(spec.related_type == 'Assemblies' || spec.related_type == 'SupportedAssemblies')
		newline.sum_of_components = 1;
	for(var idx in spec)
		newline[idx] = spec[idx];
	if (!newline.pricing_adjust_id) {
		var prevln = null;
		if(grp.lines_order.length > 1) {
			var previd = grp.lines_order[grp.lines_order.length - 2];
			prevln = grp.lines[previd];
		}
		if (prevln && !blank(prevln.pricing_adjust_id) && prevln.adjusts) {
			var tpl = deep_clone(prevln.adjusts[prevln.pricing_adjust_id]);
			var adj = this.add_line_adjustment(newline, tpl);
			newline.pricing_adjust_id = adj.id;
		}
	}
	if(this.get_line_pricing_method(grp.pricing_method, newline) != '') {
		var fn = this.get_pricing_method_fn(group_id, newline);
		if(fn) fn(newline);
	}
	this.update_ext_price(newline);
	grp.lines[newline.id] = newline;
	if(! no_redraw) {
		this.recalc_group(group_id);
		this.redraw_group(group_id);
		if(focus) {
			var ref_id = [group_id, 'lines', spec.id, spec.related_type == 'Notes' ? 'body' : 'name_input'],
				div = this.get_element(ref_id), inp;
			if(div && (inp = SUGAR.ui.getFormInput(this.form, div.id)))
				inp.select();
		}
	}
	return spec.id;
}

this.replace_line_item = function(group_id, line_id, spec, recalc, no_redraw) {
	var grp = this.groups[group_id];
	if(! grp)  return;
	if(! line_id)
		line_id = this.add_line_item(group_id, spec, true);
	var line = grp.lines[line_id];
	if(! line) return;
	for(var idx in spec) {
		line[idx] = spec[idx];
	}
	if(this.get_line_pricing_method(grp.pricing_method, line) != '') {
		var fn = this.get_pricing_method_fn(group_id, line);
		if(fn) fn(line);
	}
	this.update_ext_price(line);
	delete line.custom_unit_price;
	if(recalc)
		this.recalc_group(group_id, ! no_redraw);
	return line_id;
}

this.load_line_item = function(values) {
	if(! YLang.isObject(values)) values = {};
	if (values.tax_class_id) {
        var tax_exempt = SUGAR.ui.getFormInput(this.form, 'tax_exempt');
		if (tax_exempt && tax_exempt.getValue()) {
			values.tax_class_id = '-99';
		}
		else if(values.tax_class_id != '-99' && this.default_tax_code_id) {
			values.tax_class_id = this.default_tax_code_id;
		}
	}
	if (this.isPO && !blank(values.purchase_name)) {
		values.name = values.purchase_name;
	}
	var currency_id = this.get_currency_id(),
		line_currency_id = get_default(values.currency_id, ''),
		conversion_rate = this.currency_rate(),
		decimals = this.currency_decimal_places(),
		numval;
	if(! line_currency_id) line_currency_id = '-99';
	for(var idx = 0; idx < this.price_columns.length; idx++) {
		var col = this.price_columns[idx];
		if(line_currency_id == currency_id && isset(values['raw_'+col])) {
			values[col] = trunc(parseFloat(values['raw_'+col]), decimals);
		} else {
			numval = parseFloat(get_default(values[col], 0.0));
			values[col] = trunc(numval * conversion_rate, decimals);
		}
	}
	if(! isset(values.std_unit_price) && isset(values.unit_price))
		values.std_unit_price = values.unit_price;
	if(! isset(values.name))
		values.name = '';
	if(! isset(values.related_id))
		values.related_id = '';
	if(! isset(values.mfr_part_no))
		values.mfr_part_no = '';
	delete values.id;
	delete values._display;

	if(values.related_type == 'Assets' || (blank(values.unit_price) && this.module != 'PurchaseOrders' && this.module != 'Bills' && this.module != 'CreditNotes')) {
		values.unit_price = values.list_price;
	} else if(values.related_type == 'BookingCategories') {
		var extra = [];
		if (values.location != '') extra.push(values.location);
		if (values.duration != '') extra.push(values.duration);
		if (values.seniority != '') extra.push(values.seniority);
		if (extra.length > 0) {
			values.name += ' (' + extra.join(' / ') + ')';
		}
		values.unit_price = values.std_unit_price = values.list_price;
		values.cost_price = 0.00;
	}

	if(values.related_type == 'ProductCatalog'
			&& (this.module == 'Quotes' || this.module == 'SalesOrders' || this.module == 'Invoice' || this.isPO)
			&& values.related_id && !isset(values.attributes_meta)) {
		values.attributes_meta = this.get_product_attrs(values.related_id);
	}
	return values;
}

this.return_line_item = function(group_id, line_id, values, custom_redraw) {
	this.load_line_item(values);

	var new_line_id = this.replace_line_item(group_id, line_id, values, true, true);
	var line = this.groups[group_id].lines[new_line_id];
	var redraw_group = (new_line_id != line_id && ! custom_redraw);
	if(line) {
		if(line.adjusts) {
			for (var i in line.adjusts) {
				var adj = line.adjusts[i];
				if (adj.type == 'ProductAttributes') {
					delete line.adjusts[i];
				}
			}
		}
		if(values.attributes_meta) {
			for (var i in values.attributes_meta) {
				this.add_line_adjustment(line, {type: 'ProductAttributes', related_type: 'ProductAttributes', 'name': i });
			}
		}
		if(! redraw_group && ! custom_redraw) {
			this.redraw_line_item(group_id, new_line_id);
			if(line.parent_id)
				this.redraw_line_item(group_id, line.parent_id);
		}

		if(values.related_type == 'Assemblies' || values.related_type == 'SupportedAssemblies') {
			this.clear_assembly_components(group_id, new_line_id);
			this.add_assembly_components(group_id, new_line_id, values.related_id, values.related_type, this.groups[group_id].group_type == 'support');
			if(this.add_assembly_description && ! blank(values.description_plain))
				this.add_line_item(group_id, { related_type: 'Notes', parent_id: new_line_id, body: values.description_plain }, true);
			if(! custom_redraw)
				redraw_group = true;
		}
		else if(values.related_type == 'ProductCatalog') {
			if(this.add_product_description && ! blank(values.description_plain)) {
				var grp = this.groups[group_id];
				if(grp && grp.lines && ! grp.lines[new_line_id].parent_id) {
					this.add_line_item(group_id, { related_type: 'Notes', parent_id: new_line_id, body: values.description_plain });
				}
			}

		}
		if(custom_redraw !== 1)
			this.recalc_group(group_id);
		if(redraw_group || custom_redraw == 2) {
			this.redraw_group(group_id);
			this.redraw_totals();
		}
		else if(! custom_redraw) {
			this.redraw_group_footer(group_id);
			this.redraw_totals();
		}
	}
}

this.get_product_attrs = function(ids) {
	var attrs = {},
		params;
	if(ids && ids.unshift) {
		params = '&record_ids=';
		for(var idx = 0; idx < ids.length; idx++)
			params += encodeURIComponent(ids[idx]) + ',';
	} else
		params = '&record=' + encodeURIComponent(ids);
	var result = call_json_method(
		'ProductAttributes',
		'get_attributes_for_product',
		params
	);
	if(! result)
		return attrs;
	var decimals = this.currency_decimal_places(),
		currency_id = this.get_currency_id(),
		conversion_rate = this.currency_rate();
	for(var ind = 0; ind < result.length; ind++) {
		if(! YLang.isObject(result[ind]))
			continue;
		var instance = deep_clone(result[ind]);
		var key = instance.name;
		if(instance.parent_id) key += ':'+instance.parent_id;
		var line_currency_id = get_default(instance.currency_id, '-99');
		instance.price_usdollar = parseFloat(instance.price_usdollar || 0);
		if(line_currency_id == currency_id)
			instance.price = trunc(parseFloat(instance.price || 0), decimals);
		else
			instance.price = trunc(instance.price_usdollar * conversion_rate, decimals);
		if(! attrs[key])
			attrs[key] = [];
		attrs[key].push(instance);
	}
	return attrs;
}

this.return_line_discount = function(group_id, line_id, values) {
	var currency_id = this.get_currency_id();
	var conversion_rate = this.currency_rate();
	if(isset(values.amount)) {
		var numval = parseFloat(values.amount);
		var decimals = this.currency_decimal_places();
		values.amount = trunc(numval * conversion_rate, decimals);
	}
	var spec = {
		related_id: values.related_id,
		related_type: 'Discounts',
		name: values.name
	}
	if (values.discount_type == 'percentage') {
		spec.amount = 0.0;
		spec.rate = parseFloat(values.rate);
		spec.type = 'StdPercentDiscount';
	} else {
		spec.rate = 0.0;
		spec.amount = values.amount;
		spec.type = 'StdFixedDiscount';
	}
	var grp = this.groups[group_id];
	if(grp && grp.lines && grp.lines[line_id].pricing_adjust_id) {
		this.update_price_adjustment(group_id, line_id, spec);
	}
}

this.return_line_event = function(group_id, line_id, values) {
	var grp = this.groups[group_id];
	if (!grp) return;
	var line = grp.lines[line_id];
	if (!line) return;
	line.event_session_name = values.name;
	line.event_session_id = values.id;
	line.event_session_date = values.date_start;
	this.redraw_group(group_id);
}

this.add_assembly_components = function(group_id, line_id, related_id, related_type, support) {
	var is_supp_ass = (related_type == 'SupportedAssemblies');
	var result = call_json_method(
		related_type,
		'get_product_list',
		'&record='+related_id
	);
	if(! result)
		return;
	var decimals = this.currency_decimal_places();
	for (var ind = 0; ind < result.length; ind++) {
		if(! YLang.isObject(result[ind]))
			break;
		var instance = deep_clone(result[ind]);
		var convrate = this.currency_rate();
		if (is_supp_ass || support) {
			var discount = 0;
		} else if (instance.discount_type == 'fixed') {
			var discount = parseFloat(instance.discount_value);
		} else {
			var discount = instance.purchase_usdollar * parseFloat(instance.discount_value) / 100.00;
		}
		var currency_id = this.get_currency_id();
		var line_currency_id = get_default(instance.currency_id, '');
		if(! line_currency_id) line_currency_id = '-99';
		var prc = {};
		if (is_supp_ass) {
			prc = {
				cost_price: 'support_cost_usdollar',
				list_price: 'unit_support_usdollar'
			};
		} else {
			if (support) {
				prc = {
					cost_price: 'support_cost_usdollar',
					list_price: 'support_list_usdollar',
					unit_price: 'support_selling_usdollar'
				};
			} else {
				prc = {
					cost_price: 'cost_usdollar',
					list_price: 'list_usdollar',
					unit_price: 'purchase_usdollar'
				};
			}
		}
		var prod = {
			related_id: instance.id,
			quantity: instance.quantity,
			parent_id: line_id,
			related_type: support ? 'Assets' :  'ProductCatalog',
			name: instance.name.replace(/&amp;/gi,'&').replace(/&lt;/gi,'<').replace(/&gt;/gi,'>').replace(/&#039;/gi,'\'').replace(/&quot;/gi,'"'),
			tax_class_id: instance.tax_code_id,
			mfr_part_no: instance.manufacturers_part_no,
			vendor_part_no: instance.vendor_part_no			
		};
		for(col in prc) {
			//var numval = parseFloat(instance[col]);
			var numval = parseFloat(get_default(instance[prc[col]], '0.00'));
			if(col == 'unit_price') numval -= discount;

			if(line_currency_id == currency_id && isset(instance['raw_'+col])) {
				prod[col] = trunc(parseFloat(instance['raw_'+col]), decimals);
			} else {
				prod[col] = trunc(numval * convrate, decimals);
			}
		}
		prod.std_unit_price = prod.unit_price;
		if (support) prod.serial_no = instance.serial_no;
        var tax_exempt = SUGAR.ui.getFormInput(this.form, 'tax_exempt');
		if (tax_exempt && tax_exempt.getValue()) {
			prod.tax_class_id = '-99';
		}
		if(this.isPO)
			prod.unit_price = prod.std_unit_price = prod.cost_price;
		this.add_line_item(group_id, prod, true);
	}
}

this.clear_assembly_components = function(group_id, line_id) {
	var grp = this.groups[group_id];
	if(! grp) return;
	for(var idx = grp.lines_order.length - 1; idx >= 0; idx --) {
		if(grp.lines[grp.lines_order[idx]].parent_id == line_id) {
			delete grp.lines[grp.lines_order[idx]];
			grp.lines_order.splice(idx, 1);
		}
	}
}

this.move_line_item = function(group_id, line_id, offset, check_only) {
	var grp = this.groups[group_id];
	if(! grp)  return false;
	var line = grp.lines[line_id];
	if(! line) return false;
	var startpos = -1;
	for(var idx = 0; idx < grp.lines_order.length; idx++) {
		if(grp.lines_order[idx] == line_id) {
			startpos = idx;
			break;
		}
	}
	if(startpos < 0)
		return false;
	var numchild = 0;
	for(var cpos = startpos + 1; cpos < grp.lines_order.length; cpos++) {
		if(grp.lines[grp.lines_order[cpos]].depth <= line.depth)
			break;
		numchild ++;
	}
	function fix_range(x, min, max) {
		return Math.max(0, Math.min(grp.lines_order.length - 1, x));
	}
	var nextpos = fix_range(startpos + offset + (offset > 0 ? numchild : 0));
	if(grp.lines[grp.lines_order[nextpos]].depth < line.depth || startpos == nextpos)
		return false;
	if (check_only)
		return true;
	while(offset < 0 && nextpos > 0
		&& grp.lines[grp.lines_order[nextpos]].depth > line.depth)
			nextpos --;
	var next = grp.lines[grp.lines_order[nextpos]];
	var nextchild = 0;
	for(var cpos = nextpos + 1; cpos < grp.lines_order.length; cpos++) {
		if(grp.lines[grp.lines_order[cpos]].depth <= next.depth)
			break;
		nextchild ++;
	}
	var movefrom, moveto, movecount;
	if(offset > 0) {
		movefrom = nextpos; moveto = startpos; movecount = nextchild + 1;
	}
	else {
		movefrom = startpos; moveto = nextpos; movecount = numchild + 1;
	}
	var cut = grp.lines_order.splice(movefrom, movecount);
	for(var idx = cut.length - 1; idx >= 0; idx--)
		grp.lines_order.splice(moveto, 0, cut[idx]);
	this.redraw_group(group_id);
}

this.remove_line_item = function(group_id, line_id) {
	var grp = this.groups[group_id];
	if(! grp)  return;
	var row = this.get_element([group_id, 'lines', line_id, 'row']);
	if(row)
		row.parentNode.removeChild(row);
	var row = this.get_element([group_id, 'lines', line_id, 'pricing_row']);
	if(row)
		row.parentNode.removeChild(row);
	var row = this.get_element([group_id, 'lines', line_id, 'event_row']);
	if(row)
		row.parentNode.removeChild(row);
	var row = this.get_element([group_id, 'lines', line_id, 'parts_row']);
	if(row)
		row.parentNode.removeChild(row);
	var row = this.get_element([group_id, 'lines', line_id, 'serial_row']);
	if(row)
		row.parentNode.removeChild(row);
	var row = this.get_element([group_id, 'lines', line_id, 'attrs_row']);
	if(row)
		row.parentNode.removeChild(row);
	var row = this.get_element([group_id, 'lines', line_id, 'spacer_row']);
	if(row)
		row.parentNode.removeChild(row);
	var dropidx = [];
	for(var idx = 0; idx < grp.lines_order.length; idx++) {
		var iid = grp.lines_order[idx];
		if(iid == line_id)
			dropidx.push(idx);
		else {
			var line = grp.lines[iid];
			if(line && line.parent_id == line_id)
				dropidx.push(idx);
		}
	}
	for(var idx = dropidx.length-1; idx >= 0; idx--)
		grp.lines_order.splice(dropidx[idx], 1);
	this.recalc_group(group_id, true);
	return true;
}

this.get_line_item = function(group_id, line_id) {
	var grp = this.groups[group_id];
	if(! grp)  return grp;
	return grp.lines[line_id];
}

this.update_line_item = function(group_id, line_id, field, value, recalc) {
	var grp = this.groups[group_id];
	if(! grp)  return;
	var line = grp.lines[line_id];
	if(! line) return;
	if(field == 'quantity') {
		if(line.quantity)
			line.ext_quantity = line.ext_quantity / line.quantity * value;
		else
			line.ext_quantity = value; // recalc fixes assembly components
		this.update_ext_price(line);
	}
	else if(field == 'adjust_price') {
		var old_pmethod = this.get_line_pricing_method(grp.pricing_method, line);
		if(old_pmethod == '') {
			field = 'unit_price';
			value = trunc(value + line.unit_price, 5);
		}
		else
			field = null;
	}
	if(field)
		line[field] = value;
	var pmethod = this.get_line_pricing_method(grp.pricing_method, line);
	if(field == 'unit_price') {
		if(pmethod == '')
			line.custom_unit_price = line.unit_price;
		this.update_ext_price(line);
	}
	else if(field == 'pricing_percentage')
		this.update_pricing_method(group_id);
	if(pmethod != '') {
		var fn = this.get_pricing_method_fn(group_id, line);
		if(fn) fn(line);
	}
	if(recalc)
		this.recalc_group(group_id, true);
}

this.display_pricing_options = function(line_id, method) {
	var div = this.get_element([line_id, 'pricing_perc_div']);
	if(div) {
		if (typeof(method) == 'undefined' || method == '' || method == 'list' || method == 'inherit' || method == 'stddiscount') {
			div.style.display = 'none';
		} else {
			div.style.display = '';
		}
	}
	var div = this.get_element([line_id, 'discount_div']);
	if(div) {
		if (method != 'stddiscount') {
			div.style.display = 'none';
		} else {
			div.style.display = '';
		}
	}
}

this.update_group_field = function(group_id, field, value, recalc) {
	var grp = this.groups[group_id];
	if(! grp)  return;
    if (typeof(value) == 'number' && isNaN(value)) value = 0;
	grp[field] = value;
	if(field == 'shipping' || field == 'shipping_tax_class_id') {
		var adj = grp.adjusts[grp.shipping_adj_id];
		if(field == 'shipping_tax_class_id')
			grp.shipping_tax_class_id = value;
		if(adj) {
			if(field == 'shipping')
				adj.amount = value;
			else {
				adj.type = value != '-99' ? 'TaxedShipping' : 'UntaxedShipping';
				adj.tax_class_id = value;
			}
		}
		else {
			var adj_id = this.add_adjustment(group_id, {
				related_type: 'ShippingProviders',
				type: grp.shipping_tax_class_id != '-99' ? 'TaxedShipping' : 'UntaxedShipping',
				amount: field == 'shipping_tax_class_id' ? 0.00 : value,
				tax_class_id: field == 'shipping_tax_class_id' ? value : '-99'
			});
			grp.shipping_adj_id = adj_id;
		}
	}
	else if(field == 'pricing_method') {
		this.update_pricing_method(group_id);
		var div = this.get_element([group_id, 'pricing_perc_div']);
		if(div)
			div.style.display = (value == '' || value == 'list') ? 'none' : '';
	}
	else if(field == 'pricing_percentage') {
		this.update_pricing_method(group_id);
	}
	if(recalc)
		this.recalc_group(group_id, true);
}



// -- Calculations

// when the form is not editable, this method only updates group.cost (which is not saved)
this.recalc_group = function(group_id, redraw) {
	var discount_before_taxes = this.get_discount_before_taxes();
	var grp = this.groups[group_id];
	if(! grp) return;
	grp.cost = 0.0;
	var tally = [];
	var all_taxcodes = {};

	var adj_ord = [];
	var taxes_ord = [];
	var taxes = {};
	var old_taxes = {};
	var rates = [];	

	// iterate lines
	var decimals = this.currency_decimal_places();
	for(var idx = 0; idx < grp.lines_order.length; idx++) {
		var line = grp.lines[grp.lines_order[idx]];
		if(! line.is_comment) {
			if(this.editable) {
				if(line.sum_of_components) {
					for(var fdx = 0; fdx < this.price_columns.length; fdx++)
						line[this.price_columns[fdx]] = 0.0;
				}
				if(line.parent_id && grp.lines[line.parent_id]) {
					var parent = grp.lines[line.parent_id];
					line.ext_quantity = trunc(parent.quantity * line.quantity, 2);
				}
				else
					line.ext_quantity = line.quantity;
				line.unit_price = trunc(line.unit_price, 5);
				//line.ext_price = trunc(line.ext_quantity * line.unit_price, decimals);
				this.update_ext_price(line);
				if(! line.sum_of_components) {
					tally.push({
						unadj_amount: line.ext_price,
						amount: line.ext_price,
						taxcode: line.tax_class_id,
						id: line.id}
					);
					if(line.tax_class_id && line.tax_class_id != '-99')
						all_taxcodes[line.tax_class_id] = 1;
				}


				for(var jdx in line.adjusts) {
					var adj = line.adjusts[jdx];
					//if (adj.line_id) continue;
					if(adj.type == 'StandardTax' || adj.type == 'CompoundedTax') {
						if (SysData.taxrates[adj.related_id]) {
							var r = deep_clone(SysData.taxrates[adj.related_id]);
							var type = r.compounding != 'compounded' ? 'StandardTax' : 'CompoundedTax';
							adj.amount = 0.0;
							r.editable = 1;
							r.line_id = line.id;
							rates.push(r);
						}
					}
				}  
			}
			if(! line.sum_of_components)
				grp.cost += trunc(line.ext_quantity * line.cost_price, decimals);
		}
		if(redraw)
			this.redraw_line_item(group_id, line.id);
	}
	for(var idx = 0; idx < grp.discounts_order.length; idx++) {
		var adj = grp.adjusts[grp.discounts_order[idx]];
		if(adj.tax_class_id && adj.tax_class_id != '-99') {
				all_taxcodes[adj.tax_class_id] = 1;
		}
	}

	if(grp.shipping_adj_id && isset(grp.adjusts[grp.shipping_adj_id])) {
		var shipping = grp.adjusts[grp.shipping_adj_id];
		if (shipping.tax_class_id && shipping.tax_class_id != '-99') {
			all_taxcodes[shipping.tax_class_id] = 1;
		}
	}

	if(this.editable) {
		var discount_taxes = {};
		// update assembly prices
		for(var idx = grp.lines_order.length - 1; idx >= 0; idx--) {
			var line_id = grp.lines_order[idx];
			var line = grp.lines[line_id];
			if(line.parent_id && isset(grp.lines[line.parent_id]) && ! line.is_comment) {
				var parent = grp.lines[line.parent_id];
				if(parent.sum_of_components) {
					for(var fdx = 0; fdx < this.price_columns.length; fdx++) {
						var col = this.price_columns[fdx];
						if(col != 'ext_price')
							parent[col] += trunc(line[col] * (line.ext_quantity / parent.ext_quantity), decimals);
					}
					if(redraw)
						this.redraw_line_item(group_id, parent.id);
				}
				parent.ext_price = trunc(parent.ext_quantity * parent.unit_price, decimals);
			}
		}
		// calculate group subtotal
		grp.subtotal = 0.0;
		for(var idx = 0; idx < tally.length; idx++) {
			grp.subtotal += tally[idx].amount;
		}
		// apply discounts to line items and sum total discount
		var disc_total = 0.0;
		for(var idx = 0; idx < grp.discounts_order.length; idx++) {
			var adj = grp.adjusts[grp.discounts_order[idx]];
			if(adj.type != 'StdFixedDiscount') {
				adj.amount = 0.0;
				for(var jdx = 0; jdx < tally.length; jdx++) {
					var disc = trunc(tally[jdx].unadj_amount * adj.rate / 100, decimals);
					tally[jdx].amount -= disc;
					adj.amount += disc;
				}
			} else {
				var disc = adj.amount;
				if (tally.length > 0) tally[0].amount -= disc;//because we need subtract fixed discount one time				
			}	
			if (adj.tax_class_id && adj.tax_class_id != '-99') {
				var r = SysData.get_tax_rates(adj.tax_class_id);
				for(var k = 0; k < r.length; k++) {
					var rate = r[k];
					discount_taxes[rate.id] = 
						get_default(discount_taxes[rate.id], 0.0) - adj.amount * rate.rate / 100.0;
				}
			}
			disc_total += adj.amount;
		}
		grp.total = grp.subtotal - disc_total;
		// add shipping
		if(grp.shipping_adj_id && isset(grp.adjusts[grp.shipping_adj_id])) {
			var shipping = grp.adjusts[grp.shipping_adj_id];
			var shipping_taxed = (shipping.type == 'TaxedShipping');
			grp.total += shipping.amount;
			if(shipping_taxed) {
				tally.push({amount: shipping.amount, unadj_amount: shipping.amount, taxcode: shipping.tax_class_id});
			}
		}
		// find applicable taxes
		for(var code_id in all_taxcodes) {
			var r = SysData.get_tax_rates(code_id);
			for(var idx = 0; idx < r.length; idx++) {
				var rr = deep_clone(r[idx]);
				r.from_code = code_id;
				rates.push(rr);
			}
		}
		// update taxes
		for(var idx = 0; idx < grp.adjusts_order.length; idx++) {
			var adj = grp.adjusts[grp.adjusts_order[idx]];
			//if (adj.line_id) continue;
			if(adj.type == 'StandardTax' || adj.type == 'CompoundedTax') {
				if (!adj.editable) {
					var taxuid = adj.type + '~' + adj.id + '~' + adj.rate;
					old_taxes[taxuid] = adj;
					delete grp.adjusts[adj.id];
				} else {
					var taxuid = type +'~'+ adj.id +'~'+ adj.rate + '~edit' + idx;
					old_taxes[taxuid] = adj;
					delete grp.adjusts[adj.id];
					if (SysData.taxrates[adj.related_id]) {
						var r = deep_clone(SysData.taxrates[adj.related_id]);
						var type = r.compounding != 'compounded' ? 'StandardTax' : 'CompoundedTax';
						r.editable = 1;
						r.uid = taxuid;
						rates.push(r);
					}
				}
			}
			else
				adj_ord.push(adj.id);
		}
		// calculate totals
		for(var idx = 0; idx < rates.length; idx++) {
			var rate = rates[idx];
			var type = rate.compounding != 'compounded' ? 'StandardTax' : 'CompoundedTax';
			var taxuid = rate.uid || type +'~'+ rate.id +'~'+ rate.rate;
			var adj = null;
			if(! isset(taxes[taxuid])) {
				if(isset(old_taxes[taxuid])) {
					adj = old_taxes[taxuid];
					adj.amount = 0.0;
				}
				else {
					adj = {
						type: type, 
						name: rate.name,
						related_type: 'TaxRates',
						related_id: rate.id,
						rate: rate.rate,
						amount: get_default(discount_taxes[rate.id], 0.0),
						editable: 0
					};
					adj.id = 'newadj~' + (this.unique_index ++);
				}
				taxes[taxuid] = adj.id;
				taxes_ord.push(adj.id);
				adj_ord.push(adj.id);
				grp.adjusts[adj.id] = adj;
			}
			else
				adj = grp.adjusts[taxes[taxuid]];
			for(var jdx = 0; jdx < tally.length; jdx++) {
				if(tally[jdx].taxcode == rate.code_id || tally[jdx].taxcode == 'all') {
					var base, prevtax = get_default(tally[jdx].tax, 0.0);
					if (discount_before_taxes) {
						base = tally[jdx].unadj_amount;
					} else {
						base = tally[jdx].amount;
					}
					if(adj.type == 'CompoundedTax')
						base += prevtax;
					var amt = base * rate.rate / 100;
					tally[jdx].tax = prevtax + amt;
					adj.amount += amt;
				}
				var code = SysData.taxcodes[tally[jdx].taxcode];
				if (code && code.taxation_scheme == 'tax' && rate.editable) {
					if (!rate.line_id || tally[jdx].id == rate.line_id) {
						var base, prevtax = get_default(tally[jdx].tax, 0.0);
						if (discount_before_taxes) {
							base = tally[jdx].unadj_amount;
						} else {
							base = tally[jdx].amount;
						}
						if(adj.type == 'CompoundedTax')
							base += prevtax;
						var amt = base * rate.rate / 100;
						tally[jdx].tax = prevtax + amt;
						adj.amount += amt;
					}
				}
			}
		}
		for(var taxuid in taxes) {
			var adj = grp.adjusts[taxes[taxuid]];
			adj.amount = trunc(adj.amount, decimals);
			grp.total += adj.amount;
		}
		grp.total = trunc(grp.total, decimals);
		grp.adjusts_order = adj_ord;
		grp.taxes_order = taxes_ord;
	}
	if(redraw) {
		this.redraw_group_footer(group_id);
		this.redraw_totals();
	}
}

this.redraw_totals = function() {
	var cost = 0.0;
	var subt = 0.0;
	var tax = 0.0;
	var disc = 0.0;
	var ship = 0.0;
	for(var idx = 0; idx < this.groups_order.length; idx++) {
		var grp = this.groups[this.groups_order[idx]];
		if(! grp) continue;
		cost += grp.cost;
		subt += grp.subtotal;
		for(var jdx = 0; jdx < grp.taxes_order.length; jdx++) {
			var tot = grp.adjusts[grp.taxes_order[jdx]];
			tax += tot.amount;
		}
		for(var jdx = 0; jdx < grp.discounts_order.length; jdx++) {
			var tot = grp.adjusts[grp.discounts_order[jdx]];
			disc += tot.amount;
		}
		if(grp.shipping_adj_id && grp.adjusts[grp.shipping_adj_id])
			ship += grp.adjusts[grp.shipping_adj_id].amount;
	}
	this.totals = {
		subtotal: subt,
		taxes: tax, discounts: disc, shipping: ship,
		pretax: subt - disc + ship,
		total: subt - disc + ship + tax		
	};

	var disctotal = subt - disc;
	var profit = disctotal - cost;
	var gpperc = (disctotal != 0 ? profit / disctotal * 100 : 0.0).toFixed(1);
	var gprofit_div = $('DetailFormgross_profit-input');
	if(gprofit_div) {
		gprofit_div.value = formatCurrency(profit);
		gprofit_div.className = (profit < 0 ? 'error' : '');
	}
	var gprofit_div = $('DetailFormgross_profit_pc-input');
	if(gprofit_div) {
		gprofit_div.value = stdFormatNumber(gpperc, 2, 2);
		gprofit_div.className = (profit < 0 ? 'error' : '');
	}
	var gt = $('tally_grand_totals');
	if(gt) gt.style.display = (this.groups_order.length > 1) ? '' : 'none';
	for(var idx in this.totals) {
		var fld = $('grand_' + idx);
		var val = this.totals[idx];
		if(idx == 'discounts') val = -val;
		//if(idx == 'total') val = Math.round(val*100)/100; 
		if(fld) fld.value = formatCurrency(val);
	}

	this.hideGrossProfit();
}

this.hideGrossProfit = function() {
	if (!this.productCosts) {
		var gprofit_value = $('DetailFormgross_profit');
		if(gprofit_value) {
			gprofit_value.style.display = 'none';
		}	
		var gprofit_value = $('DetailFormgross_profit_pc');
		if(gprofit_value) {
			gprofit_value.style.display = 'none';
		}	
	}	
}

this.recalculate = function(no_redraw) {
	for(var idx = 0; idx < this.groups_order.length; idx++) {
		var gid = this.groups_order[idx];
		this.recalc_group(gid);
		if(! no_redraw)
			this.redraw_group_footer(gid);
	}
	if(! no_redraw)
		this.redraw_totals();
}


// -- Field updates

this.update_all_rows = function(func) {
	for(var idx = 0; idx < this.groups_order.length; idx++) {
		this.update_group_rows(this.groups_order[idx], func);
	}
}

this.update_group_rows = function(group_id, func) {
	var grp = this.groups[group_id];
	if(! grp) return false;
	if (func) fnc = func;
	// update non-assembly lines
	for(var idx = 0; idx < grp.lines_order.length; idx++) {
		var line_id = grp.lines_order[idx];
		var line = grp.lines[line_id];
		if(!func)
			fnc = this.get_pricing_method_fn(group_id, line);
		if(!fnc)
			continue;
		try {
			fnc(line, group_id);
		} catch(exception) {
			if(!warned){
				alert('error: ' + exception.trace);
				warned = true;
			}
			return false;
		}
	}
	this.recalc_group(group_id, true);
}

this.update_ext_price = function(line) {
	var value = line.ext_quantity * line.unit_price;
	var minDec = TallyEditor.currency_decimal_places();
    line.ext_price = trunc(value, minDec);
}

this.get_pricing_method_fn = function(group_id, ln) {
	var calc = this.get_pricing_formula(group_id, ln);
	if(calc) {
		return function(line) {
			if(! line.sum_of_components) {
				var attr_adjust = 0.0;
				if(! calc.no_attrs && line.adjusts) {
					for(var i in line.adjusts) {
						var adj = line.adjusts[i];
						if(i != line.pricing_adjust_id && adj.type != 'StdFixedDiscount')
							attr_adjust += parseFloat(adj.amount);
					}
				}
				var upd = calc(line);
				if(isset(upd)) {
					upd += attr_adjust;
					var minDec = TallyEditor.currency_decimal_places();
					var maxDec = isset(calc.base) ? Math.max(countDecimals(line[calc.base]), minDec) : undefined;
					upd = restrictDecimals(upd, minDec, maxDec);
					line.unit_price = upd;
					TallyEditor.update_ext_price(line);
				}
			}
		}
	}
}

this.update_pricing_method = function(group_id) {
	this.update_group_rows(group_id);
}

this.update_exchange_rate = function(old_rate, new_rate) {
	var price_cols = this.price_columns;
	var upd_pricing = [];
	var decimals = this.currency_decimal_places();
	this.update_all_rows(
		function(line, group_id) {
			for(var idx = 0; idx < price_cols.length; idx++) {
				var col = price_cols[idx];
				if(isset(line[col])) {
					line[col] = trunc(line[col] / old_rate * new_rate, decimals);
				}
			}
			if (line.attributes_meta) for (var i in line.attributes_meta) {
				var attr = line.attributes_meta[i];
				for (var j in attr) {
					line.attributes_meta[i][j].price = trunc(line.attributes_meta[i][j].price / old_rate * new_rate, decimals);
				}
			}
			for (var i in line.adjusts) {
				var adj = line.adjusts[i];
				if (adj.type != 'ProductAttributes' && i != line.pricing_adjust_id) {
					continue;
				}
				var amount = trunc(adj.amount / old_rate * new_rate, decimals);
				editor.update_line_adjustment(group_id, line.id, i, {'amount': amount}, true);
			}
		});
	for(var idx = 0; idx < this.groups_order.length; idx++) {
		var gid = this.groups_order[idx];
		var shipping = this.groups[gid].shipping / old_rate * new_rate;
		this.update_group_field(gid, 'shipping', shipping, true);
	}
	//for(var idx = 0; idx < upd_pricing.length; idx++)
	//	editor.update_pricing_method(upd_pricing[idx]);
}

this.get_line_pricing_method = function(group_method, line, noconvert) {
	if(line.is_comment || line.sum_of_components)
		return '';
	if(! line.pricing_adjust_id) {
		var adj = this.add_line_adjustment(line, {type: 'Inherit'});
		line.pricing_adjust_id = adj.id;
	}
	var adj = line.adjusts[line.pricing_adjust_id];
	// If we don't have a valid object, just return inherit
	if (!adj) {
		adj = {
			type: 'Inherit', 
			name: '',
			related_type: '',
			related_id: '',
			rate: 0,
			amount: 0.0
		};
		// Store the adjustment
		line.adjusts[line.pricing_adjust_id] = adj;
	}

	switch (adj.type) {
		case 'PercentDiscount': return 'discount';
		case 'StdPercentDiscount': return 'discount';
		case 'StdFixedDiscount': return 'fixed_discount';
		case 'Markup': return 'markup';
		case 'Margin': return 'margin';
		case 'SameAsList': return 'list';
		case 'Inherit': {
			if (noconvert) return 'inherit';
			return group_method;
		}

		case 'None':
		default:
			return ''; 
	}
}

this.get_pricing_formula = function(group_id, ln) {
	var grp = this.groups[group_id];
	if(! grp) return null;
	if(ln.is_comment)
		return null;
	var method = this.get_line_pricing_method(grp.pricing_method, ln, true);
	if (method != 'inherit' && ln.pricing_adjust_id && ln.adjusts[ln.pricing_adjust_id]) {
		var adj = ln.adjusts[ln.pricing_adjust_id];
		if (adj.type == 'StdFixedDiscount') {
			var perc = adj.amount;
		} else {
			var perc = adj.rate;
		}
	} else {
		method = grp.pricing_method;
		var perc = grp.pricing_percentage;
	}

	if(isNaN(perc))
		perc = 0.0;
	perc = parseFloat(perc);

	var fn = null;
	switch(method) {
		case 'margin':
			perc = 100 - perc;
			fn = function(line) { return line.cost_price * 100 / perc };
			fn.base = 'cost_price';
			break;
		case 'markup':
			perc += 100;
			fn = function(line) { return line.cost_price * perc / 100 };
			fn.base = 'cost_price';
			break;
		case 'discount':
			perc = 100 - perc;
			fn = function(line) { return line.list_price * perc / 100  };
			fn.base = 'list_price';
			break;
		case 'fixed_discount':
			fn = function(line) { return line.list_price - perc  };
			fn.base = 'list_price';
			break;
		case 'list':
			fn = function(line) { return line.list_price };
			fn.base = 'list_price';
			break;
		case '':
		default:
			fn = function(line) { return isset(line.custom_unit_price) ? line.custom_unit_price : line.std_unit_price  };
			fn.no_attrs = true;
	}
	if(fn)
		fn.method = method;
	return fn;
}


// -- Rendering

this.render_adjustment_row = function(row, group_id, adj_id) {
	var adj = this.groups[group_id].adjusts[adj_id];
	if(! adj) return;
	var lbl_prefix = '';
	var editable = this.editable;
	var options = {};
	var opt_order = [];

	if (adj.line_id)
		return;	

	if(adj.related_type == 'Discounts') {
		opt_order = SysData.discounts_order;
		options = SysData.discounts;
		lbl_prefix = 'LBL_DISCOUNT';
	}
	else if(adj.related_type == 'TaxRates') {
		lbl_prefix = 'LBL_TAX';
		editable &= !!adj.editable;
		opt_order = SysData.taxrates_order;
		options = SysData.taxrates;
	}
	else
		return;

	var left_td = createTableCell({}, true);
	if(lbl_prefix) {
		left_td.appendChild(createTextLabel(lbl_prefix));
		left_td.appendChild(sepLabel());
	}

	if(editable) {
		var labels = {};
		for(var i = 0; i < opt_order.length; i++){
			var id = opt_order[i];
			var rate;
			if(options[id].type == 'StdFixedDiscount')
				rate = formatCurrency(options[id].amount);
			else
				rate = stdFormatNumber(options[id].rate) + '%';
			labels[id] = options[id].name;
			if(id != '' && id != '-99')
				labels[id] += ' (' + rate + (options[id].compounding ? ' +' : '') + ')';
		}
		var onchange = function() {
			editor.update_adjustment(group_id, adj_id, options[this.getValue()]);
		}
		var optSel = createSelectInput(opt_order, labels, adj.related_id||'-99',
			{onchange: onchange, tabIndex: this.tabIndex, options_width: 300});
		left_td.appendChild(optSel);
		this.add_element([group_id, 'adjust_select', adj_id], optSel);

		if (this.get_discount_before_taxes() && adj.related_type == 'Discounts') {

			left_td.appendChild(nbsp());
			left_td.appendChild(createTextLabel('LBL_TAX_CODE'));
			left_td.appendChild(nbsp());

			var onchange = function() {
				editor.update_discount_tax(group_id, adj_id, this.getValue());
			}

			var codes = {};
			for(var c in SysData.taxcodes)
				codes[c] = SysData.taxcodes[c].code;
			var attrs = {onchange: onchange};
			var taxCls = createSelectInput(SysData.taxcodes_order, codes, adj.tax_class_id||'-99', attrs);
			left_td.appendChild(taxCls);
			this.add_element([group_id, 'adjust_tax_select', adj_id], taxCls);
		}

	}
	else {
		var compounded = (adj.type == 'CompoundedTax');
		var rate = '';
		var tax = '';
		if(adj.type != 'StdFixedDiscount') {
			rate = ' (' + stdFormatNumber(adj.rate) + '%' + (compounded ? ' +' : '') + ')';
			if (adj.tax_class_id && adj.tax_class_id != '-99') {
				tax = ' (' + SysData.taxcodes[adj.tax_class_id].code + ')';
			}
		}
		var label = adj.name + rate + tax;
		left_td.appendChild(createTextLabel(label, true));
	}

	var row_count = 1;

	if(editable && row_count > 0) {
		left_td.appendChild(nbsp());
		var onclick = function() {
			editor.remove_adjustment(group_id, adj_id);
			return false;
		}
		var remrow = createIconLink('remove', '', onclick);
		left_td.appendChild(remrow);
	}
	row.appendChild(left_td);
	var right_td = createTableCell({width: 70});
	var amtval = adj.amount;
	if(adj.related_type == 'Discounts') amtval = -amtval;
	var amount = createTextInput(formatCurrency(amtval),
		{size: 13, format: 'currency', style: {textAlign: 'right'}, tabIndex: this.tabIndex, readOnly: true}, this.form);
	right_td.appendChild(amount);
	this.add_element([group_id, 'adjust_amount', adj_id], amount);

	row.appendChild(right_td);
	this.add_element([group_id, 'adjust_row', adj_id], row);

	return adj;
}


this.add_adjustment = function(group_id, spec, no_redraw) {
	var group = this.groups[group_id];
	if(! group)  return;
	if(! isset(spec))
		return;
	if(spec.related_type == 'Discounts') {
		var order = group.discounts_order;
		var last_row = this.get_element([group_id, 'discount_add_row']);
	}
	else if(spec.related_type == 'TaxRates') {
		var order = group.taxes_order;
		var last_row = this.get_element([group_id, 'tax_add_row']);
	}
	else if(spec.related_type == 'ShippingProviders') {
		var order = null;

	}
	else
		return;
	var adj_id = 'newadj~' + (this.unique_index ++);
	if(! YLang.isObject(spec))
		spec = {};
	spec.id = adj_id;
	if(! isset(spec.rate))
		spec.rate = 0.0;
	if(! isset(spec.amount))
		spec.amount = 0.0;

	// TODO check if this required
	/*
	if(spec.related_type == 'Discounts' && this.get_discount_before_taxes()) {
		this.iterate_group_lines(group_id, 
			function(group_id, line_id, line) {
				if (line.is_comment) return false;
				spec.tax_class_id = get_default(line.tax_class_id, -99);
				return true;
			}
		);
	}
	*/
	group.adjusts[adj_id] = spec;


	if(order)
		order.push(adj_id);
	group.adjusts_order.push(adj_id);
	if(! no_redraw)
		this.redraw_group_footer(group_id);
	return adj_id;
}

this.remove_adjustment = function(group_id, adj_id) {
	var group = this.groups[group_id];
	if(! group) return;
	var adj = group.adjusts[adj_id];
	if(! adj) return;
	if(adj.related_type == 'Discounts')
		group.discounts_order.remove(adj_id);
	else if(adj.related_type == 'TaxRates')
		group.taxes_order.remove(adj_id);
	group.adjusts_order.remove(adj_id);
	delete group.adjusts[adj_id];
	setTimeout('TallyEditor.recalc_group("' + group_id + '", true)' , 100);
}

this.remove_line_adjustment = function(group_id, line_id, adj_id) {
	var group = this.groups[group_id];
	if(! group) return;
	var line = group.lines[line_id];
	if(! line) return;
	var adj = line.adjusts[adj_id];
	if(! adj) return;
	delete line.adjusts[adj_id];
	this.recalc_group(group_id, true);
	this.redraw_group(group_id);
}

this.get_discount_adjustment = function(disc_id) {
	var disc = SysData.discounts[disc_id];
	var adj = {type: 'StdPercentDiscount', related_type: 'Discounts', related_id: disc_id, amount: 0.0};
	if(disc) {
		adj.rate = parseFloat(disc.rate);
		adj.amount = parseFloat(disc.amount);
		adj.type = disc.type;
		adj.name = disc.name;
	}
	return adj;
}

this.update_adjustment = function(group_id, adj_id, spec) {
	var grp = this.groups[group_id];
	if(! grp) return;
	var adj = grp.adjusts[adj_id];
	if(! adj) return;
	for(var idx in spec) {
		if(idx == 'id')
			adj.related_id = spec.id;
		else
			adj[idx] = spec[idx];
	}
	if (adj.related_type == 'TaxRates') {
		adj.type = adj.compounding ? 'CompoundedTax' : 'StandardTax';
	}
	adj.rate = parseFloat(adj.rate);
	adj.amount = parseFloat(adj.amount);
	this.recalc_group(group_id, true);
}

this.update_discount_tax = function(group_id, adj_id, value) {
	var grp = this.groups[group_id];
	if(! grp) return;
	var adj = grp.adjusts[adj_id];
	if(! adj) return;
	adj.tax_class_id = value;
	this.recalc_group(group_id, true);
}

this.get_discount_before_taxes = function() {
	var discount_before_taxes = SUGAR.ui.getFormInput(this.form, 'discount_before_taxes');
	if (discount_before_taxes && discount_before_taxes.getValue())
		return true;
	return false;
}

this.update_price_adjustment = function(group_id, line_id, spec, no_group_update) {
	var grp = this.groups[group_id];
	if(! grp) return;
	var line = grp.lines[line_id];
	if (!line) return;
	this.update_line_adjustment(group_id, line_id, line.pricing_adjust_id, spec, no_group_update);
	var pmethod = this.get_line_pricing_method(grp.pricing_method, line);
	if(pmethod == '' && isset(line.custom_unit_price))
		line.unit_price = line.custom_unit_price;
	if(! no_group_update)
		this.update_pricing_method(group_id);
	var input = this.get_element([group_id, 'lines', line_id, 'discount_select']);
	if (input) {
		input.value = line.adjusts[line.pricing_adjust_id].name;
		if ('' + input.value != '') {
			if (spec.type == 'StdPercentDiscount') {
				input.value += ' (' + stdFormatNumber(spec.rate) + '%)';
			} else {
				input.value += ' (' + formatCurrency(spec.amount) + ')';
			}
		}
	}
}

this.update_line_adjustment = function(group_id, line_id, adj_id, spec, no_group_update) {
	var grp = this.groups[group_id];
	if(! grp) return;
	var line = grp.lines[line_id];
	if (!line) return;
	var adj = line.adjusts[adj_id];
	if(! adj) return;
	var adj_amount = null;
	for(var idx in spec) {
		if(idx == 'id')
			adj.related_id = spec.id;
		else {
			if(idx == 'amount') {
				spec.amount = parseFloat(spec.amount);
				adj_amount = spec.amount - get_default(adj.amount, 0);
			}
			adj[idx] = spec[idx];
		}
	}
	adj.rate = parseFloat(adj.rate);
	if(isset(adj_amount) && ! this.isPO)
		this.update_line_item(group_id, line_id, 'adjust_price', adj_amount);
	else
		this.update_line_item(group_id, line_id);
	if(! no_group_update)
		this.recalc_group(group_id, true);
}


this.move_group = function (group_id, offset, check_only) {
	var a = -1;
	for (var i = 0; i < this.groups_order.length; i++) {
		if (this.groups[this.groups_order[i]].id == group_id) {
			a = i;
			break;
		}
	}
	if (a < 0)
		return false;
	b = a+ offset;
	if (b >= this.groups_order.length || b < 0)
		return false;
	if (check_only)
		return true;
	var tmp = this.groups_order[a];
	this.groups_order[a] = this.groups_order[b];
	this.groups_order[b] = tmp;

	var top_level = $('tally_groups');
	for (var i = 0; i < this.groups_order.length; i++) {
		var gid = this.groups_order[i];
		var tbl = this.get_element([gid, 'container'])
		if(tbl)
			top_level.removeChild(tbl);
		this.remove_elements(gid);
	}
	this.redraw_all();
};

this.render_group_header_table = function(group_id) {
	var group_info = this.groups[group_id];
	var tbl = createTable();
	var row = tbl.insertRow(tbl.rows.length);

	if (this.editable) {
		if (editor.move_group(group_id, +1, true)) {
			var cell = createTableCell({width: '1%', style: {textAlign: 'center'}});
			var onclick = function() { 
				editor.move_group(group_id, +1);
				return false;
			}
			var downlink = createIconLink('down', '', onclick, {});
			cell.appendChild(downlink);
			row.appendChild(cell);
		}
		
		if (editor.move_group(group_id, -1, true)) {
			var cell = createTableCell({width: '1%', style: {textAlign: 'center'}});
			var onclick = function() { 
				editor.move_group(group_id, -1);
				return false;
			}
			var uplink = createIconLink('up', '', onclick, {});
			cell.appendChild(uplink);
			row.appendChild(cell);
		}
	}

	var cell = createTableCell({width: '33%', style: {fontWeight: 'bold'}}, true);
	cell.appendChild(createTextLabel('LBL_GROUP_NAME'));
	cell.appendChild(sepLabel());

	var attrs = {size: 20, tabIndex: this.tabIndex};
	if(this.editable)
		attrs.onchange = function() {
			editor.update_group_field(group_id, 'name', this.getValue());
		};
	else
		attrs.readOnly = true;
	var nameInp = createTextInput(group_info.name, attrs, this.form);
	cell.appendChild(nameInp);
	this.add_element([group_id, 'name'], nameInp);
	row.appendChild(cell);

	var cell = createTableCell({width: '30%'}, true);
	if(this.module == 'Quotes') {
		var lbl = createElement2('span', {style: {fontWeight: 'bold'}});
		lbl.appendChild(createTextLabel('LBL_GROUP_STAGE'));
		lbl.appendChild(sepLabel());
		cell.appendChild(lbl);
		var attrs = {tabIndex: this.tabIndex, style: {width: '11em'}};
		if(this.editable)
			attrs.onchange = function() {
				editor.update_group_field(group_id, 'status', this.getValue());
			};
		else
			attrs.disabled = true;
		var strings = app_list_strings_ord('stage_dom');
		var statusSel = createSelectInput(strings.order, strings.values, group_info.status, attrs);
		cell.appendChild(statusSel);
		this.add_element([group_id, 'status'], statusSel);
	}
	else
		cell.appendChild(nbsp());
	row.appendChild(cell);

	if((!this.isPO || this.module == 'Bills' || this.module == 'CreditNotes')  && this.module != 'Shipping' && this.module != 'Receiving') {
		var cell = createTableCell({width: '25%'}, true);
		var lbl = createElement2('span', {style: {fontWeight: 'bold'}});
		lbl.appendChild(createTextLabel('LBL_GROUP_TYPE'));
		lbl.appendChild(sepLabel());
		cell.appendChild(lbl);

		var attrs = {tabIndex: this.tabIndex, style: {width: '11em'}};
		if(this.editable)
			attrs.onchange = function() {
				if (editor.groups[group_id].lines_order.length > 0) {
					if (!confirm(mod_string('LBL_CONFIRM_GROUP_TYPE'))) {
						this.value = editor.groups[group_id].group_type;
						return false;
					}
				}
                var val = this.value;
                if (typeof(val) == 'undefined')
                    val = this.getValue();
				editor.update_group_field(group_id, 'group_type', val);
				editor.groups[group_id].lines = {};
				editor.groups[group_id].lines_order = [];
				editor.recalculate(true);
				editor.redraw_all();
			};
		else
			attrs.disabled = true;
		var keep = null;
		if (this.module == 'Bills') {
			keep = ['products', 'expenses'];
		}
		var strings = app_list_strings_ord('group_type_dom', null, keep);
		var group_type = createSelectInput(strings.order, strings.values, group_info.group_type, attrs);
		cell.appendChild(group_type);
		this.add_element([group_id, 'group_type'], group_type);
		row.appendChild(cell);

		if (this.module != 'Bills') {
			var cell = createTableCell({width: '37%'}, true);
			var lbl = createElement2('span', {style: {fontWeight: 'bold'}});
			lbl.appendChild(createTextLabel('LBL_PRICING_METHOD'));
			lbl.appendChild(sepLabel());
			cell.appendChild(lbl);
			var attrs = {tabIndex: this.tabIndex, style: {width: '11em'}};
			if(this.editable)
				attrs.onchange = function() {
					var val = this.value;
					if (typeof(val) == 'undefined')
						val = this.getValue();
					editor.update_group_field(group_id, 'pricing_method', val);
				};
			else
				attrs.disabled = true;

			var strings = app_list_strings_ord('pricing_method_dom');
			var disabled = {};
			if (!this.productCosts) {
				disabled.margin = true;
				disabled.markup = true;			
			}	
			if (this.fixedPrices) {
				disabled.margin = true;
				disabled.markup = true;
				disabled.list = true;
			}
			if (!this.manualDiscounts) {
				disabled.discount = true;
			}
			var pricMeth = createSelectInput(strings.order, strings.values, group_info.pricing_method, attrs, disabled);
			cell.appendChild(pricMeth);
			this.add_element([group_id, 'pricing_method'], pricMeth);
			var span = document.createElement('span');
			if(group_info.pricing_method == '' || group_info.pricing_method == 'list')
				span.style.display = 'none';
			var attrs = {size: 5, format: 'float', decimals: 2, tabIndex: this.tabIndex};
			if(this.editable)
				attrs.onchange = function() {
					editor.update_group_field(group_id, 'pricing_percentage', this.numVal);
				};
			else
				attrs.readOnly = true;
			var pricPerc = createTextInput(stdFormatNumber(group_info.pricing_percentage || '0.00'), attrs);
			span.appendChild(pricPerc);
			this.add_element([group_id, 'pricing_percentage'], pricPerc);
			span.appendChild(nbsp());
			span.appendChild(createTextLabel('%', true));
			cell.appendChild(nbsp());
			cell.appendChild(span);
			this.add_element([group_id, 'pricing_perc_div'], span);
			row.appendChild(cell);
		}
	}

	return tbl;
}

this.render_group_footer_table = function(group_id) {
	var group_info = this.groups[group_id];
	var tbl = createTable();
	var row = tbl.insertRow(tbl.rows.length);
	var cell = createTableCell({style: {width: '70%'}});
	if(this.editable)
		this.render_add_line_links(cell, group_id);
	else
		cell.appendChild(nbsp());
	row.appendChild(cell);


	if (this.module != 'Shipping' && this.module != 'Receiving') {
		var cell = createTableCell({style: {width: '20em', fontWeight: 'bold'}}, true);
		cell.appendChild(createTextLabel('LBL_SUBTOTAL'));
		row.appendChild(cell);
		var cell = createTableCell({style: {width: '70px', textAlign: 'right'}});
		row.appendChild(cell);
		var subt = createTextInput(formatCurrency(group_info.subtotal),
			{size: 13, format: 'currency', style: {textAlign: 'right'}, tabIndex: this.tabIndex, readOnly: true});
		cell.appendChild(subt);
		this.add_element([group_id, 'footer', 'subtotal_amount'], subt);
		row.appendChild(cell);

		// render discounts
		var pretax = group_info.subtotal + (group_info.shipping_tax_class_id != '-99' ? group_info.shipping : 0.0);
		var discs = group_info.discounts_order;
		for(var idx = 0; idx < discs.length; idx++) {
			var adj_id = discs[idx];
			var row = tbl.insertRow(tbl.rows.length);
			var cell = createTableCell();
			cell.appendChild(nbsp());
			row.appendChild(cell);
			var adj_obj = this.render_adjustment_row(row, group_id, adj_id);
			if(adj_obj)
				pretax -= adj_obj.amount;
		}
		if(this.editable) {
			var row = tbl.insertRow(tbl.rows.length);
			var cell = createTableCell();
			cell.appendChild(nbsp());
			row.appendChild(cell);
			var cell = createTableCell({colSpan: 2, style: {padding: '2px 0 2px 2em'}});
			var onclick = function() {
				editor.add_adjustment(group_id, {related_type: 'Discounts'});
				return false;
			}
			var addrow = createIconLink('insert', 'LBL_ADD_DISCOUNT', onclick, {});
			cell.appendChild(addrow);
			row.appendChild(cell);
			this.add_element([group_id, 'discount_add_row'], row);
		}

		// render shipping
		var shiprow = tbl.insertRow(tbl.rows.length);
		var cell = createTableCell();
		cell.appendChild(nbsp());
		shiprow.appendChild(cell);
		var cell = createTableCell({}, true);
		cell.appendChild(createTextLabel('LBL_SHIPPING'));
		cell.appendChild(sepLabel());
		var taxedDiv = document.createElement('span');
			taxedDiv.style.paddingLeft = '2em';
		var shipping_taxed = get_default(group_info.shipping_tax_class_id, '-99') != '-99';

		var codes = {};
		var order = [];
		for(var c in SysData.taxcodes) {
			codes[c] = SysData.taxcodes[c].code;
		}
		codes['all'] = mod_string('LBL_ALL_FROM_GROUP');
		var idx = 0;
		for(var i = 0; i < SysData.taxcodes_order.length; i++) {
			order[idx++] = SysData.taxcodes_order[i];
			if (i == 0) {
				order[idx++] = 'all';
			}
		}
		var attrs = {style: {width: '7em'}};
		if(this.editable)
			attrs.onchange = function() {
                var val = this.value;
                if (typeof(val) == 'undefined')
                    val = this.getValue();
				editor.update_group_field(group_id, 'shipping_tax_class_id', val, true);
			};
		else
			attrs.disabled = true;
		var taxCls = createSelectInput(order, codes, group_info.shipping_tax_class_id||'-99', attrs);
		taxedDiv.appendChild(taxCls);



		cell.appendChild(taxedDiv);
		shiprow.appendChild(cell);
		var cell = createTableCell({width: 70});
		var attrs = {size: 13, format: 'currency', style: {textAlign: 'right'}, tabIndex: this.tabIndex};
		if(this.editable)
			attrs.onchange = function() {
				//var shipVal = validateNumberInput(this, {decimalPlaces:editor.currency_decimal_places()});
				editor.update_group_field(group_id, 'shipping', this.numVal, true);
			};
		else
			attrs.readOnly = true;
		var amount = createTextInput(formatCurrency(group_info.shipping), attrs);
		this.add_element([group_id, 'footer', 'shipping_amount'], amount);
		cell.appendChild(amount);
		shiprow.appendChild(cell);
		this.add_element([group_id, 'footer', 'shipping_row'], shiprow);

		// pretax total
		if(this.show_pretax_totals && group_info.taxes_order.length) {
			var row = tbl.insertRow(tbl.rows.length);
			var cell = createTableCell();
			cell.appendChild(nbsp());
			row.appendChild(cell);
			var cell = createTableCell({style: {fontWeight: 'bold'}}, true);
			cell.appendChild(createTextLabel('LBL_PRETAX_TOTAL'));
			row.appendChild(cell);
			var cell = createTableCell({width: 70});
			var attrs = {size: 13, format: 'currency', style: {textAlign: 'right'}, tabIndex: this.tabIndex, readOnly: true};
			var amount = createTextInput(formatCurrency(pretax), attrs);
			this.add_element([group_id, 'footer', 'pretax_amount'], amount);
			cell.appendChild(amount);
			row.appendChild(cell);
		}

		// render taxes
		var taxes = group_info.taxes_order;
		for(var idx = 0; idx < taxes.length; idx++) {
			var adj_id = taxes[idx];
			var row = tbl.insertRow(tbl.rows.length);
			var cell = createTableCell();
			cell.appendChild(nbsp());
			row.appendChild(cell);
			this.render_adjustment_row(row, group_id, adj_id);
		}
		if(this.editable) {
			var row = tbl.insertRow(tbl.rows.length);
			var cell = createTableCell();
			cell.appendChild(nbsp());
			row.appendChild(cell);
			var cell = createTableCell({colSpan: 2, style: {padding: '2px 0 2px 2em'}});
			var onclick = function() {
				editor.add_adjustment(group_id, {related_type: 'TaxRates', editable: 1, type: 'StandardTax'});
				return false;
			}
			var addrow = createIconLink('insert', 'LBL_ADD_TAX', onclick, {});
			cell.appendChild(addrow);
			row.appendChild(cell);
			this.add_element([group_id, 'tax_add_row'], row);
		}

		if(! shipping_taxed) {
			tbl.tBodies[0].removeChild(shiprow);
			tbl.tBodies[0].appendChild(shiprow);
		}

		// 'group_total_row'
		var row = tbl.insertRow(tbl.rows.length);
		var cell = createTableCell();
		if(this.editable) {
			var onclick = function() {
				if(confirm(mod_string('NTC_CONFIRM_DELETE_GROUP')))
					editor.remove_group(group_id);
				return false;
			}
			var rem_link = createIconLink('remove', 'LBL_DELETE_GROUP', onclick, {});
			cell.appendChild(rem_link);
		}
		cell.appendChild(nbsp());
		row.appendChild(cell);

		var cell = createTableCell({style: {fontWeight: 'bold'}}, true);
		cell.appendChild(createTextLabel('LBL_TOTAL'));
		row.appendChild(cell);

		var cell = createTableCell({width: 70});
		var total = createTextInput(formatCurrency(group_info.total),
			{size: 13, format: 'currency', style: {textAlign: 'right'}, tabIndex: this.tabIndex, readOnly: true});
		cell.appendChild(total);
		this.add_element([group_id, 'footer', 'total_amount'], total);
		row.appendChild(cell);
	}		
	return tbl;
}


this.render_add_line_links = function(cell, parent_group, parent_id) {
	// links will depend on group type and parent type
	var grp = this.groups[parent_group];
    var addTax = false;
	if(parent_id) {
		var parent = grp.lines[parent_id];
		if (grp.group_type == 'support') {
			if (parent.related_type == 'SupportedAssemblies') {
				var add_links = [
					{label: 'LBL_ADD_COMPONENT', related_type: 'Assets'},
					{label: 'LBL_ADD_COMMENT', related_type: 'Notes', blank: true}
				];
			} else {
				var add_links = [
					{label: 'LBL_ADD_COMPONENT', related_type: 'ProductCatalog'},
					{label: 'LBL_ADD_COMMENT', related_type: 'Notes', blank: true}
				];
			}
		} else {
			var add_links = [
				{label: 'LBL_ADD_COMPONENT', related_type: 'ProductCatalog', blank: true},
				{label: 'LBL_ADD_COMMENT', related_type: 'Notes', blank: true}
			];
		}
	}
	else {
		if (grp.group_type == 'support') {
			var add_links = [
				{label: 'LBL_ADD_PRODUCT', related_type: 'ProductCatalog'},
				{label: 'LBL_ADD_ASSEMBLY', related_type: 'Assemblies'},
				null,
				{label: 'LBL_ADD_SUPPORTED_PRODUCT', related_type: 'Assets'},
				{label: 'LBL_ADD_SUPPORTED_ASSEMBLY', related_type: 'SupportedAssemblies'},
				null,
				{label: 'LBL_ADD_COMMENT', related_type: 'Notes', blank: true}
			];
			addTax = true;
		} else if (grp.group_type == 'expenses') {
			var add_links = [
				{label: 'LBL_ADD_EXPENSE', related_type: 'BookingCategories'},
				{label: 'LBL_ADD_COMMENT', related_type: 'Notes', blank: true}
			];
			addTax = true;
		} else if (grp.group_type == 'service') {
			var add_links = [
				{label: 'LBL_ADD_BOOKING', related_type: 'BookingCategories'},
				{label: 'LBL_ADD_COMMENT', related_type: 'Notes', blank: true}
			];
			addTax = true;
		} else {
			var add_links = [
				{label: 'LBL_ADD_ROW', related_type: 'ProductCatalog', blank: true},
				{label: 'LBL_ADD_ASSEMBLY', related_type: 'Assemblies', blank: true},
				{label: 'LBL_ADD_COMMENT', related_type: 'Notes', blank: true},
				{label: 'LBL_ADD_MULT_PRODUCT', related_type: 'ProductCatalog'}
			];
			addTax = true;
		}
	}
	var addbr = false;
	for(var idx = 0; idx < add_links.length; idx++) {
		if(add_links[idx] === null) {
			addbr = true;
			continue;
		}
		if(addbr) {
			cell.appendChild(document.createElement('br'));
			addbr = false;
		}
		else if(idx) cell.appendChild(nbsp(2));
		var onclick_add_blank = function() {
			editor.add_line_item(parent_group, { related_type: this.reltype, parent_id: parent_id }, false, true);
			return false;
		}
		var support = (grp.group_type == 'support');
		var onclick_select = function() {
			var request_data = {
				call_back_function: 'return_line_item',
				form_name: this.form_name,
				passthru_data: {
					group_id: parent_group,
					parent_id: parent_id,
					module: this.reltype
				},
				field_to_name_array: editor.get_field_map(this.reltype, support)
			};
			var filter = '';
			if(editor.isPO) {
				var supp = editor.form.supplier_name;
				if(supp && supp.value)
					filter = '&supplier_name='+encodeURIComponent(supp.value);
			}
			var wh = $('warehouse_id');
			if (wh) {
				filter += '&warehouse_id='+encodeURIComponent(wh.value);
			}
			if(editor.module == 'Invoice' && this.reltype == 'BookingCategories') {
				filter = '&booking_class='+encodeURIComponent('billable-work');
			}
			if(editor.module == 'Bills' && this.reltype == 'BookingCategories') {
				filter = '&booking_class='+encodeURIComponent('expenses');
			}
			filter += '&display_currency_id=' + TallyEditor.get_currency_id();
			var options = {create: this.can_create, multiple: true};
			TallyEditor.open_select_popup(this.reltype, request_data, filter, options);
			return false;
		}
		var onclick = (add_links[idx].blank ? onclick_add_blank : onclick_select);
		var attrs = {
			reltype: add_links[idx].related_type, can_create: ! this.catalogOnly,
			currency_id: this.get_currency_id()
		};
		var iconlink = createIconLink('insert', add_links[idx].label, onclick, attrs);
		cell.appendChild(iconlink);
	}
}


this.render_line_items_header_row = function(grp, row, parent_item, child_count) {
	row.vAlign = 'bottom';
	row.height = '19';
	if(parent_item) {
		var bgStyle = treeNodeBg((! this.editable && ! child_count) ? 'end' : 'dots');
		var cell = createTableCell({width: 20, style: bgStyle});
		cell.appendChild(nbsp());
		row.appendChild(cell);
	}
	var cell = createTableCell({width: 40}, true);
	if (grp.group_type != 'expenses') {
		cell.appendChild(createTextLabel('LBL_QUANTITY'));
	} else {
		cell.appendChild(nbsp());
	}
	row.appendChild(cell);

	if (grp.group_type == 'expenses') {
		var cell = createTableCell({}, true);
		cell.appendChild(createTextLabel('LBL_EXPENSE_CATEGORY'));
		row.appendChild(cell);
	}

	var cell = createTableCell({}, true);
	if (grp.group_type == 'service') {
		cell.appendChild(createTextLabel('LBL_BOOKING_CATEGORY'));
	} else if (grp.group_type == 'expenses') {
		cell.appendChild(createTextLabel('LBL_EXPENSE_DESCRIPTION'));
	} else {
		cell.appendChild(createTextLabel(parent_item ? 'LBL_COMPONENT_PRODUCT' : 'LBL_PRODUCT_OR_ASSEMBLY'));
	}
	var spacer = document.createElement('img');
	spacer.src = 'include/images/blank.gif';
	spacer.height = 1; spacer.width = 50;
	cell.appendChild(spacer);
	row.appendChild(cell);

	if (grp.group_type != 'expenses') {
		var cell = createTableCell({width: ''}, true);
		if (grp.group_type == 'service') {
			cell.appendChild(nbsp());
		} else {
			cell.appendChild(createTextLabel('LBL_PARTNO'));
		}
		row.appendChild(cell);
	}

	if (this.module == 'Shipping' || this.module == 'Receiving') {
		var cell = createTableCell({width: ''}, true);
		if (grp.group_type == 'service') {
			cell.appendChild(nbsp());
		} else {
			cell.appendChild(createTextLabel('LBL_VENDOR_PARTNO'));
		}
		row.appendChild(cell);
	}	

	if (this.module != 'Shipping' && this.module != 'Receiving') {
		var cell = createTableCell({width: ''}, true);
		if (grp.group_type == 'support') {
			cell.appendChild(createTextLabel('LBL_SERIAL_NO'));
		} else {
			cell.appendChild(nbsp());
		}
		row.appendChild(cell);

		var cell = createTableCell({width: 70}, true);
		cell.appendChild(createTextLabel('LBL_TAX_CODE'));
		row.appendChild(cell);
		var labels = ['LBL_COST', 'LBL_LIST_PRICE', 'LBL_UNIT_PRICE'];
		if (!this.productCosts) labels = ['', 'LBL_LIST_PRICE', 'LBL_UNIT_PRICE'];
		if(this.isPO)
			labels = ['LBL_UNIT_PRICE', 'LBL_EXT_PRICE'];
		else if(grp.group_type == 'service')
			labels = ['LBL_LIST_PRICE', 'LBL_UNIT_PRICE', 'LBL_EXT_PRICE'];
		else if(! this.editable)
			labels.push('LBL_EXT_PRICE');
		for(var i = 0; i < labels.length; i++) {
			var cell = createTableCell({width: 75}, true);
			cell.appendChild(createTextLabel(labels[i]));
			row.appendChild(cell);
		}
	}
	if(this.editable) {
		// removal & repositioning links
		var cell = createTableCell({width: 80}, true);
		cell.appendChild(nbsp());
		row.appendChild(cell);
	}
}

this.get_line_items_subtable = function(group_id, line_id) {
	return this.get_element([group_id, 'lines', line_id, 'subtable']);
}

this.render_line_items_subtable = function(group_id, line_id) {
	var grp = this.groups[group_id];
	if(! grp) return;
	var line_item = grp.lines[line_id];
	if(! line_item) return;

	var subtbl = createTable({});
	this.add_element([group_id, 'lines', line_id, 'subtable'], subtbl);

	var hdr_row = subtbl.insertRow(subtbl.rows.length);
	var child_count = 0;
	for(var idx = 0; idx < grp.lines_order.length; idx++)
		if(grp.lines[grp.lines_order[idx]].parent_id == line_id)
			child_count ++;
	this.render_line_items_header_row(grp, hdr_row, line_item, child_count);
	this.add_element([group_id, 'lines', line_id, 'subtable', 'header'], hdr_row);

	var foot_row = subtbl.insertRow(subtbl.rows.length);
	var cell = foot_row.insertCell(0);
	cell.appendChild(nbsp());
	if(this.editable) {
		setAttrs(cell, {style: treeNodeBg('end')});
		if (this.isPO) {
			var colspan = 8;
		} else if (this.module == 'Shipping' || this.module == 'Receiving') {
			var colspan = 8;
		} else {
			var colspan = 9;
		}
		var cell = createTableCell({colSpan: colspan, style: {padding: '1pt 0 3pt 2pt'}});
		this.render_add_line_links(cell, group_id, line_id);
		foot_row.appendChild(cell);
	}
	else
		foot_row.style.display = 'none';
	this.add_element([group_id, 'lines', line_id, 'subtable', 'footer'], foot_row);

	return subtbl;
}


this.render_line_item_row = function(row, group_id, line_id, is_last) {
	var grp = this.groups[group_id];
	if(! grp) return;
	var line_item = grp.lines[line_id];
	if(! line_item) return;

	function std_attrs(name, recalc, attrs) {
		if(! isset(attrs.id))
			attrs.id = [group_id, 'lines', line_id, name].join(':');
		attrs.tabIndex = editor.tabIndex;
		if(editor.editable && blank(attrs.readOnly)) {
			attrs.onchange = function() {
				var val = this.getValue(true);
				editor.update_line_item(group_id, line_id, name, val, recalc);
			}
		}
		else
			attrs.readOnly = true;
		return attrs;
	}

	if(line_item.depth) {
		var dots = (is_last && ! editor.editable) ? 'end' : 'branch';
		var bgStyle = treeNodeBg(dots);
		var cell = createTableCell({style: bgStyle});
		cell.style.width = '16px';
		cell.appendChild(nbsp(3));
		row.appendChild(cell);
	}

	if(line_item.is_comment) {
		if (this.isPO) {
			var colspan = 3;
		} else if (this.module == 'Shipping' || this.module == 'Receiving') {
			var colspan = 1;
		} else {
			var colspan = 4;
		}
		// + quantity
		var cell = createTableCell({width: '2%', align: 'right'});
		cell.appendChild(get_icon_image('Notes'));
		row.appendChild(cell);
		cell = createTableCell({colSpan: colspan});
		var attrs = std_attrs('body', false, {rows: 3, cols: 60, style: {width: '98%'}});
		var textInput = createTextInput(line_item.body, attrs, this.form);
		cell.appendChild(textInput);
		this.add_element(attrs.id, textInput);
		row.appendChild(cell);

		if (this.module != 'Shipping' && this.module != 'Receiving') {
			if (this.isPO) {
				var colspan = 2;
			} else {
				var colspan = 3;
			}
			var cell = createTableCell({colSpan: colspan});
			cell.appendChild(nbsp());
			row.appendChild(cell);
		}
	} else {
		// + quantity
		var cell = createTableCell({width: '2%'});
		if (grp.group_type == 'expenses') {
			cell.appendChild(nbsp());
		} else {
			var attrs = std_attrs('quantity', true, {
				size: 4,
				format: 'float',
				min_decimals: 0,
				decimals: 2,
				default_num_value: 1
			});
			var qtyInput = createTextInput(stdFormatNumber(line_item.quantity), attrs);
			cell.appendChild(qtyInput);
			this.add_element(attrs.id, qtyInput);
		}
		row.appendChild(cell);

		// + name / selection
		var cell = createTableCell();
		var subtbl = this.render_line_item_name(group_id, line_id);
		cell.appendChild(subtbl);
		row.appendChild(cell);

		// + manuf. part number
		var cell = createTableCell();
		if(grp.group_type != 'service' && grp.group_type != 'expenses') {
			var attrs = std_attrs('mfr_part_no', false, {size: (this.module == 'Shipping' || this.module == 'Receiving') ? 25 : 15});
			this.catalog_attrs(attrs);
			var partNum = createTextInput(line_item.mfr_part_no, attrs, this.form);
			cell.appendChild(partNum);
			this.add_element(attrs.id, partNum);
		} else if (grp.group_type == 'expenses') {
			var attrs = {
				id: [group_id, 'lines', line_id, 'description_input'].join(':'),
				onchange: function() {
					editor.update_line_item(group_id, line_id, 'description', this.value, false);
				},
				size: 30, /*style: style,*/ tabIndex: this.tabIndex
			};

			this.catalog_attrs(attrs);
			attrs.readOnly = !this.editable;
			var nameInput = createTextInput(line_item.description, attrs, this.form);
			cell.appendChild(nameInput);
		} else {
			cell.appendChild(nbsp());
		}
		row.appendChild(cell);

		if (this.module == 'Shipping' || this.module == 'Receiving') {					
			// + vendor part number
			var cell = createTableCell();
			if(grp.group_type != 'service') {
				var attrs = std_attrs('vendor_part_no', false, {size: 25});
				this.catalog_attrs(attrs);
				var vndNum = createTextInput(line_item.vendor_part_no, attrs, this.form);
				cell.appendChild(vndNum);
				this.add_element(attrs.id, partNum);
			}
			else
				cell.appendChild(nbsp());
			row.appendChild(cell);
		}	

		if (this.module != 'Shipping' && this.module != 'Receiving') {					
			// + serial number
			var cell = createTableCell();
			if(grp.group_type == 'support' && (line_item.related_type == 'Assets' || line_item.related_type == 'SupportedAssemblies')) {
				var attrs = std_attrs('serial_no', false, {size: 6});
				this.catalog_attrs(attrs);
				var serialNum = createTextInput(line_item.serial_no, attrs, this.form);
				cell.appendChild(serialNum);
				this.add_element(attrs.id, serialNum);
			}
			else
				cell.appendChild(nbsp());
			row.appendChild(cell);

			// + tax class
			var cell = createTableCell({width: 100});
			if(line_item.sum_of_components)
				cell.appendChild(nbsp());
			else {
				var codes = {};
				for(var c in SysData.taxcodes)
					codes[c] = SysData.taxcodes[c].code;
				var attrs = std_attrs('tax_class_id', true, {style: {width: '7em'}});
				var taxCls = createSelectInput(SysData.taxcodes_order, codes, line_item.tax_class_id||'-99', attrs);
				cell.appendChild(taxCls);
				this.add_element(attrs.id, taxCls);
			}
			row.appendChild(cell);


			// + price columns
			var price_fields = ['cost_price', 'list_price', 'dummy'];
			if (!this.productCosts) price_fields[0] = 'dummy';

			if(this.isPO) {
				price_fields = ['unit_price'];
				price_fields.push(line_item.sum_of_components ? 'dummy' : 'ext_price');
			}
			else if (this.module == 'Shipping' || this.module == 'Receiving') {
				price_fields[0] = price_fields[1] = 'dummy';
			}
			else {
				if(line_item.sum_of_components || grp.group_type == 'service')
					price_fields[2] = 'unit_price';
				if(grp.group_type == 'service') {
					price_fields.shift();
					price_fields.push('ext_price');
				}
			}

			for(var idx = 0; idx < price_fields.length; idx++) {
				var fld = price_fields[idx];
				var cell = createTableCell();
				if (fld != 'dummy') {
					var attrs = {size: 10, format: 'currency', style: {textAlign: 'right'}};
					if(line_item.sum_of_components || (fld == 'unit_price' && editor.get_line_pricing_method(grp.pricing_method, line_item) != ''))
						attrs.readOnly = true;
					else if(fld == 'ext_price')
						attrs.readOnly = true;
					attrs = std_attrs(fld, true, attrs);
					this.price_attrs(attrs);
					if (grp.group_type == 'expenses' && fld == 'unit_price') {
						attrs.readOnly = !this.editable;
					}
					var price = createTextInput(formatCurrency(line_item[fld], true), attrs);
					this.add_element(attrs.id, price);						
				} else {
					var price = nbsp();
				}
				cell.appendChild(price);
				row.appendChild(cell);
			}
		}
	}

	// + removal & repositioning links
	if(this.editable) {
		var cell = createTableCell();
		var links = this.render_up_down_remove_table(cell, group_id, line_id);
		cell.appendChild(links);
		row.appendChild(cell);
	}

	return line_item;
}

this.render_line_item_pricing_row = function(row, group_id, line_id, is_last) {
	var grp = this.groups[group_id];
	if(! grp) return;
	var line_item = grp.lines[line_id];
	if(! line_item) return;

	this.get_line_pricing_method(grp.pricing_method, line_item);
	function std_attrs(name, recalc, attrs) {
		if(! isset(attrs.id))
			attrs.id = [group_id, 'lines', line_id, name].join(':');
		attrs.tabIndex = editor.tabIndex;
		if(editor.editable && blank(attrs.readOnly)) {
			attrs.onchange = function() {
				var val = this.getValue(true);
				editor.update_line_item(group_id, line_id, name, val, recalc);
			}
		}
		else
			attrs.readOnly = true;
		return attrs;
	}

	if(line_item.depth) {
		var dots = 'dots';
		if(! this.editable && is_last) {
			dots = null;
		}
		if (dots) {
			var bgStyle = treeNodeBg(dots);
			var cell = createTableCell({style: bgStyle});
		} else {
			var cell = createTableCell({});
			cell.appendChild(nbsp());
		}
		row.appendChild(cell);
	}

	var cell = createTableCell();
    cell.appendChild(createTextLabel('LBL_PRICING_METHOD'));
    cell.appendChild(sepLabel());
	row.appendChild(cell);

	var colspan = this.isPO ? 5 : 6;
	var cell = createTableCell({colSpan: colspan});

	if (grp.group_type != 'service') {
		var attrs = std_attrs('pricing_method', true, {style: {width: '15em'}});
		attrs.onchange = function() {
			var val = this.getValue();
			var spec = {
				related_type: '',
				related_id: ''
			};
			switch (val) {
				case 'inherit' :
					spec.type = 'Inherit';
					break;
				case '':
					spec.type = 'None';
					break;
				case 'margin':
					spec.type = 'Margin';
					break;
				case 'markup':
					spec.type = 'Markup';
					break;
				case 'discount':
					spec.name = '';
					//spec.rate = 0.00;
					//spec.amount = 0.00;
					spec.type = 'PercentDiscount';
					break;
				case 'stddiscount':
					spec.type = 'StdPercentDiscount';
					spec.name = '';
					spec.rate = 0.00;
					spec.amount = 0.00;
					break;
				case 'list':
					spec.type = 'SameAsList';
					break;
			}
			editor.update_price_adjustment(group_id, line_id, spec);
			var perc = editor.get_element([line_id, 'discount_perc']);
			perc.value = stdFormatNumber(line_item.adjusts[line_item.pricing_adjust_id].rate, 2, 2);
			editor.display_pricing_options(line_id, val);
		}

		var strings = app_list_strings_ord('line_pricing_method_dom');
			var disabled = {};
			if (!this.productCosts) {
				disabled.margin = true;
				disabled.markup = true;
			}
			if (this.fixedPrices) {
				disabled.margin = true;
				disabled.markup = true;
				disabled.list = true;
			}
			if (!this.manualDiscounts) {
				disabled.discount = true;
			}
			if (!this.standardDiscounts) {
				disabled.stddiscount = true;
			}
		var pricMeth = createSelectInput(strings.order, strings.values, this.type_to_method(line_item.adjusts[line_item.pricing_adjust_id].type), attrs, disabled);
		cell.appendChild(pricMeth);
	}
	cell.appendChild(nbsp());

	var method = this.type_to_method(line_item.adjusts[line_item.pricing_adjust_id].type);
	var span = document.createElement('span');
	if (typeof(method) == 'undefined' || method == '' || method == 'list' || method == 'inherit' || method == 'stddiscount') {
		span.style.display = 'none';
	}

	this.add_element([line_item.id, 'pricing_perc_div'], span);
	var attrs = {size: 5, numFormat: {minimumDecimals: true}, tabIndex: this.tabIndex};
	attrs = std_attrs('pricing_percentage', true, attrs);
	if(!this.editable) {
		attrs.readOnly = true;
	}
	attrs.onchange = function() {
		var spec = {
			rate: this.numVal
		};
		editor.update_price_adjustment(group_id, line_id, spec);
	}

	var pricPerc = createTextInput(stdFormatNumber(line_item.adjusts[line_item.pricing_adjust_id].rate, 2, 2), attrs);
	span.appendChild(pricPerc);
	span.appendChild(nbsp());
	span.appendChild(document.createTextNode('%'));
	cell.appendChild(span);
	this.add_element([line_item.id, 'discount_perc'], pricPerc);

	var span = document.createElement('span');
	var method = this.type_to_method(line_item.adjusts[line_item.pricing_adjust_id].type);
	if (method != 'stddiscount') {
		span.style.display = 'none';
	}
	this.add_element([line_item.id, 'discount_div'], span);
	var subtbl = this.render_line_discount_select(group_id, line_id);
	span.appendChild(subtbl);
	cell.appendChild(span);

	row.appendChild(cell);

	var price_fields = ['unit_price', 'ext_price'];
	for(var idx = 0; idx < price_fields.length; idx++) {
		var fld = price_fields[idx];
		var cell = createTableCell();
		var attrs = {size: 10, format: 'currency', style: {textAlign: 'right'}};
		attrs = std_attrs(fld, true, attrs);
		if(line_item.sum_of_components || (fld == 'unit_price' && this.get_line_pricing_method(grp.pricing_method, line_item) != '')) {
			attrs.readOnly = true;
		}
		if(fld == 'ext_price') {
			attrs.readOnly = true;
			attrs.size = 13;
		}
		this.price_attrs(attrs);
		var price = createTextInput(formatCurrency(line_item[fld], true), attrs);
		cell.appendChild(price);
		this.add_element(attrs.id, price);
		row.appendChild(cell);
	}

	// + removal & repositioning links
	if(this.editable && price_fields.length < 2) {
		var cell = createTableCell();
		cell.appendChild(nbsp());
		row.appendChild(cell);
	}

	return line_item;
}

this.check_line_attributes = function(line) {
	var missing_attr = {};
	if(! line.attributes_meta || YLang.isArray(line.attributes_meta))
		line.attributes_meta = {};
	for(var nm in line.attributes_meta)
		missing_attr[nm] = 1;
	for (var i in line.adjusts) {
		var adj = line.adjusts[i];
		if (adj.type != 'ProductAttributes')
			continue;
		var s = adj.name.split(' : '), preset_value;
		if(s.length > 1) {
			adj.name = s[0];
			adj.value = preset_value = s[1];
		}
		if(isset(missing_attr[adj.name]))
			delete missing_attr[adj.name];
		if(isset(preset_value) && line.attributes_meta[adj.name]) {
			for (var j = 0; j < line.attributes_meta[adj.name].length; j++) {
				var meta = line.attributes_meta[adj.name][j];
				if(isset(preset_value) && preset_value == meta.value) {
					meta.price = adj.amount;
					meta.price_usdollar = adj.amount_usd;
					preset_value = null;
				}
				if(j == line.attributes_meta[adj.name].length - 1 && isset(preset_value)) {
					// add new item
					var new_meta = {
						'type': adj.type,
						'name': adj.name,
						'value': preset_value,
						'price': adj.amount,
						'price_usdollar': adj.amount_usd,
						'related_type': adj.related_type,
						'related_id': adj.related_id
					};
					line.attributes_meta[adj.name].push(new_meta);
					preset_value = null;
				}
			}
		}
	}
	for(var nm in missing_attr)
		this.add_line_adjustment(line, {type: 'ProductAttributes', related_type: 'ProductAttributes', 'name': nm });
}

this.render_attrs_row = function(row, group_id, line_id, is_last) {
	var grp = this.groups[group_id];
	if(! grp) return;
	var line = grp.lines[line_id];
	if(! line) return;

	while(row.childNodes.length)
		row.removeChild(row.childNodes[0]);

	if(line.depth) {
		var dots = 'dots';
		if(! this.editable && is_last) {
			dots = null;
		}
		if (dots) {
			var bgStyle = treeNodeBg(dots);
			var cell = createTableCell({style: bgStyle});
		} else {
			var cell = createTableCell({});
			cell.appendChild(nbsp());
		}
		row.appendChild(cell);
	}

	var cell = createTableCell();
	cell.appendChild(nbsp());
	row.appendChild(cell);

	var cell = createTableCell({colSpan: 2, style: {verticalAlign: 'top'}});
	row.appendChild(cell);

	var subtable = createTable();
	cell.appendChild(subtable);

	var adjtpls = {};
	for (var i in line.adjusts) {
		var adj = line.adjusts[i];
		if (adj.type != 'ProductAttributes') {
			continue;
		}

		var attr_row = subtable.insertRow(subtable.rows.length);
		var cell = createTableCell();
		attr_row.appendChild(cell);

		var s = adj.name.split(' : '), preset_value;
		if(s.length > 1) {
			adj.name = s[0];
			adj.value = preset_value = s[1];
		}

		if (line.attributes_meta && line.attributes_meta[adj.name]) {
			cell.appendChild(createElement2('div', {className: 'input-icon theme-icon bean-ProductAttribute'}));
			cell.appendChild(document.createTextNode(' ' + adj.name));
			var values = [''];
			var labels = {'' : app_string('LBL_NONE')};
			adjtpls[adj.id] = {
				'' : {
					'type' : 'ProductAttributes',
					'name' : adj.name,
					'value' : '',
					'amount' : 0.0,
					'amount_usd': 0.0,
					'related_type' : 'ProductAttributes',
					'related_id' : ''
				}
			};
			for (var j = 0; j < line.attributes_meta[adj.name].length; j++) {
				var meta = line.attributes_meta[adj.name][j];
				labels[meta.value] = meta.value;
				if(meta.price) {
					var price = formatCurrency(meta.price, true);
					if(meta.price > 0) price = '+'+price;
					 labels[meta.value] += ' (' + price + ')';
				}
				values.push(meta.value);
				adjtpls[adj.id][meta.value] = {
					'type' : 'ProductAttributes',
					'name' : meta.name,
					'value' : meta.value,
					'amount' : meta.price,
					'amount_usd' : meta.price_usdollar,
					'related_type' : 'ProductAttributes',
					'related_id' : meta.id
				};
			}

			var cell = createTableCell();
			var adj_id = i;
			var onchange = function() {
				editor.update_line_adjustment(group_id, line_id, this.adj_id, adjtpls[this.adj_id][this.getValue()]);
			}
			var select = createSelectInput(values, labels, adj.value || '', {onchange: onchange, adj_id: adj_id, style: {width: '18em'}, disabled: !this.editable, tabIndex:this.tabIndex});
			cell.appendChild(select);
			attr_row.appendChild(cell);
		} else {
			cell.appendChild(createElement2('div', {className: 'input-icon theme-icon bean-ProductAttribute'}));
			var value = adj.value,
				amount = formatCurrency(adj.amount);
			if(adj.amount > 0) amount = '+' + amount;
			if(value) value = app_string('LBL_SEPARATOR') + value;
			else value = '';
			cell.appendChild(document.createTextNode(' ' + adj.name + value + ' (' + amount + ')'));
		}
	}
};


this.render_taxes_row = function(row, group_id, line_id, is_last) {
    var grp = this.groups[group_id];
    if(! grp) return;
    var line = grp.lines[line_id];
    if(! line) return;

    while(row.childNodes.length)
        row.removeChild(row.childNodes[0]);

    if(line.depth) {
        var dots = 'dots';
        if(! this.editable && is_last) {
            dots = null;
        }
        if (dots) {
            var bgStyle = treeNodeBg(dots);
            var cell = createTableCell({style: bgStyle});
        } else {
            var cell = createTableCell({});
            cell.appendChild(nbsp());
        }
        row.appendChild(cell);
    }

    var cell = createTableCell({colSpan: 2, style: {textAlign: 'right', verticalAlign: 'top'}});
    cell.appendChild(nbsp());
    row.appendChild(cell);

    var cell = createTableCell({colSpan: 2, style: {verticalAlign: 'top'}});
    row.appendChild(cell);

    var subtable = createTable();
    cell.appendChild(subtable);

    var adjtpls = {};
    for (var i in line.adjusts) {
        var adj = line.adjusts[i];
        if (adj.related_type != 'TaxRates') {
            continue;
        }

        var attr_row = subtable.insertRow(subtable.rows.length);
        var cell = createTableCell();
        attr_row.appendChild(cell);
        cell.appendChild(createTextLabel('LBL_TAX'));

        var s = adj.name.split(' : '), preset_value;
        if(s.length > 1) {
            adj.name = s[0];
            adj.value = preset_value = s[1];
        }

        if (this.editable) {
            var values = [''];
            var labels = {'' : app_string('LBL_NONE')};
            adjtpls[adj.id] = {
                '' : {
                    'type' : 'StandardTax',
                    'name' : adj.name,
                    'value' : '',
                    'amount' : 0.0,
                    'related_type' : 'TaxRates',
                    'related_id' : ''
                }
            };
            for (var j = 0; j < SysData.taxrates_order.length; j++) {
                var meta = SysData.taxrates[SysData.taxrates_order[j]];
                if (meta.id == '-99') continue;
                labels[meta.id] = meta.name;
                labels[meta.id] += ' (' + meta.rate + '%)';
                values.push(meta.id);
                adjtpls[adj.id][meta.id] = {
                    'type' : 'StandardTax',
                    'name' : meta.name,
                    'value' : meta.id,
                    'amount' : 0.0,
                    'related_type' : 'TaxRates',
                    'related_id' : meta.id,
                    rate: meta.rate
                };
            }


            var cell = createTableCell();
            var adj_id = i;
            var onchange = function() {
                editor.update_line_adjustment(group_id, line_id, this.adj_id, adjtpls[this.adj_id][this.value]);
            }
            var select = createSelectInput(values, labels, adj.related_id, {onchange: onchange, adj_id: adj_id, style: {width: '18em'}, disabled: !this.editable, tabIndex:this.tabindex});
            cell.appendChild(select);

            var onRemove = function() {
                editor.remove_line_adjustment(group_id, line_id, this.adj_id);
                return false;
            }
            var remlink = createIconLink('remove', '', onRemove, {adj_id: adj_id});
            cell.appendChild(nbsp());
            cell.appendChild(remlink);

            attr_row.appendChild(cell);
        } else {
            cell.appendChild(document.createTextNode(adj.name + ' (' + formatCurrency(adj.rate) + '%)'));
		}
	}
};


this.render_serial_row = function(row, group_id, line_id, is_last) {
	var grp = this.groups[group_id];
	if(! grp) return;
	var line_item = grp.lines[line_id];
	if(! line_item) return;

	function std_attrs(name, recalc, attrs) {
		if(! isset(attrs.id))
			attrs.id = [group_id, 'lines', line_id, name].join(':');
		attrs.tabIndex = editor.tabIndex;
		if(editor.editable && blank(attrs.readOnly)) {
			attrs.onchange = function() {
                var val = this.value;
                if (typeof(val) == 'undefined')
                    val = this.getValue();
				editor.update_line_item(group_id, line_id, name, val, recalc);
			}
		}
		else
			attrs.readOnly = true;
		return attrs;
	}

	if(line_item.depth) {
		var dots = 'dots';
		if(! this.editable && is_last)
			dots = null;
		if (dots) {
			var bgStyle = treeNodeBg(dots);
			var cell = createTableCell({style: bgStyle});
		} else {
			var cell = createTableCell({});
			cell.appendChild(nbsp());
		}
		row.appendChild(cell);
	}
	var cell = createTableCell({colSpan: 2, style : {textAlign: 'right', verticalAlign: 'top'}});
	cell.appendChild(createTextLabel('LBL_SERIAL_NUMBERS'));
	row.appendChild(cell);

	var cell = createTableCell();
	var attrs = std_attrs('serial_numbers', false, {rows: 2, cols: 30 });
	var serialInput = createTextInput(line_item.serial_numbers, attrs);
	cell.appendChild(serialInput);
	row.appendChild(cell);

	return line_item;
}

this.render_line_item_event_row = function(row, group_id, line_id, is_last) {
	var grp = this.groups[group_id];
	if(! grp) return;
	var line_item = grp.lines[line_id];
	if(! line_item) return;

	function std_attrs(name, recalc, attrs) {
		if(! isset(attrs.id))
			attrs.id = [group_id, 'lines', line_id, name].join(':');
		attrs.tabIndex = editor.tabIndex;
		if(editor.editable && blank(attrs.readOnly)) {
			attrs.onchange = function() {
				var val = this.numFormat ? this.numVal : this.getValue();
				editor.update_line_item(group_id, line_id, name, val, recalc);
			}
		}
		else
			attrs.readOnly = true;
		return attrs;
	}

	if(line_item.depth) {
		var dots = 'dots';
		if(! this.editable && is_last) {
			dots = null;
		}
		if (dots) {
			var bgStyle = treeNodeBg(dots);
			var cell = createTableCell({style: bgStyle});
		} else {
			var cell = createTableCell({});
			cell.appendChild(nbsp());
		}
		row.appendChild(cell);
	}

	var cell = createTableCell();
	cell.appendChild(createTextLabel('LBL_EVENT'));
    cell.appendChild(sepLabel());
	row.appendChild(cell);

	var cell = createTableCell({'colSpan': 11});
	var subtbl = this.render_line_event_select(group_id, line_id);
	cell.appendChild(subtbl);

	row.appendChild(cell);
	return line_item;
}


this.methods_map = {
	Inherit : 'inherit',
	None : '',
	Margin : 'margin',
	Markup : 'markup',
	SameAsList : 'list',
	PercentDiscount: 'discount',
	StdPercentDiscount : 'stddiscount',
	StdFixedDiscount  : 'stddiscount'
};

this.type_to_method = function (type) {
	return this.methods_map[type];
}

this.refresh_line_item = function(group_id, line_id) {
	var grp = this.groups[group_id];
	if(! grp) return;
	var line = grp.lines[line_id];
	if(! line) return;
	for(var idx = 0; idx < this.price_columns.length; idx++) {
		var col = this.price_columns[idx];
		var fld = this.get_element([group_id, 'lines', line_id, col]);
		if(fld) {
			if(isset(line[col]))
				fld.value = formatCurrency(line[col]);
			if(col == 'unit_price') {
				makeEditable(fld, ! line.sum_of_components && this.get_line_pricing_method(grp.pricing_method, line) == '' && !this.fixedPrices);
			}
		}
	}
	var line_fields = ['quantity', 'name', 'mfr_part_no', 'vendor_part_no', 'tax_class_id', 'body'];
	for(var idx = 0; idx < line_fields.length; idx++) {
		var col = line_fields[idx];
		var fld = this.get_element([group_id, 'lines', line_id, col]);
		if(fld && isset(line[col])) {
			if(col == 'quantity')
				fld.value = stdFormatNumber(line[col]);
			else
				fld.value = line[col];
		}
	}
}

this.catalog_attrs = function(attrs) {
	if (this.catalogOnly) attrs.readOnly = true;
}

this.price_attrs = function(attrs) {
	if (this.fixedPrices) attrs.readOnly = true;
}

this.get_field_map = function(related_type, support) {
	var field_map;
	if(related_type == 'ProductCatalog') {
		field_map = {
			'related_id': 'id',
			'name': 'name',
			'purchase_name': 'purchase_name',
			'tax_class_id': 'tax_code_id',
			'mfr_part_no': 'manufacturers_part_no',
			'vendor_part_no': 'vendor_part_no',
			'currency_id': 'currency_id',
			'exchange_rate': 'exchange_rate',
			'description': 'description',
			'description_plain': 'description_plain'
		};
		if (support) {
			field_map['raw_cost_price'] = 'support_cost';
			field_map['raw_list_price'] = 'support_list_price';
			field_map['raw_unit_price'] = 'support_selling_price';
			field_map['cost_price'] = 'support_cost_usdollar';
			field_map['list_price'] = 'support_list_usdollar';
			field_map['unit_price'] = 'support_selling_usdollar';
		} else {
			field_map['raw_cost_price'] = 'cost';
			field_map['raw_list_price'] = 'list_price';
			field_map['raw_unit_price'] = 'purchase_price';
			field_map['cost_price'] = 'cost_usdollar';
			field_map['list_price'] = 'list_usdollar';
			field_map['unit_price'] = 'purchase_usdollar';
		}
		if(this.isPO) {
			field_map.raw_unit_price = 'cost';
			field_map.unit_price = 'cost_usdollar';
		}
	} else if(related_type == 'Assets') {
		field_map = {
			'related_id': 'id',
			'name': 'name',
			'tax_class_id': 'tax_code_id',
			'mfr_part_no': 'manufacturers_part_no',
			'vendor_part_no': 'vendor_part_no',
			'raw_cost_price': 'support_cost',
			'cost_price': 'support_cost_usdollar',
			'raw_list_price': 'unit_support_price',
			'list_price': 'unit_support_usdollar',
			'currency_id': 'currency_id',
			'exchange_rate': 'exchange_rate',
			'description_plain': 'description',
			'serial_no' : 'serial_no'
		};
	}
	else if(related_type == 'Assemblies' || related_type == 'SupportedAssemblies') {
		field_map = {
			'related_id': 'id',
			'name': 'name',
			'mfr_part_no': 'manufacturers_part_no',
			'description': 'description',
			'description_plain': 'description_plain'
		};
		if(related_type == 'SupportedAssemblies') {
			field_map.serial_no = 'serial_no';
			field_map.description_plain = 'description';
		} else {
			field_map.purchase_name = 'purchase_name';
		}
	} else if(related_type == 'BookingCategories') {
		field_map = {
			'related_id': 'id',
			'name': 'name',
			'location': 'location',
			'duration': 'duration',
			'seniority': 'seniority',
			'currency_id': 'currency_id',
			'raw_list_price': 'billing_rate',
			'list_price': 'billing_rate_usd',
			'exchange_rate': 'exchange_rate',
			'tax_class_id': 'tax_code_id'
		};
	} else if(related_type == 'TaxRates') {
		field_map = {
			'related_id': 'id',
			'name': 'name'
		};
	}
	return field_map;
}

this.render_line_item_name = function(group_id, line_id) {
	var line_item = this.groups[group_id].lines[line_id];
	var grp = this.groups[group_id];
    var support = (grp.group_type == 'support');
    var field_map = this.get_field_map(line_item.related_type, support);
    var ref_id = [group_id, 'lines', line_id, 'name_input'].join(':');

    var attrs = {
        id: ref_id,
        module: line_item.related_type,
        tabIndex: this.tabIndex,
        width: '25em',
        onchange: return_line_item2,
        onrename: rename_line_item,
        extra_fields: field_map,
        popup_passthru: {
            group_id: group_id,
            line_id: line_id,
            module: line_item.related_type
        },
        allow_custom: ! this.catalogOnly,
        allow_rename: ! this.catalogOnly,
        disabled: ! this.editable
    };

    if (this.isPO)
        attrs.add_filters = [{param: 'supplier', field_name: 'supplier'}];

	//this.catalog_attrs(attrs);
	//if (grp.group_type == 'service')
		//attrs.readOnly = true;
	/*if (grp.group_type == 'expenses') {
		attrs.readOnly = !this.editable;
		if (line_item.related_type == 'BookingCategories') {
			attrs.readOnly = true;
		}
	}*/
    var outer = createRefInput(line_item.related_id, line_item.name, attrs, this.form);
    this.add_element(attrs.id, outer);

	return outer;
}

this.render_line_discount_select = function(group_id, line_id) {
	var line_item = this.groups[group_id].lines[line_id];
	var adj, name_value = '';

	if(line_item.pricing_adjust_id && line_item.adjusts[line_item.pricing_adjust_id]) {
		adj = line_item.adjusts[line_item.pricing_adjust_id];
		if(adj.related_id) {
			name_value = adj.name;
			if (adj.type == 'StdPercentDiscount') {
				name_value += ' (' + stdFormatNumber(adj.rate) + '%)';
			} else {
				name_value += ' (' + formatCurrency(adj.amount) + ')';
			}
		}
	}

	var field_map = {
		'related_id': 'id',
		'name': 'name',
		'amount': 'fixed_amount_usdollar',
		'currency_id': 'currency_id',
		'exchange_rate': 'exchange_rate',
		'rate': 'rate',
		'discount_type': 'discount_type'
	};
	var id = [group_id, 'lines', line_id, 'discount_select'];

	var attrs = {
		id: id.join(':'),
		module: 'Discounts',
		tabIndex: this.tabIndex,
		width: '25em',
		popup_callback: 'return_line_discount',
		extra_fields: field_map,
		disabled: !(this.editable),
		filter: '&only_product_id=' + line_item.related_id,
		popup_passthru: {
			group_id: group_id,
			line_id: line_id
		}
	};

	var refOuter = createRefInput(adj ? adj.related_id : '', name_value, attrs, this.form);
	this.add_element(id, refOuter);
	return refOuter;
}

this.render_line_event_select = function(group_id, line_id) {
	var line_item = this.groups[group_id].lines[line_id];
	var span = document.createElement('span');
    if (blank(line_item.event_session_name)) line_item.event_session_name = '';
    if (blank(line_item.event_session_id)) line_item.event_session_id = '';
    if (blank(line_item.event_session_date)) line_item.event_session_date = '';

	var field_map = {
		'id': 'id',
		'name': 'name',
		'date_start': 'date_start'
	};
	var id = [group_id, 'lines', line_id, 'event_select'].join(':');
    //var filters = [];
    //filters[0] = {param: 'product_id', value: line_item.related_id};
    var attrs = {
        id: id,
        module: 'EventSessions',
        tabIndex: this.tabIndex,
        width: '25em',
        popup_callback: 'return_line_event',
        extra_fields: field_map,
        disabled: !(this.editable || this.editable_events),
        popup_passthru: {
            group_id: group_id,
            line_id: line_id
        }
    };

    var outer = createRefInput(line_item.event_session_id, line_item.event_session_name, attrs, this.form);
    span.appendChild(outer);
    this.add_element(attrs.id, outer);

	return span;
}

this.redraw_line_item = function(group_id, line_id) {
	var is_last = 0;
	var grp = this.groups[group_id];
	if(grp) {
		var stat = 0;
		var found;
		for(var idx = 0; idx < grp.lines_order.length; idx++) {
			var l = grp.lines[grp.lines_order[idx]];
			if(l.id == line_id) {
				stat = 1;
				found = grp.lines_order[idx];
			}
			else if(stat && l.parent_id == grp.lines[found].parent_id) {
				stat = 2;
				break;
			}
		}
		if(stat < 2) is_last = 1;
	}
	var row = this.get_element([group_id, 'lines', line_id, 'row']);
	if(row) {
		while(row.childNodes.length)
			row.removeChild(row.childNodes[0]);
		this.render_line_item_row(row, group_id, line_id, is_last);
	}
	var pricing_row = this.get_element([group_id, 'lines', line_id, 'pricing_row']);
	if (pricing_row) {
		while(pricing_row.childNodes.length)
			pricing_row.removeChild(pricing_row.childNodes[0]);
		this.render_line_item_pricing_row(pricing_row, group_id, line_id, is_last);
	}
	var has_attrs = has_product_attributes(grp.lines[line_id]);
	var attrs_row = this.get_element([group_id, 'lines', line_id, 'attrs_row']);
	if(attrs_row) {
		if(has_attrs) {
			attrs_row.style.display = '';
			this.render_attrs_row(attrs_row, group_id, line_id, is_last);
		}
		else
			attrs_row.style.display = 'none';
	}
}


this.render_up_down_remove_table = function(cell, group_id, line_id) {
	var linkTbl = createTable({cellPadding: 0, cellSpacing: 0, width: 80});
	var row = linkTbl.insertRow(0);

	var cell = createTableCell({width: '30%', style: {textAlign: 'center'}});
	if (editor.move_line_item(group_id, line_id, +1, true)) {
		var onclick = function() { 
			editor.move_line_item(group_id, line_id, +1);
			return false;
		}
		var downlink = createIconLink('down', '', onclick, {});
		cell.appendChild(downlink);
	}
	row.appendChild(cell);

	var cell = createTableCell({width: '30%', style: {textAlign: 'center'}});
	if (editor.move_line_item(group_id, line_id, -1, true)) {
		var onclick = function() {
			editor.move_line_item(group_id, line_id, -1);
			return false;
		}
		var uplink = createIconLink('up', '', onclick, {});
		cell.appendChild(uplink);
	}
	row.appendChild(cell);

	var cell = createTableCell({width: '40%', style: {textAlign: 'center'}});
	var onRemove = function() {
		if (confirm(mod_string('NTC_CONFIRM_REMOVE_ROW')))
			editor.remove_line_item(group_id, line_id); 
		return false;
	}
	var remlink = createIconLink('remove', '', onRemove, {});
	cell.appendChild(remlink);
	row.appendChild(cell);

	return linkTbl;
}


this.redraw_footer = function() {
	var div = $('tally_footer');
	div.innerHTML = '';
	if(this.editable) {
		div.appendChild(document.createElement('hr'));
		var onclick = function() {
			TallyEditor.add_group();
			return false;
		};
		div.appendChild(createIconLink('insert', 'LBL_ADD_GROUP', onclick, {}));
	}
}


this.redraw_all = function() {
	this.redraw_header();
	for(var idx = 0; idx < this.groups_order.length; idx++)
		this.redraw_group(this.groups_order[idx]);
	this.redraw_footer();
	this.redraw_totals();
}

this.redraw_header = function() {
   var tax_exempt = SUGAR.ui.getFormInput(this.form, 'tax_exempt');
	var tax_exempt_warning = $('tax_exempt_warning');
	if (tax_exempt) {
		if (tax_exempt.getValue()) {
			tax_exempt_warning.innerHTML = mod_string('LBL_TAX_EXEMPT_WARNING');
		} else {
			tax_exempt_warning.innerHTML = '&nbsp;';
		}
	}
}

this.tax_exempt_changed = function(cb, from_account) {
	if (cb.getValue() && this.editable) {
		var conf = confirm(mod_string(from_account ? 'LBL_TAX_EXEMPT_ACCOUNT' : 'LBL_TAX_EXEMPT_CONFIRMATION')); 
		if (conf) {
			this.reset_tax_codes();
		} else {
            cb.setValue(0);
		}
	}
	this.redraw_header();
}

this.tax_discount_changed = function(cb) {
	if (!cb.getValue()) {
		this.iterate_groups(
			function(group_id, grp) {
				for(var jdx = 0; jdx < grp.discounts_order.length; jdx++) {
					var adj = grp.adjusts[grp.discounts_order[jdx]];
					adj.tax_class_id = null;
				}
			}
		);
	}
	this.recalculate();
}

this.reset_tax_codes = function() {
	for(var i = 0; i < this.groups_order.length; i++) {
		var grp = this.groups[this.groups_order[i]];
		for(var idx = 0; idx < grp.lines_order.length; idx++) {
			var line = grp.lines[grp.lines_order[idx]];
			if(! line.is_comment) {
				if(! line.sum_of_components) {
					line.tax_class_id = '-99';
				}
			}
		}

	}
	this.recalculate(true);
	this.redraw_all();
}

this.add_line_adjustment = function(line, template) {
	if (!line.adjusts) line.adjusts = { };
	var adj_id = 'newadj~' + (this.unique_index ++);
	if(! YLang.isObject(template))
		template = {};
	template.id = adj_id;
	if(! isset(template.rate))
		template.rate = 0.0;
	if(! isset(template.amount))
		template.amount = 0.0;
	line.adjusts[adj_id] = template;
	return line.adjusts[adj_id];
};

this.set_default_discount = function(discount_id) {
	var last_default = this.default_discount_id;
	this.default_discount_id = discount_id;
	var update_groups = true;
	var adjs = {};
	// update existing groups only if they have no discounts, or only the last default discount
	for(var idx = 0; idx < this.groups_order.length; idx++) {
		var grp = this.groups[this.groups_order[idx]];
		for(var jdx = 0; jdx < grp.discounts_order.length; jdx++) {
			var adj = grp.adjusts[grp.discounts_order[jdx]];
			if(adj.related_id && adj.related_id != last_default) {
				update_groups = false;
				break;
			}
			if(adjs[this.groups_order[idx]]) {
				update_groups = false;
			}
			adjs[this.groups_order[idx]] = grp.discounts_order[jdx];
		}
		if(! update_groups)
			break;
	}
	if(! update_groups)
		return;
	var upd_adj = this.get_discount_adjustment(discount_id);
	for(var idx = 0; idx < this.groups_order.length; idx++) {
		if(adjs[this.groups_order[idx]])
			this.update_adjustment(this.groups_order[idx], adjs[this.groups_order[idx]], upd_adj);
		else
			this.add_adjustment(this.groups_order[idx], upd_adj, true);
	}
	this.recalculate();
};

this.get_currency_id = function() {
	// FIXME cache value at TallyEditor level, add listener to input field
	return this.currency_input.getValue() || '-99';
}

this.currency_decimal_places = function(currency_id) {
	return this.currency_input.getDecimals(currency_id);
};

this.currency_rate = function(currency_id) {
	return this.currency_input.getRate(true, currency_id);
};

// -- Element cache

this.add_element = function(id, elt) {
	if(YLang.isObject(id))
		id = id.join(':');
	this.elements[id] = elt;
}
this.get_element = function(id) {
	if(YLang.isObject(id))
		id = id.join(':');
	if(isset(this.elements[id]))
		return this.elements[id];
	return null;
}
this.remove_elements = function(id) {
	if(YLang.isObject(id))
		id = id.join(':');
	for(var idx in this.elements) {
		if(idx.substr(0, id.length) == id)
			delete this.elements[idx];
	}
}


// -- Form

this.iterate_group_lines = function(group_id, callback)
{
	var grp = this.groups[group_id];
	if(! grp) return;
	for(var idx = 0; idx < grp.lines_order.length; idx++) {
		var line = grp.lines[grp.lines_order[idx]];
		if (callback(grp, grp.lines_order[idx], line)) {
			break;
		}
	}
}

this.iterate_groups = function(callback)
{
	for(var idx = 0; idx < this.groups_order.length; idx++) {
		var group_id = this.groups_order[idx];
		var grp = this.groups[group_id];
		if(grp) {
			if (callback(group_id, grp)) {
				break;
			}
		}
	}
}

this.open_select_popup = function(module, request_data, filter, options) {
	if(! isset(options)) options = {};
	options.inline = true;
	options.module = module;
	options.request_data = request_data;
	if(! options.title)
		options.title = mod_string('LBL_SELECT_PREFIX') + module_name(module, true);
	var width = options.width;
	if(! width) {
		var minw = (module == 'BookingCategories') ? 670 : 750;
		width = get_default(options.min_width, minw);
		var scrw = YAHOO.util.Dom.getViewportWidth();
		if(scrw > 1024)
			width += Math.round(Math.min(150, (scrw - 1024) / 2));
		options.width = width;
	}
	var height = options.height;
	if(! height) {
		height = get_default(options.min_height, 450);
		var scrh = YAHOO.util.Dom.getViewportHeight();
		if(scrh > 600)
			height += Math.round(Math.min(200, (scrh - 600) / 2));
		options.height = height;
	}
	options.filter = filter;
	if(! isset(options.close_popup))
		options.close_popup = true;
	return open_popup_window(options);
}

this.validate = function() {
	if(this.editable || this.editable_events) {
		if(this.module == 'Invoice') {
			var count = 0;
			for(var idx = 0; idx < this.groups_order.length; idx++) {
				var grp = this.groups[this.groups_order[idx]];
				count += grp.lines_order.length;
			}
			if(! count) {
				this.invalidMsg = mod_string('NTC_ONE_ITEM_NEEDED');
				return false;
			}
		}
	}

	// FIXME - re-enable inventory check - use synchronous JSON call, don't modify actual form attributes
	if (0 && this.module == 'Shipping') {
		this.form.action.value = 'CheckInventory';
		this.form.to_pdf.value = 'true';

		var callback = function(o) {
			if(! o) return;
			try {
				SUGAR.util.evalScript(o.responseText);
				var failure = window.inventoryCheck.failure;
				if (failure) {
					var msg = mod_string('NTC_CONFIRM_UNDER_STOCK1') + "\n";
					for (var name in window.inventoryCheck.outOfStock) {
						msg += "\n" + name + ' : ' + window.inventoryCheck.outOfStock[name];
					}
					msg += "\n\n" +mod_string('NTC_CONFIRM_UNDER_STOCK2') ;
					failure = !confirm(msg);
				}
				if (!failure) {
					TallyEditor.form.submit();
				}
			} catch (e) {
			}
		};
		SUGAR.conn.sendForm(this.form, {status_msg: mod_string('LBL_CHECKING_INVENTORY')}, callback);
		this.form.action.value = 'Save';
		this.form.to_pdf.value = '';
		return false;
	}

	return true;
}

this.encode_line_items = function() {
	for (var i = 0; i < this.groups_order.length; i++) {
		var grp_id = this.groups_order[i];
		var grp = this.groups[grp_id];
		for (var j = 0; j < grp.lines_order.length; j++) {
			var line_id = grp.lines_order[j];
			var line = grp.lines[line_id];
			for (var adj_id in line.adjusts) {
				var adj = deep_clone(line.adjusts[adj_id]);
				if (adj.type == 'ProductAttributes') {
					if(! isset(adj.value) || adj.value === '')
						continue;
					adj.name += ' : ' + adj.value;
				}
				adj.line_id = line_id;
				grp.adjusts_order.push(adj_id);
				this.groups[grp_id].adjusts[adj_id] = adj;
			}
		}
	}
	return { order: this.groups_order, data: this.groups };
}

this.beforeSubmitForm = function(form) {
	if(this.editable || this.editable_events) {
		var ret = this.encode_line_items(), input;
		input = form.line_items;
		if(! input) input = createElement2('input', {type: 'hidden', name: 'line_items'}, null, form);
		input.value = JSON.stringify(ret);
	}
}

this.setAccount = function(result, prefix, force_additional) {
	if(! result || YLang.isString(result)) return;
	var upd_currency = false;
	var upd_rate = false;
	var other_map = {'shipping': 'billing', 'billing': 'shipping'};
	var other_prefix = other_map[prefix];
	var other_input = SUGAR.ui.getFormInput(this.form, other_prefix + '_account');
	var update_opposite = other_input && ! other_input.getKey();
	var update_additional = update_opposite || force_additional;
	this.default_tax_code_id = get_default(result.tax_code_id, '');
	if (this.default_tax_code_id == '-99') {
		var tax_exempt = SUGAR.ui.getFormInput(this.form, 'tax_exempt');
		if (tax_exempt && !tax_exempt.getValue()) {
			tax_exempt.setValue(1);
			this.tax_exempt_changed(tax_exempt, true);
		}
	}
	if(isdef(result.currency_id)) {
		if (update_additional && this.editable) {
			var cid = result.currency_id;
			if(! cid)  cid = '-99';
			upd_currency = true;
		}
		delete result.currency_id;
	}
	if(isdef(result.exchange_rate)) {
		if (update_additional && this.editable) {
			var exch_rate = result.exchange_rate;
			upd_rate = true;
		}
		delete result.exchange_rate;
	}
	if (update_opposite) {
		if(isset(result[prefix + '_phone']))
			result[other_prefix + '_phone'] = result[prefix + '_phone'];
		if(isset(result[prefix + '_email']))
			result[other_prefix + '_email'] = result[prefix + '_email'];
	}

	if(!update_opposite) {
		var re = new RegExp('^' + other_prefix + '_');
		for (var the_key in result) {
			if (the_key.match(re)) delete(result[the_key]);
		}
	}
	if (!update_opposite && prefix != 'shipping' && prefix != 'supplier') {
		delete result.shipping_provider_id;
	}
	if(isdef(result.terms) && ! result.terms)
		delete result.terms;

	SUGAR.ui.setFormInputValues(this.form, result);

	if((upd_currency || upd_rate) && update_additional) {
		if(! upd_rate)
			this.currency_input.setValue(cid);
		else
			this.currency_input.setValueAndRate(cid, exch_rate);
	}
	if(!blank(result.account_credit_limit) && (result.account_credit_limit > 0) && !blank(result.account_balance) && update_additional) {
		if(result.account_balance > result.account_credit_limit)
			alert(mod_string('NTC_OVER_CREDIT_LIMIT'));
	}
	if(!blank(result.discount_id) && result.discount_id != '-99' && update_additional && this.editable) {
		this.set_default_discount(result.discount_id);
	}

	if (result.account_popups == 1 && result.sales_popup && prefix == 'billing') {
		setTimeout(function() {show_popup_message(result.sales_popup.replace(/\r\n?/g, "<br />"), {timeout:15000, title: result['_display'], close_button: app_string('LBL_ADDITIONAL_DETAILS_CLOSE')});}, 1000);
	}
    if (update_opposite)
        other_input.update(result.id, result);
}

return this;
}();
// end TallyEditor


function createTable(attrs) {
	var tbl = document.createElement('table');
	tbl.border = 0;
	tbl.cellPadding = 0;
	tbl.cellSpacing = 1;
	tbl.width = '100%';
	setAttrs(tbl, attrs);
	return tbl;
}

function createTableCell(attrs, is_label) {
	if(! YLang.isObject(attrs))  attrs = {};
	var cell = document.createElement('td');
	if(is_label)
		cell.className = 'dataLabel';
	else
		cell.className = 'dataField';
	cell.noWrap = 'nowrap';
	if(isset(attrs.body)) {
		cell.appendChild(attrs.body);
		delete attrs.body;
	}
	if(isset(attrs.body_text)) {
		cell.appendChild(document.createTextNode(attrs.body_text));
		delete attrs.body_text;
	}
	setAttrs(cell, attrs);
	return cell;
}

function createIconLink(icon, text, onclick, attrs) {
	if((icon == 'up' || icon == 'down' || icon == 'remove') && ! text) {
		if(icon == 'remove') icon = 'delete';
		var elt = createElement2('div',
			{className: 'input-icon icon-'+icon+' active-icon', onclick: onclick});
		setAttrs(elt, attrs);
		return elt;
	}

	if(! YLang.isObject(attrs))  attrs = {};
	var link = document.createElement('a');
	link.href = '#';
	link.onclick = onclick;
	var title = get_default(attrs.title, '');
	link.title = title;
	link.className = 'utilsLink';
	if(typeof(icon) == 'string')
		icon = get_icon_image(icon);
	if(! blank(icon)) {
		link.appendChild(icon);
		if(text) link.appendChild(nbsp());
	}
	if(text)
		link.appendChild(createTextLabel(text));
	setAttrs(link, attrs);
	return link;
}

function createRefInput(key, value, attrs, form) {
	if(! YLang.isObject(attrs))  attrs = {};
    attrs.init_key = key;
    attrs.init_value = value;
    var input = new SUGAR.ui.RefInput(null, attrs);
    input.addFilter({param: 'display_currency_id', field_name: 'currency_id'});
    if(form) SUGAR.ui.registerInput(form, input);
    return input.render();
}

function createTextInput(value, attrs, form) {
	if(! YLang.isObject(attrs))  attrs = {};
	attrs.init_value = value;
	if(attrs.format == 'currency' && ! isset(attrs.decimals))
		attrs.decimals = TallyEditor.currency_decimal_places();
	var input;
	if(isset(attrs.rows))
		input = new SUGAR.ui.TextAreaInput(null, attrs);
	else
		input = new SUGAR.ui.TextInput(null, attrs);
	if(form) SUGAR.ui.registerInput(form, input);
	return input.render();
}


function createSelectInput(values, labels, selvalues, attrs, disabled) {
	if (!disabled) disabled = {};
    var display_lbls = [];
	for(var i = 0; i < values.length; i++) {
        display_lbls[i] = labels[values[i]];
	}
    if(! blank(attrs.readOnly)) {
        delete attrs.readOnly;
        attrs.disabled = true;
    }

    var outer = createElement2('span', {className: 'list-edit-value input-inline-arrow'});
    var opts = {keys: values, values: display_lbls};
    var select_opts = new SUGAR.ui.SelectOptions(opts);
    attrs.options = select_opts;
    if(attrs.options_width) attrs.options.width = attrs.options_width;

    var lbl = labels[selvalues];
    if (typeof(lbl) == 'undefined') lbl = '--';
    SUGAR.ui.setElementText(outer, lbl);

    if(! attrs.disabled) {
        var quicksel = new SUGAR.ui.QuickSelect(attrs);
        quicksel.initSource(outer, selvalues, attrs.onchange);
    } else {
    	outer.className += ' disabled';
    }

	return outer;
}

function createCheckboxInput(attrs) {
	// checked flag must be set after adding to document in IE
	var chk = document.createElement('input');
	chk.type = 'checkbox';
	chk.className = 'checkbox';
	setAttrs(chk, attrs);
	return chk;
}

function createButtonInput(attrs) {
	var btn = document.createElement('button');
	btn.setAttribute('type', 'button');
	btn.className = 'button';
	setAttrs(btn, attrs);
	return btn;
}

function createTextLabel(lbl, no_translate) {
	if(blank(no_translate))
		lbl = mod_string(lbl);
	var elt = document.createTextNode(lbl);
	return elt;
}

function makeEditable(element, yes) {
	var editable = isset(yes) ? !!yes : true; 
	if(element.tagName == 'SELECT')
		element.disabled = editable;
	else
		element.readOnly = !editable;
}

function makeVisible(element, yes) {
	var visible = isset(yes) ? !!yes : true;
	element.style.display = visible ? '' : 'none';
}

function treeNodeBg(type) {
	var rep = type == 'end' ? 'no-repeat' : 'repeat';
	return {
		background: 'url('+get_image_url('tree_' + type)+')',
		backgroundRepeat: rep
	};
}

function return_line_item(data) {
	var parms = data.passthru_data;
	if(data.selection_list) {
		var lines = [];
		var fetch_attrs = [];
		var attrs = {};
		var seen = {};
		for(var idx in data.selection_list) {
			values = data.associated_row_data[idx];
			if(values.related_id) {
				// popup may return duplicates
				if(seen[values.related_id]) continue;
				seen[values.related_id] = 1;
			}
			if(parms.module == 'ProductCatalog' && values.related_id)
				fetch_attrs.push(values.related_id);
			lines.push(deep_clone(values));
		}
		if(fetch_attrs.length) {
			var all_attrs = TallyEditor.get_product_attrs(fetch_attrs);
			for(var adx in all_attrs) {
				for(var vdx = 0; vdx < all_attrs[adx].length; vdx++) {
					var attr = all_attrs[adx][vdx];
					var pid = attr.parent_id;
					delete attr.parent_id;
					if(! attrs[pid]) attrs[pid] = {};
					if(! attrs[pid][attr.name]) attrs[pid][attr.name] = [];
					attrs[pid][attr.name].push(attr);
				}
			}
		}
		for(var idx = 0; idx < lines.length; idx++) {
			values = lines[idx];
			if(attrs[values.related_id])
				values.attributes_meta = deep_clone(attrs[values.related_id]);
			else
				values.attributes_meta = {};
			return_line_item2(0, values, parms, (idx == lines.length - 1 ? 2 : 1));
		}
	}
}

function return_line_item2(key, values, parms, redraw) {
	var line_id = get_default(parms.line_id, '');
	if(! values) {
		if(! line_id) return;
		values = {};
	}
	values.related_type = parms.module;
	if(parms.parent_id)
		values.parent_id = parms.parent_id;
	/*for(idx in values) {
		if(typeof(values[idx]) == 'string')
			values[idx] = html_unescape(values[idx]);
	}*/
	TallyEditor.return_line_item(parms.group_id, line_id, values, redraw);
}

function rename_line_item(value, parms, redraw) {
	var line_id = get_default(parms.line_id, '');
	TallyEditor.update_line_item(parms.group_id, line_id, 'name', value);
}

function return_line_discount(data) {
	var parms = data.passthru_data;
	var values = data.name_to_value_array;
	/*for(idx in values)
		if (values.hasOwnProperty(idx) && ! blank(values[idx]))
			values[idx] = html_unescape(values[idx]);*/
	TallyEditor.return_line_discount(parms.group_id, parms.line_id, values);
}

function return_line_event(result) {
    ui_popup_return(result);
    var params = result.passthru_data;
	var values = result.unformat_name_to_value_array;
	/*for(idx in values) {
        if (values.hasOwnProperty(idx) && ! blank(values[idx]))
		    values[idx] = html_unescape(values[idx]);
    }*/
	TallyEditor.return_line_event(params.group_id, params.line_id, values);
}

function trunc(val, places, bankers) {
	if(! isset(val) || val === '') return 0.0;
	var b = Math.pow(10, isset(places) ? places : currency_significant_digits);
	var bval = val * b;
	if(b > 1) {
		if(! bankers) bval += (bval < 0 ? -0.1 : 0.1);
		else if(Math.floor(bval) % 2 == 0) bval -= 0.1;
	}
	return Math.round(bval) / b;
}

function formatCurrency(val, noRound) {
	if(! isset(val) || val === '') return '';
	var decimals = TallyEditor.currency_decimal_places();
	if(noRound)
		return stdFormatNumber(restrictDecimals(parseFloat(val), decimals));
	return stdFormatNumber(parseFloat(val * 100).toFixed(decimals) / 100, decimals, decimals);
}

function setSupplier(result)
{
	TallyEditor.setAccount(result, 'supplier', true);
}

function setBillingContact(popup_data)
{
	set_return(popup_data);
	var form = document.forms[popup_data.form_name];
	if(form.shipping_contact_id.value == '' && form.shipping_account_id.value == form.billing_account_id.value) {
		form.shipping_contact_id.value = form.billing_contact_id.value;
		form.shipping_contact_name.value = form.billing_contact_name.value;

		if (form.shipping_phone)
		{
			form.shipping_phone.value = form.billing_phone.value;
			form.shipping_email.value = form.billing_email.value;
		}
	}
}

function has_product_attributes(line) {
	for (var i in line.adjusts) {
		if(line.adjusts[i].type == 'ProductAttributes')
			return true;
	}
	return false;
}

function setDateFromTerm(form) {
	var getDate = function(days, date_entered) {
		var d;
        if (date_entered) {
            d = new Date(date_entered);
        } else {
            d = new Date();
        }
		while (days > 0) {
			var year = d.getFullYear();
			var mon = d.getMonth();
			var day = d.getDate();
			var max = d.getMonthDays(mon);
			if (day + days < max) {
				day += days;
				days = 0;
			} else {
				days -= (max - day + 1);
				day = 1;
				mon++;
				if (mon > 12) {
					mon = 1;
					year++;
				}
			}
			d = new Date(year, mon, day, 0, 0, 0);
		}
		return d.print('%Y-%m-%d');
	}

    var frm = SUGAR.ui.getForm('DetailForm'),
    	date_inp = SUGAR.ui.getFormInput(frm, 'due_date'),
    	date_start_inp = SUGAR.ui.getFormInput(frm, 'invoice_date') || SUGAR.ui.getFormInput(frm, 'bill_date'),
    	terms_inp = SUGAR.ui.getFormInput(frm, 'terms'),
    	date_start, term = 0;

    if (date_inp && date_start_inp && (date_start = date_start_inp.getValue(true)) && terms_inp) {
		switch (terms_inp.getValue()) {
			case 'COD':
			case 'Due on Receipt':
				term = 0;
				break;
			case 'Net 7 Days':
				term = 7;
				break;
			case 'Net 15 Days':
				term = 15;
				break;
			case 'Net 30 Days':
				term = 30;
				break;
			case 'Net 45 Days':
				term = 45;
				break;
			case 'Net 60 Days':
				term = 60;
				break;
		}
		date_inp.setValue(getDate(term, date_start));
	}
}

function has_taxes(line) {
	for (var i in line.adjusts) {
		if(line.adjusts[i].related_type == 'TaxRates')
			return true;
	}
	return false;
}

if(isset(Object.prototype.toJSON))
	delete Object.prototype.toJSON;

var exchange_rate_editor_defaults = {
	id: null,
	form_name: 'EditView',
	form: null,
	source_id: '',
	source: null,
	currency_field_name: 'currency_id',
	currency_field: null,
	rate_field_name: 'exchange_rate',
	rate_field: null,
	rate_field_unformatted: false,
	amount_field_info: [],
	amount_fields: null,
	allow_override: true,
	link_updates: true,
	update_callback: null
};

function resolve_callback(callback) {
	if(blank(callback))
		return false;
	if(typeof(callback) == 'string' && isset(window[callback]))
		return window[callback];
	else if(typeof(callback) == 'string') {
		return function() {
			eval(callback);
		}
	}
	else if(typeof(callback) == 'function')
		return callback;
	return false;
}

ExchangeRateEditor = {
	count: 0,
	active_editors: {},
	config: function(defaults) {
		for(var idx in defaults)
			exchange_rate_editor_defaults[idx] = defaults[idx];
	},
	setup: function(params) {
		if(! isset(params)) params = {};
		
		var editor = { inited: false, valid: false };
		
		for(var idx in exchange_rate_editor_defaults) {
			if(isset(params[idx]))
				editor[idx] = params[idx];
			else
				editor[idx] = exchange_rate_editor_defaults[idx];
		}
		
		editor.init = function() {
			if(! isset(this.custom_rates))
				this.custom_rates = {};
			if(! this.form && isset(this.form_name))
				this.form = document.forms[this.form_name];
			if(this.form && ! this.currency_field)
				this.currency_field = this.form[this.currency_field_name];
			if(this.form && ! this.rate_field)
				this.rate_field = this.form[this.rate_field_name];
			if(this.form && (! this.amount_fields || ! this.amount_fields.length)) {
				this.amount_fields = [];
				for(var idx = 0; idx < this.amount_field_info.length; idx++) {
					var field_info = this.amount_field_info[idx];
					var elt = null;
					var field_name = '';
					if(field_info.name)
						elt = this.form[field_name = field_info.name];
					if(! elt && field_info.id)
						elt = document.getElementById(field_name = field_info.id);
					if(elt) {
						var field = {};
						for(var jdx in field_info)
							field[jdx] = field_info[jdx];
						field.input = elt;
						if(! field.label) field.label = field_name;
						field.auto_update = get_default(field.auto_update, true);
						field.allow_update_disabled = get_default(field.allow_update_disabled, false);
						this.amount_fields.push(field);
					}
				}
				if(! this.amount_fields.length)
					this.amount_fields = null;
			}
			if(! this.source && this.source_id)
				this.source = document.getElementById(this.source_id);
			if(this.form && this.rate_field && ! this.override_field)
				this.override_field = this.form['override_exchange_rate['+this.rate_field.name+']'];
			else
				this.override_field = null;
			this.inited = true;
			this.valid = (this.source && this.currency_field && this.rate_field);
			if(this.valid) {
				this.currency_id = this.currency_field.value;
				this.set_custom_rate(this.rate_field.value);
			}
		}
				
		editor.make_caption_row = function(title) {
			var html = '<tr><td class="dataLabel" style="text-align: left; padding-left: 0.5em">'+title+'</td></tr>';
			return html;
		}
		
		editor.make_input_row = function(id, value, enabled, onchange, after) {
			var size = 10;
			var disabled = enabled ? '' : 'readonly="readonly"';
			var html = '<tr><td class="dataField" style="text-align: center">\n';
			html += '<input type="text" class="dataField" id="'+id+'" size="'+size+'" value="'+value+'" tabindex="99" onchange="'+onchange+'" '+disabled+'>\n';
			if(after) html += after;
			html += '</td></tr>';
			return html;
		}
		
		editor.show = function() {
			if(! this.inited)  this.init();
			if(! this.valid)  return false;

			var std_rate = this.get_standard_rate(true);
			var rate = this.get_custom_rate(true);
			
			this.last_input_rate = this.get_custom_rate(false);
			this.last_input_amounts = {};
			this.default_amounts = {};
			
			var allow_override = ! isset(this.allow_override) || this.allow_override;
			if((! this.currency_id || this.currency_id == '-99') && parseFloat(this.last_input_rate) == 1.0)
				allow_override = false;
			var content = '<table class="tabForm" style="margin: 0; padding: 0" width="180px" cellpadding="0" cellspacing="0" border="0">';
			var symbols = CurrencyData['-99'].symbol + ' &rarr; ' + CurrencyData[this.currency_id].symbol;
			content += this.make_caption_row(this.lbl_standard_rate + ' ' + symbols);
			content += this.make_input_row('ere_stnd_rate_'+this.id, std_rate, false, '');
			content += this.make_caption_row(this.lbl_custom_rate);
			var onchange = 'ExchangeRateEditor._update_input_rate(\'' + this.id + '\', this);';
			var show_link = (rate == std_rate ? 'none' : 'inline');
			var clear_onclick = 'ExchangeRateEditor._clear_input_rate(\'' + this.id + '\'); return false;';
			var clear_link = '';
			if(allow_override) {
				clear_link += '<a href="#" onclick="' + clear_onclick + '" style="display: ' + show_link + '" id="ere_clear_link_' + this.id + '">';
				clear_link += '<img src="' + this.image_path + this.img_clear_src + '" width="12" height="12" alt="' + this.clear_alt_text + '" align="absmiddle" border="0"></a>';
			}
			content += this.make_input_row('ere_cstm_rate_'+this.id, rate, allow_override, onchange, clear_link);

			var checked = this.link_updates ? 'checked' : '';
			var onclick = 'ExchangeRateEditor._set_link_updates(\'' + this.id + '\', this);';
			content += '<tr><td class="dataField" style="text-align: center; padding-top: 3pt">\n';
			content += '<input type="checkbox" class="checkbox" id="ere_link_updates_'+this.id+'" tabindex="99" onclick="'+onclick+'" '+checked+'>';
			content += '&nbsp;<span class="dataLabel">'+this.lbl_link_updates+'</span>';
			content += '</td></tr>';

			if(allow_override) {
				if(this.amount_fields) {
					for(var idx = 0; idx < this.amount_fields.length; idx++) {
						var field = this.amount_fields[idx];
						content += this.make_caption_row(field.label);
						var enabled = !(field.input.readOnly || field.input.disabled);
						if(field.allow_update_disabled)
							enabled = true;
						if(! field.auto_update)
							enabled &= this.link_updates;
						var onchange = 'ExchangeRateEditor._update_amount(\'' + this.id + '\', ' + idx + ', this);';
						content += this.make_input_row('ere_amount_input_'+this.id+'_'+idx, field.input.value, enabled, onchange);
						this.default_amounts[idx] = parseFloat(stdUnformatNumber(field.input.value));
						this.last_input_amounts[idx] = this.default_amounts[idx];
					}
				}
			
				content += '<tr><td class="dataField" style="text-align: center; padding-top: 3pt">\n';
				var onclick = 'ExchangeRateEditor._reset_values(\'' + this.id + '\')';
				content += '<input type="button" class="button" tabindex="99" onclick="'+onclick+'" value="'+this.lbl_reset+'">\n';
				var onclick = 'ExchangeRateEditor._commit_values(\'' + this.id + '\')';
				content += '&nbsp;<input type="button" class="button" tabindex="99" onclick="'+onclick+'" value="'+this.lbl_commit+'">\n';
				content += '</td></tr>';
			}
			content += '</table>';
			this.popup = SUGAR.popups.quickShow(content, this.editor_title, this.source, {close_on_exit: false});
			return this.popup;
		}
		
		editor.hide = function() {
			if(this.popup)
				SUGAR.popups.close(this.popup);
		}
		
		editor.create_source = function(parent) {
			if(isset(parent)) {
				this.source = document.createElement('span');
				this.source.className = 'exchangeRateSource';
				parent.appendChild(this.source);
			}
			else {
				var id = 'exc_rate_edit_src_' + this.id;
				var html = '<span className="exchangeRateSource" id="' + id + '"></span>';
				document.write(html);
				this.source = document.getElementById(id);
			}
			return this.source;
		}
		
		// get the rate defined by the system for this currency
		editor.get_standard_rate = function(formatted, currency_id) {
			if(! isset(currency_id))
				currency_id = this.currency_id;
			var rate = ConversionRates[currency_id];
			if(! rate)
				rate = 1.0;
			rate = parseFloat(rate).toFixed(10);
			if(isset(formatted) && formatted)
				return stdFormatNumber(rate, 10, 10);
			return rate;
		}
		
		// get the user's override value for this currency, or the standard rate
		editor.get_custom_rate = function(formatted, currency_id) {
			if(! isset(currency_id))
				currency_id = this.currency_id;
			var rate = this.custom_rates[currency_id];
			if(! rate)
				return this.get_standard_rate(formatted, currency_id);
			rate = parseFloat(rate).toFixed(10);
			if(isset(formatted) && formatted)
				rate = stdFormatNumber(rate, 10, 10);
			return rate;
		}
		
		editor.get_input_rate = function(formatted) {
			var rate = parseFloat(this.last_input_rate).toFixed(10);
			if(isset(formatted) && formatted)
				rate = stdFormatNumber(rate, 10, 10);
			return rate;
		}
		
		editor.set_custom_rate = function(rate, currency_id) {
			if(! isset(currency_id))
				currency_id = this.currency_id;
			if(! rate) {
				delete this.custom_rates[currency_id];
				rate = '';
			}
			else {
				var numrate = rate;
				if(typeof(rate) == 'string' && ! this.rate_field_unformatted)
					numrate = parseFloat(stdUnformatNumber(rate));
				else {
					numrate = parseFloat(rate);
					rate = stdFormatNumber(numrate.toFixed(10), 10, 10);
				}
				this.custom_rates[currency_id] = numrate;
			}
			if(currency_id == this.currency_id) {
				this.rate_field.value = this.rate_field_unformatted ? numrate.toFixed(10) : rate;
				if(this.override_field)
					this.override_field.value = '1';
			}
		}
		
		// redraw exchange rate icon (popup source)
		editor.update_source = function() {
			if(! this.inited)  this.init();
			if(! this.valid)  return false;
			this.source.innerHTML = '';
			var std_rate = parseFloat(this.get_standard_rate(false));
			var cst_rate = parseFloat(this.get_custom_rate(false));
			//if((! this.currency_id || this.currency_id == '-99') && cst_rate == 1.0)
			//	return false;
			var link = this.source_link = document.createElement('a');
			link.href = '#';
			link.onclick = function() {
				editor.show();
				return false;
			}
			link.title = this.editor_title;
			var img = this.source_icon = document.createElement('img');
			if(std_rate && cst_rate && Math.abs(std_rate - cst_rate)/(std_rate + cst_rate) < 0.005)
			//if(std_rate && cst_rate && Math.abs(std_rate - cst_rate) < 0.01)
				img.src = this.image_path + this.img_standard_src;
			else
				img.src = this.image_path + this.img_custom_src;
			img.width = img.height = 16;
			img.alt = this.source_alt_text;
			img.align = 'absmiddle';
			img.border = 0;
			link.appendChild(img);
			this.source.appendChild(link);
			return true;
		}
		
		// called when exchange_rate is updated in the popup
		editor.update_input_rate = function(new_rate) {
			if(! new_rate)
				new_rate = this.get_standard_rate(false);
			if(this.link_updates)
				this.convert_amounts(this.last_input_rate, new_rate);
			this.last_input_rate = new_rate;
			this.update_clear_link();
		}
		
		editor.update_clear_link = function() {
			var show_link = (this.get_input_rate() == this.get_standard_rate()) ? 'none' : 'inline';
			var link = document.getElementById('ere_clear_link_' + this.id);
			if(link)  link.style.display = show_link;
		}
		
		editor.update_input_amount = function(idx, amount) {
			var old_amount = this.last_input_amounts[idx];
			this.last_input_amounts[idx] = amount;
			if(old_amount && this.link_updates) {
				var new_rate = this.last_input_rate * amount / old_amount;
				this.convert_amounts(this.last_input_rate, new_rate, false, idx);
				this.last_input_rate = new_rate;
				var rate_inp = document.getElementById('ere_cstm_rate_' + this.id);
				if(rate_inp) {
					rate_inp.numvalue = new_rate;
					rate_inp.value = stdFormatNumber(new_rate.toFixed(10), 10, 10);
					this.update_clear_link();
				}
			}
		}
		
		// called when currency_id/exchange_rate fields are updated programmatically
		// does not try to adjust amounts
		editor.resync = function() {
			if(! this.inited)  this.init();
			if(! this.valid)  return false;
			this.currency_id = this.currency_field.value;
			this.set_custom_rate(this.rate_field.value);
			this.hide(); // just in case
			this.update_source();
		}
		
		// called when the user changes the currency, adjusts amounts when updates are linked
		editor.update_currency = function(update_rate) {
			if(! this.inited)  this.init();
			if(! this.valid)  return false;
			this.old_rate = this.get_custom_rate(false, this.currency_id);
			this.currency_id = this.currency_field.value;
			if(isset(update_rate) && update_rate)
				this.set_custom_rate(this.rate_field.value);
			var new_rate = this.get_custom_rate(false);
			this.last_input_rate = new_rate;
			this.rate_field.value = this.rate_field_unformatted ? new_rate : stdFormatNumber(new_rate, 10, 10);
			if(this.link_updates)
				this.convert_amounts(this.old_rate, new_rate, true);
			this.do_update_callback(this.old_rate, new_rate);
			this.hide(); // just in case
			this.update_source();
		}
				
		editor.do_update_callback = function(old_rate, new_rate) {
			var update_callback = resolve_callback(this.update_callback);
			if(update_callback)
				update_callback(old_rate, new_rate, this);
		}
		
		editor.convert_amounts = function(old_rate, new_rate, real_amounts, skipindex) {
			if(! isset(real_amounts)) real_amounts = false;
			if(this.amount_fields) {
				for(var idx = 0; idx < this.amount_fields.length; idx++) {
					if(isset(skipindex) && idx == skipindex)
						continue;
					var field_info = this.amount_fields[idx];
					var field;
					if(real_amounts) {
						if(! field_info.auto_update)
							continue;
						field = this.amount_fields[idx].input;
						if(field && (field.readOnly || field.disabled) && ! field_info.allow_update_disabled)
							continue;
					}
					else
						field = document.getElementById('ere_amount_input_'+this.id+'_'+idx);
					if(! field || ! field.value)
						continue;
					var old_value = parseFloat(stdUnformatNumber(field.value));
					var new_value = (old_value * new_rate / old_rate);
					if(! real_amounts && field_info.calc_callback) {
						var calc_callback = resolve_callback(field_info.calc_callback);
						if(calc_callback) {
							var chk_new_value = calc_callback(old_rate, new_rate, this, idx);
							if(isset(chk_new_value))
								new_value = parseFloat(chk_new_value);
						}
					}
					var decimals = isset(editor.amount_fields[idx].decimals) ? editor.amount_fields[idx].decimals : editor.currency_decimal_places();
					new_value = new_value.toFixed(decimals);
					field.value = stdFormatNumber(new_value, decimals, decimals);
					if(! real_amounts)
						this.last_input_amounts[idx] = new_value;
				}
			}
		}
		
		editor.reset_values = function() {
			this.last_input_rate = this.get_custom_rate(false);
			var rate = document.getElementById('ere_cstm_rate_'+this.id);
			if(rate)
				rate.value = stdFormatNumber(this.last_input_rate, 10, 10);
			if(this.amount_fields) {
				for(var idx = 0; idx < this.amount_fields.length; idx++) {
					var amt = document.getElementById('ere_amount_input_'+this.id+'_'+idx);
					if(amt)
						amt.value = this.amount_fields[idx].input.value;
				}
			}
			this.update_clear_link();
		}
		
		editor.commit_values = function() {
			if(this.last_input_rate) {
				this.old_rate = this.get_custom_rate(false);
				this.set_custom_rate(parseFloat(this.last_input_rate));
				if(this.amount_fields) {
					for(var idx = 0; idx < this.amount_fields.length; idx++) {
						var amt = document.getElementById('ere_amount_input_'+this.id+'_'+idx);
						if(amt && this.amount_fields[idx].auto_update)
							this.amount_fields[idx].input.value = amt.value;
					}
				}
				this.do_update_callback(this.old_rate, this.get_custom_rate(false));
			}
			this.hide();
			this.update_source();
		}
		
		editor.set_link_updates = function(linked) {
			this.link_updates = linked ? true : false;
			if(this.amount_fields) {
				for(var idx = 0; idx < this.amount_fields.length; idx++) {
					var edit_field = document.getElementById('ere_amount_input_'+this.id+'_'+idx);
					if(! edit_field)  continue;
					var field = this.amount_fields[idx];
					var enabled = !(field.input.readOnly || field.input.disabled);
					if(field.allow_update_disabled)
						enabled = true;
					if(! field.auto_update && ! this.link_updates) {
						enabled = false;
						edit_field.value = field.input.value;
					}
					edit_field.readOnly = ! enabled;
				}
			}
		}
		
		editor.currency_decimal_places = function() {
			if(this.currency_field) {
				return CurrencyDecimalPlaces(this.currency_field.value);
			}
			return currency_significant_digits;
		}
		
		this.count ++;
		if(! editor.id)
			editor.id = this.count;
		this.active_editors[editor.id] = editor;
		
		return editor;
	},
	
	show: function(id) {
		if(this.active_editors[id]) {
			this.active_editors[id].show();
			return true;
		}
		return false;
	},
	hide: function(id) {
		if(this.active_editors[id]) {
			this.active_editors[id].hide();
			return true;
		}
		return false;
	},
	display_sources: function() {
		for(var id in this.active_editors) {
			this.active_editors[id].update_source();
			this.active_editors[id].hide(); // just in case
		}
	},
	resync: function(id) {
		var editor = this.active_editors[id];
		if(editor)  editor.resync();
	},
	update_currency: function(id, update_rate) {
		var editor = this.active_editors[id];
		if(editor)  editor.update_currency(update_rate);
	},

	get_currency_field: function(id) {
		var editor = this.active_editors[id];
		if(editor)  return editor.currency_field;
	},

	get_rate: function(id, formatted) {
		var rate = '1.00000';
		var editor = this.active_editors[id];
		if(editor)  rate = editor.get_custom_rate(formatted);
		else {
			if(formatted) rate = stdFormatNumber(rate, 10, 10);
		}
		return rate;
	},
	convert_from_dollar: function(id, amt) {
		var rate = this.get_rate(id);
		var conv = amt * rate;
		return conv;
	},
	convert_to_dollar: function(id, amt) {
		var rate = this.get_rate(id);
		var conv = amt / rate;
		return conv;
	},
	get_currency_decimal_places: function(id) {
		var editor = this.active_editors[id];
		if(editor) return editor.currency_decimal_places();
	},
	_set_link_updates: function(id, chk) {
		var editor = this.active_editors[id];
		if(editor) editor.set_link_updates(chk.checked);
	},
	_update_override: function(id, override) {
		var editor = this.active_editors[id];
		if(editor && editor.override_field)
			editor.override_field.value = override ? '1' : '0';
	},
	_clear_input_rate: function(id) {
		var input = document.getElementById('ere_cstm_rate_' + id);
		if(input) {
			input.value = '';
			this._update_input_rate(id, input);
		}
	},
	_update_input_rate: function(id, input) {
		var editor = this.active_editors[id];
		if(editor) {
			numvalue = validateNumberInput(input, { defaultNumValue: editor.get_standard_rate(false), decimalPlaces: 5 });
			editor.update_input_rate(numvalue);
		}
	},
	_update_amount: function(id, amt_idx, input) {
		var editor = this.active_editors[id];
		if(editor && editor.amount_fields && editor.amount_fields[amt_idx]) {
			var field_info = editor.amount_fields[amt_idx];
			var decimals = isset(field_info.decimals) ? field_info.decimals : editor.currency_decimal_places();
			numvalue = validateNumberInput(input, { blankAllowed: true, decimalPlaces: decimals });
			editor.update_input_amount(amt_idx, numvalue);
		}
	},
	_commit_values: function(id) {
		var editor = this.active_editors[id];
		if(editor)  editor.commit_values();
	},
	_reset_values: function(id) {
		var editor = this.active_editors[id];
		if(editor)  editor.reset_values();
	}
};


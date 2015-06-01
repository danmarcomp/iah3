// vim: set autoindent smartindent :
/*****************************************************************************
 * The contents of this file are subject to The Long Reach Corporation
 * Software License Version 1.0 ("License"); You may not use this file except
 * in compliance with the License. You may obtain a copy of the License at
 * <http://www.thelongreach.com/swlicense.html>
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations under
 * the License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) The Long Reach Corporation copyright notice,
 * (ii) the "Powered by SugarCRM" logo, and
 * (iii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is:
 *    Info At Hand Add-on Module to SugarCRM Open Source project.
 * The Initial Developer of this Original Code is The Long Reach Corporation
 * and it is Copyright (C) 2004-2007 by The Long Reach Corporation;
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2005 SugarCRM, Inc.;
 * All Rights Reserved.
*****************************************************************************/

WorkflowEditor = new function() {
	this.id = 'WorkflowEditor';
	this.uid = 0;
	this.trigger_module = '';
	var params = {
		options: {
			a : 'aaaa',
			b : 'bbb'
		}
	};

	this.editable = false;
	this.registered_options = {};
	this.operations = [];
	this.conditions = [];
	this.groupBoxes = {};
	this.no_templates = {options: {'':''}, values : {}};
	this.has_related = false;
	
	this.criteria_field_types = {
		'int': 'numeric', 'number': 'numeric', 'float': 'numeric', 'currency': 'numeric', 'double' : 'numeric',
		'email': 'text', 'phone': 'text', 'relate': 'text', 'char': 'text', 'varchar': 'text', 'text': 'text',
		'date': 'date', 'datetime': 'date',
		'name': 'text',
		'enum': 'enum',
		'bool': 'bool',
		'user_name': 'user',
		'ref': 'ref',
		'assigned_user_name': 'user'
	};

	this.criteria_operator_options = {
		'numeric':	['', 'num_is_equal_to', 'num_is_not_equal_to', 'num_is_less_than', 'num_is_not_less_than', 'num_is_greater_than', 'num_is_not_greater_than', 'does_not_change'],
		'text':	['', 'txt_is_equal_to', 'txt_is_not_equal_to', 'txt_contains', 'txt_not_contain', 'txt_begins_with', 'txt_ends_with', 'does_not_change'],
		'date':	['', 'dat_is_equal_to', 'dat_before', 'dat_after', 'dat_within', 'dat_not_within', 'dat_relative_before', 'dat_relative_after', 'does_not_change'],
		'bool':	['', 'bol_is_false', 'bol_is_true', 'does_not_change'],
		'user' : ['', 'user_is', 'user_is_not'],
		'ref' : ['', 'ref_is', 'ref_is_not'],
		'enum':	['', 'enum_one_of', 'does_not_change']
	}
	
	this.date_relative_operators = ['dat_within', 'dat_not_within', 'dat_relative_before', 'dat_relative_after'];

	this.primary_email_options = [
		{
			'modules' : ['Contacts'],
			'primary' : 'LBL_INCLUDE_CONTACT_EMAIL'
		},
		{
			'modules' : ['Leads'],
			'primary' : 'LBL_INCLUDE_LEAD_EMAIL'
		},
		{
			'modules' : ['Cases'],
			'primary' : 'LBL_INCLUDE_CONTACTS_EMAIL'
		},
		{
			'modules' : ['Accounts', 'SubContracts'],
			'primary' : 'LBL_INCLUDE_ACCOUNT_EMAIL'
		},
		{
			'modules' : ['Invoice', 'Quotes', 'SalesOrders', 'PurchaseOrders', 'Bills', 'Shipping', 'Receiving'],
			'primary' : 'LBL_INCLUDE_ACCOUNT_EMAIL',
			'secondary' : 'LBL_INCLUDE_CONTACT_EMAIL'
		}
	];

	this.minutesOptions = {
		'0' : '00',
		'15': '15',
		'30': '30',
		'45': '45'
	};
	
	this.init = function(params)
	{
		this.editable = params.editable;
		var conditions = params.conditions || {};
		this.conditions = conditions.conditions || [];
		var operations = params.operations || {};
		this.operations = operations.operations || [];
		this.all_fields = params.fields || {};
		this.trigger_module = params.trigger_module;
		this.bean_fields = this.all_fields[this.trigger_module];
		this.groupBoxes = params.groupBoxes || {};
		this.templates = params.templates || this.no_templates;
		this.template_fields = params.template_fields;
		this.occurs_when = params.occurs_when;
		for (var i in this.all_fields) {
			if (i != this.trigger_module) {
				this.has_related = true;
				break;
			}
		}
		for (var i =0; i < this.operations.length; i++) {
			for (var j in this.operations[i]) {
				if (this.operations[i][j] === null)
					this.operations[i][j] = '';
			}
		}
		for (var i =0; i < this.conditions.length; i++) {
			for (var j in this.conditions[i]) {
				if (this.conditions[i][j] === null)
					this.conditions[i][j] = '';
			}
		}
	};

	this.setup = function()
	{
		if (this.editable) {
			var x = SUGAR.ui.getFormInput(this.form, 'occurs_when');
			x.onchange = this.occursWhenChanged;
			this.setTriggerState(this.form.occurs_when.value);
			var trigger = SUGAR.ui.attachFormInputEvent(this.form, 'trigger_module', 'onchange',
				function(k, v, ch) {
					if (ch)  {
						WorkflowEditor.form.trigger_module.value = k;
						return SUGAR.ui.sendForm(WorkflowEditor.form, {'record_perform':'edit'}, {no_validate: true});
					}
				});
		}

		
		this.renderCriteria();
		this.renderOperations();
	};

	this.beforeSubmitForm = function()
	{
		this.readHTMLEditors();
		var ret = {
			operations: this.operations,
			conditions : this.conditions
		};
	    this.form.workflow_items.value = JSON.stringify(ret);
	};


	this.renderCriteria = function()
	{
		this.clearTable('workflow_conditions');
		var table = $('workflow_conditions');
		var conds = this.conditions;
		for (var i in conds) {
			var q = parseInt(i);
			if (isNaN(q)) continue;
			var criteria = conds[i];
			
			// if the criteria is empty, default to first bean field
			if (criteria.field_name == "") {
				for (var m in this.bean_fields) {
					var def = this.bean_fields[m];
					criteria.field_name = def['name'];
					break;
				}
			}
			
			if (!isset(criteria.field_type)) {
				criteria.field_type = this.lookupFieldType(criteria.field_name);
			}
			if (table.rows.length > 0) {
				this.addGlue(table, i);
			}
			var tr = table.insertRow(table.rows.length);
			var cell_count = 0;
			
			fieldTd = tr.insertCell(cell_count++);
			fieldTd.className = "dataLabel"; 
			fieldTd.width = "30%";
			fieldTd.appendChild(this.makeFieldSelection(criteria, i));
			fieldTd.style.paddingLeft = (1 + get_default(criteria.level, 0) * 10) + 'px';
			operatorTd = tr.insertCell(cell_count++);
			operatorTd.className = "dataLabel"; 
			operatorTd.width = "20%";
			operatorTd.appendChild(this.makeOperatorSelection(criteria, i));

			valueTd = tr.insertCell(cell_count++);
			valueTd.className = "dataLabel"; 
			valueTd.width = "40%";
			if (criteria.operator != 'does_not_change' && criteria.operator) {
				var val = this.makeValueField(criteria, i, valueTd);
				if (val) valueTd.appendChild(val);
			} else {
				valueTd.appendChild(nbsp());
			}
			
			var timeTd = tr.insertCell(cell_count++);
			timeTd.className = "dataLabel"; 
			timeTd.width = "10%";
			if (this.occurs_when == 'time' && criteria.operator) {
				timeTd.appendChild(this.makeTimeSelection(criteria, i));
			} else {
				timeTd.appendChild(nbsp());
			}

			buttonTd = tr.insertCell(cell_count++);
			buttonTd.className = "dataLabel"; 
			buttonTd.width = "10%";
			buttonTd.appendChild(this.makeButtons(i));
		}
	}

	this.addGlue = function(table, idx) {
		var tr = table.insertRow(table.rows.length);
		var cell_count = 0;
		var level = this.conditions[idx].level;
		for (var i in this.conditions) {
			var q = parseInt(i);
			if (isNaN(q)) continue;
			if (q >= idx) break;
			if (this.conditions[q].level <= this.conditions[idx].level) {
				level = this.conditions[q].level;
			}
		}
		
		var td = tr.insertCell(cell_count++);
		td.className = "dataLabel"; 
		td.width = "30%";
		td.style.paddingLeft = (1 + level * 10) + 'px';

		var glues = new SUGAR.ui.SelectOptions(mod_list_strings('GLUES'));
		var attrs = {
			onchange : function(k, v, ch) {if (ch) WorkflowEditor.conditions[idx].glue = k;},
			max_width : '150px'
		};
		if (!this.conditions[idx].glue) this.conditions[idx].glue = 'AND';
		var sel = this.createSelectInput2( glues, null, this.conditions[idx].glue, attrs);
		td.appendChild(sel);
	}

	this.clearTable = function(id)
	{
		var table = $(id);
		if (!table) return;
		while(table.rows.length > 0)
			table.deleteRow(0);
	};

	this.lookupFieldType = function(name, module)
	{
		var type = 'text';
		if (!module) module = this.trigger_module;
		if (this.all_fields[module][name])
			if (this.criteria_field_types[this.all_fields[module][name].type])
				type = this.criteria_field_types[this.all_fields[module][name].type];
		return type;
	};

	this.makeFieldSelection = function(criteria, idx)
	{
		var values = [];
		var labels = {};
		for (var i in this.bean_fields) {
			var fld = this.bean_fields[i];
			values.push(i);
			labels[i] = mod_string(fld.vname, this.trigger_module);
		}
		var attrs = {
			max_width : '400px',
			onchange : function(k, v, ch) {
				if (ch) {
					WorkflowEditor.checkOperatorCompatability(idx, k);
					WorkflowEditor.conditions[idx].field_name = k;
					WorkflowEditor.conditions[idx].field_type = WorkflowEditor.lookupFieldType(k);
					WorkflowEditor.renderCriteria();
				}
			}
		};
		var select = this.createSelectInput2(values, labels, criteria.field_name, attrs);
		return select;
	};

	this.makeUpdateCurFieldSelection = function(idx, valueCell, module)
	{
		var values = [];
		var labels = {};
		if (!this.all_fields[module]) return nbsp();
		for (var i in this.all_fields[module]) {
			var fld = this.all_fields[module][i];
			if (fld.type == 'ref') continue;
			values.push(i);
			labels[i] = mod_string(fld.vname, module);
		}
		var attrs = {
			width: '200px',
			onchange : function(k, v, ch) {
				if (ch) {
					WorkflowEditor.operations[idx].dm_field_name = k;
					WorkflowEditor.operations[idx].dm_field_value = '';
					WorkflowEditor.makeUpdateCurField(idx, valueCell);
				}
			}
		};
		var select = this.createSelectInput2(values, labels, this.operations[idx].dm_field_name, attrs);
		return select;
	};

	this.makeOperatorSelection = function(criteria, idx)
	{
		var values = [];
		var labels = {};
		var optTypes = this.criteria_operator_options[criteria.field_type];
		if (typeof(optTypes) != "undefined") {
			for (var i=0; i < optTypes.length; i++ ) {
				var value = optTypes[i];
				if (this.editable) {
					if (this.occurs_when != 'time' && value == 'does_not_change') continue;
				}
				values.push(value);
				labels[value] = mod_string('LBL_OPT_' + value);
			}
		}
		attrs = {
			max_width: '250px',
			onchange: function() {
				WorkflowEditor.conditions[idx].operator = this.getValue();
				WorkflowEditor.renderCriteria();
				if (this.getValue() == 'bol_is_true')
					WorkflowEditor.conditions[idx].field_value = true;
				else if (this.getValue() == 'bol_is_false')
					WorkflowEditor.conditions[idx].field_value = false;
			}
		};
		var select = this.createSelectInput2(values, labels, criteria.operator, attrs);
		return select;
	};

	this.makeOperationSelection = function(idx)
	{
		var op = this.operations[idx],
			options_dom = SUGAR.language.getList('workflow_actions_dom'),
			keys = deep_clone(options_dom.keys || []),
			values = deep_clone(options_dom.values || {});
		if (this.occurs_when == 'time') {
			var ix = keys.indexOf('showAlert');
			if (ix >= 0)  {
				keys.splice(ix, 1);
				values.splice(ix, 1);
			}
		}
		if (! this.has_related) {
			var ix = keys.indexOf('updateRelatedData');
			if (ix >= 0)  {
				keys.splice(ix, 1);
				values.splice(ix, 1);
			}
		}
		var attrs = {
			max_width: '250px',
			onchange : function(k,v, ch) {
				if (ch) {
					WorkflowEditor.operations[idx] = WorkflowEditor.operationTemplate();
					WorkflowEditor.operations[idx].operation_type = k;
					WorkflowEditor.renderOperations();
				}
			}
		};
		return this.createSelectInput2(keys, values, op.operation_type, attrs);

	};

	this.makeValueField = function(criteria, idx, par)
	{
		if (criteria.operator == 'does_not_change' || criteria.operator == '') {
			return nbsp();
		}
		var field_type = this.lookupFieldType(criteria.field_name);
		if (field_type == 'enum') {
			this.makeEnumValueField(criteria, idx, par);
			return false;
		} else if (field_type == 'date') {
			return this.makeDateValueField(criteria, idx);
		} else if (field_type == 'ref') {
			return this.makeRefField(criteria, idx);
		} else {
			attrs = {
				style: 'width:20em',
				value: criteria.field_value,
				onchange : function() {
					criteria.field_value = this.value;
				}
			};
			return this.makeTextInput(attrs);
		}
	};

	this.makeUpdateCurField = function(idx, par)
	{
		var op = this.operations[idx];
		var node = nbsp();
		var module = this.trigger_module;
		if (op.dm_module_name && op.dm_module_name != '') module = op.dm_module_name;
		par.innerHTML = '';
		if (op.dm_field_name != '') {
			var field_type = this.lookupFieldType(op.dm_field_name, module);
			if (field_type == 'enum') {
				node = this.makeUpdateFieldSelector(op);
			} else if (field_type == 'date') {
				var attrs = {
					onchange: function() {
						op.dm_field_value = this.getValue();
					}
				};
				var baseId = 'workflow-action-value-' + idx;
				node = this.makeDateInput(baseId, op.dm_field_value, attrs);
			} else if (field_type == 'bool') {
				var options = {
					keys: ['true', 'false'],
					values: [
						mod_string('LBL_DM_FIELD_VALUE_TRUE'),
						mod_string('LBL_DM_FIELD_VALUE_FALSE')
					]};
				var attrs = {
					max_width:'200px',
					onchange : function(k,v,ch) {
						op.dm_field_value = k;
					}
				};
				node = this.createSelectInput2(options, null, op.dm_field_value, attrs);
			} else {
				attrs = {
					style: 'width:20em',
					value: op.dm_field_value,
					onchange : function() {
						op.dm_field_value = this.value;
					}
				};
				node = this.makeTextInput(attrs);
			}
		}
		par.appendChild(node);
	};

	this.makeUpdateFieldSelector = function(op)
	{
		var optionsName = this.registerOptions(op.dm_field_name, true, op.dm_module_name);
		var attrs = {
			max_width: '400px',
			onchange : function(k, v, ch) {
				op.dm_field_value = k;
			}
		};
		return this.createSelectInput2(app_list_strings(optionsName), null, op.dm_field_value, attrs);
	};

	this.registerOptions = function (fieldName, remove_blank, module)
	{
		var name = '';
		if (!module || module == '') module = this.trigger_module;
		var field_list = this.all_fields[module];
		if (field_list[fieldName]) {
			if (field_list[fieldName].options) {
				name = field_list[fieldName].options;
				var options = SUGAR.language.get('app_list_strings', name);
				if (remove_blank && typeof(options['']) != 'undefined') {
					name += '_no_blank';
				}
				if (!this.registered_options[name]) {
					var keys = [];
					var values = [];
					for (var k in options) {
						if (remove_blank && k == '') continue;
						keys.push(k);
						values.push(options[k]);
					}
					SUGAR.ui.registerSelectOptions(name, {keys:keys,values:values, maxlen:20,width:"20em"});
					this.registered_options[name] = true;
				}
			}
		}
		return name;
	};

	this.makeEnumValueField = function(criteria, idx, par)
	{
		var baseId = 'workflow-condition-value-' + idx;

		var inputValue = '';
		var outer = createElement2('div');
		try {
			var selected = JSON.parse(criteria.field_value);
			if (selected.join) inputValue = selected.join('^,^');
		} catch(e) {
		}
		var input = createElement2('input', {
			type:'hidden',
			name: baseId,
			value: inputValue
		});
		outer.appendChild(input);
		var inner = createElement2('div', {className:'input-select-multi input-outer', id: baseId + '-input'});
		outer.appendChild(inner);
		var scroll = createElement2('div', {className:'input-scroll select-inner', id: baseId + '-input-scroll'});
		inner.appendChild(scroll);

		var optionsName = this.registerOptions(criteria.field_name, true);
		par.appendChild(outer);
		var attrs = {
			onchange: function() {
				var selectionJSON = JSON.stringify(this.getSelectedKeys());
				WorkflowEditor.conditions[idx].field_value = selectionJSON;
			},
			options: optionsName,
			name: baseId,
			multi_select: true,
			rows: 3
		};

		var list = new SUGAR.ui.SelectList(baseId + '-input', attrs);
		SUGAR.ui.registerInput(this.form, list);
		list.setup();
		list.setSelectedKeys
		return outer;
	};

	this.makeDateValueField = function(criteria, idx)
	{
		if (this.date_relative_operators.includes(criteria.operator)) {
			var times = new SUGAR.ui.SelectOptions(mod_list_strings('OPTIONS_TIME_CONDITION'));
			attrs = {
				max_width: '250px',
				onchange: function() {
					WorkflowEditor.conditions[idx].field_value = this.getValue();
				}
			};
			var select = this.createSelectInput2(times, null, criteria.field_value, attrs);
			var container = createElement2('span');
			container.appendChild(select);
			container.appendChild(nbsp(2));
			container.appendChild(document.createTextNode(mod_string('FROM_NOW')));
			return container;
		} else {
			var baseId = 'workflow-condition-value-' + idx;
			var attrs = {
				onchange : function() {
					WorkflowEditor.conditions[idx].field_value = this.getValue();
				}
			};
			return this.makeDateInput(baseId, this.conditions[idx].field_value, attrs);
		}
	};

	this.makeDateInput = function(baseId, value, attrs)
	{
		if (!this.editable) return document.createTextNode(value);
		var outer = createElement2('div', {className:'input-complex input-outer', id: baseId});
		var dateDiv = createElement2('div', {className: 'input-part sep-right'});
		outer.appendChild(dateDiv);
		var dateInput = createElement2('input', {
			type:'text',
			className: 'input-text input-entry',
			size:11,
			id: baseId + '-date',
			'value' : value
		});
		dateDiv.appendChild(dateInput);
		var button = createElement2('button', {className: 'input-button compact', type:'button', id: baseId + '-date-sel'});
		outer.appendChild(button);
		var icon = createElement2('div', {className: 'input-icon icon-calendar'});
		button.appendChild(icon);
		attrs.field = dateInput;
		attrs.button = button;
		var d = new SUGAR.ui.DateInput(outer, attrs);
		d.setup();
		return outer;
	};

	this.makeRefField = function(criteria, idx)
	{
		var field = this.bean_fields[criteria.field_name];
		if (!field) return nbsp();
		if (field.type != 'ref') return nbsp();
		var baseId = 'workflow-condition-value-' + idx;
		attrs = {
			disabled : !this.editable,
			onchange : function() {
				WorkflowEditor.conditions[idx].field_value = this.getKey();
				WorkflowEditor.conditions[idx].field_value_name = this.getValue();
			}
		};
		return this.makeRefInput(field.ref_module_dir, baseId, criteria.field_value, criteria.field_value_name, attrs);
	};
	
	this.makeRefInput = function(module, baseId, key, value, attrs)
	{

		var outer = createElement2('div', {id: baseId});

		if(typeof(attrs) != 'object')  attrs = {};
	    attrs.field_name = baseId + '-name';
	    attrs.field_id = baseId + '-name';
	    attrs.init_key = key;
		attrs.init_value = value;
		attrs.module = module;
		attrs.form = this.form;
	    var input = new SUGAR.ui.RefInput(attrs.field_id, attrs);
		input.setDisabled(!this.editable);
	    SUGAR.ui.registerInput(this.form, input);
	    return input.render();
	};
	
	this.lookupFieldOptions = function(field_name)
	{
		var options = {};
		var field_list = this.bean_fields;
		for (var i in field_list) {
			if (field_list[i]['name'] == field_name) {
				var opts_name = field_list[i]['options'];
				options = SUGAR.language.get('app_list_strings', opts_name);
				if(typeof(options) != 'object')
					options = {};
				break;
			}
		}
		return options;
	}
	
	this.makeTimeSelection = function(criteria, idx)
	{

		var times = new SUGAR.ui.SelectOptions(mod_list_strings('OPTIONS_TIME'));
		if (!criteria.time_interval) criteria.time_interval = '1h';
		attrs = {
			max_width: '250px',
			onchange: function() {
				WorkflowEditor.conditions[idx].time_interval = this.getValue();
			}
		};
		var select = this.createSelectInput2(times, null, criteria.time_interval, attrs);
		return select;
	};
	
	this.moveLeft = function(idx) {
		if (this.conditions[idx].level > 0) {
			this.conditions[idx].level--;
			this.renderCriteria();
		}
	}

	this.moveRight = function(idx) {
		var max = -1;
		for (var i in this.conditions) {
			if (isNaN(parseInt(i))) continue;
			if (i >= idx) break;
			if (i < idx) {
				max = this.conditions[i].level;
			}
		}
		if (this.conditions[idx].level <= max) {
			this.conditions[idx].level++;
			this.renderCriteria();
		}
	}

	this.insertCriteria = function(idx) {
		newCond = {
			"field_name": "",
			"operator": "",
			"field_value": "",
			"level" : 0,
			"glue" : 'AND'
		};
		if (idx) {
			var i = 0;
			var replace = [];
			for (var ptr in this.conditions) {
				if (isNaN(parseInt(ptr))) continue;
				if (ptr == idx) {
					replace[i] = newCond;
					i++;
				}
				replace[i] = this.conditions[ptr];
				i++;
			}
			this.conditions = replace;
		} else {
			this.conditions.push(newCond);
		}

		this.renderCriteria();
	}

	this.makeButtons = function(idx) {
		if (!this.editable) return nbsp();
		var table = createElement2('table', { border :0, cellpadding : 0, cellspacing : 0, width : '100%'});
		var rowCount = 0;
		
		var tr = table.insertRow(0);

		var leftBtn = get_icon_image('left');
		leftBtn.style.cursor = 'pointer';
		leftBtn.onclick = function() { WorkflowEditor.moveLeft(idx); }
		td = tr.insertCell(0);
		td.className = 'dataField'; td.width = '25%';
		td.appendChild(leftBtn);

		var rightBtn = get_icon_image('right');
		rightBtn.style.cursor = 'pointer';
		rightBtn.onclick = function() { WorkflowEditor.moveRight(idx); }
		td = tr.insertCell(1);
		td.className = 'dataField'; td.width = '25%';
		td.appendChild(rightBtn);

		var plusBtn = get_icon_image('insert');
		plusBtn.style.cursor = 'pointer';
		plusBtn.onclick = function() { WorkflowEditor.insertCriteria(idx); }
		
		td = tr.insertCell(2);
		td.className = 'dataField'; td.width = '25%';
		td.appendChild(plusBtn);

		
		var minusBtn = get_icon_image('remove');
		minusBtn.style.cursor = 'pointer';
		minusBtn.onclick = function() {
			var del_level = WorkflowEditor.conditions[idx].level;
			for (var i =0; i < WorkflowEditor.conditions.length; i++) {
				if (i <= idx) continue;
				if (WorkflowEditor.conditions[i].level <= del_level) break;
				WorkflowEditor.conditions[i].level--;
			}
			WorkflowEditor.conditions.splice(idx, 1);
			WorkflowEditor.renderCriteria();
			return false;
		}
		
		td = tr.insertCell(3);
		td.className = 'dataField'; td.width = '25%';
		td.appendChild(minusBtn);
		
		return table;
	}

	this.occursWhenChanged = function(k, v, ch)
	{
		if (ch) {
			WorkflowEditor.conditions = [];
			WorkflowEditor.operations = [];
			WorkflowEditor.renderCriteria();
			WorkflowEditor.renderOperations();
			WorkflowEditor.setTriggerState(k);
		}
	};

	this.setTriggerState = function(k)
	{
		var action = SUGAR.ui.getFormInput(this.form, 'trigger_action');
		this.occurs_when = k;
		if (k == 'time') {
			action.menu.setSelectedKey('saved');
			this.form['DetailFormtrigger_action-input'].disabled = true;
		} else {
			this.form['DetailFormtrigger_action-input'].disabled = false;
		}
	};

	this.checkOperatorCompatability = function(idx, newFieldName)
	{
		var oldType = this.conditions[idx].field_type;
		var newType = this.lookupFieldType(newFieldName);
		if (oldType != newType) {
			this.conditions[idx].operator = '';
			this.conditions[idx].field_value = '';
			this.conditions[idx].field_value_name = '';
		}
	};

	this.checkValueCompatability = function(idx, newOp)
	{
	};

	this.createSelectInput2 = function(values, labels, value, attrs) {
		var id = 'workflow_editor_id_' + this.uid++;
		if (!attrs) attrs = {};
		if(! blank(attrs.readOnly)) {
			delete attrs.readOnly;
			attrs.disabled = true;
		}
		var outer, options;
		//if(! attrs.width) attrs.width = '200px';
		attrs.init_value = value;
		if(labels)
			options = {keys: values, values: labels};
		else if(values instanceof SUGAR.ui.SelectOptions)
			options = values;
		else {
			options = {keys: [], values: values};
			for (var k in values)
				options.keys.push(k+'');
		}
		if(attrs.max_width) {
			options.width = attrs.max_width;
		}
		attrs.options = options;
		
		var quicksel = new SUGAR.ui.SelectInput(id, attrs);
		//SUGAR.ui.registerInput(this.form, quicksel);

		if (!this.editable) {
			quicksel.menu.setup();
			outer = document.createTextNode(quicksel.getOptionValue());
			outer = createElement2('span', {className: "list-edit-value disabled"}, outer);
		}
		else {
			outer = quicksel.render();
		}

		return outer;
	};

	this.insertOperation = function(idx) {
		this.operations.push(this.operationTemplate());
		this.renderOperation(this.operations.length-1);
	}

	this.renderOperations = function()
	{
		this.readHTMLEditors();
		this.clearTable('workflow_operations');
		for (var i=0; i < this.operations.length; i++) {
			this.renderOperation(i);
		}
	};
		
	this.readHTMLEditors = function()
	{
		for(var idx = 0; idx < this.operations.length; idx++) {
			var inp = SUGAR.ui.getFormInput(this.form, 'body_html_' + idx);
			if(inp) {
				if(! YDom.inDocument(inp.elt)) {
					inp.destroy();
					SUGAR.ui.unregisterInput(this.form, inp);
				} else if (!this.operations[idx].no_content_update)
					this.operations[idx].notification_content = inp.getValue();
			}
			delete (this.operations[idx].no_content_update);
		}
	};

	this.renderOperation = function(idx)
	{
		var table = $('workflow_operations');
		if (idx > 0) {
			var tr = table.insertRow(table.rows.length);
			this.makeCell(tr, {colSpan:6}, createElement2('hr'));
		}
		this.renderOperationHeader(idx);
		this.renderOperationDetails(idx);
	};

	this.renderOperationHeader = function(idx)
	{
		var op = this.operations[idx];
		var table = $('workflow_operations');
		var tr = table.insertRow(table.rows.length);
		
		this.makeCell(tr, {className : "dataLabel", width: "7%"}, mod_string('LBL_OPERATIONS_PERFORM'));
		
		// operation selection
		var node = this.makeOperationSelection(idx);
		this.makeCell(tr, {className : "dataField", width: "20%"}, node);
		
		
		var node = this.makeBeforeAfterSection(idx);
		this.makeCell(tr, {className: "dataField", width: "70%", colSpan: 3, whiteSpace: 'nowrap'}, node);

		var link1, link2, link3;
		if (idx) {
			if (this.editable) {
				var attrs = {
					className: 'listViewTdToolsS1',
					href : '#',
					onclick : function() {
						var removed = WorkflowEditor.operations.splice(idx, 1);
						WorkflowEditor.operations.splice(idx-1, 0, removed[0]);
						WorkflowEditor.renderOperations();
						return false;
					}
				};
				link1 = createElement2("a", attrs);
				var upBtn = get_icon_image('up');
				link1.appendChild(upBtn);
			} else {
				link1 = nbsp();
			}
			this.makeCell(tr, {className: "dataField", align: "center", width :"2%", noWrap: 'nowrap'}, link1);
		}
		if (idx < this.operations.length - 1) {
			if (this.editable) {
				var attrs = {
					className: 'listViewTdToolsS1',
					href : '#',
					onclick : function() {
						var removed = WorkflowEditor.operations.splice(idx, 1);
						WorkflowEditor.operations.splice(idx+1, 0, removed[0]);
						WorkflowEditor.renderOperations();
						return false;
					}
				};
				link2 = createElement2("a", attrs);
				var downBtn = get_icon_image('down');
				link2.appendChild(downBtn);
			} else {
				link2 = nbsp();
			}
			this.makeCell(tr, {className: "dataField", align: "center", width :"2%", noWrap: 'nowrap'}, link2);
		}
		if (this.editable) {
			var attrs = {
				className: 'listViewTdToolsS1',
				href : '#',
				onclick : function() {
					WorkflowEditor.operations.splice(idx, 1);
					WorkflowEditor.renderOperations();
					return false;
				}
			};
			var link3 = createElement2("a", attrs);
			var minusBtn = get_icon_image('remove');
			link3.appendChild(minusBtn);
		} else {
			link3 = nbsp();
		}
		this.makeCell(tr, {className: "dataField", align: "center", width :"2%", noWrap: 'nowrap'}, link3);
	};

	this.makeBeforeAfterSection = function(idx)
	{
		var op = this.operations[idx];
		var canSelect = op.operation_type == 'sendEmail' && this.occurs_when != 'time';
		if (canSelect) {
			var options = {
				0: mod_string('LBL_OPERATIONS_PERFORM_AFTER'),
				1: mod_string('LBL_OPERATIONS_PERFORM_BEFORE')
			};
			attrs = {
				max_width: '200px',
				onchange: function() {
					WorkflowEditor.operations[idx].performed_before_event = this.getValue();
				}
			};
			var select = this.createSelectInput2(options, null, op.performed_before_event, attrs);
			return select;
		} else {
			var lbl;
			if(op.operation_type == 'updateCurrentData' && this.occurs_when != 'time') {
				op.performed_before_event = '1';
				lbl = 'LBL_OPERATIONS_PERFORM_BEFORE';
			} else {
				op.performed_before_event = '0';
				lbl = 'LBL_OPERATIONS_PERFORM_AFTER';
			}
			return mod_string(lbl);
		}

	};

	this.renderOperationDetails = function(idx)
	{
		var table = $('workflow_operations');
		var op = this.operations[idx];
		var tr = table.insertRow(table.rows.length);
		var node = nbsp(), input;
		switch (op.operation_type) {
			case 'sendEmail':
				var result = this.renderEmailOperation(idx);
				node = result[0];
				input = result[1];
				break;
			case 'scheduleCall':
			case 'scheduleMeeting':
				node = this.renderCallMeeting(idx);
				break;
			case 'createTask':
				node = this.renderTask(idx);
				break;
			case 'showAlert':
				node = this.renderAlert(idx);
				break;
			case 'updateCurrentData':
				node = this.renderUpdateCurrent(idx);
				break;
			case 'updateRelatedData':
				node = this.renderUpdateRelated(idx);
				break;
		}
		this.makeCell(tr, null, nbsp());
		this.makeCell(tr, {colSpan: 5, className: 'dataField'}, node);
		if(input)
			SUGAR.ui.registerInput(this.form, input);
	};

	this.renderUpdateCurrent = function(idx)
	{
		var table = createElement2('table', {border: 0, width: '100%'});
		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: "dataLabel", width: "15%", valign:'top'}, 'LBL_DM_SET_CURRENT', true);
		
		var fieldCell = this.makeCell(tr, {className: "dataField", valign:'top', width:'20%'}, '');
		this.makeCell(tr, {className: "dataLabel", width: "3%", valign:'top'}, 'LBL_DM_TO', true);
		var valueCell = this.makeCell(tr, {className: "dataField", valign:'top'}, '');
		
		fieldCell.appendChild(this.makeUpdateCurFieldSelection(idx, valueCell, this.trigger_module));
		this.makeUpdateCurField(idx, valueCell);
		return table;
	};

	this.renderUpdateRelated = function(idx)
	{
		var op = this.operations[idx];
		var table = createElement2('table', {border: 0, width: '100%'});
		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: "dataLabel", width: "3%", valign:'top', whiteSpace:'nowrap'}, 'LBL_DM_SET', true);
		
		var relCell = this.makeCell(tr, {className: "dataField", width:'3%', valign:'top'}, '');
		this.makeCell(tr, {className: "dataLabel", valign:'top', width:'1%', whiteSpace:'nowrap'}, 'LBL_DM_S', true);
		var fieldCell = this.makeCell(tr, {className: "dataField", valign:'top', width:'3%', whiteSpace:'nowrap'}, '');
		this.makeCell(tr, {className: "dataLabel", valign:'top', width:'1%', whiteSpace:'nowrap'}, 'LBL_DM_TO', true);
		var valueCell = this.makeCell(tr, {className: "dataField", valign:'top'}, '');
		var select = this.makeRelatedSelection(idx, fieldCell, valueCell);
		relCell.appendChild(select);
		fieldCell.appendChild(this.makeUpdateCurFieldSelection(idx, valueCell, op.dm_module_name));
		this.makeUpdateCurField(idx, valueCell);
		
		return table;
	};

	this.makeRelatedSelection = function(idx, fieldCell, valueCell)
	{
		var op = this.operations[idx];
		var options = {};
		for (var i in this.bean_fields) {
			var def = this.bean_fields[i];
			if (def.type != 'ref') continue;
			var label = mod_string(def.vname, this.trigger_module);
			options[i] = label;
		}
		var attrs = {
			width: '200px',
			onchange: function(k, v, ch) {
				op.dm_link_name = k;
				op.dm_module_name = WorkflowEditor.bean_fields[k].ref_module_dir;
				op.dm_field_name = '';
				op.dm_field_value = '';
				fieldCell.innerHTML = '';
				valueCell.innerHTML = '';
				fieldCell.appendChild(WorkflowEditor.makeUpdateCurFieldSelection(idx, valueCell, op.dm_module_name));
			}
		};
		return this.createSelectInput2(options, null, op.dm_link_name, attrs);
	};

	this.renderAlert = function(idx)
	{
		var table = createElement2('table', {border: 0, width: '100%'});
		var op = this.operations[idx];
		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: "dataLabel", width: "15%", valign:'top'}, 'LBL_NOTIFICATION_MESSAGE', true);
		var attrs = {
			cols: 80,
			rows: 5,
			value: this.operations[idx].notification_content,
			onchange : function() {
				WorkflowEditor.operations[idx].notification_content = this.value;
			}
		};
		if (!this.editable) attrs.readOnly = true;

		var input = this.makeTextAreaInput(attrs);
		this.makeCell(tr, {className: 'dataField', colSpan:5}, input);

		return table;
	};

	this.makeTemplateSource = function(idx)
	{
		var op = this.operations[idx];
		var table = createElement2('table');
		var tr = table.insertRow(table.rows.length);
		var sourceCell = this.makeCell(tr, {}, '');
		var varCell = this.makeCell(tr, {}, '');
		var input = createElement2('input', {type:'text', className: 'input-text'});
		this.makeCell(tr, {}, input);

		var options = {
			'Contacts' : mod_string('LBL_RECIPIENTS')
		};

		options[this.trigger_module] = SUGAR.ui.select_options['workflow_trigger_module'].getValue(this.trigger_module);
		var attrs = {
			max_width: '250px',
			onchange : function(k, v, ch) {
				WorkflowEditor.fillVariables(k, varCell, input);
			}
		};
		var select = this.createSelectInput2(options, null, get_default(op.selected_var_source, 'Contacts'), attrs);
		sourceCell.appendChild(select);
		
		this.fillVariables('Contacts', varCell, input);

		var onclick = function() {
				var inp = SUGAR.ui.getFormInput(WorkflowEditor.form, 'body_html_'+idx);
				try {
					if(inp) inp.insertHtml(input.value);
				} catch(e) {
				}
			},
			btn = new SUGAR.ui.ButtonInput(
				null, {onclick: onclick, label: mod_string('LBL_INSERT_VAR')});
		this.makeCell(tr, {}, btn.render());
		
		return table;
	};

	this.renderEmailOperation = function(idx)
	{
		var table = createElement2('table', {border: 0, width: '100%'});
		var op = this.operations[idx];
		
		// invitees
		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: "dataLabel", width: "15%", valign:'top'}, mod_string('LBL_NOTIFICATION_TO'));
		
		var targets = createElement2('div', {id: 'invitees_' + idx + '_display'});
		this.makeCell(tr, {className: "dataField", colSpan: 3}, targets);
		targets.appendChild (this.makeInviteesList(idx));
		
		
		var tr = table.insertRow(table.rows.length);
		var cell = this.makeCell(tr, {className: "dataField", colSpan:5});
		var callback = function(v) {op.include_initiator = v;};
		cell.appendChild(this.makeCheckbox(op.include_initiator, callback));
		cell.appendChild(nbsp());
		cell.appendChild(document.createTextNode(mod_string('LBL_INCLUDE_INITIATOR')));


		var keys = ['primary', 'secondary'];
		for (var i = 0; i < keys.length; i++) {
			var key = keys[i];
			for (var j = 0; j < this.primary_email_options.length; j++) {
				var def = this.primary_email_options[j];
				if (def.modules.includes(this.trigger_module) && def[key]) {
					var tr = table.insertRow(table.rows.length);
					var cell = this.makeCell(tr, {className: "dataField", colSpan:5});
					var callback = function(k) {
						return function(val) {
							op['include_' + k + '_email'] = val;
						};
					}(key);
					var cb = this.makeCheckbox(op['include_' + key + '_email'], callback);
					cell.appendChild(cb);
					
					cell.appendChild(nbsp());
					cell.appendChild(document.createTextNode(mod_string(def[key])));
					break;
				}
			}
		}
		
		var box_id = op.notification_cc_mailbox;
		if (typeof(box_id) == 'undefined' || box_id == null) {
			box_id = '';
		}

		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: "dataLabel"}, mod_string('LBL_CC_MAILBOX'));
		var attrs = {
			max_width : '250px',
			onchange : function(k, v, ch) {
				if (ch) {
					op.notification_cc_mailbox = k;
				}
			}
		};
		var select = this.createSelectInput2(this.groupBoxes, null, box_id, attrs);
		this.makeCell(tr, {className: "dataField"}, select);
	
		if (this.editable) {
			var template_id = op.template_id;
			if (typeof(template_id) == 'undefined' || template_id == null) 
				template_id = '';
			var tr = table.insertRow(table.rows.length);
			this.makeCell(tr, {className: "dataLabel"}, mod_string('LBL_NOTIFICATION_TEMPLATE'));
			var attrs = {
				max_width : '250px',
				onchange : function(k, v, ch) {
					if (ch) {
						WorkflowEditor.fillEmail(k, idx);
					}
				}
			};
			var select = this.createSelectInput2(this.templates.options, null, template_id, attrs);
			this.makeCell(tr, {className: "dataField"}, select);
		}
	
		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: 'dataLabel'}, mod_string('LBL_NOTIFICATION_SUBJECT'));
		var attrs = {
			size: 80,
			value : op.notification_subject,
			onchange : function() {
				op.notification_subject = this.value;
			}
		};
		var input = this.makeTextInput(attrs);
		this.makeCell(tr, {className: 'dataField'}, input);

		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: 'dataLabel'}, mod_string('LBL_NOTIFICATION_FROM_NAME'));
		var attrs = {
			size: 80,
			value : op.notification_from_name,
			onchange : function() {
				op.notification_from_name= this.value;
			}
		};
		var input = this.makeTextInput(attrs);
		this.makeCell(tr, {className: 'dataField'}, input);
		
		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: 'dataLabel'}, mod_string('LBL_NOTIFICATION_FROM_EMAIL'));
		var attrs = {
			size: 80,
			value : op.notification_from_email,
			onchange : function() {
				op.notification_from_email = this.value;
			}
		};
		var input = this.makeTextInput(attrs);
		this.makeCell(tr, {className: 'dataField'}, input);

		if (this.editable) {
			var tr = table.insertRow(table.rows.length);
			this.makeCell(tr, {className: 'dataLabel', valign:'top'}, mod_string('LBL_INSERT_VARIABLE'));
			this.makeCell(tr, {className: 'dataLabel', valign:'top'}, this.makeTemplateSource(idx));
		}

		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: 'dataLabel', valign:'top'}, mod_string('LBL_NOTIFICATION_MESSAGE'));
		var input = this.createCKEditor(idx, get_default(op.notification_content, '')),
			elt;
		if(input.render) {
			elt = input.render();
		} else {
			elt = input;
			input = null;
		}
		this.makeCell(tr, {className: 'dataField'}, elt);

		return [table, input];
	};

	this.makeTextInput = function(attrs)
	{
		var input;
		if (this.editable) {
			attrs.className = 'input-text';
			attrs.type = 'text';
			input = createElement2('input', attrs);
		}
		else {
			input = document.createTextNode(attrs.value);
			input = createElement2('span', {className: "list-edit-value disabled"}, input);
		}
		return input;
	};
	
	this.makeTextAreaInput = function(attrs)
	{
		var input;
		if (this.editable) {
			attrs.className = 'input-text';
			input = createElement2('textarea', attrs);
		}
		else input = document.createTextNode(attrs.value);
		return input;
	};

	this.renderCallMeeting = function(idx)
	{
		var table = createElement2('table', {border: 0, width: '100%'});
		var op = this.operations[idx];
		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: "dataLabel", width: "15%", valign:'top'}, 'LBL_NOTIFICATION_WITH', true);
		
		var targets = createElement2('div', {id: 'invitees_' + idx + '_display'});
		this.makeCell(tr, {className: "dataField", colSpan: 3}, targets);
		targets.appendChild (this.makeInviteesList(idx));
		
		
		var tr = table.insertRow(table.rows.length);
		var cell = this.makeCell(tr, {className: "dataField", colSpan:5}, nbsp());
		if (this.trigger_module == 'Contacts') {
			var callback = function(v) {op.include_contact = v;};
			cell.appendChild(this.makeCheckbox(op.include_contact, callback));
			cell.appendChild(nbsp());
			cell.appendChild(document.createTextNode(mod_string('LBL_INCLUDE_CONTACT')));
		}

		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: "dataLabel", width: "15%", valign:'top'}, 'LBL_NOTIFICATION_SUBJECT', true);
		var attrs = {
			size: 80,
			value: this.operations[idx].notification_subject,
			onchange : function() {
				WorkflowEditor.operations[idx].notification_subject = this.value;
			}
		};
		var input = this.makeTextInput(attrs);
		this.makeCell(tr, {className: 'dataField', colSpan:5}, input);
		
		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: 'dataLabel'}, nbsp());

		this.makeCell(tr, {className: 'dataField'}, this.makeDatesPanel(idx));

		return table;
	};

	this.renderTask = function(idx)
	{
		var table = createElement2('table', {border: 0, width: '100%'});
		var op = this.operations[idx];
		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: "dataLabel", width: "15%", valign:'top'}, 'LBL_NOTIFICATION_SUBJECT', true);
		var attrs = {
			size: 80,
			value: this.operations[idx].notification_subject,
			onchange : function() {
				WorkflowEditor.operations[idx].notification_subject = this.value;
			}
		};
		var input = this.makeTextInput(attrs);
		this.makeCell(tr, {className: 'dataField', colSpan:5}, input);
		
		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: 'dataLabel'}, nbsp());

		this.makeCell(tr, {className: 'dataField'}, this.makeDatesPanel(idx));
		
		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: 'dataLabel'}, nbsp());
		this.makeCell(tr, {className: 'dataField'}, this.makeTaskStatus(idx));
		
		
		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {className: "dataLabel", width: "15%", valign:'top'}, 'LBL_TASK_DESC', true);
		var attrs = {
			cols: 80,
			rows: 5,
			value: this.operations[idx].notification_content,
			onchange : function() {
				WorkflowEditor.operations[idx].notification_content = this.value;
			}
		};

		var input = this.makeTextAreaInput(attrs);
		this.makeCell(tr, {className: 'dataField', colSpan:5}, input);

		return table;
	};

	this.makeTaskStatus = function(idx)
	{
		var op = this.operations[idx];
		var table = createElement2('table');
		var tr = table.insertRow(table.rows.length);

		this.makeCell(tr, {}, 'LBL_TASK_STATUS', true, null, true);
		var options = new SUGAR.ui.SelectOptions(SUGAR.language.getList('task_status_dom', 'app'));
		var attrs = {
			max_width:'200px',
			onchange: function(k, v, ch) {
				op.task_status = k;
			}
		};
		var select = this.createSelectInput2(options, null, op.task_status, attrs);
		this.makeCell(tr, {}, select);
			
		this.makeCell(tr, {}, 'LBL_TASK_PRIORITY', true, null, true);
		var options = new SUGAR.ui.SelectOptions(SUGAR.language.getList('task_priority_dom', 'app'));
		var attrs = {
			max_width:'200px',
			onchange: function(k, v, ch) {
				op.task_priority = k;
			}
		};
		var select = this.createSelectInput2(options, null, op.task_priority, attrs);
		this.makeCell(tr, {}, select);
		
		this.makeCell(tr, {}, 'LBL_TASK_CONTACT', true, null, true);
		attrs = {
			onchange : function() {
				op.task_contact_id = this.getKey();
				op.task_contact_name = this.getValue();
			}
		};
		var select = this.makeRefInput('Contacts', 'workflow_task_contact+' + idx, op.task_contact_id, op.task_contact_name, attrs);
		this.makeCell(tr, {}, select);
			
		return table;
	};

	this.makeDatesPanel = function(idx)
	{
		var table = createElement2('table');
		var task = this.operations[idx].operation_type == 'createTask';
		if (task) {
			this.makeDatePanel(idx, table, true, true);
		}
		this.makeDatePanel(idx, table, false, task);
		return table;
	};

	this.makeDatePanel = function(idx, table, due, task)
	{
		var op = this.operations[idx];
		var prefix = 'notification_start_';
		if (task && due) prefix = 'task_due_';
		var flagName = due ? 'task_due_date_flag' : 'task_start_date_flag';
		
		var attrs = {
			size: 5,
			value :  op[prefix + 'date'],
			onchange: function() {
				op[prefix + 'date'] = this.value;
			}
		};
		if (op[prefix + 'date_choice'] != 'C3') attrs.readOnly = true;
		var startDateInput = this.makeTextInput(attrs);
		
		var tr = table.insertRow(table.rows.length);
		var label = due ? 'LBL_TASK_DUE_DATE' : 'LBL_NOTIFICATION_START_TIME';
		this.makeCell(tr, {}, label, true, null, true);

		var attrs = {
			max_width: '150px',
			onchange: function(k, v, ch) {
				if (ch) {
					op[prefix + 'date_choice'] = k;
					if (k == 'C3') {
						startDateInput.removeAttribute('readOnly');
					} else {
						startDateInput.setAttribute('readOnly', 'readOnly');
					}
				}
			}
		};
		var opts = new SUGAR.ui.SelectOptions(mod_list_strings('START_DATE_CHOICES'));
		var select = this.createSelectInput2(opts, null, op[prefix+ 'date_choice'], attrs);
		this.makeCell(tr, {}, select);

		this.makeCell(tr, {}, startDateInput);

		var options = {};
		for (var i = 0; i < 24; i++) {
			options['' + i] = '' + i;
		}

		var attrs = {
			max_width: '150px',
			onchange: function(k, v, ch) {
				if (ch) op[prefix + 'time_hour'] = k;
			}
		};
		var select = this.createSelectInput2(options, null, op[prefix + 'time_hour'], attrs);
		this.makeCell(tr, {}, select);

		var options = this.minutesOptions;
		var attrs = {
			max_width: '150px',
			onchange: function(k, v, ch) {
				if (ch) op[prefix + 'time_min'] = k;
			}
		};
		var select = this.createSelectInput2(options, null, op[prefix + 'time_min'], attrs);
		this.makeCell(tr, {}, select);

		if (task) {
			var callback = function(v) {
				op[flagName] = v;
			}
			var cb = this.makeCheckbox(op[flagName], callback);
			var cell = this.makeCell(tr, {}, cb);
			cb.appendChild(document.createTextNode(mod_string('LBL_TASK_NONE')));
		} else {
			this.makeCell(tr, {}, nbsp());
		}

		if (!due) {
			var key = 'notification_duration_hour';
			if (task) key = 'task_est_effort';
			var label = task ? 'LBL_TASK_EST_DURATION' : 'LBL_NOTIFICATION_DURATION';
			this.makeCell(tr, {}, label, true, null, true);
			var attrs = {
				onchange : function() {
					op[key] = this.value;
				},
				size: 4,
				value: op[key]
			};
			var input = this.makeTextInput(attrs);
			this.makeCell(tr, {}, input);
			
			if (!task) {
				var options = this.minutesOptions;
				var attrs = {
					max_width: '150px',
					onchange: function(k, v, ch) {
						if (ch) op.notification_duration_min = k;
					}
				};
				var select = this.createSelectInput2(options, null, op.notification_duration_min, attrs);
				this.makeCell(tr, {}, select);
			} else {
				var options = new SUGAR.ui.SelectOptions(mod_list_strings('TASK_EST_UNIT'));
				var attrs = {
					max_width: '150px',
					onchange: function(k, v, ch) {
						if (ch) op.task_est_effort_unit = k;
					}
				};
				var select = this.createSelectInput2(options, null, op.task_est_effort_unit, attrs);
				this.makeCell(tr, {}, select);
			}
		}

		var tr = table.insertRow(table.rows.length);
		this.makeCell(tr, {}, nbsp());
		this.makeCell(tr, {}, nbsp());
		this.makeCell(tr, {}, 'LBL_OPERATIONS_DAYS', true);
		this.makeCell(tr, {}, 'LBL_OPERATIONS_HOUR', true);
		this.makeCell(tr, {}, 'LBL_OPERATIONS_MIN', true);
		if (!due) {
			this.makeCell(tr, {}, nbsp());
			this.makeCell(tr, {}, nbsp());
			this.makeCell(tr, {}, task ? nbsp() : 'LBL_OPERATIONS_HOUR', true);
			if (task) {
				this.makeCell(tr, {}, 'LBL_TASK_EST_UNIT', true);
			} else {
				this.makeCell(tr, {}, 'LBL_OPERATIONS_MIN', true);
			}
		}

		return table;
	};

	this.fillVariables = function(source, selectContainer, input) {
		selectContainer.innerHTML = '';
		var selected = '';
		input.value = '';
		for (var i in this.template_fields[source]) {
			selected = i;
			input.value = '$' + i;
			break;
		}
		var attrs = {
			max_width: '250px',
			onchange : function(k, v, c) {
				input.value = '$' + k;
			}
		};
		var select = this.createSelectInput2(this.template_fields[source], null, selected, attrs);
		selectContainer.appendChild(select);
	};


	this.fillEmail = function(id, idx)
	{
		var op = this.operations[idx],
			req = new SUGAR.conn.JSONRequest('retrieve', null, {module: 'EmailTemplates', record: id});		
		function returnTemplate() {
			var tpl = this.getResult();
			if(tpl) {
				op.notification_subject = tpl.fields.subject;
				op.notification_content = tpl.fields.body_html;
				op.template_id = tpl.fields.id;
				op.no_content_update = true;
			}
			WorkflowEditor.renderOperations();
		}
		if(op) req.fetch(returnTemplate);		
	};

	this.makeInviteesList = function(idx)
	{
		var container = createElement2('div');
		var inner = createElement2('div');
		container.appendChild(inner);
		var op = this.operations[idx];
		if (op.notification_invitee_types != "") {
			var ids = op.notification_invitee_ids.split(';');
			var names = op.notification_invitee_names.split(';');
			var types = op.notification_invitee_types.split(';');
			for (var i = 0; i < ids.length; i++) {
				inner.appendChild(this.makeInviteeSelect(idx, i, ids[i], names[i], types[i]));
				inner.appendChild(createElement2('br'));
			}
		}

		if (this.editable) {
			var btn = new SUGAR.ui.ButtonInput(
				null, {onclick: function() {
					WorkflowEditor.addInvitee(idx, 'Contacts', inner);
				}, label: mod_string('LBL_ADD_CONTACT')});
			container.appendChild(btn.render());
			
			container.appendChild(nbsp());
			
			btn = new SUGAR.ui.ButtonInput(
				null, {onclick: function() {
					WorkflowEditor.addInvitee(idx, 'Users', inner);
				}, label: mod_string('LBL_ADD_USER')});
			container.appendChild(btn.render());
		}

		return container;
	};

	this.makeInviteeSelect = function(idx, jdx, id, name, type)
	{
		var baseId = 'workflow-action-invitee-' + idx + '_' + jdx;
		if (type != 'Users') type = 'Contacts';
		attrs = {
			onchange : function() {
				var me = this;
				WorkflowEditor.processInvitees(idx, function(ids, names, types) {
					ids[jdx] = me.getKey();
					names[jdx] = me.getValue();
					types[jdx] = type;
				});
			}
		};
		var outer = createElement2('div');
		var sel = this.makeRefInput(type, baseId, id, name, attrs);
		outer.appendChild(sel);
		
		if (this.editable) {
			var minusBtn = get_icon_image('remove');
			minusBtn.style.cursor = 'pointer';
			minusBtn.onclick = function() {
				var op = WorkflowEditor.operations[idx];
				var ids = op.notification_invitee_ids.split(';');
				var names = op.notification_invitee_names.split(';');
				var types = op.notification_invitee_types.split(';');
				WorkflowEditor.processInvitees(idx, function(ids, names, types) {
					ids.splice(jdx, 1)
					names.splice(jdx, 1);;
					types.splice(jdx, 1);;
				});
				WorkflowEditor.renderOperations();
			}
			outer.appendChild(minusBtn);
		}

		return outer;
	};

	this.addInvitee = function(idx, type, container)
	{
		var size;
		this.processInvitees(idx, function(ids, names, types) {
			if (types.length > 0 && types[0] == '') {
				ids[0] = '';
				names[0] = '';
				types[0] = type;
				size = 1;
			} else {
				ids.push('');
				names.push('');
				types.push(type);
				size = ids.length;
			}
		});
		container.appendChild(this.makeInviteeSelect(idx, size-1, '', '', type));
		container.appendChild(createElement2('br'));
	};

	this.processInvitees = function(idx, callback)
	{
		var op = this.operations[idx];
		var ids = op.notification_invitee_ids.split(';');
		var names = op.notification_invitee_names.split(';');
		var types = op.notification_invitee_types.split(';');
		callback(ids, names, types);
		op.notification_invitee_ids = ids.join(';');
		op.notification_invitee_names = names.join(';');
		op.notification_invitee_types = types.join(';');
	};

	this.makeCell = function(tr, attrs, content, trans, tr_module, add_sep)
	{
		var cell = tr.insertCell(-1);
		if (typeof(attrs) == 'object') {
			for (var i in attrs) {
				cell.setAttribute(i, attrs[i]);
			}
		}
		if (content) {
			var node;
			if (typeof(content) != 'object') {
				if (trans) content = mod_string(content, tr_module||'Workflow');
				if(add_sep) content += app_string('LBL_SEPARATOR');
				node = document.createTextNode(content);
			}
			else node = content;
			cell.appendChild(node);
		}
		return cell;
	};


	this.makeCheckbox = function(value, callback)
	{
		var val = (value === '1' || value === 1 || value === true) ? 1 : 0;
		var attrs =  {
			init_value: val,
			onchange : function() {
				if (typeof(callback) == 'function') {
					callback(''+this.getValue());
				}
			},
			disabled: !this.editable
		};
		var button, cb = new SUGAR.ui.CheckInput(null, attrs);
		button = cb.render();
		cb.setup();
		return button;
	};

	this.createCKEditor = function(idx, content) {
		if (!this.editable) {
			var div = createElement2('div');
			div.innerHTML = content;
			return div;
		}
		
		var old = SUGAR.ui.getFormInput(this.form, 'body_html_' + idx);
		if(old) {
			old.destroy();
			SUGAR.ui.unregisterInput(this.form, old);
		}
		var input = new SUGAR.ui.HtmlInput('body_html_' + idx,
			{name: 'body_html_' + idx, init_value: content});
		return input;
	};

	this.operationTemplate = function()
	{
		return {
			'operation_type': 'sendEmail',
			'performed_before_event': '0',
			'include_initiator': '0',
			'include_contact': '0',
			'include_primary_email': '0',
			'include_secondary_email': '0',
			'notification_invitee_ids': '',
			'notification_invitee_types': '',
			'notification_invitee_names': '',
			'notification_status': '',
			'notification_direction': '',
			'notification_subject': '',
			'notification_cc_mailbox': '',
			'notification_content': '',
			'notification_start_date': '',
			'notification_start_date_choice': 'C1',
			'notification_start_time_hour': '9',
			'notification_start_time_min': '0',
			'notification_duration_hour': '1',
			'notification_duration_min': '0',
			'dm_link_name': '',
			'dm_module_name': '',
			'dm_field_name': '',
			'dm_field_value': '',
			'task_due_date': '',
			'task_due_date_choice': 'C1',
			'task_due_time_hour': '9',
			'task_due_time_min': '0',
			'task_due_date_flag': 'on',
			'task_start_date_flag': 'off',
			'task_status': 'Not Started',
			'task_priority': 'P0',
			'task_contact_id': '',
			'task_contact_name': '',
			'task_est_effort': '',
			'task_est_effort_unit': 'hours'
		};
	};

}();

Array.prototype.includes = function(value) {
	for (var i in this) {
		if (this[i] == value) {
			return true;
		}
	}
	return false;
}


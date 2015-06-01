ModuleDesignerFields = new function()
{

	this.init = function(params)
	{
		for (var i in params) {
			this[i] = params[i];
		}
		this.nextId = 0;
	};

	this.render = function()
	{
		var table = $('fields_table');
		for (var fid in this.fields) {
			var field = this.fields[fid];
			if (field.inferred)
				continue;
			if (field.type == 'module_name')
				continue;
			var rid = 'row_' + fid;
			var row = $(rid);
			if (!row) {
				row = table.insertRow(table.rows.length);
				row.onclick = function() {
					ModuleDesignerFields.showForm(this.id.replace('row_', ''));
				}
				row.onmouseover = function() {
					YDom.addClass(this, 'currentRow markRow');
				};
				row.onmouseout = function() {
					YDom.removeClass(this, 'currentRow markRow');
				};
				row.style.cursor = 'pointer';
				row.className = 'evenListRowS1';
				row.id = rid;
			
				var sep = table.insertRow(table.rows.length);
				var cell = sep.insertCell(sep.cells.length);
				cell.className = 'listViewHRS1';
				cell.colSpan = 12;
			}
			while (row.cells.length)
				row.deleteCell(0);
			var cell = row.insertCell(row.cells.length);
			cell.innerHTML = '&nbsp;';
			cell.style.width = '1px';
			cell.className = 'listViewTd listViewMeta';
			var cell = row.insertCell(row.cells.length);
			cell.appendChild(document.createTextNode(field.name));
			cell.className = 'listViewTd';
			var cell = row.insertCell(row.cells.length);
			var label = get_default(field.label, mod_string(field.vname, this.module));
			cell.appendChild(document.createTextNode(label));
			cell.className = 'listViewTd';
			var cell = row.insertCell(row.cells.length);
			cell.appendChild(document.createTextNode(app_list_strings('field_type_dom')[field.type]));
			cell.className = 'listViewTd';

		}
	
	};
	
	this.showForm = function(field_id)
	{
		this.resetErrors();
		this.editingId = field_id;
		this.editingField = deep_clone(this.fields[field_id]);
		this.renderForm();
		if (!this.popup) {
			this.popup = new SUGAR.ui.Dialog('field_editor', {content_elt: $('field_dialog'), width: '640px', destroy_on_close: false});
		}
		this.popup.setTitle(mod_string('LBL_EDIT_DIALOG_TITLE', 'ModuleDesigner'));
		this.popup.render();
		this.popup.show();
	}
        
	this.renderForm = function()
	{
		table = $('custom_fields_body');
		while (table.rows.length > 0) {
			table.deleteRow(0);
		}
		var isNew = this.isNewField(this.editingId);
		$('data_type').value = this.editingField.type;
		$('data_type').disabled = !isNew;
		$('field_name').value = this.editingField.name;
		$('field_name').disabled = !isNew;
		$('label').value = get_default(this.editingField.label, mod_string(this.editingField.vname, this.module));
		$('audited').checked = get_default(this.editingField.audited, false) ? true : false;
		$('massupdate').checked = get_default(this.editingField.massupdate, false) ? true : false;
		$('required').checked = get_default(this.editingField.required, false) ? true : false;
		$('required').disabled = this.editingField.module_builder == 'label_only';
		this.displayAudit(true);
		this.displayMassUpdate(true);
		this.displayFormula(false);
        this.displayRequired(true);

		switch ($('data_type').value) {
			case 'ref':
				this.renderModulesList(table, mod_string('LBL_RELATED_TYPE', 'ModuleDesigner'));
				break;
			case 'varchar':
				this.renderDefaultText(table);
				if (!this.editingField.len) this.editingField.len = 50;
				this.renderSize(table);
				break;
			case 'text':
				this.renderDefaultTextArea(table);
				break;
			case 'int':
				this.renderDefaultText(table);
				if (!this.editingField.len) this.editingField.len = 11;
				if (this.editingField.len > 11) this.editingField.len = 11;
				this.renderSize(table);
				break;
			case 'double':
				this.renderDefaultText(table);
				break;
			case 'bool':
				this.renderDefaultBool(table);
				break;
			case 'email':
				this.renderDefaultText(table);
				break;
			case 'enum':
				this.renderDropDownsList(table, mod_string('LBL_DROPDOWN_LIST', 'ModuleDesigner'));
				this.renderDefaultDropdown(table, $('ext1').value);
				break;
			case 'multienum':
				this.renderDropDownsList(table, mod_string('LBL_MULTI_SELECT', 'ModuleDesigner'));
				this.renderDefaultDropdown(table, $('ext1').value);
				break;
			case 'date':
				this.renderDefaultDate(table, mod_string('LBL_DEFAULT_VALUE', 'ModuleDesigner'));
				break;
			case 'url':
				this.renderDefaultText(table, mod_string('LBL_DEFAULT_URL', 'ModuleDesigner'));
				break;
			case 'html':
				this.renderDefaultTextArea(table, mod_string('LBL_HTML', 'ModuleDesigner'));
				break;
            case 'calculated':
                this.displayAudit(false);
                this.displayMassUpdate(false);
                this.displayRequired(false);
                this.displayFormula(true);
                this.renderFormulaInput(table, mod_string('LBL_FORMULA', 'ModuleDesigner'));
                break;
		}
	};
    
	this.renderFormulaInput = function(table, label)
    {
        if (!label) label = SUGAR.language.getString('LBL_FORMULA');
        var row = table.insertRow(table.rows.length);
        this.renderLabel(row, label);

        var cell = row.insertCell(1);
        var input = document.createElement('textarea');
        input.name = 'formula';
        input.id = 'formula';
        input.size = 15;
        input.rows = 4;
        input.cols = 50;
        input.value = this.editingField.calc_formula || '';
        input.onchange = function() {
            ModuleDesignerFields.editingField.calc_formula = this.value;
        };
        cell.appendChild(input);
        return input;
    };
    
	this.displayFormula = function(show)
    {
        this.displayElement('custom_fields_formula', show);
    };

    this.addToFormula = function(formula_elt)
    {
        if ($('formula') && formula_elt.innerHTML != '') {
            var postfix = '';
            if (formula_elt.id.indexOf('newfunc') != -1)
                postfix = '(';
            $('formula').value = $('formula').value + formula_elt.innerHTML + postfix;
            this.editingField.calc_formula = $('formula').value;
			$('formula').focus();
        }
    };

	this.defaultDefault = function(type)
	{
		switch (type) {
			case 'ref':
				break;
			case 'email':
			case 'varchar':
			case 'text':
			case 'url':
			case 'html':
				return "";
				break;
			case 'int':
			case 'double':
				return 0;
				break;
			case 'bool':
				return false;
				break;
		}
	};
	
	this.renderDefaultBool = function(table)
	{
		this.renderCheckbox(table, mod_string('LBL_DEFAULT_VALUE', 'ModuleDesigner'), 'default');
	};
	
	this.renderCheckbox = function(table, label, name) {
		var row = table.insertRow(table.rows.length);
		this.renderLabel(row, label);

		var cell = row.insertCell(1);
		var input = document.createElement('input');
		input.type ='checkbox';
		input.id = name;
		input.name = name;
		input.value = 1;
		input.checked = get_default(this.editingField[name], false);
		input.onclick = function() {
			ModuleDesignerFields.updateField(this, name);
		}
		cell.appendChild(input);
		if (this.editingField.module_builder == 'label_only')
			input.disabled = true;
	};

	this.renderLabel = function(row, label)
	{
		var cell = row.insertCell(0);
		cell.className = 'dataLabel';
		cell.style.verticalAlign = 'top';
		cell.appendChild(document.createTextNode(label));
	};
	
	this.renderDefaultText = function(table, label)
	{
		if (!label) label = mod_string('LBL_DEFAULT_VALUE', 'ModuleDesigner');
		return this.renderTextField(table, label, 'default', 20);
	};

	this.renderSize = function(table)
	{
		return this.renderTextField(table, mod_string('LBL_MAX_SIZE', 'ModuleDesigner'), 'len', 5);
	};
	
	this.renderTextField = function(table, label, name, size) {
		var row = table.insertRow(table.rows.length);
		this.renderLabel(row, label);

		var cell = row.insertCell(1);
		var input = document.createElement('input');
		input.name = name;
		input.id = name;
		input.size = size;
		input.value = get_default(this.editingField[name], '');
		input.onchange = function() {
			ModuleDesignerFields.updateField(this, name);
		}
		if (this.editingField.module_builder == 'label_only')
			input.disabled = true;
		cell.appendChild(input);
		return input;
	};
	
	this.renderModulesList = function(table, label)
	{
		var row = table.insertRow(table.rows.length);
		this.renderLabel(row, label);
		var cell = row.insertCell(1);
		var select = document.createElement('select');
		select.name = 'bean_name';
		select.id = 'bean_name';
		for (var i in this.rel_modules) {
			var opt = document.createElement('option');
			opt.value = i;
			opt.text = this.rel_modules[i];
			try {
				select.add(opt, null);
			}
			catch(e) {
				select.add(opt);
			}
			if (i == this.editingField.bean_name)
				opt.selected = true;
		}
		select.onchange = function() {
			ModuleDesignerFields.updateField(this, 'bean_name');
		};
		select.disabled = !this.isNewField(this.editingId);
		if (this.editingField.module_builder == 'label_only')
			select.disabled = true;
		cell.appendChild(select);
	};
	
	this.renderDropDownsList = function(table, label)
	{
		var row = table.insertRow(table.rows.length);
		this.renderLabel(row, label);
		var cell = row.insertCell(1);
		var select = document.createElement('select');
		select.name = 'ext1';
		select.id = 'ext1';
		for (var i=0; i < this.dom_opts.length; i++) {
			var opt = document.createElement('option');
			opt.value = this.dom_opts[i];
			opt.text = this.dom_opts[i];
			try {
				select.add(opt, null);
			}
			catch(e) {
				select.add(opt);
			}
			if(this.dom_opts[i] == this.editingField.options)
				opt.selected = true;
		}
		select.onchange = function() {
			ModuleDesignerFields.updateField(this, 'options');
			ModuleDesignerFields.renderDefaultDropdown(table, this.value, $('default'));
		};
		if (this.editingField.module_builder == 'label_only')
			select.disabled = true;
		cell.appendChild(select);
	};
	
	this.renderDefaultDropdown = function(table, list, select)
	{

		var app_lang = SUGAR.language.getModule('app');
		var def = undefined;
		if (select) {
			select.innerHTML = '';
		} else {
			var row = table.insertRow(table.rows.length);
			this.renderLabel(row, mod_string('LBL_DEFAULT_VALUE', 'ModuleDesigner'));
			var cell = row.insertCell(1);
			select = document.createElement('select');
			select.name = 'default';
			select.id = 'default';
			select.onchange = function() {
				ModuleDesignerFields.updateField(this, 'default');
			};
			cell.appendChild(select);
		}
		var keys = deep_clone(app_lang.lists.keys[list]);
		var values = deep_clone(app_lang.lists.values[list]);
		if(keys.indexOf('') < 0) {
			keys.unshift('');
			values.unshift('');
		}
		for (var i =0; i < keys.length; i++) {
			var k = keys[i];
			var v = values[i];
			var opt = document.createElement('option');
			opt.value = k;
			opt.text = v;
			try {
				select.add(opt, null);
			}
			catch(e) {
				select.add(opt);
			}
			if (typeof(def) == 'undefined') def = k;
			if(k == this.editingField['default']) {
				opt.selected = true;
				def = k;
			}
		}
		if (this.editingField.module_builder == 'label_only')
			select.disabled = true;
	};
	
	this.renderDefaultTextArea = function(table, label)
	{
		if (!label) label = mod_string('LBL_DEFAULT_VALUE', 'ModuleDesigner');
		var row = table.insertRow(table.rows.length);
		this.renderLabel(row, label);

		var cell = row.insertCell(1);
		var input = document.createElement('textarea');
		input.size = 20;
		input.value = get_default(this.editingField['default'], '');
		input.rows = 8;
		input.cols = 40;
		input.onchange = function() {
			ModuleDesignerFields.updateField(this, 'default');
		};
		input.id = 'default';
		input.name = 'default';
		cell.appendChild(input);
		if (this.editingField.module_builder == 'label_only')
			input.disabled = true;
	};

	this.renderDefaultDate = function(table, label)
	{
		var row = table.insertRow(table.rows.length);
		this.renderLabel(row, label);
		var cell = row.insertCell(1);
		var select = document.createElement('select');
		select.name = 'ext1';
		select.id = 'ext1';
		for (var i in this.date_default_opts) {
			var opt = document.createElement('option');
			opt.value =i;
			opt.text = this.date_default_opts[i];
			if(i == this.editingField['default'])
				opt.selected = true;
			try {
				select.add(opt, null);
			}
			catch(e) {
				select.add(opt);
			}
		}
		select.onchange = function() {
			ModuleDesignerFields.updateField(this, 'default');
		};
		cell.appendChild(select);
		if (this.editingField.module_builder == 'label_only')
			select.disabled = true;
	};

	this.updateField = function(input, key)
	{
		var value;
		if (get_default(input.type) == 'checkbox')
			value = input.checked;
		else
			value = input.value;
		this.editingField[key] =value;
		this.renderForm();
	};
    
	this.displayElement = function(id, show, input)
    {
        var display = '';
        if (! show)
            display = 'none';
        if ($(id))
            $(id).style.display = display;
		if ($(input))
			$(input).disabled = this.editingField.module_builder == 'label_only';
    };
    
	this.displayRequired = function(show)
    {
        this.displayElement('req', show, 'required');
    };
	
	this.displayAudit = function(show)
    {
        this.displayElement('audit', show, 'audited');
    };
	
	this.displayMassUpdate = function(show)
    {
        this.displayElement('mass_update', show, 'massupdate');
    };

	this.saveField = function()
	{
		if (!this.validate())
			return;
		this.popup.hide();
		this.fields[this.editingId] = this.editingField;
		if (typeof(this.fields[this.editingId]['default'] == "undefined"))
			this.fields[this.editingId]['default'] = this.defaultDefault(this.editingField.type);
		if (this.editingField.type == 'ref' && !this.editingField.bean_name) {
			this.fields[this.editingId + '_module'] = {
				name: this.editingField.name + '_mb_module_',
				type: 'module_name'
			};
			this.fields[this.editingId].dynamic_module = this.editingField.name + '_mb_module_';
		} else {
			delete (this.fields[this.editingId].dynamic_module);
		}
		this.render();
	};

	this.newField = function()
	{
		this.resetErrors();
		this.editingId = 'newfield~' + this.nextId++;
		this.editingField = {name:'', vname: '', label: '', type: 'varchar'};
		this.renderForm();
		if (!this.popup) {
			this.popup = new SUGAR.ui.Dialog('field_editor', {content_elt: $('field_dialog'), width: '640px', title: mod_string('LBL_CREATE_DIALOG_TITLE', 'ModuleDesigner'), destroy_on_close: false});
		}
		this.popup.setTitle(mod_string('LBL_CREATE_DIALOG_TITLE', 'ModuleDesigner'));
		this.popup.render();
		this.popup.show();
	};

	this.isNewField = function(id)
	{
		return id.match(/^newfield~/);
	};

	this.resetErrors = function()
	{
		$('custom_errors').innerHTML = '';
		$('field_name').className = 'input-text';
        if ($('default'))
			$('default').className = '';
        if ($('len'))
			$('len').className = '';
	};

	this.validate = function()
	{
		this.errors = [];
		var ok = true;
		if (!this.validateFieldName()) ok = false;
		if (!this.validateDefaultValue()) ok = false;
		if (!this.validateSize()) ok = false;
		$('custom_errors').innerHTML = '';
		for (var i =0; i < this.errors.length; i++) {
			var div = document.createElement('div');
			div.className = 'error';
			div.appendChild(document.createTextNode(this.errors[i]));
			$('custom_errors').appendChild(div);
		}
		return ok;
	};

	this.validateFieldName = function()
	{
		var value = $('field_name').value;
		ok = value.match(/^[a-zA-Z]([A-Za-z0-9_])*$/);
		if (!ok) this.errors.push(mod_string('ERR_INVALID_NAME', 'ModuleDesigner'));

		if (ok) {
			for (var id in this.fields) {
				if (this.fields[id].name == value && id != this.editingId) {
					this.errors.push(mod_string('ERR_DUPLICATE_FIELD', 'ModuleDesigner'));
					ok = false;
					break;
				}
			}
		}

		if (!ok) $('field_name').className = 'input-text invalid';
		else $('field_name').className = 'input-text';
		return ok;
	};

	this.validateSize = function()
	{
		var ok = true;
		if (!$('len')) return true;
		var value = $('len').value;
        var val = '';
		switch ($('data_type').value) {
			case 'int':
				val =  parseInt(value);
				if ('' + val == 'NaN') val = 0;
				ok = val > 0 && val < 12;
				if (!ok) this.errors.push(mod_string('ERR_SIZE_1', 'ModuleDesigner'));
				break;
			case 'varchar':
				val =  parseInt(value);
				if ('' + val == 'NaN') val = 0;
				ok = val > 0 && val < 256;
				if (!ok) this.errors.push(mod_string('ERR_SIZE_2', 'ModuleDesigner'));
				break;
		}
		if (!ok) $('len').className = 'input-text invalid';
		else $('len').className = '';
		return ok;
	};

	this.validateDefaultValue = function()
	{
		var ok = true;
        if ($('default')) {
            var value = $('default').value;
            switch ($('data_type').value) {
                case 'int':
                    ok = (value == '') || ('' + parseInt(value)) != 'NaN';
					if (!ok) this.errors.push(mod_string('ERR_INT_VALUE', 'ModuleDesigner'));
                    break;
                case 'double':
                    ok = (value == '') || ('' + parseFloat(value)) != 'NaN';
					if (!ok) this.errors.push(mod_string('ERR_DEC_VALUE', 'ModuleDesigner'));
                    break;
                case 'date':
                    ok = validateDateInput($('default'));
					ok = true;
					if (!ok) this.errors.push(mod_string('ERR_DATE_VALUE', 'ModuleDesigner'));
                    break;
            }
            if (!ok) $('default').className = 'input-text invalid';
            else $('default').className = '';
        }
		return ok;
	};

	this.saveAll = function(form)
	{
		var fields = JSON.stringify(this.fields);
		return SUGAR.ui.sendForm(form, {fields: fields}, null);
	};

}();


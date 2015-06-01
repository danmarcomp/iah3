NewStudio = new function() {
	this.data_type = '';
	this.field_name = '';
	this.default_value = '';
    this.formula = '';
	this.fieldLabel = '';
	this.max_size = 50;
	this.required = false;
	this.audited = false;
	this.ext1 = '';
	this.dom_opts = [];
	this.date_default_opts = {};
	this.errors = [];

	this.init = function(params) {
		for (var p in params) {
			this[p] = params[p];
		}
	};

	this.renderForm = function() {
		table = $('custom_fields_body');
		while (table.rows.length > 0) {
			table.deleteRow(0);
		}

        this.displayAudit(true);
        this.displayMassUpdate(true);
        this.displayFormula(false);

		switch ($('data_type').value) {
			case 'ref':
				this.renderModulesList(table, mod_string('LBL_RELATED_TYPE', 'NewStudio'));
				break;
			case 'varchar':
				this.renderDefaultText(table);
				if (!this.max_size) this.max_size = 50;
				this.renderSize(table);
				break;
			case 'text':
				this.renderDefaultTextArea(table);
				break;
			case 'int':
				this.renderDefaultText(table);
				if (!this.max_size) this.max_size = 11;
				if (this.max_size > 11) this.max_size = 11;
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
				this.renderDropDownsList(table, SUGAR.language.getString('LBL_DROPDOWN_LIST'));
				this.renderDefaultDropdown(table, $('ext1').value);
				break;
			case 'multienum':
				this.renderDropDownsList(table, SUGAR.language.getString('LBL_MULTI_LIST'));
				this.renderDefaultDropdown(table, $('ext1').value);
				break;
			case 'date':
				this.renderDefaultDate(table, SUGAR.language.getString('LBL_DEFAULT_VALUE'));
				break;
			case 'url':
				this.renderDefaultText(table, SUGAR.language.getString('LBL_DEFAULT_URL'));
				break;
			case 'html':
				this.renderDefaultTextArea(table, SUGAR.language.getString('LBL_HTML'));
				break;
            case 'calculated':
                this.displayAudit(false);
                this.displayMassUpdate(false);
                this.displayFormula(true);
                this.renderFormulaInput(table);
                break;
		}
	};
	
	this.renderModulesList = function(table, label)
	{
		var row = table.insertRow(table.rows.length);
		this.renderLabel(row, label);
		var cell = row.insertCell(1);
		var select = document.createElement('select');
		select.name = 'ext1';
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
			if (i == this.ext1)
				opt.selected = true;
		}
		select.onchange = function() {
			NewStudio.ext1 = this.value;
		};
		//select.disabled = !this.isNewField(this.editingId);
		cell.appendChild(select);
	};

    this.displayAudit = function(show)
    {
        this.displayElement('audit_row', show);
    };

    this.displayMassUpdate = function(show)
    {
        this.displayElement('mass_update_row', show);
    };

    this.displayFormula = function(show)
    {
        this.displayElement('custom_fields_formula', show);
    };

	this.renderDefaultText = function(table, label)
	{
		if (!label) label = SUGAR.language.getString('LBL_DEFAULT_VALUE');
		return this.renderTextField(table, label, 'default_value', 20);
	};

	this.renderDefaultBool = function(table)
	{
		this.renderCheckbox(table,SUGAR.language.getString('LBL_DEFAULT_VALUE'), 'default_value');
	};

	this.renderDefaultTextArea = function(table, label)
	{
		if (!label) label = SUGAR.language.getString('LBL_DEFAULT_VALUE');
		var row = table.insertRow(table.rows.length);
		this.renderLabel(row, label);

		var cell = row.insertCell(1);
		var input = document.createElement('textarea');
		input.size = 20;
		input.value = this.default_value;
		input.rows = 8;
		input.cols = 60;
		input.onchange = function() {
			NewStudio.default_value = this.value;
		};
		input.id = 'default_value';
		input.name = 'default_value';
		cell.appendChild(input);
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
			if(this.dom_opts[i] == this.ext1)
				opt.selected = true;
		}
		select.onchange = function() {
			NewStudio.ext1 = this.value;
			NewStudio.renderDefaultDropdown(table, this.value, $('default_value'));
		};
		cell.appendChild(select);
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
			if(i == this.ext1)
				opt.selected = true;
			try {
				select.add(opt, null);
			}
			catch(e) {
				select.add(opt);
			}
		}
		select.onchange = function() {
			NewStudio.default_value = this.value;
		};
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
			this.renderLabel(row, SUGAR.language.getString('LBL_DEFAULT_VALUE'));
			var cell = row.insertCell(1);
			select = document.createElement('select');
			select.name = 'default_value';
			select.id = 'default_value';
			select.onchange = function() {
				NewStudio.default_value = this.value;
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
			if(k == this.default_value) {
				opt.selected = true;
				def = k;
			}
		}
		this.default_value = def;
	};

	this.renderSize = function(table)
	{
		return this.renderTextField(table, SUGAR.language.getString('LBL_MAX_SIZE'), 'max_size', 5);
	};

	this.renderTextField = function(table, label, name, size) {
		var row = table.insertRow(table.rows.length);
		this.renderLabel(row, label);

		var cell = row.insertCell(1);
		var input = document.createElement('input');
		input.name = name;
		input.id = name;
		input.size = size;
		if (typeof(this[name]) != 'undefined') {
			input.value = this[name];
			input.onchange = function() {
				NewStudio[name] = this.value;
			}
		}
		cell.appendChild(input);
		return input;
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
		if (typeof(this[name]) != 'undefined') {
			input.checked = this[name];
			input.onclick = function() {
				NewStudio[name] = this.checked ? 1 : 0;
			}
		}
		cell.appendChild(input);
	};
		
	this.renderLabel = function(row, label)
	{
		var cell = row.insertCell(0);
		cell.className = 'dataLabel';
		cell.style.verticalAlign = 'top';
		cell.appendChild(document.createTextNode(label));
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
        input.value = this.formula;
        input.onchange = function() {
            NewStudio['formula'] = this.value;
        };
        cell.appendChild(input);
        return input;
    };

    this.addToFormula = function(formula_elt)
    {
        if ($('formula') && formula_elt.innerHTML != '') {
            var postfix = '';
            if (formula_elt.id.indexOf('newfunc') != -1)
                postfix = '(';
            $('formula').value = $('formula').value + formula_elt.innerHTML + postfix;
            this.formula = $('formula').value;
        }
    };

	this.save = function()
	{
		if (!this.validate()) return false;
	};

	this.validate = function()
	{
		this.errors = [];
		var ok = true;
		if (!this.validateFieldName()) ok = false;
		if (!this.validateDefaultValue()) ok = false;
		if (!this.validateSize()) ok = false;
        if (!this.validateFormula()) ok = false;
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
		if (!ok) $('field_name').className = 'input-text invalid';
		else $('field_name').className = 'input-text';
		if (!ok) this.errors.push(SUGAR.language.getString('LBL_INVALID_FIELD_NAME'));
		return ok;
	};

	this.validateSize = function()
	{
		var ok = true;
		if (!$('max_size')) return true;
		var value = $('max_size').value;
        var val = '';
		switch ($('data_type').value) {
			case 'int':
				val =  parseInt(value);
				if ('' + val == 'NaN') val = 0;
				ok = val > 0 && val < 12;
				if (!ok) this.errors.push(SUGAR.language.getString('LBL_INVALID_SIZE'));
				break;
			case 'varchar':
				val =  parseInt(value);
				if ('' + val == 'NaN') val = 0;
				ok = val > 0 && val < 256;
				if (!ok) this.errors.push(SUGAR.language.getString('LBL_INVALID_SIZE2'));
				break;
		}
		if (!ok) $('max_size').className = 'input-text invalid';
		else $('max_size').className = '';
		return ok;
	};

	this.validateDefaultValue = function()
	{
		var ok = true;
        if ($('default_value')) {
            var value = $('default_value').value;
            switch ($('data_type').value) {
                case 'int':
                    ok = (value == '') || ('' + parseInt(value)) != 'NaN';
					if (!ok) this.errors.push(SUGAR.language.getString('LBL_INVALID_INT'));
                    break;
                case 'double':
                    ok = (value == '') || ('' + parseFloat(value)) != 'NaN';
					if (!ok) this.errors.push(SUGAR.language.getString('LBL_INVALID_DEC'));
                    break;
                case 'date':
                    ok = validateDateInput($('default_value'));
					ok = true;
					if (!ok) this.errors.push(SUGAR.language.getString('LBL_INVALID_DATE'));
                    break;
            }
            if (!ok) $('default_value').className = 'input-text invalid';
            else $('default_value').className = '';
        }
		return ok;
	};

    this.validateFormula = function()
   	{
   		var ok = true;
        if ($('data_type').value == 'calculated') {
            var value = $('formula').value;
            ok = (value != '');
			if (!ok) this.errors.push(SUGAR.language.getString('LBL_INVALID_FORMULA'));
            if (!ok) $('formula').className = 'input-textarea invalid';
            else $('formula').className = '';
        }
   		return ok;
   	};

    this.displayElement = function(id, show)
    {
        var display = '';
        if (! show)
            display = 'none';
        if ($(id))
            $(id).style.display = display;
    };

}();

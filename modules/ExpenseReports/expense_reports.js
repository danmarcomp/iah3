
ExpenseEditor = new function() {
	var editor = this;
	
	this.id = 'ExpenseEditor';
	this.readOnly = false;
	this.topItems = {};
	this.allItems = {};
	this.itemCnt = 1;
	this.elements = {};
	this.itemsOrder = [];
	this.dialogs = {};
	this.Categories = {};

	this.init = function()
	{
        this.readOnly = false;
        this.topItems = {};
        this.allItems = {};
        this.itemCnt = 1;
        this.elements = {};
        this.itemsOrder = [];
        this.dialogs = {};
        this.Categories = {};
		this.fileInputs = {};
	};
	
	this.setup = function() {
		SUGAR.ui.onInitForm(this.form, function() {
			editor.paint();
			if(! editor.readOnly)
				editor.updateTotals();
		});
		this.currency_input = SUGAR.ui.getFormInput(this.form, 'currency_id') || this.currencyStub('currency_id', this.currency_id);
		SUGAR.ui.attachInputEvent(this.currency_input, 'onrateupdate', function(old_rate, new_rate) {
			editor.updateExchangeRate(old_rate, new_rate);
		});
		this.advance_currency = SUGAR.ui.getFormInput(this.form, 'advance_currency_id') || this.currencyStub('advance_currency_id', this.advance_currency_id);
		SUGAR.ui.attachInputEvent(this.advance_currency, 'onrateupdate', function(old_rate, new_rate) {
			editor.updateAdvanceExchangeRate(old_rate, new_rate);
		});
		SUGAR.ui.attachFormInputEvent(this.form, 'advance', 'onchange', function() {
			editor.updateAdvanceAmount();
		});
		this.detectHTML5();
	};

	this.detectHTML5 = function()
	{
		var input = document.createElement("input");
		input.setAttribute("type", "email");
		this.html5 = (input.type !== "text");
		delete input;
	};
	
	this.currencyStub = function(id, value) {
		var sel = new SUGAR.ui.CurrencySelect(id, {init_value: value, form: this.form});
		sel.setup();
		return sel;
	};

    this.setItems = function(items)
    {
        this.topItems = items;
    };

	this.addItem = function(parentId, values, keepId)
	{
		var id ;
		if (!keepId) {
			id = '~newitem~' + this.itemCnt++;
		} else  {
			id = values.id;
		}
		var item = {
			category: '',
			total: 0.0,
			total_usdollar: 0.0,
			amount: 0.0,
			amount_usdollar: 0.0,
			tax_class_id : '',
			quantity: 0.0,
			paid_rate : 0.0,
			paid_rate_usd : 0.0,
			description : '',
			'date' : '',
			attachmentSrc : null,
			unit: ''
		};
		if (values) {
			for (var k in values) {
				item[k] = values[k];
			}
		}
		if (parentId) {
			delete item.attachmentSrc;
		}
		item.children = {};
		item.itemsOrder = [];
		item.split = 0;
		item.id = id;
		item.parent_id = parentId;

		if (blank(item.category)) {
			for (var i = 0; i < this.Categories.order.length; i++) {
				item.category = this.Categories.order[i];
				var category = this.Categories.values[item.category];
				item.unit = category.expenses_unit;
				item.paid_rate = category.paid_rate_usd * this.currencyRate();
				break;
			}
		}

		if (parentId) {
			var parentItem = this.topItems[parentId];
			if (!parentItem) {
				return;
			}
			parentItem.children[id] = item;
			parentItem.itemsOrder.push(id);
			parentItem.split = 1;
			parentItem.expanded = true;
			item['date'] = parentItem['date'];
		} else {
			this.topItems[id] = item;
			this.itemsOrder.push(id);
		}
		this.allItems[id] = item;
		return item;
	};


	this.paint = function()
	{
        if (!this.readOnly) {
        	SUGAR.ui.getFormInput(this.form, 'total_pretax').setDisabled(true);
        	SUGAR.ui.getFormInput(this.form, 'total_amount').setDisabled(true);
        	SUGAR.ui.getFormInput(this.form, 'balance').setDisabled(true);
        }
		for (var i in this.fileInputs) {
			this.form.appendChild(this.fileInputs[i]);
		}
		$('expense_groups').innerHTML = '';
		var tbl = createElement2('table', {width: '100%'});
		this.addElement('expenses_table', tbl);
		var rCnt = 0;
		var row = tbl.insertRow(rCnt++);
		this.createTableHeader(row);

		for (var i = 0; i < this.itemsOrder.length; i++) {
			var iId = this.itemsOrder[i];
			var item = this.topItems[iId];
			var row = tbl.insertRow(rCnt++);
			this.addItemLine(item, row);

			if (item.split && item.expanded) {
				for (var j = 0; j < item.itemsOrder.length; j++) {
					var cId = item.itemsOrder[j];
					var child = item.children[cId];
					var row = tbl.insertRow(rCnt++);
					this.addItemLine(child, row);
				}

				if (!this.readOnly) {
					var row = tbl.insertRow(rCnt++);
					var spacerCell = row.insertCell(0);
					spacerCell.style.width = '10px';
					spacerCell.colSpan = 3;
					spacerCell.innerHTML = '&nbsp;';
					var cell = row.insertCell(1);
					button = createIconLink('plus', 'LBL_ADD_SPLIT');
					button.iId = iId;
					button.onclick = function()
					{
						var newItem = ExpenseEditor.addItem(this.iId, ExpenseEditor.allItems[this.iId]);
						if (ExpenseEditor.hasUnits(newItem)) {
							newItem.amount = newItem.quantity * newItem.paid_rate;
						} else {
							newItem.amount = 0;
						}
						ExpenseEditor.recalcTaxes(newItem);
						ExpenseEditor.recalculateSplit(newItem.parent_id);
						ExpenseEditor.paint();
						var categorySelect = ExpenseEditor.getElement([newItem.id, 'category']);
						categorySelect.focus();
						return false;
					};
					cell.appendChild(button);
				}
			}
		}
		$('expense_groups').appendChild(tbl);
	};
	
	this.createTableHeader = function(row)
	{
		var headers = [
			['', {width: '20px'}],
			['LBL_ITEM_DATE', {}],
			['LBL_ITEM_CATEGORY', {colSpan : 2}],
			['LBL_ITEM_QUANTITY', {colSpan : 2}],
			['LBL_ITEM_RATE', {colSpan : 2}],
			['LBL_ITEM_PRETAX', {}],
			['LBL_ITEM_TAX', {}],
			['LBL_ITEM_AMOUNT', {}],
			['LBL_ITEM_DESCRIPTION', {}]
		];
		var cCnt = 0;
		var cell;
		for (var i = 0; i < headers.length; i++) {
			cell = row.insertCell(cCnt++);
			cell.appendChild(headers[i][0] ? document.createTextNode(mod_string(headers[i][0])) : nbsp());
			setAttrs(cell, {'class': 'dataLabel', style:{fontWeight:'bold', whiteSpace: 'nowrap'} });
			setAttrs(cell, headers[i][1]);
		}

	};

	this.addUploadLine = function(item, row)
	{
	};

	this.addItemLine = function(item, row)
	{
		setAttrs(row, {style: {lineHeight: '20px', verticalAlign: 'middle'}});
		var cCnt = 0;

		var actionCell = row.insertCell(cCnt++);
		var button = null;
		var decimals = this.currencyDecimalPlaces();

		if (!item.split && !item.parent_id) {
			if (!this.readOnly) {
				button = createIconLink('split', '');
				var iId = item.id;
				button.onclick = function()
				{
					var newItem = ExpenseEditor.addItem(iId, item);
					ExpenseEditor.recalcTaxes(newItem);
					ExpenseEditor.paint();
					if (ExpenseEditor.hasUnits(newItem)) {
						var quantityInput = ExpenseEditor.getElement([newItem.id, 'quantity']);
						quantityInput.focus();
						quantityInput.select();
					} else {
						var amountInput = ExpenseEditor.getElement([newItem.id, 'amount']);
						amountInput.focus();
						amountInput.select();
					}
					return false;
				};
			} else {
				button = createIconLink('blank', '');
			}
		} else {
			if (item.split) {
				if (item.expanded) {
					button = createIconLink('collapse', '');
					button.onclick = function()
					{
						item.expanded = false;
						ExpenseEditor.paint();
						return false;
					};
				} else {
					button = createIconLink('expand', '');
					button.onclick = function()
					{
						item.expanded = true;
						ExpenseEditor.paint();
						return false;
					};
				}
			}
		}
		
		if (button) {
			actionCell.appendChild(button);
		} else {
			actionCell.innerHTML = '&nbsp;';
		}

		var dateCell = row.insertCell(cCnt++);
		setAttrs(dateCell, {style: {width: '110px', whiteSpace: 'nowrap'}});
		if (blank(item.parent_id)) {
            var date_id = 'calendar_' + this.itemCnt++;
			var dateInput = new SUGAR.ui.DateInput(date_id, {
				init_value: item['date'],
				onchange: function() {
					item['date'] = this.getValue();
				},
				disabled: this.readOnly
			});
			dateCell.appendChild(dateInput.render());
		} else {
			dateCell.innerHTML = '&nbsp;';
		}


		if (item.parent_id) {
			var spacerCell = row.insertCell(cCnt++);
			spacerCell.style.width = '10px';
			spacerCell.innerHTML = '&nbsp;';
		}
		var categoryCell = row.insertCell(cCnt++);
		categoryCell.style.textAlign = 'right';
		categoryCell.style.paddingLeft = '5px';
		categoryCell.style.paddingRight = '5px';
		if(! item.parent_id)
			categoryCell.colSpan = 2;
		var options = this.Categories;
		var attrs = {
			onchange: function(value) {
				item.category = value;
				item.quantity = 0.0;
				item.amount = 0.0;
				item.unit = editor.Categories.values[value].expenses_unit;
				var rate = editor.currencyRate();
				item.paid_rate = editor.paidRate(value) * rate;
				editor.recalcTaxes(item);
				if (item.parent_id) {
					editor.recalculateSplit(item.parent_id);
				}
				editor.updateTotals();
				editor.paint();
			}
		}
		var categorySelect = this.createSelect(options.order, options.values, item.category, attrs, 'name');
		categoryCell.appendChild(categorySelect);
		this.addElement([item.id, 'category'], categorySelect);

		var quantityCell = row.insertCell(cCnt++);
		if (this.hasUnits(item) && !item.split) {
			quantityCell.style.width = '80px';
			var quantityInput = this.createTextInput({
				format: 'float',
				decimals: 2,
				style: {width: '60px'},
				init_value: parseFloat(item.quantity),
				onchange: function() {
					var val = this.getValue(true);
					item.quantity = val;
					item.amount = val * item.paid_rate;
					editor.recalcTaxes(item);
					if (item.parent_id) {
						editor.recalculateSplit(item.parent_id);
					}
					editor.updateTotals();
					editor.paint();
				}
			});
			quantityCell.appendChild(quantityInput);
			this.addElement([item.id, 'quantity'], quantityInput);

			var unitsCell = row.insertCell(cCnt++);
			setAttrs(unitsCell, {style: {width: '120px', whiteSpace: 'nowrap'}});
			unitsCell.appendChild(document.createTextNode(ExpenseEditor.unitName(item)));
			unitsCell.appendChild(document.createTextNode(' x '));

			var rateCell = row.insertCell(cCnt++);
			var rateInput = this.createTextInput({
				format: 'currency',
				decimals: decimals,
				style: {width: '60px'},
				init_value: parseFloat(item.paid_rate),
				onchange: function() {
					var val = this.getValue(true);
					item.paid_rate = val;
					item.amount = val * item.quantity;
					editor.recalcTaxes(item);
					if (item.parent_id) {
						editor.recalculateSplit(item.parent_id);
					}
					editor.updateTotals();
					editor.paint();
				}
			});
			rateCell.appendChild(rateInput);
			this.addElement([item.id, 'paid_rate'], rateInput);

			var sepCell = row.insertCell(cCnt++);
			sepCell.appendChild(document.createTextNode(' = '));
		} else {
			quantityCell.style.width = '160px';
			quantityCell.colSpan = 4;
			quantityCell.innerHTML = '&nbsp';
		}

		var amountCell = row.insertCell(cCnt++);
		var amountInput = this.createTextInput({
			format: 'currency',
			decimals: decimals,
			style: {width: '60px'},
			init_value: parseFloat(item.amount),
			readOnly: (item.split || this.hasUnits(item)),
			onchange: function() {
				var val = this.getValue(true);
				item.amount = val;
				editor.recalcTaxes(item);
				if (item.parent_id) {
					editor.recalculateSplit(item.parent_id);
				}
				editor.updateTotals();
				editor.paint();
			}
		});
		amountCell.appendChild(amountInput);
		this.addElement([item.id, 'amount'], amountInput);

		var taxCell = row.insertCell(cCnt++);
		taxCell.style.paddingLeft = '5px';
		var order = SysData.taxcodes_order, options = SysData.taxcodes;
		var attrs = {
			onchange: function(value) {
				item.tax_class_id = value;
				editor.recalcTaxes(item);
				if (item.parent_id) {
					editor.recalculateSplit(item.parent_id);
				}
				editor.updateTotals();
				editor.paint();
			}
		}
		var taxSelect = this.createSelect(order, options, item.tax_class_id||'-99', attrs, 'code');
		if (item.split) {
			taxSelect.disabled = 'disabled';
		}
		taxCell.appendChild(taxSelect);
		this.addElement([item.id, 'tax'], taxSelect);


		var totalCell = row.insertCell(cCnt++);
		var totalInput = this.createTextInput({
			format: 'currency',
			decimals: decimals,
			style: {width: '60px'},
			init_value: parseFloat(item.total),
			readOnly: true
		});
		totalCell.appendChild(totalInput);
		this.addElement([item.id, 'total'], totalInput);
	
		var descCell = row.insertCell(cCnt++);
		var descInput = this.createTextInput({
			style: {width: '180px'},
			init_value: item.description,
			onchange: function() {
				item.description = this.getValue();
			}
		});
		descCell.appendChild(descInput);
		this.addElement([item.id, 'description'], descInput);

		if (!this.readOnly) {
			var cell = row.insertCell(cCnt++);
			button = createIconLink('down', '');
			button.onclick = function()
			{
				ExpenseEditor.moveDown(item.id, item.parent_id);
				ExpenseEditor.paint();
				return false;
			};
			cell.appendChild(button);

			var cell = row.insertCell(cCnt++);
			button = createIconLink('up', '');
			button.onclick = function()
			{
				ExpenseEditor.moveUp(item.id, item.parent_id);
				ExpenseEditor.paint();
				return false;
			};
			cell.appendChild(button);

			var cell = row.insertCell(cCnt++);
			button = createIconLink(
				'remove', '',  
				function() {
					ExpenseEditor.removeItem(item.id, item.parent_id);
					ExpenseEditor.paint();
					return false;
				}
			);
			cell.appendChild(button);
		}

		if (item.split || !item.parent_id) {
			var cell1 = row.insertCell(cCnt++);
			var cell2 = row.insertCell(cCnt++);
			if (item.attachmentSrc || item.hasNewAttachment) {
				if (item.attachmentSrc) {
					var img = createElement2('img', {src: item.iconSrc, style:{border: 'none'}});
					var a = createElement2('a', {href: item.attachmentSrc, target: '_blank'});
					a.appendChild(img);
					cell1.appendChild(a);
				} else {
					cell1.appendChild(nbsp());
				}

				if (!this.readOnly) {
					var div = createElement2('div', {className: 'ticon-minus', style:{cursor:'pointer'}, title:'Remove Attachment'});
					div.onclick = function() {
						ExpenseEditor.removeAttachment(item);
					}
					cell2.appendChild(div);
				} else {
					cell2.appendChild(nbsp());
				}
				

			} else {
				cell1.appendChild(nbsp());
				cell2.appendChild(nbsp());
			}
			if (!this.readOnly /*|| (item.attachmentSrc && this.isImage(item.attachmentSrc))*/) {
				var cell = row.insertCell(cCnt++);
				button = createIconLink('note', '');
				button.onclick = function()
				{
					$('upload_' + item.id).click();
					return false;
				};
				var container = createElement2('div', {style: {position: 'relative'}});
				button.style.position = 'absolute';
				button.style.top = '0px';
				button.style.left = '0px';
				button.style.zIndex = 1;
				var fileInput = $('upload_' + item.id);
				if (!fileInput) {
					fileInput = createElement2('input', {type:'file', id: 'upload_' + item.id, name: 'upload_' + item.id, value: item.uploadSrc});
					fileInput.onchange = function() {
						if (ExpenseEditor.html5) {
							button.title = this.value;
						} else {
							this.title = this.value;
						}
						item.hasNewAttachment = true;
						ExpenseEditor.paint();
					};
					if (!this.html5) {
						fileInput.style.position = 'relative';
						fileInput.style.width = '20px';
						fileInput.style.right = '20px';
						fileInput.style.zIndex = 2;
						fileInput.style.filter = 'alpha(opacity: 0)';
						fileInput.style.opacity = 0;
						fileInput.style.cursor = 'pointer';
					} else {
						fileInput.setAttribute('style', "visibility:hidden;position:absolute;top:-50;left:-50");
						this.form.appendChild(fileInput);
					}
					this.fileInputs[item.id] = fileInput;
				}
				container.style.width='19px';
				container.style.height='19px';
				container.style.overflow = 'hidden';
				container.appendChild(button);
				if (!this.html5) {
					container.appendChild(fileInput);
				}
				cell.appendChild(container);
			}
		}
	};

	this.removeAttachment = function(item)
	{
		item.attachmentSrc = null;
		item.hasNewAttachment = false;
		item.removeOriginalAttachment = true;
		this.paint();
	};

	this.recalcTaxes = function(item)
	{
		var rates = [];
		var tid = item.tax_class_id;
		if (tid) {
			if (SysData.taxcodes[tid]) {
				rates = SysData.taxcodes[tid].rates;
			}
		}
		item.total = 0.0;
		var base = parseFloat(item.amount);
		for (var i = 0; i < rates.length; i++) {
			item.total += base * rates[i].rate / 100;
		}
		item.total += base;
	};

	this.addElement = function(id, elt)
	{
		if(typeof(id) == 'object')
			id = id.join(':');
		this.elements[id] = elt;
	};
	
	this.getElement = function(id)
	{
		if(typeof(id) == 'object')
			id = id.join(':');
		if(this.elements[id])
			return this.elements[id];
		return null;
	};

	this.createSelect = function(values, labels, selected, attrs, labelKey)
	{	
		if(! attrs) attrs = {};
		if(! blank(attrs.readOnly)) {
			delete attrs.readOnly;
			attrs.disabled = true;
		}
		if(this.readOnly)
			attrs.disabled = true;
	
		var outer = createElement2('span', {className: 'list-edit-value input-inline-arrow'});
		var opts = {keys: values, values: labels, display_name: labelKey || 'name'};
		var select_opts = new SUGAR.ui.SelectOptions(opts);
		if(attrs.options_width) select_opts.width = attrs.options_width;
		attrs.options = select_opts;
	
		var lbl = labels[selected];
		if (! isset(lbl)) lbl = '--';
		else if(YLang.isObject(lbl)) lbl = lbl[opts.display_name];
		SUGAR.ui.setElementText(outer, lbl);
	
		if(! attrs.disabled) {
			var quicksel = new SUGAR.ui.QuickSelect(attrs);
			quicksel.initSource(outer, selected, attrs.onchange);
		} else {
			outer.className += ' disabled';
		}
	
		return outer;
	};

	this.checkAmount = function(value)
	{
		var ret = parseFloat(value);
		if ('' + ret == 'NaN') ret = 0;
		return ret;
	};

	this.recalculateSplit = function(topId)
	{
		var amount = 0.0;
		var total = 0.0;
		for (var cId in this.topItems[topId].children) {
			var child = this.topItems[topId].children[cId];
			amount += parseFloat(child.amount);
			total += parseFloat(child.total);
		}
		this.topItems[topId].amount = amount;
		this.topItems[topId].total = total;

		var amountInput = this.getElement([topId, 'amount']);
		amountInput.value = this.formatCurrency(amount);
		
		amountInput = this.getElement([topId, 'total']);
		amountInput.value = this.formatCurrency(total);
		
		this.updateTotals();
	};

	this.updateTotals = function()
	{
		var total = 0.00;
		var amount = 0.00;
		for (var iId in this.topItems) {
			amount += parseFloat(this.topItems[iId].amount);
			total += parseFloat(this.topItems[iId].total);
		}
		var taxes = this.roundNumber(total - amount);
		$('tax').value = this.formatCurrency(taxes);
        $('tax_display').value = '';
		SUGAR.ui.getFormInput(this.form, 'total_pretax').setValue(this.formatCurrency(amount));
        SUGAR.ui.getFormInput(this.form, 'total_amount').setValue(this.formatCurrency(total));
		$('tax_display').value += '' + this.formatCurrency(taxes);
  
		try {
			var totalUSD = total / this.currencyRate();
            var adv_inp = SUGAR.ui.getFormInput(this.form, 'advance');
            var advance = 0;
            if (adv_inp)
                advance = adv_inp.getValue(true);
			var advanceUSD = advance / this.advanceCurrencyRate();
			var balanceUSD = totalUSD - advanceUSD;

			var balance = balanceUSD * this.currencyRate();
            var a = this.formatCurrency(balance);
            SUGAR.ui.getFormInput(this.form, 'balance').setValue(this.formatCurrency(balance));
		} catch (e) {
		}
	};

	this.moveDown = function(iId, parentId)
	{
		var order;
		if (parentId) {
			order = this.topItems[parentId].itemsOrder;
		} else {
			order = this.itemsOrder;
		}
		for (var i = 0; i < order.length; i++) {
			if (order[i] == iId) {
				if (i < order.length - 1) {
					order[i] = order[i+1];
					order[i+1] = iId;
					this.paint();
				}
			}
		}
	};

	this.moveUp = function(iId, parentId)
	{
		var order;
		if (parentId) {
			order = this.topItems[parentId].itemsOrder;
		} else {
			order = this.itemsOrder;
		}
		for (var i = 0; i < order.length; i++) {
			if (order[i] == iId) {
				if (i > 0) {
					order[i] = order[i-1];
					order[i-1] = iId;
					this.paint();
				}
			}
		}
	};

	this.removeItem = function(iId, parentId)
	{
		var order;
		if (parentId) {
			order = this.topItems[parentId].itemsOrder;
		} else {
			order = this.itemsOrder;
		}
		for (var i = 0; i < order.length; i++) {
			if (order[i] == iId) {
				order.splice(i, 1);
				delete(this.allItems[iId]);
				if (parentId) {
					delete(this.allItems[parentId].children[iId]);
					if (order.length == 0) {
						this.allItems[parentId].split = 0;
					}
				} else {
					delete(this.topItems[iId]);
				}
				if (parentId) {
					this.recalculateSplit(parentId);
				}
				this.paint();
			}
		}
		if (this.fileInputs[iId]) {
			this.form.removeChild(this.fileInputs[iId]);
			this.fileInputs[iId] = null;
			delete this.fileInputs[iId];
		}
	};

	this.getItemsJSON = function()
	{
		var order = 1;
		var ret = [];
		var rate = this.currencyRate();
		for (var i = 0; i < this.itemsOrder.length; i++) {
			var item = this.topItems[this.itemsOrder[i]];
			var copy = deep_clone(item);
			copy.line_order = order++;
			delete(copy.children);
			delete(copy.itemsOrder);
			copy.total_usdollar = copy.total / rate;
			copy.amount_usdollar = copy.amount / rate;
			copy.paid_rate_usd = copy.paid_rate / rate;
			ret.push(copy);
			for (var j = 0; j < item.itemsOrder.length; j++) {
				var child = deep_clone(item.children[item.itemsOrder[j]]);
				child.line_order = order++;
				child['date'] = item['date'];
				delete child.children;
				delete child.itemsOrder;
				child.amount_usdollar = child.amount / rate;
				child.paid_rate_usd = child.paid_rate / rate;
				ret.push(child);
			}
		}
		return JSON.stringify(ret);
	};

	this.createInput = function(attrs)
	{
		if (typeof(attrs) != 'object') {
			attrs = {};
		}
		if (this.readOnly) {
			attrs.readOnly = 'readOnly';
		}
		var input = document.createElement('input');
		setAttrs(input, attrs);
		return input;

	};
	
	this.createTextInput = function(attrs) {
		if(typeof(attrs) != 'object')  attrs = {};
		if (this.readOnly)
			attrs.readOnly = true;
		var input;
		if(isset(attrs.rows))
			input = new SUGAR.ui.TextAreaInput(null, attrs);
		else
			input = new SUGAR.ui.TextInput(null, attrs);
		SUGAR.ui.registerInput(this.form, input);
		return input.render();
	}
	
	this.updateExchangeRate = function (old_rate, new_rate)
	{
		for (var i = 0; i < this.itemsOrder.length; i++) {
			var item = this.topItems[this.itemsOrder[i]];
			if (this.hasUnits(item) && !item.split) {
				item.paid_rate = item.paid_rate / old_rate * new_rate;
				item.amount = item.paid_rate * item.quantity;
			} else {
				item.amount = item.amount / old_rate * new_rate;
			}
			for (var j = 0; j < item.itemsOrder.length; j++) {
				var child = item.children[item.itemsOrder[j]];
				if (this.hasUnits(child)) {
					child.paid_rate = child.paid_rate / old_rate * new_rate;
					child.amount = child.paid_rate * child.quantity;
				} else {
					child.amount = child.amount / old_rate * new_rate;
				}
				this.recalcTaxes(child);
			}
			this.recalcTaxes(item);
		}
		this.updateTotals();
		this.paint();
	};

	this.updateAdvanceExchangeRate = function (old_rate, new_rate)
	{
        /*var form = document.DetailForm;
        var adv_inp = document.DetailForm.advance;
		var numVal = validateNumberInput(adv_inp);*/
		this.updateTotals();
		this.paint();
	};

	this.updateAdvanceAmount = function ()
	{
        //var adv_inp = document.DetailForm.advance;
		//var numVal = validateNumberInput(adv_inp);
        this.updateTotals();
		this.paint();
	};

	this.isImage = function (src)
	{
		return src.match(/\.(gif|jpg|jpeg|png)$/i);
	};

	this.isPDF = function (src)
	{
		return src.match(/\.pdf$/i);
	}

	this.hasUnits = function(item)
	{
		if (!this.Categories.values[item.category]) return false;
		return !blank(this.Categories.values[item.category].expenses_unit);
	}

	this.unitName = function(item)
	{
		return SUGAR.language.get('app_list_strings', 'expense_units_dom')[item.unit];
	}
	
	this.paidRate = function(category)
	{
		if (!this.Categories.values[category]) return "";
		return this.Categories.values[category].paid_rate_usd;
	}
	
	this.formatCurrency = function(val, noRound) {
		if(! isset(val) || val === '') return '';
		var decimals = this.currencyDecimalPlaces();
		if(noRound)
			return stdFormatNumber(restrictDecimals(parseFloat(val), decimals));
		return stdFormatNumber(parseFloat(val * 100).toFixed(decimals) / 100, decimals, decimals);
	};

    this.roundNumber = function(val) {
        var decimals = this.currencyDecimalPlaces();
        return parseFloat(val * 100).toFixed(decimals) / 100;
    };

	this.currencyDecimalPlaces = function(currency_id) {
		return this.currency_input.getDecimals(currency_id);
	};
	
	this.currencyRate = function(currency_id) {
		return this.currency_input.getRate(true, currency_id);
	};

	this.advanceCurrencyRate = function(currency_id) {
		return this.advance_currency.getRate(true, currency_id);
	};
	
	this.beforeSubmitForm = function(form) {
        if (form) {
            if ($('tax'))
                $('tax').value = stdUnformatNumber($('tax').value);
            var currency_fields = ['balance', 'total_amount', 'total_pretax'];
            var field = '';
            var field_input = null;

            for (var i = 0; i < currency_fields.length; i++) {
                field = currency_fields[i];
                field_input = SUGAR.ui.getFormInput(form, field);
                if (field_input)
                    field_input.setValue(stdUnformatNumber(field_input.getValue()));
            }

            if(! this.readOnly) {
                var input = form.line_items;
                if(! input) input = createElement2('input', {type: 'hidden', name: 'line_items'}, null, form);
                input.value = this.getItemsJSON();
            }
        }
	};
	
	return this;
}();

function createIconLink(icon, text, onclick, attrs) {
	if(typeof(attrs) != 'object')  attrs = {};
	
	if((icon == 'up' || icon == 'down' || icon == 'remove' || icon == 'note') && ! text) {
		if(icon == 'remove') icon = 'delete';
		var elt = createElement2('div',
			{className: 'input-icon icon-'+icon+' active-icon', onclick: onclick});
		setAttrs(elt, attrs);
		return elt;
	}
	
	var link = createElement2('a', {href: '#'});
	link.style.whiteSpace = 'nowrap';
	if(isset(onclick))
		link.onclick = onclick;
	link.className = 'listViewTdToolsS1';
	setAttrs(link, attrs);
	if(icon === 'plus')
		icon = createElement2('div', {className: 'input-icon icon-add active-icon'});
	else if(typeof(icon) == 'string')
		icon = get_icon_image(icon);
	if(! blank(icon)) {
		if(typeof icon == 'string')
			link.innerHTML = icon;
		else
			link.appendChild(icon);
		link.appendChild(nbsp());
	}
	if(text)
		link.appendChild(createTextLabel(text));
	return link;
}

function createTextLabel(lbl, no_translate) {
	if(blank(no_translate))
		lbl = mod_string(lbl);
	var elt = document.createTextNode(lbl);
	return elt;
}

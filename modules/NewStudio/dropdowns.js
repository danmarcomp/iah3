DropdownEditor = new function() {

	this.header_rendered = false;
	this.activeModule = 'app';
	this.activeDropdown = '';
	this.activeLanguage = '';
	this.activeEditor = null;
	this.undoButton = null;
	this.undoHistory = [];
	this.deleted = {};

	this.init = function(data) {
		for (var i in data) {
			this[i] = data[i];
			if (i == 'languages') {
				for (var j in data[i]) {
					this.activeLanguage = j;
					break;
				}
			}
		}
	};

	this.render = function()
	{
		if (!this.header_rendered) this.render_header();
		this.renderOptions();
	};

	this.render_header = function()
	{
		var table = $('DropdownEditor');
		while (table.rows.length) table.deleteRow(0);


		var row = table.insertRow(0);
		var cell= row.insertCell(0);
		cell.colSpan = 3;

		var saveButton = document.createElement('button');
		saveButton.className = 'form-button input-outer';
		var saveIcon = document.createElement('div');
		saveIcon.className = 'input-icon left icon-accept';
		saveButton.appendChild(saveIcon);
		var saveText = document.createElement('span');
		saveText.className = 'input-label';
		saveButton.appendChild(saveText);
		saveText.appendChild(document.createTextNode('Save'));
		cell.appendChild(saveButton);
		saveButton.onclick = function() {
			DropdownEditor.save(this.form);
		};

		var addButton = document.createElement('button');
		addButton.className = 'form-button input-outer';
		var addIcon = document.createElement('div');
		addIcon.className = 'input-icon left icon-add';
		addButton.appendChild(addIcon);
		var addText = document.createElement('span');
		addText.className = 'input-label';
		addButton.appendChild(addText);
		addText.appendChild(document.createTextNode('Add Dropdown'));
		cell.appendChild(document.createTextNode(' '));
		cell.appendChild(addButton);
		addButton.onclick = function() {
			DropdownEditor.add(this);
			return false;
		};

		
		var undoButton = document.createElement('button');
		undoButton.className = 'form-button input-outer';
		var undoText = document.createElement('span');
		saveText.className = 'input-label';
		undoButton.appendChild(undoText);
		undoText.appendChild(document.createTextNode('Undo'));
		cell.appendChild(document.createTextNode(' '));
		cell.appendChild(undoButton);
		undoButton.onclick = function() {
			DropdownEditor.undo();
		};

		if (this.undoHistory.length < 1) {
			undoButton.style.display = 'none';
		}

		this.undoButton = undoButton;
		
		var resetButton = document.createElement('button');
		resetButton.className = 'form-button input-outer';
		var resetText = document.createElement('span');
		resetText.className = 'input-label';
		resetButton.appendChild(resetText);
		resetText.appendChild(document.createTextNode('Reset All Dropdowns to Default Values'));
		cell.appendChild(document.createTextNode(' '));
		cell.appendChild(resetButton);
		resetButton.onclick = function() {
			DropdownEditor.resetAllToDefault();
			return false;
		};





		var row = table.insertRow(1);
		var cell = row.insertCell(0);
		var modSelect = this.createSelect(this.modules, this.activeModule);
		modSelect.onchange = function() {
			DropdownEditor.activeModule = this.value;
			DropdownEditor.header_rendered = false;
			DropdownEditor.activeDropdown = '';
			DropdownEditor.header_rendered = false;
			DropdownEditor.render();
		};
		cell.appendChild(modSelect);
		
		var cell = row.insertCell(1);
		var ddSelect = this.createSelect(this.lists[this.activeModule], this.activeDropdown, true);
		if (this.activeDropdown == '') {
			for (var i in this.lists[this.activeModule]) {
				this.activeDropdown = i;
				break;
			}
		}
		ddSelect.onchange = function() {
			DropdownEditor.activeDropdown = this.value;
			DropdownEditor.header_rendered = false;
			DropdownEditor.render();
		};
		cell.appendChild(ddSelect);
		
		var cell = row.insertCell(2);
		var langSelect = this.createSelect(this.languages, this.activeLanguage);
		cell.appendChild(langSelect);
		
		var cell = row.insertCell(3);
		var button = document.createElement('button');
		button.appendChild(document.createTextNode('Reset to Default'));
		button.className = 'form-button';
		button.onclick = function() {
			DropdownEditor.resetToDefault();
			return false;
		};
		cell.appendChild(button);

		if (!this.def_lists[this.activeModule] || !this.def_lists[this.activeModule][this.activeDropdown]) {
			var delButton = document.createElement('button');
			delButton.className = 'form-button input-outer';
			var delIcon = document.createElement('div');
			delIcon.className = 'input-icon left icon-delete';
			delButton.appendChild(delIcon);
			var delText = document.createElement('span');
			delText.className = 'input-label';
			delButton.appendChild(delText);
			delText.appendChild(document.createTextNode('Delete'));
			cell.appendChild(document.createTextNode(' '));
			cell.appendChild(delButton);
			delButton.onclick = function() {
				DropdownEditor.deleteDropdown();
				return false;
			};
			cell.appendChild(delButton);
		}
		
		var row = table.insertRow(2);
		var cell = row.insertCell(0);
		cell.colSpan = 3;
		cell.appendChild(document.createElement('hr'));

		this.header_rendered = true;
	};

	this.save = function(form)
	{
		$('dropdowns_data').value = JSON.stringify({
			lists: this.lists,
			deleted: this.deleted
		});
		form.submit();
	};

	this.add = function(elt) {
		var quickEdit = new SUGAR.ui.QuickText({value: ''});
		quickEdit.onchange = function()
		{
			var newLabel = this.getValue();
			if (newLabel == '') return;
			var de = DropdownEditor;
			if (de.lists[de.activeModule][newLabel]) return;
			de.addUndoEntry(newLabel);
			de.lists[de.activeModule][newLabel] = {};
			de.lists[de.activeModule][newLabel][de.base_language] = {};
			de.activeDropdown = newLabel;
			de.header_rendered = false;
			de.render();
		}
		quickEdit.showPopup(null, elt);
	};

	this.renderOptions = function() {
		var table = $('DropdownEditor');
		while (table.rows.length > 3) table.deleteRow(3);
		var list;
		if (this.lists[this.activeModule][this.activeDropdown][this.activeLanguage]) {
			list = this.lists[this.activeModule][this.activeDropdown][this.activeLanguage];
		} else {
			list = this.lists[this.activeModule][this.activeDropdown][this.base_language];
		}
		
		var row = table.insertRow(table.rows.length);
		var cell = row.insertCell(0);
		cell.appendChild(document.createTextNode('Database Value'));
		var cell = row.insertCell(1);
		cell.appendChild(document.createTextNode('Display Value'));
		var cell = row.insertCell(2);
		cell.appendChild(document.createTextNode('Delete'));
		
		var row = table.insertRow(table.rows.length);
		var cell = row.insertCell(0);
		cell.appendChild(document.createElement('hr'));
		var cell = row.insertCell(1);
		cell.appendChild(document.createElement('hr'));
		var cell = row.insertCell(2);
		cell.appendChild(document.createElement('hr'));
		
		for (var i in list) {
			if (typeof(list[i]) == 'function') continue;
			var f = function() {
				var row = table.insertRow(table.rows.length);
				var cell = row.insertCell(0);
				cell.appendChild(document.createTextNode(i));
				var cell = row.insertCell(1);
				cell.appendChild(document.createTextNode(list[i]));
				var key = i;
				cell.onclick = function() {
					DropdownEditor.editText(this, key);
				}
				var cell = row.insertCell(2);
				var delButton = document.createElement('span');
				delButton.className = 'input-icon icon-delete';
				delButton.style.cursor = 'pointer';
				delButton.onclick = function() {
					DropdownEditor.deleteOption(key);
				}
				cell.appendChild(delButton);
			}();
		}

		var row = table.insertRow(table.rows.length);
		var cell = row.insertCell(0);
		var addButton = document.createElement('span');
		addButton.style.cursor = 'pointer';
		addButton.onclick = function() {
			DropdownEditor.addElement(cell);
		};
		var addIcon = document.createElement('span');
		addIcon.className = 'input-icon icon-add';
		addButton.appendChild(addIcon);
		addButton.appendChild(document.createTextNode('Add'));
		cell.appendChild(addButton);
	};

	this.addElement = function(elt)
	{
		var quickEdit = new SUGAR.ui.QuickText({value: ''});
		quickEdit.onchange = function()
		{
			var newLabel = this.getValue();
			var de = DropdownEditor;
			if (de.lists[de.activeModule][de.activeDropdown][de.activeLanguage]) {
				list = de.lists[de.activeModule][de.activeDropdown][de.activeLanguage];
			} else {
				list = de.lists[de.activeModule][de.activeDropdown][de.base_language];
			}
			if (!list[newLabel]) {
				de.addUndoEntry();
				list[newLabel] = '';
				de.render();
			}
		}
		quickEdit.showPopup(null, elt);
	};

	this.editText = function(cell, key)
	{
		if (this.activeEditor) return;
		if (this.lists[this.activeModule][this.activeDropdown][this.activeLanguage]) {
			list = this.lists[this.activeModule][this.activeDropdown][this.activeLanguage];
		} else {
			list = this.lists[this.activeModule][this.activeDropdown][this.base_language];
		}
		var input = document.createElement('input');
		input.size = 25;
		input.onblur = function() {
			DropdownEditor.endEdit(cell, key);
		};
		input.onkeydown = function(evt) { DropdownEditor.editorKeyDown(evt || window.event, cell, key, this.value); };
		input.className = 'input-text';
		input.value = list[key];
		cell.innerHTML = '';
		cell.appendChild(input);
		input.focus();
		this.activeEditor = input;
	};

	this.editorKeyDown = function(evt, cell, key, value) {
		if (evt.keyCode == 27) {
			this.endEdit(cell, key);
		}
		if (evt.keyCode == 13) {
			this.endEdit(cell, key, value);
		}
	};

	this.endEdit = function(cell, key, value) {
		if (this.lists[this.activeModule][this.activeDropdown][this.activeLanguage]) {
			list = this.lists[this.activeModule][this.activeDropdown][this.activeLanguage];
		} else {
			list = this.lists[this.activeModule][this.activeDropdown][this.base_language];
		}
		if (value) {
			this.addUndoEntry();
			list[key] = value;
		}
		cell.innerHTML = '';
		cell.appendChild(document.createTextNode(list[key]));
		this.activeEditor = null;
	};

	this.addUndoEntry = function(dd) {
		var state;
		if (this.lists[this.activeModule][this.activeDropdown][this.activeLanguage]) {
			list = this.lists[this.activeModule][this.activeDropdown][this.activeLanguage];
		} else {
			list = this.lists[this.activeModule][this.activeDropdown][this.base_language];
		}
		state = {
			'module' : this.activeModule,
			'dropdown' : this.activeDropdown,
			'language' : this.activeLanguage,
			'data' : deep_clone(list),
			'added' : dd
		};
		this.undoHistory.push(state);
		this.undoButton.style.display = '';
	};

	this.undo = function() {
		var state = this.undoHistory[this.undoHistory.length-1];
		this.activeModule = state.module;
		this.activeDropdown = state.dropdown;
		this.activeLanguage = state.language;
		if (state.added) {
			delete this.lists[this.activeModule][state.added];
		} else {
			this.lists[this.activeModule][this.activeDropdown][this.activeLanguage] = state.data;
		}
		this.undoHistory.splice(this.undoHistory.length-1, 1);
		this.header_rendered = false;
		this.render();
	};

	this.deleteOption = function(key) {
		this.addUndoEntry();
		if (this.lists[this.activeModule][this.activeDropdown][this.activeLanguage]) {
			list = this.lists[this.activeModule][this.activeDropdown][this.activeLanguage];
		} else {
			list = this.lists[this.activeModule][this.activeDropdown][this.base_language];
		}
		delete list[key];
		this.render();
	};

	this.createSelect = function(options, selected, useKeys)
	{	
		var select = document.createElement('select');
		for (var s in options) {
			var opt = document.createElement('option');
			opt.value = s;
			var text = options[s];
			if (useKeys) text = s;
			opt.text = text;
			try {
				select.add(opt, null);
			}
			catch(e) {
				select.add(opt);
			}
			if(s == selected)
				opt.selected = true;
		}
		return select;
	};

	this.resetToDefault = function()
	{
		if (this.def_lists[this.activeModule][this.activeDropdown][this.activeLanguage]) {
			this.addUndoEntry();
			this.lists[this.activeModule][this.activeDropdown][this.activeLanguage] = 
				deep_clone(this.def_lists[this.activeModule][this.activeDropdown][this.activeLanguage]);
			this.render();
		}
	};

	this.resetAllToDefault = function()
	{
		for (var mod in this.def_lists) {
			var byMod = this.def_lists[mod];
			for (var dd in byMod) {
				var byDD = byMod[dd];
				for (var lang in byDD) {
					if (!this.lists[mod])
						this.lists[mod] = {};
					if (!this.lists[mod][dd])
						this.lists[mod][dd] = {};
					if (!this.lists[mod][dd][lang])
						this.lists[mod][dd][lang] = {};
					this.lists[mod][dd][lang] = 
						deep_clone(this.def_lists[mod][dd][lang]);
				}
			}
		}
		this.header_rendered = false;
		this.render();
	};

	this.deleteDropdown = function()
	{
		if (!this.deleted[this.activeModule]) this.deleted[this.activeModule] = {};
		this.deleted[this.activeModule][this.activeDropdown] = this.lists[this.activeModule][this.activeDropdown];
		delete this.lists[this.activeModule][this.activeDropdown];
		for (var  d in this.lists[this.activeModule]) {
			this.activeDropdown = d;
			break;
		}
		this.header_rendered = false;
		this.render();
	}
}();


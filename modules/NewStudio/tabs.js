RenameTabs = new function() {

	this.tabs = {};
	this.languages = {};
	this.language = null;
	this.header_rendered = false;
	this.activeEditor = null;

	this.init = function(tabs, languages) {
		this.tabs = tabs;
		this.languages = languages;
	};

	this.render = function() {
		if (!this.header_rendered) this.render_header();
		this.render_options();
	};

	this.render_header = function() {
		var table = $('RenameTabs');
		while (table.rows.length) table.deleteRow(0);
		
		var row = table.insertRow(0);
		var cell = row.insertCell(0);
		cell.appendChild(document.createTextNode('Language'));

		var select = document.createElement('select');
		for (var l in this.languages) {
			if (this.language == null)
				this.language = l;
			var opt = document.createElement('option');
			opt.value = l;
			var text = this.languages[l];
			opt.text = text;
			try {
				select.add(opt, null);
			}
			catch(e) {
				select.add(opt);
			}
			if(l == this.language)
				opt.selected = true;
		}
		var cell = row.insertCell(1);
		select.onchange = function() {
			RenameTabs.language = this.value;
			RenameTabs.render();
		};
		cell.appendChild(select);
		
		var cell = row.insertCell(2);
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
			RenameTabs.save(this.form);
		};


		var row = table.insertRow(1);
		var cell = row.insertCell(0);
		cell.colSpan = 3;
		cell.appendChild(document.createElement('hr'));

		this.header_rendered = true;
	};

	this.render_options = function() {
		var table = $('RenameTabs');
		while (table.rows.length > 2) table.deleteRow(2);
		if (!this.tabs[this.language]) return;
		
		var row = table.insertRow(table.rows.length);
		var cell = row.insertCell(0);
		cell.appendChild(document.createTextNode('Database Value'));
		var cell = row.insertCell(1);
		cell.appendChild(document.createTextNode('Display Value'));
		
		var row = table.insertRow(table.rows.length);
		var cell = row.insertCell(0);
		cell.colSpan = 3;
		cell.appendChild(document.createElement('hr'));
	
		var list = this.tabs[this.language];
		for (var i in list) {
			if (typeof(list[i]) == 'function') continue;
			var f = function() {
				var row = table.insertRow(table.rows.length);
				var cell = row.insertCell(0);
				cell.appendChild(document.createTextNode(i));
				var cell = row.insertCell(1);
				cell.style.width = '30em';
				cell.appendChild(document.createTextNode(list[i]));
				var key = i;
				cell.onclick = function() {
					RenameTabs.editText(this, key);
				}
			}();
		}
	};

	this.editText = function(cell, key) {
		if (this.activeEditor) return;
		list = this.tabs[this.language];
		var input = document.createElement('input');
		input.size = 25;
		input.onblur = function() {
			RenameTabs.endEdit(cell, key);
		};
		input.onkeydown = function(evt) {
			RenameTabs.editorKeyDown(evt || window.event, cell, key, this.value);
		},
		input.className = 'input-text';
		input.value = list[key];
		cell.innerHTML = '';
		cell.appendChild(input);
		input.focus();
		this.activeEditor = input;
	};
	
	this.endEdit = function(cell, key, value) {
		list = this.tabs[this.language];
		if (value) {
			list[key] = value;
		}
		cell.innerHTML = '';
		cell.appendChild(document.createTextNode(list[key]));
		this.activeEditor = null;
	};

	this.editorKeyDown = function(evt, cell, key, value) {
		if (evt.keyCode == 27) {
			this.endEdit(cell, key);
		}
		if (evt.keyCode == 13) {
			this.endEdit(cell, key, value);
		}
	};

	this.save = function(form) {
		$('tabs_data').value = JSON.stringify(this.tabs);
		form.submit();
	};
}();


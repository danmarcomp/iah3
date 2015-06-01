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
 *****************************************************************************
 * $Id: skills.js 7296 2010-06-12 03:09:50Z andrew $
 * File Description:
 * Contributor(s):
*****************************************************************************/

// vim: set ai si :

var SkillsEditor = new function() {
	this.id = 'SkillsEditor';
	this.table = null;
	this.skills = [];
	this.allSkills = [];
	this.rowCount = 0;
	this.input = null;
	this.inputName = null;
	this.readonly = false;
	this.lastIndex = 0;

	this.init = function(tableId, buttonId, inputName, skills, allSkills) {
		this.table = $(tableId);
		this.input = null;
		this.inputName = inputName;
		this.button = $(buttonId);
		if(YLang.isArray(skills))
			skills = {};
		if(YLang.isArray(allSkills))
			allSkills = {};
		this.skills = skills;
		this.allSkills = allSkills;
	}
	
	this.setup = function() {
		if (this.button) {
			this.button.onclick = function() { SkillsEditor.addRow(); };
		}
		this.clearTable();
		for (var i in this.skills) this.addRow(i, this.skills);
	}

	this.clearTable = function() {
		SUGAR.ui.clearChildNodes(this.table);
		this.firstIndex = 0;
		this.lastIndex = this.firstIndex;
	}


	this.addRow = function(id, data) {
		if (!data) data = {};
		if (!id) id = '';
		var dropdown = this.createDropdown();
		if (!dropdown) {
			return;
		}
		if (id != '') {
			dropdown.value = id;
		}
		dropdown.setAttribute('name', 'skills_skill[]');
		dropdown.style.width = '18em';
		var row = this.createRow();
		row.count = this.rowCount;
		this.rowCount ++;
		var cell = this.createTableCell({body_text: SUGAR.language.get('app_strings', 'LBL_SKILL_NAME'), style:{textAlign:'right'}}, true);
		row.appendChild(cell);

		if (!this.readonly) {
			var cell = this.createTableCell({});
			cell.appendChild(dropdown);
		} else {
			var cell = this.createTableCell({body_text: dropdown.options[dropdown.selectedIndex].text});
		}
		row.appendChild(cell);

		var cell = this.createTableCell({body_text: SUGAR.language.get('app_strings', 'LBL_SKILL_RATING'), style:{textAlign: 'right'}}, true);
		row.appendChild(cell);

		if (!this.readonly) {
			var cell = this.createTableCell({}, true);
			var input = new SUGAR.ui.TextInput(null, {format: 'int', size: 10});
			if (id != '') {
				dropdown.value = id;
				if (data[dropdown.value]) {
					input.init_value = data[dropdown.value].rating;
				}
			} else
				input.init_value = 0;
			cell.appendChild(input.render());
		} else {
			var cell = this.createTableCell({body_text: data[dropdown.value].rating});
		}
		row.appendChild(cell);

		if (!this.readonly) {
			var cell = this.createTableCell({});
			var img = createElement2('div', {className: 'input-icon icon-delete active-icon'});
			img.count = row.count;
			img.onclick = function () {
				SkillsEditor.removeRow(this.count);
				return false;
			};
			cell.appendChild(img);
			row.appendChild(cell);
		}
		this.lastIndex++;
		this.removeDuplicates();
		return row;
	}

	this.removeRow = function(n) {
		if (this.button) this.button.removeAttribute('disabled');
		var row = null;
		for (var i=this.firstIndex; i < this.lastIndex; i++) {
			if (this.table.rows[i].count == n) {
				row = this.table.rows[i];
				break;
			}
		}
		if (!row) return;
		var val = row.cells[1].childNodes[0].value;
		row.parentNode.removeChild(row);
		this.lastIndex--;
		for (var i=this.firstIndex; i < this.lastIndex; i++) {
			var dd = this.table.rows[i].cells[1].childNodes[0];
			var opt = document.createElement('option');
			opt.value = val;
			opt.text = this.allSkills[val];
			try {
				dd.add(opt, null);
			} catch(ex) {
				dd.add(opt);
			}
		}

	}

	this.createDropdown = function() {
		var options = deep_clone(this.allSkills);
		var ret = document.createElement('select');
		for (var val in options) {
			var opt = document.createElement('option');
			opt.value = val;
			opt.text = options[val];
			try {
				ret.add(opt, null);
			} catch(ex) {
				ret.add(opt);
			}
		}
		ret.count = this.rowCount;
		ret.onchange = function() {
			SkillsEditor.reassignOptions(this);
		};
		return ret;
	}

	this.reassignOptions = function(source) {
		for (var i=this.firstIndex; i < this.lastIndex; i++) {
			var select = this.table.rows[i].cells[1].childNodes[0];
			if (select != source) {
				for (var j =0; j < select.options.length; ) {
					if (select.options[j].value == source.value) {
						select.removeChild(select.options[j]);
					} else {
						j++;
					}
				}
				var opt = document.createElement('option');
				opt.value = source.lastValue;
				opt.text = this.allSkills[source.lastValue];
				try {
					select.add(opt, null);
				} catch(ex) {
					select.add(opt);
				}
				
			}
		}
		source.lastValue = source.value;
	}

	this.createTableCell = function(attrs, is_label) {
		if(typeof(attrs) != 'object')  attrs = {};
		var cell = document.createElement('td');
		if(is_label) {
			cell.className = this.getLabelClass();
            cell.width = '20%';            
        } else {
			cell.className = this.getFieldClass();
            cell.width = '30%';            
        }
		cell.noWrap = 'nowrap';
		if(isset(attrs.body)) {
			cell.appendChild(attrs.body);
			delete attrs.body;
		}
		if(isset(attrs.body_text)) {
			cell.appendChild(document.createTextNode(attrs.body_text));
			delete attrs.body_text;
		}
		this.setAttrs(cell, attrs);
		return cell;
	}

	this.setAttrs = function(elt, attrs) {
		if(! attrs)
			return;
		for(var idx in attrs)
			this.setAttr(elt, idx, attrs[idx]);
	}
	this.setAttr = function(elt, name, val) {
		if(typeof(val) == 'object' && isset(elt[name]))
			this.setAttrs(elt[name], val);
		else
			elt[name] = val;
	}

	this.beforeSubmitForm = function() {
		if(this.readonly) return;
		if (!this.input) {
			this.input = createElement2('input', {name: this.inputName, type: 'hidden'});
			this.form.appendChild(this.input);
		}
		var values = [];
		if (this.table) for (var i=this.firstIndex; i < this.lastIndex; i++) {
			values.push({
				id: this.table.rows[i].cells[1].childNodes[0].value,
				rating: this.table.rows[i].cells[3].childNodes[0].value
			});
		}
		this.input.value = JSON.stringify(values);
	}

	this.getLabelClass = function() {
		if (this.readonly) {
			return 'tabDetailViewDL';
		} else {
			return 'dataLabel';
		}
	}

	this.getFieldClass = function() {
		if (this.readonly) {
			return 'tabDetailViewDF';
		} else {
			return 'dataField';
		}
	}

	this.createRow = function() {
		var row = this.table.insertRow(this.firstIndex);
		return row;
	}

	this.removeDuplicates = function() {
		var disable = true;
		if (!this.readonly) {
			for (var i=this.firstIndex; i < this.lastIndex; i++) {
				var value = this.table.rows[i].cells[1].childNodes[0].value;
				for (var j=this.firstIndex; j < this.lastIndex; j++) {
					if (i ==j) continue;
					var dd = this.table.rows[j].cells[1].childNodes[0];
					for (var k=0; k < dd.options.length; ) {
						if (dd.options[k].value == value) {
							dd.removeChild(dd.options[k]);
						} else {
							k++;
						}
					}
				}
			}
			for (var j=this.firstIndex; j < this.lastIndex; j++) {
				var dd = this.table.rows[j].cells[1].childNodes[0];
				dd.lastValue = dd.value;
				if (dd.options.length > 1) disable = false;
			}
			if (this.button) {
				if (disable) this.button.setAttribute('disabled', true);
				else this.button.removeAttribute('disabled');
			}
		}
	}
	
	return this;
}();

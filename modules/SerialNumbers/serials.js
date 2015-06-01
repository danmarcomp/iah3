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
 * $Id$
 * File Description:
 * Contributor(s):
*****************************************************************************/

if(typeof(Object.prototype.toJSON) != 'undefined')
	delete Object.prototype.toJSON;

var row_remove_img;
var item_name_opts = {};
var image_path;
var default_limit = null;
var request_id = 0; // used by http_fetch_sync

function hasAttribute(element, attr) {
	if (element.hasAttribute) return element.hasAttribute(attr);
	return (typeof(element.getAttribute(attr)) == typeof(''));
}

var serials_editor = new function() {
	var form_id, table_id;
	var assembly_id, asset_id;
	var focus_type, focus_id;
	var current_offset;
	var instances_quantity = 0;
	var current_quantity = 0;
	var rowCount = 1;
	var save_url;
	var serials = [];
	var updated_serials = {};
	var the_editor = this;
	
	this.init = function(_form_id, _table_id, _assembly_id, _asset_id, _focus_type, _focus_id, _save_url) {
		form_id = _form_id;
		table_id = _table_id;
		assembly_id = _assembly_id;
		asset_id = _asset_id;
		focus_type = _focus_type;
		focus_id = _focus_id;
		save_url = _save_url;
	}
	
	this.setSerials = function(_serials, _offset, _totalCount) {
		serials = _serials;
		updated_serials = {};
		current_offset = _offset;
		instances_quantity = _totalCount;
		current_quantity = serials.length;
		this.clearTable();
		for(var idx = 0; idx < serials.length; idx++)
			this.addRow(idx, serials[idx]);
	}
	
	this.addSerial = function(newserial) {
		var defaults = {
			assembly_id: assembly_id,
			asset_id: asset_id,
			instance_id: '',
			serial_no: '',
			item_name: '',
			notes: '',
			deleted: 0
		};
		var idx = serials.length;
		for(var f in defaults)
			if(!isset(newserial[f]))
				newserial[f] = defaults[f];
		serials.push(newserial);
		updated_serials[idx] = 1;
		instances_quantity ++;
		current_quantity ++;
		this.addRow(idx, newserial);
	}

	this.addRow = function(serialIndex, serial)
	{
		var form = document.getElementById(form_id);

		/*if (!instance_id && form.quantity.value <= instances_quantity) {
			alert(LANGUAGE.mod_strings.LBL_INSTANCES_ALERT);
			return false;
		}*/
		
		var table = document.getElementById(table_id);
		var row = table.insertRow(table.rows.length );
		var rowName = 'item_row_' + rowCount;
		row.id = rowName;
		row.serialIndex = serialIndex;
		
		var cell1 = row.insertCell(row.cells.length);
		cell1.appendChild(document.createTextNode('\u00a0'));
		
		var cell3 = row.insertCell(row.cells.length);
		cell3.noWrap = 'nowrap';
		cell3.className = 'dataField';
		var selItemName = document.createElement('select');
		selItemName.serialIndex = serialIndex;
		selItemName.field = 'item_name';
		selItemName.onchange = function() { the_editor.updateField(this); }
        var counter = 0;
		for(var idx in item_name_opts) {
			var opt = document.createElement('option');
			opt.value = idx;
			opt.text = item_name_opts[idx];
			if(idx == serial.item_name) {
				opt.selected = true;
            } else if (! serial.item_name && counter == 0) {
                opt.selected = true;
            }
			try {
				selItemName.add(opt, null);
			}
			catch(e) {
				selItemName.add(opt);
			}
            counter ++;
		}
		cell3.appendChild(selItemName);
		
		var cell4 = row.insertCell(row.cells.length);
		cell4.nowrap = 'nowrap';
		cell4.className = 'dataField';
		var textE2 = document.createElement('input');
		textE2.type = 'text';
		textE2.serialIndex = serialIndex;
		textE2.field = 'notes';
		textE2.value = serial.notes;
		textE2.onchange = function() { the_editor.updateField(this); }
		textE2.size = 40;
		cell4.appendChild(textE2);
		
		var cell2 = row.insertCell(row.cells.length);
		cell2.noWrap = 'nowrap';
		cell2.className = 'dataField';
		var textE1 = document.createElement('input');
		textE1.type = 'text';
		textE1.className = 'dataField';
		textE1.serialIndex = serialIndex;
		textE1.field = 'serial_no';
		textE1.value = serial.serial_no;
		textE1.size = 30;
		textE1.onchange = function() { the_editor.updateField(this); }
		cell2.appendChild(textE1);
		
		var cell4 = row.insertCell(row.cells.length);
		cell4.nowrap = 'nowrap';
		var remlink = document.createElement('a');
		remlink.href = '#';
		remlink.className = 'listViewTdToolsS1';
		remlink.onclick = function() { the_editor.deleteRow(rowName); return false; }
		remlink.innerHTML = row_remove_img;
		cell4.appendChild(remlink);
		var textE3 = document.createElement('a');
		textE3.href = '#';
		textE3.className = 'listViewTdToolsS1';
		textE3.onclick = remlink.onclick;
		textE3.innerHTML = '\u00a0'+LANGUAGE.app_strings.LNK_REMOVE;
		cell4.appendChild(textE3);
		
		rowCount ++;
		//this.updateRange();
	}
	
	this.updateField = function(field) {
		serials[field.serialIndex][field.field] = field.value;
		updated_serials[field.serialIndex] = 1;
	}
	
	this.deleteRow = function(rowId) {
		var table = document.getElementById(table_id);
		var row = document.getElementById(rowId);
		serials[row.serialIndex].deleted = 1;
		updated_serials[row.serialIndex] = 1;
		table.deleteRow(row.rowIndex);
		current_quantity --;
		instances_quantity --;
		//this.updateRange();
	}

	this.clearTable = function()
	{
		var table = document.getElementById(table_id);
		var form = document.getElementById(form_id);
		var rows = table.rows.length;
		for(i = rows - 1 ; i > 0; i--){
			table.deleteRow(i);
		}
		rowCount = 1;
	}

	this.updateRange = function() {
		var icons = ['start', 'previous', 'next', 'end'];
		for(var i = 0; i < 4; i++) {
			var t = icons[i];
			var enabled;
			if(t == 'start' || t == 'previous')
				enabled = (current_offset > 0);
			else
				enabled = (instances_quantity > current_offset + default_limit);
			document.getElementById('serial_list_nav_'+t+(enabled ? '_on' : '_off')).style.display = 'inline';
			document.getElementById('serial_list_nav_'+t+(enabled ? '_off' : '_on')).style.display = 'none';
		}
		document.getElementById('serial_list_nav_index').innerHTML = '(' + (current_quantity > 0 ? current_offset + 1 : 0) + ' - ' + (current_offset + current_quantity) + ' of ' + instances_quantity + ')';
	}
	
	this.getChanges = function() {
		var changes = [];
		for(var idx in updated_serials) {
			if(updated_serials[idx]) {
				var serial = serials[idx];
				if(serial.instance_id == '' && serial.deleted)
					continue;
				changes.push(serial);
			}
		}
		var json = changes.length ? JSON.stringify(changes) : '';
		return json;
	}

	this.navigate = function(nav_action) {
		var offset;
		switch(nav_action) {
			case 'start':
				offset = 0; break;
			case 'previous':
				offset = Math.max(0, current_offset - default_limit); break;
			case 'next':
				offset = current_offset + current_quantity; break;
			case 'end':
				offset = Math.max(0, instances_quantity - default_limit); break;
		}
		var form = document.getElementById(form_id);
		
		var changed = this.getChanges();
		var post_data = 'serial_updates='+escape(changed);
		post_data += '&serial_offset='+offset;
		post_data += '&object_name='+focus_type;
		post_data += '&record='+focus_id;
		var result = http_fetch_sync(save_url, post_data);
		eval(result.responseText);
	}

};

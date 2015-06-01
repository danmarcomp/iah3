/**
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 */


var subtabCount = [];
var subtabModules = [];
var tabLabelToValue = [];
StudioTabGroup = function(){
	this.lastEditTabGroupLabel = -1;
};


StudioTabGroup.prototype.editTabGroupLabel = function (id, done){
	if(!done){
		if(this.lastEditTabGroupLabel != -1)editTabGroupLabel(this.lastEditTabGroupLabel, true);
		$('tabname_'+id).style.display = 'none';
		$('tablabel_'+id).style.display = '';
		$('tabother_'+id).style.display = 'none';
		$('tablabel_'+id).focus();
		$('tablabel_'+id).onkeydown = function(e) { if((e || event).keyCode == 13) { this.blur(); return false; } };
		this.lastEditTabGroupLabel = id;
		YAHOO.util.DragDropMgr.lock();
	}else{
		this.lastEditTabGroupLabel = -1;
		$('tabname_'+id).innerHTML = '';
		$('tabname_'+id).appendChild(document.createTextNode($('tablabel_'+id).value));
		$('tabname_'+id).style.display = '';
		$('tablabel_'+id).style.display = 'none';
		$('tabother_'+id).style.display = '';
		YAHOO.util.DragDropMgr.unlock();
	}
}

 StudioTabGroup.prototype.sendGroupForm = function(form){
 	var i, j, updates = {save_group_tabs: 1};
	for(j = 0; j < slotCount; j++){
		var ul = $('ul' + j),
			items = ul.getElementsByTagName('li');
		for(i = 0; i < items.length; i++) {
			if(isset(subtabModules[items[i].id]))
				updates[j + '_' + i] = subtabModules[items[i].id];
		}
	}
	return SUGAR.ui.sendForm(form, updates, null, true);
};

 StudioTabGroup.prototype.sendTabsForm = function(form){
 	var i, j, updates = {save_tabs: 1};
	for(j = 0; j < slotCount; j++){
		var ul = $('ul' + j), idx = 0,
			items = ul.getElementsByTagName('li');
		for(i = 0; i < items.length; i++) {
			if(isset(subtabModules[items[i].id])){
				updates['group_'+ j + '[' + idx + ']'] = tabLabelToValue[subtabModules[items[i].id]];
				idx ++;
			}
		}
	}
	return SUGAR.ui.sendForm(form, updates, null, true);
};

StudioTabGroup.prototype.deleteTabGroup = function(id){
		if($('delete_' + id).value == 0){
			$('ul' + id).style.display = 'none';
			$('tabname_'+id).style.textDecoration = 'line-through'
			$('delete_' + id).value = 1;
		}else{
			$('ul' + id).style.display = '';
			$('tabname_'+id).style.textDecoration = 'none'
			$('delete_' + id).value = 0;
		}
	}	


var lastField = '';
			var lastRowCount = -1;
			var undoDeleteDropDown = function(transaction){
			    deleteDropDownValue(transaction['row'], $(transaction['id']), false);
			}
			jstransaction.register('deleteDropDown', undoDeleteDropDown, undoDeleteDropDown);
			function deleteDropDownValue(rowCount, field, record){
			    if(record){
			        jstransaction.record('deleteDropDown',{'row':rowCount, 'id': field.id });
			    }
			    //We are deleting if the value is 0
			    if(field.value == '0'){
			        field.value = '1';
			        $('slot' + rowCount + '_value').style.textDecoration = 'line-through';
			    }else{
			        field.value = '0';
			        $('slot' + rowCount + '_value').style.textDecoration = 'none';
			    }
			    
			   
			}
var studiotabs = new StudioTabGroup();

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


var yahooSlots = new Array();
function addNewRowToView(id){
    var curRow = $(id);
    var parent = curRow.parentNode;
    var newRow = document.createElement('tr');
    var newRow = parent.insertRow(parent.rows.length);
    var re = /studiorow[0-9]+/g;
    var cell = newRow.insertCell(0);

    cell.innerHTML = curRow.cells[0].innerHTML.replace(re, 'studiorow' + slotCount);
    cell.className = curRow.cells[0].className;
    for(var j = 1; j < curRow.cells.length ; j++){
        var cell = newRow.insertCell(j);
        cell.innerHTML = '&nbsp;';
        cell.className = curRow.cells[j].className;
    }
    var index = parent.rows.length;
    for(var i = 0; i < parent.rows.length ; i++){
        if(parent.rows[i].id == id){
            index = i + 1;
        }
    }
    newRow.id = 'studiorow' + slotCount;
    if(typeof(curRow.parentId) == 'undefined'){
        newRow.parentId = id;
    }else{
        newRow.parentId = curRow.parentId;
    }
    if(index < parent.rows.length){
        parent.insertBefore(newRow, parent.rows[index]);
    }else{
        parent.appendChild(newRow);
    }
    $('add_' + newRow.parentId).value = 1 + parseInt($('add_' + newRow.parentId).value);
    slotCount++;
}

function deleteRowFromView(id, index){
    var curRow = $(id);
	var count = 0;
	for (var i=0; i< curRow.parentNode.childNodes.length; i++) {
		if (curRow.parentNode.childNodes[i].id && curRow.parentNode.childNodes[i].id.match(/^studiorow[0-9]+$/)) count++;
	}
	if (count < 2) {
    	alert('At least one row must exist!');
		return;
	}
    curRow.parentNode.removeChild(curRow);
    if(typeof(curRow.parentId) == 'undefined'){
        $('form_' + id).value=-1;
    }else{
        $('add_' + curRow.parentId).value =  parseInt($('add_' + curRow.parentId).value) - 1;
    }
}

function addNewColToView(id, index){
    
    var curCol = $(id);
    var index = curCol.cellIndex;
    var parent = curCol.parentNode;
    var cell = parent.insertCell(index + 1);
    if(parent.parentNode.rows[parent.rowIndex + 1])parent.parentNode.rows[parent.rowIndex + 1].insertCell(index + 1)
    var re = /studiocol[0-9]+/g;
    cell.innerHTML = '[NEW]';
    cell.className = curCol.className;
    if(typeof(curCol.parentId) == 'undefined'){
        cell.parentId = id;
    }else{
        cell.parentId = curCol.parentId;
    }
  
    $('add_' + cell.parentId).value = 1 + parseInt($('add_' + cell.parentId).value);
    slotCount++;
}

function deleteColFromView(id, index){
    var curCol = $(id);
    var row = curCol.parentNode;
     var index = curCol.cellIndex;
    if(typeof(row.cells[index + 1].parentId) == 'undefined'){
       row.deleteCell(index);
       row.deleteCell(index - 1);
       if(row.parentNode.rows[row.rowIndex + 1]){
       	row.parentNode.rows[row.rowIndex + 1].deleteCell(index );
      	 row.parentNode.rows[row.rowIndex + 1].deleteCell(index - 1);
       }
      
      
    }else{
       row.deleteCell(index + 1);
        if(row.parentNode.rows[row.rowIndex + 1])row.parentNode.rows[row.rowIndex + 1].deleteCell(index +1);
        
    }
     $('add_' + curCol.id).value =  parseInt($('add_' + curCol.id).value) - 1;
    
}




var field_count_MSI = 0;
var studioLoaded = false;
var dyn_field_count = 0;
function addNewFieldType(type){
    var select = $('studio_display_type').options;
    for(var i = 0; i < select.length; i++){
        if(select[i].value == type){
            return;
        }
    }
    select[i] = new Option(type, type);
}

function filterStudioFields(type){
    var table = $('studio_fields');
    for(var i = 0; i < table.rows.length; i++){
        children = table.rows[i].cells[0].childNodes;
        for(var j = 0; j < children.length; j++){
            child = children[j];
            if(child.nodeName == 'DIV' && typeof(child.fieldType) != 'undefined'){
                if(type == 'all'){
                    table.rows[i].style.display = '';
                }else if(type == 'custom'){
                    if(child.isCustom){
                        table.rows[i].style.display = ''
                    }else{
                        table.rows[i].style.display = 'none';
                    }
                }else{
                    if(child.fieldType == type){
                        table.rows[i].style.display = ''
                    }else{
                        table.rows[i].style.display = 'none';
                    }

                }
            }
        }
    }

}


function addNewField(id, name, label, html, fieldType,isCustom, table_id, top){
    
    html = replaceAll(html, "&qt;", '"');
    html = replaceAll(html, "&sqt;", "'");
    var table = $(table_id);
    var row = false;
    if(top){
         row = table.insertRow(1);
    }else{
         row = table.insertRow(table.rows.length);
    }
   
    var cell = row.insertCell(0);
    var div = document.createElement('div');
    div.className = 'slot';
    div.setAttribute('id', id);
    div.fieldType = fieldType;
    addNewFieldType(fieldType);
    div.isCustom = isCustom;
    div.style.width='100%';
    var textEl = document.createElement('input');
    textEl.setAttribute('type', 'hidden')
    textEl.setAttribute('name',  'slot_field_' + field_count_MSI );
    textEl.setAttribute('id', 'slot_field_' + field_count_MSI  );
    textEl.setAttribute('value', 'add:' + name );
    field_list_MSI['form_' + name] = textEl;
    document.studio.appendChild(textEl);
    div.innerHTML = label;
    var cell2 = row.insertCell(1);
    var div2 = document.createElement('div');
    setMouseOverForField(div, true);
    div2.style.display = 'none';
    div2.setAttribute('id',  id + 'b' );
    html = html.replace(/(<input)([^>]*)/g, '$1 disabled readonly $2');
    html = html.replace(/(<select)([^>]*)/g, '$1 disabled readonly $2');
    html = html.replace(/(onclick=')([^']*)/g, '$1'); // to strip {} from after a JS onclick call
    div2.innerHTML += html;
    cell.appendChild(div);
    cell2.appendChild(div2);
    field_count_MSI++;
    if(top){
        yahooSlots[id] = new ygDDSlot(id, "studio");
    }else{
        dyn_field_count++;
    }
    return name;

}


function removeFieldFromTable(field, table)
{
    var table = $(table);
    var rows = table.rows;
    for(i = 0 ; i < rows.length; i++){
        cells = rows[i].cells;
        for(j = 0; j < cells.length; j++){
            cell = rows[i].cells[j];
            children = cell.childNodes;
            for(k = 0; k < children.length; k++){
                child = children[k];
                if(child.nodeType == 1){

                    if(child.getAttribute('id') == 'slot_' + field){
                        table.deleteRow(i);
                        return;
                    }
                }
            }
        }
    }
}
function setMouseOverForField(field, on){

    if(on){
        field.onmouseover = function(){
            return SUGAR.popups.tooltip($(this.id + 'b').innerHTML, this);
        };
        field.onmouseout = function(){};
    }else{
        field.onmouseover = function(){};
        field.onmouseout = function(){};
    }
}
var lastIDClick = '';
var lastIDClickTime = 0;
var dblDelay = 500;
function wasDoubleClick(id) {
    var d = new Date();
    var now = d.getTime();

    if (lastIDClick == id && (now - lastIDClickTime) < dblDelay) {
        lastIDClick = '';

        return true;
    }
    lastIDClickTime = now;
    lastIDClick = id;
    return false;
}
function confirmNoSave(){
    return confirm('Any changes will go unsaved. Are you sure you would like to continue?');
}
var labelEdit = false;
 SUGAR.Studio = function(){
    this.labelEdit = false;
    this.lastLabel = false;
}
 SUGAR.Studio.prototype.endLabelEdit =  function(id){
     if(id == 'undefined')return;
    $('span' + id).style.display = 'none';
    jstransaction.record('studioLabelEdit', {'id':id, 'new': $(id).value , 'old':$('label' + id).innerHTML});
    $('label' + id).innerHTML = $(id).value;
	// longreach - modified -- handle multiple instances
    var inputs = document.getElementsByName('label_' + id);
	for (var i in inputs) {
	    inputs[i].value = $(id).value;
	}
	// longreach - end modified
     $('label' + id).style.display = '';
    this.labelEdit = false;
    YAHOO.util.DragDropMgr.unlock();
};

 SUGAR.Studio.prototype.undoLabelEdit =  function (transaction){
    var id = transaction['id'];
    $('span' + id).style.display = 'none';
    $('label' + id).innerHTML = transaction['old'];
    $('label_' + id).value = transaction['old'];
};
 SUGAR.Studio.prototype.redoLabelEdit=  function  (transaction){
    var id = transaction['id'];
    $('span' + id).style.display = 'none';
    $('label' + id).innerHTML = transaction['new'];
    $('label_' + id).value = transaction['new'];
};

 SUGAR.Studio.prototype.handleLabelClick =  function(id, count){
    if(this.lastLabel != ''){
        //endLabelEdit(lastLabel);
    }
    if(wasDoubleClick(id) || count == 1){
        $('span' + id).style.display = '';
        $(id).focus();
        $(id).select();
        $('label' + id).style.display = 'none';
        this.lastLabel = id;
        YAHOO.util.DragDropMgr.lock();
    }
    
    

}
jstransaction.register('studioLabelEdit', SUGAR.Studio.prototype.undoLabelEdit, SUGAR.Studio.prototype.redoLabelEdit);


SUGAR.Studio.prototype.save = function(formName, publish){
    var formObject = document.forms[formName]; 
    SUGAR.conn.sendForm(formObject, {status_msg: app_string('LBL_SAVING'), argument: publish, timeout: 5000}, SUGAR.Studio.prototype.saved);
}
SUGAR.Studio.prototype.saved= function(o){
    if(o){
    SUGAR.ui.showStatus(app_string('LBL_SAVED'), 2000);
    
    if(o.argument){
        studiojs.publish();
    }else{
    	document.location.reload();
    }
    }else{
        SUGAR.ui.showStatus(SUGAR.language.get('Studio', 'LBL_FAILED_TO_SAVE'), 2000);
    }
}
    
SUGAR.Studio.prototype.publish = function(){
    SUGAR.conn.asyncRequest('index.php?to_pdf=1&module=Studio&action=Publish',
    	{status_msg: SUGAR.language.get('Studio', 'LBL_PUBLISHING')},
    	SUGAR.Studio.prototype.published);
}

SUGAR.Studio.prototype.published= function(o){
    if(o){
    SUGAR.ui.showStatus(SUGAR.language.get('Studio', 'LBL_PUBLISHED'), 2000);
    document.location.reload();
    }else{
        SUGAR.ui.showStatus(SUGAR.language.get('Studio', 'LBL_FAILED_PUBLISHED'), 2000);
    }
}

var studiopopup = function() {
    return {
        // covers the page w/ white overlay
        display: function() {
            if(studiojs.popupVisible)return false;
            studiojs.popupVisible = true;
            var url = 'index.php?to_pdf=1&module=Studio&action=wizard&wizard=EditCustomFieldsWizard&option=CreateCustomFields&popup=true';
            SUGAR.conn.asyncRequest(url, null, studiopopup.render);
        },
        destroy:function(){
            studiojs.popup.hide();
        },
        evalScript:function(text){
            SUGAR.util.evalScript(text);
             
        },
        render: function(obj){
            if(obj){
                
                studiojs.popup = new YAHOO.widget.Dialog("dlg", {  effect:{effect:YAHOO.widget.ContainerEffect.SLIDE,duration:.5}, fixedcenter: false, constraintoviewport: false, underlay:"shadow",modal:true, close:true, visible:false, draggable:true, monitorresize:true} );
                
                studiojs.popup.setBody(obj.responseText); 
                studiojs.popupAvailable = true;
          	    studiojs.popup.render(document.body);
          	    studiojs.popup.center();
          	    studiojs.popup.beforeHideEvent.fire = function(e){
          	        studiojs.popupVisible = false;
          	    }
          	      studiopopup.evalScript(obj.responseText);
                
                
            }
            
        }
        

    };
}();
var studiojs = new SUGAR.Studio();
studiojs.popupAvailable = false;
studiojs.popupVisible = false;





var popupSave = function(o){
    var errorIndex = o.responseText.indexOf('[ERROR]');
    
    if(errorIndex > -1){
    	var error = o.responseText.substr(errorIndex + 7, o.responseText.length);
   		SUGAR.ui.showStatus(error, 2000);
    	return;
    }
    var typeIndex = o.responseText.indexOf('[TYPE]') ;
   var labelIndex = o.responseText.indexOf('[LABEL]') ;
    var dataIndex = o.responseText.indexOf('[DATA]');
    var errorIndex = o.responseText.indexOf('[ERROR]');
    var name = o.responseText.substr(6, typeIndex - 6);
    var type =  o.responseText.substr(typeIndex + 6,labelIndex - (typeIndex + 6));
   var label =  o.responseText.substr(labelIndex + 7,dataIndex - (labelIndex + 7));
   var data = o.responseText.substr(dataIndex + 6, o.responseText.length);
  
     addNewField('dyn_field_' + field_count_MSI, name, label, data, type, 1, 'studio_fields', true)
   
   
};
function submitCustomFieldForm(isPopup){
		
    if(typeof(document.popup_form.presave) != 'undefined'){
        document.popup_form.presave();
    }
   
    if(!check_form('popup_form'))return;
    if(isPopup){        
        SUGAR.conn.sendForm('popup_form', null, popupSave);
        studiopopup.destroy();
    }else{
        
        document.popup_form.submit();
    }
}

function deleteCustomFieldForm(isPopup){
		
   
   
    if(confirm("WARNING\nDeleting a custom field will delete all data related to that custom field. \nYou will still need to remove the field from any layouts you have added it to.")){
    	document.popup_form.option.value = 'DeleteCustomField';
  		document.popup_form.submit();
    }
}

function dropdownChanged(value){
    if(typeof(app_list_strings[value]) == 'undefined')return;
    var select = $('default_value').options;
    select.length = 0;

    var count = 0;
    for(var key  in app_list_strings[value]){
        select[count] = new Option(app_list_strings[value][key], key);
        count++;
    }
}

function customFieldChanged(){
}

var populateCustomField = function(response){
    var div = $('customfieldbody');
    if(response.status = 0){
        div.innerHTML = 'Server Connection Failed';
    }else{
    		validate['popup_form'] = new Array();
    		inputsWithErrors = new Array();
        div.innerHTML = response.responseText;
        studiopopup.evalScript(response.responseText);
        if(studiojs.popupAvailable){
           
        var region = YAHOO.util.Dom.getRegion('custom_field_table') ;
        studiojs.popup.cfg.setProperty('width', region.right - region.left + 30 + 'px');
        studiojs.popup.cfg.setProperty('height', region.bottom - region.top + 30 + 'px');
       
         studiojs.popup.render(document.body);
        studiojs.popup.center();
        studiojs.popup.show();
        }
      
    }
};
function changeTypeData(type){
    $('customfieldbody').innerHTML = '<h2>Loading...</h2>';
    var url = 'index.php?module=Studio&popup=true&action=index&&ajax=editcustomfield&to_pdf=true&type=' + type;
    SUGAR.conn.asyncRequest(url, {argument: 1}, populateCustomField);
}

function typeChanged(obj)
{
    changeTypeData(obj.options[obj.selectedIndex].value);

}

function handle_duplicate(){
    document.popup_form.action.value  = 'EditView';
    document.popup_form.duplicate.value = 'true';
    document.popup_form.submit();
}

function forceRange(field, min, max){
	field.value = parseInt(field.value);
	if(field.value == 'NaN')field.value = max;
	if(field.value > max) field.value = max;
	if(field.value < min) field.value = min;
}
function changeMaxLength(field, length){
	field.maxLength = parseInt(length);
	field.value = field.value.substr(0, field.maxLength);
}




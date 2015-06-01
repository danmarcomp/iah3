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

//grid functions

var grid2, grid3, grid4, grid3F,grid4F;
var add_all_fields = SUGAR.language.get('app_strings', 'LBL_ADD_ALL_LEAD_FIELDS');
var remove_all_fields = SUGAR.language.get('app_strings', 'LBL_REMOVE_ALL_LEAD_FIELDS');

function addGrids(form_name) {
	/*if(!check_form(form_name)) {
        return false;
	} else {*/
		grid3 = document.getElementById('ddgrid3_list');
		grid4 = document.getElementById('ddgrid4_list');
		var webFormDiv = document.getElementById('webformfields');
		//add columns to webformfields div
		addCols(grid3,'colsFirst',webFormDiv);
		addCols(grid4,'colsSecond',webFormDiv);
		return true;
	//}
}
function checkFields(REQUIRED_LEAD_FIELDS,LEAD_SELECT_FIELDS){
	grid2 = document.getElementById('ddgrid2_list');
	grid3 = document.getElementById('ddgrid3_list');
	grid4 = document.getElementById('ddgrid4_list');
    //check if all required fields are selected
    var reqFields = [];
    for(var i=0; i<grid2.childNodes.length; i++){
        if(grid2.childNodes[i].getAttribute('isRequired'))
            reqFields.push(grid2.childNodes[i].title);
    }
    if(reqFields.length){
        alert(REQUIRED_LEAD_FIELDS+' '+reqFields[reqFields.length-1]);
        return false;
    } else if(! grid3.childNodes.length && ! grid4.childNodes.length){
        alert(LEAD_SELECT_FIELDS);
        return false;
    }else{
        return true;
    }
}

function askLeadQ(direction,REQUIRED_LEAD_FIELDS,LEAD_SELECT_FIELDS){                
    //change current step value to that of the step being navigated to
    if(direction == 'back'){
       var grid_Div = document.getElementById('grid_Div');
       var lead_Div = document.getElementById('lead_queries_Div');
        grid_Div.style.display='block';
        lead_Div.style.display='none';
    }
    if(direction == 'next'){
      if(!checkFields(REQUIRED_LEAD_FIELDS,LEAD_SELECT_FIELDS)){
          return false;
       }
      else{
       var lead_Div = document.getElementById('lead_queries_Div');
       var grid_Div = document.getElementById('grid_Div');
       lead_Div.style.display='block';
       grid_Div.style.display='none';
       }
    }
}

function campaignPopulated(){
    var camp_populated = document.getElementById('campaign_id');
    if(camp_populated.value == 0){
        return true;
    }
    return true;
}
 
function selectFields(indexes,grid){
    var retStr='';
    for(var i=0;i<indexes.length;i++){
        retStr=retStr+grid.getRow(indexes[i]).childNodes[0].childNodes[0].innerHTML+','+'\n';
        retStr=retStr+'\n';
    }
    return retStr.substring(0,retStr.lastIndexOf(','));
}

function displayAddRemoveDragButtons(Add_All_Fields,Remove_All_Fields){
    var addRemove = document.getElementById("lead_add_remove_button");    
    if(grid2.getDataModel().getTotalRowCount() ==0) {
    addRemove.setAttribute('value',Remove_All_Fields);	
     addRemove.setAttribute('title',Remove_All_Fields);	
    }
    else if(grid3.getDataModel().getTotalRowCount() ==0 && grid4.getDataModel().getTotalRowCount() ==0){
      addRemove.setAttribute('value',Add_All_Fields);	
     addRemove.setAttribute('title',Add_All_Fields);		
   }	
}

function displayAddRemoveButtons(Add_All_Fields,Remove_All_Fields){
    var addRemove = document.getElementById("lead_add_remove_button");    
    if(grid2.childNodes.length >0) {
     addRemove.setAttribute('value',Add_All_Fields);	
     addRemove.setAttribute('title',Add_All_Fields);		
    }
    else{
     addRemove.setAttribute('value',Remove_All_Fields);	
     addRemove.setAttribute('title',Remove_All_Fields);		
    }	
}
function dragDropAllFields(Add_All_Fields,Remove_All_Fields){
   grid2 = document.getElementById('ddgrid2_list');
   grid3 = document.getElementById('ddgrid3_list');
   grid4 = document.getElementById('ddgrid4_list');
   
   //move from main grid to columns 1&2
   var addRemove = document.getElementById("lead_add_remove_button");   
   if(addRemove.value==Add_All_Fields && grid2.childNodes.length) {
     for(var i=0;grid2.childNodes.length;i++){
        if(i%2 ==0)
        	grid3.appendChild(grid2.childNodes[0]);
        else
			grid4.appendChild(grid2.childNodes[0]);
   	 }
    }        
   else if(addRemove.value==Remove_All_Fields){ //move back to the main grid if grid is empty and columns populated
     for(var i=0;grid3.childNodes.length || grid4.childNodes.length;i++){
        if(grid3.childNodes.length)
        	grid2.appendChild(grid3.childNodes[0]);
        if(grid4.childNodes.length)
        	grid2.appendChild(grid4.childNodes[0]);
   	 }
   } 
   displayAddRemoveButtons(Add_All_Fields,Remove_All_Fields);
}

function addCols(grid,colsNumber,webFormDiv){
    for(var i=0;i<grid.childNodes.length;i++){
        var selectedEl = grid.childNodes[i].id;
        var webField = document.createElement('input');
        webField.setAttribute('id', colsNumber+i);
        webField.setAttribute('name',colsNumber+'[]');
        webField.setAttribute('type', 'hidden');
        webField.setAttribute('value',selectedEl);
        webFormDiv.appendChild(webField);
    } 
}

function editUrl(){
    var edit_inp = SUGAR.ui.getFormInput('DetailForm', 'chk_edit_url');
    var check = edit_inp.getValue();
    if(check == 1) {
        var url_elm = document.getElementById("post_url");
        url_elm.disabled=false;
    } else {
        var url_elm = document.getElementById("post_url");
        url_elm.disabled=true;
    }
}
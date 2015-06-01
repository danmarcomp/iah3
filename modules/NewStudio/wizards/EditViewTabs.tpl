{*

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



*}


{$title}
<br>
<style type='text/css'>
{literal}
.slot {
	border-width:1px;border-color:#999999;border-style:solid;padding:0px 1px 0px 1px;margin:2px;cursor:move;

}


.slotSub {
	border-width:1px;border-color:#006600;border-style:solid;padding:0px 1px 0px 1px;margin:2px;cursor:move;

}
.slotB {
	border-width:0;cursor:move;

}
.listContainer
{
	margin-left: 4px;
	padding-left: 4px;
	margin-right: 4px;
	padding-right: 4px;
	list-style-type: none;
}

.tableContainer
{
	
}
.tdContainer{
	border: thin solid gray;
	padding: 10px;
}


{/literal}
</style>

<form name='edittabs' id='edittabs' method='POST' action='index.php'>
<input type='hidden' name='action' value='index'>
<input type='hidden' name='module' value='NewStudio'>
<input type='hidden' name='wizard' value='GroupTabs'>

<button class="form-button" type="submit" onclick="return studiotabs.sendGroupForm(this.form);"><div class="input-icon left icon-accept"></div><span class="input-label">{$MOD.LBL_BTN_SAVEPUBLISH}</span></button>
<button class="form-button" type="button" onclick="{literal}if(confirm(mod_string('NTC_BTN_RESET_GROUPS'))) SUGAR.ui.sendForm(this.form, {reset_group_tabs: 1}, null, true); return false;{/literal}"><div class="input-icon left icon-return"></div><span class="input-label">{$MOD.LBL_BTN_RESET_GROUPS}</span></button>
<button class="form-button" onclick="SUGAR.util.loadUrl('index.php?module=Administration&action=index');" type="button"><div class="input-icon icon-cancel left"></div><span class="input-label">{$APP.LBL_CANCEL_BUTTON_LABEL}</span></button>
<table><tr><td valign='top' class='tabForm' nowrap>
<table  cellpadding="0" cellspacing="0" border="1" width="100%"   id='s_field_delete'>
							<tr><td ><ul id='trash' class='listContainer' style="margin: 0.3em">
<li class='noBullet' id='trashcan'>{$deleteImage}&nbsp;{$MOD.LBL_DELETE_MODULE}</li>
</ul>
						</td></tr></table>


<div class="noBullet">
<h2>{$MOD.LBL_MODULES}</h2>
<ul class='listContainer'>
{counter start=0 name="modCounter" print=false assign="modCounter"}
{foreach from=$availableModuleList key='key' item='value'}


<li  id='modSlot{$modCounter}' class="noBullet"><span class='slotB'>{$value.label}</span></li>
<script>
tabLabelToValue['{$value.label}'] = '{$value.value}';
subtabModules['modSlot{$modCounter}'] = '{$value.label}'</script>
{counter name="modCounter"}
{/foreach}
</ul>
</td>
<td valign='top' nowrap>
<table class='tableContainer' id='groupTable'><tr>
{counter start=0 name="tabCounter" print=false assign="tabCounter"}

{foreach from=$tabs.tabs item='tab' key='tabName'}
{if $tabCounter > 0 && $tabCounter % $tabsPerRow == 0}
</tr><tr>
{/if}
<td valign='top' class='tdContainer'>
<div id='slot{$tabCounter}' class='noBullet'><h2 id='handle{$tabCounter}' ><span id='tabname_{$tabCounter}' class='slotB'>{$tabName}</span><br><span id='tabother_{$tabCounter}'><span onclick='studiotabs.editTabGroupLabel({$tabCounter}, false)'>{$editImage}</span>&nbsp;<span onclick='studiotabs.deleteTabGroup({$tabCounter})'>{$deleteImage}</span></span></h2><input type='hidden' name='tablabelid_{$tabCounter}' id='tablabelid_{$tabCounter}'  value='{$tab.vlabel|escape}'><input type='text' name='tablabel_{$tabCounter}' id='tablabel_{$tabCounter}' style='display:none' value='{sugar_translate label=$tab.label escape=true}' onblur='studiotabs.editTabGroupLabel({$tabCounter}, true)'>
<ul id='ul{$tabCounter}' class='listContainer'>
{counter start=0 name="subtabCounter" print=false assign="subtabCounter"}
{foreach from=$tab.tabs item='list' key='listID'}

<li id='subslot{$tabCounter}_{$subtabCounter}' class='listStyle noBullet' name='{$listId}'><span class='slotB' >{$list.label}</span></li>
<script>subtabModules['subslot{$tabCounter}_{$subtabCounter}'] = '{$availableModuleList[$listID].value}'</script>
{counter name="subtabCounter"}
{/foreach}
<li class='noBullet' id='noselectbottom{$tabCounter}'>&nbsp;</li>
<script>subtabCount[{$tabCounter}] = {$subtabCounter};</script>
</ul>
</div>
<div id='slot{$tabCounter}b'>
<input type='hidden' name='slot_{$tabCounter}' id='slot_{$tabCounter}' value ='{$tabCounter}'>
<input type='hidden' name='delete_{$tabCounter}' id='delete_{$tabCounter}' value ='0'>
</div>
{counter name="tabCounter"}
</td>
{/foreach}

</tr>
<tr><td><button type='button' class='input-button input-outer' onclick='addTabGroup()'><div class="input-icon icon-add left"></div><span class="input-label">{$MOD.LBL_ADD_GROUP}</span></button></td></tr>
</table>

</td>
</table>



<span class='error'>{$error}</span>



{literal}


			<script>
		  function addTabGroup(){
		  	var table = document.getElementById('groupTable');
		  	var rowIndex = table.rows.length - 1;
		  	var rowExists = false;
		  	for(var i = 0; i < rowIndex;i++){
		  		if(table.rows[i].cells.length < {/literal}{$tabsPerRow}{literal}){
		  			rowIndex = i;
		  			rowExists = true;
		  		}
		  	}
		  	
		  	if(!rowExists)table.insertRow(rowIndex);
		  	cell = table.rows[rowIndex].insertCell(table.rows[rowIndex].cells.length);
		  	cell.className='tdContainer';
		  	cell.vAlign='top';
		  	var slotDiv = document.createElement('div');
		  	slotDiv.id = 'slot'+ slotCount;
		  	var header = document.createElement('h2');
		  	header.id = 'handle' + slotCount;
		  	headerSpan = document.createElement('span');
		  	headerSpan.innerHTML = 'New Group';
		  	headerSpan.id = 'tabname_' + slotCount;
		  	header.appendChild(headerSpan);
		  	header.appendChild(document.createElement('br'));
		  	headerSpan2 = document.createElement('span');
		  	headerSpan2.id = 'tabother_' + slotCount;
		  	subspan1 = document.createElement('span');
		  	subspan1.slotCount=slotCount;
		  	subspan1.innerHTML = '{/literal}{$editImage|escape:"quotes"}{literal}&nbsp;';
		  	subspan1.onclick= function(){
		  		studiotabs.editTabGroupLabel(this.slotCount, false);
		  	};
		  	subspan2 = document.createElement('span');
		  	subspan2.slotCount=slotCount;
		  	subspan2.innerHTML = '{/literal}{$deleteImage|escape:"quotes"}{literal}&nbsp;';
		  	subspan2.onclick= function(){
		  		studiotabs.deleteTabGroup(this.slotCount);
		  	};
		  	headerSpan2.appendChild(subspan1);
		  	headerSpan2.appendChild(subspan2);
		  	
		  	var editLabel = document.createElement('input');
		  	editLabel.style.display = 'none';
		  	editLabel.type = 'text';
		  	editLabel.value = 'New Group';
		  	editLabel.id = 'tablabel_' + slotCount;
		  	editLabel.name = 'tablabel_' + slotCount;
		  	editLabel.slotCount = slotCount;
		  	editLabel.onblur = function(){
		  		studiotabs.editTabGroupLabel(this.slotCount, true);
		  	}
		  	
		  	
		  	var list = document.createElement('ul');
		  	list.id = 'ul' + slotCount;
		  	list.className = 'listContainer';
		  	header.appendChild(headerSpan2);
		  	var li = document.createElement('li');
		  	li.id = 'noselectbottom' + slotCount;
		  	li.className = 'noBullet';
		  	li.innerHTML = '[DROP HERE]';
		  	list.appendChild(li);
		  	
		  	slotDiv.appendChild(header);
		  	slotDiv.appendChild(editLabel);
		  	slotDiv.appendChild(list);
			var slotB = document.createElement('div');
		  	slotB.id = 'slot' + slotCount + 'b';
		  	var slot = document.createElement('input');
		  	slot.type = 'hidden';
		  	slot.id =  'slot_' + slotCount;
		  	slot.name =  'slot_' + slotCount; 
		  	slot.value = slotCount;
		  	var deleteSlot = document.createElement('input');
		  	deleteSlot.type = 'hidden';
		  	deleteSlot.id =  'delete_' + slotCount;
		  	deleteSlot.name =  'delete_' + slotCount; 
		  	deleteSlot.value = 0;
		  	slotB.appendChild(slot);
		  	slotB.appendChild(deleteSlot);
		  	cell.appendChild(slotDiv);
		  	cell.appendChild(slotB);
		  	
		  	yahooSlots["slot" + slotCount] = new ygDDSlot("slot" + slotCount, "mainTabs");
			yahooSlots["slot" + slotCount].setHandleElId("handle" + slotCount);
		  	yahooSlots["noselectbottom"+ slotCount] = new ygDDListStudio("noselectbottom"+ slotCount , "subTabs", -1);
		  	subtabCount[slotCount] = 0;
		  	slotCount++;
		  	ygDDListStudio.prototype.updateTabs();
		  }
		  var slotCount = {/literal}{$tabCounter}{literal};
		  var modCount = {/literal}{$modCounter}{literal};
			var subSlots = [];
			 var yahooSlots = [];
			function dragDropInit(){
				YAHOO.util.DDM.mode = YAHOO.util.DDM.POINT;
				for(mj = 0; mj <= slotCount; mj++){
					yahooSlots["slot" + mj] = new ygDDSlot("slot" + mj, "mainTabs");
					yahooSlots["slot" + mj].setHandleElId("handle" + mj);
					
					yahooSlots["noselectbottom"+ mj] = new ygDDListStudio("noselectbottom"+ mj , "subTabs", -1);
					for(msi = 0; msi <= subtabCount[mj]; msi++){
						yahooSlots["subslot"+ mj + '_' + msi] = new ygDDListStudio("subslot"+ mj + '_' + msi, "subTabs", 0);
						
					}
					
				}
				for(msi = 0; msi <= modCount ; msi++){
						yahooSlots["modSlot"+ msi] = new ygDDListStudio("modSlot" + msi, "subTabs", 1);
						
				}
				var trash1  = new ygDDListStudio("trashcan" , "subTabs", 'trash');
				ygDDListStudio.prototype.updateTabs();
			
			}
			
			YAHOO.util.DDM.mode = YAHOO.util.DDM.INTERSECT; 
			YAHOO.util.Event.addListener(window, "load", dragDropInit);
			
			
</script>	
{/literal}


<div id='logDiv' style='display:none'> 
</div>
</form>



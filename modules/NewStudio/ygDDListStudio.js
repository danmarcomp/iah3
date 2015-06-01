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



/* Copyright (c) 2006 Yahoo! Inc. All rights reserved. */

/**
 * @class a YAHOO.util.DDProxy implementation. During the drag over event, the
 * dragged element is inserted before the dragged-over element.
 *
 * @extends YAHOO.util.DDProxy
 * @constructor
 * @param {String} id the id of the linked element
 * @param {String} sGroup the group of related DragDrop objects
 */
var addListStudioCount = 0;
var moduleTabs = []

function ygDDListStudio(id, sGroup, fromOnly) {

	if (id) {
	
		if(id == 'trashcan' || id.indexOf('noselect') ==0){
			this.initTarget(id, sGroup);
		}else{
			this.init(id, sGroup);
		}
		this.initFrame();
		this.fromOnly = fromOnly;
	}

	var s = this.getDragEl().style;
	s.borderColor = "transparent";
	s.backgroundColor = "#f6f5e5";
	s.opacity = 0.76;
	s.filter = "alpha(opacity=76)";
}

ygDDListStudio.prototype = new YAHOO.util.DDProxy();

ygDDListStudio.prototype.clickContent = '';
ygDDListStudio.prototype.clickBorder = '';
ygDDListStudio.prototype.clickHeight = '';
ygDDListStudio.prototype.lastNode = false;
ygDDListStudio.prototype.startDrag
ygDDListStudio.prototype.startDrag = function(x, y) {

	var dragEl = this.getDragEl();
	var clickEl = this.getEl();
 
  this.parentID = clickEl.parentNode.id;
	dragEl.innerHTML = clickEl.innerHTML;
	dragElObjects = dragEl.getElementsByTagName('object');
	
	dragEl.className = clickEl.className;
	dragEl.style.color = clickEl.style.color;
	dragEl.style.border = "1px solid #aaa";

	// save the style of the object 
	this.clickContent = clickEl.innerHTML;
	this.clickBorder = clickEl.style.border;
	this.clickHeight = clickEl.style.height;
	
	clickElRegion = YAHOO.util.Dom.getRegion(clickEl);
	clickEl.style.height = (clickElRegion.bottom - clickElRegion.top) + 'px';
	clickEl.style.opacity = .5;
	clickEl.style.filter = "alpha(opacity=10)";
	clickEl.style.border = '2px dashed #cccccc';
};
ygDDListStudio.prototype.updateTabs = function(){
		moduleTabs = [];
		for(j = 0; j < slotCount; j++){
			
			var ul = document.getElementById('ul' + j);
			moduleTabs[j] = [];
			items = ul.getElementsByTagName("li");
			for(i = 0; i < items.length; i++) {
			
				// longreach - added - translate label
				var droplbl = app_string('LBL_STUDIO_DROP_HERE');
				if(droplbl == 'LBL_STUDIO_DROP_HERE')
					droplbl = '[Drop Here]'; // missing from lang. pack
			
				if(items.length == 1){
					items[i].innerHTML = droplbl;
				}else{
					if(items[i].innerHTML == droplbl){
						items[i].innerHTML='';
					} 
				}
				moduleTabs[ul.id.substr(2, ul.id.length)][subtabModules[items[i].id]] = true;
			}
			
		}
	
};
ygDDListStudio.prototype.endDrag = function(e) {
	
	var clickEl = this.getEl();
	clickEl.innerHTML = this.clickContent
	var p = clickEl.parentNode;
	if(p.id == 'trash'){
		p.removeChild(clickEl);
		this.lastNode = false;
		this.updateTabs();
		return;
	}
	if(this.clickHeight) {
	    clickEl.style.height = this.clickHeight;
			if(this.lastNode)this.lastNode.style.height=this.clickHeight;
	}
	else{ 
		clickEl.style.height = '';
		if(this.lastNode)this.lastNode.style.height='';
		}
	
	if(this.clickBorder){ 
	    clickEl.style.border = this.clickBorder;
			if(this.lastNode)this.lastNode.style.border=this.clickBorder;
	}
	else {
		clickEl.style.border = '';
			if(this.lastNode)this.lastNode.style.border='';
		}
		clickEl.style.opacity = 1;
				clickEl.style.filter = "alpha(opacity=100)";
		if(this.lastNode){
			this.lastNode.id = 'addLS' + addListStudioCount;
			subtabModules[this.lastNode.id] = this.lastNode.module;
			yahooSlots[this.lastNode.id] = new ygDDListStudio(this.lastNode.id, 'subTabs', false);
			addListStudioCount++;
				this.lastNode.style.opacity = 1;
				this.lastNode.style.filter = "alpha(opacity=100)";
		}
	this.lastNode = false;
	this.updateTabs();
};

ygDDListStudio.prototype.onDrag = function(e, id) {
 		
};

ygDDListStudio.prototype.onDragOver = function(e, id) {
	var el;
		 if(this.lastNode){
			this.lastNode.parentNode.removeChild(this.lastNode);
			this.lastNode = false;
		}
     if(id.substr(0, 7) == 'modSlot'){
     	return;
     }   
    if ("string" == typeof id) {
        el = YAHOO.util.DDM.getElement(id);
    } else { 
        el = YAHOO.util.DDM.getBestMatch(id).getEl();
    }
    
	dragEl = this.getDragEl();
	elRegion = YAHOO.util.Dom.getRegion(el);
    

	var mid = YAHOO.util.DDM.getPosY(el) + (Math.floor((elRegion.bottom - elRegion.top) / 2));
	var el2 = this.getEl();
	var p = el.parentNode;
 if( (this.fromOnly ||  ( el.id != 'trashcan' && el2.parentNode.id != p.id && el2.parentNode.id == this.parentID)) ){
 	if(typeof(moduleTabs[p.id.substr(2,p.id.length)][subtabModules[el2.id]]) != 'undefined')return;
 		
	}
	
 if(this.fromOnly && el.id != 'trashcan'){
	el2 = el2.cloneNode(true);
	el2.module = subtabModules[el2.id];
	el2.id = 'addListStudio' + addListStudioCount;
	this.lastNode = el2;
	this.lastNode.clickContent = el2.clickContent;
	this.lastNode.clickBorder = el2.clickBorder;
	this.lastNode.clickHeight = el2.clickHeight

	
  }
	if (YAHOO.util.DDM.getPosY(dragEl) < mid ) { // insert on top triggering item
		p.insertBefore(el2, el);
	}
	if (YAHOO.util.DDM.getPosY(dragEl) >= mid ) { // insert below triggered item
		p.insertBefore(el2, el.nextSibling);
	}
	
	
};

ygDDListStudio.prototype.onDragEnter = function(e, id) {
	
};

ygDDListStudio.prototype.onDragOut = function(e, id) {
 
}

/////////////////////////////////////////////////////////////////////////////

function ygDDListStudioBoundary(id, sGroup) {
	if (id) {
		this.init(id, sGroup);
		this.isBoundary = true;
	}
}

ygDDListStudioBoundary.prototype = new YAHOO.util.DDTarget();

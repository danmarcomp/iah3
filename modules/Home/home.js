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


SUGAR.sugarHome = function() {
	var originalLayout = null;
	var configureDashletId = null;
	var configureDlg = null;
	var addDlg = null;
	var leftColumnInnerHTML = null;
	var leftColObj = null;
	var maxCount;
	var warningLang;
	var pendingRetrieve = [];
	var dashletWidth = {};
	var refreshPending = false;
	
	return {
		// get the current dashlet layout
		getLayout: function(asString) {
			var columns = [];
			for(var je = 0; ; je++) {
			    var dashlets = $('col' + je);
				if(! dashlets)
					break;
			    var dashletIds = [];
			    for(var wp = 0; wp < dashlets.childNodes.length; wp++) {
			      if(typeof dashlets.childNodes[wp].id != 'undefined' && dashlets.childNodes[wp].id.match(/dashlet_[\w-]*/)) {
					dashletIds.push(dashlets.childNodes[wp].id.replace(/dashlet_/,''));
			      }
			    }
				if(asString) columns.push(dashletIds.join(','));
				else columns.push(dashletIds);
			}
			if(asString) return columns.join('|');
			return columns;
		},
		
		// called when dashlet is picked up
		onDrag: function(e, id) {
			originalLayout = SUGAR.sugarHome.getLayout(true);
			return true;
		},
		
		// called when dashlet is dropped
		onDrop: function(e, id) {
			var newLayout = SUGAR.sugarHome.getLayout(true);
		  	if(originalLayout != newLayout) { // only save if the layout has changed
				SUGAR.sugarHome.saveLayout(newLayout);
		  	}
		},
		
		// save the layout of the dashlet
		saveLayout: function(order) {
			var success = function(data) {
				SUGAR.ui.showStatus(app_string('LBL_SAVED_LAYOUT'), 2000);
			}
			var layout = SUGAR.session.dashboard_id;
			var url = 'async.php?module=Home&action=SaveLayout&layout=' + layout;
			SUGAR.conn.asyncRequest(url, {status_msg: app_string('LBL_SAVING_LAYOUT'), method: 'POST', postData: {page_layout: order}}, success);
		},

		uncoverPage: function(id) {
			SUGAR.sugarHome.hideConfigure();
			SUGAR.sugarHome.retrieveDashlet(SUGAR.sugarHome.configureDashletId);
		},
		
		// call to configure a Dashlet
		configureDashlet: function(id, title) {
            SUGAR.sugarHome.configureDashletId = id; // save the id of the dashlet being configured
			configureDlg = SUGAR.popups.openUrl('async.php?module=Home&action=ConfigureDashlet&id=' + id, null, {width: '504px', title_text: title, resizable: false});
		},
		
		hideConfigure: function() {
			if(configureDlg) configureDlg.close();
		},
		
		calcDashletWidth: function(id) {
			var outer = $('dashlet-body-' + id);
			if(outer) {
				var size = SUGAR.ui.getEltRegion(outer);
				if(size)
					return parseInt(size.width) - 2;
			}
			return '';
		},
				
		/** returns dashlets contents
		 * if url is defined, dashlet will be retrieve with it, otherwise use default url
		 *
		 * @param string id id of the dashlet to refresh
		 * @param string url url to be used
		 * @param function callback callback function after refresh
		 * @param bool dynamic does the script load dynamic javascript, set to true if you user needs to refresh the dashlet after load
		 */
		retrieveDashlet: function(id, url, callback, dynamic, refresh) {
			var multi, postData = [], idx;
			if(!url) {
				url = 'async.php?action=DisplayDashlet&module=Home';
				if(YLang.isArray(id)) {
					for(idx = 0; idx < id.length; idx++) {
						postData.push('id[]=' + id[idx]);
						postData.push('width[]=' + SUGAR.sugarHome.calcDashletWidth(id[idx]));
					}
					multi = true;
				} else {
					postData.push('id=' + id);
					postData.push('width=' + SUGAR.sugarHome.calcDashletWidth(id));
				}
			}
			if( (pid = SUGAR.session.dashboard_id) )
				url += '&layout='+pid;
			if(dynamic)
				url += '&dynamic=true';
			if(refresh)
				url += '&refresh=true';
			if(this.layoutMode)
				url += '&sample=true';
			if(this.retrieveParams) {
				if(YLang.isArray(this.retrieveParams))
					postData.pushArray(this.retrieveParams);
				else
					url += this.retrieveParams;
			}
			postData = postData.join('&');

			var outer_div = {};
			var ids = id;
			if(! multi)
				ids = [ids];
			for(idx=0; idx < ids.length; idx++) {
				var did = ids[idx];
				outer_div[did] = $('dashlet_entire_' + did);
				if(outer_div[did].style.position != 'absolute')
					SUGAR.util.maskDiv(outer_div[did], did);
			}
			
			var fillInDashlet = function(dashlet_id, response) {
				outer_div[dashlet_id].innerHTML = SUGAR.util.disableInlineScripts(response);
				SUGAR.util.evalScript(response);
			}
			
		 	var handleResponse = function(data) {
				if(data) {
					if(! multi)
						fillInDashlet(ids[0], data.responseText);
					else {
						var text = data.responseText;
						var start = text.search(/\{#-(.*?)-#}/);
						while(start >= 0) {
							var end = text.search('-#}');
							var id = text.substring(3, end);
							text = text.substring(end + 3);
							var next = text.search(/\{#-(.*?)-#}/);
							var body;
							if(next >= 0) {
								body = text.substring(0, next);
								text = text.substring(next);
								start = 0;
							} else {
								body = text;
								start = next;
							}
							fillInDashlet(id, body);
						}
					}
				}
				if(callback) callback();
			}
			SUGAR.conn.asyncRequest(url, {status_msg: mod_string('LBL_LOADING_DASHLETS'), postData: postData}, handleResponse);
			return false;
		},

		refreshDashlet: function(id, url, callback, dynamic) {
			this.retrieveDashlet(id, url, callback, dynamic, true);
		},

        initDashletAutoRefresh: function(id, interval) {
            if (interval > 0) {
                var one_min_in_ms = 60000;
                var interval_ms = interval * one_min_in_ms;
                setTimeout(function() { SUGAR.sugarHome.refreshDashlet(id) }, interval_ms);
            }
        },

		fetchPendingDashlets: function() {
			if(pendingRetrieve.length) {
				var dlets = pendingRetrieve;
				pendingRetrieve = [];
				this.retrieveDashlet(dlets, null, null, false, refreshPending);
			}
		},
		pendingDashlet: function(id, refresh) {
			pendingRetrieve.push(id);
			YAHOO.util.Event.onDOMReady(
				function() { setTimeout(function() { SUGAR.sugarHome.fetchPendingDashlets(); }, 200); }
			);
			refreshPending = refresh;
		},
		
		focusDashletFilter: function(id) {
			SUGAR.ui.focusInput(id+'-FilterForm', 'filter_text');
		},
		showDashletFilter: function(id) {
			var vis = toggleDisplay('dashlet_filter_'+id);
			if(vis && vis !== 'none')
				this.focusDashletFilter(id);
		},
		setDashletFilter: function(id, value) {
			this.retrieveParams = '&filter_value_'+id+'='+encodeURIComponent(value);
			this.refreshDashlet(id, '', function() { SUGAR.sugarHome.focusDashletFilter(id); });
		},
		
		// for the display columns widget
		setChooser: function() {		
			/*var displayColumnsDef = new Array();
			var hideTabsDef = new Array();

		    var left_td = $('display_tabs_td');	
		    var right_td = $('hide_tabs_td');			
	
		    var displayTabs = left_td.getElementsByTagName('select')[0];
		    var hideTabs = right_td.getElementsByTagName('select')[0];
			
			for(var i = 0; i < displayTabs.options.length; i++) {
				displayColumnsDef.push(displayTabs.options[i].value);
			}
			
			if(typeof hideTabs != 'undefined') {
				for(var i = 0; i < hideTabs.options.length; i++) {
			         hideTabsDef.push(hideTabs.options[i].value);
				}
			}
			
			$('displayColumnsDef').value = displayColumnsDef.join('|');
			$('hideTabsDef').value = hideTabsDef.join('|');*/
		},
		
		deleteDashlet: function(id) {
			if(confirm(SUGAR.language.get('Home', 'LBL_REMOVE_DASHLET_CONFIRM'))) {

				var del = function() {
					var success = function(data) {
						var dashlet = $('dashlet_' + id);
						dashlet.parentNode.removeChild(dashlet);
						SUGAR.ui.showStatus(SUGAR.language.get('Home', 'LBL_REMOVED_DASHLET'), 2000);
					}
				
					var layout = SUGAR.session.dashboard_id;
					var url = 'async.php?module=Home&action=DeleteDashlet&id=' + id + '&layout=' + layout;
					var cObj = SUGAR.conn.asyncRequest(url, {status_msg: SUGAR.language.get('Home', 'LBL_REMOVING_DASHLET')}, success);
				}
				
				var anim = new YAHOO.util.Anim('dashlet_entire_' + id, { height: {to: 1} }, .5 );					
				anim.onComplete.subscribe(del);					
				$('dashlet_entire_' + id).style.overflow = 'hidden';
				anim.animate();
				
				return false;
			}
			return false;
		},
		
		checkMaxDashlets: function(columns) {
			var numDlets = 0;
			for(var i = 0; i < columns.length; i++)
				numDlets += columns[i].length;
			if(numDlets >= SUGAR.sugarHome.maxCount) {
				alert(SUGAR.language.get('Home', 'LBL_MAX_DASHLETS_REACHED'));
				return true;
			}
			return false;
		},
		
		addDashlet: function(id) {
			var columns = SUGAR.sugarHome.getLayout();
			if(addDlg) addDlg.close();
			if(this.checkMaxDashlets(columns))
				return;
			var success = function(data) {
				var colZero = $('col0'),
					newDashlet = document.createElement('li'), // build the list item
					dashletId = trim(data.responseText);
				newDashlet.id = 'dashlet_' + dashletId;
				newDashlet.className = 'noBullet';
				// hide it first, but append to getRegion
				newDashlet.innerHTML = '<div style="position: absolute; top: -1000px; overflow: hidden;" id="dashlet_entire_' + dashletId + '"></div>';

				colZero.insertBefore(newDashlet, colZero.firstChild); // insert it into the first column
				
				var finishRetrieve = function() {
					var dashletEntire = $('dashlet_entire_' + dashletId);
					var dd = new ygDDList('dashlet_' + dashletId); // make it draggable
					dd.setHandleElId('dashlet_header_' + dashletId);
					dd.onMouseDown = SUGAR.sugarHome.onDrag;
					dd.onDragDrop = SUGAR.sugarHome.onDrop;

					SUGAR.ui.showStatus(SUGAR.language.get('Home', 'LBL_ADDED_DASHLET'), 2000);
					var dashletRegion = YAHOO.util.Dom.getRegion(dashletEntire);
					dashletEntire.style.position = 'relative';
					dashletEntire.style.height = '1px';
					dashletEntire.style.top = '0px';

					var anim = new YAHOO.util.Anim('dashlet_entire_' + dashletId, { height: {to: dashletRegion.bottom - dashletRegion.top} }, .5 );
					anim.onComplete.subscribe(function() { $('dashlet_entire_' + dashletId).style.height = ''; });
					anim.animate();
				}
				SUGAR.sugarHome.retrieveDashlet(dashletId, null, finishRetrieve, true); // retrieve it from the server	
			}
			
			var layout = SUGAR.session.dashboard_id;
			var url = 'async.php?module=Home&action=AddDashlet&id=' + id + '&layout=' + layout;
			SUGAR.conn.asyncRequest(url, {status_msg: SUGAR.language.get('Home', 'LBL_ADDING_DASHLET')}, success);
			return false;
		},
		
		showDashletsPopup: function() {
			var columns = SUGAR.sugarHome.getLayout();
			var layout = SUGAR.session.dashboard_id;
			if(this.checkMaxDashlets(columns))
				return;
			if(addDlg) {
				addDlg.show();
				return false;
			}
			addDlg = new SUGAR.ui.Dialog("dashlet-add",
				{ width: "504px", destroy_on_close: false, title_text: mod_string('LBL_ADD_DASHLETS'), resizable: false });
			addDlg.fetchContent('async.php?module=Home&action=DashletsPopup&layout=' + layout, {}, null, true);
			return false;
		},
		
		doneAddDashlets: function() {
			$('add_dashlets').style.display = '';
			leftColObj.innerHTML = leftColumnInnerHTML;
			return false;
		},
		
		postForm: function(theForm, callback) {	
			var success = function(data) {
				if(data) {
					callback(data.responseText);
				}
			}
			SUGAR.conn.sendForm(theForm, {status_msg: app_string('LBL_SAVING')}, success);
			return false;
		},
		
		setColumns: function(cols) {
			var layout = SUGAR.session.dashboard_id;
			var reload = function(data) {
				SUGAR.util.fetchContentMain('async.php?module=Home&action=index&edit=page&layout=' + layout);
			}
			var url = 'async.php?module=Home&action=SetPageColumns&columns='+cols +'&layout=' + layout;
			var cObj = SUGAR.conn.asyncRequest(url, {}, reload);
		},
		setColumnWidths: function(widths) {
			this.setColumns('&widths='+widths);
		},

		/**
		 * Generic javascript method to use Dashlet methods
		 * 
		 * @param string dashletId Id of the dashlet being call
		 * @param string methodName method to be called (function in the dashlet class)
		 * @param string postData data to send (eg foo=bar&foo2=bar2...)
		 * @param bool refreshAfter refreash the dashlet after sending data
		 * @param function callback function to be called after dashlet is refreshed (or not refresed) 
		 */ 
		callMethod: function(dashletId, methodName, postData, refreshAfter, callback) {
        	var response = function(data) {
				if(refreshAfter) SUGAR.sugarHome.retrieveDashlet(dashletId);
				if(callback) {
					callback(data.responseText);
				}
        	}
        	var layout = SUGAR.session.dashboard_id;
	    	var post = 'module=Home&layout=' + layout + '&action=CallMethodDashlet&method=' + methodName + '&id=' + dashletId + '&' + postData;
			SUGAR.conn.asyncRequest(null, {method: 'POST', postData: post, status_msg: app_string('LBL_SAVING')}, response);
		},

		setDashletWidth : function(id, width)
		{
			dashletWidth[id] = width;
		}
	 };
	 
}();

SUGAR.sugarHome.ColumnSlider = function(elt, column) {
	SUGAR.sugarHome.ColumnSlider.superclass.constructor.call(this, elt, 'col_sliders', {scroll: true, dragElId: 'slider_proxy'});
	this.setYConstraint(0, 0);
	this.column = column;
	this.on('startDragEvent', this.startDragEvt, this, true);
	this.on('dragEvent', this.dragEvt, this, true);
	this.on('endDragEvent', this.endDragEvt, this, true);
};
YAHOO.extend(SUGAR.sugarHome.ColumnSlider, YAHOO.util.DDProxy, {
	startOffset:null, dragOnly:true, constrainY: true,
	
	startDragEvt: function(x,y) {
		this.setYConstraint(0, 0);
		var tbl = $('dashboard_columns');
		var tblRgn = YAHOO.util.Dom.getRegion(tbl);
		var dragEl = this.getDragEl();
		this.currentLoc = this.startLoc = YAHOO.util.Dom.getXY(dragEl);
		dragEl.style.display = '';
		dragEl.style.height = '' + (tblRgn.height + 6) + 'px';
		dragEl.style.width = '8px';
		this.leftCol = tbl.rows[0].cells[this.column-1];
		this.rightCol = tbl.rows[0].cells[this.column];
		this.leftRgn = YAHOO.util.Dom.getRegion(this.leftCol);
		this.rightRgn = YAHOO.util.Dom.getRegion(this.rightCol);
		this.leftWidth = parseInt(this.leftCol.width);
		this.rightWidth = parseInt(this.rightCol.width);

		var minX = Math.max(this.leftRgn.width - Math.max(this.leftRgn.width * 10/this.leftWidth, 200), 0);
		var maxX = Math.max(this.rightRgn.width - Math.max(this.rightRgn.width * 10/this.rightWidth, 200), 0);
		this.setXConstraint(minX, maxX);
		this.leftLbl = $('slider_label1');
		this.rightLbl = $('slider_label2');
		this.leftLbl.style.display = '';
		this.rightLbl.style.display = '';
		this.calcWidths();
	},
	calcWidths: function() {
		var diff = this.currentLoc[0] - this.startLoc[0];
		this.newLeftWidth = Math.round((this.leftRgn.width + diff) * this.leftWidth / this.leftRgn.width);
		this.newRightWidth = this.rightWidth + this.leftWidth - this.newLeftWidth;
		this.leftLbl.innerHTML = '' + this.newLeftWidth + '%';
		this.rightLbl.innerHTML = '' + this.newRightWidth + '%';
		this.centerLabel(this.leftRgn, this.leftLbl);
		this.centerLabel(this.rightRgn, this.rightLbl);
	},
	centerLabel: function(colRgn, label) {
		var lblRgn = YAHOO.util.Dom.getRegion(label);
		var diff = this.currentLoc[0] - this.startLoc[0];
		var colWidth = colRgn.width + diff;
		label.style.left = '' + (colRgn.left + (colWidth - lblRgn.width) / 2) + 'px';
		label.style.top = '' + (colRgn.top - 5) + 'px';
	},
	dragEvt: function(e) {
		this.currentLoc = YAHOO.util.Dom.getXY(this.getDragEl());
		this.calcWidths();
	},
	endDragEvt: function(e) {
		this.leftLbl.style.display = 'none';
		this.rightLbl.style.display = 'none';
		var tbl = $('dashboard_columns');
		var row = tbl.rows[0];
		var w, ws = [];
		for(var i = 0; i < row.cells.length; i++) {
			if(i == this.column - 1)
				w = this.newLeftWidth;
			else if(i == this.column)
				w = this.newRightWidth;
			else
				w = parseInt(row.cells[i].width);
			ws.push(w);
		}
		SUGAR.sugarHome.setColumnWidths(ws.join(','));
	}
});

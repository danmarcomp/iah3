var AjaxObject = {
	ret : '',
	currentRequestObject : null,
	timeout : 30000, // 30 second timeout default
	forceAbort : false,
	
	/**
	 */
	_reset : function() {
		this.timeout = 30000;
		this.forceAbort = false;
	},
    handleFailure : function(o) {
    	alert('asynchronous call failed.');



	},
	/**
	 */
	startRequest : function(callback, args, forceAbort) {
		if(this.currentRequestObject != null) {
			if(this.forceAbort == true || callback.forceAbort == true) {
				YAHOO.util.Connect.abort(this.currentRequestObject, null, false);
			}
		}
		
		this.currentRequestObject = YAHOO.util.Connect.asyncRequest('POST', "./index.php?module=Administration&action=Async&to_pdf=true", callback, args);
		this._reset();
	},
	
	/**************************************************************************
	 * Place callback handlers below this comment
	 **************************************************************************/
	 
	/**
	 * gets an estimate of how many rows to process
	 */
	refreshEstimate : function(o) {
		this.ret = JSON.parse(o.responseText);
		document.getElementById('repairXssDisplay').style.display = 'inline';
		document.getElementById('repairXssCount').value = this.ret.count;
		
		SUGAR.Administration.RepairXSS.toRepair = this.ret.toRepair;
	},
	showRepairXssResult : function(o) {
		var resultCounter = document.getElementById('repairXssResultCount');
		
		this.ret = JSON.parse(o.responseText);
		document.getElementById('repairXssResults').style.display = 'inline';
		
		if(this.ret.msg == 'success') {
			SUGAR.Administration.RepairXSS.repairedCount += this.ret.count;
			resultCounter.value = SUGAR.Administration.RepairXSS.repairedCount;
		} else {
			resultCounter.value = this.ret;
		}
		
		SUGAR.Administration.RepairXSS.executeRepair();
	}
};

/*****************************************************************************
 *	MODEL callback object:
 * ****************************************************************************
	var callback = {
		success:AjaxObject.handleSuccess,
		failure:AjaxObject.handleFailure,
		timeout:AjaxObject.timeout,
		scope:AjaxObject,
		forceAbort:true, // optional
		argument:[ieId, ieName, focusFolder] // optional
	};
 */

var callbackRepairXssRefreshEstimate = {
	success:AjaxObject.refreshEstimate,
	failure:AjaxObject.handleFailure,
	timeout:AjaxObject.timeout,
	scope:AjaxObject
};
var callbackRepairXssExecute = {
	success:AjaxObject.showRepairXssResult,
	failure:AjaxObject.handleFailure,
	timeout:AjaxObject.timeout,
	scope:AjaxObject
};

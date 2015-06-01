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


{literal}<script>
if(typeof JotPad == 'undefined') { // since the dashlet can be included multiple times a page, don't redefine these functions
	JotPad = function() {
	    return {
	    	/**
	    	 * Called when the textarea is blurred
	    	 */
	        blur: function(ta, id) {
	        	var status = '{/literal}{$saving}{literal}';
	        	// what data to post to the dashlet
                var va=encodeURIComponent(JSON.stringify(ta.value));
                var postData = 'to_pdf=1&module=Home&action=CallMethodDashlet&method=saveText&id=' + id + '&savedText=' + va;
				SUGAR.conn.asyncRequest('index.php', {method: 'POST', postData: postData, status_msg: status}, JotPad.saved);
	        },
		    /**
	    	 * Called when the textarea is double clicked on
	    	 */
			edit: function(divObj, id) {
				var ta = document.getElementById('jotpad_textarea_' + id);
				var ta_outer = document.getElementById('jotpad_textouter_' + id);
				if(isIE)
					ta.value = divObj.innerText;
				else
					ta.innerHTML = divObj.innerHTML.replace(/<br>/gi, '');
				divObj.style.display = 'none';
				ta_outer.style.display = '';
				ta.focus();
			},
		    /**
	    	 * handle the response of the saveText method
	    	 */
	        saved: function(data) {
	        	SUGAR.ui.showStatus('{/literal}{$saved}{literal}', 2000);
	        	var result;
	        	eval(data.responseText);
	           	if(typeof result != 'undefined') {
					var ta_outer = document.getElementById('jotpad_textouter_' + result['id']);
					var theDiv = document.getElementById('jotpad_' + result['id']);
					theDiv.innerHTML = result['savedText'];
					ta_outer.style.display = 'none';
					theDiv.style.display = '';
				}
	        }
	    };
	}();
}
</script>{/literal}

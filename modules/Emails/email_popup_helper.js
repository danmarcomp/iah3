/**
 *
 * Popup helper functions
 *
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

// $Id: email_popup_helper.js,v 1.3 2005/12/12 17:03:24 roger Exp $

function send_back_email(module, id, use_2nd_email)
{
	if(use_2nd_email) {
		var assoc = associated_javascript_data[id];
		assoc.email1 = assoc.email2;
		assoc.email_and_name1 = assoc.email_and_name2;
	}
	send_back(module, id);
	do_close_popup();
}

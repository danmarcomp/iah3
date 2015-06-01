<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
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
 ********************************************************************************/
//updates the link between contract and document with latest revision of
//the document and sends the control back to calling page.

require_once('modules/Documents/Document.php');
require_once('include/formbase.php');
if (!empty($_REQUEST['record'])) {

	$document = new Document();
	$document->retrieve($_REQUEST['record']);
	if (!empty($document->document_revision_id) && !empty($_REQUEST['get_latest_for_id']))  {
		$query="update linked_documents set document_revision_id='{$document->document_revision_id}', date_modified='".gmdate("Y-m-d H:i:s")."' where id ='{$_REQUEST['get_latest_for_id']}' ";
		$document->db->query($query);
	}	
}
handleRedirect();
?>

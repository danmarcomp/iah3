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

clean_string($_REQUEST['target_module'], "STANDARD");
clean_string($_REQUEST['tpl'], "STANDARD");
$target_module = $_REQUEST['target_module']; // target class
if(is_file('modules/' . $target_module . '/' . $target_module . 'QuickCreate.php')) { // if there is a quickcreate override
    require_once('modules/' . $target_module . '/' . $target_module . 'QuickCreate.php');
    $editviewClass = $target_module . 'QuickCreate'; // eg. OpportunitiesQuickCreate 
    $editview = new $editviewClass($target_module, 'modules/' . $target_module . '/tpls/' . $_REQUEST['tpl']);
    $editview->viaAJAX = true;
}
else { // else use base class
    require_once('include/EditView/EditViewQuickCreate.php');
    $editview = new EditViewQuickCreate($target_module, 'modules/' . $target_module . '/tpls/' . $_REQUEST['tpl']);
}

$editview->process();
echo $editview->display();

?>

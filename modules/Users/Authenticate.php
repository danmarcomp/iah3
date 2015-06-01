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
/*********************************************************************************
 * $Id: Authenticate.php,v 1.46.4.3 2006/05/09 20:35:46 majed Exp $
 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright(C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/



$authController->login($_REQUEST['user_name'], $_REQUEST['user_password']);

$module = $action = $record = "";

if(isset($_SESSION['authenticated_user_id'])) {
	if(! empty($_REQUEST['login_module'])) {
	    $module = '?module='.$_REQUEST['login_module'];
	    $action = !empty($_REQUEST['login_action']) ? '&action='.$_REQUEST['login_action'] : '&action=index';
	    $record = !empty($_REQUEST['login_record']) ? '&record='.$_REQUEST['login_record'] : '';
    }
    if(isset($_REQUEST['js_enabled']) && ! $_REQUEST['js_enabled']) {
    	$module = '?module=Home';
    	$action = '&action=JSDisabled';
    	$record = '';
    	if(isset($_REQUEST['login_module'])) {
    		$record .= '&login_module='.$_REQUEST['login_module'];
    		$record .= '&login_action='.$_REQUEST['login_action'];
    		$record .= '&login_record='.$_REQUEST['login_record'];
    	}
    }
}

header('Location: index.php'.$module.$action.$record);

sugar_cleanup();
?>

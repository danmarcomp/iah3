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
/*
 * Created on Oct 4, 2005
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
require_once('XTemplate/xtpl.php');
require_once("data/Tracker.php");
require_once('themes/'.$theme.'/layout_utils.php');


global $app_strings;
global $app_list_strings;
global $mod_strings;
global $current_user;

$xtpl = new XTemplate('modules/MailMerge/Step5.html');
$xtpl->assign("MOD", $mod_strings);
$xtpl->assign("APP", $app_strings);

if(!empty($_REQUEST['mtime']))
{
	$mTime = $_REQUEST['mtime'];
	$file = $_SESSION['MAILMERGE_TEMP_FILE_'.$mTime];
	$rtfFile = 'sugartokendoc'.$mTime.'.doc';
	unlink($file);
	if(file_exists($rtfFile)){
		unlink($rtfFile);
	}
}

$xtpl->parse("main");
$xtpl->out("main");
?>

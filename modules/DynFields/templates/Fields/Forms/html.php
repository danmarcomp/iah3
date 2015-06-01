<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
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



require_once('modules/DynFields/templates/Fields/Forms/setupform.php');
$edit_mod_strings = return_module_language($current_language, 'DynFields');
$smartyForm->assign('MOD', $edit_mod_strings);
if(!empty($cf))$smartyForm->assign('cf', $cf);
if(file_exists('include/CKeditor/ckeditor.php')) {
	include('include/CKeditor_IAH/CKeditor_IAH.php') ;
	$oldcontents = ob_get_contents();
	ob_clean();	
	$oCKeditor = new CKeditor_IAH();
	$instancename = 'htmlcode';  
	$content = "";
	if(!empty($cf->ext4)) {
		$content = $cf->ext4;
	}	
	$oCKeditor->config['toolbar']= 'Basic';
	$oCKeditor->config['height'] = 200;
	$oCKeditor->config['width'] = 300;
	
	$htmlareaSrc = $oCKeditor->editor($instancename, $content);
	$htmlareaSrc = str_replace(array("\r\n", "\n"), " ",$htmlareaSrc);	
	$smartyForm->assign("HTML_EDITOR", $htmlareaSrc);
	ob_clean();
	echo $oldcontents;
}
$smartyForm->display('modules/DynFields/templates/Fields/Forms/html.tpl')
?>

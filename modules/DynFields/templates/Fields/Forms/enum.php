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
if(!isset($edit_mod_strings)){
$edit_mod_strings = return_module_language($current_language, 'DynFields');
}
if(!empty($_REQUEST['data_type']) && $_REQUEST['data_type'] == 'radioenum'){
	$edit_mod_strings['LBL_DROP_DOWN_LIST'] = $edit_mod_strings['LBL_RADIO_FIELDS'];
}
$smartyForm->assign('MOD', $edit_mod_strings);
$my_list_strings = $app_list_strings;
foreach($my_list_strings as $key=>$value){
	if(!is_array($value)){
		unset($my_list_strings[$key]);
	}
}
$dropdowns = array_keys($my_list_strings);
asort($dropdowns);
$keys = array_keys($dropdowns);
$first_string = $my_list_strings[$dropdowns[$keys[0]]];
if(!empty($cf))$smartyForm->assign('cf', $cf);
$smartyForm->assign('dropdowns',$dropdowns);
$smartyForm->assign('selected_dropdown', $first_string);
require_once('include/JSON.php');
$json = new JSON(JSON_LOOSE_TYPE);
$smartyForm->assign('app_list_strings', $json->encode($my_list_strings));
$smartyForm->display('modules/DynFields/templates/Fields/Forms/enum.tpl')
?>

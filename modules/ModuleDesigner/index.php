<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point'); 
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */

if (! in_array('designer', $_SESSION['LIC_PRODUCTS']))
	indexRedirect(array('path' => 'index.php'));

require_once 'include/Sugar_Smarty.php';

// Only consider modules with primary beans
$beans = AppConfig::setting('modinfo.primary_beans');

$modules = array();
global $theme;
// filter out modules that explicitly say they do not want module designer
foreach ($beans as $module => $model) {
	if (AppConfig::setting("modinfo.by_name.$module.detail.module_designer", true))
		$modules[] = array(
			'name' => $module,
			'label' => translate('LBL_MODULE_TITLE', $module),
			'icon' => get_image($module, ''),
		);
}

usort($modules, 'sort_builder_modules');

$tpl = new Sugar_Smarty;
$tpl->assign('LANG',  $mod_strings);
$tpl->assign('modules', $modules);


$title = $mod_strings['LBL_MODULE_TITLE'];
echo get_module_title($mod_strings['LBL_MODULE_NAME'], $title, true); 

$tpl->display('modules/ModuleDesigner/templates/modules_list.tpl');


function sort_builder_modules($a, $b)
{
	return strcmp($a['label'], $b['label']);
}



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

 * Description:  Target for ajax calls to retrieve AdditionalDetails
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

global $beanList, $beanFiles, $current_user;

$moduleDir = empty($_REQUEST['bean']) ? '' : $_REQUEST['bean'];
$beanName = empty($beanList[$moduleDir]) ? '' : $beanList[$moduleDir];
$id = empty($_REQUEST['id']) ? '' : $_REQUEST['id'];

if(empty($beanFiles[$beanName]) ||
    empty($id) || !is_file(substr($beanFiles[$beanName], 0, strrpos($beanFiles[$beanName], '/')) . '/metadata/additionalDetails.php')) {
        //echo 'bad data';
        die();
} 

require_once($beanFiles[$beanName]);
require_once(substr($beanFiles[$beanName], 0, strrpos($beanFiles[$beanName], '/')) . '/metadata/additionalDetails.php');
$adFunction = 'additionalDetails' . $beanName;

if(function_exists($adFunction)) { // does the additional details function exist
    global $theme;
    $json = getJSONobj();
    $bean = new $beanName();
	$bean->retrieve($id);
    $arr = array_change_key_case($bean->toArray(), CASE_UPPER);

    $results = $adFunction($arr);
    $retArray['body'] = str_replace(array("\rn", "\r", "\n"), array('','','<br />'), $results['string']);
    if(!$bean->ACLAccess('EditView')) $results['editLink'] = '';

	$caption = isset($results['caption']) ? $results['caption'] : $app_strings['LBL_ADDITIONAL_DETAILS'];
	$icon =  get_image_info($bean->module_dir);

    $retArray['caption'] = "<div style='float:left'>{$icon['html']}&nbsp;{$caption}</div><div style='float: right'>" . (!empty($results['editLink']) ? "<a title='{$app_strings['LBL_EDIT_BUTTON']}' href={$results['editLink']}><img style='margin-top: 2px' border=0 src={$image_path}edit_inline.gif></a>" : '');
    $retArray['caption'] .= (!empty($results['viewLink']) ? "<a title='{$app_strings['LBL_VIEW_BUTTON']}' href={$results['viewLink']}><img style='margin-left: 2px; margin-top: 2px' border=0 src={$image_path}view_inline.gif></a>" : '') . "</div>"; 
    $retArray['width'] = (empty($results['width']) ? '300' : $results['width']);              
    $retArray['theme'] = $theme;

    echo 'result = ' . $json->encode($retArray);
}

?>
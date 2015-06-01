<?php
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
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


require_once 'include/utils/html_utils.php';

global $db;

echo '<p>';
echo get_module_title($mod_strings['LBL_UPGRADE_SOFTWARE_PRODUCTS'], $mod_strings['LBL_UPGRADE_SOFTWARE_PRODUCTS_DESC'], false);
echo '</p>';

$qDone = "SELECT * FROM versions WHERE name = 'Default Software Product'";
$rDone = $db->query($qDone);
$rowsDone = $db->getRowCount($rDone);
$done = $rowsDone > 0;

if ($done) {
	echo $mod_strings['LBL_UPGRADE_PERFORMED'];
	return;
} else if (empty($_REQUEST['upgrade_confirm'])) {
	echo ' <a href="index.php?module=Administration&action=UpgradeSoftwareProducts&upgrade_confirm=1">' . $mod_strings['LBL_CONTINUE'] . '</a>';
	return;
}

require_once 'modules/SoftwareProducts/SoftwareProduct.php';
$sp = new SoftwareProduct;
$sp->name = 'Default Product';
$productId = $sp->save();

$query = "UPDATE releases SET product_id = '$productId'";
$db->query($query);

$query = "UPDATE bugs SET product_id = '$productId' WHERE (found_in_release OR planned_for_release OR fixed_in_release)";
$db->query($query);

echo $mod_strings ['LBL_UPGRADE_COMPLETE'];

require_once('modules/Versions/Version.php');
Version::mark_upgraded('Default Software Product', '6.0.2', '6.0.2');



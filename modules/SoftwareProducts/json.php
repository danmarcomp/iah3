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

$json_supported_actions['get_releases_for_product'] = array();
function json_get_releases_for_product()
{
    require_once 'modules/Releases/Release.php';
    $release = new Release;
    $releases = $release->get_for_product(@$_REQUEST['record'], false, false, 'Active');
	json_return_value($releases);
}

$json_supported_actions['portal_get_software_products'] = array(
	'login_required' => 'portal',
);

function json_portal_get_software_products()
{
	global $db;
	require_once'modules/SoftwareProducts/SoftwareProduct.php';
	$products = get_software_products_list();
	foreach ($products as $id => $name) {
		$products[$id] = array('name' => $name, 'releases' => array());
	}
	$query = "SELECT id, name, product_id FROM releases WHERE !deleted";
	$res = $db->query($query);
	while ($row = $db->fetchByAssoc($res)) {
		$products[$row['product_id']]['releases'][$row['id']] = $row['name'];
	}
	json_return_value($products);
}




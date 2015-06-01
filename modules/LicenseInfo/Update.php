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


if(! empty($_SESSION['LIC_NAME']))
	action_restricted_for('demo');

require_once('modules/LicenseInfo/LicenseHistory.php');
require_once('modules/Users/User.php');
global $vendor_info, $sugar_version, $sugar_flavor;
global $timedate;
require_once('vendor_info.php');
require_once('sugar_version.php');

$newlicenses = null;
$seed = new LicenseHistory();

function fail_update($msg) {
	global $mod_strings;
	print '<b>'.$mod_strings['LBL_LICENSE_UPDATE_FAILURE'].'</b><br>&nbsp;&nbsp;&nbsp;'.$msg;
	print_return_button();
	exit;
}
function print_return_button() {
	global $app_strings;
	echo <<<EOS
		<p>
		<form action="index.php" method="GET">
		<input type="hidden" name="module" value="LicenseInfo">
		<input type="hidden" name="action" value="index">
		<button type="submit" class="input-button input-outer" name=""><div class="input-icon icon-return left"></div><span class="input-label">{$app_strings['LNK_LIST_RETURN']}</span></button>
		</form>
		</p>
EOS;
}

if(! empty($_REQUEST['license_key'])) {

	$request = array();
	$request['license_key'] = $_REQUEST['license_key'];
	$request['version'] = $sugar_version;
	$request['flavor'] = $sugar_flavor;
	$request['vendor_id'] = $vendor_info['id'];
	
	if($seed->retrieve_latest() !== null) {
		$request['license_id'] = $seed->license_id;
		$request['license_vendor_id'] = $seed->vendor_id;
		$exts = $seed->get_extensions();
		$ext_ids = array();
		foreach($exts as $ext)
			$ext_ids[] = $ext->license_id;
		$request['extensions'] = implode(',', $ext_ids);
		$request['active_limit'] = $seed->ext_active_limit;
		$request['date_support_end'] = $seed->ext_support_end;
	}
	
	$query = "SELECT COUNT(*) as active_users FROM users WHERE status='Active' AND NOT portal_only AND NOT deleted";
	$result = $seed->db->query($query, false);
	if($result && ($count = $seed->db->fetchByAssoc($result)) !== null)
		$request['active_users'] = $count['active_users'];
	$query = "SELECT COUNT(DISTINCT user_id) AS activity_count FROM users_activity WHERE NOT deleted";
	$result = $seed->db->query($query, false);
	if($result && ($count = $seed->db->fetchByAssoc($result)) !== null)
		$request['activity_count'] = $count['activity_count'];

	$params = array();
	foreach($request as $key => $value)
		array_push($params, array('name' => $key, 'value' => $value ));

	if(class_exists('SoapClient')) {
		try {
			$sc = @new SoapClient($vendor_info['license_server'].'?wsdl', array(
				'encoding' => 'utf-8',
				'exceptions' => true,
				//'trace' => true,
				//'cache_wsdl' => WSDL_CACHE_NONE,
				'features' => SOAP_USE_XSI_ARRAY_TYPE));
			$result = $sc->fetch_licenses($params);
			if(! $result)
				fail_update("No response from server");
			$licenses = $result->licenses;
		} catch (SoapFault $e) {
			fail_update($e->faultstring);
			return;
		}
	} else {
		require_once('include/nusoap/nusoap.php');
		$soap = new nusoap_client($vendor_info['license_server'], false, false, false, false, false, 30, 30);

		$params = $soap->serialize_val($params); // bit of a hack for 4.2 nusoap class
		$result = $soap->call('fetch_licenses', $params);
	
		if($errstr = $soap->getError())
			fail_update("An error occurred while retrieving the license information: $errstr");
		$licenses = $result['licenses'];
	}
	
	$lics = array();
	foreach($licenses as $rlic) {
		$lic = array();
		foreach($rlic as $arr) {
			if(is_object($arr))
				$lic[$arr->name] = $arr->value;
			else
				$lic[$arr['name']] = $arr['value'];
		}
		$lics[] = $lic;
	}
	$newlicenses = $lics;
}
else if(isset($_FILES['license_file']) && is_uploaded_file($_FILES['license_file']['tmp_name'])) {
	$fpath = $_FILES['license_file']['tmp_name'];
	$fp = fopen($fpath, 'rb');
	$content = fread($fp, filesize($fpath));
	if(empty($content) || ($serdata = base64_decode($content)) === false)
		fail_update("The provided license file is invalid.");
	$newlicenses = unserialize($serdata);
}
else
	fail_update("Either a valid license key or a license file must be provided in order to perform the update.");


$error_message = '';
if(!is_array($newlicenses)) {
	fail_update('The license information is invalid or could not be decoded.');
}
else {
	$query = "SELECT DISTINCT id,license_id FROM license_history WHERE NOT deleted";
	$result = $seed->db->query($query, true, "Error retrieving license history");
	$installed = array();
	while($row = $seed->db->fetchByAssoc($result))
		$installed[$row['license_id']] = $row['id'];

	$invalidate = false;
	$lic_fields = array_merge($seed->column_fields, $seed->additional_column_fields);
	foreach($newlicenses as $lic) {
		if(isset($installed[$lic['license_id']])) {
			$licobj = ListQuery::quick_fetch('LicenseHistory', $installed[$lic['license_id']]);
			$newobj = RowUpdate::for_result($licobj);
		} else
			$newobj = RowUpdate::blank_for_model('LicenseHistory');
		foreach($lic_fields as $f) {
			if(isset($lic[$f]))
				$newobj->set($f, $lic[$f]);
		}
		$newobj->set('date_loaded', $timedate->get_gmt_db_datetime());
		$lic_type = $newobj->getField('type');
		if(! in_array($lic_type, $seed->known_types))
			fail_update("The new license information does not appear to be valid.");
		if($lic_type == 'extension')
			$newobj->set('licensee', '--');
		if(! $newobj->validate())
			throw new IAHError("An error occurred in validating the license information.");
		$newobj->save();
		$invalidate = true;
	}
}

if($invalidate) {
	print $mod_strings['LBL_LICENSE_UPDATE_SUCCESS'];
	$seed->invalidateSessionCache();
}
else
	print $mod_strings['LBL_LICENSE_UPDATE_NOCHANGE'];

print_return_button();


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

$type = array_get_default($_REQUEST, 'type');

$bean_name = AppConfig::module_primary_bean($type);
if(! $bean_name)
	sugar_die('Missing parent type');
$model = new ModelDef($bean_name);
$fdefs = $model->getDisplayFieldDefinitions();
$addrs = array();
foreach($fdefs as $fid => $fdef) {
	if(@$fdef['type'] === 'address') {
		$addrs[$fid] = $fdef;
	}
}
if(! $addrs)
	sugar_die('No defined address fields');

$bean = ListQuery::quick_fetch($model, $_REQUEST['id']);
if(! $bean)
	sugar_die('Missing parent record');

$addr_field = array_get_default($_REQUEST, 'field');
if(isset($field) && isset($addrs[$field]))
	$prefix = $addrs[$field]['source']['prefix'];
else {
	$prefix = array_get_default($_REQUEST, 'prefix');
	if($prefix && isset($addrs[$prefix.'_address']))
		$addr_field = $prefix.'_address';
	else {
		reset($addrs);
		$addr_field = key($addrs);
	}
}

$fields = array(
	'street', 'city', 'state', 'postalcode', 'country',
);
$address = array();
foreach ($fields as $f) {
	$f = $prefix . 'address_' . $f;
	$val = $bean->getField($f);
	if (trim($val) !== '') {
		$address[] = $val;
	}
}
$display_name = javascript_escape($bean->getField('name'));
$address = javascript_escape(join(', ', $address));
$pageInstance->add_js_literal(
	"IAHMap.setAddress('{$display_name}', '{$address}');",
	null, LOAD_PRIORITY_FOOT);
$pageInstance->add_js_language('CompanyAddress', null, LOAD_PRIORITY_BODY);
$pageInstance->title = null;

require_once 'modules/CompanyAddress/CompanyAddress.php';
require_once 'include/layout/forms/EditableForm.php';

$ca = new CompanyAddress;
$companyAddresses =  $ca->getAll(null, false);

global $locale;

$options = array();
$main_addr = '';
foreach ($companyAddresses as $i => $addr)
{
	$plain = array();
	foreach ($fields as $f) {
		$f = 'address_'.$f;
		if (!empty($addr[$f])) {
			$plain[] = $addr[$f];
		}
	}
	if(! empty($addr['main']))
		$main_addr = $addr['id'];
	$options[$addr['id']] = array(
		'id' => $addr['id'],
		'name' => $addr['name'],
		'html' => $locale->getLocaleFormattedAddress($addr),
		'plain' => join(', ', $plain),
	);
}

$frm = new EditableForm('editview', 'gmaps');
$spec = array(
	'name' => 'address_select',
	'options' => $options,
	'required' => true,
	'width' => '250px',
);
$select_addr = $frm->renderSelect($spec, $main_addr);
$select_addr = $frm->open() . $select_addr . $frm->close();
$frm->exportIncludes();

?>

<div id="gmaps_sidebar" style="position: relative; width:295px; height:400px; margin: 5px">
	<b><?php echo $mod_strings['LBL_SELECT_COMPANY']?></b>
	<div style="margin-bottom: 0.3em">
	<?php echo $select_addr; ?>
	</div>
	<button type="button" class="input-button input-outer" onclick="IAHMap.showCompany()"><span class="input-label"><?php echo $mod_strings['LBL_SHOW_ON_MAP']?></span></button>
	<button type="button" class="input-button input-outer" onclick="IAHMap.driveFromTo(1)"><span class="input-label"><?php echo $mod_strings['LBL_DRIVE_FROM_HERE']?></span></button>
	<button type="button" class="input-button input-outer" onclick="IAHMap.driveFromTo(0)"><span class="input-label"><?php echo $mod_strings['LBL_DRIVE_HERE']?></span></button>
	<div id="gmaps_didyoumean" style="display:none; overflow:auto; position: absolute; top: 60px; left: 0; width: 100%; bottom: 0">
		<br><p>
			<?php echo $mod_strings['LBL_DIDYOUMEAN_1']?>
			<span style="font-weight:bold" id="gmaps_didyoumean_address"></span>
			<?php echo $mod_strings['LBL_DIDYOUMEAN_2']?>
			</p>
		<div id="gmaps_suggestions"></div>
	</div>
	<div id="gmaps_directions" style="display:none; overflow:auto; position:absolute; top: 60px; left: 0; width: 100%; bottom: 0"></div>
</div>
<div id="gmaps_outer" style="position: absolute; left: 305px; top: 5px; width:400px; height:400px; border:solid 1px black">
</div>

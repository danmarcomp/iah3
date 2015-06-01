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


require_once('modules/ProspectLists/DynamicProspects.php');


if (!isset($_REQUEST['record'])) {
	sugar_die('No record ID specified');
}

$record = $_REQUEST['record'];

$plist = ListQuery::quick_fetch('ProspectList', $record, array('id', 'dynamic'), array('acl_filters' => 'view'));
if(! $plist)
	sugar_die('Error loading prospect list');

$std_fields = array(
	'id',
	'salutation',
	'first_name',
	'last_name',
	'email1',
	'email2',
	'phone_home',
	'phone_work',
	'phone_mobile',
	'phone_other',
	'department',
	'title',
);
$address_map = array(
	'address_street' => 'primary_address_street',
	'address_city' => 'primary_address_city',
	'address_state' => 'primary_address_state',
	'address_postalcode' => 'primary_address_postalcode',
	'address_country' => 'primary_address_country',
);
$account_names = array(
	'prospects' => '',
	'contacts' => array('accounts_contacts', 'contact_id'),
	'leads' => 'account_name',
	'users' => '',
);

$what = '';
if (! empty($_REQUEST['export_what']))
    $what = $_REQUEST['export_what'];

switch ($what) {
	case 'targets':
		$tables = array('prospects');
		break;
	case 'contacts':
		$tables = array('contacts');
		break;
	case 'leads':
		$tables = array('leads');
		break;
	case 'users':
		$tables = array('users');
		break;
	default:
		$tables = array('prospects', 'contacts', 'leads', 'users');
		break;
}

if($plist->getField('dynamic')) {
	$qs = DynamicProspects::getListQueries($record);
	$lqu = new ListQuery();
	foreach($qs as $name => $q) {
		$lqu->addUnionQuery($q, strtolower($name));
	}
	$lqu->addFields($std_fields);
	
	$acct_spec = array('name' => 'account_name');
	foreach($account_names as $tbl => $acct_rel) {
		$lqa = $lqu->getUnionQuery($tbl);
		if($lqa)
			$lqa->auto_add_fields = false;
		if(! $acct_rel)
			$acct_spec['union_source'][$tbl] = array(
				'name' => 'account_name',
				'type' => 'varchar',
				'source' => array(
					'type' => 'static',
					'value' => '',
				),
			);
		else if(! is_array($acct_rel))
			;
		else
			$acct_spec['union_source'][$tbl] = 'primary_account.name';
	}
	$lqu->addField($acct_spec);
	
	foreach($address_map as $alias => $f) {
		$spec = array(
			'name' => $f,
			'union_source' => array(
				'users' => $alias,
			),
		);
		$lqu->addField($spec, $alias);
	}
	$query = $lqu->getSql();
	//pr2($query, null, true);return;
}
else {
	$queries = array();

	foreach ($tables as $table) {

		$type = ucfirst($table);
		$query = "SELECT ";
		foreach($std_fields as $f)
			$query .= "cts.$f, ";
		$acct_rel = $account_names[$table];
		$acct_join = '';
		if(! $acct_rel) $acct_rel = '""';
		else if(! is_array($acct_rel)) $acct_rel = "cts.$acct_rel";
		else {
			$acct_join = " LEFT JOIN {$acct_rel[0]} arel ON arel.{$acct_rel[1]}=cts.id AND NOT arel.deleted ";
			$acct_join .= " LEFT JOIN accounts acc ON acc.id=arel.account_id AND NOT acc.deleted ";
			$acct_rel = "acc.name";
		}
		$query .= "$acct_rel account_name, ";
		foreach ($address_map as $user_field => $field) {
			if ('users' == $table) {
				$field = $user_field;
			}
			$query .= 'cts.' . $field . ',';
		}
		$query = substr($query, 0, -1);
		$query .= ", plp.related_type FROM prospect_lists_prospects plp
		LEFT JOIN $table cts ON cts.id = plp.related_id  $acct_join
		WHERE plp.deleted=0 AND plp.related_type='$type' AND plp.prospect_list_id='$record'
		ORDER BY cts.last_name,
		cts.first_name";
		$queries[] = $query;
	}

	$query = join(' ) UNION ( ', $queries);
	if (count($queries) > 1) {
		$query = '(' . $query . ')';
	}
}

$result = $db->query($query,true,"Error exporting prospect list: $query");

$fields_array = $db->getFieldsArray($result);
$content = '';

$header = implode("\",\"",array_values($fields_array));
$header = "\"" .$header;
$header .= "\"\r\n";
$content .= $header;

$column_list = implode(",",array_values($fields_array));

while($val = $db->fetchByAssoc($result,-1,false))
{
	$new_arr = array();

	foreach (array_values($val) as $value)
	{
		array_push($new_arr, preg_replace("/\"/","\"\"",$value));
	}

	$line = implode("\",\"",$new_arr);
	$line = "\"" .$line;
	$line .= "\"\r\n";

	$content .= $line;
}

header("Pragma: cache");
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename={$_REQUEST['module']}.csv");
header("Content-transfer-encoding: binary");
header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header( "Cache-Control: post-check=0, pre-check=0", false );
if(! AppConfig::get_server_compression())
	header("Content-Length: ".strlen($content));
print $content;
sugar_cleanup(true);
?>
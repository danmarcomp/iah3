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

global $theme;
require_once('themes/'.$theme.'/layout_utils.php');
require_once 'XTemplate/xtpl.php';
require_once 'modules/CompanyAddress/CompanyAddress.php';

$addr = new CompanyAddress;

$service_ids = array(

	/*
	'PRIORITY_OVERNIGHT',
	'STANDARD_OVERNIGHT',
	'FEDEX_2_DAY',
	'FEDEX_EXPRESS_SAVER',
	'FIRST_OVERNIGHT',
	'GROUND_HOME_DELIVERY',
	'FEDEX_GROUND',
	 */

'PRIORITY_OVERNIGHT',
'STANDARD_OVERNIGHT',
'FIRST_OVERNIGHT',
'FEDEX_2_DAY',
'FEDEX_EXPRESS_SAVER',
'INTERNATIONAL_PRIORITY',
'INTERNATIONAL_ECONOMY',
'INTERNATIONAL_FIRST',
'FEDEX_1_DAY_FREIGHT',
'FEDEX_2_DAY_FREIGHT',
'FEDEX_3_DAY_FREIGHT',
'FEDEX_GROUND',
'GROUND_HOME_DELIVERY',
'INTERNATIONAL_PRIORITY_FREIGHT',
'INTERNATIONAL_ECONOMY_FREIGHT',
//'EUROPE_FIRST_INTERNATIONAL_PRIORITY',


);

$payors = array(
	'RECIPIENT' => 'Recipient',
	'SENDER' => 'Sender',
);

$service_types = array();
foreach ($service_ids as $sid) {
	$service_types[$sid] = ucwords(strtolower(str_replace('_', ' ', $sid)));
}


$company_options = $addr->get_warehouse_options(array_get_default($_POST, 'company_id', ''), true);
$address = $addr->getAddressArray(array_get_default($_POST, 'company_id', null));

$xtpl = new XTemplate('modules/Shipping/FedexPopup.html');

$map = array(
	's_company' => 'name',
	's_address1' => 'address_street',
	's_phone' => 'phone',
	's_city' => 'address_city',
	's_state' => 'address_state',
	's_country' => 'address_country',
	's_zip' => 'address_postalcode',
);

foreach ($map as $k => $v) {
	$xtpl->assign($k, array_get_default($_POST, $k, $address[$v]));
}


foreach ($_POST as $k => $v) {
	$xtpl->assign($k, $v);
}

$country = guess_country(array_get_default($_POST, 's_country', $address['address_country']));

$xtpl->assign('s_country', $country);


$errors = '';
if (!empty($_POST)) {
	$errors = createShipment();
}

$xtpl->assign('ERRORS', $errors);

$xtpl->assign('MOD', $mod_strings);

$xtpl->assign('TYPE_OPTIONS', get_select_options_with_id($service_types, @$_POST['service_type']));

$xtpl->assign('DUTY_PAYOR_OPTIONS', get_select_options_with_id($payors, array_get_default($_POST, 'duties_payor', 'RECIPIENT')));

$xtpl->assign('COMPANY_OPTIONS', $company_options);

$xtpl->assign('COUNTRIES', get_select_options_with_id(array(''=> $mod_strings['LBL_SELECT_COUNTRY']) + $app_list_strings['country_codes'], ''));

global $image_path;
$xtpl->assign('DELETE_IMG', addcslashes(get_image($image_path.'delete_inline', 'align="absmiddle"  border="0" onclick="remove_row((i))"'), "\"'"));


insert_popup_header($theme);

$xtpl->parse('main');
$xtpl->out('main');

$json = getJSONObj();

if (isset($_POST['com'])) {
	echo '<script type="text/javascript">';
	foreach ($_POST['com'] as $com) {
		echo 'add_row(' . $json->encode($com) , ');';
	}
	echo '</script>';
}

echo get_form_footer();
echo insert_popup_footer();

function addNS($type)
{
	static $namespace = 'http://fedex.com/ws/ship/v3';
	return '{' . $namespace . '}' . $type;
}

function createShipment()
{
	require_once('SOAP/Client.php');

	$namespace = 'http://fedex.com/ws/ship/v3';

	$params = array(
		'url' => 'https://gatewaybeta.fedex.com/web-services',
		'wsdl' => false,
		'http_proxy_host' => false,
		'http_proxy_port' => false,
		'http_proxy_user' => false,
		'http_proxy_password' => false,
		'soap_timeout' => 0,
		'soap_response_timeout' => 30,
	);

	extract($params);


	$client = new SOAP_Client($url, $wsdl);
	$client->setUse('literal');

	$request = array();

	$request['WebAuthenticationDetail'] = array(
		'UserCredential' => array(
			'Key' => 'WeLZvApWoFSRK2cf',
			'Password' => '7sBZOwq0wuyUOUM8XC7FWftXP'
		)
	); // Replace 'XXX' and 'YYY' with FedEx provided credentials 

	$request['ClientDetail'] = array(
		'AccountNumber' => '510087844',
		'MeterNumber' => '1226241'
	);// Replace 'XXX' with your account and meter number

	$request['TransactionDetail'] = array(
		'CustomerTransactionId' => '766JKHJK78JdfsDF;'
	);

	$request['Version'] = array(
		'ServiceId' => 'ship',
		'Major' => '3',
		'Intermediate' => '0',
		'Minor' => '0'
	);

	$request['RequestedShipment'] = array(
		'ShipTimestamp' => date('c'),
		'DropoffType' => 'REGULAR_PICKUP', // valid values REGULAR_PICKUP, REQUEST_COURIER, DROP_BOX, BUSINESS_SERVICE_CENTER and STATION
		'ServiceType' => $_POST['service_type'],
		'PackagingType' => 'YOUR_PACKAGING', // valid values FEDEX_BOK, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
		'TotalWeight' => array(
			'Units' => 'LB',
			'Value' => 50.0,
		), // valid values LB and KG
		'Shipper' => array(
			'Contact' => array(
				'PersonName' => $_POST['s_contact'],
				'CompanyName' => $_POST['s_company'],
				'PhoneNumber' => $_POST['s_phone'],
			),
			'Address' => array(
				new SOAP_Value(addNS('StreetLines'), false, $_POST['s_address1']),
				new SOAP_Value(addNS('StreetLines'), false, $_POST['s_address2']),
				'City' => $_POST['s_city'],
				'StateOrProvinceCode' => $_POST['s_state'],
				'PostalCode' => $_POST['s_zip'],
				'CountryCode' => $_POST['s_country'],
			)
		),
		'Recipient' => array(
			'Contact' => array(
				'PersonName' => $_POST['r_contact'],
				'CompanyName' => $_POST['r_company'],
				'PhoneNumber' => $_POST['r_phone'],
			),
			'Address' => array(
				new SOAP_Value(addNS('StreetLines'), false, $_POST['r_address1']),
				new SOAP_Value(addNS('StreetLines'), false, $_POST['r_address2']),
				'City' => $_POST['r_city'],
				'StateOrProvinceCode' => $_POST['r_state'],
				'PostalCode' => $_POST['r_zip'],
				'CountryCode' => $_POST['r_country'],
				'Residential' => !empty($_POST['r_residential']),
			),
		),
		'ShippingChargesPayment' => array(
			'PaymentType' => 'SENDER', // valid values RECIPIENT, SENDER and THIRD_PARTY
			'Payor' => array(
				'AccountNumber' => '510087844', // Replace 'XXX' with your account number
				'CountryCode' => $_POST['s_country']
			)
		),

		'SpecialServicesRequested' => array (
			'SpecialServiceTypes' => 'COD',
			'CodDetail' => array('CollectionType' => 'ANY'), // ANY, GUARANTEED_FUNDS
			'CodCollectionAmount' => array(
				'Currency' => 'USD',
				'Amount' => sprintf("%0.2f", $_POST['cod_amount']),
			)
		),
		
		'InternationalDetail' => array(
			'DutiesPayment' => array(
				'PaymentType' => $_POST['duties_payor'], // valid values RECIPIENT, SENDER and THIRD_PARTY

				'Payor' => array(
					'AccountNumber' => '510087844',
					'CountryCode' => 'US',
				),
			),
			'CustomsValue' => array(
				'Currency' => 'USD',
				'Amount' => sprintf("%0.2f", @$_POST['customs_value']),
			),
		),

		'LabelSpecification' => array(
			'LabelFormatType' => empty($_POST['create_labels']) ? 'LABEL_DATA_ONLY' : 'COMMON2D', // valid values COMMON2D, LABEL_DATA_ONLY
			'ImageType' => 'PDF'
		), // valid values DPL, EPL2, PDF, ZPLII and PNG

		'RateRequestTypes' => 'ACCOUNT', // valid values ACCOUNT and LIST
		'PackageCount' => 1,
		'RequestedPackages' => array(
			'Weight' => array(
				'Units' => 'LB',
				'Value' => 50.0,
			),

		),
	); // valid values LB and KG

	if (empty($_POST['cod'])) {
		unset($request['RequestedShipment']['SpecialServicesRequested']);
	}

	if (!empty($_POST['com'])) {
		foreach ($_POST['com'] as $com) {
			$request['RequestedShipment']['InternationalDetail'][] = 
				new SOAP_Value(addNS('Commodities'), false, array
				(
					'NumberOfPieces' => sprintf("%d", $com['num_pieces']),
					'Description' => $com['description'],
					'CountryOfManufacture' => $com['country'],
					'Weight' => array(
						'Units' => $com['weight_units'],
						'Units' => strtoupper($com['weight_units']) == 'KG' ? 'KG': 'LB',
						'Value' => sprintf("%0.1f", $com['weight']),
					),
					'Quantity' => sprintf("%d", $com['qty']),
					'QuantityUnits' => $com['qty_units'],
					'UnitPrice' => array(
						'Currency' => 'USD',
						'Amount' => sprintf('%0.6f', $com['unit_price']),
					),
					'CustomsValue' => array (
						'Currency' => 'USD',
						'Amount' => sprintf('%0.6f', $com['customs_value']),
					),
				)
			);
		}
	}

	$response = $client->call('ProcessShipmentRequest', $request, $namespace);  // FedEx web service invocation
	$errors = '';
	
	if (! ($response instanceof SOAP_Fault)) {
		if ($response["HighestSeverity"] == 'FAILURE' || $response["HighestSeverity"] == 'ERROR') {
			if (is_array($response["Notifications"])) {
				$err = $response["Notifications"];
			} else {
				$err = array($response["Notifications"]);
			}
			foreach ($err as $error) {
				$errors .= htmlspecialchars($error->Message) . "<br />\n";
			}
		}

		$fp = fopen('cod_label.pdf', 'wb');   
		fwrite($fp, base64_decode($response['CompletedShipmentDetail']->CodReturnDetail->Label->Parts->Image));
		fclose($fp);

		$fp = fopen('ship_label.pdf', 'wb');   
		fwrite($fp, base64_decode($response['CompletedShipmentDetail']->CompletedPackageDetails->Label->Parts->Image));
		fclose($fp);
	}
	return $errors;

}

function guess_country($name)
{
	global $app_list_strings;
	$countries = $app_list_strings['country_codes'];
	if (isset($countries[strtoupper($name)])) {
		return strtoupper($name);
	}
	foreach ($countries as $k => $v) {
		if (strtoupper($v) == strtoupper($name)) {
			return $k;
		}
	}
	return null;
}

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



class QuoteWithAddress extends QuotePDF
{
	var $addressY = 0;
	
	function initLayout(&$pdf) {
		$m = $pdf->getMargins();
		$pdf->SetTopMargin($m['left'] * 1.5);
	}

	function execute(&$pdf)
	{
		$pdf->print_logo();
		$this->printCompanyAddress($pdf);
		$pdf->print_details();
		$pdf->print_extra_head();
		$this->print_addresses($pdf);
		$pdf->print_main();
		$pdf->print_notes();
		$pdf->print_terms();
		$pdf->print_extra_foot();
	}

	function getAddressesPosition(&$pdf)
	{
		$m = $pdf->getMargins();
		return array(
			'x' => $m['left'], // * 2,
			'y' => max($pdf->getY(), $this->addressY) + $pdf->lv('28pt')
		);
	}
	
	function getLogoPosition(&$pdf)
	{
		$m = $pdf->getMargins();
		return array(
			'x' => 2*$m['left'],
			'y' => $m['top'],
			'w' => $pdf->lv('35%'),
		);
	}
	
	function print_addresses(&$pdf) {
		$pdf->print_addresses();
	}

	function printCompanyAddress(&$pdf)
	{
		global $app_strings;
		$logo = $pdf->get_company_logo_info();
		$info = $pdf->getLoadedImage($logo['path']);
		$pos = $this->getLogoPosition($pdf);
		$y = $pos['y'] + $pos['w'] / $pdf->lv($info['w'].'px') * $pdf->lv($info['h'].'px') + 10;
		//var_dump($w, $h, $pos, $y); exit;
		$pdf->setXY($pos['x'], $y);
		$settings = $pdf->getCompanyAddress();
		$address_arr = array(
				preg_replace('/\r\n|\n/', ' ', $settings['name']),
				$settings['address_street'],
				$settings['address_city']
					. ', '. $settings['address_state']
					. ' '. $settings['address_postalcode'] .
					', ' .$settings['address_country']
				);
		$phones = array();
		if (!empty($settings['phone'])) {
			$phones[] = $app_strings['LBL_PDF_PHONE'] . ': ' . $settings['phone'];
		}
		if (!empty($settings['fax'])) {
			$phones[] = $app_strings['LBL_PDF_FAX'] . ': ' . $settings['fax'];
		}
		$cols = array('text' => array('width' => '290pt'));
		if (!empty($phones)) $address_arr[] = implode(" • ", $phones);
		$data = array(array('text' => implode("\n", $address_arr)));
		$opts = array(
			'border' => 0,
			'padding' => 1,
			'font-size' => 11,
		);
		$pdf->DrawTable($data, $cols, '', false, $opts);

		$this->addressY = $pdf->getY();
	}

	function hasHeader() { return false; }
}


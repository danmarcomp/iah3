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


/** eggsurplus: 2008/01/31 QuoteForEnvelope */
class QuoteForEnvelope extends QuotePDF
{
	var $addressY = 0;
	var $billAddressPos;
	
	function initLayout(&$pdf) {
		$m = $pdf->getMargins();
		$pdf->SetTopMargin($m['left'] * 1.5);
		$this->billAddressPos = AppConfig::setting('company.envelope_window_position');
	}

	function execute(&$pdf)
	{
		$pdf->print_logo();
		$this->printCompanyAddress($pdf); 
		$this->print_details($pdf);
		$this->print_addresses($pdf); 
		$pdf->print_extra_head(); 
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
			'y' => max($pdf->getY(), $this->addressY) + $pdf->lv('14pt')
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
	
	function getDetailsPosition(&$pdf) {
		return array('x' => $pdf->lv('4.9in'), 'y' => $pdf->lv('0.45in'));
	}

	function printCompanyAddress(&$pdf)
	{
		global $app_strings;
		$logo = $pdf->get_company_logo_info();
		$info = $pdf->getLoadedImage($logo['path']);
		$pos = $this->getLogoPosition($pdf);
		$y = $pos['y'] + $pos['w'] / $pdf->lv($info['w'].'px') * $pdf->lv($info['h'].'px') + 10;
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

	function print_addresses(&$pdf) {
		$addrs = $pdf->get_addresses();
		
		if(! $addrs)
			return;
		$strings =& $pdf->mod_strings();
		$cols = array();
		$data = array();
		$w = min(35, 100 / count($addrs)).'%';
		
		$addr_idx = isset($addrs['LBL_PDF_BILL_TO']) ? 'LBL_PDF_BILL_TO' : key($addrs);

		$cols = array('text' => array('width' => '4.5in'));
		$data = array(array('text' => implode("\n", $addrs[$addr_idx])));
		
		$pos = $pdf->layout->getAddressesPosition($pdf);
		if($this->billAddressPos == 'left')
			$xpos = $pdf->lv('.8in');
		else
			$xpos = $pdf->lv('-4.5in');
		$pdf->setXY($xpos, $pdf->lv('2.3in'));
		$head = array('font-size' => 11);
		$body = array('border' => 0, 'font-size' => 10, 'font-face' => $this->get_monospace_font_face());
		$pdf->DrawTable($data, $cols, '', false, $body);
		
		if($pdf->layout->showShipping() && isset($addrs['LBL_PDF_SHIP_TO']) && $addr_idx != 'LBL_PDF_SHIP_TO') {
			$cols = array('ship' => array('title' => $strings['LBL_PDF_SHIP_TO'], 'width' => '3in'));
			$data = array();
			foreach($addrs['LBL_PDF_SHIP_TO'] as $line)
				$data[]['ship'] = $line;
			if($this->billAddressPos == 'left')
				$xpos = $pdf->lv('4.9in');
			else
				$xpos = $pos['x'] + $pdf->lv('.1in');
			$pdf->setXY($xpos, $pos['y']);
			$head = array('font-size' => 11);
			$body = array('border' => 0, 'font-size' => 10);
			$pdf->DrawTable($data, $cols, '', $pdf->layout->showAddressTitle() ? $head : false, $body);
		}

		$pdf->setY($pdf->getY() + $pdf->lv('.8in'));
		$pdf->ruleLine(array('color' => array(200,200,200), 'height' => '1pt'));
	}

	function print_details($pdf) {
		$pos = $pdf->layout->getDetailsPosition($pdf);
		if (empty($pos)) {
			return;
		}
		$pdf->setXY($pos['x'], $pos['y']);
		
		$title = $pdf->get_detail_title();
		$data = $pdf->get_detail_fields();
		$data2 = array();
		$s =& $pdf->mod_strings();
		foreach($data as $k=>$v)
			$data2[] = array('title' => $s[$k].':', 'value' => $v);
		$cols = array(
			'title' => array('width' => '100pt', 'nowrap' => true),
			'value' => array('width' => '160pt'),
		);
		$opts = array(
			'border' => 0,
			'padding' => 1,
			'font-size' => 10,
		);
		if($data2)
			$pdf->DrawTable($data2, $cols, $title, false, $opts);
	}
	
	function hasHeader() { return false; }
}


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


require_once('modules/Accounts/Account.php');
require_once('modules/Contacts/Contact.php');
require_once('modules/TaxRates/TaxRate.php');
require_once('modules/Discounts/Discount.php');
require_once('modules/Quotes/Quote.php');
require_once('include/pdf/PDFManager.php');
require_once 'include/layout/FieldFormatter.php';

class QuotePDF extends PDFManager {
	var $focus;
	var $focus_type = 'Quote';

	var $printTotals = true;
	var $printSerials = false;
	var $printPretax = false;

	var $layout, $layoutName;
	var $pdf_type;

	static $taxedShipping;

	static $shipping;

	function QuotePDF() {
		if (isset($_REQUEST['use_address'])) $this->useCompanyAddress($_REQUEST['use_address']);
		parent::PDFManager();
		if(empty($_REQUEST['attach_to_email']) || $_REQUEST['attach_to_email'] == 'false') {
			$layout = AppConfig::setting('company.print_statement_layout', 'QuoteForEnvelope');
		} else {
			$layout = AppConfig::setting('company.email_statement_layout', 'QuotePDF');
		}
		$layout = isset($_REQUEST['use_layout']) ? $_REQUEST['use_layout'] : $layout;
		$this->printPretax = !! AppConfig::setting('company.show_pretax_totals');
		$this->setLayout($layout);
		self::$taxedShipping = 0;
		self::$shipping = 0;
		if(! empty($_REQUEST['pdf_type']))
			$this->pdf_type = $_REQUEST['pdf_type'];
	}
	
	function system_layout() {
		return AppConfig::setting('company.quote_layout', 'QuotePDF');
	}

	function setLayout($layoutName)
	{
		$layoutName = preg_replace('/[^a-z_]/i', '', $layoutName);
		if (strtolower(get_class($this)) == strtolower($layoutName)) {
			$this->layout =& $this;
		} elseif (file_exists('modules/Quotes/' . $layoutName . '.php')) {
			require_once 'modules/Quotes/' . $layoutName . '.php';
			$this->layout = new $layoutName;
		}
		$this->layout_name = $layoutName;
		$this->layout->initLayout($this);
	}
	
	function initLayout(&$pdf) {
	}

	function &mod_strings() {
		static $strings;
		$lang_module = $this->focus->getModuleDir();
		if (!empty($this->alt_lang_module))
			$lang_module = $this->alt_lang_module;
		if(! isset($strings))
			$strings = return_module_language($this->use_language, $lang_module);
		return $strings;
	}
	
	function create_filename() {
		$mod_strings =& $this->mod_strings();
		$filename_pfx = $this->get_detail_title();
		if(! empty($mod_strings['LBL_FILENAME_PROPOSAL']))
			$filename_pfx = $mod_strings['LBL_FILENAME_PROPOSAL'];
		$filename = $filename_pfx . '_' . $this->row['prefix'];
		$filename .= $this->row['quote_number'] . '_' . $this->row['billing_account_name'];
		$filename = $filename . '.pdf';
		return $filename;
	}

	function getLogoPosition(&$pdf)
	{
		$voffs = $pdf->lv('40pt');
		$m = $pdf->getMargins();
		return array(
			'x' => 2*$m['left'],
			'y' => $m['header']+$voffs,
			'w' => $pdf->lv('35%'),
		);
	}

	function print_logo() {
		$logo = $this->get_company_logo_info();
		$pos = $this->layout->getLogoPosition($this);
		if (!empty($pos)) {
			$this->Image($logo['path'], $pos['x'], $pos['y'], $pos['w']);
		}
	}
	
	function get_detail_title() {
		$s =& $this->mod_strings();
		if($this->pdf_type == 'proforma')
			return $s['LBL_PDF_PROFORMA_TITLE'];
		return $s['LBL_PDF_PROPOSAL'];
	}
	
	function get_detail_fields() {
		global $timedate;
		$row =& $this->row;
		$user = new User();
		$user->retrieve($row['assigned_user_id'], false);
		$number_label = ($this->pdf_type == 'proforma') ? 'LBL_PDF_PROFORMA_NUMBER' : 'LBL_PDF_QUOTE_NUMBER';
		$terms = AppConfig::setting("lang.lists.{$this->use_language}.app.terms_dom", array());
		$fields = array(
			$number_label => $row['prefix'] . $row['quote_number'],
			'LBL_PDF_TERMS' => array_get_default($terms, $row['terms'], $row['terms']),
			'LBL_PDF_DATE' => $timedate->to_display_date($row['date_entered']),
			'LBL_PDF_SALES_PERSON' => $row['assigned_user_name'],
			'LBL_PDF_VALID_UNTIL' => $timedate->to_display_date($row['valid_until'], false),
		);
		$tax_info = $this->_company_tax_info();
		if(! empty($tax_info))
			$fields['LBL_PDF_TAX_INFO'] = $tax_info;
		return $fields;
	}

	function getDetailsPosition(&$pdf)
	{
		$voffs = $pdf->lv('40pt');
		$m = $pdf->getMargins();
		return array(
			'x' => $pdf->lv('-280pt'),
			'y' => $m['header'] + $voffs,
		);
	}

	function print_details() {
		$pos = $this->layout->getDetailsPosition($this);
		if (empty($pos)) {
			return;
		}
		$this->setXY($pos['x'], $pos['y']);
		$title = $this->get_detail_title();
		$data = $this->get_detail_fields();
		$data2 = array();
		$s =& $this->mod_strings();
		foreach($data as $k=>$v)
			$data2[] = array('title' => $s[$k].':', 'value' => $v);
		$cols = array(
			'title' => array('width' => '140pt', 'nowrap' => true),
			'value' => array('width' => '120pt'),
		);
		$opts = array(
			'border' => 0,
			'padding' => 1,
			'font-size' => 10,
		);
		if($data2)
			$this->DrawTable($data2, $cols, $title, false, $opts);
	}
	
	function print_extra_head() {
	}
	
	function _company_tax_info() {
		$tax_info = AppConfig::setting('company.tax_information', '');
		return from_html($tax_info);
	}
	
	function _address($pfx, $altfocus=null) {		
		if($altfocus)
			$focus =& $altfocus;
		else
			$focus =& $this->focus;
			
		$fid = "{$pfx}contact_id";
		$fnm = "{$pfx}contact_name";
		if(strlen($focus->getField($fid)) && !strlen($focus->getField($fnm) == 0)) {
			if( ($rec = ListQuery::quick_fetch('Contact', $focus->getField($fid), array('_display', 'salutation'))) ) {
				$focus->assign($fnm, $rec->getField('_display'));
				$focus->assign($pfx.'contact_salutation', $rec->getField('salutation'));
			}
		}
		$fid = "{$pfx}account_id";
		$fnm = "{$pfx}account_name";
		if(strlen($focus->getField($fid)) && !strlen($focus->getField($fnm) == 0)) {
			if( ($rec = ListQuery::quick_fetch('Account', $focus->getField($fid), array('_display'))) ) {
				$focus->assign($fnm, $rec->getField('_display'));
			}
		}
		
		global $locale;
		return explode("\n", $locale->getLocaleBeanFormattedAddress($focus, $pfx, false));

		
		$fs = array('street', 'city', 'state', 'postalcode', 'country');
		$a = array();
		foreach($fs as $f) {
			$fn = "{$pfx}address_{$f}";
			$a[$f] = '';
			$val = $focus->getField($fn);
			if(isset($val))
				$a[$f] = $val;
		}
		$addr = array();
		$fcn = "{$pfx}contact_name";
		$val = $focus->getField($fcn);
		if(! empty($val) && trim($val))
			$addr[] = $val;
		$fan = "{$pfx}account_name";
		$val = $focus->getField($fan);
		if(! empty($val) && trim($val))
			$addr[] = $val;
		if(strlen($a['street']))
			$addr[] = $a['street'];
		$line2 = $a['city'];
		if(strlen($a['state'])) {
			if($line2) $line2 .= ', ';
			$line2 .= $a['state'];
		}
		if(strlen($a['postalcode'])) {
			if($line2) $line2 .= ' ';
			$line2 .= $a['postalcode'];
		}
		if($line2)
			$addr[] = $line2;
		if(strlen($a['country']))
			$addr[] = $a['country'];
		return $addr;
	}
	
	function get_addresses() {
		$bill = $this->_address('billing_');
		$tax_information = $this->focus->getField('tax_information');
		if(! empty($tax_information))
			$bill[] = $tax_information;
		$ship = $this->_address('shipping_');
		return array(
			'LBL_PDF_BILL_TO' => $bill,
			'LBL_PDF_SHIP_TO' => $ship,
		);
	}
	
	function getAddressesPosition(&$pdf)
	{
		$m = $pdf->getMargins();
		return array(
			'x' => $m['left'],
			'y' => $pdf->getY() + $pdf->lv('14pt')
		);
	}

	function showShipping() { return true; }
	function showAddressTitle() { return true; }

	function print_addresses() {
		$addrs = $this->get_addresses();
		if(! $addrs)
			return;
		if (!$this->layout->showShipping() && isset($addrs['LBL_PDF_SHIP_TO'])) {
			unset($addrs['LBL_PDF_SHIP_TO']);
		}
		$strings =& $this->mod_strings();
		$cols = array();
		$data = array();
		$w = min(35, 100 / count($addrs)).'%';
		foreach($addrs as $title => $lines) {
			$cols[$title] = array('title' => $strings[$title], 'width' => $w);
			$i = 0;
			foreach($lines as $l)
				$data[$i++][$title] = $l;
		}
		$pos = $this->layout->getAddressesPosition($this);
		$this->setXY($pos['x'], $pos['y']);
		$head = array('font-size' => 11);
		$body = array('border' => 0, 'font-size' => 10);
		$this->DrawTable($data, $cols, '', $this->layout->showAddressTitle() ? $head : false, $body);
	}
	
	function get_main_columns($group = null) {
		$focus =& $this->focus;
		$mod_strings =& $this->mod_strings();
		if ($group && array_get_default($group, 'group_type') == 'service') {
			$product_label = $mod_strings['LBL_PDF_SERVICE'];
		} else {
			$product_label = $mod_strings['LBL_PDF_PRODUCT'];
		}
		$cols = array(
			'number' => array(
				'title' => $mod_strings['LBL_PDF_INDEX'],
				'width' => '4%',
			),
			'number2' => array(
				'width' => '4%',
			),
			'quantity' => array(
				'title' => $mod_strings['LBL_PDF_QUANTITY'],
				'width' => '6%',
			),
			'product' => array(
				'title' => $product_label,
				'width' => '44%',
			),
			'listprice' => array(
				'title' => $mod_strings['LBL_PDF_LISTPRICE'],
				'width' => '14%',
				'text-align' => 'right',
			),
			'unitprice' => array(
				'title' => $mod_strings['LBL_PDF_UNITPRICE'],
				'width' => '14%',
				'text-align' => 'right',
			),
			'extprice' => array(
				'title' => $mod_strings['LBL_PDF_EXTPRICE'],
				'width' => '14%',
				'text-align' => 'right',
			),
		);

		if(!$focus->getField('show_list_prices')) {
			$cols['product']['width'] = '58%';
			unset($cols['listprice']);
		}
		
		return $cols;
	}
	
	function get_group_lines(&$grp) {
    	$mod_strings =& $this->mod_strings();
		$focus =& $this->focus;
		$currency =& $this->get_currency();
		$symbol = $currency->symbol;
		$data = array();
		$num = 1;
		$attribs_by_line = array();
		if(! empty($grp['adjusts'])) {
			foreach ($grp['adjusts'] as $adj) {
				if(! empty($adj['line_id'])) {
					if($adj['type'] == 'ProductAttributes')
						$attribs_by_line[$adj['line_id']][] = $adj;
					continue;
				}
			}
		}
		if($grp['lines']) foreach($grp['lines'] as $row) {
			$attrib_amount = 0.0;
			if(! empty($attribs_by_line[$row['id']])) {
				foreach($attribs_by_line[$row['id']] as $adj) {
					if($adj['amount']) {
						$attrib_amount += $adj['amount'];
					}
				}
			}
			if(! empty($row['is_comment'])) {
				$data[] = array(
					'product' => $row['body'],
				);
			}
			else {
				$datarow = array(
					'depth' => $row['depth'],
					'quantity' => format_number($row['quantity'], -1),
					'product' => $row['name'],
					'mfr_part_no' => $row['mfr_part_no'],
				);
				if (!empty($row['description'])) {
					$datarow['product'] .= ' : ' . $row['description'];
				}
				if($row['depth'] && ! $focus->getField('show_components'))
					continue;
				if(! $row['depth'] || $focus->getField('show_components') != 'names') {
					if (isset($row['list_price'])) {
						$datarow['listprice'] = $this->currency_format($row['list_price'], $currency->id, true);
					}
					$unitprice = $row['unit_price'] - $attrib_amount;
					$datarow['unitprice'] = $this->currency_format($unitprice, $currency->id, true);
					$ext_price = $row['ext_price'] - $row['ext_quantity'] * $attrib_amount;
					$datarow['extprice'] = $this->currency_format($ext_price, $currency->id);
				}
				if($row['depth'])
					$datarow['number2'] = $component_num ++;
				else {
					$datarow['number'] = $num ++;
					$component_num = 1;
				}
				if (isset($row['serial_numbers'])) {
					$datarow['serial_numbers'] = $row['serial_numbers'];
				}
				$data[] = $datarow;

				if(! empty($attribs_by_line[$row['id']])) {
					foreach($attribs_by_line[$row['id']] as $adj) {
						if (1 || $adj['amount']) {
							$datarow = array(
								'product' => '    ' . $adj['name'],
								'unitprice' => $this->currency_format($adj['amount'], $currency->id, true),
								'extprice' => $this->currency_format($adj['amount'] * $row['ext_quantity'], $currency->id),
							);
							$data[] = $datarow;
						}
					}
				}
			}
		}
		return $data;
	}
	
    function get_totals_columns($final=false) {
        $cols = array (
            'info_name' => array(
                'width' => '43%',
				'text-align' => 'right',
            ),
            'info_value' => array(
                'width' => '18%',
            ),
            'name' => array(
                'width' => '25%',
                'text-align' => 'right',
            ),
            'value' => array(
                'width' => '14%',
                'text-align' => 'right',
            ),
        );
        if(! $final)
        	$cols['info_value']['font-weight'] = 'bold';
        return $cols;
    }
    
    function get_group_totals(&$grp, $final=false) {
    	$this->printPretax = true;
    	$mod_strings =& $this->mod_strings();
    	$currency =& $this->get_currency();
    	$shipping_provider =& $this->get_shipping_provider();
    	$discounts = array();
    	$taxes = array();
    	$sep = translate('LBL_SEPARATOR', 'app');
    	
    	$shipping_row = array(
			'name' => $mod_strings['LBL_SHIPPING'] . $sep,
			'value' => '',
		);

		if($final) {
			$shipping_text = $mod_strings['LBL_SHIPPING'];
			$taxed_shipping_row = array(
				'name' => $shipping_text ." (".$mod_strings['LBL_SHIPPING_IS_TAXED'].")" . $sep,
				'value' => '',
			);						
			if (self::$shipping > 0 || self::$taxedShipping == 0) {
				$shipping_row['info_name'] = $mod_strings['LBL_SHIPPING_PROVIDER'] . $sep;
				$shipping_row['info_value'] = $shipping_provider->name;
			} else {
				$taxed_shipping_row['info_name'] = $mod_strings['LBL_SHIPPING_PROVIDER'] . $sep;
				$taxed_shipping_row['info_value'] = $shipping_provider->name;				
			}	
		}
		
		$pretax = $grp['subtotal'];
    	$shipping_taxed = false;

    	if($grp['id'] == 'GRANDTOTAL') {
    		if($grp['discount'] != 0) {
				$discounts[] = array(
					'info_name' => '',
					'info_value' => '',
					'name' => $mod_strings['LBL_DISCOUNT'] . $sep,
					'value' => $this->currency_format(-$grp['discount'], $currency->id),
				);
				$pretax -= $grp['discount'];
			}
			if($grp['tax'] != 0)
				$taxes[] = array(
					'info_name' => '',
					'info_value' => '',
					'name' => $mod_strings['LBL_TAX'] . $sep,
					'value' => $this->currency_format($grp['tax'], $currency->id),
				);
			if($grp['shipping'] != 0)
				$shipping_row['value'] = $this->currency_format($grp['shipping'], $currency->id);
			$shipping_taxed = $grp['shipping_taxed'];
			if($shipping_taxed || self::$taxedShipping > 0) {
				if (self::$taxedShipping <= 0) {
					$pretax += $grp['shipping'];
				} else {
					$pretax += self::$taxedShipping;					
				}					
			}
    	}
    	else if(! empty($grp['adjusts'])) {
			foreach($grp['adjusts'] as $idx => $adj) {
				if (!empty($adj['line_id'])) {
					if ($adj['type'] != 'StandardTax' && $adj['type'] != 'CompoundedTax')
						continue;
				}
				$row = array();
				$row['name'] = $adj['name'];
				if($adj['type'] != 'StdFixedDiscount')
					$row['name'] .= ' (' . format_number($adj['rate'], -1) . '%)';
				if($adj['type'] == 'StandardDiscount' || $adj['type'] == 'StdPercentDiscount' || $adj['type'] == 'StdFixedDiscount') {
					$pretax -= $adj['amount'];
					$adj['amount'] = -$adj['amount'];
				}
				$row['name'] .= $sep;
				$val = $this->currency_format($adj['amount'], $currency->id);
				if($adj['type'] == 'StandardDiscount' || $adj['type'] == 'StdPercentDiscount' || $adj['type'] == 'StdFixedDiscount') {
					$row['value'] = $val;
					$discounts[] = $row;
				}
				else if($adj['type'] == 'StandardTax' || $adj['type'] == 'CompoundedTax') {
					$row['value'] = $val;
					$taxes[] = $row;
				}
				else if($adj['type'] == 'TaxedShipping' || $adj['type'] == 'UntaxedShipping') {
					if($adj['amount'] != 0)
						$shipping_row['value'] = $val;
					$shipping_taxed = ($adj['type'] == 'TaxedShipping');
					if($shipping_taxed) {
						$pretax += $adj['amount'];
						self::$taxedShipping += $adj['amount'];
					} else {
						self::$shipping += $adj['amount'];
					}	
				}
			}
		}
		$data = array();
		$subt = array(
			'info_name' => '',
			'info_value' => $mod_strings['LBL_GROUP_TOTALS'],
			'name' => $mod_strings['LBL_SUBTOTAL'] . $sep,
			'value' => $this->currency_format($grp['subtotal'], $currency->id),
		);
		if($final) {
			$subt['info_name'] = $mod_strings['LBL_CURRENCY'] . $sep;
			$subt['info_value'] = $currency->name;
		}
		$data[] = $subt;
		$data = array_merge($data, $discounts);
		$grp['shipping_taxed'] = $shipping_taxed; // used by caller
		

    	if($grp['id'] == 'GRANDTOTAL') {		
			if($shipping_taxed || self::$taxedShipping > 0) {
				if (self::$taxedShipping > 0) {
					$taxed_shipping_row['value'] = $this->currency_format(self::$taxedShipping, $currency->id);
					$data[] = $taxed_shipping_row;						
				}				
			}
    	} else {
			if($shipping_row['value'] && $shipping_taxed)
				$data[] = $shipping_row;				
		}	
		
		if($this->printPretax && count($taxes)) {
			$data[] = array(
				'info_name' => '',
				'info_value' => '',
				'name' => $mod_strings['LBL_PRETAX_TOTAL'] . $sep,
				'value' => $this->currency_format($pretax, $currency->id),
			);
		}
		$data = array_merge($data, $taxes);

    	if($grp['id'] == 'GRANDTOTAL') {				
			if(!$shipping_taxed || self::$shipping > 0) {
				if (self::$shipping > 0) {
					$shipping_row['value'] = $this->currency_format(self::$shipping, $currency->id);					
					$data[] = $shipping_row;					
				}	
			}
    	} else {
			if($shipping_row['value'] && !$shipping_taxed)
				$data[] = $shipping_row;				
		}	
			
		$data[] = array(
			'info_name' => '',
			'info_value' => '',
			'name' => $mod_strings['LBL_TOTAL'] . $sep,
			'value' => $this->currency_format($grp['total'], $currency->id),
		);

		return $data;
    }
    
    function &get_currency() {
    	static $currency;
    	if(! isset($currency)) {
			require_once('modules/Currencies/Currency.php');
			$currency = new Currency();
			$currency->retrieve($this->focus->getField('currency_id'), false);
		}
		return $currency;
    }
    
    function &get_shipping_provider() {
    	static $shipper;
    	if(! isset($shipper)) {
			require_once('modules/ShippingProviders/ShippingProvider.php');
			$shipper = new ShippingProvider();
			$shipper->retrieve($this->focus->getField('shipping_provider_id'), false);
		}
		return $shipper;
    }
    
	function print_main() {
		$mod_strings =& $this->mod_strings();
		require_once('include/Tally/TallyUpdate.php');
		$tally = TallyUpdate::for_result($this->focus);
		$groups = $tally->getGroups();
		$gtotal = $tally->getTotals();
		
		$this->setXY($this->lMargin, $this->getY() + $this->lv('10pt'));
		
		$total_cols = $this->get_totals_columns();
		$show_totals = $this->showTotals();
		$taxed_shipping = 0;
		$shipping = 0;
		
		foreach($groups as $idx => $grp) {
			$main_cols = $this->get_main_columns($grp);
			$title = $grp['name'];
			$lines = $this->get_group_lines($grp);
			$header = true;
			$opts = array(
				'border' => 0,
				'alt-background-color' => array(236,236,236),
			);
			$serial_opts = $opts;
			$serial_opts['font-style'] = 'italic';
			$empty = array();
			//$this->DrawTable($lines, $main_cols, $title, $header, $opts);
			$this->DrawTable($empty, $main_cols, $title, $header, $opts);
			foreach ($lines as $ln) {
				$line = array($ln);
				$this->DrawTable($line, $main_cols, '', null, $opts);
				if (isset($ln['serial_numbers']) && trim($ln['serial_numbers']) !== '') {
					$serials = preg_replace('/[\r\n]+/', ', ', trim($ln['serial_numbers']));
					foreach($line[0] as $k => $v) {
						$line[0][$k] = '';
					}
					$line[0]['product'] = $mod_strings['LBL_PDF_SERIALS'] . ' ' . $serials;
					$this->DrawTable($line, $main_cols, '', null, $serial_opts);
				}
			}
			//$this->DrawTable($lines, $main_cols, $title, $header, $opts);
			
			if($show_totals && count($groups) > 1) {
				$this->ruleLine(array('color' => array(100,100,100), 'height' => '1pt'));
				$totals = $this->get_group_totals($grp);
				$header = false;
				$opts = array('border' => 0);
				$this->DrawTable($totals, $total_cols, '', false, $opts);
			}
		}
		
		if($show_totals) {
			$total_cols = $this->get_totals_columns(true);
			$last = $gtotal;
			$last['id'] = 'GRANDTOTAL';
			if(count($groups) == 1)
				$last = $groups[$idx];
			if(count($groups))
				$this->ruleLine(array('color' => array(100,100,100), 'height' => '1.5pt'));
			$totals = $this->get_group_totals($last, true);
			$header = false;
			$opts = array('border' => 0, 'font-size' => 10);
			$title = $this->get_grand_totals_title();
			$this->DrawTable($totals, $total_cols, $title, false, $opts);
		}
	}
	
	function get_grand_totals_title() {
		$mod_strings =& $this->mod_strings();
		if($this->pdf_type == 'proforma')
			return $mod_strings['LBL_PDF_PROFORMA_TOTALS'];
		return $mod_strings['LBL_GRAND_TOTALS'];
	}
	
	function print_notes() {
		$mod_strings =& $this->mod_strings();
		$description = $this->focus->getField('description');
		if(strlen($description)) {
			$cols = array('text' => array('width' => '100%'));
			$data = array(array('text' => $description));
			$opts = array('border' => 0);
			$title = array('text' => $mod_strings['LBL_PDF_NOTES'], 'text-align' => 'L', 'font-size' => 12);
			$this->setX($this->lMargin);
			$this->DrawTable($data, $cols, $title, false, $opts);
		}
	}
	
	function get_terms() {
		$terms = from_html(trim(AppConfig::setting('company.std_quote_terms', '')));
		return $terms;
	}
	
	function print_terms() {
		$terms = $this->get_terms();
		if(! empty($terms)) {
			$this->moveY('10pt');
			$this->setFontSize(8);
			$this->Write($this->FontSize, $terms);
		}
	}
    
	function print_extra_foot() {
		if($this->pdf_type == 'proforma')
			return;

		$this->setFontSize(10);
		$this->moveY('20pt');
		$mod_strings =& $this->mod_strings();
		$cols = array('label' => array('width' => '80pt', 'nowrap' => true), 'line' => array('width' => '170pt', 'nowrap' => true));
		$title = array('text' => $mod_strings['LBL_PDF_ACCEPT_TITLE'], 'text-align' => 'L', 'font-size' => 12);
		$line = '__________________________';

		if ($this->focus->getField('budgetary_quote'))
		{
			$title = array('text' => $mod_strings['LBL_BUDGETARY_QUOTE_TEXT'], 'text-align' => 'L', 'font-size' => 12);
			$data = array();
			$params = array('border' => 0, 'width' => '260pt', 'allow-page-span' => false);
		}
		else
		{
			$data = array(
				array('label' => $mod_strings['LBL_QUOTE_NUMBER'], 'line' => $this->focus->getField('prefix') . $this->focus->getField('quote_number')),
				array('label' => $mod_strings['LBL_PDF_ACCEPT_PRINT_NAME'], 'line' => $line),
				array('label' => $mod_strings['LBL_PDF_ACCEPT_EMPLOYEE_TITLE'], 'line' => $line),
				array('label' => $mod_strings['LBL_PDF_ACCEPT_SIGNATURE'], 'line' => $line),
				array('label' => $mod_strings['LBL_PDF_ACCEPT_DATE'], 'line' => $line),
			);
			$params = array('border' => 0, 'width' => '200pt', 'allow-page-span' => false);
		}
		
		$this->DrawTable($data, $cols, $title, false, $params);
	}

	function set_focus(&$focus)
	{
        $bean = $focus;
        if (get_class($focus) != 'RowResult')
            $bean = ListQuery::quick_fetch($this->focus_type, $focus->id);

		$fmt = new FieldFormatter('pdf');
		$fmt->formatRowResult($bean);
		$this->focus =& $bean;
		$this->row = $bean->row;
		$this->frow = $bean->formatted;
	}

	function do_print(&$focus) {
		$this->set_focus($focus);
		$this->filename = filename_safe_string($this->create_filename());
		$this->new_page();
		$this->layout->execute($this);
	}

	function execute(&$pdf)
	{
		$pdf->print_logo();
		$pdf->print_details();
		$pdf->print_extra_head();
		$pdf->print_addresses();
		$pdf->print_main();
		$pdf->print_notes();
		$pdf->print_terms();
		$pdf->print_extra_foot();
	}
	
	function get_config_title() {
		if($this->pdf_type == 'proforma')
			return translate('LBL_PDF_PROFORMA');
		return translate('LBL_PDF_QUOTE');
	}
	
	function get_config_fields() {
		$fs = parent::get_config_fields();
		$fs['use_layout'] = array(
			'vname' => 'LBL_USE_LAYOUT',
			'vname_module' => 'app',
			'type' => 'enum',
			'options' => self::getAvailableLayouts(),
			'default' => self::system_layout(),
			'required' => true,
			'width' => 45,
			'editable' => true,
		);
		return $fs;
	}

	function createListQuery($id)
	{
		$lq = new ListQuery($this->focus_type, true);
		$lq->addFilterPrimaryKey($id);
		$lq->addField('shipping_account.name', 'shipping_account_name');
		$lq->addField('billing_account.name', 'billing_account_name');
		$lq->addField('account.name', 'account_name');
		$lq->addField('billing_contact.name', 'billing_contact_name');
		$lq->addField('billing_contact.salutation', 'billing_contact_salutation');
		$lq->addField('shipping_contact.name', 'shipping_contact_name');
		$lq->addField('shipping_contact.salutation', 'shipping_contact_salutation');
        $lq->addField('assigned_user.name', 'assigned_user_name');
        return $lq;
	}

	function handle_request($attach_note=true) {
		if(! empty($_REQUEST['config'])) {
			$this->render_config();
			return;
		}
		$lq = $this->createListQuery(array_get_default($_REQUEST, 'record'));
		$focus = $lq->runQuerySingle();
		if(!$focus) {
			sugar_die("Record ID missing or unknown");
		}
		if (!ACLController::checkAccess($focus->getModuleDir(), 'view', AppConfig::current_user_id())) {
			ACLController::displayNoAccess(true);
			sugar_cleanup(true);
		}
		$this->do_print($focus);
		if($attach_note)
			$this->create_note();
		else
			$this->serve_dynamic(true);
	}
	
	function get_note_title() {
		$s =& $this->mod_strings();
		return $s['LBL_PREPARED_QUOTE'];
	}

	function get_email_title() {
		$s =& $this->mod_strings();
		return $s['LBL_EMAILED_QUOTE'];
	}
	
	function create_note() {
		global $current_user;
		$focus =& $this->focus;
		$module = $focus->getModuleDir();
		$content = $this->get_output();
		
		require_once('modules/Notes/Note.php');
		$note = new Note();
		$note->name = $this->get_note_title();
		require_once('include/upload_file.php');
		$upload_file = new UploadFile();
		$upload_file->set_for_soap($this->filename, $content);
		$note->filename = $upload_file->create_stored_filename();
		$note->assigned_user_id = $current_user->id;
		$note->parent_type = $module;
		$note->parent_id = $focus->getField('id');
		if (isset($focus->billing_contact_id)) {
			$note->contact_id = $focus->getField('billing_contact_id');
		} elseif ($focus->getField('supplier_contact_id')) {
			$note->contact_id = $focus->getField('supplier_contact_id');
		}
		$note->file_mime_type = 'application/pdf';
		$note->save();
		$upload_file->final_move($note->id);
		
		if(empty($_REQUEST['attach_to_email']) || $_REQUEST['attach_to_email'] == 'false') {
			//$path = $upload_file->get_url($note->filename, $note->id);
			$path = 'download.php?type=Notes&id=' . $note->id;
			header('Location: ' . $path);
		}
		else {
			$rel_mod = $module;
			$rel_id = $focus->getField('id');
			$rel_name = $focus->getField('name');
			$cbillto = null;
			if ($focus->getField('billing_contact_id')) {
				$cbillto = new Contact;
				$cbillto->retrieve($focus->getField('billing_contact_id'), false);
			} elseif ($focus->getField('supplier_contact_id')) {
				$cbillto = new Contact;
				$cbillto->retrieve($focus->getField('supplier_contact_id'), false);
			} elseif ($focus->getField('supplier_id')) {
				$cbillto = new Account;
				$cbillto->retrieve($focus->getField('supplier_id'), false);
			} elseif ($focus->getField('billing_account_id')) {
				$cbillto = new Account;
				$cbillto->retrieve($focus->getField('billing_account_id'), false);
			}
			if($rel_mod == 'Payments' || $rel_mod == 'PaymentsOut') {
				$rel_mod = 'Accounts';
				$rel_id = $focus->getField('account_id');
				$cbillto = new Account;
				if($cbillto->retrieve($rel_id))
					$rel_name = $cbillto->name;
			}
			$label = urlencode($this->get_email_title());
			$note = "reattach_note_id={$note->id}&relabel_note={$label}";
			$parent = "parent_type=$rel_mod&parent_id={$rel_id}&parent_name=" . urlencode($rel_name);
			$id = $focus->getField('id');
			$return = "return_module=$module&return_action=DetailView&return_record={$id}";
			if ($cbillto) {
				$contact = "&contact_name=". urlencode($cbillto->name) . "&to_email_addrs={$cbillto->email1}";
				if ($cbillto->module_dir == 'Contacts') {
					$contact .= "&contact_id={$cbillto->id}";
				}
			} else {
				$contact = '';
			}
			header("Location: index.php?module=Emails&action=EditView&type=out&{$note}&{$parent}{$contact}&{$return}");
		}
		sugar_cleanup(true);
	}
	
	/**
    * create_pdf_attachment;	Creates a note for the statement pdf and logs the attachment against it
    *
    * @access public
    * @return bool
    */
    function create_pdf_attachment($objSeed) {
        // Get access to the current user
		global $current_user;
        // Get the focus object
		$strContent = $this->get_output();
        // Create a new note object
		$objNote = new Note();
        // Set the note  title
		$objNote->name = $this->get_note_title();
        // Create the upload file object
		$objFileupload = new UploadFile();
        // Set the file content
		$objFileupload->set_for_soap($this->filename, $strContent);
        // Generate the stored filename
		$objNote->filename = $objFileupload->create_stored_filename();
		// Note is assigned to the current user
        $objNote->assigned_user_id = $current_user->id;
		// Set the parent type
		$objNote->parent_type = $objSeed->module_dir;
        // Set the parent id to the accunt
		$objNote->parent_id = $objSeed->id;
        // Set the mime type
        $objNote->file_mime_type = 'application/pdf';
        // Save the note
		$strNoteId = $objNote->save();
        // Move the file to the final archived location
		$objFileupload->final_move($strNoteId);
        // Return the note id
        return $strNoteId;
	}

	function hasHeader()
	{
		return true;
	}

	function Header()
	{
		if ($this->layout->hasHeader()) {
			parent::Header();
		}
	}

	function getAvailableLayouts()
	{
		global $current_language;
		static $strings;
		if(! isset($strings)) {
			$strings = return_module_language($current_language, 'Quotes');
		}
		return array(
			'QuotePDF' => $strings['LBL_STD_LAYOUT'],
			'QuoteWithAddress' => $strings['LBL_PETRIS_LAYOUT'],
			'QuoteForEnvelope' => $strings['LBL_ENVELOPE_LAYOUT'],
		);
	}

	function showTotals()
	{
		return $this->printTotals;
	}

	function currency_format($amount, $currency_id = false, $no_round = false)
	{
		$params = array('currency_symbol' => false, 'type' => 'pdf');
		if ($currency_id) {
			$params['currency_symbol'] = true;
			$params['currency_id'] = $currency_id;
		}
		if($no_round)
			$params['round'] = null;
		return currency_format_number($amount, $params);
	}

}


?>

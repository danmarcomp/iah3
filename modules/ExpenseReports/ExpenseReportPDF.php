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


require_once 'modules/ExpenseReports/ExpenseReport.php';
require_once 'modules/BookingCategories/BookingCategory.php';
require_once 'include/pdf/PDFManager.php';

class ExpenseReportPDF extends PDFManager {
	var $focus;
	var $focus_type = 'ExpenseReport';
	var $layout, $layoutName;
	var $pdf_type;

	function ExpenseReportPDF() {
		if (isset($_REQUEST['use_address'])) $this->useCompanyAddress($_REQUEST['use_address']);
		parent::PDFManager();
		$layout = '';
		$this->setLayout($layout);
	}

	function system_layout() {
		return 'ExpenseReportPDF';
	}

	function setLayout($layoutName)
	{
		$layoutName = preg_replace('/[^a-z_]/i', '', $layoutName);
		if (file_exists('modules/ExpenseReports/' . $layoutName . '.php')) {
			require_once 'modules/ExpenseReports/' . $layoutName . '.php';
			$this->layout = new $layoutName;
		}  else {
			$this->layout =& $this;
		}
		$this->layout_name = $layoutName;
		$this->layout->initLayout($this);
	}

	function initLayout(&$pdf) {
	}

	function &mod_strings() {
		static $strings;
		if(! isset($strings))
			$strings = return_module_language($this->use_language, $this->focus->module_dir);
		return $strings;
	}

	function create_filename() {
		$mod_strings =& $this->mod_strings();
		$filename_pfx = $this->get_detail_title();
		if(! empty($mod_strings['LBL_FILENAME_REPORT']))
			$filename_pfx = $mod_strings['LBL_FILENAME_REPORT'];
		$filename = $filename_pfx . '_' . $this->focus->prefix;
		$filename .= $this->focus->report_number . '_' . $this->focus->assigned_user_name;
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
		return $s['LBL_PDF_REPORT_TITLE'];
	}
	
	function get_config_title() {
		return translate('LBL_PDF_REPORT');
	}

	function get_detail_fields() {
		$focus =& $this->focus;
		$user = new User();
		$user->retrieve($focus->assigned_user_id, false);
		$fields = array(
			'LBL_PDF_REPORT_NUMBER' => $focus->prefix . $focus->report_number,
			'LBL_PDF_DATE' => substr($focus->date_entered,0,10),
			'LBL_PDF_EMPLOYEE' => $user->name,
		);
		if ($focus->date_approved) {
			$fields['LBL_PDF_DATE_APPROVED'] = substr($focus->date_approved,0,10);
			$fields['LBL_PDF_APPROVED_BY'] = $focus->approved_user_name;
		}
		if ($focus->date_paid) {
			$fields['LBL_PDF_DATE_PAID'] = substr($focus->date_paid,0,10);
		}
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


	function get_main_columns() {
		$focus =& $this->focus;
		$mod_strings =& $this->mod_strings();
		$cols = array(
			'number' => array(
				'title' => $mod_strings['LBL_PDF_INDEX'],
				'width' => '4%',
			),
			'number2' => array(
				'width' => '4%',
			),
			'date' => array(
				'width' => '12%',
				'title' => $mod_strings['LBL_PDF_ITEM_DATE'],
			),
			'category' => array(
				'title' => $mod_strings['LBL_PDF_CATEGORY'],
				'width' => '20%',
			),
			'quantity' => array(
				'title' => $mod_strings['LBL_PDF_QUANTITY'],
				'width' => '8%',
				'text-align' => 'right',
			),
			'rate' => array(
				'title' => $mod_strings['LBL_PDF_RATE'],
				'width' => '8%',
				'text-align' => 'right',
			),
			'amount' => array(
				'title' => $mod_strings['LBL_PDF_AMOUNT'],
				'width' => '10%',
				'text-align' => 'right',
			),
			'description' => array(
				'title' => $mod_strings['LBL_PDF_DESCRIPTION'],
				'width' => '34%',
				'text-align' => 'left',
			),
		);

		return $cols;
	}

	function get_lines(&$items) {

		static $taxCodes;
		if (!$taxCodes) {
			$taxCodes = array();
			require_once 'modules/TaxCodes/TaxCode.php';
			$tc = new TaxCode;
			$codes = $tc->get_full_list('position asc');
			$tc->retrieve('-99');
			array_unshift($codes, $tc);
			foreach ($codes as $tc) {
				$taxCodes[$tc->id] = $tc->code;
				$tc->cleanup();
			}
			unset($codes);
		}


		$mod_strings =& $this->mod_strings();
		$focus =& $this->focus;
		$currency =& $this->get_currency();
		$data = array();
		$num = 1;

		$cat = new BookingCategory;
		$categories = $cat->get_option_list('expenses');

		foreach($items as $row) {
			if (!$row->tax_class_id) {
				$row->tax_class_id= '-99';
			}
			if ($row->split) $num2 = 1;
			$datarow = array(
				'number' => $num ++,
				'category' => from_html(array_get_default($categories, $row->category, '')),
				'quantity' => $row->quantity ? $row->quantity : '',
				'rate' => $row->paid_rate ? $this->currency_format($row->paid_rate, $currency->id) : '',
				'amount' => 
						$this->currency_format($row->amount, $currency->id)
						. "\n" . $taxCodes[$row->tax_class_id]
						. "\n" . $this->currency_format($row->total, $currency->id),
				'date' => $row->date,
				'description' => from_html($row->description),
				'split' => $row->split,
			);
			if ($row->parent_id) {
				$datarow['category'] = '        ' . $datarow['category'];
				$datarow['number2'] = $num2 ++;
				unset($datarow['number']);
			}
			if ($row->split) {
				unset($datarow['quantity']);
				unset($datarow['rate']);
			}
			$datarow['raw_amount'] = $row->amount;
			$data[] = $datarow;
		}
		return $data;
	}

    function get_totals_columns() {

		$mod_strings =& $this->mod_strings();
		$cols = array (
			'row_title' => array(
				'width' => '25%',
				'font-weight' => 'bold',
			),
            'advance_value' => array(
				'width' => '25%',
				'title' => $mod_strings['LBL_PDF_ADVANCE_HEADER'],
            ),
            'total_value' => array(
                'width' => '25%',
				'title' => $mod_strings['LBL_PDF_TOTAL_HEADER'],
            ),
            'balance_value' => array(
                'width' => '25%',
				'title' => $mod_strings['LBL_PDF_BALANCE_HEADER'],
            ),
        );
        return $cols;
    }

    function &get_currency() {
    	static $currency;
    	if(! isset($currency)) {
			require_once('modules/Currencies/Currency.php');
			$currency = new Currency();
			$currency->retrieve($this->focus->currency_id, false);
		}
		return $currency;
    }
	
	function &get_advance_currency() {
    	static $currency;
    	if(! isset($currency)) {
			require_once('modules/Currencies/Currency.php');
			$currency = new Currency();
			$currency->retrieve($this->focus->advance_currency_id, false);
		}
		return $currency;
    }

	function &get_system_currency() {
    	static $currency;
    	if(! isset($currency)) {
			require_once('modules/Currencies/Currency.php');
			$currency = new Currency();
			$currency->loadDefaultCurrency();;
		}
		return $currency;
    }

    function &get_shipping_provider() {
    	static $shipper;
    	if(! isset($shipper)) {
			require_once('modules/ShippingProviders/ShippingProvider.php');
			$shipper = new ShippingProvider();
			$shipper->retrieve($this->focus->shipping_provider_id, false);
		}
		return $shipper;
    }

	function print_main() {
		$mod_strings =& $this->mod_strings();
		$items = $this->focus->getLineItemsList();

		$this->setXY($this->lMargin, $this->getY() + $this->lv('10pt'));

		$taxed_shipping = 0;
		$shipping = 0;
		$total = 0;

		$main_cols = $this->get_main_columns();
		$title = $grp->name;
		$lines = $this->get_lines($items);
		$header = true;
		$opts = array(
			'border' => 0,
			'alt-background-color' => array(236,236,236),
		);
		$empty = array();
		$this->DrawTable($empty, $main_cols, $title, $header, $opts);

		foreach ($lines as $ln) {
			$line = array($ln);
			$opts['background-color'] = !empty($ln['number2']) ? array(255, 255, 255) : array(220, 220, 248);
			if (empty($ln['number2'])) {
				$total += $ln['raw_amount'];
			}
			$this->DrawTable($line, $main_cols, '', null, $opts);
		}
		//$this->DrawTable($lines, $main_cols, $title, $header, $opts);


		$currency =& $this->get_advance_currency();
		$advance = $this->currency_format($this->focus->advance, $currency->id);


		$currency_row = $exchange_row = $amount_row = $usd_row = array();

		if ($this->focus->advance_currency_id != '-99') {
			$currency_row['advance_value'] = $currency->name;
			$exchange_row['advance_value'] = number_format($this->focus->advance_exchange_rate, 5);
			$amount_row['advance_value'] = $advance;
		}

		$currency =& $this->get_system_currency();
		$advance = $this->currency_format($this->focus->advance_usdollar, $currency->id);
		$usd_row['advance_value'] = $advance;

		$currency =& $this->get_currency();
		$total = $this->currency_format($total + $this->focus->tax, $currency->id);

		$this->ruleLine(array('color' => array(100,100,100), 'height' => '1.5pt'));

		if ($this->focus->currency_id != '-99') {
			$currency_row['total_value'] = $currency->name;
			$exchange_row['total_value'] = number_format($this->focus->exchange_rate, 5);
			$amount_row['total_value'] = $total;
		}

		$currency =& $this->get_system_currency();
		$total = $this->currency_format($this->focus->total_amount_usdollar, $currency->id);
		$usd_row['total_value'] = $total;


		$currency =& $this->get_currency();
		$balance = $this->currency_format($this->focus->balance, $currency->id);
		if ($this->focus->currency_id != '-99') {
			$currency_row['balance_value'] = $currency->name;
			$exchange_row['balance_value'] = number_format($this->focus->exchange_rate, 5);
			$amount_row['balance_value'] = $balance;
		}

		$currency =& $this->get_system_currency();
		$balance = $this->currency_format($this->focus->balance_usdollar, $currency->id);
		$usd_row['balance_value'] = $balance;


		if (!empty($currency_row)) {
			$currency =& $this->get_system_currency();
			$currency_row['row_title'] =  $mod_strings['LBL_PDF_CURRENCY'];
			$exchange_row['row_title'] = $mod_strings['LBL_PDF_EXCHANGE_RATE'];
			$amount_row['row_title'] = $mod_strings['LBL_PDF_TOTAL_AMOUNT'];
			$totals = array(
				$currency_row,
				$exchange_row,
				$amount_row,
			);
		} else {
			$totals = array();
		}

		$usd_row['row_title'] = sprintf($mod_strings['LBL_PDF_AMOUNT_USD'], $currency->name);

		$totals[] = $usd_row;

		$opts = array(
				'border' => 0,
				'font-size' => 10,
				'background-color' => array(255, 255, 255),
		);
		$title = $this->get_grand_totals_title();
		$total_cols = $this->get_totals_columns();
		$this->DrawTable($totals, $total_cols, $title, $opts, array('border' => 0));
	}

	function get_grand_totals_title() {
		return '';
		$mod_strings =& $this->mod_strings();
		return $mod_strings['LBL_GRAND_TOTALS'];
	}

	function print_notes() {
		$mod_strings =& $this->mod_strings();
		$typeName = null;
		if (!empty($this->focus->parent_name)) {
			$sing = AppConfig::setting("lang.lists.{$this->use_language}.app.moduleListSingular", array());
			$typeName = array_get_default($sing, $this->focus->parent_type, $this->focus->parent_type);
		}

		if (!empty($this->account_name) || !empty($typeName)) {
			$text = $mod_strings['LBL_PDF_RELATED_TO'];

			if (!empty($this->focus->account_name)) {
				$text .= $mod_strings['LBL_PDF_RELATED_ACCOUNT'];
				$text .= $this->focus->account_name;
				if (!empty($typeName)) {
					$text .= ', ';
				}
			}
			if (!empty($typeName)) {
				$text .= $typeName;
				$text .= ' : ';
				$text .= $this->focus->parent_name;
			}

			$cols = array('text' => array('width' => '100%'));
			$data = array(array('text' => $text));
			$opts = array('border' => 0);
			$this->setX($this->lMargin);
			$this->setY($this->getY() + $this->lv('10pt'));
			$this->DrawTable($data, $cols, '', false, $opts);
		}

		if(! empty($this->focus->description)) {
			$cols = array('text' => array('width' => '100%'));
			$data = array(array('text' => $this->focus->description));
			$opts = array('border' => 0);
			$title = array('text' => $mod_strings['LBL_PDF_NOTES'], 'text-align' => 'L', 'font-size' => 12);
			$this->setX($this->lMargin);
			$this->setY($this->getY() + $this->lv('10pt'));
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
		$this->setFontSize(10);
		$this->moveY('20pt');
		$mod_strings =& $this->mod_strings();
		$cols = array('label' => array('width' => '80pt', 'nowrap' => true), 'line' => array('width' => '170pt', 'nowrap' => true));
		$title = array('text' => $mod_strings['LBL_PDF_ACCEPT_TITLE'], 'text-align' => 'L', 'font-size' => 12);
		$line = '__________________________';

		$data = array(
			array('label' => $mod_strings['LBL_PDF_REPORT_NUMBER'], 'line' => $this->focus->prefix . $this->focus->report_number),
			array('label' => $mod_strings['LBL_PDF_ACCEPT_PRINT_NAME'], 'line' => $line),
			array('label' => $mod_strings['LBL_PDF_ACCEPT_EMPLOYEE_TITLE'], 'line' => $line),
			array('label' => $mod_strings['LBL_PDF_ACCEPT_SIGNATURE'], 'line' => $line),
			array('label' => $mod_strings['LBL_PDF_ACCEPT_DATE'], 'line' => $line),
		);
		$params = array('border' => 0, 'width' => '200pt', 'allow-page-span' => false);

		$this->DrawTable($data, $cols, $title, false, $params);
	}

	function do_print(&$focus) {
		$this->focus =& $focus;
		$this->filename = filename_safe_string($this->create_filename());
		$this->new_page();
		$this->layout->execute($this);
	}

	function execute(&$pdf)
	{
		$pdf->print_logo();
		$pdf->print_details();
		$pdf->print_extra_head();
		$pdf->print_main();
		$pdf->print_notes();
		$pdf->print_terms();
		$pdf->print_extra_foot();
	}

	function handle_request($attach_note=true) {
		if(! empty($_REQUEST['config'])) {
			$this->render_config();
			return;
		}

		$focus = new $this->focus_type;
		if(empty($_REQUEST['record']) || ! $focus->retrieve($_REQUEST['record'], false)) {
			sugar_die("Record ID missing or unknown");
		}
		$focus->pdf_output_mode = true; // prevent HTML-escaping of certain field values
		$focus->fill_in_additional_detail_fields();

		if ($focus->approved_user_id) {
			$u = new User;
			if ($u->retrieve($focus->approved_user_id, false))
				$focus->approved_user_name = $u->name;
		}
		

		if(! $focus->ACLAccess('DetailView')){
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
		return $s['LBL_PREPARED_REPORT'];
	}

	function get_email_title() {
		$s =& $this->mod_strings();
		return $s['LBL_EMAILED_REPORT'];
	}

	function create_note() {
		global $current_user;
		$focus =& $this->focus;
		$module = $focus->module_dir;
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
		$note->parent_id = $focus->id;
		if (isset($focus->billing_contact_id)) {
			$note->contact_id = $focus->billing_contact_id;
		} elseif (isset($focus->supplier_contact_id)) {
			$note->contact_id = $focus->supplier_contact_id;
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
			$label = urlencode($this->get_email_title());
			$note = "reattach_note_id={$note->id}&relabel_note={$label}";
			$parent = "parent_type=$module&parent_id={$focus->id}&parent_name=" . urlencode($focus->name);
			$return = "return_module=$module&return_action=DetailView&return_id={$focus->id}";
			header("Location: index.php?module=Emails&action=EditView&type=out&{$note}&{$parent}{$contact}&{$return}");
		}
		sugar_cleanup(true);
	}

	/**
    * create_pdf_attachment;	Creates a note for the statement pdf and logs the attachment against it
    *
    * @access public
     *@param $objSeed
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
			$params['round'] = -1;
		return currency_format_number($amount, $params);
	}

}


?>

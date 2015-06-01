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


require_once 'modules/Cases/Case.php';
require_once 'modules/Contacts/Contact.php';
require_once 'include/pdf/PDFManager.php';

class WorkOrderPDF extends PDFManager {
	
	var $focus;
	var $focus_type = 'aCase';
	var $layout, $layoutName;
	var $pdf_type;

	function WorkOrderPDF() {
		if (isset($_REQUEST['use_address'])) $this->useCompanyAddress($_REQUEST['use_address']);
		parent::PDFManager();
		$layout = '';
		$this->setLayout($layout);		
	}

	function system_layout() {
		return 'WorkOrderPDF';
	}

	function setLayout($layoutName) {
		$layoutName = preg_replace('/[^a-z_]/i', '', $layoutName);
		if (file_exists('modules/Cases/' . $layoutName . '.php')) {
			require_once 'modules/Cases/' . $layoutName . '.php';
			$this->layout = new $layoutName;
		}  else {
			$this->layout =& $this;
		}
		$this->layout_name = $layoutName;
		$this->layout->initLayout($this);
	}

	function initLayout(&$pdf) {}

	function &mod_strings() {
		static $strings;
		if(! isset($strings))
			$strings = return_module_language($this->use_language, $this->focus->getModuleDir());
		return $strings;
	}

	function create_filename() {
		$mod_strings =& $this->mod_strings();
		$filename_pfx = $this->get_detail_title();
		if(! empty($mod_strings['LBL_FILENAME_WORK_ORDER']))
			$filename_pfx = $mod_strings['LBL_FILENAME_WORK_ORDER'];
		$filename = $filename_pfx . "_";
		$filename .= $this->focus->getField('case_number') . '_' . $this->focus->getField('assigned_user.name');
		$filename = $filename . '.pdf';
		return $filename;
	}

	function getLogoPosition(&$pdf) {
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
		return $s['LBL_PDF_ORDER'];
	}
	
	function get_config_title() {
		return translate('LBL_PRINT_WO_BUTTON_LABEL');
	}

	function get_detail_fields() {
		$row = $this->row;
		
		$fields = array(
			'LBL_PDF_CASE_NUMBER' => $row['case_number'],
			'LBL_PDF_DATE' => substr($row['date_entered'], 0, 10),
			'LBL_PDF_CUSTOMER' => $row['cust_contact.name'],
			'LBL_PDF_CUSTOMER_REQ_NO' => $row['cust_req_no'],
			'LBL_PDF_CUSTOMER_PHONE' => $row['cust_phone_no'],
		);
				
		return $fields;
	}

	function getDetailsPosition(&$pdf) {
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

	function print_extra_head() {}

	function get_addresses() {
		global $locale;
		$address = array();

		$addr = $locale->getLocaleBeanFormattedAddress($this->focus, 'shipping_', 'pdf');
		if($addr)
			$address['LBL_PDF_CUSTOMER_ADDRESS'] = explode("\n", $addr);
		
		/*if ($contact->retrieve($focus->cust_contact_id, false)) {
			$addr = $locale->getLocaleBeanFormattedAddress($contact, 'primary_', 'pdf');
			if($addr)
				$address['LBL_PDF_CONTACT_ADDRESS'] = explode("\n", $addr);
		}*/

		return $address;
	}

	function getAddressesPosition(&$pdf) {
		$m = $pdf->getMargins();
		return array(
			'x' => $m['left'],
			'y' => $pdf->getY() + $pdf->lv('14pt')
		);
	}

	function showAddressTitle() { return true; }

	function print_addresses() {
		$addrs = $this->get_addresses();
		if(! $addrs)
			return;
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
	
	function get_booked_columns() {
		$mod_strings =& $this->mod_strings();
		$cols = array(
			'number' => array(
				'title' => $mod_strings['LBL_PDF_INDEX'],
				'width' => '5%',
			),		
			'name' => array(
				'title' => $mod_strings['LBL_PDF_BH_SUBJECT'],
				'width' => '30%',
			),
			'hours' => array(
				'title' => $mod_strings['LBL_PDF_BH_HOURS'],			
				'width' => '10%',
			),
			'status' => array(
				'width' => '15%',
				'title' => $mod_strings['LBL_PDF_BH_STATUS'],
			),
			'booking_class' => array(
				'title' => $mod_strings['LBL_PDF_BH_BOOKING_CLASS'],
				'width' => '20%',
			),
			'date_start' => array(
				'title' => $mod_strings['LBL_PDF_BH_DATE'],
				'width' => '12%',
			),
			'assigned_user_name' => array(
				'title' => $mod_strings['LBL_PDF_BH_USER'],
				'width' => '8%',
			),
		);

		return $cols;
	}

	function get_booked_lines(&$items) {
		$data = array();
		$num = 1;
		$bk_cls = AppConfig::setting("lang.lists.{$this->use_language}.app.booked_hours_related_dom", array());

		foreach($items as $row) {
			$datarow = array(
				'number' => $num ++,
				'name' => $row['name'],
				'hours' => format_number($row['quantity'], 2, 2),
				'status' => ucwords($row['status']),
				'booking_class' => array_get_default($bk_cls, $row['related_type'], $row['related_type']),
				'date_start' => $row['date_start'],
				'assigned_user_name' => $row['assigned_user.name']
			);
			
			$data[] = $datarow;
		}
		
		return $data;
	}

	function get_service_parts_columns() {
		$mod_strings =& $this->mod_strings();
		$cols = array(
			'number' => array(
				'title' => $mod_strings['LBL_PDF_INDEX'],
				'width' => '5%',
			),		
			'name' => array(
				'title' => $mod_strings['LBL_SP_NAME'],
				'width' => '25%',
			),
			'category' => array(
				'title' => $mod_strings['LBL_SP_CATEGORY'],			
				'width' => '30%',
			),
			'type' => array(
				'width' => '30%',
				'title' => $mod_strings['LBL_SP_TYPE'],
			),
			'selling_price' => array(
				'title' => $mod_strings['LBL_SP_SELLING_PRICE'],
				'width' => '10%',
			),
		);

		return $cols;
	}

	function get_service_parts_lines(&$items) {
		$data = array();
		$num = 1;

		foreach($items as $row) {
			$datarow = array(
				'number' => $num ++,
				'name' => $row['name'],
				'category' => $row['product_category.name'],
				'type' => $row['product_type.name'],
				'selling_price' => $this->currency_format($row['purchase_price'], $row['currency_id'], true)			
			);
			
			$data[] = $datarow;
		}
		
		return $data;
	}	
	
	function print_table($lines, $main_cols, $table_title) {
		$this->setXY($this->lMargin, $this->getY() + $this->lv('10pt'));

		$title = array('text' => $table_title, 'text-align' => 'L', 'font-size' => 10);		
		$header = true;
		$opts = array(
			'border' => 0,
			'alt-background-color' => array(220, 220, 248),
		);
		$empty = array();
		$this->DrawTable($empty, $main_cols, $title, $header, $opts);		
		$this->DrawTable($lines, $main_cols, '', null, $opts);

		$this->ruleLine(array('color' => array(100,100,100), 'height' => '1.5pt'));
	}

	function print_main() {
		$mod_strings =& $this->mod_strings();

		$lq = new ListQuery('aCase', true, array('link_name' => 'booked_hours'));
		$lq->setParentKey($this->focus->getField('id'));
		$lq->addField('assigned_user.name');
		$res = $lq->runQuery();
		$hours = $res->getRows();

		$booked_lines = $this->get_booked_lines($hours);
		foreach(range(1,5) as $i)
			$booked_lines[] = array();
		$booked_cols = $this->get_booked_columns();
		$booked_title = $mod_strings['LBL_PDF_BH_TITLE'];
		$this->print_table($booked_lines, $booked_cols, $booked_title);

		$lq = new ListQuery('aCase', true, array('link_name' => 'products'));
		$lq->setParentKey($this->focus->getField('id'));
		$lq->addField('assigned_user.name');
		$lq->addField('product_category.name');
		$lq->addField('product_type.name');
		$res = $lq->runQuery();
		$parts = $res->getRows();

		$parts_lines = $this->get_service_parts_lines($parts);
		foreach(range(1,5) as $i)
			$parts_lines[] = array();
		$parts_cols = $this->get_service_parts_columns();
		$parts_title = $mod_strings['LBL_SP_TITLE'];
		
		$this->print_table($parts_lines, $parts_cols, $parts_title);		
	}	
	
	function print_notes() {
		$mod_strings =& $this->mod_strings();

		if($this->focus->getField('name')) {
			$cols = array('text' => array('width' => '100%'));
			$data = array(array('text' => $this->focus->getField('description')));
			$opts = array('border' => 0, 'font-size' => 8);
			$title = array('text' => $mod_strings['LBL_PDF_NOTES'] .": ". $this->focus->getField('name'), 'text-align' => 'L', 'font-size' => 10);
			$this->setX($this->lMargin);
			$this->DrawTable($data, $cols, $title, false, $opts);
		}		
		
		if($this->focus->getField('resolution')) {
			$cols = array('text' => array('width' => '100%'));
			$data = array(array('text' => $this->focus->getField('resolution')));
			$opts = array('border' => 0, 'font-size' => 8);
			$title = array('text' => $mod_strings['LBL_PDF_RESOLUTION'], 'text-align' => 'L', 'font-size' => 10);
			$this->setX($this->lMargin);
			$this->DrawTable($data, $cols, $title, false, $opts);
		}		
	}
	
	function get_terms() {
		$terms = from_html(trim(AppConfig::setting('company.std_case_terms', '')));
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
			array('label' => $mod_strings['LBL_PDF_CASE_NUMBER'], 'line' => $this->focus->getField('case_number')),
			array('label' => $mod_strings['LBL_PDF_ACCEPT_PRINT_NAME'], 'line' => $line),
			array('label' => $mod_strings['LBL_PDF_ACCEPT_CUSTOMER_TITLE'], 'line' => $line),
			array('label' => $mod_strings['LBL_PDF_ACCEPT_SIGNATURE'], 'line' => $line),
			array('label' => $mod_strings['LBL_PDF_ACCEPT_DATE'], 'line' => $line),
		);
		
		$params = array('border' => 0, 'width' => '200pt', 'allow-page-span' => false);
		
		$this->DrawTable($data, $cols, $title, false, $params);
	}

	function do_print(&$focus) {
		$this->focus =& $focus;
		$this->row = $focus->row;
		$this->filename = filename_safe_string($this->create_filename());
		$this->new_page();
		$this->layout->execute($this);
	}

	function execute(&$pdf) {
		$pdf->print_logo();
		$pdf->print_details();
		$pdf->print_extra_head();
		$pdf->print_addresses();
		$pdf->print_notes();
		$pdf->print_main();		
		$pdf->print_terms();
		$pdf->print_extra_foot();
	}

	function handle_request($attach_note=true) {
		if(! empty($_REQUEST['config'])) {
			$this->render_config();
			return;
		}

		$lq = new ListQuery($this->focus_type, true);
		$lq->addFilterPrimaryKey($_REQUEST['record']);
		$lq->addField('assigned_user.name');
		$lq->addField('cust_contact.name');
		$lq->addField('account.name', 'shipping_account_name');
		$lq->addfield('account.shipping_address_street', 'shipping_address_street');
		$lq->addfield('account.shipping_address_city', 'shipping_address_city');
		$lq->addfield('account.shipping_address_state', 'shipping_address_state');
		$lq->addfield('account.shipping_address_postalcode', 'shipping_address_postalcode');
		$lq->addfield('account.shipping_address_country', 'shipping_address_country');
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
		return $s['LBL_PREPARED_ORDER'];
	}

	function get_email_title() {
		$s =& $this->mod_strings();
		return $s['LBL_EMAILED_ORDER'];
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
		$note->contact_id = $focus->getField('cust_contact_id');
		$note->file_mime_type = 'application/pdf';
		$note->save();
		$upload_file->final_move($note->id);

		if(empty($_REQUEST['attach_to_email']) || $_REQUEST['attach_to_email'] == 'false') {
			$path = 'download.php?type=Notes&id=' . $note->id;
			header('Location: ' . $path);
		} else {
			$id = $focus->getField('id');
			$name = $focus->getField('name');
			$label = urlencode($this->get_email_title());
			$note = "reattach_note_id={$note->id}&relabel_note={$label}";
			$parent = "parent_type=$module&parent_id={$id}&parent_name=" . urlencode($name);
			$return = "return_module=$module&return_action=DetailView&return_id={$id}";
			header("Location: index.php?module=Emails&action=EditView&type=out&{$note}&{$parent}{$contact}&{$return}");
		}
		
		sugar_cleanup(true);
	}

	/**
    * create_pdf_attachment;
    * Creates a note for the statement pdf and logs the attachment against it
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

	function hasHeader() { return true; }

	function Header() {
		if ($this->layout->hasHeader()) {
			parent::Header();
		}
	}
	
	function currency_format($amount, $currency_id = false, $no_round = false) {
		$params = array('currency_symbol' => false, 'type' => 'pdf');
		if ($currency_id == null) {
			$currency_id = '-99';
		}
		$params['currency_symbol'] = true;
		$params['currency_id'] = $currency_id;
		if($no_round)
			$params['round'] = -1;
		return currency_format_number($amount, $params);
	}	
}
?>

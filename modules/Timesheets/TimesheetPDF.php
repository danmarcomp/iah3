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


require_once 'include/pdf/PDFManager.php';
require_once 'modules/Timesheets/Timesheet.php';
require_once 'modules/HR/Employee.php';

class TimesheetPDF extends PDFManager {
	var $focus;

	var $layout, $layoutName;
	var $proforma = null;

	static $taxedShipping;

	static $shipping;

	function TimesheetPDF()
	{
		if (isset($_REQUEST['use_address'])) $this->useCompanyAddress($_REQUEST['use_address']);
		parent::PDFManager();
		$layout = 'TimesheetPDF';
		$this->setLayout($layout);
	}
	
	function setLayout($layoutName)
	{
		$layoutName = preg_replace('/[^a-z_]/i', '', $layoutName);
		if (strtolower(get_class($this)) == strtolower($layoutName)) {
			$this->layout =& $this;
		} elseif (file_exists('modules/Timesheets/' . $layoutName . '.php')) {
			require_once 'modules/Timesheets/' . $layoutName . '.php';
			$this->layout = new $layoutName;
		}
		$this->layout_name = $layoutName;
		$this->layout->initLayout($this);
	}
	
	function initLayout(&$pdf) {
	}

	function &mod_strings() {
		global $current_language;
		static $strings;
		if(! isset($strings))
			$strings = return_module_language($current_language, $this->focus->module_dir);
		return $strings;
	}
	
	function create_filename() {
		$mod_strings =& $this->mod_strings();
		$filename_pfx = $this->get_detail_title();
		$filename = $filename_pfx . '_' . $this->focus->name;
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
		return $s['LBL_PDF_TIMESHEET'];
	}
	
	function get_detail_fields() {
		global $app_list_strings;
		$focus =& $this->focus;
		$user = new User();
		$user->retrieve($focus->assigned_user_id, false);
		$emp = new Employee;
		$emp->retrieve_for_user_id($user->id);
		$fields = array(
			'LBL_PDF_EMPLOYEE' => $user->name,
			'LBL_PDF_TITLE' => $user->title,
			'LBL_PDF_DEPARTMENT' => array_get_default(
										$app_list_strings['departments_dom'],
										$user->department, $user->department
									),
			'LBL_PDF_REPORTS_TO' => $user->reports_to_name,
			'LBL_PDF_EMPLOYEE_NUM' => $emp->employee_num,
			'LBL_PDF_PERIOD_START' => $this->focus->date_starting,
			'LBL_PDF_PERIOD_END' => $this->focus->date_ending,
			'LBL_PDF_MODIFIED' => $this->focus->date_modified,
			'LBL_PDF_TOTAL_HOURS' => $this->focus->total_hours,
			'LBL_PDF_STATUS' => array_get_default(
									$app_list_strings['timesheet_status_dom'],
									$this->focus->status,
									$this->focus->status
								),
		);
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
	
	
	function print_main() {
		global $timedate, $company_info, $app_list_strings;
		$lang = $this->mod_strings();
		$grid = $this->focus->getTotalsByWeek();

		require_once 'modules/Booking/BookedHours.php';
		$hours_seed = new BookedHours;
		$hours = $hours_seed->query_hours(true, $this->focus->id, '', '', '', '', false);

		$data = array();
		$dayTotal = 0;
		$opts = array(
			'border' => 0,
			'header' => array(
				'border' => 0,
			),
		);
		$cols = array(
			'date' => array('width' => '20%'),
			'description' => array('width' => '40%'),
			'related_name' => array('width' => '40%'),
		);

		$last_time = null;
		foreach ($hours as $line) {
			$line_time = strtotime($line['date_start'] . ' GMT ');
			$line_week = date("W", $line_time);
			$line_time = custom_strftime($lang['LBL_LINE_DATE_FORMAT'], $line_time);

			if ($last_time && $last_time != $line_time) {
				$cols['date']['title'] = $last_time;
				$cols['related_name']['title'] = sprintf($lang['LBL_PDF_DAY_TOTAL'], $dayTotal);
				$this->setXY($this->lMargin, $this->getY() + $this->lv('10pt'));
				$this->DrawTable($data, $cols, '', true, $opts);
				$data = array();
				$dayTotal = 0;
			}
			$minutes = (int)($line['quantity']);
			$start_time =  $timedate->to_display_time($line['date_start']);
			$end_time = $timedate->to_display_time(date('Y-m-d H:i:s', strtotime($line['date_start'] . " + $minutes minutes")));

			$related_name = '';
			if ($line['related_name'] && $line['related_type']) {
				$related_name = $app_list_strings['moduleListSingular'][$line['related_type']];
				$related_name .= ': ';
				$related_name .= $line['related_name'];
			}

			$cols['date']['title'] = $line_time;
            $quantity = ($minutes > 0) ? ($minutes / 60) : 0;
			$data[] = array(
				'date' => $start_time . '-' . $end_time . sprintf($lang['LBL_PDF_NUM_HOURS'], $quantity),
				'description' => $line['name'],
				'related_name' => $related_name,
			);
			$dayTotal += $quantity;
			$last_time = $line_time;
		}

		if ($last_time) {
			$cols['date']['title'] = $last_time;
			$cols['related_name']['title'] = sprintf($lang['LBL_PDF_DAY_TOTAL'], $dayTotal);
			$this->setXY($this->lMargin, $this->getY() + $this->lv('10pt'));
			$this->DrawTable($data, $cols, '', true, $opts);
		}

		return;

		$this->setXY($this->lMargin, $this->getY() + $this->lv('10pt'));
		$cols = array();
		foreach ($grid as $weekTitle => $days) {
			if (empty($cols)) {
				foreach ($days as $weekDayName => $unused) {
					$cols[$weekDayName] = array(
						'title' => $weekDayName,
						'text-align' => 'center',
					);
				}
			}
			$data = array($days);
			$title = sprintf($lang['LBL_PDF_WEEK_STARTS'], $weekTitle);
			$this->DrawTable($data, $cols, $title);
		}

		if (count($grid) > 1) {
			$i = 1;
			$cols = array();
			$data = array();
			foreach ($grid as $weekTitle => $days) {
				$weekName = $i;
				$cols[$i] = array(
					'title' => sprintf($lang['LBL_PDF_WEEK_NUMBER'], $weekName),
					'text-align' => 'center',
				);
				$data[0][$i] = array_sum($days);
				$i++;
			}
			$this->DrawTable($data, $cols, $lang['LBL_PDF_TOTALS_TITLE']);
		}
	}
	
	function print_notes() {
		$mod_strings =& $this->mod_strings();
		if(! empty($this->focus->description)) {
			$cols = array('text' => array('width' => '100%'));
			$data = array(array('text' => $this->focus->description));
			$opts = array('border' => 0);
			$title = array('text' => $mod_strings['LBL_PDF_NOTES'], 'text-align' => 'L', 'font-size' => 12);
			$this->setX($this->lMargin);
			$this->DrawTable($data, $cols, $title, false, $opts);
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
            array('label' => $mod_strings['LBL_PDF_ACCEPT_TIMESHEET'], 'line' => $this->focus->name),
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
		$this->filename = filename_safe_string($this->create_filename());
		$this->new_page();
		$this->layout->execute($this);
	}

	function execute(&$pdf)
	{
		$pdf->print_logo();
		$pdf->print_details();
		$pdf->print_notes();
		$pdf->print_main();
        $pdf->print_extra_foot();        
	}
	
	function handle_request($attach_note=true) {		
		$focus = new Timesheet;
		if(empty($_REQUEST['record']) || ! $focus->retrieve($_REQUEST['record'], false)) {
			sugar_die("Record ID missing or unknown");
		}
		$focus->pdf_output_mode = true; // prevent HTML-escaping of certain field values
		$focus->fill_in_additional_detail_fields();
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
		return $s['LBL_PREPARED_QUOTE'];
	}

	function get_email_title() {
		$s =& $this->mod_strings();
		return $s['LBL_EMAILED_QUOTE'];
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
			$cbillto = null;
			if (!empty($focus->billing_contact_id)) {
				$cbillto = new Contact;
				$cbillto->retrieve($focus->billing_contact_id, false);
			} elseif (!empty($focus->supplier_contact_id)) {
				$cbillto = new Contact;
				$cbillto->retrieve($focus->supplier_contact_id, false);
			} elseif (!empty($focus->supplier_id)) {
				$cbillto = new Account;
				$cbillto->retrieve($focus->supplier_id, false);
			} elseif (!empty($focus->billing_account_id)) {
				$cbillto = new Account;
				$cbillto->retrieve($focus->billing_account_id, false);
			}
			$label = urlencode($this->get_email_title());
			$note = "reattach_note_id={$note->id}&relabel_note={$label}";
			$parent = "parent_type=$module&parent_id={$focus->id}&parent_name=" . urlencode($focus->name);
			$return = "return_module=$module&return_action=DetailView&return_id={$focus->id}";
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
	}}
?>
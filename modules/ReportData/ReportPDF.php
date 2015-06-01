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


require_once('include/pdf/PDFManager.php');


class ReportPDF extends PDFManager {
	var $default_orientation = 'L';
	var $formatter;
	var $title;
	var $shown_title = array();
	
	function init() {
		set_time_limit(180);
		$this->new_page();
	}
	
	function report_init_output(&$formatter) {
		$this->formatter =& $formatter;
		$this->title = $formatter->get_title();
	}
	
	function report_format_rows($depth, &$cols, &$rows, $alt_title=null) {
		$title = array(
			'text' => $this->title,
			'new-page-only' => array_get_default($this->shown_title, $this->page, false),
			'left' => $this->lMargin,
		);
		if($alt_title !== null) {
			$title['text'] = $alt_title;
			unset($title['new-page-only']);
		}
		$xOffs = $depth > 0 ? $this->lv($depth*15 . 'pt') : 0;
		foreach($cols as $idx => $c) {
			if(! empty($c['is_total']))
				$cols[$idx]['font-weight'] = 'bold';
			if(! empty($c['is_float']))
				$cols[$idx]['text-align'] = 'right';
		}
		$this->setX($this->lMargin + $xOffs);
		$header = array(
			'border' => 1,
			'border-color' => array(255,255,255),
			'border-width' => '0.5pt',
			'color' => array(255,255,255),
			'background-color' => array(100,100,100),
			'font-weight' => 'normal',
		);
		$options = array(
			'border' => 0,
			'alt-background-color' => array(200,200,200),
		);
		$this->DrawTable($rows, $cols, $title, $header, $options);
		$this->shown_title[$this->page] = true;
	}
	
	function report_format_totals(&$cols, &$rows, $title) {
		$this->report_format_rows(-1, $cols, $rows, $title);
	}
	
	function &report_get_output() {
		$this->filename = $this->formatter->create_filename();
		$output = array(
			'format' => 'string',
			'filename' => $this->filename . '.pdf',
			'mimetype' => 'application/pdf',
			'disposition' => 'attachment',
			'data' => $this->get_output(),
		);
		return $output;
	}
}

?>

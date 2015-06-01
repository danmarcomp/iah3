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


class ReportExport {

	var $formatter;

	function report_init_output(&$formatter) {
		$this->formatter =& $formatter;
	}

	function report_format_output() {
		global $locale;
		
		$focus =& $this->formatter->focus;
		$method = $this->formatter->format;
	
		$delimited = array(
			'csv' => array('delim' => ',', 'replace' => false, 'ext' => 'csv', 'mime' => 'text/csv'),
			'scsv' => array('delim' => ';', 'replace' => false, 'ext' => 'csv', 'mime' => 'text/csv'),
			'tsv' => array('delim' => "\t", 'replace' => ' ', 'ext' => 'tsv', 'mime' => 'text/tab-separated-values'),
		);
	
		if(! isset($delimited[$method]))
			sugar_die('Unknown export method: '.$method);
		$method_spec = $delimited[$method];
	
		$charset = $locale->getExportCharset();
	
		$rows = $focus->get_rows();
		$report = $focus->get_report();
		$fields = $focus->get_fields_translated();
		$header = array();
		$newline = "\r\n";
	
		foreach($fields as $name => $field) {
			if($field['display'] != 'query_only' && empty($field['no_export']))
				$header[] = $field['display_name'];
		}
	
		for(reset($rows); list($idx,) = each($rows);)
			$focus->format_row_data($fields, $rows[$idx], 'export');
	
		array_unshift($rows, $header);
		$content = $focus->make_separated_values($rows, $method_spec['delim'], $method_spec['replace']);
		$content = implode($newline, $content). $newline;
		if(strtoupper($charset) != 'UTF-8')
			$content = $locale->translateCharset($content, 'UTF-8', $charset, true);
		$name = $this->formatter->create_filename() . '.' . $method_spec['ext'];
	
		$output = array('format' => 'string', 'data' => $content, 'filename' => $name,
			'mimetype' => $method_spec['mime'], 'charset' => $charset);
		return $output;
		
	}
	
}


?>

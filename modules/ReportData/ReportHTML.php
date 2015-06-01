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



class ReportHTML {
	var $formatter;
	var $title;
	var $charset;
	var $header_text;
	var $levels;
	var $body = '';
	
	function init() {
		set_time_limit(180);
	}
	
	function report_init_output(&$formatter) {
		$this->formatter =& $formatter;
		$this->title = $formatter->get_title();
		$this->charset = AppConfig::charset();
		require_once('include/pdf/PDFManager.php');
		$mgr = new PDFManager();
		$this->header_text = $mgr->get_company_header_text();
		$this->levels = $formatter->group_levels;
	}
	
	function report_format_rows($depth, &$cols, &$rows) {
		$title = $depth ? '' : $this->title;
		$options = array();
		$nc = count($cols);
		foreach($cols as $idx => $c) {
			if(! empty($c['is_total']))
				$cols[$idx]['font-weight'] = 'bold';
			if(! empty($c['is_float']))
				$cols[$idx]['text-align'] = 'right';
			$cols[$idx]['width'] = sprintf('%d', 100/$nc);
		}
		
		$row = '<tr>'."\n";
		for($i = 0; $i < $depth; $i++) {
			$row .= '<td class="reportDataSpacer" style="width: 15pt">&nbsp;</td>'."\n";
		}
		$row .= '<td colspan="'.($this->levels - min(0, $depth) + 1).'">'."\n";
	
		$row .= '<table class="reportData" border="0" cellpadding="0" cellspacing="0" width="100%">'."\n";
		$row .= '<thead>';
		$left_margin = $depth ? $depth.'em' : '';
		$row .= '<tr class="reportDataHeader">'."\n";
		foreach($cols as $name => $col) {
			$width = array_get_default($col, 'width');
			if(!empty($width)) $width = ' width="'.$width.'%"';
			$title = $col['title'];
			$row .= "  <th{$width}>{$title}</th>\n";
		}
		$row .= '</tr>'."\n";
		$row .= '</thead>';
		$row .= '<tbody>';
		foreach($rows as $idx => $r) {
			$row_class = $idx % 2 ? 'odd' : 'even';
			$row .= '<tr class="'.$row_class.'DataRow">'."\n";
			foreach($cols as $name => $col)
				$row .= '  <td>'.$r[$name].'</td>'."\n";
			$row .= '</tr>'."\n";
		}
		$row .= '</tbody>';
		$row .= '</table>'."\n";
		
		$row .= '</td></tr>'."\n";
		$this->body .= $row;
	}
	
	function report_format_totals(&$cols, &$rows, $title) {
		if($title)
			$this->body .= '<tr><td colspan="'.($this->levels+1).'"><h4 class="reportGrandTotals">'.$title.'</h4></td></tr>';
		$this->report_format_rows(-1, $cols, $rows);
	}
	
	function &report_get_output() {
		$sugar_version = AppConfig::version();
		$content = <<<EOH
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>{$this->title}</title>
	<meta name="generator" content="info@hand $sugar_version" />
	<meta http-equiv="Content-Type" content="text/html; charset={$this->charset}" />
	<style type="text/css">
		body {
			background-color: #fff;
		}
		h3.reportHeader {
			font-family: Helvetica, Arial, sans-serif;
			font-size: 14pt;
			font-weight: bold;
			text-align: center;
		}
		h4.reportGrandTotals {
			font-family: Helvetica, Arial, sans-serif;
			font-size: 12pt;
			font-weight: bold;
			text-align: center;
			margin: 0; padding: 0;
			margin-top: 1em;
		}
		.reportFooter {
			margin-top: 2em;
			font-family: Helvetica, Arial, sans-serif;
			font-size: 8pt;
		}
		.reportFooter a:link, .reportFooter a:visited {
			color: #666;
		}
		table.reportData {
			border: 1px solid #aaa;
			border-top: none;
		}
		table.reportData td {
			vertical-align: top;
			font-family: Helvetica, Arial, sans-serif;
			font-size: 9pt;
			padding: 1px 0.3em;
			color: #333;
		}
		table.reportData tr.oddDataRow {
			background-color: #ddd;
		}
		tr.reportDataHeader {
			background-color: #666;
		}
		tr.reportDataHeader th {
			color: white;
			border: 1px outset #999;
			white-space: nowrap;
			font-family: Helvetica, Arial, sans-serif;
			font-size: 9pt;
			font-weight: bold;
			padding: 1px 0.2em;
		}
		table.reportData a:link, table.reportData a:visited {
			color: #239;
			text-decoration: none;
		}
		table.reportData a:hover {
			text-decoration: underline;
		}
		table.reportMultilineText td {
			font-size: 80%;
		}
	</style>
</head>
<body>

EOH;

	$header_text = nl2br($this->header_text);

	$content .= '<h3 class="reportHeader">'.$this->title."</h3>\n";
	
	$levels = $this->levels + 1;
	$content .= <<<EOH
<table class="reportDataWrapper" border="0" cellpadding="0" cellspacing="0" width="100%">
<tfoot>
	<tr><td colspan="{$levels}">
	<p align="center" class="reportFooter"><a href="http://www.infoathand.com">info@hand CRBM System</a></p>
	</td></tr>
</tfoot>
<tbody>
EOH;

	$content .= $this->body;
	
	$content .= <<<EOH
</tbody>
</table>
</body>
</html>
EOH;
		
		$filename = $this->formatter->create_filename();
		$output = array(
			'format' => 'string', 'data' => $content, 'filename' => "$filename.html",
			'mimetype' => 'text/html', 'charset' => $this->charset,
		);
		return $output;
	}
}

?>

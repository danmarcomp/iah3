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

require_once('modules/Reports/Report.php');
require_once('modules/Reports/ReportTemplate.php');

global $sugar_version;

$report = new Report();
$report->retrieve($_REQUEST['record']);

$tpl = new ReportTemplate();

$data = array();

foreach($tpl->template_fields as $f) {
	if(array_key_exists($f, $tpl->serialized_fields)) {
		$f_arr = $tpl->serialized_fields[$f];
		if($f == 'report_fields') $f = 'fields';
		$data[$f] = $report->$f_arr;
	}
	else
		$data[$f] = $report->$f;
}

$fld_allow = array(
	'name', 'display', 'vname', 'vname_module', 'display_name', 'width', 'sort_order', 'summations'
);
foreach(array_keys($data['fields']) as $fld) {
	foreach(array_keys($data['fields'][$fld]) as $f)
		if(!in_array($f, $fld_allow)) unset($data['fields'][$fld][$f]);
}

$name = preg_replace('~[^A-Za-z0-9\-.]~', '_', $report->name);

ob_start();
echo "<?php\r\n\r\n";
echo "\$report_template = ";
echo var_export_helper($data);
echo ";\r\n\r\n?>";
$content = ob_get_contents();
ob_end_clean();

header('Content-Type: application/x-httpd-php-source');
header("Content-Disposition: inline; filename={$name}.php");
header("Pragma: cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header("Cache-Control: post-check=0, pre-check=0", false );
header('Content-Length: '.strlen($content));
echo $content;

?>

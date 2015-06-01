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

if(! isset($_REQUEST['record']))
	sugar_die('Error: the record parameter is required');
	
$record = $_REQUEST['record'];
$report = new Report();
if(! $report->retrieve($record))
	sugar_die('Error: could not retrieve the specified report.');

if($report->xml_engine == 'ReportQuery') {
	header("Pragma: cache");
	header("Content-Disposition: inline; filename={$_REQUEST['module']}.csv");
	header("Content-Type: text/csv; charset=UTF-8");
	header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
	header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
	header( "Cache-Control: post-check=0, pre-check=0", false );
	header("Content-Length: ".strlen($content));

	include('modules/Reports/ReportQuery.php');
}
else
	// don't quite know how
	sugar_die('Error: cannot export the requested report.');

?>
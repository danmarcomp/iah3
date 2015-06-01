<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point'); 
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
$predefined_charts = array(


	// longreach - added
	'Chart_fiscal_year_booked' =>
		array('type'=>'code', 'id'=>'Chart_fiscal_year_booked', 'label'=>'LBL_FISCAL_YEAR_SALES'),
	// longreach - added
	'Chart_pipeline_by_month' =>
		array('type'=>'code', 'id'=>'Chart_pipeline_by_month', 'label'=>'LBL_PIPELINE_MONTHS_TITLE_FULL'),
	'Chart_won_opportunities' =>
		array('type'=>'code', 'id'=>'Chart_won_opportunities', 'label'=>'LBL_WON_OPPS_TITLE'),


	'Chart_pipeline_by_sales_stage'=>
	array('type'=>'code','id'=>'Chart_pipeline_by_sales_stage','label'=>'LBL_SALES_STAGE_FORM_TITLE'),
	'Chart_lead_source_by_outcome'=>
	array('type'=>'code','id'=>'Chart_lead_source_by_outcome','label'=>'LBL_LEAD_SOURCE_BY_OUTCOME'),
	'Chart_outcome_by_month'=>
	array('type'=>'code','id'=>'Chart_outcome_by_month','label'=>'LBL_OUTCOME_BY_MONTH_TITLE'),
	'Chart_pipeline_by_lead_source'=>
	array('type'=>'code','id'=>'Chart_pipeline_by_lead_source','label'=>'LBL_LEAD_SOURCE_FORM_TITLE'),
	
	
	// longreach - start added
	'Chart_project_revenue_by_month' =>
		array('type'=>'code', 'id'=>'Chart_project_revenue_by_month', 'label'=>'LBL_PROJECT_PHASE_FORM_TITLE'),
	
	'Cases_by_Status_by_User' => 
		array('type'=>'report', 'id'=>md5('cases_by_status_by_user'), 'label'=>'Cases by Status by User'),
	'Cases_by_Month_by_Priority' => 
		array('type'=>'report', 'id'=>md5('cases_by_month_by_priority'), 'label'=>'Cases by Month by Priority'),
	'Bugs_by_Status_by_User' => 
		array('type'=>'report', 'id'=>md5('bugs_by_status_by_user'), 'label'=>'Bugs by Status by User'),
	'Bugs_by_Month_by_Priority' => 
		array('type'=>'report', 'id'=>md5('bugs_by_month_by_priority'), 'label'=>'Bugs by Month by Priority'),
	
	'Invoiced_Sales_Report' =>
		array('type'=>'report', 'id'=>md5('invoiced_sales_report'), 'label'=>'Invoiced Sales Report'),
	// longreach - end added
                );


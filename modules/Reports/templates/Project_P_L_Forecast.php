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
?><?php return; /* no output */ ?>

detail
	name: LBL_STD_REPORT_PROJECT_FORECAST
	run_method: scheduled
	run_interval: 1
	run_interval_unit: months
	chart_type: hbar
	chart_title: LBL_STD_CHART_TITLE_2_GROUP_TOTAL
	chart_rollover: LBL_STD_CHART_ROLL_2_GROUP
	chart_series: project_financials.actual_cost_usd:sum
	primary_module: Project
sources
	--
		name: proj
		display: primary
		module: Project
		bean_name: Project
		required: 1
	--
		name: project_financials
		display: joined
		module: Project
		bean_name: ProjectFinancials
		parent: proj
		vname: LBL_PROJECT_FINANCIALS
		vname_module: Project
		field_name: project_financials
		required: 1
columns
	--
		field: period
		vname: LBL_FORMAT_FQTR
		vname_module: Project
		label: Fiscal Quarter
		grouped: 1
		format: fqtr
		source: project_financials
	--
		field: period
		vname: LBL_FORMAT_MONTH
		vname_module: Project
		label: Month
		grouped: 1
		format: month
		source: project_financials
	--
		field: actual_cost_usd
		total: sum
		vname: LBL_FIELD_TOTAL_COST
		label: Total Cost
		source: project_financials
	--
		field: actual_revenue_usd
		total: sum
		vname: LBL_FIELD_TOTAL_REVENUE
		label: Total Revenue
		source: project_financials
	--
		field: expected_cost_usd
		total: sum
		vname: LBL_FIELD_TOTAL_EXP_COST
		label: Total Expected Cost
		source: project_financials
	--
		field: expected_revenue_usd
		total: sum
		vname: LBL_FIELD_TOTAL_EXP_REVENUE
		label: Total Expected Revenue
		source: project_financials
filters
	--
		field: period
		source: project_financials
		operator: period
		hidden: 1
sort_order

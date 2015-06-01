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
	name: LBL_STD_REPORT_PROJECT_PROFITABILITY
	run_method: interactive
	primary_module: Project
sources
	--
		name: proj
		display: primary
		module: Project
		bean_name: Project
		required: 1
	--
		name: projF
		display: nested
		module: Project
		bean_name: ProjectFinancials
		parent: proj
		vname: LBL_PROJECT_FINANCIALS
		vname_module: Project
		field_name: project_financials
		required: 1
columns
	--
		field: expected_revenue
		total: sum
		vname: LBL_FIELD_TOTAL_EXPECTED_REVENUE
		label: Total Expected Revenue
		source: projF
	--
		field: expected_profit
		total: sum
		vname: LBL_FIELD_TOTAL_EXPECTED_PROFIT
		label: Total Expected Profit
		source: projF
	--
		field: actual_revenue
		total: sum
		vname: LBL_FIELD_TOTAL_ACTUAL_REVENUE
		label: Total Actual Revenue
		source: projF
	--
		field: actual_profit
		total: sum
		vname: LBL_FIELD_TOTAL_ACTUAL_PROFIT
		label: Total Actual Profit
		source: projF
	--
		field: account
		vname: LBL_ACCOUNT_NAME
		vname_module: Project
		source: proj
	--
		field: name
		vname: LBL_NAME
		vname_module: Project
		source: proj
	--
		field: project_phase
		vname: LBL_PROJECT_PHASE
		vname_module: Project
		source: proj
	--
		field: date_starting
		vname: LBL_DATE_STARTING
		vname_module: Project
		source: proj
	--
		field: assigned_user
		vname: LBL_ASSIGNED_USER
		vname_module: Project
		source: proj
	--
		field: period
		vname: LBL_PERIOD
		vname_module: Project
		source: projF
	--
		field: expected_cost
		vname: LBL_EXPECTED_COST
		vname_module: Project
		source: projF
	--
		field: actual_cost
		vname: LBL_ACTUAL_COST
		vname_module: Project
		source: projF
filters
	--
		field: project_phase
		operator: eq
		value
			- Active - Starting Soon
			- Active - In Progress
			- Closed - Complete
			- Closed - Not Pursued
			- Closed - Terminated
	--
		field: date_starting
		operator: after_date
sort_order
	--
		field: account
	--
		field: name

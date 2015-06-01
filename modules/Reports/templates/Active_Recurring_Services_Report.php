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
	name: LBL_STD_REPORT_ACTIVE_MONTHLY_SERVICES
	primary_module: MonthlyServices
	run_method: interactive
	chart_series: ""
sources
	--
		name: monser
		display: primary
		module: MonthlyServices
		bean_name: MonthlyService
		required: 1
columns
	--
		field: instance_number
		vname: LBL_NUMBER
		vname_module: MonthlyServices
		source: monser
	--
		field: frequency
		vname: LBL_FREQUENCY
		vname_module: MonthlyServices
		source: monser
	--
		field: total_sales
		vname: LBL_TOTAL_SALES
		vname_module: MonthlyServices
		source: monser
	--
		field: balance_due
		vname: LBL_BALANCE_DUE
		vname_module: MonthlyServices
		source: monser
	--
		field: start_date
		vname: LBL_START_DATE
		vname_module: MonthlyServices
		source: monser
	--
		field: end_date
		vname: LBL_END_DATE
		vname_module: MonthlyServices
		source: monser
	--
		field: booking_category
		vname: LBL_NAME
		vname_module: BookingCategories
		source: monser
	--
		field: account
		vname: LBL_ACCOUNT
		vname_module: Accounts
		source: monser
	--
		field: total_sales
		total: sum
		source: monser
	--
		field: balance_due
		total: sum
		source: monser
filters
	--
		field: end_date
		operator: after_date
sort_order
	--
		field: booking_category
	--
		field: end_date

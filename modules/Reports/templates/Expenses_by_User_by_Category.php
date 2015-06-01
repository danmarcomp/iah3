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
	name: LBL_STD_REPORT_EXPENSES_USER_CAT
	primary_module: ExpenseReports
	run_method: interactive
	chart_series: ""
sources
	--
		name: expe
		display: primary
		module: ExpenseReports
		bean_name: ExpenseReport
		required: 1
	--
		name: items_link
		display: nested
		module: ExpenseReports
		bean_name: ExpenseItem
		parent: expe
		vname: LBL_LINE_ITEMS
		vname_module: ExpenseReports
		field_name: items_link
		required: 1
	--
		name: category_link
		display: joined
		module: BookingCategories
		bean_name: BookingCategory
		parent: items_link
		vname: LBL_CATEGORY
		vname_module: ExpenseReports
		field_name: category_link
		required: 1
columns
	--
		field: assigned_user
		vname: LBL_ASSIGNED_TO
		vname_module: ExpenseReports
		grouped: 1
		source: expe
	--
		field: name
		vname: LBL_NAME
		vname_module: BookingCategories
		grouped: 1
		source: category_link
	--
		field: date
		vname: LBL_DATE
		vname_module: ExpenseReports
		source: items_link
	--
		field: amount
		total: sum
		source: items_link
filters
	--
		field: date
		source: items_link
		operator: between_dates
	--
		field: split
		source: items_link
		operator: eq
		value: 0
		hidden: true
sort_order
	--
		field: items_link.date

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
	name: LBL_STD_REPORT_OPEN_INVOICES
	run_method: manual
	chart_type: hbar
	chart_title: LBL_STD_CHART_TITLE_1_GROUP_TOTAL
	chart_rollover: LBL_STD_CHART_ROLL_1_GROUP
	chart_series: amount_usdollar:sum
	primary_module: Invoice
sources
	--
		name: invo
		display: primary
		module: Invoice
		bean_name: Invoice
		required: 1
columns
	--
		field: date_entered
		vname: LBL_DATE_ENTERED
		vname_module: Invoice
		grouped: 1
		format: yearmonth
		source: invo
	--
		field: billing_account
		vname: LBL_BILLING_ACCOUNT
		vname_module: Invoice
		source: invo
	--
		field: full_number
		vname: LBL_INVOICE_NUMBER
		vname_module: Invoice
		source: invo
	--
		field: amount_usdollar
		vname: LBL_AMOUNT
		vname_module: Invoice
		source: invo
	--
		field: date_entered
		vname: LBL_DATE_ENTERED
		vname_module: Invoice
		source: invo
	--
		field: terms
		vname: LBL_TERMS
		vname_module: Invoice
		source: invo
	--
		field: due_date
		vname: LBL_DUE_DATE
		vname_module: Invoice
		source: invo
	--
		field: amount_usdollar
		total: sum
		vname: LBL_FIELD_TOTAL_AMOUNT
		label: Total Amount
		source: invo
	--
		field: amount_due_usdollar
		vname: LBL_AMOUNT_DUE
		vname_module: Invoice
		source: invo
filters
	--
		field: amount_due_usdollar
		operator: not_empty
		hidden: 1
		value: 0
	--
		field: cancelled
		operator: eq
		hidden: 1
		value: 0
sort_order
	--
		field: date_entered

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
	name: LBL_STD_REPORT_ACCOUNT_HISTORY
	run_method: manual
	primary_module: Accounts
sources
	--
		name: acco
		display: primary
		module: Accounts
		bean_name: Account
		required: 1
	--
		name: invoice
		display: nested
		module: Invoice
		bean_name: Invoice
		parent: acco
		vname: LBL_INVOICES
		vname_module: Accounts
		field_name: invoice
		required: 1
columns
	--
		field: name
		vname: LBL_FIELD_ACCOUNT_NAME
		vname_module: Accounts
		label: Account Name
		source: acco
	--
		field: full_number
		vname: LBL_INVOICE_NUMBER
		vname_module: Invoice
		source: invoice
	--
		field: name
		vname: LBL_INVOICE_SUBJECT
		vname_module: Invoice
		source: invoice
	--
		field: amount_usdollar
		vname: LBL_AMOUNT
		vname_module: Invoice
		source: invoice
	--
		field: amount_due_usdollar
		vname: LBL_AMOUNT_DUE
		vname_module: Invoice
		source: invoice
	--
		field: terms
		vname: LBL_TERMS
		vname_module: Invoice
		source: invoice
	--
		field: date_entered
		vname: LBL_DATE_ENTERED
		vname_module: Invoice
		format: date_only
		source: invoice
	--
		field: due_date
		vname: LBL_DUE_DATE
		vname_module: Invoice
		source: invoice
	--
		field: balance
		vname: LBL_BALANCE
		vname_module: Accounts
		source: acco
	--
		field: amount_due_usdollar
		total: sum
		vname: LBL_FIELD_TOTAL_OUTSTANDING
		label: Total Outstanding
		source: invoice
	--
		field: amount_usdollar
		total: sum
		vname: LBL_FIELD_TOTAL_BUSINESS
		label: Total Business
		source: invoice
	--
		field: name
		total: count
		source: invoice
filters
	--
		field: cancelled
		source: invoice
		operator: eq
		hidden: 1
		value: 0
sort_order
	--
		field: name
	--
		field: date_entered
		source: invoice

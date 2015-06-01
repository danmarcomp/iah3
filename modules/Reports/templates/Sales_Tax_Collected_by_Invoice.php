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
	name: LBL_STD_REPORT_SALES_TAX_COLLECTED
	run_method: interactive
	primary_module: Invoice
sources
	--
		name: inv
		display: primary
		module: Invoice
		bean_name: Invoice
		required: 1
	--
		name: inv_adj
		display: joined
		module: Invoice
		bean_name: InvoiceAdjustment
		parent: inv
		vname: LBL_INVOICE_ADJUSTMENTS
		vname_module: Invoice
		field_name: adjustments_link
		required: 1
columns
	--
		field: invoice_number
		vname: LBL_INVOICE_NUMBER
		vname_module: Invoice
		source: inv
	--
		field: name
		vname: LBL_NAME
		vname_module: Invoice
		source: inv
	--
		field: billing_account
		vname: LBL_ACCOUNT_NAME
		vname_module: Invoice
		source: inv
	--
		field: date_entered
		vname: LBL_DATE_ENTERED
		vname_module: Invoice
		source: inv
	--
		field: amount
		vname: LBL_AMOUNT
		source: inv
	--
		field: name
		vname: LBL_NAME
		vname_module: Invoice
		source: inv_adj
	--
		field: amount
		vname: LBL_AMOUNT
		source: inv_adj
	--
		field: assigned_user
		vname: LBL_ASSIGNED_USER
		vname_module: Invoice
		source: inv
	--
		field: amount
		total: sum
		vname: LBL_FIELD_TOTAL_AMOUNT
		label: Total Amount
		source: inv
	--
		field: amount
		total: sum
		vname: LBL_FIELD_TOTAL_TAXES_COLLECTED
		label: Total Taxes Collected
		source: inv_adj
filters
	--
		field: related_type
		source: inv_adj
		operator: contains
		value: TaxRates
		hidden: true
	--
		field: date_entered
		operator: between_dates
	--
		field: invoice_number
		operator: between
	--
		field: name
		source: inv_adj
		operator: contains
		value: ""
sort_order
	--
		field: invoice_number

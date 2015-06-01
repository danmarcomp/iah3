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
	name: LBL_STD_REPORT_PAYMENT_HISTORY
	run_method: interactive
	primary_module: Payments
sources
	--
		name: paym
		display: primary
		module: Payments
		bean_name: Payment
		required: 1
	--
		name: account_link
		display: joined
		module: Accounts
		bean_name: Account
		parent: paym
		vname: LBL_ACCOUNT
		vname_module: Payments
		field_name: account_link
		required: 1
columns
	--
		field: payment_id
		vname: LBL_PAYMENT_ID
		vname_module: Payments
		source: paym
	--
		field: name
		vname: LBL_FIELD_ACCOUNT_NAME
		vname_module: Accounts
		label: Account Name
		source: account_link
	--
		field: payment_type
		vname: LBL_PAYMENT_TYPE
		vname_module: Payments
		source: paym
	--
		field: amount_usdollar
		vname: LBL_AMOUNT
		vname_module: Payments
		source: paym
	--
		field: payment_date
		vname: LBL_PAYMENT_DATE
		vname_module: Payments
		source: paym
filters
	--
		field: payment_date
		operator: between_dates
sort_order
	--
		field: payment_date
	--
		field: name
		source: account_link

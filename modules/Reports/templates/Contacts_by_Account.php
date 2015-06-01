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
	name: LBL_STD_REPORT_CONTACTS_ACCOUNT
	run_method: manual
	primary_module: Contacts
sources
	--
		name: Cont
		display: primary
		module: Contacts
		bean_name: Contact
		required: 1
columns
	--
		field: primary_account
		vname: LBL_ACCOUNT_NAME
		vname_module: Contacts
		grouped: 1
		source: Cont
	--
		field: first_name
		vname: LBL_FIRST_NAME
		vname_module: Contacts
		source: Cont
	--
		field: last_name
		vname: LBL_LAST_NAME
		vname_module: Contacts
		source: Cont
	--
		field: primary_address_city
		vname: LBL_FIELD_CITY
		vname_module: Contacts
		label: City
		source: Cont
	--
		field: primary_address_state
		vname: LBL_FIELD_STATE
		vname_module: Contacts
		label: State
		source: Cont
	--
		field: primary_address_country
		vname: LBL_FIELD_COUNTRY
		vname_module: Contacts
		label: Country
		source: Cont
	--
		field: phone_work
		vname: LBL_OFFICE_PHONE
		vname_module: Contacts
		source: Cont
	--
		field: phone_mobile
		vname: LBL_MOBILE_PHONE
		vname_module: Contacts
		source: Cont
	--
		field: email1
		vname: LBL_EMAIL_ADDRESS
		vname_module: Contacts
		source: Cont
filters
sort_order
	--
		field: account_name
	--
		field: last_name

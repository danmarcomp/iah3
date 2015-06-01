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
	name: LBL_STD_REPORT_EVENTREG_LEADS
	primary_module: EventSessions
	run_method: interactive
	chart_series: ""
sources
	--
		name: sessions
		display: primary
		module: EventSessions
		bean_name: EventSession
		required: 1
	--
		name: attendees
		display: joined
		module: EventCustomers
		bean_name: EventCustomer
		vname: LBL_ATTENDEES
		vname_module: EventSessions
		parent: sessions
		field_name: attendees
		required: 1
	--
		name: leads
		display: joined
		module: Leads
		bean_name: Lead
		parent: attendees
		vname: LBL_LEADS
		vname_module: EventsCustomers
		field_name: leads
		required: 1
columns
	--
		field: name
		vname: LBL_NAME
		vname_module: EventSessions
	--
		field: name
		vname: LBL_FULL_NAME
		vname_module: Leads
		label: Lead Name
		source: leads
	--
		field: registered
		vname: LBL_REGISTERED
		vname_module: EventsCustomers
		source: attendees
	--
		field: attended
		vname: LBL_ATTENDED
		vname_module: EventsCustomers
		source: attendees
	--
		field: phone_work
		vname: LBL_OFFICE_PHONE
		vname_module: Leads
		source: leads
	--
		field: email1
		vname: LBL_EMAIL_ADDRESS
		vname_module: Leads
		source: leads
	--
		field: last_name
		total: count
		label: Total Leads
		source: leads
filters
	--
		field: name
		operator: eq
	--
		field: date_start
		operator: after_date
	--
		field: date_end
		operator: before_date
sort_order
	--
		field: attendees.registered
	--
		field: attendees.attended

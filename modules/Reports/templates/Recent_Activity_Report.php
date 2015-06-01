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
	name: LBL_STD_REPORT_RECENT_ACTIVITY
	run_method: interactive
	primary_module: Activities
sources
	--
		name: acti
		display: primary
		module: Activities
		bean_name: Activities
		required: 1
columns
	--
		field: name
		vname: LBL_SUBJECT
		vname_module: Activities
		source: acti
	--
		field: parent
		vname: LBL_LIST_RELATED_TO
		vname_module: Activities
		source: acti
	--
		field: date_start
		vname: LBL_DATE
		vname_module: Activities
		source: acti
	--
		field: status
		vname: LBL_STATUS
		vname_module: Activities
		source: acti
	--
		field: date_modified
		vname: LBL_DATE_MODIFIED
		vname_module: Activities
		source: acti
	--
		field: assigned_user
		vname: LBL_ASSIGNED_TO
		vname_module: Activities
		source: acti
filters
	--
		field: is_private
		operator: eq
		value: 0
		hidden: true
	--
		field: date_modified
		operator: after_date
	--
		field: filter_owner
		value: not_mine
sort_order
	--
		field: date_modified
		order: desc

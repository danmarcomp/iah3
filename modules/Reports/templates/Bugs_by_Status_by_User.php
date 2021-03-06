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
	name: LBL_STD_REPORT_BUGS_STATUS_USER
	run_method: manual
	chart_name: Bugs_by_Status_by_User
	chart_type: hbar
	chart_title: LBL_STD_CHART_TITLE_2_GROUP_TOTAL
	chart_rollover: LBL_STD_CHART_ROLL_BUGS
	chart_series: name:count
	primary_module: Bugs
sources
	--
		name: bugs
		display: primary
		module: Bugs
		bean_name: Bug
		required: 1
columns
	--
		field: status
		vname: LBL_STATUS
		vname_module: Bugs
		grouped: 1
		source: bugs
	--
		field: assigned_user
		vname: LBL_FIELD_USER
		vname_module: Bugs
		label: User
		grouped: 1
		source: bugs
	--
		field: bug_number
		vname: LBL_NUMBER
		vname_module: Bugs
		source: bugs
	--
		field: name
		vname: LBL_LIST_SUBJECT
		vname_module: Bugs
		source: bugs
	--
		field: type
		vname: LBL_TYPE
		vname_module: Bugs
		source: bugs
	--
		field: product_category
		vname: LBL_PRODUCT_CATEGORY
		vname_module: Bugs
		source: bugs
	--
		field: name
		total: count
		source: bugs
filters
sort_order

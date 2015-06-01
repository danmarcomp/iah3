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
	name: LBL_STD_REPORT_ACTIVE_PROJECTS
	run_method: manual
	primary_module: Project
sources
	--
		name: Proj
		display: primary
		module: Project
		bean_name: Project
		required: 1
columns
	--
		field: name
		vname: LBL_PROJECT_NAME
		vname_module: Project
		source: Proj
	--
		field: account
		vname: LBL_ACCOUNT_NAME
		vname_module: Project
		source: Proj
	--
		field: amount_usdollar
		vname: LBL_AMOUNT
		vname_module: Project
		source: Proj
	--
		field: date_starting
		vname: LBL_DATE_STARTING
		vname_module: Project
		source: Proj
	--
		field: project_phase
		vname: LBL_PROJECT_PHASE
		vname_module: Project
		source: Proj
	--
		field: project_type
		vname: LBL_TYPE
		vname_module: Project
		source: Proj
filters
	--
		field: project_phase
		operator: eq
		hidden: 1
		value
			- Active - Starting Soon
			- Active - In Progress
sort_order
	--
		field: account_name

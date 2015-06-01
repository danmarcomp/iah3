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
	name: LBL_STD_REPORT_ACTIVE_PROJECT_TASKS
	run_method: manual
	primary_module: Project
sources
	--
		name: proj
		display: primary
		module: Project
		bean_name: Project
		required: 1
	--
		name: project_tasks
		display: nested
		module: ProjectTask
		bean_name: ProjectTask
		parent: proj
		vname: LBL_PROJECT_TASKS
		vname_module: Project
		field_name: project_tasks
		required: 1
columns
	--
		field: name
		vname: LBL_PROJECT_NAME
		vname_module: Project
		source: proj
	--
		field: project_phase
		vname: LBL_PROJECT_PHASE
		vname_module: Project
		source: proj
	--
		field: project_type
		vname: LBL_TYPE
		vname_module: Project
		source: proj
	--
		field: date_starting
		vname: LBL_DATE_STARTING
		vname_module: Project
		source: proj
	--
		field: date_ending
		vname: LBL_DATE_ENDING
		vname_module: Project
		source: proj
	--
		field: assigned_user
		vname: LBL_ASSIGNED_TO
		vname_module: Project
		source: proj
	--
		field: name
		vname: LBL_FIELD_PROJECT_TASK
		vname_module: ProjectTask
		label: Project Task
		source: project_tasks
	--
		field: status
		vname: LBL_STATUS
		vname_module: ProjectTask
		source: project_tasks
	--
		field: percent_complete
		vname: LBL_PERCENT_COMPLETE
		vname_module: ProjectTask
		source: project_tasks
	--
		field: priority
		vname: LBL_PRIORITY
		vname_module: ProjectTask
		source: project_tasks
	--
		field: date_start
		vname: LBL_DATE_START
		vname_module: ProjectTask
		source: project_tasks
	--
		field: date_due
		vname: LBL_DATE_DUE
		vname_module: ProjectTask
		source: project_tasks
	--
		field: assigned_user
		vname: LBL_ASSIGNED_USER_ID
		vname_module: ProjectTask
		source: project_tasks
filters
	--
		field: status
		source: project_tasks
		operator: eq
		hidden: 1
		value
			- Not Started
			- In Progress
			- Pending Input
sort_order

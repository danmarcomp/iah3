<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Workflow/Workflow.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: true
	comment:
		Workflow is a series of actions registered with certain system activities,
		i.e. new record creation.
	table_name: workflow
	primary_key: id
hooks
	new_record
		--
			class_function: init_record
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	app.deleted
	name
		type: name
		vname: LBL_NAME
		comment: Name of the workflow
		unified_search: true
		audited: true
		vname_list: LBL_LIST_NAME
		required: true
	description
		vname: LBL_DESCRIPTION
		type: text
		comment: Descriptive information about the workflow
	trigger_module
		vname: LBL_TRIGGER_MODULE
		type: module_name
		len: 50
		width: 20
		comment: Workflow target module
		vname_list: LBL_LIST_TRIGGER_MODULE
		required: true
		options_function
			- Workflow
			- get_target_options
		options_add_blank: false
		massupdate: true
	trigger_action
		vname: LBL_TRIGGER_ACTION
		type: enum
		options: workflow_trigger_dom
		len: 25
		comment: The action on target module that triggers workflow
		vname_list: LBL_LIST_TRIGGER_ACTION
		massupdate: true
	trigger_value
		vname: LBL_TRIGGER_VALUE
		type: varchar
		comment: The value for changed_to and changed_from actions
	execute_mode
		vname: LBL_EXECUTE_MODE
		type: enum
		options: workflow_execute_mode_dom
		len: 30
		comment:
			Workflow execute mode: new records only or new record and
			existing records
		required: true
		massupdate: true
	occurs_when
		vname: LBL_OCCURS_WHEN
		type: enum
		len: 25
		options: workflow_occurs_dom
		required: true
		massupdate: true
	status
		vname: LBL_STATUS
		type: enum
		len: 10
		default: inactive
		options: workflow_status_dom
		comment: Workflow status: active or inactive
		vname_list: LBL_LIST_STATUS
		required: true
		massupdate: true
	operation_mode
		vname: LBL_OPERATION_MODE
		type: varchar
		comment: Operation mode: immediately or in given time period
	operation_days
		vname: LBL_OPERATION_DAYS
		type: varchar
		comment: Operation performed in days
	operation_hours
		vname: LBL_OPERATION_HOURS
		type: varchar
		comment: Operation performed in hours
	operation_mins
		vname: LBL_OPERATION_MINS
		type: varchar
		comment: Operation performed in minutes
	operation_owner
		vname: LBL_OPERATIONS_AS
		type: enum
		len: 30
		options: workflow_execute_dom
		comment: Operation owner: the trigger or admin
		required: true
		massupdate: true
links
	criteria
		relationship: criteria
		module: Workflow
		bean_name: WFConditionCriteria
		vname: LBL_COND
	operations
		relationship: operations
		module: Workflow
		bean_name: WFOperation
		vname: LBL_COND
fields_compat
	assigned_user_name
		vname: LBL_ASSIGNED_TO_NAME
		type: varchar
		reportable: false
		source: nondb
		table: users
		id_name: assigned_user_id
		module: Users
		duplicate_merge: disabled
		importable: false
relationships
	criteria
		key: id
		target_bean: Workflow
		target_key: workflow_id
		relationship_type: one-to-many
	operations
		key: id
		target_bean: Workflow
		target_key: workflow_id
		relationship_type: one-to-many

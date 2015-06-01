<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Workflow/WFConditionCriteria.php
	audit_enabled: true
	comment: Condition criteria is used to filter workflow trigger events.
	table_name: workflow_criteria
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	app.deleted
	workflow
		required: true
		type: ref
		bean_name: Workflow
	field_name
		type: varchar
		vname: LBL_CRITERIA_FIELD
		comment: Name of the field
		unified_search: true
		audited: true
	field_value
		vname: LBL_CRITERIA_VALUE
		type: varchar
		comment: Value of the field
	operator
		vname: LBL_OPERATOR
		type: varchar
		comment: Criteria operator
	time_interval
		vname: LBL_TIME
		type: varchar
		len: 10
	glue
		type: varchar
		len: 10
	level
		type: int
	idx
		type: int
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

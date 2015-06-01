<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Workflow/WFOperation.php
	audit_enabled: true
	comment: Workflow operations: email, invites, data manipulations, SMS, etc.
	table_name: workflow_operation
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
	operation_type
		vname: LBL_OP_TYPE
		type: varchar
		required: true
	performed_before_event
		vname: LBL_OP_BEFORE
		type: varchar
		required: true
	include_initiator
		type: bool
	include_contact
		type: bool
	include_primary_email
		type: bool
	include_secondary_email
		type: bool
	notification_invitee_ids
		type: text
	notification_invitee_names
		type: text
	notification_invitee_types
		type: text
	notification_subject
		vname: LBL_NOTIFICATION_SUBJECT
		type: varchar
		comment: Notification subject
	notification_content
		vname: LBL_NOTIFICATION_CONTENT
		type: text
		comment: Notification content
	notification_from_name
		vname: LBL_NOTIFICATION_FROM_NAME
		type: varchar
		len: 50
		comment: Notification FROM name
	notification_from_email
		vname: LBL_NOTIFICATION_FROM_email
		type: varchar
		len: 70
		comment: Notification FROM email
	notification_status
		vname: LBL_NOTIFICATION_STATUS
		type: varchar
	notification_direction
		vname: LBL_NOTIFICATION_DIRECTION
		type: varchar
	notification_start_date
		vname: LBL_NOTIFICATION_TIME_START
		type: varchar
	notification_start_date_choice
		vname: LBL_NOTIFICATION_TIME_START
		type: varchar
	notification_start_time_hour
		type: varchar
	notification_start_time_min
		type: varchar
	notification_duration_hour
		vname: LBL_NOTIFICATION_DURATION
		type: varchar
	notification_duration_min
		type: varchar
	notification_cc_mailbox
		type: id
	dm_link_name
		vname: LBL_LINK_NAME
		type: varchar
	dm_module_name
		vname: LBL_MODULE_NAME
		type: varchar
		comment: Manipulating target module
	dm_field_name
		vname: LBL_FIELD_NAME
		type: varchar
		comment: Manipulating target field
	dm_field_value
		vname: LBL_FIELD_VALUE
		type: varchar
		comment: Manipulating value
	task_due_date
		type: varchar
	task_due_date_choice
		type: varchar
	task_due_time_hour
		type: varchar
	task_due_time_min
		type: varchar
	task_due_date_flag
		type: varchar
	task_start_date_flag
		type: varchar
	task_status
		type: varchar
	task_priority
		type: varchar
	task_contact_id
		type: varchar
	task_contact_name
		type: varchar
	task_est_effort
		type: varchar
	task_est_effort_unit
		type: varchar
	display_order
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

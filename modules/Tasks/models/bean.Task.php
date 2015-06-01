<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Tasks/Task.php
	unified_search: true
	activity_log_enabled: true
	duplicate_merge: false
	optimistic_locking: true
	table_name: tasks
	primary_key: id
	importable: LBL_TASKS
	reportable: true
hooks
	new_record
		--
			class_function: init_record
		--
			class_function: init_from_email
	notify
		--
			class_function: send_notification
	after_save
		--
			class_function: update_parent_activity
		--
			class_function: set_related
		--
			class_function: clear_vcal
	after_delete
		--
			class_function: clear_vcal
fields
	app.id
	app.date_entered
	app.date_modified
	app.assigned_user
	app.modified_user
	app.created_by_user
	app.deleted
	effort_estim
		vname: LBL_EFFORT_ESTIM
		type: duration
		len: 4
	effort_estim_unit
		vname: LBL_EFFORT_ESTIM_UNIT
		type: char
		len: 20
	effort_actual
		vname: LBL_EFFORT_ACTUAL
		type: duration
		len: 4
	effort_actual_unit
		vname: LBL_EFFORT_ACTUAL_UNIT
		type: char
		len: 20
	is_private
		vname: LBL_IS_PRIVATE
		type: bool
		massupdate: true
	name
		vname: LBL_SUBJECT
		type: name
		len: 150
		vname_list: LBL_LIST_SUBJECT
		unified_search: true
		required: true
	status
		vname: LBL_STATUS
		type: enum
		options: task_status_dom
		len: 25
		vname_list: LBL_LIST_STATUS
		default: Not Started
		required: true
		massupdate: true
	date_due
		vname: LBL_DUE_DATE_AND_TIME
		type: datetime
		vname_list: LBL_LIST_DUE_DATE
		massupdate: true
	date_start
		vname: LBL_START_DATE_AND_TIME
		type: datetime
		vname_list: LBL_LIST_START_DATE
		massupdate: true
	parent_type
		vname: LBL_RELATED_TYPE
		type: module_name
		reportable: false
		default: Accounts
		options
			- Accounts
			- Opportunities
			- Cases
			- Leads
			- Bugs
			- Project
			- Quotes
			- Invoice
			- PurchaseOrders
			- SalesOrders
			- Emails
			- ProjectTask
			- Tasks
			- Contacts
			- Prospects
	parent
		vname: LBL_RELATED_TO
		vname_list: LBL_LIST_RELATED_TO
		type: ref
		dynamic_module: parent_type
		massupdate: true
	contact
		vname: LBL_CONTACT
		type: ref
		bean_name: Contact
		add_filters
			--
				param: primary_account
				field_name: parent
		massupdate: true
	contact_phone
		vname: LBL_PHONE
		type: phone
		source
			type: field
			field: contact.phone_work
	contact_email
		vname: LBL_EMAIL
		type: email
		source
			type: field
			field: contact.email1
	priority
		vname: LBL_PRIORITY
		type: enum
		options: task_priority_dom
		len: 25
		vname_list: LBL_LIST_PRIORITY
		default: P1
		required: true
		massupdate: true
	description
		vname: LBL_DESCRIPTION
		type: text
links
	emails
		relationship: emails_tasks_rel
		side: right
		vname: LNK_EMAIL_LIST
	contacts
		relationship: contact_tasks
		link_type: one
		vname: LBL_CONTACT
	accounts
		relationship: account_tasks
		vname: LBL_ACCOUNT
	opportunities
		relationship: opportunity_tasks
		vname: LBL_TASKS
	calls
		relationship: task_calls
		module: Calls
		bean_name: Call
		vname: LNK_CALL_LIST
	app.securitygroups
		relationship: securitygroups_tasks
indices
	idx_tsk_name
		fields
			- name
	idx_task_con_del
		fields
			- contact_id
			- deleted
	idx_task_par_del
		fields
			- parent_id
			- parent_type
			- deleted
	idx_task_assigned
		fields
			- assigned_user_id
relationships
	task_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: Tasks
		relationship_type: one-to-many

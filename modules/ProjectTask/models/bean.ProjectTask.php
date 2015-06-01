<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ProjectTask/ProjectTask.php
	audit_enabled: true
	unified_search: true
	activity_log_enabled: true
	duplicate_merge: false
	table_name: project_task
	primary_key: id
	default_order_by: order_number
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	before_save
		--
			class_function: preupdate_data
	after_save
		--
			class_function: recalc_financial
		--
			class_function: update_assigned_user
	after_delete
		--
			class_function: recalc_financial
	notify
		--
			class_function: send_notification
			required_fields
				--
					name: parent
fields
	app.id
	app.date_entered
	app.date_modified
	app.assigned_user
	app.modified_user
	app.created_by_user
	app.currency
	app.deleted
	portal_flag
		vname: LBL_PORTAL_FLAG
		type: bool
		required: true
		default: 0
		reportable: false
		massupdate: true
	name
		vname: LBL_NAME
		required: true
		type: name
		len: 150
		unified_search: true
		vname_list: LBL_LIST_NAME
	status
		vname: LBL_STATUS
		type: enum
		options: project_task_status_options
		audited: true
		len: 20
		vname_list: LBL_LIST_STATUS
		required: true
		default: Not Started
		massupdate: true
	date_due
		vname: LBL_DATE_DUE
		type: datetime
		audited: true
		required: true
		vname_list: LBL_LIST_DATE_DUE
		format: date_only
		massupdate: true
	date_start
		vname: LBL_DATE_START
		type: datetime
		validation
			type: isbefore
			compareto: date_due
			blank: true
		audited: true
		required: true
		format: date_only
		massupdate: true
	include_weekends
		type: bool
		default: 0
		vname: LBL_INCLUDE_WEEKENDS
		massupdate: true
	parent
		vname: LBL_PROJECT
		required: true
		type: ref
		bean_name: Project
		massupdate: true
	priority
		vname: LBL_PRIORITY
		type: enum
		options: project_task_priority_options
		len: 20
		default: P1
		vname_list: LBL_LIST_PRIORITY
		massupdate: true
	description
		vname: LBL_DESCRIPTION
		type: text
	order_number
		vname: LBL_ORDER_NUMBER
		type: int
		default: 1
		vname_list: LBL_LIST_ORDER_NUMBER
	task_number
		vname: LBL_TASK_NUMBER
		type: int
	depends_on
		vname: LBL_DEPENDS_ON
		type: ref
		bean_name: ProjectTask
		massupdate: true
	dependency_type
		vname: LBL_DEPENDENCY_TYPE
		type: enum
		options: project_task_dependency_type
		len: 10
		default: FTS
		massupdate: true
	milestone_flag
		vname: LBL_MILESTONE_FLAG
		type: bool
		default: 0
		massupdate: false
	percent_complete
		vname: LBL_PERCENT_COMPLETE
		default: 0
		type: int
		audited: true
links
	activities
		relationship: project_tasks_activities
		module: Activities
		bean_name: Activity
		vname: LBL_ACTIVITIES_SUBPANEL_TITLE
	history
		relationship: project_tasks_history
		module: Activities
		bean_name: ActivityHistory
		layout: History
		vname: LBL_HISTORY_SUBPANEL_TITLE
	project_link
		vname: LBL_PROJECT
		link_type: one
		side: left
		relationship: project_project_tasks
		massupdate: false
	booking_users
		relationship: projecttasks_users
		vname: LBL_BOOKING_USERS_SUBPANEL_TITLE
		layout: ProjectTask
	booked_hours
		relationship: booked_hours_projecttask
		link_type: one
		side: right
		layout: Quick
		vname: LBL_BOOKED_HOURS
	notes
		relationship: project_task_notes
		vname: LBL_NOTES
	meetings
		relationship: project_task_meetings
		vname: LBL_MEETINGS
	calls
		relationship: project_task_calls
		vname: LBL_CALLS
	emails
		relationship: project_task_emails
		side: right
		vname: LBL_EMAILS
	tasks
		relationship: project_task_tasks
		side: right
		vname: LBL_TASKS
	depends_link
		relationship: project_task_depends
		vname: LBL_DEPENDS_ON
		link_type: one
		side: right
		module: ProjectTask
		bean_name: ProjectTask
	app.securitygroups
		relationship: securitygroups_project_task
relationships
	project_task_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: ProjectTask
		relationship_type: one-to-many
	project_task_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: ProjectTask
		relationship_type: one-to-many
	project_task_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: ProjectTask
		relationship_type: one-to-many
	project_task_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: ProjectTask
		relationship_type: one-to-many
	project_task_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: ProjectTask
		relationship_type: one-to-many
	project_task_depends
		key: id
		target_bean: ProjectTask
		target_key: depends_on_id
		relationship_type: one-to-many
	project_tasks_activities
		target_bean: Activity
		target_key: id
		sources
			meetings
				relationship: project_task_meetings
			calls
				relationship: project_task_calls
			tasks
				relationship: project_task_tasks
	project_tasks_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: project_task_meetings
			calls
				relationship: project_task_calls
			notes
				relationship: project_task_notes
			tasks
				relationship: project_task_tasks
			emails
				relationship: emails_project_task_rel

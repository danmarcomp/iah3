<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Bugs/Bug.php
	audit_enabled: true
	unified_search: true
	activity_log_enabled: true
	duplicate_merge: true
	optimistic_locking: true
	comment: Bugs are defects in products and services
	table_name: bugs
	primary_key: id
	display_name
		type: prefixed
		fields
			- bug_number
			- name
	reportable: true
hooks
	new_record
		--
			class_function: init_record
		--
			class_function: init_from_email
	before_save
		--
			class_function: before_save
	after_save
		--
			class_function: after_save
	notify
		--
			class_function: send_notification
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.deleted
	app.created_by_user
	bug_number
		vname: LBL_NUMBER
		type: sequence_number
		sequence_name: bug_number_sequence
		required: true
		unified_search: true
		comment: Visual unique identifier
		duplicate_merge: disabled
		vname_list: LBL_LIST_NUMBER
		editable: false
	is_private
		vname: LBL_IS_PRIVATE
		type: bool
		required: true
		default: 0
		massupdate: true
	name
		vname: LBL_LIST_SUBJECT
		type: name
		len: 255
		audited: true
		unified_search: true
		comment: The short description of the bug
		merge_filter: selected
		required: true
		multiline: true
		width: 60
		height: 2
	account
		vname: LBL_PRIMARY_ACCOUNT
		type: ref
		bean_name: Account
		massupdate: true
	contact
		vname: LBL_PRIMARY_CONTACT
		type: ref
		bean_name: Contact
		massupdate: true
	product
		vname: LBL_PRODUCT
		type: ref
		massupdate: true
		bean_name: SoftwareProduct
	status
		vname: LBL_STATUS
		type: enum
		options: bug_status_dom
		len: 25
		audited: true
		comment: The status of the bug
		merge_filter: enabled
		vname_list: LBL_LIST_STATUS
		default: New
		required: true
		massupdate: true
	found_in
		id_name: found_in_release
		type: ref
		bean_name: Release
		vname: LBL_FOUND_IN_RELEASE
		vname_list: LBL_LIST_RELEASE
		comment: The software or service release that manifested the bug
		duplicate_merge: disabled
		massupdate: true
	priority
		vname: LBL_PRIORITY
		type: enum
		options: bug_priority_dom
		len: 25
		audited: true
		comment: An indication of the priorty of the bug
		merge_filter: enabled
		vname_list: LBL_LIST_PRIORITY
		default: P1
		required: true
		massupdate: true
	planned_for
		id_name: planned_for_release
		type: ref
		bean_name: Release
		vname: LBL_PLANNED_FOR_RELEASE
		vname_list: LBL_LIST_PLANNED_FOR_RELEASE
		massupdate: true
	type
		vname: LBL_TYPE
		type: enum
		options: bug_type_dom
		comment: The type of bug (ex: bug, feature)
		merge_filter: enabled
		vname_list: LBL_LIST_TYPE
		default: Defect
		required: true
		massupdate: true
	fixed_in
		id_name: fixed_in_release
		type: ref
		bean_name: Release
		vname: LBL_FIXED_IN_RELEASE
		vname_list: LBL_LIST_FIXED_IN_RELEASE
		comment: The software or service release that corrected the bug
		duplicate_merge: disabled
		massupdate: true
	description
		vname: LBL_DESCRIPTION
		type: text
		comment: A full description of the bug
	product_category
		vname: LBL_PRODUCT_CATEGORY
		type: enum
		options: product_category_dom
		comment: Where the bug was discovered (ex: Accounts, Contacts, Leads)
		massupdate: true
	resolution
		vname: LBL_RESOLUTION
		type: enum
		options: bug_resolution_dom
		audited: true
		comment: An indication of how the bug was resolved
		merge_filter: enabled
		vname_list: LBL_LIST_RESOLUTION
		massupdate: true
	work_log
		vname: LBL_WORK_LOG
		type: text
		comment: Free-form text used to denote activities of interest
	source
		vname: LBL_SOURCE
		type: enum
		options: source_dom
		comment:
			An indicator of how the bug was entered (ex: via web, email,
			etc.)
		massupdate: true
links
	activities
		relationship: bugs_activities
		module: Activities
		bean_name: Activity
		vname: LBL_ACTIVITIES_SUBPANEL_TITLE
	history
		relationship: bugs_history
		module: Activities
		bean_name: ActivityHistory
		layout: History
		vname: LBL_HISTORY_SUBPANEL_TITLE
	threads
		relationship: bugs_threads
		module: threads
		bean_name: Thread
		vname: LBL_THREADS
	tasks
		relationship: bug_tasks
		vname: LBL_TASKS
	notes
		relationship: bug_notes
		vname: LBL_NOTES
	meetings
		relationship: bug_meetings
		vname: LBL_MEETINGS
	calls
		relationship: bug_calls
		vname: LBL_CALLS
	emails
		relationship: emails_bugs_rel
		side: right
		vname: LBL_EMAILS
	contacts
		relationship: contacts_bugs
		vname: LBL_CONTACTS
	accounts
		relationship: accounts_bugs
		vname: LBL_ACCOUNTS
	cases
		relationship: cases_bugs
		vname: LBL_CASES
	app.securitygroups
		relationship: securitygroups_bugs
indices
	bug_number
		fields
			- bug_number
	idx_bug_name
		fields
			- name
relationships
	bug_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: Bugs
		relationship_type: one-to-many
	bug_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: Bugs
		relationship_type: one-to-many
	bug_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: Bugs
		relationship_type: one-to-many
	bug_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: Bugs
		relationship_type: one-to-many
	bug_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Bugs
		relationship_type: one-to-many
	bugs_assigned_user
		key: assigned_user_id
		target_bean: User
		target_key: id
		relationship_type: one-to-many
	bugs_modified_user
		key: modified_user_id
		target_bean: User
		target_key: id
		relationship_type: one-to-many
	bugs_created_by
		key: created_by
		target_bean: User
		target_key: id
		relationship_type: one-to-many
	bugs_release
		key: found_in_release
		target_bean: Release
		target_key: id
		relationship_type: one-to-many
	bugs_fixed_in_release
		key: fixed_in_release
		target_bean: Release
		target_key: id
		relationship_type: one-to-many
	bugs_planned_release
		key: planned_for_release
		target_bean: Release
		target_key: id
		relationship_type: one-to-many
	bugs_threads
		key: id
		target_bean: Thread
		target_key: id
		join
			table: bugs_threads
			source_key: bug_id
			target_key: thread_id
		relationship_type: many-to-many
	bugs_activities
		target_bean: Activity
		target_key: id
		sources
			meetings
				relationship: bug_meetings
			calls
				relationship: bug_calls
			tasks
				relationship: bug_tasks
	bugs_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: bug_meetings
			calls
				relationship: bug_calls
			tasks
				relationship: bug_tasks
			notes
				relationship: bug_notes
			emails
				relationship: emails_bugs_rel

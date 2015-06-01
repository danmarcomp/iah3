<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Meetings/Meeting.php
	unified_search
		enabled: true
		default: false
	activity_log_enabled: true
	duplicate_merge: false
	optimistic_locking: true
	comment: Meeting activities
	table_name: meetings
	primary_key: id
	importable: LBL_MEETINGS
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	before_save
		--
			class_function: before_save
	after_save
		--
			class_function: after_save
		--
			class_function: update_parent_activity
		--
			class_function: update_invitees
	notify_on
		--
			class_function: notify_on
	after_delete
		--
			class_function: after_delete
	after_add_link
		--
			class_function: update_user_vcal
	after_remove_link
		--
			class_function: update_user_vcal
fields
	app.id
	app.date_entered
	app.date_modified
	app.assigned_user
	app.modified_user
	app.created_by_user
	app.recurrence_fields
	app.deleted
	is_publish
		vname: LBL_PUBLISH_SETTING
		type: bool
		massupdate: false
		reportable: false
		default: 1
	is_daylong
		vname: LBL_IS_DAYLONG
		type: bool
		massupdate: false
		reportable: false
	is_private
		vname: LBL_IS_PRIVATE
		type: bool
		massupdate: true
	name
		vname: LBL_SUBJECT
		type: name
		len: 150
		comment: Meeting name
		unified_search: true
		vname_list: LBL_LIST_SUBJECT
		required: true
		default: ""
	location
		vname: LBL_LOCATION
		type: varchar
		len: 50
		comment: Meeting location
	duration
		vname: LBL_DURATION
		type: duration
		comment: Meeting duration in minutes
		all_day_flag: is_daylong
	date_start
		vname: LBL_DATE_TIME
		type: datetime
		comment: Date of start of meeting
		vname_list: LBL_LIST_DATE
		massupdate: true
	date_end
		vname: LBL_DATE_END
		type: date
		massupdate: false
		comment: Date meeting ends
	parent_type
		vname: LBL_RELATED_TYPE
		type: module_name
		reportable: false
		comment: Module meeting is associated with
		options
			- Accounts
			- Contacts
			- Opportunities
			- Leads
			- Prospects
			- Emails
			- Tasks
			- Project
			- ProjectTask
			- Bugs
			- Cases
			- Quotes
			- Invoice
			- PurchaseOrders
			- SalesOrders
		default: Accounts
	status
		vname: LBL_STATUS
		type: enum
		len: 25
		width: 10
		options: meeting_status_dom
		comment: Meeting status (ex: Planned, Held, Not held)
		vname_list: LBL_LIST_STATUS
		required: true
		default: Planned
		massupdate: true
	parent
		vname: LBL_LIST_RELATED_TO
		type: ref
		comment: Item indicated by parent_type
		dynamic_module: parent_type
		massupdate: true
	description
		vname: LBL_DESCRIPTION
		type: text
		comment: Full description of meeting
		unified_search: true
	reminder_time
		vname: LBL_REMINDER
		type: reminder
		default: -1
		comment:
			Specifies when a reminder alert should be issued; -1 means
			no alert; otherwise the number of seconds prior to the start
	email_reminder_time
		vname: LBL_EMAIL_REMINDER
		type: reminder
		default: -1
		len: 4
		comment:
			Specifies when a reminder email should be sent; -1 means no
			email; otherwise the number of seconds prior to the start
	outlook_id
		vname: LBL_OUTLOOK_ID
		type: varchar
		len: 255
		reportable: false
		comment:
			When the Sugar Plug-in for Microsoft Outlook syncs an
			Outlook appointment, this is the Outlook appointment item ID
links
	resources
		relationship: meetings_resources
		vname: LBL_RESOURCES
	contacts
		relationship: meetings_contacts
		vname: LBL_CONTACTS
	users
		relationship: meetings_users
		vname: LBL_USERS
	invitees
		relationship: meetings_invitees
		vname: LBL_INVITEE
		module: Contacts
		bean_name: Invitees
		layout: Invitees
	account
		relationship: account_meetings
		link_type: one
		vname: LBL_ACCOUNT
	opportunity
		relationship: opportunity_meetings
		link_type: one
		vname: LBL_OPPORTUNITY
	case
		relationship: case_meetings
		link_type: one
		vname: LBL_CASE
	notes
		relationship: meetings_notes
		module: Notes
		bean_name: Note
		vname: LBL_NOTES
	app.securitygroups
		relationship: securitygroups_meetings
indices
	idx_mtg_name
		fields
			- name
	idx_meet_par_del
		fields
			- parent_id
			- parent_type
			- deleted
relationships
	meetings_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Meetings
		relationship_type: one-to-many

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Calls/Call.php
	unified_search: true
	activity_log_enabled: true
	duplicate_merge: false
	optimistic_locking: true
	comment: A Call is an activity representing a phone call
	table_name: calls
	primary_key: id
	importable: LBL_CALLS
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
			class_function: update_parent_activity
		--
			class_function: update_invitees
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
	app.deleted
	is_private
		vname: LBL_IS_PRIVATE
		type: bool
		massupdate: true
	is_publish
		vname: LBL_PUBLISH_SETTING
		type: bool
		massupdate: false
		reportable: false
		default: 1
	name
		vname: LBL_SUBJECT
		type: name
		len: 150
		comment: Brief description of the call
		unified_search: true
		vname_list: LBL_LIST_SUBJECT
		required: true
		default: ""
	duration
		vname: LBL_DURATION
		type: duration
		len: 4
		comment: Call duration in minutes
	date_start
		vname: LBL_DATE_TIME
		type: datetime
		comment: Date in which call is schedule to (or did) start
		vname_list: LBL_LIST_DATE
		massupdate: true
	date_end
		vname: LBL_DATE_END
		type: date
		massupdate: false
		comment: Date is which call is scheduled to (or did) end
	parent_type
		vname: LBL_RELATED_TYPE
		type: module_name
		reportable: false
		comment: The object to which the call is related
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
			- Users
		default: Accounts
	status
		vname: LBL_STATUS
		type: enum
		len: 25
		options: call_status_dom
		comment: The status of the call (Held, Not Held, etc.)
		required: true
		width: 15
		default: Planned
		massupdate: true
	direction
		vname: LBL_DIRECTION
		type: enum
		len: 25
		options: call_direction_dom
		comment: Indicates whether call is inbound or outbound
		vname_list: LBL_LIST_DIRECTION
		default: Outbound
		massupdate: true
	phone_number
		vname: LBL_PHONE_NUMBER
		type: phone
		unified_search: true
		comment: Phone number of the contact
	parent
		vname: LBL_LIST_RELATED_TO
		type: ref
		comment: An item related to the call
		dynamic_module: parent_type
		massupdate: true
	description
		vname: LBL_DESCRIPTION
		type: text
		comment: A full description of the purpose and outcome of call
		unified_search: true
	reminder_time
		vname: LBL_REMINDER
		type: reminder
		default: -1
		len: 4
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
	account
		relationship: account_calls
		link_type: one
		vname: LBL_ACCOUNT
	opportunity
		relationship: opportunity_calls
		link_type: one
		vname: LBL_OPPORTUNITY
	case
		relationship: case_calls
		link_type: one
		vname: LBL_CASE
	accounts
		relationship: account_calls
		module: Accounts
		bean_name: Account
		vname: LBL_ACCOUNTS
	contacts
		relationship: calls_contacts
		vname: LBL_CONTACTS
	users
		relationship: calls_users
		vname: LBL_USERS
	invitees
		relationship: calls_invitees
		vname: LBL_INVITEE
		module: Contacts
		bean_name: Invitees
		layout: Invitees
	notes
		relationship: calls_notes
		module: Notes
		bean_name: Note
		vname: LBL_NOTES
	app.securitygroups
		relationship: securitygroups_calls
indices
	idx_call_name
		fields
			- name
relationships
	calls_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Calls
		relationship_type: one-to-many

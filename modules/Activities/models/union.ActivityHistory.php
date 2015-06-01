<?php return; /* no output */ ?>

detail
	type: union
	primary_key: id
	default_order_by: activity_date desc
	reportable: true
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.assigned_user
	# show module icon in subpanel
	# show 'close' button depending on module
	query_module
		type: module_name
		vname: LBL_MODULE
		source
			type: module_name
		options
			- Calls
			- Meetings
			- Emails
			- Tasks
			- Notes
	name
		type: name
		vname: LBL_SUBJECT
		vname_list: LBL_LIST_SUBJECT
		width: 40
		dynamic_module: query_module
		unified_search: true
	status
		type: enum
		options: activity_status_dom
		vname: LBL_STATUS
		vname_list: LBL_LIST_STATUS
		width: 25
		massupdate: false
		union_source
			notes
				name: status
				type: enum
				options: moduleListSingular
				source
					type: static
					value: Notes
	has_attach
		vname: LBL_HAS_ATTACHMENTS
		type: attach_icon
		union_source
			emails
				name: has_attach
				type: attach_icon
				source
					type: subselect
					class: Email
					function: has_attach
			notes
				name: has_attach
				type: static
				value: 0
				source
					type: non-db
			calls
				name: has_attach
				type: static
				value: 0
				source
					type: non-db
			meetings
				name: has_attach
				type: static
				value: 0
				source
					type: non-db
			tasks
				name: has_attach
				type: static
				value: 0
				source
					type: non-db
	contact
		type: ref
		bean_name: Contact
		vname: LBL_CONTACT
		vname_list: LBL_LIST_CONTACT
		width: 25
	parent
		type: ref
		vname: LBL_RELATED_TO
		vname_list: LBL_LIST_RELATED_TO
		dynamic_module: parent_type
		width: 25
		massupdate: false
	parent_type
		type: module_name
		vname: LBL_RELATED_MODULE
	activity_date
		type: datetime
		vname: LBL_DATE
		vname_list: LBL_LIST_DATE
		width: 20
		massupdate: false
		union_source
			meetings: date_start
			calls: date_start
			tasks: date_due
			emails: date_start
			notes: date_entered
	is_private
		type: bool
		vname: LBL_PRIVATE
		union_source
			emails
				source
					type: static
					value: 0
			notes
				source
					type: static
					value: 0
	description
		type: text
		vname: LBL_DESCRIPTION
		union_source
			emails: body.description
sources
	meetings
		model: Meeting
		filters
			--
				field: status
				value: [Held, Not Held]
	calls
		model: Call
		filters
			--
				field: status
				value: [Held, Not Held]
	tasks
		model: Task
		filters
			--
				field: status
				value: [Completed, Deferred]
	emails
		model: Email
	notes
		model: Note
		filters
			--
				field: email_attachment
				operator: "false"

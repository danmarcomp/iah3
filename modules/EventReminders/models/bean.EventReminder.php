<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/EventReminders/EventReminder.php
	unified_search: false
	duplicate_merge: false
	table_name: event_reminders
	primary_key: id
fields
	app.id
	app.date_entered
	app.created_by_user
	app.date_modified
	app.modified_user
	app.deleted
	session
		type: ref
		vname: LBL_SESSION_NAME
		bean_name: EventSession
		updateable: false
		massupdate: true
	template
		type: ref
		bean_name: EmailTemplate
		id_name: template_id
		vname: LBL_TEMPLATE_ID
		options_function
			- EmailTemplate
			- get_folder_options
		required: false
		massupdate: true
	name
		vname: LBL_ER_NAME
		type: name
		len: 50
		required: true
	description
		vname: LBL_DESCRIPTION
		type: text
	hours
		vname: LBL_HOURS
		type: int
		reportable: false
	minutes
		vname: LBL_MINUTES
		type: int
		reportable: false
		validation
			type: range
			min: 0
			max: 59
	date_send
		vname: LBL_SEND_DATE_TIME
		type: datetime
		required: true
		massupdate: true
	send_on_registration
		vname: LBL_SEND_ON_REGISTRATION
		type: bool
		default: 0
		massupdate: true
	reminder_sent
		type: bool
		default: 0
		massupdate: true
	event_name
		vname: LBL_EVENT_NAME
		type: name
		reportable: false
		source
			type: function
			value_function
				- EventReminder
				- get_event_name
			fields
				- session_id
links
	emailtemplate
		relationship: email_template_event_reminders
		vname: LBL_TEMPLATE
fields_compat
	template_name
		rname: name
		id_name: template_id
		vname: LBL_TEMPLATE_NAME
		type: relate
		table: email_templates
		isnull: true
		module: EmailTemplates
		dbType: varchar
		link: emailtemplate
		len: 255
		source: non-db
relationships
	email_template_event_reminders
		key: template_id
		target_bean: EmailTemplate
		target_key: id
		relationship_type: one-to-many

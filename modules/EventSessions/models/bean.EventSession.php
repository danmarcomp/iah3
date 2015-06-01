<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/EventSessions/EventSession.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	optimistic_locking: true
	table_name: event_sessions
	primary_key: id
	reportable: true
hooks
	after_save
		--
			class_function: go_to_next
	after_remove_link
		--
			class_function: remove_relation
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	app.deleted
	event
		vname: LBL_EVENT_NAME
		vname_list: LBL_LIST_EVENT_NAME
		reportable: false
		type: ref
		bean_name: Event
		editable: false
	event_name
		vname: LBL_EVENT_NAME
		type: varchar
		source
			type: field
			field: event.name
	event_description
		vname: LBL_DESCRIPTION
		type: text
		source
			type: field
			field: event.description
	event_assigned_user
		vname: LBL_ASSIGNED_TO
		type: ref
		bean_name: User
		source
			type: field
			field: event.assigned_user
		massupdate: true
	event_num_sessions
		vname: LBL_NUM_SESSIONS
		type: int
		source
			type: field
			field: event.num_sessions
	event_product
		vname: LBL_PRODUCT
		type: ref
		bean_name: Product
		source
			type: field
			field: event.product
		massupdate: true
	event_type
		vname: LBL_TYPE
		type: ref
		bean_name: EventType
		source
			type: field
			field: event.event_type
		massupdate: true
	event_format
		vname: LBL_FORMAT
		type: enum
		options: event_format_dom
		source
			type: field
			field: event.format
		massupdate: true
	name
		type: name
		vname: LBL_SESSION_NAME
		unified_search: true
		audited: true
		required: true
		vname_list: LBL_LIST_NAME
	session_number
		type: int
		vname: LBL_SESSION_NUMBER
		unified_search: false
		audited: true
		updateable: false
		vname_list: LBL_LIST_SESSION_NUMBER
	date_start
		type: datetime
		vname: LBL_START_DATE
		vname_list: LBL_LIST_DATE_START
		massupdate: true
	date_end
		type: datetime
		vname: LBL_END_DATE
		vname_list: LBL_LIST_DATE_END
		massupdate: true
	no_date_start
		vname: LBL_NO_DATE_START
		type: bool
		required: true
		massupdate: true
	no_date_end
		vname: LBL_NO_DATE_END
		type: bool
		required: true
		massupdate: true
	url
		type: url
		len: 255
		vname: LBL_URL
		massupdate: true
	phone
		type: phone
		len: 40
		vname: LBL_PHONE
	phone_password
		type: varchar
		len: 40
		vname: LBL_PHONE_PASSWORD
	location
		type: varchar
		len: 255
		vname: LBL_LOCATION
		unified_search: false
	location_url
		type: url
		len: 255
		vname: LBL_LOCATION_URL
		massupdate: true
	location_maplink
		type: url
		len: 255
		vname: LBL_LOCATION_MAPLINK
		massupdate: true
	website
		type: url
		len: 255
		vname: LBL_WEBSITE
		massupdate: true
	tracking_code
		type: varchar
		len: 50
		vname: LBL_TRACKING_CODE
	breakfast
		type: varchar
		len: 255
		vname: LBL_BREAKFAST
		unified_search: false
	lunch
		type: varchar
		len: 255
		vname: LBL_LUNCH
		unified_search: false
	dinner
		type: varchar
		len: 255
		vname: LBL_DINNER
		unified_search: false
	refreshments
		type: varchar
		len: 255
		vname: LBL_REFRESHMENTS
		unified_search: false
	parking
		type: varchar
		len: 255
		vname: LBL_PARKING
		unified_search: false
	speaker1
		type: varchar
		len: 50
		vname: LBL_SPEAKER1
		unified_search: false
	host1
		type: varchar
		len: 100
		vname: LBL_HOST1
		unified_search: false
	speaker2
		type: varchar
		len: 50
		vname: LBL_SPEAKER2
		unified_search: false
	host2
		type: varchar
		len: 100
		vname: LBL_HOST2
		unified_search: false
	speaker3
		type: varchar
		len: 50
		vname: LBL_SPEAKER3
		unified_search: false
	host3
		type: varchar
		len: 100
		vname: LBL_HOST3
		unified_search: false
	speaker4
		type: varchar
		len: 50
		vname: LBL_SPEAKER4
		unified_search: false
	host4
		type: varchar
		len: 100
		vname: LBL_HOST4
		unified_search: false
	speaker5
		type: varchar
		len: 50
		vname: LBL_SPEAKER5
		unified_search: false
	host5
		type: varchar
		len: 100
		vname: LBL_HOST5
		unified_search: false
	attendee_limit
		type: int
		vname: LBL_ATTENDEE_LIMIT
		vname_list: LBL_LIST_CAPACITY
	attendee_overflow
		type: int
		vname: LBL_ATTENDEE_OVERFLOW
	reminder_sent
		type: bool
		vname: LBL_REMINDER_SENT
		default: 0
		massupdate: true
	calendar_post
		type: bool
		vname: LBL_CALENDAR_POST
		default: 1
		massupdate: true
	calendar_color
		type: enum
		options: event_color_dom
		vname: LBL_CALENDAR_COLOR
		massupdate: true
	description
		type: text
		vname: LBL_DESCRIPTION
		unified_search: false
	registrant_count
		vname: LBL_LIST_REGISTRANT_COUNT
		type: formula
		source: non-db
		massupdate: false
		reportable: false
		formula
			type: function
			class: EventSession
			function: calc_registrants
			fields: [id]
links
	events
		relationship: events_event_sessions
		vname: LBL_EVENTS
	attendees
		relationship: events_registered_sessions
		vname: LBL_ATTENDEES
	contacts
		relationship: events_contacts
		layout: Events
		vname: LBL_CONTACTS
	accounts
		relationship: events_accounts
		layout: Events
		vname: LBL_ACCOUNTS
	leads
		relationship: events_leads
		layout: Events
		vname: LBL_LEADS
	eventreminders
		relationship: events_event_reminders
		vname: LBL_REMINDERS
	invoice
		relationship: eventsessions_invoice
		vname: LBL_INVOICES
	app.securitygroups
		relationship: securitygroups_events
indices
	idx_eventsess_id_del
		fields
			- id
			- deleted
	idx_eventsess_assigned_del
		fields
			- deleted
			- assigned_user_id
relationships
	events_contacts
		key: id
		target_bean: Contact
		target_key: id
		join
			model: EventsCustomer
			source_key: session_id
			target_key: customer_id
			role_column: customer_type
			role_value: Contacts
		relationship_type: many-to-many
	events_accounts
		key: id
		target_bean: Account
		target_key: id
		join
			model: EventsCustomer
			source_key: session_id
			target_key: customer_id
			role_column: customer_type
			role_value: Accounts
		relationship_type: many-to-many
	events_leads
		key: id
		target_bean: Lead
		target_key: id
		join
			model: EventsCustomer
			source_key: session_id
			target_key: customer_id
			role_column: customer_type
			role_value: Leads
		relationship_type: many-to-many
	events_event_reminders
		key: id
		target_bean: EventReminder
		target_key: session_id
		relationship_type: one-to-many
	eventsessions_invoice
		key: id
		target_bean: Invoice
		target_key: event_id
		relationship_type: one-to-many

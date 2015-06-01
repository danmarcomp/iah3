<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Events/Event.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	optimistic_locking: true
	table_name: events
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	app.deleted
	name
		type: name
		vname: LBL_NAME
		unified_search: true
		audited: true
		required: true
		vname_list: LBL_LIST_NAME
	num_sessions
		type: int
		vname: LBL_NUM_SESSIONS
		unified_search: false
		required: true
		audited: true
		min: 1
		max: 100
	description
		type: text
		vname: LBL_DESCRIPTION
		unified_search: false
	event_type
		type: ref
		vname: LBL_TYPE
		required: true
		bean_name: EventType
		massupdate: true
	format
		type: enum
		options: event_format_dom
		vname: LBL_FORMAT
		len: 40
		required: true
		massupdate: true
	product
		type: ref
		vname: LBL_PRODUCT_NAME
		required: true
		bean_name: Product
		massupdate: true
links
	eventsessions
		relationship: events_event_sessions
		vname: LBL_SESSIONS
	productcatalog
		relationship: events_products
		vname: LBL_PRODUCT
		link_type: one
	eventtypes
		relationship: events_types
		vname: LBL_TYPE
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
	product_name
		rname: name
		id_name: product_id
		vname: LBL_PRODUCT_NAME
		type: relate
		link: productcatalog
		table: products
		join_name: products
		isnull: true
		module: ProductCatalog
		dbType: varchar
		len: 100
		source: non-db
		unified_search: false
	session_number
		rname: session_number
		id_name: id
		vname: LBL_SESSION_NUMBER
		join_name: event_sessions
		type: relate
		link: eventsessions
		table: event_sessions
		isnull: true
		module: EventSessions
		dbType: varchar
		len: 255
		source: non-db
		unified_search: false
	session_name
		rname: name
		id_name: id
		vname: LBL_SESSION_NAME
		join_name: event_sessions
		type: relate
		link: eventsessions
		table: event_sessions
		isnull: true
		module: EventSessions
		dbType: varchar
		len: 255
		source: non-db
		unified_search: false
	date_end
		rname: date_end
		id_name: id
		vname: LBL_DATE_END
		join_name: event_sessions
		type: relate
		link: eventsessions
		table: event_sessions
		isnull: true
		module: EventSessions
		dbType: datetime
		len: 255
		source: non-db
		unified_search: false
	attendee_limit
		rname: attendee_limit
		id_name: id
		vname: LBL_ATTENDEE_LIMIT
		join_name: event_sessions
		type: relate
		link: eventsessions
		table: event_sessions
		isnull: true
		module: EventSessions
		dbType: int
		len: 11
		source: non-db
		unified_search: false
	date_start
		vname: LBL_DATE_START
		type: datetime
		len: 255
		source: non-db
		unified_search: false
	registrant_count
		type: int
		source: non-db
		vname: ""
	session_id
		rname: id
		id_name: id
		vname: LBL_SESSION_NUMBER
		join_name: event_sessions
		type: relate
		link: eventsessions
		table: event_sessions
		isnull: true
		module: EventSessions
		dbType: varchar
		len: 255
		source: non-db
		unified_search: false
indices
	idx_event_id_del
		fields
			- id
			- deleted
	idx_event_assigned_del
		fields
			- deleted
			- assigned_user_id
relationships
	events_event_sessions
		key: id
		target_bean: EventSession
		target_key: event_id
		relationship_type: one-to-many
	events_products
		key: product_id
		target_bean: Product
		target_key: id
		relationship_type: one-to-many
	events_types
		key: event_type_id
		target_bean: EventType
		target_key: id
		relationship_type: one-to-many

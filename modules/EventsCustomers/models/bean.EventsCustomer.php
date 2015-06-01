<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/EventsCustomers/EventsCustomer.php
	unified_search: false
	duplicate_merge: false
	table_name: events_customers
	primary_key: id
fields
	app.id
	app.deleted
	app.date_modified
	session
		vname: LBL_SESSION_ID
		required: true
		type: ref
		bean_name: EventSession
		massupdate: true
	customer
		vname: LBL_CUSTOMER_ID
		required: true
		type: ref
		reportable: false
		dynamic_module: customer_type
		massupdate: true
	customer_type
		vname: LBL_CUSTOMER_TYPE
		required: true
		type: module_name
	registered
		vname: LBL_REGISTERED
		type: bool
		required: true
		default: 1
		massupdate: true
	attended
		vname: LBL_ATTENDED
		type: bool
		required: true
		default: 0
		massupdate: true
	set_registered
		vname: LBL_REGISTERED
		type: html
		reportable: false
		source
			type: function
			value_function
				- EventsCustomer
				- get_set_registered_control
			fields
				- id
				- registered
	set_attended
		vname: LBL_ATTENDED
		type: html
		reportable: false
		source
			type: function
			value_function
				- EventsCustomer
				- get_set_attended_control
			fields
				- id
				- attended
links
	sessions
		relationship: events_registered_sessions
		vname: LBL_SESSIONS
	leads
		relationship: events_registered_leads
		vname: LBL_LEADS
	contacts
		relationship: events_registered_contacts
		vname: LBL_CONTACTS
indices
	idx_events_customers
		type: alternate_key
		fields
			- session_id
			- customer_type
			- customer_id
relationships
	events_registered_sessions
		key: session_id
		target_bean: EventSession
		target_key: id
		relationship_type: one-to-one
	events_registered_leads
		key: customer_id
		target_bean: Lead
		target_key: id
		role_column: customer_type
		role_value: Leads
		relationship_type: one-to-one
	events_registered_contacts
		key: customer_id
		target_bean: Contact
		target_key: id
		role_column: customer_type
		role_value: Contacts
		relationship_type: one-to-one

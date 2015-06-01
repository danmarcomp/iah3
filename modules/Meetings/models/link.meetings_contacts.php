<?php return; /* no output */ ?>

detail
	type: link
	table_name: meetings_contacts
	primary_key
		- meeting_id
		- contact_id
fields
	app.date_modified
	app.deleted
	meeting
		type: ref
		bean_name: Meeting
	contact
		type: ref
		bean_name: Contact
	required
		type: bool
		default: 1
	accept_status
		type: enum
		options: dom_meeting_accept_status
		len: 25
		default: none
indices
	idx_con_mtg_con
		fields
			- contact_id
relationships
	meetings_contacts
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: meeting_id
		join_key_rhs: contact_id
		lhs_bean: Meeting
		rhs_bean: Contact

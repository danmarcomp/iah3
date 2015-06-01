<?php return; /* no output */ ?>

detail
	type: link
	table_name: calls_contacts
	primary_key
		- call_id
		- contact_id
fields
	app.date_modified
	app.deleted
	call
		type: ref
		bean_name: Call
	contact
		type: ref
		bean_name: Contact
	required
		type: bool
		default: 1
	accept_status
		type: enum
		len: 25
		options: dom_meeting_accept_status
		default: none
indices
	idx_con_call_con
		fields
			- contact_id
relationships
	calls_contacts
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: call_id
		join_key_rhs: contact_id
		lhs_bean: Call
		rhs_bean: Contact

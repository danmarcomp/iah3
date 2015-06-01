<?php return; /* no output */ ?>

detail
	type: link
	table_name: calls_users
	primary_key
		- call_id
		- user_id
fields
	app.date_modified
	app.deleted
	call
		type: ref
		bean_name: Call
	user
		type: ref
		bean_name: User
	required
		type: bool
		default: 1
	accept_status
		type: enum
		len: 25
		options: dom_meeting_accept_status
		default: none
indices
	idx_usr_call_usr
		fields
			- user_id
relationships
	calls_users
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: call_id
		join_key_rhs: user_id
		lhs_bean: Call
		rhs_bean: User

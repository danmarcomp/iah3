<?php return; /* no output */ ?>

detail
	type: link
	table_name: meetings_users
	primary_key
		- meeting_id
		- user_id
fields
	app.date_modified
	app.deleted
	meeting
		type: ref
		bean_name: Meeting
	user
		type: ref
		bean_name: User
	required
		type: bool
		default: 1
	accept_status
		type: enum
		options: dom_meeting_accept_status
		len: 25
		default: none
indices
	idx_usr_mtg_usr
		fields
			- user_id
relationships
	meetings_users
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: meeting_id
		join_key_rhs: user_id
		lhs_bean: Meeting
		rhs_bean: User

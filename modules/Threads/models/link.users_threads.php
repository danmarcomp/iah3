<?php return; /* no output */ ?>

detail
	type: link
	table_name: users_threads
	primary_key
		- user_id
		- thread_id
fields
	app.deleted
	app.date_modified
	user_id
		type: char
		len: 36
		required: true
		default: ""
	thread_id
		type: char
		len: 36
		required: true
		default: ""
indices
	idx_user_thr_user
		fields
			- user_id
	idx_user_thr_thr
		fields
			- thread_id
relationships
	users_threads
		lhs_key: id
		rhs_key: id
		join_key_lhs: user_id
		join_key_rhs: thread_id
		relationship_type: many-to-many
		lhs_bean: User
		rhs_bean: Thread

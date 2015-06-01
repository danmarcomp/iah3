<?php return; /* no output */ ?>

detail
	type: link
	table_name: accounts_threads
	primary_key
		- account_id
		- thread_id
fields
	app.deleted
	app.date_modified
	account_id
		type: char
		len: 36
		required: true
		default: ""
	thread_id
		type: char
		len: 36
		required: true
		default: ""
	relationship_type
		type: varchar
		len: 50
		required: true
		default: Accounts
indices
	idx_acc_thr_acc
		fields
			- account_id
	idx_acc_thr_thr
		fields
			- thread_id
relationships
	accounts_threads
		lhs_key: id
		rhs_key: id
		join_key_lhs: account_id
		join_key_rhs: thread_id
		relationship_type: many-to-many
		lhs_bean: Account
		rhs_bean: Thread

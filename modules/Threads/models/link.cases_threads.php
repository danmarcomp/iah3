<?php return; /* no output */ ?>

detail
	type: link
	table_name: cases_threads
	primary_key
		- case_id
		- thread_id
fields
	app.deleted
	app.date_modified
	case_id
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
		default: Cases
indices
	idx_cas_thr_cas
		fields
			- case_id
	idx_cas_thr_thr
		fields
			- thread_id
relationships
	cases_threads
		lhs_key: id
		rhs_key: id
		join_key_lhs: case_id
		join_key_rhs: thread_id
		relationship_type: many-to-many
		lhs_bean: aCase
		rhs_bean: Thread

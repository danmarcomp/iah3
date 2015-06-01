<?php return; /* no output */ ?>

detail
	type: link
	table_name: bugs_threads
	primary_key
		- bug_id
		- thread_id
fields
	app.deleted
	app.date_modified
	bug_id
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
		default: Bugs
indices
	idx_bug_thr_bug
		fields
			- bug_id
	idx_bug_thr_thr
		fields
			- thread_id
relationships
	bugs_threads
		lhs_key: id
		rhs_key: id
		join_key_lhs: bug_id
		join_key_rhs: thread_id
		relationship_type: many-to-many
		lhs_bean: Bug
		rhs_bean: Thread

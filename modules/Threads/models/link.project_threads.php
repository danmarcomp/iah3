<?php return; /* no output */ ?>

detail
	type: link
	table_name: project_threads
	primary_key
		- project_id
		- thread_id
fields
	app.deleted
	app.date_modified
	project_id
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
		default: Project
indices
	idx_project_thr_project
		fields
			- project_id
	idx_project_thr_thr
		fields
			- thread_id
relationships
	project_threads
		lhs_key: id
		rhs_key: id
		join_key_lhs: project_id
		join_key_rhs: thread_id
		relationship_type: many-to-many
		lhs_bean: Project
		rhs_bean: Thread

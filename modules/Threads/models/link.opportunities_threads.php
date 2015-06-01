<?php return; /* no output */ ?>

detail
	type: link
	table_name: opportunities_threads
	primary_key
		- opportunity_id
		- thread_id
fields
	app.deleted
	app.date_modified
	opportunity_id
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
		default: Opportunities
indices
	idx_opp_thr_opp
		fields
			- opportunity_id
	idx_opp_thr_thr
		fields
			- thread_id
relationships
	opportunities_threads
		lhs_key: id
		rhs_key: id
		join_key_lhs: opportunity_id
		join_key_rhs: thread_id
		relationship_type: many-to-many
		lhs_bean: Opportunity
		rhs_bean: Thread

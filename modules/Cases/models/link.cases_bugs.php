<?php return; /* no output */ ?>

detail
	type: link
	table_name: cases_bugs
	primary_key
		- case_id
		- bug_id
fields
	app.date_modified
	app.deleted
	case
		type: ref
		bean_name: aCase
	bug
		type: ref
		bean_name: Bug
indices
	idx_cas_bug_cas
		fields
			- case_id
	idx_cas_bug_bug
		fields
			- bug_id
relationships
	cases_bugs
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: case_id
		join_key_rhs: bug_id
		lhs_bean: aCase
		rhs_bean: Bug

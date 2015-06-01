<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_cases
	primary_key: [email_id, case_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	case
		type: ref
		bean_name: aCase
indices
	idx_case_email_case
		fields
			- case_id
relationships
	emails_cases_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: case_id
		lhs_bean: Email
		rhs_bean: aCase

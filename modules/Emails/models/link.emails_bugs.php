<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_bugs
	primary_key: [email_id, bug_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	bug
		type: ref
		bean_name: Bug
indices
	idx_bug_email_bug
		fields
			- bug_id
relationships
	emails_bugs_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: bug_id
		lhs_bean: Email
		rhs_bean: Bug

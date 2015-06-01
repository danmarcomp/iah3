<?php return; /* no output */ ?>

detail
	type: link
	table_name: accounts_bugs
	primary_key
		- account_id
		- bug_id
fields
	app.date_modified
	app.deleted
	account
		type: ref
		bean_name: Account
	bug
		type: ref
		bean_name: Bug
indices
	idx_acc_bug_acc
		fields
			- account_id
	idx_acc_bug_bug
		fields
			- bug_id
relationships
	accounts_bugs
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: account_id
		join_key_rhs: bug_id
		lhs_bean: Account
		rhs_bean: Bug

<?php return; /* no output */ ?>

detail
	type: link
	table_name: accounts_cases
	primary_key: [account_id, case_id]
fields
	app.date_modified
	app.deleted
	account
		type: ref
		bean_name: Account
	case
		type: ref
		bean_name: aCase
indices
	idx_acc_acc_case
		fields
			- case_id
relationships
	accounts_cases
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: account_id
		join_key_rhs: case_id
		lhs_bean: Account
		rhs_bean: aCase

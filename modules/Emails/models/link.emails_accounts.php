<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_accounts
	primary_key: [email_id, account_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	account
		type: ref
		bean_name: Account
indices
	idx_acc_email_acc
		fields
			- account_id
relationships
	emails_accounts_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: account_id
		lhs_bean: Email
		rhs_bean: Account

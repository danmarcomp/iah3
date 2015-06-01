<?php return; /* no output */ ?>

detail
	type: link
	table_name: accounts_contacts
	primary_key
		- account_id
		- contact_id
fields
	app.date_modified
	app.deleted
	contact
		type: ref
		bean_name: Contact
	account
		type: ref
		bean_name: Account
indices
	idx_acc_cont_acc
		fields
			- account_id
	idx_acc_cont_cont
		fields
			- contact_id
relationships
	accounts_contacts
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: account_id
		join_key_rhs: contact_id
		lhs_bean: Account
		rhs_bean: Contact

<?php return; /* no output */ ?>

detail
	type: link
	table_name: contacts_users
	primary_key
		- contact_id
		- user_id
fields
	app.date_modified
	app.deleted
	contact
		type: ref
		bean_name: Contact
	user
		type: ref
		bean_name: User
indices
	idx_con_users_con
		fields
			- contact_id
	idx_con_users_user
		fields
			- user_id
relationships
	contacts_users
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: contact_id
		join_key_rhs: user_id
		lhs_bean: Contact
		rhs_bean: User

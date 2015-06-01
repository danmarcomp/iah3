<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_users
	primary_key: [email_id, user_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	user
		type: ref
		bean_name: User
indices
	idx_usr_email_usr
		fields
			- user_id
relationships
	emails_users_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: user_id
		lhs_bean: Email
		rhs_bean: User

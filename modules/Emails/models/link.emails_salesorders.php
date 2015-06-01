<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_salesorders
	primary_key: [email_id, so_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	so
		type: ref
		bean_name: SalesOrder
indices
	idx_so_email_so
		fields
			- so_id
relationships
	emails_salesorders_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: so_id
		lhs_bean: Email
		rhs_bean: SalesOrder

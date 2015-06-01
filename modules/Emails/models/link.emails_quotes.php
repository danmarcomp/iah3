<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_quotes
	primary_key: [email_id, quote_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	quote
		type: ref
		bean_name: Quote
indices
	idx_quote_email_quote
		fields
			- quote_id
relationships
	emails_quotes_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: quote_id
		lhs_bean: Email
		rhs_bean: Quote

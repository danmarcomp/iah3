<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_invoices
	primary_key: [email_id, invoice_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	invoice
		type: ref
		bean_name: Invoice
indices
	idx_invoice_email_invoice
		fields
			- invoice_id
relationships
	emails_invoices_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: invoice_id
		lhs_bean: Email
		rhs_bean: Invoice

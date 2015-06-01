<?php return; /* no output */ ?>

detail
	type: link
	table_name: cases_invoices
	primary_key
		- case_id
		- invoice_id
fields
	app.date_modified
	app.deleted
	case
		type: ref
		required: true
		bean_name: aCase
	invoice
		type: ref
		required: true
		bean_name: Invoice
indices
	cases_invoices_cid
		fields
			- case_id
	cases_invoices_iid
		fields
			- invoice_id
	cases_invoices_uniq
		type: unique
		fields
			- case_id
			- invoice_id
			- deleted
relationships
	cases_invoices
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: case_id
		join_key_rhs: invoice_id
		lhs_bean: aCase
		rhs_bean: Invoice

<?php return; /* no output */ ?>

detail
	type: link
	table_name: invoices_payments
	primary_key: [invoice_id, payment_id]
fields
	app.date_modified
	app.deleted
	app.currency
	invoice
		type: ref
		bean_name: Invoice
		required: true
	payment
		type: ref
		bean_name: Payment
		required: true
	amount
		type: currency
		required: true
        vname: LBL_LIST_ALLOCATED
		base_field: amount_usdollar
indices
	idx_inv_pay_pay
		fields
			- payment_id
relationships
	invoices_payments
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: invoice_id
		join_key_rhs: payment_id
		lhs_bean: Invoice
		rhs_bean: Payment
		managed: true

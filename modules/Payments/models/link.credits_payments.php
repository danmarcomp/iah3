<?php return; /* no output */ ?>

detail
	type: link
	table_name: credits_payments
	primary_key: [credit_id, payment_id]
fields
	app.date_modified
	app.deleted
	app.currency
	credit
		type: ref
		bean_name: CreditNote
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
	idx_cred_pay_pay
		fields
			- payment_id
relationships
	credits_payments
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: credit_id
		join_key_rhs: payment_id
		lhs_bean: CreditNote
		rhs_bean: Payment
		managed: true

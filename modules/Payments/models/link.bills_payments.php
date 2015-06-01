<?php return; /* no output */ ?>

detail
	type: link
	table_name: bills_payments
	primary_key: [bill_id, payment_id]
fields
	app.date_modified
	app.deleted
	app.currency
	bill
		type: ref
		bean_name: Bill
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
	idx_bill_pay_pay
		fields
			- payment_id
relationships
	bills_payments
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: bill_id
		join_key_rhs: payment_id
		lhs_bean: Bill
		rhs_bean: Payment
		managed: true

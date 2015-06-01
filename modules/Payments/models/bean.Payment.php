<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Payments/Payment.php
	audit_enabled: true
	activity_log_enabled: true
	unified_search
		enabled: true
		default: false
	duplicate_merge: false
	table_name: payments
	primary_key: id
	default_order_by: full_number
	display_name
		type: prefixed
		fields
			- full_number
			- name
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	after_delete
		--
			class_function: after_delete
	fill_defaults
		--
			class_function: fill_defaults
	before_save
		--
			class_function: before_save
	after_save
		--
			class_function: add_line_items
	after_add_link
		--
			class_function: after_add_link
	before_subpanel_create
		--
			class: Payment
			file: modules/Payments/Payment.php
			class_function: before_subpanel_create
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.assigned_user
	app.created_by_user
	app.currency
		required: true
	amount
		vname: LBL_AMOUNT
		type: currency
		required: true
		audited: true
		vname_list: LBL_LIST_AMOUNT
		base_field: amount_usdollar
	total_amount
		vname: LBL_TOTAL_ALLOCATED
		type: currency
		required: true
		base_field: total_amount_usdollar
		editable: false
	payment_date
		vname: LBL_PAYMENT_DATE
		type: date
		required: true
		vname_list: LBL_LIST_PAYMENT_DATE
		updateable: false
		massupdate: true
	prefix
		vname: LBL_PAYMENT_PREFIX
		type: sequence_prefix
		sequence_name: payment_prefix
	payment_id
		vname: LBL_PAYMENT_ID
		type: sequence_number
		sequence_name: payment_number_sequence
		required: true
		unified_search: exact
	full_number
		vname: LBL_PAYMENT_NUMBER
		type: item_number
		source
			type: sequence
			fields
				- prefix
				- payment_id
		unified_search: exact
	customer_reference
		vname: LBL_CUSTOMER_REFERENCE
		type: varchar
		len: 50
	notes
		vname: LBL_NOTES
		type: text
	payment_type
		vname: LBL_PAYMENT_TYPE
		type: enum
		options: payment_type_dom
		len: 40
		required: true
		audited: true
		vname_list: LBL_LIST_PAYMENT_TYPE
		updateable: false
		massupdate: true
	direction
		type: enum
		options: payment_direction_dom
		len: 20
		default: incoming
		vname: LBL_PAYMENT_DIRECTION
		massupdate: false
	refund
		type: bool
		default: 0
		vname: LBL_REFUND
		massupdate: false
	account
		vname: LBL_ACCOUNT_ID
		type: ref
		bean_name: Account
		massupdate: false
		required: true
		updateable: false
	refunded
		vname: LBL_REFUNDED
		type: bool
		reportable: false
		default: 0
		massupdate: true
links
	account_link
		vname: LBL_ACCOUNT
		relationship: payments_accounts
		link_type: one
		side: left
		module: Accounts
		bean_name: Account
	invoices
		relationship: invoices_payments
		vname: LBL_PAYMENT_ALLOCATION
		layout: Payments
		updateable: false
	credits
		relationship: credits_payments
		vname: LBL_PAYMENT_ALLOCATION
		layout: Payments
		updateable: false
	bills
		relationship: bills_payments
		vname: LBL_PAYMENT_ALLOCATION
		layout: Payments
		updateable: false
relationships
	payments_accounts
		key: account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many

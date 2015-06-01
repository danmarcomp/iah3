<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/MonthlyServices/MonthlyService.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	optimistic_locking: true
	table_name: monthly_services
	primary_key: id
	default_order_by: booking_category
	reportable: true
	display_name
		type: prefixed
		fields
			- instance_number
			- account
hooks
	new_record
		--
			class_function: init_record
	before_save
		--
			class_function: before_save
	after_add_link
		--
			class_function: after_add_link
fields
	app.id
	app.date_entered
	app.created_by_user
	app.date_modified
	app.modified_user
	app.assigned_user
	app.deleted
	instance_number
		vname: LBL_NUMBER
		type: sequence_number
		sequence_name: mo_service_number_sequence
		dbType: int
		len: 11
		isnull: false
		required: true
		unified_search: true
		editable: false
	booking_category
		vname: LBL_SERVICE_NAME
		type: ref
		reportable: true
		required: true
		bean_name: BookingCategory
		massupdate: true
	address
		vname: LBL_ADDRESS
		type: ref
		bean_name: CompanyAddress
		required: true
		reportable: false
		massupdate: true
	account
		vname: LBL_ACCOUNT
		type: ref
		required: true
		reportable: false
		bean_name: Account
		massupdate: true
	quote
		vname: LBL_QUOTE_TEMPLATE
		type: ref
		required: true
		reportable: false
		bean_name: Quote
		massupdate: false
		add_filters
			--
				param: billing_account
				field_name: account
	purchase_order_num
		vname: LBL_PURCHASE_ORDER_NUM
		type: varchar
		len: 100
	invoice_value
		vname: LBL_MONTHLY_INVOICE_VALUE
		type: base_currency
		default: 0
	total_sales
		vname: LBL_TOTAL_SALES
		type: base_currency
		default: 0
		reportable: false
	balance_due
		vname: LBL_BALANCE_DUE
		type: base_currency
		default: 0
	start_date
		vname: LBL_START_DATE
		type: date
		required: true
		massupdate: true
	end_date
		vname: LBL_END_DATE
		type: date
		subtype: period
		required: true
		massupdate: true
	billing_day
		vname: LBL_BILLING_DAY
		type: int
		len: 3
		required: true
		auto_increment: false
		disable_num_format: true
	invoice_terms
		vname: LBL_INVOICE_TERMS
		type: enum
		options: terms_dom
		len: 25
		required: true
		audited: true
		massupdate: true
	paid_until
		vname: LBL_SERVICE_PAID_UNTIL
		type: date
		isnull: true
		massupdate: false
		editable: false
	contact
		vname: LBL_EMAIL_CONTACT
		type: ref
		reportable: false
		bean_name: Contact
		massupdate: true
		add_filters
			--
				param: primary_account
				field_name: account
	cc_user
		vname: LBL_CC_EMAIL_USER
		type: ref
		bean_name: User
		reportable: true
		massupdate: false
	frequency
		vname: LBL_FREQUENCY
		type: enum
		options: service_frequency_dom
		len: 25
		required: true
		default: monthly
		massupdate: true
	next_invoice
		vname: LBL_NEXT_INVOICE
		type: date
		isnull: true
		massupdate: false
links
	booking_category_link
		vname: LBL_SERVICE_NAME
		relationship: service_categories
		link_type: one
		side: left
		module: BookingCategories
		bean_name: BookingCategory
	account_name_link
		relationship: relate_account
		vname: LBL_ACCOUNT
		link_type: one
	quote_name_link
		vname: LBL_QUOTE_TEMPLATE
		relationship: quote_names
		link_type: one
		side: left
		module: Quotes
		bean_name: Quote
	invoices
		relationship: services_invoices
		no_create: true
		link_type: one
		side: right
		vname: LBL_INVOICES
		module: Invoice
		bean_name: Invoice
fields_compat
	booking_category_name
		rname: name
		id_name: booking_category_id
		vname: LBL_SERVICE_NAME
		type: relate
		link: booking_category_link
		table: service_categories
		isnull: true
		module: BookingCategories
		dbType: char
		source: non-db
		len: 255
		massupdate: false
		required: true
		join_name: booking_category
	account_name
		rname: name
		id_name: account_id
		vname: LBL_ACCOUNT
		type: relate
		link: account_name_link
		table: accounts
		isnull: true
		module: Accounts
		dbType: varchar
		len: 255
		source: non-db
		join_name: accounts
		required: true
		unified_search: true
	quote_name
		rname: name
		id_name: quote_id
		vname: LBL_QUOTE_TEMPLATE
		type: relate
		link: quote_name_link
		table: quotes
		isnull: true
		module: Quotes
		dbType: char
		source: non-db
		len: 255
		massupdate: false
		required: true
		join_name: quote_name
	cc_user_name
		rname: user_name
		id_name: cc_user_id
		vname: LBL_CC_EMAIL_USER
		type: relate
		table: users
		module: Users
		dbType: varchar
		link: users
		len: 255
		source: non-db
		massupdate: false
		reportable: false
relationships
	service_categories
		key: booking_category_id
		target_bean: BookingCategory
		target_key: id
		relationship_type: one-to-one
	quote_names
		key: quote_id
		target_bean: Quote
		target_key: id
		relationship_type: one-to-one
	relate_account
		key: account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many

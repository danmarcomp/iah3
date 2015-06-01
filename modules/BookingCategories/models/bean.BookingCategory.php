<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/BookingCategories/BookingCategory.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: booking_categories
	primary_key: id
	default_order_by: name
	reportable: true
	display_name
		type: function
		value_function
			- BookingCategory
			- format_display_name
		fields
			- name
			- booking_class
fields
	app.id
	app.created_by_user
	app.modified_user
	app.date_entered
	app.date_modified
	app.deleted
	name
		vname: LBL_NAME
		type: name
		len: 100
		required: true
		unified_search: true
		vname_list: LBL_LIST_NAME
	booking_class
		vname: LBL_BOOKING_CLASS
		type: enum
		options: booking_classes_dom
		len: 30
		massupdate: false
		required: true
		vname_list: LBL_LIST_BOOKING_CLASS
	description
		vname: LBL_DESCRIPTION
		type: text
	billing_rate
		vname: LBL_BILLING_RATE
		type: currency
		vname_list: LBL_LIST_BILLING_RATE
		currency: billing_currency
	billing_currency
		vname: LBL_BILLING_CURRENCY
		type: currency_ref
		exchange_rate: billing_exchange_rate
	billing_exchange_rate
		vname: LBL_BILLING_EXCHANGE_RATE
		type: exchange_rate
	paid_rate
		vname: LBL_RAW_PAID_RATE
		type: currency
		currency: paid_currency
	paid_currency
		vname: LBL_PAID_CURRENCY
		type: currency_ref
		exchange_rate: paid_exchange_rate
	paid_exchange_rate
		vname: LBL_PAID_EXCHANGE_RATE
		type: exchange_rate
	location
		type: enum
		vname: LBL_LOCATION
		len: 30
		options: booking_location_dom
		vname_list: LBL_LIST_LOCATION
		massupdate: true
	seniority
		type: enum
		vname: LBL_SENIORITY
		len: 30
		options: booking_seniority_dom
		vname_list: LBL_LIST_SENIORITY
		massupdate: true
	duration
		type: enum
		vname: LBL_DURATION
		len: 30
		options: booking_duration_dom
		massupdate: false
	expenses_unit
		type: enum
		vname: LBL_EXPENSES_UNIT
		len: 30
		options: expense_units_dom
		massupdate: false
	tax_code
		vname: LBL_TAX_CODE
		type: ref
		bean_name: TaxCode
		massupdate: false
	display_unit
		vname: LBL_LIST_UNIT
		type: varchar
		source
			type: function
			class: BookingCategory
			function: get_display_unit
			fields
				- booking_class
				- duration
				- expenses_unit

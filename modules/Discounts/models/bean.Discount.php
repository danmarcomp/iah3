<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Discounts/Discount.php
	unified_search: false
	duplicate_merge: false
	table_name: discounts
	primary_key: id
	default_order_by: name
	js_cache
		enabled: true
	display_name
		type: name_rate
		fields: [name, rate, discount_type, fixed_amount, fixed_amount_usdollar, currency_id, exchange_rate]
fields
	app.id
	app.currency
	app.deleted
	app.date_entered
	app.date_modified
	app.created_by_user
	name
		vname: LBL_LIST_NAME
		type: name
		dbType: char
		len: 40
		required: true
		unified_search: true
	rate
		vname: LBL_RATE
		type: rate_percent
		default: 0
	status
		vname: LBL_STATUS
		type: enum
		dbType: char
		options: simple_status_dom
		len: 25
		massupdate: false
		default: Active
		required: true
	discount_type
		vname: LBL_DISCOUNT_TYPE
		type: enum
		dbType: char
		options: discount_type_dom
		len: 15
		default: percentage
		massupdate: false
		required: true
	fixed_amount
		vname: LBL_RAW_AMOUNT
		type: currency
		base_field: fixed_amount_usdollar
	applies_to_selected
		vname: LBL_APPLIES_TO
		type: enum
		dbType: bool
		options: discount_applies_dom
		required: true
		reportable: false
		default: 0
		massupdate: false
links
	products
		relationship: discounts_products
		vname: LBL_PRODUCTS
	app.securitygroups
		relationship: securitygroups_discounts

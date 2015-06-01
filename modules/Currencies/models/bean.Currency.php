<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Currencies/Currency.php
	unified_search: false
	duplicate_merge: false
	comment:
		Currencies allow Sugar to store and display monetary values in various
		denominations
	table_name: currencies
	primary_key: id
	default_order_by: name
	standard_records
		- -99
	js_cache
		enabled: true
		fields
			- id
			- name
			- symbol
			- iso4217
			- symbol_place_after
			- decimal_places
			- conversion_rate
			- status
	display_name
		type: prefixed
		fields
			- name
			- symbol
hooks
	before_save
		--
			class_function: before_save
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.created_by_user
	app.modified_user
	name
		vname: LBL_NAME
		vname_list: LBL_LIST_NAME
		type: name
		dbType: char
		len: 40
		required: true
		comment: Name of the currency
		unified_search: true
	symbol
		vname: LBL_SYMBOL
		vname_list: LBL_LIST_SYMBOL
		type: item_number
		dbType: char
		len: 10
		required: true
		comment: Symbol representing the currency
		unified_search: true
	symbol_place_after
		vname: LBL_LIST_SYMBOL_PLACE_AFTER
		type: bool
		default: 0
		comment: Place currency symbol after the value?
		massupdate: true
	decimal_places
		vname: LBL_DECIMAL_PLACES
		vname_list: LBL_LIST_DECIMAL_PLACES
		type: int
		default: 2
		comment: Decimal places used in values of this currency
		min: 0
	iso4217
		vname: LBL_ISO4217
		vname_list: LBL_LIST_ISO4217
		type: item_number
		dbType: char
		len: 10
		required: true
		comment: 3-letter identifier specified by ISO 4217 (ex: USD)
		unified_search: true
	conversion_rate
		vname: LBL_RATE
		vname_list: LBL_LIST_RATE
		type: double
		decimal_places: 5
		default: 0
		required: true
		comment: Conversion rate factor (relative to stored value)
		min: 0
	status
		vname: LBL_STATUS
		vname_list: LBL_LIST_STATUS
		type: enum
		dbType: char
		options: simple_status_dom
		len: 25
		default: Active
		required: true
		comment: Currency status
		massupdate: true
indices
	idx_currency_name
		fields
			- name
			- deleted

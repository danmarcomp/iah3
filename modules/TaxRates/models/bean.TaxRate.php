<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/TaxRates/TaxRate.php
	unified_search: false
	duplicate_merge: false
	table_name: taxrates
	primary_key: id
	default_order_by: name
	standard_records
		- -99
	js_cache
		enabled: true
	display_name
		type: name_rate
		fields
			- name
			- rate
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
	name
		vname: LBL_NAME
		vname_list: LBL_LIST_NAME
		type: name
		dbType: char
		len: 40
		required: true
		unified_search: true
	rate
		vname: LBL_RATE
		vname_list: LBL_LIST_RATE
		type: rate_percent
		default: 0
		required: true
	status
		vname: LBL_STATUS
		type: enum
		dbType: char
		options: simple_status_dom
		len: 25
		default: Active
		required: true
		massupdate: true
	compounding
		vname: LBL_COMPOUNDING
		type: enum
		dbType: char
		len: 30
		options: tax_compounding_dom
		default: ""
		massupdate: true
links
	taxcodes
		relationship: taxcodes_rates
		vname: LBL_TAX_CODES

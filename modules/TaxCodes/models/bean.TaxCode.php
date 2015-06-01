<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/TaxCodes/TaxCode.php
	unified_search: false
	duplicate_merge: false
	table_name: taxcodes
	primary_key: id
	default_order_by: position
	standard_records
		- -99
		- standard-tax_-code-0000-000000000001
	js_cache
		enabled: true
	display_name: name
hooks
	before_save
		--
			class_function: before_save
	new_record
		--
			class_function: init_position
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.created_by_user
	name
		vname: LBL_NAME
		type: name
		dbType: char
		len: 40
		required: true
		unified_search: true
	code
		vname: LBL_CODE
		type: item_number
		dbType: char
		len: 10
		required: true
		unified_search: true
	position
		vname: LBL_POSITION
		type: int
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
	taxation_scheme
		vname: LBL_TAXATION_SCHEME
		type: enum
		len: 6
		options: taxation_scheme_dom
		default: list
		required: true
		massupdate: true
	description
		vname: LBL_DESCRIPTION
		type: text
links
	taxrates
		relationship: taxcodes_rates
		vname: LBL_TAX_RATES

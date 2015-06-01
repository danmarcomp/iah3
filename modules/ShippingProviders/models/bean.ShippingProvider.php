<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ShippingProviders/ShippingProvider.php
	unified_search: false
	duplicate_merge: false
	table_name: shipping_providers
	primary_key: id
	default_order_by: name
	js_cache
		enabled: true
	reportable: true
	display_name: name
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.created_by_user
	name
		vname: LBL_LIST_NAME
		type: name
		dbType: char
		len: 50
		required: true
		unified_search: true
	status
		vname: LBL_STATUS
		type: enum
		dbType: char
		options: simple_status_dom
		len: 25
		massupdate: true

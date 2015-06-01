<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ProductAttributes/ProductAttribute.php
	unified_search: false
	duplicate_merge: false
	table_name: product_attributes
	primary_key: id
hooks
	new_record
		--
			class_function: init_record
	before_save
		--
			class_function: update_currency
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	app.created_by_user
	app.currency
		required: true
	product
		vname: LBL_PRODUCT
		type: ref
		required: true
		bean_name: Product
		massupdate: true
	name
		vname: LBL_PA_NAME
		type: name
		len: 100
		required: true
	value
		vname: LBL_PA_VALUE
		type: varchar
		len: 100
	price
		vname: LBL_PA_PRICE
		type: currency
		extra_precision: true
		base_field: price_usdollar

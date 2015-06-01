<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ProductTypes/ProductType.php
	unified_search: false
	duplicate_merge: false
	table_name: product_types
	primary_key: id
	default_order_by: name
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	app.created_by_user
	category
		vname: LBL_PRODUCT_CATEGORY
		type: ref
		required: true
		massupdate: false
		bean_name: ProductCategory
	name
		vname: LBL_PRODUCT_TYPE_NAME
		type: name
		len: 100
		required: true
		massupdate: false
		vname_list: LBL_LIST_NAME
		unified_search: true
	description
		vname: LBL_PRODUCT_DESCRIPTION
		type: text
		massupdate: false
		vname_list: LBL_LIST_DESCRIPTION
links
	products
		vname: LBL_PRODUCTS
		relationship: product_product_type
		module: ProductCatalog
		bean_name: Product
		massupdate: false
	assemblies
		vname: LBL_ASSEMBLIES
		relationship: assembly_product_type
		module: Assemblies
		bean_name: Assembly

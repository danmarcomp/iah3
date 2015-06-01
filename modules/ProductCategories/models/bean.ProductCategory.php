<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ProductCategories/ProductCategory.php
	unified_search: false
	duplicate_merge: false
	table_name: product_categories
	primary_key: id
	default_order_by: name
	importable: LBL_PRODUCT_CATEGORIES
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	app.created_by_user
	name
		vname: LBL_PRODUCT_CATEGORIES_NAME
		type: name
		len: 100
		required: true
		vname_list: LBL_LIST_NAME
		unified_search: true
	description
		vname: LBL_PRODUCT_DESCRIPTION
		type: text
		vname_list: LBL_LIST_DESCRIPTION
	eshop
		vname: LBL_ESHOP
		type: bool
		default: 1
		reportable: false
		vname_list: LBL_LIST_ESHOP
		massupdate: true
	parent
		vname: LBL_PARENT_CATEGORY
		type: ref
		reportable: false
		bean_name: ProductCategory
		massupdate: true
links
	products
		vname: LBL_PRODUCTS
		relationship: product_product_category
		module: ProductCatalog
		bean_name: Product
	assemblies
		vname: LBL_ASSEMBLIES
		relationship: assembly_product_category
		module: Assemblies
		bean_name: Assembly
relationships
	product_category_parent
		key: id
		target_bean: ProductCategory
		target_key: parent_id
		relationship_type: one-to-many

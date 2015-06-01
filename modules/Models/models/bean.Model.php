<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Models/Model.php
	unified_search: false
	duplicate_merge: false
	table_name: models
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	app.created_by_user
	manufacturer
		vname: LBL_MANUFACTURER
		type: ref
		reportable: false
		massupdate: false
		bean_name: Account
	name
		vname: LBL_NAME
		type: name
		len: 100
		required: true
		vname_list: LBL_LIST_NAME
		unified_search: true
	description
		vname: LBL_DESCRIPTION
		type: text
		vname_list: LBL_LIST_DESCRIPTION
links
	products
		relationship: product_model
		vname: LBL_PRODUCTS
		module: ProductCatalog
		bean_name: Product
	assets
		relationship: asset_model
		vname: LBL_ASSETS
		module: Models
		bean_name: Model
	assemblies
		relationship: assembly_model
		vname: LBL_ASSEMBLIES
		module: Assemblies
		bean_name: Assembly
indices
	models_manufacurer
		fields
			- manufacturer_id
			- deleted
	models_name
		fields
			- name
			- deleted
relationships
	model_manufacturer
		key: manufacturer_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many

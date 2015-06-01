<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Assemblies/Assembly.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	table_name: assemblies
	primary_key: id
	default_order_by: name
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	before_save
		--
			class_function: uploadImage
		--
			class_function: recalc_prices
	after_save
		--
			class_function: updated_supplier
fields
	app.id
	app.date_entered
	app.created_by_user
	app.date_modified
	app.modified_user
	app.deleted
	image_url
		vname: LBL_IMAGE_URL
		type: image
		dbType: varchar
		len: 255
		default: ""
		reportable: false
	image_file
		vname: LBL_OR_UPLOAD_IMAGE
		type: file_ref
		autothumb: true
		len: 255
		source: non-db
		editable: true
		reportable: false
	thumbnail_url
		vname: LBL_THUMBNAIL_URL
		type: image
		dbType: varchar
		len: 255
		default: ""
		reportable: false
	thumbnail_file
		vname: LBL_OR_UPLOAD_IMAGE
		type: file_ref
		len: 255
		source: non-db
		editable: true
		reportable: false
	name
		vname: LBL_ASSEMBLY_NAME
		type: name
		len: 4096
		required: true
		vname_list: LBL_LIST_NAME
		unified_search: true
	purchase_name
		vname: LBL_PURCHASE_NAME
		type: varchar
		len: 255
	product_category
		vname: LBL_PRODUCT_CATEGORY
		type: ref
		required: true
		bean_name: ProductCategory
		massupdate: true
	product_type
		vname: LBL_PRODUCT_TYPE
		type: ref
		bean_name: ProductType
		massupdate: true
	supplier
		vname: LBL_SUPPLIER
		type: ref
		bean_name: Account
		add_filters
			--
				param: is_supplier
				value: 1
		massupdate: true
	manufacturer
		vname: LBL_MANUFACTURER
		type: ref
		bean_name: Account
		add_filters
			--
				param: account_type
				value: Manufacturer
		massupdate: true
	model
		vname: LBL_MODEL
		type: ref
		bean_name: Model
		massupdate: true
	product_url
		vname: LBL_PRODUCT_URL
		type: url
		massupdate: true
	manufacturers_part_no
		vname: LBL_MANUFACTURERS_PART_NO
		type: item_number
		len: 100
		required: true
		vname_list: LBL_LIST_MANUFACTURER_NUMBER
		unified_search: true
	vendor_part_no
		vname: LBL_VENDOR_PART_NO
		type: item_number
		len: 100
		vname_list: LBL_LIST_VENDOR_PART_NUMBER
	description
		vname: LBL_DESCRIPTION
		type: html
		dbType: text
	description_plain
		vname: LBL_PRODUCT_DESCRIPTION
		type: formula
		source: non-db
		formula
			type: html2plaintext
			fields: [description]
		reportable: false
	cost_usdollar
		vname: LBL_PRODUCT_COST
		type: base_currency
		importable: false
	list_usdollar
		vname: LBL_PRODUCT_LIST_PRICE
		type: base_currency
		importable: false
	purchase_usdollar
		vname: LBL_PRODUCT_PURCHASE_PRICE
		type: base_currency
		importable: false
	eshop
		vname: LBL_ESHOP
		type: bool
		default: 1
		vname_list: LBL_LIST_ESHOP
		massupdate: true
	tax_code
		vname: LBL_TAX_CODE_ID
		type: ref
		default: ""
		massupdate: false
		bean_name: TaxCode
links
	products
		relationship: products_assemblies
		vname: LBL_PRODUCTS
		layout: Assemblies
	suppliers
		relationship: assemblies_suppliers
		module: Accounts
		bean_name: Account
		vname: LBL_SUPPLIERS
		add_filters
			--
				param: is_supplier
				value: 1
relationships
	assembly_product_category
		key: product_category_id
		target_bean: Product
		target_key: id
		relationship_type: one-to-many
	assembly_product_type
		key: product_type_id
		target_bean: Product
		target_key: id
		relationship_type: one-to-many
	assembly_supplier
		key: supplier_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	assembly_manufacturer
		key: manufacturer_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	assembly_model
		key: model_id
		target_bean: Model
		target_key: id
		relationship_type: one-to-many

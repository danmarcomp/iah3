<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/SupportedAssemblies/SupportedAssembly.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	table_name: supported_assemblies
	primary_key: id
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	fill_defaults
		--
			class_function: set_updated_name
	after_save
		--
			class_function: after_save
fields
	app.id
	app.date_entered
	app.created_by_user
	app.date_modified
	app.modified_user
	app.deleted
	name
		vname: LBL_SUPPORTED_ASSEMBLY_NAME
		type: name
		len: 4096
		required: true
		vname_list: LBL_LIST_NAME
		unified_search: true
	product_category
		vname: LBL_PRODUCT_CATEGORY
		type: ref
		bean_name: ProductCategory
		massupdate: true
	product_type
		vname: LBL_PRODUCT_TYPE
		type: ref
		bean_name: ProductType
		massupdate: true
	account
		vname: LBL_ACCOUNT
		type: ref
		bean_name: Account
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
		vname_list: LBL_LIST_MANUFACTURER_NUMBER
		unified_search: true
	vendor_part_no
		vname: LBL_VENDOR_PART_NO
		type: item_number
		len: 100
	description
		vname: LBL_DESCRIPTION
		type: text
	service_subcontract
		vname: LBL_SERVICE_CONTRACT
		type: ref
		reportable: false
		bean_name: SubContract
		massupdate: true
	project
		vname: LBL_PROJECT_NAME
		type: ref
		bean_name: Project
		massupdate: true
	quantity
		vname: LBL_QUANTITY
		type: int
		len: 11
		default: 1
		required: true
		audited: true
		massupdate: false
		vname_list: LBL_LIST_QUANTITY
links
	assets
		relationship: assets_supported_assemblies
		vname: LBL_PRODUCTS_SUBPANEL_TITLE
		add_existing: true
		initial_filter_fields
			account_id: account_id
	suppliers
		relationship: supp_assem_suppliers
		module: Accounts
		bean_name: Account
		vname: LBL_SUPPLIERS
		add_filters
			--
				param: is_supplier
				value: 1
	serial_numbers
		relationship: assembly_serial_numbers
		vname: LBL_SERIAL_NUMBERS
		module: SerialNumbers
		bean_name: SerialNumber
relationships
	supp_assembly_product_category
		key: product_category_id
		target_bean: ProductCategory
		target_key: id
		relationship_type: one-to-many
	supp_assembly_product_type
		key: product_type_id
		target_bean: ProductType
		target_key: id
		relationship_type: one-to-many
	supp_assembly_supplier
		key: supplier_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	supp_assembly_manufacturer
		key: manufacturer_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	supp_assembly_model
		key: model_id
		target_bean: Model
		target_key: id
		relationship_type: one-to-many
	supp_assembly_account
		key: account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	assets_supported_assemblies
		key: id
		target_bean: Asset
		target_key: supported_assembly_id
		relationship_type: one-to-many

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Assets/Asset.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	table_name: assets
	primary_key: id
	default_order_by: account
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
	app.date_modified
	app.modified_user
	app.created_by_user
	app.deleted
	app.currency
		required: true
	url
		vname: LBL_PRODUCT_URL
		type: url
		massupdate: true
	description
		vname: LBL_DESCRIPTION
		type: text
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
		vname: LBL_PRODUCT_MANUFACTURER_NAME
		type: ref
		bean_name: Account
		add_filters
			--
				param: account_type
				value: Manufacturer
		massupdate: true
	model
		vname: LBL_PRODUCT_MODEL_NAME
		type: ref
		bean_name: Model
		massupdate: true
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
	weight_1
		vname: LBL_PRODUCT_WEIGHT_1
		type: int
		len: 11
	weight_2
		vname: LBL_PRODUCT_WEIGHT_2
		type: int
		len: 11
	vendor_part_no
		vname: LBL_PRODUCT_VENDOR_PART_NO
		type: item_number
		len: 100
	manufacturers_part_no
		vname: LBL_PRODUCT_SUPPLIER_PART_NO
		type: item_number
		len: 100
		unified_search: true
	purchase_price
		vname: LBL_PRODUCT_PURCHASE_PRICE
		type: currency
		required: true
		extra_precision: true
		vname_list: LBL_LIST_PURCHASEPRICE
		base_field: purchase_usdollar
	support_cost
		vname: LBL_PRODUCT_SUPPORT_COST
		type: currency
		default: 0
		audited: true
		extra_precision: true
		base_field: support_cost_usdollar
	unit_support_price
		vname: LBL_PRODUCT_UNIT_SUPPORT_PRICE
		type: currency
		default: 0
		extra_precision: true
		vname_list: LBL_LIST_UNIT_SUPPORT_PRICE
		base_field: unit_support_usdollar
	tax_code
		vname: LBL_TAX_CODE
		type: ref
		default: ""
		massupdate: false
		bean_name: TaxCode
	date_available
		vname: LBL_PRODUCT_DATE_AVAIL
		type: date
		massupdate: false
	warranty_start_date
		vname: LBL_PRODUCT_WARRANTY_START_DATE
		type: date
		massupdate: true
	warranty_expiry_date
		vname: LBL_PRODUCT_WARRANTY_EXPIRY_DATE
		type: date
		massupdate: true
	name
		vname: LBL_PRODUCT_NAME
		type: name
		len: 255
		required: true
		vname_list: LBL_LIST_NAME
		unified_search: true
	quantity
		vname: LBL_PRODUCT_QUANTITY
		type: int
		len: 11
		default: 1
		required: true
		audited: true
		vname_list: LBL_LIST_QUANTITY
	account_role
		vname: LBL_PRODUCT_ACCOUNT_ROLE
		type: varchar
		len: 80
		reportable: false
		importable: false
	service_subcontract
		vname: LBL_PRODUCT_SUBCON
		type: ref
		importable: false
		bean_name: SubContract
		massupdate: true
	project
		vname: LBL_PROJECT_NAME
		type: ref
		importable: false
		bean_name: Project
		massupdate: true
	supported_assembly
		vname: LBL_SUPPORTED_ASSEMBLY
		type: ref
		importable: false
		bean_name: SupportedAssembly
		massupdate: true
links
	cases
		relationship: cases_assets
		vname: LBL_CASES
		link_type: one-to-many
		module: Cases
		bean_name: aCase
	serial_numbers
		relationship: asset_serial_numbers
		vname: LBL_SERIAL_NUMBERS
		module: SerialNumbers
		bean_name: SerialNumber
	suppliers
		relationship: assets_suppliers
		module: Accounts
		bean_name: Account
		vname: LBL_SUPPLIERS
		add_filters
			--
				param: is_supplier
				value: 1
	app.securitygroups
		relationship: securitygroups_assets
indices
	assets_name_idx
		fields
			- name
	assets_acct_idx
		fields
			- account_id
	assets_category_idx
		fields
			- product_category_id
	service_subcontract_idx
		fields
			- service_subcontract_id
relationships
	asset_product_category
		key: product_category_id
		target_bean: ProductCategory
		target_key: id
		relationship_type: one-to-many
	asset_product_type
		key: product_type_id
		target_bean: ProductType
		target_key: id
		relationship_type: one-to-many
	asset_supplier
		key: supplier_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	asset_manufacturer
		key: manufacturer_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	asset_model
		key: model_id
		target_bean: Model
		target_key: id
		relationship_type: one-to-many
	asset_account
		key: account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	cases_assets
		key: id
		target_bean: aCase
		target_key: asset_id
		relationship_type: one-to-many
	assets_supported_assemblies
		key: supported_assembly_id
		target_bean: SupportedAssembly
		target_key: id
		relationship_type: one-to-many
	asset_tax_code
		key: tax_code_id
		target_bean: TaxCode
		target_key: id
		relationship_type: one-to-many

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ProductCatalog/Product.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	table_name: products
	primary_key: id
	default_order_by: name
	reportable: true
	importable: LBL_PRODUCTS
hooks
	new_record
		--
			class_function: init_record
	before_save
		--
			class_function: uploadImage
	after_save
		--
			class_function: after_save
		--
			class_function: update_assembly_prices
		--
			class_function: update_attribute_prices
	after_delete
		--
			class_function: update_assembly_prices
		--
			class_function: update_attribute_prices
	after_add_link
		--
			class_function: update_stock_qty
	after_remove_link
		--
			class_function: update_stock_qty
fields
	app.id
	app.currency
		required: true
	app.date_entered
	app.date_modified
	app.modified_user
	app.created_by_user
	app.deleted
	name
		vname: LBL_PRODUCT_NAME
		type: name
		len: 4096
		required: true
		vname_list: LBL_LIST_NAME
		unified_search: true
	purchase_name
		vname: LBL_PURCHASE_NAME
		type: varchar
		len: 255
	url
		vname: LBL_PRODUCT_URL
		type: url
		massupdate: true
	description
		vname: LBL_PRODUCT_DESCRIPTION
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
	description_long
		vname: LBL_DESCRIPTION_LONG
		type: html
		dbType: text
	supplier
		vname: LBL_PRODUCT_SUPPLIER_NAME
		type: ref
		audited: true
		bean_name: Account
		add_filters
			--
				param: is_supplier
				value: 1
		massupdate: true
	manufacturer
		vname: LBL_PRODUCT_MANUFACTURER_NAME
		type: ref
		audited: true
		bean_name: Account
		add_filters
			--
				param: account_type
				value: Manufacturer
		massupdate: true
	model
		vname: LBL_PRODUCT_MODEL_NAME
		type: ref
		audited: true
		bean_name: Model
		massupdate: true
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
		vname_list: LBL_LIST_VENDOR_PART_NUMBER
	manufacturers_part_no
		vname: LBL_PRODUCT_SUPPLIER_PART_NO
		type: item_number
		len: 100
		required: true
		vname_list: LBL_LIST_PART_NUMBER
		unified_search: true
	cost
		vname: LBL_PRODUCT_COST
		type: currency
		required: true
		audited: true
		extra_precision: true
		vname_list: LBL_LIST_COST
		base_field: cost_usdollar
	list_price
		vname: LBL_PRODUCT_LIST_PRICE
		type: currency
		required: true
		audited: true
		extra_precision: true
		vname_list: LBL_LIST_LISTPRICE
		base_field: list_usdollar
	purchase_price
		vname: LBL_PRODUCT_PURCHASE_PRICE
		type: currency
		required: true
		audited: true
		extra_precision: true
		vname_list: LBL_LIST_PURCHASEPRICE
		base_field: purchase_usdollar
	support_cost
		vname: LBL_SUPPORT_PRODUCT_COST
		type: currency
		default: 0
		audited: true
		extra_precision: true
		vname_list: LBL_LIST_SUPPORT_COST
		base_field: support_cost_usdollar
	support_list_price
		vname: LBL_SUPPORT_PRODUCT_LIST_PRICE
		type: currency
		default: 0
		audited: true
		extra_precision: true
		vname_list: LBL_LIST_SUPPORT_LISTPRICE
		base_field: support_list_usdollar
	support_selling_price
		vname: LBL_SUPPORT_PRODUCT_SELLING_PRICE
		type: currency
		default: 0
		audited: true
		extra_precision: true
		vname_list: LBL_LIST_SUPPORT_PURCHASEPRICE
		base_field: support_selling_usdollar
	pricing_formula
		vname: LBL_PRODUCT_PRICING_FORMULA
		type: enum
		len: 30
		required: true
		options: prod_price_formula_dom
		default: Fixed Price
		massupdate: false
	support_price_formula
		vname: LBL_PRODUCT_SUPPORT_PRICE_FORMULA
		type: enum
		len: 30
		options: prod_support_price_formula_dom
		default: Fixed Price
		massupdate: false
	is_available
		vname: LBL_IS_AVAILABLE
		type: enum
		options: yes_no_dom
		len: 3
		default: no
		audited: true
		vname_list: LBL_LIST_AVAILABLE
		required: true
		massupdate: true
	date_available
		vname: LBL_PRODUCT_DATE_AVAIL
		type: date
		massupdate: true
	ppf_perc
		vname: LBL_PRODUCT_PPF_PERC
		type: rate_percent
		len: 11
	support_ppf_perc
		vname: LBL_PRODUCT_SUPPORT_PPF_PERC
		type: rate_percent
		len: 11
	track_inventory
		vname: LBL_TRACK_INVENTORY
		type: enum
		len: 20
		options: track_inventory_dom
		audited: true
		default: semiauto
		required: true
		massupdate: true
	all_stock
		type: int
		vname: LBL_ALL_IN_STOCK
		importable: false
		vname_list: LBL_LIST_IN_STOCK
	tax_code
		vname: LBL_TAX_CODE
		type: ref
		default: ""
		massupdate: true
		dbType: id
		bean_name: TaxCode
	eshop
		vname: LBL_ESHOP
		type: bool
		default: 1
		massupdate: true
	image_url
		vname: LBL_IMAGE_URL
		type: image
		dbType: varchar
		len: 255
		default: ""
		reportable: false
	image_file
		vname: LBL_OR_UPLOAD_IMAGE
		type: image_ref
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
		type: image_ref
		len: 255
		source: non-db
		editable: true
		reportable: false
links
	warehouses
		relationship: products_warehouses_rel
		layout: ProductCatalog
		vname: LBL_WAREHOUSES_SUBPANEL_TITLE
		no_create: true
	assemblies
		relationship: products_assemblies
		vname: LBL_ASSEMBLIES
	productattributes
		relationship: products_product_attributes
		vname: LBL_ATTRIBUTES
	suppliers
		relationship: products_suppliers
		module: Accounts
		bean_name: Account
		vname: LBL_SUPPLIERS
		add_filters
			--
				param: is_supplier
				value: 1
	quote_lines
		relationship: products_quote_lines
		vname: LBL_QUOTE_LINES
	invoice_lines
		relationship: products_invoice_lines
		vname: LBL_INVOICE_LINES
	credit_lines
		relationship: products_credit_lines
		vname: LBL_CREDIT_LINES
	bill_lines
		relationship: products_bill_lines
		vname: LBL_BILL_LINES
	po_lines
		relationship: products_po_lines
		vname: LBL_PO_LINES
	so_lines
		relationship: products_so_lines
		vname: LBL_SO_LINES
	app.securitygroups
		relationship: securitygroups_productcatalog
relationships
	product_product_category
		key: product_category_id
		target_bean: Product
		target_key: id
		relationship_type: one-to-many
	product_type_product_category
		key: category_id
		target_bean: Product
		target_key: id
		relationship_type: one-to-many
	product_product_type
		key: product_type_id
		target_bean: Product
		target_key: id
		relationship_type: one-to-many
	product_supplier
		key: supplier_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	product_manufacturer
		key: manufacturer_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	product_model
		key: model_id
		target_bean: Model
		target_key: id
		relationship_type: one-to-many
	product_tax_code
		key: tax_code_id
		target_bean: TaxCode
		target_key: id
		relationship_type: one-to-many
	products_product_attributes
		key: id
		target_bean: ProductAttribute
		target_key: product_id
		relationship_type: one-to-many
	products_invoice_lines
		key: id
		target_bean: InvoiceLine
		target_key: related_id
		relationship_type: one-to-many
		role_column: related_type
		role_value: ProductCatalog
	products_quote_lines
		key: id
		target_bean: QuoteLine
		target_key: related_id
		relationship_type: one-to-many
		role_column: related_type
		role_value: ProductCatalog
	products_credit_lines
		key: id
		target_bean: CreditNoteLine
		target_key: related_id
		relationship_type: one-to-many
		role_column: related_type
		role_value: ProductCatalog
	products_bill_lines
		key: id
		target_bean: BillLine
		target_key: related_id
		relationship_type: one-to-many
		role_column: related_type
		role_value: ProductCatalog
	products_po_lines
		key: id
		target_bean: PurchaseOrderLine
		target_key: related_id
		relationship_type: one-to-many
		role_column: related_type
		role_value: ProductCatalog
	products_so_lines
		key: id
		target_bean: SalesOrderLine
		target_key: related_id
		relationship_type: one-to-many
		role_column: related_type
		role_value: ProductCatalog

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/CompanyAddress/CompanyAddress.php
	unified_search: false
	duplicate_merge: false
	table_name: company_addresses
	primary_key: id
	js_cache
		enabled: true
	display_name: name
hooks
	before_save
		--
			class_function: before_save
	after_save
		--
			class_function: after_save
fields
	app.id
	app.date_modified
	app.modified_user
	app.deleted
	name
		vname: LBL_NAME
		type: name
		required: true
		len: 150
		unified_search: true
	address_street
		vname: LBL_ADDRESS
		type: varchar
		len: 150
	address_city
		vname: LBL_CITY
		type: varchar
		len: 150
	address_state
		vname: LBL_STATE
		type: varchar
		len: 150
	address_postalcode
		vname: LBL_POSTAL_CODE
		type: varchar
		len: 150
	address_country
		vname: LBL_COUNTRY
		type: varchar
		len: 150
	phone
		vname: LBL_PHONE
		type: varchar
		len: 150
	fax
		vname: LBL_FAX
		type: varchar
		len: 150
	address_format
		vname: LBL_ADDRESS_FORMAT
		type: varchar
		len: 50
	main
		vname: LBL_MAIN
		vname_list: LBL_LIST_MAIN
		type: bool
		reportable: false
		default: 0
		massupdate: false
	logo
		vname: LBL_COMPANY_LOGO
		vdesc: LBL_LOGO_DESCRIPTION
		type: image_ref
		len: 255
		default: ""
		upload_dir: {FILES}/images/logos/
	is_warehouse
		type: bool
		vname: LBL_IS_WAREHOUSE
		default: 0
		massupdate: false
	main_warehouse
		type: bool
		vname: LBL_MAIN_WAREHOUSE
		default: 0
		massupdate: false
links
	products
		relationship: products_warehouses_rel
		vname: LBL_PRODUCTS

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/LicenseInfo/LicenseHistory.php
	unified_search: false
	duplicate_merge: false
	table_name: license_history
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	vendor_id
		vname: LBL_VENDOR_ID
		type: varchar
		len: 40
		required: true
	vendor_name
		vname: LBL_VENDOR_NAME
		vname_list: LBL_LIST_LICENSE_VENDOR
		type: varchar
		len: 100
		required: true
	license
		vname: LBL_LICENSE_ID
		type: ref
		required: true
		bean_name: ClientLicense
		massupdate: true
	prev_license
		vname: LBL_PREV_LICENSE_ID
		type: ref
		bean_name: ClientLicense
		massupdate: true
	name
		vname: LBL_NAME
		type: varchar
		len: 255
		required: true
	type
		vname: LBL_TYPE
		vname_list: LBL_LIST_LICENSE_TYPE
		type: enum
		options: license_type_dom
		len: 20
		required: true
		massupdate: true
	product_list
		vname: LBL_PRODUCT_LIST
		vname_list: LBL_LIST_LICENSE_PRODUCTS
		type: multienum
		options: license_product_dom
		len: 100
		massupdate: true
	licensee
		vname: LBL_LICENSEE
		type: varchar
		len: 100
		required: true
	date_purchased
		vname: LBL_DATE_PURCHASED
		vname_list: LBL_LIST_LICENSE_PURCHASED
		type: date
		required: true
		massupdate: true
	date_support_start
		vname: LBL_DATE_SUPPORT_START
		type: date
		massupdate: true
	date_support_end
		vname: LBL_DATE_SUPPORT_END
		vname_list: LBL_LIST_LICENSE_SUPPORT_END
		type: date
		massupdate: true
	date_loaded
		vname: LBL_DATE_LOADED
		type: datetime
		massupdate: true
	active_limit
		vname: LBL_ACTIVE_LIMIT
		vname_list: LBL_LIST_LICENSE_ACTIVE_LIMIT
		type: int
	terms_short
		vname: LBL_TERMS
		type: varchar
		len: 100
	terms_long
		vname: LBL_TERMS_LONG
		type: text
indices
	license_exts_dt
		fields
			- date_entered

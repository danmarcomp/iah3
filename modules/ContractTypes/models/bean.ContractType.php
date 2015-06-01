<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ContractTypes/ContractType.php
	unified_search: false
	duplicate_merge: false
	table_name: service_contracttypes
	primary_key: id
	reportable: true
fields
	app.id
	app.date_entered
	app.created_by_user
	app.date_modified
	app.modified_user
	app.deleted
	name
		vname: LBL_TYPE_NAME
		type: name
		len: 50
		required: true
		unified_search: true
	contract_no
		vname: LBL_TYPE_CONTRACT_NO
		type: id
		reportable: false
	response_time
		vname: LBL_TYPE_RESPONSE_TIME
		type: int
		len: 11
	vendor_name
		vname: LBL_TYPE_VENDOR_NAME
		type: varchar
		len: 50
		required: true
	description
		vname: LBL_DESCRIPTION
		type: text

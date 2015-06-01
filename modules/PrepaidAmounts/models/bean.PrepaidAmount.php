<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/PrepaidAmounts/PrepaidAmount.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	table_name: prepaid_amounts
	primary_key: id
hooks
	new_record
		--
			class_function: init_record
	after_save
		--
			class_function: update_balance
fields
	app.id
	app.currency
		required: true
	app.date_entered
	app.created_by_user
	app.date_modified
	app.modified_user
	app.deleted
	name
		vname: LBL_SUBJECT
		type: name
		len: 100
		required: true
		vname_list: LBL_LIST_SUBJECT
	transaction_type
		vname: LBL_TRANSACTION_TYPE
		type: enum
		options: debit_credit_dom
		len: 20
		required: true
		default: credit
		editable: false
	account
		vname: LBL_LIST_ACCOUNT_NAME
		type: ref
		reportable: false
		bean_name: Account
		massupdate: true
	subcontract
		vname: LBL_LIST_SUBCONTRACT
		type: ref
		reportable: false
		bean_name: SubContract
		required: true
		massupdate: true
	related_type
		vname: LBL_RELATED_TYPE
		type: module_name
	related
		vname: LBL_LIST_RELATED
		type: ref
		dynamic_module: related_type
		massupdate: true
	amount
		vname: LBL_AMOUNT
		type: currency
		required: true
		audited: true
		vname_list: LBL_LIST_AMOUNT
		base_field: amount_usdollar
	description
		vname: LBL_DESCRIPTION
		type: text
links
	account_link
		vname: LBL_ACCOUNT_NAME
		link_type: one
		relationship: prepaid_amount_account
	subcontract_link
		vname: LBL_SUBCONTRACT_NAME
		link_type: one
		relationship: prepaid_amount_subcontract
fields_compat
	account_name
		rname: name
		id_name: account_id
		vname: LBL_ACCOUNT_NAME
		type: relate
		table: accounts
		isnull: true
		module: Accounts
		dbType: char
		len: 255
		source: non-db
		massupdate: false
		link: account_link
		join_name: accounts
	subcontract_name
		rname: name
		id_name: subcontract_id
		vname: LBL_SUBCONTRACT_NAME
		type: relate
		table: service_subcontracts
		isnull: true
		module: SubContracts
		dbType: char
		len: 255
		source: non-db
		massupdate: false
		link: subcontract_link
		join_name: service_subcontracts
	related_name
		vname: LBL_RELATED_NAME
		type: varchar
		len: 100
		source: non-db
	created_by_name
		id_name: created_by
		type: varchar
		table: users
		source: non-db
		reportable: false
		massupdate: false
		vname: LBL_CREATED_BY_NAME
		duplicate_merge: disabled
indices
	subcon_idx
		fields
			- subcontract_id
	related_idx
		fields
			- related_type
			- related_id
relationships
	prepaid_amount_account
		key: account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	prepaid_amount_subcontract
		key: subcontract_id
		target_bean: SubContract
		target_key: id
		relationship_type: one-to-many

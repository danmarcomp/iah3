<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Service/Contract.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	table_name: service_maincontracts
	primary_key: id
	display_name
		field: contract_no
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	validate
		--
			class_function: check_account
	fill_defaults
		--
			class_function: set_number
			required_fields
				- account_id
	after_save
		--
			class_function: set_account_contract
			required_fields
				- account_id
fields
	app.id
	app.date_entered
	app.created_by_user
	app.date_modified
	app.modified_user
	app.deleted
	contract_no
		vname: LBL_CONTRACT_NAME
		type: name
		len: 25
		width: 25
		required: true
		vname_list: LBL_LIST_CONTRACT_NAME
		unified_search: true
		updateable: false
	account
		vname: LBL_ACCOUNT_NAME
		type: ref
		audited: true
		reportable: false
		bean_name: Account
		massupdate: false
		unified_search: true
		updateable: false
		required: true
	description
		vname: LBL_DESCRIPTION
		type: text
		vname_list: LBL_LIST_DESCRIPTION
	is_active
		vname: LBL_IS_ACTIVE
		type: enum
		options: contract_status_dom
		len: 3
		default: yes
		audited: true
		required: true
		massupdate: true
links
	account_link
		vname: LBL_ACCOUNT_NAME
		link_type: one
		relationship: account_main_service_contract
	subcontracts
		vname: LBL_SUBCONTRACTS
		relationship: contract_subcontracts
		bean_name: SubContract
		module: SubContracts
	app.securitygroups
		relationship: securitygroups_contract
indices
	contracts_uniq_no
		type: unique
		fields
			- contract_no
relationships
	account_main_service_contract
		key: id
		target_bean: Account
		target_key: main_service_contract_id
		relationship_type: one-to-one
	contract_subcontracts
		key: id
		target_bean: SubContract
		target_key: main_contract_id
		relationship_type: one-to-many

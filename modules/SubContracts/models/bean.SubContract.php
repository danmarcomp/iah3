<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/SubContracts/SubContract.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	table_name: service_subcontracts
	primary_key: id
	display_name
		field: name
hooks
	new_record
		--
			class_function: init_record
			required_fields
				- account_id
	after_save
		--
			class_function: update_balance
fields
	app.id
	app.date_entered
	app.created_by_user
	app.date_modified
	app.modified_user
	app.deleted
	main_contract
		vname: LBL_SUBC_MAIN_CONTRACT_NAME
		type: ref
		reportable: false
		bean_name: Contract
		updateable: false
		massupdate: true
	date_start
		vname: LBL_SUBC_DATE_START
		type: date
		massupdate: true
	date_expire
		vname: LBL_SUBC_DATE_EXPIRE
		type: date
		audited: true
		massupdate: true
	date_billed
		vname: LBL_SUBC_DATE_BILLED
		type: date
		audited: true
		massupdate: true
	name
		vname: LBL_SUBC_NAME
		type: name
		len: 100
		required: true
		unified_search: true
	vendor_contract
		vname: LBL_SUBC_VENDOR_CONTRACT_NAME
		type: varchar
		len: 100
	employee_contact
		vname: LBL_SUBC_EMPLOYEE_CONTACT_NAME
		type: ref
		audited: true
		reportable: false
		bean_name: User
		massupdate: true
	customer_contact
		vname: LBL_SUBC_CUST_CONTACT_NAME
		type: ref
		audited: true
		reportable: false
		bean_name: Contact
		massupdate: true
	contract_type
		vname: LBL_SUBC_CONTRACT_TYPE
		type: ref
		required: true
		reportable: false
		bean_name: ContractType
		massupdate: true
	description
		vname: LBL_DESCRIPTION
		type: text
	is_active
		vname: LBL_IS_ACTIVE
		type: enum
		options: contract_status_dom
		dbType: char
		len: 3
		default: yes
		audited: true
		massupdate: true
	prepaid_balance_usd
		vname: LBL_SUBC_PREPAID_BALANCE
		type: base_currency
	prepaid_balance
		vname: LBL_SUBC_PREPAID_BALANCE
		type: formula
		source: non-db
		formula
			type: function
			class: SubContract
			function: convert_balance
			fields: [id]
	total_purchase
		vname: LBL_SUBC_TOTAL_PURCHASE
		type: formula
		source: non-db
		formula
			type: function
			class: SubContract
			function: calc_total_perchase
			fields: [id]
	total_support_cost
		vname: LBL_SUBC_TOTAL_SUPPORT_COST
		type: formula
		source: non-db
		formula
			type: function
			class: SubContract
			function: calc_total_support
			fields: [id]
links
	employee_contact_link
		relationship: subcon_employee_contact
		vname: LBL_SUBC_EMPLOYEE_CONTACT_NAME
		link_type: one
		module: Users
		bean_name: User
	customer_contact_link
		relationship: subcon_customer_contact
		vname: LBL_SUBC_CUST_CONTACT_NAME
		link_type: one
		module: Contacts
		bean_name: Contact
	contract_type_link
		relationship: subcon_contract_type
		vname: LBL_SUBC_CONTRACT_TYPE
		link_type: one
		side: left
		module: ContractTypes
		bean_name: ContractType
	assets
		relationship: subcontract_assets
		vname: LBL_ASSETS
		add_existing: true
		initial_filter_fields
			main_contract.account_id: account_id
	supportedassemblies
		relationship: subcontract_supported_assemblies
		vname: LBL_SUPPORTED_ASSEMBLIES
		add_existing: true
		initial_filter_fields
			main_contract.account_id: account_id
	cases
		relationship: subcontract_cases
		vname: LBL_CASES
	prepaid_amounts
		relationship: subcontract_prepaid_amounts
		module: PrepaidAmounts
		bean_name: PrepaidAmount
		vname: LBL_PREPAID_AMOUNTS
		layout: SubContracts
indices
	customer_contact_idx
		fields
			- customer_contact_id
	employee_contact_idx
		fields
			- employee_contact_id
	main_contract_idx
		fields
			- main_contract_id
	contract_type_idx
		fields
			- contract_type_id
relationships
	subcon_employee_contact
		key: employee_contact_id
		target_bean: User
		target_key: id
		relationship_type: one-to-many
	subcon_customer_contact
		key: customer_contact_id
		target_bean: Contact
		target_key: id
		relationship_type: one-to-many
	subcon_contract_type
		key: contract_type_id
		target_bean: ContractType
		target_key: id
		relationship_type: one-to-many
	subcontract_assets
		key: id
		target_bean: Asset
		target_key: service_subcontract_id
		relationship_type: one-to-many
	subcontract_supported_assemblies
		key: id
		target_bean: SupportedAssembly
		target_key: service_subcontract_id
		relationship_type: one-to-many
	subcontract_cases
		key: id
		target_bean: aCase
		target_key: contract_id
		relationship_type: one-to-many
	subcontract_prepaid_amounts
		key: id
		target_bean: PrepaidAmount
		target_key: subcontract_id
		relationship_type: one-to-many

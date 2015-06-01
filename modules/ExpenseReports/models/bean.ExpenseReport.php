<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ExpenseReports/ExpenseReport.php
	audit_enabled: true
	unified_search: true
	duplicate_merge: true
	optimistic_locking: true
	comment: ""
	table_name: expense_reports
	primary_key: id
	display_name
		type: prefixed
		fields
			- full_number
			- assigned_user
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	before_save
		--
			class_function: set_number
		--
			class_function: set_status_date
	after_delete
		--
			class_function: update_project_costs
fields
	app.id
	app.date_entered
	app.date_modified
	app.currency
		required: true
	app.assigned_user
	app.modified_user
	app.created_by_user
	app.deleted
	prefix
		vname: LBL_EXPENSE_REPORT_PREFIX
		type: sequence_prefix
		sequence_name: expense_prefix
	report_number
		vname: LBL_EXPENSE_REPORT_SUFFIX
		type: sequence_number
		sequence_name: expense_number_sequence
		required: true
		unified_search: exact
	full_number
		vname: LBL_EXPENSE_REPORT_NUMBER
		type: name
		source
			type: sequence
			fields
				- prefix
				- report_number
		len: 20
		unified_search: exact
	total_amount
		vname: LBL_TOTAL_AMOUNT
		type: currency
		required: true
		default: 0
		audited: true
		disable_num_format: true
		vname_list: LBL_LIST_TOTAL
		base_field: total_amount_usdollar
	total_pretax
		vname: LBL_TOTAL_PRETAX
		type: currency
		required: true
		default: 0
		audited: true
		disable_num_format: true
		base_field: total_pretax_usdollar
	tax
		vname: LBL_TAX
		type: currency
		required: true
		default: 0
		audited: true
		disable_num_format: true
		base_field: tax_usdollar
	advance_currency
		vname: LBL_ADVANCE_CURRENCY
		type: currency_ref
		exchange_rate: advance_exchange_rate
		required: true
	advance_exchange_rate
		vname: LBL_ADVANCE_EXCHANGE_RATE
		type: exchange_rate
	advance
		vname: LBL_ADVANCE_AMOUNT
		type: currency
		required: true
		default: 0
		audited: true
		currency: advance_currency
		base_field: advance_usdollar
	balance
		vname: LBL_BALANCE
		type: currency
		required: true
		default: 0
		audited: true
		disable_num_format: true
		base_field: balance_usdollar
	status
		vname: LBL_STATUS
		type: enum
		len: 25
		options: expense_report_status_dom
		massupdate: false
		required: true
		editable: false
	type
		vname: LBL_TYPE
		type: enum
		len: 25
		options: expense_report_type_dom
		required: true
		massupdate: false
	description
		vname: LBL_DESCRIPTION
		type: text
		comment: Descriptive information about the expense report
	date_submitted
		vname: LBL_DATE_SUBMITTED
		type: datetime
		editable: false
		comment: Date report submitted
		massupdate: false
	date_approved
		vname: LBL_DATE_APPROVED
		type: datetime
		editable: false
		comment: Date report approved
		massupdate: false
	date_paid
		vname: LBL_DATE_PAID
		type: datetime
		editable: false
		comment: Date report paid
		massupdate: false
	approved_user
		vname: LBL_APPROVED_BY
		type: ref
		editable: false
		audited: true
		comment: The user who performed approval
		duplicate_merge: disabled
		massupdate: false
		bean_name: User
	parent_type
		vname: LBL_RELATED_TO
		type: module_name
		reportable: false
		massupdate: false
		options_limit_keys
			- Opportunities
			- Cases
			- Project
		default: Opportunities
	parent
		vname: LBL_RELATED_TO
		type: ref
		dynamic_module: parent_type
		massupdate: false
	account
		type: ref
		vname: LBL_ACCOUNT_NAME
		audited: true
		bean_name: Account
		massupdate: true
	taxes
		vname: LBL_TAX
		type: html
		reportable: false
		source
			type: function
			value_function
				- ExpenseReport
				- calc_taxes
			fields
				- total_amount
				- total_pretax
				- currency_id
links
	account
		relationship: expense_reports_accounts
		link_type: one
		side: right
		vname: LBL_ACCOUNT
	items_link
		vname: LBL_LINE_ITEMS
		relationship: expense_report_items
		side: right
		module: ExpenseReports
		bean_name: ExpenseItem
	notes
		relationship: expense_reports_notes
		vname: LBL_NOTES
relationships
	expense_report_items
		key: id
		target_bean: ExpenseItem
		target_key: report_id
		relationship_type: one-to-many
	expense_reports_notes
		key: note_id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: ExpenseReports
		relationship_type: one-to-many
	expense_reports_accounts
		key: account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	expense_opportunities
		key: parent_id
		target_bean: Opportunity
		target_key: id
		role_column: parent_type
		role_value: Opportunities
		relationship_type: one-to-many
	expense_employee
		key: parent_id
		target_bean: Employee
		target_key: id
		role_column: parent_type
		role_value: HR
		relationship_type: one-to-many
	expense_cases
		key: parent_id
		target_bean: aCase
		target_key: id
		role_column: parent_type
		role_value: Cases
		relationship_type: one-to-many
	expense_project
		key: parent_id
		target_bean: Project
		target_key: id
		role_column: parent_type
		role_value: Project
		relationship_type: one-to-many

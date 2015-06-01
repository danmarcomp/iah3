<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Project/Project.php
	audit_enabled: true
	unified_search: true
	duplicate_merge: false
	optimistic_locking: true
	table_name: project
	primary_key: id
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	after_save
		--
			class_function: copy_tasks
		--
			class_function: update_opportunity
		--
			class_function: set_account_relationchip
fields
	app.currency
		required: true
	app.id
	app.date_entered
	app.date_modified
	app.assigned_user
	app.modified_user
	app.created_by_user
	app.deleted
	name
		vname: LBL_PROJECT_NAME
		type: name
		required: true
		unified_search: true
		vname_list: LBL_LIST_NAME
	description
		vname: LBL_DESCRIPTION
		type: text
	amount
		vname: LBL_AMOUNT
		type: currency
		required: true
		audited: true
		vname_list: LBL_LIST_AMOUNT
		base_field: amount_usdollar
		display_converted: true
	project_type
		vname: LBL_TYPE
		type: enum
		options: project_type_dom
		massupdate: true
	lead_source
		vname: LBL_LEAD_SOURCE
		type: enum
		options: lead_source_dom
		len: 30
		massupdate: true
	date_starting
		vname: LBL_DATE_STARTING
		type: date
		required: true
		massupdate: true
	date_ending
		vname: LBL_DATE_ENDING
		type: date
		required: true
		massupdate: true
	project_phase
		vname: LBL_PROJECT_PHASE
		type: enum
		options: project_status_dom
		len: 30
		required: true
		audited: true
		vname_list: LBL_LIST_PROJECT_PHASE
		default: Active - Starting Soon
		massupdate: true
	percent_complete
		vname: LBL_PERCENT_COMPLETE
		default: 0
		type: int
		audited: true
		min: 0
		max: 100
	account
		type: ref
		vname: LBL_ACCOUNT
		audited: true
		bean_name: Account
		massupdate: false
		required: true
	use_timesheets
		vname: LBL_USE_TIMESHEETS
		type: bool
		default: 0
		massupdate: false
	invoice
		vname: LBL_INVOICE_ID
		type: ref
		bean_name: Invoice
		massupdate: false
	led_color
		vname: LBL_STATUS
		type: status_color
		source
			type: subselect
			class: Project
			function: led_color
	total_expected_cost
		vname: LBL_EXPECTED_COST
		type: currency
		default: 0
		base_field: total_expected_cost_usdollar
		display_converted: true
	total_expected_revenue
		vname: LBL_EXPECTED_REVENUE
		type: currency
		default: 0
		base_field: total_expected_revenue_usdollar
		display_converted: true
	total_actual_cost
		vname: LBL_ACTUAL_COST
		type: currency
		default: 0
		base_field: total_actual_cost_usdollar
		display_converted: true
	total_actual_revenue
		vname: LBL_ACTUAL_REVENUE
		type: currency
		default: 0
		base_field: total_actual_revenue_usdollar
		display_converted: true
links
	activities
		relationship: projects_activities
		module: Activities
		bean_name: Activity
		vname: LBL_ACTIVITIES_SUBPANEL_TITLE
	history
		relationship: projects_history
		module: Activities
		bean_name: ActivityHistory
		layout: History
		vname: LBL_HISTORY_SUBPANEL_TITLE
	accounts
		relationship: projects_accounts
		vname: LBL_ACCOUNTS
		module: Accounts
		bean_name: Account
	contacts
		relationship: projects_contacts
		vname: LBL_CONTACTS
		module: Contacts
		bean_name: Contact
	opportunities
		relationship: projects_opportunities
		vname: LBL_OPPORTUNITIES
		module: Opportunities
		bean_name: Opportunity
	quotes
		relationship: projects_quotes
		vname: LBL_QUOTES
		module: Quotes
		bean_name: Quote
	invoices
		relationship: projects_invoices
		link_type: one
		side: right
		vname: LBL_INVOICES
		module: Invoice
		bean_name: Invoice
	tasks
		relationship: project_tasks
		vname: LBL_TASKS
		module: Tasks
		bean_name: Task
	notes
		relationship: project_notes
		vname: LBL_NOTES
		module: Notes
		bean_name: Note
	meetings
		relationship: project_meetings
		vname: LBL_MEETINGS
		module: Meetings
		bean_name: Meeting
	calls
		relationship: project_calls
		vname: LBL_CALLS
		module: Calls
		bean_name: Call
	emails
		relationship: project_emails
		vname: LBL_EMAILS
		module: Emails
		bean_name: Email
	documents
		relationship: documents_projects
		module: Documents
		bean_name: Document
		vname: LBL_DOCUMENTS
	project_tasks
		relationship: project_project_tasks
		module: ProjectTask
		bean_name: ProjectTask
		vname: LBL_PROJECT_TASKS
	booked_hours
		relationship: project_booked_hours
		vname: LBL_BOOKED_HOURS
		module: Booking
		bean_name: BookedHours
		updateable: false
	project_financials
		relationship: project_project_financials
		module: Project
		bean_name: ProjectFinancials
		vname: LBL_PROJECT_FINANCIALS
	assets
		relationship: project_assets
		vname: LBL_ASSETS
		module: Assets
		bean_name: Asset
		add_existing: true
		initial_filter_fields
			account_id: account_id
	supportedassemblies
		relationship: project_supported_assemblies
		vname: LBL_SUPPORTED_ASSEMBLIES
		module: SupportedAssemblies
		bean_name: SupportedAssembly
		add_existing: true
		initial_filter_fields
			account_id: account_id
	threads
		relationship: project_threads
		module: threads
		bean_name: Thread
		vname: LBL_THREADS
	expensereports
		relationship: expense_project
		vname: LBL_EXPENSE_REPORTS
		module: ExpenseReports
		bean_name: ExpenseReport
	app.securitygroups
		relationship: securitygroups_project
	this_month
		vname: LBL_LIST_THIS_MONTH
		type: ref
		link_type: one
		bean_name: ProjectFinancials
		class: Project
		function: financials_link
	next_month
		vname: LBL_LIST_NEXT_MONTH
		type: ref
		link_type: one
		bean_name: ProjectFinancials
		class: Project
		function: financials_link
indices
	project_name_index
		fields
			- name
relationships
	project_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: Project
		relationship_type: one-to-many
	project_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: Project
		relationship_type: one-to-many
	project_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: Project
		relationship_type: one-to-many
	project_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Project
		relationship_type: one-to-many
	project_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: Project
		relationship_type: one-to-many
	project_project_tasks
		key: id
		target_bean: ProjectTask
		target_key: parent_id
		relationship_type: one-to-many
	project_project_financials
		key: id
		target_bean: ProjectFinancials
		target_key: project_id
		relationship_type: one-to-many
	project_assets
		key: id
		target_bean: Asset
		target_key: project_id
		relationship_type: one-to-many
	project_supported_assemblies
		key: id
		target_bean: SupportedAssembly
		target_key: project_id
		relationship_type: one-to-many
	project_threads
		key: id
		target_bean: Thread
		target_key: id
		join
			table: project_threads
			source_key: project_id
			target_key: thread_id
		relationship_type: many-to-many
	project_booked_hours
		key: id
		target_bean: BookedHours
		target_key: related_id
		join
			table: ProjectTask
			source_key: parent_id
			target_key: id
		relationship_type: one-to-many
	projects_activities
		target_bean: Activity
		target_key: id
		sources
			meetings
				relationship: project_meetings
			calls
				relationship: project_calls
			tasks
				relationship: project_tasks
	projects_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: project_meetings
			calls
				relationship: project_calls
			tasks
				relationship: project_tasks
			notes
				relationship: project_notes
			emails
				relationship: emails_projects_rel

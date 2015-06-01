<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Opportunities/Opportunity.php
	audit_enabled: true
	unified_search: true
	activity_log_enabled: true
	duplicate_merge: true
	optimistic_locking: true
	comment: An opportunity is the target of selling activities
	table_name: opportunities
	primary_key: id
	default_order_by: name
	importable: LBL_OPPORTUNITIES
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	notify
		--
			class_function: send_notification
	after_save
		--
			class_function: after_save
	after_delete
		--
			class_function: update_forecasts
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	app.deleted
	app.currency
		required: true
	forecast_category
		vname: LBL_FORECAST_CATEGORY
		type: enum
		options: forecast_category_dom
		len: 25
		audited: true
		required: true
		default: Pipeline
		massupdate: true
	name
		vname: LBL_OPPORTUNITY_NAME
		type: name
		len: 80
		unified_search: true
		comment: Name of the opportunity
		vname_list: LBL_LIST_OPPORTUNITY_NAME
		required: true
	opportunity_type
		vname: LBL_TYPE
		type: enum
		options: opportunity_type_dom
		len: 25
		audited: true
		comment: Type of opportunity (ex: Existing, New)
		massupdate: true
	account
		vname: LBL_ACCOUNT
		type: ref
		audited: true
		bean_name: Account
		required: true
		massupdate: true
	campaign
		comment: Campaign that generated lead
		vname: LBL_CAMPAIGN
		type: ref
		bean_name: Campaign
		massupdate: false
		duplicate_merge: disabled
	lead_source
		vname: LBL_LEAD_SOURCE
		type: enum
		options: lead_source_dom
		comment: Source of the opportunity
		massupdate: true
	amount
		vname: LBL_AMOUNT
		type: currency
		comment: Unconverted amount of the opportunity
		dbType: double
		vname_list: LBL_LIST_AMOUNT
		base_field: amount_usdollar
		required: true
		display_converted: true
	date_closed
		vname: LBL_DATE_CLOSED
		type: date
		audited: true
		comment: Expected or actual date the oppportunity will close
		vname_list: LBL_LIST_DATE_CLOSED
		required: true
		massupdate: true
	next_step
		vname: LBL_NEXT_STEP
		type: varchar
		len: 100
		comment: The next step in the sales process
	sales_stage
		vname: LBL_SALES_STAGE
		type: enum
		options: sales_stage_dom
		len: 25
		audited: true
		comment: Indication of progression towards closure
		vname_list: LBL_LIST_SALES_STAGE
		required: true
		default: Prospecting
		massupdate: true
	probability
		vname: LBL_PROBABILITY
		type: percentage
		display_format: rounded
		disable_num_format: true
		audited: true
		comment: The probability of closure
		dbType: double
		vname_list: LBL_LIST_PROBABILITY
	description
		vname: LBL_DESCRIPTION
		type: text
		comment: Full description of the opportunity
	partner
		vname: LBL_PARTNER
		type: ref
		reportable: false
		bean_name: Partner
		massupdate: true
	weighted_amount
		vname: LBL_WEIGHTED_AMOUNT
		type: base_currency
		display_converted: true
		source
			value_function: [Opportunity, calc_weighted_amount]
			fields
				amount: amount_usdollar
				probability: probability
links
	activities
		relationship: opportunities_activities
		module: Activities
		bean_name: Activity
		vname: LBL_ACTIVITIES_SUBPANEL_TITLE
	history
		relationship: opportunities_history
		module: Activities
		bean_name: ActivityHistory
		layout: History
		vname: LBL_HISTORY_SUBPANEL_TITLE
	documents
		relationship: documents_opportunities
		module: Documents
		bean_name: Document
		vname: LBL_DOCUMENTS
	threads
		relationship: opportunities_threads
		module: threads
		bean_name: Thread
		vname: LBL_THREADS
	parent_account
		relationship: opportunities_parent_account
		link_type: one
		module: Accounts
		bean_name: Account
		vname: LBL_ACCOUNTS
	accounts
		relationship: accounts_opportunities
		module: Accounts
		bean_name: Account
		vname: LBL_ACCOUNTS
	contacts
		relationship: opportunities_contacts
		module: Contacts
		bean_name: Contact
		vname: LBL_CONTACTS
	tasks
		relationship: opportunity_tasks
		vname: LBL_TASKS
		bean_name: Task
	notes
		relationship: opportunity_notes
		vname: LBL_NOTES
		bean_name: Note
	meetings
		relationship: opportunity_meetings
		vname: LBL_MEETINGS
		bean_name: Meeting
	calls
		relationship: opportunity_calls
		vname: LBL_CALLS
		bean_name: Call
	emails
		relationship: emails_opportunities_rel
		side: right
		vname: LBL_EMAILS
		bean_name: Email
	expensereports
		relationship: expense_opportunities
		vname: LBL_EXPENSE_REPORTS
		bean_name: ExpenseReport
	quotes
		relationship: quotes_opportunities
		vname: LBL_QUOTES
		bean_name: Quote
	salesorders
		relationship: so_opportunities
		vname: LBL_SALES_ORDERS
		bean_name: SalesOrder
	invoices
		relationship: invoices_opportunities
		vname: LBL_INVOICES
		bean_name: Invoice
	converted_lead
		relationship: lead_converted_opp
		vname: LBL_CONVERTED_LEAD
	partner_link
		vname: LBL_PARTNER
		relationship: opportunities_partners
		link_type: one
		side: left
		module: Partners
		bean_name: Partner
	project
		relationship: projects_opportunities
		vname: LBL_PROJECTS
		bean_name: Project
	leads
		relationship: opportunity_leads
		vname: LBL_LEADS
		bean_name: Lead
	campaigns
		relationship: opportunities_campaign
		module: Campaigns
		bean_name: Campaign
		vname: LBL_CAMPAIGNS
	app.securitygroups
		relationship: securitygroups_opportunities
indices
	idx_opp_name
		fields
			- name
	idx_opp_assigned
		fields
			- assigned_user_id
relationships
	opportunity_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: Opportunities
		relationship_type: one-to-many
	opportunity_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: Opportunities
		relationship_type: one-to-many
	opportunity_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: Opportunities
		relationship_type: one-to-many
	opportunity_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Opportunities
		relationship_type: one-to-many
	opportunity_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: Opportunities
		relationship_type: one-to-many
	opportunity_leads
		key: id
		target_bean: Lead
		target_key: opportunity_id
		relationship_type: one-to-many
	opportunities_campaign
		key: campaign_id
		target_bean: Campaign
		target_key: id
		relationship_type: one-to-many
	opportunities_parent_account
		key: account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	opportunities_threads
		key: id
		target_bean: Thread
		target_key: id
		join
			table: opportunities_threads
			source_key: opportunity_id
			target_key: thread_id
		relationship_type: many-to-many
	opportunities_activities
		target_bean: Activity
		target_key: id
		sources
			meetings
				relationship: opportunity_meetings
			calls
				relationship: opportunity_calls
			tasks
				relationship: opportunity_tasks
	opportunities_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: opportunity_meetings
			calls
				relationship: opportunity_calls
			tasks
				relationship: opportunity_tasks
			notes
				relationship: opportunity_notes
			emails
				relationship: emails_opportunities_rel

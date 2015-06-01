<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Cases/Case.php
	audit_enabled: true
	unified_search: true
	activity_log_enabled: true
	duplicate_merge: true
	optimistic_locking: true
	comment:
		Cases are issues or problems that a customer asks a support representative
		to resolve
	table_name: cases
	primary_key: id
	default_order_by: case_number
	display_name
		type: prefixed
		fields
			- case_number
			- name
	importable: LBL_CASES
	reportable: true
hooks
	new_record
		--
			class_function: init_record
		--
			class_function: init_from_email
	before_save
		--
			class_function: before_save
	after_save
		--
			class_function: after_save
	notify
		--
			class_function: send_notification
			required_fields
				- cust_contact_id
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	app.deleted
	case_number
		vname: LBL_NUMBER
		type: sequence_number
		sequence_name: case_number_sequence
		required: true
		isnull: false
		unified_search: true
		comment: Visible case identifier
		vname_list: LBL_LIST_NUMBER
		editable: false
	effort_actual
		vname: LBL_TIME_USED
		type: float
		dbType: double
	effort_actual_unit
		vname: LBL_TIME_USED_UNIT
		type: enum
		options: task_effort_unit_dom
		len: 20
		massupdate: false
	travel_time
		vname: LBL_TRAVEL_TIME
		type: duration
	travel_time_unit
		vname: LBL_TRAVEL_TIME_UNIT
		type: enum
		options: task_effort_unit_dom
		len: 20
		massupdate: false
	arrival_time
		vname: LBL_ARRIVAL_TIME
		type: varchar
		len: 30
	cust_req_no
		vname: LBL_CUSTOMER_REQ_NO
		type: varchar
		len: 30
	cust_contact
		vname: LBL_CUST_CONTACT
		type: ref
		bean_name: Contact
		massupdate: true
	cust_phone_no
		vname: LBL_CONTACT_PHONE
		type: phone
		len: 30
	date_closed
		vname: LBL_DATE_CLOSED
		type: date
		massupdate: true
	date_billed
		vname: LBL_DATE_BILLED
		type: date
		massupdate: true
	vendor_rma_no
		vname: LBL_VENDOR_RMA_NO
		type: varchar
		len: 30
	vendor_svcreq_no
		vname: LBL_VENDOR_SVCREQ_NO
		type: varchar
		len: 30
	contract
		vname: LBL_CONTRACT_NAME
		type: ref
		bean_name: SubContract
		massupdate: true
	asset
		vname: LBL_ASSET
		type: ref
		bean_name: Asset
		massupdate: true
	asset_serial_no
		vname: LBL_ASSET_SERIAL
		type: varchar
		len: 100
	category
		vname: LBL_CATEGORY
		type: enum
		options: case_category_dom
		len: 40
		massupdate: true
	type
		vname: LBL_TYPE
		type: enum
		options: case_type_dom
		len: 40
		massupdate: true
	name
		vname: LBL_LIST_SUBJECT
		type: name
		len: 255
		unified_search: true
		comment: The subject of the case
		required: true
		multiline: true
		width: 60
		height: 2
	account
		type: ref
		vname: LBL_PRIMARY_ACCOUNT
		audited: true
		comment: The account to which the case is associated
		bean_name: Account
		massupdate: false
	status
		vname: LBL_STATUS
		type: enum
		options: case_status_dom
		len: 25
		audited: true
		comment: The status of the case
		vname_list: LBL_LIST_STATUS
		default: Active - New
		required: true
		massupdate: true
	priority
		vname: LBL_PRIORITY
		type: enum
		options: case_priority_dom
		len: 25
		audited: true
		comment: The priority of the case
		vname_list: LBL_LIST_PRIORITY
		default: P2
		required: true
		massupdate: true
	description
		vname: LBL_DESCRIPTION
		type: text
		fulltext_search: true
		comment: The case description
	resolution
		vname: LBL_RESOLUTION
		type: text
		fulltext_search: true
		comment: The resolution of the case
	age
		vname: LBL_AGE
		type: age
		source
			field: date_entered
			format: age
		massupdate: false
		sort_on: date_entered
links
	activities
		relationship: cases_activities
		module: Activities
		bean_name: Activity
		vname: LBL_ACTIVITIES_SUBPANEL_TITLE
	history
		relationship: cases_history
		module: Activities
		bean_name: ActivityHistory
		layout: History
		vname: LBL_HISTORY_SUBPANEL_TITLE
	documents
		relationship: documents_cases
		module: Documents
		bean_name: Document
		vname: LBL_DOCUMENTS
	booked_hours
		relationship: booked_hours_cases
		link_type: one
		side: right
		layout: Quick
		vname: LBL_BOOKED_HOURS
	invoices
		relationship: cases_invoices
		link_type: one
		side: right
		module: Invoice
		bean_name: Invoice
		vname: LBL_INVOICES
	threads
		relationship: cases_threads
		module: threads
		bean_name: Thread
		vname: LBL_THREADS
	kb_articles
		relationship: kb_articles_cases
		module: KBArticles
		bean_name: KBArticle
		vname: LBL_KB_ARTICLES
	expensereports
		relationship: expense_cases
		vname: LBL_EXPENSE_REPORTS
	tasks
		relationship: case_tasks
		vname: LBL_TASKS
	notes
		relationship: case_notes
		vname: LBL_NOTES
	meetings
		relationship: case_meetings
		bean_name: Meeting
		vname: LBL_MEETINGS
	emails
		relationship: emails_cases_rel
		vname: LBL_EMAILS
	calls
		relationship: case_calls
		vname: LBL_CALLS
	bugs
		relationship: cases_bugs
		vname: LBL_BUGS
	skills
		relationship: cases_skills
		vname: LBL_SKILLS
	contacts
		relationship: contacts_cases
		vname: LBL_CONTACTS
	accounts
		relationship: accounts_cases
		vname: LBL_ACCOUNTS
	products
		relationship: products_cases
		module: ProductCatalog
		bean_name: Product
		vname: LBL_PRODUCTS
	app.securitygroups
		relationship: securitygroups_cases
	prepaid_amounts
		relationship: cases_prepaid_amounts
		module: PrepaidAmounts
		bean_name: PrepaidAmount
		vname: LBL_PREPAID_AMOUNTS
		layout: Cases
indices
	case_number
		fields
			- case_number
	idx_case_name
		fields
			- name
relationships
	case_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: Cases
		relationship_type: one-to-many
	case_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: Cases
		relationship_type: one-to-many
	case_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Cases
		relationship_type: one-to-many
	case_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: Cases
		relationship_type: one-to-many
	case_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: Cases
		relationship_type: one-to-many
	cases_assets
		key: asset_id
		target_bean: Asset
		target_key: id
		relationship_type: one-to-many
	cases_subcontracts
		key: contract_id
		target_bean: SubContract
		target_key: id
		relationship_type: one-to-many
	cases_cust_contacts
		key: cust_contact_id
		target_bean: Contact
		target_key: id
		relationship_type: one-to-many
	cases_threads
		key: id
		target_bean: Thread
		target_key: id
		join
			table: cases_threads
			source_key: case_id
			target_key: thread_id
		relationship_type: many-to-many
	cases_prepaid_amounts
		key: id
		target_bean: PrepaidAmount
		target_key: related_id
		role_column: related_type
		role_value: Cases
		relationship_type: one-to-many
	cases_activities
		target_bean: Activity
		target_key: id
		sources
			meetings
				relationship: case_meetings
			calls
				relationship: case_calls
			tasks
				relationship: case_tasks
	cases_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: case_meetings
			calls
				relationship: case_calls
			tasks
				relationship: case_tasks
			notes
				relationship: case_notes
			emails
				relationship: emails_cases_rel

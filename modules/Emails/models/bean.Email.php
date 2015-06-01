<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Emails/Email.php
	unified_search: true
	duplicate_merge: false
	table_name: emails
	primary_key: id
	default_order_by: date_start DESC
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	before_save
		--
			class_function: before_save
	after_save
		--
			class_function: after_save
		--
			class_function: update_parent_activity
	delete
		--
			class_function: mark_deleted
	notify
		--
			class_function: send_notification
	fill_defaults
		--
			class_function: set_default_folder
		--
			class_function: fix_date_start
fields
	app.id
	app.date_entered
	app.date_modified
	app.assigned_user
	app.modified_user
	app.created_by_user
	app.deleted
	send_error
		vname: LBL_SEND_ERROR
		type: bool
		reportable: false
		massupdate: false
	replace_fields
		vname: LBL_REPLACE_TEMPLATE_FIELDS
		type: bool
		default: 0
		massupdate: false
	mailbox
		type: ref
		vname: LBL_MAILBOX
		bean_name: EmailPOP3
		massupdate: false
	message_id
		type: varchar
		len: 255
		charset: ascii
		vname: LBL_MESSAGE_ID
		reportable: false
	thread_id
		type: varchar
		len: 40
		charset: ascii
		vname: LBL_THREAD_ID
		reportable: false
	in_reply_to
		type: varchar
		len: 255
		charset: ascii
		vname: LBL_IN_REPLY_TO
		reportable: false
	intent
		vname: LBL_INTENT
		type: char
		len: 25
		default: pick
		reportable: false
	name
		vname: LBL_SUBJECT
		type: name
		required: true
		len: 255
		width: 70
		vname_list: LBL_LIST_SUBJECT
		unified_search: true
	date_start
		vname: LBL_DATE
		type: datetime
		massupdate: false
		vname_list: LBL_LIST_CREATED
	parent_type
		vname: LBL_PARENT_TYPE
		type: module_name
		default: Accounts
	parent
		vname: LBL_RELATED_TO
		vname_list: LBL_LIST_RELATED_TO
		type: ref
		dynamic_module: parent_type
		massupdate: true
	contact
		vname: LBL_CONTACT_NAME
		vname_list: LBL_LIST_CONTACT
		type: ref
		bean_name: Contact
		massupdate: false
	from_addr
		vname: LBL_FROM
		type: varchar
		len: 100
	from_name
		vname: LBL_FROM_NAME
		type: varchar
		len: 100
	reply_to_addr
		vname: LBL_REPLY_TO
		type: varchar
		len: 100
	reply_to_name
		vname: LBL_REPLY_TO_NAME
		type: varchar
		len: 100
	to_addrs
		vname: LBL_TO
		type: email_recips
	cc_addrs
		vname: LBL_CC
		type: email_recips
	bcc_addrs
		vname: LBL_BCC
		type: email_recips
	to_addrs_ids
		vname: LBL_TO_IDS
		type: text
		reportable: false
	to_addrs_names
		vname: LBL_TO_NAMES
		type: text
		reportable: false
	to_addrs_emails
		vname: LBL_TO_EMAILS
		type: text
		reportable: false
	cc_addrs_ids
		vname: LBL_CC_IDS
		type: text
		reportable: false
	cc_addrs_names
		vname: LBL_CC_NAMES
		type: text
		reportable: false
	cc_addrs_emails
		vname: LBL_CC_EMAILS
		type: text
		reportable: false
	bcc_addrs_ids
		vname: LBL_BCC_IDS
		type: text
		reportable: false
	bcc_addrs_names
		vname: LBL_BCC_NAMES
		type: text
		reportable: false
	bcc_addrs_emails
		vname: LBL_BCC_EMAILS
		type: text
		reportable: false
	type
		vname: LBL_LIST_TYPE
		type: enum
		options: dom_email_types
		len: 25
		massupdate: false
	status
		vname: LBL_STATUS
		len: 25
		type: enum
		options: dom_email_types
		massupdate: false
	folder_ref
		type: ref
		bean_name: EmailFolder
		id_name: folder
		vname: LBL_FOLDER
		options_function
			- EmailFolder
			- get_folder_options
		required: true
		massupdate: true
	folder_reserved
		type: varchar
		vname: LBL_FOLDER_RESERVED
		source
			type: field
			field: folder_ref.reserved
	isread
		dbType: bool
		type: enum
		options: email_isread_dom
		vname: LBL_IS_READ
		required: true
		default: 0
		massupdate: true
	description
		vname: LBL_TEXT_BODY
		type: html
		updateable: true
		fulltext_search: true
		source
			field: body.description
	description_html
		vname: LBL_HTML_BODY
		type: html
		updateable: true
		fulltext_search: true
		source
			field: body.description_html
	has_attach
		vname: LBL_HAS_ATTACHMENTS
		type: attach_icon
		source
			type: subselect
			class: Email
			function: has_attach
links
	body
		relationship: emails_emails_bodies
		link_type: one
		side: right
		vname: LBL_DESCRIPTION
	related_emails
		relationship: emails_related_emails
		no_create: true
		vname: LBL_EMAILS_SUBPANEL_TITLE
	quotes
		vname: LBL_EMAILS_QUOTE_REL
		relationship: emails_quotes_rel
		module: Quotes
		bean_name: Quote
	invoice
		vname: LBL_EMAILS_INVOICE_REL
		relationship: emails_invoices_rel
		module: Invoice
		bean_name: Invoice
	purchaseorders
		vname: LBL_EMAILS_PURCHASEORDER_REL
		relationship: emails_purchaseorders_rel
		module: PurchaseOrders
		bean_name: PurchaseOrder
	salesorders
		vname: LBL_EMAILS_SALESORDER_REL
		relationship: emails_salesorders_rel
		module: SalesOrders
		bean_name: SalesOrder
	users
		relationship: emails_users_rel
		vname: LBL_USERS
	contacts
		relationship: emails_contacts_rel
		vname: LBL_CONTACTS
	accounts
		vname: LBL_EMAILS_ACCOUNTS_REL
		relationship: emails_accounts_rel
		module: Accounts
		bean_name: Account
	bugs
		vname: LBL_EMAILS_BUGS_REL
		relationship: emails_bugs_rel
		module: Bugs
		bean_name: Bug
	cases
		vname: LBL_EMAILS_CASES_REL
		relationship: emails_cases_rel
		module: Cases
		bean_name: aCase
	leads
		vname: LBL_EMAILS_LEADS_REL
		relationship: emails_leads_rel
		module: Leads
		bean_name: Lead
	tasks
		vname: LBL_EMAILS_TASKS_REL
		relationship: emails_tasks_rel
		module: Tasks
		bean_name: Task
	opportunities
		vname: LBL_EMAILS_OPPORTUNITIES_REL
		relationship: emails_opportunities_rel
		module: Opportunities
		bean_name: Opportunity
	project
		vname: LBL_EMAILS_PROJECT_REL
		relationship: emails_projects_rel
		module: Project
		bean_name: Project
	projecttask
		vname: LBL_EMAILS_PROJECT_TASK_REL
		relationship: emails_project_task_rel
		module: ProjectTask
		bean_name: ProjectTask
	prospects
		vname: LBL_EMAILS_PROSPECT_REL
		relationship: emails_prospects_rel
		module: Prospects
		bean_name: Prospect
	app.securitygroups
		relationship: securitygroups_emails
indices
	idx_email_name
		fields
			- name
	idx_email_dt
		fields
			- date_entered
			- deleted
	idx_email_mod
		fields
			- date_modified
			- status
	idx_email_mid
		fields
			- message_id
	idx_email_select
		fields
			- folder
			- deleted
			- isread
			- assigned_user_id
	idx_email_ftext_s
		type: fulltext
		fields
			- name
relationships
	emails_opportunities_rel
		key: id
		target_bean: Opportunity
		target_key: id
		join
			table: emails_opportunities
			source_key: email_id
			target_key: opportunity_id
		relationship_type: many-to-many
	emails_cases_rel
		key: id
		target_bean: aCase
		target_key: id
		join
			table: emails_cases
			source_key: email_id
			target_key: case_id
		relationship_type: many-to-many
	emails_bugs_rel
		key: id
		target_bean: Bug
		target_key: id
		join
			table: emails_bugs
			source_key: email_id
			target_key: bug_id
		relationship_type: many-to-many
	emails_accounts_rel
		key: id
		target_bean: Account
		target_key: id
		join
			table: emails_accounts
			source_key: email_id
			target_key: account_id
		relationship_type: many-to-many
	emails_leads_rel
		key: id
		target_bean: Lead
		target_key: id
		join
			table: emails_leads
			source_key: email_id
			target_key: lead_id
		relationship_type: many-to-many
	emails_tasks_rel
		key: id
		target_bean: Task
		target_key: id
		join
			table: emails_tasks
			source_key: email_id
			target_key: task_id
		relationship_type: many-to-many
	emails_emails_bodies
		key: id
		target_bean: emails_bodies
		target_key: email_id
		relationship_type: one-to-one
	emails_related_emails
		key: thread_id
		target_bean: Email
		target_key: thread_id
		relationship_type: one-to-many

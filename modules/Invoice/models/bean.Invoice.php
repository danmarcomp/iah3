<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Invoice/Invoice.php
	audit_enabled: true
	unified_search
		enabled: true
		default: false
	activity_log_enabled: true
	duplicate_merge: false
	table_name: invoice
	primary_key: id
	default_order_by: full_number
	display_name
		type: prefixed
		fields
			- full_number
			- name
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
	after_delete
		--
			class_function: after_delete
	notify
		--
			class_function: send_notification
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	app.recurrence_fields
	app.deleted
	app.currency
		required: true
	prefix
		vname: LBL_INVOICE_PREFIX
		type: sequence_prefix
		sequence_name: invoice_prefix
	invoice_number
		vname: LBL_INVOICE_SUFFIX
		type: sequence_number
		sequence_name: invoice_number_sequence
		required: true
		unified_search: exact
	full_number
		vname: LBL_INVOICE_NUMBER
		vname_list: LBL_LIST_NUMBER
		type: item_number
		source
			type: sequence
			fields
				- prefix
				- invoice_number
		unified_search: exact
	from_quote
		vname: LBL_FROM_QUOTE_NUMBER
		type: ref
		bean_name: Quote
		massupdate: false
	from_so
		vname: LBL_FROM_SO_NUMBER
		type: ref
		bean_name: SalesOrder
		massupdate: false
	shipping_stage
		vname: LBL_SHIPPING_STAGE
		type: enum
		options: shipping_stage_dom
		len: 40
		audited: true
		massupdate: false
		default: Pending
		vname_list: LBL_LIST_SHIPPING_STAGE
		required: true
	cancelled
		vname: LBL_CANCELLED
		type: bool
		required: true
		default: 0
		audited: true
		massupdate: false
	products_created
		vname: LBL_PRODUCTS_CREATED
		type: bool
		default: 0
		audited: true
		reportable: false
		importable: false
		massupdate: false
	name
		vname: LBL_INVOICE_SUBJECT
		type: name
		len: 100
		unified_search: true
		vname_list: LBL_LIST_SUBJECT
		required: true
	opportunity
		vname: LBL_OPPORTUNITY_NAME
		type: ref
		bean_name: Opportunity
		massupdate: true
	purchase_order_num
		vname: LBL_PURCHASE_ORDER_NUM
		type: varchar
		len: 100
	invoice_date
		vname: LBL_INVOICE_DATE
		type: date
		required: true
		massupdate: true
	due_date
		vname: LBL_DUE_DATE
		type: date
		required: true
		vname_list: LBL_LIST_DUE_DATE
		massupdate: true
	partner
		vname: LBL_PARTNER
		type: ref
		bean_name: Partner
		massupdate: true
	billing_account
		vname: LBL_BILLING_ACCOUNT
		type: ref
		bean_name: Account
		massupdate: false
		required: true
		add_filters
			--
				param: is_supplier
				value: 0
	billing_contact
		vname: LBL_BILLING_CONTACT
		type: ref
		bean_name: Contact
		massupdate: false
	billing_address_street
		vname: LBL_BILLING_ADDRESS_STREET
		type: varchar
		len: 150
	billing_address_city
		vname: LBL_BILLING_ADDRESS_CITY
		type: varchar
		len: 100
	billing_address_state
		vname: LBL_BILLING_ADDRESS_STATE
		type: varchar
		len: 100
	billing_address_postalcode
		vname: LBL_BILLING_ADDRESS_POSTALCODE
		type: varchar
		len: 20
	billing_address_country
		vname: LBL_BILLING_ADDRESS_COUNTRY
		type: varchar
		len: 100
	billing_phone
		vname: LBL_BILLING_PHONE
		type: phone
	billing_email
		vname: LBL_BILLING_EMAIL
		type: email
	shipping_account
		vname: LBL_SHIPPING_ACCOUNT
		type: ref
		bean_name: Account
		massupdate: false
	shipping_contact
		vname: LBL_SHIPPING_CONTACT
		type: ref
		bean_name: Contact
		massupdate: false
	shipping_address_street
		vname: LBL_SHIPPING_ADDRESS_STREET
		type: varchar
		len: 150
	shipping_address_city
		vname: LBL_SHIPPING_ADDRESS_CITY
		type: varchar
		len: 100
	shipping_address_state
		vname: LBL_SHIPPING_ADDRESS_STATE
		type: varchar
		len: 100
	shipping_address_postalcode
		vname: LBL_SHIPPING_ADDRESS_POSTALCODE
		type: varchar
		len: 20
	shipping_address_country
		vname: LBL_SHIPPING_ADDRESS_COUNTRY
		type: varchar
		len: 100
	shipping_phone
		vname: LBL_SHIPPING_PHONE
		type: phone
	shipping_email
		vname: LBL_SHIPPING_EMAIL
		type: email
	shipping_provider
		vname: LBL_SHIPPING_PROVIDER
		type: ref
		bean_name: ShippingProvider
		massupdate: true
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
		editable: false
		default: 0
	amount_due
		vname: LBL_AMOUNT_DUE
		type: currency
		required: true
		audited: true
		vname_list: LBL_LIST_AMOUNT_DUE
		base_field: amount_due_usdollar
		editable: false
		default: 0
	gross_profit
		vname: LBL_GROSS_PROFIT
		type: currency
		base_field: gross_profit_usdollar
		disabled: true
	gross_profit_pc
		vname: LBL_GROSS_PROFIT_PERCENT
		type: double
		disabled: true
	subtotal
		vname: LBL_SUBTOTAL
		type: currency
		required: true
		editable: false
		default: 0
	pretax
		vname: LBL_PRETAX
		type: currency
		required: true
		audited: true
		vname_list: LBL_LIST_PRETAX
		editable: false
	terms
		vname: LBL_TERMS
		type: enum
		options: terms_dom
		len: 25
		required: true
		audited: true
		massupdate: true
	tax_information
		vname: LBL_TAX_INFORMATION
		type: varchar
		len: 150
	show_components
		vname: LBL_SHOW_COMPONENTS
		type: enum
		options: quote_show_components_dom
		len: 40
		isnull: false
		default: ""
		massupdate: false
	tax_exempt
		vname: LBL_TAX_EXEMPT
		type: bool
		required: true
		default: 0
		massupdate: false
	discount_before_taxes
		vname: LBL_DISCOUNT_BEFORE_TAXES
		type: bool
		required: true
		default: 0
		massupdate: false
	event
		vname: LBL_EVENT_NAME
		type: ref
		bean_name: EventSession
		massupdate: true
links
	activities
		relationship: invoices_activities
		module: Activities
		bean_name: Activity
		vname: LBL_ACTIVITIES_SUBPANEL_TITLE
	history
		relationship: invoices_history
		module: Activities
		bean_name: ActivityHistory
		layout: History
		vname: LBL_HISTORY_SUBPANEL_TITLE
	line_groups_link
		vname: LBL_LINE_GROUPS
		relationship: invoices_line_groups
		module: Invoice
		bean_name: InvoiceLineGroup
	lines_link
		vname: LBL_LINE_ITEMS
		relationship: invoices_line_items
		module: Invoice
		bean_name: InvoiceLine
	adjustments_link
		vname: LBL_ADJUSTMENTS
		relationship: invoices_adjustments
		module: Invoice
		bean_name: InvoiceAdjustment
	comments_link
		vname: LBL_COMMENTS
		relationship: invoices_comments
		side: right
		module: Invoice
		bean_name: InvoiceComment
	tasks
		relationship: invoice_tasks
		vname: LBL_TASKS
	notes
		relationship: invoice_notes
		vname: LBL_NOTES
	meetings
		relationship: invoice_meetings
		vname: LBL_MEETINGS
	calls
		relationship: invoice_calls
		vname: LBL_CALLS
	emails
		relationship: invoice_emails
		vname: LBL_EMAILS
	projects
		relationship: projects_invoices
		vname: LBL_PROJECTS
	monthlyservices
		relationship: services_invoices
		vname: LBL_SERVICES
	cases
		relationship: cases_invoices
		vname: LBL_CASES
	payments
		relationship: invoices_payments
		vname: LBL_PAYMENTS
		layout: Invoice
	creditnotes
		relationship: credit_notes_invoices
		vname: LBL_CREDIT_NOTES
	shipping
		vname: LBL_SHIPPING
		relationship: invoice_shipping_rel
		side: left
		module: Shipping
		bean_name: Shipping
	app.securitygroups
		relationship: securitygroups_invoice
indices
	idx_invoice_number
		fields
			- invoice_number
	idx_invoice_name
		fields
			- name
	idx_invoice_billacct
		fields
			- billing_account_id
	idx_invoice_shipacct
		fields
			- shipping_account_id
	idx_opportunity_id
		fields
			- opportunity_id
	idx_from_quote_id
		fields
			- from_quote_id
	idx_deleted
		fields
			- deleted
	idx_from_so_id
		fields
			- from_so_id
	idx_assigned_user_id
		fields
			- assigned_user_id
	idx_partner_id
		fields
			- partner_id
relationships
	invoice_billto_accounts
		key: billing_account_id
		target_bean: Account
		target_key: id
		relationship_type: many-to-one
	invoice_shipto_accounts
		key: shipping_account_id
		target_bean: Account
		target_key: id
		relationship_type: many-to-one
	invoice_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: Invoice
		relationship_type: one-to-many
	invoice_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: Invoice
		relationship_type: one-to-many
	invoice_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: Invoice
		relationship_type: one-to-many
	invoice_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Invoice
		relationship_type: one-to-many
	invoice_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: Invoice
		relationship_type: one-to-many
	invoices_line_groups
		key: id
		target_bean: InvoiceLineGroup
		target_key: parent_id
		relationship_type: one-to-many
	invoices_line_items
		key: id
		target_bean: InvoiceLine
		target_key: invoice_id
		relationship_type: one-to-many
	invoices_adjustments
		key: id
		target_bean: InvoiceAdjustment
		target_key: invoice_id
		relationship_type: one-to-many
	invoices_comments
		key: id
		target_bean: InvoiceComment
		target_key: invoice_id
		relationship_type: one-to-many
	invoices_from_quotes
		key: from_quote_id
		target_bean: Quote
		target_key: id
		relationship_type: many-to-one
	invoices_activities
		target_bean: Activity
		target_key: id
		sources
			meetings
				relationship: invoice_meetings
			calls
				relationship: invoice_calls
			tasks
				relationship: invoice_tasks
	invoices_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: invoice_meetings
			calls
				relationship: invoice_calls
			tasks
				relationship: invoice_tasks
			notes
				relationship: invoice_notes
			emails
				relationship: emails_invoices_rel

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Quotes/Quote.php
	audit_enabled: true
	activity_log_enabled: true
	unified_search
		enabled: true
		default: false
	duplicate_merge: false
	table_name: quotes
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
	app.deleted
	app.currency
		required: true
	prefix
		vname: LBL_QUOTE_PREFIX
		type: sequence_prefix
		sequence_name: quotes_prefix
	quote_number
		vname: LBL_QUOTE_SUFFIX
		type: sequence_number
		sequence_name: quotes_number_sequence
		required: true
		unified_search: exact
	full_number
		vname: LBL_QUOTE_NUMBER
		vname_list: LBL_LIST_NUMBER
		type: item_number
		source
			type: sequence
			fields
				- prefix
				- quote_number
		unified_search: exact
	name
		vname: LBL_QUOTE_SUBJECT
		type: name
		len: 100
		unified_search: true
		vname_list: LBL_LIST_SUBJECT
		required: true
	opportunity
		vname: LBL_OPPORTUNITY
		type: ref
		bean_name: Opportunity
		massupdate: true
	quote_stage
		vname: LBL_QUOTE_STAGE
		type: enum
		options: quote_stage_dom
		len: 25
		required: true
		audited: true
		vname_list: LBL_LIST_STAGE
		default: Draft
		massupdate: true
	approval
		vname: LBL_APPROVAL_STATUS
		type: enum
		options: quote_approval_status
		len: 25
		audited: true
		default: ""
		massupdate: false
	approval_problem
		vname: LBL_APPROVAL_PROBLEM
		type: text
		audited: true
		massupdate: false
	approved_user
		vname: LBL_APPROVED_BY
		type: ref
		bean_name: User
		audited: true
		massupdate: false
	purchase_order_num
		vname: LBL_PURCHASE_ORDER_NUM
		type: varchar
		len: 100
	sales_order
		vname: LBL_SALES_ORDER
		type: ref
		bean_name: SalesOrder
		massupdate: true
	valid_until
		vname: LBL_VALID_UNTIL
		type: date
		required: true
		vname_list: LBL_LIST_VALID_UNTIL
		massupdate: true
	show_list_prices
		vname: LBL_SHOW_LIST_PRICES
		type: bool
		default: 0
		isnull: false
		massupdate: false
	show_components
		vname: LBL_SHOW_COMPONENTS
		type: enum
		options: quote_show_components_dom
		len: 40
		isnull: false
		default: ""
		massupdate: false
	partner
		vname: LBL_PARTNER
		type: ref
		bean_name: Partner
		massupdate: true
	billing_account
		vname: LBL_BILLING_ACCOUNT
		type: ref
		required: true
		bean_name: Account
		add_filters
			--
				param: is_supplier
				value: 0
		massupdate: true
	billing_contact
		vname: LBL_BILLING_CONTACT
		type: ref
		bean_name: Contact
		massupdate: true
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
	shipping_account
		vname: LBL_SHIPPING_ACCOUNT
		type: ref
		bean_name: Account
		massupdate: true
	shipping_contact
		vname: LBL_SHIPPING_CONTACT
		type: ref
		bean_name: Contact
		massupdate: true
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
	shipping_provider
		vname: LBL_SHIPPING_PROVIDER
		type: ref
		bean_name: ShippingProvider
		width: 16
		massupdate: true
	description
		vname: LBL_DESCRIPTION
		type: text
	markup
		vname: LBL_MARKUP
		type: float
		dbType: double
	margin
		vname: LBL_MARGIN
		type: float
		dbType: double
	amount
		vname: LBL_AMOUNT
		type: currency
		required: true
		audited: true
		vname_list: LBL_LIST_AMOUNT
		base_field: amount_usdollar
		editable: false
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
	tax_exempt
		vname: LBL_TAX_EXEMPT
		type: bool
		required: true
		default: 0
		massupdate: true
	budgetary_quote
		vname: LBL_BUDGETARY_QUOTE
		type: bool
		default: 0
		massupdate: true
	discount_before_taxes
		vname: LBL_DISCOUNT_BEFORE_TAXES
		type: bool
		required: true
		default: 0
		massupdate: false
links
	activities
		relationship: quotes_activities
		module: Activities
		bean_name: Activity
		vname: LBL_ACTIVITIES_SUBPANEL_TITLE
	history
		relationship: quotes_history
		module: Activities
		bean_name: ActivityHistory
		layout: History
		vname: LBL_HISTORY_SUBPANEL_TITLE
	billing_account_link
		vname: LBL_BILLING_ACCOUNT
		relationship: quotes_billto_accounts
		link_type: one
		side: left
		module: Accounts
		bean_name: Account
	opportunity_link
		vname: LBL_OPPORTUNITY
		relationship: quotes_opportunities
		link_type: one
		side: left
		module: Opportunities
		bean_name: Opportunity
	shipping_account_link
		vname: LBL_SHIPPING_ACCOUNT
		relationship: quotes_shipto_accounts
		link_type: one
		side: left
		module: Accounts
		bean_name: Account
	line_groups_link
		vname: LBL_LINE_GROUPS
		relationship: quotes_line_groups
		side: right
		module: Quotes
		bean_name: QuoteLineGroup
	lines_link
		vname: LBL_LINE_ITEMS
		relationship: quotes_line_items
		side: right
		module: Quotes
		bean_name: QuoteLine
	adjustments_link
		vname: LBL_ADJUSTMENTS
		relationship: quotes_adjustments
		side: right
		module: Quotes
		bean_name: QuoteAdjustment
	comments_link
		vname: LBL_COMMENTS
		relationship: quotes_comments
		side: right
		module: Quotes
		bean_name: QuoteComment
	tasks
		relationship: quotes_tasks
		vname: LBL_TASKS
	notes
		relationship: quotes_notes
		vname: LBL_NOTES
	meetings
		relationship: quotes_meetings
		vname: LBL_MEETINGS
	calls
		relationship: quotes_calls
		vname: LBL_CALLS
	emails
		relationship: quotes_emails
		vname: LBL_EMAILS
	salesorders
		relationship: so_quote_rel
		vname: LBL_SALES_ORDERS
	invoice
		relationship: invoices_from_quotes
		vname: LBL_INVOICES
	app.securitygroups
		relationship: securitygroups_quotes
indices
	idx_quote_number
		fields
			- quote_number
	idx_quote_name
		fields
			- name
	idx_partner_id
		fields
			- partner_id
relationships
	quotes_contacts_shipto
		key: shipping_contact_id
		target_bean: Contact
		target_key: id
		relationship_type: one-to-many
	quotes_opportunities
		key: opportunity_id
		target_bean: Opportunity
		target_key: id
		relationship_type: one-to-many
	quotes_billto_accounts
		key: billing_account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	quotes_shipto_accounts
		key: shipping_account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	quotes_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: Quotes
		relationship_type: one-to-many
	quotes_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: Quotes
		relationship_type: one-to-many
	quotes_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: Quotes
		relationship_type: one-to-many
	quotes_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Quotes
		relationship_type: one-to-many
	quotes_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: Quotes
		relationship_type: one-to-many
	quotes_line_groups
		key: id
		target_bean: QuoteLineGroup
		target_key: parent_id
		relationship_type: one-to-many
	quotes_line_items
		key: id
		target_bean: QuoteLine
		target_key: quote_id
		relationship_type: one-to-many
	quotes_adjustments
		key: id
		target_bean: QuoteAdjustment
		target_key: quote_id
		relationship_type: one-to-many
	quotes_comments
		key: id
		target_bean: QuoteComment
		target_key: quote_id
		relationship_type: one-to-many
	quotes_activities
		target_bean: Activity
		target_key: id
		sources
			meetings
				relationship: quotes_meetings
			calls
				relationship: quotes_calls
			tasks
				relationship: quotes_tasks
	quotes_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: quotes_meetings
			calls
				relationship: quotes_calls
			tasks
				relationship: quotes_tasks
			notes
				relationship: quotes_notes
			emails
				relationship: emails_quotes_rel

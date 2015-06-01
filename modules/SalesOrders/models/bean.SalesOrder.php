<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/SalesOrders/SalesOrder.php
	audit_enabled: true
	activity_log_enabled: true
	unified_search
		enabled: true
		default: false
	duplicate_merge: false
	table_name: sales_orders
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
		vname: LBL_NUMBER_PREFIX
		type: sequence_prefix
		sequence_name: sales_order_prefix
	so_number
		vname: LBL_NUMBER_SUFFIX
		type: sequence_number
		sequence_name: sales_order_sequence
		required: true
		unified_search: exact
	full_number
		vname: LBL_SALES_ORDER_NUM
		vname_list: LBL_LIST_NUMBER
		type: item_number
		source
			type: sequence
			fields
				- prefix
				- so_number
		unified_search: exact
	name
		vname: LBL_SUBJECT
		type: name
		len: 100
		unified_search: true
		vname_list: LBL_LIST_SUBJECT
		required: true
	opportunity
		vname: LBL_OPPORTUNITY_NAME
		type: ref
		reportable: false
		bean_name: Opportunity
		massupdate: true
	so_stage
		vname: LBL_SO_STAGE
		type: enum
		options: so_stage_dom
		len: 30
		required: true
		audited: true
		vname_list: LBL_LIST_STAGE
		massupdate: true
	purchase_order_num
		vname: LBL_PURCHASE_ORDER_NUM
		type: varchar
		len: 100
	due_date
		vname: LBL_DUE_DATE
		type: date
		required: true
		vname_list: LBL_LIST_DUE_DATE
		massupdate: true
	delivery_date
		vname: LBL_DELIVERY_DATE
		type: date
		massupdate: true
	show_list_prices
		vname: LBL_SHOW_LIST_PRICES
		type: bool
		default: 0
		isnull: false
		reportable: false
		massupdate: false
	show_components
		vname: LBL_SHOW_COMPONENTS
		type: enum
		options: quote_show_components_dom
		len: 40
		isnull: false
		default: ""
		reportable: false
		massupdate: false
	partner
		vname: LBL_PARTNER
		type: ref
		bean_name: Partner
		massupdate: true
	billing_account
		vname: LBL_BILLING_ACCOUNT
		type: ref
		reportable: false
		bean_name: Account
		required: true
		add_filters
			--
				param: is_supplier
				value: 0
		massupdate: true
	billing_contact
		vname: LBL_BILLING_CONTACT
		type: ref
		reportable: false
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
		reportable: false
		bean_name: Account
		massupdate: true
	shipping_contact
		vname: LBL_SHIPPING_CONTACT
		type: ref
		reportable: false
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
		reportable: false
		bean_name: ShippingProvider
		massupdate: true
	description
		vname: LBL_DESCRIPTION
		type: text
	related_quote
		vname: LBL_RELATED_QUOTE
		type: ref
		reportable: false
		bean_name: Quote
		massupdate: true
	amount
		vname: LBL_AMOUNT
		type: currency
		required: true
		audited: true
		vname_list: LBL_LIST_AMOUNT
		base_field: amount_usdollar
		editable: false
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
	discount_before_taxes
		vname: LBL_DISCOUNT_BEFORE_TAXES
		type: bool
		required: true
		default: 0
		reportable: false
		massupdate: false
	tax_exempt
		vname: LBL_TAX_EXEMPT
		type: bool
		required: true
		default: 0
		massupdate: true
links
	activities
		relationship: so_activities
		module: Activities
		bean_name: Activity
		vname: LBL_ACTIVITIES_SUBPANEL_TITLE
	history
		relationship: so_history
		module: Activities
		bean_name: ActivityHistory
		layout: History
		vname: LBL_HISTORY_SUBPANEL_TITLE
	related_quote_link
		link_type: one
		relationship: so_quote_rel
		module: Quotes
		bean_name: Quote
		vname: LBL_RELATED_QUOTE
	billing_account_link
		vname: LBL_BILLING_ACCOUNT
		relationship: so_billto_accounts
		link_type: one
		side: left
		module: Accounts
		bean_name: Account
	opportunity_link
		vname: LBL_OPPORTUNITY
		relationship: so_opportunities
		link_type: one
		side: left
		module: Opportunities
		bean_name: Opportunity
	shipping_account_link
		vname: LBL_SHIPPING_ACCOUNT
		relationship: so_shipto_accounts
		link_type: one
		side: left
		module: Accounts
		bean_name: Account
	line_groups_link
		vname: LBL_LINE_GROUPS
		relationship: so_line_groups
		side: right
		module: SalesOrders
		bean_name: SalesOrderLineGroup
	lines_link
		vname: LBL_LINE_ITEMS
		relationship: so_line_items
		side: right
		module: SalesOrders
		bean_name: SalesOrderLine
	adjustments_link
		vname: LBL_ADJUSTMENTS
		relationship: so_adjustments
		side: right
		module: SalesOrders
		bean_name: SalesOrderAdjustment
	comments_link
		vname: LBL_COMMENTS
		relationship: so_comments
		side: right
		module: SalesOrders
		bean_name: SalesOrderComment
	invoice
		relationship: so_invoices
		vname: LBL_INVOICES
	purchaseorders
		relationship: so_po
		add_existing: true
		vname: LBL_PURCHASE_ORDERS
	tasks
		relationship: so_tasks
		vname: LBL_TASKS
	notes
		relationship: so_notes
		vname: LBL_NOTES
	meetings
		relationship: so_meetings
		vname: LBL_MEETINGS
	calls
		relationship: so_calls
		vname: LBL_CALLS
	emails
		relationship: so_emails
		vname: LBL_EMAILS
	shipping
		vname: LBL_SHIPPING
		relationship: so_shipping_rel
		side: left
		module: Shipping
		bean_name: Shipping
	app.securitygroups
		relationship: securitygroups_salesorders
indices
	idx_so_number
		fields
			- so_number
	idx_so_name
		fields
			- name
	idx_partner_id
		fields
			- partner_id
relationships
	so_quote_rel
		key: related_quote_id
		target_bean: Quote
		target_key: id
		relationship_type: one-to-many
	so_invoices
		key: id
		target_bean: Invoice
		target_key: from_so_id
		relationship_type: one-to-many
	so_po
		key: id
		target_bean: PurchaseOrder
		target_key: from_so_id
		relationship_type: one-to-many
	so_contacts_shipto
		key: shipping_contact_id
		target_bean: Contact
		target_key: id
		relationship_type: one-to-many
	so_opportunities
		key: opportunity_id
		target_bean: Opportunity
		target_key: id
		relationship_type: one-to-many
	so_billto_accounts
		key: billing_account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	so_shipto_accounts
		key: shipping_account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	so_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: SalesOrders
		relationship_type: one-to-many
	so_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: SalesOrders
		relationship_type: one-to-many
	so_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: SalesOrders
		relationship_type: one-to-many
	so_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: SalesOrders
		relationship_type: one-to-many
	so_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: SalesOrders
		relationship_type: one-to-many
	so_line_groups
		key: id
		target_bean: SalesOrderLineGroup
		target_key: parent_id
		relationship_type: one-to-many
	so_line_items
		key: id
		target_bean: SalesOrderLine
		target_key: sales_orders_id
		relationship_type: one-to-many
	so_adjustments
		key: id
		target_bean: SalesOrderAdjustment
		target_key: sales_orders_id
		relationship_type: one-to-many
	so_comments
		key: id
		target_bean: SalesOrderComment
		target_key: sales_orders_id
		relationship_type: one-to-many
	so_activities
		target_bean: Activity
		target_key: id
		sources
			meetings
				relationship: so_meetings
			calls
				relationship: so_calls
			tasks
				relationship: so_tasks
	so_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: so_meetings
			calls
				relationship: so_calls
			tasks
				relationship: so_tasks
			notes
				relationship: so_notes
			emails
				relationship: emails_salesorders_rel

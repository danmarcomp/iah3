<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/PurchaseOrders/PurchaseOrder.php
	audit_enabled: true
	unified_search
		enabled: true
		default: false
	duplicate_merge: false
	table_name: purchase_orders
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
	related_invoice
		vname: LBL_RELATED_INVOICE
		type: ref
		reportable: false
		bean_name: Invoice
		massupdate: false
	supplier
		vname: LBL_SUPPLIER
		type: ref
		bean_name: Account
		required: true
		add_filters
			--
				param: is_supplier
				value: 1
		massupdate: false
	supplier_contact
		vname: LBL_SUPPLIER_CONTACT
		type: ref
		bean_name: Contact
		massupdate: false
		add_filters
			--
				param: primary_account
				field_name: supplier
	prefix
		vname: LBL_NUMBER_PREFIX
		type: sequence_prefix
		sequence_name: purchase_order_prefix
	po_number
		vname: LBL_NUMBER_SUFFIX
		type: sequence_number
		sequence_name: purchase_order_sequence
		required: true
		unified_search: exact
	full_number
		vname: LBL_PURCHASE_ORDER_NUMBER
		vname_list: LBL_LIST_NUMBER
		type: item_number
		source
			type: sequence
			fields
				- prefix
				- po_number
		unified_search: exact
	cancelled
		vname: LBL_CANCELLED
		type: bool
		required: true
		default: 0
		audited: true
		massupdate: false
	name
		vname: LBL_SUBJECT
		type: name
		len: 100
		unified_search: true
		vname_list: LBL_LIST_SUBJECT
		required: true
	shipping_provider
		vname: LBL_SHIPPING_PROVIDER
		type: ref
		reportable: false
		bean_name: ShippingProvider
		massupdate: false
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
		reportable: false
		massupdate: false
	shipping_stage
		vname: LBL_SHIPPING_STAGE
		type: enum
		options: po_status_dom
		len: 30
		required: true
		audited: true
		massupdate: false
		default: Draft
		vname_list: LBL_LIST_SHIPPING_STAGE
	from_so
		type: ref
		vname: LBL_RELATED_SO
		massupdate: false
		bean_name: SalesOrder
	drop_ship
		vname: LBL_DROP_SHIP
		type: bool
		default: 0
		reportable: false
		massupdate: false
	shipping_contact
		vname: LBL_SHIPPING_CONTACT
		type: ref
		reportable: false
		bean_name: Contact
		massupdate: true
		add_filters
			--
				param: primary_account
				field_name: shipping_account
	shipping_account
		vname: LBL_SHIPPING_ACCOUNT
		type: ref
		reportable: false
		bean_name: Account
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
	discount_before_taxes
		vname: LBL_DISCOUNT_BEFORE_TAXES
		type: bool
		required: true
		default: 0
		massupdate: false
links
	activities
		relationship: purchase_orders_activities
		module: Activities
		bean_name: Activity
		vname: LBL_ACTIVITIES_SUBPANEL_TITLE
	history
		relationship: purchase_orders_history
		module: Activities
		bean_name: ActivityHistory
		layout: History
		vname: LBL_HISTORY_SUBPANEL_TITLE
	related_invoice_link
		link_type: one
		relationship: po_invoice_rel
		module: Invoice
		bean_name: Invoice
		vname: LBL_RELATED_INVOICE
	shipping_account_link
		vname: LBL_SHIPPING_ACCOUNT
		relationship: purchase_orders_shipto_accounts
		link_type: one
		side: left
		module: Accounts
		bean_name: Account
	line_groups_link
		vname: LBL_LINE_GROUPS
		relationship: purchase_orders_line_groups
		side: right
		module: PurchaseOrders
		bean_name: PurchaseOrderLineGroup
	lines_link
		vname: LBL_LINE_ITEMS
		relationship: purchase_orders_line_items
		side: right
		module: PurchaseOrders
		bean_name: PurchaseOrderLine
	adjustments_link
		vname: LBL_ADJUSTMENTS
		relationship: purchase_orders_adjustments
		side: right
		module: PurchaseOrders
		bean_name: PurchaseOrderAdjustment
	comments_link
		vname: LBL_COMMENTS
		relationship: purchase_orders_comments
		side: right
		module: PurchaseOrders
		bean_name: PurchaseOrderComment
	tasks
		relationship: purchase_orders_tasks
		vname: LBL_TASKS
	notes
		relationship: purchase_orders_notes
		vname: LBL_NOTES
	meetings
		relationship: purchase_orders_meetings
		vname: LBL_MEETINGS
	calls
		relationship: purchase_orders_calls
		vname: LBL_CALLS
	emails
		relationship: purchase_orders_emails
		vname: LBL_EMAILS
	supplier_link
		link_type: one
		relationship: purchase_orders_supplier
		vname: LBL_SUPPLIER
	receiving
		vname: LBL_RECEIVING_SUBPANEL_TITLE
		relationship: po_receiving_rel
		side: left
		module: Receiving
		bean_name: Receiving
	bills
		vname: LBL_BILLS_SUBPANEL_TITLE
		relationship: po_bills_rel
		side: left
		module: Bills
		bean_name: Bill
	app.securitygroups
		relationship: securitygroups_purchaseorders
indices
	idx_purchase_orders_number
		fields
			- po_number
	idx_purchase_orders_name
		fields
			- name
relationships
	so_invoice_rel
		key: related_invoice_id
		target_bean: Invoice
		target_key: id
		relationship_type: one-to-many
	po_receiving_rel
		key: id
		target_bean: Receiving
		target_key: po_id
		relationship_type: one-to-many
	po_bills_rel
		key: id
		target_bean: Bill
		target_key: related_purchase_order_id
		relationship_type: one-to-many
	purchase_orders_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: PurchaseOrders
		relationship_type: one-to-many
	purchase_orders_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: PurchaseOrders
		relationship_type: one-to-many
	purchase_orders_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: PurchaseOrders
		relationship_type: one-to-many
	purchase_orders_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: PurchaseOrders
		relationship_type: one-to-many
	purchase_orders_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: PurchaseOrders
		relationship_type: one-to-many
	purchase_orders_line_groups
		key: id
		target_bean: PurchaseOrderLineGroup
		target_key: parent_id
		relationship_type: one-to-many
	purchase_orders_line_items
		key: id
		target_bean: PurchaseOrderLine
		target_key: purchase_orders_id
		relationship_type: one-to-many
	purchase_orders_adjustments
		key: id
		target_bean: PurchaseOrderAdjustment
		target_key: purchase_orders_id
		relationship_type: one-to-many
	purchase_orders_comments
		key: id
		target_bean: PurchaseOrderComment
		target_key: purchase_orders_id
		relationship_type: one-to-many
	purchase_orders_supplier
		key: supplier_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	purchase_orders_shipto_accounts
		key: shipping_account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	purchase_orders_activities
		target_bean: Activity
		target_key: id
		sources
			meetings
				relationship: purchase_orders_meetings
			calls
				relationship: purchase_orders_calls
			tasks
				relationship: purchase_orders_tasks
	purchase_orders_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: purchase_orders_meetings
			calls
				relationship: purchase_orders_calls
			tasks
				relationship: purchase_orders_tasks
			notes
				relationship: purchase_orders_notes
			emails
				relationship: emails_purchaseorders_rel

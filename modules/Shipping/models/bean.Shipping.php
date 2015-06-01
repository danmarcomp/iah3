<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Shipping/Shipping.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	table_name: shipping
	primary_key: id
	default_order_by: full_number
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	after_save
		--
			class_function: update_stage
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
		sequence_name: shipping_prefix
	shipping_number
		vname: LBL_NUMBER_SUFFIX
		type: sequence_number
		sequence_name: shipping_sequence
		required: true
		unified_search: exact
	full_number
		vname: LBL_SHIPPING_NUM
		vname_list: LBL_LIST_NUMBER
		type: item_number
		source
			type: sequence
			fields
				- prefix
				- shipping_number
		unified_search: exact
	tracking_number
		vname: LBL_TRACKING_NUMBER
		type: varchar
		len: 100
	name
		vname: LBL_SUBJECT
		type: name
		len: 100
		unified_search: true
		vname_list: LBL_LIST_SUBJECT
		required: true
	shipping_stage
		vname: LBL_SHIPPING_STAGE
		type: enum
		options: shipping_status_dom
		len: 40
		required: true
		audited: true
		massupdate: true
		vname_list: LBL_LIST_STAGE
	show_list_prices
		vname: LBL_SHOW_LIST_PRICES
		type: bool
		default: 1
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
	description
		vname: LBL_DESCRIPTION
		type: text
	date_shipped
		vname: LBL_DATE_SHIPPED
		type: date
		vname_list: LBL_LIST_DATE_SHIPPED
		massupdate: true
	purchase_order_num
		vname: LBL_PURCHASE_ORDER_NUM
		type: varchar
		len: 100
	weight_1
		vname: LBL_WEIGHT_1
		type: int
		len: 11
	weight_2
		vname: LBL_WEIGHT_2
		type: int
		len: 11
	num_packages
		type: int
		default: 0
		vname: LBL_NUM_PACKAGES
	shipping_cost
		vname: LBL_COST
		type: currency
		required: true
		audited: true
	invoice
		vname: LBL_INVOICE
		type: ref
		reportable: false
		bean_name: Invoice
		massupdate: false
	so
		vname: LBL_SALES_ORDER
		type: ref
		reportable: false
		bean_name: SalesOrder
		massupdate: false
	shipping_provider
		vname: LBL_SHIPPING_PROVIDER
		type: ref
		reportable: false
		bean_name: ShippingProvider
		massupdate: false
	shipping_account
		vname: LBL_SHIPPING_ACCOUNT
		type: ref
		reportable: false
		bean_name: Account
		massupdate: false
	shipping_contact
		vname: LBL_SHIPPING_CONTACT
		type: ref
		reportable: false
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
	warehouse
		vname: LBL_WAREHOUSE
		type: ref
		required: true
		bean_name: CompanyAddress
		massupdate: false
links
	shipping_account_link
		vname: LBL_SHIPPING_ACCOUNT
		relationship: shipping_accounts
		link_type: one
		side: left
		module: Accounts
		bean_name: Account
	related_warehouse
		vname: LBL_RELATED_WAREHOUSE
		relationship: shipping_warehouse
		link_type: one
		side: left
		module: CompanyAddress
		bean_name: CompanyAddress
	line_groups_link
		vname: LBL_LINE_GROUPS
		relationship: shipping_line_groups
		side: right
		module: Shipping
		bean_name: ShippingLineGroup
	lines_link
		vname: LBL_LINE_ITEMS
		relationship: shipping_line_items
		side: right
		module: Shipping
		bean_name: ShippingLine
	adjustments_link
		vname: LBL_ADJUSTMENTS
		relationship: shipping_adjustments
		side: right
		module: Shipping
		bean_name: ShippingAdjustment
	comments_link
		vname: LBL_COMMENTS
		relationship: shipping_comments
		side: right
		module: Shipping
		bean_name: ShippingComment
	history
		relationship: shipping_history
		module: Activities
		bean_name: ActivityHistory
		layout: History
		vname: LBL_HISTORY_SUBPANEL_TITLE
	notes
		relationship: shipping_notes
		vname: LBL_NOTES
	emails
		relationship: shipping_emails
		vname: LBL_EMAILS
	tasks
		relationship: shipping_tasks
		vname: LBL_TASKS
	meetings
		relationship: shipping_meetings
		vname: LBL_MEETINGS
	calls
		relationship: shipping_calls
		vname: LBL_CALLS
	app.securitygroups
		relationship: securitygroups_shipping
indices
	idx_sh_number
		fields
			- shipping_number
	idx_sh_name
		fields
			- name
relationships
	so_shipping_rel
		key: so_id
		target_bean: SalesOrder
		target_key: id
		relationship_type: one-to-many
	invoice_shipping_rel
		key: invoice_id
		target_bean: Invoice
		target_key: id
		relationship_type: one-to-many
	shipping_accounts
		key: shipping_account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	shipping_warehouse
		key: warehouse_id
		target_bean: CompanyAddress
		target_key: id
		relationship_type: one-to-one
	shipping_line_groups
		key: id
		target_bean: ShippingLineGroup
		target_key: parent_id
		relationship_type: one-to-many
	shipping_line_items
		key: id
		target_bean: ShippingLine
		target_key: shipping_id
		relationship_type: one-to-many
	shipping_adjustments
		key: id
		target_bean: ShippingAdjustment
		target_key: shipping_id
		relationship_type: one-to-many
	shipping_comments
		key: id
		target_bean: ShippingComment
		target_key: shipping_id
		relationship_type: one-to-many
	shipping_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Shipping
		relationship_type: one-to-many
	shipping_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: Shipping
		relationship_type: one-to-many
	shipping_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: Shipping
		relationship_type: one-to-many
	shipping_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: Shipping
		relationship_type: one-to-many
	shipping_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: Shipping
		relationship_type: one-to-many
	shipping_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: shipping_meetings
			calls
				relationship: shipping_calls
			tasks
				relationship: shipping_tasks
			notes
				relationship: shipping_notes
			emails
				relationship: shipping_emails

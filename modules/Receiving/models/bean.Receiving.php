<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Receiving/Receiving.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	table_name: receiving
	primary_key: id
	default_order_by: full_number
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	before_save
		--
			class_function: auto_set_fields
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
	supplier
		vname: LBL_SUPPLIER
		type: ref
		bean_name: Account
		massupdate: false
	supplier_contact
		vname: LBL_SUPPLIER_CONTACT
		type: ref
		bean_name: Contact
		massupdate: false
	prefix
		vname: LBL_NUMBER_PREFIX
		type: sequence_prefix
		sequence_name: receiving_prefix
	receiving_number
		vname: LBL_NUMBER_SUFFIX
		type: sequence_number
		sequence_name: receiving_sequence
		required: true
		unified_search: exact
	full_number
		vname: LBL_RECEIVING_NUM
		vname_list: LBL_LIST_NUMBER
		type: item_number
		source
			type: sequence
			fields
				- prefix
				- receiving_number
		unified_search: exact
	name
		vname: LBL_SUBJECT
		type: name
		len: 100
		unified_search: true
		vname_list: LBL_LIST_SUBJECT
		required: true
	receiving_stage
		vname: LBL_RECEIVING_STAGE
		type: enum
		options: receiving_status_dom
		len: 40
		required: true
		audited: true
		massupdate: false
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
		required: true
		vname_list: LBL_LIST_DATE_SHIPPED
		massupdate: true
	packing_slip_num
		vname: LBL_PACKING_SLIP_NUM
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
		required: true
		vname: LNL_NUM_PACKAGES
	receiving_cost
		vname: LBL_COST
		type: currency
		required: true
		audited: true
	po
		vname: LBL_PO
		type: ref
		reportable: false
		bean_name: PurchaseOrder
		massupdate: false
	so
		vname: LBL_SO
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
	warehouse
		vname: LBL_WAREHOUSE
		type: ref
		required: true
		bean_name: CompanyAddress
		massupdate: false
	credit_memo
		vname: LBL_CREDIT_MEMO
		type: ref
		reportable: false
		bean_name: CreditNote
		massupdate: false
links
	supplier_link
		link_type: one
		relationship: receiving_supplier
		vname: LBL_SUPPLIER
	credit_memo_link
		vname: LBL_CREDIT_MEMO
		link_type: one
		relationship: receiving_credit_notes
	purchase_order_link
		vname: LBL_PURCHASE_ORDER
		link_type: one
		relationship: receiving_purchase_order
	line_groups_link
		vname: LBL_LINE_GROUPS
		relationship: receiving_line_groups
		side: right
		module: Receiving
		bean_name: ReceivingLineGroup
	lines_link
		vname: LBL_LINE_ITEMS
		relationship: receiving_line_items
		side: right
		module: Receiving
		bean_name: ReceivingLine
	adjustments_link
		vname: LBL_ADJUSTMENTS
		relationship: receiving_adjustments
		side: right
		module: Receiving
		bean_name: ReceivingAdjustment
	comments_link
		vname: LBL_COMMENTS
		relationship: receiving_comments
		side: right
		module: Receiving
		bean_name: ReceivingComment
	notes
		relationship: receiving_notes
		vname: LBL_NOTES
	emails
		relationship: receiving_emails
		vname: LBL_EMAILS
	tasks
		relationship: receiving_tasks
		vname: LBL_TASKS
	meetings
		relationship: receiving_meetings
		vname: LBL_MEETINGS
	calls
		relationship: receiving_calls
		vname: LBL_CALLS
	app.securitygroups
		relationship: securitygroups_receiving
indices
	idx_sh_number
		fields
			- receiving_number
	idx_sh_name
		fields
			- name
relationships
	receiving_supplier
		key: supplier_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	receiving_purchase_order
		key: po_id
		target_bean: PurchaseOrder
		target_key: id
		relationship_type: one-to-many
		massupdate: false
	receiving_credit_notes
		key: credit_memo_id
		target_bean: CreditNote
		target_key: id
		relationship_type: one-to-many
	receiving_accounts
		key: receiving_account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	receiving_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Receiving
		relationship_type: one-to-many
	receiving_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: Receiving
		relationship_type: one-to-many
	receiving_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: Receiving
		relationship_type: one-to-many
	receiving_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: Receiving
		relationship_type: one-to-many
	receiving_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: Receiving
		relationship_type: one-to-many
	receiving_line_groups
		key: id
		target_bean: ReceivingLineGroup
		target_key: parent_id
		relationship_type: one-to-many
	receiving_line_items
		key: id
		target_bean: ReceivingLine
		target_key: receiving_id
		relationship_type: one-to-many
	receiving_adjustments
		key: id
		target_bean: ReceivingAdjustment
		target_key: receiving_id
		relationship_type: one-to-many
	receiving_comments
		key: id
		target_bean: ReceivingComment
		target_key: receiving_id
		relationship_type: one-to-many

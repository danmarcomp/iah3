<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Bills/Bill.php
	audit_enabled: true
	unified_search
		enabled: true
		default: false
	duplicate_merge: false
	table_name: bills
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
	app.deleted
	app.currency
		required: true
	related_purchase_order
		vname: LBL_RELATED_PURCHASE
		type: ref
		bean_name: PurchaseOrder
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
	prefix
		vname: LBL_NUMBER_PREFIX
		type: sequence_prefix
		sequence_name: bill_prefix
	bill_number
		vname: LBL_NUMBER_SUFFIX
		type: sequence_number
		sequence_name: bill_number_sequence
		required: true
		unified_search: exact
	full_number
		vname: LBL_BILL_NUMBER
		vname_list: LBL_LIST_NUMBER
		type: item_number
		source
			type: sequence
			fields
				- prefix
				- bill_number
		unified_search: exact
	from_purchase_order
		vname: LBL_FROM_PURCHASE_ORDER
		type: ref
		bean_name: PurchaseOrder
		massupdate: false
	invoice_reference
		vname: LBL_INVOICE_REFERENCE
		type: varchar
		len: 50
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
	bill_date
		vname: LBL_BILL_DATE
		type: date
		required: true
		massupdate: true
	due_date
		vname: LBL_DUE_DATE
		type: date
		required: true
		vname_list: LBL_LIST_DUE_DATE
		massupdate: true
	shipping_provider
		vname: LBL_SHIPPING_PROVIDER
		type: ref
		bean_name: ShippingProvider
		massupdate: false
	description
		vname: LBL_DESCRIPTION
		type: text
	amount
		vname: LBL_AMOUNT
		type: currency
		required: true
		default: 0
		audited: true
		vname_list: LBL_LIST_AMOUNT
		base_field: amount_usdollar
		editable: false
	amount_due
		vname: LBL_AMOUNT_DUE
		type: currency
		required: true
		default: 0
		audited: true
		vname_list: LBL_LIST_AMOUNT_DUE
		base_field: amount_due_usdollar
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
	discount_before_taxes
		vname: LBL_DISCOUNT_BEFORE_TAXES
		type: bool
		required: true
		default: 0
		reportable: false
		massupdate: false
links
	line_groups_link
		vname: LBL_LINE_GROUPS
		relationship: bills_line_groups
		side: right
		module: Bills
		bean_name: BillLineGroup
	lines_link
		vname: LBL_LINE_ITEMS
		relationship: bills_line_items
		side: right
		module: Bills
		bean_name: BillLine
	adjustments_link
		vname: LBL_ADJUSTMENTS
		relationship: bills_adjustments
		side: right
		module: Bills
		bean_name: BillAdjustment
	comments_link
		vname: LBL_COMMENTS
		relationship: bills_comments
		side: right
		module: Bill
		bean_name: BillComment
	tasks
		relationship: bills_tasks
		vname: LBL_TASKS
	notes
		relationship: bills_notes
		vname: LBL_NOTES
	meetings
		relationship: bills_meetings
		vname: LBL_MEETINGS
	calls
		relationship: bills_calls
		vname: LBL_CALLS
	emails
		relationship: bills_emails
		vname: LBL_EMAILS
	supplier_link
		link_type: one
		relationship: bills_supplier
		vname: LBL_SUPPLIER
	payments
		relationship: bills_payments
		vname: LBL_PAYMENTS
	app.securitygroups
		relationship: securitygroups_bills
indices
	idx_bills_number
		fields
			- bill_number
	idx_bills_name
		fields
			- name
relationships
	bills_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: Bills
		relationship_type: one-to-many
	bills_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: Bills
		relationship_type: one-to-many
	bills_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: Bills
		relationship_type: one-to-many
	bills_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Bills
		relationship_type: one-to-many
	bills_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: Bills
		relationship_type: one-to-many
	bills_line_groups
		key: id
		target_bean: BillLineGroup
		target_key: parent_id
		relationship_type: one-to-many
	bills_line_items
		key: id
		target_bean: BillLine
		target_key: bills_id
		relationship_type: one-to-many
	bills_adjustments
		key: id
		target_bean: BillAdjustment
		target_key: bills_id
		relationship_type: one-to-many
	bills_comments
		key: id
		target_bean: BillComment
		target_key: bills_id
		relationship_type: one-to-many
	bills_supplier
		key: supplier_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many

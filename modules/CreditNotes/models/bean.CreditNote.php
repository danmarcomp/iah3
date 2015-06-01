<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/CreditNotes/CreditNote.php
	audit_enabled: true
	unified_search
		enabled: true
		default: false
	duplicate_merge: false
	table_name: credit_notes
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
		vname: LBL_CREDIT_PREFIX
		type: sequence_prefix
		sequence_name: credit_prefix
	credit_number
		vname: LBL_CREDIT_SUFFIX
		type: sequence_number
		sequence_name: credit_number_sequence
		required: true
		unified_search: exact
	full_number
		vname: LBL_CREDIT_NUMBER
		vname_list: LBL_LIST_NUMBER
		type: item_number
		source
			type: sequence
			fields
				- prefix
				- credit_number
		unified_search: exact
	invoice
		vname: LBL_CREDIT_FOR_INVOICE
		type: ref
		bean_name: Invoice
		massupdate: false
	apply_credit_note
		vname: LBL_AFFECT_INVOICE_BALANCE
		type: bool
		reportable: false
		editable: true
		updateable: true
		massupdate: true
	cancelled
		vname: LBL_CANCELLED
		type: bool
		required: true
		default: 0
		audited: true
		massupdate: false
	name
		vname: LBL_CREDIT_SUBJECT
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
	due_date
		vname: LBL_ISSUE_DATE
		type: date
		required: true
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
	tax_information
		vname: LBL_TAX_INFORMATION
		type: varchar
		len: 150
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
		reportable: false
		massupdate: false
links
	line_groups_link
		vname: LBL_LINE_GROUPS
		relationship: credits_line_groups
		module: CreditNotes
		bean_name: CreditNoteLineGroup
	lines_link
		vname: LBL_LINE_ITEMS
		relationship: credits_line_items
		module: CreditNotes
		bean_name: CreditNoteLine
	adjustments_link
		vname: LBL_ADJUSTMENTS
		relationship: credits_adjustments
		module: CreditNotes
		bean_name: CreditNoteAdjustment
	comments_link
		vname: LBL_COMMENTS
		relationship: credits_comments
		side: right
		module: CreditNotes
		bean_name: CreditNoteComment
	payments
		relationship: credits_payments
		vname: LBL_PAYMENTS
		module: Payments
		bean_name: Payment
		layout: CreditNotes
	receiving
		vname: LBL_RECEIVING
		relationship: receiving_credit_notes
		module: Receiving
		bean_name: Receiving
	app.securitygroups
		relationship: securitygroups_credits
indices
	idx_credit_number
		fields
			- credit_number
	idx_credit_name
		fields
			- name
	idx_opportunity_id
		fields
			- opportunity_id
	idx_credit_billacct
		fields
			- billing_account_id
	idx_invoice_id
		fields
			- invoice_id
	idx_deleted
		fields
			- deleted
	idx_assigned_user_id
		fields
			- assigned_user_id
relationships
	credits_billto_accounts
		key: billing_account_id
		target_bean: Account
		target_key: id
		relationship_type: many-to-one
	credits_line_groups
		key: id
		target_bean: CreditNoteLineGroup
		target_key: parent_id
		relationship_type: one-to-many
	credits_line_items
		key: id
		target_bean: CreditNoteLine
		target_key: credit_id
		relationship_type: one-to-many
	credits_adjustments
		key: id
		target_bean: CreditNoteAdjustment
		target_key: credit_id
		relationship_type: one-to-many
	credits_comments
		key: id
		target_bean: CreditNoteComment
		target_key: credit_id
		relationship_type: one-to-many
	credit_notes_invoices
		key: invoice_id
		target_bean: Invoice
		target_key: id
		relationship_type: one-to-many

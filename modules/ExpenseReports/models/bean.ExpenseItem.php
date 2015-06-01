<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ExpenseReports/ExpenseItem.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: expense_items
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.assigned_user
		required: false
	app.deleted
	report
		vname: LBL_REPORT_ID
		type: ref
		required: true
		reportable: false
		bean_name: Report
	item_number
		vname: LBL_ITEM_NUMBER
		type: int
		len: 11
		required: true
		auto_increment: false
		disable_num_format: true
	description
		vname: LBL_DESCRIPTION
		type: text
		comment: Descriptive information about the expense report item
	date
		vname: LBL_DATE
		type: date
		comment: Date
		default: 0000-00-00
	amount
		vname: LBL_AMOUNT
		type: currency
		required: true
		default: 0
		audited: true
		base_field: amount_usdollar
    tax
        vname: LBL_TAX
        type: currency
        default: 0
        audited: true
        disable_num_format: true
        base_field: tax_usdollar
    total
		vname: LBL_TOTAL
		type: currency
		required: true
		default: 0
		audited: true
		base_field: total_usdollar
	paid_rate
		vname: LBL_PAID_RATE
		type: currency
	quantity
		vname: LBL_QUANTITY
		type: double
		len: 20
		required: false
		audited: true
	unit
		type: enum
		vname: LBL_UNIT
		len: 30
		options: expense_units_dom
		massupdate: false
	category
		vname: LBL_CATEGORY
		type: id
		required: true
	parent
		required: false
		reportable: false
		type: ref
		bean_name: ExpenseItem
	split
		vname: LBL_SPLIT
		type: bool
		required: true
		reportable: true
		default: 0
	tax_class
		type: ref
		vname: LBL_TAX_CLASS
		bean_name: TaxCode
	note
		type: ref
		reportable: false
		bean_name: Note
	line_order
		type: int
		default: 0
		required: true
		reportable: false
links
	category_link
		relationship: report_item_category
		link_type: one
		side: right
		vname: LBL_CATEGORY
relationships
	report_item_assigned_user
		key: assigned_user_id
		target_bean: User
		target_key: id
		relationship_type: one-to-many
	report_item_category
		key: category
		target_bean: BookingCategory
		target_key: id
		relationship_type: one-to-many

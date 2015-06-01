<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Booking/BookedHours.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: booked_hours
	primary_key: id
	default_order_by: date_entered
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	before_save
		--
			class_function: set_booking_class
			required_fields
				- booking_category
	after_save
		--
			class_function: recalc_project_financial
		--
			class_function: update_case
fields
	app.id
	app.assigned_user
	app.created_by_user
	app.modified_user
	app.date_entered
	app.date_modified
	app.deleted
	name
		vname: LBL_SUBJECT
		type: name
		len: 200
		unified_search: true
		required: true
		vname_list: LBL_LIST_SUBJECT
	status
		vname: LBL_STATUS
		type: enum
		options: booked_hours_status_dom
		len: 30
		massupdate: false
		required: true
		vname_list: LBL_LIST_STATUS
	quantity
		vname: LBL_QUANTITY
		type: duration
		len: 4
		required: true
		vname_list: LBL_LIST_QUANTITY
	date_start
		vname: LBL_DATE_START
		type: datetime
		required: true
		vname_list: LBL_LIST_DATE
		massupdate: true
	billing_rate
		vname: LBL_BILLING_RATE
		type: currency
		currency: billing_currency
		required: true
	billing_total
		vname: LBL_BILLING_TOTAL
		type: currency
		currency: billing_currency
		required: true
	billing_currency
		vname: LBL_BILLING_CURRENCY
		type: currency_ref
		exchange_rate: billing_exchange_rate
		required: true
	billing_exchange_rate
		vname: LBL_BILLING_EXCHANGE_RATE
		type: exchange_rate
	paid_rate
		vname: LBL_PAID_RATE
		type: currency
		currency: paid_currency
		required: true
	paid_total
		vname: LBL_PAID_TOTAL
		type: currency
		currency: paid_currency
		required: true
	paid_currency
		vname: LBL_PAID_CURRENCY
		type: currency_ref
		exchange_rate: paid_exchange_rate
		required: true
	paid_exchange_rate
		vname: LBL_PAID_EXCHANGE_RATE
		type: exchange_rate
	account
		vname: LBL_ACCOUNT_NAME
		type: ref
		bean_name: Account
		massupdate: true
	invoice
		vname: LBL_RELATED_INVOICE
		type: ref
		bean_name: Invoice
		massupdate: false
	timesheet
		vname: LBL_TIMESHEET
		type: ref
		bean_name: Timesheet
		massupdate: false
	related
		vname: LBL_RELATED_TO
		type: ref
		dynamic_module: related_type
		required: false
		options_function
			- BookedHours
			- bookable_objects
		label_key: _display
		options_icon: icon
		updateable: false
		massupdate: true
	related_type
		vname: LBL_RELATED_TYPE
		type: module_name
		options: booked_hours_related_dom
		len: 30
		massupdate: false
		required: false
	tax_code
		vname: LBL_TAX_CODE
		type: ref
		bean_name: TaxCode
		massupdate: false
	booking_category
		vname: LBL_BOOKING_CATEGORY
		type: ref
		required: true
		bean_name: BookingCategory
		add_filters
			booking_class
				- billable-work
				- nonbill-work
		options_function
			- BookingCategory
			- get_work_options
		label_key: _display
		massupdate: false
	booking_class
		vname: LBL_BOOKING_CLASS
		type: enum
		options: booking_classes_dom
		len: 30
		massupdate: false
		vname_list: LBL_LIST_BOOKING_CLASS
indices
	bkd_hours_rel
		fields
			- related_type
			- related_id
	bkd_hours_timesheet
		fields
			- timesheet_id
	bkd_hours_start
		fields
			- date_start
	bkd_hours_status
		fields
			- status
relationships
	booked_hours_accounts
		key: account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	booked_hours_cases
		key: related_id
		target_bean: aCase
		target_key: id
		role_column: related_type
		role_value: Cases
		relationship_type: one-to-many
	booked_hours_projecttask
		key: related_id
		target_bean: ProjectTask
		target_key: id
		role_column: related_type
		role_value: ProjectTask
		relationship_type: one-to-many
	booked_hours_timesheet
		key: timesheet_id
		target_bean: Timesheet
		target_key: id
		relationship_type: one-to-many
	booked_hours_category
		key: booking_category_id
		target_bean: BookingCategory
		target_key: id
		relationship_type: one-to-many
	booked_hours_invoice
		key: invoice_id
		target_bean: Invoice
		target_key: id
		relationship_type: one-to-many

<?php return; /* no output */ ?>

list
	default_order_by: date_entered
	massupdate_handlers
		--
			name: AddSelectedToInvoice
			class: CaseInvoicePopup
			file: modules/Cases/CaseInvoicePopup.php
hooks
    edit
        --
            class_function: can_book
filters
	name
	date_start
	current_user_only
		my_items: true
		vname: LBL_CURRENT_USER_FILTER
		field: assigned_user_id
	assigned_user
	status
		options_add_blank: true
	quantity
	account_name
		field: accounts.name
	view_billed_hours
		where_function_basic: get_view_billed_where_basic
		where_function_advanced: get_view_billed_where_advanced
		always_check: true
		vname: LBL_VIEW_BILLED_HOURS
	billing_status
		where_function_basic: get_billing_status_where_basic
		where_function_advanced: get_billing_status_where_advanced
	booking_category
widgets
    BookingDialogWidget
        type: section
        path: modules/Booking/booking_dialog/BookingDialogWidget.php
    BookingButton
        type: form_button
        path: modules/Booking/widgets/BookingButton.php

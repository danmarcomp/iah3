<?php return; /* no output */ ?>

list
	default_order_by: date_entered desc
    field_formatter
        file: include/layout/HighlightedListFieldFormatter.php
        class: HighlightedListFieldFormatter
    buttons
        print_invoices
            icon: icon-print
            vname: LBL_STATEMENTS_BUTTON_LABEL
            type: button
            perform: sListView.sendMassUpdate('{LIST_ID}', 'PrintInvoices', true, null, false)
    massupdate_handlers
        --
            name: InvoicesPDF
            class: InvoicePDF
            file: modules/Invoice/InvoicePDF.php
view
	pdf_manager
		file: modules/Invoice/InvoicePDF.php
		class: InvoicePDF
hooks
    view
        --
            class_function: add_view_popups
            required_fields: [billing_account_id]
basic_filters
	view_closed
	billing_account
filters
	view_closed
		default_value: false
		type: flag
		negate_flag: true
		vname: LBL_VIEW_CLOSED_ITEMS
		multiple
			--
				operator: non_zero
				field: amount_due
			--
				operator: not
				field: cancelled
fields
	billing_address
		vname: LBL_BILLING_ADDRESS
		type: address
		source
			type: address
			prefix: billing_
	shipping_address
		vname: LBL_SHIPPING_ADDRESS
		type: address
		source
			type: address
			prefix: shipping_
	line_items
		vname: LBL_LINE_ITEMS
		widget: TallyWidget
		reportable: false
	default_discount_id
		type: id
		reportable: false
		updateable: true
	default_tax_code_id
		type: id
		reportable: false
		updateable: true

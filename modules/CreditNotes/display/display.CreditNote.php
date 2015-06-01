<?php return; /* no output */ ?>

list
	default_order_by: date_entered desc
view
	pdf_manager
		file: modules/CreditNotes/CreditNotePDF.php
		class: CreditNotePDF
basic_filters
	view_closed
auto_filters
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
    amt_due
		vname: LBL_LIST_AMOUNT_DUE
        vname_module: CreditNotes
        type: varchar
        widget: CreditNoteAmountDue
widgets
    CreditNoteAmountDue
        type: column
        path: modules/CreditNotes/widgets/CreditNoteAmountDue.php


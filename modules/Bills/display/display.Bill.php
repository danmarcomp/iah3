<?php return; /* no output */ ?>

list
	default_order_by: date_entered desc
    field_formatter
        file: include/layout/HighlightedListFieldFormatter.php
        class: HighlightedListFieldFormatter
view
	pdf_manager
		file: modules/Bills/BillPDF.php
		class: BillPDF
hooks
basic_filters
    view_closed
auto_filters
    supplier
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
	bill_number
		operator: =
		where_function: search_by_number
		vname: LBL_BILL_NUMBER
	name
	amount
	amount_due
        field: amount_due
        operator: non_zero
	supplier_name
		field: suppliers.name
fields
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

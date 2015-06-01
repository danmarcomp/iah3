<?php return; /* no output */ ?>

list
	default_order_by: date_entered desc
    field_formatter
        file: include/layout/HighlightedListFieldFormatter.php
        class: HighlightedListFieldFormatter
view
	pdf_manager
		file: modules/SalesOrders/SalesOrderPDF.php
		class: SalesOrderPDF
edit
	add_conversion_fields
		- related_quote_id
basic_filters
	view_closed
filters
	view_closed
		default_value: false
		type: flag
		negate_flag: true
		vname: LBL_VIEW_CLOSED_ITEMS
		field: so_stage
		operator: not_like
		match: prefix
		value: "Closed "
	related_quote_number
		vname: LBL_RELATED_QUOTE_NUM
        type: int
		field: quotes.quote_number
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

<?php return; /* no output */ ?>

list
	default_order_by: date_entered desc
view
	pdf_manager
		file: modules/Shipping/ShippingPDF.php
		class: ShippingPDF
basic_filters
	view_closed
filters
	view_closed
		default_value: false
		type: flag
		negate_flag: true
		vname: LBL_VIEW_CLOSED_ITEMS
		field: shipping_stage
		operator: not_like
		match: prefix
		value: Shipped
fields
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

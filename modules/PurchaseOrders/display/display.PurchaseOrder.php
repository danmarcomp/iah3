<?php return; /* no output */ ?>

list
	default_order_by: date_entered desc
view
	pdf_manager
		file: modules/PurchaseOrders/PurchaseOrderPDF.php
		class: PurchaseOrderPDF
hooks
    edit
        --
            class_function: drop_ship
basic_filters
	view_closed
filters
	view_closed
		default_value: false
		type: flag
		negate_flag: true
		vname: LBL_VIEW_CLOSED_ITEMS
		multiple
			--
				operator: not_eq
				field: shipping_stage
				value: Received
			--
				operator: not
				field: cancelled
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
	default_discount_id
		type: id
		reportable: false
		updateable: true
	default_tax_code_id
		type: id
		reportable: false
		updateable: true

<?php return; /* no output */ ?>

list
	default_order_by: date_entered desc
view
	pdf_manager
		file: modules/Receiving/ReceivingPDF.php
		class: ReceivingPDF
basic_filters
	view_closed
filters
	view_closed
		default_value: false
		type: flag
		negate_flag: true
		vname: LBL_VIEW_CLOSED_ITEMS
		operator: not_eq
		field: receiving_stage
		value: Received
	related_po_number
		field: po.po_number
fields
	line_items
		vname: LBL_LINE_ITEMS
		widget: TallyWidget
		reportable: false

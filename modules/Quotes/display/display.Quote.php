<?php return; /* no output */ ?>

list
	default_order_by: date_entered desc
view
	pdf_manager
		file: modules/Quotes/QuotePDF.php
		class: QuotePDF
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
		field: quote_stage
		operator: not_like
		match: prefix
		value: "Closed "
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
widgets
	TallyWidget
		type: section
		path: modules/Quotes/widgets/TallyWidget.php

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Invoice/InvoiceLine.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: invoice_lines
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	invoice
		vname: LBL_INVOICE
		type: ref
		required: true
		bean_name: Invoice
	line_group
		vname: LBL_LINE_GROUP
		type: ref
		required: true
		reportable: false
		bean_name: InvoiceLineGroup
	pricing_adjust
		vname: LBL_PRICE_ADJUSTMENT
		type: ref
		bean_name: InvoiceAdjustment
	name
		vname: LBL_NAME
		type: varchar
		len: 4096
	position
		vname: LBL_POSITION
		type: int
	parent
		vname: LBL_PARENT_LINE
		type: ref
		bean_name: InvoiceLine
	quantity
		vname: LBL_QUANTITY
		type: float
	ext_quantity
		vname: LBL_EXT_QUANTITY
		type: float
	related_type
		vname: LBL_RELATED_TYPE
		type: module_name
	related
		vname: LBL_RELATED
		type: ref
		dynamic_module: related_type
	mfr_part_no
		vname: LBL_PARTNO
		type: varchar
		len: 100
	serial_no
		vname: LBL_SERIAL_NO
		type: varchar
		len: 100
		default: ""
	serial_numbers
		vname: LBL_SERIAL_NO
		type: text
		default: ""
	tax_class
		vname: LBL_TAX_CODE
		type: ref
		bean_name: TaxCode
	sum_of_components
		vname: LBL_SUM_OF_COMPONENTS
		type: bool
		isnull: false
		default: 0
	cost_price
		vname: LBL_COST
		type: currency
	list_price
		vname: LBL_LIST_PRICE
		type: currency
	unit_price
		vname: LBL_UNIT_PRICE
		type: currency
	std_unit_price
		vname: LBL_STD_UNIT_PRICE
		type: currency
	ext_price
		vname: LBL_EXT_PRICE
		type: currency
	event_session
		vname: LBL_SESSION
		type: ref
		bean_name: Event
	event_session_name
		vname: LBL_SESSION_NAME
		type: varchar
		len: 60
	event_session_date
		vname: LBL_SESSION_DATE
		type: datetime
indices
	invoice_lines_posn
		fields
			- invoice_id
			- line_group_id
			- position
	invoice_lines_reltype
		fields
			- related_type
			- related_id

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Invoice/InvoiceAdjustment.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: invoice_adjustments
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	invoice
		vname: LBL_INVOICE_ID
		type: ref
		required: true
		reportable: false
		bean_name: Invoice
	line_group
		vname: LBL_GROUP_ID
		type: ref
		required: true
		reportable: false
		bean_name: InvoiceLineGroup
	line
		vname: LBL_LINE_ID
		type: ref
		reportable: false
		bean_name: InvoiceLine
	name
		vname: LBL_NAME
		type: varchar
		len: 100
	position
		vname: LBL_POSITION
		type: int
	related_type
		vname: LBL_RELATED_TYPE
		type: module_name
	related
		vname: LBL_RELATED_ID
		type: ref
		dynamic_module: related_type
	rate
		vname: LBL_RATE
		type: float
		dbType: double
	type
		vname: LBL_TYPE
		type: enum
		len: 30
		options: price_adjustment_type_dom
	amount
		vname: LBL_AMOUNT
		type: currency
	tax_class
		vname: LBL_TAX_CLASS_ID
		type: ref
		bean_name: TaxCode
indices
	invoice_adjustments_posn
		fields
			- invoice_id
			- line_group_id
			- position
	invoice_adjustments_reltype
		fields
			- related_type
			- related_id

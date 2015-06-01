<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/CreditNotes/CreditNoteAdjustment.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: credits_adjustments
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	credit
		vname: LBL_CREDIT_ID
		type: ref
		required: true
		reportable: false
		bean_name: CreditNote
	line_group
		vname: LBL_GROUP_ID
		type: ref
		required: true
		reportable: false
		bean_name: CreditNoteLineGroup
	line
		vname: LBL_LINE_ID
		type: ref
		reportable: false
		bean_name: CreditNoteLine
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
	credit_adjustments_posn
		fields
			- credit_id
			- line_group_id
			- position
	credit_adjustments_reltype
		fields
			- related_type
			- related_id

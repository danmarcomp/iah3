<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Bills/BillLine.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: bill_lines
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	bills
		vname: LBL_BILL_ID
		type: ref
		required: true
		reportable: false
		bean_name: Bill
	line_group
		vname: LBL_GROUP_ID
		type: ref
		required: true
		reportable: false
		bean_name: BillLineGroup
	pricing_adjust
		vname: LBL_GROUP_ID
		type: ref
		reportable: false
		bean_name: BillAdjustment
	name
		vname: LBL_NAME
		type: varchar
		len: 4096
	description
		vname: LBL_DESCRIPTION
		type: text
	position
		vname: LBL_POSITION
		type: int
	parent
		vname: LBL_PARENT_ID
		type: ref
		reportable: false
		bean_name: BillLine
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
		vname: LBL_RELATED_ID
		type: ref
		dynamic_module: related_type
	mfr_part_no
		vname: LBL_PARTNO
		type: varchar
		len: 100
	tax_class
		vname: LBL_TAX_CLASS_ID
		type: ref
		bean_name: TaxCode
	sum_of_components
		vname: LBL_SUM_OF_COMPONENTS
		type: bool
		isnull: false
		default: 0
	unit_price
		vname: LBL_UNIT_PRICE
		type: currency
	std_unit_price
		vname: LBL_STD_UNIT_PRICE
		type: currency
	ext_price
		vname: LBL_EXT_PRICE
		type: currency
indices
	bills_lines_posn
		fields
			- bills_id
			- line_group_id
			- position
	bills_lines_reltype
		fields
			- related_type
			- related_id

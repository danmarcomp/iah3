<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Receiving/Receiving.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: receiving_lines
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	receiving
		vname: LBL_RECEIVING_ID
		type: ref
		required: true
		reportable: false
		bean_name: Receiving
	line_group
		vname: LBL_GROUP_ID
		type: ref
		required: true
		reportable: false
		bean_name: ReceivingLineGroup
	pricing_adjust
		vname: LBL_GROUP_ID
		type: ref
		reportable: false
		bean_name: ReceivingAdjustment
	name
		vname: LBL_NAME
		type: varchar
		len: 4096
	position
		vname: LBL_POSITION
		type: int
	parent
		vname: LBL_PARENT_ID
		type: ref
		reportable: false
		bean_name: ReceivingLine
	quantity
		vname: LBL_QUANTITY
		type: float
	ext_quantity
		vname: LBL_EXT_QUANTITY
		type: float
	po_line
		vname: LBL_PO_ID
		type: ref
		bean_name: PurchaseOrder
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
		vname: LBL_TAX_CLASS_ID
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
indices
	receiving_lines_posn
		fields
			- receiving_id
			- line_group_id
			- position
	receiving_lines_reltype
		fields
			- related_type
			- related_id

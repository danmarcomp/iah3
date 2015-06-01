<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/PurchaseOrders/PurchaseOrderLine.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: purchase_order_lines
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	purchase_orders
		vname: LBL_PURCHASE_ORDER_ID
		type: ref
		required: true
		reportable: false
		bean_name: PurchaseOrder
	line_group
		vname: LBL_GROUP_ID
		type: ref
		required: true
		reportable: false
		bean_name: PurchaseOrderLineGroup
	pricing_adjust
		vname: LBL_GROUP_ID
		type: ref
		reportable: false
		bean_name: PurchaseOrderAdjustment
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
		bean_name: PurchaseOrderLine
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
	purchase_orders_lines_posn
		fields
			- purchase_orders_id
			- line_group_id
			- position
	purchase_orders_lines_reltype
		fields
			- related_type
			- related_id

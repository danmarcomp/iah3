<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/PurchaseOrders/PurchaseOrderAdjustment.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: purchase_order_adjustments
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
	line
		vname: LBL_LINE_ID
		type: ref
		reportable: false
		bean_name: PurchaseOrderLine
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
	tax_class
		type: ref
		vname: LBL_TAX_CLASS
		bean_name: TaxCode
	editable
		vname: LBL_EDITABLE
		type: bool
		default: 0		
	amount
		vname: LBL_AMOUNT
		type: currency
indices
	purchase_orders_adjustments_posn
		fields
			- purchase_orders_id
			- line_group_id
			- position
	purchase_orders_adjustments_reltype
		fields
			- related_type
			- related_id

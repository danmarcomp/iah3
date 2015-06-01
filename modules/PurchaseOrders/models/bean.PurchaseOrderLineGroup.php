<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/PurchaseOrders/PurchaseOrderLineGroup.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: purchase_order_line_groups
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	parent
		vname: LBL_PURCHASE_ORDER_ID
		type: ref
		required: true
		reportable: false
		bean_name: PurchaseOrderLineGroup
	name
		vname: LBL_GROUP_NAME
		type: varchar
		len: 50
	position
		vname: LBL_POSITION
		type: int
	status
		vname: LBL_GROUP_STAGE
		type: enum
		len: 30
		options: quote_stage_dom
	subtotal
		vname: LBL_SUBTOTAL
		type: currency
	total
		vname: LBL_TOTAL
		type: currency
indices
	purchase_orders_line_groups_posn
		fields
			- parent_id
			- position

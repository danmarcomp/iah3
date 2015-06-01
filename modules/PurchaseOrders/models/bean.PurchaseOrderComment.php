<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/PurchaseOrders/PurchaseOrderComment.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: purchase_order_comments
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
	name
		vname: LBL_NAME
		type: varchar
		len: 100
	position
		vname: LBL_POSITION
		type: int
	parent
		vname: LBL_PARENT_ID
		type: ref
		reportable: false
		bean_name: PurchaseOrderComment
	body
		vname: LBL_BODY
		type: text
indices
	purchase_orders_comments_posn
		fields
			- purchase_orders_id
			- line_group_id
			- position

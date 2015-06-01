<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/SalesOrders/SalesOrderComment.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: sales_order_comments
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	sales_orders
		vname: LBL_SALES_ORDER_ID
		type: ref
		required: true
		reportable: false
		bean_name: SalesOrder
	line_group
		vname: LBL_GROUP_ID
		type: ref
		required: true
		reportable: false
		bean_name: SalesOrderLineGroup
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
		bean_name: SalesOrderComment
	body
		vname: LBL_BODY
		type: text
indices
	sales_orders_comments_posn
		fields
			- sales_orders_id
			- line_group_id
			- position

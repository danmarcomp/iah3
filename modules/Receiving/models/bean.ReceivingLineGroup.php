<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Receiving/ReceivingLineGroup.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: receiving_line_groups
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	parent
		vname: LBL_SALES_ORDER_ID
		type: ref
		required: true
		reportable: false
		bean_name: ReceivingLineGroup
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
		options: so_stage_dom
	pricing_method
		vname: LBL_PRICING_METHOD
		type: enum
		len: 30
		options: quote_pricing_method_dom
	pricing_percentage
		vname: LBL_PRICING_PERCENTAGE
		type: double
	subtotal
		vname: LBL_SUBTOTAL
		type: currency
	total
		vname: LBL_TOTAL
		type: currency
	group_type
		type: varchar
		len: 15
		default: products
indices
	receiving_line_groups_posn
		fields
			- parent_id
			- position

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Shipping/ShippingAdjustment.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: shipping_adjustments
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	shipping
		vname: LBL_SHIPPING_ID
		type: ref
		required: true
		reportable: false
		bean_name: Shipping
	line_group
		vname: LBL_GROUP_ID
		type: ref
		required: true
		reportable: false
		bean_name: ShippingLineGroup
	line
		vname: LBL_LINE_ID
		type: ref
		reportable: false
		bean_name: ShippingLine
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
indices
	shipping_adjustments_posn
		fields
			- shipping_id
			- line_group_id
			- position
	shipping_adjustments_reltype
		fields
			- related_type
			- related_id

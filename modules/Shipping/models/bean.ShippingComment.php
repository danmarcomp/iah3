<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Shipping/ShippingComment.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: shipping_comments
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
		bean_name: ShippingComment
	body
		vname: LBL_BODY
		type: text
indices
	shipping_comments_posn
		fields
			- shipping_id
			- line_group_id
			- position

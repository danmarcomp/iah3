<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Receiving/ReceivingComment.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: receiving_comments
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
		bean_name: ReceivingComment
	body
		vname: LBL_BODY
		type: text
indices
	receiving_comments_posn
		fields
			- receiving_id
			- line_group_id
			- position

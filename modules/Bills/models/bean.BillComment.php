<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Bills/BillComment.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: bill_comments
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	bills
		vname: LBL_BILL_ID
		type: ref
		required: true
		reportable: false
		bean_name: Bill
	line_group
		vname: LBL_GROUP_ID
		type: ref
		required: true
		reportable: false
		bean_name: BillLineGroup
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
		bean_name: BillComment
	body
		vname: LBL_BODY
		type: text
indices
	bills_comments_posn
		fields
			- bills_id
			- line_group_id
			- position

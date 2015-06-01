<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/CreditNotes/CreditNoteComment.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: credits_comments
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	credit
		vname: LBL_CREDIT_ID
		type: ref
		required: true
		reportable: false
		bean_name: CreditNote
	line_group
		vname: LBL_GROUP_ID
		type: ref
		required: true
		reportable: false
		bean_name: CreditNoteLineGroup
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
		bean_name: CreditNoteLine
	body
		vname: LBL_BODY
		type: text
indices
	credit_comments_posn
		fields
			- credit_id
			- line_group_id
			- position

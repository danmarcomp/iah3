<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Quotes/QuoteComment.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: quote_comments
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	quote
		vname: LBL_QUOTE_ID
		type: ref
		required: true
		reportable: false
		bean_name: Quote
	line_group
		vname: LBL_GROUP_ID
		type: ref
		required: true
		reportable: false
		bean_name: QuoteLineGroup
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
		bean_name: QuoteComment
	body
		vname: LBL_BODY
		type: text
indices
	quote_comments_posn
		fields
			- quote_id
			- line_group_id
			- position

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Invoice/InvoiceComment.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: invoice_comments
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	invoice
		vname: LBL_INVOICE_ID
		type: ref
		required: true
		reportable: false
		bean_name: Invoice
	line_group
		vname: LBL_GROUP_ID
		type: ref
		required: true
		reportable: false
		bean_name: InvoiceLineGroup
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
		bean_name: InvoiceComment
	body
		vname: LBL_BODY
		type: text
indices
	invoice_comments_posn
		fields
			- invoice_id
			- line_group_id
			- position

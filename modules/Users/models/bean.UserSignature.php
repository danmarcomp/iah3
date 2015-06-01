<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Users/UserSignature.php
	unified_search: false
	duplicate_merge: false
	table_name: users_signatures
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	user
		vname: LBL_USER_ID
		type: ref
		bean_name: User
	name
		vname: LBL_NAME
		type: varchar
		len: 255
	signature
		vname: LBL_SIGNATURE
		type: text
		reportable: false
	signature_html
		vname: LBL_SIGNATURE_HTML
        type: html
		dbType: text
		reportable: false
indices
	idx_user_id
		fields
			- user_id

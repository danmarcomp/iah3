<?php return; /* no output */ ?>

detail
	type: table
	table_name: user_preferences
	primary_key: [assigned_user_id, category]
fields
	app.assigned_user
	category
		type: varchar
		vname: LBL_CATEGORY
		charset: ascii
		len: 50
	contents
		type: text
    	vname: LBL_DESCRIPTION
    	charset: ascii
	app.date_modified

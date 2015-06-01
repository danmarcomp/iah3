<?php return; /* no output */ ?>

detail
	type: table
	table_name: users_activity
	primary_key: [user_id, session_id]
fields
	app.deleted
	user
		type: ref
		required: true
		bean_name: User
	access_time
		type: datetime
		required: true
	session_id
		type: char
		len: 40
		charset: ascii
		required: true
indices
	user_activity_time
		fields
			- access_time

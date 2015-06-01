<?php return; /* no output */ ?>

detail
	type: table
	table_name: mail_threads
	primary_key: [thread_id, message_id]
fields
	app.date_modified
	app.deleted
	thread_id
		type: char
		len: 36
		required: true
	message_id
		type: char
		len: 255
		required: true
indices
	mail_threads_msg_id
		fields
			- message_id

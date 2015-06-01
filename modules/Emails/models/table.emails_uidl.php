<?php return; /* no output */ ?>

detail
	type: table
	table_name: emails_uidl
	primary_key
		- boxhash
		- uidl
		- message_id
fields
	boxhash
		type: char
		len: 32
		default: ""
	uidl
		type: char
		len: 70
		default: ""
	message_id
		type: char
		len: 255
		default: ""
indices
	uidl_uidl
		fields
			- uidl
	uidl_message_id
		fields
			- message_id

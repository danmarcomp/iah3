<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Feeds/Feed.php
	unified_search: false
	duplicate_merge: false
	comment: RSS Feeds
	table_name: feeds
	primary_key: id
	default_order_by: title
	display_name: title
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	title
		type: name
		len: 100
		vname: LBL_TITLE
		comment: Title of RSS feed
		unified_search: true
		required: true
	description
		type: text
		vname: LBL_DESCRIPTION
		comment: Description of RSS feed
	url
		type: url
		len: 255
		vname: LBL_URL
		comment: URL that represents the RSS feed
		required: true
		massupdate: true
indices
	idx_feed_name
		fields
			- title
			- deleted

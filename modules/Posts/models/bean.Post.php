<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Posts/Post.php
	unified_search: false
	duplicate_merge: false
	comment: Captures posts made to threads in Forums module
	table_name: posts
	primary_key: id
	display_name: title
hooks
	notify
		--
			class_function: send_notification
	after_save
		--
			class_function: counters_inc_created
	after_delete
		--
			class_function: counters_dec_deleted
fields
	app.id
	app.date_entered
	app.created_by_user
	app.date_modified
	app.modified_user
	app.deleted
	title
		vname: LBL_TITLE
		required: true
		type: name
		len: 255
		comment: Title of the post
	description_html
		vname: LBL_BODY
		type: html
		comment: Post content
	thread
		vname: LBL_THREAD_NAME
		type: ref
		comment: Associated thread
		bean_name: Thread
		massupdate: true
	post_detail_view
		vname: LNK_POST_LIST
		type: html
		reportable: false
		source
			type: function
			value_function
				- Post
				- get_post_detail_view
			fields
				- id
				- title
				- created_by_user
				- modified_user
				- date_entered
				- date_modified
				- description_html
				- thread_id
fields_compat
	created_by_user
		type: varchar
		source: non-db
	modified_by_user
		type: varchar
		source: non-db
	thread_name
		type: varchar
		source: non-db

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Forums/Forum.php
	unified_search: false
	duplicate_merge: false
	comment: Forums are named collections of threads
	table_name: forums
	primary_key: id
	display_name: title
fields
	app.id
	app.date_entered
	app.created_by_user
	app.date_modified
	app.modified_user
	app.deleted
	category
		vname: LBL_CATEGORY
		vname_list: LBL_CATEGORY
		type: ref
		bean_name: ForumTopic
		required: true
		comment: Category forum is associated
		massupdate: true
	threadandpostcount
		vname: LBL_POST_COUNT
		type: int
		default: 0
		comment:
			The number of posts in this Forum. Threads are included in
			this count.
	threadcount
		vname: LBL_THREAD_COUNT
		type: int
		default: 0
		comment: The number of threads in this Forum.
	title
		vname: LBL_TITLE
		required: true
		type: name
		len: 150
		comment: Forum title
	description
		vname: LBL_DESCRIPTION
		type: text
		comment: Forum description
	latest_thread
		vname: LBL_RECENT_THREAD_TITLE
		type: varchar
		len: 255
		reportable: false
		source
			type: function
			value_function: [Forum, get_latest_thread]
			fields: [id]
	latest_post_by
		vname: LBL_RECENT_THREAD_MODIFIED_NAME
		type: varchar
		len: 255
		reportable: false
		source
			type: function
			value_function: [Forum, get_latest_post_by]
			fields: [id]
	latest_post_time
		vname: LBL_LAST_POST_TIME
		type: varchar
		len: 255
		reportable: false
		source
			type: function
			value_function: [Forum, get_latest_post_time]
			fields: [id]
links
	threads
		relationship: forums_threads
		module: Threads
		bean_name: Thread
		vname: LBL_THREADS_SUBPANEL_TITLE
fields_compat
	category_ranking
		type: varchar
		source: non-db
	recent_thread_title
		type: varchar
		source: non-db
	recent_thread_id
		type: varchar
		source: non-db
	recent_thread_modified_name
		type: varchar
		source: non-db
	recent_thread_modified_id
		type: varchar
		source: non-db
relationships
	forums_threads
		key: id
		target_bean: Thread
		target_key: forum_id
		relationship_type: one-to-many

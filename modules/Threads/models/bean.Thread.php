<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Threads/Thread.php
	unified_search: false
	duplicate_merge: false
	table_name: threads
	primary_key: id
	display_name: title
fields
	app.id
	app.date_entered
	app.created_by_user
	app.date_modified
	app.modified_user
	app.deleted
	postcount
		vname: LBL_POST_COUNT
		type: int
		default: 0
	title
		vname: LBL_TITLE
		required: true
		type: name
		len: 255
		unified_search: true
	description_html
		vname: LBL_BODY
		type: html
	forum
		vname: LBL_FORUM
		type: ref
		required: true
		bean_name: Forum
		massupdate: true
	is_sticky
		vname: LBL_IS_STICKY
		type: bool
		default: 0
		massupdate: true
	view_count
		vname: LBL_VIEW_COUNT
		type: int
		required: true
		default: 0
	user_photo
		vname: LBL_CREATED_BY
		type: html
		upload_dir: {FILES}/images/directory/
		reportable: false
		editable: false
		source
			type: function
			value_function
				- Thread
				- render_created_user
			fields
				- created_by
				- created_by_user
	latest_post
		vname: LBL_RECENT_POST_TITLE
		type: varchar
		len: 255
		reportable: false
		source
			type: function
			value_function
				- Thread
				- get_latest_post
			fields
				- id
	current_user_subscribed
		vname: LBL_SUBSCRIBED
		type: bool
		reportable: false
		source
			type: function
			value_function
				- Thread
				- current_user_subscribed
			fields
				- id
		massupdate: true
	latest_post_by
		vname: LBL_RECENT_POST_MODIFIED_NAME
		type: varchar
		len: 255
		reportable: false
		source
			type: function
			value_function
				- Thread
				- get_latest_post_by
			fields
				- id
hooks
	after_save
		--
			class_function: counters_inc_created
	after_delete
		--
			class_function: counters_dec_deleted
links
	accounts
		relationship: accounts_threads
		vname: LBL_ACCOUNTS
	bugs
		relationship: bugs_threads
		vname: LBL_BUGS
	cases
		relationship: cases_threads
		vname: LBL_CASES
	opportunities
		relationship: opportunities_threads
		module: opportunities
		bean_name: Opportunity
		vname: LBL_OPPORTUNITIES
	project
		relationship: project_threads
		module: project
		bean_name: Project
		vname: LBL_PROJECTS
	posts
		relationship: threads_posts
		module: Posts
		bean_name: Post
		vname: LBL_POSTS_SUBPANEL_TITLE
	users
		relationship: users_threads
		module: Users
		bean_name: User
		vname: LBL_USERS
fields_compat
	forum_name
		source: non-db
		vname: LBL_FORUM_NAME
		rname: title
		id_name: forum_id
		type: relate
		link: forums
		table: forums
		module: Forums
		len: 255
		massupdate: false
		importable: false
	created_by_user
		type: varchar
		source: non-db
	modified_by_user
		type: varchar
		source: non-db
	stickyDisplay
		type: varchar
		source: non-db
	recent_post_title
		type: varchar
		source: non-db
	recent_post
		type: ref
		source: non-db
		bean_name: Post
	recent_post_modified
		type: ref
		source: non-db
		bean_name: Post
	recent_post_modified_name
		type: varchar
		source: non-db
relationships
	threads_posts
		key: id
		target_bean: Post
		target_key: thread_id
		relationship_type: one-to-many

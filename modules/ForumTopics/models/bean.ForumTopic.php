<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ForumTopics/ForumTopic.php
	unified_search: false
	duplicate_merge: false
	comment: Topics are used to categorize forums
	table_name: forumtopics
	primary_key: id
	display_name: name
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.modified_user
	app.created_by_user
	name
		vname: LBL_NAME
		type: name
		len: 50
		required: true
		comment: Topic name
	list_order
		vname: LBL_LIST_ORDER
		type: int
		len: 4
		required: true
		default: 0
		comment: Topic list order
links
	forums
		relationship: forums_topics
		module: Forums
		bean_name: Forum
		vname: LBL_FORUMS_SUBPANEL_TITLE
indices
	idx_forumtopics
		fields
			- name
			- deleted
relationships
	forums_topics
		key: id
		target_bean: Forum
		target_key: category_id
		relationship_type: one-to-many

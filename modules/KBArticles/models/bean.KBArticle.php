<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/KBArticles/KBArticle.php
	unified_search: true
	duplicate_merge: false
	table_name: kb_articles
	primary_key: id
hooks
	new_record
		--
			class_function: init_record
	after_save
		--
			class_function: set_related
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	app.created_by_user
	app.assigned_user
	app.modified_user
	name
		vname: LBL_NAME
		type: name
		len: 100
		required: true
		unified_search: true
		vname_list: LBL_LIST_NAME
	summary
		vname: LBL_SUMMARY
		type: text
		fulltext_search: true
	description
		vname: LBL_DESCRIPTION
		type: html
		dbType: text
		fulltext_search: true
	portal_active
		vname: LBL_PORTAL_ACTIVE
		type: bool
		default: 1
		reportable: false
		massupdate: true
links
	notes
		relationship: kb_articles_notes
		ignore_role: true
		vname: LBL_NOTES
	cases
		relationship: kb_articles_cases
		ignore_role: true
		vname: LBL_CASES
	documents
		relationship: kb_articles_documents
		ignore_role: true
		vname: LBL_DOCUMENTS
indices
	kb_articles_name
		fields
			- name

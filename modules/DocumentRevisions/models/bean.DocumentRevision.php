<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/DocumentRevisions/DocumentRevision.php
	unified_search: false
	duplicate_merge: false
	table_name: document_revisions
	primary_key: id
	default_order_by: revision
	display_name
		type: prefixed
		fields
			- revision
			- document_ref.document_name
hooks
	new_record
		--
			class_function: new_record
	after_save
		--
			class_function: after_save
fields
	app.id
	app.date_entered
	app.created_by_user
	app.deleted
	app.date_modified
	change_log
		vname: LBL_CHANGE_LOG
		type: text
		dbType: varchar
		len: 255
	document_ref
		vname: LBL_DOCUMENT
		type: ref
		bean_name: Document
		updateable: false
		required: true
		id_name: document_id
		massupdate: true
	document_revision
		vname: LBL_LATEST_REVISION
		type: varchar
		source
			field: document_ref.document_revision.revision
		editable: false
	filename
		vname: LBL_FILENAME
		type: file_ref
		required: true
		file_ext: file_ext
		file_mime_type: file_mime_type
		updateable: false
		width: 40
	file_ext
		vname: LBL_FILE_EXTENSION
		type: varchar
		len: 25
	file_mime_type
		vname: LBL_MIME
		type: varchar
		len: 100
	revision
		vname: LBL_REVISION
		type: name
		len: 25
		required: true
links
	documents
		relationship: document_revisions
		vname: LBL_REVISIONS
relationships
	revisions_created_by
		key: created_by
		target_bean: User
		target_key: id
		relationship_type: one-to-many

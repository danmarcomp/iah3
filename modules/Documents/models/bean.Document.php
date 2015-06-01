<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Documents/Document.php
	unified_search: false
	duplicate_merge: false
	table_name: documents
	primary_key: id
	default_order_by: document_name
	display_name: document_name
	reportable: true
hooks
	new_record
		--
			class_function: init_record
	after_save
		--
			class_function: set_revision
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	app.modified_user
	app.created_by_user
	document_name
		vname: LBL_NAME
		type: name
		len: 255
		required: true
		unified_search: true
	active_date
		vname: LBL_DOC_ACTIVE_DATE
		type: date
		vname_list: LBL_LIST_ACTIVE_DATE
		massupdate: true
	exp_date
		vname: LBL_DOC_EXP_DATE
		type: date
		vname_list: LBL_LIST_EXP_DATE
		massupdate: true
	description
		vname: LBL_DESCRIPTION
		type: text
	category_id
		vname: LBL_SF_CATEGORY
		type: enum
		len: 25
		options: document_category_dom
		vname_list: LBL_LIST_CATEGORY
		massupdate: true
	subcategory_id
		vname: LBL_SF_SUBCATEGORY
		type: enum
		len: 25
		options: document_subcategory_dom
		vname_list: LBL_LIST_SUBCATEGORY
		massupdate: true
	status_id
		vname: LBL_DOC_STATUS
		type: enum
		len: 25
		options: document_status_dom
		required: true
		default: Draft
		massupdate: true
	department
		vname: LBL_DEPARTMENT
		type: enum
		options: departments_dom
		len: 30
		massupdate: true
	keywords
		vname: LBL_KEYWORDS
		type: varchar
		len: 100
	section
		vname: LBL_SECTION
		type: varchar
		len: 20
		isnull: false
		default: ""
		reportable: false
	document_revision
		vname: LBL_LATEST_REVISION
		type: ref
		bean_name: DocumentRevision
		editable: false
		ref_display_name: revision
	mail_merge_document
		vname: LBL_MAIL_MERGE_DOCUMENT
		type: bool
		dbType: varchar
		len: 3
		default: off
		audited: true
		massupdate: true
	related_doc
		vname: LBL_DET_RELATED_DOCUMENT
		type: ref
		bean_name: Document
		massupdate: false
	related_doc_rev
		vname: LBL_DET_RELATED_DOCUMENT_VERSION
		type: ref
		bean_name: DocumentRevision
		massupdate: false
	revision_number
		vname: LBL_REVISION_NAME
		type: varchar
		source
			type: field
			field: document_revision.revision
		updateable: false
		required: true
	revision_filename
		vname: LBL_FILENAME
		type: file_ref
		source
			type: field
			field: document_revision.filename
        file_mime_type: document_revision.file_mime_type
		reportable: false
		updateable: false
		required: true
	revision_creator
		vname: LBL_LAST_REV_CREATOR
		type: ref
		source
			type: field
			field: document_revision.created_by_user
		massupdate: true
	revision_date
		vname: LBL_LAST_REV_DATE
		type: datetime
		editable: false
		format: date_only
		source
			type: field
			field: document_revision.date_entered
	is_template
		vname: LBL_IS_TEMPLATE
		type: bool
		default: 0
		reportable: false
		massupdate: true
	template_type
		vname: LBL_TEMPLATE_TYPE
		type: enum
		len: 25
		options: document_template_type_dom
		reportable: false
		massupdate: true
links
	accounts
		relationship: documents_accounts
		ignore_role: true
		vname: LBL_ACCOUNTS
	contacts
		relationship: documents_contacts
		ignore_role: true
		vname: LBL_CONTACTS
	opportunities
		relationship: documents_opportunities
		ignore_role: true
		vname: LBL_OPPORTUNITIES
	projects
		relationship: documents_projects
		ignore_role: true
		vname: LBL_PROJECTS
	leads
		relationship: documents_leads
		ignore_role: true
		vname: LBL_LEADS
	bugs
		relationship: documents_bugs
		ignore_role: true
		vname: LBL_BUGS
	cases
		relationship: documents_cases
		ignore_role: true
		vname: LBL_CASES
	employees
		relationship: documents_employees
		ignore_role: true
		vname: LBL_EMPLOYEES
	kb_articles
		relationship: kb_articles_documents
		ignore_role: true
		vname: LBL_KB_ARTICLES
	revisions
		relationship: document_revisions
		vname: LBL_REVISIONS
		bean_name: DocumentRevision
		module: DocumentRevisions
	related_documents
		relationship: document_related_docs
		vname: LBL_RELATED_DOCUMENTS
	app.securitygroups
		relationship: securitygroups_documents
relationships
	document_revisions
		key: id
		target_bean: DocumentRevision
		target_key: document_id
		relationship_type: one-to-many
	document_related_docs
		key: id
		target_bean: Document
		target_key: related_doc_id
		relationship_type: one-to-many

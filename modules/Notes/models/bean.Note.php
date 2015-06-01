<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Notes/Note.php
	unified_search: true
	duplicate_merge: false
	optimistic_locking: true
	comment: Notes and Attachments
	table_name: notes
	primary_key: id
	default_order_by: name
	importable: LBL_NOTES
	reportable: true
hooks
	after_save
		--
			class_function: update_parent_activity
	new_record
		--
			class_function: init_record
fields
	app.id
	app.date_entered
	app.date_modified
	app.assigned_user
	app.modified_user
	app.created_by_user
	app.deleted
	name
		vname: LBL_SUBJECT
		type: name
		len: 255
		width: 60
		unified_search: true
		comment: Name of the note
		vname_list: LBL_LIST_SUBJECT
	filename
		vname: LBL_FILENAME
		type: file_ref
		file_mime_type: file_mime_type
		len: 255
		unified_search: true
		comment: File name associated with the note (attachment)
		vname_list: LBL_LIST_FILENAME
	file_mime_type
		vname: LBL_FILE_MIME_TYPE
		type: varchar
		len: 100
		comment: Attachment MIME type
	parent_type
		vname: LBL_PARENT_TYPE
		type: module_name
		comment: Sugar module the Note is associated with
		options
			- Accounts
			- Opportunities
			- Cases
			- Documents
			- Leads
			- Bugs
			- Emails
			- Project
			- ProjectTask
			- Quotes
			- Invoice
			- PurchaseOrders
			- Bills
			- SalesOrders
			- Shipping
			- Receiving
			- Meetings
			- Calls
			- KBArticles
	parent
		vname: LBL_RELATED_TO
		vname_list: LBL_LIST_RELATED_TO
		type: ref
		comment: The ID of the Sugar item specified in parent_type
		dynamic_module: parent_type
		massupdate: true
	contact
		vname: LBL_CONTACT_NAME
		vname_list: LBL_LIST_CONTACT
		type: ref
		comment: Contact ID note is associated with
		bean_name: Contact
		add_filters
			--
				param: primary_account
				field_name: parent
		massupdate: true
	contact_phone
		vname: LBL_PHONE
		type: phone
		source
			type: field
			field: contact.phone_work
	contact_email
		vname: LBL_EMAIL_ADDRESS
		type: email
		source
			type: field
			field: contact.email1
	portal_flag
		vname: LBL_PORTAL_FLAG
		type: bool
		required: true
		default: 0
		comment: Portal flag indicator determines if note created via portal
		massupdate: true
	description
		vname: LBL_NOTE
		type: text
		unified_search: true
		comment: Full text of the note
	email_attachment
		vname: ""
		type: bool
		required: true
		default: 0
		reportable: false
		massupdate: false
links
	cases
		relationship: case_notes
		vname: LBL_CASES
	app.securitygroups
		relationship: securitygroups_notes
indices
	notes_attachment
		fields
			- email_attachment
	idx_note_name
		fields
			- name
	idx_notes_parent
		fields
			- parent_id
			- parent_type
	idx_note_contact
		fields
			- contact_id

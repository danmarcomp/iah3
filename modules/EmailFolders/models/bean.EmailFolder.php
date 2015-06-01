<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/EmailFolders/EmailFolder.php
	unified_search: false
	duplicate_merge: false
	table_name: emails_folders
	primary_key: id
hooks
    after_delete
        --
            class_function: clear
    new_record
        --
            class_function: init_record
fields
	app.id
	app.deleted
	user
		vname: LBL_LIST_USER_NAME
		type: ref
		bean_name: User
        massupdate: false
	name
		vname: LBL_NAME
		type: name
		len: 255
		vname_list: LBL_LIST_NAME
        unified_search: true
        required: true
	description
		vname: LBL_DESCRIPTION
		type: text
	reserved
		type: bool
		required: true
		reportable: false
        massupdate: false
        default: 0
    total_messages
        vname: LBL_LIST_NUMBER_TOTAL
        type: formula
        source: non-db
        massupdate: false
        reportable: false
        sortable: false
        formula
            type: function
            class: EmailFolder
            function: calc_total_msg
            fields: [id]
    new_messages
        vname: LBL_LIST_NUMBER_NEW
        type: formula
        source: non-db
        massupdate: false
        reportable: false
        sortable: false
        formula
            type: function
            class: EmailFolder
            function: calc_new_msg
            fields: [id]
indices
	idx_folders_user
		fields
			- user_id
			- deleted
	idx_folders_reserved
		fields
			- id
			- reserved
			- deleted
	idx_folder_name
		fields
			- name
relationships
	emailfolders_emails
		key: id
		target_bean: Email
		target_key: folder
		relationship_type: one-to-many

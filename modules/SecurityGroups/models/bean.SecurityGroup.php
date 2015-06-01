<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/SecurityGroups/SecurityGroup.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	table_name: securitygroups
	primary_key: id
hooks
	after_save
		--
			class_function: after_save
	after_add_link
		--
			class_function: update_acl
	after_remove_link
		--
			class_function: update_acl
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.created_by_user
	app.deleted
	app.assigned_user
	name
		vname: LBL_LIST_NAME
		type: name
		len: 255
		unified_search: true
		comment: The name of the security group
		vname_list: LBL_NAME
	description
		vname: LBL_DESCRIPTION
		type: text
		comment: The security group description
	noninheritable
		vname: LBL_NONINHERITABLE
		type: bool
		reportable: false
		comment: Indicator for whether a group can be inherited by a record
		massupdate: true
links
	users
		relationship: securitygroups_users
		vname: LBL_USERS_SUBPANEL_TITLE
	aclroles
		relationship: securitygroups_acl_roles
		vname: LBL_ROLES_SUBPANEL_TITLE

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ACLRoles/ACLRole.php
	unified_search: false
	duplicate_merge: false
	table_name: acl_roles
	primary_key: id
	standard_records: ["default_-role-0000-0000-000000000001"]
hooks
	before_save
		--
			class_function: before_save
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
	name
		type: name
		vname: LBL_NAME
		len: 150
	description
		vname: LBL_DESCRIPTION
		type: text
links
	users
		relationship: acl_roles_users
		vname: LBL_USERS
	actions
		relationship: acl_roles_actions
		vname: LBL_USERS
	app.securitygroups
		relationship: securitygroups_acl_roles
indices
	idx_aclrole_id_del
		fields
			- id
			- deleted
relationships
	acl_roles_users
		key: id
		target_bean: User
		target_key: id
		join
			table: acl_roles_users
			source_key: role_id
			target_key: user_id
		relationship_type: many-to-many

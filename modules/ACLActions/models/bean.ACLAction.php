<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ACLActions/ACLAction.php
	unified_search: false
	duplicate_merge: false
	comment: Determine the allowable actions available to users
	table_name: acl_actions
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.created_by_user
	app.deleted
	role
		vname: LBL_ROLE
		type: ref
		bean_name: ACLRole
		comment: Reference to parent ACLRole
		required: true
		massupdate: true
	name
		type: char
		charset: ascii
		vname: LBL_NAME
		len: 30
		comment: Name of the allowable action (view, list, delete, edit)
	category
		vname: LBL_CATEGORY
		type: module_name
		dbType: char
		reportable: true
		comment:
			Category of the allowable action (usually the name of a
			module)
	acltype
		vname: LBL_TYPE
		type: char
		len: 30
		reportable: true
		comment: "Specifier for Category, usually \"module\""
	aclaccess
		vname: LBL_ACCESS
		type: int
		len: 3
		reportable: true
		comment: "Number specifying access priority; highest access \"wins\""
indices
	idx_by_role
		fields
			- role_id
			- deleted

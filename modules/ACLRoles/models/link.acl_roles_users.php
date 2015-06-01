<?php return; /* no output */ ?>

detail
	type: link
	table_name: acl_roles_users
	primary_key
		- role_id
		- user_id
fields
	app.date_modified
	app.deleted
	role
		type: ref
		bean_name: Role
	user
		type: ref
		bean_name: User
indices
	idx_aclrole_id
		fields
			- role_id
	idx_acluser_id
		fields
			- user_id
relationships
	acl_roles_users
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: role_id
		join_key_rhs: user_id
		lhs_bean: ACLRole
		rhs_bean: User

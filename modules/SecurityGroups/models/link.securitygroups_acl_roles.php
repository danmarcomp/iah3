<?php return; /* no output */ ?>

detail
	type: link
	table_name: securitygroups_acl_roles
	primary_key: [role_id, securitygroup_id]
fields
	app.date_modified
	app.deleted
	securitygroup
		type: ref
		bean_name: SecurityGroup
	role
		type: ref
		bean_name: ACLRole
relationships
	securitygroups_acl_roles
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: role_id
		lhs_bean: SecurityGroup
		rhs_bean: ACLRole

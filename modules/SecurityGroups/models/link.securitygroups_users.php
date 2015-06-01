<?php return; /* no output */ ?>

detail
	type: link
	table_name: securitygroups_users
	primary_key
		- securitygroup_id
		- user_id
fields
	app.date_modified
	app.deleted
	securitygroup
		type: ref
		bean_name: SecurityGroup
	user
		type: ref
		bean_name: User
	noninheritable
		vname: LBL_NONINHERITABLE
		type: bool
		reportable: false
		comment: Indicator for whether a group can be inherited by a record
indices
	securitygroups_users_idxb
		fields
			- user_id
relationships
	securitygroups_users
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: user_id
		lhs_bean: SecurityGroup
		rhs_bean: User

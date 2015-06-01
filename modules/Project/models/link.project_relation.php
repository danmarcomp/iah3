<?php return; /* no output */ ?>

detail
	type: link
	table_name: project_relation
	primary_key
		- relation_type
		- relation_id
		- project_id
fields
	app.deleted
	app.date_modified
	project
		vname: LBL_PROJECT_ID
		required: true
		type: ref
		bean_name: Project
	relation
		vname: LBL_PROJECT_NAME
		required: true
		type: ref
		dynamic_module: relation_type
	relation_type
		required: true
		type: module_name
		options: project_relation_type_options
indices
	by_project
		fields
			- project_id
relationships
	projects_accounts
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: relation_id
		join_key_rhs: project_id
		relationship_role_column: relation_type
		relationship_role_column_value: Accounts
		lhs_bean: Account
		rhs_bean: Project
	projects_contacts
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: project_id
		join_key_rhs: relation_id
		relationship_role_column: relation_type
		relationship_role_column_value: Contacts
		lhs_bean: Project
		rhs_bean: Contact
	projects_opportunities
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: project_id
		join_key_rhs: relation_id
		relationship_role_column: relation_type
		relationship_role_column_value: Opportunities
		lhs_bean: Project
		rhs_bean: Opportunity
	projects_quotes
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: project_id
		join_key_rhs: relation_id
		relationship_role_column: relation_type
		relationship_role_column_value: Quotes
		lhs_bean: Project
		rhs_bean: Quote

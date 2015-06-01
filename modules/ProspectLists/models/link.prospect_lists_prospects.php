<?php return; /* no output */ ?>

detail
	type: link
	table_name: prospect_lists_prospects
	primary_key
		- related_type
		- related_id
		- prospect_list_id
fields
	app.date_modified
	app.deleted
	prospect_list
		type: ref
		bean_name: Prospect
	related
		type: ref
		dynamic_module: related_type
	related_type
		type: module_name
indices
	idx_plp_pro_id
		fields
			- prospect_list_id
relationships
	prospect_list_contacts
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: prospect_list_id
		join_key_rhs: related_id
		relationship_role_column: related_type
		relationship_role_column_value: Contacts
		lhs_bean: ProspectList
		rhs_bean: Contact
	prospect_list_prospects
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: prospect_list_id
		join_key_rhs: related_id
		relationship_role_column: related_type
		relationship_role_column_value: Prospects
		lhs_bean: ProspectList
		rhs_bean: Prospect
	prospect_list_leads
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: prospect_list_id
		join_key_rhs: related_id
		relationship_role_column: related_type
		relationship_role_column_value: Leads
		lhs_bean: ProspectList
		rhs_bean: Lead
	prospect_list_users
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: prospect_list_id
		join_key_rhs: related_id
		relationship_role_column: related_type
		relationship_role_column_value: Users
		lhs_bean: ProspectList
		rhs_bean: User

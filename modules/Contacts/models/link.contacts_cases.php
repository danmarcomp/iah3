<?php return; /* no output */ ?>

detail
	type: link
	table_name: contacts_cases
	primary_key
		- contact_id
		- case_id
fields
	app.date_modified
	app.deleted
	contact
		type: ref
		bean_name: Contact
	case
		type: ref
		bean_name: aCase
	contact_role
		type: varchar
		len: 50
		default: Primary Contact
indices
	idx_con_case_con
		fields
			- contact_id
	idx_con_case_case
		fields
			- case_id
relationships
	contacts_cases
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: contact_id
		join_key_rhs: case_id
		lhs_bean: Contact
		rhs_bean: aCase

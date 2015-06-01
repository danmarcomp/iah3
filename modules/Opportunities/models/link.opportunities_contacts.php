<?php return; /* no output */ ?>

detail
	type: link
	table_name: opportunities_contacts
	primary_key
		- opportunity_id
		- contact_id
fields
	app.date_modified
	app.deleted
	contact
		type: ref
		bean_name: Contact
	opportunity
		type: ref
		bean_name: Opportunity
	contact_role
		type: varchar
		len: 50
		default: Primary Decision Maker
indices
	idx_con_opp_con
		fields
			- contact_id
	idx_con_opp_opp
		fields
			- opportunity_id
relationships
	opportunities_contacts
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: opportunity_id
		join_key_rhs: contact_id
		lhs_bean: Opportunity
		rhs_bean: Contact

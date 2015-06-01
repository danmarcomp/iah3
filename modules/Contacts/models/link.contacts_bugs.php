<?php return; /* no output */ ?>

detail
	type: link
	table_name: contacts_bugs
	primary_key
		- contact_id
		- bug_id
fields
	app.date_modified
	app.deleted
	contact
		type: ref
		bean_name: Contact
	bug
		type: ref
		bean_name: Bug
	contact_role
		type: varchar
		len: 50
indices
	idx_con_bug_con
		fields
			- contact_id
	idx_con_bug_bug
		fields
			- bug_id
relationships
	contacts_bugs
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: contact_id
		join_key_rhs: bug_id
		lhs_bean: Contact
		rhs_bean: Bug

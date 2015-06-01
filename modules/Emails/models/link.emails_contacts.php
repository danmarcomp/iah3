<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_contacts
	primary_key: [email_id, contact_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	contact
		type: ref
		bean_name: Contact
indices
	idx_con_email_con
		fields
			- contact_id
relationships
	emails_contacts_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: contact_id
		lhs_bean: Email
		rhs_bean: Contact

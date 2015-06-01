<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_prospects
	primary_key: [email_id, prospect_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	prospect
		type: ref
		bean_name: Prospect
indices
	idx_prospect_email_prospect
		fields
			- prospect_id
relationships
	emails_prospects_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: prospect_id
		lhs_bean: Email
		rhs_bean: Prospect

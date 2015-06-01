<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_leads
	primary_key: [email_id, lead_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	lead
		type: ref
		bean_name: Lead
indices
	idx_lead_email_lead
		fields
			- lead_id
relationships
	emails_leads_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: lead_id
		lhs_bean: Email
		rhs_bean: Lead

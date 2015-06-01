<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_opportunities
	primary_key: [email_id, opportunity_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	opportunity
		type: ref
		bean_name: Opportunity
indices
	idx_opp_email_opp
		fields
			- opportunity_id
relationships
	emails_opportunities_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: opportunity_id
		lhs_bean: Email
		rhs_bean: Opportunity

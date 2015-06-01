<?php return; /* no output */ ?>

detail
	type: link
	table_name: accounts_opportunities
	primary_key
		- account_id
		- opportunity_id
fields
	app.date_modified
	app.deleted
	opportunity
		type: ref
		bean_name: Opportunity
	account
		type: ref
		bean_name: Account
indices
	idx_acc_opp_acc
		fields
			- account_id
	idx_acc_opp_opp
		fields
			- opportunity_id
	idx_a_o_opp_acc_del
		fields
			- opportunity_id
			- account_id
			- deleted
relationships
	accounts_opportunities
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: account_id
		join_key_rhs: opportunity_id
		lhs_bean: Account
		rhs_bean: Opportunity

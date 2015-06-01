<?php return; /* no output */ ?>

detail
	type: link
	table_name: prospect_list_campaigns
	primary_key
		- prospect_list_id
		- campaign_id
fields
	app.date_modified
	app.deleted
	prospect_list
		type: ref
		bean_name: Prospect
	campaign
		type: ref
		bean_name: Campaign
indices
	idx_pro_id
		fields
			- prospect_list_id
	idx_cam_id
		fields
			- campaign_id
relationships
	prospect_list_campaigns
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: prospect_list_id
		join_key_rhs: campaign_id
		lhs_bean: ProspectList
		rhs_bean: Campaign

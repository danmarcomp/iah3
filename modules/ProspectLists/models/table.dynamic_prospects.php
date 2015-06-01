<?php return; /* no output */ ?>

detail
	type: table
	table_name: dynamic_prospects
	primary_key
        - prospect_list_id
        - related_type
fields
	app.date_modified
    app.deleted
    prospect_list
        type: ref
        bean_name: Prospect
    related_type
        type: module_name
    filters
        type: text
indices
	idx_plp_pro_id
		fields
			- prospect_list_id
relationships
    prospect_lists
        key: id
        target_bean: ProspectList
        target_key: prospect_list_id
        relationship_type: one-to-many

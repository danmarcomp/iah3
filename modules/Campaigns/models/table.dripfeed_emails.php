<?php return; /* no output */ ?>

detail
	type: table
	table_name: dripfeed_emails
	primary_key
        - campaign_id
        - marketing_id
        - related_id
fields
	app.date_modified
    app.deleted
    campaign_id
        type: id
    marketing_id
        type: id
    related_id
        type: id
indices
    idx_cam_id
        fields
            - campaign_id
	idx_mark_id
		fields
			- marketing_id
    idx_rel_id
        fields
            - related_id

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/CampaignTrackers/CampaignTracker.php
	unified_search: false
	duplicate_merge: false
	comment: Maintains the Tracker URLs used in campaign emails
	table_name: campaign_trkrs
	primary_key: id
	display_name: tracker_name
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.created_by_user
	app.deleted
	tracker_name
		vname: LBL_TRACKER_NAME
		type: name
		len: 30
		comment: The name of the campaign tracker
		required: true
	tracker_url
		vname: LBL_TRACKER_URL
		type: url
		comment:
			The URL that represents the landing page when the tracker
			URL in the campaign email is clicked
		massupdate: true
	tracker_key
		vname: LBL_TRACKER_KEY
		type: int
		len: 11
		auto_increment: true
		required: true
		comment: Internal key to uniquely identifier the tracker URL
	campaign
		vname: LBL_CAMPAIGN
		type: ref
		reportable: false
		comment: The ID of the campaign
		bean_name: Campaign
		massupdate: true
	is_optout
		vname: LBL_OPTOUT
		type: bool
		required: true
		default: 0
		reportable: false
		comment: Indicator whether tracker URL represents an opt-out link
		massupdate: true
	content
		vname: LBL_CONTENT
		type: html
		comment: Page content displayed to user if no redirect URL is specified
links
	campaign
		relationship: campaign_campaigntrakers
		vname: LBL_CAMPAIGN
indices
	campaign_tracker_key_idx
		fields
			- tracker_key
relationships
	campaign_campaigntrakers
		key: campaign_id
		target_bean: Campaign
		target_key: id
		relationship_type: one-to-many

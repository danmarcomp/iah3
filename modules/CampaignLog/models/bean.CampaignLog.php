<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/CampaignLog/CampaignLog.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	comment: Tracks items of interest that occurred after you send an email campaign
	table_name: campaign_log
	primary_key: id
fields
	app.id
	app.deleted
	app.date_modified
	campaign
		vname: LBL_CAMPAIGN
		type: ref
		comment: Campaign identifier
		reportable: false
		bean_name: Campaign
		massupdate: true
	campaign_name
		vname: LBL_CAMPAIGN
		type: name
		source
			field: campaign.name
		unified_search: true
	marketing
		vname: LBL_MARKETING_ID
		type: ref
		default: ""
		reportable: false
		bean_name: EmailMarketing
		massupdate: true
	target_tracker_key
		vname: LBL_TARGET_TRACKER_KEY
		type: id
		comment: Identifier of Tracker URL
	target
		vname: LBL_TARGET
		type: ref
		comment: Identifier of target record
		dynamic_module: target_type
		massupdate: true
	target_type
		vname: LBL_TARGET_TYPE
		type: varchar
		len: 25
		comment: Descriptor of the target record type (e.g., Contact, Lead)
	activity_type
		vname: LBL_ACTIVITY_TYPE
		type: enum
		options: campainglog_activity_type_dom
		len: 25
		comment:
			The activity that occurred (e.g., Viewed Message, Bounced,
			Opted out)
		massupdate: true
	activity_date
		vname: LBL_ACTIVITY_DATE
		type: datetime
		comment: The date the activity occurred
		massupdate: true
	related
		vname: LBL_RELATED_TO
		type: ref
		dynamic_module: related_type
		massupdate: true
	related_type
		vname: LBL_RELATED_TYPE
		type: varchar
		len: 25
	archived
		vname: LBL_ARCHIVED
		type: bool
		reportable: false
		default: 0
		comment: Indicates if item has been archived
		massupdate: true
	hits
		vname: LBL_HITS
		type: int
		default: 0
		reportable: false
		comment:
			Number of times the item has been invoked (e.g., multiple
			click-thrus)
	list
		vname: LBL_LIST_ID
		type: ref
		reportable: false
		comment: The target list from which item originated
		bean_name: ""
		massupdate: true
	more_information
		vname: LBL_MORE_INFO
		type: email
		len: 100
		parent_type_field: target_type
		parent_id_field: target_id
links
	campaign
		relationship: campaign_campaignlog
		vname: LBL_CAMPAIGN
indices
	idx_camp_tracker
		fields
			- target_tracker_key
	idx_camp_campaign_id
		fields
			- campaign_id
	idx_camp_more_info
		fields
			- more_information

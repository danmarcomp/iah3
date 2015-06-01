<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Campaigns/Campaign.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	comment:
		Campaigns are a series of operations undertaken to accomplish a purpose,
		usually acquiring leads
	table_name: campaigns
	primary_key: id
	default_order_by: name
	reportable: true
hooks
	notify
		--
			class_function: send_notification
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	app.deleted
	app.currency
		exchange_rate: ""
	tracker_key
		vname: LBL_TRACKER_KEY
		type: int
		required: true
		len: 11
		auto_increment: true
		comment:
			The internal ID of the tracker used in a campaign; no longer
			used as of 4.2 (see campaign_trkrs)
	tracker_count
		vname: LBL_TRACKER_COUNT
		type: int
		len: 11
		default: 0
		comment:
			The number of accesses made to the tracker URL; no longer
			used as of 4.2 (see campaign_trkrs)
	name
		vname: LBL_CAMPAIGN_NAME
		type: name
		len: 60
		comment: The name of the campaign
		unified_search: true
		vname_list: LBL_LIST_CAMPAIGN_NAME
		required: true
	start_date
		vname: LBL_CAMPAIGN_START_DATE
		type: date
		audited: true
		comment: Starting date of the campaign
		massupdate: true
	end_date
		vname: LBL_CAMPAIGN_END_DATE
		type: date
		audited: true
		comment: Ending date of the campaign
		vname_list: LBL_LIST_END_DATE
		massupdate: true
	status
		vname: LBL_CAMPAIGN_STATUS
		type: enum
		options: campaign_status_dom
		len: 25
		audited: true
		comment: Status of the campaign
		vname_list: LBL_LIST_STATUS
		required: true
		default: Planning
		massupdate: true
	impressions
		vname: LBL_CAMPAIGN_IMPRESSIONS
		type: int
		default: 0
		reportable: false
		comment: Expected Click throughs manually entered by Campaign Manager
	budget
		vname: LBL_CAMPAIGN_BUDGET
		type: float
		comment: Budgeted amount for the campaign
		dbType: double
	expected_cost
		vname: LBL_CAMPAIGN_EXPECTED_COST
		type: float
		comment: Expected cost of the campaign
		dbType: double
	actual_cost
		vname: LBL_CAMPAIGN_ACTUAL_COST
		type: float
		comment: Actual cost of the campaign
		dbType: double
	expected_revenue
		vname: LBL_CAMPAIGN_EXPECTED_REVENUE
		type: float
		comment: Expected revenue stemming from the campaign
		dbType: double
	campaign_type
		vname: LBL_CAMPAIGN_TYPE
		type: enum
		options: campaign_type_dom
		len: 25
		audited: true
		comment: The type of campaign
		vname_list: LBL_LIST_TYPE
		massupdate: true
		required: true
	objective
		vname: LBL_CAMPAIGN_OBJECTIVE
		type: text
		comment: The objective of the campaign
	content
		vname: LBL_CAMPAIGN_CONTENT
		type: text
		comment: The campaign description
	frequency
		vname: LBL_CAMPAIGN_FREQUENCY
		type: enum
		len: 25
		comment: Frequency of the campaign
		options: newsletter_frequency_dom
		massupdate: true
	opportunities_won
		vname: LBL_CAMPAIGN_OPPORTUNITIES_WON
		type: formula
		source: non-db
		formula
			type: function
			class: Campaign
			function: get_opportunities_won
			fields: [id]
	cost_per_impression
		vname: LBL_CAMPAIGN_COST_PER_IMPRESSION
		type: formula
		source: non-db
		formula
			type: function
			class: Campaign
			function: get_cost_per_impression
			fields: [impressions, actual_cost]
	cost_per_click
		vname: LBL_CAMPAIGN_COST_PER_CLICK_THROUGH
		type: formula
		source: non-db
		formula
			type: function
			class: Campaign
			function: get_cost_per_click
			fields: [id, actual_cost]
links
	prospectlists
		relationship: prospect_list_campaigns
		vname: LBL_PROSPECT_LISTS
	emailmarketing
		relationship: campaign_email_marketing
		vname: LBL_EMAIL_MARKETING
		removeable: false
	dripfeedemails
		relationship: campaign_email_marketing
		layout: DripFeed
		vname: LBL_EMAIL_MARKETING
	queueitems
		relationship: campaign_emailman
		reportable: false
	log_entries
		relationship: campaign_campaignlog
		vname: LBL_CAMPAIGN_LOGS
		reportable: false
	sent_emails
		no_create: true
		layout: WithRelated
		relationship: campaign_campaignlog_sent
		vname: LBL_LOG_ENTRIES_TARGETED_TITLE
		module: CampaignLog
		bean_name: CampaignLog
		reportable: false
	errored_emails
		no_create: true
		layout: WithRelated
		relationship: campaign_campaignlog_error
		vname: LBL_LOG_ENTRIES_SEND_ERROR_TITLE
		module: CampaignLog
		bean_name: CampaignLog
		reportable: false
	invalid_emails
		no_create: true
		layout: WithRelated
		relationship: campaign_campaignlog_invalid
		vname: LBL_LOG_ENTRIES_INVALID_EMAIL_TITLE
		module: CampaignLog
		bean_name: CampaignLog
		reportable: false
	link_clicked_emails
		no_create: true
		layout: WithRelated
		relationship: campaign_campaignlog_link
		vname: LBL_LOG_ENTRIES_LINK_TITLE
		module: CampaignLog
		bean_name: CampaignLog
		reportable: false
	viewd_emails
		no_create: true
		layout: WithRelated
		relationship: campaign_campaignlog_viewed
		vname: LBL_LOG_ENTRIES_VIEWED_TITLE
		module: CampaignLog
		bean_name: CampaignLog
		reportable: false
	removed_emails
		no_create: true
		layout: WithRelated
		relationship: campaign_campaignlog_removed
		vname: LBL_LOG_ENTRIES_REMOVED_TITLE
		module: CampaignLog
		bean_name: CampaignLog
		reportable: false
	created_leads
		no_create: true
		relationship: campaign_campaignlog_lead
		vname: LBL_LOG_ENTRIES_LEAD_TITLE
		module: CampaignLog
		bean_name: CampaignLog
		reportable: false
	created_contacts
		no_create: true
		relationship: campaign_campaignlog_contact
		vname: LBL_LOG_ENTRIES_CONTACT_TITLE
		module: CampaignLog
		bean_name: CampaignLog
		reportable: false
	tracked_urls
		relationship: campaign_campaigntrakers
		vname: LBL_TRACKED_URLS
		reportable: false
	leads
		relationship: campaign_leads
	opportunities
		relationship: campaign_opportunities
	app.securitygroups
		relationship: securitygroups_campaigns
indices
	camp_auto_tracker_key
		fields
			- tracker_key
	idx_campaign_name
		fields
			- name
relationships
	campaign_accounts
		key: id
		target_bean: Account
		target_key: campaign_id
		relationship_type: one-to-many
	campaign_contacts
		key: id
		target_bean: Contact
		target_key: campaign_id
		relationship_type: one-to-many
	campaign_leads
		key: id
		target_bean: Lead
		target_key: campaign_id
		relationship_type: one-to-many
	campaign_prospects
		key: id
		target_bean: Prospect
		target_key: campaign_id
		relationship_type: one-to-many
	campaign_opportunities
		key: id
		target_bean: Opportunity
		target_key: campaign_id
		relationship_type: one-to-many
	campaign_email_marketing
		key: id
		target_bean: EmailMarketing
		target_key: campaign_id
		relationship_type: one-to-many
	campaign_emailman
		key: id
		target_bean: EmailMan
		target_key: campaign_id
		relationship_type: one-to-many
	campaign_campaignlog
		key: id
		target_bean: CampaignLog
		target_key: campaign_id
		relationship_type: one-to-many
	campaign_campaignlog_sent
		managed: true
		key: id
		target_bean: CampaignLog
		target_key: campaign_id
		relationship_type: one-to-many
		role_column: activity_type
		role_value: targeted
	campaign_campaignlog_error
		managed: true
		key: id
		target_bean: CampaignLog
		target_key: campaign_id
		relationship_type: one-to-many
		role_column: activity_type
		role_value: send error
	campaign_campaignlog_invalid
		managed: true
		key: id
		target_bean: CampaignLog
		target_key: campaign_id
		relationship_type: one-to-many
		role_column: activity_type
		role_value: invalid email
	campaign_campaignlog_link
		managed: true
		key: id
		target_bean: CampaignLog
		target_key: campaign_id
		relationship_type: one-to-many
		role_column: activity_type
		role_value: link
	campaign_campaignlog_viewed
		managed: true
		key: id
		target_bean: CampaignLog
		target_key: campaign_id
		relationship_type: one-to-many
		role_column: activity_type
		role_value: viewed
	campaign_campaignlog_removed
		managed: true
		key: id
		target_bean: CampaignLog
		target_key: campaign_id
		relationship_type: one-to-many
		role_column: activity_type
		role_value: removed
	campaign_campaignlog_lead
		managed: true
		key: id
		target_bean: CampaignLog
		target_key: campaign_id
		relationship_type: one-to-many
		role_column: activity_type
		role_value: lead
	campaign_campaignlog_contact
		managed: true
		key: id
		target_bean: CampaignLog
		target_key: campaign_id
		relationship_type: one-to-many
		role_column: activity_type
		role_value: contact

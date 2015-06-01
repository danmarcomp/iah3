<?php return; /* no output */ ?>

list
	default_order_by: name
    layouts
        Newsletters
            vname: LBL_NEWSLETTERS
            override_filters
                campaign_type: NewsLetter
        Dripfeed
            vname: LBL_DRIPFEED
            override_filters
				campaign_type: Dripfeed
view
	layouts
		Standard
			vname: LBL_LAYOUT_STANDARD
		Track
			vname: LBL_LAYOUT_STATUS
		Roi
			vname: LBL_LAYOUT_ROI

filters
	name
	campaign_type
	status
	current_user_only
		my_items: true
		vname: LBL_CURRENT_USER_FILTER
		field: assigned_user_id
	assigned_user
widgets
	CampaignWidget
		type: section
		path: modules/Campaigns/widgets/CampaignWidget.php
    WebToLeadWidget
        type: section
        path: modules/Campaigns/widgets/WebToLeadWidget.php
    WizardWidget
        type: section
        path: modules/Campaigns/widgets/WizardWidget.php
    EmailSetupWidget
        type: section
        path: modules/Campaigns/widgets/EmailSetupWidget.php

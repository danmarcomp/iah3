<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    scripts
        --
            file: "modules/Opportunities/opportunity.js"
    form_hooks
        oninit: "initOppForm({FORM})"
	sections
		--
			elements
                - name
                - currency
                - account
                - amount
                - partner
                --
                    name: campaign
                    widget: CampaignNameWidget
                - opportunity_type
                - date_closed
                - lead_source
                - next_step
                - forecast_category
                --
                    name: sales_stage
                    onchange: setStage(this.form, this.getValue());
                - assigned_user
                - probability
                --
                	name: description
                	colspan: 2

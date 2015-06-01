<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    scripts
        --
            file: "modules/Opportunities/opportunity.js"
	sections
		--
			elements
                - name
                - account
                - currency
                - amount
                - forecast_category
                --
                    name: sales_stage
                    onchange: setStage(this.form, this.getValue());
                - probability
                - date_closed

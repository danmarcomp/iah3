<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    form_hidden_fields
        return_module: Campaigns
        return_action: index
	sections
		--
			id: main
			elements
				- campaign
				-
                - tracker_name
                - is_optout
				--
					name: tracker_url
					colspan: 2
				--
					name: content
					colspan: 2


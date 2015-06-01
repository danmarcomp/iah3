<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
                - contract_no
                - 
                - account
                - 
                - total_purchase
                - date_entered
                - is_active
                - date_modified
                --
                    name: description
                    colspan: 2
    subpanels
        - subcontracts
        - securitygroups

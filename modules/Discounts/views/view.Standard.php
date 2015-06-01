<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
                - name
                - discount_type
                --
                	name: rate
                	hidden: bean.discount_type!=percentage
                --
                	name: fixed_amount
                	hidden: bean.discount_type!=fixed
                - status
                - date_entered
                - applies_to_selected
                - date_modified
    subpanels
        --
        	name: products
        	hidden: !bean.applies_to_selected
		- securitygroups
<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	form_buttons
	sections
		--
            id: main
            elements
                --
                	name: name
                	colspan: 2
                	width: 60
                - parent
                - status
                - date_start
                - priority
                - date_due
                - percent_complete
                - currency
                - depends_on
                - task_number
                - dependency_type
                - include_weekends
                - order_number
                - portal_flag
                - milestone_flag
                -
                - assigned_user
                --
                    name: description
                    colspan: 2

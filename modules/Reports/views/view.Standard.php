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
                - run_method
				--
					id: schedule_info
					section: true
					toggle_display
						name: run_method
						value: scheduled
					elements
						- next_run
						- run_interval
				- assigned_user
                - date_entered
                -
                - date_modified
                --
                    name: description
                    colspan: 2
    subpanels
        - securitygroups

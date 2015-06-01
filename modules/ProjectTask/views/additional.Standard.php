<?php return; /* no output */ ?>

detail
    type: view
layout
	sections
		--
			id: mini_details
			columns: 1
            label_position: top
			elements
				- parent
                - name
                - assigned_user
                - priority
                - percent_complete
                - utilization
                - estimated_effort
                - actual_effort
                - task_number
                - date_start
                - date_due
                - description

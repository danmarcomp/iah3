<?php return; /* no output */ ?>

detail
    type: view
layout
	sections
		--
			id: mini_details
			columns: 1
            label_position: top
            hidden: bean.is_private
			elements
				- name
                - date_start
                - date_due
                - priority
                - status
                - effort_estim
                - effort_actual
                - description

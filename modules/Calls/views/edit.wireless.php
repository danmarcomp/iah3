<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	sections
		--
			elements
				- name
				- date_start
				- direction
				- status
				--
					field: duration
					label: LBL_DURATION_MINUTES
				- description
				- assigned_user
	subpanels
		- contacts
		- leads
		- users

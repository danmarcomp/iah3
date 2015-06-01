<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	sections
		--
			elements
				--
					name: case_number
					wireless_detail_only: true
				- name
				- account
				- priority
				- status
				- description
				- resolution
				- assigned_user
	subpanels
		- calls
		- meetings
		- tasks
		- contacts
		- accounts

<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	sections
		--
			elements
				--
					field: bug_number
					wireless_detail_only: true
				- priority
				- status
				- name
				#- release
				#- product
				- assigned_user
	subpanels
		- calls
		- meetings
		- tasks
		- contacts
		- accounts
		- cases

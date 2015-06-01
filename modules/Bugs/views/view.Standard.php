<?php return; /* no output */ ?>
	
detail
	type: view
	title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
				- bug_number
				-
				- priority
				- account
				- type
				- contact
				- status
				- is_private
				- resolution
				- product
				- source
				- product_category
				- found_in
				- fixed_in
				- planned_for
				- date_entered
				- assigned_user
				- date_modified
		--
			id: text
			elements
				--
					name: name
					colspan: 2
				--
					name: description
					colspan: 2
				--
					name: work_log
					colspan: 2
	subpanels
		- accounts
		- contacts
		- activities
		- history
		- cases
		- threads
		- securitygroups

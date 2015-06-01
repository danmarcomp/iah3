<?php return; /* no output */ ?>

detail
	primary_bean: Report
	tab_visibility: normal
	default_group: LBL_TABGROUP_REPORTS
	module_designer: false
web_actions
	InstallTemplates
	RunReport
acl
	fixed
		view: team
		edit: owner
	editable: false

<?php return; /* no output */ ?>

detail
    type: edit
    title: LBL_ACL_TITLE
    icon: Administration
layout
	form_buttons
	sections
		--
			id: defaults
			vname: LBL_ACL_SETTINGS_TITLE
			columns: 2
			show_descriptions: true
			widths: 5%
			label_widths: 20%
			desc_widths: 25%
			elements
				- aclconfig_additive
				#- aclconfig_user_role_precedence
		--
			id: inherit
			vname: LBL_ACL_TEAM_INHERIT_TITLE
			columns: 2
			show_descriptions: true
			widths: 5%
			label_widths: 20%
			desc_widths: 25%
			elements
				- aclconfig_inherit_creator
				- aclconfig_inherit_assigned

<?php return; /* no output */ ?>

list
	default_order_by: last_name
    no_mass_delete: true
view
	layouts
		Standard
			vname: LBL_LAYOUT_STANDARD
			edit_layout: Standard
		Display
			vname: LBL_LAYOUT_DISPLAY
			edit_layout: Display
			acl: owner
		Email
			vname: LBL_LAYOUT_EMAIL
			edit_layout: Email
			acl: owner
		Integration
			vname: LBL_LAYOUT_INTEGRATION
			edit_layout: Integration
			acl: owner
		ACL
			vname: LBL_LAYOUT_ACL
			edit_layout: ACL
			acl: admin
	scripts
		--
			file: "modules/Users/User.js"
edit
	scripts
		--
			file: "modules/Users/User.js"
hooks
    edit
        --
            class_function: rewrite_onsubmit
basic_filters
	- status
	- hide_portal_only
filters
	hide_portal_only
		default_value: true
		type: flag
		vname: LBL_HIDE_PORTAL_ONLY
		operator: not
		field: portal_only
		hide_fields: true
	any_email
        vname: LBL_ANY_EMAIL
        type: email
		fields
			- email1
			- email2
	any_phone
        vname: LBL_ANY_PHONE
        type: phone
		fields
			- phone_home
			- phone_mobile
			- phone_work
			- phone_other
			- phone_fax
	status
		type: section
		field: status
		vname: LBL_STATUS
		options_function: [User, get_status_options]
		default_value: NotInactive
		filter_clause_source: [User, get_search_status_where]
		required: false
fields
    home_address
        vname: LBL_HOME_ADDRESS
        type: address
        source
            type: address
            prefix: ""
    estim_hours
        vname: LBL_LIST_ESTIMATED_HOURS
        vname_module: Users
        type: varchar
        widget: ProjectRateInput
        reportable: false
    hourly_cost
        vname: LBL_LIST_HOURLY_COST
        vname_module: Users
        type: varchar
        widget: ProjectRateInput
        reportable: false
    user_skills
    	vname: LBL_USER_SKILLS
    	widget: SkillsWidget
    	reportable: false
widgets
	UserInfoWidget
		type: section
		path: modules/Users/widgets/UserInfoWidget.php
    ProjectRateInput
        type: column
        path: modules/Users/widgets/ProjectRateInput.php

<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    form_buttons
        change_password
            vname: LBL_CHANGE_PASSWORD_BUTTON_LABEL
            accesskey: LBL_CHANGE_PASSWORD_BUTTON_KEY
            type: button
            onclick: "open_password_form();"
            acl: edit
	sections
		--
			id: main
			elements
				--
					name: full_name
					widget: SalutationNameWidget
                - user_name
                - status
                -
        --
            id: user_info
            vname: LBL_USER_INFORMATION
            elements
                - employee_status
                - 
                - title
                - phone_work
                - department
                - phone_mobile
                - reports_to
                - phone_other
                - location
                - phone_fax
                - phone_home
                - sms_address
                - messenger_type
                -
                - messenger_id
                -
                --
                    name: description
                    colspan: 2
        --
            id: user_settings
            vname: LBL_USER_SETTINGS
            widget: UserInfoWidget
        --
            id: required_skills
            vname: LBL_USER_SKILLS
            widget: SkillsWidget

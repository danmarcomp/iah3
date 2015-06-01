<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE	
layout
	form_buttons
        change_password
            vname: LBL_CHANGE_PASSWORD_BUTTON_LABEL
            accesskey: LBL_CHANGE_PASSWORD_BUTTON_KEY
            type: button
            onclick: "open_password_form();"
    form_hooks
        oninit: "setPasswordValidation({FORM})"
	sections
		--
			id: main
			elements
                --
                	name: first_name
                	widget: SalutationNameWidget
                - user_name
                - last_name
                --
                	name: status
                	editable: mode.admin
        --
            id: user_password
            widget: UserInfoWidget
            hidden: ! mode.new_record
        --
            id: user_info
            vname: LBL_USER_INFORMATION
            elements
                --
                	name: employee_status
					editable: mode.admin
                -
                - title
                - phone_work
                - department
                - phone_mobile
                - reports_to
                - phone_other
                - location
                - phone_fax
                - sms_address
                - phone_home
                - messenger_type
                - messenger_id
                -
                --
                    name: description
                    colspan: 2
        --
            id: user_settings
            vname: LBL_USER_SETTINGS
            widget: UserInfoWidget
        - user_skills
        --
            id: user_photo
            vname: LBL_PHOTO_SUBFORM
            elements
                - photo_filename

<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	form_buttons
	sections
		--
			id: main
			elements
                - first_name
                - user
                - last_name
                - employee_num
                - sex
                - start_date
                - dob
                - end_date
                - education
                - emergency_name
                - sin_ssn
                - emergency_phone
                - salary_currency
                - benefits_start_date
                - salary_amount
                - benefits_end_date
                - salary_period
                -
                - std_hourly_rate
                - last_review
        --
            id: address_info
            vname: LBL_ADDRESS_INFORMATION
            elements
                - home_address
                -
        --
            id: public_info
            vname: LBL_PUBLIC_INFORMATION
            elements
                - title
                - phone_work
                - department
                - phone_mobile
                - location
                - phone_other
                - email1
                - phone_fax
                - email2
                - sms_address
                --
                    name: description
                    colspan: 2
        --
            id: user_photo
            vname: LBL_PHOTO_SUBFORM
            elements
                - photo_filename

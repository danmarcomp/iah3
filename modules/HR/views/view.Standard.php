<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
                - full_name
                - user
                - employee_num
                - start_date
                - sex
                - end_date
                - education
                - emergency_name
                - dob
                - emergency_phone
                - sin_ssn
                -
                - salary
                - benefits_start_date
                - std_hourly_rate
                - benefits_end_date
                -
                - last_review
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
            id: employee_leave
            vname: LBL_EMPLOYEE_LEAVE
            widget: EmployeeWidget
    subpanels
        - dependants
        - documents
        - expense_reports

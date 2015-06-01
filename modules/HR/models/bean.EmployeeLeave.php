<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/HR/EmployeeLeave.php
	unified_search: false
	duplicate_merge: false
	table_name: employee_leave
	primary_key
		- employee_id
		- fiscal_year
fields
	app.date_modified
	app.modified_user
	app.deleted
	employee
		vname: LBL_EMPLOYEE_NAME
		type: ref
		required: true
		reportable: false
		bean_name: Employee
        editable: false
	fiscal_year
		vname: LBL_FISCAL_YEAR
		type: int
		len: 5
		required: true
		reportable: true
		disable_num_format: true
        editable: false
	vacation_allowed
		vname: LBL_ANNUAL_VACATION_DAYS
		type: float
		dbType: double
		reportable: true
	sick_days_allowed
		vname: LBL_ANNUAL_SICK_DAYS
		type: float
		dbType: double
		reportable: true
	vacation_carried_over
		vname: LBL_VACATION_CARRIED_OVER
		type: float
		dbType: double
		reportable: true
	description
		vname: LBL_NOTES
		type: text
		reportable: true

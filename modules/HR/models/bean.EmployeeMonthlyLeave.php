<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/HR/EmployeeMonthlyLeave.php
	unified_search: false
	duplicate_merge: false
	table_name: employee_leave_monthly
	primary_key: null
fields
	app.date_modified
	app.modified_user
	app.deleted
	employee
		vname: LBL_EMPLOYEE_ID
		type: ref
		required: true
		reportable: false
		bean_name: Employee
	period
		vname: LBL_MONTH
		type: date
		required: true
		format: yearmonth
	vacation_taken
		vname: LBL_VACATION_DAYS
		type: float
		dbType: double
	sickleave_taken
		vname: LBL_SICK_DAYS
		type: float
		dbType: double
indices
	empl_leave_mon_pk
		fields
			- employee_id
			- period

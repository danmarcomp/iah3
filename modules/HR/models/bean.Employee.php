<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/HR/Employee.php
	unified_search: false
	duplicate_merge: false
	table_name: employees
	primary_key: id
	default_order_by: full_name
	display_name
		field: full_name
	reportable: true
hooks
	after_save
		--
			class_function: update_user
fields
	app.id
	app.date_modified
	app.modified_user
	app.deleted
	user
		vname: LBL_USER_NAME
		type: ref
		required: true
		reportable: false
		bean_name: User
		editable: false
	full_name
		vname: LBL_EMPLOYEE_NAME
		type: name
		source
			field: user.full_name
		unified_search: true
	first_name
		vname: LBL_FIRST_NAME
		type: name
		len: 30
		source
			field: user.first_name
	last_name
		vname: LBL_LAST_NAME
		type: name
		len: 30
		source
			field: user.last_name
	title
		vname: LBL_TITLE
		type: varchar
		len: 50
		source
			field: user.title
	department
		vname: LBL_DEPARTMENT
		type: enum
		options: departments_dom
		source
			field: user.department
		massupdate: true
	location
		vname: LBL_LOCATION
		type: varchar
		len: 30
		source
			field: user.location
	email1
		vname: LBL_EMAIL
		type: email
		source
			field: user.email1
	email2
		vname: LBL_OTHER_EMAIL
		type: email
		source
			field: user.email2
	sms_address
		vname: LBL_SMS
		type: varchar
		len: 100
		source
			field: user.sms_address
	phone_work
		vname: LBL_OFFICE_PHONE
		type: phone
		source
			field: user.phone_work
	phone_mobile
		vname: LBL_MOBILE_PHONE
		type: phone
		source
			field: user.phone_mobile
	phone_other
		vname: LBL_OTHER
		type: phone
		source
			field: user.phone_other
	phone_fax
		vname: LBL_FAX
		type: phone
		source
			field: user.phone_fax
	description
		vname: LBL_NOTES
		type: text
		source
			field: user.description
	photo_filename
		vname: LBL_PHOTO
		type: image_ref
		upload_dir: {FILES}/images/directory/
		source
			field: user.photo_filename
		reportable: false
	address_street
		vname: LBL_ADDRESS_STREET
		type: varchar
		len: 150
		source
			field: user.address_street
	address_city
		vname: LBL_ADDRESS_CITY
		type: varchar
		len: 100
		source
			field: user.address_city
	address_state
		vname: LBL_ADDRESS_STATE
		type: varchar
		len: 100
		source
			field: user.address_state
	address_country
		vname: LBL_ADDRESS_COUNTRY
		type: varchar
		len: 25
		source
			field: user.address_country
	address_postalcode
		vname: LBL_ADDRESS_POSTALCODE
		type: varchar
		len: 9
		source
			field: user.address_postalcode
	employee_num
		vname: LBL_EMPLOYEE_NUMBER
		type: item_number
		len: 30
		vname_list: LBL_LIST_EMPLOYEE_NUMBER
		unified_search: true
	sex
		vname: LBL_SEX
		type: enum
		options: sex_dom
		dbType: char
		len: 1
		massupdate: false
	sin_ssn
		vname: LBL_SIN_SSN
		type: char
		len: 20
	emergency_name
		vname: LBL_EMERGENCY_CONTACT
		type: varchar
		len: 60
	emergency_phone
		vname: LBL_EMERGENCY_PHONE
		type: varchar
		len: 20
	start_date
		vname: LBL_START_DATE
		type: date
		massupdate: false
		disable_relative_format: true
	end_date
		vname: LBL_END_DATE
		type: date
		massupdate: true
	benefits_start_date
		vname: LBL_BENEFITS_START
		type: date
		massupdate: true
	benefits_end_date
		vname: LBL_BENEFITS_END
		type: date
		massupdate: true
	dob
		vname: LBL_DATE_OF_BIRTH
		type: date
		massupdate: false
	salary
		vname: LBL_SALARY
		type: currency
		source
			type: concat
			fields
				- salary_currency
				- salary_amount
				- salary_period
		importable: false
	salary_currency
		vname: LBL_SALARY_CURRENCY
		type: currency_ref
		exchange_rate: salary_exchange_rate
	salary_exchange_rate
		vname: LBL_SALARY_EXCHANGE_RATE
		type: exchange_rate
	salary_amount
		vname: LBL_SALARY
		type: currency
		currency: salary_currency
		reportable: false
		base_field: salary_amount_usdollar
	salary_period
		vname: LBL_SALARY_PERIOD
		type: enum
		options: salary_period_dom
		len: 20
		reportable: false
		massupdate: false
	last_salary_increase
		vname: LBL_LAST_SALARY_INCREASE
		type: date
		massupdate: false
		reportable: false
	last_review
		vname: LBL_LAST_REVIEW
		type: date
		massupdate: true
	education
		vname: LBL_EDUCATION
		type: enum
		options: education_dom
		len: 30
		massupdate: true
	std_hourly_rate
		vname: LBL_STD_HOURLY_RATE
		type: currency
		currency: salary_currency
		base_field: std_hourly_rate_usdollar
	service
		vname: LBL_SERVICE
		vname_list: LBL_LIST_SERVICE
		type: age
		source
			type: age
			field: start_date
		format: months
links
	user_link
		relationship: employee_user
		module: Users
		link_type: one
		bean_name: User
		vname: LBL_RELATED_USER
	documents
		relationship: documents_employees
		module: Documents
		bean_name: Document
		vname: LBL_DOCUMENTS
		add_filters
			--
				param: section
				value: hr
			--
				param: show_hr
				value: true
	expense_reports
		relationship: expense_employee
		module: ExpenseReports
		bean_name: ExpenseReport
		vname: LBL_EXPENSE_REPORTS_SUBPANEL_TITLE
	dependants
		layout: Dependants
		relationship: employee_to_dependants
		module: HR
		bean_name: EmployeeDependant
		vname: LBL_DEPENDANTS
		detail_link_url: index.php?module={MODULE}&action=DependantDetail&record={ID}
	leave
		relationship: employee_to_leave
		module: HR
		bean_name: EmployeeLeave
		vname: LBL_EMPLOYEE_ANNUAL_LEAVE
	leave_monthly
		relationship: employee_to_leave_monthly
		module: HR
		bean_name: EmployeeMonthlyLeave
		vname: LBL_EMPLOYEE_MONTHLY_LEAVE
fields_compat
	full_name
		vname: LBL_FULL_NAME
		type: relate
		table: users
		sort_on: last_name
		source: non-db
		len: 510
		db_concat_fields
			- first_name
			- last_name
		rname: last_name
		id_name: user_id
		link: user_link
		massupdate: false
		join_name: users
indices
	employees_uid
		type: unique
		fields
			- user_id
relationships
	employee_user
		key: user_id
		target_bean: User
		target_key: id
		relationship_type: one-to-many
	employee_to_dependants
		key: id
		target_bean: EmployeeDependant
		target_key: employee_id
		relationship_type: one-to-many
	employee_to_leave
		key: id
		target_bean: Employee
		target_key: employee_id
		relationship_type: one-to-many
	employee_to_leave_monthly
		key: id
		target_bean: Employee
		target_key: employee_id
		relationship_type: one-to-many

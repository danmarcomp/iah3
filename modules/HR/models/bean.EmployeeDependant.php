<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/HR/EmployeeDependant.php
	unified_search: false
	duplicate_merge: false
	table_name: employee_dependants
	primary_key: id
hooks
    after_remove_link
        --
            class_function: remove_relation
fields
	app.id
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
    name
        vname: LBL_NAME
        type: name
        source
            type: person_name
            fields: [first_name, last_name, salutation]
        importable: false
	first_name
		vname: LBL_FIRST_NAME
		type: varchar
		len: 30
		required: true
		reportable: true
	last_name
		vname: LBL_LAST_NAME
		type: varchar
		len: 30
		required: true
		reportable: true
	dob
		vname: LBL_DATE_OF_BIRTH
		type: date
		reportable: true
	relationship
		vname: LBL_RELATIONSHIP
		type: enum
		options: dep_relationship_dom
		len: 30
		reportable: true

<?php return; /* no output */ ?>

detail
	type: template
	bean_file: modules/Audit/Audit.php
	unified_search: false
	duplicate_merge: false
	comment:
		Store and show change log
	table_name: audit
	primary_key: id
	default_order_by: field_name
fields
	app.id
	app.created_by_user
    date_created
        vname: LBL_LIST_DATE
        type: datetime
        required: true
        comment: Change date
    parent
        type: ref
        required: true
        comment: Parent record
	field_name
		vname: LBL_FIELD_NAME
		type: char
		len: 100
		required: true
		comment: Name of table field
    data_type
		vname: LBL_FIELD_TYPE
        type: char
        len: 100
        required: true
        comment: Type of changed data
    before_value_string
        vname: LBL_OLD_VALUE
        type: varchar
        len: 255
        comment: Field value before change
    after_value_string
        vname: LBL_NEW_VALUE        
        type: varchar
        len: 255
        comment: Field value after change
    before_value_text
        type: text
        comment: Text before change
    after_value_text
        type: text
        comment: Text after change

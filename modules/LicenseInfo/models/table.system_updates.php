<?php return; /* no output */ ?>

detail
	type: table
	table_name: system_updates
	primary_key: id
fields
    app.id
    package
        vname: LBL_PACKAGE
        type: varchar
        len: 30
        required: true
    product
        vname: LBL_PRODUCT
        type: varchar
        len: 100
        required: true
    date_posted
        vname:  LBL_DATE_POSTED
        type: date
        required: true
    version
        vname: LBL_PACKAGE_VERSION
        type: varchar
        len: 10
        required: true
    type
        vname: LBL_PACKAGE_TYPE
        type: enum
        options: packages_types_dom
        len: 25
        required: true
    notes
        vname: LBL_PACKAGE_NOTES
        type: text
        dbType: mediumtext
    notes_link
        vname: LBL_NOTES_LINK
        type: url
    download_link
        vname: LBL_DOWNLOAD_LINK
        type: url

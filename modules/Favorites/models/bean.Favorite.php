<?php return; /* no output */ ?>

detail
    type: bean
    bean_file: modules/Favorites/Favorite.php
    unified_search: false
    duplicate_merge: false
    table_name: iah_favorites
    primary_key: [created_by, module, module_record_id]
fields
	app.deleted
    app.date_entered
    app.created_by_user
    module
        type: module_name
        vname: LBL_MODULE
        len: 150
        required: true
    module_record_id
        type: id
        vname: LBL_MODULE_RECORD
        required: true

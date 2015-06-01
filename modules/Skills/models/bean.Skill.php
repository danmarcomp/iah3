<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Skills/Skill.php
	unified_search: false
	duplicate_merge: false
	table_name: skills
	primary_key: id
fields
	app.id
	app.date_modified
	app.modified_user
	app.deleted
	name
		vname: LBL_NAME
		type: name
		required: true
		len: 150

<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Administration/UpgradeHistory.php
	unified_search: false
	duplicate_merge: false
	comment:
		Tracks Sugar Suite upgrades made over time; used by Upgrade Wizard and
		Module Loader
	table_name: upgrade_history
	primary_key: id
fields
	app.id
	app.date_entered
	app.deleted
	filename
		type: varchar
		len: 255
		comment: Cached filename containing the upgrade scripts and content
	md5sum
		type: varchar
		len: 32
		comment: The MD5 checksum of the upgrade file
	type
		type: varchar
		len: 30
		comment: The upgrade type (module, patch, theme, etc)
	status
		type: varchar
		len: 50
		comment: The status of the upgrade (ex:  "installed")
	version
		type: varchar
		len: 20
		comment: Version as contained in manifest file
	name
		type: varchar
		len: 255
	description
		type: text
	id_name
		type: varchar
		len: 255
		comment: The unique id of the module
	manifest
		type: text
		comment: A serialized copy of the manifest file.
	from_ppack
		type: bool
		default: 0
		comment: Flag used to mark packages installed as part of personality pack
	ppack_conditions
		type:text
indices
	upgrade_history_md5_uk
		type: unique
		fields
			- md5sum

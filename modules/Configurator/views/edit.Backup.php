<?php return; /* no output */ ?>

detail
    type: edit
    title: LBL_BACKUPS_TITLE
    icon: Backups
layout
	form_buttons
	sections
		--
			id: main
			columns: 1
			show_descriptions: true
			widths: 35%
			label_widths: 25%
			desc_widths: 40%
			elements
				- backup_enabled
                - backup_target_dir
				- backup_mysqldump_path
				- backup_dump_available
				--
					name: backup_dump_enabled
					hidden: ! bean.backup_dump_available
				- backup_zip_files_filter
		--
			id: rsync_info
			vname: LBL_BACKUP_REMOTE_SYNC_OPTIONS
			columns: 1
			show_descriptions: true
			widths: 35%
			label_widths: 25%
			desc_widths: 40%
			elements
				- backup_rsync_path
				- backup_rsync_available
				- backup_rsync_enabled
				- backup_rsync_target_dir
		--
			id: backup_history
			widget: BackupHistoryWidget

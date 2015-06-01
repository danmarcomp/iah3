<?php return; /* no output */ ?>

list
	title: LBL_MANAGE_IMPORT
	show_create_button: false
	show_additional_details: false
	show_tabs: false
	no_mass_delete: true
	buttons
		delete_related
			icon: icon-cancel
			type: button
			vname: LBL_DELETE_WITH_RELATED
			confirm: NTC_DELETE_CONFIRMATION_2
			perform: sListView.sendMassUpdate('{LIST_ID}', 'DeleteWithRelated', false, null, false)
			width:30em
		delete1
			icon: icon-accept
			type: button
			vname: LBL_DELETE_WITHOUT_RELATED
			confirm: NTC_DELETE_CONFIRMATION_1
			perform: sListView.sendMassUpdate('{LIST_ID}', 'delete', false, null, false)
			width:30em
	massupdate_handlers
		--
			name: DeleteWithRelated
			class: ImportDBHistory
			file: modules/ImportDB/ImportDBHistory.php
basic_filters
	related_module
fields
	related_module
		vname: LBL_MODULE
		type: enum
		options_function
			class_function: getModuleOptions
			class: ImportDBHistory
			file: modules/ImportDB/ImportDBHistory.php
		source
			type: concat
			fields: [module_id]
		force_label:true
		options_icon: icon
		width: 60em


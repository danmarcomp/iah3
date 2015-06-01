<?php return; /* no output */ ?>

fields
	mod_name
		vname: LBL_CUSTOM_MODULE_NAME
		type: varchar
		len: 40
		required: true
		format: regexp
		format_regexp: "^[A-Za-z][A-Za-z0-9_]+$"
	reportable
		vname: LBL_REPORTABLE
		type: bool
		default: 1
	created_by_module_designer
		type: bool
	icon
		vname: LBL_ICON
		type: image_ref
		len: 255
	current_icon
		vname: 
		type: varchar
		len: 4095
	mod_title
		vname: LBL_CUSTOM_MODULE_TITLE
		type: varchar
		len: 255
		required: true
	tab_group
		vname: LBL_TAB_GROUP
		type: enum
		len: 40
		options_add_blank: false
		options_function
			class: ModuleDesignerModel
			file: modules/ModuleDesigner/ModuleDesignerModel.php
			class_function: tabGroups
	acl_level
		vname: LBL_ACL_LEVEL
		type: enum
		len: 40
		options: acl_level_dom
		options_add_blank: false
	acl_list
		vname: LBL_ACL_LIST
		type: enum
		len: 40
		options_add_blank: false
		options_function
			class: ModuleDesignerModel
			file: modules/ModuleDesigner/ModuleDesignerModel.php
			class_function: aclOptions
	acl_view
		vname: LBL_ACL_VIEW
		type: enum
		len: 40
		options_add_blank: false
		options_function
			class: ModuleDesignerModel
			file: modules/ModuleDesigner/ModuleDesignerModel.php
			class_function: aclOptions
	acl_edit
		vname: LBL_ACL_EDIT
		type: enum
		len: 40
		options_add_blank: false
		options_function
			class: ModuleDesignerModel
			file: modules/ModuleDesigner/ModuleDesignerModel.php
			class_function: aclOptions
	acl_delete
		vname: LBL_ACL_DELETE
		type: enum
		len: 40
		options_add_blank: false
		options_function
			class: ModuleDesignerModel
			file: modules/ModuleDesigner/ModuleDesignerModel.php
			class_function: aclOptions


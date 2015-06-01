<?php ?>
detail
    type: edit
    title: LBL_EDIT_MODULE
layout
	form_buttons
        save
            vname: LBL_SAVE_BUTTON_LABEL
            type: button
            onclick: return SUGAR.ui.sendForm(this.form, {"record_perform":"save"}, null);
            order: 15
            icon: icon-accept
        cancel
            vname: LBL_CANCEL_BUTTON_LABEL
            type: button
            onclick: return SUGAR.ui.cancelEdit(this.form);
            order: 15
            icon: icon-cancel
	sections
		--
			id: general
			columns: 2
			label_widths: 25%
			widths: 25%
			elements
				--
					name: mod_name
					editable: mode.new_record
				- tab_group
				--
					name: reportable
					hidden: !bean.created_by_module_designer&&!mode.new_record
				- mod_title
				- acl_level
				--
				--
					id: acl_options
					section: true
					toggle_display
						name: acl_level
						value: fixed
					elements
						- acl_view
						- acl_edit
						- acl_list
						- acl_delete
				- icon
				--
				--
					name: current_icon
					editable: false
					hidden: mode.new_record
			


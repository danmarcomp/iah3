detail
	type: loginform
layout
	indicate_required: false
	buttons_position: bottom
	editable: true
	class: loginBody
	mobile_form_buttons
		login
			id: login_button
			icon: icon-accept
			vname: LBL_LOGIN_BUTTON_LABEL
			type: submit
			onclick: ""
	form_buttons
		login
			id: login_button
			icon: icon-accept
			vname: LBL_LOGIN_BUTTON_LABEL
			type: submit
			onclick: ""
	sections
		--
			id: main
			label_position: top
			columns: 1
			elements
				--
					name: user_name
					style: "width: 165px"
				--
					name: user_password
					style: "width: 165px"
				--
					section: true
					id: ext
					elements
						--
							name: user_theme
							width: "145px"
							hidden: mobile.enabled
						--
							name: user_language
							width: "145px"

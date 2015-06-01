<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	form_buttons
        test_run
            vname: LBL_TEST_BUTTON_LABEL
            accessKey: LBL_TEST_BUTTON_KEY
            async: false
            //onclick: return SUGAR.ui.sendForm(document.forms.DetailForm, {"action":"TestRun", "no_redirect":true}, null, false, true);
            url: ?module={MODULE}&action=TestRun&record={RECORD}&{RETURN}
	sections
		--
			id: main
			elements
				--
					name: show_type
					colspan: 2
                - enabled
                - run_interval
                - run_on_user_login
                -
                --
                	name: status_text
                	colspan: 2
                --
                    name: show_description
                    colspan: 2

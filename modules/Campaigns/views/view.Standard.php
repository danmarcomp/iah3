<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    form_buttons
        test
            vname: LBL_TEST_BUTTON_LABEL
            accesskey: LBL_TEST_BUTTON_KEY
            async: false
			onclick: return SUGAR.ui.sendForm(document.forms.DetailForm, {"action":"Schedule", "return_module":"Campaigns", "return_action":"DetailView", "return_record":this.form.record.value, "mode":"test"}, null);
			hidden: bean.campaign_type!=Email&&bean.campaign_type!=NewsLetter
        queue
            vname: LBL_QUEUE_BUTTON_LABEL
            accesskey: LBL_QUEUE_BUTTON_KEY
            async: false
            onclick: return SUGAR.ui.sendForm(document.forms.DetailForm, {"action":"Schedule", "return_module":"Campaigns", "return_action":"DetailView", "return_record":this.form.record.value}, null);
			hidden: bean.campaign_type!=Email&&bean.campaign_type!=NewsLetter
        mark_sent
            vname: LBL_MARK_AS_SENT
            async: false
            onclick: return SUGAR.ui.sendForm(document.forms.DetailForm, {"action":"MarkSent", "return_module":"Campaigns", "return_action":"DetailView", "return_record":this.form.record.value}, null);
			hidden: bean.campaign_type=Email||bean.campaign_type=NewsLetter
	sections
		--
			id: main
			elements
                - name
                - assigned_user
                - status
                -
                - start_date
                - date_modified
                - end_date
                - date_entered
                - campaign_type
                - frequency
                - 
                -
                - budget
                - actual_cost
                - expected_revenue
                - expected_cost
                - impressions
                - 
                -
                -
                --
                    name: objective
                    colspan: 2
                --
                    name: content
                    colspan: 2            
    subpanels
    	- prospectlists
        - tracked_urls
        --
            name: emailmarketing
            hidden: bean.campaign_type=Dripfeed
        --
            name: dripfeedemails
            hidden: bean.campaign_type!=Dripfeed

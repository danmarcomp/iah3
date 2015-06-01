<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    scripts
        --
            file: "modules/ProspectLists/dynamic_targets.js"
        --
            file: "modules/ProspectLists/target_list.js"
    form_hooks
        oninit: "swapDomainVisibility({FORM})"
    form_hidden_fields
        list_id : 1
    current_tab: general
    tabs
        --
            name: general
            label: LBL_TAB_GENERAL
            icon: icon-sources
        --
            name: filters
            label: LBL_TAB_FILTERS
            icon: icon-filter
	sections
		--
            id: main
            tab: general
            elements
                - name
                - assigned_user
                --
                    name: list_type
                    onchange: swapDomainVisibility(this.form);
                - domain_name
                --
                    name: dynamic
                    onchange: DynamicTargetList.swapTabStatus(this.getValue());
                -
                --
                    name: description
                    colspan: 2
        --
            id: dynamic_filters
            tab: filters
            widget: TargetWidget

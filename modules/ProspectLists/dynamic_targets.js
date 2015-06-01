var DynamicTargetList = function() {
	return {
        disableTab: function(is_dynamic) {
            setTimeout ("DynamicTargetList.swapTabStatus("+is_dynamic+")", 200);
        },
		swapTabStatus: function(is_dynamic) {
            var form = SUGAR.ui.getForm('DetailForm');
            var tabs = SUGAR.ui.getFormInput(form, 'form-tabs');
            var disable_tab = true;

            if (is_dynamic)
                disable_tab = false;

            if (typeof(tabs) != 'undefined')
                tabs.enableDisableTab('filters', disable_tab);
		},
        toggleFilters: function(name, status) {
            var elt = $(name + "-filters");
            if(! elt) return null;
            var display = 'none';
            if (status)
                display = '';
            elt.style.display = display;
        }
    };
}();
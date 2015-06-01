function initProductForm(form) {
	addFormulaHooks(form);
	enforce_formula(form);
	setCategoryFilter(form);
	SUGAR.ui.attachFormInputEvent(form, 'product_category', 'onchange', function() { addCatFilter(this.form, 1); });
}

function initAssemblyForm(form) {
	setCategoryFilter(form);
	SUGAR.ui.attachFormInputEvent(form, 'product_category', 'onchange', function() { addCatFilter(this.form, 1); });
}

function addFormulaHooks(form) {
	var fields = ['cost', 'list_price', 'purchase_price', 'pricing_formula', 'ppf_perc'];
	for(var i = 0; i < fields.length; i++)
		SUGAR.ui.attachFormInputEvent(form, fields[i], 'onchange', function() { enforce_formula(this.form); });
	fields = ['support_cost', 'support_list_price', 'support_selling_price', 'support_price_formula', 'support_ppf_perc'];
	for(var i = 0; i < fields.length; i++)
		SUGAR.ui.attachFormInputEvent(form, fields[i], 'onchange', function() { enforce_support_formula(this.form); });
	SUGAR.ui.attachFormInputEvent(form, 'currency_id', 'onchange', function() { update_decimals(this.form); });
}

function setCategoryFilter(form) {
    setTimeout(function() { addCatFilter(form, 0) }, 200);
}
function addCatFilter(form, clear) {
    var cat = SUGAR.ui.getFormInput(form, 'product_category');
    if (! cat) return;
    var cat_id = cat.getKey();

    var type = SUGAR.ui.getFormInput(form, 'product_type');
    if(! type) return;
    var filter = [];
    filter[0] = {param: 'category_id', value: cat_id};
        type.add_filters = filter;

    if (clear)
        type.clear();
}
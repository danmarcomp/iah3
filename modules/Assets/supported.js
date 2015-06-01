function setOnSubmitEvent() {
    if ($('DetailForm_save'))
        $('DetailForm_save').onclick = submitForm;
    if ($('DetailForm_save2'))
        $('DetailForm_save2').onclick = submitForm;
    $('DetailForm').onsubmit = submitForm;
}

function submitForm() {
    var form = document.DetailForm;
    form.serial_updates.value = serials_editor.getChanges();
    return SUGAR.ui.sendForm(form, {'record_perform':'save'});
}

function init_form(form, account_id) {
    setTimeout('set_filters("'+form+'", "'+account_id+'")', 400);
}

function set_filters(form, account_id) {
    set_contract_filters(form, account_id);
    set_cat_filter(form, 0);
}

function set_name_extra(form) {
    setTimeout('add_product_extra_fields("'+form+'")', 400);
}

function add_product_extra_fields(form) {
    var name = SUGAR.ui.getFormInput(form, 'product_name');
    if (name) {
        if (name.getKey() != '')
            name.update('', name.getKey(), true);
        var fields = {
            url: 'url',
            date_available: 'date_available',
            tax_code: 'tax_code',
            tax_code_id: 'tax_code_id',
            supplier: 'supplier',
            supplier_id: 'supplier_id',
            manufacturer: 'manufacturer',
            manufacturer_id: 'manufacturer_id',
            model: 'model',
            model_id: 'model_id',
            weight_1: 'weight_1',
            weight_2: 'weight_2',
            manufacturers_part_no: 'manufacturers_part_no',
            product_category: 'product_category',
            product_category_id: 'product_category_id',
            product_type: 'product_type',
            product_type_id: 'product_type_id',
            vendor_part_no: 'vendor_part_no',
            purchase_price: 'purchase_price',
            unit_support_price: 'support_selling_price',
            support_cost: 'support_cost',
            currency_id: 'currency_id',
            exchange_rate: 'exchange_rate',
            description: 'description'
        };
        name.addExtraReturnFields(fields);
    }
}

function set_asm_name_extra(form) {
    setTimeout('add_assembly_extra_fields("'+form+'")', 400);
}

function add_assembly_extra_fields(form) {
    var name = SUGAR.ui.getFormInput(form, 'assembly_name');
    if (name) {
        if (name.getKey() != '')
            name.update('', name.getKey(), true);
        var fields = {
            product_category: 'product_category',
            product_category_id: 'product_category_id',
            product_type: 'product_type',
            product_type_id: 'product_type_id',
            supplier: 'supplier',
            supplier_id: 'supplier_id',
            manufacturer: 'manufacturer',
            manufacturer_id: 'manufacturer_id',
            model: 'model',
            model_id: 'model_id',
            product_url: 'product_url',
            manufacturers_part_no: 'manufacturers_part_no',
            vendor_part_no: 'vendor_part_no',
            description: 'description'
        };
        name.addExtraReturnFields(fields);
    }
}

function set_contract_filters(form, account_id) {
    if (account_id != '') {
        var contract = SUGAR.ui.getFormInput(form, 'service_subcontract');
        if (contract) {
            var filter = [];
            filter[0] = {param: 'main_account_id', value: account_id};
            contract.add_filters = filter;
        }
    }
}

function set_cat_filter(form, clear) {
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

function set_from_main(key, value, passthru) {
    var inp = null;
    for (var field in value) {
		val = value[field];
		if (field == 'description') {
			var tmp = document.createElement("DIV");
			tmp.innerHTML = val;
			val = tmp.textContent || tmp.innerText;
		}

        if (value.hasOwnProperty(field)) {
            inp = SUGAR.ui.getFormInput('DetailForm', field);
            if (inp) {
                if (typeof(inp.setValue) != 'undefined') {
                    inp.setValue(val);
                } else if (typeof(inp.update) != 'undefined') {
                    inp.update(value[field + '_id'], val);
                    delete value[field + '_id'];
                }
            } else {
                if (typeof(document.forms.DetailForm.field) != 'undefined') {
                    document.forms.DetailForm.field.value = val;
                }
            }
        }
    }
}

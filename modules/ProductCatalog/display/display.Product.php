<?php return; /* no output */ ?>

list
	default_order_by: name
    show_favorites: true
    layouts
        Simple
            hidden: true
            view_name: Simple
	massupdate_handlers
		--
			name: AddSelectedToInvoice
			class: CaseInvoicePopup
			file: modules/Cases/CaseInvoicePopup.php
basic_filters
	- product_category
	--
        field: product_type
        add_filters
            --
                param: category
                field_name: product_category
auto_filters
    supplier
filters
fields
	weight
		vname: LBL_PRODUCT_WEIGHT
		type: weight
		source
            fields: [weight_1, weight_2]
    discount_name
        vname: LBL_LIST_DISCOUNT
        vname_module: ProductCatalog
        widget: DiscountInput
widgets
    DiscountInput
        type: column
        path: modules/ProductCatalog/widgets/DiscountInput.php

function setStage(form, sales_stage) {
    var forecast_cats = app_list_strings('sales_forecast_dom');
    var probabilities = app_list_strings('sales_probability_dom');

    if(isset(sales_stage)) {
        var probability_val = 0;
        if (probabilities[sales_stage])
            probability_val = probabilities[sales_stage];

        var probability = SUGAR.ui.getFormInput(form, 'probability'),
        	forecast = SUGAR.ui.getFormInput(form, 'forecast_category');
        if(probability)
			probability.setValue(probability_val);
        if (forecast_cats[sales_stage] && forecast)
            forecast.setValue(forecast_cats[sales_stage]);
    }
}

function updateExchangeRate(oldrate, newrate) {
	var amount = SUGAR.ui.getFormInput(this.form, 'amount');
	if(amount) {
		amount.decimals = this.getDecimals();
		if(! amount.isBlank())
			amount.setValue(amount.getValue(true) * newrate / oldrate);
	}
}

function initOppForm(form) {
	SUGAR.ui.attachFormInputEvent(form, 'currency_id', 'onrateupdate', updateExchangeRate);

    var campaign = SUGAR.ui.getFormInput(form, 'campaign');

    if (campaign) {
        var fields = {
            campaign_id: 'campaign_id',
            campaign_name: 'campaign_name'
        };
        campaign.addExtraReturnFields(fields);

        campaign.popup_passthru = { form: form.id };
        campaign.popup_callback = 'set_campaign';
    }
}

function set_campaign(data) {
    var params = data.passthru_data;
    var values = data.name_to_value_array;

    if (params.form && (values.campaign_id && values.campaign_name)) {
        var campaign = SUGAR.ui.getFormInput(params.form, 'campaign');
        if (campaign)
            campaign.update(values.campaign_id, values.campaign_name);
    }
}

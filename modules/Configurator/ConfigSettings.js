var forecast_warned = false;

function notify_forecasts() {
    if (! forecast_warned)
            alert(mod_string('LBL_FORECASTS_ERASED', 'Configurator'));
    forecast_warned = true;
}

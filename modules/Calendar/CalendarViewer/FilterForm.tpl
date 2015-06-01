<button type="button" class="input-select input-outer flatter" id="button_mode_filter"><div class="input-arrow select-label">
<div class="input-icon left active {$MODE_ICON}"></div><span id="span_mode_filter" class="input-label">{$MODE_NAME}</span></div>
</button>

{iah_script}
	var calendar = {$calendar_ctrl};
	{literal}var m = new SUGAR.ui.SelectInput('button_mode_filter', {icon_key: 'icon', options: {/literal}{$MODE_OPTIONS}{literal}, init_value: '{/literal}{$MODE}{literal}'});
	m.onchange = function(key) { {/literal}{$calendar_ctrl}{literal}.applyMode(key); }
	m.setup();
{/literal}{/iah_script}

<div id="dashlet-body-{$id}" style="padding-bottom: 0.5em">
<table id="weather_rows_{$id}" cellpadding='0' cellspacing='0' width='100%' border='0' class='listView form-bottom'>
    <tr height='20'>
		<td scope='col' class='listViewThS1' width="30%" nowrap>
			{$LANG.LBL_CITY}
		</td>
		<td scope='col' class='listViewThS1' colspan='2' width="70" nowrap>
			{$LANG.LBL_CURRENT}
		</td>
		{if $showTimes}
		<td scope='col' class='listViewThS1' width="15%" nowrap>
			{$LANG.LBL_LIST_TIME}
		</td>
		{/if}
		<td scope='col' class='listViewThS1' width="20%" nowrap>
			{$LANG.LBL_TODAY}
		</td>
		<td scope='col' class='listViewThS1' width="20%" nowrap>
			{$LANG.LBL_TOMORROW}
		</td>
    </tr>
    {foreach from=$citiesList item=rowData name=rowIteration}
		{if $smarty.foreach.rowIteration.iteration is odd}
			{assign var='_rowColor' value=$rowColor[0]}
		{else}
			{assign var='_rowColor' value=$rowColor[1]}
		{/if}
	<tr class='{$_rowColor}S1' height='20' onmouseover="sListView.row_action(this, 'over', '{$id}');" onmouseout="sListView.row_action(this, 'out', '{$id}');" onmousedown="return Weather.markRow(this, 'click', '{$rowData.woeid}', '{$id}');" id="weather_row_{$rowData.woeid}" style="cursor: pointer"
				title="{$rowData.weather_data.forecast1_text|escape}">
		<td scope='row' style='vertical-align: middle;'>
			{$rowData.name}
		</td>
    {if ! $rowData.weather_data}
    	<td colspan='19' style='vertical-align: middle;'>
    		<em>{$LANG.LBL_LOOKUP_ERROR}</em>
    	</td>
    {else}
		<td scope='row' valign="middle" style='vertical-align: middle;'>
			<img src="{$rowData.weather_data.image}" width="26" height="26" alt="{$rowData.weather_data.forecast1_text|escape}" style="vertical-align: middle" />
		</td>
		<td scope='row' valign="middle" style="vertical-align: middle; text-align: right; font-weight: bold; padding-right: 25px" nowrap>
			{$rowData.weather_data.temp}
		</td>
		{if $showTimes}
		<td scope='row' valign="middle" style='vertical-align: middle;' nowrap>
			{$rowData.current_time}
		</td>
		{/if}		
		<td scope='row' valign="middle" style='vertical-align: middle;' nowrap>
			<span style="font-weight: bold;">{$rowData.weather_data.forecast1_high}</span> / {$rowData.weather_data.forecast1_low}
		</td>
		<td scope='row' valign="middle" style='vertical-align: middle;' nowrap>
			<span style="font-weight: bold;">{$rowData.weather_data.forecast2_high}</span> / {$rowData.weather_data.forecast2_low}
		</td>
	{/if}
    </tr>
	{if ! $smarty.foreach.rowIteration.last}<tr><td colspan='20' class='listViewHRS1'></td></tr>{/if}
    {/foreach}
</table>
<div id="weather_details_{$id}" style="display: none" class="fade hidden">
<table cellpadding='0' cellspacing='0' width='100%' border='0' class="h3Row">
<tr><td width="90%">
	<h3 id="city_name_{$id}"></h3>
</td><td nowrap>
	<a href="" id="weather_ext_link_{$id}" class="chartToolsLink" target="_blank">{$LANG.LBL_VISIT_SITE} <div class="input-icon icon-tlink"></div></a>
</td></tr>
</table>
<table cellpadding='0' cellspacing='0' width='100%' border='0' class='listView form-bottom'>
	<tr class="oddListRowS1">
		<td width="20%">{$LANG.LBL_CURRENT_CONDITIONS}</td>
		<td width="30%"><span style="font-weight:bold" id="weather_current_condition_{$id}"></span> <span style="white-space: nowrap" id="weather_windchill_{$id}"></span></td>
		<td width="20%">{$LANG.LBL_BAROMETER}</td>
		<td width="30%" style="font-weight:bold" id="weather_barometer_{$id}"></td>
	</tr>
	<tr><td colspan='20' class='listViewHRS1'></td></tr>
	<tr class="oddListRowS1">
		<td>{$LANG.LBL_HUMIDITY}</td>
		<td style="font-weight:bold" id="weather_humidity_{$id}"></td>
		<td>{$LANG.LBL_VISIBILITY}</td>
		<td style="font-weight:bold" id="weather_visibility_{$id}"></td>
	</tr>
	<tr><td colspan='20' class='listViewHRS1'></td></tr>
	<tr class="oddListRowS1">
		<td>{$LANG.LBL_SUNRISE}</td>
		<td style="font-weight:bold" id="weather_sunrise_{$id}"></td>
		<td>{$LANG.LBL_SUNSET}</td>
		<td style="font-weight:bold" id="weather_sunset_{$id}"></td>
	</tr>
	<tr><td colspan='20' class='listViewHRS1'></td></tr>
	<tr class="oddListRowS1">
		<td>{$LANG.LBL_WIND}</td>
		<td style="font-weight:bold" id="weather_wind_{$id}"></td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr><td colspan='20' class='listViewHRS1'></td></tr>
	<tr class="oddListRowS1">
		<td>{$LANG.LBL_FORECAST}</td>
		<td colspan="3" id="weather_forecast1_{$id}">
			<span id="weather_forecast1_day_{$id}"></span>
			 - <span id="weather_forecast1_text_{$id}" style="font-weight:bold"></span> 
			&nbsp;&nbsp;{$LANG.LBL_HIGH}: <span id="weather_forecast1_high_{$id}" style="font-weight:bold"></span> 
			&nbsp;&nbsp;{$LANG.LBL_LOW}: <span id="weather_forecast1_low_{$id}" style="font-weight:bold"></span>				
		</td>
	</tr>
	<tr class="oddListRowS1">
		<td>&nbsp;</td>
		<td colspan="3" id="weather_forecast2_{$id}">
			<span id="weather_forecast2_day_{$id}"></span>
			 - <span id="weather_forecast2_text_{$id}" style="font-weight:bold"></span> 
			&nbsp;&nbsp;{$LANG.LBL_HIGH}: <span id="weather_forecast2_high_{$id}" style="font-weight:bold"></span> 
			&nbsp;&nbsp;{$LANG.LBL_LOW}: <span id="weather_forecast2_low_{$id}" style="font-weight:bold"></span>				
		</td>		
	</tr>
</table>
</div>
</div>


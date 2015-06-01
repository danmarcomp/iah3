<div id="div_settings_bar" style="padding-top: 10px;">
<form name="settings_form" id="settings_form" method="POST" action="index.php" onsubmit="return false;">
{if $MODE eq 'resources'}
	{include file="modules/Calendar/CalendarViewer/SettingsBars/ResourcesSettings.tpl"}
{elseif $MODE eq 'projects'}
	{include file="modules/Calendar/CalendarViewer/SettingsBars/ProjectsSettings.tpl"}
{elseif $MODE eq 'timesheets'}
	{include file="modules/Calendar/CalendarViewer/SettingsBars/TimesheetsSettings.tpl"}
{else}
	{include file="modules/Calendar/CalendarViewer/SettingsBars/ActivitiesSettings.tpl"}
{/if}
</form>
</div>
{*
/******************************************************************************
* The contents of this file are subject to the CareBrains Software End User
* License Agreement ('License') which can be viewed at
* http://www.sugarforum.jp/download/cbieula.shtml
* By installing or using this file, You have unconditionally agreed to the
* terms and conditions of the License, and You may not use this file except in
* compliance with the License.  Under the terms of the license, You shall not,
* among other things: 1) sublicense, resell, rent, lease, redistribute, assign
* or otherwise transfer Your rights to the Software, and 2) use the Software
* for timesharing or service bureau purposes such as hosting the Software for
* commercial gain and/or for the benefit of a third party.  Use of the Software
* may be subject to applicable fees and any use of the Software without first
* paying applicable fees is strictly prohibited.
* Your Warranty, Limitations of liability and Indemnity are expressly stated
* in the License.  Please refer to the License for the specific language
* governing these rights and limitations under the License.
*****************************************************************************/
*}

<form name="form_calendar" id="form_calendar" method="POST">
	<input type="hidden" name="module" value="Calendar">
	<input type="hidden" name="action" value="asyncCalendarBody">
	<input type="hidden" name="to_pdf" value="1">
	<input type="hidden" id="view_type" name="view_type" value="resource_day">
	<input type="hidden" id="target_date" name="target_date" value="{$target_date}">
	<input type="hidden" id="selected_targets" name="selected_targets" value="{$selected_targets}">
	<input type="hidden" id="target_id" name="target_id" value="{$target_id}">
	<input type="hidden" id="view_mode" name="view_mode" value="{$MODE}">	
	<input type="hidden" id="target_type" name="target_type" value="{$target_type}">
	<input type="hidden" id="target_user_id" name="target_user_id" value="">
	<input type="hidden" id="target_team_id" name="target_team_id" value="">
	<input type="hidden" id="target_res_type" name="target_res_type" value="{$target_res_type}">
	<input type="hidden" id="project_id" name="project_id" value="{$PROJECT_ID}">	
	<input type="hidden" id="timesheet_id" name="timesheet_id" value="{$timesheet_id}">	

	<input type="hidden" id="display_meetings" name="display_meetings" {$display_meetings}>
	<input type="hidden" id="display_calls" name="display_calls" {$display_calls}>
	<input type="hidden" id="display_tasks" name="display_tasks" {$display_tasks}>
	<input type="hidden" id="display_project_tasks" name="display_project_tasks" {$display_project_tasks}>
	<input type="hidden" id="display_events" name="display_events" {$display_events}>
	<input type="hidden" id="display_leave" name="display_leave" {$display_leave}>
	<input type="hidden" id="target_department" name="target_department" value="{$target_department}">
	<input type="hidden" id="forDashlet" name="forDashlet" value="{$forDashlet}">
</form>
<form name="settings_form" id="settings_form" method="POST" action="index.php" onsubmit="return false;">
<table cols="1" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			{$calendar_tab}
			<table style="height:40px;" cols="3" class="tabForm calendarHead" border="0" cellpadding="0" cellspacing="0" width="100%">	
				<tr>
					<td valign="middle" nowrap="nowrap" style="padding-left:0.5em">
					{assign var="calendar_ctrl" value="DayCalendarCtrl"}
					{include file="modules/Calendar/CalendarViewer/MakerFilter.tpl"}
					</td>
				
					<td valign="middle" align="center" nowrap="nowrap" width="50%" style="padding:0 1em">
						<button type="button" onclick="ResourceDayCalendarCtrl.moveCalendar('{$cal_dt.localPrevDate}');" title="{$APP.LBL_PREVIOUS_DAY}" class="input-button input-outer nav-button"><div class="input-icon icon-prev"></div></button>
						<span class="monthHeaderH3" id="target_date_text">&nbsp;{$cal_dt.formattedDate}</span>
						<span class="{$weekdayfonts[$cal_dt.localWeekDayIndex]}">({$cal_dt.localWeekDay})&nbsp;</span>
						<button type="button" onclick="ResourceDayCalendarCtrl.moveCalendar('{$cal_dt.localNextDate}');" title="{$APP.LBL_NEXT_DAY}" class="input-button input-outer nav-button"><div class="input-icon icon-next"></div></button>
						{if $cal_dt.holiday != ""}
							<br /><span class="holiday">{$cal_dt.holiday}</span>
						{/if}
					</td>

					<td valign="middle" align="right" nowrap="nowrap" width="25%">
						<a style="font-weight:bold;" href="javascript:ResourceDayCalendarCtrl.moveCalendar('{$cal_dt.localDate}');" class="NextPrevLink">&nbsp;{$APP.LBL_CALENDAR_TODAY}</a>
						<a style="font-weight:bold;border-left:1px solid #CCCCCC;margin-right:0.5em;" href="#" class="NextPrevLink" id="select_day" >&nbsp;{$APP.LNK_SELECT_DATE}&nbsp;</a>
					</td>
				</tr>
				<tr>
					<td colspan="3" valign="middle" nowrap="nowrap" width="100%" style="padding:0 0.5em">
						{include file="modules/Calendar/CalendarViewer/GridSettingsBar.tpl"}
					</td>
				</tr>				
			</table>
		</td>
	</tr>
	
	<tr>
		<td valign="top">
			<table class="calendar_outer_table" cellpadding="0" cellspacing="0"><tbody>
            <tr>
                <td colspan="100" class="team_cal_separator">{$MOD.LBL_SECTION_USERS}</td>
                </tr>
                {foreach from=$activities_every_user_array key=user_id item=activities_every_user}
                <tr>
                <td width="100" class="team_cal_user" rowspan="2">&nbsp;</td>
                {foreach from=$hours key=hour item=hour_info}
                <td width="100" colspan="4" align="center" class="team_cal_header">{$hour_info.display}</td>
                {/foreach}
			</tr>
			<tr>
                {foreach from=$hours key=hour item=hour_info}
				<td width="25" onselectstart="return false;" onmousedown="return false;" onmouseover="ResourceDayCalendarCtrl.ov(this);" onmouseout="ResourceDayCalendarCtrl.ot(this);" class="team_cal_header_min"
				{if $canEdit && $MODE != "projects"}
                    {if $MODE != "timesheets"}
                        onclick="MeetingsEditView.showNew(this, '{$user_id}', '', '{$target_date}', '{$hour}', '0', '', '15', 0, '{$user_ids}', '{$resource_ids}');"
                    {else}
                        onclick="HoursEditView.showNew(this, '{$user_id}', '', '{$target_date}', '{$hour}', '0', '', '15');"
                    {/if}
				{/if}>&nbsp;</td>
				<td width="25" onselectstart="return false;" onmousedown="return false;" onmouseover="ResourceDayCalendarCtrl.ov(this);" onmouseout="ResourceDayCalendarCtrl.ot(this);" class="team_cal_header_min noleft"
				{if $canEdit && $MODE != "projects"}
                    {if $MODE != "timesheets"}
                        onclick="MeetingsEditView.showNew(this, '{$user_id}', '', '{$target_date}', '{$hour}', '15', '', '15', 0, '{$user_ids}', '{$resource_ids}');"
                    {else}
                        onclick="HoursEditView.showNew(this, '{$user_id}', '', '{$target_date}', '{$hour}', '15', '', '15');"
                    {/if}
				{/if}>&nbsp;
				</td>
				<td width="25" onselectstart="return false;" onmousedown="return false;" onmouseover="ResourceDayCalendarCtrl.ov(this);" onmouseout="ResourceDayCalendarCtrl.ot(this);" class="team_cal_header_min"
				{if $canEdit && $MODE != "projects"}
                    {if $MODE != "timesheets"}
                        onclick="MeetingsEditView.showNew(this, '{$user_id}', '', '{$target_date}', '{$hour}', '30', '', '15', 0, '{$user_ids}', '{$resource_ids}');"
                    {else}
                        onclick="HoursEditView.showNew(this, '{$user_id}', '', '{$target_date}', '{$hour}', '30', '', '15');"
                    {/if}
				{/if}>&nbsp;</td>
				<td width="25" onselectstart="return false;" onmousedown="return false;" onmouseover="ResourceDayCalendarCtrl.ov(this);" onmouseout="ResourceDayCalendarCtrl.ot(this);" class="team_cal_header_min noleft"
				{if $canEdit && $MODE != "projects"}
                    {if $MODE != "timesheets"}
                        onclick="MeetingsEditView.showNew(this, '{$user_id}', '', '{$target_date}', '{$hour}', '45', '', '15', 0, '{$user_ids}', '{$resource_ids}');"
                    {else}
                        onclick="HoursEditView.showNew(this, '{$user_id}', '', '{$target_date}', '{$hour}', '45', '', '15');"
                    {/if}
				{/if}>&nbsp;</td>
	            {/foreach}
			</tr>
	        {foreach from=$activities_every_user.activities_of_level key=level item=activities name=act}
			<tr>
                {if $smarty.foreach.act.first}
                <td valign="top" class="team_cal_user" rowspan="{$activities_every_user.max_level+1}"  nowrap="nowrap">
                <a href="javascript:DayCalendarCtrl.asyncCalendarBody('{$target_date}', '{$user_id}', 'user');">{$activities_every_user.full_name}</a>
                &nbsp;<div class="input-icon active-icon icon-delete" onclick="{$calendar_ctrl}.deleteGridEntry('{$user_id}', 'user');"></div>
                </td>
                {/if}
                {assign var=pre_end_offset value=$start_offset_min}
                {foreach from=$activities item=activity}
                {assign var=activity_id value=$activity.id}
                {* 空白を追加 *}
                {if $pre_end_offset < $activity.startOffsetMinutes}
                {math equation="(x-y+1)/15" x=$activity.startOffsetMinutes y=$pre_end_offset format="%d" assign=diff}
                <td valign="top" colspan="{$diff}" class="team_cal_empty">&nbsp;</td>
                {/if}
                {* 予定を追加 *}
                {math equation="max(x+y, 0)" x=$activity.startOffsetMinutes y=$activity.durationMin format="%d" assign=endOffsetMinutes}
                {if $endOffsetMinutes > $end_offset_min}
                {math equation="(x-y+1)/15" x=$end_offset_min y=$activity.startOffsetMinutes format="%d" assign=diff}
                {assign var=pre_end_offset value=$end_offset_min}
                {else}
                {math equation="(x-y+1)/15" x=$endOffsetMinutes y=$activity.startOffsetMinutes format="%d" assign=diff}
                {assign var=pre_end_offset value=$endOffsetMinutes}
                {/if}
                {if $activity.isViewAble == true}
                <td  valign="top" colspan="{$diff}" class="team_cal_activity{if $activity.isDuplicate} dup{/if}{if $activity.canEdit} can_edit{/if}"
                 onselectstart="return false;" onmousedown="return false;"
                {if $activity.canEdit && $MODE != "projects"}
                    {if $MODE != 'timesheets'}
                        ondblclick="MeetingsEditView.show(this,  '{$activity.module}', '{$activity_id}', '{$resource_id}');"
                    {else}
                        ondblclick="HoursEditView.show(this,  '{$activity_id}', '', '', '', '', '');"
                    {/if}
                {/if}>
                <div id="div_act_{$activity_id}" valign="top">
                <a href="index.php?module={$activity.module}&amp;action=DetailView&amp;record={$activity_id}" class="week_activity_item">
                {$activity.imgHTML}
                </a>
                {$activity.subject}
                {$activity.recurrenceImgHTML}
                </div>
                </td>
                {else}
                <td  valign="top" colspan="{$diff}" class="team_cal_activity{if $activity.isDuplicate} dup{/if}">
                <div id="div_act_{$activity_id}" valign="top">
                {$activity.imgHTML}
                {$activity.subject}
                {$activity.recurrenceImgHTML}
                </div>
                </td>
                {/if}
                {/foreach}
                {* 一番最後の予定より後ろの部分に空白を埋める *}
                {if $pre_end_offset < $end_offset_min}
                {math equation="ceil((x-y+1)/30)*2" x=$end_offset_min y=$pre_end_offset format="%d" assign=diff}
                <td valign="top" colspan="{$diff}" class="team_cal_empty">&nbsp;</td>
                {/if}
			</tr>
	        {/foreach}
            {/foreach}

            <tr>
            	<td class="team_cal_add" align="right">
                    <b>{$MOD.LBL_ADD_USER}:&nbsp;</b>
                </td>
                <td colspan="100" class="team_cal_add">&nbsp;{$add_user}</td>
            </tr>

            {if $MODE eq "activities"}
            <tr>
                <td colspan="100" class="team_cal_separator">{$MOD.LBL_SECTION_RESOURCES}</td>
            </tr>
            {foreach from=$activities_every_resource_array key=resource_id item=activities_every_resource}
			<tr>
			    <td width="100" class="team_cal_user" rowspan="2">&nbsp;</td>
	            {foreach from=$hours key=hour item=hour_info}
				<td width="100" colspan="4" align="center" class="team_cal_header">{$hour_info.display}</td>
	            {/foreach}
			</tr>
			<tr>
    	        {foreach from=$hours key=hour item=hour_info}
	    		<td width="25" onselectstart="return false;" onmousedown="return false;" onmouseover="ResourceDayCalendarCtrl.ov(this);" onmouseout="ResourceDayCalendarCtrl.ot(this);" class="team_cal_header_min"
				{if $canEdit}
				onclick="MeetingsEditView.showNew(this, '', '{$resource_id}', '{$target_date}', '{$hour}', '0', '', 15, 0, '{$user_ids}', '{$resource_ids}');"
				{/if}
				>&nbsp;</td>
		    	<td width="25" onselectstart="return false;" onmousedown="return false;" onmouseover="ResourceDayCalendarCtrl.ov(this);" onmouseout="ResourceDayCalendarCtrl.ot(this);" class="team_cal_header_min noleft"
				{if $canEdit}
				onclick="MeetingsEditView.showNew(this, '', '{$resource_id}', '{$target_date}', '{$hour}', '15', '', 15, 0, '{$user_ids}', '{$resource_ids}');"
				{/if}
				>&nbsp;</td>
				<td width="25" onselectstart="return false;" onmousedown="return false;" onmouseover="ResourceDayCalendarCtrl.ov(this);" onmouseout="ResourceDayCalendarCtrl.ot(this);" class="team_cal_header_min"
				{if $canEdit}
				onclick="MeetingsEditView.showNew(this, '', '{$resource_id}', '{$target_date}', '{$hour}', '30', '', 15, 0, '{$user_ids}', '{$resource_ids}');"
				{/if}
				>&nbsp;</td>
			    <td width="25" onselectstart="return false;" onmousedown="return false;" onmouseover="ResourceDayCalendarCtrl.ov(this);" onmouseout="ResourceDayCalendarCtrl.ot(this);" class="team_cal_header_min noleft"
				{if $canEdit}
				onclick="MeetingsEditView.showNew(this, '', '{$resource_id}', '{$target_date}', '{$hour}', '45', '', 15, 0, '{$user_ids}', '{$resource_ids}');"
				{/if}
				>&nbsp;</td>
	            {/foreach}
			</tr>
	        {foreach from=$activities_every_resource.activities_of_level key=level item=activities name=act}
			<tr>
				{if $smarty.foreach.act.first}
				<td valign="top" class="team_cal_user" rowspan="{$activities_every_resource.max_level+1}" nowrap="nowrap">
				<a href="javascript:DayCalendarCtrl.asyncCalendarBody('{$target_date}', '{$resource_id}', 'resource');">{$activities_every_resource.full_name}</a>
                &nbsp;<div class="input-icon active-icon icon-delete" onclick="{$calendar_ctrl}.deleteGridEntry('{$resource_id}', 'resource');"></div>
				</td>
				{/if}
	            {assign var=pre_end_offset value=$start_offset_min}
        	    {foreach from=$activities item=activity}
	            {assign var=activity_id value=$activity.id}
		        {* 空白を追加 *}
		        {if $pre_end_offset < $activity.startOffsetMinutes}
			    {math equation="(x-y+1)/15" x=$activity.startOffsetMinutes y=$pre_end_offset format="%d" assign=diff}
				<td valign="top" colspan="{$diff}" class="team_cal_empty">&nbsp;</td>
		        {/if}
		        {* 予定を追加 *}
        		{math equation="max(x+y, 0)" x=$activity.startOffsetMinutes y=$activity.durationMin format="%d" assign=endOffsetMinutes}

		        {if $endOffsetMinutes > $end_offset_min}
			    {math equation="(x-y+1)/15" x=$end_offset_min y=$activity.startOffsetMinutes format="%d" assign=diff}
			    {assign var=pre_end_offset value=$end_offset_min}
		        {else}
			    {math equation="(x-y+1)/15" x=$endOffsetMinutes y=$activity.startOffsetMinutes format="%d" assign=diff}
			    {assign var=pre_end_offset value=$endOffsetMinutes}
		        {/if}
		        {if $activity.isViewAble == true}
				<td  valign="top" colspan="{$diff}" class="team_cal_activity{if $activity.isDuplicate} dup{/if}{if $activity.canEdit} can_edit{/if}"
				 onselectstart="return false;" onmousedown="return false;"
				{if $activity.canEdit}
				ondblclick="MeetingsEditView.show(this,  '{$activity.module}', '{$activity_id}', '{$resource_id}');"
				{/if}>
				<div id="div_act_{$activity_id}" valign="top">
				<a href="index.php?module={$activity.module}&amp;action=DetailView&amp;record={$activity_id}" class="week_activity_item">
				{$activity.imgHTML}
				</a>
				{$activity.subject}
				{$activity.recurrenceImgHTML}
				</div>
				</td>
        		{else}
				<td  valign="top" colspan="{$diff}" class="team_cal_activity{if $activity.isDuplicate} dup{/if}">
				<div id="div_act_{$activity_id}" valign="top">
					{$activity.imgHTML}
					{$activity.subject}
					{$activity.recurrenceImgHTML}
				</div>
				</td>
		        {/if}
	            {/foreach}
	            {* 一番最後の予定より後ろの部分に空白を埋める *}
	            {if $pre_end_offset < $end_offset_min}
		        {math equation="ceil((x-y+1)/30)*2" x=$end_offset_min y=$pre_end_offset format="%d" assign=diff}
				<td valign="top" colspan="{$diff}" class="team_cal_empty">&nbsp;</td>
	            {/if}
			</tr>
	        {/foreach}
            {/foreach}

            <tr>
                <td class="team_cal_add" align="right">
	                <b>{$MOD.LBL_ADD_RESOURCE}:&nbsp;</b>
	            </td>
                <td colspan="100" class="team_cal_add">&nbsp;{$add_resource}</td>
            </tr>
            {/if}
			</tbody>
	        </table>
	    </td>
    </tr>    
	<tr>
		<td>
			<div class="calendar_footer timebar">
			<div class="calendar_footer_help">
			{$MOD.LBL_MODIFIED_TIMEBAR_HELP}
			</div>
			</div>
		</td>
	</tr>
</table>
</form>
<input id="print_url" type="hidden" value="{$PRINT_LINK}" >
{iah_script}
{$init_js}
setPrintLink({if $forDashlet}'dashlet_print_{$dashletId}'{/if});
window.defaultEditModule = '{$defaultEditModule}';
{literal}
function checkUserId() {
	setTimeout('var newId = $("user_id_selected").value; if (newId != "") DayCalendarCtrl.addGridEntry($("settings_form"), "user");', 200);
}
function checkResourceId() {
	setTimeout('var newId = $("res_id_selected").value; if (newId != "") DayCalendarCtrl.addGridEntry($("settings_form"), "resource");', 200);
}
{/literal}
{/iah_script}

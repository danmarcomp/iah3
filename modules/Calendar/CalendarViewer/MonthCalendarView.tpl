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
	<input type="hidden" id="view_type" name="view_type" value="month">
	<input type="hidden" id="target_date" name="target_date" value="{$target_date}">
	<input name="target_res_type" type="hidden" value="">
	
	<input type="hidden" id="view_mode" name="view_mode" value="{$MODE}">		
	<input type="hidden" id="target_type" name="target_type" value="{$target_type}">
	<input type="hidden" id="target_id" name="target_id" value="{$target_id}">
	<input type="hidden" id="selected_targets" name="selected_targets" value="{$selected_targets}">
	<input type="hidden" id="target_team_id" name="target_team_id" value="{$target_team_id}">
	<input type="hidden" id="project_id" name="project_id" value="{$PROJECT_ID}">	
	<input type="hidden" id="timesheet_id" name="timesheet_id" value="{$timeshee_id}">

	<input type="hidden" id="display_meetings" name="display_meetings" {$display_meetings}>
	<input type="hidden" id="display_calls" name="display_calls" {$display_calls}>
	<input type="hidden" id="display_tasks" name="display_tasks" {$display_tasks}>
	<input type="hidden" id="display_project_tasks" name="display_project_tasks" {$display_project_tasks}>
	<input type="hidden" id="display_events" name="display_events" {$display_events}>
	<input type="hidden" id="display_leave" name="display_leave" {$display_leave}>
	<input type="hidden" id="display_booked_hours" name="display_booked_hours" {$display_booked_hours}>		
	<input type="hidden" id="target_department" name="target_department" value="{$target_department}">
	<input type="hidden" id="forDashlet" name="forDashlet" value="{$forDashlet}">
</form>

<table cols="1" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td>
			{$calendar_tab}
			<table style="height:40px;" cols="3" class="tabForm calendarHead" border="0" cellpadding="0" cellspacing="0" width="100%">	
				<tr>
					<td valign="middle" nowrap="nowrap" width="25%" style="padding-left:0.5em">
					{assign var="calendar_ctrl" value="MonthCalendarCtrl"}
					{include file="modules/Calendar/CalendarViewer/FilterForm.tpl"}
					</td>
				
					<td valign="middle" align="center" nowrap="nowrap" width="50%" style="padding:0 1em">
						<span class="monthHeaderH3" id="target_date_text">
							<button type="button" onclick="MonthCalendarCtrl.moveCalendar('{$prev_date}');" title="{$APP.LBL_PREVIOUS_MONTH}" class="input-button input-outer nav-button"><div class="input-icon icon-prev"></div></button>
							&nbsp;{$cal_dt.formattedMonth}&nbsp;
							<button type="button" onclick="MonthCalendarCtrl.moveCalendar('{$next_date}');" title="{$APP.LBL_NEXT_MONTH}" class="input-button input-outer nav-button"><div class="input-icon icon-next"></div></button>
						</span>
					</td>
					
					<td valign="middle" align="right" nowrap="nowrap" width="25%">
						<a style="font-weight:bold;" href="javascript:MonthCalendarCtrl.moveCalendar('{$to_day}');" class="NextPrevLink">&nbsp;{$APP.LBL_CALENDAR_TODAY}</a>
						<a style="font-weight:bold;border-left:1px solid #CCCCCC;margin-right:0.5em;" href="#" class="NextPrevLink" id="select_day" >&nbsp;{$APP.LNK_SELECT_DATE}&nbsp;</a>
					</td>
				</tr>
				<tr>
					<td colspan="3" valign="middle" nowrap="nowrap" width="100%" style="padding:0 0.5em">
						{include file="modules/Calendar/CalendarViewer/SettingsBar.tpl"}
					</td>				
				</tr>				
			</table>
		</td>
	</tr>
	
	<tr>
		<td valign="top">
	{assign var=colCount value=$activities_of_weeks_array.0.week_days|@count}
			<div class="calendarbox">
			<table cols="{$colCount+1}" class="calendar_outer_table" cellpadding="0" cellspacing="0" width="100%"><tbody>
				<tr>
					<td width="5%" valign="top" class="month_header_left corner">{$APP.LBL_WEEK_COL_HEADER}</td>
	{foreach from=$activities_of_weeks_array.0.week_days key=day_index item=weekday name=days}
					<td width="14%" align="center" valign="top" class="month_header day_name{$weekday.weekdayCssClass}{if !$smarty.foreach.days.index} first{/if}">
							<span class="{$weekday.weekdayCssClass}">{$weekday.weekday}</span>
					</td>
	{/foreach}
				</tr>
{foreach from=$activities_of_weeks_array key=row_index item=activities_of_weeks name=weeks}
				<tr class="month_header_row">
					<td width="5%" valign="top" class="month_header_left week_start{if !$row_index} toprow{/if}">&nbsp;</td>
	{foreach from=$activities_of_weeks.week_days key=day_index item=weekday name=days}
					
					<td width="14%" align="center" valign="top" class="month_header week_day{$weekday.dateCssClass}{if !$row_index} toprow{/if}{if !$smarty.foreach.days.index} first{/if}">
						<a href="javascript:DayCalendarCtrl.moveCalendar('{$weekday.date}');">
							<span class="{$weekdayfonts[$weekday.weekDayIndex]}">{$weekday.day}</span></a>
						{if $canEdit && $MODE != 'projects'}						  
						    <img src="themes/{$THEME}/images/CreateMeetings.gif" border="0" style="vertical-align:bottom;cursor:pointer"
                            {if $MODE != "timesheets"}
						        onclick="MeetingsEditView.show(this, '{$defaultEditModule}', '', '{$target_user_id}', '{$activities_of_weeks.week_days.$day_index.date}', '{$local_current_hour}', '0', '{$target_res_id}');"
						    {else}
						        onclick="HoursEditView.show(this, '', '{$target_user_id}', '{$activities_of_weeks.week_days.$day_index.date}', '{$local_current_hour}', '0', '{$timesheet_id}');"
						    {/if}
						    onselectstart="return false;" onmousedown="return false;" />
						{/if}
					</td>
	{/foreach}
				</tr>
				<tr>
					{assign var=nusers value=0}
					{foreach from=$activities_of_weeks.activities_of_week key=user_id item=activities_of_week_array}
						{assign var=nusers value=$nusers+1}
					{/foreach}
					{assign var=nusersSpan value=1}
					{if $nusers > 1 || ($multiple_users && $nusers > 0)}
						{assign var=nusersSpan value=$nusers*2}
					{/if}
					<td valign="top" class="month_header_left week_number" rowspan="{$nusersSpan}">
						<a href="javascript:WeekCalendarCtrl.asyncCalendarBody('{$activities_of_weeks.weekDT->localDate}', '{$target_id}', '{$target_type}', '{$target_team_id}');">{$activities_of_weeks.week_number}</a>
					</td>
		{assign var=user_counter value=0}
	{foreach from=$activities_of_weeks.activities_of_week key=user_id item=activities_of_week_array}
		{assign var=user_counter value=$user_counter+1}
		{if $user_counter != 1}
		</tr><tr>
		{/if}
		<!-- 1 -->
		{if $nusers > 1 || $multiple_users}
		<td colspan="{$colCount}" class="month_activity user_name">
			<b>{$activities_of_week_array.user_full_name}</b>
		</td>
		</tr><tr>
		{/if}
		{foreach from=$activities_of_week_array.activities_of_day_array key=week_index item=activities_of_day name=days}
					<td valign="top" class="month_activity{$activities_of_weeks.week_days[$week_index].dateCssClass}{if !$smarty.foreach.days.index} first{/if}"
						{if $canEdit}
                            {if $MODE != 'timesheets'}
    							onclick="MeetingsEditView.show(this, '{$defaultEditModule}', '', '{$target_user_id}', '{$activities_of_weeks.week_days.$week_index.date}', '{$local_current_hour}', '0', '{$target_res_id}');"
                            {else}
                                onclick="HoursEditView.show(this,  '', '{$target_user_id}', '{$activities_of_weeks.week_days.$week_index.date}', '{$local_current_hour}', '0', '{$timesheet_id}');"
                            {/if}
							onmouseover="MonthCalendarCtrl.ov(this);" onmouseout="MonthCalendarCtrl.ot(this);"
						{/if}
					onselectstart="return false;" onmousedown="return false;">
					{if $activities_of_weeks.week_days[$week_index].holiday != ""}
						<div onselectstart="return false;" onmousedown="return false;">
							<table cols="2" width="95%" class="week_activity_item" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td colspan="2" class="week_activity_item content">
										<span class="holiday">{$activities_of_weeks.week_days[$week_index].holiday}</span>
									</td>
								</tr>
							</table>
						</div>
					{/if}
			{foreach from=$activities_of_day.activities key=activity_id item=activity}
		<!-- 3 -->
				{if $activity.isViewAble == true}
						<div 
						{if $activity.canEdit}
                            {if $MODE != 'timesheets'}
        						ondblclick="MeetingsEditView.show(this,  '{$activity.module}', '{$activity_id}', '{$target_user_id}');"
                            {else}
                                ondblclick="HoursEditView.show(this,  '{$activity_id}', '{$target_user_id}', '', '', '', '{$timesheet_id}');"                            
                            {/if}
						{/if}
						onclick="if(YAHOO.util.Event.getTarget(event).tagName == 'TD') YAHOO.util.Event.stopEvent(event);"
						onselectstart="return false;" onmousedown="return false;" valign="top" class="{$activity.cssClass}">
							<table cols="2" width="95%" class="week_activity_item{if $activity.is_duplicate} dup{/if}{if $activity.canEdit} can_edit{/if}" style="{$activity.calendar_color}" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td width="22" class="week_activity_item" style="white-space: nowrap">
										<a href="index.php?module={$activity.module}&amp;action=DetailView&amp;record={$activity_id}" class="week_activity_item">
										{$activity.activityImgHTML}
										</a>
										<div class="input-icon icon-info" id="act_{$activity_id}_{$row_index}_{$week_index}" onmouseover="return SUGAR.util.getAdditionalDetails('{$activity.module}', '{$activity_id}', 'act_{$activity_id}_{$row_index}_{$week_index}');" onmouseout="return SUGAR.util.clearAdditionalDetailsCall();"></div>
									</td>
									<td class="week_activity_item title">
										{$activity.startTime}{if $activity.endTime}-{$activity.endTime}{/if}
									</td>
								</tr>
								<tr>
									<td colspan="2" class="week_activity_item content">
                                        {if $activity.status_color != ''}
                                            <div class="input-icon icon-led{$activity.status_color}"></div>
                                        {/if}
										{$activity.subject}
										{$activity.recurrenceImgHTML}									
									</td>
								</tr>
							</table>
						</div>
				{else}
						<div onselectstart="return false;" onmousedown="return false;" class="{$activity.cssClass}">	
							<table cols="2" width="95%" class="week_activity_item{if $activity.is_duplicate} dup{/if}" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td width="16" class="week_activity_item" nowrap>
										{$activity.activityImgHTML}
									</td>
									<td class="week_activity_item title" nowrap>
                                        {$activity.startTime}-{$activity.endTime}
									</td>
								</tr>
								<tr>
									<td colspan="2" class="week_activity_item content">
                                        {if $activity.status_color != ''}
                                            <div class="input-icon icon-led{$activity.status_color}"></div>
                                        {/if}
										{$activity.subject}
										{$activity.recurrenceImgHTML}									
									</td>
								</tr>
							</table>
						</div>
				{/if}
				<!-- /3 -->
			{/foreach}
			&nbsp;
			{foreach from=$activities_of_day.tasks key=task_id item=task}
						<div valign="top">							
							<table cols="1" width="95%" class="week_activity_item" border="0" cellspacing="0" cellpadding="2">
								<tr>
									<td width="16" class="week_activity_item">
										<a href="index.php?module=Tasks&amp;action=DetailView&amp;record={$task_id}" class="week_activity_item">
											<img src="themes/{$THEME}/images/Tasks.gif" valign="bottom" style="vertical-align: bottom;" border="0" height="16" hspace="0" vspace="0" width="16" />
										</a>
									<td>
									<td class="week_activity_item">
										<div class="input-icon icon-info" id="act_{$task_id}" onmouseover="return SUGAR.util.getAdditionalDetails('Tasks', '{$task_id}', 'act_{$task_id}');" onmouseout="return SUGAR.util.clearAdditionalDetailsCall();"></div>
										{$task.subject}
									</td>
								</tr>
							</table>
						</div>
			{/foreach}
					</td>
		<!-- /2 -->
		{/foreach}
		<!-- /1 -->
	{foreachelse}
		{foreach from=$activities_of_weeks.week_days key=week_index name=days item=day}
	<td class="month_activity{$day.dateCssClass}{if !$smarty.foreach.days.index} first{/if}" onmouseover="MonthCalendarCtrl.ov(this);" onmouseout="MonthCalendarCtrl.ot(this);">
		{if $day.holiday != ""}
			<div onselectstart="return false;" onmousedown="return false;">
				<table cols="2" width="95%" class="week_activity_item" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td colspan="2" class="week_activity_item content">
							<span class="holiday">{$day.holiday}</span>
						</td>
					</tr>
				</table>
			</div>
		{else}
			&nbsp;
		{/if}
	</td>
		{/foreach}
	{/foreach}
				</tr>
{/foreach}
			</tbody></table>
			</div>
		</td>		
	<tr>
		<td>
			<div class="calendar_footer">
				<div class="calendar_footer_help">
					&nbsp;
				</div>
			</div>
		</td>
	</tr>
</table>

<input id="print_url" type="hidden" value="{$PRINT_LINK}" >
{iah_script}
{$init_js}
setPrintLink({if $forDashlet}'dashlet_print_{$dashletId}'{/if});
window.defaultEditModule = '{$defaultEditModule}';
{/iah_script}

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

<form name="form_calendar">
	<input type="hidden" name="module" value="Calendar">
	<input type="hidden" name="action" value="asyncCalendarBody">
	<input type="hidden" name="to_pdf" value="1">
	<input type="hidden" id="view_type" name="view_type" value="team_day">
	<input type="hidden" id="selected_targets" name="selected_targets" value="{$selected_targets}">
	<input type="hidden" id="view_mode" name="view_mode" value="{$MODE}">
	<input type="hidden" id="project_id" name="project_id" value="{$PROJECT_ID}">				
	<input type="hidden" id="target_id" name="target_id" value="{$target_id}">
	<input type="hidden" id="target_type" name="target_type" value="{$target_type}">
	<input type="hidden" id="target_date" name="target_date" value="{$target_date}">
	<input type="hidden" id="target_user_id" name="target_user_id" value="{$target_user_id}">
	<input type="hidden" id="target_team_id" name="target_team_id" value="{$target_team_id}">
	<input type="hidden" id="display_meetings" name="display_meetings" {$display_meetings}>
	<input type="hidden" id="display_calls" name="display_calls" {$display_calls}>
	<input type="hidden" id="display_tasks" name="display_tasks" {$display_tasks}>
	<input type="hidden" id="display_project_tasks" name="display_project_tasks" {$display_project_tasks}>
	<input type="hidden" id="display_events" name="display_events" {$display_events}>
	<input type="hidden" id="display_leave" name="display_leave" {$display_leave}>
	<input type="hidden" id="display_booked_hours" name="display_booked_hours" {$display_booked_hours}>		
	<input type="hidden" id="forDashlet" name="forDashlet" value="{$forDashlet}">
</form>

<table cols="1" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			{$calendar_tab}
			<table style="border-top: 0px none;height:40px;" cols="3" class="tabForm calendarHead" border="0" cellpadding="0" cellspacing="0" width="100%">	
				<tr>
					<td align="left" nowrap="nowrap" width="25%">
						<form id="form_select_team" name="form_select_team">
							<img src="themes/{$THEME}/images/Teams.gif" border="0" style="vertical-align:bottom;" />
							<span class="monthHeaderH3">{$APP.LBL_TEAM}</span><select name="sel_target_team" onchange="TeamWeekCalendarCtrl.teamOnChange(this.value)">{$team_options}</select>
						</form>
					</td>
				
					<td valign="middle" align="center" nowrap="nowrap" width="50%">
						<span class="monthHeaderH3" id="target_date_text">{$cal_dt.localYear}{$MOD.LBL_YEAR_SEPARATOR}{$cal_dt.localMonth}{$MOD.LBL_MONTH_SEPARATOR}{$cal_dt.localDay}{$MOD.LBL_DAY_SEPARATOR}
							<span class="{$weekdayfonts[$cal_dt.localWeekDayIndex]}">({$cal_dt.localWeekDay})</span>
							{$cal_dt.rokuyo}
							{if $cal_dt.holiday != ""}
								<span class="week_day_holiday">{$cal_dt.holiday}</span>
							{/if}
						</span>
					</td>
					<td valign="middle" align="right" nowrap="nowrap" width="25%">
						<a style="font-weight:bold;border-left:1px solid #CCCCCC;" href="javascript:TeamDayCalendarCtrl.moveCalendar('{$cal_dt.localPrevDate}');" class="NextPrevLink">
							&nbsp;<img src="themes/{$THEME}/images/calendar_previous.gif" alt="{$APP.LBL_PREVIOUS_DAY}" title="{$APP.LBL_PREVIOUS_DAY}" align="absmiddle" border="0" height="10" width="6">
						</a>
						&nbsp;
						<a href="javascript:TeamDayCalendarCtrl.moveCalendar('{$cal_dt.localNextDate}');" alt="{$APP.LBL_NEXT_DAY}" class="NextPrevLink">
							<img src="themes/{$THEME}/images/calendar_next.gif" alt="{$APP.LBL_NEXT_DAY}" title="{$APP.LBL_NEXT_DAY}" align="absmiddle" border="0" height="10" width="6">
						</a>
						<a style="font-weight:bold;border-left:1px solid #CCCCCC;" href="javascript:TeamDayCalendarCtrl.moveCalendar('{$cal_dt.localDate}');" class="NextPrevLink">&nbsp;{$APP.LBL_CALENDAR_TODAY}</a>
												
						<a style="font-weight:bold;border-left:1px solid #CCCCCC;border-right:1px solid #CCCCCC;" href="#" class="NextPrevLink" id="select_day" >&nbsp;{$APP.LNK_SELECT_DATE}&nbsp;</a>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr>
		<td valign="top">
			<table class="team_cal" cellpadding="0" cellspacing="0"><tbody>
{foreach from=$activities_every_user_array key=user_id item=activities_every_user}
				<tr>
					<td width="100" class="team_cal_header" rowspan="2">&nbsp;</td>
	{foreach from=$hours key=hour item=hour_info}
					<td width="100" colspan="4" align="center" class="team_cal_header">{$hour_info.display}</td>
	{/foreach}
				</tr>
				<tr>
	{foreach from=$hours key=hour item=hour_info}
					<td width="25" onmouseover="TeamWeekCalendarCtrl.ov(this);" onmouseout="TeamWeekCalendarCtrl.ot(this);" class="team_cal_header_min"
						ondblclick="MeetingsEditView.show(this, 'Meetings', '', '{$user_id}', '{$target_date}', '{$hour}', '0');">&nbsp;</td>
					<td width="25" onmouseover="TeamWeekCalendarCtrl.ov(this);" onmouseout="TeamWeekCalendarCtrl.ot(this);" class="team_cal_header_min_noleft"
						ondblclick="MeetingsEditView.show(this, 'Meetings', '', '{$user_id}', '{$target_date}', '{$hour}', '15');">&nbsp;</td>
					<td width="25" onmouseover="TeamWeekCalendarCtrl.ov(this);" onmouseout="TeamWeekCalendarCtrl.ot(this);" class="team_cal_header_min"
						ondblclick="MeetingsEditView.show(this, 'Meetings', '', '{$user_id}', '{$target_date}', '{$hour}', '30');">&nbsp;</td>
					<td width="25" onmouseover="TeamWeekCalendarCtrl.ov(this);" onmouseout="TeamWeekCalendarCtrl.ot(this);" class="team_cal_header_min_noleft"
						ondblclick="MeetingsEditView.show(this, 'Meetings', '', '{$user_id}', '{$target_date}', '{$hour}', '45');">&nbsp;</td>
	{/foreach}
				</tr>
				<tr>
					<td valign="top" class="team_cal_user" rowspan="{$activities_every_user.max_level+1}">
						<a href="javascript:DayCalendarCtrl.asyncCalendarBody('{$target_date}', '{$user_id}', 'user');">{$activities_every_user.full_name}</a>
					</td>
	{assign var="pre_end_offset" value=$start_offset_min}
	
	{foreach from=$activities_every_user.activities key=activity_id item=activity}
		{* 空白を追加 *}
		{if $pre_end_offset < $activity.startOffsetMinutes}
			{math equation="(x-y+1)/15" x=$activity.startOffsetMinutes y=$pre_end_offset format="%d" assign=diff}
					<td valign="top" colspan="{$diff}" class="team_cal_empty">
						&nbsp;
					</td>
		{/if}
		
		{* 予定を追加 *}
		{math equation="(x+y)" x=$activity.startOffsetMinutes y=$activity.durationMin format="%d" assign=endOffsetMinutes}

		{if $endOffsetMinutes > $end_offset_min}
			{math equation="(x-y+1)/15" x=$end_offset_min y=$activity.startOffsetMinutes format="%d" assign=diff}
			{assign var="pre_end_offset" value=$end_offset_min}
		{else}
			{math equation="(x-y+1)/15" x=$endOffsetMinutes y=$activity.startOffsetMinutes format="%d" assign=diff}
			{assign var="pre_end_offset" value=$endOffsetMinutes}
		{/if}
		
		{if $activity.isViewAble == true}
					<td  valign="top" colspan="{$diff}" class="{if $activity.isDuplicate}team_cal_activity_dup{else}team_cal_activity{/if}" ondblclick="MeetingsEditView.show(this,  '{$activity.module}', '{$activity_id}', '{$user_id}');"  style="cursor:pointer;">
						<div id="div_act_{$activity_id}" valign="top">
							<a href="index.php?module={$activity.module}&amp;action=DetailView&amp;record={$activity_id}" class="week_activity_item">
								{$activity.imgHTML}
							</a>
							{$activity.subject}
							{$activity.recurrenceImgHTML}
						</div>
					</td>
		{else}
					<td  valign="top" colspan="{$diff}" class="{if $activity.isDuplicate}team_cal_activity_dup{else}team_cal_activity{/if}">
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
		{math equation="(x-y+1)/15" x=$end_offset_min y=$pre_end_offset format="%d" assign=diff}
					<td valign="top" colspan="{$diff}" class="team_cal_empty">
						&nbsp;
					</td>
	{/if}
				</tr>
				
	{* 時間が重複している予定 *}
	{foreach from=$activities_every_user.activities_of_level key=level item=activities}
				<tr>
		{assign var="pre_end_offset" value=$start_offset_min}
		{foreach from=$activities key=activity_id item=activity}
			{* 空白を埋める *}
			{if $pre_end_offset < $activity.startOffsetMinutes}
				{math equation="(x-y+1)/15" x=$activity.startOffsetMinutes y=$pre_end_offset format="%d" assign=diff}
					<td valign="top" colspan="{$diff}" class="team_cal_empty">
						&nbsp;
					</td>
			{/if}

			{* 予定を追加 *}
			{math equation="(x+y)" x=$activity.startOffsetMinutes y=$activity.durationMin format="%d" assign=endOffsetMinutes}
	
			{if $endOffsetMinutes > $end_offset_min}
				{math equation="(x-y+1)/15" x=$end_offset_min y=$activity.startOffsetMinutes format="%d" assign=diff}
				{assign var="pre_end_offset" value=$end_offset_min}
			{else}
				{math equation="(x-y+1)/15" x=$endOffsetMinutes y=$activity.startOffsetMinutes format="%d" assign=diff}
				{assign var="pre_end_offset" value=$endOffsetMinutes}
			{/if}
					
			{if $activity.isViewAble == true}
					<td  valign="top" colspan="{$diff}" class="{if $activity.isDuplicate}team_cal_activity_dup{else}team_cal_activity{/if}" ondblclick="MeetingsEditView.show(this,  '{$activity.module}', '{$activity_id}', '{$user_id}');" style="cursor:pointer;">
						<div valign="top">
							<a href="index.php?module={$activity.module}&amp;action=DetailView&amp;record={$activity_id}" class="week_activity_item">
								{$activity.imgHTML}
							</a>
							{$activity.subject}
							{$activity.recurrenceImgHTML}
						</div>
					</td>
			{else}
					<td  valign="top" colspan="{$diff}" class="{if $activity.isDuplicate}team_cal_activity_dup{else}team_cal_activity{/if}">
						<div valign="top">
							{$activity.imgHTML}
							{$activity.subject}
							{$activity.recurrenceImgHTML}
						</div>
					</td>
			{/if}		
		{/foreach}
			
		{* 一番最後の予定より後ろの部分に空白を埋める *}
		{if $pre_end_offset < $end_offset_min}
			{math equation="(x-y+1)/15" x=$end_offset_min y=$pre_end_offset format="%d" assign=diff}
					<td valign="top" colspan="{$diff}" class="team_cal_empty">
						&nbsp;
					</td>
		{/if}
				</tr>
	{/foreach}
{/foreach}
			</tbody></table>
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

<input id="print_url" type="hidden" value="{$PRINT_LINK}" >
<script type="text/javascript">
{$init_js}
setPrintLink({if $forDashlet}'dashlet_print_{$dashletId}'{/if});
</script>


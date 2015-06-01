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

<div style="">

<table cols="1" border="0" cellpadding="0" cellspacing="0"><tbody>
	<tr>
		<td valign="top" nowrap="nowrap" class="team_cal_info">
			<span class="monthHeaderH3" id="target_date_text">
				{$cal_dt.formattedDate}
			</span>
			<span class="{$weekdayfonts[$cal_dt.localWeekDayIndex]}">({$cal_dt.localWeekDay})</span>
			{if $cal_dt.holiday != ""}
				<span class="week_day_holiday">{$cal_dt.holiday}</span>
			{/if}
			&nbsp;
			<a style="font-weight:bold;border-left:1px solid #CCCCCC;" href="javascript:MeetingsEditView.loadLightUserCal('{$USER_IDS}', '{$cal_dt.localPrevDate}');" class="NextPrevLink">
				&nbsp;<img src="themes/{$THEME}/images/calendar_previous.gif" alt="{$APP.LBL_PREVIOUS_DAY}" title="{$APP.LBL_PREVIOUS_DAY}" align="absmiddle" border="0" height="10" width="6">
			</a>
			&nbsp;
			<a href="javascript:MeetingsEditView.loadLightUserCal('{$USER_IDS}', '{$cal_dt.localNextDate}');" alt="{$MOD.LBL_NEXT_DAY}" class="NextPrevLink">
				<img src="themes/{$THEME}/images/calendar_next.gif" alt="{$APP.LBL_NEXT_DAY}" title="{$APP.LBL_NEXT_DAY}" align="absmiddle" border="0" height="10" width="6">
			</a>
			<a style="font-weight:bold;border-left:1px solid #CCCCCC;border-right:1px solid #CCCCCC;" href="javascript:MeetingsEditView.showParticipantsSchedule();" class="NextPrevLink">&nbsp;{$MTG_MOD.LBL_DAY_MEETING}&nbsp;</a>
		</td>
		
		<td nowrap align="right" class="team_cal_info">
			{$MOD.LBL_APPLY_DATETIME_FROM_TIMEBAR_HELP}
		</td>
	</tr>
	<tr>
		<td valign="top" colspan="2">
			<table class="team_cal" cellpadding="0" cellspacing="0"><tbody>
				<tr>
					<td width="30" nowrap class="team_cal_header" rowspan="2">
						&nbsp;
					</td>
	{foreach from=$hours key=hour item=hour_info}
					<td width="25" colspan="4" align="center" class="team_cal_header">{$hour_info.display}</td>
	{/foreach}
				</tr>
				<tr>
	{foreach from=$hours key=hour item=hour_info}
					<td id="time_bar_{$hour}_0" width="5" style="cursor:pointer;" onclick="MeetingsEditView.applyStartTime('{$cal_dt.localDate}', {$hour},0);" onmouseover="UsersCalendarCtrl.ov(this);" onmouseout="UsersCalendarCtrl.ot(this);" class="user_cal_header_min">&nbsp;</td>
					<td id="time_bar_{$hour}_15" width="5" style="cursor:pointer;" onclick="MeetingsEditView.applyStartTime('{$cal_dt.localDate}', {$hour},15);" onmouseover="UsersCalendarCtrl.ov(this);" onmouseout="UsersCalendarCtrl.ot(this);" class="user_cal_header_min noleft">&nbsp;</td>
					<td id="time_bar_{$hour}_30" width="5" style="cursor:pointer;" onclick="MeetingsEditView.applyStartTime('{$cal_dt.localDate}', {$hour},30);" onmouseover="UsersCalendarCtrl.ov(this);" onmouseout="UsersCalendarCtrl.ot(this);" class="user_cal_header_min">&nbsp;</td>
					<td id="time_bar_{$hour}_45" width="5" style="cursor:pointer;" onclick="MeetingsEditView.applyStartTime('{$cal_dt.localDate}', {$hour},45);" onmouseover="UsersCalendarCtrl.ov(this);" onmouseout="UsersCalendarCtrl.ot(this);" class="user_cal_header_min noleft">&nbsp;</td>
	{/foreach}
				</tr>
{foreach from=$activities_every_user_array key=user_id item=activities_every_user}
				<tr>
					<td nowrap valign="top" class="team_cal_user" rowspan="{$activities_every_user.max_level+1}">
						{$activities_every_user.full_name}
					</td>
	{assign var="pre_end_offset" value=$start_offset_min}
	
	{foreach from=$activities_every_user.activities key=activity_id item=activity}
		{* 空白を追加 *}
		{if $pre_end_offset < $activity.startOffsetMinutes}
			{math equation="(x-(y)+1)/15" x=$activity.startOffsetMinutes y=$pre_end_offset format="%d" assign=diff}
					<td valign="top" colspan="{$diff}" class="light_cal_empty">
						&nbsp;
					</td>
		{/if}
		
		{* 予定を追加 *}
		{math equation="(x+(y))" x=$activity.startOffsetMinutes y=$activity.durationMin format="%d" assign=endOffsetMinutes}

		{if $endOffsetMinutes > $end_offset_min}
			{math equation="(x-(y)+1)/15" x=$end_offset_min y=$activity.startOffsetMinutes format="%d" assign=diff}
			{assign var="pre_end_offset" value=$end_offset_min}
		{else}
			{math equation="(x-(y)+1)/15" x=$endOffsetMinutes y=$activity.startOffsetMinutes format="%d" assign=diff}
			{assign var="pre_end_offset" value=$endOffsetMinutes}
		{/if}
					<td  valign="top" colspan="{$diff}" class="{if $activity.schedule_target == true}light_cal_schedule_target{else}light_cal_activity{/if}">
						&nbsp;
					</td>
	{/foreach}
	
	{* 一番最後の予定より後ろの部分に空白を埋める *}
	{if $pre_end_offset < $end_offset_min}
		{math equation="(x-(y)+1)/15" x=$end_offset_min y=$pre_end_offset format="%d" assign=diff}
					<td valign="top" colspan="{$diff}" class="light_cal_empty">
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
				{math equation="(x-(y)+1)/15" x=$activity.startOffsetMinutes y=$pre_end_offset format="%d" assign=diff}
					<td valign="top" colspan="{$diff}" class="light_cal_empty">
						&nbsp;
					</td>
			{/if}

			{* 予定を追加 *}
			{math equation="(x+(y))" x=$activity.startOffsetMinutes y=$activity.durationMin format="%d" assign=endOffsetMinutes}
	
			{if $endOffsetMinutes > $end_offset_min}
				{math equation="(x-(y)+1)/15" x=$end_offset_min y=$activity.startOffsetMinutes format="%d" assign=diff}
				{assign var="pre_end_offset" value=$end_offset_min}
			{else}
				{math equation="(x-(y)+1)/15" x=$endOffsetMinutes y=$activity.startOffsetMinutes format="%d" assign=diff}
				{assign var="pre_end_offset" value=$endOffsetMinutes}
			{/if}
					<td valign="top" colspan="{$diff}" class="light_cal_activity">
						&nbsp;
					</td>
		{/foreach}
			
		{* 一番最後の予定より後ろの部分に空白を埋める *}
		{if $pre_end_offset < $end_offset_min}
			{math equation="(x-(y)+1)/15" x=$end_offset_min y=$pre_end_offset format="%d" assign=diff}
					<td valign="top" colspan="{$diff}" class="light_cal_empty">
						&nbsp;
					</td>
		{/if}
				</tr>
	{/foreach}
{/foreach}
			</tbody></table>
		</td>
	</tr>
</tbody></table>

<script type="text/javascript">
{$init_js}
</script>

</div>

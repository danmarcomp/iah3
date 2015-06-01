{*

/**
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 */



*}


<form name='configure_{$id}' action="index.php" method="post" onSubmit='return SUGAR.sugarHome.postForm("configure_{$id}", SUGAR.sugarHome.uncoverPage);'>
<input type='hidden' name='id' value='{$id}'>
<input type='hidden' name='module' value='Home'>
<input type='hidden' name='action' value='ConfigureDashlet'>
<input type='hidden' name='to_pdf' value='true'>
<input type='hidden' name='configure' value='true'>
<table width="98%" cellpadding="0" cellspacing="0" border="0" class="tabForm" align="center">
<tr>
    <td valign='top' nowrap class='dataLabel'>{$LBL_DATE_START} <br><i>{$user_date_format}</i></td>
    <td valign='top' class='dataField'>
    	<input onblur="parseDate(this, '{$cal_dateformat}');" class="text" name="mypbss_date_start" size='12' maxlength='10' id='date_start' value='{$date_start}'>
    	<img src="{$image_path}jscalendar.gif" alt="{$LBL_ENTER_DATE}" id="date_start_trigger" align="absmiddle">
    </td>
</tr>
<tr>
    <td valign='top' nowrap class='dataLabel'>{$LBL_DATE_END}<br><i>{$user_date_format}</i></td>
    <td valign='top' class='dataField'>
    	<input onblur="parseDate(this, '{$cal_dateformat}');" class="text" name="mypbss_date_end" size='12' maxlength='10' id='date_end' value='{$date_end}'>
    	<img src="{$image_path}jscalendar.gif" alt="{$LBL_ENTER_DATE}" id="date_end_trigger" align="absmiddle">
    </td>
</tr>

    <tr>
    <td valign='top' class='dataLabel' nowrap>{$LBL_SALES_STAGES}</td>
    <td valign='top' class='dataField'>
    	<select name="mypbss_sales_stages[]" multiple size='5'>
    		{$selected_datax}
    	</select></td>
    </tr>

<tr>
    <td align="right" colspan="2">
        <input type='submit' onclick="" class='button' value='Submit'>
   	</td>
</tr>
</table>
</form>
{literal}
<script type="text/javascript">
Calendar.setup ({
    inputField : "date_start", ifFormat : "{/literal}{$cal_dateformat}{literal}", showsTime : false, button : "date_start_trigger", singleClick : true, step : 1
});
Calendar.setup ({
    inputField : "date_end", ifFormat : "{/literal}{$cal_dateformat}{literal}", showsTime : false, button : "date_end_trigger", singleClick : true, step : 1
});
{/literal}
</script>

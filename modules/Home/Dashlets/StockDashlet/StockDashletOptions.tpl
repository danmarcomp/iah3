<div style='width: 500px'>
<form name='configure_{$id}' action="index.php" method="post" onSubmit='return SUGAR.sugarHome.postForm("configure_{$id}", SUGAR.sugarHome.uncoverPage);'>
<input type='hidden' name='id' value='{$id}'>
<input type='hidden' name='module' value='Home'>
<input type='hidden' name='action' value='ConfigureDashlet'>
<input type='hidden' name='to_pdf' value='true'>
<input type='hidden' name='configure' value='true'>
<table width="100%" cellpadding="0" cellspacing="0" border="0" class="tabForm" align="center">
<tr>
    <td valign='top' nowrap class='dataLabel'>{$TITLE_LABEL}</td>
    <td valign='top' class='dataField'>
    	<input class="input-text" name="title" size='20' value='{$TITLE}'>
    </td>
</tr>
<tr>
    <td valign='top' nowrap class='dataLabel'>{$REFRESH_LABEL}</td>
    <td valign='top' class='dataField'>
    {html_options name=auto_refresh_time options=$REFRESH_OPTIONS selected=$REFRESH_TIME}
    </td>
</tr>
<tr>
    <td valign='top' nowrap class='dataLabel'>{$SYMBOLS_LABEL}</td>
    <td valign='top' class='dataField'>
    	<textarea class="input-textarea" name="symbols" cols="30" rows="2" spellcheck="false">{$SYMBOLS}</textarea>
    </td>
</tr>
<tr>
    <td nowrap class='dataLabel'>{$CHART_LABEL}</td>
    <td valign='top' class='dataField'>
    	<input class="checkbox" type="checkbox" name="display_chart" value='1' {$CHART}>
    </td>
</tr>
<tr>
    <td align="right" colspan="2">
		<input type='hidden' name='resetDashlet' value=''>
		<button type='button' class='input-button input-outer' onclick="SUGAR.sugarHome.hideConfigure();"><div class="input-icon icon-cancel left"></div><span class="input-label">{$cancelLbl}</span></button>
		<button type='button' class='input-button input-outer' onclick="this.form.resetDashlet.value='1'; this.form.onsubmit();"><div class="input-icon icon-delete left"></div><span class="input-label">{$resetLbl}</span></button>
		<button type='submit' class='input-button input-outer'><div class="input-icon icon-accept left"></div><span class="input-label">{$saveLbl}</span></button>
   	</td>
</tr>
</table>
</form>
</div>

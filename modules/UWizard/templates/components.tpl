<p>

        <table cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>
            <th align="left" colspan="2" width="50%">{$LANG.LBL_COMPONENT}</th>
            <th align="left">{$LANG.LBL_STATUS}</th>
          </tr>
          <tr>
          <td>&nbsp;</td>
          </tr>
{foreach from=$result.messages item=message}
          <tr>
			<td></td>
            <td><b>{$message.name}</b></td>
            <td style="color: {$colors[$message.level]}">{$message.message}</td>
          </tr>
{/foreach}
		</table>
</p>


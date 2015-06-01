<form method="POST" action="async.php">
<input type="hidden" name="module" value="ModuleDesigner" />
<input type="hidden" name="action" value="Subpanels" />
<input type="hidden" name="mod_name" value="{$module}" />
<input type="hidden" name="_save" value="1" />

<table width="100%" cellpadding="0" cellspacing="0" border="0" class="listView form-top" >
<thead class="listViewColumns">
<tr class="listHead">
<th class="listViewThS1">&nbsp;</th>
<th class="listViewThS1">{$LANG.LBL_RELATED_MODULE}</th>
<th class="listViewThS1">{$LANG.LBL_SUBPANEL_TITLE}</th>
<th class="listViewThS1">{$LANG.LBL_RELATION_ID}</th>
</tr>
</thead>
<tbody>
{foreach from=$subpanels item=subpanel}
 	<tr class="evenListRowS1">
		<td width="1%" class="listViewTd" >
		<input type="checkbox" name="{$subpanel.name}_visible" {if $subpanel.visible}checked="checked"{/if} value="1" />
		</td>
		<td width="25%" class="listViewTd" >
		{$subpanel.icon}
		{$subpanel.module_name}
		</td>
		<td width="25%" class="listViewTd" >
		<input type="text" name="{$subpanel.name}_title" value="{$subpanel.title|escape}" />
		</td>
		<td width="25%" class="listViewTd" >
		{$subpanel.relationship}
		</td>
 	</tr>
	<tr><td class="listViewHRS1" colspan="12"></td></tr>
{/foreach}
</tbody>
</table>
<div style="height: 6px"></div>
<button onclick="SUGAR.ui.sendForm(this.form);" tabindex="" style="" class="input-button input-outer flatter input-outer" type="button"><div class="input-icon left icon-accept"></div><span class="input-label">{$LANG.LBL_SAVE}</span></button>
</form>




<script type="text/javascript" src="modules/ModuleDesigner/rel.js"></script>
<script type="text/javascript">
ModuleDesignerRel.init('{$module}', {$moduleData});
</script>

<form method="POST" action="async.php" name="RelationsForm">
<input type="hidden" name="module" value="ModuleDesigner" />
<input type="hidden" name="action" value="Relations" />
<input type="hidden" name="mod_name" value="{$module}" />

{$MODULE_SELECTOR}&nbsp;<button id="rel_add_button" onclick="ModuleDesignerRel.add(SUGAR.ui.getFormInput(this.form, 'add_module_name').getValue());" tabindex="" style="display:none" class="input-button input-outer flatter input-outer" type="button"><div class="input-icon left icon-add"></div><span class="input-label">{$LANG.LBL_ADD_RELATIONSHIP}</span></button>
<div style="height: 6px"></div>
<table width="100%" cellpadding="0" cellspacing="0" border="0" class="listView form-top" >
<thead class="listViewColumns">
<tr class="listHead">
<th class="listViewThS1">{$LANG.LBL_RELATED_MODULE}</th>
<th class="listViewThS1">{$LANG.LBL_RELATED_BEAN}</th>
<th class="listViewThS1">{$LANG.LBL_RELATION_ID}</th>
<th class="listViewThS1">&nbsp;</th>
</tr>
</thead>

<tbody id="rels_table">
{foreach from=$relationships item=rel key=idx}
 	<tr class="evenListRowS1">
		<td width="25%" class="listViewTd">
		{$rel.icon}
		{$rel.module_name}
		</td>
		<td width="25%" class="listViewTd">
		{$rel.bean_name}
		</td>
		<td width="25%" class="listViewTd">
		{$rel.name}
		</td>
		<td width="1%" class="listViewTd">
		&nbsp;
		</td>
 	</tr>
	<tr><td class="listViewHRS1" colspan="12"></td></tr>
{/foreach}
</tbody>
</table>
<div style="height: 6px"></div>
<button onclick="ModuleDesignerRel.save(this.form);" tabindex="" style="" class="input-button input-outer flatter input-outer" type="button"><div class="input-icon left icon-accept"></div><span class="input-label">{$LANG.LBL_SAVE}</span></button>
</form>




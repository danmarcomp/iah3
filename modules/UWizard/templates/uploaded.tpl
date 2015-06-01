<p>&nbsp;</p>
<div class="listViewTitle">
<h3>{$LANG.LBL_UPLOADED_PACKAGES}</h3>
</div>
<table cellspacing="0" cellpadding="0" border="0" width="100%" class="listView form-mid">
<thead class="listViewColumns">
	<tr class="listHead">
		<th width="3%" class="listViewThS1">
			<div class="listColLabel">&nbsp;</div>
		</th>
		<th width="35%" class="listViewThS1">
			<div class="listColLabel">{$LANG.LBL_NAME}</div>
		</th>
		<th width="12%" class="listViewThS1">
			<div class="listColLabel">{$LANG.LBL_TYPE}</div>
		</th>
		<th width="11%" class="listViewThS1">
			<div class="listColLabel">{$LANG.LBL_VERSION}</div>
		</th>
		<th width="16%" class="listViewThS1">
			<div class="listColLabel">{$LANG.LBL_DATE_PUBLISHED}</div>
		</th>
		<th width="21%" class="listViewThS1">
			<div class="listColLabel">{$LANG.LBL_AUTHOR}</div>
		</th>
		<th width="21%" class="listViewThS1">
			<div class="listColLabel">{$LANG.LBL_ACTION}</div>
		</th>
	</tr>
</thead>
<tbody>
	{foreach from=$packages item=package}
	<tr class="oddListRowS1">
		<td class="listViewTd">
			{$package.icon}
		</td>
		<td class="listViewTd">
			{$package.name}
		</td>
		<td class="listViewTd">
			{$LISTS.module_types_dom[$package.type]}
		</td>
		<td class="listViewTd">
			{$package.version}
		</td>
		<td class="listViewTd">
			{$package.date}
		</td>
		<td class="listViewTd">
			{$package.author}
		</td>
		<td class="listViewTd" rowspan="3">
			{if $package.installable}
			<form method="post" action="index.php">
			<input type="hidden" name="module" value="UWizard">
			<input type="hidden" name="action" value="index">
			<input type="hidden" name="source" value="{$package.source}">
			<input type="hidden" name="perform" value="prepare_install">
			<input type="hidden" id="conditions_{$package.type}_{$package.id}_{$package.version}" name="conditions" value="">
			<button class="input-button input-outer" type="submit" style="vertical-align: top">
				<div class="input-icon icon-accept left"></div><span class="input-label">{$LANG.LBL_INSTALL}</span>
			</button>
			</form>
			{/if}
			<form method="post" action="index.php">
			<input type="hidden" name="module" value="UWizard">
			<input type="hidden" name="action" value="index">
			<input type="hidden" name="source" value="{$package.source}">
			<input type="hidden" name="perform" value="delete">
			<button class="input-button input-outer" type="submit" onclick="return confirm('Delete this package file?');" style="vertical-align: top">
				<div class="input-icon icon-delete left"></div><span class="input-label">{$LANG.LBL_DELETE}</span>
			</button>
			</form>
		</td>
	</tr>
	<tr class="oddListRowS1">
		<td class="listViewTd" colspan="7">
			<div class="listAddLine">{$package.description}</div>
		</td>
	</tr>
	<tr class="oddListRowS1">
		<td class="listViewTd" colspan="7">
			<div class="listAddLine {$package.class}" style="font-style:italic">{$LANG[$package.status]|default:$package.status}</div>
		</td>
	</tr>
	{if $package.conditions}
	{foreach from=$package.conditions key=condition_name item=condition_label}
	<tr class="oddListRowS1">
		<td class="listViewTd" colspan="1">
			<input onclick="update_condition($('conditions_{$package.type}_{$package.id}_{$package.version}'), '{$condition_name}', this.checked)" type="checkbox" id="condition_{$condition_name}"  value="1" checked="checked" />
		</td>
		<td class="listViewTd" colspan="6">
			<label for="condition_{$condition_name}">{$condition_label}</label>
		</td>
	{/foreach}
	</tr>
	{/if}
	<tr><td colspan="7" class="listViewHRS1"></td></tr>
	{/foreach}
</tbody>
</table>
<script type="text/javascript">
{foreach from=$packages item=package}
{if $package.conditions}
{foreach from=$package.conditions key=condition_name item=condition_label}
$('conditions_{$package.type}_{$package.id}_{$package.version}').value += '{$condition_name}|';
{/foreach}
{/if}
{/foreach}
{literal}
function update_condition(input, name, inc)
{
	var items = input.value.split('|');
	var found = 0;
	for (var i = 0; i < items.length; i++) {
		var item = items[i];
		if (item == name) {
			found = i+1;
		}
	}
	if (found && !inc) {
		items.splice(found - 1, 1);
	} else if (!found && inc) {
		items.push(name);
	}
	input.value = items.join('|');
}
{/literal}
</script>


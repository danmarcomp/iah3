<script type="text/javascript" src="modules/ModuleDesigner/fields.js"></script>

<form method="POST" action="async.php">
<input type="hidden" name="module" value="ModuleDesigner" />
<input type="hidden" name="action" value="EditFields" />
<input type="hidden" name="mod_name" value="{$module}" />

<button onclick="ModuleDesignerFields.newField();" tabindex="" style="" class="input-button input-outer flatter input-outer" type="button"><div class="input-icon left icon-edit"></div><span class="input-label">{$LANG.LBL_CREATE}</span></button>
<button onclick="ModuleDesignerFields.saveAll(this.form);" tabindex="" style="" class="input-button input-outer flatter input-outer" type="button"><div class="input-icon left icon-accept"></div><span class="input-label">{$LANG.LBL_SAVE}</span></button>

<table width="100%" cellpadding="0" cellspacing="0" border="0" class="listView form-top">
<thead class="listViewColumns">
<tr class="listHead">
<th class="listViewThS1">&nbsp;</th>
<th class="listViewThS1">{$LANG.LBL_FIELD_NAME}</th>
<th class="listViewThS1">{$LANG.LBL_FIELD_LABEL}</th>
<th class="listViewThS1">{$LANG.LBL_DATA_TYPE}</th>
</tr>
</thead>
<tbody id="fields_table">
</tbody>
</table>
</form>

<script type="text/javascript">
ModuleDesignerFields.init({$params});
ModuleDesignerFields.render();
</script>


<div id="field_dialog" style="width:100%; display:none">
<table class="tabForm" style="width:100%">
<tbody id="custom_fields_main">
<tr>
<td class="dataLabel">{$LANG.LBL_DATA_TYPE}</td>
<td class="dataField">
{$typeSelect}
</td></tr>

<tr>
<td class="dataLabel">{$LANG.LBL_FIELD_NAME}</td>
<td class="dataField"><input type="text" name="field_name" id="field_name" onchange="ModuleDesignerFields.updateField(this, 'name')"></td>
</tr>

<tr>
<td class="dataLabel">{$LANG.LBL_FIELD_LABEL}</td>
<td class="dataField"><input type="text" name="label" id="label" onchange="ModuleDesignerFields.updateField(this, 'label');"></td>
</tr>

<tr id="audit">
<td class="dataLabel">{$LANG.LBL_AUDIT}</td>
<td class="dataField"><input type="checkbox" name="audited" id="audited" value="1" onclick="ModuleDesignerFields.updateField(this, 'audited');"></td>
</tr>

<tr id="mass_update">
<td class="dataLabel">{$LANG.LBL_MASSUPDATE}</td>
<td class="dataField"><input type="checkbox" name="massupdate" id="massupdate" value="1" onclick="ModuleDesignerFields.updateField(this, 'massupdate');"></td>
</tr>

<tr id="req">
<td class="dataLabel">{$LANG.LBL_REQUIRED}</td>
<td class="dataField"><input type="checkbox" name="required" id="required" value="1" onclick="ModuleDesignerFields.updateField(this, 'required');"></td>
</tr>


</tbody>
<tbody id="custom_fields_body">
</tbody>

<tbody id="custom_fields_formula" style="display: none;">
<tr>
    <td class="dataLabel">&nbsp;</td>
    <td class="dataLabel">
        <table>
            <tr>
            <td class="dataLabel"><div style="font-weight: bold;">{$LANG.LBL_FORMULA_FUNTIONS}</div></td>
            <td class="dataLabel"><div style="font-weight: bold;">{$LANG.LBL_FORMULA_FIELDS}</div></td>
            </tr>
            <tr>
            <td class="dataField">
                <div style="max-height: 100px; min-height: 10px ! important; background: none repeat scroll 0 0 #FAFAFA;  width: 200px;" class="dialog-content" id="le_fields_content">
                {$FUNCTIONS}
                </div>
            </td>
            <td class="dataField">
                <div style="max-height: 100px; min-height: 10px ! important; width: 300px; background: none repeat scroll 0 0 #FAFAFA; overflow-x: hidden;"  class="dialog-content" id="re_fields_content">
                {$FUNCTIONS_FIELDS}
                </div>
            </td>
            </tr>
        </table>
    </td>
</tr>
</tbody>

</table>

<br />
<button onclick="ModuleDesignerFields.saveField();" tabindex="" style="" class="input-button input-outer flatter input-outer" type="button"><div class="input-icon left icon-accept"></div><span class="input-label">{$LANG.LBL_SAVE}</span></button>
<br />
<div id="custom_errors"></div>
</div>


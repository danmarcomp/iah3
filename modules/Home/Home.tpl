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
				

<script type='text/javascript'>
	SUGAR.session.dashboard_id='{$dashboard_id}';
</script>

{if $editLayout}
<table cellspacing='0' cellpadding='0' border='0' valign='top' width='100%'>
	<tr><td align='left'>
		<button type='button' class='input-button input-outer' id='finish_edit' onclick='document.location.replace("?module=Home&action=index&layout={$dashboard_id}");'><div class="input-icon icon-accept"></div><span class="input-label">{$MOD.LBL_FINISH_EDIT}</span></button>
		<button type='button' class='input-button input-outer' id='add_dashlets' onclick='return SUGAR.sugarHome.showDashletsPopup();'><div class="input-icon icon-add"></div><span class="input-label">{$MOD.LBL_ADD_DASHLETS}</span></button>
		<button type='button' class='input-button input-outer' id='set_columns' onclick='SUGAR.popups.showPopup(this, "set_columns_menu", {ldelim}below:1{rdelim});'><div class="input-icon icon-layout"></div><span class="input-label">{$MOD.LBL_SET_COLUMNS}</span></button>
		<div class="menu" id="set_columns_menu">
			{section name=cols start=1 loop=5}
			{assign var=lbl value="LBL_COLUMNS_`$smarty.section.cols.index`"}
			<a href="#" onclick="SUGAR.popups.hidePopup(); SUGAR.sugarHome.setColumns({$smarty.section.cols.index}); return false;" onmouseover="SUGAR.popups.hiliteItem(this, true);" onmouseout="SUGAR.popups.unhiliteItem(this);" class="menuItem">{$MOD.$lbl}</a>
			{/section}
		</div>
	</td>
</table>
{/if}

{assign var=colCount value=$columns|@count}
<table id="dashboard_columns" cellspacing='0' cellpadding='0' border='0' valign='top' width='100%' cols="{$colCount}" {if $editLayout}style="border-collapse: collapse; empty-cells: show; margin-top: 1.0em"{/if}>
	<tr>
		{counter assign=hiddenCounter start=0 print=false}
		{foreach from=$columns key=colNum item=data}
		{if $colNum || $editLayout}{assign var=leftpad value='4px'}{else}{assign var=leftpad value='0px'}{/if}
		{if $colNum < $colCount-1 || $editLayout}{assign var=rightpad value='4px'}{else}{assign var=rightpad value='0px'}{/if}
		<td valign='top' width="{$data.width}" style="padding: 0 {$rightpad} 0 {$leftpad}" {if $editLayout}class="visible-column"{/if}>
			{if $colNum && $editLayout}
				<div id="colSlider{$colNum}" class="sliderTab" style="cursor: pointer; margin-left: -13px; margin-top: -8px; margin-bottom: -3px; width: 17px; height: 21px; background: url(include/javascript/yui/build/slider/assets/thumb-s.gif);">&nbsp;</div>
			{elseif $editLayout}
				<div class="sliderTabPad" style="width: 1px; height: 10px; line-height: 1px">&nbsp;</div>
			{/if}
			<ul class='noBullet' id='col{$colNum}' {if $editLayout}style="min-height: 150px; min-width: 50px"{/if}>
		        {foreach from=$data.dashlets key=id item=dashlet}		
				<script type="text/javascript">
					SUGAR.sugarHome.setDashletWidth('{$id}', '{$data.width}');
				</script>
				<li class='noBullet' id='dashlet_{$id}'>
					<div id='dashlet_entire_{$id}'>
						{$dashlet.script}
						{$dashlet.display}
					</div>
				</li>
				{/foreach}
			</ul>
		</td>
		{counter}
		{/foreach}
	</tr>
</table>

{if $editLayout}
<div class="sliderTabShim" id="slider_proxy" style="visibility: hidden; position: absolute; z-index: 100; width: 8px; border-right: 1px dashed red">
	<div class="sliderTab" style="width: 17px; height: 21px; cursor: pointer; background: url(include/javascript/yui/build/slider/assets/thumb-s.gif);">&nbsp;</div>
</div>
<div class="sliderLabel" id="slider_label1" style="opacity: 0.8; width: 4em; height: 1.5em; text-align: center; border: 1px solid orange; background: #ee0; color: #222; position: absolute; display: none"></div>
<div class="sliderLabel" id="slider_label2" style="opacity: 0.8; width: 4em; height: 1.5em; text-align: center; border: 1px solid orange; background: #ee0; color: #222; position: absolute; display: none"></div>
{/if}



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


<div class="dashlet-body">
<div id='jotpad_{$id}' ondblclick='JotPad.edit(this, "{$id}")' class='textarea' style='overflow: auto; height: {$height}px;'>{$savedText}</div>
<div id="jotpad_textouter_{$id}" style="display: none; margin-right: 6px; padding: 0">
<textarea id="jotpad_textarea_{$id}" rows="5" onblur='JotPad.blur(this, "{$id}")' style="margin: 0; width: 100%; height: {$height}px; border: none; padding: 0.25em"></textarea>
</div>
</div>
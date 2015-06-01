<!--
/*********************************************************************************
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
 ********************************************************************************/
/*********************************************************************************

 ********************************************************************************/
-->

<table cellpadding="0" cellspacing="0" width="100%" border="0" class="listView">
	
	<tr height="20">
	<td scope="col" width="1%" class="listViewThS1">{$CHECKALL}&nbsp;</td>
		<td scope="col" width="20%" class="listViewThS1" nowrap><slot>{$MOD.LBL_NAME}</slot></td>
		<td scope="col" width="10%" class="listViewThS1" nowrap><slot>{$MOD.LBL_DESCRIPTION}</slot></td>
	  </tr>

{foreach from=$ROLES item="ROLE"}

<tr height="20" >
    			<td>{$PREROW}&nbsp;</td>
    			<td valign=TOP  ><slot><a href="#" onclick="send_back('Users','{$ROLE.id}');">{$ROLE.name}</a></slot></td>
    			<td valign=TOP  ><slot>{$ROLE.description}</slot></td>

</tr>
{foreachelse}
        <tr>
            <td colspan="2">No Roles</td>
        </tr>
{/foreach}
<tr><td colspan="20" class="listViewHRS1"></td></tr>


</table>
{$ASSOCIATED_JAVASCRIPT_DATA}

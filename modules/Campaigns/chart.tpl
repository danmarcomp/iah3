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


<!-- BEGIN: main -->
<graphData title="{GRAPHTITLE}">

        <yData defaultAltText="{Y_DEFAULT_ALT_TEXT}">
                <!-- BEGIN: row -->
                <dataRow title="{Y_ROW_TITLE}" endLabel="{Y_ROW_ENDLABEl}">
                        <!-- BEGIN: bar -->
                        <bar id="{Y_BAR_ID}" totalSize="{Y_BAR_SIZE}" altText="{Y_BAR_ALTTEXT}" url="{Y_BAR_URL}"/>
                        <!-- END: bar -->
                </dataRow>
                <!-- END: row -->
        </yData>
        <xData min="{XMIN}" max="{XMAX}" length="{XLENGTH}" kDelim="{XKDELIM}" prefix="{XPREFIX}" suffix="{XSUFFIX}"/>
        <colorLegend status="on">
                <mapping id="'.$outcome.'" name="'.$outcome_translation.'" color="'.$color.'"/>
        </colorLegend>
        <graphInfo><![CDATA[{GRAPH_DATA}]]></graphInfo>
        <chartColors {COLOR_DEFS}/>
</graphData>
<!-- END: main -->

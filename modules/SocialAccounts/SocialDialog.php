<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */
require_once('modules/SocialAccounts/SocialAccount.php');
require_once('include/layout/forms/EditableForm.php');

class SocialDialog {

    /**
     * @var string
     */
    private $network;

    /**
     * @var string
     */
    private $related_type;

    /**
     * @var string
     */
    private $related_id;

    const FORM_ID = 'AddSocialForm';

    /**
     * @param string $network
     * @param string $related_type
     * @param string $related_id
     */
    public function __construct($network, $related_type, $related_id) {
        $this->network = $network;
        $this->related_type = $related_type;
        $this->related_id = $related_id;
    }

    /**
     * Render dialog
     *
     * @return string
     */
    public function render() {
        $social_account = new SocialAccount();
        $content = '';

        if ($social_account->networkSupported($this->network)) {
            $form = new EditableForm('editview', self::FORM_ID);
            $networks = SocialAccount::getSupportedNetworks();

            $label = translate('LBL_SOCIAL_ENTER');
            $vmod = array_get_default($networks[$this->network], 'vname_module', 'SocialAccounts');
            $label .= ' '  .$social_account->getIcon($networks[$this->network]['icon'], SocialAccount::ICON_SMALL_SIZE). '&nbsp;' . translate($networks[$this->network]['vname'], $vmod) . ' ' .translate('LBL_SOCIAL_PROFILE_URL'). ':';
            $save_label = translate('LBL_SAVE_BUTTON_LABEL');
            $cancel_label = translate('LBL_CANCEL_BUTTON_LABEL');
            $form_id = self::FORM_ID;

            $content = <<<EOQ
                <form action="#" method="POST" id="{$form_id}" onsubmit='SocialAccount.add("{$form_id}"); return false;' autocomplete="off">
                <input type="hidden" name="network" value="{$this->network}" />
                <input type="hidden" name="related_type" value="{$this->related_type}" />
                <input type="hidden" name="related_id" value="{$this->related_id}" />
                <table width="100%" cellspacing="5" cellpadding="5" border="0" class="tabForm">
                    <tr>
                        <td class="dataField">
                        <p class="topLabel">{$label}</p>
                        {$this->renderTextInput($form, 'profile_url', '', array('width' => AppConfig::is_mobile() ? 30 : 60, 'type' => 'url'))}
                    </tr>
                    <tr>
                        <td width="100%" class="dataLabel"><div id="social_error_msg" class="error">&nbsp;</div></td>
                    </tr>
                    <tr>
                        <td width="100%" class="dataLabel">
                        <button type='submit' class='input-button input-outer'><div class="input-icon icon-accept left"></div><span class="input-label">{$save_label}</span></button>
                        &nbsp;
                        <button class="input-button input-outer" onclick="return SUGAR.popups.close();" type="button" name="button"><div class="input-icon icon-cancel left"></div><span class="input-label">{$cancel_label}</span></button>
                        </td>
                    </tr>
                </table>
                </form>
EOQ;
			$form->exportIncludes();
        }

        return $content;
    }

    /**
     * Render text input
     *
     * @param EditableForm $form
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return string
     */
    private function renderTextInput($form, $name, $value, $params = array()) {
        $spec = array('name' => $name) + $params;
        return $form->renderText($spec, $value);
    }
}
?>
<?php

require_once('include/layout/forms/FormField.php');

class CampaignNameWidget extends FormField {

	function init($params=null, $model=null) {
		parent::init($params, $model);
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		$value = '';
        $campaign = null;
        $campaign_id = $row_result->getField('campaign_id');

        if($campaign_id)
            $campaign = $this->loadCampaign($campaign_id);

        if($this->getEditable($context)) {
            $spec = array_get_default($row_result->fields, 'campaign', array());
            $value = $this->renderEditHtml($gen, $campaign, $spec);
        } else {
            if ($campaign)
                $value = $this->renderViewHtml($campaign);
        }

		return $value;
	}

    function renderViewHtml(RowResult $campaign) {
        global $image_path;
        $display_value = $campaign->getField('name');
        $detail_link = get_detail_link($campaign->getField('id'), 'Campaigns');

        if (! AppConfig::is_mobile() || AppConfig::is_mobile_module('Campaigns'))
            $detail_link = '<a class="tabDetailViewDFLink" href="'.to_html($detail_link).'">'.$display_value.'</a>';
        else
            $detail_link = $display_value;

        return  get_image($image_path . 'Campaigns', 'style="vertical-align: text-top"') . '&nbsp;' . $detail_link;
    }

    /**
     * @param HtmlFormGenerator $gen
     * @param RowResult|null $campaign
     * @param array $spec
     * @return string
     */
    function renderEditHtml(HtmlFormGenerator &$gen, $campaign, $spec) {
        $html = '';

        if (sizeof($spec > 0)) {
            $spec['icon'] = 'theme-icon module-Campaigns';
            if ($campaign && $campaign->getField('name')) {
                $spec['value'] = $campaign->getField('name');
                $spec['id_value'] = $campaign->getField('id');
            } else {
                $campaign = new RowResult();
            }

            $html = $gen->getFormObject()->renderRef($campaign, $spec);
        }

        return $html;
    }

    function loadCampaign($id) {
        return ListQuery::quick_fetch('Campaign', $id, array('name'));
    }
}

?>
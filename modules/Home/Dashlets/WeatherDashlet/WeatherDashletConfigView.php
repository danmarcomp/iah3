<?php
require_once('include/layout/forms/EditableForm.php');
require_once('include/layout/widgets/DynamicListInput.php');

class WeatherDashletConfigView {

    /**
     * @var WeatherDashlet
     */
    private $dashlet;

    /**
     * @var EditableForm
     */
    private $form;

    const FORM_ID = 'weather_edit_form';

    /**
     * @param WeatherDashlet $dashlet
     */
    public function __construct(WeatherDashlet $dashlet) {
        $this->dashlet = $dashlet;
        $this->form = new EditableForm('editview', 'configure_' . $this->dashlet->id, self::FORM_ID);
    }

    /**
     * @return string
     */
    public function getTemplate() {
        $template = $this->getFormattedHtml();
        $this->form->exportIncludes();
        return $template;
    }

    /**
     * @return string
     */
    private function getFormattedHtml() {
        global $mod_strings, $app_strings;

        $fahrenheit = WeatherDashlet::FAHRENHEIT;
        $celsius = WeatherDashlet::CELSIUS;
        $form_id = self::FORM_ID;

        if ($this->dashlet->degreesUnits == WeatherDashlet::FAHRENHEIT) {
            $check_fahrenheit = 'checked="checked"';
            $check_celsius = '';
        } else {
            $check_fahrenheit = 'checked="checked"';
            $check_celsius = 'checked="checked"';
        }

        $title_spec = array('name' => 'title', 'size' => 25);

        $auto_refresh_spec = array(
            'name' => 'auto_refresh_time',
            'options' => 'dashlet_auto_refresh_dom',
            'options_add_blank' => false
        );

        $check_show_time = false;
        if ($this->dashlet->showTimes)
            $check_show_time = true;
        $show_time_spec = array('name' => 'show_times');

        $add_city_spec = array('name' => 'add_city_name', 'size' => 25, 'autocomplete' => 'off',
            'onkeypress' => "if(event.keyCode == 13) {Weather.searchLocations('{$this->dashlet->id}'); return false;}");

        $result = <<<EOQ
            <div style='width: 500px'>
            <form name='configure_{$this->dashlet->id}' id="{$form_id}" action="index.php" method="post" onSubmit="return Weather.submit('{$this->dashlet->id}');">
            <input type='hidden' name='id' value='{$this->dashlet->id}'>
            <input type='hidden' name='module' value='Home'>
            <input type='hidden' name='action' value='ConfigureDashlet'>
            <input type='hidden' name='to_pdf' value='true'>
            <input type='hidden' name='configure' value='true'>
            <table width="100%" cellpadding="0" cellspacing="0" border="0" class="tabForm" align="center">
            <tr>
                <td valign='top' nowrap class='dataLabel'>{$this->dashlet->dashletStrings['LBL_CONFIGURE_TITLE']}</td>
                <td valign='top' class='dataField'>
                {$this->form->renderText($title_spec, $this->dashlet->title)}
                </td>
            </tr>
            <tr>
                <td valign='top' nowrap class='dataLabel'>{$mod_strings['LBL_DASHLET_CONFIGURE_AUTOREFRESH']}</td>
                <td valign='top' class='dataField'>
                {$this->form->renderSelect($auto_refresh_spec, $this->dashlet->autoRefreshTime)}
                </td>
            </tr>
            <tr>
                <td valign='top' nowrap class='dataLabel'>{$this->dashlet->dashletStrings['LBL_DEGREES_UNITS']}</td>
                <td valign='top' class='dataField'>
                    <label><input type="radio" class="radio" name="degrees_units" value="{$fahrenheit}" {$check_fahrenheit} />&nbsp;&deg;F</label>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <label><input type="radio" class="radio" name="degrees_units" value="{$celsius}" {$check_celsius} />&nbsp;&deg;C</label>
                </td>
            </tr>
            <tr>
                <td valign='top' nowrap class='dataLabel'>{$this->dashlet->dashletStrings['LBL_DISPLAY_TIME']}</td>
                <td valign='top' class='dataField'>
                {$this->form->renderCheck($show_time_spec, $check_show_time)}
                </td>
            </tr>
            <tr>
            <td class="dataLabel">{$this->dashlet->dashletStrings['LBL_CURRENT_CITIES']}</td>
            <td class="dataField" style='padding-bottom: 10px;'>
                <table border="0" cellpadding="1" cellspacing="0" id="exist_cities" width="100%" align="center">
                <tr id="city_row"><td colspan="2">{$this->getCitiesTable()}</td></tr>
                </table>
            </td>
            </tr>
            <tr>
                <td valign='top' nowrap class='dataLabel'>{$this->dashlet->dashletStrings['LBL_ADD_CITY']}:</td>
                <td valign='top' class='dataField'>
                    {$this->form->renderText($add_city_spec, '')}
                    &nbsp;&nbsp;<a href="#" onclick="Weather.searchLocations('{$this->dashlet->id}'); return false;">{$this->dashlet->dashletStrings['LBL_SEARCH']}</a>
                    <div id="add_city_data" style="padding-top: 10px;"></div>
                    <div id="add_city_data" style="padding-top: 10px;"></div>
                    <div id="add_city_but" style="display: none; padding-top: 10px; padding-bottom: 10px;"><a href="#" onclick="Weather.addCity('{$this->dashlet->id}'); return false;">{$this->dashlet->dashletStrings['LBL_ADD_CITY']}</a>&nbsp;&nbsp;<a href="#" onclick="Weather.clearCities(); return false;">{$this->dashlet->dashletStrings['LBL_CLEAR']}</a></div>
                </td>
            </tr>
            <tr>
                <td align="right" colspan="2">
                	<input type='hidden' name='resetDashlet' value=''>
					<button type='button' class='input-button input-outer' onclick="SUGAR.sugarHome.hideConfigure();"><div class="input-icon icon-cancel left"></div><span class="input-label">{$app_strings['LBL_CANCEL_BUTTON_LABEL']}</span></button>
					<button type='button' class='input-button input-outer' onclick="this.form.resetDashlet.value='1'; this.form.onsubmit();"><div class="input-icon icon-delete left"></div><span class="input-label">{$app_strings['LBL_RESET_BUTTON_LABEL']}</span></button>
                    <button type='submit' class='input-button input-outer'><div class="input-icon icon-accept left"></div><span class="input-label">{$app_strings["LBL_SAVE_BUTTON_LABEL"]}</span></button>
                </td>
            </tr>
            </table>
            </form>
            </div>
EOQ;

        return $result;
    }

    /**
     * Get table with cities list
     *
     * @return string
     */
    private function getCitiesTable() {
        $attrs = array(
            'name' => 'cities',
            'cols' => array(
                array(
                    'name' => 'name',
                    'label' => $this->dashlet->dashletStrings['LBL_CITY'],
                    'width' => '50%',
                    'editable' => true,
                    'size' => 30
                ),
                array(
                    'name' => 'woeid',
                    'label' => $this->dashlet->dashletStrings['LBL_WOEID'],
                    'width' => '50%',
                    'editable' => false,
                )
            ),
            'width' => '350',
            'depth_attrib' => '',
            'show_delete' => true
        );

        $dom_data = new DynamicListInput($attrs);
        $dom_data->exportIncludes();
        return $dom_data->render($this->form, $this->dashlet->cities);
    }

}

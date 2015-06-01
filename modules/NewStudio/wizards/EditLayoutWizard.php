<?php

require_once 'modules/NewStudio/wizards/StudioWizardBase.php';

class EditLayoutWizard extends StudioWizardBase
{
	public function render()
	{
		if (!empty($this->params['edit_module'])) return $this->renderViews();
		return $this->renderModules();
	}
	
	
	private function renderModules()
	{
		global $mod_strings;
		$sep = translate('LBL_SEPARATOR', 'app');
		echo get_module_title('Administration', $mod_strings['LBL_MODULE_TITLE'] . $sep . $mod_strings['LBL_EDIT_LAYOUT'] . $sep . $mod_strings['LBL_SELECT_MODULE'], false);
		$beans = AppConfig::setting('modinfo.primary_beans');
		global $app_list_strings;
		$modules = AppConfig::setting('modinfo.index.normal');
		$modules = array_merge($modules, AppConfig::setting('modinfo.index.manual'));
		asort($modules);
		$i = 0;
		echo "<table border='0' cellpadding='0' cellspacing='8' width='700'>";
		foreach ($modules as $mod) {
			if (!isset($beans[$mod])) continue;
			$bean = $beans[$mod];
			if (($i & 3) == 0) {
				echo "<tr>";
			}
			$modName = array_get_default($app_list_strings['moduleList'], $mod, $mod);
			$icon = get_image($bean, '');
			echo "<td>$icon <a href=\"index.php?module=NewStudio&wizard=EditLayout&edit_module=$mod\" class=\"body\">$modName</a>&nbsp;&nbsp;</td>";
			if (($i & 3) == 3) {
				echo "</tr>";
			}
			$i++;
		}
		if (($i & 3) != 3) {
			while ($i & 3) {
				echo "<td>&nbsp;</td>";
				$i++;
			}
			echo "</tr>";
		}
		echo "</table>";
	}

	private function renderViews()
	{
		global $mod_strings;
		global $app_list_strings;
		$mod = $this->params['edit_module'];
		$modName = array_get_default($app_list_strings['moduleList'], $mod, $mod);
		$bean = AppConfig::module_primary_bean($mod);
		$sep = translate('LBL_SEPARATOR', 'app');
		echo get_module_title('Administration', $mod_strings['LBL_MODULE_TITLE'] . $sep . $mod_strings['LBL_EDIT_LAYOUT'] . $sep . $modName . $sep . $mod_strings['LBL_SELECT_VIEW'], false);
		$layouts = array('view' => array(), 'edit' => array());
		$types = array(
			'view', 'edit'
		);
		foreach ($types as $type) {
			$detail = AppConfig::setting("views.detail.{$mod}.{$type}");
			foreach ($detail as $layout => $def) {
				if ($layout == 'wireless' || $layout == 'Mobile')
					continue;
				$vname = AppConfig::setting("display.$type.$bean.layouts.$layout.vname");
				if (empty($vname)) {
					$vname = array_get_default($def, "title");
				}
				$title = translate($vname, $mod);
				$layouts[$type][] = array($layout, $title);
			}
		}
		$count = max(count($layouts['view']), count($layouts['edit']));

		echo '<table>';
		echo "<tr><td>{$mod_strings['LBL_VIEW_LAYOUTS']}</td>";
		echo "<td>&nbsp;</td>";
		echo "<td>{$mod_strings['LBL_EDIT_LAYOUTS']}</td></tr>";
		for ($i = 0; $i < $count; $i++) {
			echo '<tr>';
			if (isset($layouts['view'][$i])) {
				$html = "<a href=\"index.php?module=$mod&action=DetailView&record_perform=edit_layout_view&layout={$layouts['view'][$i][0]}\">";
				$html .= "{$layouts['view'][$i][1]} [{$layouts['view'][$i][0]}]";
				$html .= "</a>";
			} else {
				$html = '&nbsp';
			}
			echo "<td>$html</td>";
			echo "<td>&nbsp;</td>";
			if (isset($layouts['edit'][$i])) {
				$html = "<a href=\"index.php?module=$mod&action=DetailView&record_perform=edit_layout_edit&layout={$layouts['edit'][$i][0]}\">";
				$html .= "{$layouts['edit'][$i][1]} [{$layouts['edit'][$i][0]}]";
				$html .= "</a>";
			} else {
				$html = '&nbsp';
			}
			echo "<td>$html</td>";
		}
		echo '</tr></table>';
	}
}


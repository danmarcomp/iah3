<?php

abstract class StudioWizardBase
{
	var $params;

	public function __construct($params)
	{
		$this->params = $params;
	}

	public function process() {}
	abstract public function render();

}

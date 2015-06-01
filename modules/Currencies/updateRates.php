<?php

if (!defined('inScheduler')) die('Unauthorized access');
require_once 'modules/Currencies/CurrencyUtils.php';

CurrencyUtils::updateRates();


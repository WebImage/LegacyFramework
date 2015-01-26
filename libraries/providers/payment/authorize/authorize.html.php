<?php

$order			= $this->getOrder();

/**
 * Configure Form Credit Card Types
 */
 
$auth_card_type		= Page::getControlById('auth_card_type');

$config_card_types	= 'MC=>Master Card;V=>Visa;AMX=>American Express;';
$config_cards = explode(';', $config_card_types);

$result_card_types = new ResultSet();

foreach($config_cards as $config_card) {

	$id_name	= explode('=>', $config_card);
	$tmp_id		= $id_name[0];
	
	if (isset($id_name[1])) $tmp_name = $id_name[1];	
	else $tmp_name = $id_name[0];
	if (strlen($tmp_id) > 0 || strlen($tmp_name) > 0) {
		$tmp_card	= new stdClass();
		$tmp_card->id	= $tmp_id;
		$tmp_card->name	= $tmp_name;
		$result_card_types->add($tmp_card);
	}
}

$auth_card_type->setData($result_card_types);

/**
 * Configure Form Credit Card Month/Date
 */

$auth_card_exp_month	= Page::getControlById('auth_card_exp_month');
$auth_card_exp_year	= Page::getControlById('auth_card_exp_year');

$result_months		= new ResultSet();
$result_years		= new ResultSet();

$months			= array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
$year_start		= date('Y');
$year_end		= date('Y') + 10;

for ($m=1; $m <= 12; $m++) {
	$tmp_month	= new stdClass();
	$tmp_month->id	= sprintf("%02d", $m);
	$tmp_month->name= sprintf("%02d", $m);
	$result_months->add($tmp_month);
}

for ($y=$year_start; $y <= $year_end; $y++) {
	$tmp_year	= new stdClass();
	$tmp_year->id	= $y;
	$tmp_year->name	= $y;
	$result_years->add($tmp_year);
}

$auth_card_exp_month->setData($result_months);
$auth_card_exp_year->setData($result_years);

?>